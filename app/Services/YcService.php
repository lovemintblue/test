<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Services\BaseService;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 * 自动化运营工具
 * Class YcService
 * @property  CommonService $commonService
 * @property  UserService $userService
 * @property  UserUpService $userUpService
 * @package App\Services
 * @property  MovieService $movieService
 * @property  PostService  $postService
 * @property  ComicsService $comicsService
 * @property  NovelService $novelService
 */
class YcService extends  BaseService
{
    /**
     *  请求资源库
     * @param $url
     * @param array $data
     * @return null
     */
    protected function doAvMediaSystemApi($url, $data = [])
    {
        $mediaUrl = $this->commonService->getConfig('media_url');
        $mediaKey = $this->commonService->getConfig('media_key');

        $url = sprintf('%s/cxapi/%s?key=%s', $mediaUrl, $url, $mediaKey);
        $result = CommonUtil::httpPost($url, $data);
        $result = empty($result) ? null : json_decode($result, true);
        if (empty($result) || $result['status'] != 'y') {
            return null;
        }
        return $result['data'];
    }

    /**
     *  请求视频资源库
     * @param $url
     * @param array $data
     * @return null
     */
    protected function doMovieMediaSystemApi($url, $data = [])
    {
        $config = container()->get('config');
        $mediaUrl =$config->api->movie_mrs_url;
        $mediaKey =$config->api->movie_mrs_key;

        $url = sprintf('%s/cxapi/%s?key=%s', $mediaUrl, $url, $mediaKey);
        $result = CommonUtil::httpPost($url, $data);
        $result = empty($result) ? null : json_decode($result, true);
        if (empty($result) || $result['status'] != 'y') {
            return null;
        }
        return $result['data'];
    }

    /**
     *  同步正规影视
     */
    public function asyncMovie()
    {
        $url = 'movie/list';
        $query['page_size']=100;
        $query['page']=0;
        while (true)
        {
            $query['page']+=1;
            LogUtil::info('Async page:'.$query['page']);
            $items = $this->doMovieMediaSystemApi($url,$query);
            if(empty($items['items'])){
                break;
            }
            $items = $items['items'];
            foreach ($items as $item)
            {
                LogUtil::info('Save movie:'.$item['name']);
                $this->movieService->saveMovieMrsData($item);
            }
        }

    }

    /**
     *  自动上架函数
     * @param $movieId
     * @return  mixed
     */
    public function handleMovie($movieId)
    {
        $movie = $this->movieService->findByID($movieId);
        if($movie['position']=='movie'){
            return true;
        }
        $config=container()->get('config');
        $userIds = $config->yc->movie_users;
        $userKey = 'movie_'.$movie['position'].'_users';
        if(isset($config->yc->{$userKey}) && $config->yc->{$userKey}){
            $userIds = $config->yc->{$userKey};
        }
        if($movie['img_type']=='short' && $movie['cat_id']=='3'){
            $userIds = $config->yc->movie_douyin_users;
        }
        if($movie['categories']=='1'){
            $userIds = $config->yc->movie_av_users;
        }
        if(empty($movie) || $movie['status']!==0){
            //return false;
        }
        $userIds = CommonUtil::parseUserIds($userIds);
        $userId=0;
        if($userIds){
            $userId =$userIds[mt_rand(0,count($userIds)-1)];
        }
        $userTagFile = RUNTIME_PATH.'/tag_user.json';
        if(!file_exists($userTagFile)){
            LogUtil::info('请先生成Tag关联到用户!');
            return false;
        }
        if(file_exists($userTagFile)){
            $tempIds = file_get_contents($userTagFile);
            $tempIds = json_decode($tempIds,true);
            foreach ($movie['tags'] as $tag){
                if($tempIds[$tag]){
                    $userId = $tempIds[$tag]['user_id']*1;
                    break;
                }
            }
        }
        if(empty($userId)){
            return false;
        }
        // 有演员的查询演员 没得自动生成
        $updateData = [
            'status'=>1,
            'show_at' => mt_rand(time()-24*3600*20,time()),
            'user_id' => $userId
        ];

        //竖屏修改成短视频
        if($movie['img_type']=='short' && $movie['cat_id']=='3'){
            $updateData['cat_id']=13;
            $updateData['position']='douyin';
        }

        $this->userService->updateRaw(['$set'=>['is_up'=>1]],['_id'=>$userId]);
        if($movie['actor']){
            if(strpos($movie['actor'],',')>0){
                $movie['actor'] =  explode(',',$movie['actor']);
                $movie['actor']= $movie['actor'][0];
            }
            $up = $this->userUpService->findFirst(['nickname'=>$movie['actor']]);
            if(!empty($up)){
                $updateData['user_id'] = $up['user_id']*1;
            }
        }
        $this->movieService->updateRaw(['$set'=>$updateData],['_id'=>$movieId]);
        $this->movieService->asyncEs($movieId);
        LogUtil::info('Async movie end:'.$movie['name']);
    }

