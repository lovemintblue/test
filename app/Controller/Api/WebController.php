<?php


declare(strict_types=1);

namespace App\Controller\Api;

use App\Core\Controller\BaseController;
use App\Exception\BusinessException;
use App\Repositories\Api\SystemRepository;
use App\Services\AdvService;
use App\Services\AiService;
use App\Services\ApkService;
use App\Services\ChannelAppService;
use App\Services\DomainService;
use App\Utils\AesUtil;

/**
 * Class UserController
 * @package App\Controller\Api
 * @property SystemRepository $systemRepository
 * @property DomainService $domainService
 * @property ChannelAppService $channelAppService
 * @property AdvService $advService
 * @property AiService $aiService
 */
class WebController extends BaseController
{
    /**
     * 配置信息
     */
    public function configAction()
    {
        $configs = $this->systemRepository->getConfigs();
        $result = array(
            'site_title' => $configs['site_title'],
            'site_name'  => $configs['site_name'],
            'keywords' => $configs['keywords'],
            'description' => $configs['description'],
            'site_url' => $configs['site_url'],
            'service_link'  => $configs['service_link'],
            'group_link'   => $configs['group_link'],
            'business_link'  => $configs['business_link'],
            'service_email' => $configs['service_email'],
            'count_code' => $configs['count_code'],
            'version'    => $configs['landing_static_version'],
            'ios_description' => $configs['ios_description'],
            'apk_china_line'  => $this->channelAppService->getApkByType('china_line'),
            'apk_oversea_line'  => $this->channelAppService->getApkByType('oversea_line'),
            'apk_channel_line'  => $this->channelAppService->getApkByType('channel_line'),
            'domain' => $this->domainService->getAllGroupBy(),
            'domain_channel_code' => $this->domainService->getAllGroupBy('url','channel_code','string'),
            'nav_url' => $configs['nav_url'],
            'no_nav_channels' => $configs['no_nav_channels'],
            'web_float_ad' => $this->advService->getAll("web_float_ad", 'n',5),
        );
        $result = base64_encode(json_encode($result));
        $this->sendSuccessResult($result);
    }

    /**
     * 新增日志
     */
    public function logsAction()
    {
        $data = $_REQUEST['data'];
        if (empty($data)) {
            $this->sendErrorResult('数据错误!');
        }
        $items = json_decode($data,true);
        foreach ($items as $item)
        {
            $data = array();
            if($item['action']=='download'){
                $channel = strpos($item['data']['code'],'channel://')!==false?str_replace('channel://','',$item['data']['code']):"";
                $data = array(
                    'action' => 'download_'.$item['data']['type'],
                    'uid' => $item['data']['uid'],
                    'is_new_user' => $item['data']['is_new_user'] *1,
                    'url' => $item['data']['url'],
                    'time' => $item['data']['time'] *1,
                    'channel' => $channel,
                    'ip' => trim($item['data']['ip']),
                    'referer' => trim($item['data']['referer'])
                );
                $data['date'] = date('Y-m-d',$data['time']);
            }elseif ($item['action']=='view'){
                $channel = strpos($item['data']['code'],'channel://')!==false?str_replace('channel://','',$item['data']['code']):"";
                $data = array(
                    'action' => 'view',
                    'uid' => $item['data']['uid'],
                    'is_new_user' => $item['data']['is_new_user'] *1,
                    'url' => $item['data']['url'],
                    'time' => $item['data']['time'] *1,
                    'channel' => $channel,
                    'ip' => trim($item['data']['ip']),
                    'referer' => trim($item['data']['referer'])
                );
                $data['date'] = date('Y-m-d',$data['time']);
            }else{
                continue;
            }
        }
        $this->sendSuccessResult();
    }

    /**
     * 中转-第三方链接逻辑处理
     * @return void
     */
    public function jumpAction()
    {
        try {
            $token = $this->getRequest("token",'string','');
            $token = json_decode(AesUtil::decrypt($token),true);
            if(empty($token)||empty($token['user_id'])||$token['key']!=container()->get('config')->app->webview_key){
                throw new BusinessException();
            }

            switch ($token['type']){
                case 'letian'://乐天游戏
                    $jumpUrl = $this->aiService->getGameQpAuthUrl($token['user_id'],$token['device_type']);
                    break;
                default:
                    $jumpUrl = null;
                    break;
            }

            if($jumpUrl){
                echo "<script>window.location.replace('{$jumpUrl['auth_url']}');</script>";
//                header("Location: {$jumpUrl['auth_url']}");
                exit;
            }
        } catch (\Exception $exception){

        }
        header("http/1.1 404 not found");
        header("status: 404 not found");
    }
}
