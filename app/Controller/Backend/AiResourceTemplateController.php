<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\AiCategoryRepository;
use App\Repositories\Backend\AiResourceTemplateRepository;

/**
 * AI资源模版
 *
 * @package App\Controller\Backend
 *
 * @property  AiResourceTemplateRepository $aiResourceTemplateRepository
 * @property  AiCategoryRepository $aiCategoryRepository
 */
class AiResourceTemplateController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
    }

    public function initData($position)
    {
        $this->view->setVar('position',$position);
        $this->view->setVar('hot',CommonValues::getHot());
        $this->view->setVar('disabled',CommonValues::getIsDisabled());
        $this->view->setVar('posArr',CommonValues::getAiPosition());
        $this->view->setVar('catArr',$this->aiCategoryRepository->getAll($position));
    }

    /**
     * 列表
     */
    public function listAction()
    {
        $position = $this->getRequest("position");
        $this->checkPermission('/aiResourceTemplate'.ucfirst($position));
        if ($this->isPost()) {
            $result = $this->aiResourceTemplateRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->initData($position);
        $this->view->pick('aiResourceTemplate/list');
    }

    /**
     * 详情
     * @throws BusinessException
     */
    public function detailAction()
    {
        $id = $this->getRequest("_id");
        $position = $this->getRequest("position");
        if (!empty($id)) {
            $result = $this->aiResourceTemplateRepository->getDetail($id);
            $this->view->setVar('row',$result);
        }
        $this->initData($position);
    }

    /**
     * 保存
     * @throws BusinessException
     */
    public function saveAction()
    {
        $result = $this->aiResourceTemplateRepository->save($_POST);
        if ($result) {
            $this->sendSuccessResult();
        }
        $this->sendErrorResult("保存错误!");
    }

    /**
     * 同步
     */
    public function asyncAction()
    {
        $position = $this->getRequest("position");
        $result = $this->aiResourceTemplateRepository->asyncResourceTemplate($position);
        $this->sendSuccessResult($result);
    }

    /**
     * 批量操作
     */
    public  function  doAction()
    {
        $idStr = $this->getRequest("id");
        $act = $this->getRequest("act");
        if(empty($idStr) || empty($act)){
            $this->sendErrorResult("操作错误!");
        }
        if($act=='del') {
            $ids = explode(',', $idStr);
            foreach ($ids as $id) {
                $this->aiResourceTemplateRepository->delete($id);
            }
        }elseif($act=='update'){
            $ids = explode(',', $idStr);
            $sort = $this->getRequest('sort','string');
            $isDisabled = $this->getRequest('is_disabled','string');
            $isPorn = $this->getRequest('is_porn','string');
            foreach ($ids as $id) {
                if($sort!=''){$data['sort'] = intval($sort);}
                if($isDisabled!=''){$data['is_disabled'] = intval($isDisabled);}
                if($isPorn!=''){$data['is_porn'] = intval($isPorn);}
                if(empty($data)){
                    $this->sendErrorResult("数据为空!");
                }
                $data['_id'] = intval($id);
                $this->aiResourceTemplateRepository->aiResourceTemplateService->save($data);
            }
        }
        $this->sendSuccessResult();
    }

    /**
     * 批量设置
     */
    public function updateAction()
    {
        $position = $this->getRequest("position");
        $this->view->setVar('ids',$this->getRequest('ids','string'));
        $this->initData($position);
    }

}