<?php


namespace App\Services;


use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Jobs\Common\UserShareJob;
use App\Models\AreaReportModel;
use App\Models\OldTokenModel;
use App\Models\UserFindLogModel;
use App\Models\UserModel;
use App\Utils\AesUtil;
use App\Utils\CommonUtil;
use App\Utils\GameNameUtil;
use App\Utils\LogUtil;
use App\Utils\UserSign;

/**
 * Class UserService
 * @property UserModel $userModel
 * @property AreaReportModel $areaReportModel
 * @property UserFindLogModel $userFindLogModel
 * @property ApiService $apiService
 * @property H5Service $h5Service
 * @property TokenService $tokenService
 * @property IpService $ipService
 * @property QueueService $queueService
 * @property ChannelService $channelService
 * @property UserGroupService $userGroupService
 * @property ProductService $productService
 * @property AccountService $accountService
 * @property CommonService $commonService
 * @property JobService $jobService
 * @property UserMessageService $userMessageService
 * @property CreditLogService $creditLogService
 * @property ConfigService $configService
 * @property DomainService $domainService
 * @property  UserUpService $userUpService
 * @property ChannelAppService $channelAppService
 * @property AgentSystemService $agentSystemService
 * @property OldTokenModel $oldTokenModel
 * @package App\Services
 */
class UserService extends BaseService
{

