<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Models\AiMessageModel;
use App\Utils\CommonUtil;

/**
 * Class AiMessageService
 * @package App\Services
 * @property AiMessageService $aiMessageService
 * @property UserService $userService
 * @property ConfigService $configService
 * @property CommonService $commonService
 * @property AiMessageModel $aiMessageModel
 * @property AccountService $accountService
 */
class AiMessageService extends BaseService
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
        return $this->aiMessageModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->aiMessageModel->count($query);
    }

    /**
     * @param $userId
     * @param $aiModel
     * @param $type
     * @param $content
     * @param $fee
     * @return bool|int|null
     * @throws BusinessException
     */
    public function doSave($userId, $aiModel, $type, $content,$fee=0)
    {
        if (!in_array($type,['text'])) {
            throw new BusinessException(StatusCode::DATA_ERROR, '仅支持文本!');
        }

        $userId     = intval($userId);
//        $user = $this->userService->getInfoFromCache($userId);
        $user = $this->userService->findByID($userId);
        $this->userService->checkUser($user);

        $configs = $this->configService->getAll();
        $aiModels = $this->getAiModels($configs['ai_chat_model']);
        if(!$aiModels[$aiModel]){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '请选择要使用AI模型!');
        }
        if($configs['ai_chat_price']>0){
            $fee = intval($configs['ai_chat_price']);
        }
        if($user['balance'] < $fee){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '可用余额不足!');
        }

//        if (!$this->commonService->checkActionLimit('do_ai_chat_' . $userId, 10, 1)) {
//            throw new BusinessException(StatusCode::DATA_ERROR, '发布过快,请稍等几分钟!');
//        }

        //对接ai系统

        $replyContent = '系统正在处理中，请稍后...';
        $status = 0;
        $errorMsg = '';
        $data = [
            'user_id'   => $userId,
            'ai_model'  => $aiModel,
            'type'      => $type,
            'content'   => $content,
            'reply_content' => $replyContent,
            'ip'        => getClientIp(),
            'fee'       => 0,
            'status'    => $status,
            'error_msg' => $errorMsg,
            'order_sn'  => '',
        ];
        $messageId = $this->aiMessageModel->insert($data);
        if(empty($messageId)){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '保存失败!');
        }

        //扣款
        if($fee>0){
            $orderSn=CommonUtil::createOrderNo('AI');
            $remark = "AI聊天消耗:{$fee}金币";
            $result = $this->accountService->reduceBalance($user,$orderSn,$fee,3,$remark);
            if(empty($result)){
                $this->aiMessageModel->delete(['_id'=>$messageId]);
                throw  new BusinessException(StatusCode::PARAMETER_ERROR, '购买失败!');
            }
            DataCenterService::doReduceBalance($messageId,$remark,$fee,$user['balance'],($user['balance']-$fee),'content_purchase',$orderSn,time());
            $this->aiMessageModel->updateRaw(array('$set'=>array('order_sn'=>$orderSn,'fee'=>$fee)),array('_id'=>$messageId));
            $this->userService->setInfoToCache($user['_id']);
        }

        return $replyContent;
    }

    /**
     * @param $userId
     * @param $page
     * @param $pageSize
     * @return array
     */
    public function getAiMessages($userId, $page, $pageSize = 15)
    {
        $userId = intval($userId);
        $skip   = ($page - 1) * $pageSize;
        $items  = $this->getList(['user_id' => $userId], [], ['_id' => -1], $skip, $pageSize);
        $result = [];
        foreach ($items as $item) {
            $user = $this->userService->getInfoFromCache($item['user_id']);
            $row = array(
                'id' => strval($item['_id']),
                'type' => $item['type'],
                'content' => strval($item['content']),
                'reply_content' => strval($item['reply_content']),
                'user_id' => strval($item['user_id']),
                'nickname' => strval($user['nickname']),
                'head_img' => $this->commonService->getCdnUrl($user['img']),
                'time_label' => CommonUtil::ucTimeAgo($item['created_at'])
            );
            $result[] = $row;
        }
        return $result;
    }

    /**
     * ai模型
     * @param $modelConfig
     * @return array
     */
    public function getAiModels($modelConfig)
    {
        if(empty($modelConfig)){return [];}
        $models = explode("\n",$modelConfig);
        $result = [];
        foreach ($models as $model)
        {
            $model = explode('|',$model);
            if($model[0] && $model[1]){
                $result[$model[0]] = [
                    'name' => trim($model[0]),
                    'key' => trim($model[0]),
                    'image' => $this->commonService->getCdnUrl(trim($model[1])),
                ];
            }
        }
        return $result;
    }
}