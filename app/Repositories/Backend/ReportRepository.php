<?php

declare(strict_types=1);

namespace App\Repositories\Backend;


use App\Constants\CommonValues;
use App\Core\Repositories\BaseRepository;
use App\Services\ReportHourLogService;
use App\Services\ReportService;
use App\Utils\LogUtil;

/**
 * report
 * @package App\Repositories\Backend
 * @property ReportHourLogService $reportHourLogService
 * @property  ReportService $reportService
 */
class ReportRepository extends BaseRepository
{
    /**
     *系统统计数据
     */
    public function getReportData()
    {
        $result = [];
        //地区top 15
        $items = [];
        $areaTopData = $this->reportService->getAreaList([], [], ['num' => -1], 0, 15);
        foreach ($areaTopData as $item) {
            $item['num'] = (formatNum($item['num'], 2));
            $items[] = $item;
            unset($item);
        }
        $result['top_area'] = $items;

        //日活
        $items = [];
        $query = ['type' => 'app_day'];
        $appDayData = $this->reportService->getList($query, [], ['date' => -1], 0, 15);
        foreach ($appDayData as $item) {
            $item['value'] = (formatNum($item['value'], 2));
            $items[] = $item;
            unset($item);
        }
        $result['app_day'] = $items;

        //注册
        $items = [];
        $query = ['type' => 'user_reg'];
        $appRegData = $this->reportService->getList($query, [], ['date' => -1], 0, 15);
        foreach ($appRegData as $item) {
            $item['value'] = (formatNum($item['value'], 2));
            $items[] = $item;
            unset($item);
        }
        $result['user_reg'] = $items;


        //充值
        $result['money'] = $this->getOrderItems('money', 15);

        //设备类型 IOS
        $query = ['type' => 'device_type_h5','date'=>date('Y-m-d')];
        $appIosData = $this->reportService->findFirst($query, []);
        $appIosData['value'] = (formatNum($appIosData['value'], 2));
        $result['device_type_h5'] = $appIosData;
        //设备类型 android
        $query = ['type' => 'device_type_android','date'=>date('Y-m-d')];
        $appAndroidData = $this->reportService->findFirst($query, []);
        $appAndroidData['value'] = (formatNum($appAndroidData['value'], 2));
        $result['device_type_android'] = $appAndroidData;


        //用户总数 user_total
        $query = ['type' => 'user_total'];
        $userTotalData = $this->reportService->getList($query, [],array('updated_at' => -1),0,1);
        $userTotalData = $userTotalData[0];
        $userTotalData['value'] = (formatNum($userTotalData['value'], 2));
        $result['user_total'] = $userTotalData;

        //购买的用户 user_total
        $query = ['type' => 'user_total_has_buy'];
        $userTotalHasBuyData = $this->reportService->getList($query, [],array('updated_at' => -1),0,1);
        $userTotalHasBuyData = $userTotalHasBuyData[0];
        $userTotalHasBuyData['value'] = (formatNum($userTotalHasBuyData['value'], 2));
        $result['user_total_has_buy'] = $userTotalHasBuyData;

        //留存
        $result['ur'] = $this->getRRItems(15);

        //网站统计数据
        $result['web_log'] = $this->getWebLog(15);

        return $result;
    }

