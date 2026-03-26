<?php


namespace App\Jobs\Common;


use App\Jobs\BaseJob;
use App\Services\AiService;
use App\Services\ConfigService;
use App\Utils\CommonUtil;

/**
 * Class UploadImageSizeJob
 * @property ConfigService $configService
 * @property AiService $aiService
 * @package App\Jobs\Common
 */
class UploadImageSizeJob extends BaseJob
{
    private $type;
    private $url;
    private $id;

    /**
     * UploadImageSizeJob constructor.
     * @param $type
     * @param $url
     * @param null $id
     */
    public function __construct($type,$url,$id=null)
    {
        $this->type = $type;
        $this->url  = $url;
        $this->id   = $id;
    }

    public function handler($uniqid)
    {
        $mediaUrl = $this->configService->getConfig('media_url');
        $imageInfo = getimagesize($mediaUrl.$this->url);
        if(empty($imageInfo)){
            throw new \Exception("get image size error url:{$this->url}");
        }
        switch ($this->type){
            case 'ai':
                $this->aiService->aiModel->updateRaw(['$set'=>[
                    'extra.width'=>doubleval($imageInfo[0]),
                    'extra.height'=>doubleval($imageInfo[1]),
                ]],['_id'=>strval($this->id)]);
                break;
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