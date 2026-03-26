<?php

namespace App\Jobs\Mrs;

use App\Jobs\BaseJob;
use App\Services\CommonService;
use App\Services\MovieService;
use App\Utils\LogUtil;

/**
 * Class XbMovieJob
 * @property MovieService $movieService
 * @property CommonService $commonService
 * @package App\Jobs\Mrs
 */
class XbMovieJob extends BaseJob
{
    private $category;
    private $ids;
    private $isUpdate;
    private $isUpdateLink;

    public function __construct($category='',$ids='',$isUpdate=false,$isUpdateLink=false)
    {
        $this->category=$category;
        $this->ids=$ids;
        $this->isUpdate=$isUpdate;
        $this->isUpdateLink=$isUpdateLink;
    }

    public function handler($uniqid)
    {
        exit;
        if($this->ids){
            $ids=explode(',',$this->ids);
            foreach ($ids as $id) {
                $this->movieService->asyncMrs(array('id'=>$id));
            }
        }elseif ($this->isUpdate){
            $query = array('update_status' => 0);
            $count = $this->movieService->count($query);
            $pageSize = 100;
            $totalPage = ceil($count / $pageSize);
            for ($page = 1; $page <= $totalPage; $page++) {
                LogUtil::info('Starting update mrs:' . $page);
                $items = $this->movieService->getList($query, ['_id'], [], ($page - 1) * $pageSize, $pageSize);
                foreach ($items as $item) {
                    $this->movieService->asyncMrs(array('ids' => $item['_id']));
                }
            }
        }elseif ($this->isUpdateLink){
            $query = [];
            $count = $this->movieService->count($query);
            $pageSize = 100;
            $totalPage = ceil($count / $pageSize);
            for ($page = 1; $page <= $totalPage; $page++) {
                LogUtil::info('Starting update mrs:' . $page);
                $items = $this->movieService->getList($query, ['_id'], [], ($page - 1) * $pageSize, $pageSize);
                foreach ($items as $item) {
                    $this->movieService->asyncMrs(array('ids' => $item['_id']),$this->isUpdateLink);
                }
            }
        } else{
            LogUtil::info('不支持批量同步视频了!');
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