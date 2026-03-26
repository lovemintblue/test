<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\ChannelRepository;

/**
 * 渠道管理
 *
 * @package App\Controller\Backend
 *
 * @property  ChannelRepository $channelRepository
 */
class ChannelController extends BaseBackendController
{

    /**
     * 列表
     */
    public function listAction()
    {
        $this->checkPermission('/channel');
        if ($this->isPost()) {
            $result = $this->channelRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
    }

    /**
     *详情
     * @throws BusinessException
     */
    public function detailAction()
    {
        $this->checkPermission('/channel');
        $id = $this->getRequest("_id");
        if (empty($id)) {
            $this->sendErrorResult("参数错误");
        }
        $result = $this->channelRepository->getDetail($id);
        $this->sendSuccessResult($result);
    }

    /**
     * 保存
     * @throws BusinessException
     */
    public function saveAction()
    {
        $this->checkPermission('/channel');
        $result = $this->channelRepository->save($_POST);
        if ($result) {
            $this->sendSuccessResult();
        }
        $this->sendErrorResult("保存错误!");
    }

    /**
     * 列表
     */
    public function reportAction()
    {
        $this->checkPermission('/channelReport');
        if ($this->isPost()) {
            $result = $this->channelRepository->getReportList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('appDayArr',CommonValues::getAppDay());
    }

    /**
     * 导出excel
     * @throws BusinessException
     */
    public function exportReportAction()
    {
        $this->checkPermission('/channelReport');
        $path = $this->channelRepository->exportReportExcel($_REQUEST);
        $this->sendSuccessResult(array('file' => $path));
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
                $this->channelRepository->delete($id);
            }
            return $this->sendSuccessResult();
        }else if($act=='up') {
            $update = ['is_disabled'=> 0];
        } else if($act=='down') {
            $update = ['is_disabled'=> 1];
        }
        $ids = explode(',', $ids);
        $ids = array_unique($ids);
        foreach ($ids as $id) {
            if (!empty($update)) {
                $update['_id'] = $id;
                $this->channelRepository->update($update);
            }
        }
        return $this->sendSuccessResult();

    }
}