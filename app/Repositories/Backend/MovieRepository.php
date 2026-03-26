<?php

namespace App\Repositories\Backend;

use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Services\JobService;
use App\Services\MovieCategoryService;
use App\Services\MovieTagService;
use App\Services\UserService;
use App\Utils\HanziConvert;
use App\Utils\CommonUtil;
use App\Services\MovieService;

/**
 * Class MovieRepository
 * @property MovieService $movieService
 * @property MovieCategoryService $movieCategoryService
 * @property MovieTagService $movieTagService
 * @property JobService $jobService
 * @property UserService $userService
 */
class MovieRepository extends BaseRepository
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

        if ($request['_id']) {
            $filter['_id'] = $this->getRequest($request, '_id');
            $query['_id']  = $filter['_id'];
        }
        if ($request['user_id']) {
            $filter['user_id'] = $this->getRequest($request, 'user_id','int');
            $query['user_id']  = $filter['user_id'];
        }
        if ($request['mid']) {
            $filter['mid'] = $this->getRequest($request, 'mid','string');
            $query['mid']  = $filter['mid'];
        }
        if ($request['source']) {
            $filter['source'] = $this->getRequest($request, 'source','string');
            $query['source']  = $filter['source'];
        }
        if ($request['name']) {
            $filter['name'] = $this->getRequest($request, 'name');
            $query['name']  = array('$regex' => $filter['name'], '$options' => 'i');
        }
        if ($request['number']) {
            $filter['number'] = $this->getRequest($request, 'number');
            $query['number']  = $filter['number'];
        }
        if ($request['categories']) {
            $filter['categories'] = $this->getRequest($request, 'categories','int');
            $query['categories']  = $filter['categories'];
        }
        if ($request['position']) {
            $filter['position'] = $this->getRequest($request, 'position','string');
            $query['position']  = $filter['position'];
        }
        if ($request['tags']) {
            $filter['tags'] = $this->getRequest($request, 'tags','int');
            $query['tags']  = ['$in'=>[$filter['tags']]];
        }

        if (isset($request['is_hot']) && $request['is_hot']!=="") {
            $filter['is_hot'] = $this->getRequest($request, 'is_hot','int');
            $query['is_hot']  = $filter['is_hot'];
        }

        if (isset($request['is_new']) && $request['is_new']!=="") {
            $filter['is_new'] = $this->getRequest($request, 'is_new','int');
            $query['is_new']  = $filter['is_new'];
        }
        if (isset($request['status']) && $request['status']!=="") {
            $filter['status'] = $this->getRequest($request, 'status','int');
            $query['status']  = $filter['status'];
        }
        if (isset($request['canvas'])&&$request['canvas']!=='') {
            $filter['canvas'] = $this->getRequest($request, 'canvas');
            $query['canvas']  = $filter['canvas'];
        }
        if (isset($request['img_type'])&&$request['img_type']!=='') {
            $filter['img_type'] = $this->getRequest($request, 'img_type');
            $query['img_type']  = $filter['img_type'];
        }
        if (isset($request['pay_type'])&&$request['pay_type']!=='') {
            $filter['pay_type'] = $this->getRequest($request, 'pay_type');
            $query['pay_type']  = $filter['pay_type'];
        }
        if (isset($request['is_more_link'])&&$request['is_more_link']!=='') {
            $filter['is_more_link'] = $this->getRequest($request, 'is_more_link','int');
            $query['is_more_link']  = $filter['is_more_link'];
        }
        if (isset($request['clickMinSort'])&&$request['clickMinSort']!=='') {
            $filter['clickMinSort'] = $this->getRequest($request, 'clickMinSort','int');
            $query['real_click']['$gte']  = $filter['clickMinSort'];
        }
        if (isset($request['clickMaxSort'])&&$request['clickMaxSort']!=='') {
            $filter['clickMaxSort'] = $this->getRequest($request, 'clickMaxSort','int');
            $query['real_click']['$lte']  = $filter['clickMaxSort'];
        }

        if (isset($request['favMinSort'])&&$request['favMinSort']!=='') {
            $filter['favMinSort'] = $this->getRequest($request, 'favMinSort','int');
            $query['favorite_rate']['$gte']  = $filter['favMinSort'];
        }
        if (isset($request['favMaxSort'])&&$request['favMaxSort']!=='') {
            $filter['favMaxSort'] = $this->getRequest($request, 'favMaxSort','int');
            $query['favorite_rate']['$lte']  = $filter['favMaxSort'];
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
        $count  = $this->movieService->count($query);
        $items  = $this->movieService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['id']         = $item['_id'];
            $item['categories'] = value(function ()use($item){
                $categories = $this->movieService->getCategoriesByIds([$item['categories']]);
                $categories = array_column($categories,'name');
                return $categories?join(',',$categories):'-';
            });
            $item['tags'] = value(function ()use($item){
                $tags = $this->movieService->getTagsByIds($item['tags']);
                $tags = array_column($tags,'name');
                return $tags?join(',',$tags):'-';
            });

            $item['status']     = CommonValues::getMovieStatus($item['status']);
            $item['is_hot']     = CommonValues::getHot($item['is_hot']);
            $item['is_new']     = CommonValues::getNew($item['is_new']);
            $item['canvas']     = CommonValues::getMovieCanvas($item['canvas']);
            $item['img_type']    = CommonValues::getMovieCanvas($item['img_type']);
            $item['duration']= CommonUtil::formatSecond($item['duration']);
            $item['favorite_rate'] = $item['favorite_rate'].'%';
            $item['number']     = $item['number']?:'-';
            $item['show_at']    = $item['show_at']?dateFormat($item['show_at'],"y-m-d H:s"):'-';
            $item['created_at'] = dateFormat($item['created_at'],"y-m-d H:s");
            $item['updated_at'] = dateFormat($item['updated_at'],"y-m-d H:s");
            $item['position']   = CommonValues::getMoviePosition($item['position']);
            $item['is_more_link'] = $item['is_more_link']*1;
            $item['is_more_link_text'] = CommonValues::getMovieLinkType($item['is_more_link']);
            $items[$index] = $item;
        }

        return array(
            'filter'=> $filter,
            'items' => empty($items) ? array() : array_values($items),
            'count' => $count,
            'page'  => $page,
            'pageSize' => $pageSize
        );
    }

    /**
     * 保存
     * @param $data
     * @return bool|int|mixed
     * @throws BusinessException
     */
    public function save($data)
    {
        $row = array(
            '_id'               => $this->getRequest($data, '_id','string'),
            'name'              => HanziConvert::convert($this->getRequest($data, 'name','string')),
//            'name_tw'           => '',
            'number'            => $this->getRequest($data, 'number','string'),
            'is_hot'            => $this->getRequest($data, 'is_hot','int'),
            'is_new'            => $this->getRequest($data, 'is_new','int'),
            'sort'              => $this->getRequest($data, 'sort','int'),
            'user_id'           => $this->getRequest($data, 'user_id','int'),
            'click'             => $this->getRequest($data, 'click','int'),
            'favorite'          => $this->getRequest($data, 'favorite','int'),
            'money'             => $this->getRequest($data, 'money','int'),
            'status'            => $this->getRequest($data, 'status','int'),
            'img_x'             => $this->getRequest($data, 'img_x','string'),
            'img_y'             => $this->getRequest($data, 'img_y','string'),
            'preview_m3u8_url'  => $this->getRequest($data, 'preview_m3u8_url','string'),
            'm3u8_url'          => $this->getRequest($data, 'm3u8_url','string'),
            'duration'          => $this->getRequest($data, 'duration','int'),
            'description'       => $this->getRequest($data, 'description','string'),
            'position'          => $this->getRequest($data, 'position','string'),
            'show_at'           => $this->getRequest($data, 'show_at','string'),
            'score'             => $this->getRequest($data, 'score','int'),
            'categories'        => $this->getRequest($data, 'categories','int'),
            'tags'              => value(function ()use($data){
                $tagIds = $_REQUEST['tags'];
                $result = [];
                foreach ($tagIds as $tagId) {
                    if(empty($tagId)){continue;}
                    $result[] = intval($tagId);
                }
                return $result;
            }),
        );
        $row['show_at'] = $row['show_at']?strtotime($row['show_at']):0;
        $row['pay_type']= CommonValues::getPayTypeByMoney($row['money']);
//        $row['name_tw'] = HanziConvert::convert($row['name'], true);
        if (empty($row['_id'])){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '请选择视频');
        }
        if (empty($row['name'])||empty($row['img_x'])||empty($row['position'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '名称、封面图、分区不能为空!');
        }
        if(empty($row['user_id'])){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '用户编号为空!');
        }
        $userInfo = $this->userService->getInfoFromCache($row['user_id']);
        if(empty($userInfo)){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '用户不存在!');
        }
        $result=$this->movieService->save($row);
        if($result){
            $this->asyncEs($row['_id']?$row['_id']:$result);
            delCache("movie_detail_{$row['_id']}");
        }
        return $result;
    }

    /**
     * 删除
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->movieService->delete($id);
    }

    /**
     * 更新数据
     * @param $data
     * @return bool|int|mixed
     */
    public function update($data)
    {
        return $this->movieService->save($data);
    }

    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->movieService->findByID($id);
        if (empty($row)) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '数据不存在!');
        }
        $row['show_at'] = $row['show_at']?date('Y-m-d H:i:s',$row['show_at']):'';
        return $row;
    }

    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getLinks($id)
    {
        $rows = $this->movieService->getLinks($id);
        if (empty($rows)) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '数据不存在!');
        }
        return $rows;
    }


    /**
     * 同步es
     * @param $id
     * @return mixed
     */
    public function asyncEs($id)
    {
        return $this->movieService->asyncEs($id);
    }

    /**
     * 同步媒资库
     * @param $mid
     * @param $source
     * @return mixed
     */
    public function asyncMrs($mid,$source)
    {
        return $this->movieService->asyncMrs(array('id'=>$mid,'source'=>$source));
    }

    /**
     * 同步媒资库
     * @param $mid
     * @return mixed
     */
    public function asyncCommonMrs($mid)
    {
        return $this->movieService->asyncCommonMrs(array('id'=>$mid));
    }

    public function updateLinks($movieId,$links)
    {
        return $this->movieService->updateLinks($movieId,$links);
    }

}