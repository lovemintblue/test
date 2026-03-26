<?php

declare(strict_types=1);

namespace App\Tasks;

use App\Constants\StatusCode;
use App\Core\BaseTask;
use App\Exception\BusinessException;
use App\Services\AdminUserService;
use App\Services\ApiService;
use App\Services\ComicsService;
use App\Services\CommonService;
use App\Services\ConfigService;
use App\Services\IpService;
use App\Services\QueueService;
use App\Services\UserService;
use App\Utils\AesUtil;
use App\Utils\DevUtil;
use App\Utils\LogUtil;
use Phalcon\Cli\Task;

/**
 * Class ToolsTask
 * @package App\Tasks
 * @property CommonService $commonService
 * @property ConfigService $configService
 * @property UserService $userService
 * @property AdminUserService $adminUserService
 * @property QueueService $queueService
 * @property ComicsService $comicsService
 */
class ToolsTask extends BaseTask
{
    /**
     * model生成器
     */
    public function modelAction($table='')
    {
        $devUtils = new DevUtil();
        $devUtils->autoCreateModels($table);
    }

    /**
     * 导入ip
     * @throws BusinessException
     */
    public function importIpAction()
    {
        $ipService = new IpService();
        $ipService->init();
    }

    /**
     * 清理缓存
     */
    public function clearCacheAction()
    {
        $this->commonService->clearCache();
        LogUtil::info('Clear cache ok!');
    }

    /**
     * 清除日志
     */
    public function cleanLogAction()
    {

    }



