<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\ArticleCategoryRepository;
use App\Repositories\Backend\ArticleRepository;


/**
 * 文章
 *
 * @package App\Controller\Backend
 *
 * @property  ArticleRepository $articleRepository
 * @property  ArticleCategoryRepository $articleCategoryRepo
 */
class ArticleController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/article');
    }

    /**
     * 列表
     */
    public function listAction()
    {
        if($this->isPost()){
            $result = $this->articleRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('catOptions', $this->articleCategoryRepo->getTreeCodeOptions());
    }

    /**
     * 详情
     * @throws BusinessException
     */
    public function detailAction()
    {
        $id = $this->getRequest("_id");
        if (!empty($id)) {
            $result = $this->articleRepository->getDetail($id);
            $this->view->setVar('row', $result);
        }
        $this->view->setVar('catOptions', $this->articleCategoryRepo->getTreeCodeOptions());
    }

    /**
     * 保存
     * @throws BusinessException
     */
    public function saveAction()
    {
        $result = $this->articleRepository->save($_POST);
        if ($result) {
            return $this->sendSuccessResult();
        }
        return $this->sendErrorResult("保存错误!");
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
                $this->articleRepository->delete($id);
            }
        }
        $this->sendSuccessResult();
    }

}