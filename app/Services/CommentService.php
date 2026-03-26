<?php


namespace App\Services;


use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Models\CommentModel;
use App\Models\CommentReplyModel;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 * Class CommentService
 * @property CommentModel $commentModel
 * @property CommentReplyModel $commentReplyModel
 * @property UserService $userService
 * @property ComicsService $comicsService
 * @property MovieService $movieService
 * @property PostService $postService
 * @property CommonService $commonService
 * @property PlayService $playService
 * @property CommentLoveService $commentLoveService
 * @property UserTaskService $userTaskService
 * @property NovelService $novelService
 * @property ElasticService $elasticService
 * @package App\Services
 */
class CommentService extends BaseService
{

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->commentModel->count($query);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->commentModel->delete(['_id'=>$id]);
    }

    /**
     * 获取列表
     * @param array $query
     * @param array $fields
     * @param array $sort
     * @param int $skip
     * @param int $limit
     * @return array
     */
    public function getList($query = array(), $fields = array(), $sort = array(), $skip = 0, $limit = 10)
    {
        return $this->commentModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->commentModel->findByID($id);
    }

    /**
     *
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->commentModel->update($data, array("_id" => $data['_id']));
        } else {
             $data['_id'] = CommonUtil::getId(true);
             $this->commentModel->insert($data);
             return $data['_id'];
        }
    }

    /**
     * 获取评论总数
     * @param $objectId
     * @param $objectType
     * @return int
     */
    public function sum($objectId,$objectType)
    {
        $query      = ['object_id'=>$objectId,'object_type'=>$objectType];
        $commentNum = $this->commentModel->count($query);
        $childNum   = $this->commentModel->aggregate([
            ['$match' => $query],
            ['$group' => ['_id' => null, 'count' => ['$sum' => '$child_num']]]
        ]);
        $childNum   =$childNum?$childNum->count:0;
        return intval($commentNum+$childNum);
    }


    /**
     * 去评论
     * @param $userId
     * @param $objectType
     * @param $objectId
     * @param $content
     * @param int $time
     * @param bool $isAdmin
     * @return mixed
     * @throws BusinessException
     */
    public function do($userId,$objectType,$objectId,$content,$time=0,$isAdmin=false)
    {
        if (empty($objectId) || empty($content)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '请检查必要输入!');
        }
        $userInfo = $this->userService->getInfoFromCache($userId);
        $this->userService->checkUser($userInfo);
        if (!CommonUtil::checkKeywords($content)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '内容不能含有关键字和广告!');
        }
        if (preg_match('/\d\d+/',$content)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '内容不能含有关键字和广告!');
        }
        if (preg_match('/\d\s*[\d]/',$content)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '内容不能含有关键字和广告!');
        }
        if($isAdmin==false){
            //会员评论
//            if ($userInfo['is_vip']=='n') {
//                throw new BusinessException(StatusCode::DATA_ERROR, 'VIP用户才可评论!');
//            }
            if (!$this->commonService->checkActionLimit('do_comment_' . $userId, 60*5, 2)) {
                throw new BusinessException(StatusCode::DATA_ERROR, '发布评论过快,请稍等几分钟!');
            }
        }
        switch ($objectType){
            case 'comics':
                $model = $this->comicsService->count(array('_id'=>$objectId));
                if (empty($model)) {
                    throw new BusinessException(StatusCode::DATA_ERROR, '漫画已下架!');
                }
                break;
            case 'movie':
                $model = $this->movieService->count(array('_id'=>$objectId));
                if (empty($model)) {
                    throw new BusinessException(StatusCode::DATA_ERROR, '视频已下架!');
                }
                break;
            case 'post':
                $model = $this->postService->count(array('_id'=>$objectId));
                if (empty($model)) {
                    throw new BusinessException(StatusCode::DATA_ERROR, '帖子不存在!');
                }
                break;
            case 'novel':
                $model = $this->novelService->count(array('_id'=>$objectId));
                if (empty($model)) {
                    throw new BusinessException(StatusCode::DATA_ERROR, '小说不存在!');
                }
                break;
        }

        $data = array(
            'from_uid'  => $userId,
            'object_id' => $objectId,
            'object_type'=> $objectType,
            'content'   => $content,
            'love'      => 0,
            'ip'        => getClientIp(),
            'status'    => 1,
            'child_num' => 0,
            'time'      => $time,
            'created_at' => time(),
        );
        $commentId = $this->save($data);
        $data['_id'] = $commentId;
        $this->handler($objectId,$objectType);
        if($objectType == 'movie'){
            $movie = $this->elasticService->get($objectId, 'movie', 'movie');
            $movieName = $movie['name']??'';
            $movieCatId = $movie['categories']['id']??'';
            $movieCatName = $movie['categories']['name']??'';
            DataCenterService::doMovieComment($objectId, $movieName,$movieCatId,$movieCatName,$content);
        }

        //评论任务
        $this->userTaskService->doCommentTask($userId,$commentId);
        return  $this->formatComment($userId,$data,$userInfo);
    }

    /**
     * 回复评论
     * @param $userId
     * @param $id
     * @param $content
     * @param $type
     * @return mixed
     * @throws BusinessException
     */
    public function doReply($userId,$id,$content,$type)
    {
        if (empty($id) || empty($content)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '请检查必要输入!');
        }
        $userInfo = $this->userService->getInfoFromCache($userId);
        $this->userService->checkUser($userInfo);
        if (!CommonUtil::checkKeywords($content)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '内容不能含有关键字和广告!');
        }
        if (preg_match('/\d\d+/',$content)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '内容不能含有关键字和广告!');
        }
        if (preg_match('/\d\s*[\d]/',$content)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '内容不能含有关键字和广告!');
        }
        //会员评论
