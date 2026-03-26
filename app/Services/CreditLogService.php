<?php


namespace App\Services;


use App\Core\Services\BaseService;
use App\Models\CreditLogModel;
use App\Constants\CacheKey;
use App\Utils\GameNameUtil;

/**
 * 积分日志
 * Class AccountService
 * @property CreditLogModel $creditLogModel
 * @property UserService $userService
 * @package App\Services
 */
class CreditLogService extends BaseService
{
    /**
     * 积分兑换物
     * @var array
     */
    public $exchanges = [
        1=>[
            'name'=>'VIP 1天',
            'credit'=>'100',
            'type'=>'vip',
            'add_num'=>'1',
            'object_id'=>'1'
        ],
        2=>[
            'name'=>'VIP 3天',
            'credit'=>'250',
            'type'=>'vip',
            'add_num'=>'3',
            'object_id'=>'1'
        ],
        3=>[
            'name'=>'VIP 7天',
            'credit'=>'600',
            'type'=>'vip',
            'add_num'=>'7',
            'object_id'=>'1'
        ],
        4=>[
            'name'=>'观影券1张',
            'credit'=>'1000',
            'type'=>'movie',
            'add_num'=>'1',
            'object_id'=>''
        ],
        5=>[
            'name'=>'裸聊券1张',
            'credit'=>'2000',
            'type'=>'naked',
            'add_num'=>'1',
            'object_id'=>''
        ],
        6=>[
            'name'=>'观影券3张',
            'credit'=>'2500',
            'type'=>'movie',
            'add_num'=>'3',
            'object_id'=>''
        ],
        7=>[
            'name'=>'VIP 30天',
            'credit'=>'2800',
            'type'=>'vip',
            'add_num'=>'30',
            'object_id'=>'1'
        ],
        8=>[
            'name'=>'至尊VIP 30天',
            'credit'=>'5000',
            'type'=>'vip',
            'add_num'=>'30',
            'object_id'=>'3'
        ]
    ];
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
        return $this->creditLogModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->creditLogModel->count($query);
    }

    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->creditLogModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->creditLogModel->findByID(intval($id));
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->creditLogModel->delete(array('_id' => intval($id)));
    }

    /**
     * 积分增加
     * @param $user
     * @param $type
     * @param int $num
     * @param string $remark
     * @param int $objectId
     * @param string $itemType
     * @param int $addNum
     * @param string $ext
     * @return bool
     */
    public function addCredit($user,$type,$num=10,$remark = '',$objectId = 0,$itemType = '',$addNum = 1,$ext = '')
    {
        $num=intval($num);
        $data = array(
            'user_id'   => intval($user['_id']),
            'username'  => strval($user['username']),
            'type'      => intval($type),
            'num'       => $num,
            'num_log'   => doubleval($user['credit']+$num),//积分余额
            'object_id' => intval($objectId),
            'remark'    => strval($remark),
            'ext'       => strval($ext),
            'label'     => strval(date('Y-m-d')),
            'item_type' => strval($itemType),
            'add_num'   => intval($addNum),
        );
        $result1 = $this->userService->findAndModify(array(
            '_id' => intval($user['_id'])
        ), array(
            '$inc' => array('credit' => $num)
        ), array('_id'));
        if ($result1) {
            $result2 = $this->creditLogModel->insert($data);
            return empty($result2) ? false : true;
        }
        return false;
    }

    /**
     * 减少积分
     * @param $user
     * @param $type
     * @param int $num
     * @param string $remark
     * @param int $objectId
     * @param string $itemType
     * @param int $addNum
     * @param string $ext
     * @return bool
     */
    public function reduceCredit($user,$type,$num=10,$remark = '',$objectId = 0,$itemType = '',$addNum = 1,$ext = '')
    {
        $num=intval($num);
        $data = array(
            'user_id'   => intval($user['_id']),
            'username'  => strval($user['username']),
            'type'      => intval($type),
            'num'       => $num * -1,
            'num_log'   => doubleval($user['credit']-$num),//积分余额
            'object_id' => intval($objectId),
            'remark'    => strval($remark),
            'ext'       => strval($ext),
            'label'     => strval(date('Y-m-d')),
            'item_type' => strval($itemType),
            'add_num'   => intval($addNum),
        );
        $result1 = $this->userService->findAndModify(array(
            '_id' => intval($user['_id'])
        ), array(
            '$inc' => array('credit' => $num * -1)
        ), array('_id'));
        if ($result1) {
            $result2 = $this->creditLogModel->insert($data);
            return empty($result2) ? false : true;
        }
        return false;
    }

    /**
     * 兑换物
     * @param $user
     * @return array
     */
    public function exchange($user)
    {
        $exchange = [];
        foreach($this->exchanges as $key=>$val){
            $exchange[] = [
                'id'=>strval($key),
                'name'=>strval($val['name']),
                'credit'=>strval($val['credit']),
                'can_exchange'=>$user['credit']<$val['credit']?'n':'y'
            ];
        }
        return $exchange;
    }

    /**
     * 兑换物品滚动
     * @param $user
     * @return array
     */
    public function notice($user)
    {
        $keyName = 'credit_notice';
        $notice = getCache($keyName);
        if(is_null($notice)){
            $exchanges = array_keys($this->exchanges);
            $result = [
                ['_id'=> '1', 'object_id' => array_rand($exchanges)],
                ['_id'=> '2', 'object_id' => array_rand($exchanges)],
                ['_id'=> '3', 'object_id' => array_rand($exchanges)],
            ];
            $notice=[];
            foreach($result as $key=>$val){
                $notice[] = [
                    'id'=>strval($val->_id),
                    'name'=>"「".GameNameUtil::getNickname()."」 兑换了奖品  {$this->exchanges[$val['object_id']]['name']}"
                ];
            }
            setCache($keyName,$notice,300);
        }
        return $notice;
    }

}