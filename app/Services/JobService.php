<?php


namespace App\Services;


use App\Core\ShouldQueue;
use App\Core\Services\BaseService;
use App\Core\Services\RedisService;
use App\Models\JobModel;
use App\Utils\LogUtil;

/**
 * Class JobService
 * @property JobModel $jobModel
 * @package App\Services
 */
class JobService extends BaseService
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
        return $this->jobModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->jobModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->jobModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->jobModel->findByID(intval($id));
    }

    /**
     * 通过id查询
     * @param $uniqid
     * @return array
     */
    public function findByUniqid($uniqid)
    {
        return $this->jobModel->findFirst(['uniqid'=>$uniqid]);
    }

    /**
     * 创建任务
     * @param ShouldQueue $job
     * @param string $drive
     * @param int $planAt 计划执行时间
     * @param string $level low medium high
     * @param string $exp
     */
    public function create(ShouldQueue $job,$drive='sync',int $planAt=0,$level='low')
    {
        $job->setJobDrive($drive);
        $data=[
            'uniqid'   =>$this->getUniqid($job),
            'job'      =>$this->serialize($job),
            'exception'=>'',
            'status'   =>0,
            'level'    =>$level,
            'failed_at'=>null,
            'plan_at'  =>$planAt?intval($planAt):time(),
        ];
        if($drive=='mongodb'){
            $this->save($data);
        }else if($drive=='redis'){
            $this->getRedis()->lPush('job_task',$data);
        } else{
            $this->executeQueue($job,$data['uniqid']);
        }
    }

    /**
     * 消费队列
     * @param string $level
     * @param string $drive
     * @return bool
     */
    public function onQueue($level='low',$drive='mongodb')
    {
        if($drive=='mongodb'){
            $row = $this->jobModel->findAndModify(array('status' => 0,'level'=>$level,'plan_at'=>['$lte'=>time()]), array('$set' => array('status' => 1,'exception'=>'')));
        }elseif ($drive=='redis'){
            $row = $this->getRedis()->rPop('job_task');
        }else{
            return false;
        }
        if(empty($row)){
            if($level=='high'){
                sleep(2);
            }elseif ($level=='medium'){
                sleep(5);
            }else{
                sleep(10);
            }
            return true;
        }
        $jobClass=$this->unserialize($row['job']);
        $this->executeQueue($jobClass,$row['uniqid']);
    }

    /**
     * 执行队列
     * @param ShouldQueue $jobClass
     * @param $uniqid
     */
    private function executeQueue(ShouldQueue $jobClass,$uniqid)
    {
        $jobDrive=$jobClass->getJobDrive();
        try{
            call_user_func_array([$jobClass,'handler'],[$uniqid]);
            LogUtil::info(sprintf("Queue: %s=>success",$uniqid));
            if($jobDrive=='mongodb'){
                $this->jobModel->delete(['uniqid'=>$uniqid]);
            }
            call_user_func_array([$jobClass,'success'],[$uniqid]);
        }catch (\Exception $e){
            LogUtil::error(sprintf('Queue: %s %s in %s line %s', $uniqid,$e->getMessage(), $e->getFile(),$e->getLine()));
            if($jobDrive=='mongodb'){
                if ($this->jobModel->count(['uniqid'=>$uniqid])) {
                    $this->jobModel->updateRaw(['$set'=>['exception'=>serialize($e),'failed_at'=>time(),'status'=>-1]],['uniqid'=>$uniqid]);
                }
            }
            call_user_func_array([$jobClass,'error'],[$uniqid]);
        }
    }

    /**
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->jobModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->jobModel->insert($data);
        }
    }

    /**
     * 生成唯一值
     * @param ShouldQueue $job
     * @return string
     */
    private function getUniqid(ShouldQueue $job){
        return get_class($job).'_'.(microtime(true)*10000);
    }

    /**
     * @param ShouldQueue $job
     * @return string
     */
    private function serialize(ShouldQueue $job)
    {
        return serialize($job);
    }

    /**
     * @param $job
     * @return ShouldQueue
     */
    private function unserialize($job)
    {
        return unserialize($job);
    }
}