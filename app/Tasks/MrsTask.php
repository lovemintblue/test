<?php


namespace App\Tasks;


use App\Core\BaseTask;
use App\Jobs\Mrs\XbComicsJob;
use App\Jobs\Mrs\XbMovieJob;
use App\Jobs\Mrs\XbNovelJob;
use App\Services\JobService;

/**
 * 媒资库同步
 * Class AsyncTask
 * @property JobService $jobService
 * @package App\Tasks
 */
class MrsTask extends BaseTask
{
    /**
     * 同步视频
     * @param string $category
     * @param string $ids
     */
    public function movieAction($category='',$ids='')
    {
        $this->jobService->create(new XbMovieJob($category,$ids),'sync');
    }

    /**
     * 同步漫画
     * @param string $category
     * @param string $ids
     */
    public function comicsAction($category='',$ids='')
    {
        $this->jobService->create(new XbComicsJob($category,$ids),'sync');
    }

    /**
     * 同步小说
     * @param string $category
     * @param string $ids
     */
    public function novelAction($category='',$ids='')
    {
        $this->jobService->create(new XbNovelJob($category,$ids),'sync');
    }

    /**
     * 同步更新中的漫画
     */
    public function updateComicsAction()
    {
        $this->jobService->create(new XbComicsJob('','',true),'sync');
    }

    /**
     * 同步更新中的漫画
     */
    public function updateNovelAction()
    {
        $this->jobService->create(new XbNovelJob('','',true),'sync');
    }

    /**
     * 同步更新中的视频
     * @param string $category
     * @param string $ids
     */
    public function updateMovieAction($category='',$ids='')
    {
        $this->jobService->create(new XbMovieJob($category,$ids,true),'sync');
    }

    /**
     *  更新视频的播放地址和封面
     * @param string $category
     * @param string $ids
     */
    public function updateMovieLinkAndImgAction($category='',$ids='')
    {
        $this->jobService->create(new XbMovieJob($category,$ids,false,true),'sync');
    }
}