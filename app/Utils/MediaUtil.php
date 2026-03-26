<?php

declare(strict_types=1);

namespace App\Utils;


class MediaUtil
{
    /**
     * @param $url
     * @return string
     */
    private static function urlMd5($url)
    {
        return md5(parse_url($url,PHP_URL_PATH));
    }

    /**
     * 下载视频文件
     * @param $url
     * @param string $referer
     * @return array
     * @throws \Exception
     */
    public static function videoDownload($url,$referer='')
    {
        $keyName       = self::urlMd5($url);
        $videoExt      = CommonUtil::getFileExtName($url)?:'.mp4';
        $videoSaveFile = WEB_PATH.'/media2/source-video/'.substr($keyName,3,3).'/'.$keyName.$videoExt;
        if (!file_exists(dirname($videoSaveFile))) {mkdir(dirname($videoSaveFile), 0777, true);}

        //文件不存在才下载
        if(!file_exists($videoSaveFile)){
            $command = "wget '{$url}'  -O '{$videoSaveFile}'  -t  10  -T  900 --referer={$referer}";
            shell_exec($command);
        }
        if (file_exists($videoSaveFile) && filesize($videoSaveFile) > 1024 * 1024) {
            $mediaInfo = MediaUtil::getVideoInfo($videoSaveFile);
            $mediaInfo['link']=str_replace(WEB_PATH,'',$videoSaveFile);
            return $mediaInfo;
        }
        unlink($videoSaveFile);
        return [];
    }

    /**
     * 将hls合并为mp4
     * @param $url
     * @param array $options 自定义命令数组
     * @param string $bin
     * @return array|bool
     * @throws \Exception
     */
    public static function m3u8ToMp4($url,$options=[],$bin='/usr/local/bin/ffmpeg')
    {
        $keyName       = self::urlMd5($url);
        $mp4TempFile = WEB_PATH .'/media2/runtime/'.$keyName.'-temp.mp4';
        $mp4SaveFile = WEB_PATH .'/media2/source-video/'.substr($keyName,3,3).'/'.$keyName.'.mp4';

        if (!file_exists(dirname($mp4TempFile))) {mkdir(dirname($mp4TempFile), 0777, true);}
        if (!file_exists(dirname($mp4SaveFile))) {mkdir(dirname($mp4SaveFile), 0777, true);}
        if (!file_exists($bin)){
            throw new \Exception("ffmpeg does not exist path:{$bin}");
        }
        if(file_exists($mp4TempFile)&&!file_exists($mp4SaveFile)){
            unlink($mp4TempFile);
        }
        //文件不存在才下载
        if (!file_exists($mp4TempFile)&&!file_exists($mp4SaveFile)){
            $options=array_merge(["-vcodec"=>"copy", "-acodec"=>"copy"],$options);
            $command = "{$bin}  -i '{$url}' ";
            foreach ($options as $key=>$val) {
                $command.=" {$key} {$val}";
            }
            $command .= " -absf aac_adtstoasc  '{$mp4TempFile}'";
            shell_exec($command);

            if (file_exists($mp4TempFile) && filesize($mp4TempFile) > 1024 * 1024) {
                $mp4MvRes=rename($mp4TempFile, $mp4SaveFile);
                if(!$mp4MvRes){
                    LogUtil::error('error:'.$mp4TempFile.'=>'.$mp4SaveFile);
                    return false;
                }
                $mediaInfo = MediaUtil::getVideoInfo($mp4SaveFile);
                $mediaInfo['link']=str_replace(WEB_PATH,'',$mp4SaveFile);
                return $mediaInfo;
            }
            return false;
        }else{
            $mediaInfo = MediaUtil::getVideoInfo($mp4SaveFile);
            $mediaInfo['link']=str_replace(WEB_PATH,'',$mp4SaveFile);
            return $mediaInfo;
        }
    }

