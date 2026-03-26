<?php


namespace App\Repositories\Api;


use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\AccountService;
use App\Services\AdvService;
use App\Services\CommonService;
use App\Services\DataCenterService;
use App\Services\FollowService;
use App\Services\PostCategoryService;
use App\Services\PostFavoriteService;
use App\Services\PostLoveService;
use App\Services\PostService;
use App\Services\UserBuyLogService;
use App\Services\UserFollowService;
use App\Services\UserService;
use App\Utils\CommonUtil;

/**
 * Class PostRepository
 * @property PostCategoryService $postCategoryService
 * @property CommonService $commonService
 * @property PostService $postService
 * @property  AdvService $advService
 * @property  FollowService $followService
 * @property  UserService $userService
 * @property  UserBuyLogService $userBuyLogService
 * @property  AccountService $accountService
 * @property PostLoveService $postLoveService
 * @property  PostFavoriteService $postFavoriteService
 * @property  UserFollowService $userFollowService
 * @package App\Repositories\Api
 */
class PostRepository extends BaseRepository
{

    /**
     * 获取社区主页
     * @param $userId
     * @param $data
     * @return array
     * @throws BusinessException
     */
    public function getHome($userId,$data)
    {
        $filter = empty($data['filter'])?null:json_decode($data['filter'],true);
        if(empty($filter)){
            throw new BusinessException(StatusCode::DATA_ERROR, '请求数据错误!');
        }
        $position = empty($filter['position'])?'normal':$filter['position'];
        $result= array(
            'banner' => value(function ()use($position){
                $positionCode = $position=='dark'?'dark_post_banner':'post_banner';
                $ads = $this->advService->getAll($positionCode, 'n',30);
                return empty($ads)?array():$ads;
            }),
            'block_name' => '推荐板块',
            'block_id' => '',
            'categories' => array(),
            'orders' => array()
        );
        if($filter['block_id']){
           $result['categories']=$this->getCategoriesByBlockId($filter['block_id'],$userId);
        }
        if($result['categories'] && $result['categories'][0]['block_name']){
            $result['block_name'] =$result['categories'][0]['block_name'];
            $result['block_id'] =$result['categories'][0]['block_id'];
        }

        $orders = array(
            array('name'=>'推荐','filter'=>array('is_hot'=>'y')),
            array('name'=>'最新','filter'=>array('order'=>'new','is_home'=>'y')),
            array('name'=>'最热','filter'=>array('order'=>'hot','is_home'=>'y')),
            array('name'=>'视频','filter'=>array('type'=>'video','is_home'=>'y')),
        );
        foreach ($orders as $order){
            $result['orders'][] = array(
                'name' => $order['name'],
                'filter' => json_encode(array_merge($filter,$order['filter']))
            );
        }
        return $result;
    }
    /**
     * 获取分类
     * @param $position
     * @param $userId
     * @return array
     */
    public function getCategoriesByPosition($position,$userId)
    {
        $items = $this->postCategoryService->getAll();
        $result = array();
        foreach ($items as $item){
            if($item['position']==$position){
                $item['img'] = $this->commonService->getCdnUrl($item['img']);
                $item['has_follow'] = $this->followService->has($userId,$item['id'],'post_category')?'y':'n';
                $result[] = $item;
            }
        }
        return $result;
    }

    /**
     * 查看板块详情
     * @param $catId
     * @param $userId
     * @return array|null
     * @throws BusinessException
     */
    public function getCategoryDetail($catId,$userId)
    {
        $result = $this->postCategoryService->getAll();
        $result = empty($result[$catId])?null:$result[$catId];
        if(empty($result)){
            throw new BusinessException(StatusCode::DATA_ERROR, '查看的板块不存在!');
        }
        $result['img'] = $this->commonService->getCdnUrl($result['img']);
        $result['has_follow'] = $this->followService->has($userId,$result['id'],'post_category')?'y':'n';
        return array(
            'cat_info' => $result,
            'block_id' => $result['block_id'],
            'block_name' => strval(empty($result['block_name'])?'推荐':$result['block_name']),
            'categories' => $this->getCategoriesByBlockId($result['block_id'],$userId),
            'orders'  => array(
                array('name'=>'推荐','filter'=>json_encode(array('order'=>'','cat_ids'=>$result['id']))),
                array('name'=>'最热','filter'=>json_encode(array('order'=>'hot','cat_ids'=>$result['id']))),
                array('name'=>'最新','filter'=>json_encode(array('order'=>'new','cat_ids'=>$result['id']))),
                array('name'=>'精华','filter'=>json_encode(array('is_hot'=>'y','cat_ids'=>$result['id']))),
                array('name'=>'视频','filter'=>json_encode(array('type'=>'video','cat_ids'=>$result['id']))),
            )
        );
    }

