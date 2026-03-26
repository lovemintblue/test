<?php

namespace App\Jobs\Es;

use App\Jobs\BaseJob;
use App\Services\CartoonService;
use App\Services\MovieService;
use App\Utils\LogUtil;

/**
 * 同步视频到Es
 * Class MovieJob
 * @property MovieService $movieService
 * @package App\Jobs\Async
 */
class MovieJob extends BaseJob
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
            $items = $this->movieService->getList($where, ['_id'], ['_id' => -1], 0, $pageSize);
            if (empty($items)) {
                break;
            }
            foreach ($items as $item) {
                if ($this->movieService->asyncEs($item['_id'])) {
                    LogUtil::info('Async movie to es ok:' . $item['_id']);
                } else {
                    LogUtil::error('Async movie to es error:' . $item['_id']);
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