    /**
     * mp4转hls
     * @param $mp4Path
     * @param array $options 自定义命令数组
     * @param bool $isPreview 是否预览
     * @param string $bin
     * @return bool|mixed
     * @throws \Exception
     */
    public static function mp4ToM3u8($mp4Path,$options=[],$isPreview=false,$bin='/usr/local/bin/ffmpeg')
    {
        if (!file_exists($bin)){
            throw new \Exception("FFmpeg does not exist path:{$bin}");
        }
        if (!file_exists($mp4Path)){
            throw new \Exception("Mp4 does not exist path:{$mp4Path}");
        }
        $keyName     = self::urlMd5($mp4Path);
        if ($isPreview) {
            $m3u8SaveFile= WEB_PATH .'/media2/m3u8-preview/'.substr($keyName,3,3).'/'.$keyName.'/index.m3u8';
        }else{
            $m3u8SaveFile= WEB_PATH .'/media2/m3u8/'.substr($keyName,3,3).'/'.$keyName.'/index.m3u8';
        }
        $keyPath = WEB_PATH . '/media2/m3u8/enc.keyinfo';
        if (!file_exists($keyPath)){
            throw new \Exception("Key does not exist path:{$keyPath}");
        }
        if (!file_exists(dirname($m3u8SaveFile))) {mkdir(dirname($m3u8SaveFile), 0777, true);}
        $options=array_merge(["-vcodec"=>"copy", "-acodec"=>"copy"],$options);
        $command = "{$bin}  -i '{$mp4Path}' ";
        foreach ($options as $key=>$val) {
            $command.=" {$key} {$val}";
        }
        $command.=" -bsf:v h264_mp4toannexb -hls_list_size 0 -hls_time 10  -hls_key_info_file {$keyPath}  '{$m3u8SaveFile}' ";
        shell_exec($command);
        if (file_exists($m3u8SaveFile)) {
            return str_replace(WEB_PATH, '', $m3u8SaveFile);
        }
        return false;
    }

    /**
     * 下载图片
     * @param string $url
     * @param bool $isOriginal 是否保留原始路径
     * @param string $referer
     * @return mixed|string
     */
    public static function imageDownload(string $url,$isOriginal=false,$referer='')
    {
        $keyName = self::urlMd5($url);
        $imgExt  = CommonUtil::getFileExtName($url)?:'.jpg';
        if($isOriginal){
            $imgSaveFile= WEB_PATH .'/'.(ltrim(parse_url($url,PHP_URL_PATH),'/'));
        }else{
            $imgSaveFile= WEB_PATH .'/media2/images/'.substr($keyName,3,3).'/'.$keyName.'-'.uniqid().$imgExt;
        }
        if (!file_exists(dirname($imgSaveFile))) {mkdir(dirname($imgSaveFile), 0777, true);}
        //文件不存在才下载
        if(!file_exists($imgSaveFile)){
            $command = "wget '{$url}'  -O '{$imgSaveFile}'  -t  10  -T  900 --referer={$referer}";
            shell_exec($command);
        }
        if(file_exists($imgSaveFile) && filesize($imgSaveFile)>1024 ){
            //如果文件大于100KB 则压缩
            $size=FileUtil::getFileSize($imgSaveFile,false);
            if($size>100){
                try{
                    //覆盖源文件 有损压缩 85%(视觉无损)
                    $imageCompress = new CompressUtil($imgSaveFile, $imgSaveFile, 85, 9);
                    $imgSaveFile= $imageCompress->compress_image();
                }catch (\Exception $e){

                }
            }
            FileUtil::encodeFile($imgSaveFile);
            return str_replace(WEB_PATH,'',$imgSaveFile);
        }
        unlink($imgSaveFile);
        return "";
    }

    /**
     * 解析m3u8下载ts 按原始路径保存 可用于媒资库平移
     * @param string $m3u8Url
     * @param string $referer
     * @param string $proxy
     * @return bool
     */
    public static function tsDownload(string $m3u8Url,$referer='',$proxy='')
    {
        $m3u8SaveFile= WEB_PATH .'/'.(ltrim(parse_url($m3u8Url,PHP_URL_PATH),'/'));
        $m3u8Content = CommonUtil::httpGetProxy($m3u8Url, 40, array(), $referer, $proxy);
        if(empty($m3u8Content)){
            return false;
        }
        $downloadList = array();
        $split = self::getSplitChar($m3u8Content);
        $lines = explode($split, $m3u8Content);
        foreach ($lines as $line) {
            if (strpos($line, '.ts') > 0) {
                $tsFile = self::getFullUrl($m3u8Url, $line);
                $downloadList[] = $tsFile;
            }
        }
        $tsSuccess=0;
        foreach ($downloadList as $item) {
            $tsSaveFile=WEB_PATH .'/'.(ltrim(parse_url($item,PHP_URL_PATH),'/'));
            //文件不存在才下载
            if(!file_exists($tsSaveFile)){
                if (!file_exists(dirname($tsSaveFile))) {mkdir(dirname($tsSaveFile), 0777, true);}
                $command = "wget '{$item}'  -O '{$tsSaveFile}'  -t  10  -T  900 --referer={$referer}";
                shell_exec($command);
                if(file_exists($tsSaveFile) && filesize($tsSaveFile)>1024 ){
                    $tsSuccess++;
                }
            }else{
                $tsSuccess++;
            }
        }
        if(count($downloadList)==$tsSuccess){
            if (!file_exists(dirname($m3u8SaveFile))) {mkdir(dirname($m3u8SaveFile), 0777, true);}
            file_put_contents($m3u8SaveFile,$m3u8Content);
            return true;
        }
        return false;
    }

