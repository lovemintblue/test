<?php

namespace App\Jobs\Mrs;

use App\Constants\CacheKey;
use App\Jobs\BaseJob;
use App\Services\AgentSystemService;
use App\Services\ConfigService;
use App\Utils\LogUtil;

/**
 * 同步cdn域名
 * Class LsjCdnJob
 * @property AgentSystemService $agentSystemService
 * @property ConfigService $configService
 * @package App\Jobs\Common
 */
class CdnJob extends BaseJob
{
    public function handler($uniqid)
    {
        $success = 0;
        $configs = getConfigs();
        if($configs['cdn_id']){
            $result = $this->agentSystemService->cdn($configs['cdn_id']);
            if(empty($result)){return;}

            //视频CDN驱动-默认
            if(isset($configs['cdn_drive_video_default'])&&$configs['cdn_drive_video_default']!=$result['cdn_drive_video_default']){
                $this->configService->save('cdn_drive_video_default',$result['cdn_drive_video_default']);
                $success++;
            }
            //视频CDN
            if(isset($configs['cdn_video_default'])&&$configs['cdn_video_default']!=$result['cdn_video_default']){
                $this->configService->save('cdn_video_default',$result['cdn_video_default']);
                $success++;
            }
            //图片CDN驱动
            if(isset($configs['cdn_drive_image_default'])&&$configs['cdn_drive_image_default']!=$result['cdn_drive_image_default']){
                $this->configService->save('cdn_drive_image_default',$result['cdn_drive_image_default']);
                $success++;
            }
            //图片CDN
            if(isset($configs['cdn_image_default'])&&$configs['cdn_image_default']!=$result['cdn_image_default']){
                $this->configService->save('cdn_image_default',$result['cdn_image_default']);
                $success++;
            }
            //海外视频CDN驱动
            if(isset($configs['cdn_drive_video_overseas'])&&$configs['cdn_drive_video_overseas']!=$result['cdn_drive_video_overseas']){
                $this->configService->save('cdn_drive_video_overseas',$result['cdn_drive_video_overseas']);
                $success++;
            }
            //海外视频CDN
            if(isset($configs['cdn_video_overseas'])&&$configs['cdn_video_overseas']!=$result['cdn_video_overseas']){
                $this->configService->save('cdn_video_overseas',$result['cdn_video_overseas']);
                $success++;
            }
            //CDN Referrer
            if(isset($configs['cdn_referrer_domain'])&&$configs['cdn_referrer_domain']!=$result['cdn_referrer_domain']){
                $this->configService->save('cdn_referrer_domain',$result['cdn_referrer_domain']);
                $success++;
            }
            //上传地址
            if(isset($configs['upload_url'])&&$configs['upload_url']!=$result['upload_url']){
                $this->configService->save('upload_url',$result['upload_url']);
                $success++;
            }
        }

        //ip白名单
        $result = $this->agentSystemService->ipWhitelist();
        if($result!==false){
            $ipWhitelist = implode("\r\n",$result);
            if(isset($configs['whitelist_ip'])&&$configs['whitelist_ip']!=$ipWhitelist){
                $this->configService->save('whitelist_ip',$ipWhitelist);
                $success++;
            }
        }

        if($success){
            delCache(CacheKey::SYSTEM_CONFIG);
            LogUtil::info('Async cdn ok!');
        }
    }

    public function success($uniqid)
    {

    }

    public function error($uniqid)
    {

    }
}