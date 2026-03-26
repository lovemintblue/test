<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CommonValues;
use App\Core\Services\BaseService;
use App\Models\AiPromptWordModel;
use App\Utils\CommonUtil;

/**
 * 提示词
 * @package App\Services
 *
 * @property  AiPromptWordModel $aiPromptWordModel
 * @property  ConfigService $configService
 */
class AiPromptWordService extends BaseService
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
        return $this->aiPromptWordModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->aiPromptWordModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->aiPromptWordModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->aiPromptWordModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->aiPromptWordModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->aiPromptWordModel->insert($data);
        }
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->aiPromptWordModel->delete(array('_id' => intval($id)));
    }

    /**
     * 提示词分组
     * @return array
     */
    public function getGroup()
    {
        $promptWordGroup = $this->configService->getConfig('prompt_word_group');
        if(empty($promptWordGroup)){return [];}
        $groups = explode("\n",$promptWordGroup);
        $result = [];
        foreach ($groups as $group)
        {
            $group = explode('===>',$group);
            if(!empty($group[0])){
                $result[] = [
                    'name' => strval(trim($group[0])),
                    'items' => value(function()use($group){
                        $items = [];
                        if(!empty($group[1])){
                            foreach(explode(',',$group[1]) as $item){
                                $items[] = [
                                    'name'=>strval($item),
                                    'items'=>[]
                                ];
                            }
                        }
                        return $items;
                    })
                ];
            }
        }
        return $result;
    }

    /**
     * 获取分组属性
     * @return array
     */
    public function getGroupAttrAll($isHot=null)
    {
        $cacheKey = 'prompt_word_'.$isHot;
        $result = getCache($cacheKey);
        if ($result === null) {
            $query = [];
            if($isHot!==null){
                $query['is_hot'] = 1;
            }
            $items = $this->aiPromptWordModel->find($query, array(), array("sort" => -1), 0, 1000);
            $rows = [];
            foreach ($items as $item) {
                $rows[$item['group']][] = array(
                    'id' => $item['en_name'],
                    'name' => $item['name'],
                );
            }

            $result=$this->getGroup();
            foreach($result as &$groups){
                if(!empty($groups['items'])){
                    foreach($groups['items'] as &$group){
                        $group['items'] = $rows[$group['name']]??[];
                    }
                }else{
                    $groups['items'] = $rows[$groups['name']]??[];
                }
            }
            setCache($cacheKey, $result, 300);
        }

        return $result;
    }

}