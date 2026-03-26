<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Core\Controller\BaseController;
use App\Exception\BusinessException;
use App\Services\PaymentService;
use App\Services\MmsService;

/**
 * Class MovieController
 * @package App\Controller\Api
 * @property PaymentService $paymentService
 * @property MmsService $mmsService
 */
class PaymentController extends BaseController
{
    /**
     * 支付通知
     * @param $paymentId
     * @param $orderId
     * @param $type
     */
    public function notifyAction($paymentId,$orderId,$type)
    {
        if(empty($paymentId) || empty($orderId) || empty($type)){
            exit('error');
        }
        $this->paymentService->notify($paymentId,intval($orderId), $type);
    }

    /**
     * 订单回退
     * 取消订单拉黑用户
     */
    public function doBackAction()
    {
        try {
            $orderSn = $_REQUEST['order_sn'];

            $result = $this->paymentService->doBack($orderSn);
            if($result){
                echo json_encode(['status'=>'y','data'=>['user_id'=>$result['user_id'],'nickname'=>$result['nickname']]]);exit;
            }
            throw new \Exception('服务器内部错误');
        }catch (\Exception $e){
            echo json_encode(['status'=>'n','message'=>$e->getMessage()]);exit;
        }
    }

    /**
     * 返回
     */
    public  function  returnAction()
    {
        header('Content:text/html;charset=utf8');
        exit('请打开官网!');
    }

}