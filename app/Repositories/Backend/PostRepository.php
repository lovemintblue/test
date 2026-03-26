<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\CommonService;
use App\Services\PostService;
use App\Services\UserService;
use App\Services\postCategoryService;

/**
 * 帖子
 * @package App\Repositories\Backend
 *
 * @property  PostService $postService
 * @property  UserService $userService
 * @property  CommonService $commonService
 * @property  postCategoryService $postCategoryService
 */
class PostRepository extends BaseRepository
{
    /**
     * 获取列表
     * @param $request
     * @param $isAi
     * @return array
     */
    public function getList($request,$isAi=false)
    {
        $page       = $this->getRequest($request, 'page', 'int', 1);
        $pageSize   = $this->getRequest($request, 'pageSize', 'int', 15);
        $sort     = $this->getRequest($request, 'sort', 'string', 'created_at');
        $order    = $this->getRequest($request, 'order', 'int', -1);
        $query  = array();
        $filter = array();

        if ($request['categories']) {
            $filter['categories'] = $this->getRequest($request, 'categories','int');
            $query['categories']  = $filter['categories'];
        }
        if ($request['title']) {
            $filter['title'] = $this->getRequest($request, 'title', 'string');
            $query['title']  = array('$regex' => $filter['title'], '$options' => 'i');
        }
        if ($request['id']!='') {
            $filter['id'] = $this->getRequest($request, 'id','string');
            $query['_id']  = $filter['id'];
        }
        if ($request['user_id']!='') {
            $filter['user_id'] = $this->getRequest($request, 'user_id','int', 0);
            $query['user_id']  = $filter['user_id'];
        }
        if (isset($request['status']) && $request['status']!=='') {
            $filter['status'] = $this->getRequest($request, 'status','int', 0);
            $query['status']  = $filter['status'];
        }else{
            if($isAi){
                $query['status'] = 3;
            }else{
                $query['status'] = array('$in'=>array(0,1,2,-1));
            }
        }
        if ($request['pay_type']!=='') {
            $filter['pay_type'] = $this->getRequest($request, 'pay_type','string', '');
            $query['pay_type']  = $filter['pay_type'];
        }
        if ($request['is_hot']!=='') {
            $filter['is_hot'] = $this->getRequest($request, 'is_hot','int', 0);
            $query['is_hot']  = $filter['is_hot'];
        }
        if ($request['is_top']!=='') {
            $filter['is_top'] = $this->getRequest($request, 'is_top','int', 0);
            $query['is_top']  = $filter['is_top'];
        }
        if ($request['start_time']!=='') {
            $filter['start_time'] = $this->getRequest($request, 'start_time','string', '');
            $query['created_at']  = array('$gte'=>intval(strtotime($filter['start_time'])));
        }
        if ($request['end_time']!=='') {
            $filter['end_time'] = $this->getRequest($request, 'end_time','string', '');
            $query['created_at']  = array('$lte'=>intval(strtotime($filter['end_time'])));
        }

        $skip   = ($page - 1) * $pageSize;
        $fields = array();
        $count  = $this->postService->count($query);
        $items  = $this->postService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        $positionArr = CommonValues::getPostPosition();
        $categories = $this->postCategoryService->getAll();
        $types = CommonValues::getPostTypes();
        foreach ($items as $index => $item) {
            $user = $this->userService->findByID($item['user_id']);
            $item['user_id']    = $user['_id'];
            $item['nickname']   = $user['nickname'];
            $item['head']       = $user['img'];
            $item['is_system']  = $user['is_system'];
            $item['user_sex']   = CommonValues::getUserSex($user['sex']);
            $item['group_name'] = $this->userService->isVip($user)?$user['group_name']:'-';
            $item['is_hot']     = CommonValues::getHot($item['is_hot']);
            $item['is_top']     = CommonValues::getTop($item['is_top']);
            $item['pay_type']   = CommonValues::getPayTypes($item['pay_type']);
            $item['status']     = CommonValues::getPostStatus($item['status']);
            $item['last_comment'] = $item['last_comment']?dateFormat($item['last_comment']):'-';
            $item['created_at'] = dateFormat($item['created_at'],"m-d H:i");
            $item['updated_at'] = dateFormat($item['updated_at'],"m-d H:i");
            $item['type_text']       = strval($types[$item['type']]);
            $item['categories'] = value(function()use($item,$categories){
                $names = array();
                foreach ($item['categories'] as $category){
                    $names[]=$categories[$category]['name'];
                }
                return $names?join(',',$names):'-';
            });
            $item['position'] = $positionArr[$item['position']];
            unset($item['content']);
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
            'title'          => $this->getRequest($data, 'title', 'string', ''),
            'user_id'        => $this->getRequest($data, 'user_id', 'int', 0),
            'categories'     => [],
            'color'          => $this->getRequest($data, 'color', 'string', ''),
            'is_hot'         => $this->getRequest($data, 'is_hot','int', 0),
            'is_top'         => $this->getRequest($data, 'is_top','int', 0),
            'position'       => $this->getRequest($data, 'position', 'string', ''),
            'content'        => $this->getRequest($data, 'content'),
            'images'         => [],
            'video_path'        => $this->getRequest($data, 'video_path'),
            'click'          => $this->getRequest($data, 'click', 'int', 0),
            'favorite'       => $this->getRequest($data, 'favorite', 'int', 0),
            'love'           => $this->getRequest($data, 'love', 'int', 0),
            'money'          => $this->getRequest($data, 'money','int', 0),
            'sort'           => $this->getRequest($data, 'sort','int',0),
            'status'         => $this->getRequest($data, 'status','int', 0),
            'deny_msg'       => $this->getRequest($data, 'deny_msg','string', ''),
            'files'          => value(function()use($data){
                $files = $this->getRequest($data, 'files','string', '');
                $fileArr = [];
                if($files){
                    $files = explode("\n", $files);
                    foreach($files as $v){
                        $v = trim($v);
                        if($v){$fileArr[] = trim($v);}
                    }
                }
                return $fileArr;
            }),
            'type' => empty($data['video_path'])?'image':'video'
        );

        $row['pay_type']= CommonValues::getPayTypeByMoney($row['money']);
        if (empty($row['title']) || empty($data['categories'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '请输入标题或板块不能为空!');
        }
        if(!is_numeric($row['user_id'])){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '用户id必须为正整数!');
        }
        if($data['created_at']){
            $row['created_at'] = strtotime($data['created_at']);
        }
        if($data['updated_at']){
            $row['updated_at'] = strtotime($data['updated_at']);
        }
        $user = $this->userService->findByID($row['user_id']);
        if(empty($user)){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '用户不存在!');
        }
        $row['images']  = array();
        foreach ($data['images'] as $image){
            if(!empty($image)){
                $row['images'][]=$image;
            }
        }
        foreach ($data['categories'] as $category){
            $row['categories'][]= intval($category);
        }
        $categories = $this->postCategoryService->getAll();
        if(empty($categories[$row['categories'][0]])){
            throw new BusinessException(StatusCode::DATA_ERROR, '所属帖子板块不存在!');
        }
        $row['position'] = $categories[$row['categories'][0]]['position'];

