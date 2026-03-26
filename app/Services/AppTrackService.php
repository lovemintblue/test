<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CommonValues;
use App\Core\Services\BaseService;
use App\Models\AppTrackModel;

/**
 * 数据跟踪
 * @package App\Services
 *
 * @property  AppTrackModel $appTrackModel
 * @property CommonService $commonService
 * @property AgentSystemService $agentSystemService
 * @property UserService $userService
 * @property ReportAdvAppLogService $reportAdvAppLogService
 * @property ReportAdvLogService $reportAdvLogService
 * @property AdvService $advService
 * @property AdvAppService $advAppService
 */
class AppTrackService extends BaseService
{

    /**
     * 获取列表
     * @param array $query
     * @param array $fields
     * @param array $sort
     * @param int $skip
     * @param int $limit
     * @return array
     */
    public function getList($query = array(), $fields = array(), $sort = array(), $skip = 0, $limit = 10)
    {
        return $this->appTrackModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->appTrackModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->appTrackModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->appTrackModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->appTrackModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->appTrackModel->insert($data);
        }
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->appTrackModel->delete(array('_id' => intval($id)));
    }

    /**
     *  数据跟踪接口
     * @param $type
     * @param string $id
     * @param  $name
     * @return mixed
     */
    public function addTrackQueue($userId, $type, $id='', $name='')
    {
        if(in_array($type,array_keys(CommonValues::getAppTrackTypes()))){

            $channelName = '';
            if(!empty($userId)){
                $userInfo = $this->userService->getInfoFromCache($userId);
                $channelName = $userInfo['channel_name'];
            }

            try {
                switch ($type) {
                    case 'ad':
                        $this->reportAdvLogService->inc($id, $name, 'click',$channelName,1);
                        $tmp = $this->advService->advModel->findByID($id);
                        if($tmp){
                            DataCenterService::doAdvClick($tmp['_id'],$tmp['position_code'],$tmp['position_code']);
                        }
                        break;
                    case 'app':
                        $this->reportAdvAppLogService->inc($id, $name, 'click',$channelName,1);
                        $tmp = $this->advAppService->advAppModel->findByID($id);
                        if($tmp){
                            DataCenterService::doAdvAppClick($tmp['_id'],$tmp['position'],$tmp['position']);
                        }
                        break;
                }
            } catch (\Exception $e) {

            }

            $this->getRedis()->lPush('app_track',['type'=>$type,'id'=>$id,'name'=>$name,'user_id'=>$userId,'ip'=>getClientIp(),'time'=>time()]);
        }
        return true;
    }

    /**
     *  数据跟踪接口
     * @return mixed
     */
    public function doTrackQueue()
    {
        $runTime = 297;//可执行时间/秒
        $startTime = time();
        while (true)
        {
            if (time() - $startTime >= $runTime) {
                break;
            }
            $data = $this->getRedis()->rPop('app_track');
            if(empty($data)){
                sleep(5);
                continue;
            }
            if(empty($data['type']) || empty($data['id']) || empty($data['time'])){
                continue;
            }
            if(!in_array($data['type'],array_keys(CommonValues::getAppTrackTypes()))){
                continue;
            }
            $noIdGroups = [
                'buy_vip','buy_point','enter_buy_vip','enter_buy_point','share'
            ];
            if(in_array($data['type'],$noIdGroups)){
                $label = $data['type'];
                $data['id'] = '';
            }else{
                $label = $data['type'].'_'.$data['id'];
            }

            //广告点击上报
            if (in_array($data['type'],['ad','app'])) {
                $this->agentSystemService->track($data['user_id'],$data['id'],$data['type'],$data['ip'],$data['time']);
            }

            $date  = date('Y-m-d',$data['time']);
            $label =  $label.'_'.$date;
            $uid = md5($label);
            $count =$this->appTrackModel->count(['_id'=>$uid]);
            if($count>0){
                $this->appTrackModel->updateRaw(['$inc'=>['num'=>1]],['_id'=>$uid]);
            }else{
                $logData = [
                    '_id'=>$uid,
                    'label'=>$label,
                    'type' => $data['type'],
                    'date'=> $date,
                    'id' => $data['id'],
                    'name'=> strval($data['name']),
                    'num'=>1
                ];
                $this->appTrackModel->insert($logData);
            }
        }
    }
}