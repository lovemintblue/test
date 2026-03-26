<?php

declare(strict_types=1);

namespace App\Repositories\Backend;


use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\ChatService;
use App\Services\CommonService;
use App\Services\MessageService;
use App\Services\UserGroupService;
use App\Services\UserService;
use App\Utils\CommonUtil;

/**
 * 消息
 * @package App\Repositories\Backend
 *
 * @property  MessageService $messageService
 * @property  ChatService $chatService
 * @property  CommonService $commonService
 * @property  UserService $userService
 * @property  UserGroupService $userGroupService
 */
class ChatRepository extends BaseRepository
{
    /**
     * 获取列表
     * @param $request
     * @return array
     */
    public function getList($request)
    {
        $page       = $this->getRequest($request, 'page', 'int', 1);
        $pageSize   = $this->getRequest($request, 'pageSize', 'int', 30);
        $sort       = $this->getRequest($request, 'sort', 'string', 'updated_at');
        $order      = $this->getRequest($request, 'order', 'int', -1);
        $query      = [];
        $filter     = [];

        if ($request['user_id']) {
            $filter['user_id'] = $this->getRequest($request, 'user_id','int');
            $query['user_id']  = $filter['user_id'];
        }
        if ($request['to_user_id']) {
            $filter['to_user_id'] = $this->getRequest($request, 'to_user_id','int');
            $query['to_user_id']  = $filter['to_user_id'];
        }
        if (isset($request['status'])&& $request['status']!=="") {
            $filter['status'] = $this->getRequest($request, 'status','int');
            $query['status']  = $filter['status'];
        }
        if ($request['start_time']) {
            $filter['start_time'] = $this->getRequest($request, 'start_time','string');
            $query['created_at']['$gte'] = strtotime($filter['start_time']);
        }
        if ($request['end_time']) {
            $filter['end_time'] = $this->getRequest($request, 'end_time','string');
            $query['created_at']['$lte'] = strtotime($filter['end_time']);
        }
        $skip   = ($page - 1) * $pageSize;
        $count  = $this->chatService->count($query);
        $items  = $this->chatService->getList($query, [], array($sort => $order), $skip, $pageSize);

        foreach ($items as $index => $item) {
            $userInfo = $this->userService->getInfoFromCache($item['to_user_id']);
            $item['device_type']      =$userInfo['device_type'];
            $item['to_user_nickname'] = $userInfo['nickname'] ?? '';
            $item['to_user_img']      = $userInfo['img'] ?? '';
            $item['status']           = CommonValues::getChatStatus($item['status']);
            $item['updated_at']       = CommonUtil::ucTimeAgo($item['updated_at']);
            $items[$index] = $item;
        }

        return array(
            'filter' => $filter,
            'items' => empty($items) ? array() : array_values($items),
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
        );
    }


    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->chatService->findByID($id);
        if (empty($row)) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '数据不存在!');
        }

        $user = $this->userService->getInfoFromCache($row['to_user_id']);
        $row['group_name']      = $user['group_name'] ?? '';
        $row['user_nickname']   = $user['nickname'] ?? '';
        $row['device_version']  = $user['device_version']??'';
        $row['device_type']     = $user['device_type']??'';
        $row['user_img']        = $user['img'] ?? '';
        return $row;
    }


    /**
     * 某个会话的消息
     * @param $request
     * @return array
     */
    public function getMessageList($request)
    {
        $page       = $this->getRequest($request, 'page', 'int', 1);
        $pageSize   = $this->getRequest($request, 'pageSize', 'int', 15);
        $userId   = $this->getRequest($request, 'user_id', 'int', -1);
        $toUserId   = $this->getRequest($request, 'to_user_id', 'int', '');

        $userIds = array($userId, $toUserId);
        $chatId = md5(min($userIds) . '_' . max($userIds) . '_chat');

        $skip   = ($page - 1) * $pageSize;
        $fields = [];
        $query  = ['chat_id' => $chatId];
        $items = $this->messageService->getList($query, $fields, array('created_at' => -1), $skip, $pageSize);
        foreach ($items as $index => $item){
            $user = $this->userService->getInfoFromCache($item['user_id']);
            $item['user_nickname']  = $user['nickname'] ?? '';
            $item['user_img']       = $user['img'] ?? '';
            $item['is_my']          = $item['to_user_id'] == $userId ? 'y' : 'n';
            $item['time_label']     = CommonUtil::ucTimeAgo($item['created_at']);
            $items[$index] = $item;
        }

        return array_reverse($items);
    }


    /**
     * @param $userId
     * @param $toUserId
     * @param string $type
     * @param string $content
     * @param string $ext
     * @return bool
     * @throws BusinessException
     */
    public function sendMessage($userId, $toUserId, $type = 'text', $content = '', $ext = '')
    {
        return $this->chatService->send($userId, $toUserId, $type, $content, $ext);
    }

}