<?php


namespace App\Jobs\Common;


use App\Jobs\BaseJob;
use App\Services\CartoonService;
use App\Services\CartoonTagService;
use App\Services\MovieService;
use App\Services\MovieTagService;
use App\Services\PostService;
use App\Utils\LogUtil;

/**
 * Class TagCountJob
 * @property MovieService $movieService
 * @property PostService  $postService
 * @package App\Jobs\Common
 */
class TagCountJob extends BaseJob
{
    public function handler($uniqid)
    {
        // TODO: Implement handler() method.
    }

    public function success($uniqid)
    {
        $this->countPostTags();
    }

    public function countPostTags()
    {
        $this->postService->countTags();
    }

    public function error($uniqid)
    {
        // TODO: Implement error() method.
    }

}