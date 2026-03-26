<?php


namespace App\Repositories\Customer;


use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Jobs\Center\CenterCustomerJob;
use App\Services\CollectionsService;
use App\Services\CommonService;
use App\Services\CustomerSystemService;
use App\Services\PaymentService;
use App\Services\RechargeService;
use App\Services\UserOrderService;
use App\Services\UserService;
use App\Utils\CommonUtil;

/**
 * Class CommentRepository
 * @property CustomerSystemService $customerSystemService
 * @property UserService           $userService
 * @property UserOrderService      $userOrderService
 * @property RechargeService      $rechargeService
 * @property PaymentService        $paymentService
 * @property CommonService         $commonService
 * @package App\Repositories\Api
 */
class UserRepository extends BaseRepository
{

    /**
     * 订单记录
     * @param $request
     * @return array|null
     */
    public function order($request=[])
    {
        $sign = $this->getRequest($request,'sign','string');//固定参数,加密后的userId=xx
        $configs  = CenterCustomerJob::getCenterConfig('customer');
        $sign = $this->customerSystemService->decode($sign,$configs['appkey']);
        parse_str($sign, $params);

        $userId = $this->getRequest($params,'userId','int');//固定参数
        $page   = $this->getRequest($params,'page','int',1);//猜测参数,中心未提供
        $pageSize = $this->getRequest($params,'pageSize','int',200);//猜测参数,中心未提供
        if(empty($sign)||empty($userId)){
            return [];
        }
        //获取用户订单列表
        // [
        //           {
        //               "userId"     :  "123"                   // 用户Id  1
        //               "userName"   :  "张三"                   // 用户姓名 user
        //               "account"    :  "aa2233"                // 账号 user
        //               "email"      :  "test@gmail"            // 邮箱 user
        //               "phone"      :  "18154344344"           // 手机号 user
        //               "userIp"     :  "127.0.01"              // 用户IP user
        //               "devType"    :  "ios"                   // 设备类型
        //               "appId"      :  "1001"                  // appId
        //               "appName"    :  "抖阴"                   // appName
        //               "payType"    :  "alipay"                // 支付方式
        //               "orderId"    :  "xxxxx"                 // 订单号
        //               "channelOid" :  "vvvvv"                 // 渠道订单号
        //               "money"      :  100.00                  // 订单金额
        //               "payMoney"   :  100.00                  // 支付金额
        //               "status"     :  "进行中"                 // 订单状态 进行中、失败、成功、退款
        //               "productName":  "月卡"                   // 商品名称
        //               "remark"     :  "可填写项目上特殊备注"      // 备注
        //               "createTime" :  2019-09-09T06:45:41.094Z // 下单时间
        //               "payTime"    :  2019-09-09T06:45:41.094Z // 支付时间
        //               "notifyTime" :  2019-09-09T06:45:41.094Z // 回调时间
        //           }
        //       ]
        $userInfo = $this->userService->getInfoFromCache($userId);
        if (empty($userInfo)) {
            return null;
        }

        //查询订单和金币
        $orders = $this->userOrderService->getList(['user_id'=>$userId],[],['created_at'=>-1],($page-1)*$pageSize,$pageSize);
        $recharges = $this->rechargeService->getList(['user_id'=>$userId],[],['created_at'=>-1],($page-1)*$pageSize,$pageSize);

        $result = [];
        foreach ($orders as $row) {
            $result[] = [
                "userId"    =>strval($row['user_id']),
                "userName"  =>strval($userInfo['nickname']),
                "account"   =>strval($row['username']),
                "email"     =>strval(""),
                "phone"     =>strval($userInfo['phone']),
                "userIp"    =>strval($row['created_ip']),
                "devType"   =>strval($row['device_type']),
                "appId"     =>strval($configs['appid']),
                "appName"   =>strval($configs['appname']),
                "payType"   =>strval($row['pay_name']),
                "orderId"   =>strval($row['order_sn']),
                "channelOid"   =>strval($row['trade_sn']),
                "money"   =>doubleval($row['price']),
                "payMoney"   =>doubleval($row['real_price']),
                "status"   =>value(function ()use($row){
                    if($row['status']==1){
                        return '成功';
                    }
                    if($row['status']==-1){
                        return '退款';
                    }
                    return "未支付";
                }),
                "productName"=>"会员套餐:{$row['group_id']}",
                'remark'    =>strval($row['group_name']),
                'createTime'=>date('c', $row['created_at']),
                'payTime'=>$row['pay_at']?date('c', $row['pay_at']):"",
                'notifyTime'=>$row['pay_at']?date('c', $row['pay_at']):"",
            ];
            unset($row);
        }

        foreach ($recharges as $row) {
            $result[] = [
                "userId"    =>strval($row['user_id']),
                "userName"  =>strval($userInfo['nickname']),
                "account"   =>strval($row['username']),
                "email"     =>strval(""),
                "phone"     =>strval($userInfo['phone']),
                "userIp"    =>strval($row['created_ip']),
                "devType"   =>strval($row['device_type']),
                "appId"     =>strval($configs['appid']),
                "appName"   =>strval($configs['appname']),
                "payType"   =>strval($row['pay_name']),
                "orderId"   =>strval($row['order_sn']),
                "channelOid"   =>strval($row['trade_sn']),
                "money"   =>doubleval($row['amount']),
                "payMoney"   =>doubleval($row['real_amount']),
                "status"   =>value(function ()use($row){
                    if($row['status']==1){
                        return '成功';
                    }
                    if($row['status']==-1){
                        return '退款';
                    }
                    return "未支付";
                }),
                "productName"=>"金币套餐:{$row['product_id']}",
                'remark'    =>strval("数量:".($row['num']+$row['give'])),
                'createTime'=>date('c', $row['created_at']),
                'payTime'=>$row['pay_at']?date('c', $row['pay_at']):"",
                'notifyTime'=>$row['pay_at']?date('c', $row['pay_at']):"",
            ];
            unset($row);
        }

        #融合
        array_multisort(array_column($result, 'createTime'), SORT_DESC, $result);

        return $result;
    }


