<?php

namespace App\Jobs\Es;

use App\Jobs\BaseJob;
use App\Services\PostService;
use App\Utils\LogUtil;

/**
 * 同步帖子到Es
 * Class CartoonJob
 * @property PostService $postService
 * @package App\Jobs\Async
 */
class PostJob extends BaseJob
{
    public function handler($uniqid)
    {
        // TODO: Implement handler() method.
        $where = [];
        $pageSize = 1000;
        $lastId = null;
        while (true) {
            if ($lastId !== null) {
                $where['_id'] = ['$lt' => $lastId];
            }
            $items = $this->postService->getList($where, ['_id'], ['_id' => -1], 0, $pageSize);
            if (empty($items)) {
                break;
            }
            foreach ($items as $item) {
                if ($this->postService->asyncEs($item['_id'])) {
                    LogUtil::info('Async post to es ok:' . $item['_id']);
                } else {
                    LogUtil::error('Async post to es error:' . $item['_id']);
                }
                $lastId = $item['_id'];
            }
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