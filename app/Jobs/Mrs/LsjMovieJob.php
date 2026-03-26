<?php

namespace App\Jobs\Mrs;

use App\Jobs\BaseJob;
use App\Services\CommonService;
use App\Services\MovieService;
use App\Services\MrsSystemService;
use App\Utils\LogUtil;

/**
 * 同步视频
 * Class LsjMovieJob
 * @property MovieService $movieService
 * @property CommonService $commonService
 * @property MrsSystemService $mrsSystemService
 * @package App\Jobs\Mrs
 */
class LsjMovieJob extends BaseJob
{
    private $query;
    private $isUpdate;

    private $while;

    public function __construct($query,$isUpdate=false,$while = true)
    {
        set_time_limit(0);
        @ini_set('memory_limit','1024M');
        $this->query=$query;
        $this->isUpdate=$isUpdate;
        $this->while=$while;
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
            LogUtil::info('Starting async movie:'.json_encode($query));
            $rows = $this->mrsSystemService->getLsjMovieList($query);
            if(empty($rows)){break;}
            foreach($rows as $row){
                $this->movieService->asyncMrs(array('id'=>$row['mid'],'source'=>'laosiji'));
            }
            if(!$this->while){break;}
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