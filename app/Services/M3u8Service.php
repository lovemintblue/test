<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Utils\CommonUtil;

/**
 * Class M3u8Service
 * @package App\Services
 * @property CommonService $commonService
 * @property CdnService $cdnService
 */
class M3u8Service extends BaseService
{
    private $key='cm_';

    /**
     * 获取加密m3u8地址
     * @param $m3u8Url
     * @param string $deviceType
     * @param string $cdnType
     * @param string $source
     * @param string $module
     * @return string
     */
    public function encode($m3u8Url,$deviceType='',$cdnType='default',$source='media',$module='Api')
    {
        if(empty($m3u8Url)){
            return '';
        }

        $domain = '';
        $configs = getConfigs();
        if($deviceType=='android' || $deviceType=='ios'){
            $requestScheme = CommonUtil::getServerSchema();
            $domain = $requestScheme."://".$_SERVER['HTTP_HOST'];
        }

        if($source=='laosiji'){
            $config = container()->get('config');
            $videoCdn = $cdnType=='overseas'?$config->mrs->laosiji_aws_path:$config->mrs->laosiji_tencent_path;
            return $this->cdnService->getLsjUrl($domain.$videoCdn,$m3u8Url);
        }

        //每个token可以使用6个小时  但是业务系统每2个小时换token
        $cdnDriveType = $configs['cdn_drive_video_'.$cdnType];
        $fileKeyMaxTime = 1*3600;
        $tokenMaxTime   =  3*3600;
        /*******腾讯的key最多3小时************/
        if($cdnDriveType=='tencent'){
            $fileKeyMaxTime = 180;
            $tokenMaxTime  = 3600*2;
        }
        $cdnUrl = $configs['cdn_video_'.$cdnType];
        $fileKey = md5($m3u8Url.'-'.$cdnType.'-'.$cdnUrl.'-'.$fileKeyMaxTime);
        $tokenInfo = $this->getRedis()->get($fileKey);
        if(empty($tokenInfo)){
            $token = CommonUtil::getId();
            $tokenInfo = array('token'=>$token,'time'=>time());
            $this->getRedis()->set($fileKey,json_encode($tokenInfo),$fileKeyMaxTime);
            $this->getRedis()->set($token,json_encode(array('m3u8'=>$m3u8Url,'cdnType'=>$cdnType,'time'=>time())),$tokenMaxTime);
        }else{
            $tokenInfo = json_decode($tokenInfo,true);
            $fileInfoExists = $this->getRedis()->exists($tokenInfo['token']);
            if(!$fileInfoExists){
                $this->getRedis()->set($tokenInfo['token'],json_encode(array('m3u8'=>$m3u8Url,'cdnType'=>$cdnType,'time'=>time())),$tokenMaxTime);
            }
        }
        $module = container()->get('config')->path("modules.$module");
        return $domain.$module.'/m3u8/p/'.$tokenInfo['token'].'.m3u8';
    }

    /**
     * 解码前端
     * @param $token
     * @return mixed
     */
    public function decode($token)
    {
        $fileInfo = $this->getRedis()->get($token);
        if(empty($fileInfo)){
            return null;
        }
        $fileInfo = json_decode($fileInfo,true);
        if(empty($fileInfo['m3u8'])){
            return null;
        }
        if(empty($fileInfo['content'])){
            $result = $this->parseMrsM3u8($fileInfo['m3u8']);
            if(empty($result)){
                return null;
            }
            $dirPath = pathinfo($fileInfo['m3u8'],PATHINFO_DIRNAME);
            foreach ($result['files'] as $file){
                $fullPath = $file;
                if(strpos($file,'/')!==0){
                    $fullPath = $dirPath.'/'.$file;
                }
                $fullPath = $this->commonService->getCdnUrl($fullPath,'video',$fileInfo['cdnType']);
                $result['content'] = str_replace($file,$fullPath,$result['content']);
            }
            $fileInfo['content'] = $result['content'];
            //$this->getRedis()->set($token,json_encode($fileInfo),3600*1);
        }
        return  array('time'=>$fileInfo['time'],'content'=>$fileInfo['content']);
    }

