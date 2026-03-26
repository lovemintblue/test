<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Services\BaseService;
use App\Models\AiResourceTemplateModel;
use App\Utils\LogUtil;

/**
 * Ai资源模版
 * @package App\Services
 *
 * @property  AiResourceTemplateModel $aiResourceTemplateModel
 */
class AiResourceTemplateService extends BaseService
{

    /**
     * 获取列表
     * @param array $query
     * @param array $fields
     * @param array $sort
     * @param int $skip
     * @param int $limit
     * @return array
     */
    public function getList($query = array(), $fields = array(), $sort = array(), $skip = 0, $limit = 10)
    {
        return $this->aiResourceTemplateModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->aiResourceTemplateModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->aiResourceTemplateModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->aiResourceTemplateModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->aiResourceTemplateModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->aiResourceTemplateModel->insert($data);
        }
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->aiResourceTemplateModel->delete(array('_id' => intval($id)));
    }

    /**
     * 同步模版-从缓存中获取
     * @return void
     */
    public function asyncResourceTemplate($position)
    {
        $success = 0;
        $fail = 0;
        $keyName = 'ai_configs';
        $cacheData = container()->get('redis')->get($keyName);
        $cacheData = !empty($cacheData)?json_decode($cacheData,true):[];

        switch ($position){
            case 'face_video'://视频换脸
                $templates = $cacheData['data']['face_videos']??[];
                break;
            case 'emoji'://表情
                $templates = $cacheData['data']['bq_videos']??[];
                break;
            case 'change'://换装
                $templates = $cacheData['data']['change_method']??[];
                break;
            case 'generate'://绘画
                $templates = $cacheData['data']['generate_models']??[];
                break;
            case 'image_to_video'://图生视频
                $templates = $cacheData['data']['imageToVideo']??[];
                break;
            default:
                break;
        }

        $configs=getConfigs();
        $width = 0;
        $height = 0;
        foreach ($templates as $template) {
            $aid = $template['code']??$template['id'];
            $count = $this->count(['aid' => strval($aid),'position'=>strval($position)]);
            if ($count>0) {continue;}
            if(!empty($template['img'])){//先后台获取一遍图片宽高
                $imageInfo = getimagesize($configs['media_url'].$template['img']);
                $width = $imageInfo[0];
                $height = $imageInfo[1];
            }
            $saveData = array(
                'name' => strval($template['name']??$template['id']),
                'categories' => [],
                'money' => 0,
                'aid' => strval($aid),
                'tips' => strval($template['tips']),
                'img'  => strval($template['img']),
                'video' => strval($template['m3u8_url']),
                'position' => strval($position),
                'width' => intval($width),
                'height' => intval($height),
                'sort' => 0,
                'buy' => 0,
                'is_porn' => $template['is_pron']=='n'?0:1,
                'is_disabled' => 1
            );

            if($this->save($saveData)){
                $success++;
                LogUtil::info('add '.$position.' template success ID:' . $template['id']);
                continue;
            }
            $fail++;
        }
        return ['success'=>$success,'fail'=>$fail];
    }

    /**
     * 事件处理
     * @param $data
     */
    public function handler($data)
    {
        $templateId = intval($data['template_id']);
        if(empty($templateId)){return false;}
        switch ($data['action']) {
            case 'buy':
                $this->aiResourceTemplateModel->updateRaw(array('$inc' => array('buy' => 1)), array('_id' => $templateId));
                break;
        }
    }

}