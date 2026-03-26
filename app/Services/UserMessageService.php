<?php


namespace App\Services;


use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Models\UserMessageModel;

/**
 * Class UserMessageService
 * @package App\Services
 * @property UserMessageModel $userMessageModel
 */
class UserMessageService extends BaseService
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
        return $this->userMessageModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->userMessageModel->findByID(intval($id));
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->userMessageModel->count($query);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->userMessageModel->delete(['_id'=>intval($id)]);
    }

    /**
     * 发送消息
     * @param $userId
     * @param $title
     * @param $content
     * @param string $link
     * @param string $type
     * @throws BusinessException
     */
    public function sendMessage($userId,$title,$content,$link='',$type='text')
    {
        if(empty($userId)||empty($title)||empty($content)){
            throw new BusinessException(StatusCode::DATA_ERROR, '用户ID,标题,内容不能为空!');
        }
        $this->save([
            'user_id'=>intval($userId),
            'title'=>strval($title),
            'content'=>strval($content),
            'link'  =>strval($link),
            'date_label'=>date('Y-m-d'),
            'type' =>$type,
            'read_status'=>0
        ]);
    }

    /**
     * 保存
     * @param $data
     * @return bool|int
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->userMessageModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->userMessageModel->insert($data);
        }
    }

    /**
     * 未读消息数量
     * @param $userId
     * @return float|int
     */
    public function unreadByUserId($userId)
    {
        return $this->userMessageModel->count(['user_id'=>$userId,'read_status'=>0])*1;
    }

    /**
     * 阅读消息
     * @param $userId
     * @return bool
     */
    public function readMessage($userId)
    {
        $this->userMessageModel->update(['read_status' => 1],['user_id' => intval($userId)]);
        return true;
    }
}