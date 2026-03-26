<?php


namespace App\Jobs\Common;


use App\Jobs\BaseJob;
use App\Models\ChannelAppModel;
use App\Models\UserModel;
use App\Services\AgentSystemService;
use App\Services\ChannelAppService;
use App\Services\UserGroupService;
use App\Utils\AesUtil;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;
use App\Utils\UserSign;

/**
 * Class UserJob
 * @property UserGroupService $userGroupService
 * @property UserModel $userModel
 * @property ChannelAppService $channelAppService
 * @property AgentSystemService $agentSystemService
 * @package App\Jobs\Common
 */
class ChannelApkJob extends BaseJob
{
    public function handler($uniqid)
    {
        $page = 1;
        while (true) {
            $data = array('page' => $page);
            $result = $this->agentSystemService->doHttpPost('channel/apk', $data);
            if (empty($result) || $result['status'] != 'y') {
                LogUtil::error('Async error!');
                break;
            }
            if(empty($result['data'])){
                break;
            }
            foreach ($result['data'] as $item) {
                $type = 'channel_line';
                if (strpos($item['channel_code'], 'china_') !== false) {
                    $type = 'china_line';
                } elseif (strpos($item['channel_code'], 'oversea_') !== false) {
                    $type = 'oversea_line';
                }else{
                    continue;
                }

                $channelApp = $this->channelAppService->findFirst(array('code' => $item['channel_code']));
                $apkLink = empty($item['cache_apk_link']) ? $item['apk_link'] : $item['cache_apk_link'];
                LogUtil::info('Check channel apk:' . $item['channel_code']);
                if (empty($channelApp)) {
                    $row = array(
                        'name' => $item['channel_code'],
                        'type' => $type,
                        'code' => $item['channel_code'],
                        'link' => $apkLink,
                        'is_auto_download' => empty($item['is_auto_download']) ? 0 : 1,
                        'is_need_verify' => 0,
                        'is_disabled' => 0
                    );
                    $this->channelAppService->save($row);
                    LogUtil::info('Add channel apk:' . $item['channel_code']);
                } else {
                    if ($channelApp['link'] != $apkLink) {
                        $this->channelAppService->save(array(
                            '_id' => $channelApp['_id'] * 1,
                            'link' => $apkLink,
                            'is_auto_download' => empty($item['is_auto_download']) ? 0 : 1
                        ));
                        LogUtil::info('Update channel apk:' . $item['channel_code']);
                    }
                }
            }
            $page += 1;
        }
        LogUtil::info('Async channel apk ok!');
    }

    public function success($uniqid)
    {
        // TODO: Implement success() method.
    }

    public function error($uniqid)
    {
        // TODO: Implement error() method.
    }

}