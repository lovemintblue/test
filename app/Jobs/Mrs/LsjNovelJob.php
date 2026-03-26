<?php

namespace App\Jobs\Mrs;

use App\Jobs\BaseJob;
use App\Services\CommonService;
use App\Services\MrsSystemService;
use App\Services\NovelService;
use App\Utils\LogUtil;

/**
 * 同步小说
 * Class LsjNovelJob
 * @property NovelService $novelService
 * @property CommonService $commonService
 * @property MrsSystemService $mrsSystemService
 * @package App\Jobs\Mrs
 */
class LsjNovelJob extends BaseJob
{
    private $query;
    private $isUpdate;

    public function __construct($query,$isUpdate=false)
    {
        set_time_limit(0);
        @ini_set('memory_limit','1024M');
        $this->query=$query;
        $this->isUpdate=$isUpdate;
    }

    public function handler($uniqid)
    {
        if(empty($this->query)){return;}

        if($this->isUpdate){
            $this->query['start_time'] = $this->query['start_time']?:date('Y-m-d 00:00:00',strtotime('-1 day'));
        }

        $page  = 1;
        $query = $this->query;
        while (true){
            $query['page']=$page;
            LogUtil::info('Starting async novel:'.json_encode($query));
            $rows = $this->mrsSystemService->getLsjNovelList($query);
            if(empty($rows)){break;}
            foreach($rows as $row){
                $this->novelService->asyncMrs(['ids'=>$row['id']]);
            }
            $page++;
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