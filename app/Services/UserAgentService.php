<?php


namespace App\Services;


use App\Core\Services\BaseService;
use App\Models\AccountLogModel;
use App\Models\UserAgentAccountModel;
use App\Models\UserAgentModel;
use App\Utils\CommonUtil;

/**
 * Class UserAgentService
 * @package App\Services
 * @property UserAgentModel $userAgentModel
 * @property UserAgentAccountModel $userAgentAccountModel
 * @property UserService $userService
 */
class UserAgentService extends BaseService
{
    /**
     * 业绩分成比例 key=层级 value=比例
     * @var array
     */
    public static $_bill=[
        1=>100,
        2=>10,
        3=>5,
    ];

    /**
     * 收益计算比例 key=收益区间 value=返利比
     * @var array
     */
    public static $_income=[
        ['start'=>0,'rate'=>55],
        ['start'=>5000,'rate'=>65],
        ['start'=>30000,'rate'=>75],
    ];

    /**
     * 获取余额操作类型
     * @param null $value
     * @return string|string[]
     */
    public static function getAccType($value = null)
    {
        $arr = array(
            '1' => '代理收入',
            '2' => '提现',
            '3' => '提现退款',
            '4' => '游戏提现',
            '5' => '游戏购买',
        );
        if ($value === null || $value === "") {
            return $arr;
        }
        return $arr[$value];
    }

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
        return $this->userAgentModel->find($query, $fields, $sort, $skip, $limit);
    }


    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->userAgentModel->count($query);
    }



    /**
     * 查找并更新数据
     * @param array $query
     * @param array $update
     * @param array $fields
     * @param bool $upsert
     * @return mixed
     */
    public function findAndModify($query = array(), $update = array(), $fields = array(), $upsert = false)
    {
        return $this->userAgentModel->findAndModify($query, $update, $fields, $upsert);
    }

    /**
     * @param $userId
     * @return mixed
     */
    public function findById($userId)
    {
        return $this->userAgentModel->findByID(intval($userId));
    }

    /**
     *
     * @param $username
     * @return array
     */
    public function findByUsername($username)
    {
        return $this->userAgentModel->findFirst(['username'=>$username]);
    }







    /**
     * 用户分销
     * @param $userId
     * @return bool|void
     */
    public function userMLM($userId)
    {
        $userInfo=$this->userService->findByID($userId);
        //获取一级上级,直接上级
        if($userInfo['parent_id']){
            $userInfoOne=$this->userService->findByID(intval($userInfo['parent_id']));
        }
        if(!isset($userInfoOne)){return;}
        $agentInfoOne=$this->findById($userInfoOne['_id']);
        if(!$agentInfoOne){
            $this->save($userInfoOne['_id'],$userInfoOne['username'],1);
        }else{
            $this->userAgentModel->updateRaw(['$inc'=>['level1_num'=>1]],['_id'=>intval($userInfoOne['_id'])]);
        }

        //获取二级上级
        if($userInfoOne['parent_id']){
            $userInfoTwo=$this->userService->findByID(intval($userInfoOne['parent_id']));
        }
        if(!isset($userInfoTwo)){return;}
        $agentInfoTwo=$this->findById($userInfoTwo['_id']);
        if(!$agentInfoTwo){
            $this->save($userInfoTwo['_id'],$userInfoTwo['username'],2);
        }else{
            $this->userAgentModel->updateRaw(['$inc'=>['level2_num'=>1]],['_id'=>intval($userInfoTwo['_id'])]);
        }

        //获取三级上级
        if($userInfoTwo['parent_id']){
            $userInfoThree=$this->userService->findByID(intval($userInfoTwo['parent_id']));
        }
        if(!isset($userInfoThree)){return;}
        $agentInfoThree=$this->findById($userInfoThree['_id']);
        if(!$agentInfoThree){
            $this->save($userInfoThree['_id'],$userInfoThree['username'],3);
        }else{
            $this->userAgentModel->updateRaw(['$inc'=>['level3_num'=>1]],['_id'=>intval($userInfoThree['_id'])]);
        }
        return true;
    }

    /**
     * 订单分销
     * @param $userId
     * @param $money
     * @return bool|void
     */
    public function orderMLM($userId,$money)
    {
        if(empty($userId)||empty($money)){
            return false;
        }
        $userInfo=$this->userService->findByID($userId);
        //获取一级上级,直接上级
        if($userInfo['parent_id']){
            $userInfoOne=$this->userService->findByID(intval($userInfo['parent_id']));
        }
        if(!isset($userInfoOne)){return;}
        $agentInfoOne=$this->save($userInfoOne['_id'],$userInfoOne['username'],1);
        //业绩
        $bill=$this->bill($money,1);
        //收益
        $balance=$this->income($agentInfoOne['bill']+$bill,$bill);
//        $this->userAgentModel->updateRaw(['$inc'=>['bill'=>$bill,'amount'=>$balance,'balance'=>$balance,'level1_bill'=>$bill]],['_id'=>$userInfoOne['_id']]);
        $this->addBalance($agentInfoOne,$bill,$balance,1,1);


        //获取二级上级
        if($userInfoOne['parent_id']){
            $userInfoTwo=$this->userService->findByID(intval($userInfoOne['parent_id']));
        }
        if(!isset($userInfoTwo)){return;}
        $agentInfoTwo=$this->save($userInfoTwo['_id'],$userInfoTwo['username'],2);
        //业绩
        $bill=$this->bill($money,2);
        //收益
        $balance=$this->income($agentInfoTwo['bill']+$bill,$bill);
//        $this->userAgentModel->updateRaw(['$inc'=>['bill'=>$bill,'amount'=>$balance,'balance'=>$balance,'level2_bill'=>$bill]],['_id'=>$userInfoTwo['_id']]);
        $this->addBalance($userInfoTwo,$bill,$balance,2,1);


        //获取三级上级
        if($userInfoTwo['parent_id']){
            $userInfoThree=$this->userService->findByID(intval($userInfoTwo['parent_id']));
        }
        if(!isset($userInfoThree)){return;}
        $agentInfoThree=$this->save($userInfoThree['_id'],$userInfoThree['username'],3);
        //业绩
        $bill=$this->bill($money,3);
        //收益
        $balance=$this->income($agentInfoThree['bill']+$bill,$bill);
        $this->addBalance($agentInfoThree,$bill,$balance,3,1);
//        $this->userAgentModel->updateRaw(['$inc'=>['bill'=>$bill,'amount'=>$balance,'balance'=>$balance,'level3_bill'=>$bill]],['_id'=>$agentInfoThree['_id']]);
        return true;
    }

    /**
     * 保存代理商
     * @param $userId
     * @param $username
     * @param $level
     * @return bool|int
     */
    public function save($userId,$username,$level)
    {
        $userId=intval($userId);
        $info=$this->findById($userId);
        if(!$info){
            $info=[
                '_id'=>$userId,
                'username'=>$username,
                'balance'=>0,
                'amount'=>0,
                'bill'=>0,
                'level1_num'=>$level==1?1:0,
                'level2_num'=>$level==2?1:0,
                'level3_num'=>$level==3?1:0,
                'level1_bill'=>0,
                'level2_bill'=>0,
                'level3_bill'=>0,
            ];
            $this->userAgentModel->insert($info);
        }
        return $info;
    }


    /**
     * 计算业绩
     * @param $money 本次金额
     * @param int $level 层级
     * @return float|int
     */
    private function bill($money,int $level)
    {
        return $money*self::$_bill[$level]/100;
    }

    /**
     * 计算收益
     * @param $totalBill 累计总业绩
     * @param $money 本次业绩金额
     * @return int|mixed
     */
    private function income($totalBill,$money)
    {
        $income=array_column(self::$_income,'start');
        array_push($income,$totalBill);
        $income = array_unique($income);
        sort($income);
        $index=array_search($totalBill,$income);
        $index=$index-1;
        if($index<0){return 0;}
        return self::$_income[$index]['rate']*$money/100;
    }
}