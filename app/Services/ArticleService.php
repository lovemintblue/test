<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Services\BaseService;
use App\Models\AdvPosModel;
use App\Models\ArticleModel;

/**
 *  文章
 * @package App\Services
 *
 * @property  ArticleModel $articleModel
 */
class ArticleService extends BaseService
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
        return $this->articleModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->articleModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->articleModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->articleModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->articleModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->articleModel->insert($data);
        }
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->articleModel->delete(array('_id' => intval($id)));
    }


    /**
     * 获取通知
     * @return array
     */
    public function getAnnouncement($code='announcement')
    {
        $items = $this->getArticleList($code, 1, 1);
        if (empty($items)) return null;
        return array(
            'key' => md5(date('Y-m-d H')),
            'title' => strval($items[0]['title']),
            'content' => strval($items[0]['content'])
        );
    }

    /**
     * 获取文章
     * @param string $code
     * @param int $page
     * @param int $pageSize
     * @return array|mixed|null
     */
    public function getArticleList($code = 'announcement', $page = 1, $pageSize = 10)
    {
        $keyName = 'article_list_' . $code . '_' . $page . '_' . $pageSize;
        $result = getCache($keyName);
        if ($result === null) {
            $result = array();
            $skip = ($page - 1) * $pageSize;
            $items = $this->getList(array('category_code' => $code), array(), array( 'sort' => -1), $skip, $pageSize);
            foreach ($items as $item) {
                $result[] = array(
                    'id' => strval($item['_id']),
                    'title' => strval($item['title']),
                    'content' => strval($item['content']),
                    'created_at' => dateFormat($item['created_at'])
                );
            }
            setCache($keyName, $result, 300);
        }
        return $result;
    }

}