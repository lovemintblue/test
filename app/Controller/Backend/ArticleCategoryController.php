<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\ArticleCategoryRepository;


/**
 * 文章分类
 *
 * @package App\Controller\Backend
 *
 * @property  ArticleCategoryRepository $articleCategoryRepository
 */
class ArticleCategoryController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/articleCategory');
    }

    /**
     * 列表
     */
    public function listAction()
    {
        if($this->isPost()){
            $result = $this->articleCategoryRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
    }

    /**
     * 详情
     * @throws BusinessException
     */
    public function detailAction()
    {
        $id = $this->getRequest("_id");
        if (!empty($id)) {
            $result = $this->articleCategoryRepository->getDetail($id);
            $this->view->setVar('row', $result);
        }
        $this->view->setVar('options', $this->articleCategoryRepository->getTreeOptions());
    }

    /**
     * 保存
     * @throws BusinessException
     */
    public function saveAction()
    {
        $result = $this->articleCategoryRepository->save($_REQUEST);
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
                $this->articleCategoryRepository->delete($id);
            }
        }
        $this->sendSuccessResult();
    }

}