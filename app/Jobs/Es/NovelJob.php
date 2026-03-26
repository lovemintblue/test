<?php

namespace App\Jobs\Es;

use App\Jobs\BaseJob;
use App\Services\NovelService;
use App\Services\PostService;
use App\Utils\LogUtil;

/**
 * 同步小说到Es
 * Class CartoonJob
 * @property NovelService $novelService
 * @package App\Jobs\Async
 */
class NovelJob extends BaseJob
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
            $items = $this->novelService->getList($where, ['_id'], ['_id' => -1], 0, $pageSize);
            if (empty($items)) {
                break;
            }
            foreach ($items as $item) {
                if ($this->novelService->asyncEs($item['_id'])) {
                    LogUtil::info('Async novel to es ok:' . $item['_id']);
                } else {
                    LogUtil::error('Async novel to es error:' . $item['_id']);
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