    /**
     * 添加配置
     */
    public function addConfigsAction()
    {
        $rows=[
           // ['code' => 'movie_filter_tag_ids', 'name' => '视频库标签', 'type' =>1, 'value' => '', 'values' => '', 'group' => 'movie', 'sort' => 0, 'help' => '视频库的搜索标签,每个用,分开'],
           // ['code' => 'cartoon_filter_tag_ids', 'name' => '动漫库标签', 'type' =>1, 'value' => '', 'values' => '', 'group' => 'movie', 'sort' => 0, 'help' => '动漫库的搜索标签,每个用,分开'],
           // ['code' => 'sign_configs', 'name' => '签到配置', 'type' =>2, 'value' => '', 'values' => '', 'group' => 'userTask', 'sort' => 0, 'help' => '签到配置 天|积分数 每行一个'],
           // ['code' => 'comics_max_limit_view', 'name' => '漫画限流次数', 'type' =>1, 'value' => '', 'values' => '8', 'group' => 'app', 'sort' => 0, 'help' => '三分钟内最多可以阅读多少章漫画!'],
           // ['code' => 'order_prefix', 'name' => '订单前缀', 'type' =>1, 'value' => '', 'values' => '8', 'group' => 'other', 'sort' => 0, 'help' => '订单前缀'],
           // ['code' => 'share_integral', 'name' => '分享赠送积分', 'type' =>1, 'value' => '', 'values' => '20', 'group' => 'app', 'sort' => 0, 'help' => '分享赠送积分'],
//            ['code' => 'sub_page_ad_show_method', 'name' => '副页广告模式', 'type' =>3, 'value' => 'banner', 'values' => 'banner|banner;ico|ico', 'group' => 'app', 'sort' => 0, 'help' => '二级页面广告展示模式'],
//            ['code' => 'detail_page_ad_show_method', 'name' => '详情广告模式', 'type' =>3, 'value' => 'banner', 'values' => 'banner|banner;ico|ico', 'group' => 'app', 'sort' => 0, 'help' => '详情页面广告展示模式'],
            ['code' => 'app_start_ad_show_method', 'name' => '启动页广告模式', 'type' =>3, 'value' => 'default', 'values' => 'default|展示1个广告;three|展示3个广告', 'group' => 'app', 'sort' => 0, 'help' => '启动页广告展示'],
            ['code' => 'app_layer_ad_show_method', 'name' => '弹窗广告模式', 'type' =>3, 'value' => 'default', 'values' => 'default|展示1个广告;three|展示3个广告', 'group' => 'app', 'sort' => 0, 'help' => '弹窗广告展示'],
//            ['code' => 'play_ad_show_time', 'name' => '播放广告时间', 'type' =>1, 'value' => '5', 'values' => '20', 'group' => 'app', 'sort' => 0, 'help' => '详情广告展示时长'],
//            ['code' => 'detail_page_ad_num', 'name' => '详情广告数量', 'type' =>1, 'value' => '5', 'values' => '20', 'group' => 'app', 'sort' => 0, 'help' => '5个或者10个'],
//            ['code' => 'layer_app_pos', 'name' => '弹出应用位置', 'type' =>3, 'value' => '0', 'values' => '0|后面;1|前面', 'group' => 'app', 'sort' => 0, 'help' => '前面表示第一个弹出,后面表示最后弹出'],
//            ['code' => 'play_ad_auto_jump', 'name' => '播放广告跳转', 'type' =>3, 'value' => 'n', 'values' => 'n|否;y|是', 'group' => 'app', 'sort' => 0, 'help' => '否表示广告倒计时不自动跳转,是表示自动跳转'],
//            ['code' => 'share_vip_day', 'name' => '分享送VIP天数', 'type' =>1, 'value' => '', 'values' => '', 'group' => 'app', 'sort' => 0, 'help' => '分享送VIP天数,为空或者0表示不送'],
//            ['code' => 'post_multiplication_base', 'name' => '帖子基数', 'type' =>1, 'value' => '', 'values' => '', 'group' => 'app', 'sort' => 0, 'help' => '帖子分类前端显示数量为 帖子基数x分类下真实帖子数'],
//            ['code' => 'can_update_nickname_group_id', 'name' => '可以修改昵称的会员卡', 'type' =>1, 'value' => '', 'values' => '', 'group' => 'app', 'sort' => 0, 'help' => '填写会员卡id，多个英文逗号间隔'],
//            ['code' => 'media_url_cdn', 'name' => '媒资回源CDN', 'type' => 1, 'value' => '', 'values' => '', 'group' => 'cdn', 'sort' => 0, 'help' => ''],
//            ['code' => 'project_group_id', 'name' => '维护群ID', 'type' => 1, 'value' => '', 'values' => '', 'group' => 'other', 'sort' => 0, 'help' => ''],
//            ['code' => 'project_bot_token', 'name' => '机器人token', 'type' => 1, 'value' => '', 'values' => '', 'group' => 'other', 'sort' => 0, 'help' => ''],
//            ['code' => 'business_link', 'name' => '商务链接', 'type' => 1, 'value' => '', 'values' => '', 'group' => 'base', 'sort' => 0, 'help' => ''],
//            ['code' => 'open_game', 'name' => '游戏图标显示', 'type' => 3, 'value' => '', 'values' => 'y|显示;n|隐藏', 'group' => 'app', 'sort' => 0, 'help' => '首页是否显示游戏图标'],
//            ['code' => 'game_ico', 'name' => '游戏图标', 'type' => 6, 'value' => '', 'values' => '', 'group' => 'app', 'sort' => 0, 'help' => '首页游戏图标'],
//            ['code' => 'game_channel', 'name' => '游戏渠道', 'type' => 1, 'value' => '', 'values' => '', 'group' => 'app', 'sort' => 0, 'help' => '游戏渠道'],
//            ['code' => 'business_link', 'name' => '商务链接', 'type' => 1, 'value' => '', 'values' => '', 'group' => 'base', 'sort' => 999, 'help' => '可配置tg和土豆链接'],

//            ['code' => 'media_url_video', 'name' => '媒资链接-视频', 'type' => 1, 'value' => '', 'values' => '', 'group' => 'other', 'sort' => 995, 'help' => '媒资库链接-视频'],
//            //ai配置
//            ['code' => 'ai_api_domain', 'name' => 'Ai接口域名', 'type' => 1, 'value' => '', 'values' => '', 'group' => 'other', 'sort' => 0, 'help' => ''],
//            ['code' => 'ai_api_key', 'name' => 'Ai接口Key', 'type' => 1, 'value' => '', 'values' => '', 'group' => 'other', 'sort' => 0, 'help' => ''],
//            ['code' => 'media_url_open', 'name' => '外部访问媒资库资源域名', 'type' => 1, 'value' => '', 'values' => '', 'group' => 'other', 'sort' => 0, 'help' => ''],
//            ['code' => 'ai_tpl_id', 'name' => '模版资源', 'type' => 3, 'value' => '2', 'values' => '1|模版1;2|模版2', 'group' => 'ai', 'sort' => 1000, 'help' => ''],
//            ['code' => 'ai_face_image_price', 'name' => '图片换脸价格', 'type' => 1, 'value' => '', 'values' => '', 'group' => 'ai', 'sort' => 900, 'help' => '自定义模版-单张Ai换脸的价格'],
////            ['code' => 'ai_face_video_price', 'name' => '视频换脸价格', 'type' => 1, 'value' => '', 'values' => '', 'group' => 'ai', 'sort' => 900, 'help' => '单张Ai换脸的价格'],
//            ['code' => 'ai_undress_price', 'name' => 'AI去衣价格', 'type' => 1, 'value' => '', 'values' => '', 'group' => 'ai', 'sort' => 900, 'help' => '单张Ai去衣的价格'],
////            ['code' => 'ai_change_price', 'name' => 'AI换装价格', 'type' => 1, 'value' => '', 'values' => '', 'group' => 'ai', 'sort' => 900, 'help' => '单张Ai换装的价格'],
////            ['code' => 'ai_generate_price', 'name' => 'AI绘画价格-普通', 'type' => 1, 'value' => '', 'values' => '', 'group' => 'ai', 'sort' => 900, 'help' => '单张Ai绘画的价格'],
////            ['code' => 'ai_generate_porn_price', 'name' => 'AI绘画价格-18禁', 'type' => 1, 'value' => '', 'values' => '', 'group' => 'ai', 'sort' => 900, 'help' => '单张Ai绘画的价格'],
//            ['code' => 'ai_novel_price', 'name' => 'AI小说价格', 'type' => 1, 'value' => '', 'values' => '', 'group' => 'ai', 'sort' => 900, 'help' => '单张Ai小说的价格'],
////            ['code' => 'ai_emoji_price', 'name' => 'AI表情价格', 'type' => 1, 'value' => '', 'values' => '', 'group' => 'ai', 'sort' => 900, 'help' => '单个Ai表情的价格'],
////            ['code' => 'ai_chat_price', 'name' => 'AI聊天价格', 'type' => 1, 'value' => '', 'values' => '', 'group' => 'ai', 'sort' => 900, 'help' => '单条Ai聊天消息的价格'],
//            ['code' => 'prompt_word_group', 'name' => '创意提示词分组', 'type' => 2, 'value' => '', 'values' => '', 'group' => 'ai', 'sort' => 900, 'help' => '每行一个格式：一级分组===>二级分组1,二级分组2;最多支持两级分组'],
//            ['code' => 'ai_tips', 'name' => 'AI定制提示', 'type' => 2, 'value' => '可以选择公开或者私密，公开则会分享给大家一起欣赏哟,兄弟有福一起享,选择私密则会出现在 我的购买-AI定制中，别人是无法查看的哟！', 'values' => '', 'group' => 'ai', 'sort' => 900, 'help' => '发布Ai定制时候的提示'],
////            ['code' => 'ai_chat_model', 'name' => 'AI聊天模型', 'type' => 2, 'value' => '', 'values' => '', 'group' => 'ai', 'sort' => 900, 'help' => '每行一个格式:模型名称|模型图片'],
//            ['code' => 'ai_undress_example', 'name' => 'AI去衣效果图', 'type' => 7, 'value' => '', 'values' => '', 'group' => 'ai', 'sort' => 800, 'help' => ''],
//            ['code' => 'resource_url_cdn', 'name' => '附件资源CDN', 'type' => 1, 'value' => '', 'values' => '', 'group' => 'cdn', 'sort' => 0, 'help' => ''],
            ['code' => 'cdn_id', 'name' => 'CDN系统id', 'type' => 1, 'value' => '', 'values' => '', 'group' => 'cdn', 'sort' => 1000, 'help' => ''],
        ];
        foreach ($rows as $row) {
            $this->configService->insert($row);
        }
        LogUtil::info('Add config ok!');
    }

