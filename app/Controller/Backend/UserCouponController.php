<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\CommonRepository;
use App\Repositories\Backend\UserCouponRepository;

/**
 * 观影券
 *
 * @package App\Controller\Backend
 *
 * @property  UserCouponRepository $userCouponRepository
 * @property  CommonRepository $commonRepo
 */
class UserCouponController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/userCoupon');
    }
    
    /**
     * 列表
     */
    public function listAction()
    {
        if ($this->isPost()) {
            $result = $this->userCouponRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('codeStatus', CommonValues::getUserCodeStatus());
        $this->view->setVar('typeArr', CommonValues::getCouponType());
    }

    /**
     * 详情
     */
    public function detailAction()
    {
        $this->view->setVar('moneyArr', CommonValues::getUserCouponMoney());
        $this->view->setVar('typeArr', CommonValues::getCouponType());
        $this->view->pick('userCoupon/detail');
    }

    /**
     * 保存
     * @throws BusinessException
     */
    public function saveAction()
    {
        $result = $this->userCouponRepository->save($_POST,true);
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
                $this->userCouponRepository->delete($id);
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
                    $this->userCouponRepository->update($update);
                }
            }
        }
        $this->sendSuccessResult();
    }

}