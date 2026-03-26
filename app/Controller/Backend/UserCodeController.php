<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\AdminUserRepository;
use App\Repositories\Backend\CommonRepository;
use App\Repositories\Backend\UserCodeRepository;
use App\Repositories\Backend\UserCodeLogRepository;
use App\Repositories\Backend\UserGroupRepository;
use App\Repositories\Backend\ProductRepository;

/**
 * 兑换码管理
 *
 * @package App\Controller\Backend
 *
 * @property  UserCodeRepository $userCodeRepository
 * @property  UserCodeLogRepository $userCodeLogRepository
 * @property  UserGroupRepository $userGroupRepository
 * @property  ProductRepository $productRepository
 * @property  AdminUserRepository $adminUserRepo
 * @property  CommonRepository $commonRepo
 */
class UserCodeController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/userCode');
    }


    /**
     * 列表
     */
    public function listAction()
    {
        if ($this->isPost()) {
            $result = $this->userCodeRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('codeStatus', CommonValues::getUserCodeStatus());
        $this->view->setVar('codeTypes', CommonValues::getUserCodeType());
    }

    /**
     * 详情
     */
    public function detailAction()
    {
        $this->view->setVar('userGroups', $this->userGroupRepository->getEnableAll());
        $this->view->setVar('productGroups', $this->productRepository->getEnableAll());
        $this->view->setVar('codeTypes', CommonValues::getUserCodeType());
    }

    /**
     * 保存
     * @throws BusinessException
     */
    public function saveAction()
    {
        $googleCode = $this->getRequest('google_code');
        if (empty($googleCode) || !$this->adminUserRepo->verifyGoogleCode($googleCode)) {
            $this->sendErrorResult("谷歌验证码错误!");
        }
        $result = $this->userCodeRepository->save($_POST);
        if ($result) {
            $this->sendSuccessResult();
        }
        $this->sendErrorResult("保存错误!");
    }

    /**
     * 导出数据
     * @throws BusinessException
     */
    public function exportAction()
    {
        $_REQUEST['pageSize'] = $this->commonRepo->getExportMaxSize();
        $result = $this->userCodeRepository->getList($_REQUEST);
        if($result['count']<1){
            $this->sendErrorResult('导出的数据为空,请尝试更换条件!');
        }
        $cells = array('name'=>'名称','code'=>'兑换码','type_text'=>'类型','object_name'=>'名称','status_text'=>'状态','expired_at'=>'过期时间','add_num'=>'兑换数量');
        $excel =$this->commonRepo->exportExcel($cells,$result['items'],'兑换码');
        $this->sendSuccessResult(array(
            'file' => $excel,
            'count' => $result['count']
        ));
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
                $this->userCodeRepository->delete($id);
            }
        }else{
            $update = [];
            if ($act == 'disable') {
                $update['status'] = -1;
            }
            if (empty($update)) {
                return $this->sendErrorResult("不能理解的操作!");
            }
            $ids = explode(',', $idStr);
            foreach ($ids as $id) {
                $update['_id'] = intval($id);
                if ($update) {
                    $this->userCodeRepository->update($update);
                }
            }
        }
        $this->sendSuccessResult();
    }


    /**
     * 兑换码日志
     */
    public function logAction()
    {
        if ($this->isPost()) {
            $result = $this->userCodeLogRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('codeTypes', CommonValues::getUserCodeType());
    }
}