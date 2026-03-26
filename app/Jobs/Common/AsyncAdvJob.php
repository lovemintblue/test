<?php


namespace App\Jobs\Common;


use App\Constants\CommonValues;
use App\Jobs\BaseJob;
use App\Services\AdvAppService;
use App\Services\AdvPosService;
use App\Services\AdvService;
use App\Services\AgentSystemService;
use App\Services\ConfigService;
use App\Utils\LogUtil;

/**
 * 临时推送数据用
 * Class AsyncDomainJob
 * @property AgentSystemService $agentSystemService
 * @property AdvPosService $advPosService
 * @property AdvService $advService
 * @property AdvAppService $advAppService
 * @property ConfigService $configService
 * @package App\Jobs\Common
 */
class AsyncAdvJob extends BaseJob
{
    public $action;
    public $data;
    public $url;

    public function __construct($url,$action='')
    {
        $this->url=$url;
        $this->action=$action;
    }

    public function handler($uniqid)
    {
        if($this->action){
            $action = $this->action;
            $this->$action();
        }else{
            $this->advPos();
            $this->adv();
            $this->advApp();
        }
    }

    //推送广告位
    public function advPos()
    {
        if(!empty($this->data)){
            $this->advPosDataSubmit($this->data);
        }else{
            $advPos=$this->advPosService->getList([],[],['created_at'=>1],0,1000);
            foreach ($advPos as $pos) {
                $this->advPosDataSubmit($pos);
            }
        }
    }
    public function advPosDataSubmit($pos)
    {
        $postData=[
            'code' => strval($pos['code']),
            'name' => strval($pos['name']),
            'width' => strval($pos['width']),
            'height' => strval($pos['height']),
            'is_disabled' => strval($pos['is_disabled']),
        ];
        $result = $this->agentSystemService->doHttpPost('ad/asyncPos',$postData);
        if (empty($result) || $result['status'] != 'y') {
            LogUtil::error('Async advPos error!'.var_export($result,true));
            exit;
        }
        LogUtil::info('post advPos '.$postData['name'].' code '. $postData['code'].' ok!');
    }

    //推送广告
    public function adv()
    {
        if(empty($this->url)){
            LogUtil::error('图片访问域名未配置');
            return false;
        }
        if(!empty($this->data)){
            $this->advDataSubmit($this->data);
        }else{
            $advs=$this->advService->getList([],[],['created_at'=>1],0,1000);
            foreach ($advs as $adv) {
                $this->advDataSubmit($adv);
            }
        }
    }
    public function advDataSubmit($adv)
    {
        $postData=[
            'id' => strval($adv['_id']),
            'name' => strval($adv['name']),
            'position_code' => strval($adv['position_code']),
            'link' => strval($adv['link']),
            'start_time' => strval(date('Y-m-d H:i:s',$adv['start_time'])),
            'end_time' => strval(date('Y-m-d H:i:s',$adv['end_time'])),
            'show_user_type' => strval($adv['right']),
            'img' => $this->url.strval($adv['content']),
            'sort' => strval($adv['sort']),
            'channel_group' => strval($adv['channel_group']?:'other'),
        ];
        $result = $this->agentSystemService->doHttpPost('ad/asyncOld',$postData);
        if (empty($result) || $result['status'] != 'y') {
            LogUtil::error('Async adv error!'.var_export($result,true));
            exit;
        }
        LogUtil::info('post adv '.$postData['name'].' position_code '. $postData['position_code'].' ok!');
    }

    //推送广告应用
    public function advApp()
    {
        if(empty($this->url)){
            LogUtil::error('图片访问域名未配置');
            return false;
        }
        if(!empty($this->data)){
            $this->advAppDataSubmit($this->data);
        }else{
            $advApps=$this->advAppService->getList([],[],['created_at'=>1],0,1000);
            foreach ($advApps as $app) {
                $this->advAppDataSubmit($app);
            }
        }
    }
    public function advAppDataSubmit($app)
    {
        $postData=[
            'id' => strval($app['_id']),
            'name' => strval($app['name']),
            'image' => $this->url.strval($app['image']),
            'download' => strval($app['download']),
            'sort' => strval($app['sort']),
            'download_url' => strval($app['download_url']),
            'description' => strval($app['description']),
            'is_hot' => strval(!empty($app['is_hot'])?'y':'n'),
            'channel_group' => strval($app['channel_group']?:'other'),
            'group' => strval($app['category']),
        ];
        $result = $this->agentSystemService->doHttpPost('ad/asyncOldApp',$postData);
        if (empty($result) || $result['status'] != 'y') {
            LogUtil::error('Async advApp error!'.var_export($result,true));
            exit;
        }
        LogUtil::info('post advApp '.$postData['name'].' group:'. $postData['group'].' id:'.$postData['id'].' ok!');
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