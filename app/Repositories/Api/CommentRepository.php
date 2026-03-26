<?php


namespace App\Repositories\Api;


use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\CommentLoveService;
use App\Services\CommentService;
use App\Utils\CommonUtil;

/**
 * Class CommentRepository
 * @property CommentService $commentService
 * @property CommentLoveService $commentLoveService
 * @package App\Repositories\Api
 */
class CommentRepository extends BaseRepository
{
    /**
     * @param $userId
     * @param $type
     * @param $id
     * @param $content
     * @param int $time
     * @return bool|int|mixed
     * @throws BusinessException
     */
    public function doComment($userId,$type,$id,$content,$time=0)
    {
        return $this->commentService->do($userId,$type,$id,$content,$time,false);
    }


    /**
     * 发布评论
     * @param $userId
     * @param $id
     * @param $content
     * @param $type
     * @return string
     * @throws BusinessException
     */
    public function doReply($userId,$id,$content,$type)
    {
        return $this->commentService->doReply($userId,$id,$content,$type);
    }

    /**
     * @param $userId
     * @param $id
     * @param $type
     * @param $page
     * @return array
     * @throws BusinessException
     */
    public function commentList($userId,$id,$type,$page)
    {
        return $this->commentService->getCommentList($userId,$id,$type,$page);
    }


    /**
     * @param $userId
     * @param $commentId
     * @return bool
     * @throws BusinessException
     */
    public function doLove($userId,$commentId)
    {
        return $this->commentLoveService->do($userId,$commentId);
    }
}