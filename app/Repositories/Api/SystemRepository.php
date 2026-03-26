<?php

declare(strict_types=1);

namespace App\Repositories\Api;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\AdvAppService;
use App\Services\AdvService;
use App\Services\ApiService;
use App\Services\AppErrorService;
use App\Services\AppTrackService;
use App\Services\ArticleService;
use App\Services\BlockPositionService;
use App\Services\ChannelAppService;
use App\Services\CommonService;
use App\Services\DomainService;
use App\Services\FollowService;
use App\Services\MmsService;
use App\Services\MovieBlockService;
use App\Services\MovieCategoryService;
use App\Services\PostBlockService;
use App\Services\SmsService;
use App\Services\UserActService;
use App\Services\UserGroupService;
use App\Services\UserService;
use App\Services\WssService;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;
use App\Jobs\Center\CenterBaseJob;

/**
 * Class SystemRepository
 * @package App\Repositories\Api
 * @property ApiService $apiService
 * @property UserService $userService
 * @property CommonService $commonService
 * @property MovieBlockService $movieBlockService
 * @property AdvService $advService
 * @property ArticleService $articleService
 * @property MovieCategoryService $movieCategoryService
 * @property SmsService $smsService
 * @property AdvAppService $advAppService
 * @property AppErrorService $appErrorService
 * @property MmsService $mmsService
 * @property WssService $wssService
 * @property UserGroupService $userGroupService
 * @property ChannelAppService $channelAppService
 * @property BlockPositionService $blockPositionService
 * @property  PostBlockService $postBlockService
 * @property  FollowService $followService
 * @property  DomainService $domainService
 * @property  AppTrackService $appTrackService
 * @property  UserActService $userActService
 */
