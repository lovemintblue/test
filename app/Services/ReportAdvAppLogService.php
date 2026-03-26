<?php

namespace App\Services;

use App\Core\Services\BaseService;
use App\Models\ReportAdvAppLogModel;

/**
 * 应用广告统计
 *
 * @property  ReportAdvAppLogModel $reportAdvAppLogModel
 */
class ReportAdvAppLogService extends BaseService
{
    /**
     * @param $advId
     * @param $advName
     * @param $field
     * @param $channelName
     * @param $value
     * @return void
     */
    public function inc($advId, $advName, $field, $channelName,$value = 1)
    {
        $this->do($advId, $advName, $field, '_all',$value);

        if(!empty($channelName)){
            $this->do($advId, $advName, $field,$channelName,$value);
        }
    }

    /**
     * @param $advId
     * @param $advName
     * @param $field
     * @param $channelName
     * @param $value
     * @return true|void
     */
    private function do($advId, $advName, $field, $channelName,$value = 1)
    {
        $advId = intval($advId);
        if (!in_array($field, ['click'])) {
            return;
        }
        $label = date('Y-m-d');
        $idValue = md5($label . '_'.$channelName.'_'. $advId);
        $this->reportAdvAppLogModel->findAndModify([
            '_id' => $idValue,
        ], [
            '$set' => [
                'name' => $advName,
                'updated_at' => time(),
            ],
            '$inc' => [
                $field => $value
            ],
            '$setOnInsert' => [
                '_id' => $idValue,
                'adv_id' => $advId,
                'label' => $label,
                'channel_name' => $channelName,
                'created_at' => time(),
            ]
        ], [], true);
        return true;
    }

    /**
     * @param $date
     * @param $field
     * @param $channelName
     * @return int
     */
    public function getFieldCount($date,$field,$channelName)
    {
        $count = $this->reportAdvAppLogModel->aggregate([
            [
                '$match' => [
                    'label'=>$date,
                    'channel_name'=>$channelName
                ]
            ],
            [
                '$group' => [
                    '_id' => null,
                    'count' => ['$sum' =>'$'.$field]
                ]
            ]
        ]);
        return intval($count->count??0);
    }
}
