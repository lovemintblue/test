<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Models\ChatModel;
use App\Models\MessageModel;
use App\Utils\CommonUtil;

/**
 * Class MessageService
 * @package App\Services
 * @property ChatModel $chatModel
 * @property MessageModel $messageModel
 */
class ChatService extends BaseService
{
    /**
     * 发送消息 ,逻辑是保存两份会话表  一份消息表
     * @param $userId
     * @param $toUserId
     * @param string $type
     * @param string $content
     * @param string $ext
     * @return bool
     * @throws BusinessException
     */
    public function send($userId, $toUserId, $type = 'text', $content = '', $ext = '')
    {
        $userId     = intval($userId);
        $toUserId   = intval($toUserId);
        $userIds    = [$userId, $toUserId];
        $chatId     = md5(min($userIds) . '_' . max($userIds) . '_chat');
        if (!in_array($type,['text','image','html'])) {
            throw new BusinessException(StatusCode::DATA_ERROR, '仅支持文本和图片!');
        }
        //当前用户
        $data = [
            '_id'       => $chatId . '_' . $userId,
            'chat_id'   => $chatId,
            'user_id'   => $userId,
            'to_user_id'=> $toUserId,
            'status'    => 1,
            'type'      => $type,
            'content'      => value(function ()use($type,$content){
                switch ($type){
                    case 'html':
                    case 'text':
                        return $content;
                        break;
                    case 'image':
                        return '图片消息,点击查看';
                        break;
                    default:
                        return '未知类型';
                        break;
                }
            }),
            'ip'        => getClientIp(),
            'created_at'=>time(),
            'updated_at'=>time(),
        ];

        $this->chatModel->findAndModify(['_id'=>$data['_id']],$data,[],true);
        //目标用户
        $data['_id']        = $chatId.'_'.$toUserId;
        $data['user_id']    = $toUserId;
        $data['to_user_id'] = $userId;
        $data['status']     = 0;
        $this->chatModel->findAndModify(['_id'=>$data['_id']],$data,[],true);


        //消息记录
        unset($data['_id'],$data['status']);
        $data['ext']        = strval($ext);
        $data['user_id']    = $userId;
        $data['to_user_id'] = $toUserId;
        $data['content']    = $content;
        $this->messageModel->insert($data);
        return true;
    }


    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->chatModel->count($query);
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
        return $this->chatModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->chatModel->findByID(strval($id));
    }

    /**
     * 删除会话
     * @param $userId
     * @param $toUserId
     * @return bool
     */
    public function delChat($userId, $toUserId)
    {
        $userIds = array($userId, $toUserId);
        $chatId = md5(min($userIds) . '_' . max($userIds) . '_chat');
        $itemId = md5($chatId . '_' . $userId);
        $this->chatModel->delete(array('_id' => $itemId));
        $this->messageModel->delete(array('chat_id'=>$itemId));
        return true;
    }

}