    /**
     * 背包信息
     * @param $request
     * @return array
     * @throws \Exception
     */
    public function backpack($request=[])
    {
        $sign = $this->getRequest($request,'sign','string');//固定参数,加密后的userId=xx
        $configs  = CenterCustomerJob::getCenterConfig('customer');
        $sign = $this->customerSystemService->decode($sign,$configs['appkey']);
        parse_str($sign, $params);

        $userId = self::getRequest($params,'userId','int');//固定参数
        if(empty($sign)||empty($userId)){
            return [];
        }

        $userInfo = $this->userService->getInfoFromCache($userId);
        if(empty($userInfo)){
            throw new \Exception("用户信息不存在");
        }
        //获取用户信息
        //{
        //               "userId"        :  "123"                   // 用户Id
        //               "userName"      :  "张三"                   // 用户姓名
        //               "account"	     :  "testaccount"           //账号
        //               "email"		 : "test@gmail.com"         //邮箱
        //               "phone"		 : "180232332"              //手机号
        //               "userIpLocation":  "湖南"                   // 用户IP属地
        //               "uuid"          :  "fdssdfsd"              // uuid
        //               "devType"       :  "ios"                   // 设备类型
        //               "devId"         :  "fsdfsdfsdfsd"          // 设备id
        //               "systemType"    :  "ios"                   // 系统类型
        //               "systemVersion" :  "ios"                   // 系统版本
        //               "appId"         :  "1001"                  // appId
        //               "appName"       :  "抖阴"                   // appName
        //               "vipExpiredTime":  2019-09-09T06:45:41.094Z// VIP过期时间
        //               "vipLevel"      :  "1"                     // VIP等级,根据项目自定义传,展示给客服看
        //               "vipName"       :  "至尊会员卡"               // VIP名
        //               "balance"       :  100.00                  // 金币余额
        //               "userRights"    :  "可填写项目上特殊备注"      // 用户权益,eg:金币抵扣券*3,AI抵扣券*4
        //       }
        $result =[
            "userId" =>strval($userId),
            "userName"=>strval($userInfo['nickname']),
            'account'=>strval($userInfo['username']),
            'email'  =>strval(''),
            'phone'  =>strval(''),
            'userIp' =>strval($userInfo['last_ip']),
            'userIpLocation'      =>strval($userInfo['province'].'-'.$userInfo['city']),
            'uuid'      =>strval($userId),
            'devType'   =>strval($userInfo['device_type']),
            'devId'     =>strval($userInfo['device_id']),
            'systemType'=>strval($userInfo['device_type']),
            'systemVersion'=>strval($userInfo['device_version']),//未知
            'appId'     =>strval($configs['appid']),
            'appName'   =>strval($configs['appname']),
            'vipExpiredTime'=> $userInfo['group_end_time'] ? date('c', $userInfo['group_end_time']) : '',
            'vipLevel'      =>$userInfo['level']?$userInfo['level']:'0',
            'vipName'       =>$userInfo['group_name'],
            'balance'       =>doubleval($userInfo['balance']),
            'userRights'    =>''
        ];
        return $result;
    }
}