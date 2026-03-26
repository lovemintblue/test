<?php

namespace App\Services;

use App\Constants\CacheKey;
use App\Core\Services\BaseService;
use App\Utils\AesUtil;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 * 媒资库系统
 * Class MrsSystemService
 * @property  CommonService $commonService
 * @property  ConfigService $configService
 * @package App\Services
 */
class MrsSystemService extends BaseService
{
    /**
     * 执行 http post - 小组库
     * @param $url
     * @param $data
     * @return array|false|mixed
     */
    public function doHttpPostMrs($url,$data=[])
    {
        $mediaUrl = $this->commonService->getConfig('media_url');
        $mediaKey = $this->commonService->getConfig('media_key');
        $url = sprintf('%s/cxapi/%s?key=%s', $mediaUrl, $url, $mediaKey);
        $result = json_decode(CommonUtil::httpPost($url,$data),true);
        if($result['status']!='y'){
            LogUtil::error("接口请求异常 url:{$url} error:{$result['error']}");
            return false;
        }
        return !empty($result['data'])?$result['data']:[];
    }

    /**
     * 执行 http post - 老司机库
     * @param $url
     * @param $data
     * @return array|false|mixed
     */
    public function doLsjHttpPostMrs($url,$data=[])
    {
        if((is_array($data) || is_object($data))){
            $data = json_encode($data);
        }elseif(empty($data)){
            LogUtil::error("请求体有误");
            return false;
        }

        $config=container()->get('config');
        $url = sprintf('%s/lsjapi/%s', $config->mrs->laosiji_api_url, $url);
        $data  = empty($data)?null:AesUtil::encryptBase64($data,$config->mrs->laosiji_api_key);
        $result = CommonUtil::httpJson($url,$data,40,['appid:'.$config->mrs->laosiji_api_appid]);
        $result = empty($result)?null:json_decode($result,true);
        if($result['status']!='y'){
            LogUtil::error("接口请求异常 url:{$url} error:{$result['error']}");
            return false;
        }

        return empty($result['data'])?[]:json_decode(AesUtil::decryptBase64($result['data'],$config->mrs->laosiji_api_key),true);
    }

    /**
     * 小组库-视频列表
     * @param $query
     * @return array|mixed
     */
    public function getMovieList($query)
    {
        $url = 'av/list';
        $items = $this->doHttpPostMrs($url,$query);
        $result = $items['items']?:[];
        return $result;
    }

    /**
     * 小组库-帖子列表
     * @param $query
     * @return array|mixed
     */
    public function getPostList($query)
    {
        $url = 'post/list';
        return $this->doHttpPostMrs($url,$query);
    }

    /**
     * 小组库-漫画列表
     * @param $query
     * @return array|mixed
     */
    public function getComicsList($query)
    {
        $url = 'comics/list';
        return $this->doHttpPostMrs($url,$query);
    }

    /**
     * 小组库-小说列表
     * @param $query
     * @return array|mixed
     */
    public function getNovelList($query)
    {
        $url = 'novel/list';
        return $this->doHttpPostMrs($url,$query);
    }

    /**
     * 老司机-CDN域名同步
     * @return array|false|mixed
     */
    public function getLsjCdn()
    {
        $url = 'system/domains';
        return $this->doLsjHttpPostMrs($url);
    }

    /**
     * 老司机-视频列表
     *
     * position 说明
     * --guochan 是国产成人视频
     * --av 主要是日本和欧美成人视频
     * --movie 是正规影视资源
     * --bl  男同
     * --douyin 短视频
     * --cartoon 动漫
     * --dark 暗网资源
     *
     * 分类说明-ID(cat_id)、名称、分区(position)
     * --1	AV	av
     * --2	DM	guochan
     * --3	GC	guochan
     * --4	综艺	movie
     * --5	连续剧	movie
     * --6	电影	movie
     * --7	动漫	movie
     * --8	纪录片	movie
     * --9	短剧	movie
     * --10	音乐	movie
     * --11	电影解说	movie
     * --12	VR	av
     * --13	成人短视频	guochan
     *
     *
     * @param $query
     * @return array
     */
    public function getLsjMovieList($query)
    {
        $url = 'movie/search';
        return $this->doLsjHttpPostMrs($url,$query);
    }

    /**
     * 老司机-视频详情
     * @param $query
     * @return array|mixed
     */
    public function getLsjMovieDetail($query)
    {
        $url = 'movie/detailByMid';
//        $url = 'movie/detail';
        $result = $this->doLsjHttpPostMrs($url,$query);
        if(!empty($result)){
            foreach($result['links'] as &$link){
                $link['preview_m3u8_url'] = $link['preview_m3u8_url']?'/'.$link['preview_m3u8_url']:'';
                $link['m3u8_url'] = $link['m3u8_url']?'/'.$link['m3u8_url']:'';
            }
            $result['source'] = 'laosiji';
            $result['id'] = $result['mid'];
            $result['img'] = $result['img_x'];
            $result['img_y'] = $result['img_y']==$result['img_x']?'':$result['img_y'];
            $result['m3u8_url'] = $result['links'][0]['m3u8_url'];
            $result['preview_m3u8_url'] = $result['links'][0]['preview_m3u8_url'];
            $result['width']  = $result['width']??$result['img_width'];
            $result['height'] = $result['height']??$result['img_height'];
            $result['canvas'] = $result['img_type'];
            $result['preview_images'] = implode(',',$result['preview_images']);
            $result['is_more_link'] = $result['is_more_link']==='y'?1:0;
            $result['nickname'] = $result['up_user']['nickname']??'';
        }
        return $result;
    }

