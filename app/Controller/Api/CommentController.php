<?php


namespace App\Controller\Api;


use App\Controller\BaseApiController;
use App\Repositories\Api\CommentRepository;
use App\Utils\LogUtil;

/**
 * Class CommentController
 * @property CommentRepository $commentRepository
 * @package App\Controller\Api
 */
class CommentController extends BaseApiController
{
    public function initialize()
    {
        parent::initialize();
    }

    /**
     * 发布评论
     * @throws \App\Exception\BusinessException
     */
    public function doAction()
    {
        $userId     = $this->getUserId();
        $id         = $this->getRequest('id', 'string');
        $content    = $this->getRequest('content','string');
        $time       = $this->getRequest('time','string');
        $type       = $this->getRequest('type','string');
        $result     = $this->commentRepository->doComment($userId,$type,$id,$content,$time);
        $this->sendSuccessResult($result);
    }

    /**
     * 回复
     * @throws \App\Exception\BusinessException
     */
    public function doReplyAction()
    {
        $userId     = $this->getUserId();
        $id         = $this->getRequest('id', 'string');
        $content    = $this->getRequest('content','string');
        $type      = $this->getRequest('type','string');
        $result     = $this->commentRepository->doReply($userId,$id,$content,$type);
        $this->sendSuccessResult($result);
    }

    /**
     * 评论列表
     * @throws \App\Exception\BusinessException
     */
    public function logsAction()
    {
        $userId= $this->getUserId();
        $id    = $this->getRequest('id', 'string');
        $page  = $this->getRequest('page', 'int',1);
        $type  = $this->getRequest('type','string');
        $result= $this->commentRepository->commentList($userId,$id,$type,$page);
        $this->sendSuccessResult($result);
    }

    public function replyListAction()
    {

    }

    /**
     * 评论点赞
     * @throws \App\Exception\BusinessException
     */
    public function doLoveAction()
    {
        $userId= $this->getUserId();
        $id    = $this->getRequest('id', 'string');
        $result= $this->commentRepository->doLove($userId,$id);
        $this->sendSuccessResult(array(
            'status' => $result?'y':'n'
        ));
    }
}