<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\AdminRoleRepository;
use App\Repositories\Backend\AuthorityRepository;

/**
 * 用户角色
 * Class SystemResourceController
 * @package App\Controller\Backend
 * @property AdminRoleRepository $adminRoleRepo
 * @property AuthorityRepository $authorityRepo
 */
class AdminRoleController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/adminRole');
    }

    /**
     * 列表
     */
    public function listAction()
    {
        if($this->isPost()){
            $result = $this->adminRoleRepo->getList($_REQUEST);
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
            $result = $this->adminRoleRepo->getDetail($id);
            $this->view->setVar('row', $result);
        }
        $this->view->setVar('authorities',$this->authorityRepo->getTree());
    }

    /**
     * 保存数据
     * @throws BusinessException
     */
    public function saveAction()
    {
        $result = $this->adminRoleRepo->save($_POST);
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
                $this->adminRoleRepo->delete($id);
            }
        }
        $this->sendSuccessResult();
    }

}