<?php

declare(strict_types=1);

namespace App\Controller\Backend;


use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\UserUpRepository;

/**
 * 用户任务
 *
 * @package App\Controller\Backend
 * @property  UserUpRepository $userUpRepo
 */
class UserUpController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/userUp');
    }

    /**
     * 列表
     */
    public function listAction()
    {
        if($this->isPost()){
            $result = $this->userUpRepo->getList($_REQUEST);
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
            $result = $this->userUpRepo->getDetail($id);
            $this->view->setVar('row', $result);
        }
    }

    /**
     * 保存
     * @throws BusinessException
     */
    public function saveAction()
    {
        $result = $this->userUpRepo->save($_POST);
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
                $this->userUpRepo->delete($id);
            }
        }
        $this->sendSuccessResult();
    }

}