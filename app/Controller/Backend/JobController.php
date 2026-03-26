<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Repositories\Backend\JobRepository;


/**
 * 任务
 * Class JobController
 * @package App\Controller\Backend
 * @property JobRepository $jobRepository
 */
class JobController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/job');
    }

    /**
     * 列表
     */
    public function listAction()
    {
        if($this->isPost()){
            $result = $this->jobRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('statusArr',CommonValues::getJobStatus());
        $this->view->setVar('levelArr',CommonValues::getJobLevel());
    }

    /**
     * 各种操作
     */
    public function doAction()
    {
        $ids = $this->getRequest("ids");
        $act = $this->getRequest("act");

        if (empty($ids) || empty($act)) {
            return $this->sendErrorResult("参数错误!");
        }
        if ($act == 'retry'){
            $update = array(
                'status'     => 0,
                'updated_at' => time(),
            );
        }else{
            return $this->sendErrorResult("参数错误!");
        }
        $ids = explode(',', $ids);
        foreach ($ids as $id) {
            $job = $this->jobRepository->findByID($id);
            if($job&&$job['status']==-1){
                $update['_id'] = intval($id);
                $this->jobRepository->update($update);
            }
        }
        return $this->sendSuccessResult();
    }

}