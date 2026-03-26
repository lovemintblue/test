<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\ChannelReportService;
use App\Services\ChannelService;
use App\Services\CommonService;

/**
 * 渠道管理
 * @package App\Repositories\Admin
 *
 * @property  ChannelService $channelService
 * @property  ChannelReportService $channelReportService
 * @property  CommonService $commonService
 */
class ChannelRepository extends BaseRepository
{
    /**
     * 获取列表
     * @param $request
     * @return array
     */
    public function getList($request)
    {

        $page = $this->getRequest($request, 'page', 'int', 1);
        $pageSize = $this->getRequest($request, 'pageSize', 'int', 30);
        $sort     = $this->getRequest($request, 'sort', 'string', '_id');
        $order    = $this->getRequest($request, 'order', 'int', -1);
        $query = array();
        $filter = array();

        if ($request['name']) {
            $filter['name'] = $this->getRequest($request, 'name');
            $query['name'] = array('$regex' => $filter['name'], '$options' => 'i');
        }
        if ($request['code']) {
            $filter['code'] = $this->getRequest($request, 'code');
            $query['code'] = $filter['code'];
        }
        $skip = ($page - 1) * $pageSize;
        $fields = array();
        $count = $this->channelService->count($query);
        $items = $this->channelService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['channel_url'] = 'https://xxx.com?_c=' . $item['code'];
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);
            $item['is_disabled'] = CommonValues::getIsDisabled($item['is_disabled']);
            $item['last_bind'] = dateFormat($item['last_bind']);
            $items[$index] = $item;
        }

        return array(
            'filter' => $filter,
            'items' => empty($items) ? array() : array_values($items),
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize
        );
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     * @throws BusinessException
     */
    public function save($data)
    {
        if (empty($data['name']) || empty($data['code'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }
        foreach (explode(',', $data['code']) as $code) {
            $row = array(
                'name' => $this->getRequest($data, 'name'),
                'code' => $code,
                'is_disabled' => $this->getRequest($data, 'is_disabled', 'int', 10),
                'weight' => $this->getRequest($data, 'weight', 'int', 10),
                'remark' => $this->getRequest($data, 'remark', ''),
            );
            if ($data['_id'] > 0) {
                $row['_id'] = $this->getRequest($data, '_id', 'int');
            }
            $checkRow = $this->channelService->findFirst(array('code' => $row['code']));
            if ($checkRow && $checkRow['_id'] != $row['_id']) {
                throw  new BusinessException(StatusCode::PARAMETER_ERROR, '标识已存在!');
            }
            $this->channelService->save($row);
        }
        return true;
    }

    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->channelService->findByID($id);
        if (empty($row)) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '数据不存在!');
        }
        return $row;
    }

    /**
     * 更新数据
     * @param $data
     * @return bool|int|mixed
     */
    public function update($data)
    {
        return $this->channelService->save($data);
    }

    /**
     * 删除订单
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->channelService->delete($id);
    }


    /**
     * 获取列表
     * @param $request
     * @return array
     */
    public function getReportList($request)
    {

        $page = $this->getRequest($request, 'page', 'int', 1);
        $pageSize = $this->getRequest($request, 'pageSize', 'int', 15);
        $sort     = $this->getRequest($request, 'sort', 'string', 'user_reg');
        $order    = $this->getRequest($request, 'order', 'int', -1);

        $query = array();
        $filter = array();

        if ($request['code']) {
            $filter['code'] = $this->getRequest($request, 'code');
            $query['code'] = $filter['code'];
        }
        if ($request['start_time']) {
            $filter['start_time'] = $this->getRequest($request, 'start_time');
            $query['created_at']['$gte'] = strtotime($filter['start_time']);
        }
        if ($request['end_time']) {
            $filter['end_time'] = $this->getRequest($request, 'end_time');
            $query['created_at']['$lte'] = strtotime($filter['end_time']." 23:59:59");
        }

        $skip = ($page - 1) * $pageSize;
        $fields = array();
        $count = $this->channelReportService->count($query);
        $items = $this->channelReportService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        $days = CommonValues::getAppDay();
        $group = [];
        foreach ($items as $index => $item) {
            $channel    =$this->channelService->findFirst(['code'=>$item['code']]);

            $item['is_disabled']= $channel?CommonValues::getIsDisabled($channel['is_disabled']):'未找到';
            $item['channel_id'] = $channel?$channel['_id']:'-';
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);

            $item['user_reg']                   = (formatNum($item['user_reg'],2));
//            $item['user_reg_valid']             = (formatNum($item['user_reg_valid'],2));
            $item['user_reg_invalid']           = (formatNum($item['user_reg_invalid'],2));
            $item['android_reg']                = (formatNum($item['android_reg'],2));
            $item['h5_reg']                     = (formatNum($item['h5_reg'],2));

            $item['order_num']                  = (formatNum($item['order_num'],2));
            $item['point_num']                  = (formatNum($item['point_num'],2));
            $item['today_order_num']            = (formatNum($item['today_order_num'],2));
            $item['yesterday_order_num']        = (formatNum($item['yesterday_order_num'],2));

            $item['app_day']                    = (formatNum($item['app_day'],2));
            $item['android_app_day']            = (formatNum($item['android_app_day'],2));
            $item['h5_app_day']                 = (formatNum($item['h5_app_day'],2));
            $item['android_today_app_day']      = (formatNum($item['android_today_app_day'],2));
            $item['h5_today_app_day']           = (formatNum($item['h5_today_app_day'],2));

            $item['pv'] = (formatNum($item['pv'],2));
            $item['uv'] = (formatNum($item['uv'],2));
            $item['ip'] = (formatNum($item['ip'],2));
            $item['click_android'] = (formatNum($item['click_android'],2));
            $item['click_ios'] = (formatNum($item['click_ios'],2));

            foreach ($days as $key=>$val){
                $item['android_app_day'.$key] = (formatNum($item['android_app_day'.$key],2));
                $item['h5_app_day'.$key]      = (formatNum($item['h5_app_day'.$key],2));
                $group['android_app_day'.$key] = ['$sum' => '$android_app_day'.$key];
                $group['h5_app_day'.$key]     = ['$sum' => '$h5_app_day'.$key];
            }

            $items[$index] = $item;
        }
        $moneyCount=$this->channelReportService->sum([['$match'=>$query], ['$group' => array_merge([
            '_id' => null,
            'user_reg' => ['$sum' => '$user_reg'],
//            'user_reg_valid' => ['$sum' => '$user_reg_valid'],
            'user_reg_invalid' => ['$sum' => '$user_reg_invalid'],
            'android_reg' => ['$sum' => '$android_reg'],
            'h5_reg' => ['$sum' => '$h5_reg'],
            'android_app_day' => ['$sum' => '$android_app_day'],
            'app_day' => ['$sum' => '$app_day'],
            'h5_app_day' => ['$sum' => '$h5_app_day'],
            'android_today_app_day' => ['$sum' => '$android_today_app_day'],
            'h5_today_app_day' => ['$sum' => '$h5_today_app_day'],
            'order_num' => ['$sum' => '$order_num'],
            'point_num' => ['$sum' => '$point_num'],
            'pv' => ['$sum' => '$pv'],
            'uv' => ['$sum' => '$uv'],
            'ip' => ['$sum' => '$ip'],
            'click_android' => ['$sum' => '$click_android'],
            'click_ios' => ['$sum' => '$click_ios'],
        ],$group)]]);

        return array(
            'filter' => $filter,
            'items' => empty($items) ? array() : array_values($items),
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalRow'=>value(function()use($moneyCount){
                $totalRow = ['code' => '合计'];
                foreach($moneyCount as $key=>$val){
                    if($key=='_id'){continue;}
                    $totalRow[$key] = formatNum($val,2);
                }
                return $totalRow;
            })
        );
    }

    /**
     * 导出excel
     * @throws BusinessException
     */
    public function exportReportExcel($request)
    {
        $query  = [];
        $filter = [];

        $filter['date'] = $filter['date'] = date('Y-m-d');
        if (!empty($request['code'])) {
            $filter['code'] = $this->getRequest($request, 'code');
            $query['code'] = $filter['code'];
        }
        if (!empty($request['date'])) {
            $filter['date'] = $this->getRequest($request, 'date');
            $query['date'] = $filter['date'];
        }

        $fields = [];
        $count = $this->channelReportService->count($query);
        $items = $this->channelReportService->getList($query, $fields, array('created_at' => -1), 0, $count);

        $data = [];
        foreach ($items as $item){
            $row['code']                     = $item['code'];
            $row['date']                     = $item['date'];

            $row['user_reg']                 = (formatNum($item['user_reg'],2));
//            $row['user_reg_valid']           = (formatNum($item['user_reg_valid'],2));
            $row['user_reg_invalid']         = (formatNum($item['user_reg_invalid'],2));
            $row['app_day']                  = (formatNum($item['app_day'],2));
            $row['yesterday_app_day']        = (formatNum($item['yesterday_app_day'],2));
            $row['today_app_day']            = (formatNum($item['today_app_day'],2));
            $row['order_num']                = (formatNum($item['order_num'],2));
            $row['today_order_num']          = (formatNum($item['today_order_num'],2));
            $row['yesterday_order_num']      = (formatNum($item['yesterday_order_num'],2));
            $row['game_order_num']           = (formatNum($item['game_order_num'],2));
            $row['today_game_order_num']     = (formatNum($item['today_game_order_num'],2));
            $row['yesterday_game_order_num'] = (formatNum($item['yesterday_game_order_num'],2));
            $data[] = $row;
        }

        $cells = [
            'code'                      => '渠道',
            'date'                      => '日期',
            'user_reg'                  => '注册量',
//            'user_reg_valid'            => '有效注册量',
            'user_reg_invalid'          => '无效注册量',
            'app_day'                   => '日活',
            'today_app_day'             => '今日日活',
            'yesterday_app_day'         => '次日日活',
            'order_num'                 => '订单总额',
            'today_order_num'           => '今日订单总额',
            'yesterday_order_num'       => '次日订单总额',
            'game_order_num'            => '游戏订单总额',
            'today_game_order_num'      => '今日游戏订单总额',
            'yesterday_game_order_num'  => '次日游戏订单总额',
        ];

        return $this->commonService->exportExcel($cells, $data, '渠道报表');

    }
}