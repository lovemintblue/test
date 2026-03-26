<?php


namespace App\Controller\Api;


use App\Constants\CommonValues;
use App\Controller\BaseApiController;
use App\Repositories\Api\MovieRepository;
use App\Repositories\Api\SystemRepository;
use App\Services\UserActiveService;
use App\Services\UserActService;

/**
 * Class MovieController
 * @property MovieRepository $movieRepository
 * @property UserActiveService $userActiveService
 * @property SystemRepository $systemRepository
 * @property UserActService $userActService
 * @package App\Controller\Api
 */
class MovieController extends BaseApiController
{
    /**
     * 首页-推荐
     */
    public function homeAction()
    {
        $page   = $this->getRequest("page",'int',1);
        $position= $this->getRequest('position','string');
        $code  = $this->getRequest('code','string');
        if(!in_array($position,array_keys(CommonValues::getMoviePosition()))){
            $this->sendErrorResult('参数错误!');
        }
        if(empty($code)){
            $this->sendErrorResult('必要参数错误!');
        }
        $result = array(
            'banner' => $this->systemRepository->getAdsByCode('home_banner_'.$position,'n',100),
            'block' => array()
        );
        $result['block'] = $this->movieRepository->getBlockList($code,$page,$position,12);
        $this->sendSuccessResult($result);
    }

    /**
     * 首页-推荐
     */
    public function blockAction()
    {
        $page   = $this->getRequest("page",'int',1);
        $position= $this->getRequest('position','string');
        $code  = $this->getRequest('code','string');
        if(!in_array($position,['normal','deep','dark'])){
            $this->sendErrorResult('参数错误!');
        }
        if(empty($code)){
            $this->sendErrorResult('必要参数错误!');
        }
        $result = array(
            'banner' => $this->systemRepository->getAdsByCode('home_banner_'.$position,'n',100),
            'block' => array()
        );
        $result['block'] = $this->movieRepository->getBlockList($code,$page,$position,12);
        $this->sendSuccessResult($result);
    }


    /**
     * 热门关键字
     */
    public function keywordsAction()
    {
        $position = $this->getRequest("position","string","normal");
        $result = $this->movieRepository->getHotKeywords(10,$position);
        $this->sendSuccessResult(array(
            'items'=>empty($result)?array():$result,
            'ads'=> $this->systemRepository->getAdsByCode('search_page','n',3)
        ));
    }


    /**
     * 专题列表
     */
    public function specialListAction()
    {
        $userId = $this->getUserId();
        $page   = $this->getRequest('page','int',1);
        $position= $this->getRequest('position','string');
        if(!in_array($position,array_keys($this->movieRepository->movieSpecialService->position))){
            $this->sendErrorResult('参数错误');
        }
        $result = $this->movieRepository->specialList($position,$page);
        $this->sendSuccessResult($result);
    }

    /**
     * 专题详情
     * @throws \App\Exception\BusinessException
     */
    public function specialDetailAction()
    {
        $userId = $this->getUserId();
        $id     = $this->getRequest('id','int');
        $result = $this->movieRepository->specialDetail($id);
        $this->sendSuccessResult($result);
    }

    /**
     * @throws \App\Exception\BusinessException
     */
    public function detailAction()
    {
        $userId = $this->getUserId();
        $id     = $this->getRequest('id','string');
        $linkId     = $this->getRequest('link_id','string');
        $adCode     = $this->getRequest('ad_code','string');
        $result = $this->movieRepository->getDetail($id,$userId,$linkId,$adCode);
        $this->userActiveService->do($userId);
        $this->userActService->addActQueue($userId,'movie_detail');
        $this->sendSuccessResult($result);
    }

    /**
     * 搜索条件接口
     */
    public function filterAction()
    {
        $userId = $this->getUserId();
        $position = $this->getRequest("position");
        if(empty($position)){
            $this->sendErrorResult("参数错误!");
        }
        $result = $this->movieRepository->getSearchFilter($userId,$position);
        $this->sendSuccessResult($result);
    }

    /**
     * 搜索
     */
    public function searchAction()
    {
        $userId = $this->getUserId();
        $result = $this->movieRepository->doSearch($userId,$_REQUEST)['data'];
        $this->sendSuccessResult($result);
    }

