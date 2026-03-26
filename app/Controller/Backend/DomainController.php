<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\DomainRepository;

/**
 * 域名管理
 *
 * @package App\Controller\Backend
 *
 * @property  DomainRepository $domainRepository
 */
class DomainController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/domain');
    }
    /**
     * 列表
     */
    public function listAction()
    {
        if ($this->isPost()) {
            $result = $this->domainRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('typeArr',CommonValues::getDomainType());
        $this->view->setVar('statusArr',CommonValues::getDomainStatus());
        $this->view->setVar('cities',CommonValues::getMonitorCities());
    }

    /**
     *详情
     * @throws BusinessException
     */
    public function detailAction()
    {
        $id = $this->getRequest("_id");
        if (!empty($id)) {
            $result = $this->domainRepository->getDetail($id);
            $this->view->setVar('row', $result);
        }
        $this->view->setVar('typeArr',CommonValues::getDomainType());
        $this->view->setVar('statusArr',CommonValues::getDomainStatus());
    }

    /**
     * 保存
     * @throws BusinessException
     */
    public function saveAction()
    {
        $result = $this->domainRepository->save($_POST);
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
                $this->domainRepository->delete($id);
            }
            return $this->sendSuccessResult();
        }elseif($act=='checkDomain') {
            $update = [
                'updated_at'=>time()
            ];
        }
        $ids = explode(',', $ids);
        $ids = array_unique($ids);
        foreach ($ids as $id) {
            if (!empty($update)) {
                $update['_id'] = $id;
                $this->domainRepository->update($update);
            }
        }
        return $this->sendSuccessResult();

    }
}