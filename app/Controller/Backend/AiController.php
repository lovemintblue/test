<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\AiRepository;

/**
 * AI
 *
 * @package App\Controller\Backend
 *
 * @property  AiRepository $aiRepository
 */
class AiController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/ai');
    }

    /**
     * 列表
     */
    public function listAction()
    {
        if ($this->isPost()) {
            $result = $this->aiRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('statusArr',CommonValues::getAiStatus());
        $this->view->setVar('posArr',CommonValues::getAiPosition());
        $this->view->setVar('deviceArr',CommonValues::getDeviceTypes());
    }

    /**
     * 详情
     * @throws BusinessException
     */
    public function detailAction()
    {
        $id = $this->getRequest("_id");
        if (!empty($id)) {
            $result = $this->aiRepository->getDetail($id);
            $this->view->setVar('row',$result);
        }
    }

    /**
     * 批量操作
     */
    public  function  doAction()
    {
        $idStr = $this->getRequest("id");
        $act = $this->getRequest("act");
        if(empty($idStr) || empty($act)){
            $this->sendErrorResult("操作错误!");
        }

        $ids = explode(',', $idStr);
        foreach ($ids as $id) {
            if($act=='retry') {
                $updateData = [
                    'status'=>2,
                    'error_msg'=>''
                ];
            }elseif($act=='refund'){
                $row = $this->aiRepository->aiService->findByID($id);
                $this->aiRepository->aiService->refund($row,$row['real_money']);
                $updateData = [
                    'status'=>-1,
                    'updated_at'=>time()
                ];
            }elseif($act=='del') {
                $updateData = [
                    'is_disabled'=>1
                ];
            }

            if(empty($updateData)){continue;}
            $this->aiRepository->aiService->aiModel->updateRaw(['$set'=>$updateData],['_id'=>strval($id)]);
            delCache("ai_detail_{$id}");
        }

        $this->sendSuccessResult();
    }

}