//        if ($userInfo['is_vip']=='n') {
//            throw new BusinessException(StatusCode::DATA_ERROR, 'VIP用户才可评论!');
//        }
        if (!$this->commonService->checkActionLimit('do_comment_' . $userId, 60*5, 2)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '发布评论过快,请稍等几分钟!');
        }
        $data = array();
        $childToUserInfo = array();
        //评论
        if($type=='comment'){
            //检查评论是否存在
            $comment=$this->findByID($id);
            if (!$comment) {
                throw new BusinessException(StatusCode::DATA_ERROR, '评论不存在!');
            }
            $data=[
                '_id'       => CommonUtil::getId(true),
                'comment_id'=> $id,
                'reply_id'  => $id,
                'reply_type'=> 'comment',
                'object_type'=>$comment['object_type'],
                'content'   => $content,
                'from_uid'  => $userId,
                'to_uid'    => '',
                'status'    => 1,
                'created_at' => time(),
            ];
            $this->commentReplyModel->insert($data);
            $this->commentModel->updateRaw(['$inc'=>['child_num'=>1]],['_id'=>$id]);
        }elseif ($type=='reply'){
            //获取被回复的评论
            $replyModel =$this->commentReplyModel->findByID($id);
            $comment    =$this->findByID($replyModel['comment_id']);
            $data=[
                '_id'       => CommonUtil::getId(true),
                'comment_id'=> $replyModel['comment_id'],
                'reply_id'  => $id,
                'reply_type'=> 'reply',
                'object_type'=>$comment['object_type'],
                'content'   => $content,
                'from_uid'  => $userId,
                'to_uid'    => $replyModel['from_uid'],
                'status'    => 1,
                'created_at' => time(),
            ];
            $childToUserInfo  = $this->userService->getInfoFromCache($replyModel['from_uid']);
            $this->commentReplyModel->insert($data);
            $this->commentModel->updateRaw(['$inc'=>['child_num'=>1]],['_id'=>$data['comment_id']]);
        }else{
            throw new BusinessException(StatusCode::DATA_ERROR, '不支持该回复类型!');
        }
        $this->handler($comment['object_id'],$comment['object_type']);
        return  $this->formatReplay($data,$userInfo,$childToUserInfo);
    }

    /**
     * 评论事件
     * @param $objectId
     * @param $objectType
     */
    public function handler($objectId,$objectType)
    {
        $objectId=intval($objectId);
        $comment=$this->commonService->updateRedisCounter("{$objectType}_comment_{$objectId}",1);
        if($objectType=='cartoon'){
            $this->cartoonService->updateRaw(['$set'=>['comment'=>$comment]],['_id'=>$objectId]);
        }elseif ($objectType=='movie'){
            $this->movieService->updateRaw(['$set'=>['comment'=>$comment]],['_id'=>$objectId]);
        }elseif ($objectType=='post'){
            $this->postService->updateRaw(['$set'=>['comment'=>$comment,'last_comment'=>time()]],['_id'=>$objectId]);
        }elseif ($objectType=='play'){
            $this->playService->updateRaw(['$set'=>['comment'=>$comment,'last_comment'=>time()]],['_id'=>$objectId]);
        }
    }

    /**
     * 获取评论列表
     * @param $userId
     * @param $objectId
     * @param $objectType
     * @param int $page
     * @param int $pageSize
     * @return array
     * @throws BusinessException
     */
    public function getCommentList($userId,$objectId,$objectType,$page=1,$pageSize=15)
    {
        if (!in_array($objectType,['comics','movie','post','play','novel'])) {
            throw new BusinessException(StatusCode::DATA_ERROR, '不支持该类型!');
        }
        $skip   = ($page - 1) * $pageSize;
        $rows = $this->getList(['object_id'=>$objectId,'object_type'=>$objectType,'status'=>1],[],['child_num'=>-1,'love'=>-1,'_id'=>-1,],$skip,$pageSize);
        $result=[];
        foreach ($rows as $key=>$row) {
            $userInfo = $this->userService->getInfoFromCache($row['from_uid']);
            if(empty($userInfo)||$userInfo['is_disabled']==1){
                $userInfo=['nickname'=>'已销号','id'=>$row['from_uid'],'img'=>'','is_disabled'=>1];
            }
            $item   = $this->formatComment($userId,$row,$userInfo);
            $children  = $this->commentReplyModel->find(['comment_id'=>$row['_id'],'status'=>1],[],['_id'=>1],0,30);
            foreach ($children as $child) {
                $childUserInfo = $this->userService->getInfoFromCache($child['from_uid']);
                if(empty($childUserInfo)||$childUserInfo['is_disabled']==1){
                    continue;
                }
                $childToUserInfo = $this->userService->getInfoFromCache($child['to_uid']);
                if(empty($childToUserInfo)||$childToUserInfo['is_disabled']==1){
                    $childToUserInfo=['nickname'=>$child['to_uid']?'已销号':'','id'=>$child['to_uid'],'img'=>'','is_disabled'=>1];
                }
                $item['child'][]= $this->formatReplay($child,$childUserInfo,$childToUserInfo);
            }
            $result[]=$item;
        }
        if($page==1){
            $userInfo = $this->userService->getInfoFromCache(-1);
            $defaultRow = array(
                '_id' => strval(-1),
                'created_at'=>time(),
                'status' => 1,
                'content'   => "官方提醒您,评论区中QQ,微信等联系方式均为骗子,请勿相信\n------------------\n🔥🔥🔥来自真实用户被骗后的反馈🔥🔥🔥\n此评论系统生成,无法回复",
            );
            array_unshift($result,$this->formatComment($userId,$defaultRow,$userInfo));
        }

        return $result;
    }

    /**
     * 格式化标注的评论列表
     * @param $userId
     * @param $row
     * @param $userInfo
     * @return array
     */
    public function  formatComment($userId,$row,$userInfo)
    {
       return [
            'id'          => strval($row['_id']),
            'user_id'     => strval($userInfo['id']),
            'nickname'    => strval($userInfo['nickname']),
            'img'         => $this->commonService->getCdnUrl($userInfo['img']),
            'content'     => strval($userInfo['is_disabled']==0&&$row['status']?$row['content']:'该评论已被删除'),
            'love'        => strval($row['love']<=0?0:$row['love']*1),
            'has_love'    => $this->commentLoveService->has($userId,$row['_id'])?'y':'n',
            'label'       => CommonUtil::ucTimeAgo($row['created_at']),
            'child_num'   => strval($row['child_num']?:0),
            'child'       => [],
        ];
    }

    /**
     * 格式化回复
     * @param $child
     * @param $childUserInfo
     * @param $childToUserInfo
     * @return array
     */
    public function formatReplay($child,$childUserInfo,$childToUserInfo)
    {
       return [
            'id'        => strval($child['_id']),
            'reply_type'=> $child['reply_type'],
            'content'   => strval($childUserInfo['is_disabled']==0&&$child['status']?$child['content']:'该评论已被删除'),
            'from_uid'  => strval($childUserInfo['id']),
            'from_uname'=> strval($childUserInfo['nickname']),
            'from_uimg' => $this->commonService->getCdnUrl($childUserInfo['img']),
            'to_uid'    => strval($child['to_uid']),
            'to_uname'  => strval($childToUserInfo['nickname']),
            'label'     => CommonUtil::ucTimeAgo($child['created_at']),
        ];
    }
}