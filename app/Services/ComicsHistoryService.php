<?php


namespace App\Services;


use App\Core\Services\BaseService;
use App\Models\ComicsHistoryModel;

/**
 * Class MovieHistoryService
 * @property ComicsHistoryModel $comicsHistoryModel
 * @property UserService $userService
 * @property ComicsService $comicsService
 * @property CommonService $commonService
 * @package App\Services
 */
class ComicsHistoryService extends BaseService
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
        return $this->comicsHistoryModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 通过id查询
     * @param  $id
     * @param  $fields
     * @return mixed
     */
    public function findByID($id, $fields = array())
    {
        return $this->comicsHistoryModel->findByID($id, '_id', $fields);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    private function count($query = [])
    {
        return $this->comicsHistoryModel->count($query);
    }


    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->comicsHistoryModel->delete(['_id' => $id]);
    }

    /**
     * @param $userId
     * @param $comicsId
     * @param $chapter
     * @return bool|int|mixed
     */
    public function do($userId, $comicsId, $chapter)
    {
        $userId = intval($userId);
        $itemId = md5($userId . '_' . $comicsId);
        if ($this->findByID($itemId,['_id'])) {
            return $this->comicsHistoryModel->updateRaw([
                '$set' => ['status' => 1, 'label' => date("Y-m-d"), 'updated_at' => time(), 'chapter_name' => $chapter['name'], 'chapter_id' => $chapter['_id']]
            ], ['_id' => $itemId]);
        } else {
            $this->comicsService->handler(['action'=>'click','comics_id'=>$comicsId]);
            return $this->comicsHistoryModel->insert([
                '_id' => $itemId,
                'user_id' => $userId,
                'comics_id' => $comicsId,
                'chapter_name' => $chapter['name'],
                'chapter_id' => $chapter['_id'],
                'status' => 1,
                'label' => date("Y-m-d"),
            ]);
        }
    }


    /**
     * 观看列表
     * @param $userId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getHistories($userId, $page = 1, $pageSize = 20)
    {
        $result = array();
        $skip = ($page - 1) * $pageSize;
        $items = $this->getList(['user_id' => intval($userId), 'status' => 1], [], ['updated_at' => -1], $skip, $pageSize);
        foreach ($items as $item) {
            $result[$item['comics_id']] = array(
                'comics_id' => strval($item['comics_id']),
                'time' => strval($item['time']),
                'date_label' => dateFormat($item['updated_at'], 'Y-m-d'),
                'updated_time' => strval($item['updated_at'])
            );
        }
        return $result;
    }

    /**
     * 删除一个
     * @param $userId
     * @param $comicsId
     * @return bool
     */
    public function delFirst($userId, $comicsId)
    {
        $itemId = md5($userId . '_' . $comicsId);
        $this->comicsHistoryModel->updateRaw(['$set' => ['status' => 0]], ['_id' => $itemId]);
        return true;
    }

    /**
     * 删除所有
     * @param $userId
     * @return mixed
     */
    public function deleteAll($userId)
    {
        return $this->comicsHistoryModel->updateRaw(['$set' => ['status' => 0]], ['user_id' => intval($userId)]);
    }

}