    /**
     * 获取话题圈关注列表
     * @param $userId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getFollowCategories($userId,$page=1,$pageSize=10)
    {
        $query = array('user_id'=>intval($userId),'object_type'=>'post_category');
        $items = $this->followService->getList($query,array(),array(),($page-1)*$pageSize,$pageSize);
        $categories = $this->postCategoryService->getAll();
        $result = array();
        foreach ($items as $item)
        {
            if(empty($categories[$item['object_id']])){
                continue;
            }
            $category = $categories[$item['object_id']];
            $category['has_follow']='y';
            $category['img'] = $this->commonService->getCdnUrl($category['img']);
            $result[] = $category;
        }
        return $result;
    }

    /**
     * 获取分类
     * @param $blockId
     * @param $userId
     * @return array
     */
    public function getCategoriesByBlockId($blockId,$userId)
    {
        $items = $this->postCategoryService->getAll();
        $result = array();
        foreach ($items as $item){
            if($item['block_id']==$blockId){
                $item['img'] = $this->commonService->getCdnUrl($item['img']);
                $item['has_follow'] = $this->followService->has($userId,$item['id'],'category')?'y':'n';
                $result[] = $item;
            }
        }
        return $result;
    }

    /**
     * 保持帖子
     * @param $userId
     * @param $data
     * @return bool|int|mixed
     * @throws BusinessException
     */
    public function savePost($userId,$data)
    {
        $title        = $this->getRequest($data,'title');
        $categories   = $this->getRequest($data,'categories');
        $images       = $this->getRequest($data,'images');
        $content      = $this->getRequest($data,'content','string','');
        $files        = $this->getRequest($data,'files','string','');
        $money        = $this->getRequest($data,'money','int',0);
        if(empty($title) || empty($images) || empty($categories)){
            throw new BusinessException(StatusCode::DATA_ERROR, '标题,封面,帖子板块不能为空!');
        }
        $images  = explode(',',$images);
        $categories = explode(',',$categories);
        foreach ($categories as $categoryIndex=>$category){
            $categories[$categoryIndex] = intval($category);
        }
        $money = $money<1?0:$money;
        $data = array(
            'user_id'    => intval($userId),
            'title'      => $title,
            'pay_type'   => $money>0?'money':'free',
            'type'       => empty($files)?'image':'video',
            'money'      => $money,
            'categories' => $categories,
            'content'    => $content,
            'images'     => $images,
            'video_path' =>$files,
            'ip'         => getClientIp(),
        );
        $categories = $this->postCategoryService->getAll();
        if(empty($categories[$data['categories'][0]])){
            throw new BusinessException(StatusCode::DATA_ERROR, '所属帖子板块不存在!');
        }
        $data['position'] = $categories[$data['categories'][0]]['position'];
        return $this->postService->save($data);
    }
    /**
     * 获取搜索条件
     * @param $userId
     * @return array
     */
    public function getSearchFilter($userId)
    {
        $result = array();

        $categories = $this->getCategoriesByPosition('normal', $userId);
        $catArr = array(
            array('name' => '全部话题', 'value' => '', 'code' => 'cat_ids'),
        );
        foreach ($categories as $category) {
            $catArr[] = array('name' => $category['name'], 'value' => strval($category['id']), 'code' => 'cat_ids');
        }
        $result[] = $catArr;

        $result[] = array(
            array('name' => '全部类型', 'value' => '', 'code' => 'type'),
            array('name' => '图文', 'value' => 'image', 'code' => 'type'),
            array('name' => '视频', 'value' => 'video', 'code' => 'type')
        );

        $result[] = array(
            array('name' => '综合排序', 'value' => '', 'code' => 'order'),
            array('name' => '精选', 'value' => 'hot', 'code' => 'order'),
            array('name' => '最新', 'value' => 'new', 'code' => 'order')
        );
        return $result;
    }