    /**
     * 老司机-帖子列表
     * @param $query
     * @return array
     */
    public function getLsjPostList($query)
    {
        $url = 'post/search';
        return $this->doLsjHttpPostMrs($url,$query);
    }

    /**
     * 老司机-帖子详情
     * @param $query
     * @return array|mixed
     */
    public function getLsjPostDetail($query)
    {
        $url = 'post/detail';
        $result = $this->doLsjHttpPostMrs($url,$query);
        if(!empty($result)){
            $images = [];
            $videoPath = '';
            foreach($result['files'] as $file){
                if($file['type']=='image'){
                    $images[] = $file['image'];
                }elseif($file['type']=='video'){
                    $videoPath = '/'.$file['video_link'];
                }
            }
            $result['source'] = 'laosiji';
            $result['type'] = $result['video_count']>0?'video':'image';
            $result['author'] = $result['up_user']['nickname']??'';
            $result['release_date'] = $result['time']??'';
            $result['created_at'] = $result['created_at']??$result['time'];
            $result['images'] = $images;
            $result['video_path'] = $videoPath;
            $result['files'] = '';
        }
        return $result;
    }

    /**
     *
     * cat_id=
     * 韩漫
     * 日漫
     * 国漫
     * 本子
     * 色图
     * 腐漫
     * Cosplay
     * 3D
     * CG
     * 欧美漫画
     * 港台漫画
     * 真人漫画
     * 同人
     * 写真
     * AI
     *
     * 老司机-漫画列表
     * @param $query
     * @return array
     */
    public function getLsjComicsList($query)
    {
        $url = 'comics/search';
        return $this->doLsjHttpPostMrs($url,$query);
    }

    /**
     * 老司机-漫画详情
     * @param $query
     * @return array|mixed
     */
    public function getLsjComicsDetail($query)
    {
        $url = 'comics/detail';
        $result = $this->doLsjHttpPostMrs($url,$query);
        if(!empty($result)){
            $result['source'] = 'laosiji';
            $result['is_adult'] = $result['is_adult']==='y'?1:0;
            foreach($result['chapter'] as &$chapter){
                $chapter['updated_at'] = $chapter['updated_at']?:$chapter['created_at'];
            }
        }
        return $result;
    }

    /**
     *
     * category分类值
     * normal 普通小说
     * 18R 成人小说
     * audio 有声小说
     *
     * 老司机-小说列表
     * @param $query
     * @return array
     */
    public function getLsjNovelList($query)
    {
        $url = 'novel/search';
        return $this->doLsjHttpPostMrs($url,$query);
    }

    /**
     * 老司机-小说详情
     * @param $query
     * @return array|mixed
     */
    public function getLsjNovelDetail($query)
    {
        $url = 'novel/detail';
        $result = $this->doLsjHttpPostMrs($url,$query);
        if(!empty($result)){
            $result['source'] = 'laosiji';
            $result['is_adult'] = $result['is_adult']==='y'?1:0;
            foreach($result['chapter'] as &$chapter){
                $chapter['is_audio'] = $chapter['is_audio']==='y'?1:0;
                $chapter['volume_name'] = $chapter['volume_name']??'';
                $chapter['preview_content'] = $chapter['preview_content']??'';
                $chapter['updated_at'] = $chapter['updated_at']?:$chapter['created_at'];
                $chapter['content'] = $chapter['content']?'/'.ltrim($chapter['content'],'/'):'';
            }
        }
        return $result;
    }

    /**
     * 老司机-ai女友-获取授权url
     * @param $query
     * @return array|false|mixed
     */
    public function getLsjGirlFriendAuthUrl($query)
    {
        $url = 'aiGirlFriend/auth';
        return $this->doLsjHttpPostMrs($url,$query);
    }

    /**
     * 老司机-ai女友-资金带入
     * @param $query
     * @return array|false|mixed
     */
    public function doLsjGirlFriendBringInAssets($query)
    {
        $url = 'aiGirlFriend/bringInAssets';
        return $this->doLsjHttpPostMrs($url,$query);
    }

    /**
     * 老司机-ai女友-资金带出
     * @param $query
     * @return array|false|mixed
     */
    public function doLsjGirlFriendBringOutAssets($query)
    {
        $url = 'aiGirlFriend/bringOutAssets';
        return $this->doLsjHttpPostMrs($url,$query);
    }

    /**
     * 老司机-ai女友-获取订单记录，不支持时间
     * @param $query
     * @return array|false|mixed
     */
    public function getLsjGirlFriendOrderLogs($query)
    {
        $url = 'aiGirlFriend/orderLogs';
        return $this->doLsjHttpPostMrs($url,$query);
    }

    public function getLsjGirlFriendAllOrderLogs($query)
    {
        $url = 'aiGirlFriend/allOrderLogs';
        return $this->doLsjHttpPostMrs($url,$query);
    }

    /**
     * 老司机-棋牌游戏-获取授权url
     * @param $query
     * @return array|false|mixed
     */
    public function getLsjGameQpAuthUrl($query)
    {
        $url = 'gameQp/login';
        return $this->doLsjHttpPostMrs($url,$query);
    }

}