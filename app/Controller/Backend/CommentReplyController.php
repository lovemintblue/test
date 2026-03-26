<?php


namespace App\Controller\Backend;


use App\Controller\BaseBackendController;
use App\Repositories\Backend\CommentReplyRepository;
use App\Repositories\Backend\CommentRepository;
use App\Services\UserService;

/**
 * Class CommentReplyController
 * @property CommentRepository $commentRepository
 * @property CommentReplyRepository $commentReplyRepository
 * @property UserService $userService
 * @package App\Controller\Backend
 */
class CommentReplyController extends BaseBackendController
{
    /**
     * _id 查询
     */
    public function replyAction()
    {
        $commentId = $this->getRequest('_id');
        if(!$commentId){
            $this->sendErrorResult('参数错误');
        }
        $comment = $this->commentRepository->findById($commentId);
        if(empty($comment)){
            $this->sendErrorResult('评论不存在');
        }
        if ($this->isPost()) {
            $_REQUEST['comment_id']=$commentId;
            $result = $this->commentReplyRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->pick('commentReply/list');
    }


    /**
     * 视频回复
     */
    public function movieAction()
    {
        $this->checkPermission('/replyMovie');
        if($this->isPost()){
            $_REQUEST['object_type']='movie';
            $result = $this->commentReplyRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->pick('commentReply/list');
    }

    /**
     * 帖子评论回复
     */
    public function postAction()
    {
        $this->checkPermission('/replyPost');
        if($this->isPost()){
            $_REQUEST['object_type']='post';
            $result = $this->commentReplyRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->pick('commentReply/list');
    }

    /**
     * 漫画评论回复
     */
    public function cartoonAction()
    {
        $this->checkPermission('/replyCartoon');
        if($this->isPost()){
            $_REQUEST['object_type']='cartoon';
            $result = $this->commentReplyRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->pick('commentReply/list');
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
                    $this->commentReplyRepository->delete($id);
                }
                break;
            case 'delAndDis':
                foreach ($ids as $id) {
                    $this->commentReplyRepository->delete($id,true);
                }
                break;
        }

        return $this->sendSuccessResult();
    }
}