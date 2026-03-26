<?php

namespace App\Jobs\Report;
use App\Jobs\BaseJob;
use App\Models\ReportLogModel;
use App\Services\AnalysisMovieService;
use App\Services\CommonService;
use App\Services\MmsService;
use App\Services\ReportService;
use App\Services\UserService;
use App\Utils\LogUtil;

/**
 * 上报总管理系统
 * Class ReportMmsJob
 * @property ReportService $reportService
 * @property ReportLogModel $reportLogModel
 * @property MmsService $mmsService
 * @property UserService $userService
 * @property CommonService $commonService
 * @package App\Jobs\Report
 */
class ReportMmsJob extends BaseJob
{
    public function handler($uniqid)
    {
        // TODO: Implement handler() method.
        $result = array();
        //最近三天总数
        $items = $this->reportLogModel->find(array(
            'type' => 'user_total'
        ), array(), array('created_at' => -1), 0, 2);
        foreach ($items as $item) {
            $result['total_user'][] = array(
                'date' => $item['date'],
                'num' => $item['value'] * 1
            );
        }
        //最近三天总人数
        $items = $this->reportLogModel->find(array(
            'type' => 'user_reg'
        ), array(), array('created_at' => -1), 0, 2);
        foreach ($items as $item) {
            $result['user_reg'][] = array(
                'date' => $item['date'],
                'num' => $item['value'] * 1
            );
        }
        //最近三天日活
        $items = $this->reportLogModel->find(array(
            'type' => 'app_day'
        ), array(), array('created_at' => -1), 0, 2);
        foreach ($items as $item) {
            $result['app_day'][] = array(
                'date' => $item['date'],
                'num' => $item['value'] * 1
            );
        }
        try {
            $this->mmsService->doReport($result);
            LogUtil::info('Do report ok!');
        } catch (\Exception $exception) {
            LogUtil::info($exception->getMessage());
        }

        //一小时分钟上报一次
        if(date("i")>=0&&date("i")<5){
            $this->analysis(date("Y-m-d"));
        }
    }

    /**
     * 集团-统计
     * @param $date
     */
    public function analysis($date)
    {
        //当天视频注册人数
        $userReg = $this->reportLogModel->findFirst(['type' => 'user_reg','date'=>$date]);
        //当天绑定手机人数
        $userBindNum = $this->commonService->getRedis()->sCard("user_bind_{$date}");
        //用户总数
        $userTotalNum = $this->reportLogModel->findFirst(['type' => 'user_total','date'=>$date]);
        //日活总数
        $userDayNum = $this->reportLogModel->findFirst(['type' => 'app_day','date'=>$date]);
        //当日观看影片人数
        $userWatchNum = $this->commonService->getRedis()->sCard("movie_play_{$date}");

        $result = [
            'date_label'    => strval($date),
            'user_reg_num'  => strval($userReg['value']),
            'user_bind_num' => strval($userBindNum),
            'user_total_num'=> strval($userTotalNum['value']),
            'user_day_num'  => strval($userDayNum['value']),
            'user_watch_num'=> strval($userWatchNum),
        ];
        for ($i=1;$i<=10;$i++){
            $item = $this->doUR($i,strtotime($date));
            $result["user_reg_num_".$i]=strval($item['reg']);
            $result["user_day_num_".$i]=strval($item['active']);
        }
        try {
            $this->mmsService->doAnalysis($result);
            LogUtil::info('Do report analysis ok!');
        } catch (\Exception $exception) {
            LogUtil::info($exception->getMessage());
        }

    }

    /**
     * 统计留存率
     * @param $day
     * @param int $startTime 基准时间
     */
    private function doUR($day,$startTime)
    {
        // TODO:次日留存率、三日留存率、周留存率、半月留存率和月留存率
        $date   = date("Y-m-d",$startTime-3600*24*$day);

        //指定日期注册用户,基准时间是否活跃
        $total = $this->userService->count(['register_date'=>$date]);
        $activeTotal = $this->userService->count(['register_date'=>$date,'last_date'=>date("Y-m-d",$startTime)]);
        LogUtil::info("regDate:{$date} dayDate:".date('Y-m-d',$startTime)." reg:{$total} active:{$activeTotal}");
        return [
            'reg' => $total,
            'active'=>$activeTotal,
        ];
    }



    public function success($uniqid)
    {
        // TODO: Implement success() method.
    }

    public function error($uniqid)
    {
        // TODO: Implement error() method.
    }

}