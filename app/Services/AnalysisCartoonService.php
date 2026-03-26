<?php


namespace App\Services;


use App\Core\Services\BaseService;
use App\Models\AnalysisCartoonModel;
use App\Models\CartoonModel;
use App\Models\ComicsModel;
use App\Utils\LogUtil;

/**
 * 漫画统计
 * Class AnalysisCartoonService
 * @package App\Services
 * @property AnalysisCartoonModel $analysisCartoonModel
 * @property CartoonModel $cartoonModel
 * @property ComicsModel $comicsModel
 */
class AnalysisCartoonService extends BaseService
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
        return $this->analysisCartoonModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->analysisCartoonModel->count($query);
    }

    /**
     * @param $pipeline
     * @return mixed
     */
    public function sum($pipeline)
    {
        return $this->analysisCartoonModel->aggregate($pipeline);
    }


    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->analysisCartoonModel->findByID($id);
    }

    /**
     * @param $cartoonId
     * @param $field
     * @param int $value
     * @return bool|int|mixed|void
     */
    public function inc($cartoonId,$field,$value=1)
    {
        $cartoonId=strval($cartoonId);
        if(!in_array($field,['buy_num','favorite','click'])){return;}
        if(!$this->comicsModel->findByID($cartoonId)){ return;}
        $dateLabel= date('Y-m-d');
        $idValue  = md5($cartoonId.'_'.$dateLabel);
        $analysis = $this->findByID($idValue);
        if(!$analysis){
            $data=[
                '_id'       => $idValue,
                'date_label'=> $dateLabel,
                'time'      => (int)strtotime($dateLabel),
                'cartoon_id'=>$cartoonId,
                'detail'     => 0,
                'favorite'  => 0,
                'read' => 0,
            ];
            $data[$field]      = $value;
            return $this->analysisCartoonModel->insert($data);
        }else{
            return $this->analysisCartoonModel->findAndModify([
                '_id'=>$analysis['_id']
            ],[
                '$inc'=>[$field=>$value]
            ]);
        }
    }
}