    /**
     *  自动化同步av 里番等需要日常更新的
     * @param $query
     * @param null $isAll
     */
    public function asyncAvMedia($query,$isAll=null)
    {
        $url = 'av/list';
        $maxPage=$isAll?10000:3;
        $sort = $isAll?1:-1;
        for ($page=1;$page<=$maxPage;$page++)
        {
            $query['page']=$page;
            $query['page_size']=200;
            $query['sort']=$sort;
            LogUtil::info(sprintf('Async %s/%s',$page,$maxPage));
            $items = $this->doAvMediaSystemApi($url,$query);
            if(empty($items['items'])){
                $items = $this->doAvMediaSystemApi($url,$query);
            }
            if(empty($items['items'])){
                break;
            }
            foreach ($items['items'] as $item)
            {
                LogUtil::info('Async movie start:'.$item['name']);
                if($item['duration']<10 && empty($item['is_more_link'])){
                    LogUtil::error($item['id'].'=>'.$item['name']);
                    continue;
                }
                $movieId= $this->movieService->saveMrsData($item);
                if(empty($movieId)){
                    LogUtil::error('Async movie error:'.$item['name']);
                }
                $this->handleMovie($movieId);
            }
        }
        LogUtil::info('Async all is ok!');

    }

    /**
     *  生成up
     */
    public function buildUp()
    {
        $query['is_up']=1;
        $query['_id'] = ['$gte'=>263856];
        $items = $this->userService->getList($query,[],[],0,500);
        foreach ($items as $item)
        {
            $count= $this->userUpService->count(['user_id'=>$item['_id']*1]);
            if($count>0){
                LogUtil::info('Build username:'.$item['nickname']);
                $fans = mt_rand(12000,35000);
                $love = mt_rand(30000,60000);
                $follow=mt_rand(12000,15000);
                $this->userService->userModel->update(['fans'=>$fans,'love'=>$love,'follow'=>$follow],['_id'=>$item['_id']*1]);
                $this->userService->setInfoToCache($item['_id']);
                continue;
            }
            $row = array(
                'user_id' => $item['_id'],
                'username' => $item['username'],
                'nickname' => $item['nickname'],
                'sort' => 0,
                'is_hot' =>0,
                'price' => 0,
                'categories' => ['post'],
                'first_letter' => strtoupper(substr(CommonUtil::pinyin($item['nickname'], true), 0, 1))
            );
            $this->userUpService->save($row);
        }
    }

