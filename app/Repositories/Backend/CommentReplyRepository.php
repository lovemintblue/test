<?php


namespace App\Repositories\Backend;


use App\Core\Repositories\BaseRepository;
use App\Services\CartoonService;
use App\Services\CommentReplyService;
use App\Services\CommentService;
use App\Services\CommonService;
use App\Services\MovieService;
use App\Services\PostService;
use App\Services\UserService;

/**
 * Class CommentReplyRepository
 * @property CommentReplyService $commentReplyService
 * @property UserService $userService
 * @property CommentService $commentService
 * @property CartoonService $cartoonService
 * @property MovieService $movieService
 * @property CommonService $commonService
 * @property PostService  $postServices
 * @package App\Repositories\Backend
 */
class CommentReplyRepository extends BaseRepository
{
    public function getList($request=[])
    {
        $page = $this->getRequest($request, 'page', 'int', 1);
        $pageSize = $this->getRequest($request, 'pageSize', 'int', 30);
        $sort     = $this->getRequest($request, 'sort', 'string', '_id');
        $order    = $this->getRequest($request, 'order', 'int', -1);
        $query = array();
        $filter = array();

        if ($request['comment_id']) {
            $filter['comment_id'] = $this->getRequest($request, 'comment_id');
            $query['comment_id'] = $filter['comment_id'];
        }
        if ($request['from_uid']) {
            $filter['from_uid'] = $this->getRequest($request, 'from_uid','int');
            $query['from_uid'] = $filter['from_uid'];
        }

        if ($request['object_type']) {
            $filter['object_type'] = $this->getRequest($request, 'object_type','string');
            $query['object_type'] = $filter['object_type'];
        }
        if ($request['content']) {
            $filter['content'] = $this->getRequest($request, 'content','string');
            $query['content']  = array('$regex' => $filter['content'], '$options' => 'i');
        }

        $skip = ($page - 1) * $pageSize;
        $fields = array();
        $count = $this->commentReplyService->count($query);
        $items = $this->commentReplyService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $userInfo = $this->userService->findByID($item['from_uid']);
            $item['user_status']=1;
            if(empty($userInfo) || $userInfo['is_disabled']){
                $item['status']  = 0;
                $item['user_status']  = 0;
            }
            $item['nickname'] = $userInfo['nickname']?:'已删除';
            $item['created_at']= dateFormat($item['created_at']);
            $items[$index] = $item;
        }
        return array(
            'filter' => $filter,
            'items' => empty($items) ? array() : array_values($items),
            'count' => $count,
            'last_page' => ceil($count/$pageSize),
            'page' => $page,
            'pageSize' => $pageSize
        );
    }


    /**
     * 删除回复
     * @param $replyId
     * @param bool $disabledUser
     * @return bool|mixed
     */
    public function delete($replyId,$disabledUser=false)
    {
        $reply = $this->commentReplyService->findByID($replyId);
        if(empty($reply)){
            return false;
        }
        $comment = $this->commentService->findByID($reply['comment_id']);
        $this->commentReplyService->delete($replyId);
        $commentReplyCount = $this->commentReplyService->count(array(
            'comment_id' => $reply['comment_id']
        ));
        $this->commentService->save(array('_id'=>$comment['_id'],'child_num'=>$commentReplyCount*1));
        if($comment['object_type']=='movie'){
            $this->movieService->asyncEs($comment['object_id']);
        }elseif ($comment['object_type']=='post'){
            $this->postServices->asyncEs($comment['object_id']);
        }
        if($disabledUser){
            $this->userService->doDisabled($reply['from_uid'],'评论违规');
        }
        return true;
    }
}