<?php


namespace App\Jobs\Common;


use App\Jobs\BaseJob;
use App\Models\UserModel;
use App\Services\UserGroupService;
use App\Utils\LogUtil;
use App\Utils\UserSign;

/**
 * Class UserJob
 * @property UserGroupService $userGroupService
 * @property UserModel $userModel
 * @package App\Jobs\Common
 */
class UserJob extends BaseJob
{
    public $rows;
    public function __construct($rows)
    {
        $this->rows=$rows;
    }
    public function handler($uniqid)
    {
        // TODO: Implement handler() method.
        $groups = $this->userGroupService->getAll();
        foreach ($this->rows as $row) {
            $row=[
                '_id'       => intval($row['id']),
                'nickname'  => strval($row['nickname']),
                'username'  => strval($row['username']),
                'sign'=>UserSign::getSign(),
                'phone'     => $row['phone']?strval($row['phone']):uniqid('system_'),
                'country_code'     => '',
                'device_id' => strval($row['device_id']),
                'device_type' => strval(strtolower($row['device_type'])),
                'device_version' => '1.6',
                'device_ext' => strval($row['device_ext']),
                'password' => strval($row['password']),
                'slat' => strval(mt_rand(1000, 9000)),
                'balance' => intval($row['balance']),
                'credit' => intval(0),
                'sing' => strval(''),
                'is_disabled' => intval($row['is_disabled']),
                'error_msg' => '',
                'img' => strval($row['img']),
                'bg_img' => strval(''),
                'group_id' => $this->groupId($row['group_id']),
                'group_rate'=>'',
                'group_name'=>'',
                'group_start_time'=>intval($row['group_start_time']),
                'group_end_time'=>intval($row['group_end_time']),
                'level'     =>0,
                'sex'=>intval($row['sex']),
                'parent_name'=>strval($row['parent_name']),
                'parent_id'=>intval($row['parent_id']),
                'channel_name'=>strval($row['channel_name']),
                'register_at'=>intval($row['register_at']),
                'register_date'=>strval(date("Y-m-d",$row['register_at'])),
                'register_ip'=>strval($row['register_ip']),
                'login_num'=>intval($row['login_num']),
                'last_at'=>intval($row['last_at']),
                'last_date'=>strval(date("Y-m-d",$row['last_at'])),
                'last_ip'=>strval($row['last_ip']),
                'share_num'=>intval($row['share_num']),
                'fans'=>0,
                'follow'=>0,
                'gift_count'=>0,
                'send_count'=>0,
                'country'=>'',
                'province'=>'',
                'city'=>'',
                'location'=>'',
                'register_area'=>'',
                'is_china'=>'',
                'withdraw_info'=>[],
                'tag'=>[],
                'is_system'=>0,
                'right'=>[],
                'created_at'=>intval($row['register_at']),
                'updated_at'=>intval($row['updated_at']),
            ];
//                $ipInfo = $this->ipService->parse($row['register_ip']);
//                $area = $this->ipService->getProvinceAndCity($ipInfo);
            $ipInfo=[];
            $area='';
            $group=isset($groups[$row['group_id']]) ? $groups[$row['group_id']]:[];
            $row['group_rate']=$group?intval($group['rate']):100;
            $row['group_name']=$group?$group['name']:'';
            $row['level']   =$group?intval($group['level']):0;
            $row['country'] =$ipInfo['country']?:'';
            $row['province'] =$ipInfo['province']?:'';
            $row['city'] =$ipInfo['city']?:'';
            $row['location'] ='';
            $row['register_area'] =$area?:'';
            $row['is_china'] =1;
            try {
                $this->userModel->findAndModify(['_id'=>$row['_id']],$row,[],true);//注意,会与账号找回系统冲突
//                $this->userModel->findAndModify(['device_id'=>$row['device_id']],$row,[],true);
            }catch (\Exception $e){
                LogUtil::error(sprintf('%s in %s line %s',$e->getMessage(), $e->getFile(),$e->getLine()));
            }
        }
        LogUtil::info(sprintf('Import  user id=>%s', $row['_id']));
    }
    /**
     * 用户组映射
     * @param $id
     * @return int
     */
    public function groupId($id)
    {
        switch ($id){
            case 1://VIP体验卡
                return 1;
                break;
            case 2://月度VIP
                return 3;
                break;
            case 3://季度VIP
                return 4;
                break;
            case 4://年度VIP
                return 5;
                break;
            case 5://终身VIP
                return 6;
                break;
            case 6://新人专享
                return 2;
                break;
            case 7://游戏卡
                return 7;
                break;
            case 8://约会卡
                return 8;
                break;
            case 9://活动限时约会卡
                return 8;
                break;
            case 11://月度VIP
                return 3;
                break;
            case 13://至尊卡
                return 9;
                break;
            default:
                return 0;
                break;
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