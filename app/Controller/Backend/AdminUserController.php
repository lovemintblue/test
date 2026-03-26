<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\AdminRoleRepository;
use App\Repositories\Backend\AdminUserRepository;


/**
 * 系统用户管理
 *
 * @package App\Controller\Backend
 *
 * @property  AdminUserRepository $adminUserRepo
 * @property AdminRoleRepository $adminRoleRepo
 */
class AdminUserController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/adminUser');
    }


    /**
     * 列表
     */
    public function listAction()
    {
        if($this->isPost()){
            $result = $this->adminUserRepo->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('roles',$this->adminRoleRepo->getRoles());
    }

    public function doAction()
    {
        $act = $this->getRequest('act');
        $ids = $this->getRequest('id');
        if(empty($act) || empty($ids)){
            $this->sendErrorResult('操作错误!');
        }
        $result = false;
        $ids = explode(',',$ids);
        if($act=='disable'){
            foreach ($ids as $id)
            {
                $this->adminUserRepo->doDisable($id);
            }
        }

    }

}
