<?php


namespace App\Jobs\Common;


use App\Jobs\BaseJob;
use App\Services\UserService;

/**
 * 用户支付任务-计算累计充值任务
 * Class UserPayJob
 * @property UserService $userService
 * @package App\Jobs\Common
 */
class UserPayJob extends BaseJob
{
    public $userId;

    public function __construct($userId)
    {
        $this->userId=intval($userId);
    }

    public function handler($uniqid)
    {
        $userRow = $this->userService->findByID($this->userId);
        if($userRow['group_end_time']>=1893427200){
            return true;
        }
        if($userRow['money_count']>=10000){
            $this->userService->doChangeGroup($userRow,3650,9);
        }elseif ($userRow['money_count']>=500){
            $this->userService->doChangeGroup($userRow,3650,8);
        }elseif ($userRow['money_count']>=300){
            $this->userService->doChangeGroup($userRow,3650,7);
        }elseif ($userRow['money_count']>=200){
            $this->userService->doChangeGroup($userRow,3650,6);
        }
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