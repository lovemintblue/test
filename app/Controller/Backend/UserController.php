<?php


namespace App\Controller\Backend;


use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\CommonRepository;
use App\Repositories\Backend\UserGroupRepository;
use App\Repositories\Backend\UserRepository;
use Exception;

/**
 * Class UserController
 * @property UserRepository $userRepository
 * @property CommonRepository $commonRepository
 * @property UserGroupRepository $userGroupRepository
 * @package App\Controller\Backend
 */
class UserController extends BaseBackendController
{

    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/user');
    }

    /**
     * 列表
     */
    public function listAction()
    {
        if ($this->isPost()) {
            $result = $this->userRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('groupArr', $this->userGroupRepository->getAll());
        $this->view->setVar('disabledArr',CommonValues::getIsDisabled());
        $this->view->setVar('deviceArr',CommonValues::getDeviceTypes());
        $this->view->setVar('isUpArr',CommonValues::getIsUp());
    }

    /**
     * 详情
     * @throws BusinessException
     */
    public function detailAction()
    {
        $id = $this->getRequest("_id");
        if (!empty($id)) {
            $result = $this->userRepository->getDetail($id);
            $this->view->setVar('row', $result);
        }
        $this->view->setVar('groupArr', $this->userGroupRepository->getAll());
        $this->view->setVar('disabledArr',CommonValues::getIsDisabled());
        $this->view->setVar('sexArr',CommonValues::getUserSex());

    }

    /**
     * 编辑
     * @throws BusinessException
     */
    public function saveAction()
    {
        $result = $this->userRepository->update($_POST);
        if ($result) {
            $this->sendSuccessResult();
        }
        $this->sendErrorResult("保存错误!");
    }

    /**
     * 操作
     */
    public function doAction()
    {
        $ids = $this->getRequest("id");
        $act = $this->getRequest("act");
        $errorMsg = $this->getRequest("error_msg");
        if (empty($ids) || empty($act)) {
            return $this->sendErrorResult("参数错误!");
        }
        $token=$this->getToken();

        if($act =='up'){
            $update = ['is_disabled' => 0];
        }elseif ($act =='down'){
            if($errorMsg){
                $update = ['is_disabled' => 1, 'error_msg' => $errorMsg." 操作人:{$token['username']}"];
            }else{
                $update = ['is_disabled' => 1, 'error_msg' => "操作人:{$token['username']}"];
            }
        }elseif ($act == "es") {
            $update = array();
        } else {
            return $this->sendErrorResult("不能理解的操作!");
        }
        $ids = explode(',', $ids);
        foreach ($ids as $id) {
            if ($update) {
                $update['_id'] = intval($id);
                $this->userRepository->save($update);
            }
            $this->userRepository->setInfoToCache($id);
        }
        return $this->sendSuccessResult();
    }

    /**
     * 后台充值
     * @throws BusinessException
     */
    public function rechargeAction()
    {
        $money  = $this->getRequest("money");
        $userId = $this->getRequest("user_id");
        $googleCode = $this->getRequest("google");
        $action = $this->getRequest('action','string','point');
        $remark = $this->getRequest('remark','string','');

        if (empty($money)) {
            $this->sendErrorResult("必填项缺失");
        }

        if (empty($googleCode)) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '请输入谷歌验证码!');
        }
        if (!$this->adminUserRepo->verifyGoogleCode($googleCode)) {
            $this->sendErrorResult("谷歌验证码错误!");
        }
        if($action=='point'){
            $result = $this->userRepository->doRecharge($userId, $money,'point', $remark);
        }else{
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }
        if ($result) {
            $this->userRepository->setInfoToCache($userId);
            $this->sendSuccessResult();
        }
    }


    /**
     * 找回账号页面
     */
    public function findAction()
    {
        if ($this->isPost()) {
            $userId1 = $this->getRequest('user_id1', 'int');
            $userId2 = $this->getRequest('user_id2', 'int');
            if(empty($userId1) || empty($userId2) || $userId1 == $userId2){
                $this->sendErrorResult('参数错误');
            }
            $rows = $this->userRepository->getAccList($userId1,$userId2);
            $this->sendSuccessResult($rows);
        }
    }

    /**
     * 确认找回
     * @throws BusinessException
     */
    public function doFindAction()
    {
        $oldUserId = $this->getRequest('user_id1', 'int');
        $newUserId = $this->getRequest('user_id2', 'int');
        $googleCode = $this->getRequest('google_code');

        if (empty($oldUserId) || empty($newUserId) || $newUserId == $oldUserId) {
            $this->sendErrorResult('参数错误');
        }

        if (empty($googleCode) || !$this->adminUserRepo->verifyGoogleCode($googleCode)) {
            $this->sendErrorResult("谷歌验证码错误!");
        }
        $result = $this->userRepository->findAccount($oldUserId, $newUserId);
        if ($result) {
            $this->sendSuccessResult();
        }
        $this->sendErrorResult("找回账号失败!");
    }
}