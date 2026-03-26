<?php


namespace App\Controller\Api;


use App\Controller\BaseApiController;
use App\Exception\BusinessException;
use App\Repositories\Api\MovieRepository;
use App\Repositories\Api\UserRepository;
use App\Services\ApiService;
use App\Services\UserActiveService;
use App\Utils\CommonUtil;

/**
 * Class UserController
 * @property ApiService $apiService
 * @property UserRepository $userRepository
 * @property MovieRepository $movieRepository
 * @property UserActiveService $userActiveService
 * @package App\Controller\Api
 */
class UserController extends BaseApiController
{
    /**
     * 用户登录-token过期
     * @throws BusinessException
     */
    public function loginAction()
    {
        $deviceId = $this->apiService->getDeviceId();
        $token = $this->userRepository->login($deviceId);
        $this->sendSuccessResult($token);
    }

    /**
     * 获取用户信息
     */
    public function infoAction()
    {
        $userId = $this->getUserId();
        $result = $this->userRepository->getInfo($userId);
        $this->sendSuccessResult($result);
    }

    /**
     * 个人主页
     */
    public function homeAction()
    {
        $userId = $this->getUserId();
        $id  = $this->getRequest('id','int');
        $result = $this->userRepository->getHome($id,$userId);
        $this->sendSuccessResult($result);
    }

    /**
     * 分享信息
     */
    public function shareInfoAction()
    {
        $userId = $this->getUserId();
        $result = $this->userRepository->getShareInfo($userId);
        $this->sendSuccessResult($result);
    }

    /**
     * 获取分享列表
     */
    public function shareLogsAction()
    {
        $userId = $this->getUserId();
        $page   = $this->getRequest('page', 'int', 1);
        $result = $this->userRepository->getShareList($userId,$page);
        $this->sendSuccessResult($result);
    }

    /**
     *头像接口
     */
    public function imagesAction()
    {
        $result = $this->userRepository->getHeadImages();
        $this->sendSuccessResult($result);
    }

    /**
     * 更新
     * @throws BusinessException
     */
    public function updateInfoAction()
    {
        $userId = $this->getUserId();
        $field = $this->getRequest('field');
        $value = $this->getRequest("value", 'string', '');
        if (empty($field) || $value === "") {
            $this->sendErrorResult('修改错误!');
        }
        $this->userRepository->doSimpleUpdate($userId, $field, $value);
        $this->sendSuccessResult();
    }

    /**
     * 绑定手机号
     * @throws BusinessException
     */
    public function bindPhoneAction()
    {
        $userId = $this->getUserId();
        $phone = $this->getRequest('phone');
        $code = $this->getRequest("code");
        if (empty($phone) || empty($code)) {
            $this->sendErrorResult('请输入正确参数!');
        }
        $smsKey = 'phone_' . $phone;
        $checkCode = container()->get('redis')->get($smsKey);
        if ($checkCode != $code) {
            $this->sendErrorResult('验证码错误-02!');
        }
        $this->userRepository->bindPhone($userId, $phone);
        delCache($smsKey);
        $this->sendSuccessResult();
    }

    /**
     * 绑定上级(手动)
     * @throws BusinessException
     */
    public function bindParentAction()
    {
        $shareCode   = $this->getRequest("code");
        if(empty($shareCode)){
            $this->sendErrorResult('請輸入正確的編號!');
        }
        $userId = $this->getUserId();

        $result=$this->userRepository->bindParent($userId,$shareCode);
        if($result){
            $this->sendSuccessResult();
        }else{
            $this->sendErrorResult('绑定错误!');
        }
    }

    /**
     * 绑定渠道码
     * @throws BusinessException
     */
    public function bindCodeAction()
    {
        $userId = $this->getUserId();
        $code = $this->getRequest("code");
        if (empty($code)) {
            $this->sendErrorResult('请输入正确参数!');
        }
        if (strpos($code, 'channel://') > -1) {
            $this->userRepository->bindChannel($userId, CommonUtil::getChannel($code));
        } elseif (strpos($code, 'share://') > -1) {
            $this->userRepository->bindParent($userId, CommonUtil::getParent($code));
        } else {
            $this->sendErrorResult('不支持的类型!');
        }
        $this->sendSuccessResult();
    }

    /**
     * 找回账号
     * @throws BusinessException
     */
    public  function findQrcodeAction()
    {
        $code  = $this->getRequest('code');
        if(empty($code)){
            $this->sendErrorResult('请输入正确参数!');
        }
        $userId = $this->getUserId();

        $result = $this->userRepository->doBackQR($userId,$code);
        $this->sendSuccessResult($result);
    }

