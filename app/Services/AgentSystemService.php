<?php


namespace App\Services;


use App\Core\Services\BaseService;
use App\Models\AccountLogModel;
use App\Utils\AesUtil;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 * 代理系统
 * Class AccountService
 * @property AccountLogModel $accountLogModel
 * @property UserService $userService
 * @property AdminUserService $adminUserService
 * @package App\Services
 */
class AgentSystemService extends BaseService
{

    /**
     * 执行http post
     * @param $url
     * @param $data
     * @return mixed|null
     */
    public function doHttpPost($url,$data)
    {
        $configs = getConfigs();
        $url = sprintf('%s/cxapi/%s?app_id=%s',$configs['channel_system_url'],$url,$configs['channel_system_app_id']);
        if(is_array($data) || is_object($data)){
            $data= json_encode($data);
        }
        $data = AesUtil::encryptRaw($data, $configs['channel_system_app_key']);
        $result = CommonUtil::httpRaw($url, $data);
        if (empty($result)) {
            return null;
        }
        $result = AesUtil::decryptRaw($result, $configs['channel_system_app_key']);
        if (empty($result)) {
           return null;
        }

        return json_decode($result, true);
    }

    /**
     * 获取广告
     * @param $positionCode
     * @param $userIsVip
     * @param $limit
     * @return array|mixed
     */
    public function getAdvList($positionCode,$userIsVip,$pageSize)
    {
        $result = $this->doHttpPost('ad/list',[
            'position_code'=>strval($positionCode),
            'is_vip'=>strval($userIsVip),
            'method'=>'proxy',
            'page_size'=>strval($pageSize),
        ]);
        if (empty($result) || $result['status'] != 'y') {
            return false;
        }
        return !empty($result['data'])?$result['data']:[];
    }

    /**
     * 获取广告应用
     * @param $positionCode
     * @param $userIsVip
     * @param $limit
     * @return array|mixed
     */
    public function getAdvAppList($page,$pageSize=100)
    {
        $result = $this->doHttpPost('ad/appList', [
            'page'=>strval($page),
            'page_size'=>strval($pageSize),
        ]);
        if (empty($result) || $result['status'] != 'y') {
            return false;
        }
        return !empty($result['data'])?$result['data']:[];
    }

    /**
     * 上报广告点击
     * @param $userId
     * @param $id
     * @param $type
     * @param $ip
     * @param $time
     * @return bool
     */
    public function track($userId,$id,$type,$ip,$time)
    {
        $result = $this->doHttpPost('ad/track',[
            'id'=>strval($id),
            'type'=>strval($type),
            'ip'=>$ip?:getClientIp(),
            'user_id'=>strval($userId?:'-1'),
            'time'=>$time,
        ]);
        return $result['status'] == 'y';
    }

    /**
     * cdn
     * @param $id
     * @return bool
     */
    public function cdn($id)
    {
        $result = $this->doHttpPost('common/cdnDetail',[
            'id'=>strval($id),
        ]);
        if (empty($result) || $result['status'] != 'y') {
            return false;
        }
        return !empty($result['data'])?$result['data']:[];
    }

    /**
     * 签到
     * @param $act
     * @param $username
     * @return bool
     */
    public function adminLogs($act,$username='',$ip='')
    {
        $token = $this->adminUserService->getToken();
        $result = $this->doHttpPost('common/adminLogs',[
            'user'=>strval($username?:$token['username']),
            'act'=>strval($act),
            'ip'=>$ip?:getClientIp(),
        ]);
        if (empty($result) || $result['status'] != 'y') {
            return false;
        }
        return true;
    }

    /**
     * ip白名单
     * @return bool
     */
    public function ipWhitelist()
    {
        $result = $this->doHttpPost('common/ipWhitelist',[]);
        if (empty($result) || $result['status'] != 'y') {
            return false;
        }
        return !empty($result['data'])?$result['data']:[];
    }

    /**
     * 日活数据
     * @return bool
     */
    public function reportDayLog($data)
    {
        $result = $this->doHttpPost('channel/reportDayLog',$data);
        if (empty($result) || $result['status'] != 'y') {
            return false;
        }
        return true;
    }

    /**
     * 用户数据
     * @return bool
     */
    public function reportUser($data)
    {
        $result = $this->doHttpPost('channel/reportUser',$data);
        if (empty($result) || $result['status'] != 'y') {
            return false;
        }
        return true;
    }

    /**
     * 订单数据
     * @return bool
     */
    public function reportOrder($data)
    {
        $result = $this->doHttpPost('channel/reportOrder',$data);
        if (empty($result) || $result['status'] != 'y') {
            return false;
        }
        return true;
    }

    /**
     * 获取所有渠道统计信息
     * @return bool
     */
    public function getDayLogs($data=[])
    {
        $result = $this->doHttpPost('channel/getDayLogs',$data);
        if (empty($result) || $result['status'] != 'y') {
            return false;
        }
        return !empty($result['data'])?$result['data']:[];
    }

    /**
     * 根据ip获取当前的渠道码
     * @param $data
     * @return array|false|mixed
     */
    public function getCodeByIp($data=[])
    {
        $result = $this->doHttpPost('channel/getCodeByIp',$data);
        if (empty($result) || $result['status'] != 'y') {
            return false;
        }
        return !empty($result['data'])?$result['data']:[];
    }

}