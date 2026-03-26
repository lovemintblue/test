<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\QuickReplyRepository;



/**
 * 快速回复
 *
 * @package App\Controller\Backend
 *
 * @property  QuickReplyRepository $quickReplyRepository
 */
class QuickReplyController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/quickReply');
    }


    /**
     * 列表
     */
    public function listAction()
    {
        if($this->isPost()){
            $result = $this->quickReplyRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
    }

    /**
     * 详情
     * @throws BusinessException
     */
    public function detailAction()
    {
        $id = $this->getRequest("_id");
        if (!empty($id)) {
            $result = $this->quickReplyRepository->getDetail($id);
            $this->view->setVar('row', $result);
        }
    }

    /**
     * 保存
     * @throws BusinessException
     */
    public function saveAction()
    {
        $result = $this->quickReplyRepository->save($_POST);
        if ($result) {
            return $this->sendSuccessResult();
        }
        return $this->sendErrorResult("保存错误!");
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
                $this->quickReplyRepository->delete($id);
            }
        }
        $this->sendSuccessResult();
    }

}