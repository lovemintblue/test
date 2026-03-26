<?php


namespace App\Jobs\Common;


use App\Jobs\BaseJob;
use App\Services\AgentSystemService;
use App\Services\MovieService;
use App\Utils\LogUtil;

/**
 * Class AsyncDomainJob
 * @property MovieService $movieService
 * @property AgentSystemService $agentSystemService
 * @package App\Jobs\Common
 */
class RandMovieJob extends BaseJob
{
    /**
     * 随机最新
     */
    protected function randNew()
    {
        /*LogUtil::info('Start reset all show time!');
        $this->movieService->updateRaw(['$set' => ['show_at' => strtotime('2023-05-08')]],array('is_new'=>0));
        LogUtil::info('End reset all show time!');*/
        $filter = array('is_new' => 1);
        $pageSize = 100;
        $page = 1;
        while (true) {
            $skip = ($page - 1) * $pageSize;
            $items = $this->movieService->getList($filter, array('_id','name'), array(), $skip, $pageSize);
            if (empty($items)) {
                break;
            }
            foreach ($items as $item) {
                LogUtil::info('Rand new movie:' . $item['name']);
                $showAt = time() - mt_rand(5,24 * 8) * 3600-mt_rand(60,2789);
                $this->movieService->updateRaw(['$set' => ['show_at' => $showAt]], array('_id' => $item['_id']));
                $this->movieService->asyncEs($item['_id']);
            }
            $page += 1;
        }
    }

    /**
     * 随机最热
     */
    protected function randHot()
    {
        $filter = array('is_hot' => 1);
        $pageSize = 100;
        $page = 1;
        while (true) {
            $skip = ($page - 1) * $pageSize;
            $items = $this->movieService->getList($filter, array('_id','name'), array(), $skip, $pageSize);
            if (empty($items)) {
                break;
            }
            foreach ($items as $item) {
                LogUtil::info('Rand hot movie:' . $item['name']);
                $showAt = time() - mt_rand(5, 24 * 8) * 3600;
                $this->movieService->updateRaw(['$set' => ['sort' => $showAt]], array('_id' => $item['_id']));
                $this->movieService->asyncEs($item['_id']);
            }
            $page += 1;
        }
    }

    public function handler($uniqid)
    {
        $this->randNew();
        $this->randHot();
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