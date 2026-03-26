<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Services\BaseService;
use App\Models\MovieSpecialModel;

/**
 *  视频专题
 * @package App\Services
 * @property CommonService $commonService
 * @property  MovieSpecialModel $movieSpecialModel
 */
class MovieSpecialService extends BaseService
{
    /**
     * 专题位置
     * @var array
     */
    public $position = [
        'video_wh'      => '视频-网黄',
//        'video_xm'      => '视频-限免',
        'video_yc'      => '视频-原创',
        'video_zt'      => '视频-专题',

        'media_hl'      => '传媒-黑料',
//        'media_xm'      => '传媒-限免',
        'media_zy'      => '传媒-综艺',
        'media_zt'      => '传媒-专题',

//        'animation_time'=> '动漫-时间表',
//        'animation_xm'  => '动漫-限免',
//        'animation_cy'  => '动漫-次元',
//        'animation_zt'  => '动漫-专题',
    ];

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
        return $this->movieSpecialModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->movieSpecialModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->movieSpecialModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->movieSpecialModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->movieSpecialModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->movieSpecialModel->insert($data);
        }
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->movieSpecialModel->delete(array('_id' => intval($id)));
    }

    /**
     * 获取所有专题
     * @param $position
     * @param int $page
     * @param int $pageSize
     * @return array|mixed
     */
    public function get($position,$page=1,$pageSize=8)
    {
        $keyName='movie_special_'.$position.'_'.$page;
        $result = getCache($keyName);
//        $result=null;
        if (is_null($result)) {
            $skip   = ($page - 1) * $pageSize;
            $result = array();
            if($position){$query['position'] = ['$in'=>['all',$position]];}
            $query['is_disabled'] = 0;
            $items = $this->movieSpecialModel->find($query, array(), array('sort' => -1), $skip, $pageSize);
            foreach ($items as $item) {
                $result[] = array(
                    'id'    => strval($item['_id']),
                    'name'  => strval($item['name']),
                    'img'   => strval($this->commonService->getCdnUrl($item['img'])),
                    'bg_img'=> strval($this->commonService->getCdnUrl($item['bg_img'])),
                    'filter'=> strval($item['filter']),
                );
            }
            setCache($keyName, $result, 300);
        }
        return $result;
    }
}