<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\ChannelAppRepository;
use App\Repositories\Backend\ChannelRepository;

/**
 * 渠道管理
 *
 * @package App\Controller\Backend
 *
 * @property  ChannelAppRepository $channelAppRepository
 */
class ChannelAppController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/channelApp');
    }
    /**
     * 列表
     */
    public function listAction()
    {
        if ($this->isPost()) {
            $result = $this->channelAppRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('typeArr',CommonValues::getChannelAppType());
    }

    /**
     *详情
     * @throws BusinessException
     */
    public function detailAction()
    {
        $id = $this->getRequest("_id");
        if (!empty($id)) {
            $result = $this->channelAppRepository->getDetail($id);
            $this->view->setVar('row', $result);
        }
        $this->view->setVar('typeArr',CommonValues::getChannelAppType());
    }

    /**
     * 保存
     * @throws BusinessException
     */
    public function saveAction()
    {
        $result = $this->channelAppRepository->save($_POST);
        if ($result) {
            $this->sendSuccessResult();
        }
        $this->sendErrorResult("保存错误!");
    }

    /**
     * 各种操作
     */
    public function doAction()
    {
        $ids = $this->getRequest("id");
        $act = $this->getRequest("act");

        if (empty($ids) || empty($act)) {
            return $this->sendErrorResult("参数错误!");
        }
        if($act=='del') {
            $ids = explode(',', $ids);
            foreach ($ids as $id) {
                $this->channelAppRepository->delete($id);
            }
            return $this->sendSuccessResult();
        }
        $ids = explode(',', $ids);
        $ids = array_unique($ids);
        foreach ($ids as $id) {
            if (!empty($update)) {
                $update['_id'] = $id;
                $this->channelAppRepository->update($update);
            }
        }
        return $this->sendSuccessResult();

    }
}