    /**
     *  同步up数据
     * @param string $type
     * @param null $isHot
     */
    public function asyncUp($type = 'jp_av', $isHot = null)
    {
        $types = [
            'jp_av' => 'jp_av',
            'fuli' => 'fuli',
            'oumei' => 'om_av',
            'zongyi' => 'zongyi'
        ];
        $url = 'av/upList';
        $query = ['type' => $type, 'page_size' => 1500];
        if ($isHot) {
            $query['is_hot'] = 1;
        }
        $result = $this->doAvMediaSystemApi($url, $query);
        foreach ($result['items'] as $item) {
          //  $this->userService->userModel->delete(['mms_user_id' => $item['id'] * 1]);
            LogUtil::info('Async user:'.$item['name']);
            $checkItem = $this->userService->findFirst(['mms_user_id' => $item['id'] * 1]);
            if (!empty($checkItem)) {
                $upData = [
                    'sort'=>$item['sort']*1,
                    'nickname' => $item['name']
                ];
                $this->userService->userUpModel->updateRaw(['$set'=>$upData],['user_id'=>$checkItem['_id']]);
                $this->userService->userModel->updateRaw(['$set'=>['nickname'=>$item['name'],'jp_name'=>$item['jp_name']]],['_id'=>$checkItem['_id']]);
                continue;
            }
            $user = $this->userService->getDefaultUserRow();
            $user['phone'] = 'mms_' . $item['id'];
            $user['device_id'] = 'mms_' . $item['id'];
            $user['nickname'] = $item['name'];
            $user['jp_name'] = $item['jp_name'];
            $user['sign'] = sprintf('Hi,我是你的%!现正式入住Pornhub中文版啦!我会在这里不定时的分享作品,希望大家能喜欢我!经典恒永久,幸福每一天,快来点个关注,防止迷路吧!', $user['nickname']);
            if ($item['img']) {
                $user['img'] = $item['img'];
            }
            $user['mms_user_id'] = $item['id'] * 1;
            $user['mms_tag_id'] = $item['tag_id'] * 1;
            $user['is_up'] = 1;
            $username = '';
            LogUtil::info('Save user:' . $item['name']);
            $userId = $this->userService->save($user, $username);
            if ($userId) {
                $row = array(
                    'user_id' => $userId,
                    'username' => $username,
                    'nickname' => $user['nickname'],
                    'sort' => 0,
                    'is_hot' => $item['is_hot'] == 'y' ? 1 : 0,
                    'price' => 0,
                    'categories' => [$types[$type]],
                    'first_letter' => strtoupper(substr(CommonUtil::pinyin($user['nickname'], true), 0, 1))
                );
                $this->userUpService->save($row);
            }
        }
    }


    /**
     *  自动化同步帖子
     * @param $filter
     * @param null $isAll
     */
    public function asyncPost($filter,$isAll=null)
    {
        $url = 'post/ids';
        $maxPage=$isAll?200:2;
        $sort = $isAll?1:-1;
        unlink('error.txt');
        $page =1;
        while (true)
        {
            if($page>$maxPage){
                break;
            }
            $query=empty($filter)?[]:$filter;
            $query['page']=$page;
            $query['page_size']=200;
            $query['order']=$sort;
            LogUtil::info(sprintf('Async %s/%s',$page,$maxPage));
            $ids = $this->doAvMediaSystemApi($url,$query);
            foreach ($ids as $id)
            {
                $listUrl = 'post/list';
                $item = $this->doAvMediaSystemApi($listUrl,['id'=>$id]);
                if(empty($item)){
                    continue;
                }
                $item = $item[0];
                LogUtil::info('Start async post:'.$item['title']);
                $id =$this->postService->saveMrsItem($item);
                if(empty($id)){
                    LogUtil::error('Async post error:'.$item['title']);
                }
            }
            $page +=1;
        }
        LogUtil::info('Async all is ok!');

    }


