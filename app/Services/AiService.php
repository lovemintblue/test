<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Jobs\Common\UploadImageSizeJob;
use App\Models\AiModel;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 * Ai
 * @package App\Services
 *
 * @property  AiModel $aiModel
 * @property  AiMessageService $aiMessageService
 * @property  AccountService $accountService
 * @property  CommonService $commonService
 * @property  UserService $userService
 * @property  AiResourceTemplateService $aiResourceTemplateService
 * @property  JobService $jobService
 * @property  UserBalanceService $userBalanceService
 * @property  MrsSystemService $mrsSystemService
 */
class AiService extends BaseService
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
        return $this->aiModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->aiModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->aiModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->aiModel->findByID($id);
    }

    /**
     * @param $pipeline
     * @return mixed
     */
    public function sum($pipeline)
    {
        return $this->aiModel->aggregate($pipeline);
    }

    /**
     * @param $pipeline
     * @return mixed
     */
    public function aggregates($pipeline)
    {
        return $this->aiModel->aggregates($pipeline);
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->aiModel->update($data, array("_id" => $data['_id']));
        } else {
            if(empty($data['_id'])){
                $data['_id'] = substr(CommonUtil::getId(),8,16);
            }
            return $this->aiModel->insert($data);
        }
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->aiModel->delete(array('_id' => $id));
    }

    /**
     * 订单保存
     * @param $userId
     * @param $data
     * @return true
     * @throws BusinessException
     */
    public function doSave($userId,$data)
    {
        $userModel = $this->userService->findByID($userId);
        $this->userService->checkUser($userModel);
        $money  = $data['num'] * $data['money'];
        if($money>$userModel['balance']){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, sprintf('当前可用金币%s个,数量不足!',$userModel['balance']));
        }

        $saveData = [
            'order_sn'  => CommonUtil::createOrderNo('AI'),
            'user_id' => intval($userId),
            'device_type' => $userModel['device_type'],
            'username' => $userModel['username'],
            'extra' => $data['extra'],
            'out_data' => [],
            'money' => intval($money),
            'real_money' => 0,
            'position' => strval($data['position']),
            'status' => 2,//待处理
            'num' => $data['num'],
            'channel_name' => strval($userModel['channel_name']),
            'register_at' => $userModel['register_at'] * 1,
            'register_date' => date('Y-m-d', $userModel['register_at'] * 1),
            'is_new_user' => $this->userService->isNewUser($userModel) ? 1 : 0,
            'register_ip' => $userModel['register_ip'],
            'created_ip' => getClientIp(),
            'is_disabled' => 0
        ];
        $id = $this->save($saveData);
        if(empty($id)){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '购买失败!');
        }

        $remark = sprintf("%s消耗:%s金币",$data['remark'],$money);
        $result = $this->accountService->reduceBalance($userModel,$saveData['order_sn'],$money,3,$remark,json_encode(['ai_id'=>$id]));
        if(empty($result)){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '购买失败!');
        }
        DataCenterService::doReduceBalance($id,$remark,$money,$userModel['balance'],($userModel['balance']-$money),'content_purchase',$saveData['order_sn'],time());
        $this->aiModel->updateRaw(array('$set'=>array('real_money'=>$saveData['money'])),array('_id'=>$id));
        $this->userService->setInfoToCache($userModel['_id']);

        $this->aiResourceTemplateService->handler(['action' => 'buy', 'template_id' => $data['extra']['template_id']]);
        if(!empty($data['extra']['source-path'])){
            $this->jobService->create(new UploadImageSizeJob('ai',$data['extra']['source-path'],$id),'mongodb');
        }
        return true;
    }

    /**
     * 我的作品
     * @param $userId
     * @param $position
     * @param $page
     * @param $pageSize
     * @return array
     */
    public function getMy($userId,$position,$page=1,$pageSize=20)
    {
        $query = [
            'user_id'=>intval($userId),
            'position'=>strval($position),
            'status'=>['$ne'=>-2]
        ];
        $rows = $this->getList($query,[],['created_at'=>-1],($page-1)*$pageSize,$pageSize);

        $result = [];
        foreach ($rows as $row) {
            $extra = (array)$row['extra'];
            $result[] = array(
                'id'            => strval($row['_id']),
                'title'         => strval($extra['description']),
                'img_x'         => $this->commonService->getCdnUrl($extra['source-path']?:$extra['target-path']),
                'content'       => '',
                'position'      => strval($row['position']),
                'status'        => value(function ()use($row){
                    $status = 'done';
                    if (in_array($row['status'],[2,3])) {
                        $status = 'processing';
                    } elseif ($row['status'] == -1) {
                        $status = 'fail';
                    }
                    return $status;
                }),
                'width'  => strval(0),
                'height' => strval(0),
                'time'   => CommonUtil::ucTimeAgo($row['created_at']),
            );
        }
        return $result;
    }

    /**
     * 搜索
     * @param $userId
     * @param array $filter
     * @return array|mixed
     */
    public function doSearch($userId,$filter = [])
    {
        $page = $filter['page'] ?: 1;
        $pageSize = $filter['page_size'] ?: 16;
        $homeId   = strval($filter['home_id']);
        $position = strval($filter['position']);
        $from       = ($page - 1) * $pageSize;
        $query    = ['is_disabled'=>0];

        if($homeId!=$userId){
            $query['status'] = 1;
        }
        if(!empty($homeId)){
            $query['user_id'] = intval($homeId);
        }
        if(!empty($position)){
            $query['position'] = $position;
        }

        $rows = $this->getList($query,[],['created_at'=>-1],$from,$pageSize);
        $result = [];
        foreach ($rows as $row) {
            $extra = (array)$row['extra'];
            $result[] = array(
                'id'            => strval($row['_id']),
                'title'         => strval($extra['description']),
                'img_x'         => $this->commonService->getCdnUrl($extra['source-path']?:$row['out_data'][0]),
                'content'       => value(function()use($row){
                    if($row['position']!='novel'){return '';}
                    $content = '';
                    if(!empty($row['out_data'])){
                        $content = mb_substr($row['out_data'][0],0,50,'utf-8').'...';
                    }
                    return $content;
                }),
                'position'      => strval($row['position']),
                'status'        => value(function ()use($row){
                    $status = 'done';
                    if (in_array($row['status'],[2,3])) {
                        $status = 'processing';
                    } elseif ($row['status'] == -1) {
                        $status = 'fail';
                    }
                    return $status;
                }),
                'width'  => strval($extra['width']?:16),
                'height' => strval($extra['height']?:9),
                'time'   => CommonUtil::ucTimeAgo($row['created_at']),
            );
        }
        return $result;
    }

    /**
     *  获取详情
     * @param $id
     * @return mixed
     */
    public function getDetail($id)
    {
        if(empty($id)){return null;}
        $keyName = "ai_detail_{$id}";
        $result = null;
        if (is_null($result)) {
            $result = $this->findByID($id);
            setCache($keyName, $result, mt_rand(90, 120));
        }
        return empty($result)?null:$result;

    }

    /**
     * 删除所有
     * @param $userId
     * @return mixed
     */
    public function deleteAll($userId)
    {
        return $this->aiModel->delete(['user_id'=>intval($userId)]);
    }

    /**
     * ai相关配置
     * @return array
     */
    public function getConfigs()
    {
        //AI系统配置
        $keyName = 'ai_configs';
        $cacheConfigs = container()->get('redis')->get($keyName);
        $cacheConfigs = !empty($cacheConfigs)?json_decode($cacheConfigs,true):[];
        //本地配置
        $configs = getConfigs();
        $result = array(
            //图片换脸
            'ai_face_image_open' => 'y',
            'ai_face_image_price' => strval($configs['ai_face_image_price']*1),

            //视频换脸
            'ai_face_video_open' => strval($cacheConfigs['data']['face_video_open'] ?? 'n'),
//            'ai_face_video_price'=> strval($configs['ai_face_video_price']*1),

            //去衣
            'ai_undress_open'   => 'y',
            'ai_undress_price'  => strval($configs['ai_undress_price']*1),
            'ai_undress_method' => value(function()use($cacheConfigs){
                $methodArr = $cacheConfigs['data']['undress_method']??[];
                foreach($methodArr as &$method){
                    if($method['id']=='method_1'){
                        $method['name'] = '多人模式';
                    }elseif($method['id']=='method_2'){
                        $method['name'] = '单人模式';
                    }
                }
                return $methodArr;
            }),
            'ai_undress_examples' => value(function()use($configs){
                $exampleArr = json_decode($configs['ai_undress_example'],true)??[];
                $aiUndressExample = [];
                foreach($exampleArr as $example){
                    $aiUndressExample[] = [
                        'link'=>'',
                        'image_url'=>$this->commonService->getCdnUrl($example)
                    ];
                }
                return $aiUndressExample;
            }),

            //换装
            'ai_change_open'   => 'y',
//            'ai_change_price'  => strval($configs['ai_change_price']*1),

            //绘画
            'ai_generate_open'   => 'y',
//            'ai_generate_price' => strval($configs['ai_generate_price']*1),
//            'ai_generate_porn_price' => strval($configs['ai_generate_porn_price']*1),
            'ai_generate_size' => $cacheConfigs['data']['generate_size']??[],

            //小说
            'ai_novel_open'   => 'y',
            'ai_novel_price' => strval($configs['ai_novel_price']*1),
            'ai_novel_method' => $cacheConfigs['data']['novel_method']??[],

            //表情
            'ai_emoji_open' => strval($cacheConfigs['data']['bq_open'] ?? 'n'),
//            'ai_emoji_price' => strval($configs['ai_emoji_price']*1),

            'ai_tips' => strval($configs['ai_tips']),
            'ai_chat_tips'  => $configs['ai_chat_price']>0?"每次发送信息将收取{$configs['ai_chat_price']}金币":'',
            'ai_chat_model' => array_values($this->aiMessageService->getAiModels($configs['ai_chat_model'])),
        );

        //上传用
        $result['upload_image_url'] = $this->commonService->getUploadImageUrl($configs);
        $result['upload_file_url'] = $this->commonService->getUploadFileUrl($configs);
        $result['upload_file_query_url'] = $this->commonService->getUploadFileQueryUrl($configs);
        $result['upload_file_max_length'] =strval( 600*1024*1024);
        $result['upload_image_max_length'] =strval( 1*1024*1024);

        return $result;
    }

    /**
     * 处理失败退款-仅退款
     * @param $row
     * @param $refundFee
     * @return bool
     */
    public function refund($row,$refundFee)
    {
        if(empty($row)||empty($refundFee)){
            return false;
        }
        $user = $this->userService->findByID($row['user_id']);
        if($user){
            $remark = sprintf("AI退款%s金币",$refundFee);
            $this->accountService->addBalance($user,$row['order_sn'],$refundFee,4,$remark,json_encode(['ai_id'=>$row['_id']]));
            $this->userService->setInfoToCache($user['_id']);
            LogUtil::error("id:{$row['_id']} remark:{$remark}");
        }
        return true;
    }

    /**
     * ai女友授权url
     * @param $userId
     * @return array|false|mixed
     * @throws BusinessException
     */
    public function getGirlFriendAuthUrl($userId)
    {
//        $row = $this->userBalanceService->findByID($userId);
//        if(!empty($row)&&!in_array($row['status'],[1,3])){
//            throw new BusinessException(StatusCode::DATA_ERROR, '状态错误，请稍后再尝试!');
//        }

        $userInfo = $this->userService->findByID($userId);
        $this->userService->checkUser($userInfo);
        $configs = getConfigs();
        $config = container()->get('config');
        $rate = $config->app->rate??1;
        $username = $userInfo['register_at']>strtotime('2025-09-07')?$userInfo['username']:$userInfo['_id'];

        $data = [
            'username'=>$config->app->name.'_'.$username,
            'asset'=>sprintf("%.2f",$userInfo['balance']*$rate),
            'currency'=>'CNY',
            'nickname'=>strval($userInfo['nickname']),
            'theme'=>'light',
            'user_avatar'=>$configs['media_url_open'].$userInfo['img'],
            'logo_url'=>$configs['media_url_open'].$config->app->ai_girlfriend_logo??'',
        ];
        $result = $this->mrsSystemService->getLsjGirlFriendAuthUrl($data);

//        $keyName = 'ai_girlfriend_auth_url_'.$userId;
//        $result['auth_url'] = $this->getRedis()->get($keyName);
//        if(empty($result['auth_url'])){
//            $result = $this->mrsSystemService->getLsjGirlFriendAuthUrl($data);
//            $this->getRedis()->set($keyName, $result['auth_url'], 30*60*60);
//        }else{
//            $result = $this->mrsSystemService->doLsjGirlFriendBringInAssets($data);
//            print_r($result);exit;
//        }

        if(empty($result['auth_url'])){
            throw new BusinessException(StatusCode::DATA_ERROR, '权限获取失败,请稍后再尝试!');
        }

        $this->aiModel->getConnection()->startTransaction();

        if($userInfo['balance']>0){
            $result1 = $this->userService->userModel->updateRaw(['$inc'=> ['balance'=>-$userInfo['balance'],]],['_id'=>intval($userId)]);
        }else{
            $result1 = true;
        }

        $result2 = $this->userBalanceService->save($userInfo,'ai_girlfriend',json_encode($data,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        if(empty($result1)||empty($result2)){
            $this->aiModel->getConnection()->abortTransaction();
            $this->mrsSystemService->doLsjGirlFriendBringOutAssets(['username'=>$config->app->name.'_'.$username]);
            throw new BusinessException(StatusCode::DATA_ERROR, '创建链接失败!');
        }

        $this->aiModel->getConnection()->commitTransaction();
        $this->userService->setInfoToCache($userId);

        return $result;
    }

    /**
     * 带出余额
     * @param $userId
     * @param $row
     * @return bool
     * @throws BusinessException
     */
    public function girlFriendBringOutAssets($userId,$row=null)
    {
        if(empty($row)){
            $row = $this->userBalanceService->findByID($userId);
            if($row['status']!=1){return false;}
        }elseif($row['status']!=2){
            return false;
        }

        $userInfo = $this->userService->findByID($userId);
        $this->userService->checkUser($userInfo);
        $config = container()->get('config');
        $rate = $config->app->rate??1;
        $username = $userInfo['register_at']>strtotime('2025-09-07')?$userInfo['username']:$userInfo['_id'];

        $order = [];
        if($row['balance']>0){
            $result = $this->mrsSystemService->doLsjGirlFriendBringOutAssets(['username'=>$config->app->name.'_'.$username]);
            if(empty($result)&&$row['error_num']>=2){
                $this->userBalanceService->userBalanceModel->update(['error_msg'=>'下分接口不通!','status'=>3],['_id'=>$userId]);
                return false;
            }elseif(empty($result)){
                $this->userBalanceService->userBalanceModel->updateRaw(['$inc'=>['error_num'=>1]],['_id'=>$userId]);
                return false;
            }
            $order = $this->mrsSystemService->getLsjGirlFriendOrderLogs([
                'username'=>$config->app->name.'_'.$username,
                'page'=>'1',
                'page_size'=>'1000',
                'start_time'=>date('Y-m-d H:i:s',$row['created_at']),
            ]);
        }

        $this->aiModel->getConnection()->startTransaction();

        $items = $order['items']?array_reverse($order['items']):[];
        foreach($items as $item){
            if($row['created_at']>strtotime($item['created_at'])){continue;}
            $money = $item['amount'] / $rate;
            $row['balance'] -= $money;
            //ai订单表
            $saveData = [
                'order_sn'  => $item['id'],
                'user_id' => intval($userId),
                'device_type' => $userInfo['device_type'],
                'username' => $userInfo['username'],
                'extra' => [
                    'content'=>"类型：{$item['type_name']}\n角色ID：{$item['role_id']}\n角色名：{$item['role_name']}"
                ],
                'out_data' => [
                    'content'=>$item['remark']
                ],
                'money' => doubleval($money),
                'real_money' => doubleval($money),
                'position' => 'ai_girlfriend',
                'status' => 1,
                'num' => 1,
                'channel_name' => strval($userInfo['channel_name']),
                'register_at' => $userInfo['register_at'] * 1,
                'register_date' => date('Y-m-d', $userInfo['register_at'] * 1),
                'is_new_user' => $this->userService->isNewUser($userInfo) ? 1 : 0,
                'register_ip' => $userInfo['register_ip'],
                'created_ip' => '',
                'is_disabled' => 0,
            ];
            if(!$this->save($saveData)){
                $this->aiModel->getConnection()->abortTransaction();
                return false;
            }
            //余额日志
            $data = array(
                'order_sn' => $item['id'],
                'user_id' => $userInfo['_id'],
                'username' => $userInfo['username'],
                'num' => doubleval($money * -1),
                'num_log'=>doubleval($row['balance']),//余额
                'type' => intval(3),//余额类型 getAccountLogsType
                'record_type' => 'point',
                'label' => date('Y-m-d',strtotime($item['created_at'])),
                'remark' => sprintf("AI女友消耗:%s金币",$money).'-'.$item['remark'],
                'ext' => json_encode(['role_id'=>$item['role_id'],'type_name'=>$item['type_name'],'role_name'=>$item['role_name']],JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            );
            if(!$this->accountService->accountLogModel->insert($data)){
                $this->aiModel->getConnection()->abortTransaction();
                return false;
            }

        }

        if($row['balance']>0){
            $result1 = $this->userService->userModel->updateRaw(['$inc'=> ['balance'=>doubleval($row['balance'])]],['_id'=>intval($userId)]);
        }else{
            $result1 = true;
        }

        $result2 = $this->userBalanceService->delete($userId);

        if(empty($result1)||empty($result2)){
            $this->aiModel->getConnection()->abortTransaction();
            return false;
        }

        $this->aiModel->getConnection()->commitTransaction();
        $this->userService->setInfoToCache($userId);
        return true;
    }

    /**
     * 棋牌游戏授权url
     * @param $userId
     * @param $deviceType
     * @return array|false|mixed
     * @throws BusinessException
     */
    public function getGameQpAuthUrl($userId,$deviceType)
    {
        $keyName = 'game_qp_auth_url_'.$userId;
        $result['auth_url'] = $this->getRedis()->get($keyName);

        if(empty($result['auth_url'])){
            $userInfo = $this->userService->findByID($userId);
            $this->userService->checkUser($userInfo);
            $configs = getConfigs();
            $username = $userInfo['register_at']>strtotime('2025-09-06')?$userInfo['username']:$userInfo['_id'];

            $data = [
                'username'=>container()->get('config')->app->name.'_'.$username,
                'nickname'=>strval($userInfo['nickname']),
                'user_avatar'=>$configs['media_url_open'].$userInfo['img'],
                'ip'=>getClientIp(),
                'device'=>$deviceType,
                'game_type'=>'letian',
            ];

            $result = $this->mrsSystemService->getLsjGameQpAuthUrl($data);
            $this->getRedis()->set($keyName, $result['auth_url'], 30*60*60);

            if(empty($result['auth_url'])){
                throw new BusinessException(StatusCode::DATA_ERROR, '权限获取失败,请稍后再尝试!');
            }
        }

        return $result;
    }

}