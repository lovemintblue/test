<?php

namespace App\Controller\Api;

use App\Constants\StatusCode;
use App\Controller\BaseApiController;
use App\Repositories\Api\PlayRepository;
use App\Services\UserActiveService;

/**
 * 玩法管理
 *
 * @property PlayRepository $playRepository
 * @property UserActiveService $userActiveService
 * @package App\Controller\Api
 */
class PlayController extends BaseApiController
{
    /**
     * 搜索
     */
    public function searchAction()
    {
        $userId = $this->getUserId();
        $result = $this->playRepository->doSearch($_REQUEST)['data'];
        $this->sendSuccessResult($result);
    }

    /**
     * 详情
     * @throws \App\Exception\BusinessException
     */
    public function detailAction()
    {
        $userId = $this->getUserId();
        $id     = $this->getRequest('id','int');
        $result = $this->playRepository->getDetail($id,$userId);
        $this->userActiveService->do($userId);
        $this->sendSuccessResult($result);
    }

    /**
     * 发布
     * @throws \App\Exception\BusinessException
     */
    public function saveAction()
    {
        $userId = $this->getUserId();
        $this->playRepository->doSave($userId,$_REQUEST);
        $this->sendSuccessResult();
    }

    /**
     * 去收藏
     * @throws \App\Exception\BusinessException
     */
    public function doFavoriteAction()
    {
        $userId= $this->getUserId();
        $id    = $this->getRequest('id','int');
        if(empty($id)){$this->sendErrorResult('参数错误');}
        $result= $this->playRepository->doFavorite($userId,$id);
        $this->sendSuccessResult(['status'=> $result?'y':'n']);
    }

    /**
     * 购买
     * @throws \App\Exception\BusinessException
     */
    public function doBuyAction()
    {
        $userId    = $this->getUserId();
        $playId    = $this->getRequest('id','int');
        $this->playRepository->doPlay($userId,$playId);
        $this->sendSuccessResult();
    }
}