    /**
     * 搜索数据
     * @param $userId
     * @param $query
     * @return array|mixed
     */
    public function doSearch($userId,$query)
    {
        $filter   =  array();
        $filter['position'] = $this->getRequest($query,'position','string','normal');
        $filter['type']     = $this->getRequest($query,'type');
        $filter['page']     = $this->getRequest($query,'page','int',1);
        $filter['page_size'] = $this->getRequest($query,'page_size',24);
        $filter['order']    = $this->getRequest($query,'order');
        $filter['keywords'] = $this->getRequest($query,'keywords');
        $filter['ids']      = $this->getRequest($query,'ids');
        $filter['cat_ids']  = $this->getRequest($query,'cat_ids');
        $filter['home_id']  = $this->getRequest($query,'home_id','int');
        $filter['home_ids'] = $this->getRequest($query,'home_ids','string');
        $filter['is_hot']   = $this->getRequest($query,'is_hot','string');
        $filter['is_top']   = $this->getRequest($query,'is_top','string');
        $filter['is_follow']   = $this->getRequest($query,'is_follow','string');
        $filter['status']   = isset($_REQUEST['status']) && $_REQUEST['status']!==""?intval($_REQUEST['status']):"";

        $isAll              = empty($query['is_all'])?false:true;
        $blockId  = $this->getRequest($query,'block_id','int');

        $needCache = false;
        if($filter['page']==1 && $query['is_home']=='y'){
            $needCache=true;
        }

        if($filter['is_follow'] && $filter['is_follow']=='y'){
            $ids = $this->userFollowService->getFollowIds($userId);
            $filter['home_ids'] = empty($ids)?'-5':join(',',$ids);
        }

        //个人主页按照最新
        if( $filter['home_id'] && empty($filter['order'])){
            $filter['order'] ='new';
        }

        if($blockId){
            $items  = $this->postCategoryService->getAll();
            $catIds = array();
            foreach ($items as $item) {
                if ($item['block_id'] == $blockId) {
                    $catIds[] = $item['id'];
                }
            }
            $filter['cat_ids'] = join(',',$catIds);
        }

        if($needCache){
            $cacheKey = 'post-'.md5(json_encode($filter));
            $items = getCache($cacheKey);
            if(empty($items)){
                $items   = $this->postService->doSearch($filter,$userId,$isAll);
                setCache($cacheKey,$items,mt_rand(60,90));
            }
        }else{
            $items   = $this->postService->doSearch($filter,$userId,$isAll);
        }

        $result    = array();
        foreach ($items['data'] as $item){
            $item = $this->formatItem($item);
            if(strpos($item['content'],'[[img')!==false){
                $item['content'] = preg_replace('/\[\[img(.*)\]\]/iUs','',$item['content']);
            }
            if(mb_strlen($item['content'],'utf-8')>50){
                $item['content'] = mb_substr($item['content'],0,50,'utf-8');
            }
            $item = $this->formatShowItem($item);
            $result[] = $item;
        }
        return $result;
    }

    /**
     * 格式化展示
     * @param $item
     * @return mixed
     */
    public function formatShowItem($item)
    {
        $files = array();
        //图片大于等于两个时候 并且包含视频的情况
        if($item['image_count']>=2 && $item['video_count']>0){
            $files[] = $item['files'][0];
            $files[] = $item['files'][1];
            $files[] = $item['files'][count($item['files'])-1];
            $files[1]['ico']='';
            $xImageCount = $item['image_count']-2;
            $files[1]['tips'] = $xImageCount>0?'+'.$xImageCount:'';
            $files[2]['ico']='';
        }elseif ($item['image_count']>=3 && $item['video_count']==0){
            $files[] = $item['files'][0];
            $files[] = $item['files'][1];
            $files[] = $item['files'][count($item['files'])-1];
            $files[1]['ico']='';
            $files[2]['ico']='';
            $xImageCount = $item['image_count']-3;
            $files[2]['tips'] = $xImageCount>0?'+'.$xImageCount:'';
        }elseif ($item['image_count']==1 && $item['video_count']==1){
            $files[] = $item['files'][1];
        }elseif ($item['image_count']==1 && $item['video_count']==0){
            $files[] = $item['files'][0];
        }elseif ($item['image_count']==2 && $item['video_count']==0){
            $files[] = $item['files'][0];
            $files[] = $item['files'][1];
            $files[1]['ico']='';
        }
        foreach ($files as $fileIndex=>$file){
//            $imageExt = CommonUtil::getFileExtName($file['image']);
//            $image=str_replace($imageExt,'-small'.$imageExt,$file['image']);
            $file['image'] = $this->commonService->getCdnUrl($file['image']);
            $file['video_link'] ='';
            $files[$fileIndex] = $file;
        }
        $item['files']=array_values($files);
        unset($item['image_count']);
        unset($item['video_count']);
        return $item;
    }

