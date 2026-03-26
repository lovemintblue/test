<?php


namespace App\Controller\Backend;


use App\Controller\BaseBackendController;
use App\Repositories\Backend\CommentReplyRepository;
use App\Repositories\Backend\CommentRepository;
use App\Services\UserService;

/**
 * Class CommentController
 * @property CommentRepository $commentRepository
 * @property CommentReplyRepository $commentReplyRepository
 * @property UserService $userService
 * @package App\Controller\Backend
 */
class CommentController extends BaseBackendController
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
    public function comicsAction()
    {
        $this->checkPermission('/comment/comics');
        $objectId = $this->getRequest('object_id','string');
        if ($this->isPost()) {
            $_REQUEST['object_type']='comics';
            $result = $this->commentRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->pick('comment/list');
        $this->view->setVar('object_title','漫画');
        $this->view->setVar('object_id',$objectId);
        $this->view->setVar('object_type','comics');
    }

    /**
     * 列表
     */
    public function movieAction()
    {
        $this->checkPermission('/commentMovie');
        $objectId = $this->getRequest('object_id','string');
        if ($this->isPost()) {
            $_REQUEST['object_type']='movie';
            $result = $this->commentRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->pick('comment/list');
        $this->view->setVar('object_title','视频');
        $this->view->setVar('object_id',$objectId);
        $this->view->setVar('object_type','movie');
    }

    /**
     * 列表
     */
    public function postAction()
    {
        $this->checkPermission('/commentPost');
        $objectId = $this->getRequest('object_id','string');
        if ($this->isPost()) {
            $_REQUEST['object_type']='post';
            $result = $this->commentRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->pick('comment/list');
        $this->view->setVar('object_title','帖子');
        $this->view->setVar('object_id',$objectId);
        $this->view->setVar('object_type','post');
    }

    /**
     * 小说列表
     */
    public function novelAction()
    {
        $this->checkPermission('/comment/novel');
        $objectId = $this->getRequest('object_id','string');
        if ($this->isPost()) {
            $_REQUEST['object_type']='novel';
            $result = $this->commentRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->pick('comment/list');
        $this->view->setVar('object_title','小说');
        $this->view->setVar('object_id',$objectId);
        $this->view->setVar('object_type','novel');
    }

    /**
     * 发布评论
     */
    public function commentAction()
    {
        $objectId = $this->getRequest('object_id','string');
        $objectType = $this->getRequest('object_type','string');
        if(empty($objectId)){
            $this->sendErrorResult('参数错误');
        }
        $this->view->setVar('object_id',$objectId);
        $this->view->setVar('object_type',$objectType);
    }


    /**
     * 发布评论
     * @throws \App\Exception\BusinessException
     */
    public function doCommentAction()
    {
        $objectId   = $this->getRequest('object_id','string');
        $objectType = $this->getRequest('object_type','string');
        $userId    = $this->getRequest('user_id','int');
        $content   = $this->getRequest('content','string');
        $result = $this->commentRepository->doComment($userId,$objectType,$objectId,$content);
        $this->sendSuccessResult($result);
    }


    /**
     * 回复
     * @throws \App\Exception\BusinessException
     */
    public function doReplyAction()
    {
        $commentId = $this->getRequest('comment_id','string');
        $userId    = $this->getRequest('user_id','int');
        $content   = $this->getRequest('content','string');
        $type      = $this->getRequest('type','string');
        if(empty($commentId)||empty($userId)||empty($content)||empty($type)){
            $this->sendErrorResult('参数错误');
        }
        $result = $this->commentRepository->doReply($userId,$type,$commentId,$content);
        $this->sendSuccessResult($result);
    }

    /**
     *
     */
    public function doAction()
    {
        $ids = $this->getRequest("id");
        $act = $this->getRequest("act");
        if (empty($ids) || empty($act)) {
            return $this->sendErrorResult("参数错误!");
        }

        $ids = explode(',', $ids);
        $ids = array_unique($ids);
        switch ($act){
            case 'del':
                foreach ($ids as $id) {
                    $this->commentRepository->delete($id);
                }
                break;
            case 'delAndDis':
                foreach ($ids as $id) {
                    $this->commentRepository->delete($id,true);
                }
                break;
        }

        return $this->sendSuccessResult();
    }
}