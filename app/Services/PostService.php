<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CommonValues;
use App\Core\Services\BaseService;
use App\Models\PostCategoryModel;
use App\Models\PostModel;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 *  帖子
 * @package App\Services
 * @property CommonService $commonService
 * @property ElasticService $elasticService
 * @property  PostModel $postModel
 * @property  PostCategoryService $postCategoryService
 * @property  PostFavoriteService $postFavoriteService
 * @property  PostLoveService $postLoveService
 * @property  CommentService $commentService
 * @property  UserService $userService
 * @property  UserFollowService $userFollowService
 * @property FollowService $followService
 * @property PostCategoryModel $postCategoryModel
 * @property MrsSystemService $mrsSystemService
 */
class PostService extends BaseService
{

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
        return $this->postModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->postModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->postModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->postModel->findByID($id);
    }

    /**
     * 保存数据
     * @param $data
     * @param $isInsert
     * @return bool|int|mixed
     */
    public function save($data,$isInsert=false)
    {
        if ($data['_id'] && !$isInsert) {
            $result = $this->postModel->update($data, array("_id" => $data['_id']));
            $id = $data['_id'];
        } else {
            if(empty($data['_id'])){
                $data['_id'] = substr(CommonUtil::getId(),8,16);
            }
            $defaultArr = array(
                'click'=>0,
                'real_click'=>0,
                'love' => 0,
                'real_love'=>0,
                'favorite'=>0,
                'real_favorite'=>0,
                'comment'=>0,
                'is_hot'=>0,
                'is_top'=>0,
                'status'=>0,
                'deny_msg'=>'',
                'ip'=>'',
                'province'=>'',
                'city'=>'',
                'sort' => 0,
                'is_public'=>1
            );
            foreach($defaultArr as $field=>$value){
                if(!isset($data[$field])){
                    $data[$field] = $value;
                }
            }
            $id= $result = $this->postModel->insert($data);
        }
        $this->asyncEs($id);
        delCache("post_detail_{$id}");
        return $result;
    }

    /**
     * 修改数据(可以使用操作符)
     * @param  $document
     * @param  $where
     * @return mixed
     * @throws
     */
    public function updateRaw($document = array(), $where = array())
    {
        return $this->postModel->updateRaw($document, $where);
    }


    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $result = $this->postModel->delete(array('_id' => $id));
        if($result){
            $this->elasticService->delete('post',"post", $id);
            delCache("post_detail_{$id}");
        }
        return $result;
    }

    /**
     * 同步到es
     * @param $id
     * @return bool
     */
    public function asyncEs($id)
    {
        $row=$this->findByID($id);
        if (empty($row)) {
            return false;
        }
        $categories = $this->postCategoryService->getAll();
        $row['categories']=value(function ()use($row,$categories){
            $items = array();
            foreach ($row['categories'] as $category){
                if(!empty($categories[$category])){
                    $items[] = array(
                        'id' => $category *1,
                        'name' => $categories[$category]['name'],
                    );
                }
            }
            return $items;
        });
        $row['id'] = $row['_id'];
        $realComment = $this->commentService->sum($id,'post');

        $this->commonService->setRedisCounter("post_comment_{$id}", $realComment);
        $this->commonService->setRedisCounter("post_click_{$id}",$row['real_click']);
        $this->commonService->setRedisCounter("post_favorite_{$id}",$row['real_favorite']);
        $this->commonService->setRedisCounter("post_love_{$id}",$row['real_love']);

        $updated = array(
            'async_at' => time(),
            'comment'  => intval($realComment)
        );
        if ($updated) {
            $this->postModel->updateRaw(array('$set' => $updated), array('_id' => $id));
        }
        unset($row['_id']);
        if(in_array($row['status'],array(1,0,2,3))){
            return $this->elasticService->save($id, $row, 'post', 'post');
        }else{
            return  $this->elasticService->delete('post','post',$id);
        }
    }

    /**
     * 搜索
     * @param array $filter
     * @param int $userId
     * @param $isAll
     * @return array|mixed
     */
    public function doSearch($filter=[],$userId=0,$isAll=false)
    {
        $userId     = intval($userId);
        $page       = $filter['page']?:1;
        $pageSize   = $filter['page_size']?:24;
        $keyword    = $filter['keywords'];
        $catId      = $filter['cat_id'];
        $catIds    =  $filter['cat_ids'];
        $isHot      = $filter['is_hot'];
        $ids        = $filter['ids'];
        $notIds     = $filter['not_ids'];
        $homeId     = $filter['home_id'];
        $homeIds    = $filter['home_ids'];
        $type       = $filter['type'];
        $position   = $filter['position'];
        $order      = $filter['order']?:'sort';
        $from = ($page - 1) * $pageSize;
        if($homeId){
            $position = null;
        }
        if($catIds && strpos($catIds,',')===false){
            $position = null;
        }
        $source = array();
        $query = array(
            'from' => $from,
            'size' => $pageSize,
            'min_score' => 1.0,
            '_source' => $source,
            'query' => array()
        );
        if(!$isAll){
            $query['query']['bool']['must'][] = array(
                'term' => array('status' => 1)
            );
        }else{
            if(isset($filter['status']) && $filter['status']!==""){
                $status    = intval($filter['status']);
                $query['query']['bool']['must'][] = array(
                    'term' => array('status' => $status)
                );
            }else{
                $query['query']['bool']['must']=array();
            }
        }
        switch ($order){
            case "sort":
                $query['sort'] = array(
                    'sort' => array('order' => 'desc'),
                    'created_at' => array('order' => 'desc'),
                );
                break;
            case 'new':
                $query['sort'] = array(
                    'created_at' => array('order' => 'desc'),
                );
                break;
            case 'hot':
                $query['sort'] = array(
                    'is_hot' => array('order' => 'desc'),
                    'created_at' => array('order' => 'desc'),
                );
                break;
            case "rand":
                $query['sort']=[
                    '_script'=>[
                        "script"=>'Math.random()',
                        "type"=>"number",
                        "order"=>"asc"
                    ]
                ];
                break;
        }
        //关键字
        if ($keyword) {
            $keyword = strtoupper($keyword);
            $query['query']['bool']['must'][] = array(
                'multi_match' => array(
                    'query' => $keyword,
                    "type" => "phrase",
                    'fields' => ['title', 'categories.name']
                ));
            $query['min_score'] = '1.2';
        }
        if(!empty($catId)){
            array_push($query['query']['bool']['must'],['terms' => ['categories.id' => explode(',', $catId)]]);
            unset($catId);
        }

        if(!empty($catIds)){
            array_push($query['query']['bool']['must'],['terms' => ['categories.id' => explode(',', $catIds)]]);
            unset($catId);
        }

        /**检查帖子是否公开 只有自己可以查看非公开**/
        $checkIsPublic =  true;
        if(isset($homeId) && $homeId==$userId){
            $checkIsPublic = false;
        }
        if($checkIsPublic){
            array_push($query['query']['bool']['must'],['term' => ['is_public' =>1]]);
            unset($isHot);
        }
        if(!empty($isHot)){
            array_push($query['query']['bool']['must'],['term' => ['is_hot' => $isHot=='y'?1:0]]);
            unset($isHot);
        }
        if(!empty($isTop)){
            array_push($query['query']['bool']['must'],['term' => ['is_top' => $isTop=='y'?1:0]]);
            unset($isNew);
        }
        if(!empty($ids)){
            array_push($query['query']['bool']['must'],['terms' => ['id' => explode(',', $ids)]]);
            unset($ids);
        }
        if(!empty($notIds)){
            array_push($query['query']['bool']['must_not'],['ids' => ['values' => explode(',', $notIds)]]);
            unset($notIds);
        }
        if(!empty($homeId)){
            array_push($query['query']['bool']['must'],['term' => ['user_id' => $homeId]]);
            unset($homeId);
        }
        if(!empty($homeIds)){
            array_push($query['query']['bool']['must'],['terms' => ['user_id' => explode(',', $homeIds)]]);
            unset($homeIds);
        }
        if(!empty($type)){
            array_push($query['query']['bool']['must'],['term' => ['type' => $type]]);
            unset($homeId);
        }
        if($position && !$isAll){
            array_push($query['query']['bool']['must'],['term' => ['position' => $position]]);
            unset($homeId);
        }


        $items = array();
        $result = $this->elasticService->search($query, 'post', 'post');

        //获取计数器
        $redisCounterKeys = [];
        foreach ($result->hits->hits as $item) {
            $id = $item->_source->id;
            $redisCounterKeys[] = "post_click_".$id;
            $redisCounterKeys[] = "post_love_".$id;
            $redisCounterKeys[] = "post_favorite_".$id;
            $redisCounterKeys[] = "post_comment_".$id;
        }
        $counterMap = $this->commonService->getRedisCounters($redisCounterKeys);

        foreach ($result->hits->hits as $item) {
            $item = $item->_source;
            $row  =[
                'id'           => strval($item->id),
                'title'        => strval($item->title),
                'time'         => CommonUtil::ucTimeAgo($item->created_at),
                'pay_type'     => strval($item->pay_type),
                'money'        => strval($item->money),
                'is_hot'       => $item->is_hot?'y':'n',
                'is_top'       => $item->is_top?'y':'n',
                'province'     => strval($item->province),
                'city'         => strval($item->city),
                'type'         => strval($item->type),
                'status'       => strval($item->status * 1),
                'status_text'  => CommonValues::getPostStatus($item->status),
                'is_own'       => $item->user_id==$userId?'y':'n',
                'content'      => strval($item->content),
                'images'       => empty( $item->images)?array(): $item->images,
                'video_path'   => strval($item->video_path),
                'files'        => strval($item->files),
                'can_view'     => 'n',
                'deny_msg'     => strval($item->deny_msg),
                'position'     => strval($item->position),
                'categories'   => value(function()use($item){
                    $categories = array();
                    foreach ($item->categories as $category){
                        $category->id = strval($category->id);
                        $categories[] = $category;
                    }
                    return $categories;
                 }),
                'click'        =>value(function()use($item,$counterMap){
                    $keyName = 'post_click_' . $item->id;
                    $real = $counterMap[$keyName] ?? 0;
                    return strval(intval($item->click+$real));
                }),
                'has_love'      =>value(function ()use($item,$userId){
                    return $this->postLoveService->has($item->id,$userId)?'y':'n';
                }),
                'love'         =>value(function()use($item,$counterMap){
                    $keyName = 'post_love_' . $item->id;
                    $real = $counterMap[$keyName] ?? 0;
                    return strval(intval($item->love+$real));
                }),
                'favorite'     =>value(function()use($item,$counterMap){
                    $keyName = 'post_favorite_' . $item->id;
                    $real = $counterMap[$keyName] ?? 0;
                    return strval(intval($item->favorite+$real));
                }),
                'comment'      => value(function()use($item,$counterMap){
                    $keyName = 'post_comment_'.$item->id;
                    $real = $counterMap[$keyName] ?? 0;
                    return strval(intval($item->comment+$real));
                }),
                'time_label'        => CommonUtil::showTimeDiff($item->created_at),
            ];
            $postUserInfo = $this->userService->getInfoFromCache($item->user_id);
            if(empty($postUserInfo) || $postUserInfo['is_disabled']){
                continue;
            }
            $row['user']=  [
                'id'           =>strval($postUserInfo['id']),
                'nickname'     =>strval($postUserInfo['nickname']),
                'img'          =>$this->commonService->getCdnUrl($postUserInfo['img']),
                'sex'          =>strval($postUserInfo['sex']),
                'is_vip'       => $this->userService->isVip($postUserInfo)?'y':'n',
                'is_up'        =>strval($postUserInfo['is_up']),
                'level'        => strval($postUserInfo['level']*1),
                'level_name'   => CommonValues::getUserLevelName($postUserInfo['level']*1),
                'is_follow'    => $this->userFollowService->has($userId,$item->user_id)?'y':'n'
            ];
            $items[] = $row;
        }
        $items = array_values($items);
        $result=[
            'data'=>$items,
            'total'=>value(function ()use($result){
                if(isset($result->hits->total->value)){
                    return strval($result->hits->total->value);
                }
                return $result->hits->total?strval($result->hits->total):'0';
            }),
            'current_page'=>(string)$page,
            'page_size'=>(string)$pageSize,
        ];
        $result['last_page']=(string)ceil($result['total']/$pageSize);
        return $result;
    }


    /**
     * 获取对应分类
     * @param $ids
     * @return array
     */
    public function getCategoriesByIds($ids)
    {
        if(empty($ids)){return [];}
        $rows=$this->postCategoryService->getList(['_id'=>['$in'=>$ids]],['_id','name'],[],0,1000);
        $result=[];
        foreach ($rows as $row){
            $result[]=[
                'id'    =>$row['_id'],
                'name'  =>$row['name'],
            ];
        }
        return $result;
    }

    /**
     * 事件处理
     * @param $data
     */
    public function handler($data)
    {
        $postId = $data['post_id'];
        switch ($data['action']){
            case 'click':
                $this->commonService->updateRedisCounter("post_click_{$postId}", 1);
                $this->postModel->updateRaw(array('$inc' => array('real_click' => 1)), array('_id' => $postId));
                break;
            case 'favorite':
                $this->commonService->updateRedisCounter("post_favorite_{$postId}", 1);
                $this->postModel->updateRaw(array('$inc' => array('real_favorite' => 1)), array('_id' => $postId));
                break;
            case 'unFavorite':
                $this->commonService->updateRedisCounter("post_favorite_{$postId}", -1);
                $this->postModel->updateRaw(array('$inc' => array('real_favorite' => -1)), array('_id' => $postId));
                break;
            case 'love':
                $this->commonService->updateRedisCounter("post_love_{$postId}", 1);
                $this->postModel->updateRaw(array('$inc' => array('real_love' => 1)), array('_id' => $postId));
                break;
            case 'unLove':
                $this->commonService->updateRedisCounter("post_love_{$postId}", -1);
                $this->postModel->updateRaw(array('$inc' => array('real_love' => -1)), array('_id' => $postId));
                break;
        }
    }

    /**
     * 统计分析标签
     */
    public function countTags()
    {
        $categories = $this->postCategoryService->getAll();
        $configs = $this->commonService->getConfigs();
        foreach ($categories as $category)
        {
            $query = ['categories'=>['$in'=>[$category['id']*1]],'status'=>1];
            $query = [
                ['$match' => $query],
                ['$group' => ['_id' => null, 'count_click' => ['$sum' => '$click'], 'count_real_click' => ['$sum' => '$real_click'],'count_num' => ['$sum' => 1]]]
            ];
            $result = $this->postModel->aggregate($query);
            if($result){
                //统计关注
                $follow= $this->followService->count(array('object_id'=>$category['id']*1,'object_type'=>'post_category'));
                $data = array(
                    '_id' => $category['id'] *1,
                    'post_count'=>$result->count_num*intval($configs['post_multiplication_base']?:1),
                    'post_click'=>$result->count_click*1+$result->count_real_click*1,
                    'post_real_click'=>$result->count_real_click*1,
                    'follow'=>$follow*1
                );
                $this->postCategoryService->save($data);
                LogUtil::debug("Post tag:{$category['id']} count:{$data['post_count']}");
            }
        }
    }

    /**
     * 视频转化帖子
     * @param $mid
     * @return array|null
     */
    public function changeFromMovie($mid)
    {
        if(empty($mid)){
            return null;
        }
        $mediaUrl = $this->commonService->getConfig('media_url');
        $mediaKey = $this->commonService->getConfig('media_key');
        $url = $mediaUrl . "/cxapi/av/list?key={$mediaKey}";
        $result = CommonUtil::httpPost($url, ['id'=>$mid]);
        $result =  empty($result)?null:json_decode($result,true);
        if ($result['status'] != 'y' || empty($result['data']['items'])) {
            return null;
        }
        $result = $result['data']['items'][0];
        if(empty($result) || empty($result['preview_images'])){
            return null;
        }
        $data = array(
            'title' => $result['name'],
            'video_path'=>$result['m3u8_url'],
            'images'=> explode(',',$result['preview_images'])
        );
        setCache('post_mid_'.$mid,$data,60*10);
        return $mid;
    }

    /**
     * 同步公共库的帖子  需要定期去更新 如果使用的云盘内容可能已经域名废弃了
     * @param $mid
     * @return bool
     */
    public function asyncFromMrs($mid)
    {
        $mids = CommonUtil::formatBrToArr($mid);
        if(empty($mids)){
            return false;
        }

        $config=container()->get('config');
        if($config->mrs->source=='laosiji'){//老司机库
            $items = [];
            foreach($mids as $mid){
                $result = $this->mrsSystemService->getLsjPostDetail(['id'=>$mid]);
                if(empty($result)){continue;}
                $items[] = $result;
            }
        }else{
            $items = $this->mrsSystemService->getPostList(['ids'=>join(',',$mids)]);
        }

        foreach ($items as $item)
        {
            if ($this->saveMrsItem($item)) {
                LogUtil::info('Async post ok:' . $item['id']);
            } else {
                LogUtil::error('Async post error:' . $item['id']);
            }
        }
        return true;
    }


    /*
     * 保存帖子
    */
    public function  saveMrsItem($item)
    {
        $checkExits = $this->postModel->count(['_id'=>$item['id']]);
        $categories=[];
        if($item['categories']){
            foreach ($item['categories'] as $category){
                $categoryItem= $this->postCategoryService->findFirst(['_id'=>$category['id']*1]);
                if(empty($categoryItem)){
                    $data =[
                        '_id' => $category['id']*1,
                        'name' => $category['name'],
                        'block_id'=>0,
                        'description'=>'',
                        'sort'=>0
                    ];
                    $this->postCategoryModel->insert($data);
                }
                $categories[] = intval($category['id']);
            }
            if(in_array(68,$categories) && in_array(71,$categories)){
                $categories=[71];
            }
        }
        if($item['created_at']){
            $item['created_at'] = strtotime($item['created_at']);
        }
        if($item['position']){
            $positions = array_keys(CommonValues::getPostPosition());
            $item['position'] = in_array($item['position'],$positions) ? $item['position'] : 'normal';
        }

        $config=container()->get('config');
        if($config->mrs->pay_type&&$config->mrs->pay_type=='free'){
            $item['money'] = -1;
        }else{
            $item['money'] = 0;
        }

        if($checkExits<1){
            $configs = getConfigs();
            $userIds = CommonUtil::parseUserIds($configs['post_user_ids']);
            if((time()-$item['created_at'])<24*3600){
                $click = mt_rand(12000,16666);
                $love = mt_rand(58,189);
                $favorite = mt_rand(28,160);
            }elseif ((time()-$item['created_at'])<7*24*3600){
                $click = mt_rand(18000,45000);
                $love = mt_rand(300,1500);
                $favorite = mt_rand(300,1500);
            }else{
                $click = mt_rand(45000,152000);
                $love = mt_rand(700,3500);
                $favorite = mt_rand(700,3500);
            }
            $row = array(
                '_id'            => $item['id'],
                'title'          => $item['title'],
                'position'       => $item['position'],
                'content'        => $item['content'],
                'img'            => $item['img'],
                'images'         => empty($item['images'])?[]:$item['images'],
                'categories'     => $categories,
                'video_path'     => $item['video_path'],
                'deny_msg'       => '',
                'files'          => $item['files'],
                'type'           => $item['type'],
                'money'          => intval($item['money']),
                'is_hot'         => 0,
                'status' => value(function ()use($config){
                    if($config->mrs->auto&&$config->mrs->auto == 'y'){
                        return 1;
                    }
                    return 0;
                }),
                'is_async_mms'   => 1, //标记是资源库过来的
                'click'          => $click,
                'love'           => $love,
                'favorite'       => $favorite,
                'created_at'     => $item['created_at'],
                'user_id'        => $userIds[mt_rand(0,count($userIds)-1)]*1
            );
            if($item['release_date']){
                $releaseDate = strtotime($item['release_date']);
                if($releaseDate>strtotime('2005-02-01')){
                    $row['created_at'] = $releaseDate;
                }
            }

            //其他内的高质量帖子自动设置热门
            if($item['star'] && in_array($item['star'],[5]) && $item['created_at']>(time()-3600*24*7)){
                $row['sort'] = mt_rand(time()-24*3600*2,time());
                $row['is_hot']=1;
            }

            $row['pay_type']= CommonValues::getPayTypeByMoney($row['money']);
            if(empty($row['img']) && !empty($row['images'])){
                $row['img'] = $row['images'][0];
            }
            $nickname= '';
            $deviceId='';
            if($item['author']){
                $nickname=$item['author'];
                $deviceId = 'up_'.$nickname;
            }elseif ($item['username']){
                $deviceId = 'up_'.substr(md5($item['username']),8,16);
                $nickname = '';
            }
            if($deviceId){
                $userId = $this->userService->createUpUser($deviceId,$nickname,'post');
                if($userId){
                    $row['user_id']=$userId*1;
                }
            }
            $postId=$this->save($row,true);
            $this->asyncEs($postId);
            return $postId;
        }else{
            $row = array(
                '_id'            => $item['id'],
                'files'          => $item['files'],
                'content'        => $item['content'],
                'img'            => $item['img'],
                'images'         => empty($item['images'])?[]:$item['images'],
                'categories'     => $categories,
                'video_path'     => $item['video_path'],
                'created_at'    =>  $item['created_at']
            );
            if($item['release_date']){
                $releaseDate = strtotime($item['release_date']);
                if($releaseDate>strtotime('2005-02-01')){
                    $row['created_at'] = $releaseDate;
                }
            }
            if($row['images']  && count($row['images'])>3){
                $row['sort'] = mt_rand($row['created_at'],time())*1;
            }
            if($item['star'] && in_array($item['star'],[4,5])){
                $row['sort'] = mt_rand($row['created_at'],time())*1;
                $row['is_hot']=1;
            }
            $this->save($row);
            $this->asyncEs($item['id']);
            return $item['id'];
        }
    }

}