    const  ENCODE_STR = 'SCUWDG3HE859QA4B1NOPIV67XLYFJ2RZTKM';
    const  AES_KEY = '625202f9149e0613';

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
        return $this->userModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->userModel->count($query);
    }

    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->userModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @param  $fields
     * @return mixed
     */
    public function findByID($id, $fields = array())
    {
        return $this->userModel->findByID(intval($id), '_id', $fields);
    }

    /**
     * @param $deviceId
     * @param array $fields
     * @return array
     */
    public function findByDeviceId($deviceId, $fields = array())
    {
        return $this->userModel->findFirst(['device_id'=>strval($deviceId)], $fields);
    }

    /**
     * 通过手机号查询
     * @param  $phone
     * @param  $fields
     * @return mixed
     */
    public function findByPhone($phone, $fields = array())
    {
        return $this->userModel->findFirst(['phone'=>strval($phone)], $fields);
    }

    /**
     * 查找并更新数据
     * @param array $query
     * @param array $update
     * @param array $fields
     * @param bool $upsert
     * @return mixed
     */
    public function findAndModify($query = array(), $update = array(), $fields = array(), $upsert = false)
    {
        return $this->userModel->findAndModify($query, $update, $fields, $upsert);
    }

    /**
     * 修改数据(可以使用操作符)
     * @param  $document
     * @param  $where
     * @return mixed
     * @throws
     */
    public function updateRaw($document = array(), $where = array())
    {
        return $this->userModel->updateRaw($document, $where);
    }
    /**
     * 保存数据
     * @param $data
     * @param string $username
     * @return bool|int|mixed
     */
    public function save($data, &$username = null, $multi=true)
    {
        if ($data['_id']) {
            return $this->userModel->update($data, array("_id" => intval($data['_id'])), $multi);
        } else {
            $data['_id'] = $this->userModel->getInsertId($this->userModel->getCollectionName());
            $data['username'] = $this->encodeUserId($data['_id']);
            $username = $data['username'];
            $result = $this->userModel->insert($data);
            if (empty($result)) {
                return null;
            }
            return $data['_id'];
        }
    }

    /**
     * @param $phone
     * @param string $channelName
     * @param string $parentCode
     * @return array
     * @throws BusinessException
     */
    public function loginByUserPhone($phone,$channelName='',$parentCode='')
    {
        $phone = strval($phone);
        if (empty($phone) || !CommonUtil::isPhoneNumber($phone)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '手机号码格式错误!');
        }
        $deviceId="web_{$phone}";
        $userRow = $this->findByPhone($phone);
        $update=[
            '_id'       => null,
            'last_at'   => time(),
            'last_date' => date("Y-m-d"),
            'last_ip'   => getClientIp(),
            'login_num' => $userRow['login_num'] + 1,
            'device_type' => $this->h5Service->getDeviceType(),
            'device_version' => $this->h5Service->getVersion(),
        ];
        if(empty($userRow)){
            //创建用户
            $userRow = $this->register($deviceId,$this->h5Service->getDeviceType(),$this->h5Service->getVersion(), $channelName,$phone,0);
            $update['_id']=$userRow['_id'];
        }else{
            $update['_id']      = $userRow['_id'];
//            $this->tokenService->deleteByUserId($userRow['_id']);
        }
        if(empty($userRow)){
            throw new BusinessException(StatusCode::DATA_ERROR, '生成用户繁忙,请稍后再试!');
        }
        $this->checkUser($userRow);
        if(!empty($parentCode)){
            $this->doBindParent($userRow['_id'],$parentCode);
        }
        $token = $this->tokenService->set($userRow['_id'], $userRow['username'], 3600*24*7, 'user');
        if ($this->save($update)==false) {
            throw new BusinessException(StatusCode::DATA_ERROR, '服务器内部错误,请联系管理员!');
        }
        $this->setInfoToCache($userRow);
        unset($token['ext']);
        return $token;
    }


    /**
     * @param $deviceId
     * @param $extData
     * @return bool
     */
    public function  verifyDevice($deviceId,$extData)
    {
        $oldDeviceId = $deviceId;
        $deviceType = $this->apiService->getDeviceType();
        $version =  $this->apiService->getVersion();
        if($version<3){
            return true;
        }
        if($deviceType=='h5' && strlen($deviceId)>32){
            $deviceIdInfo = AesUtil::decrypt($deviceId);
            $deviceIdInfo = json_decode($deviceIdInfo,true);
            if(empty($deviceIdInfo)){
                return false;
            }
            $deviceId = $deviceIdInfo['d'];
        }
        if(in_array($deviceType,['android','ios'])){
            $channelName = '';
            if ($extData['channel_code']) {
                $channelName = CommonUtil::getChannel($extData['channel_code']);
            } elseif ($extData['clipboard_text'] && strpos($extData['clipboard_text'], 'channel://') !== false) {
                $channelName = CommonUtil::getChannel($extData['clipboard_text']);
            }
            if($channelName&&$this->channelAppService->count(['code'=>$channelName,'is_need_verify'=>1])){
                return false;
            }
        }
        //老用户不校验
        $countUser = $this->userModel->count(array('device_id' => $deviceId));
        if($countUser>0){
            return true;
        }
        if($deviceType=='h5' && strlen($oldDeviceId)<=32){
            return false;
        }

        $keyName = 'user_reg_'.getClientIp();
        if(!$this->commonService->checkActionLimit($keyName,600,1)){
            return false;
        }
        return true;
    }

    /**
     * 根据用户设备生成
     * @param string $deviceId
     * @param array $extData
     * @@param array $userData
     * @return array
     * @throws BusinessException
     */
    public function loginByUserDevice($deviceId, $extData = array(), &$userData = array())
    {
        $userData = array(
            'is_new_user' => false,
            'channel_name' => '',
            'group_id' => 0,
            'group_end_time' => 0,
            'username' => ''
        );
        $deviceType = $this->apiService->getDeviceType();
        if($deviceType=='h5' && strlen($deviceId)>32){
            $deviceIdInfo = AesUtil::decrypt($deviceId);
            $deviceIdInfo = json_decode($deviceIdInfo,true);
            if(empty($deviceIdInfo) || empty($deviceIdInfo['d'])){
                throw  new BusinessException(StatusCode::DATA_ERROR, "请求不合法,请从官网下载最新!");
            }
            $deviceId = $deviceIdInfo['d'];
            $extData['channel_code']=empty($deviceIdInfo['c'])?'':'channel://'.$deviceIdInfo['c'];
            $extData['clipboard_text'] =empty($deviceIdInfo['s'])?'':'share://'.$deviceIdInfo['s'];
        }
        $userRow = $this->findFirst(array('device_id' => $deviceId), array());
        if(empty($userRow)){
            if(!empty($extData['h5_channel_code'])){
                $extData['channel_code'] = $extData['h5_channel_code'];
            }
            if ($extData['channel_code']) {
                $userData['channel_name'] = CommonUtil::getChannel($extData['channel_code']);
            }
            if ($extData['clipboard_text'] && strpos($extData['clipboard_text'], 'channel://') !== false) {
                $userData['channel_name'] = CommonUtil::getChannel($extData['clipboard_text']);
            }

            if ($extData['clipboard_text'] && strpos($extData['clipboard_text'], 'share://') !== false) {
                $extData['share_code'] = CommonUtil::getParent($extData['clipboard_text']);
            }
            //兼容老系统
            if($userData['channel_name']){
                $userData['channel_name']= str_replace('agent://','',$userData['channel_name']);
            }
            $userRow=$this->register($deviceId,$deviceType,$this->apiService->getVersion(),$userData['channel_name'],uniqid('device_'),0,$extData['share_code']);
            $userData['is_new_user'] = true;
            
        } elseif ($userRow['is_disabled']) {
            $serviceEmail=$this->commonService->getConfig('service_email');
            //会导致被禁用用户无法进入
            throw  new BusinessException(StatusCode::DATA_ERROR, "该用户账号已被系统禁用,请联系管理员解除!".$serviceEmail);
        }
        //下线h5端
        $this->tokenService->deleteByUserId($userRow['_id']);

        $userData['username'] = $userRow['username'];
        $userData['group_id'] = $userRow['group_id'];
        $userData['group_end_time'] = $userRow['group_end_time'];
        $token = $this->tokenService->set($userRow['_id'], $userRow['username'], 8 * 3600, 'user');
        $this->save(array(
            "_id" => $userRow['_id'],
            'last_at' => time(),
            'last_date' => date('Y-m-d'),
            'last_ip' => getClientIp(),
            'login_num' => $userRow['login_num'] + 1,
            'device_version' => $this->apiService->getVersion(),
        ),$extData, false);
        unset($token['ext']);
        $token['is_valid'] = 'n';
        $this->setInfoToCache($userRow);

        DataCenterService::setUserId($userRow['_id']??'');
        DataCenterService::setChannelCode($userRow['channel_name']??'');
        DataCenterService::setDeviceId($userRow['device_id']);
        if($userData['is_new_user']){
            DataCenterService::doRegister('deviceid', DataCenterService::uuidV4(),$userRow['register_at']);
        }
        DataCenterService::doLogin('deviceid');
        return $token;
    }

    /**
     * 定义用户初始化信息
     * @return array
     */
    public function getDefaultUserRow()
    {
        $configs = getConfigs();
        $nowTime = time();
        $ip=getClientIp();
        return array(
            'username' => '',
            'nickname' => GameNameUtil::getNickname(),
            'sign'=>UserSign::getSign(),
            'phone' => '',
            'country_code' => '',
            'device_id' => '',
            'device_type' => '',
            'device_ext' => '',
            'device_version' => '',
            'password' => '',
            'slat' => strval(mt_rand(1000, 9000)),
            'balance' => 0,
            'income'  => 0,
            'credit' => 0,//积分
            'movie_fee_rate'=>0,//视频分成比例
            'post_fee_rate' => 0,//帖子分成比例
            'is_disabled' => 0,
            'is_up' => 0,
            'error_msg' => '',
            'img' => sprintf('%s/common_file/headico/ico121/%s.jpg',$configs['media_dir'],mt_rand(1, 50)),
            'group_id' => 0,
            'group_rate' => 100,
            'group_name' => '',
            'group_start_time' => 0,
            'group_end_time' => 0,
            'level' => 0,
            'sex' => 0,
            'parent_name' => '',
            'parent_id' => 0,
            'channel_name' => '',
            'register_at' => $nowTime,
            'register_date' => date("Y-m-d"),
            'register_ip' => $ip,
            'login_num' => 0,
            'last_at' => $nowTime,
            'last_date' => date("Y-m-d"),
            'last_ip' => $ip,
            'share_num' => 0,
            'fans' => 0,
            'follow' => 0,
            'gift_count' => 0,
            'money_count' => 0,
            'send_count' => 0,
            'location' => '',
            'register_area'=>'',
            'is_china'=>1,
            'province' => '',
            'city' => '',
            'country' => '',
            'withdraw_info'=>[],
            'tag'=>[],
            'is_system' => 0,
            'integral'=>0,
            'has_buy' => 0,//vip是否购买的
            'is_valid' => 0,//是否有效用户
            'right' => [],//特权
        );
    }

    /**
     * 注册用户
     * @param $deviceId
     * @param $deviceType
     * @param $deviceVersion
     * @param string $channelName
     * @param string $phone
     * @param int $isSystem
     * @param $shareCode
     * @return array
     * @throws BusinessException
     */
    public function register($deviceId,$deviceType,$deviceVersion,$channelName='',$phone='',$isSystem=0,$shareCode='')
    {
        $userRow=$this->getDefaultUserRow();
        $userRow['device_id']=$deviceId;
        $userRow['device_type']=$deviceType;
        $userRow['device_version']=$deviceVersion;
        $userRow['channel_name']=$this->getChannelName($channelName,$userRow['register_ip'],$deviceType);
        $userRow['is_system']=$isSystem;
        $userRow['phone']   =$phone;
        $username='';

        $userId=$this->save($userRow,$username);
        if (empty($userId)) {
            throw  new BusinessException(StatusCode::DATA_ERROR, "生成用户繁忙,请稍后再试!");
        }
        $userRow['username']=$username;
        $userRow['_id']=$userId;
        $this->userRegHandler([
            'user_id' => $userId,
            'register_ip' => $userRow['register_ip'],
            'channel_name' => $userRow['channel_name'],
            'share_code' => $shareCode
        ]);
        return $userRow;
    }

    /**
     * 根据ip获取渠道信息--暂时先同步进行
     * @param $channelName
     * @param $ip
     * @return string
     */
    public function getChannelName($channelName,$ip,$deviceType)
    {
        if(empty($channelName)){
            try {
                $result = $this->agentSystemService->getCodeByIp(['ip'=>$ip]);
//                LogUtil::info("ip:".$ip."---".json_encode($result));
            } catch (\Exception $exception){

            }
            $channelName = $result['code'];
        }
        return strval($channelName);
    }

    /**
     * 新用户事件处理
     * @param $data
     * @throws BusinessException
     */
    public function userRegHandler($data)
    {
        if ($data['channel_name']) {
            $this->channelService->bindChannel($data['channel_name']);
        }
        if($data['share_code']){
            try{
                $this->doBindParent($data['user_id'],$data['share_code']);
            }catch (\Exception $exception){

            }
        }
        if (filter_var($data['register_ip'], \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4)) {
            $ipInfo = $this->ipService->parse($data['register_ip']);
            if (empty($ipInfo)) {
                return;
            }
            $area = $this->ipService->getProvinceAndCity($ipInfo);
            $this->userModel->updateRaw(
                array('$set'=>array(
                    'register_area' => $area,
                    'province'      => $ipInfo['province'],
                    'city'          => $ipInfo['city'],
                    'country'       => $ipInfo['country']?:'',
                    'is_china'      => intval($this->ipService->isChina($ipInfo)),
                    'updated_at    '=>time()
                )),
                array('_id' => $data['user_id'])
            );
            $this->areaReportModel->findAndModify(
                array('_id' => md5($area)),
                array('$set' => array('area' => $area, 'updated_at' => time()), '$inc' => array('num' => 1)),
                array(),
                true
            );
        }
    }

    /**
     * 通过userId 生成邀请码
     * @param $userId
     * @return string
     */
    public function encodeUserId($userId)
    {
        $sLength = strlen(self::ENCODE_STR);
        $num = $userId;
        $code = '';
        while ($num > 0) {
            $mod = $num % $sLength;
            $num = ($num - $mod) / $sLength;
            $code = self::ENCODE_STR[$mod] . $code;
        }
        if (empty($code[3])) {
            $code = str_pad($code, 4, '0', STR_PAD_LEFT);
        }
        return $code;
    }

    /**
     * 通过邀请码计算用户id
     * @param $code
     * @return float|int
     */
    public function decodeUserId($code)
    {
        $sLength = strlen(self::ENCODE_STR);
        if (strrpos($code, '0') !== false) {
            $code = substr($code, strrpos($code, '0') + 1);
        }
        $len = strlen($code);
        $code = strrev($code);
        $num = 0;
        for ($i = 0; $i < $len; $i++) {
            $num += strpos(self::ENCODE_STR, $code[$i]) * pow($sLength, $i);
        }
        return $num;
    }


    /**
     * 从缓存中取出用户信息
     * @param $userId
     * @return iterable|void
     */
    public function getInfoFromCache($userId)
    {
        $keyName = 'user_info_' . $userId;
        $result = getCache($keyName);
        if (empty($result)) {
            $result = $this->setInfoToCache($userId);
        }
        return $result;
    }

    /**
     * 设置用户信息到缓存
     * @param $user
     * @return array|null
     */
    public function setInfoToCache($user)
    {
        if ($user < 0 ) {
            return $this->setSystemUserToCache($user);
        }
        if (is_numeric($user)) {
            $user = $this->findByID($user);
        }
        if (empty($user)) {
            return null;
        }
        $isVip=$this->isVip($user);
        $result = array(
            'id'            => $user['_id'],
            'username'      => $user['username'],
            'nickname'      => $user['nickname'],
            'sign'          => $user['sign'],
            'img'           => $user['img'],
            'is_vip'        => $isVip?'y' : 'n',
            'is_up'         => $user['is_up']?'y':'n',
            'is_valid'      => $user['is_valid']?'y':'n',
            'level'         => $isVip?$user['level'] * 1:0,
            'group_id'      => $isVip?$user['group_id'] * 1:0,
            'group_rate'    => $isVip?$user['group_rate'] * 1:100,
            'group_name'    => $isVip?strval($user['group_name']):'',
            'group_start_time'=> $isVip?$user['group_start_time'] * 1:0,
            'group_end_time'=> $isVip?$user['group_end_time'] * 1:0,
            'sex'           => $user['sex'] * 1,
            'register_at'   => $user['register_at'] * 1,
            'register_ip'   => strval($user['register_ip']),
            'last_ip'   => strval($user['last_ip']),
            'fans'          => $user['fans'] * 1,
            'follow'        => $user['follow'] * 1,
            'gift_count'    => $user['gift_count'] * 1,
            'send_count'    => $user['send_count'] * 1,
            'county'        => empty($user['county']) ? '火星喵' : $user['county'],
            'province'      => empty($user['province']) ? '火星喵' : $user['province'],
            'city'          => empty($user['city']) ? '火星喵' : $user['city'],
            'location'      => empty($user['location']) ? '火星喵' : $user['location'],
            'is_disabled'   => $user['is_disabled'] * 1,
            'balance'       => $user['balance'] * 1,
            'income'        => $user['income'] *1,
            'integral'      => $user['integral']*1,
            'movie_fee_rate' => $user['movie_fee_rate'] *1,
            'post_fee_rate' => $user['post_fee_rate'] *1,
            'device_type'   => $user['device_type'],
            'device_id'     => $user['device_id'],
            'share_num'     => $user['share_num'] * 1,
            'parent_id'     => $user['parent_id'] * 1,
            'channel_name'  => strval($user['channel_name']),
            'parent_name'   => strval($user['parent_name']),
            'phone'         => strval($user['phone'])
        );
        foreach ($result as $key => $value) {
            $result[$key] = strval($value);
        }
        $result['right']=is_string($user['right'])?[$user['right']]:$user['right'];
        $result['sign_days'] =empty($user['sign_days'])?[]:$user['sign_days'];
        $result['group_end_time'] *= 1;
        $result['group_start_time'] *= 1;
        $keyName = 'user_info_' . $user['_id'];
        setCache($keyName, $result, 120);
        return $result;
    }

    /**
     * 设置系统用户信息
     * @param $userId
     * @return array
     */
    public function setSystemUserToCache($userId)
    {
        if(!in_array($userId,[-1,-2])){
            return [];
        }
        $systemHeadImage = $this->commonService->getConfig('system_user_headico');
        $result = array(
            'id'        => $userId,
            'username'  => 'system_user',
            'nickname'  => value(function ()use($userId){
                if($userId==-2){
                    return 'VIP专属客服';
                }
                return '官方客服';
            }),
            'img'       => $systemHeadImage,
            'level'     => '10',
            'group_id' => '7',
            'group_name'=> '',
            'group_start_time' => '',
            'group_end_time' => '',
            'sex'       => 1,
            'register_at'=> '',
            'fans'      => '100',
            'follow'    => '100',
            'gift_count'=> '0',
            'send_count'=> '0',
            'county'    => '火星喵',
            'province'  => '火星喵',
            'city'      => '火星喵',
            'location'  => '火星喵',
            'address'   => '火星喵',
            'is_disabled' => '0',
            'balance'   => '0',
            'device_type' => 'ios',
            'share_num' => '0',
            'parent_id' => '0',
            'channel_name' => '',
            'parent_name' => '',
            'is_vip'    => 'y',
            'phone'     =>'',
        );
        $keyName = 'user_info_' . $userId;
        setCache($keyName, $result, 180);
        return $result;
    }

    /**
     * 判断是否vip
     * @param $user
     * @return bool
     */
    public function isVip($user)
    {
        if (empty($user)) {
            return false;
        }
        if (empty($user['group_id']) || $user['group_end_time'] < time()) {
            return false;
        }
        return true;
    }

    /**
     * 禁用用户
     * @param $userId
     * @param $error
     * @return mixed
     */
    public function doDisabled($userId,$error='')
    {
        $userId=intval($userId);
        $result=$this->updateRaw(['$set'=>['is_disabled'=>1,'error_msg'=>$error]],['_id'=>$userId]);
        if($result){
            $this->setInfoToCache($userId);
        }
        return $result;
    }

    /**
     *  获取用户组
     * @param $groupId
     * @return mixed|null
     */
    public function getGroupInfo($groupId)
    {
        return $this->userGroupService->getInfo($groupId);
    }

    /**
     * 修改用户组
     * @param $user
     * @param $dayNum
     * @param $groupId
     * @param $isShare
     * @return bool
     * @throws BusinessException
     */
    public function doChangeGroup($user, $dayNum, $groupId,$isShare=false)
    {
        $groupId = intval($groupId);
        if (is_numeric($user)) {
            $user = $this->findByID($user);
        }
        if (empty($user)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '用户不存在!');
        }
        $groupInfo = $this->userGroupService->getInfo($groupId);
        if (empty($groupInfo)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '用户套餐不存在!');
        }
        $updated = array(
            'updated_at' => time(),
            'group_rate' =>$user['group_rate']?:100
        );

        if(empty($user['has_buy']) && !$isShare){
            $updated['has_buy']=1;
        }
        if (empty($user['group_id'])) {
            $updated['group_id'] = $groupId;
            $updated['level']  = intval($groupInfo['level']);
            $updated['group_name']= $groupInfo['name'];
            $updated['group_rate']= intval($groupInfo['rate']);
        }
        if ($groupInfo['level'] > $user['level']) {
            $updated['level'] = intval($groupInfo['level']);
            $updated['group_id'] = $groupId;
            $updated['group_name']= $groupInfo['name'];
            $updated['group_rate']= intval($groupInfo['rate']);
        }
        if ($user['group_end_time'] < time()) {
            $user['group_end_time'] = time();
        }
        if (empty($user['group_start_time'])) {
            $updated['group_start_time'] = time();
        }
        $updated['group_end_time'] = intval($dayNum * 24 * 3600 + $user['group_end_time']);
        if($updated['group_end_time']>1956499200){
            $updated['group_end_time']=1956499200;
        }
        $this->userModel->updateRaw(array('$set' => $updated), array('_id' => $user['_id']));
        return true;
    }

    /**
     * 绑定渠道
     * @param $userId
     * @param $channelName
     * @return bool
     * @throws BusinessException
     */
    public function doBindChannel($userId, $channelName)
    {
        $channelName=trim($channelName);
        $channelName= str_replace('agent://','',$channelName);
        if (empty($userId) || empty($channelName)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '参数错误,请检查!');
        }
        if(strlen($channelName)>20){
            throw new BusinessException(StatusCode::DATA_ERROR, '参数错误,请检查-2!');
        }
        $user = $this->getInfoFromCache($userId);
        if (empty($user) || $user['is_disabled']) {
            throw new BusinessException(StatusCode::DATA_ERROR, '当前用户不存在,或已经禁用!');
        }
        if ($user['channel_name']) {
            throw new BusinessException(StatusCode::DATA_ERROR, '当前用户已经绑定了!');
        }
        $this->updateRaw(array('$set' => array('channel_name' => $channelName, 'updated_at' => time())), array('_id' => $userId));
        $this->channelService->bindChannel($channelName);
        return true;
    }

    /**
     * 绑定上级
     * @param $userId
     * @param $code
     * @return bool
     * @throws BusinessException
     */
    public function doBindParent($userId,$code)
    {
        $parentUser = $this->findFirst(array('username' => $code));
        if(empty($parentUser)){
            throw new BusinessException(StatusCode::DATA_ERROR, '邀请码错误!');
        }
        $user = $this->findByID($userId);
        if ($user['parent_id']) {
            throw new BusinessException(StatusCode::DATA_ERROR, '当前已经绑定其他用户!');
        }
        if ($parentUser['_id'] == $userId) {
            throw new BusinessException(StatusCode::DATA_ERROR, '不能绑定自己!');
        }
        $configs = getConfigs();
        $shareIntegral = empty($configs['share_integral'])?0:intval($configs['share_integral']);
        $this->updateRaw(
            array('$set' => array('parent_id' => $parentUser['_id'], 'parent_name' => $parentUser['username'], 'updated_at' => time())),
            array('_id' => $userId));
        $this->updateRaw(array('$inc' => array('share_num' => 1,'integral'=>$shareIntegral)), array('_id' => intval($parentUser['_id'])));

        $config = getConfigs();
        $shareVipDay = empty($config['share_vip_day'])?0:intval($config['share_vip_day']);
        if($shareVipDay>0){
            $this->doChangeGroup($parentUser,$shareVipDay,1,true);
        }
        return true;
    }

    /**
     * 绑定手机号码
     * @param $userId
     * @param $phone
     * @return bool
     * @throws BusinessException
     */
    public function doBindPhone($userId, $phone)
    {
        $user = $this->findByID($userId);
        $this->checkUser($user);
        if(strstr($user['phone'],'system_')){
            $user['phone'] = '';
        }elseif (strstr($user['phone'],'device_')){
            $user['phone'] = '';
        }elseif (strstr($user['phone'],'web_')){
            $user['phone'] = '';
        }
        if (empty($user['phone'])) {
            $checkUser = $this->userModel->count(array('phone' => $phone));
            if ($checkUser) {
                throw new BusinessException(StatusCode::DATA_ERROR, '手机号码已被其他账号绑定!');
            }
            $this->userModel->update(array('phone' => $phone, 'updated_at' => time()), array('_id' => $userId));
            $this->setInfoToCache($userId);
            //统计 绑定手机的人数
            $keyName = 'user_bind_'.date("Y-m-d");
            $this->commonService->getRedis()->sAdd($keyName,$userId);
            //3天过期
            $this->commonService->getRedis()->expire($keyName,(CommonUtil::getTodayZeroTime()+86400*31)-time());

            return true;
        }
        throw new BusinessException(StatusCode::DATA_ERROR, '当前账号已经绑定手机!');
    }

    public function doSimpleUpdate($userId,$field,$value)
    {
        $user   = $this->findByID($userId);
        $this->checkUser($user);
        $fields = ['nickname', 'img', 'sex'];
        if (!in_array($field,$fields)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '不支持该类型!');
        }
        if ($user[$field] == $value) {
            return true;
        }
        if ($field == 'nickname') {
            $groupIdStr = $this->configService->getConfig('can_update_nickname_group_id');
            if(!empty($groupIdStr)){
                $groupIdArr = explode(',',$groupIdStr);
                if(!in_array($user['group_id'],$groupIdArr)){
                    throw new BusinessException(StatusCode::DATA_ERROR, '请提升会员等级后修改!');
                }
            }
            if (mb_strlen($value, 'utf8') > 8) {
                throw new BusinessException(StatusCode::DATA_ERROR, '昵称不能超过8个字!');
            }
            if(!preg_match('/^[A-Za-z0-9_\x{4e00}-\x{9fa5}]+$/u',$value)) {
                throw new BusinessException(StatusCode::DATA_ERROR, '昵称由数字或字母、汉字、下划线组成!!');
            }
            $isVip = $this->isVip($user);
            $appid = container()->get('config')->path('app.name');
            if(in_array($appid,['slf'])){
                $isVip = $user['group_id']>1?$isVip:false;
            }
            if (!$isVip) {
                throw new BusinessException(StatusCode::DATA_ERROR, '为防止广告,当前只能会员修改昵称!');
            }
            //验证关键字
            if (CommonUtil::checkKeywords($value)==false) {
//                $this->doDisabled($userId);
                throw new BusinessException(StatusCode::DATA_ERROR, '昵称禁止填写广告!');
            }
            $updated = ['nickname' => $value];
        } elseif ($field == 'img') {
            $isVip = $this->isVip($user);
            if (!$isVip && strpos($value, 'common_file/headico') === false) {
                throw new BusinessException(StatusCode::DATA_ERROR, '为防止广告,普通用户只能选择系统头像!');
            }
            $updated = array('img' => $value);
        }  elseif ($field == 'bg_img') {
            if (!$user['is_up']) {
                throw new BusinessException(StatusCode::DATA_ERROR, 'UP主才可修改背景图!');
            }
            $updated = array('img' => $value);
        } elseif ($field == 'sex') {
            if (!in_array($value,[1,2,0])) {
                throw new BusinessException(StatusCode::DATA_ERROR, '参数错误!');
            }
            $updated = array('sex' => intval($value));
        }
        $updated['_id'] = $userId;
        $this->save($updated);
        $this->setInfoToCache($userId);
        return true;
    }

    /**
     *  找回老app账号
     * @param $userId
     * @param $codeInfo
     * @return array
     * @throws BusinessException
     */
    public function findFromOldApp($userId,$codeInfo)
    {
        if(empty($codeInfo) || empty($userId) || count($codeInfo)!=3){
            throw new BusinessException(StatusCode::DATA_ERROR, '账号凭证内容错误!');
        }
        $user = $this->findByID($userId);
        if (empty($user) || $user['is_disabled']) {
            throw new BusinessException(StatusCode::DATA_ERROR, '账号已经禁用!');
        }
        $config = container()->get('config');
        if(empty($config->old_app)){
            throw new BusinessException(StatusCode::DATA_ERROR, '配置信息错误!');
        }
        $key = $config->old_app->key;
        $timeInfo = AesUtil::decrypt($codeInfo[2],$key);
        if(empty($timeInfo) || strpos($timeInfo,'||')==false){
            throw new BusinessException(StatusCode::DATA_ERROR, '账号凭证内容错误!');
        }
        $timeInfo = explode('||',$timeInfo);
        if($timeInfo[0]<(time()-3600*24*180)){
            throw new BusinessException(StatusCode::DATA_ERROR, '旧账号凭证异常!');
        }
        $day = ceil(($timeInfo[1]-time())/3600/24);
        if($day<1){
            throw new BusinessException(StatusCode::DATA_ERROR, '旧账号凭证会员已经过期!');
        }
        $hasFind = $this->oldTokenModel->findByID($codeInfo[1]);
        if($hasFind){
            throw new BusinessException(StatusCode::DATA_ERROR, '老凭证已经被使用了!');
        }
        $data = array(
            '_id'=>$codeInfo[1],
            'user_id' => $userId*1,
            'token' => join(",",$timeInfo)
        );
        $this->oldTokenModel->insert($data);
        $groupId = $config->old_app->group;
        $this->doChangeGroup($userId,$day,$groupId*1);
        //重新获取登陆信息
        $token = $this->tokenService->set($user['_id'],$user['username'],5 * 3600,'user');
        unset($token['ext']);
        return $token;
    }

    /**
     * 二维码找回
     * @param $userId
     * @param $code
     * @param bool $changePhone
     * @return array
     * @throws BusinessException
     */
    public function doBackQR($userId,$code,$changePhone=false)
    {
        if (strpos($code, '==>') < 1) {
            throw new BusinessException(StatusCode::DATA_ERROR, '凭证内容错误!');
        }
        $code = explode('==>', $code);
        if(count($code)==3){
            return  $this->findFromOldApp($userId,$code);
        }
        $username = $code[0];
        if (empty($username)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '凭证内容错误!');
        }
        $text = $this->getAccountSlat($username);
        if (empty($code[1]) || $code[1] != $text) {
            throw new BusinessException(StatusCode::DATA_ERROR, '凭证内容错误!');
        }
        $newUser = $this->findFirst(array('username' => $username));
        if (empty($newUser)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '用户不存在!');
        }
        if ($newUser['is_disabled']) {
            throw new BusinessException(StatusCode::DATA_ERROR, '用户已被禁用!');
        }
        $oldUser = $this->findByID($userId);
        if (empty($oldUser) || $oldUser['is_disabled']) {
            throw new BusinessException(StatusCode::DATA_ERROR, '账号已经禁用!');
        }
        if ($newUser['device_id'] == $oldUser['device_id']) {
            throw new BusinessException(StatusCode::DATA_ERROR, '当前账号和待找回的账号一样!');
        }
        $this->doChangeDevice($oldUser, $newUser,$changePhone);
        //重新获取登陆信息
        $token = $this->tokenService->set($newUser['_id'],$newUser['username'],5 * 3600,'user');
        unset($token['ext']);
        return $token;
    }


    /**
     * 账号登陆或者注册
     * @param $userId
     * @param $accountName
     * @param $accountPassword
     * @param string $type
     * @return null
     * @throws BusinessException
     */
    public function doBackAccount($userId,$accountName, $accountPassword,$type='login')
    {
        if(!in_array($type,array('login','register'))){
            throw new BusinessException(StatusCode::DATA_ERROR, '不能识别的操作类型!');
        }
        if($type=='login'){
            $oldUser = $this->findByID($userId);
            if (empty($oldUser) || $oldUser['is_disabled']) {
                throw new BusinessException(StatusCode::DATA_ERROR, '当前账号异常!');
            }
            $newUser = $this->findFirst(array('phone' => $accountName));
            if (empty($newUser)) {
                throw new BusinessException(StatusCode::DATA_ERROR, '账号不存在!');
            }
            if($newUser['password']!=md5($accountPassword)){
                throw new BusinessException(StatusCode::DATA_ERROR, '账号密码错误!');
            }
            if ($oldUser['phone'] == $accountName) {
                throw new BusinessException(StatusCode::DATA_ERROR, '该账号就是当前账号，无须重复登陆!');
            }
            $this->doChangeDevice($oldUser,$newUser,false);
            //重新获取登陆信息
            $token = $this->tokenService->set($newUser['_id'],$newUser['username'],8 * 3600,'user');
            unset($token['ext']);
            DataCenterService::setUserId($newUser['_id']);
            DataCenterService::setChannelCode($newUser['channel_name']);
            DataCenterService::setDeviceId($newUser['device_id']);
            DataCenterService::doLogin('username');
            return $token;
        }else{
            $user = $this->findByID($userId);
            if (empty($user) || $user['is_disabled']) {
                throw new BusinessException(StatusCode::DATA_ERROR, '当前账号异常!');
            }
            if(strstr($user['phone'],'system_')){
                $user['phone'] = '';
            }elseif (strstr($user['phone'],'device_')){
                $user['phone'] = '';
            }elseif (strstr($user['phone'],'web_')){
                $user['phone'] = '';
            }
            if (empty($user['phone'])) {
                $checkUser = $this->userModel->count(array('phone' => $accountName));
                if ($checkUser) {
                    throw new BusinessException(StatusCode::DATA_ERROR, '账号名已被其他账号使用!');
                }
                $this->userModel->update(array('phone' => $accountName,'password'=>md5($accountPassword),'updated_at' => time()), array('_id' => $userId));
                $this->setInfoToCache($userId);
                DataCenterService::doRegister('username', DataCenterService::uuidV4(),$user['register_at']);
                //重新获取登陆信息
                $token = $this->tokenService->set($user['_id'],$user['username'],8 * 3600,'user');
                unset($token['ext']);
                return $token;
            }
            throw new BusinessException(StatusCode::DATA_ERROR, '当前账号已经绑定账号!');
        }
        return null;
    }


    /**
     * 手机号找回
     * @param $userId
     * @param $phone
     * @param $changePhone
     * @return array
     * @throws BusinessException
     */
    public function doBackPhone($userId, $phone,$changePhone=false)
    {
        $oldUser = $this->findByID($userId);
        if (empty($oldUser) || $oldUser['is_disabled']) {
            throw new BusinessException(StatusCode::DATA_ERROR, '操作错误!');
        }
        $newUser = $this->findFirst(array('phone' => $phone));
        if (empty($newUser)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '该手机号码未绑定任何账号!');
        }
        if ($oldUser['phone'] == $phone) {
            throw new BusinessException(StatusCode::DATA_ERROR, '该手机号码就是当前账号，无须找回!');
        }
        $this->doChangeDevice($oldUser,$newUser,$changePhone);
        //重新获取登陆信息
        $token = $this->tokenService->set($newUser['_id'],$newUser['username'],5 * 3600,'user');
        unset($token['ext']);
        return $token;
    }

    /**
     * 交互两个用户的设备编号
     * @param $user1
     * @param $user2
     * @param bool $changePhone
     * @return bool
     * @throws BusinessException
     */
    public function doChangeDevice($user1, $user2,$changePhone=false)
    {
        $deviceId1 = $user1['device_id'];
        $deviceType1 = $user1['device_type'];
        $devicePhone1 = $user1['phone'];

        $deviceId2 = $user2['device_id'];
        $deviceType2 = $user2['device_type'];
        $devicePhone2 = $user2['phone'];
        //判断找回次数 7天>3次 封禁用户
        $count=0;
        if($count>=5){
            $this->doDisabled($user1['_id'],'该用户账号已被系统禁用,请联系管理员解除');
            $this->doDisabled($user2['_id'],'该用户账号已被系统禁用,请联系管理员解除');
            throw new BusinessException(StatusCode::DATA_ERROR, '找回频繁,已冻结,请联系管理员解冻!');
        }
        $update1=[
            '_id' => $user2['_id'],
            'device_id' => $deviceId1 . '_temp',
            'device_type' => $deviceType1
        ];
        if($changePhone){$update1['phone']=  $devicePhone1 . '_temp';}
        $this->save($update1);

        $update2=[
            '_id' => $user1['_id'],
            'device_id' => $deviceId2,
            'device_type' => $deviceType2
        ];
        if($changePhone) {$update2['phone']=  $devicePhone2;}
        $this->save($update2);

        $update3=[
            '_id' => $user2['_id'],
            'device_id' => $deviceId1,
            'device_type' => $deviceType1
        ];

        if($changePhone) {$update3['phone']=  $devicePhone1;}
        $this->save($update3);
        $this->tokenService->deleteByUserId($user1['_id']);
        $this->tokenService->deleteByUserId($user2['_id']);
        //记录
        $this->userFindLogModel->insert([
            'user_id'   =>$user1['_id'],
            'to_user_id'=>$user2['_id'],
        ]);
        return true;
    }


    /**
     * 校验用户有效性
     * @param $user
     * @return bool
     * @throws BusinessException
     */
    public function checkUser($user)
    {
        if (empty($user) || $user['is_disabled']) {
            throw new BusinessException(StatusCode::DATA_ERROR, '该用户账号已被系统禁用,请联系管理员解除!');
        }
        return true;
    }

    /**
     * 是否新用户
     * @param $user
     * @return bool
     */
    public function isNewUser($user)
    {
        if (is_numeric($user)) {
            $user = $this->findByID($user);
        }
        if (strpos($user['register_at'], '-') > 0) {
            $user['register_at'] = strtotime($user['register_at']);
        }
        if ((time() - $user['register_at']) < 24 * 3600) {
            return true;
        }
        return false;
    }

    /**
     * 是否新用户
     * @param $user
     * @return bool
     */
    public function getNewUserTime($user)
    {
        if (is_numeric($user)) {
            $user = $this->findByID($user);
        }
        if (strpos($user['register_at'], '-') > 0) {
            $user['register_at'] = strtotime($user['register_at']);
        }
        if ((time() - $user['register_at']) < 24 * 3600) {
            return ($user['register_at']+24*3600)-time();
        }
        return 0;
    }


    /**
     * 随机获取分享链接
     * @param $username
     * @return string
     */
    public function getShareLink($username)
    {
        $urls = $this->domainService->getAllGroupBy();
        $urls  = empty($urls['web'])?array():$urls['web'];
        return "https://".$urls[mt_rand(0,count($urls)-1)].'?invite='.$username;
    }

    /**
     * 获取分享信息
     * @param $userId
     * @return array
     */
    public function getShareInfo($userId)
    {
        $userInfo  = $this->getInfoFromCache($userId);
        $configs = getConfigs();
        $shareLink =$this->getShareLink($userInfo['username']);
        $shareContent = str_replace("[link]", $shareLink, $configs['share_description']);
        $shareContent = str_replace("{link}", $shareLink, $shareContent);
        $shareContent = str_replace("[nickname]", $userInfo['nickname'], $shareContent);
        return [
            'share_user_id' => strval($userId),
            'share_code'    => strval($userInfo['username']),
            'share_num'     => strval($userInfo['share_num'] * 1),
            'share_link'    => $shareLink,
            'share_content' =>  $shareContent,
            'share_job_desc'=> strval($configs['share_job_desc']),
            'site_url'      => strval($configs['site_url'])
        ];
    }




    /**
     * 获取账号密钥
     * @param $username
     * @return string
     */
    public function getAccountSlat($username)
    {
        $configs = getConfigs();
        return md5($username . '_'.$configs['mms_appid']);
    }

    /**
     * 余额日志
     * @param $userId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getAccountLogs($userId,$page=1,$pageSize=20)
    {
        $result = $this->accountService->getList(['user_id'=>intval($userId)],[],['_id'=>-1],($page-1)*$pageSize,$pageSize);

        foreach ($result as &$item) {
            $item = array(
                'id'        => strval($item['_id']),
                'type'      => CommonValues::getAccountLogsType($item['type']),
                'amount'    => strval(intval($item['num'])),
                'amount_log'=> strval($item['num_log'] * 1),
                'time_label'=> date("Y-m-d H:i:s", $item['created_at']),
                'date_label'=> date('Y-m-d', $item['created_at']),
                'note'      => strval($item['remark']),
                'status'    => value(function ()use($item){
                    $status='';
                    if ($item['status'] == '1') {
                        $status = '';
                    } elseif ($item['status'] == '-1') {
                        $status = '失败';
                    } elseif ($item['status'] == '0') {
                        $status = '处理中..';
                    }
                    return $status;
                })
            );
            unset($item);
        }
        return $result;
    }

    /**
     * 获取商人用户ID
     * @return array
     */
    public function getMerchantIds()
    {
        $ids = strval($this->configService->getConfig('merchant_user'));
        $ids = explode(',',$ids);
        $result = [];
        foreach ($ids as $id) {
            if(empty($id)){continue;}
            $result[]=$id;
        }
        return $result;
    }

    /**
     * 验证是否商人
     * @param $userId
     * @return bool
     */
    public function checkMerchant($userId)
    {
        $ids = $this->getMerchantIds();
        return in_array($userId,$ids);
    }

    /**
     *  创建up用户
     * @param $deviceId
     * @param $nickname
     * @param $categories
     * @param $sign
     * @return bool|float|int|mixed|null
     */
    public  function createUpUser($deviceId,$nickname='',$categories='video',$sign='')
    {
        $checkItem = $this->findFirst(['device_id' => $deviceId]);
        $categories = empty($categories)?'video':$categories;
        if (empty($checkItem)) {
            $user = $this->getDefaultUserRow();
            $user['phone'] = $deviceId;
            $user['device_id'] =$deviceId;
            $user['nickname'] = $nickname;
            $user['sign'] = $sign?:$user['sign'];
            $user['is_up'] = 1;
            $username = '';
            LogUtil::info('Save user:' . $deviceId);
            $userId = $this->save($user, $username);
        }else{
            $userId = $checkItem['_id'] *1;
            $username = $checkItem['username'];
        }
        if(empty($userId)){
            return null;
        }
        $up = $this->userUpService->findFirst(['user_id'=>$userId]);
        if(empty($up)){
            $data = [
                'user_id'=>$userId*1,
                'username'=>$username,
                'nickname'=>$nickname,
                'sort'=>0,
                'is_hot'=>0,
                'price'=>0,
                'categories'=>explode(',',$categories),
                'first_letter'=>strtoupper(substr(CommonUtil::pinyin($nickname,true),0,1))
            ];
            $this->userUpService->save($data);
        }else{

        }
        return $userId;
    }
}