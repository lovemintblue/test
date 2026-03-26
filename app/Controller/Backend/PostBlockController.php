<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\PostBlockRepository;

/**
 * 视频模块管理
 *
 * @package App\Controller\Backend
 *
 * @property  PostBlockRepository $postBlockRepository
 */
class PostBlockController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/postBlock');
    }

    /**
     * 列表
     */
    public function listAction()
    {
        if($this->isPost()) {
            $result = $this->postBlockRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('positions',$this->postBlockRepository->getPosition());
    }

    /**
     * 详情
     * @throws BusinessException
     */
    public function detailAction()
    {
        $id = $this->getRequest("_id");
        if (!empty($id)) {
            $result = $this->postBlockRepository->getDetail($id);
            $this->view->setVar('row', $result);
        }
        $this->view->setVar('positions',$this->postBlockRepository->getPosition());
    }

    /**
     * 保存
     * @throws BusinessException
     */
    public function saveAction()
    {
        $result = $this->postBlockRepository->save($_POST);
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
                $this->postBlockRepository->delete($id);
            }
        }
        $this->sendSuccessResult();
    }

}