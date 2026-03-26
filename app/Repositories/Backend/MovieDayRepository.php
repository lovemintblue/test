<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\MovieDayService;
use App\Services\MovieService;

/**
 * 分类
 * @package App\Repositories\Backend
 *
 * @property  MovieService $movieService
 * @property  MovieDayService $movieDayService
 */
class MovieDayRepository extends BaseRepository
{
    /**
     * 获取列表
     * @param $request
     * @return array
     */
    public function getList($request)
    {
        $page       = $this->getRequest($request, 'page', 'int', 1);
        $pageSize   = $this->getRequest($request, 'pageSize', 'int', 15);
        $sort     = $this->getRequest($request, 'sort', 'string', '_id');
        $order    = $this->getRequest($request, 'order', 'int', -1);

        $query  = array();
        $filter = array();

        if ($request['movie_id']) {
            $filter['movie_id'] = $this->getRequest($request, 'movie_id','string');
            $query['movie_id']  = $filter['movie_id'];
        }
        if ($request['label']) {
            $filter['label'] = $this->getRequest($request, 'label','string');
            $query['label']  = $filter['label'];
        }

        $skip   = ($page - 1) * $pageSize;
        $fields = array();
        $count  = $this->movieDayService->count($query);
        $items  = $this->movieDayService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $movieModel         = $this->movieService->findByID($item['movie_id']);
            $item['name']       =$movieModel['name']?:'-';
            $item['img_x']      =$movieModel['img_x']?:'';
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);
            $items[$index] = $item;
        }

        return array(
            'filter' => $filter,
            'items' => empty($items) ? array() : array_values($items),
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize
        );
    }

    /**
     * 保存数据(不实现编辑,删除再添加)
     * @param $data
     * @return bool|int|mixed
     * @throws BusinessException
     */
    public function save($data)
    {
        $ids = $this->getRequest($data, 'ids','string');
        $label = $this->getRequest($data, 'label','string');

        if (empty($ids)||empty($label)) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }
        $ids = explode(',',trim($ids));
        foreach ($ids as $id) {
            if(empty($id)){continue;}
//            $id = intval($id);
            if (!$this->movieDayService->count(['movie_id'=>$id,'label'=>$label])) {
                if (!$this->movieService->findByID($id)) {
                    continue;
                }
                $this->movieDayService->save(['movie_id'=>$id,'label'=>$label]);
            }
        }
        return true;
    }


    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->movieDayService->findByID($id);
        if (empty($row)) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '数据不存在!');
        }
        return $row;
    }

    /**
     * 删除
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->movieDayService->delete($id);
    }

    /**
     * 更新数据
     * @param $data
     * @return bool|int|mixed
     */
    public function update($data)
    {
        return $this->movieDayService->save($data);
    }

}