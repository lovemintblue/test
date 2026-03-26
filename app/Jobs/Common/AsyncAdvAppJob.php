<?php


namespace App\Jobs\Common;


use App\Jobs\BaseJob;
use App\Services\AgentSystemService;
use App\Services\DomainService;
use App\Utils\LogUtil;

/**
 * 同步广告应用
 * Class AsyncAdvAppJob
 * @property DomainService $domainService
 * @property AgentSystemService $agentSystemService
 * @package App\Jobs\Common
 */
class AsyncAdvAppJob extends BaseJob
{
    public function handler($uniqid)
    {
        $appList = [];
        $page = 1;
        $count = 0;
        while (true) {
            $items = $this->agentSystemService->getAdvAppList($page);
            if($items===false){
                LogUtil::error("Async app error page: {$page}!");
                return;
            }
            if(empty($items)){break;}
            foreach($items as $data){
                $appList[$data['group']][] = $data;
                $count++;
            }
            $page += 1;
        }
        $keyName = 'app_list_total';
        container()->get('redis')->set($keyName, json_encode(['time'=>time()+90,'items'=>$appList]));
        LogUtil::info('Async advApp ok! total:'.$count);
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