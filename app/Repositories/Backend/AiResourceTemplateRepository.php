<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\AiCategoryService;
use App\Services\AiResourceTemplateService;

/**
 * AI资源模版
 * @package App\Repositories\Backend
 *
 * @property  AiResourceTemplateService $aiResourceTemplateService
 * @property  AiCategoryService $aiCategoryService
 */
class AiResourceTemplateRepository extends BaseRepository
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

        if ($request['name']) {
            $filter['name'] = $this->getRequest($request, 'name');
            $query['name']  = array('$regex' => $filter['name'], '$options' => 'i');
        }

        if ($request['is_hot']!==''&&$request['is_hot']!==null) {
            $filter['is_hot'] = $this->getRequest($request, 'is_hot','int');
            $query['is_hot']  = $filter['is_hot'];
        }
        if ($request['is_porn']!==''&&$request['is_porn']!==null) {
            $filter['is_porn'] = $this->getRequest($request, 'is_porn','int');
            $query['is_porn']  = $filter['is_hot'];
        }
        if ($request['position']) {
            $filter['position'] = $this->getRequest($request, 'position','string');
            $query['position']  = $filter['position'];
        }

        $skip   = ($page - 1) * $pageSize;
        $fields = array();
        $count  = $this->aiResourceTemplateService->count($query);
        $items  = $this->aiResourceTemplateService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        $categories = $this->aiCategoryService->getAll(null,$request['object_type']);
        foreach ($items as $index => $item) {
            $item['created_at']  = dateFormat($item['created_at']);
            $item['updated_at']  = dateFormat($item['updated_at']);
            $item['is_hot']      = CommonValues::getHot($item['is_hot']);
            $item['is_porn']     = CommonValues::getHot($item['is_porn']);
            $item['is_disabled'] = CommonValues::getIsDisabled($item['is_disabled']);
            $item['size']      = "{$item['width']}x{$item['height']}";
            $item['buy']       = intval($item['buy']);
            $item['categories']   = value(function()use($item,$categories){
                $return = [];
                foreach($item['categories'] as $catId){
                    if(!empty($categories[$catId]['name'])){
                        $return[] = $categories[$catId]['name'];
                    }
                }
                return $return?implode("<br>",$return):'-';
            });
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
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     * @throws BusinessException
     */
    public function save($data)
    {
        $row = array(
            'name'        => $this->getRequest($data, 'name','string'),
            'categories'  => value(function ()use($data){
                $catIds = $_REQUEST['categories'];
                $result = [];
                foreach ($catIds as $catId) {
                    if(empty($catId)){continue;}
                    $result[] = intval($catId);
                }
                return $result;
            }),
            'tips'        => $this->getRequest($data, 'tips','string'),
            'img'         => $this->getRequest($data, 'img','string'),
            'position'    => $this->getRequest($data, 'position','string'),
            'money'       => $this->getRequest($data, 'money','int'),
            'sort'        => $this->getRequest($data, 'sort','int'),
            'is_porn'     => $this->getRequest($data, 'is_porn','int'),
            'is_disabled' => $this->getRequest($data, 'is_disabled','int'),
        );
        if (empty($row['name'])||empty($row['img'])||$row['money']<1) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }
        if(!empty($_REQUEST['size'])){
            $sizeArr = explode('x',$_REQUEST['size']);
            $row['width'] = intval($sizeArr[0]);
            $row['height'] = intval($sizeArr[1]);
        }

        if ($data['_id'] > 0) {
            $row['_id'] = $this->getRequest($data, '_id', 'int');
        }else{
            $row['width'] = $row['width']?:9;
            $row['height'] = $row['height']?:16;
            $row['buy'] = 0;
        }

        return $this->aiResourceTemplateService->save($row);
    }

    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->aiResourceTemplateService->findByID($id);
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
        return $this->aiResourceTemplateService->delete($id);
    }

    /**
     * @param $id
     * @return int[]|null
     */
    public function asyncResourceTemplate($position)
    {
        return $this->aiResourceTemplateService->asyncResourceTemplate($position);
    }

}