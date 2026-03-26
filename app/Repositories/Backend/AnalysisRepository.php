<?php


namespace App\Repositories\Backend;


use App\Core\Repositories\BaseRepository;
use App\Services\AnalysisCartoonService;
use App\Services\AnalysisMovieService;
use App\Services\ComicsService;
use App\Services\MovieService;

/**
 * Class AnalysisRepository
 * @property AnalysisMovieService $analysisMovieService
 * @property AnalysisCartoonService $analysisCartoonService
 * @property MovieService $movieService
 * @property ComicsService $comicsService
 * @package App\Repositories\Backend
 */
class AnalysisRepository extends BaseRepository
{
    public function getMovieList($request)
    {

        $page = $this->getRequest($request, 'page', 'int', 1);
        $pageSize = $this->getRequest($request, 'pageSize', 'int', 30);
        $sort     = $this->getRequest($request, 'sort', 'string', 'click');
        $order    = $this->getRequest($request, 'order', 'int', -1);

        $query = array();
        $filter = array();


        if ($request['object_id']) {
            $filter['object_id'] = $this->getRequest($request, 'object_id', 'int');
            $query['movie_id'] = $filter['object_id'];
        }

        if ($request['start_time']) {
            $filter['start_time'] = $this->getRequest($request, 'start_time');
            $query['time']['$gte'] = strtotime($filter['start_time']);
        }
        if ($request['end_time']) {
            $filter['end_time'] = $this->getRequest($request, 'end_time');
            $query['time']['$lte'] = strtotime($filter['end_time']." 23:59:59");
        }
        $skip = ($page - 1) * $pageSize;
        $fields = array();
        $count = $this->analysisMovieService->count($query);
        $items = $this->analysisMovieService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $movieInfo=$this->movieService->findByID($item['movie_id']);
            $item['object_id']=$item['movie_id'];
            $item['name']=$movieInfo['name']??'-';
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);
            $item['click']      = isset($item['click'])?formatNum($item['click'],2):0;
            $item['favorite']   = isset($item['favorite'])?formatNum($item['favorite'],2):0;
            $item['buy_num']    = isset($item['buy_num'])?formatNum($item['buy_num'],2):0;
            $item['buy_total']  = isset($item['buy_total'])?formatNum($item['buy_total'],2):0;
            $items[$index] = $item;
        }
        $countInfo=$this->analysisMovieService->sum([
            [
                '$match'=>$query
            ],
            [
                '$group' => [
                    '_id' => null,
                    'click' => ['$sum' => '$click'],
                    'buy_total' => ['$sum' => '$buy_total'],
                    'buy_num' => ['$sum' => '$buy_num'],
                    'favorite' => ['$sum' => '$favorite'],
                ]
            ]
        ]);
        return array(
            'filter' => $filter,
            'items' => empty($items) ? array() : array_values($items),
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalRow'=>[
                '_id'=>'合计',
                'object_id'=>'-',
                'name'=>'-',
                'date_label'=>'-',
                'click'=>formatNum($countInfo->click,2),
                'buy_total'=>formatNum($countInfo->buy_total,2),
                'buy_num'=>formatNum($countInfo->buy_num,2),
                'favorite'=>formatNum($countInfo->favorite,2),
                'updated_at'=>'-',
                'created_at'=>'-',
            ]
        );
    }


    public function getCartoonList($request)
    {

        $page = $this->getRequest($request, 'page', 'int', 1);
        $pageSize = $this->getRequest($request, 'pageSize', 'int', 30);
        $sort     = $this->getRequest($request, 'sort', 'string', 'detail');
        $order    = $this->getRequest($request, 'order', 'int', -1);

        $query = array();
        $filter = array();
        if ($request['object_id']) {
            $filter['object_id'] = $this->getRequest($request, 'object_id', 'int');
            $query['cartoon_id'] = $filter['object_id'];
        }

        if ($request['start_time']) {
            $filter['start_time'] = $this->getRequest($request, 'start_time');
            $query['time']['$gte'] = strtotime($filter['start_time']);
        }
        if ($request['end_time']) {
            $filter['end_time'] = $this->getRequest($request, 'end_time');
            $query['time']['$lte'] = strtotime($filter['end_time']." 23:59:59");
        }
        $skip = ($page - 1) * $pageSize;
        $fields = array();

        $count = $this->analysisCartoonService->count($query);
        $items = $this->analysisCartoonService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $cartoonInfo=$this->comicsService->findByID($item['cartoon_id']);
            $item['object_id']=$item['cartoon_id'];
            $item['name']=$cartoonInfo['name']??'-';
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);
            $item['favorite']   = isset($item['favorite'])?formatNum($item['favorite'],2):0;
            $item['detail']     = isset($item['detail'])?formatNum($item['detail'],2):0;
            $item['click']       = isset($item['click'])?formatNum($item['click'],2):0;
            $items[$index] = $item;
        }
        $countInfo=$this->analysisCartoonService->sum([
            [
                '$match'=>$query
            ],
            [
                '$group' => [
                    '_id'   => null,
                    'click' => ['$sum' => '$click'],
                    'detail'=> ['$sum' => '$detail'],
                    'favorite' => ['$sum' => '$favorite'],
                ]
            ]
        ]);
        return array(
            'filter' => $filter,
            'items' => empty($items) ? array() : array_values($items),
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalRow'=>[
                '_id'=>'合计',
                'object_id'=>'-',
                'name'=>'-',
                'date_label'=>'-',
                'click'      =>formatNum($countInfo->click,2),
                'detail'    =>formatNum($countInfo->detail,2),
                'favorite'  =>formatNum($countInfo->favorite,2),
                'updated_at'=>'-',
                'created_at'=>'-',
            ]
        );
    }

}