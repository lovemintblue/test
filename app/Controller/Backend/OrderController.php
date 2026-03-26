<?php


namespace App\Controller\Backend;


use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Repositories\Backend\CollectionsRepository;
use App\Repositories\Backend\RechargeRepository;
use App\Repositories\Backend\UserBuyLogRepository;
use App\Repositories\Backend\UserGroupRepository;
use App\Repositories\Backend\UserOrderRepository;

/**
 * 订单
 * Class OrderController
 * @property UserOrderRepository $userOrderRepository
 * @property RechargeRepository $rechargeRepository
 * @property UserGroupRepository $userGroupRepository
 * @property CollectionsRepository $collectionsRepository
 * @property UserBuyLogRepository $userBuyLogRepository
 * @package App\Controller\Backend
 */
class OrderController extends BaseBackendController
{
    /**
     * 会员订单
     */
    public function vipAction()
    {
        $this->checkPermission('/orderVip');
        if($this->isPost()){
            $result = $this->userOrderRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('statusArr',CommonValues::getUserOrderStatus());
        $this->view->setVar('groupArr', $this->userGroupRepository->getAll());
        $this->view->setVar('deviceArr',CommonValues::getDeviceTypes());
    }

    /**
     * 金币订单
     */
    public function pointAction()
    {
        $this->checkPermission('/orderPoint');
        if($this->isPost()){
            $_REQUEST['record_type'] = 'point';
            $result = $this->rechargeRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('statusArr',CommonValues::getRechargeStatus());
        $this->view->setVar('deviceArr',CommonValues::getDeviceTypes());
    }

    /**
     * 收款
     */
    public function collectionAction()
    {
        $this->checkPermission('/collection');
        if ($this->isPost()) {
            $result = $this->collectionsRepository->getList($_REQUEST);
            return $this->sendSuccessResult($result);
        }
        $this->view->setVar('groupArr',CommonValues::getAccountRecordType());
        $this->view->setVar('deviceArr',CommonValues::getDeviceTypes());
    }

    /**
     * 用户购买
     */
    public function buyAction()
    {
        $this->checkPermission('/orderBuy');
        if($this->isPost()){
            $result = $this->userBuyLogRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('typesArr',CommonValues::getPlayTypes());
    }
}