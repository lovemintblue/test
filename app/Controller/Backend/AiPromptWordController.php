<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\AiPromptWordRepository;

/**
 * 创意提示词
 * @package App\Controller\Backend
 *
 * @property  AiPromptWordRepository $aiPromptWordRepository
 */
class AiPromptWordController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/aiPromptWord');
    }

    /**
     * 公共
     */
    public function initData()
    {
        $this->view->setVar('groupArr',$this->aiPromptWordRepository->aiPromptWordService->getGroup());
        $this->view->setVar('hot',CommonValues::getHot());
    }


    /**
     * 列表
     */
    public function listAction()
    {
        if ($this->isPost()) {
            $result = $this->aiPromptWordRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->initData();
    }

    /**
     * 详情
     * @throws BusinessException
     */
    public function detailAction()
    {
        $id = $this->getRequest("_id");
        if (!empty($id)) {
            $result = $this->aiPromptWordRepository->getDetail($id);
            $this->view->setVar('row',json_encode($result));
        }
        $this->initData();
    }

    /**
     * 保存
     * @throws BusinessException
     */
    public function saveAction()
    {
        $result = $this->aiPromptWordRepository->save($_POST);
        if ($result) {
            $this->sendSuccessResult();
        }
        $this->sendErrorResult("保存错误!");
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
        if($act=='del') {
            $ids = explode(',', $idStr);
            foreach ($ids as $id) {
                $this->aiPromptWordRepository->delete($id);
            }
        }elseif($act=='update'){
            $ids = explode(',', $idStr);
            $group = $this->getRequest('group','string');
            $isHot = $this->getRequest('is_hot','string');
            foreach ($ids as $id) {
                if($group!=''){$data['group'] = strval($group);}
                if($isHot!=''){$data['is_hot'] = intval($isHot);}
                if(empty($data)){
                    $this->sendErrorResult("数据为空!");
                }
                $data['_id'] = intval($id);
                $this->aiPromptWordRepository->aiPromptWordService->save($data);
            }
        }
        $this->sendSuccessResult();
    }

    /**
     * 批量设置
     */
    public function updateAction()
    {
        $this->view->setVar('ids',$this->getRequest('ids','string'));
        $this->initData();
    }
}