<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Constants\CacheKey;
use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\AdminUserService;
use App\Services\UserGroupService;

/**
 * 用户组管理
 * @package App\Repositories\Backend
 *
 * @property  UserGroupService $userGroupService
 * @property AdminUserService $adminUserService
 */
class UserGroupRepository extends BaseRepository
{
    /**
     * 获取列表
     * @param $request
     * @return array
     */
    public function getList($request)
    {
        $page = $this->getRequest($request, 'page', 'int', 1);
        $pageSize = $this->getRequest($request, 'pageSize', 'int', 15);
        $sort     = $this->getRequest($request, 'sort', 'string', '_id');
        $order    = $this->getRequest($request, 'order', 'int', -1);
        $query = array();
        $filter = array();

        if ($request['name']) {
            $filter['name'] = $this->getRequest($request, 'name');
            $query['name']  = array('$regex' => $filter['name'], '$options' => 'i');
        }

        $skip = ($page - 1) * $pageSize;
        $fields = array();
        $count = $this->userGroupService->count($query);
        $items = $this->userGroupService->getList($query, $fields, array($sort => $order), $skip, $pageSize);
        foreach ($items as $index => $item) {
            $item['created_at'] = dateFormat($item['created_at']);
            $item['updated_at'] = dateFormat($item['updated_at']);
            $item['promotion_type_text'] = CommonValues::getPromotionType($item['promotion_type']);
            $item['is_disabled_text'] = CommonValues::getIsDisabled($item['is_disabled']);
            $item['level_text'] = CommonValues::getUserLevel($item['level']);
            $item['group_text'] = CommonValues::getUserGroupType($item['group']);
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
            'description'   => $this->getRequest($data, 'description'),
            'is_disabled'   => $this->getRequest($data, 'is_disabled', 'int', 0),
            'sort'          => $this->getRequest($data, 'sort', 'int', 0),
            'img'           => $this->getRequest($data, 'img', 'string', ''),
            'group'         => $this->getRequest($data, 'group', 'string',''),
            'style'         => $this->getRequest($data, 'style', 'int', 1),
            'level'         => $this->getRequest($data, 'level', 'int', 1),
            'promotion_type'=> $this->getRequest($data, 'promotion_type', 'int', 0),
            'rate'          => $this->getRequest($data, 'rate', 'int', 100),
            'coupon_num'    => $this->getRequest($data, 'coupon_num', 'int', 0),
            'price'         => $this->getRequest($data, 'price', 'double', 0),
            'old_price'     => $this->getRequest($data, 'old_price', 'double', 0),
            'day_num'       => $this->getRequest($data, 'day_num', 'int', 0),
            'gift_num'      => $this->getRequest($data, 'gift_num', 'int', 0),
            'download_num'  => $this->getRequest($data, 'download_num', 'int', 0),
            'day_tips'      => $this->getRequest($data, 'day_tips', 'string', ''),
            'price_tips'    => $this->getRequest($data, 'price_tips', 'string', ''),
        );

        if($row['rate']<-2 ||$row['rate']>100){
            throw  new BusinessException(StatusCode::DATA_ERROR, '折扣范围取值错误!');
        }
        if (empty($row['name']) || empty($row['day_num'])) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '组名或者天数配置错误!');
        }
        if (empty($row['level'])) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '等级或者样式配置错误!');
        }
        if ($data['_id'] > 0) {
            $row['_id'] = $this->getRequest($data, '_id', 'int');
        }
        $row['rights'] = empty($data['rights'])?[]:array_values($data['rights']);
        $result = $this->userGroupService->save($row);
        $this->adminUserService->addAdminLog(sprintf('操作用户组 名称%s,天数%s,价格%s,等级%s 编号%s',
            $row['name'], $row['day_num'], $row['price'], $row['level'], empty($row['_id']) ? $result : $row['_id']));
        return $result;
    }

    /**
     * 获取详情
     * @param $id
     * @return mixed
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $row = $this->userGroupService->findByID($id);
        if (empty($row)) {
            throw  new BusinessException(StatusCode::DATA_ERROR, '数据不存在!');
        }
        return $row;
    }

    /**
     * 删除订单
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $this->adminUserService->addAdminLog(sprintf('操作用户组 删除会员组%s',$id));
        return $this->userGroupService->delete($id);
    }


    /**
     * 获取所有分组
     * @return array
     */
    public function getAll()
    {
        return $this->userGroupService->getAll();
    }

    /**
     * 获取有效的分组
     * @return array
     */
    public function getEnableAll()
    {
        return $this->userGroupService->getEnableAll();
    }

}