    /**
     * 收藏列表
     */
    public function favoriteAction()
    {
        $userId= $this->getUserId();
        $page    = $this->getRequest('page','int',1);
        $result= $this->movieRepository->getFavorites($userId,$page);
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
        $result= $this->movieRepository->doFavorite($userId,$id);
        $this->sendSuccessResult(['status'=> $result?'y':'n']);
    }

    /**
     * 删除收藏
     */
    public function delFavoriteAction()
    {
        $userId= $this->getUserId();
        $ids    = $this->getRequest('ids');
        if(empty($ids)){$this->sendErrorResult('参数错误');}
        $this->movieRepository->delFavorites($userId,$ids);
        $this->sendSuccessResult();
    }

    /**
     * 去收藏
     * @throws \App\Exception\BusinessException
     */
    public function doLoveAction()
    {
        $userId= $this->getUserId();
        $id    = $this->getRequest('id');
        if(empty($id)){$this->sendErrorResult('参数错误');}
        $result= $this->movieRepository->doLove($userId,$id);
        $this->sendSuccessResult(['status'=> $result?'y':'n']);
    }


    /**
     * 购买视频
     * @throws \App\Exception\BusinessException
     */
    public function doBuyAction()
    {
        $userId     = $this->getUserId();
        $movieId    = $this->getRequest('id');
        $linkId    = $this->getRequest('link_id');
        $this->movieRepository->doBuy($userId,$movieId,$linkId);
        $this->sendSuccessResult();
    }

    /**
     * 购买日志
     */
    public function buyLogsAction()
    {
        $userId     = $this->getUserId();
        $page       = $this->getRequest('page','int',1);
        $result     = $this->movieRepository->getBuyLog($userId,$page);
        $this->sendSuccessResult($result);
    }

    /**
     * 添加播放记录
     */
    public function doHistoryAction()
    {
        $userId     = $this->getUserId();
        $movieId    = $this->getRequest('id');
        $time       = $this->getRequest('time','int');
        $this->movieRepository->doHistory($userId,$movieId,$time);
        $this->sendSuccessResult();
    }
    /**
     * 删除播放记录
     */
    public function delHistoryAction()
    {
        $userId     = $this->getUserId();
        $ids        = $this->getRequest('ids');
        $this->movieRepository->delHistories($userId,$ids);
        $this->sendSuccessResult();
    }

    /**
     * 播放记录
     */
    public function historyAction()
    {
        $userId     = $this->getUserId();
        $page       = $this->getRequest('page','int',1);
        $result     = $this->movieRepository->getHistories($userId,$page);
        $this->sendSuccessResult($result);
    }

    /**
     * 下载视频
     * @throws \App\Exception\BusinessException
     */
    public function doDownloadAction()
    {
        $userId     = $this->getUserId();
        $id         = $this->getRequest('id','string');
        $result     = $this->movieRepository->doDownload($userId,$id);
        $this->sendSuccessResult($result);
    }

    /**
     * 下载列表
     */
    public function downloadAction()
    {
        $userId     = $this->getUserId();
        $page       = $this->getRequest('page','int',1);
        $result     = $this->movieRepository->getDownloadList($userId,$page);
        $this->sendSuccessResult($result);
    }

    /**
     * 删除下载
     */
    public function delDownloadAction()
    {
        $userId     = $this->getUserId();
        $ids        = $this->getRequest('ids','string');
        $this->movieRepository->delDownload($userId,$ids);
        $this->sendSuccessResult();
    }

    /**
     * 发布视频的表情
     */
    public function tagsAction()
    {
        $result =$this->movieRepository->getTags('all',true);
        $this->sendSuccessResult(empty($result)?array():$result);
    }

    /**
     * 我的视频
     */
    public function myAction()
    {
        $userId = $this->getUserId();
        $result = $this->movieRepository->getMyList($_REQUEST,$userId);
        $this->sendSuccessResult($result);
    }

    /**
     * 删除视频
     */
    public function deleteAction()
    {
        $userId = $this->getUserId();
        $movieId = $this->getRequest('id');
        $this->movieRepository->delMy($userId,$movieId);
        $this->sendSuccessResult();
    }

    /**
     * 添加
     */
    public function addAction()
    {
        $userId = $this->getUserId();
        $result = $this->movieRepository->doAdd($userId,$_REQUEST);
        if(!empty($result)){
            $this->sendSuccessResult();
        }
        $this->sendErrorResult('发布视频错误!');
    }
}