    /**
     *   首次上架所有视频
     */
    public function firstUpVideoAction()
    {
        $now = time();
        $startTime = $now-180*3600*24;
        $xStartTime= $now-7*3600*24;
        //$xVideos= file_get_contents('videos.txt');
        $xVideos ='';
        $query =['status'=>0];
        $count = $this->movieService->count($query);
        $pageSize =  1000;
        $totalPage= ceil($count/$pageSize);
        for ($page=1;$page<=$totalPage;$page++)
        {
            $items= $this->movieService->getList($query,[],[],($page-1)*$pageSize,$pageSize);
            foreach ($items as $item)
            {
                $updateData= [];
                if($item['position']=='dark'){
                    $updateData['user_id'] = mt_rand(575118,575123);
                }
                $updateData['status']=1;
                if(strpos($xVideos,$item['_id'])!==false){
                    $num = mt_rand($xStartTime,$now);
                    $updateData['is_hot']=1;
                    $updateData['sort']=$num;
                    $updateData['show_at'] = $num;
                }else{
                    $updateData['sort']=0;
                    $updateData['show_at'] = mt_rand($startTime,$now);
                }
                $this->movieService->movieModel->update($updateData,['_id'=>$item['_id']]);
                $this->movieService->asyncEs($item['_id']);
                LogUtil::info('Update movie:'.$item['name']);
            }
        }
    }


    /**
     *   首次上架所有帖子
     */
    public function firstUpPost()
    {
        $count = $this->postService->count([]);
        $pageSize =  1000;
        $totalPage= ceil($count/$pageSize);
        $userIds = [];
        for ($page=1;$page<=$totalPage;$page++)
        {
            $items= $this->postService->getList([],[],[],($page-1)*$pageSize,$pageSize);
            foreach ($items as $item)
            {
                $updateData= [];
                $updateData['status']=1;
                $updateData['is_hot']= 0;
                $updateData['sort']=0;
                $updateData['click']= mt_rand(60000,170000);
                if($updateData['created_at']>(time()-3600*24*2)){
                    $updateData['click']= mt_rand(20000,50000);
                }
                if($item['img']){
                    if(in_array(58,$item['categories'])){
                        $updateData['sort']= mt_rand($item['created_at']-24*3600*7,$item['created_at']);
                    }
                    if(in_array(22,$item['categories'])){
                        $updateData['sort']= mt_rand($item['created_at']-24*3600*7,$item['created_at']);
                    }
                }
                $this->postService->postModel->update($updateData,['_id'=>$item['_id']]);
                $this->postService->asyncEs($item['_id']);
                LogUtil::info('Update post:'.$item['title']);
                $userIds[] = $item['user_id']*1;
            }
        }
        $userIds = array_unique($userIds);
        file_put_contents('post_up.txt',join(',',$userIds));
    }


    /**
     *   更新中的漫画
     */
    public function  asyncUpdatingComics()
    {
        $query = ['update_status'=>0];
        $count= $this->comicsService->count($query);
        $pageSize = 500;
        $totalPage= ceil($count/$pageSize);
        for ($page=1;$page<=$totalPage;$page++)
        {
            $items = $this->comicsService->getList($query,['_id'],[],($page-1)*$pageSize,$pageSize);
            foreach ($items as $item)
            {
                LogUtil::info('Async id:'.$item['_id']);
                $this->comicsService->asyncMrsById($item['_id']);
            }
        }
    }

    /**
     * 首次同步所有漫画
     * @param $isAll
     */
    public function asyncComics($isAll=false)
    {
        $page =1;
        while (true)
        {
            $query = [];
            $query['page']=$page;
            $query['page_size']=150;
            $query['sort'] = $isAll?1:-1;
            if(empty($isAll)&$page>4){
                break;
            }
            LogUtil::info(sprintf('Async %s',$page));
            $url = 'comics/ids';
            $items = $this->doAvMediaSystemApi($url,$query);
            if(empty($items)){
                break;
            }
            foreach ($items as $item)
            {
                LogUtil::info('Async id:'.$item);
                $this->comicsService->asyncMrsById($item);
            }
            $page+=1;
        }
        LogUtil::info('Done all!');
    }


    /**
     *  更新中的小说
     */
    public  function asyncUpdatingNovel()
    {
        $query = ['update_status'=>0];
        $count= $this->novelService->count($query);
        $pageSize = 500;
        $totalPage= ceil($count/$pageSize);
        for ($page=1;$page<=$totalPage;$page++)
        {
            $items = $this->novelService->getList($query,['_id'],[],($page-1)*$pageSize,$pageSize);
            foreach ($items as $item)
            {
                LogUtil::info('Async id:'.$item['_id']);
                $this->novelService->asyncMrsById($item['_id']);
            }
        }
    }