    /**
     * 下载m3u8
     * @param $m3u8Url
     * @param $source
     * @return array|null
     */
    public function doDownload($m3u8Url,$source)
    {
        $result = $this->parseMrsM3u8($m3u8Url,$source);
        if(empty($result)){
            return null;
        }
        $dirPath = pathinfo($m3u8Url,PATHINFO_DIRNAME);
        foreach ($result['files'] as $index=> $file){
            $fullPath = $file;
            if(strpos($file,'/')!==0){
                $fullPath = $dirPath.'/'.$file;
            }
            $fullPath = $this->commonService->getCdnUrl($fullPath,'video');
            $result['content'] = str_replace($file,$fullPath,$result['content']);
            $result['files'][$index] = $fullPath;
        }
        return $result;
    }

    /**
     * 解析m3u8
     * @param $m3u8Url
     * @param $source
     * @return array
     */
    public function parseMrsM3u8($m3u8Url,$source='media')
    {
        if($source=='laosiji'){
            $domain = 'http://'.$_SERVER['HTTP_HOST'];
            $videoCdn = container()->get('config')->mrs->laosiji_tencent_path;
            $m3u8Url = $this->cdnService->getLsjUrl($domain.$videoCdn,$m3u8Url);
            $content = CommonUtil::httpGet($m3u8Url, 40, array());
        }else{
            $cacheKey= md5($m3u8Url);
            $contentFile = RUNTIME_PATH.'/m3u8/'.substr($cacheKey,2,3);
            if(!file_exists($contentFile)){
                mkdir($contentFile,0777,true);
            }
            $contentFile .='/'.$cacheKey.'.m3u8';
            if(!file_exists($contentFile)){
                $configs = getConfigs();
                $mediaUrl = $configs['media_url_cdn']?:$configs['media_url'];
                $content = CommonUtil::httpGet($mediaUrl.$m3u8Url, 40, array());
                if($content&&strpos($content,'#EXTM3U')!==false){
                    file_put_contents($contentFile,$content);
                }
            }else{
                $content = file_get_contents($contentFile);
            }
        }
        if (empty($content)) {
            return null;
        }
        $downloadList = array();
        $split = $this->getSplitChar($content);
        $lines = explode($split, $content);
        foreach ($lines as $line) {
            if (strpos($line, '.ts') > 0) {
                $tsFile = trim($line);
                $downloadList[] = $tsFile;
            } elseif (strpos($line, 'URI=') > -1) {
                $matches = array();
                preg_match('/URI=\"(.*)\"/', $line, $matches);
                $keyFile = "";
                if ($matches[1]) {
                    $keyFile = $matches[1];
                }
                if ($keyFile) {
                    $downloadList[] = trim($keyFile);
                }
            }
        }
        return array(
            'content' => $content,
            'files' => $downloadList
        );
    }

    /**
     * 根据路径算绝对路径
     * @param $url
     * @param $newPath
     * @return mixed
     */
    protected function getFullUrl($url, $newPath)
    {
        if (strpos($newPath, 'http') !== false) {
            return $newPath;
        }
        $urlInfo = parse_url($url);;
        if (substr($newPath, 0, 1) == '/') {
            return $urlInfo['scheme'] . '://' . $urlInfo['host'] . $newPath;
        }
        $pathInfo = pathinfo($urlInfo['path']);
        $dirName = $pathInfo['dirname'];
        return $urlInfo['scheme'] . '://' . $urlInfo['host'] . $dirName . '/' . $newPath;
    }

    /**
     * 获取每行的分割符号
     * @param $content
     * @return string
     */
    protected function getSplitChar($content)
    {
        $split = "\n";
        if (strpos($content, "\r\n") > 0) {
            $split = "\r\n";
        };
        return $split;
    }

    /**
     * 获取一个m3u8中是否包含另外一个m3u8
     * @param $content
     * @return string
     */
    protected function checkHasChildM3u8($content)
    {
        $split = $this->getSplitChar($content);
        $lines = explode($split, $content);
        $maxLine = 5;//一般前面5行就包括了
        foreach ($lines as $index => $line) {
            if ($index > $maxLine) {
                break;
            }
            if (strpos($line, '.m3u8') > 0) {
                return $line;
            }
        }
        return "";
    }
}