    public function getHour($request = [])
    {
        $page = $this->getRequest($request, 'page', 'int', 1);
        $pageSize = $this->getRequest($request, 'pageSize', 'int', 10);
        $sort     = $this->getRequest($request, 'sort', 'string', 'date');
        $order    = $this->getRequest($request, 'order', 'int', -1);

        $query = array();
        $filter = array();


        if ($request['month']) {
            $filter['month'] = $this->getRequest($request, 'month');
            $query['month'] = $filter['month'];
        }
        $query['pid'] = $this->getRequest($request, 'pid','string','0');

        $skip = ($page - 1) * $pageSize;
        $fields = array();
        $count = $this->reportHourLogService->count($query);
        $items = $this->reportHourLogService->getList($query, $fields,array(($query['pid']==0?$sort:'date_limit') => ($query['pid']==0?$order:1)), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['dau']        = strval(round($item['dau']/100,2));
            $item['dau_android']= strval(round($item['dau_android']/100,2));
            $item['dau_h5']    = strval(round($item['dau_h5']/100,2));
            $item['reg']        = strval(number_format($item['reg']/100,2));
            $item['reg_android']= strval(number_format($item['reg_android']/100,2));
            $item['reg_h5']    = strval(number_format($item['reg_h5']/100,2));
            $item['order']      = strval(number_format($item['order']/100,2));
            $item['order_success']      = strval(number_format($item['order_success']/100,2));
            $item['order_money']= strval(number_format($item['order_money']/100,2));
            $item['tav']        = strval(number_format($item['tav']/100,2));
            $item['apr']        = strval($item['apr'].'%');
            $item['payr']       = strval($item['payr'].'%');
            $item['arpu']       = strval(number_format($item['arpu']/100,2));
            $item['haveChild']  = $this->reportHourLogService->count(['pid'=>$item['_id']])>0?true:false;
            $item['date']       = $item['pid']=='0'?$item['date']:$item['date_limit'];
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);
            $items[$index] = $item;
        }
        return array(
            'filter' => $filter,
            'items' => empty($items) ? array() : array_values($items),
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
            'haveChild' =>$query['pid']!='0'?true:false
        );
    }

    public function getDau($request = [])
    {
        $page = $this->getRequest($request, 'page', 'int', 1);
        $pageSize = $this->getRequest($request, 'pageSize', 'int', 10);
        $sort     = $this->getRequest($request, 'sort', 'string', 'date');
        $order    = $this->getRequest($request, 'order', 'int', -1);

        $query = array();
        $filter = array();


        if ($request['month']) {
            $filter['month'] = $this->getRequest($request, 'month');
            $query['month'] = $filter['month'];
        }
        $query['pid'] = $this->getRequest($request, 'pid','string','0');

        $skip = ($page - 1) * $pageSize;
        $fields = array();
        $count = $this->reportHourLogService->count($query);
        $items = $this->reportHourLogService->getList($query, $fields,array(($query['pid']==0?$sort:'date_limit') => ($query['pid']==0?$order:1)), $skip, $pageSize);
        $days = CommonValues::getAppDay();
        foreach ($items as $index => $item) {
            $item['reg']        = strval(number_format($item['reg']/100,2));
            $item['reg_android']= strval(number_format($item['reg_android']/100,2));
            $item['reg_h5']     = strval(number_format($item['reg_h5']/100,2));
            $item['dau']        = strval(round($item['dau']/100,2));
            $item['dau_android']= strval(round($item['dau_android']/100,2));
            $item['dau_h5']     = strval(round($item['dau_h5']/100,2));
            $item['dau_today_android']= strval(round($item['dau_today_android']/100,2));
            $item['dau_today_h5']     = strval(round($item['dau_today_h5']/100,2));

            foreach ($days as $key=>$val){
                if($key==1){
                    $item['dau_'.$key.'_android'] = $item['dau_yesterday_android']?:$item['dau_'.$key.'_android'];
                    $item['dau_'.$key.'_h5'] = $item['dau_yesterday_h5']?:$item['dau_'.$key.'_h5'];
                }

                $item['dau_'.$key.'_android'] = strval(round($item['dau_'.$key.'_android']/100,2));
                $item['dau_'.$key.'_h5']      = strval(round($item['dau_'.$key.'_h5']/100,2));
            }

            $item['haveChild']  = false;
            $item['date']       = $item['pid']=='0'?$item['date']:$item['date_limit'];
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);
            $items[$index] = $item;
        }
        return array(
            'filter' => $filter,
            'items' => empty($items) ? array() : array_values($items),
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
            'haveChild' =>false
        );
    }


    /**
     * 获取充值类数据项
     * @param $type
     * @param $pageSize
     * @return array
     */
    protected function getOrderItems($type, $pageSize)
    {
        $query = ['type' => $type];
        $data = array();
        $items = $this->reportService->getList($query, [], ['date' => -1], 0, $pageSize);
        foreach ($items as $item) {
            $tempArr = explode('|', $item['value']);
            $item['order_num'] = formatNum($tempArr[0], 2);
            $item['total_amount'] = doubleval(formatNum($tempArr[1], 2));
            $data[] = $item;
        }
        return $data;
    }

    /**
     * 获取留存类数据项
     * @param $pageSize
     * @return array
     */
    protected function getRRItems( $pageSize)
    {

        $days=[1,3,7,15,30];
        $result = [
            'title'=>[],
        ];
        foreach ($days as $day) {
            $items = $this->reportService->getList(['type' => "retention_{$day}"], ['value','date'], ['date' => -1], 0, $pageSize);
            $result["day_{$day}"]=array_column($items,'value');
            $title = [];
            foreach ($items as $item) {
                $title[]=$item['date'];
            }
            $result['title']=$title;
        }
        foreach ($result as $key=>&$item) {
            $item=array_reverse($item);
            unset($item);
        }
        return $result;
    }

    /**
     * 网站统计数据
     * @param $pageSize
     * @return array
     */
    protected function getWebLog($pageSize)
    {
        $types = [
            'total_user_reg'=>'总新增',
            'total_android_user_reg'=>'安卓新增',
            'total_ios_user_reg'=>'IOS新增',
            'total_order_money'=>'本日总充值',
            'today_order_money'=>'首充',
            'pv'=>'落地页PV',
            'uv'=>'落地页UV',
            'ip'=>'落地页IP',
            'click_android'=>'安卓下载点击',
            'click_ios'=>'IOS下载点击',
            'urr_1'=>'次日留存',
            'urr_3'=>'3日留存',
            'urr_7'=>'7日留存',
        ];
        $result = [
            'date'=>[],
            'types'=>$types
        ];
        $query = ['type' => 'web_log'];
        $webLogData = $this->reportService->getList($query, [], ['date' => -1], 0, $pageSize);
        $webLogData = array_reverse($webLogData);
        foreach ($webLogData as $item) {
            $result['date'][] = $item['date'];
            $values = json_decode($item['value'],true);
            foreach($values as $key=>$value){
                $result[$key][] = formatNum($value, 2);
            }
        }
        return $result;
    }


}