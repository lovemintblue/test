<?php


namespace App\Jobs\Game;


use App\Jobs\BaseJob;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 * 同步游戏链接
 * Class AsyncGameJob
 * @package App\Jobs\Game
 */
class AsyncGameJob extends BaseJob
{
    public function handler($uniqid)
    {
        $configs = getConfigs();
        $channel = $configs['game_channel'];
        if(empty($channel)){
            LogUtil::error("渠道为空");
            return;
        }
        $url = 'http://jhgfdsawre.com/api/Domain/redirects';
        $data = [
            'channel'=>$channel
        ];
        $data['sign'] = strtoupper(md5(json_encode($data)));
        $data['timestamp'] = time();
        $result = json_decode(CommonUtil::httpJson($url,$data),true);
        if($result['code']!=200){
            LogUtil::error("接口请求异常 url:{$url} error:{$result['msg']}");
            return;
        }
        $urls = [];
        foreach($result['data']['sdk']?:[] as $key=>$sdk){
            if($key>=20){break;}
            $sdk = str_replace('http://','https://',$sdk);
            $urls[] = "{$sdk}?channel={$channel}";
        }

        $keyName = 'game_url_list';
        $cacheData = container()->get('redis')->get($keyName);
        if($cacheData==json_encode($urls)){
            LogUtil::info('Async game domain no newer data!');
            return;
        }
        container()->get('redis')->set($keyName, json_encode($urls));
        LogUtil::info('Async game domain ok! total:'.count($urls));
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