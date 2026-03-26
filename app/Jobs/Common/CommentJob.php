<?php


namespace App\Jobs\Common;


use App\Jobs\BaseJob;
use App\Services\CartoonService;
use App\Services\CommentReplyService;
use App\Services\CommentService;
use App\Services\CommonService;
use App\Services\MovieService;
use App\Services\PostService;
use App\Utils\LogUtil;

/**
 * 评论次数重计算
 * Class CommentJob
 * @property MovieService $movieService
 * @property CartoonService $cartoonService
 * @property CommonService $commonService
 * @property CommentService $commentService
 * @property CommentReplyService $commentReplyService
 * @package App\Jobs\Common
 */
class CommentJob extends BaseJob
{
    public function handler($uniqid)
    {
        set_time_limit(0);
        // TODO: Implement handler() method.
        $pageSize=10000;
        $page=1;
        $cartoon=[];
        $movie=[];
        while (true){
            $rows = $this->commentService->getList([],[],[],($page-1)*$pageSize,$pageSize);
            if (empty($rows)){break;}
            foreach ($rows as $row) {
                $reply = $this->commentReplyService->count(['comment_id'=>intval($row['_id'])]);
                $this->commentService->commentModel->updateRaw(['$set'=>['child_num'=>intval($reply)]],['_id'=>intval($row['_id'])]);
                if($row['object_type']=='cartoon'){
                    if (isset($cartoon[$row['object_id']])) {
                        $cartoon[$row['object_id']]+=$reply+1;
                    }else{
                        $cartoon[$row['object_id']]=$reply+1;
                    }
                }elseif($row['object_type']=='movie'){
                    if (isset($movie[$row['object_id']])) {
                        $movie[$row['object_id']]+=$reply+1;
                    }else{
                        $movie[$row['object_id']]=$reply+1;
                    }
                }
            }
            $page++;
        }

        foreach ($movie as $movieId=>$count) {
            if ($this->movieService->count(['_id'=>intval($movieId)])==1) {
                $this->movieService->updateRaw(['$set'=>['comment'=>intval($count)]],['_id'=>intval($movieId)]);
                $this->commonService->setRedisCounter("movie_comment_{$movieId}", $count);
            }
        }
        LogUtil::debug("movie ok");
        foreach ($cartoon as $cartoonId=>$count) {
            if ($this->cartoonService->count(['_id'=>intval($cartoonId)])==1) {
                $this->cartoonService->updateRaw(['$set'=>['comment'=>intval($count)]],['_id'=>intval($cartoonId)]);
                $this->commonService->setRedisCounter("cartoon_comment_{$cartoonId}", $count);
            }
        }
        LogUtil::debug("cartoon ok");
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