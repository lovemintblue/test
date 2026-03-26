<?php


namespace App\Services;


use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Models\CommentModel;
use App\Models\CommentReplyModel;
use App\Utils\CommonUtil;

/**
 * Class CommentService
 * @property CommentReplyModel $commentReplyModel
 * @package App\Services
 */
class CommentReplyService extends BaseService
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
        return $this->commentReplyModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->commentReplyModel->count($query);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->commentReplyModel->findByID($id);
    }

    /**
     *
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->commentReplyModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->commentReplyModel->insert($data);
        }
    }

    /**
     * 删除多个
     * @param $query
     * @return mixed
     */
    public function deleteMany($query)
    {
        return $this->commentReplyModel->delete($query);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->commentReplyModel->delete(['_id'=>$id]);
    }
}