    /**
     * 根据路径算绝对路径
     * @param $url
     * @param $newPath
     * @return mixed
     */
    protected static function getFullUrl($url, $newPath)
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
    protected static function getSplitChar($content)
    {
        $split = "\n";
        if (strpos($content, "\r\n") > 0) {
            $split = "\r\n";
        };
        return $split;
    }

    /**
     * 获取视频信息
     * @param string $file 绝对路径
     * @param string $bin
     * @return array
     * @throws \Exception
     */
    public  static  function getVideoInfo(string $file,$bin='/usr/bin/mediainfo')
    {
        if(!file_exists($file)){
            throw new \Exception("Video does not exist path:{$file}");
        }
        if(!file_exists($bin)){
            throw new \Exception("MediaInfo does not exist path:{$bin}");
        }
        $command = "{$bin} --Output=JSON   '{$file}' ";
        $result = shell_exec($command);
        $result = json_decode($result,true);
        if(!isset($result['media']['track'][1])){
            return [];
        }

        $GeneralInfo = $result['media']['track'][0];
        $videoInfo = $result['media']['track'][1];
        $audioInfo = $result['media']['track'][2];
        return [
            'vcode'=> strtolower($videoInfo['CodecID']),
            'acode'=> strtolower($audioInfo['Format']),
            'size' => round($GeneralInfo['FileSize']/1024/1024,2)."M",
            'width'=> $videoInfo['Width'],
            'height'=> $videoInfo['Height'],
            'video_duration'=>intval($videoInfo['Duration']),
            'audio_duration'=>intval($audioInfo['Duration']),
        ];
    }

    /**
     * 验证视频
     * @param string $file 绝对路径
     * @return bool
     * @throws \Exception
     */
    public static function checkVideo(string $file)
    {
        $videoInfo=self::getVideoInfo($file);
        if(!in_array($videoInfo['vcode'],['avc1','h264'])){
            throw new \Exception("Video encoder is not h264");
        }
        if(!in_array($videoInfo['acode'],['aac'])){
            throw new \Exception("Audio encoder is not aac");
        }
        if ($videoInfo['video_duration']!=$videoInfo['audio_duration']) {
            throw new \Exception('Video and audio are not synchronized');
        }
        //验证文件大小 最大值 预估 每10秒3.5M 每秒0.35M
        $size=rtrim($videoInfo['size'],'M');
        if ($size/$videoInfo['video_duration']>0.35) {
            throw new \Exception('Video and audio are not synchronized');
        }
        return true;
    }


    /**
     * 移动视频
     * @param $mp4Path
     * @return bool
     * @throws \Exception
     */
    public static function mvMp4($mp4Path)
    {
        if (!file_exists($mp4Path)){
            throw new \Exception("Mp4 does not exist path:{$mp4Path}");
        }
        $keyName       = self::urlMd5($mp4Path);
        $mp4SaveFile = WEB_PATH .'/media2/source-video/'.substr($keyName,3,3).'/'.$keyName.'.mp4';
        if (!file_exists(dirname($mp4SaveFile))) {mkdir(dirname($mp4SaveFile), 0777, true);}
        $mp4MvRes=rename($mp4Path, $mp4SaveFile);
        if(!$mp4MvRes){
            LogUtil::error('error:'.$mp4Path.'=>'.$mp4SaveFile);
            return false;
        }
        return $mp4SaveFile;
    }

    /**
     * 移动图片
     * @param $imagePath
     * @return bool
     * @throws \Exception
     */
    public static function mvImage($imagePath)
    {
        if (!file_exists($imagePath)){
            throw new \Exception("Image does not exist path:{$imagePath}");
        }
        $keyName       = self::urlMd5($imagePath);
        $imageSaveFile = WEB_PATH .'/media2/source-image/'.substr($keyName,3,3).'/'.$keyName.'.jpg';
        if (!file_exists(dirname($imageSaveFile))) {mkdir(dirname($imageSaveFile), 0777, true);}
        $mp4MvRes=rename($imagePath, $imageSaveFile);
        if(!$mp4MvRes){
            LogUtil::error('error:'.$imagePath.'=>'.$imageSaveFile);
            return false;
        }
        return $imageSaveFile;
    }
}