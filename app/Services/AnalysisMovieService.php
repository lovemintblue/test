<?php


namespace App\Services;


use App\Core\Services\BaseService;
use App\Models\AnalysisMovieModel;
use App\Models\MovieModel;

/**
 * 视频统计
 * Class AnalysisMovieService
 * @package App\Services
 * @property AnalysisMovieModel $analysisMovieModel
 * @property MovieModel $movieModel
 */
class AnalysisMovieService extends BaseService
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
        return $this->analysisMovieModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->analysisMovieModel->count($query);
    }

    /**
     * @param $pipeline
     * @return mixed
     */
    public function sum($pipeline)
    {
        return $this->analysisMovieModel->aggregate($pipeline);
    }


    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->analysisMovieModel->findByID($id);
    }

    /**
     * @param $movieId
     * @param $field
     * @param int $value
     * @return mixed
     */
    public function inc($movieId,$field,$value=1)
    {
        $movieId=strval($movieId);
        if(!in_array($field,['click','favorite','buy_num','buy_total'])){return;}
        if(!$this->movieModel->findByID($movieId)){ return;}
        $dateLabel= date('Y-m-d');
        $idValue  = md5($movieId.'_'.$dateLabel);
        $analysis = $this->findByID($idValue);
        if(!$analysis){
            $data=[
                '_id'       => $idValue,
                'date_label'=> $dateLabel,
                'time'      => (int)strtotime($dateLabel),
                'movie_id'  => $movieId,
                'click'     => 0,
                'favorite'  => 0,
                'buy_total' => 0,
            ];
            $data[$field]      = $value;
            return $this->analysisMovieModel->insert($data);
        }else{
            return $this->analysisMovieModel->findAndModify([
                '_id'=>$analysis['_id']
            ],[
                '$inc'=>[$field=>$value]
            ]);
        }
    }
}