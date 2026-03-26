<?php
namespace App\Jobs\Jms;


use App\Jobs\BaseJob;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

abstract class JmsJob extends BaseJob
{
    public $host;
    public $appid;
    public $appkey;
    public $startAt;

    public function __construct($startAt)
    {
        $config= container()->get('config')->jms;
        if($config==null || empty($config)){
            exit('Config error!');
        }
        $this->host     =$config->host;
        $this->appid    =$config->app_id;
        $this->appkey   =$config->app_key;

        $this->startAt  =$startAt;
    }


    /**
     * @param $data
     * @param $url
     * @return bool
     */
    public function doHttpRequest($data,$url)
    {
        $data = array(
            'app_id' =>$this->appid,
            'sign' => '',
            'data' => json_encode($data),
            'time' => time()
        );
        $data['sign'] = md5($data['data'].$data['time'].$this->appkey);
        $result =  CommonUtil::httpPost($this->host.$url, $data);
        $result = json_decode($result,true);
        if($result["status"]=='y'){
            return true;
        }else {
            LogUtil::info("Http error:" . json_encode($result));
            return false;
        }
    }
}