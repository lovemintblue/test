<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\AdminUserService;
use App\Services\AdvAppService;

/**
 * 应用中心
 *
 * @property  AdvAppService $advAppService
 * @property  AdminUserService $adminUserService
 */
class AdvAppRepository extends BaseRepository
{
    /**
     * 获取列表
     * @param $request
     * @return array
     */
    public function getList($request)
    {
        $page       = $this->getRequest($request, 'page', 'int', 1);
        $pageSize   = $this->getRequest($request, 'pageSize', 'int', 15);

        $query  = array();
        $filter = array();

        if ($request['name']) {
            $filter['name'] = $this->getRequest($request, 'name');
            $query['name']  = array('$regex' => $filter['name'], '$options' => 'i');
        }

        if ($request['is_disabled'] !== null && $request['is_disabled'] !== '') {
            $filter['is_disabled'] = $this->getRequest($request, 'is_disabled', 'int');
            $query['is_disabled']  = $filter['is_disabled'];
        }

        $skip   = ($page - 1) * $pageSize;
        $fields = array();
        $count  = $this->advAppService->count($query);
        $items  = $this->advAppService->getList($query, $fields, array('created_at' => -1), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['is_self'] = $item['is_self']?'是':'否';
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);
            $item['category'] = empty($item['category'])?'装机必备':$item['category'];
            $items[$index] = $item;
        }

        return array(
            'filter' => $filter,
            'items' => empty($items) ? array() : array_values($items),
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize
        );
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     * @throws BusinessException
     */
    public function save($data)
    {
        $row = array(
            'name'          => $this->getRequest($data, 'name'),
            'category'      => $this->getRequest($data,'category','string','装机必备'),
            'image'         => $this->getRequest($data, 'image','string', ''),
            'download_url'   => $this->getRequest($data, 'download_url','string', ''),
            'description'   => $this->getRequest($data, 'description','string', ''),
            'download'      => $this->getRequest($data, 'download','string', ''),
            'sort'          => $this->getRequest($data, 'sort','int',0),
            'is_self'   => $this->getRequest($data, 'is_self','int',0),
        );

        if (empty($row['name'])||empty($row['image'])||empty($row['download_url'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }

        if ($data['_id'] !=='') {
            $row['_id'] = $this->getRequest($data, '_id', 'string');
        }
        $result =  $this->advAppService->save($row);
        $this->adminUserService->addAdminLog(sprintf('操作应用中心:名称%s,ios链接%s,android链接%s',$row['name'],$row['ios_url'],$row['android_url']));
        return  $result;
    }

    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->advAppService->findByID($id);
        if (empty($row)) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '数据不存在!');
        }
        return $row;
    }

    /**
     * 删除
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $result =  $this->advAppService->delete($id);
        $this->adminUserService->addAdminLog(sprintf('删除应用中心:编号%s',$id));
        return  $result;
    }


}