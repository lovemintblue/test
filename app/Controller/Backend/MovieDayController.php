<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\MovieDayRepository;

/**
 * 每日优选
 *
 * @package App\Controller\Backend
 *
 * @property  MovieDayRepository $movieDayRepository
 */
class MovieDayController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/movieDay');
    }


    /**
     * 列表
     */
    public function listAction()
    {
        if ($this->isPost()) {
            $result = $this->movieDayRepository->getList($_REQUEST);
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
            $result = $this->movieDayRepository->getDetail($id);
            $this->view->setVar('row',json_encode($result));
        }
    }

    /**
     * 保存
     * @throws BusinessException
     */
    public function saveAction()
    {
        $result = $this->movieDayRepository->save($_POST);
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
        $ids = explode(',', $idStr);
        if($act=='del') {
            foreach ($ids as $id) {
                $this->movieDayRepository->delete($id);
            }
        }elseif ($act == 'update'){
            $label = $this->getRequest('label','string','');
            $update=[];
            if($label!==''){$update['label']=strval($label);}
            if(empty($update)){
                return $this->sendErrorResult("请输入您要修改的内容!");
            }
            foreach ($ids as $id) {
                $update['_id'] = intval($id);
                $this->movieDayRepository->update($update);
            }
        }
        $this->sendSuccessResult();
    }

    /**
     * 批量设置
     */
    public function updateAction()
    {
        $this->view->setVar('ids',$this->getRequest('ids','string'));
    }

}