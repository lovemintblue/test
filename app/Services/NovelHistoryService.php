<?php


namespace App\Services;


use App\Core\Services\BaseService;
use App\Models\NovelHistoryModel;

/**
 * Class NovelHistoryService
 * @property NovelHistoryModel $novelHistoryModel
 * @property UserService $userService
 * @property MovieService $movieService
 * @property CommonService $commonService
 * @package App\Services
 */
class NovelHistoryService extends BaseService
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
        return $this->novelHistoryModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 通过id查询
     * @param  $id
     * @param  $fields
     * @return mixed
     */
    public function findByID($id, $fields = array())
    {
        return $this->novelHistoryModel->findByID($id, '_id', $fields);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    private function count($query = [])
    {
        return $this->novelHistoryModel->count($query);
    }


    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->novelHistoryModel->delete(['_id' => $id]);
    }

    /**
     * @param $userId
     * @param $novelId
     * @param $chapter
     * @return bool|int|mixed
     */
    public function do($userId, $novelId, $chapter)
    {
        $userId = intval($userId);
        $itemId = md5($userId . '_' . $novelId);
        if ($this->count(['_id' => $itemId])) {
            return $this->novelHistoryModel->updateRaw([
                '$set' => ['status' => 1, 'label' => date("Y-m-d"), 'updated_at' => time(), 'chapter_name' => $chapter['name'], 'chapter_id' => $chapter['_id']]
            ], ['_id' => $itemId]);
        } else {
            $data = [
                '_id' => $itemId,
                'user_id' => $userId,
                'novel_id' => $novelId,
                'chapter_name' => $chapter['name'],
                'chapter_id' => $chapter['_id'],
                'status' => 1,
                'label' => date("Y-m-d"),
            ];
            return $this->novelHistoryModel->insert($data);
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
            $result[$item['novel_id']] = array(
                'novel_id' => strval($item['novel_id']),
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
        $this->novelHistoryModel->updateRaw(['$set' => ['status' => 0]], ['_id' => $itemId]);
        return true;
    }

    /**
     * 删除所有
     * @param $userId
     * @return mixed
     */
    public function deleteAll($userId)
    {
        return $this->novelHistoryModel->updateRaw(['$set' => ['status' => 0]], ['user_id' => intval($userId)]);
    }

}