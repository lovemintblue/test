<?php

namespace App\Jobs\Common;

use App\Jobs\BaseJob;
use App\Services\UserAgentService;
use App\Services\UserService;
use App\Utils\LogUtil;

/**
 * 用户分享任务
 * Class UserShareJob
 * @property UserService $userService
 * @property UserAgentService $userAgentService
 * @package App\Jobs\Common
 */
class UserShareJob extends BaseJob
{
    public $userId;
    public $parentId;

    public function __construct($userId,$parentId)
    {
        $this->userId=$userId;
        $this->parentId=$parentId;
    }

    public function handler($uniqid)
    {
        // TODO: Implement handler() method.
        $userInfo = $this->userService->findByID($this->parentId);
        $actionName= "doTask{$userInfo['share_num']}";
        //生成代理表
//        $this->userAgentService->userMLM($this->userId);

        if (method_exists($this,$actionName)) {
            try{
                $this->$actionName($userInfo);
            }catch (\Exception $e){

            }
        }
    }

    /**
     * @param $userInfo
     * @throws \App\Exception\BusinessException
     */
    public function doTask2($userInfo)
    {
        $this->userService->doChangeGroup($userInfo,3,1);
    }

    /**
     * @param $userInfo
     * @throws \App\Exception\BusinessException
     */
    public function doTask5($userInfo)
    {
        $this->userService->doChangeGroup($userInfo,7,1);
    }

    public function success($uniqid)
    {
        // TODO: Implement success() method.
    }

    public function error($uniqid)
    {
        // TODO: Implement error() method.
    }

}