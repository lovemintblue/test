<?php


namespace App\Jobs\Common;


use App\Jobs\BaseJob;
use App\Services\ChannelAppService;
use App\Services\ChannelReportService;
use App\Services\CommonService;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 * 自动打包
 * @property ChannelAppService $channelAppService
 * @property ChannelReportService $channelReportService
 * @property CommonService $commonService
 * @package App\Jobs\Common
 */
class AutoBuildJob extends BaseJob
{
    private $appid;
    private $domain = 'https://www.uufrn1l.com';
    private $url    = '%s/cxapi/%s?appid=%s&key=F49FFB4945018841513E0AB47392934D';

    public function __construct($appid)
    {
        $this->appid = $appid;
    }

    public function handler($uniqid)
    {
        $report = date('i')=='10'?true:false;
        //同步最新渠道
        $this->async();
        //上报apk使用情况
//        if($report){$this->report();}
    }

    public function async()
    {
        $channel = $this->doHttpRequest([],'apk/find');

        if($channel['status']!='y'){
            LogUtil::error("获取渠道列表失败!");
            return false;
        }
        //返回空则自动下载全部删除
        if(empty($channel['data'])){
            $this->channelAppService->channelAppModel->delete(['name'=>'自动打包']);
        }else{
            foreach($channel['data'] as $key=>$val){
                $row = [
                    'name'=>strval('自动打包'),
                    'type'=>'channel_line',
                    'code'=>strval($val['channel_name']),
                    'link'=>strval($val['url']),
                    'is_auto_download'=>1,
                    'is_disabled'=>0,
                ];
                $has=$this->channelAppService->findFirst(['code'=>$row['code']]);
                if ($has) {
                    $row['_id']=$has['_id'];
                    unset($row['is_auto_download']);
                    unset($row['is_disabled']);
                }
                $this->channelAppService->save($row);
                LogUtil::info("已更新渠道包 {$row['code']}=>{$row['link']}");
            }
            //更新时间 2分钟前
            $this->channelAppService->channelAppModel->delete(['name'=>'自动打包','updated_at'=>['$lte'=>time()-60*2]]);
        }
    }

    public function report()
    {
        $channels = $this->channelAppService->getList([], [], [], 0, 10000);
        $data = [];
        foreach($channels as $channel){
            //查询当天的数据-time()-3600
            $reportChannel = $this->channelReportService->findFirst(['code'=>strval($channel['code']),'date'=>date('Y-m-d',time()-3600)]);
            $data[$channel['code']] = intval($reportChannel['user_reg']);
            $data[]=[
                'code'=>$channel['code'],
                'num'=>intval($reportChannel['user_reg']),
                'date'=>$reportChannel['date']
            ];
        }
        $this->doHttpRequest($data, 'apk/report');
        LogUtil::info('Do report apk ' . count($data) . '条');
    }

    /**
     * @param $data
     * @param $uri
     * @param int $retry
     * @return bool
     */
    public function doHttpRequest($data,$uri,$retry = 5)
    {
        if ($retry<1){return false;}
        $baseUrl    = sprintf($this->url,$this->domain,$uri,$this->appid);
        $requestData = array(
            'time'      => date('Y-m-d H:i:s'),
            'appid'     => $this->appid,
            'data'      => json_encode($data),
        );

        try{
            $result = CommonUtil::httpPost($baseUrl,$requestData);
            $result = json_decode($result,true);
            return $result;
//            return $result['status']=='y'?true:false;
        }catch (\Exception $e){
            return $this->doHttpRequest($data,$uri,--$retry);
        }
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