    /**
     * 首次同步所有小说
     * @param $isAll
     */
    public function asyncNovel($isAll=null)
    {
        $page =1;
        while (true)
        {
            $query = [];
            $query['page']=$page;
            $query['page_size']=300;
            $query['sort']=$isAll?1:-1;
            if(empty($isAll) && $query['page']>3){
                break;
            }
            LogUtil::info(sprintf('Async %s',$page));
            $url = 'novel/ids';
            $ids = $this->doAvMediaSystemApi($url,$query);
            if(empty($ids)){
                break;
            }
            foreach ($ids as $id)
            {
                LogUtil::info('Async id:'.$id);
                $this->novelService->asyncMrsById($id);
                $this->novelService->asyncEs($id);
            }
            $page+=1;
        }
        LogUtil::info('Done all!');
    }


    /**
     *   根据标签分视频区域
     */
    public function groupMovieByTag()
    {
        $blTags = [
            623,600,1011,852,1010,854,855,850,856,853,851,857,1012
        ];
        $awTags =[
            649,559,562,647,834,1062,1059,1077,648,1075,832,646,731,830,833,1060,1071,1072,1074,1070,1176
        ];

        $query =[];
        $count = $this->movieService->count($query);
        $pageSize =  1000;
        $totalPage= ceil($count/$pageSize);
        for ($page=1;$page<=$totalPage;$page++)
        {
            $items= $this->movieService->getList($query,[],[],($page-1)*$pageSize,$pageSize);
            foreach ($items as $item)
            {
                if(empty($item['tags'])){
                    continue;
                }
                $updateData= [];
                foreach ($item['tags'] as $tag){
                    if(in_array($tag,$blTags)){
                        $updateData['position']='bl';
                        break;
                    }
                    if(in_array($tag,$awTags)){
                        $updateData['position']='dark';
                        break;
                    }
                }
                if(empty($updateData)){
                    continue;
                }
                $this->movieService->movieModel->update($updateData,['_id'=>$item['_id']]);
                $this->movieService->asyncEs($item['_id']);
                LogUtil::info('Update movie:'.$item['name']);
            }
        }

    }

    /**
     *  创建up用户
     * @param $deviceId
     * @param $nickname
     * @return bool|float|int|mixed|null
     */
    public  function createUpUser($deviceId,$nickname='')
    {
        return $this->userService->createUpUser($deviceId,$nickname);
    }


    /**
     *  标签转用户
     * @param $file
     */
    public function tagToUser($file)
    {
        if(empty($file) || !file_exists($file)){
            LogUtil::error('file is error!');
            return;
        }
        $items = file_get_contents($file);
        $items = explode("\n",$items);
        unlink('tag_user.txt');
        $tagUsers = [];
        foreach ($items as $item)
        {
            if(empty($item)){
                continue;
            }
            $item = explode('|',$item);
            if(empty($item[0] || empty($item[1]))){
                continue;
            }
            $id = trim($item[0])*1;
            $name = trim($item[1]);
            if(empty($id) || empty($name)){
                continue;
            }
            $userId =$this->createUpUser('tag_'.$id,$name);
            if($userId){
                $tagUsers[$id]=['user_id'=>$userId,'tag_id'=>$id*1,'tag_name'=>$name];
                $userId = $userId * 1;
                $query = ['tags'=>$id];
                $movies = $this->movieService->getList($query,['_id','name'],[],0,4000);
                foreach ($movies as $movie)
                {
                    LogUtil::info('Update name:'.$movie['_id']);
                    $this->movieService->updateRaw(['$set'=>['user_id'=>$userId]],['_id'=>$movie['_id']]);
                    $this->movieService->asyncEs($movie['_id']);
                }
            }
        }
        file_put_contents(RUNTIME_PATH.'/tag_user.json',json_encode($tagUsers));
    }

}