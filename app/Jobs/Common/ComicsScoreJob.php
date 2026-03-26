<?php


namespace App\Jobs\Common;


use App\Jobs\BaseJob;
use App\Services\ComicsService;
use App\Utils\LogUtil;

/**
 * 漫画评分
 * Class CartoonScoreJob
 * @property ComicsService $comicsService
 * @package App\Jobs\Common
 */
class ComicsScoreJob extends BaseJob
{
    public function handler($uniqid)
    {
        LogUtil::info("漫画评分统计开始");
        $where =['status'=>1];
        $count = $this->comicsService->count($where);
        $pageSize = 1000;
        $totalPage = ceil($count / $pageSize);
        for ($page = 1; $page <= $totalPage; $page++) {
            $skip = ($page - 1) * $pageSize;
            $items = $this->comicsService->getList($where, [], array('_id' => -1), $skip, $pageSize);
            foreach ($items as $item) {
                $score=$item['real_click']>0?$item['real_favorite']*1000/$item['real_click']:0;
                //设置评分
                if ( $this->comicsService->setFavoriteRate($item['_id'],$score)) {
                    LogUtil::info("Set cartoon score id=>{$item['_id']} score=>{$score} ok");
                } else {
                    LogUtil::info("Set cartoon score id=>{$item['_id']} score=>{$score} error");
                }
//                $historyCount   = $this->cartoonHistoryService->count(['cartoon_id'=>intval($item['_id'])]);
//                $favoriteCount  = $this->cartoonFavoriteService->count(['cartoon_id'=>intval($item['_id'])])*1000;
//
//                if($historyCount == 0 || $favoriteCount == 0){
//                    $score = 0;
//                }else{
//                    $rate = intval($favoriteCount/$historyCount*1000 );
//                    if($rate>60){
//                        $score = 100;
//                    }elseif ($rate>50){
//                        $score = 90;
//                    }elseif ($rate>40){
//                        $score = 80;
//                    }elseif ($rate>30){
//                        $score = 70;
//                    }elseif ($rate>20){
//                        $score = 60;
//                    }else{
//                        $score = 50;
//                    }
//                }
//                //设置评分
//                if ( $this->cartoonService->setScore($item['_id'],$score)) {
//                    LogUtil::info("Set cartoon score id=>{$item['_id']} score=>{$score} ok");
//                } else {
//                    LogUtil::info("Set cartoon score id=>{$item['_id']} score=>{$score} error");
//                }
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