    /**
     * 格式化数据给前端显示
     * @param $item
     * @return mixed
     */
    public function  formatItem($item)
    {
        $images = $item['images'];
        $videoPath = $item['video_path'];
        unset($item['images']);
        unset($item['video_path']);
        $item['files'] = array();
        $lastImage = '';
        $item['image_count'] = count($images);
        $item['video_count'] =empty($videoPath)?0:1;
        foreach ($images as $image){
            $lastImage = $image;
            $item['files'][]= array(
                'image' => $image,
                'type' => 'image',
                'ico' => $item['pay_type'],
                'tips'    =>'',
                'video_link' => '',
                'can_download'=>'n'
            );
        }
        if($videoPath){
            $item['files'][]= array(
                'image' => $lastImage,
                'type' => 'video',
                'ico' => '',
                'tips'    =>'',
                'video_link' =>$videoPath,
                'can_download'=>'n'
            );
        }
        return $item;
    }


    /**
     * 获取详情
     * @param $userId
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($userId,$id)
    {
        $userInfo = $this->userService->getInfoFromCache($userId);
        if(!$this->userService->checkUser($userInfo)){
            throw new BusinessException(StatusCode::DATA_ERROR, '该用户账号已被系统禁用,请联系管理员解除!');
        }
        $items   = $this->postService->doSearch(['ids'=>$id],$userId);
        if(empty($items['data'])){
            throw new BusinessException(StatusCode::DATA_ERROR, '帖子不存在!');
        }
        $result    = $items['data'][0];
        $result    = $this->formatItem($result);

        //查看是否收藏
        $result['has_favorite']= $this->postFavoriteService->has($id,$userId)?'y':'n';

        //查看是否能购买
        $canView   = 'n';
        if($result['is_own']=='y'){
            $canView ='y';
        }elseif ($result['pay_type']=='vip'){
            $isVip = $this->userService->isVip($userInfo);
            if($isVip){
                $canView = 'y';
            }
        }elseif ($result['pay_type']=='money'){
            $needCheckBought=true;
            //获取用户的组-2 就完全免费
            if($userInfo['group_id']){
                $groupInfo = $this->userService->getGroupInfo($userInfo['group_id']);
                if($this->userService->isVip($userInfo) &&$groupInfo['rate']==-2){
                    $needCheckBought=false;
                }
            }
            if($needCheckBought){
                //查询是否已经购买
                $canView = $this->userBuyLogService->has($userId,$id,'post')?'y':'n';
            }else{
                $canView='y';
            }
        }elseif ($result['pay_type']=='free'){
            $canView ='y';
        }
        //检查是否能观看
        if($canView=='n'){
            $result = $this->formatShowItem($result);
        }else{
            foreach ($result['files'] as $fileIndex=>$file){
                $file['image'] = $this->commonService->getCdnUrl($file['image']);
                if($file['video_link']){
                    $file['video_link'] = $this->commonService->getVideoCdnUrl($file['video_link']);
                }
                $result['files'][$fileIndex]=$file;
            }
        }

        //处理图文模式
        if(strpos($result['content'],'[[img')!==false){
            //去掉图片 兼容老版本 但是老版本显示不了图片
            $result['content']=preg_replace('/\[\[img(.*)\]\]/iUs','',$result['content']);
        }

        unset($result['image_count']);
        unset($result['video_count']);
        $result['can_view'] =$canView;
        $result['links']=[];
        $handleKey = md5(sprintf('%s-post-click-%s-%s',date('Y-m-d'),$userId,$id));
        if(!getCache($handleKey)){
            setCache($handleKey,1,3600*3);
            $this->postService->handler(array('post_id'=>$id,'action'=>'click'));
        }
        return $result;
    }

    /**
     * 购买帖子
     * @param $userId
     * @param $postId
     * @return bool
     * @throws BusinessException
     */
    public function doBuy($userId,$postId)
    {
        if(empty($postId)){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '请选择要购买的帖子!');
        }
        $hasBuy = $this->userBuyLogService->has($userId,$postId,'post');
        if($hasBuy){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '视频已购买,无需重复购买!');
        }
        $postInfo = $this->postService->findByID($postId);
        if(empty($postInfo)||$postInfo['status']!=1){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '已下架!');
        }
        $money = $postInfo['money']*1;
        if($money <1){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '此帖子无需购买!');
        }
        $userInfo = $this->userService->findByID($userId);
        $this->userService->checkUser($userInfo);
        if($userInfo['balance'] < $money){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '可用余额不足!');
        }
        $orderSn=CommonUtil::createOrderNo('PP');
        if($money>0) {
            $remark = "购买帖子:{$money}金币";
            $result = $this->accountService->reduceBalance($userInfo,$orderSn,$money,8,$remark);
            if(empty($result)){
                throw  new BusinessException(StatusCode::PARAMETER_ERROR, '购买失败!');
            }
            DataCenterService::doReduceBalance($postId,$postInfo['title'],$money,$userInfo['balance'],($userInfo['balance']-$money),'content_purchase',$orderSn,time());
            $this->addPostUserMoney($postInfo['user_id'],$money,$postId,$orderSn);
        }
        $this->userBuyLogService->do($orderSn,$userInfo,$postId,'post',$postInfo['images'][0],$money,$postInfo['money']);
        $this->postService->handler(['action' => 'buy','post_id'=>$postId,'money'=>$money]);
        return true;
    }

    /**
     * @param $userId
     * @param $money
     * @param $postId
     * @param string $orderSn
     * @return bool
     */
    public function  addPostUserMoney($userId,$money,$postId,$orderSn='')
    {
        $userId = intval($userId);
        if(empty($userId)){
            return false;
        }
        $userInfo = $this->userService->findByID($userId);
        if(empty($userInfo) || $userInfo['is_disabled']){
            return false;
        }
        $rate = $userInfo['post_fee_rate']*1;
        if($rate<=0){
            $configs = getConfigs();
            $rate = $configs['post_fee_rate'] *1;
            if($rate<=0){
                return true;
            }
        }
        $rate = $rate<=0?20:$rate;
        $rate = $rate>60?60:$rate;
        $money = round($money * $rate /100,0);
        if($money<1){
            return true;
        }
        $this->accountService->addBalance($userInfo,$orderSn,$money,11,'帖子收入',json_encode(['post_id'=>$postId,'rate'=>$rate]));
        $this->userService->updateRaw(array('$inc'=>array('income'=>$money)),array('_id'=>$userId));
        $this->userService->setInfoToCache($userId);
        return true;
    }

    /**
     * 获取购买记录
     * @param $userId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getBuyLog($userId,$page=1,$pageSize=12)
    {
        $result=$this->userBuyLogService->log($userId,'post',$page,$pageSize);
        if(empty($result)){return [];}
        $ids =  array_keys($result);
        return $this->getListByIds($ids,$userId);
    }

    /**
     * 点赞帖子
     * @param $userId
     * @param $postId
     * @return bool
     * @throws BusinessException
     */
    public function doLove($userId,$postId)
    {
       return  $this->postLoveService->do($postId,$userId);
    }

    /**
     * 收藏
     * @param $userId
     * @param $postId
     * @return bool
     * @throws BusinessException
     */
    public function doFavorite($userId,$postId)
    {
        return  $this->postFavoriteService->do($postId,$userId);
    }
    /**
     * 删除收藏
     * @param $userId
     * @param null $ids
     * @return bool
     */
    public function delFavorites($userId, $ids = null)
    {
        //bug 未减少计数器
        if ($ids == 'all') {
            $this->postFavoriteService->deleteAll($userId);
        } else {
            $ids = explode(',', $ids);
            foreach ($ids as $id) {
                $this->postFavoriteService->delFirst($userId, $id);
            }
        }
        return true;
    }

    /**
     * 收藏列表
     * @param $userId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getFavorites($userId,$page=1,$pageSize=10)
    {
        $result=$this->postFavoriteService->getList(array('user_id'=>$userId*1),array('post_id'),array('created_at'=>-1),($page-1)*$pageSize,$pageSize);
        if(empty($result)){return [];}
        $ids = array();
        foreach ($result as $item){
            $ids[] = $item['post_id'];
        }
        return $this->getListByIds($ids,$userId);
    }

    /**
     * 根据id获取数据
     * @param $ids
     * @param $userId
     * @return array
     */
    public function  getListByIds($ids,$userId)
    {
        $tempItems = array();
        $query = array('ids' => join(',', $ids),'page'=>1);
        $items  = $this->postService->doSearch($query,$userId,true);
        $result = array();
        foreach ($items['data'] as $item)
        {
            $item = $this->formatItem($item);
            if(mb_strlen($item['content'],'utf-8')>50){
                $item['content'] = mb_substr($item['content'],0,50,'utf-8');
            }
            $item = $this->formatShowItem($item);
            $tempItems[$item['id']] = $item;
        }
        foreach ($ids as $id)
        {
            if(!empty($tempItems[$id])){
                $result[] = $tempItems[$id];
            }
        }
        return $result;
    }


    /**
     * 删除帖子
     * @param $userId
     * @param $id
     * @return bool
     * @throws BusinessException
     */
    public function doDelete($userId,$id)
    {
         if(empty($id)){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '删除的帖子不存在!');
         }
         $post = $this->postService->findByID($id);
         if(empty($post) || $post['user_id']!=$userId || !in_array($post['status'],array(2,0,1))){
             throw  new BusinessException(StatusCode::PARAMETER_ERROR, '删除的帖子不存在!');
         }
         $this->postService->updateRaw(array('status'=>-1),array('_id'=>$id));
         $this->postService->asyncEs($id);
         return true;
    }

    /**
     * 获取ai发布的配置
     * @return array
     */
    public function getAiConfigs()
    {
        $configs = getConfigs();
        $result = array(
            'ai_quyi_price' => strval($configs['ai_quyi_price']*1),
            'ai_quyi_max_num' => '9',
            'ai_huihua_price' => strval($configs['ai_huihua_price']*1),
            'ai_huihua_max_num' => '9',
            'ai_change_price' => strval($configs['ai_change_price']*1),
            'ai_change_min_face_num'=>'1',
            'ai_change_min_num'=>'1',
            'ai_change_video_price' => strval($configs['ai_change_video_price']*1),
            'ai_change_video_min_face_num'=>'3',
            'ai_tips' => strval($configs['ai_tips']),
            'ai_change_video_template' => array(),
            'ai_quyi_cat_id' => strval($configs['ai_quyi_cat_id'] * 1),
            'ai_huihua_cat_id' => strval($configs['ai_huihua_cat_id'] * 1),
            'ai_change_cat_id' => strval($configs['ai_change_cat_id'] * 1),
        );
        $templates = explode("\n",$configs['ai_change_template']);
        foreach ($templates as $template)
        {
            $template = explode('|',$template);
            if($template[0] && $template[1]){
                $result['ai_change_video_template'][] = array(
                    'image_url' => $this->commonService->getCdnUrl(trim($template[0])),
                    'image_value'=>trim($template[0]),
                    'video_url' => $this->commonService->getVideoCdnUrl(trim($template[1])),
                    'video_value' => trim($template[1]),
                );
            }
        }
        return $result;
    }

    /**
     * AI去衣
     * @param $userId
     * @param $images
     * @param $content
     * @param $isPublic
     * @return bool
     * @throws BusinessException
     */
    public function doAiQuyi($userId,$images,$content,$isPublic=false)
    {
        $userInfo = $this->userService->findByID($userId);
        if(empty($userInfo) || $userInfo['is_disabled']){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '用户异常!');
        }
        $configs = $this->getAiConfigs();
        $images = explode(',',$images);
        $imageCount = count($images);
        $totalFee  = $imageCount * $configs['ai_quyi_price'];
        if($totalFee>$userInfo['balance']){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, sprintf('当前可用金币%s个,数量不足!',$userInfo['balance']));
        }
        $data = array(
            'user_id'    => intval($userId),
            'title'      => $content,
            'pay_type'   => 'vip',
            'status'     => 3,
            'type'       => 'image',
            'money'      => 0,
            'categories' => array(
                $configs['ai_quyi_cat_id']*1
            ),
            'content'    => '',
            'images'     => $images,
            'video_path' => '',
            'ip'         => getClientIp(),
            'is_ai'      => 1,
            'is_public'  => $isPublic?1:0
        );
        $categories = $this->postCategoryService->getAll();
        if(empty($categories[$data['categories'][0]])){
            throw new BusinessException(StatusCode::DATA_ERROR, '所属帖子板块不存在!');
        }
        $data['position'] = $categories[$data['categories'][0]]['position'];
        $postId=$this->postService->save($data);
        $this->doAiFee($userInfo,$postId,$totalFee,'AI去衣');
        return true;
    }

    /**
     * AI绘画
     * @param $userId
     * @param $images
     * @param $content
     * @param $num
     * @param $isPublic
     * @return bool
     * @throws BusinessException
     */
    public function doAiHuihua($userId,$images,$content,$num,$isPublic=false)
    {
        $userInfo = $this->userService->findByID($userId);
        if(empty($userInfo) || $userInfo['is_disabled']){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '用户异常!');
        }
        if(true){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '暂时维护中!');
        }
        $configs = $this->getAiConfigs();
        $images = explode(',',$images);
        $totalFee  = $num * $configs['ai_quyi_price'];
        if($totalFee>$userInfo['balance']){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, sprintf('当前可用金币%s个,数量不足!',$userInfo['balance']));
        }
        $data = array(
            'user_id'    => intval($userId),
            'title'      => $content,
            'pay_type'   => 'vip',
            'status'     => 3,
            'type'       => 'image',
            'money'      => 0,
            'categories' => array(
                $configs['ai_huihua_cat_id']*1
            ),
            'content'    => '',
            'images'     => $images,
            'video_path' => '',
            'ip'         => getClientIp(),
            'is_ai'      => 1,
            'is_public'  => $isPublic?1:0,
            'num_ai'     => $num * 1
        );
        $categories = $this->postCategoryService->getAll();
        if(empty($categories[$data['categories'][0]])){
            throw new BusinessException(StatusCode::DATA_ERROR, '所属帖子板块不存在!');
        }
        $data['position'] = $categories[$data['categories'][0]]['position'];
        $postId=$this->postService->save($data);
        $this->doAiFee($userInfo,$postId,$totalFee,'AI绘画');
        return true;
    }

    /**
     * AI换脸图片
     * @param $userId
     * @param $images
     * @param $content
     * @param $sourceImages
     * @param $isPublic
     * @return bool
     * @throws BusinessException
     */
    public function doAiChange($userId,$images,$content,$sourceImages,$isPublic=false)
    {
        $userInfo = $this->userService->findByID($userId);
        if(empty($userInfo) || $userInfo['is_disabled']){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '用户异常!');
        }
        if(true){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '暂时维护中!');
        }
        $configs = $this->getAiConfigs();
        $sourceImages = explode(',',$sourceImages);
        $imageCount = count($sourceImages);
        $totalFee  = $imageCount * $configs['ai_change_price'];
        if($totalFee>$userInfo['balance']){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, sprintf('当前可用金币%s个,数量不足!',$userInfo['balance']));
        }
        $data = array(
            'user_id'    => intval($userId),
            'title'      => $content,
            'pay_type'   => 'vip',
            'status'     => 3,
            'type'       => 'image',
            'money'      => 0,
            'categories' => array(
                $configs['ai_change_cat_id']*1
            ),
            'content'    => '',
            'images'     => $sourceImages,
            'video_path' => '',
            'ip'         => getClientIp(),
            'is_ai'      => 1,
            'ai_face_img'=>$images,
            'is_public'  => $isPublic?1:0
        );
        $categories = $this->postCategoryService->getAll();
        if(empty($categories[$data['categories'][0]])){
            throw new BusinessException(StatusCode::DATA_ERROR, '所属帖子板块不存在!');
        }
        $data['position'] = $categories[$data['categories'][0]]['position'];
        $postId = $this->postService->save($data);
        $this->doAiFee($userInfo,$postId,$totalFee,'AI换脸');
        return true;
    }

    /**
     * AI换脸视频
     * @param $userId
     * @param $images
     * @param $content
     * @param $videoImage
     * @param $videoValue
     * @param $isPublic
     * @return bool
     * @throws BusinessException
     */
    public function doAiChangeVideo($userId,$images,$content,$videoImage,$videoValue,$isPublic=false)
    {
        $userInfo = $this->userService->findByID($userId);
        if(empty($userInfo) || $userInfo['is_disabled']){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '用户异常!');
        }
        if(true){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '暂时维护中!');
        }
        $configs = $this->getAiConfigs();
        $images= explode(',',$images);
        $totalFee  = 1 * $configs['ai_change_video_price'];
        if($totalFee>$userInfo['balance']){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, sprintf('当前可用金币%s个,数量不足!',$userInfo['balance']));
        }
        if(count($images)<3){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '人脸参照至少3张!');
        }
        $data = array(
            'user_id'    => intval($userId),
            'title'      => $content,
            'pay_type'   => 'vip',
            'status'     => 3,
            'type'       => 'video',
            'money'      => 0,
            'categories' => array(
                $configs['ai_change_cat_id']*1
            ),
            'content'    => '',
            'images'     => [$videoImage],
            'video_path' => $videoValue,
            'ip'         => getClientIp(),
            'is_ai'      => 1,
            'ai_face_img'=>$images,
            'is_public'  => $isPublic?1:0
        );
        $categories = $this->postCategoryService->getAll();
        if(empty($categories[$data['categories'][0]])){
            throw new BusinessException(StatusCode::DATA_ERROR, '所属帖子板块不存在!');
        }
        $data['position'] = $categories[$data['categories'][0]]['position'];
        $postId = $this->postService->save($data);
        $this->doAiFee($userInfo,$postId,$totalFee,'AI视频换脸');
        return true;
    }

    /**
     * 获取Ai定制日志
     * @param $userId
     * @param $page
     * @return array|mixed
     */
    public function getAiLogs($userId,$page=1)
    {
        $aiConfigs = $this->getAiConfigs();
        $filter = array(
            'is_all' =>true,
            'home_id' => $userId,
            'page' => $page*1,
            'cat_ids' => join(',',array($aiConfigs['ai_change_cat_id'],$aiConfigs['ai_huihua_cat_id'],$aiConfigs['ai_quyi_cat_id']))
        );
        return $this->doSearch($userId,$filter);
    }

    /**
     * Ai扣款
     * @param $user
     * @param $postId
     * @param $totalFee
     * @param string $remark
     * @return bool
     * @throws BusinessException
     */
    public function doAiFee($user,$postId,$totalFee,$remark='')
    {
        $totalFee = intval($totalFee);
        if($totalFee<1 || $user['balance']<$totalFee){
            $this->postService->delete($postId);
            throw new BusinessException(StatusCode::DATA_ERROR, '购买失败!');
        }
        $orderSn=CommonUtil::createOrderNo('AI');
        $remark = sprintf("%s消耗:%s金币",$remark,$totalFee);
        $result = $this->accountService->reduceBalance($user,$orderSn,$totalFee,3,$remark,json_encode(array('post_id'=>$postId,'type'=>$remark)));
        if(empty($result)){
            $this->postService->delete($postId);
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '购买失败!');
        }
        DataCenterService::doReduceBalance($postId,$remark,$totalFee,$user['balance'],($user['balance']-$totalFee),'content_purchase',$orderSn,time());
        $this->postService->updateRaw(array('$set'=>array('order_sn'=>$orderSn,'total_fee'=>$totalFee)),array('_id'=>$postId));
        $this->userService->setInfoToCache($user['_id']);
        return true;
    }
}