<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Core\Services\RequestService;
use App\Exception\BusinessException;
use App\Services\AdminRoleService;
use App\Services\AdminUserService;
use App\Services\AuthorityService;
use App\Services\CommonService;
use App\Services\GoogleService;
use App\Services\TokenService;


/**
 * 系统用户
 * @package App\Repositories\Backend
 *
 * @property  RequestService $requestService
 * @property  AdminUserService $adminUserService
 * @property  AdminRoleService $adminRoleService
 * @property  AuthorityService $authorityService
 * @property  TokenService $tokenService
 * @property  GoogleService $googleService
 * @property  CommonService $commonService
 */
class AdminUserRepository extends BaseRepository
{
    protected $tokenNameSpace = 'admin';


    /**
     * 获取谷歌验证码
     * @param $username
     * @return string
     */
    public function getGoogleQrcode($username)
    {
        return $this->adminUserService->getGoogleQrcode($username);
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
       return $this->adminUserService->bindGoogle($username,$googleCode);
    }

    /**
     * 获取列表
     * @param $request
     * @return array
     */
    public function getList($request)
    {
        $page     = $this->getRequest($request, 'page', 'int', 1);
        $pageSize = $this->getRequest($request, 'pageSize', 'int', 10);
        $sort     = $this->getRequest($request, 'sort', 'string', '_id');
        $order    = $this->getRequest($request, 'order', 'int', -1);
        $query = array();
        $filter = array();

        if ($request['username']) {
            $filter['username'] = $this->getRequest($request, 'username');
            $query['username'] = array('$regex' => $filter['username'], '$options' => 'i');
        }
        if (isset($request['role_id']) && $request['role_id'] !== "") {
            $filter['role_id'] = $this->getRequest($request, 'role_id', 'int');
            $query['role_id'] = $filter['role_id'];
        }
        if (isset($request['is_disabled']) && $request['is_disabled'] !== "") {
            $filter['is_disabled'] = $this->getRequest($request, 'is_disabled', 'int');
            $query['is_disabled'] = $filter['is_disabled'];
        }

        $skip = ($page - 1) * $pageSize;
        $fields = array();
        $count = $this->adminUserService->count($query);
        $items = $this->adminUserService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        $roles = $this->adminRoleService->getRoles();
        foreach ($items as $index => $item) {
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);
            $item['login_at'] = dateFormat($item['login_at']);
            $item['login_ip'] = strval($item['login_ip']);
            $item['role_name'] = empty($item['role_id']) ? '超级管理员' : strval($roles[$item['role_id']]['name']);
            $item['bind_google_code'] = empty($item['google_code']) ? 0 : 1;
            $item['is_disabled_text'] = CommonValues::getIsDisabled($item['is_disabled']);
            unset($item['google_code']);
            unset($item['password']);
            unset($item['slat']);
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
        $username = $this->getRequest($data, 'username');
        if (empty($username)) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }
        $password = $this->getRequest($data, 'password');
        if (empty($data['_id']) && empty($password)) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '新增用户密码不能为空!');
        }
        $checkUser = $this->adminUserService->findFirst(array('username' => $username));
        if ($checkUser && $checkUser['_id'] != $data['_id']) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '用户名已经存在!');
        }
        $row = array(
            'username'  => $username,
            'real_name' => $this->getRequest($data, 'real_name'),
            'google_code'=>$this->getRequest($data, 'google_code'),
            'role_id'   => $this->getRequest($data, 'role_id', 'int', 0),
            'is_disabled'=>$this->getRequest($data, 'is_disabled', 'int', 0),
            'email'     => $this->getRequest($data, 'email'),
        );
        if ($password) {
            $row['slat'] = strval(mt_rand(10000, 50000));
            $row['password'] = $this->adminUserService->makePassword($password, $row['slat']);
        }
        if ($data['_id'] > 0) {
            $row['_id'] = $this->getRequest($data, '_id', 'int');
        }
        $result = $this->adminUserService->save($row);
        $token = $this->getToken();
        if ($token) {
            $this->adminUserService->addLog($token['user_id'], $token['username'], '操作管理员账号:' . $row['username']);
        }
        return $result;
    }

    public function doDisable($id)
    {

    }

    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->adminUserService->findByID($id);
        if (empty($row)) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '数据不存在!');
        }
        unset($row['password']);
        unset($row['slat']);
        return $row;
    }

    /**
     * 删除
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $token = $this->getToken();
        if ($token) {
            $this->adminUserService->addLog($token['user_id'], $token['username'], '操作管理员账号:' . $id);
        }
        return $this->adminUserService->delete($id);
    }


    /**
     * 退出
     * @return bool
     */
    public function logout()
    {
        $token = $this->getToken();
        if (!empty($token)) {
            $this->tokenService->deleteByUserId($token['user_id'],$this->tokenNameSpace);
        }
        return true;
    }

    /**
     * 保存token
     * @param $adminUser
     * @return array
     */
    public function setToken($adminUser)
    {
        return $this->tokenService->set($adminUser['_id'], $adminUser['username'], 36000, $this->tokenNameSpace);
    }

    /**
     * 返回token
     * @return null|array
     */
    public function getToken()
    {
        return $this->adminUserService->getToken();
    }

    /**
     * 校验谷歌验证
     * @param $googleCode
     * @param null|array $admin
     * @return bool
     */
    public function verifyGoogleCode($googleCode,$admin=null)
    {
        if(empty($admin)){
            $token = $this->getToken();
            if (empty($token)) {
                return false;
            }
            $admin = $this->adminUserService->findByID($token['user_id']);
        }
        return $this->googleService->verifyCode($admin['google_code'], $googleCode);
    }

    /**
     * 提现是否进行过谷歌验证
     * @param null $admin
     * @return bool
     */
    public function verifyGoogleCheck($admin=null)
    {
        if(empty($admin)){
            $token = $this->getToken();
            if (empty($token)) {
                return false;
            }
            $admin = $this->adminUserService->findByID($token['user_id']);
        }
        $keyName = 'google_check_' . $admin['_id'];
        $check = getCache($keyName);
        if(!empty($check)){
            return true;
        }
        delCache($keyName);
        return false;
    }

    /**
     * 登陆返回key
     * @param $username
     * @param $password
     * @param $googleCode
     * @return array
     * @throws BusinessException
     */
    public function login($username, $password, $googleCode)
    {
        $ip = getClientIp();
        $configs = getConfigs();
        $ips = $configs['whitelist_ip'];
        if(empty($ips)){
            throw  new  BusinessException(StatusCode::DATA_ERROR, '请联系管理员配置系统白名单!');
        }
        if(strpos($ips,$ip)===false){
            throw  new  BusinessException(StatusCode::DATA_ERROR, '当前ip不在白名单!');
        }
        $adminUser = $this->adminUserService->findFirst(array('username' => $username));
        if (empty($adminUser) || $adminUser['is_disabled']) {
            throw  new  BusinessException(StatusCode::DATA_ERROR, '用户不存在或已被禁用!');
        }
        if (empty($adminUser['google_code'])) {
            throw  new  BusinessException(StatusCode::DATA_ERROR, '请联系管理员绑定谷歌验证码!');
        }
        $verifyGoogleCode = $this->verifyGoogleCode($googleCode,$adminUser);
        if(!$verifyGoogleCode){
            throw  new  BusinessException(StatusCode::DATA_ERROR, '谷歌验证码错误!');
        }
        $checkPassword = $this->adminUserService->makePassword($password, $adminUser['slat']);
        if ($checkPassword != $adminUser['password']) {
            throw  new  BusinessException(StatusCode::DATA_ERROR, '密码错误!');
        }
        $this->adminUserService->update(['login_at'=>time(), 'login_ip'=>getClientIp(),],['_id'=>$adminUser['_id']]);
        $token = $this->setToken($adminUser);
        $this->adminUserService->addLog($adminUser['_id'], $adminUser['username'], '用户登录!');
        return $token;
    }


    /**
     * 检查权限
     * @param string $path
     * @return bool
     */
    public function checkPermission($path = '')
    {
        $isSupperAdmin = false;
        $permissions = $this->getPermissions($isSupperAdmin);
        if ($isSupperAdmin) {
            return true;
        }
        $result = false;
        foreach ($permissions as $permission) {
            if ($permission['key'] == $path) {
                $result = true;
                break;
            }
            foreach ($permission['child'] as $child) {
                if ($child['key'] == $path) {
                    $result = true;
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * 获取可操作的菜单
     * @return array
     */
    public function getMenus()
    {
        $permissions = $this->getPermissions();
        foreach ($permissions as $index => $permission) {
            if (!$permission['is_menu']) {
                 unset($permissions[$index]);
                 continue;
            }
            foreach ($permission['child'] as $key => $child) {
                if (!$child['is_menu']) {
                     unset($permissions[$index]['child'][$key]);
                     continue;
                }
            }
        }
        return array_values($permissions);
    }

    /**
     * 获取所有权限
     * @param boolean $isSupperAdmin
     * @return array
     */
    public function getPermissions(&$isSupperAdmin = null)
    {
        $token = $this->getToken();
        if (empty($token)) {
            return array();
        }
        $adminUser = $this->adminUserService->findByID($token['user_id']);
        if (empty($adminUser) || $adminUser['is_disabled']) {
            return array();
        }
        $permissions = $this->authorityService->getTree();
        if (empty($adminUser['role_id'])) {
            $isSupperAdmin = true;
            return $permissions;
        }
        $adminRole = $this->adminRoleService->findByID($adminUser['role_id']);
        if (empty($adminRole) || $adminRole['is_disabled']) {
            return array();
        }

        $rights = $adminRole['rights'];
        if (is_string($rights)) {
            $rights = explode(',', $rights);
        }
        foreach ($permissions as $index => $parent) {
            foreach ($parent['child'] as $key => $resource) {
                if (!in_array($resource['key'], $rights)) {
                    unset($parent['child'][$key]);
                }
            }
            $parent['child'] = array_values($parent['child']);
            if (empty($parent['child'])) {
                unset($permissions[$index]);
            } else {
                $permissions[$index] = $parent;
            }
        }
        return array_values($permissions);
    }


    /**
     * 修改密码
     * @param $oldPassword
     * @param $newPassword
     * @return bool
     * @throws BusinessException
     */
    public function changePassword($oldPassword, $newPassword)
    {
        $token = $this->getToken();
        if (empty($token)) {
            throw  new  BusinessException(StatusCode::NO_LOGIN_ERROR);
        }
        $adminUser = $this->adminUserService->findByID($token['user_id']);
        if (empty($adminUser) || $adminUser['is_disabled']) {
            throw  new  BusinessException(StatusCode::NO_LOGIN_ERROR);
        }
        $oldPassword = $this->adminUserService->makePassword($oldPassword, $adminUser['slat']);
        if ($oldPassword != $adminUser['password']) {
            throw  new  BusinessException(StatusCode::DATA_ERROR, '旧密码不正确!');
        }

        $newPassword = $this->adminUserService->makePassword($newPassword, $adminUser['slat']);
        if ($newPassword == $oldPassword) {
            throw  new  BusinessException(StatusCode::DATA_ERROR, '新旧密码一样,无需修改!');
        }

        $this->adminUserService->save(array(
            '_id' => intval($adminUser['_id']),
            'password' => $newPassword
        ));
        return true;
    }


}