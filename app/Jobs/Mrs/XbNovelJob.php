<?php

namespace App\Jobs\Mrs;

use App\Jobs\BaseJob;
use App\Services\ComicsService;
use App\Services\CommonService;
use App\Services\NovelService;
use App\Utils\LogUtil;

/**
 * Class XbMovieJob
 * @property NovelService $novelService
 * @property CommonService $commonService
 * @package App\Jobs\Mrs
 */
class XbNovelJob extends BaseJob
{
    private $category;
    private $ids;
    private $isUpdate;

    public function __construct($category = '', $ids = '', $isUpdate = false)
    {
        $this->category = $category;
        $this->ids = $ids;
        $this->isUpdate = $isUpdate;
    }

    public function handler($uniqid)
    {
        exit;
        if ($this->ids) {
            $ids = explode(',', $this->ids);
            foreach ($ids as $id) {
                $this->novelService->asyncMrs(array('ids' => $id));
            }
        } elseif ($this->isUpdate) {
            $query = array('update_status' => 0);
            $count = $this->novelService->count($query);
            $pageSize = 100;
            $totalPage = ceil($count / $pageSize);
            for ($page = 1; $page <= $totalPage; $page++) {
                LogUtil::info('Starting update mrs:' . $page);
                $items = $this->novelService->getList($query, ['_id'], [], ($page - 1) * $pageSize, $pageSize);
                foreach ($items as $item) {
                    $this->novelService->asyncMrs(array('ids' => $item['_id']));
                }
            }
        } else {
            $page = 1;
            while (true) {
                $query = array();
                LogUtil::info('Starting async mrs:' . $page);
                $query['page'] = $page;
                $query['category'] = empty($this->category) ? '' : $this->category;
                $result = $this->novelService->asyncMrs($query);
                if ($result['count'] < 1) {
                    break;
                }
                $page++;
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