    /**
     * 删除配置
     * @param $code
     */
    public function  delConfigAction($code)
    {
        $this->configService->delConfig($code);
        LogUtil::info('Del config ok!');
    }

    /**
     * 生成用户
     * @param $num
     * @throws BusinessException
     */
    public function userAction($num)
    {
        for ($i=0;$i<$num;$i++){
            $uniqid  = uniqid();
            $deviceId='virtual_'.$uniqid;
            $this->userService->register($deviceId,'android','1.0','system','system_'.$uniqid,1);
            LogUtil::info("Create user {$deviceId} ok!");
        }
        LogUtil::info("Create user num:{$num} ok!");
    }

    /**
     * 添加用户
     * @param $username
     * @param $password
     * @param $roleId
     * @param string $googleCode
     */
    public function addAdminUserAction($username,$password,$roleId,$googleCode='')
    {
        try{
           $result= $this->adminUserService->addUser($username,$password,$roleId,$googleCode);
           LogUtil::info($result?'ok':'error');
        }catch (\Exception $exception){
            LogUtil::error($exception->getMessage());
        }
    }

    /**
     * 绑定谷歌
     * @param $username
     * @param string $googleCode
     */
    public function bindGoogleCodeAction($username,$googleCode='')
    {
        try{
            if($googleCode){
                $result= $this->adminUserService->bindGoogle($username,$googleCode);
                LogUtil::info($result?'ok':'error');
            }else {
                $result = $this->adminUserService->getGoogleQrcode($username);
                LogUtil::info('Google code url:' . $result);
            }
        }catch (\Exception $exception){
            LogUtil::error($exception->getMessage());
        }
    }

    /**
     * 禁用用户
     * @param $username
     * @param bool $isDisable
     */
    public function disableAdminUserAction($username,$isDisable=true)
    {
        try{
            $result= $this->adminUserService->disableUser($username,$isDisable);
            LogUtil::info($result?'ok':'error');
        }catch (\Exception $exception){
            LogUtil::error($exception->getMessage());
        }
    }