        if ($data['_id']) {
            $row['_id'] = $this->getRequest($data, '_id');
        }else{
            $row['ip']=getClientIp();
        }
        return $this->postService->save($row);
    }

    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->postService->findByID($id);
        if (empty($row)) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '数据不存在!');
        }
        if($row['files']){
            $row['files'] = implode("\n", $row['files']);
        }
        $row['created_at'] = date('Y-m-d H:i:s',$row['created_at']);
        $row['updated_at'] = date('Y-m-d H:i:s',$row['updated_at']);
        return $row;
    }

    /**
     * 删除
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->postService->delete($id);
    }

    /**
     * 更新数据
     * @param $data
     * @return bool|int|mixed
     */
    public function update($data)
    {
        return $this->postService->save($data);
    }

    /**
     * 同步es
     * @param $id
     * @return mixed
     */
    public function asyncEs($id)
    {
        return $this->postService->asyncEs($id);
    }

    /**
     * 返回模块显示位置
     * @param $key
     * @return array
     */
    public function getPosition($key='')
    {
        $position = CommonValues::getPostPosition();
        return $key?$position[$key]:$position;
    }

    /**
     *  视频转化帖子
     * @param $mid
     * @return array|null
     */
    public function changeFromMovie($mid)
    {
        return $this->postService->changeFromMovie($mid);
    }

    /**
     *  同步帖子
     * @param $mid
     * @return bool
     */
    public function asyncFromMrs($mid)
    {
        return $this->postService->asyncFromMrs($mid);
    }

}