    /**
     * 找回账号
     * @throws BusinessException
     */
    public  function findPhoneAction()
    {
        $phone = $this->getRequest('phone');
        $code  = $this->getRequest('code');
        if(empty($phone) || empty($code)){
            $this->sendErrorResult('请输入正确参数!');
        }
        $userId = $this->getUserId();
        $smsKey = 'phone_' . $phone;
        $checkCode = container()->get('redis')->get($smsKey);
        if ($checkCode != $code) {
            $this->sendErrorResult('验证码错误-01!');
        }
        $token = $this->userRepository->doBackPhone($userId, $phone);
        $this->sendSuccessResult($token);
    }


    /**
     * 账号密码登录
     */
    public function findByAccountAction()
    {
        $userId = $this->getUserId();
        $accountName  = $this->getRequest('account_name');
        $accountPassword  = $this->getRequest('account_password');
        $type        = $this->getRequest('type');
        if(empty($accountName) || empty($accountPassword) || empty($type)){
            $this->sendErrorResult('请输入正确参数!');
        }
        if(strlen($accountName)<5){
            $this->sendErrorResult('账号名称必须大于5位字符!');
        }
        if(!preg_match('/^[A-Za-z0-9_\x{4e00}-\x{9fa5}]+$/u',$accountName)) {
            $this->sendErrorResult('户名由数字或字母、汉字、下划线组成!');
        }
        if(strlen($accountPassword)<5){
            $this->sendErrorResult('密码必须大于5位字符!');
        }
        $token = $this->userRepository->doBackAccount($userId,$accountName, $accountPassword,$type);
        $this->sendSuccessResult($token);
    }

    /**
     * 去关注|取消关注
     * @throws \App\Exception\BusinessException
     */
    public function doFollowAction()
    {
        $userId = $this->getUserId();
        $homeId = $this->getRequest('id','int');
        if(empty($homeId) || $homeId<1){
            $this->sendErrorResult('请检查参数!');
        }
        $result = $this->userRepository->doFollow($userId,$homeId);
        $this->sendSuccessResult(array('status'=> $result?'y':'n','follow_type'=>$result));
    }

    /**
     * 关注
     */
    public function followAction()
    {
        $userId = $this->getUserId();
        $page = $this->getRequest('page','int',1);
        $result = $this->userRepository->getFollowList($userId,$page);
        $this->sendSuccessResult($result);
    }

    /**
     * 粉丝
     */
    public function fansAction()
    {
        $userId = $this->getUserId();
        $page = $this->getRequest('page','int',1);
        $result = $this->userRepository->getFansList($userId,$page);
        $this->sendSuccessResult($result);
    }

    /**
     * 使用兑换码
     * @throws BusinessException
     */
    public function doCodeAction()
    {
        $code = $this->getRequest('code','string');
        $userId = $this->getUserId();

        $this->userRepository->doCode($userId,$code);
        $this->sendSuccessResult();
    }

    /**
     * 兑换记录
     */
    public function codeLogsAction()
    {
        $page = $this->getRequest('page','int',1);
        $userId = $this->getUserId();
        $result = $this->userRepository->codeLog($userId,$page);
        $this->sendSuccessResult($result);
    }

    /**
     * 会员页面
     */
    public function vipAction()
    {
        $userId = $this->getUserId();
        $result = $this->userRepository->vipInfo($userId);
        $this->sendSuccessResult($result);
    }

    /**
     * 去购买
     * @throws BusinessException
     */
    public function doVipAction()
    {
        $userId     = $this->getUserId();
        $groupId    = $this->getRequest('group_id', 'int');
        $paymentId  = $this->getRequest('payment_id', 'int');
        if (empty($groupId)) {
            $this->sendErrorResult('请选择购买套餐!');
        }
        if (empty($paymentId)) {
            $this->sendErrorResult('请选择正确支付方式!');
        }
        $result = $this->userRepository->doVip($userId, $groupId, $paymentId);
        $this->sendSuccessResult($result);
    }

    /**
     * 购买记录
     */
    public function vipLogsAction()
    {
        $userId     = $this->getUserId();
        $page       = $this->getRequest('page', 'int',1);
        $result = $this->userRepository->getVipLogs($userId, $page);
        $this->sendSuccessResult($result);
    }

    /**
     * 金币充值
     */
    public function rechargeAction()
    {
        $userId = $this->getUserId();
        $type   = $this->getRequest('type','string');
        if(!in_array($type,['point'])){
            $this->sendErrorResult('参数错误');
        }
        $result = $this->userRepository->rechargeInfo($userId);
        $this->sendSuccessResult($result);
    }