class SystemRepository extends BaseRepository
{
    /**
     * 获取app启动
     * @param array $data
     * @return array
     * @throws BusinessException
     */
    public function info($data = array())
    {
        $deviceId = $this->apiService->getDeviceId();
        if (empty($deviceId)) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '请检查输入有效性!');
        }
        $configs    = $this->commonService->getConfigs();
        $deviceType = $this->apiService->getDeviceType();
        $adMethod   = $data['ad_method'];
        $userData   = array();
        $isVerify   = 'n';
        $token      = null;
        $domain = [];
        $domainArr = [];
        if($deviceType == "h5"){
            $domain = $this->domainService->getOne($_SERVER['HTTP_HOST']);
            $channelCode = $domain?$domain['channel_code']:'';
            if(!empty($channelCode)){$data['h5_channel_code'] = "channel://".$channelCode;}
        }else{
            $domainArr = $this->domainService->getAllGroupBy();
        }
        //图片验证码输入正确
        if($data['captcha_key'] && $data['captcha_value']){
            $key = 'captcha_image_' . $data['captcha_key'];
            $checkValue = getCache($key);
            if ($checkValue && strtolower($checkValue)== strtolower($data['captcha_value'])) {
                $token = $this->userService->loginByUserDevice($deviceId, $data, $userData);
                $isVerify='y';
            }
        }else{
            //校验是否需要图片验证码
            $deviceVerify = $this->userService->verifyDevice($deviceId,$data);
            if($deviceVerify){
                $token = $this->userService->loginByUserDevice($deviceId, $data, $userData);
                $isVerify='y';
            }
        }
        //处理广告
        $isVip      = $this->userService->isVip($userData)?'y':'n';
        $result = array(
            'is_verify'     =>$isVerify,
            'token'         => $token,
            'version'       => $deviceType == "ios" ? strval($configs['ios_version']) : $configs['android_version'],
            'min_version'   => $deviceType == "ios" ? strval($configs['ios_min_version']) : $configs['android_min_version'],
            'version_description' => $deviceType == "ios" ? strval($configs['ios_version_desc']) : $configs['android_version_desc'],
            'download_url'  => $deviceType == "android"?$this->channelAppService->getApkByType('china_line',true):"",
            'ios_url'       => strval($configs['site_url']),
            'site_url'      => strval($configs['site_url']),
            'img_key'       => strval($configs['media_encode_key']),//图片解密key
            'can_use'       => 'y',
            'error_msg'     => '',
            'service_link'  => strval($configs['service_link']),
            'service_email' => strval($configs['service_email']),
            'up_join_tips'  => strval($configs['up_join_tips']),
            'group_link'    => strval($configs['group_link']),
            'cdn_header'    => strval($configs['cdn_referrer_domain']),
            'app_store'     => strval($configs['app_store']),
            'withdraw_tips' => strval($configs['withdraw_tips']),
            'ad_auto_jump'  => 'n',
            'ad_show_time'  => '5',
            'debug_key'     => 'weaas821862941ws',
            'template'      => 'default', //主题 default new
            'dark_tips'    => strval($configs['dark_tips']),
            'domains'       => value(function()use($deviceType,$domainArr){
                if(empty($domainArr)){return [];}
                $domains = $domainArr['channel']?:$domainArr['web'];
                $result = [];
                foreach($domains as $key=>$domain){
                    if($key>2){break;}
                    $result[] = 'https://'.$domain;
                }
                return $result;
            }),
            $limit = value(function () use($configs){
                if(isset($configs['app_layer_ad_show_method']) && $configs['app_layer_ad_show_method']== 'three'){
                    return 100;
                }
                return 100;
            }),
            //启动广告
            'ad' => value(function ()use($configs,$isVip,$adMethod,$token,$limit){
                if($adMethod==2){
                    $ads = $this->advService->getAll("app_start_ad", $isVip,$limit,$token);
                    if(empty($ads)) return [];
                    return $ads;
                }else{
                    $ads = $this->advService->getAll("app_start_ad", $isVip,$limit,$token);
                    if(empty($ads)){return null;}
                    return $ads[array_rand($ads)];
                }
            }),
            //首屏弹窗广告
            'layer_ad' => value(function ()use($isVip,$token){
                $ads = $this->advService->getAll("app_layer_ad", $isVip,100,$token);
                return empty($ads)?array():$ads;
            }),
            'layer'=>$this->getLayerAds($isVip,$configs,$token),
            'bottom_ad'=>value(function ()use($isVip,$token){
                $ads = $this->advService->getAll("home_bottom", $isVip,100,$token);
                if(empty($ads)){return null;}
                return $ads[array_rand($ads)];
            }),
            'bottom_ads'=>value(function ()use($isVip,$token){
                return $this->advService->getAll("home_bottom", $isVip,100,$token);
            }),
            //通知
            'notice' => value(function (){
                $notice = $this->articleService->getAnnouncement('announcement');
                return empty($notice) ? null : $notice;
            }),
            'movie_notice' => value(function (){
                $notice = $this->articleService->getAnnouncement('announcement_movie');
                return empty($notice) ? null : $notice;
            }),
            'post_notice' => value(function (){
                $notice = $this->articleService->getAnnouncement('announcement_post');
                return empty($notice) ? null : $notice;
            }),
            'account_login_tips'  => "1.账号和密码必须大于等于5位字符串\r\n2.不提供密码修改功能请牢记自己的密码\r\n3.不提供密码修改功能请勿外泄密码\r\n4.请牢记自己的账户,丢失后将不能找回",
            'sub_page_ad_show_method'=>empty($configs['sub_page_ad_show_method'])?'banner':strval($configs['sub_page_ad_show_method']),
            'common_app_ads'=> $this->advService->getAll('common_ico',$isVip,20,$token),
            //启动页和弹窗广告模式
            'app_start_ad_show_method' => strval($configs['app_start_ad_show_method']??'default'),
            'app_layer_ad_show_method' => strval($configs['app_layer_ad_show_method']??'default'),
            //漫画小说阅读页面固定广告
            //todo 等广告位出来调整位置
            'comics_detail_ads_top'=>$this->advService->getAll('app_play_ad', $isVip, 6),
            'novel_detail_ads_top'=>$this->advService->getAll('app_play_ad', $isVip, 6)
        );

        $result['upload_image_url'] = $this->commonService->getUploadImageUrl($configs);
        $result['upload_file_url'] = $this->commonService->getUploadFileUrl($configs);
        $result['upload_file_query_url'] = $this->commonService->getUploadFileQueryUrl($configs);
        $result['upload_file_max_length'] =strval( 600*1024*1024);
        $result['upload_image_max_length'] =strval( 1*1024*1024);
        $result['cdn_ping']= array(
            ["ping_url"=>$this->commonService->getCdnUrl($configs['system_user_headico']),"name"=>"cnd1"],
            ["ping_url"=>$this->commonService->getCdnUrl($configs['system_user_headico']),"name"=>"cnd2"]
        );
        $result['count_code'] = strval($domain?$domain['count_code']:'');

        //游戏链接
        $result['game_url'] = $configs['open_game']=='y'?$this->getGameUrl():'';
        $result['game_ico'] = $this->commonService->getCdnUrl($configs['game_ico']);

        //ai
        $result['open_ai'] = 'y';
        $result['webview_ai_url'] = value(function()use($configs,$deviceType,$domainArr){
            if(empty($domainArr)){return '';}
            $version = $configs['admin_static_version']?:'1';
            $domains = $domainArr['h5_webview']?:$domainArr['h5'];
            return $domains?'https://'.$domains[rand(0,count($domains)-1)].'/ai?_v='.$version:'';
        });

        //客服中心开关
        $customerConf                     = CenterBaseJob::getCenterConfig('customer');
        $result['customer_system_status'] = value(function () use ($customerConf, $result) {
            if ($customerConf['status'] == 'y') {
                return 'y';
            } else {
                $userId = $result['token']['user_id'] ?: 0;
                $uids   = $customerConf['test_ids'] ? explode(',', trim($customerConf['test_ids'])) : [];
                if ($userId && $uids && in_array($userId, $uids)) {
                    return 'y';
                }
            }
            return 'n';
        });

        return $result;
    }

    /**
     * 获取弹窗数据
     * @param $isVip
     * @param  $configs
     * @param  $token
     * @return array
     */
    public function getLayerAds($isVip,$configs,$token)
    {
        //首页弹窗广告,应用,公告
        $result = [];
        $limit = value(function () use($configs){
            if(isset($configs['app_layer_ad_show_method']) && $configs['app_layer_ad_show_method']== 'three'){
                return 100;
            }
            return 100;
        });
        $ads = $this->advService->getAll("app_layer_ad", $isVip, $limit,$token);
        if ($ads) {
            foreach ($ads as $ad) {
                $result[] = [
                    'type' => 'ad',
                    'data' => $ad,
                ];
            }
        }
        $apps = $this->advAppService->getAll(1,20,'首页弹窗');
        $layerApp = null;
        if ($apps) {
            foreach ($apps as &$app)
            {
                $app['image'] = $this->commonService->getCdnUrl($app['image']);
                unset($app);
            }
            $layerApp =  [
                'type' => 'apps',
                'data' => [
                    'items'=>$apps
                ],
            ];
            $layerAppPos =empty($configs['layer_app_pos'])?'0':strval($configs['layer_app_pos']*1);
            if($layerAppPos){
                array_unshift($result,$layerApp);
            }else{
                $result[] = $layerApp;
            }
        }
        $notice = $this->articleService->getAnnouncement('announcement');
        if($notice){
            $result[]=[
                'type'=>'notice',
                'data'=>$notice
            ];
        }
        return $result;
    }

    /**
     * 获取广告
     * @param $code
     * @param string $isVip
     * @param int $size
     * @return array|mixed|null
     */
    public function getAdsByCode($code,$isVip='n',$size=6)
    {
        $ads = $this->advService->getAll($code, $isVip,$size);
        return empty($ads)?array():$ads;
    }

    /**
     * 获取文章
     * @param string $code
     * @param int $page
     * @param int $pageSize
     * @return array|mixed|null
     */
    public function getArticleList($code = 'announcement', $page = 1, $pageSize = 10)
    {
        return $this->articleService->getArticleList($code, $page, $pageSize);
    }

    /**
     * 应用中心
     * @param int $page
     * @param int $pageSize
     * @return array|mixed
     */
    public function getAppStores($page = 1, $pageSize = 15)
    {
        $items = $this->advAppService->getAll($page,$pageSize);
        $result = array();
        foreach ($items as $item)
        {
            $item['image'] = $this->commonService->getCdnUrl($item['image']);
            $result[]=$item;
        }
        return empty($result)?array():array_values($result);
    }

    /**
     * 获取配置
     * @return mixed|null
     */
    public function getConfigs()
    {
        return $this->commonService->getConfigs();
    }

    /**
     * 获取配置
     * @param $code
     * @return mixed|null
     */
    public function getConfig($code)
    {
        return $this->commonService->getConfig($code);
    }

    /**
     * 发送短信
     * @param $phone
     * @param $type
     * @param $userId
     * @param string $country
     * @return bool
     * @throws BusinessException
     */
    public function doSendSms($phone, $type, $userId,$country='+86')
    {
        $types = array('bind', 'find');
        if (!in_array($type, $types)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '不能识别的类型!');
        }
        $smsKey = '';
        if ($type == 'bind') {
            if (empty($phone) || !CommonUtil::isPhoneNumber($phone)) {
                throw new BusinessException(StatusCode::DATA_ERROR, '手机号码格式错误!');
            }
            //查詢當前手機是否被其他手機綁定
            $count = $this->userService->count(array('phone' => $phone));
            if ($count > 1) {
                throw new BusinessException(StatusCode::DATA_ERROR, '该手机号码已经绑定其他账号,如需找回请到个人中心找回!');
            }
            $smsKey = 'phone_' . $phone;
        } elseif ($type == 'find') {
            if (empty($phone) || !CommonUtil::isPhoneNumber($phone)) {
                throw new BusinessException(StatusCode::DATA_ERROR, '手机号码格式错误!');
            }
            $count = $this->userService->count(array('phone' => $phone));
            if ($count < 1) {
                throw new BusinessException(StatusCode::DATA_ERROR, '该手机号码未绑定账号!');
            }
            $smsKey = 'phone_' . $phone;
        }
        $keyName = 'send_sms_' . $userId;
        if (!$this->commonService->checkActionLimit($keyName, 50, 1)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '请求频繁请稍后再试!');
        }
        $smsCode = rand(12358, 95878);
        $this->getRedis()->set($smsKey, $smsCode, 600);
        $data = array(
            'country'   => $country,
            'phone'     => $phone,
            'content'   => '验证码:' . $smsCode,
            'error_info'=> '',
            'type'      => $this->apiService->getDeviceType(),
            'ip'        => getClientIp()
        );
        try {
            $this->mmsService->sendSms($phone,$smsCode,getClientIp(),$country);
        }catch (\Exception $e){
            $data['error_info'] = $e->getMessage();
        }
        $this->smsService->save($data);
        if(!empty($data['error_info'])){
            throw new BusinessException(StatusCode::DATA_ERROR, $data['error_info']);
        }
        return true;
    }


    /**
     * 增加系统日志
     * @param $content
     * @return bool
     */
    public function addAppError($content)
    {
        $data = array(
            'content' => $content,
            'device_type' => $this->apiService->getDeviceType(),
            'device_version' => $this->apiService->getVersion(),
            'date' => date('Y-m-d'),
            'ip' => getClientIp()
        );
        $this->appErrorService->save($data);
        return true;
    }

    /**
     * 获取服务器状态
     * @return string[]
     */
    public function getServerStatus()
    {
        return [
            'mysql_status'=>'y',
            'mongo_status' =>$this->commonService->getMongoStatus()>0?'y':'n',
            'redis_status' =>$this->commonService->updateRedisCounter('check_server',1)>0?'y':'n',
            'disk_free_size'=>'',
            'os_name' => '',
            "memory_free_size"=>value(function (){
                exec('free -m',$sys_info);
                $rs=preg_replace("/\s{2,}/",' ',$sys_info[1]);
                $hd = explode(" ",$rs);
                return $hd[3] .'M';
            }),
            'cpu_num' =>'',
            'load_average'=>value(function (){
                exec('uptime',$sys_info);
                if(empty($sys_info) || empty($sys_info[0])){
                    return '';
                }
                return trim(substr($sys_info[0],strpos($sys_info[0],'average:')+8));
            }),
            "nginx_active_connections"=>0,
            "nginx_requests"=>0,
            "nginx_status"=>""
        ];
    }

    /**
     * 加入日志
     * @param $userId
     * @param $event
     * @param $eventData
     * @param $eventIp
     */
    public function joinActionQueue($userId,$event,$eventData,$eventIp)
    {
        $this->wssService->joinActionQueue($userId,$event,$eventData,$eventIp);
    }

    /**
     * 获取视频模块
     * @param $group
     * @return array
     */
    public function getBlockPosByGroup($group)
    {
        $blockPosArr = $this->blockPositionService->getAll();
        $result = array();
        foreach ($blockPosArr as $blockPos)
        {
            if($blockPos['group']==$group){
                $result[] = array(
                    'name' => $blockPos['name'],
                    'code' => $blockPos['code']
                );
            }
        }
        return $result;
    }
    /**
     * 获取帖子模块
     * @param $position
     * @return array
     */
    public function getPostBlock($position)
    {
        $bockItems = $this->postBlockService->getAll($position);
        $result = array();
        $result[] = array(
            'name'=> '关注',
            'filter' => json_encode(array('position'=>$position,'is_follow'=>'y')),
            'is_ai' => 'n'
        );
        $result[] = array(
            'name'=> '推荐',
            'filter' => json_encode(array('position'=>$position,'is_hot'=>'y')),
            'is_ai' => 'n'
        );
        foreach ($bockItems as $bockItem)
        {
            $result[] = array(
                'name' => $bockItem['name'],
                'filter' => json_encode(array('block_id'=>$bockItem['id'],'is_hot'=>'y','position'=>$position)),
                'is_ai' => $bockItem['is_ai']
            );
        }
        return $result;
    }

    /**
     * 关注
     * @param $userId
     * @param $objectId
     * @param $objectType
     * @return string
     * @throws BusinessException
     */
    public function doFollow($userId,$objectId,$objectType)
    {
        return $this->followService->do($userId,$objectId,$objectType);
    }

    /**
     * 游戏链接
     * @return mixed|string
     */
    public function getGameUrl()
    {
        $cacheData = container()->get('redis')->get('game_url_list');
        if($cacheData){
            $urls = json_decode($cacheData,true);
        }
        return empty($urls)?'':$urls[array_rand($urls)];
    }

    /**
     * 数据跟踪接口
     * @param $userId
     * @param $type
     * @param $id
     * @param $name
     * @return mixed|true
     */
    public function addTrackQueue($userId, $type, $id='', $name='')
    {
        return $this->appTrackService->addTrackQueue($userId,$type,$id,$name);
    }

    /**
     * 用户行为
     * @param $userId
     * @param $act
     * @return mixed|true
     */
    public function addActQueue($userId, $act)
    {
        return $this->userActService->addActQueue($userId,$act);
    }
}