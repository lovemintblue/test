<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\CartoonTagRepository;
use App\Repositories\Backend\MovieTagRepository;

/**
 *
 *
 * @package App\Controller\Backend
 *
 * @property  MovieTagRepository $movieTagRepository
 */
class MovieTagController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/movieTag');
    }

    /**
     * 公共
     */
    public function initData()
    {
        $this->view->setVar('series',CommonValues::getSeries());
        $this->view->setVar('attribute',CommonValues::getMovieTagAttribute());
        $this->view->setVar('hot',CommonValues::getHot());
        $this->view->setVar('tags',$this->movieTagRepository->getByParentId());
    }


    /**
     * 列表
     */
    public function listAction()
    {
        if ($this->isPost()) {
            $result = $this->movieTagRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->initData();
    }

    /**
     * 详情
     * @throws BusinessException
     */
    public function detailAction()
    {
        $id = $this->getRequest("_id");
        if (!empty($id)) {
            $result = $this->movieTagRepository->getDetail($id);
            $this->view->setVar('row',json_encode($result));
        }
        $this->initData();
    }

    /**
     * 保存
     * @throws BusinessException
     */
    public function saveAction()
    {
        $result = $this->movieTagRepository->save($_POST);
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
                $this->movieTagRepository->delete($id);
            }
        }elseif($act=='update'){
            $ids = explode(',', $idStr);
            $parentId = $this->getRequest('parent_id','string');
            $attribute = $this->getRequest('attribute','string');
            $series = $this->getRequest('series','string');
            $isHot = $this->getRequest('is_hot','string');
            foreach ($ids as $id) {
                $data = [
                    '_id'      => intval($id)
                ];
                if($parentId!=''){$data['parent_id'] = intval($parentId);}
                if($attribute!=''){$data['attribute'] = strval($attribute);}
                if($series!=''){$data['series'] = strval($series);}
                if($isHot!=''){$data['is_hot'] = intval($isHot);}
                $this->movieTagRepository->movieTagService->save($data);
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
        $this->initData();
    }
}