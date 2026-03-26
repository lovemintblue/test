<?php


namespace App\Controller\Api;

use App\Controller\BaseApiController;
use App\Repositories\Api\ComicsRepository;
use App\Repositories\Api\NovelRepository;
use App\Repositories\Api\SystemRepository;
use App\Services\UserActService;

/**
 *  小说
 * Class ComicsController
 * @property  SystemRepository  $systemRepository
 * @property  NovelRepository $novelRepo
 * @property  UserActService $userActService
 * @package App\Controller\Api
 */
class NovelController  extends BaseApiController
{
    /**
     * 首页-推荐
     */
    public function homeAction()
    {
        $page   = $this->getRequest("page",'int',1);
        $code  = $this->getRequest('code','string');
        if(empty($code)){
            $this->sendErrorResult('必要参数错误!');
        }
        $result = array(
            'banner' => $this->systemRepository->getAdsByCode('novel_banner','n',100),
            'block' => array()
        );
        $result['block'] = $this->novelRepo->getBlockList($code,$page,12);
        $this->sendSuccessResult($result);
    }

    /**
     * 模块列表
     */
    public function blockListAction()
    {
        $result = $this->novelRepo->doSearchBlocks($_REQUEST);
        $this->sendSuccessResult(empty($result)?[]:$result);
    }


    /**
     * 搜索数据
     */
    public function searchAction()
    {
        $result = $this->novelRepo->doSearch(null,$_REQUEST);
        $this->sendSuccessResult(empty($result)?[]:$result['data']);
    }

    /**
     * 热门关键字
     */
    public function keywordsAction()
    {
        $result = $this->novelRepo->getHotKeywords(10);
        $this->sendSuccessResult(array(
            'items'=>empty($result)?array():$result,
            'ads'=> $this->systemRepository->getAdsByCode('search_page','n',3)
        ));
    }

    /**
     * 搜索条件接口
     */
    public function filterAction()
    {
       // $userId = $this->getUserId();
        $result = $this->novelRepo->getSearchFilter();
        $this->sendSuccessResult($result);
    }

    /**
     * 详情接口
     */
    public function detailAction()
    {
        $userId = $this->getUserId();
        $id     = $this->getRequest('id','string');
        $result = $this->novelRepo->getDetail($id,$userId);
        $this->userActService->addActQueue($userId,'novel_detail');
        $this->sendSuccessResult($result);
    }

    /**
     * 章节详情
     */
    public function chapterDetailAction()
    {
        $userId = $this->getUserId();
        $id     = $this->getRequest('id','string');
        $result = $this->novelRepo->getChapterDetail($id,$userId);
        $this->sendSuccessResult($result);
    }

    /**
     * 去收藏
     * @throws \App\Exception\BusinessException
     */
    public function doFavoriteAction()
    {
        $userId= $this->getUserId();
        $id    = $this->getRequest('id');
        if(empty($id)){$this->sendErrorResult('参数错误');}
        $result= $this->novelRepo->doFavorite($userId,$id);
        $this->sendSuccessResult(['status'=> $result?'y':'n']);
    }

    /**
     * 收藏列表
     */
    public function favoriteAction()
    {
        $userId= $this->getUserId();
        $page    = $this->getRequest('page','int',1);
        $result= $this->novelRepo->getFavorites($userId,$page);
        $this->sendSuccessResult($result);
    }

    /**
     * 删除收藏
     */
    public function delFavoriteAction()
    {
        $userId= $this->getUserId();
        $ids    = $this->getRequest('ids');
        if(empty($ids)){$this->sendErrorResult('参数错误');}
        $this->novelRepo->delFavorites($userId,$ids);
        $this->sendSuccessResult();
    }


    /**
     * 删除播放记录
     */
    public function delHistoryAction()
    {
        $userId     = $this->getUserId();
        $ids        = $this->getRequest('ids');
        $this->novelRepo->delHistories($userId,$ids);
        $this->sendSuccessResult();
    }

    /**
     * 播放记录
     */
    public function historyAction()
    {
        $userId     = $this->getUserId();
        $page       = $this->getRequest('page','int',1);
        $result     = $this->novelRepo->getHistories($userId,$page);
        $this->sendSuccessResult($result);
    }

    /**
     * 购买日志
     */
    public function buyLogsAction()
    {
        $userId     = $this->getUserId();
        $page       = $this->getRequest('page','int',1);
        $result     = $this->novelRepo->getBuyLog($userId,$page);
        $this->sendSuccessResult($result);
    }


}