    /**
     * 购买金币
     * @throws BusinessException
     */
    public function doRechargeAction()
    {
        $userId    = $this->getUserId();
        $productId = $this->getRequest('product_id', 'int');
        $paymentId = $this->getRequest('payment_id', 'int');
        $type = $this->getRequest('type', 'string', 'point');
        if (empty($productId)) {
            $this->sendErrorResult('请选择购买套餐!');
        }
        if (empty($paymentId)) {
            $this->sendErrorResult('请选择正确支付方式!');
        }
        $result = $this->userRepository->doRecharge($type,$userId, $productId, $paymentId);
        $this->sendSuccessResult($result);
    }


    /**
     * 会话列表
     */
    public function chatsAction()
    {
        $token = $this->getToken();
        $page = $this->getRequest('page','int',1);
        $result = $this->userRepository->getChats($token['user_id'],$page);
        $this->sendSuccessResult($result);
    }

    /**
     * 删除会话
     */
    public function delChatAction()
    {
        $userId = $this->getUserId();
        $toUserId = $this->getRequest('to_user_id', 'int');
        if(empty($toUserId)){
            $this->sendErrorResult("请检查输入完整性!");
        }
        $this->userRepository->delChat($userId,$toUserId);
        $this->sendSuccessResult();
    }

    /**
     * 会话消息列表
     */
    public function chatMessagesAction()
    {
        $userId     = $this->getUserId();
        $page       = $this->getRequest('page','int',1);
        $toUserId   = $this->getRequest('to_user_id', 'int');

        if(empty($toUserId)){
            $this->sendErrorResult("请检查输入完整性!");
        }
        $result['message'] = $this->userRepository->getChatMessages($userId,$toUserId,$page);
        $result['faq']     = $this->userRepository->getChatFaq();
        $result['system_head_img']=$this->userRepository->getDefaultChat()['head_img'];
        $this->sendSuccessResult($result);
    }

    /**
     * 发送消息
     * @throws BusinessException
     */
    public function sendMessageAction()
    {
        $userId = $this->getUserId();
        $type = $this->getRequest('type');
        $content = $this->getRequest('content');
        $ext = $this->getRequest('ext');
        $toUserId = $this->getRequest('to_user_id', 'int');
        if (empty($toUserId) || empty($type) || empty($content)) {
            $this->sendErrorResult("请检查输入完整性!");
        }
        if (mb_strlen($content, 'utf8') > 100) {
            $this->sendErrorResult("输入不能超过100个字!");
        }
        $this->userRepository->sendMessage($userId, $toUserId, $type, $content, $ext);
        $this->sendSuccessResult();
    }

    /**
     * 余额日志
     */
    public  function accountLogsAction()
    {
        $userId = $this->getUserId();
        $page = $this->getRequest('page','int',1);
        $result =$this->userRepository->getAccountLogs($userId,$page);
        $this->sendSuccessResult($result);
    }

    /**
     * 福利任务
     */
    public function taskAction()
    {
        $userId = $this->getUserId();
        $result =$this->userRepository->getTask($userId);
        $this->sendSuccessResult($result);
    }

    /**
     * 点击事件记录
     */
    public function doTaskLogAction()
    {
        $userId = $this->getUserId();
        $taskId = $this->getRequest('id','int',0);
        $this->userRepository->doTaskLog($userId,$taskId);
        $this->sendSuccessResult();
    }

    /**
     * 执行福利任务
     * @throws BusinessException
     */
    public function doTaskAction()
    {
        $userId = $this->getUserId();
        $taskId = $this->getRequest('id','int',0);
        $this->userRepository->doTask($userId,$taskId);
        $this->sendSuccessResult();
    }

    /**
     * 签到
     */
    public function doDaySignAction()
    {
        $userId = $this->getUserId();
        $this->userRepository->doDaySign($userId);
        $this->sendSuccessResult();
    }

    /**
     * 积分兑换
     */
    public function exchangeInfoAction()
    {
        $userId = $this->getUserId();
        $result =$this->userRepository->getExchangeInfo($userId);
        $this->sendSuccessResult($result);
    }

    /**
     * 兑换
     * @throws BusinessException
     */
    public function doExchangeAction()
    {
        $userId = $this->getUserId();
        $num= $this->getRequest('num','int',0);
        $this->userRepository->doExchange($userId,$num);
        $this->sendSuccessResult();
    }

    /**
     * 热门up
     */
    public function upAction()
    {
        $userId = $this->getUserId();
        $page= $this->getRequest('page','int',1);
        $result =$this->userRepository->getUpList($userId,$page,12);
        $this->sendSuccessResult($result);
    }

    /**
     * 获取客服链接
     * @return void
     */
    public function getCustomerUrlAction()
    {
        $userId = $this->getUserId();
        $result = $this->userRepository->getCustomerUrl($userId);
        $this->sendSuccessResult($result);
    }
}