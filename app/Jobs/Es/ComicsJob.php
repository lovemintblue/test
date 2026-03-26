<?php

namespace App\Jobs\Es;

use App\Jobs\BaseJob;
use App\Services\CartoonService;
use App\Services\ComicsService;
use App\Utils\LogUtil;

/**
 * 同步漫画到Es
 * Class CartoonJob
 * @property ComicsService $comicsService
 * @package App\Jobs\Async
 */
class ComicsJob extends BaseJob
{
    public function handler($uniqid)
    {
        // TODO: Implement handler() method.
        $where = ['status'=>1];
        $pageSize = 1000;
        $lastId = null;
        while (true) {
            if ($lastId !== null) {
                $where['_id'] = ['$lt' => $lastId];
            }
            $items = $this->comicsService->getList($where, ['_id'], ['_id' => -1], 0, $pageSize);
            if (empty($items)) {
                break;
            }
            foreach ($items as $item) {
                if ($this->comicsService->asyncEs($item['_id'])) {
                    LogUtil::info('Async comics to es ok:' . $item['_id']);
                } else {
                    LogUtil::error('Async comics to es error:' . $item['_id']);
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