<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\ComicsBlockRepository;
use App\Repositories\Backend\ComicsRepository;
use App\Repositories\Backend\MovieBlockRepository;

/**
 * 漫画模块
 *
 * @package App\Controller\Backend
 *
 * @property  ComicsBlockRepository $comicsBlockRepo
 */
class ComicsBlockController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/comicsBlock');
    }

    /**
     * 列表
     */
    public function listAction()
    {
        if($this->isPost()) {
            $result = $this->comicsBlockRepo->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('positions',$this->comicsBlockRepo->getPosition());
    }

    /**
     * 详情
     * @throws BusinessException
     */
    public function detailAction()
    {
        $id = $this->getRequest("_id");
        if (!empty($id)) {
            $result = $this->comicsBlockRepo->getDetail($id);
            $this->view->setVar('row', $result);
        }
        $this->view->setVar('styles',CommonValues::getComicsBlockStyles());
        $this->view->setVar('positions',$this->comicsBlockRepo->getPosition());
    }

    /**
     * 保存
     * @throws BusinessException
     */
    public function saveAction()
    {
        $result = $this->comicsBlockRepo->save($_POST);
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
                $this->comicsBlockRepo->delete($id);
            }
        }
        $this->sendSuccessResult();
    }

}