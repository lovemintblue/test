<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKey;
use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Models\UserTaskLogModel;
use App\Models\UserTaskModel;

/**
 *  任务
 * @package App\Services
 *
 * @property  UserTaskModel $userTaskModel
 * @property  UserTaskLogModel $userTaskLogModel
 * @property UserService $userService
 */
class UserTaskService extends BaseService
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
        return $this->userTaskModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->userTaskModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->userTaskModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->userTaskModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            $result= $this->userTaskModel->update($data, array("_id" => $data['_id']));
        } else {
            $result= $this->userTaskModel->insert($data);
        }
        delCache(CacheKey::USER_TASK);
        return $result;
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $result= $this->userTaskModel->delete(array('_id' => intval($id)));
        delCache(CacheKey::USER_TASK);
        return $result;
    }

    /**
     * 获取所有
     * @return array
     */
    public function getAll()
    {
        $result = getCache(CacheKey::USER_TASK);
        if(empty($result)){
            $items = $this->getList(array(),array(),array("sort"=>-1),0,1000);
            $result = array();
            foreach ($items as $item)
            {
                $result[$item['_id']] = array(
                    'id'   => strval($item['_id']),
                    'name'   => strval($item['name']),
                    'type'   => strval($item['type']),
                    'description'   => strval($item['description']),
                    'max_limit'   => strval($item['max_limit']*1),
                    'num'   => strval($item['num']*1),
                    'link'   => strval($item['link'])
                );
            }
            setCache(CacheKey::USER_TASK,$result,mt_rand(60,80));
        }
        return $result;
    }

    /**
     * 获取日志记录编号
     * @param $userId
     * @param $taskId
     * @return string
     */
    public function getTaskLogId($userId,$taskId)
    {
        return md5(sprintf('user_task_%s_%s_%s',$userId,$taskId,date('Y-m-d')));
    }

    /**
     * 获取是否完成任务
     * @param $userId
     * @param $taskId
     * @return int
     */
    public function  has($userId,$taskId)
    {
        $logId = $this->getTaskLogId($userId,$taskId);
        return  $this->userTaskLogModel->count(array('_id'=>$logId));
    }

    /**
     * 记录点击事件
     * @param $userId
     * @param $taskId
     * @return bool
     * @throws BusinessException
     */
    public function doTaskLog($userId,$taskId)
    {
        if(empty($userId) || empty($taskId)){
            throw new BusinessException(StatusCode::DATA_ERROR, '参数错误!');
        }
        $logKey = $this->getTaskLogId($userId,$taskId);
        $this->getRedis()->incrBy($logKey,1);
        $this->getRedis()->expire($logKey,24*3600);
        return true;
    }

    /**
     * 执行福利任务
     * @param $userId
     * @param $taskId
     * @return bool
     * @throws BusinessException
     */
    public function doTask($userId,$taskId)
    {
        $userId = $userId * 1;
        $taskId = $taskId * 1;
        if(empty($userId) || empty($taskId)){
            throw new BusinessException(StatusCode::DATA_ERROR, '参数错误!');
        }
        $tasks = $this->getAll();
        if(empty($tasks[$taskId])){
            throw new BusinessException(StatusCode::DATA_ERROR, '福利任务不存在!');
        }
        $task = $tasks[$taskId];
        $logKey  = $this->getTaskLogId($userId,$task['id']);

        $hasDone = $this->has($userId,$task['id']);
        if($hasDone){
            throw new BusinessException(StatusCode::DATA_ERROR, '今日该福利已经领取!');
        }
        if(!in_array($task['type'],array('login','download'))){
            throw new BusinessException(StatusCode::DATA_ERROR, '领取失败!');
        }
        if($task['type']=='download'){
            $hasClick =  $this->getRedis()->get($logKey);
            if(!$hasClick){
                throw new BusinessException(StatusCode::DATA_ERROR, '请先完成任务后在领取!');
            }
        }
        if($task['num']<0 || $task['num']>100){
            throw new BusinessException(StatusCode::DATA_ERROR, '该福利任务错误!');
        }
        $data = array(
            '_id'=>$logKey,
            'task_id'=>$taskId,
            'task_type'=> $task['type'],
            'user_id' => $userId,
            'integral' => $task['num']*1,
            'date_label' => date('Y-m-d')
        );
        $this->userTaskLogModel->insert($data);

        $this->userService->updateRaw(array('$inc'=>array('integral'=>$data['integral'])),array('_id'=>$userId));
        $this->userService->setInfoToCache($userId);
        return true;
    }

    /**
     * 评论任务
     * @param $userId
     * @param $commentId
     * @return bool
     */
    public function doCommentTask($userId,$commentId)
    {
        $task = $this->userTaskModel->findFirst(array('type'=>'comment'));
        if(empty($task)){
            return false;
        }
        $configs = getConfigs();
        $maxNum = empty($configs['user_task_comment_limit'])?3:intval($configs['user_task_comment_limit']);
        $lockKey = $this->getTaskLogId($userId,$task['_id']);
        $num  = $this->getRedis()->incrBy($lockKey,1);
        if($num==1){
            $this->getRedis()->expire($lockKey,24*3600);
        }
        if($num>$maxNum){
            return false;
        }
        $logKey = md5($lockKey.'_'.$commentId);
        $taskLogCount = $this->userTaskLogModel->count(array('_id'=>$logKey));
        if($taskLogCount>0){
            return false;
        }
        $data = array(
            '_id'=>$logKey,
            'task_id'=>$task['_id']*1,
            'task_type'=> $task['type'],
            'user_id' => $userId,
            'integral' => $task['num']*1,
            'date_label' => date('Y-m-d')
        );
        $this->userTaskLogModel->insert($data);

        $this->userService->updateRaw(array('$inc'=>array('integral'=>$data['integral'])),array('_id'=>$userId));
        $this->userService->setInfoToCache($userId);
        return true;
    }


    /**
     * 获取兑换项
     * @return array
     */
    public function getExchangeItems()
    {
        $result = array();
        $configs =getConfigs();
        $data = strval($configs['integral_exchange_items']);
        $data = explode("\n",$data);
        foreach ($data as $item)
        {
            $item = trim($item);
            $item =  explode("|",$item);
            if(count($item)!=3){
                continue;
            }
            $result[$item[0]*1] = array(
                'num' => strval($item[0]*1),
                'day' => strval($item[2]*1),
                'group'=>strval($item[1]*1)
            );
        }
        return $result;
    }

}