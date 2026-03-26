<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\AiCategoryRepository;

/**
 * AI分类
 *
 * @package App\Controller\Backend
 *
 * @property  AiCategoryRepository $aiCategoryRepository
 */
class AiCategoryController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/aiCategory');
    }


    /**
     * 列表
     */
    public function listAction()
    {
        if ($this->isPost()) {
            $result = $this->aiCategoryRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('hot',CommonValues::getHot());
        $this->view->setVar('posArr',CommonValues::getAiPosition());
    }

    /**
     * 详情
     * @throws BusinessException
     */
    public function detailAction()
    {
        $id = $this->getRequest("_id");
        if (!empty($id)) {
            $result = $this->aiCategoryRepository->getDetail($id);
            $this->view->setVar('row',json_encode($result));
        }
        $this->view->setVar('categories',[]);
        $this->view->setVar('hot',CommonValues::getHot());
        $this->view->setVar('posArr',CommonValues::getAiPosition());
    }

    /**
     * 保存
     * @throws BusinessException
     */
    public function saveAction()
    {
        $result = $this->aiCategoryRepository->save($_POST);
        if ($result) {
            $this->sendSuccessResult();
        }
        $this->sendErrorResult("保存错误!");
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
                $this->aiCategoryRepository->delete($id);
            }
        }
        $this->sendSuccessResult();
    }

}