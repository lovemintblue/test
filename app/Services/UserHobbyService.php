<?php


namespace App\Services;


use App\Core\Services\BaseService;
use App\Models\UserHobbyModel;

/**
 * 用户嗜好
 * Class UserHobbyService
 * @property UserHobbyModel $userHobbyModel
 * @package App\Services
 */
class UserHobbyService extends BaseService
{
    /**
     * 记录
     * @param $userId
     * @param $name
     * @param int $value
     */
    public function do($userId,$name,$value=1)
    {
        $name = trim($name);
        $id = md5($userId.'_'.$name);
        $has= $this->userHobbyModel->count(['_id'=>$id]);
        if($has){
            $this->userHobbyModel->updateRaw(['$inc'=>['value'=>$value]],['_id'=>$id]);
        }else{
            $this->userHobbyModel->insert([
                '_id'   =>$id,
                'name'  =>$name,
                'value' =>$value
            ]);
        }
    }
}