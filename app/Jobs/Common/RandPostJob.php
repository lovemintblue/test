<?php


namespace App\Jobs\Common;


use App\Jobs\BaseJob;
use App\Services\AgentSystemService;
use App\Services\MovieService;
use App\Services\PostService;
use App\Utils\LogUtil;

/**
 * Class RandPostJob
 * @property PostService $postService
 * @package App\Jobs\Common
 */
class RandPostJob extends BaseJob
{
    /**
     * 随机最新
     */
    protected function randNew()
    {
        $filter = array();
        $pageSize = 100;
        $page = 1;
        while (true) {
            $skip = ($page - 1) * $pageSize;
            $items = $this->postService->getList($filter, array('_id','title'), array(), $skip, $pageSize);
            if (empty($items)) {
                break;
            }
            foreach ($items as $item) {
                LogUtil::info('Rand new movie:' . $item['title']);
                $showAt = time() - mt_rand(5,24 * 8) * 3600-mt_rand(60,2789);
                $this->postService->updateRaw(['$set' => ['created_at' => $showAt,'updated_at'=>$showAt]], array('_id' => $item['_id']));
                $this->postService->asyncEs($item['_id']);
            }
            $page += 1;
        }
    }


    public function handler($uniqid)
    {
        $this->randNew();
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