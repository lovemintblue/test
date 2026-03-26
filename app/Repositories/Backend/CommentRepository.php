<?php


namespace App\Repositories\Backend;


use App\Constants\CommonValues;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\CommentReplyService;
use App\Services\CommentService;
use App\Services\CommonService;
use App\Services\MovieService;
use App\Services\PostService;
use App\Services\UserService;
use App\Utils\CommonUtil;

/**
 * Class CommentRepository
 * @property CommentService $commentService
 * @property MovieService $movieService
 * @property CommentReplyService $commentReplyService
 * @property UserService $userService
 * @property CommonService $commonService
 * @property PostService $postService
 * @package App\Repositories\Backend
 */
class CommentRepository extends BaseRepository
{
    /**
     * 获取列表
     * @param $request
     * @return array
     */
    public function getList($request)
    {

        $page = $this->getRequest($request, 'page', 'int', 1);
        $pageSize = $this->getRequest($request, 'pageSize', 'int', 30);
        $sort     = $this->getRequest($request, 'sort', 'string', '_id');
        $order    = $this->getRequest($request, 'order', 'int', -1);
        $query = array();
        $filter = array();

        if ($request['object_id']) {
            $filter['object_id'] = $this->getRequest($request, 'object_id','string');
            $query['object_id'] = $filter['object_id'];
        }
        if ($request['object_type']) {
            $filter['object_type'] = $this->getRequest($request, 'object_type','string');
            $query['object_type'] = $filter['object_type'];
        }
        if (isset($request['from_uid'])&& $request['from_uid']!=="") {
            $filter['from_uid'] = $this->getRequest($request, 'from_uid','int');
            $query['from_uid']  = $filter['from_uid'];
        }
        if (isset($request['status'])&& $request['status']!=="") {
            $filter['status'] = $this->getRequest($request, 'status','int');
            $query['status']  = $filter['status'];
        }
        if (isset($request['content'])&& $request['content']!=="") {
            $filter['content'] = $this->getRequest($request, 'content','string');
            $query['content']  = array('$regex' => $filter['content'], '$options' => 'i');
        }

        $count = $this->commentService->count($query);
        $items = $this->commentService->getList($query, [], array($sort => $order), ($page - 1) * $pageSize, $pageSize);
        foreach ($items as $index => $item) {
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);
            $item['status_label'] = $item['status']?'正常':'删除';
//                $item['content']    = mb_strlen($item['content'])>40?mb_strstr($item['content'],0,40):$item['content'];
            $item['user_status']=1;
            $userInfo = $this->userService->findByID($item['from_uid']);
            $item['user_status']=1;
            $item['nickname'] = $userInfo['nickname']?:'已删除';
            if(empty($userInfo) || $userInfo['is_disabled']){
                $item['status']  = 0;
                $item['user_status']  = 0;
            }
            $items[$index] = $item;
        }

        return array(
            'filter' => $filter,
            'items' => empty($items) ? array() : array_values($items),
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
        );
    }

    /**
     *
     * @param $id
     * @return mixed
     */
    public function findById($id)
    {
        return $this->commentService->findByID($id);
    }

    /**
     * @param $userId
     * @param $objectType
     * @param $objectId
     * @param $content
     * @return bool|int|mixed
     * @throws BusinessException
     */
    public function doComment($userId,$objectType,$objectId,$content)
    {
        return $this->commentService->do($userId,$objectType,$objectId,$content,0,true);
    }

    /**
     * @param $userId
     * @param $type
     * @param $id
     * @param $content
     * @return string
     * @throws BusinessException
     */
    public function doReply($userId,$type,$id,$content)
    {
        return $this->commentService->doReply($userId,$type,$id,$content);
    }


    /**
     * 更新数据
     * @param $data
     * @return bool|int|mixed
     */
    public function update($data)
    {
        return $this->commentService->save($data);
    }

    /**
     * @param $id
     * @param bool $disabledUser
     * @return mixed
     */
    public function delete($id,$disabledUser=false)
    {
        $comment = $this->commentService->findByID($id);
        if($disabledUser){
            $this->userService->doDisabled($comment['from_uid'],'评论违规');
        }
        $this->commentService->delete($id);
        $this->commentReplyService->deleteMany(array(
            'comment_id' => $id
        ));
        if($comment['type']=='post'){
            $this->postService->asyncEs($comment['object_id']);
        }elseif ($comment['type']=='movie'){
            $this->movieService->asyncEs($comment['object_id']);
        }
        return true;
    }


}