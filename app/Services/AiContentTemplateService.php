<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Models\AiContentTemplateModel;

/**
 * 内容模版
 * @package App\Services
 *
 * @property  AiContentTemplateModel $aiContentTemplateModel
 * @property  CommonService $commonService
 */
class AiContentTemplateService extends BaseService
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
        return $this->aiContentTemplateModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->aiContentTemplateModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->aiContentTemplateModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->aiContentTemplateModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->aiContentTemplateModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->aiContentTemplateModel->insert($data);
        }
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->aiContentTemplateModel->delete(array('_id' => intval($id)));
    }

    /**
     * 随机内容
     * @param $userId
     * @return mixed|null
     * @throws BusinessException
     */
    public function getRandContent($userId)
    {
        if (!$this->commonService->checkActionLimit('get_rand_content_' . $userId, 60*2, 10)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '点击过快,请稍等几分钟!');
        }
        $query = [
            ['$sample' => ['size'=>1]],
            ['$project' => ['content'=>1,'_id'=>0]],
        ];
        return $this->aiContentTemplateModel->aggregate($query);
    }
}