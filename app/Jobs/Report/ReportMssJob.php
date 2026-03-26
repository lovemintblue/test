<?php


namespace App\Jobs\Report;


use App\Jobs\BaseJob;
use App\Services\MovieService;
use App\Services\MssService;
use App\Services\UserBuyLogService;
use App\Utils\LogUtil;

/**
 * 上报销售系统
 * Class ReportMssJob
 * @property MssService $mssService
 * @property MovieService $movieService
 * @property UserBuyLogService $userBuyLogService
 * @package App\Jobs\Report
 */
class ReportMssJob extends BaseJob
{
    public $startAt;
    protected $numbers = array();

    public function __construct($startAt)
    {
        $this->startAt=$startAt;
    }

    public function handler($uniqid)
    {
        $query = ['created_at'=>['$gte' => $this->startAt],'object_type'=>'movie'];
        $count = $this->userBuyLogService->count($query);
        $pageSize = 100;
        $totalPage = ceil($count / $pageSize);
        for ($page = 1; $page <= $totalPage; $page++) {
            $items = $this->userBuyLogService->getList($query, array(), array('_id' => -1), ($page - 1) * $pageSize, $pageSize);
            $data = array();
            foreach ($items as $item) {
                if($item['object_money']<=0){continue;}
                $data[] = array(
                    'user_id'   => intval($item['user_id']), //用户编号
                    'username'  => strval($item['username']),//用户名
                    'channel'   => strval($item['channel_name']),//渠道
                    'reg_at'    => date('Y-m-d H:i:s', $item['register_at']),//注册时间
                    'price'     => doubleval($item['object_money']),//销售价格 已rmb计价
                    'order_id'  => strval($item['_id']),//订单号
                    'sale_at'   => date('Y-m-d H:i:s', $item['created_at']),//销售时间
                    'movie_number' => $this->getMovieNumber($item['object_id'])
                );
            }
            LogUtil::info(sprintf('Do request:%s/%s',$page,$totalPage));
            $this->mssService->doRequest('/movie/order', $data);
        }
    }


    /**
     * @param $movieId
     * @return mixed
     */
    public function getMovieNumber($movieId)
    {
        if (isset($this->numbers[$movieId])) {
            return $this->numbers[$movieId];
        }
        $movie = $this->movieService->findByID($movieId);
        $this->numbers[$movieId] = empty($movie) ? '' : strval($movie['number']);
        return $this->numbers[$movieId];
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