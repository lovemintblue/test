<?php

declare(strict_types=1);

namespace App\Jobs\Common;

use App\Jobs\BaseJob;
use App\Services\AdminUserService;
use App\Services\AnalysisAdminLogsService;

/**
 * 管理员日志统计
 * Class AdminLogsJob
 * @property AdminUserService $adminUserService
 * @property AnalysisAdminLogsService $analysisAdminLogsService
 * @package App\Jobs\Common
 */
class AdminLogsJob extends BaseJob
{
    public function handler($uniqid)
    {
        // TODO: Implement handler() method.
        for ($i=1;$i>0;$i--){
            $nowTime = time();
            $startTime = strtotime(date("Y-m-d",strtotime("-{$i}day")));
            $endTime = $startTime+24*60*60;
            $query = ['created_at'=>['$gte'=>$startTime,'$lt'=>$endTime],];
            $rows = $this->adminUserService->getLogList($query, [], ['_id'=>1], 0, 10000);
            $actionArr = [];
            foreach($rows as $row){
                if($row['content']=='用户登录!'){
                    $content['_url'] = 'login';
                }else{
                    preg_match('/{.*}/', $row['content'], $match);
                    if(empty($match[0])){continue;}
                    $content = json_decode($match[0],true);
                }
                ++$actionArr["{$row['admin_id']}_{$row['admin_name']}"][$content['_url']];
            }
            if(empty($actionArr)){continue;}
            foreach($actionArr as $key=>$val){
                list($adminId,$adminName) = explode('_',$key);
                $dateLabel = date('Y-m-d',$startTime);
                $idValue = md5($adminId.'_'.$dateLabel);
                $this->analysisAdminLogsService->analysisAdminLogsModel->findAndModify(['_id' => $idValue],[
                    '_id'         => $idValue,
                    'admin_id'    => intval($adminId),
                    'admin_name'  => strval($adminName),
                    'date_label'  => strval($dateLabel),
                    'num'         => intval(array_sum($val)),
                    'content'     => value(function()use($val){
                        $content = [];
                        foreach($val as $k=>$v){
                            $content[] = [
                                '_url'=>strval($k),
                                'num'=>strval($v)
                            ];
                        }
                        array_multisort(array_column($content,'num'),SORT_DESC,$content);
                        return $content;
                    }),
                    'created_at' => $endTime,
                    'updated_at' => $nowTime
                ],['_id'],true);
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