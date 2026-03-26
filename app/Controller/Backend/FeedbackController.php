<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\CommonRepository;
use App\Repositories\Backend\ChatRepository;
use App\Repositories\Backend\QuickReplyRepository;

/**
 * 反馈管理
 *
 * @package App\Controller\Backend
 *
 * @property  ChatRepository $chatRepository
 * @property  CommonRepository $commonRepo
 * @property  QuickReplyRepository $quickReplyRepo
 */
class FeedbackController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
    }

    /**
     * 列表
     */
    public function listAction()
    {
        $this->checkPermission('/feedback');
        if($this->isPost()){
            $_REQUEST['user_id']=-1;
            $result = $this->chatRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('chatStatus',CommonValues::getChatStatus());
    }

    /**
     * VIP专属列表
     */
    public function vipAction()
    {
        $this->checkPermission('/feedbackVip');
        if($this->isPost()){
            $_REQUEST['user_id']=-2;
            $result = $this->chatRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('chatStatus',CommonValues::getChatStatus());
        $this->view->pick('feedback/list');
    }

    /**
     * 详情
     * @throws BusinessException
     */
    public function detailAction()
    {
        $id = $this->getRequest("_id");
        if (!empty($id)) {
            $result = $this->chatRepository->getDetail($id);
            $this->view->setVar('row', $result);
        }
        $this->view->setVar('quickMessages', $this->quickReplyRepo->getAll());
    }

    /**
     * 会话消息列表
     */
    public function messageAction()
    {
        $rows = $this->chatRepository->getMessageList($_REQUEST);
        $this->sendSuccessResult($rows);
    }


    /**
     * 保存
     * @throws BusinessException
     */
    public function saveAction()
    {
        $token      = $this->getToken();
        $userId     = $this->getRequest('user_id', 'int');
        $toUserId   = $this->getRequest('to_user_id', 'int');
        $content    = $this->getRequest('content','string');
        $type       = $this->getRequest('type','string');
        if (empty($toUserId) || empty($content) || empty($type)) {
            $this->sendErrorResult("参数错误");
        }
        $result = $this->chatRepository->sendMessage($userId, $toUserId, $type, $content,'客服:'.$token['username']);
        if ($result) {
            $this->sendSuccessResult();
        }
        $this->sendErrorResult("保存错误!");
    }

}