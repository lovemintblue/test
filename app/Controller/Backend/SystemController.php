<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\AdminUserRepository;
use App\Repositories\Backend\AppErrorRepository;
use App\Repositories\Backend\ReportRepository;
use App\Repositories\Backend\SystemRepository;

/**
 * 日志管理管理
 *
 * @package App\Controller\Backend
 *
 * @property AppErrorRepository $appErrorRepo
 * @property AdminUserRepository $adminUserRepo
 * @property SystemRepository $systemRepo
 * @property ReportRepository $reportRepo
 */
class SystemController extends BaseBackendController
{

    /**
     * 系统主页
     */
    public function homeAction()
    {
        $this->checkPermission('/systemHome');
        $data = $this->reportRepo->getReportData();
        $this->view->setVar('data',$data);
    }

    public function hourAction()
    {
        $this->checkPermission('/systemHour');
        if($this->isPost()){
            $result = $this->reportRepo->getHour($_REQUEST);
            $this->sendSuccessResult($result);
        }
    }

    public function dauAction()
    {
        $this->checkPermission('/systemDau');
        if($this->isPost()){
            $result = $this->reportRepo->getDau($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('appDayArr',CommonValues::getAppDay());
    }

    /**
     * 系统主题
     */
    public function themeAction()
    {
        $this->view->setMainView('');
    }


    /**
     * app错误信息
     */
    public function errorLogsAction()
    {
        if ($this->isPost()) {
            $result = $this->appErrorRepo->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('deviceTypes', CommonValues::getDeviceTypes());
    }

    /**
     * 修改用户密码
     * @throws BusinessException
     */
    public function passwordAction()
    {
        if($this->isPost()){
            $oldPassword = $this->getRequest("old_password");
            $newPassword = $this->getRequest("new_password");
            if (empty($oldPassword) || empty($newPassword)) {
                $this->sendErrorResult("参数错误!");
            }
            $result = $this->adminUserRepo->changePassword($oldPassword, $newPassword);
            if ($result) {
                $this->sendSuccessResult();
            }
            $this->sendErrorResult("修改错误!");
        }
        $this->view->setMainView('');
    }

    /**
     * 清理缓存
     */
    public function clearCacheAction()
    {
        $this->systemRepo->clearCache();
        $this->sendSuccessResult();
    }

    /**
     * 签到
     */
    public function adminLogsAction()
    {
        $this->systemRepo->adminLogs('checkin');
        $this->sendSuccessResult();
    }

    /**
     * 造假数据
     */
    public function fakeAction()
    {
        $this->checkPermission('/systemFake');
        $csv = WEB_PATH . '/media/uploads/other/' . date('Y-m-d') . '/fake.csv';;
        if (is_file($csv)) {
            $file = fopen($csv,'r');
            $i=1;
            while ($data = fgetcsv($file)) { //每次读取CSV里面的一行内容
                $list[] = $data;
                $i++;
                if($i>30){
                    break;
                }
            }
            fclose($file);

            array_shift($list);

            $result = array();
            if(!empty($list)) {
                $list=array_reverse($list);
                $userTotal = 0;
                $userTodayTotal = 0;
                foreach ($list as $key=>$item) {
                    if (empty($item[0])||empty($item[1])||empty($item[2])){
                        continue;
                    }
                    //按照日期顺序来
                    $userTotal = $userTotal?:$item[3];
                    $userTodayTotal = $userTodayTotal?:$item[1];


                    //最近十五天注册
                    $userRegData[$key]['updated_at'] = strtotime($item[0]);
                    $userRegData[$key]['date'] = date('Y-m-d',strtotime($item[0]));
                    $userRegData[$key]['value'] = $item[1];

                    //最近十五天日活
                    $userDayReport[$key]['updated_at'] = strtotime($item[0]);
                    $userDayReport[$key]['date'] = date('Y-m-d',strtotime($item[0]));
                    $userDayReport[$key]['value'] = $item[2];


                }
            }

            //总会员数
            $userTotalData['updated_at'] = time();
            $userTotalData['value'] = $userTotal;
            $result['user_total'] = $userTotalData;

            //今日注册
            $result['user_reg_today']['updated_at'] = time();
            $result['user_reg_today']['value'] = $userTodayTotal;

            //注册
            $result['user_reg'] = $userRegData;

            //日活
            $result['app_day'] = $userDayReport;

            $this->view->setVar('data',$result);
        }

    }

    public function uploadCsvAction ()
    {
        $fileName = 'file';

        if (!isset($_FILES[$fileName]) || empty($_FILES[$fileName]['tmp_name'])) {
            return '';
        }
        $limit_ext_types = array(
            'csv'
        );
        $dir = WEB_PATH . '/media/uploads/other/' . date('Y-m-d') . '/';
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $tempName = $_FILES[$fileName]['tmp_name'];
        $readname = $_FILES[$fileName]['name'];
        $ext = 'csv';
        if ($ext && in_array($ext, $limit_ext_types)) {
            $newFile = $dir .  'fake.' .  $ext;
            if (move_uploaded_file($tempName, $newFile)) {
                $baseFilePath =  str_replace(WEB_PATH, "", $newFile);
                //return  $baseFilePath;
                $file =array(
                    'name' => $readname,
                    'file_path' => $baseFilePath
                );

            }
        }
        if($file){
            $this->sendSuccessResult($file);
        }
        $this->sendErrorResult('上传错误!');

    }

}