<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\PlayService;
use App\Services\UserService;

/**
 * 玩法管理
 * @package App\Repositories\Backend
 *
 * @property  PlayService $playService
 * @property  UserService $userService
 */
class PlayRepository extends BaseRepository
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
        if ($request['_id']) {
            $filter['_id'] = $this->getRequest($request, '_id','int');
            $query['_id']  = $filter['_id'];
        }
        if ($request['title']) {
            $filter['title'] = $this->getRequest($request, 'title');
            $query['title']  = array('$regex' => $filter['title'], '$options' => 'i');
        }
        if ($request['number']) {
            $filter['number'] = $this->getRequest($request, 'number');
            $query['number']  = $filter['number'];
        }
        if ($request['user_id']) {
            $filter['user_id'] = $this->getRequest($request, 'user_id','int',0);
            $query['user_id']  = $filter['user_id'];
        }
        if ($request['tag']) {
            $filter['tag'] = $this->getRequest($request, 'tag');
            $query['tag']  = $filter['tag'];
        }
        if ($request['status']!=='') {
            $filter['status'] = $this->getRequest($request, 'status','int', 0);
            $query['status']  = $filter['status'];
        }else{
            $query['status']  = array('$gt'=>-2);
        }
        if (isset($request['pay_type'])&&$request['pay_type']!=='') {
            $filter['pay_type'] = $this->getRequest($request, 'pay_type');
            $query['pay_type']  = $filter['pay_type'];
        }
        if ($request['city']) {
            $filter['city'] = $this->getRequest($request, 'city','string');
            $query['city']  = $filter['city'];
        }
        if ($request['type']) {
            $filter['type'] = $this->getRequest($request, 'type');
            $query['type']  = $filter['type'];
        }

        $skip   = ($page - 1) * $pageSize;
        $fields = array();
        $count  = $this->playService->count($query);
        $items  = $this->playService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $user = $this->userService->findByID($item['user_id']);
            $item['user_id']    = $user['_id'];
            $item['nickname']   = $user['nickname'];
            $item['head']       = $user['img'];
            $item['is_system']  = $user['is_system'];
            $item['user_sex']   = CommonValues::getUserSex($user['sex']);
            $item['group_name'] = $this->userService->isVip($user)?$user['group_name']:'-';
            $item['images'] = $item['images']?:array($item['img_x']);
            $item['status']     = CommonValues::getPlayStatus($item['status']);
            $item['city']     = $this->playService->cityArr[$item['city']]?:'-';
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);
//            $item['pay_type']   = CommonValues::getPayTypes($item['pay_type']);
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
            'title'       => $this->getRequest($data, 'title', 'string'),
            'user_id'     => $this->getRequest($data, 'user_id', 'int', 0),
            'click'       => $this->getRequest($data, 'click', 'int', 0),
            'love'        => $this->getRequest($data, 'love', 'int', 0),
            'favorite'    => $this->getRequest($data, 'favorite', 'int', 0),
            'img_x'       => $this->getRequest($data, 'img_x', 'string'),
            'type'        => $this->getRequest($data, 'type', 'string'),
            'price'       => $this->getRequest($data, 'price', 'string'),
            'contact'     => $this->getRequest($data, 'contact', 'string'),
            'description' => $this->getRequest($data, 'description', 'string'),
            'video'       => $this->getRequest($data, 'video', 'string'),
            'duration'    => $this->getRequest($data, 'duration', 'string'),
            'images'      => $this->getRequest($data, 'images'),
            'money'       => $this->getRequest($data, 'money','int', 0),
            'sort'        => $this->getRequest($data, 'sort','int',0),
            'status'      => $this->getRequest($data, 'status','int', 0),
            //游戏
            'params'        => $this->getRequest($data, 'params'),
            'download_link' => $this->getRequest($data, 'download_link', 'string'),
            'score'         => $this->getRequest($data, 'score', 'string'),
            //约炮、裸聊
            'number' => $this->getRequest($data, 'number', 'string'),
            'tag'    => $this->getRequest($data, 'tag', 'string'),
            'city'   => $this->getRequest($data, 'city', 'string'),
            'mid'    => $this->getRequest($data, 'mid', 'string',''),
            'deny_msg' => $this->getRequest($data, 'deny_msg', 'string',''),
            'is_top'  => $this->getRequest($data, 'is_top', 'int',0),
        );
        $row['money'] = $row['money']==0?-1:$row['money'];//vip转换为免费
        $row['pay_type'] = CommonValues::getPayTypeByMoney($row['money']);
        $row['images'] = $row['images']?array_values($row['images']):[];
        $row['params'] = $row['params']?explode("\n",$row['params']):[];
        $row['img_x']  = $row['img_x']?:$row['images'][0];//没有封面默认取第一张图集

        if (empty($row['title'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }
        $where=['title'=>$row['title']];
        if($row['type']=='game'){
            if(empty($row['download_link'])){
                throw  new BusinessException(StatusCode::PARAMETER_ERROR, '下载地址不能为空!');
            }
        }else{
//            if(empty($row['number'])||empty($row['tag'])){
//                throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误1!');
//            }
            if(!is_numeric($row['user_id'])){
                throw  new BusinessException(StatusCode::PARAMETER_ERROR, '用户id必须为正整数!');
            }
            $user = $this->userService->findByID($row['user_id']);
            if(empty($user)){
                throw  new BusinessException(StatusCode::PARAMETER_ERROR, '用户不存在!');
            }
            if($row['type']=='yuepao'&&empty($row['city'])){
                throw  new BusinessException(StatusCode::PARAMETER_ERROR, '请选择城市!');
            }
            if(empty($row['images'])){
                throw  new BusinessException(StatusCode::PARAMETER_ERROR, '图集不能为空!');
            }
        }

        if ($data['_id'] > 0) {
            $row['_id'] = $this->getRequest($data, '_id', 'int');
            $where['_id']=['$ne'=>$row['_id']];
        }else{
            $row['comment'] = 0;
            $row['last_comment'] = 0;
            $row['real_click'] = 0;
            $row['real_love'] = 0;
            $row['real_favorite'] = 0;
        }
        $checkRow=$this->playService->count($where);
        unset($where['title']);
//        if($row['number']){
//            $where['number'] = $row['number'];
//            $checkRowNumber=$this->playService->count($where);
//            if ($checkRowNumber) {
//                throw  new BusinessException(StatusCode::PARAMETER_ERROR, '妹子编号不能重复!');
//            }
//        }
        if ($checkRow) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '标题不能重复!');
        }
        return $this->playService->save($row);
    }

    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->playService->findByID($id);
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
        return $this->playService->delete($id);
    }

    /**
     * 同步es
     * @param $id
     * @return mixed
     */
    public function asyncEs($id)
    {
        return $this->playService->asyncEs($id);
    }

    /**
     * 更新数据
     * @param $data
     * @return bool|int|mixed
     */
    public function update($data)
    {
        return $this->playService->save($data);
    }

}