<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Controller\BaseBackendController;
use App\Services\CommonService;
use App\Utils\CommonUtil;

/**
 * 随机域名
 *
 * @package App\Controller\Backend
 *
 * @property  CommonService $commonService
 */
class AutoLineController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/autoLine');
    }

    /**
     * 随机线路
     * @param $appid
     * @param $month
     * @param $channelCode 渠道码
     * @param $lineId
     * @return string
     */
    private function getRandLine($appid,$month,$channelCode='',$lineId='')
    {
        if(empty($appid)){return '';}
        $hash = md5($appid.$month.$lineId.$channelCode);
        return  "api.".substr($hash,8,8).".com";
;
    }

    /**
     * 列表
     */
    public function listAction()
    {
        if($this->isPost()) {
            $filter['channel_code'] = $this->getRequest("channel_code");
            $filter['month'] = $this->getRequest("month")??date('Y-m');
            $items = [];

            $appid = container()->get('config')->api->appid;
            if(!empty($filter['channel_code'])){
                $items[] = [
                    'name'=>'渠道线路',
                    'month'=>$filter['month'],
                    'channel_code'=>$filter['channel_code'],
                    'url'=>$this->getRandLine($appid,$filter['month'],$filter['channel_code'])
                ];
            }

            for($i=0;$i<5;$i++){
                $items[] = [
                    'name'=>'基础线路'.($i+1),
                    'month'=>$filter['month'],
                    'channel_code'=>'-',
                    'url'=>$this->getRandLine($appid,$filter['month'],'',$i)
                ];
            }

            $this->sendSuccessResult([
                'filter'=>$filter,
                'items'=>empty($items) ? array() : array_values($items),
                'count'=>0
            ]);
        }
    }
}