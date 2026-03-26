<?php


declare(strict_types=1);

namespace App\Controller\Customer;

use App\Controller\BaseCustomerController;


use App\Repositories\Customer\UserRepository;
use App\Utils\AesUtil;
use App\Utils\LogUtil;

/**
 * Class ContentController
 * @property UserRepository $userRepository
 * @package Se\Controller\Api
 */
class UserController extends BaseCustomerController
{
    /**
     * 订单记录
     * @return void
     */
    public function rechargeAction()
    {
        try {
            $result = $this->userRepository->order($_REQUEST);
            $this->sendSuccessResult($result);
        }catch (\Exception $e){
            LogUtil::error(sprintf('%s in %s line %s', $e->getMessage(), $e->getFile(), $e->getLine()));
            $this->sendErrorResult($e->getMessage());
        }
    }

    /**
     * 用户背包信息-对方是游戏设计概念
     * @return void
     */
    public function backpackAction()
    {
        try {
            $result = $this->userRepository->backpack($_REQUEST);
            $this->sendSuccessResult($result);
        }catch (\Exception $e){
            LogUtil::error(sprintf('%s in %s line %s', $e->getMessage(), $e->getFile(), $e->getLine()));
            $this->sendErrorResult($e->getMessage());
        }
    }

}