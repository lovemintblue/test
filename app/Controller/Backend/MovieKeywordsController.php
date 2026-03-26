<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\MovieKeywordsRepository;

/**
 * 视频关键字管理
 *
 * @property  MovieKeywordsRepository $movieKeywordsRepository
 */
class MovieKeywordsController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/movieKeywords');
    }

    /**
     * 列表
     */
    public function listAction()
    {
        if ($this->isPost()) {
            $result = $this->movieKeywordsRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('posArr',CommonValues::getMoviePosition());
    }

    /**
     * 详情
     * @throws BusinessException
     */
    public function detailAction()
    {
        $id = $this->getRequest("_id");
        if (!empty($id)) {
            $result = $this->movieKeywordsRepository->getDetail($id);
            $this->view->setVar('row', $result);
        }
        $this->view->setVar('posArr',CommonValues::getMoviePosition());
    }

    /**
     * 保存
     * @throws BusinessException
     */
    public function saveAction()
    {
        $result = $this->movieKeywordsRepository->save($_POST);
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
                $this->movieKeywordsRepository->delete($id);
            }
        }
        $this->sendSuccessResult();
    }

}