    /**
     * 添加管理员ip
     * @param string $action
     * @param string $ip
     */
    public function adminIpAction($action='',$ip='')
    {
        $ipValue = $this->configService->getConfig('whitelist_ip');
        $ips = array();
        if($ipValue===null){
            $config = ['code' => 'whitelist_ip', 'name' => 'IP白名单', 'type' =>2, 'value' => '', 'values' => '', 'group' => 'other', 'sort' => 0, 'help' => '管理系统IP白名单'];
            $this->configService->insert($config);
        }else{
            $ipValue = str_replace("\r","",$ipValue);
            $ips = explode("\n",$ipValue);
        }
        if($action=='del'){
            for ($i=(count($ips)-1);$i>-1;$i--){
                if($ips[$i]==$ip){
                    unset($ips[$i]);
                }
            }
        }else{
            $ips[] = $ip;
            $ips = array_unique($ips);
        }
        $this->configService->save('whitelist_ip',join("\n",$ips));
        $this->configService->clear();
        LogUtil::info('Ok');
    }

    /**
     * 导入漫画
     * @param string $file
     */
    public function importComicsAction($file='')
    {
        if(empty($file) || !file_exists($file)){
            LogUtil::error('文件不存在!');
            return;
        }
        $handle = fopen($file,'r');
        while (($line=fgets($handle))!==false)
        {
            $line = trim($line);
            if(empty($line)){
                continue;
            }
            LogUtil::info('Import id:'.$line);
            $this->comicsService->asyncMrsById($line);
        }
        fclose($handle);
    }

    public function importAdminUserAction()
    {
        ini_set('memory_limit', '512M');
        try{
            $adminUserFile = RUNTIME_PATH . '/admin_user.txt';
            if (!file_exists($adminUserFile)) {
                throw new BusinessException(StatusCode::DATA_ERROR, "no user config!");
            }
            $handle = fopen($adminUserFile, 'r+');
            while (($line = fgets($handle)) !== false) {
                $line = str_replace(array("\n", "\r"), "", $line);
                list($id, $roleId, $username, $googleCode, $status,$password) = explode(",", $line);
                if($id&&$username&&$googleCode){
                    $adminUserModel = $this->adminUserService->findFirst(array('username' => $username));
                    $slat = strval(mt_rand(10000, 50000));
                    $password = $password?:$username.'123!';
                    $password = $this->adminUserService->makePassword($password, $slat);
                    $row = array(
                        '_id'        => intval($id),
                        'username'   => strval($username),
                        'real_name'  => strval($username),
                        'google_code'=> strval($googleCode),
                        'role_id'    => intval($adminUserModel['role_id']?:$roleId),
                        'is_disabled'=> intval($status!='enabled'),
                        'email'      => '',
                        'password'   => strval($adminUserModel['password']?:$password),
                        'slat'       => strval($adminUserModel['slat']?:$slat),
                        'login_at'   => intval($adminUserModel['login_at']?:0),
                        'login_ip'   => strval($adminUserModel['login_ip']?:''),
                        'login_num'  => intval($adminUserModel['login_num']?:0),
                        'created_at' => intval($adminUserModel['created_at']?:time()),
                        'updated_at' => intval($adminUserModel['updated_at']?:time()),
                    );
                    $result = $this->adminUserService->adminUserModel->findAndModify(['_id'=>$row['_id']],$row,[],true);
                    if(empty($result)){
                        $this->adminUserService->addLog(-1, '终端', '终端添加管理:' . $row['username'],'127.0.0.1');
                    }
                    LogUtil::info("import user success name:{$username}");
                }elseif(is_numeric($id)){
                    LogUtil::error("import user error name:{$username}");
                }
            }
            fclose($handle);
        }catch (\Exception $exception){
            LogUtil::error($exception->getMessage());
        }
    }

    /**
     * 清理漫画中的一些字符
     * @param string $keywords
     */
    public function  clearComicsNameAction($keywords='')
    {
        if(empty($keywords)){
            LogUtil::error("Please enter keywords!");
            return;
        }
        $count = $this->comicsService->count();
        $pageSize = 1000;
        $totalPage = ceil($count/$pageSize);
        for ($page=1;$page<=$totalPage;$page++)
        {
            $items = $this->comicsService->getList([],[],[],($page-1)*$pageSize,$pageSize);
            foreach ($items as $item)
            {
                if(strpos($item['name'],$keywords)!==false)
                {
                    $name = str_replace($keywords,'',$item['name']);
                    $this->comicsService->comicsModel->updateRaw(['$set'=>array('name'=>$name)],['_id'=>$item['_id']]);
                    LogUtil::info('Clear name:'.$item['name'].'=>'.$item['_id']);
                }
            }
        }
    }

}