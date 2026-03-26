<?php


namespace App\Repositories\Backend;


use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\AccountService;
use App\Services\AdminUserService;
use App\Services\UserGroupService;
use App\Services\UserService;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 * Class UserRepository
 * @property UserService $userService
 * @property UserGroupService $userGroupService
 * @property AccountService $accountService
 * @property AdminUserService $adminUserService
 * @package App\Repositories\Backend
 */
class UserRepository extends BaseRepository
{
    /**
     * 获取列表
     * @param $request
     * @return array
     */
    public function getList($request=[])
    {

        $page = $this->getRequest($request, 'page', 'int', 1);
        $pageSize = $this->getRequest($request, 'pageSize', 'int', 30);
        $sort     = $this->getRequest($request, 'sort', 'string', '_id');
        $order    = $this->getRequest($request, 'order', 'int', -1);

        $query = array();
        $filter = array();

        if ($request['user_id']) {
            $filter['user_id'] = $this->getRequest($request, 'user_id', 'int');
            $query['_id'] = $filter['user_id'];
        }

        if (isset($request['is_up']) && $request['is_up'] !== "") {
            $filter['is_up'] = $this->getRequest($request, 'is_up', 'int');
            $query['is_up'] = $filter['is_up'];
        }
        if (isset($request['is_valid']) && $request['is_valid'] !== "") {
            $filter['is_valid'] = $this->getRequest($request, 'is_valid', 'int');
            $query['is_valid'] = $filter['is_valid'];
        }

        if ($request['parent_id']) {
            $filter['parent_id'] = $this->getRequest($request, 'parent_id', 'int');
            $query['parent_id'] = $filter['parent_id'];
        }
        if ($request['username']) {
            $filter['username'] = $this->getRequest($request, 'username');
            $query['username'] = $filter['username'];
        }
        if ($request['phone']) {
            $filter['phone'] = $this->getRequest($request, 'phone');
            $query['phone'] = $filter['phone'];
        }
        if (isset($request['is_disabled']) && $request['is_disabled'] !== "") {
            $filter['is_disabled'] = $this->getRequest($request, 'is_disabled', 'int');
            $query['is_disabled'] = $filter['is_disabled'];
        }
        if ($request['device_type']) {
            $filter['device_type'] = $this->getRequest($request, 'device_type', 'string');
            $query['device_type'] = $filter['device_type'];
        }
        if ($request['group_id']) {
            $filter['group_id'] = $this->getRequest($request, 'group_id', 'int');
            $query['group_id'] = $filter['group_id'];
        }
        if ($request['channel_name']) {
            $filter['channel_name'] = $this->getRequest($request, 'channel_name', 'string');
            $query['channel_name'] = $filter['channel_name']=='-'?'':$filter['channel_name'];
        }
        if ($request['start_time']) {
            $filter['start_time'] = $this->getRequest($request, 'start_time');
            $query['register_at']['$gte'] = strtotime($filter['start_time']);
        }
        if ($request['end_time']) {
            $filter['end_time'] = $this->getRequest($request, 'end_time');
            $query['register_at']['$lte'] = strtotime($filter['end_time']);
        }
        if ($request['register_ip'] && empty($query['register_at'])) {
            $query['register_at']['$gte'] = strtotime('-10 days');
        }

        $skip = ($page - 1) * $pageSize;
        $fields = array();
        $count = $this->userService->count($query);
        $items = $this->userService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $isVip                  = $this->userService->isVip($item);
            $item['updated_at']     = dateFormat($item['updated_at'],'m-d H:i');
            $item['register_at']    = dateFormat($item['register_at'],'m-d H:i:s');
            $item['last_at']        = dateFormat($item['last_at'],'Y-m-d H:i:s');
            $item['group_name']     = $isVip?$item['group_name']:'-';
            $item['group_start_time'] = $isVip&&$item['group_start_time'] ? dateFormat($item['group_start_time'],'Y-m-d H:i') : "-";
            $item['group_end_time'] = $isVip&&$item['group_end_time'] ? dateFormat($item['group_end_time'],'Y-m-d H:i') : "-";
            $item['parent_id']      = $item['parent_id']?:'-';
            $item['is_disabled']    = CommonValues::getIsDisabled($item['is_disabled']);
            $item['level']          = CommonValues::getUserLevel($item['level'] * 1);
            $item['is_up']          = CommonValues::getIsUp($item['is_up'] * 1);
            $item['sex']            = CommonValues::getUserSex($item['sex'] * 1);
            $item['channel_name']   = $item['channel_name']?:'-';
            $item['phone']          =  value(function ()use($item){
                if(strstr($item['phone'],'system_')){
                    return '';
                }
                if(strstr($item['phone'],'device_')){
                    return '';
                }
                if(!is_numeric($item['phone'])){
                    return  $item['phone'];
                }
                return strval(CommonUtil::formatPhone($item['phone']));
            });
            unset($item['city'],$item['country'],$item['country_code'],$item['created_at'],$item['device_id'],$item['sign'],$item['password']);
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
     *
     * @param $userId1
     * @param $userId2
     * @return array
     */
    public function getAccList($userId1,$userId2)
    {
        $query['_id']=['$in'=>[intval($userId1),intval($userId2)]];
        $filter = array();
        $fields = array();
        $count = $this->userService->count($query);
        $items = $this->userService->getList($query, $fields, [], 0, 10);
        foreach ($items as $index => $item) {
            $isVip                  = $this->userService->isVip($item);
            $item['updated_at']     = dateFormat($item['updated_at'],'m-d H:i');
            $item['register_at']    = dateFormat($item['register_at'],'m-d H:i:s');
            $item['last_at']        = dateFormat($item['last_at'],'Y-m-d H:i:s');
            $item['group_name']     =$isVip ? $item['group_name'] : "-";
            $item['group_start_time'] =$isVip ? dateFormat($item['group_start_time'],'Y-m-d H:i') : "-";
            $item['group_end_time'] = $isVip ? dateFormat($item['group_end_time'],'Y-m-d H:i') : "-";
            $items[$index] = $item;
        }

        return array(
            'filter' => $filter,
            'items' => empty($items) ? array() : array_values($items),
            'count' => $count,
            'page' => 1,
            'pageSize' => 10
        );
    }

    /**
     * @param $userId
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($userId)
    {
        $row=$this->userService->findByID($userId);
        if (empty($row)) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '数据不存在!');
        }
        $row['group_end_time']=$row['group_end_time']?date("Y-m-d H:i:s",$row['group_end_time']):'';
        $row['register_at']=date('Y-m-d H:i:s',$row['register_at']);
        return $row;
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        return $this->userService->save($data);
    }

    /**
     * 编辑用户
     * @param $data
     * @return bool|int|mixed
     * @throws BusinessException
     */
    public function update($data)
    {
        $row = [
            '_id'       => $this->getRequest($data,'_id','int'),
            'nickname'  => $this->getRequest($data,'nickname','string'),
            'sign'      => $this->getRequest($data,'sign','string'),
            'img'       => $data['img'],
            'bg_img'    => $this->getRequest($data,'bg_img','string'),
            'right'     => $this->getRequest($data,'right','string',''),//特权
            'tag'       => $this->getRequest($data,'tag','string',''),//标签
            'is_disabled'=>$this->getRequest($data,'is_disabled','int',0),
            'group_id'  => $this->getRequest($data,'group_id','int',0),
            'error_msg' => $this->getRequest($data,'error_msg','string',''),
            'is_up'     =>  $this->getRequest($data,'is_up','int',0),
            'post_fee_rate' => $this->getRequest($data,'post_fee_rate','int',0),
            'movie_fee_rate' => $this->getRequest($data,'movie_fee_rate','int',0)
        ];
        if($row['is_disabled']&&empty($row['error_msg'])){
            throw new BusinessException(StatusCode::DATA_ERROR, '请填写禁用原因!');
        }

        $userInfo = $this->userService->findByID($row['_id']);
        if ($data['group_id']>0) {
            $group              = $this->userGroupService->getInfo($row['group_id']);
            $row['level']       = intval($group['level']);
            $row['group_name']  = strval($group['name']);
            $row['group_rate']  = intval($group['rate']);
            $row['group_end_time'] = $data['group_end_time']?intval(strtotime($data['group_end_time'])):0;
            if(empty($userInfo['group_start_time'])){
                $row['group_start_time'] = time();
            }
        }else{
            $row['group_end_time'] = $data['group_end_time']?intval(strtotime($data['group_end_time'])):0;
            $row['group_name']  = '';
            $row['group_rate']  = 100;
            $row['level']       = 0;
        }
        $result = $this->userService->save($row);
        $this->setInfoToCache($row['_id']);
        return $result;
    }

    /**
     * 设置用户缓存
     * @param $userId
     * @return array|null
     */
    public function setInfoToCache($userId)
    {
        return $this->userService->setInfoToCache($userId);
    }

    /**
     * 找回账号
     * @param $oldUserId
     * @param $newUserId
     * @return bool
     * @throws BusinessException
     */
    public function findAccount($oldUserId, $newUserId)
    {
        $user1 = $this->userService->findByID($oldUserId);
        $user2 = $this->userService->findByID($newUserId);
        if(empty($user1) || empty($user2) || $user1['is_disabled'] || $user2['is_disabled']) {
            throw new BusinessException(StatusCode::DATA_ERROR, '用户不存在或者已被禁用');
        }
        if($user1['device_id'] == $user2['device_id']){
            throw new BusinessException(StatusCode::DATA_ERROR, '当前账号和待找回的账号一样!');
        }

//        $this->userService->getMongo()->startTransaction();
        try {
            $this->userService->doChangeDevice($user1,$user2);
//            $this->userService->getMongo()->commitTransaction();
//            $this->setInfoToCache($oldUserId);
//            $this->setInfoToCache($newUserId);
            return true;

        }catch (\Exception $e) {
            LogUtil::info($e);
        }
//        $this->userService->getMongo()->abortTransaction();
        throw new BusinessException(StatusCode::DATA_ERROR, '找回账号错误!');

    }

    /**
     * 后台充值
     * @param $userId
     * @param $num
     * @param $type
     * @param string $remark
     * @return bool
     * @throws BusinessException
     */
    public function doRecharge($userId,$num,$type,$remark = '')
    {
        $num = intval($num);
        $token = $this->adminUserService->getToken();
        if (empty($token)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '异常操作!');
        }
        $user = $this->userService->findByID($userId);
        if (empty($user)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '用户不存在!');
        }
        if($type=='point'){
            return $this->doPointRecharge($user, $num, $token['user_id'], $token['username'], $remark);
        }
        return false;
    }

    /**
     * 充值金币
     * @param $user
     * @param $num
     * @param $adminId
     * @param $adminName
     * @param string $remark
     * @return bool
     */
    protected function doPointRecharge($user, $num, $adminId, $adminName, $remark = '')
    {
        $remark = empty($remark) ? '管理员操作' : $remark;
        $this->accountService->getMongo()->startTransaction();
        try {
            $orderSn = CommonUtil::createOrderNo('AR');
            if ($num > 0) {
                $this->accountService->addBalance($user, $orderSn, $num, 1, $remark, 'admin_' . $adminId);
            } else {
                $this->accountService->reduceBalance($user, $orderSn, $num * -1, 1, $remark, 'admin_' . $adminId);
            }
            $this->adminUserService->addLog($adminId, $adminName, sprintf('管理员充值:%s %s', $user['username'], $num));
            $this->accountService->getMongo()->commitTransaction();
            return true;
        } catch (\Exception $exception) {
            LogUtil::error($exception);
        }
        $this->accountService->getMongo()->abortTransaction();
        return false;
    }

}
