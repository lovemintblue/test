<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\NovelService;
use App\Services\NovelTagService;
use App\Utils\AesUtil;
use App\Utils\CommonUtil;

/**
 * 标签
 * @package App\Repositories\Backend
 *
 * @property  NovelTagService $novelTagService
 * @property  NovelService $novelService
 */
class NovelRepository extends BaseRepository
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
        $sort     = $this->getRequest($request, 'sort', 'string', 'created_at');
        $order    = $this->getRequest($request, 'order', 'int', -1);

        $query  = array();
        $filter = array();

        if ($request['name']) {
            $filter['name'] = $this->getRequest($request, 'name');
            $query['$or'] = array(
                ['name' => array('$regex' => $filter['name'], '$options' => 'i')],
                ['alias_name' => array('$regex' => $filter['name'], '$options' => 'i')]
            );
        }
        if ($request['tags']) {
            $filter['tags'] = $this->getRequest($request, 'tags','int');
            $query['tags']  = ['$in'=>[$filter['tags']]];
        }
        if ($request['is_hot']!==''&&$request['is_hot']!==null) {
            $filter['is_hot'] = $this->getRequest($request, 'is_hot','int');
            $query['is_hot']  = $filter['is_hot'];
        }
        if ($request['is_new']!==''&&$request['is_new']!==null) {
            $filter['is_new'] = $this->getRequest($request, 'is_new','int');
            $query['is_new']  = $filter['is_new'];
        }
        if ($request['status']!==''&&$request['status']!==null) {
            $filter['status'] = $this->getRequest($request, 'status','int');
            $query['status']  = $filter['status'];
        }
        if ($request['update_status']!==''&&$request['update_status']!==null) {
            $filter['update_status'] = $this->getRequest($request, 'update_status','int');
            $query['update_status']  = $filter['update_status'];
        }
        if ($request['cat_id']) {
            $filter['cat_id'] = $this->getRequest($request, 'cat_id');
            $query['cat_id']  = $filter['cat_id'];
        }
        if ($request['pay_type']) {
            $filter['pay_type'] = $this->getRequest($request, 'pay_type');
            $query['pay_type']  = $filter['pay_type'];
        }
        if ($request['update_date']) {
            $filter['update_date'] = $this->getRequest($request, 'update_date');
            $query['update_date']  = $filter['update_date'];
        }
        if ($request['_id']) {
            $filter['_id'] = $this->getRequest($request, '_id');
            $query['_id']  = $filter['_id'];
        }
        if (isset($request['minSort'])&&$request['minSort']!=='') {
            $filter['minSort'] = $this->getRequest($request, 'minSort','int');
            $query['sort']['$gte']  = $filter['minSort'];
        }
        if (isset($request['maxSort'])&&$request['maxSort']!=='') {
            $filter['maxSort'] = $this->getRequest($request, 'maxSort','int');
            $query['sort']['$lte']  = $filter['maxSort'];
        }

        $skip   = ($page - 1) * $pageSize;
        $fields = array();
        $count  = $this->novelService->count($query);
        $items  = $this->novelService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        $tagArr = $this->novelTagService->getAll();
        foreach ($items as $index => $item) {
            $item['created_at'] = dateFormat($item['created_at'],'m-d H:i');
            $item['updated_at'] = dateFormat($item['updated_at'],'m-d H:i');
            $item['show_at'] = dateFormat($item['show_at'],'m-d H:i');
            $item['cat_name'] = CommonValues::getNovelCategories($item['cat_id']);
            $item['status']  = CommonValues::getNovelStatus($item['status']*1);
            $item['update_status']  = CommonValues::getNovelUpdateStatus($item['update_status']*1);
            $item['is_hot']     = CommonValues::getHot($item['is_hot']);
            $item['is_new']     = CommonValues::getNew($item['is_new']);
            $tags = array();
            foreach ($item['tags'] as $tagId)
            {
                if($tagArr[$tagId]){
                    $tags[] = $tagArr[$tagId]['name'];
                }
            }
            $item['tags'] = join(',',$tags);
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
            'name'      => $this->getRequest($data, 'name'),
            'cat_id'    => $this->getRequest($data, 'cat_id','string'),
            'alias_name'    => $this->getRequest($data, 'alias_name','string'),
            'img' => $this->getRequest($data, 'img','string'),
            'img_x' => $this->getRequest($data, 'img_x','string'),
            'update_date' => $this->getRequest($data, 'update_date','string'),
            'last_update' => $this->getRequest($data, 'last_update','string'),
            'click' => $this->getRequest($data, 'click','int',0),
            'favorite' => $this->getRequest($data, 'favorite','int',0),
            'description' => $this->getRequest($data, 'description','string',''),
            'score' => $this->getRequest($data, 'score','int',80),
            'show_at' => $this->getRequest($data, 'show_at','string'),
            'free_chapter' => $this->getRequest($data, 'free_chapter','string'),
            'sort' => $this->getRequest($data, 'sort','int',0),
            'is_hot' => $this->getRequest($data, 'is_hot','int',0),
            'is_new' => $this->getRequest($data, 'is_new','int',0),
            'status' => $this->getRequest($data, 'status','int',0),
            'money'    => $this->getRequest($data, 'money','int',0),
        );
        $row['pay_type'] = CommonValues::getPayTypeByMoney($row['money']);

        if (empty($row['name'])||empty($row['img'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }
        $row['name'] = stripslashes($row['name']);
        
        if ($data['_id']) {
            $row['_id'] = $this->getRequest($data, '_id', 'string');
        }
        $row['description'] = stripslashes($row['description']);
        if($row['show_at']){
            $row['show_at'] = strtotime($row['show_at']);
        }
        if($row['last_update']){
            $row['last_update'] = strtotime($row['last_update']);
        }

        $tags = [];
        foreach ($data['tags'] as $tag)
        {
            $tags[] = intval($tag);
        }
        if(empty($tags)){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '请设置标签!');
        }
        $row['tags'] = $tags;

        $freeIds=[];
        foreach ($data['free_ids'] as $freeId)
        {
            $freeIds[] = $freeId;
        }
        $row['free_chapter'] = join(',',$freeIds);

        $result= $this->novelService->save($row);
        if($row['_id']){
            $this->novelService->asyncEs($row['_id']);
        }
        return $result;
    }

    /**
     * 同步es
     * @param $id
     * @return bool
     */
    public function asyncEs($id)
    {
        $this->novelService->asyncEs($id);
        return true;
    }

    /**
     * 同步mrs
     * @param $id
     * @return bool
     */
    public function asyncMrs($id)
    {
        $this->novelService->asyncMrsById($id);
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
        $row = $this->novelService->findByID($id);
        if (empty($row)) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '数据不存在!');
        }
        $row['show_at'] = dateFormat($row['show_at']);
        $row['last_update'] = dateFormat($row['last_update']);
        return $row;
    }

    /**
     * 删除
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->novelService->delete($id);
    }

    /**
     * 获取所有分类
     * @return array
     */
    public function getGroupAttrAll()
    {
        return $this->novelTagService->getGroupAttrAll();
    }

    /**
     * 获取章节列表
     * @param $id
     * @return array
     */
    public function getChapterList($id)
    {
        return $this->novelService->getChapterList($id);
    }

    /**
     * 获取章节
     * @param $id
     * @return array
     */
    public function getChapterDetail($id)
    {
        $result= $this->novelService->getChapterDetail($id);
        if(empty($result)){
            return null;
        }
        $result['content_text']='';
        if($result['is_audio']!=1 && !empty($result['content'])){
            $configs = getConfigs();
            $content = $configs['media_url'].$result['content'];
            $content = CommonUtil::httpGet($content);
            $result['content_text'] = AesUtil::decryptRaw($content,$configs['media_encode_key']);
            $result['content_text']  = strval($result['content_text'] );
        }
        return $result;
    }
}