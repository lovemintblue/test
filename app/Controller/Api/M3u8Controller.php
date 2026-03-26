<?php


namespace App\Controller\Api;


use App\Controller\BaseApiController;
use App\Exception\BusinessException;
use App\Services\M3u8Service;

/**
 * Class M3u8Controller
 * @property M3u8Service $m3u8Service
 * @package App\Controller\H5
 */
class M3u8Controller extends BaseApiController
{
    /**
     * 初始化
     * @throws BusinessException
     */
    public function initialize()
    {

    }

    /**
     * m3u8播放地址
     * @param string $token
     */
    public function pAction($token='')
    {
        if(empty($token) || strpos($token,'.m3u8')===false){
            $this->send404();
        }
        $token = str_replace('.m3u8','',$token);
        $result = $this->m3u8Service->decode($token);
        if($result){
            ob_clean();
            $date = gmdate("D, j M Y H:i:s", $result['time'])." GMT";
            header("Content-Type: application/vnd.apple.mpegurl");
            header("Cache-Control: public, max-age=7200", true);
            header("Last-Modified: $date", true);
            echo $result['content'];
            exit;
        }
        $this->send404();
    }
}