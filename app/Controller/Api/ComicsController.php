<?php


namespace App\Controller\Api;

use App\Controller\BaseApiController;
use App\Repositories\Api\ComicsRepository;
use App\Repositories\Api\SystemRepository;
use App\Services\UserActService;

/**
 *  漫画
 * Class ComicsController
 * @property  SystemRepository  $systemRepository
 * @property  ComicsRepository $comicsRepo
 * @property  UserActService $userActService
 * @package App\Controller\Api
 */
class ComicsController  extends BaseApiController
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
            'banner' => $this->systemRepository->getAdsByCode('comics_banner','n',100),
            'block' => array(),
            'buttons' => array()
        );
        if($code=='mh_tuijian'){
            $result['buttons'] = $this->comicsRepo->getHomeButtons();
        }
        $result['block'] = $this->comicsRepo->getBlockList($code,$page,12);
        $this->sendSuccessResult($result);
    }

    /**
     * 模块列表
     */
    public function blockListAction()
    {
        $result = $this->comicsRepo->doSearchBlocks($_REQUEST);
        $this->sendSuccessResult(empty($result)?[]:$result);
    }

    /**
     * 每日更新
     */
    public function dayInfoAction()
    {
        $result = $this->comicsRepo->getDayPageInfo();
        $this->sendSuccessResult($result);
    }

    /**
     * 搜索数据
     */
    public function searchAction()
    {
        $result = $this->comicsRepo->doSearch(null,$_REQUEST);
        $this->sendSuccessResult(empty($result)?[]:$result['data']);
    }

    /**
     * 热门关键字
     */
    public function keywordsAction()
    {
        $result = $this->comicsRepo->getHotKeywords(10);
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
        $result = $this->comicsRepo->getSearchFilter();
        $this->sendSuccessResult($result);
    }

    /**
     * 详情接口
     */
    public function detailAction()
    {
        $userId = $this->getUserId();
        $id     = $this->getRequest('id','string');
        $result = $this->comicsRepo->getDetail($id,$userId);
        $this->userActService->addActQueue($userId,'comics_detail');
        $this->sendSuccessResult($result);
    }

    /**
     * 章节详情
     */
    public function chapterDetailAction()
    {
        $userId = $this->getUserId();
        $id     = $this->getRequest('id','string');
        $result = $this->comicsRepo->getChapterDetail($id,$userId);
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
        $result= $this->comicsRepo->doFavorite($userId,$id);
        $this->sendSuccessResult(['status'=> $result?'y':'n']);
    }

    /**
     * 收藏列表
     */
    public function favoriteAction()
    {
        $userId= $this->getUserId();
        $page    = $this->getRequest('page','int',1);
        $result= $this->comicsRepo->getFavorites($userId,$page);
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
        $this->comicsRepo->delFavorites($userId,$ids);
        $this->sendSuccessResult();
    }


    /**
     * 删除播放记录
     */
    public function delHistoryAction()
    {
        $userId     = $this->getUserId();
        $ids        = $this->getRequest('ids');
        $this->comicsRepo->delHistories($userId,$ids);
        $this->sendSuccessResult();
    }

    /**
     * 播放记录
     */
    public function historyAction()
    {
        $userId     = $this->getUserId();
        $page       = $this->getRequest('page','int',1);
        $result     = $this->comicsRepo->getHistories($userId,$page);
        $this->sendSuccessResult($result);
    }

    /**
     * 购买日志
     */
    public function buyLogsAction()
    {
        $userId     = $this->getUserId();
        $page       = $this->getRequest('page','int',1);
        $result     = $this->comicsRepo->getBuyLog($userId,$page);
        $this->sendSuccessResult($result);
    }


}