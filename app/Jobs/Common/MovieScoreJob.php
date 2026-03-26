<?php


namespace App\Jobs\Common;


use App\Jobs\BaseJob;
use App\Services\MovieFavoriteService;
use App\Services\MovieHistoryService;
use App\Services\MovieService;
use App\Utils\LogUtil;

/**
 * 视频评分
 * Class MovieScoreJob
 * @property MovieService $movieService
 * @property MovieHistoryService $movieHistoryService
 * @property MovieFavoriteService $movieFavoriteService
 * @package App\Jobs\Common
 */
class MovieScoreJob extends BaseJob
{
    public function handler($uniqid)
    {
        LogUtil::info("视频评分统计开始");
        $where =['status'=>1];
        $count = $this->movieService->count($where);
        $pageSize = 1000;
        $totalPage = ceil($count / $pageSize);
        for ($page = 1; $page <= $totalPage; $page++) {
            $skip = ($page - 1) * $pageSize;
            $items = $this->movieService->getList($where, [], array('_id' => -1), $skip, $pageSize);
            foreach ($items as $item) {
                $score=$item['real_click']>0?$item['real_favorite']*1000/$item['real_click']:0;
//                $historyCount   = $this->movieHistoryService->count(['movie_id'=>intval($item['_id'])]);
//                $favoriteCount  = $this->movieFavoriteService->count(['movie_id'=>intval($item['_id'])]);
//                if($historyCount == 0 || $favoriteCount == 0){
//                    $score = 0;
//                }else{
//                    $rate = intval($favoriteCount/$historyCount*1000 );
//                    if($rate>40){
//                        $score = 100;
//                    }elseif ($rate>35){
//                        $score = 90;
//                    }elseif ($rate>30){
//                        $score = 80;
//                    }elseif ($rate>15){
//                        $score = 70;
//                    }elseif ($rate>10){
//                        $score = 60;
//                    }else{
//                        $score = 50;
//                    }
//                }
                //设置评分
                if ( $this->movieService->setFavoriteRate($item['_id'],$score)) {
                    LogUtil::info("Set movie score id=>{$item['_id']} score=>{$score} ok");
                } else {
                    LogUtil::info("Set movie score id=>{$item['_id']} score=>{$score} error");
                }
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