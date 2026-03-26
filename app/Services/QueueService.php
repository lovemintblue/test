<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CommonValues;
use App\Core\Services\BaseService;
use App\Utils\LogUtil;

/**
 * Class QueueService
 * @package App\Services
 * @property CommentService $commentService
 * @property CommonService $commonService
 * @property MovieService $movieService
 * @property UserService $userService
 * @property AccountService $accountService
 * @property UserHobbyService $userHobbyService
 * @property AnalysisMovieService $analysisMovieService
 */
class QueueService extends BaseService
{
    /**
     * 获取队列
     * @return string
     */
    protected function getQueueKey()
    {
        $queueConfig = container()->get('config')->queue;
        return $queueConfig->channel . $queueConfig->index;
    }

    /**
     * 执行程序
     * @throws \App\Exception\BusinessException
     */
    public function run()
    {
        $queueKey = $this->getQueueKey();
        //注册到队列管理器
        $this->getRedis()->sAdd(CommonValues::QUEUE_IDS_KYE,$queueKey);
        while (true) {
            $queue = $this->getRedis()->rPop($queueKey);
            if (empty($queue) || empty($queue['action'])) {
                sleep(1);
                continue;
            }
            //队列只是接收最近一小时内的数据,其它直接丢去,避免脏数据
            $queueTime = $queue['time'];
            if((time()-$queueTime)>3600){
                continue;
            }
            $action = $queue['action'];
            LogUtil::info('Do queue:' . $action);
            $data = $queue['data'];
            switch ($action) {
                case 'clearCache':
                    $this->commonService->clearCache();
                    break;
                case 'userReg':
                    $this->userService->userRegHandler($data);
                    break;
            }
        }
    }

    /**
     * 加入队列
     * @param $action
     * @param array $data
     */
    public function join($action, $data = array())
    {
        $queueKey = $this->getQueueKey();
        $this->getRedis()->lPush($queueKey, array(
            'action' => $action,
            'data' => $data,
            'time' => time()
        ));
    }

    /**
     * 发送到其他节点
     * @param $action
     * @param $data
     * @param bool $self
     */
    public function sendNodes($action,$data,$self=true)
    {
        $keys = $this->getRedis()->sMembers(CommonValues::QUEUE_IDS_KYE);
        if (empty($keys)) return;
        foreach ($keys as $key) {
            if ($this->getQueueKey()==$key&&$self==false) {
                continue;
            }
            $this->getRedis()->lPush($key,[
                'action' => $action,
                'data' => $data,
                'time' => time()
            ]);
        }
    }
}
