<?php

namespace App\Jobs\Es;

use App\Jobs\BaseJob;
use App\Services\PlayService;
use App\Utils\LogUtil;

/**
 * 同步玩法到Es
 * Class CartoonJob
 * @property PlayService $playService
 * @package App\Jobs\Async
 */
class PlayJob extends BaseJob
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
            $items = $this->playService->getList($where, ['_id'], ['_id' => -1], 0, $pageSize);
            if (empty($items)) {
                break;
            }
            foreach ($items as $item) {
                if ($this->playService->asyncEs($item['_id'])) {
                    LogUtil::info('Async play to es ok:' . $item['_id']);
                } else {
                    LogUtil::error('Async play to es error:' . $item['_id']);
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