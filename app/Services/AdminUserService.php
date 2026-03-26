<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Models\AdminLogModel;
use App\Models\AdminUserModel;
use App\Utils\CommonUtil;

/**
 * 系统用户管理
 * @package App\Services
 *
 * @property  AdminUserModel $adminUserModel
 * @property  AdminLogModel $adminLogModel
 * @property CommonService $commonService
 * @property GoogleService $googleService
 * @property  TokenService $tokenService
 */
class AdminUserService extends BaseService
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
        return $this->adminUserModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->adminUserModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->adminUserModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->adminUserModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->adminUserModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->adminUserModel->insert($data);
        }
    }

    /**
     * 更新数据
     * @param $data
     * @param $query
     * @return mixed
     */
    public function update($data, $query)
    {
        return $this->adminUserModel->update($data, $query);
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->adminUserModel->delete(array('_id' => intval($id)));
    }

    /**
     * 密码加密
     * @param $password
     * @param $slat
     * @return string
     */
    public function makePassword($password, $slat)
    {
        return md5(md5($password) . 'This is password' . md5($slat));
    }

    /**
     * 增加管理员日志
     * @param $content
     * @param string $ip
     */
    public function addAdminLog($content, $ip = '')
    {
        $token = $this->getToken();
        if (empty($token)) return;
        $this->addLog($token['user_id'], $token['username'], $content, $ip);
    }


    /**
     * 保存管理员日志
     * @param $adminId
     * @param $adminName
     * @param $content
     * @param string $ip
     * @return bool
     */
    public function addLog($adminId, $adminName, $content, $ip = '')
    {
        if (empty($ip)) {
            $ip = getClientIp();
        }
        $data = array(
            'admin_id' => intval($adminId),
            'admin_name' => $adminName,
            'content' => $content,
            'date_time'  => CommonUtil::getTodayZeroTime(),
            'ip' => $ip
        );
        $this->adminLogModel->insert($data);
        return true;
    }

    /**
     * 获取日志列表
     * @param array $query
     * @param array $fields
     * @param array $sort
     * @param int $skip
     * @param int $limit
     * @return array
     */
    public function getLogList($query = array(), $fields = array(), $sort = array(), $skip = 0, $limit = 10)
    {
        return $this->adminLogModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取日志总计
     * @param $query
     * @return integer
     */
    public function countLog($query)
    {
        return $this->adminLogModel->count($query);
    }


    /**
     * 返回token
     * @return null|array
     */
    public function getToken()
    {
        $ip  = getClientIp();
        /********ip必须在白名单 可以用tools添加**************/
        $configs = getConfigs();
        $ips = $configs['whitelist_ip'];
        if(empty($ips)){
            return null;
        }
        if(strpos($ips,$ip)===false){
            return null;
        }
        /********ip必须在白名单**************/
        $tokenStr = $this->getCookie()->get('token')->getValue();
        $uid = $this->getCookie()->get('uid')->getValue();
        if (empty($tokenStr) || empty($uid)) {
            return null;
        }
        $token = $this->tokenService->get($uid, 'admin');
        if (empty($token) || $token['user_id'] != $uid || $token['token'] != $tokenStr) {
            return null;
        }
        if($ip!=$token['ip']){
            return null;
        }
        return $token;
    }

    /**
     * 删除日志
     * @param $query
     * @return bool|mixed
     */
    public function deleteLog($query)
    {
        return $this->adminLogModel->delete($query);
    }

    /**
     * 获取谷歌验证码
     * @param $username
     * @return string
     */
    public function getGoogleQrcode($username)
    {
        $appName = $this->commonService->getConfig('system_name');
        $secret = '';
        $google = $this->googleService->createSecret($username, $appName, $secret);
        setCache('google_' . $username, $secret, 600);
        return $google;
    }

    /**
     * 绑定谷歌验证码
     * @param $username
     * @param $googleCode
     * @return string
     * @throws BusinessException
     */
    public function bindGoogle($username, $googleCode)
    {
        $secret = getCache('google_' . $username);
        if (empty($secret)) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '谷歌验证码已失效!');
        }
        $googleCode = strval($googleCode);
        $check = $this->googleService->verifyCode($secret, $googleCode);

        if (!$check) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '谷歌验证码错误!');
        }
        $adminUser = $this->findFirst(array('username' => $username));
        if (empty($adminUser) || $adminUser['is_disabled']) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '用户不存在,或者已经被禁用!');
        }
        if ($adminUser['google_code']) {
           // throw  new BusinessException(StatusCode::DATA_ERROR, '当前用户已经绑定谷歌验证码,请联系管理员先删除!');
        }
        $this->save(array(
            '_id' => $adminUser['_id'],
            'google_code' => $secret
        ));
        return true;
    }

    /**
     * 添加用户
     * @param $username
     * @param $password
     * @param $roleId
     * @param string $googleCode
     * @param string $ip
     * @return bool|int|mixed
     * @throws BusinessException
     */
    public function addUser($username,$password,$roleId,$googleCode='',$ip='127.0.0.1')
    {
        $checkUser = $this->findFirst(array('username' => $username));
        if ($checkUser) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '用户名已经存在!');
        }
        $row = array(
            'username'  => $username,
            'real_name' => $username,
            'google_code'=>$googleCode,
            'role_id'   => intval($roleId),
            'is_disabled'=>0,
            'email'     => '',
        );
        if ($password) {
            $row['slat'] = strval(mt_rand(10000, 50000));
            $row['password'] = $this->makePassword($password, $row['slat']);
        }
        $result = $this->save($row);
        $this->addLog(-1, '终端', '终端添加管理:' . $row['username'],$ip);
        return $result;
    }

    /**
     * 禁用或者启用用户
     * @param $username
     * @param bool $isDisable
     * @return bool
     */
    public function disableUser($username,$isDisable=true)
    {
        $data = array(
            'is_disabled' => $isDisable?1:0
        );
        $this->adminUserModel->updateRaw(array('$set'=>$data),array('username'=>$username));
        $this->addLog(-1, '终端', '终端禁用或者启用:' . $username,'127.0.0.1');
        return true;
    }
}