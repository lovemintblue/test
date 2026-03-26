<?php


namespace App\Tasks;

use App\Core\BaseTask;
use App\Jobs\Mrs\LsjComicsJob;
use App\Jobs\Mrs\LsjMovieJob;
use App\Jobs\Mrs\LsjNovelJob;
use App\Jobs\Mrs\LsjPostJob;
use App\Services\JobService;
use App\Services\MrsSystemService;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 * Class LsjTask
 * @property  JobService $jobService
 * @property  MrsSystemService $mrsSystemService
 * @package App\Tasks
 */
class LsjTask extends BaseTask
{
    /**
     *  视频同步
     */
    public function asyncMovieAction($position='all',$catId='')
    {
        $this->jobService->create(new LsjMovieJob(['position'=>$position,'cat_id'=>$catId,'page_size'=>100]),'sync');
    }

    /**
     * 视频同步-更新
     */
    public function updateMovieAction($startTime='',$endTime='')
    {
        $this->jobService->create(new LsjMovieJob(['start_time'=>$startTime,'end_time'=>$endTime,'page_size'=>100],true),'sync');
    }

    /**
     * 视频同步-随机更新
     */
    public function pullMovieRandomAction($position ='',$catId='',$pageSize='50')
    {
        $this->jobService->create(new LsjMovieJob(['position'=>$position,'cat_id'=>$catId,'page_size'=>$pageSize,'order'=>'rand'],false,false),'sync');
    }

    /**
     *  帖子同步
     */
    public function  asyncPostAction()
    {
        $this->jobService->create(new LsjPostJob(['page_size'=>100]),'sync');
    }

    /**
     * 帖子同步-更新
     */
    public function updatePostAction($startTime='',$endTime='')
    {
        $this->jobService->create(new LsjPostJob(['start_time'=>$startTime,'end_time'=>$endTime,'page_size'=>100],true,false),'sync');
    }

    /**
     *  漫画同步
     */
    public function asyncComicsAction($catId='')
    {
        $this->jobService->create(new LsjComicsJob(['cat_id'=>$catId,'page_size'=>100]),'sync');
    }

    /**
     * 漫画同步-更新
     */
    public function updateComicsAction($startTime='',$endTime='')
    {
        $this->jobService->create(new LsjComicsJob(['start_time'=>$startTime,'end_time'=>$endTime,'page_size'=>100],true),'sync');
    }

    /**
     * 小说同步
     */
    public function asyncNovelAction($catId='')
    {
        $this->jobService->create(new LsjNovelJob(['cat_id'=>$catId,'page_size'=>100]),'sync');
    }

    /**
     * 小说同步-更新
     */
    public function updateNovelAction($startTime='',$endTime='')
    {
        $this->jobService->create(new LsjNovelJob(['start_time'=>$startTime,'end_time'=>$endTime,'page_size'=>100],true),'sync');
    }
}