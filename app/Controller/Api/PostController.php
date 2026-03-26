<?php


namespace App\Controller\Api;


use App\Controller\BaseApiController;
use App\Repositories\Api\PostRepository;
use App\Repositories\Api\UserRepository;
use App\Services\UserActService;

/**
 * Class PostController
 * @property PostRepository $postRepo
 * @property UserRepository $userRepo
 * @property UserActService $userActService
 * @package App\Controller\Api
 */
class PostController extends BaseApiController
{
    /**
     * 获取圈子
     */
    public function categoriesAction()
    {
        $userId = $this->getUserId();
        $position = $this->getRequest('position','string','normal');
        $result = $this->postRepo->getCategoriesByPosition($position,$userId);
        $this->sendSuccessResult($result);
    }


    /**
     * 圈子详情
     * @throws \App\Exception\BusinessException
     */
    public function categoryAction()
    {
        $userId     = $this->getUserId();
        $catId       = $this->getRequest('id');
        $result =  $this->postRepo->getCategoryDetail($catId,$userId);
        $this->sendSuccessResult($result);
    }

    /**
     * 获取关注圈子
     */
    public function getFollowCategoriesAction()
    {
        $userId     = $this->getUserId();
        $page      = $this->getRequest('page','int',10);
        $result =  $this->postRepo->getFollowCategories($userId,$page);
        $this->sendSuccessResult($result);
    }

    /**
     * 模块详情
     */
    public function blockAction()
    {
        $userId     = $this->getUserId();
        $blockId       = $this->getRequest('id');
        $result = $this->postRepo->getCategoriesByBlockId($blockId,$userId);
        if(empty($result)){
            $this->sendErrorResult('模块不存在!');
        }
        $this->sendSuccessResult(array(
            'block_id' => $result[0]['block_id'],
            'block_name' => $result[0]['block_name'],
            'categories' => $result
        ));
    }


    /**
     * 发布帖子
     */
    public function saveAction()
    {
        $userId = $this->getUserId();
        $this->postRepo->savePost($userId,$_REQUEST);
        $this->sendSuccessResult();
    }

    /**
     * 社区主页
     */
    public function homeAction()
    {
        $userId = $this->getUserId();
        $result = $this->postRepo->getHome($userId,$_REQUEST);
        $this->sendSuccessResult($result);
    }

    /**
     * 搜索条件接口
     */
    public function filterAction()
    {
        $userId = $this->getUserId();
        $result = $this->postRepo->getSearchFilter($userId);
        $this->sendSuccessResult($result);
    }

    /**
     * 搜索数据
     */
    public function searchAction()
    {
        $userId = $this->getUserId();
        $result = $this->postRepo->doSearch($userId,$_REQUEST);
        $this->sendSuccessResult($result);
    }

    /**
     * 详情
     */
    public function detailAction()
    {
        $userId = $this->getUserId();
        $id   = $this->getRequest('id');
        if(empty($id)){
            $this->sendErrorResult('参数错误!');
        }
        $result = $this->postRepo->getDetail($userId,$id);
        $this->userActService->addActQueue($userId,'post_detail');
        $this->sendSuccessResult($result);
    }

    /**
     * 购买
     * @throws \App\Exception\BusinessException
     */
    public function doBuyAction()
    {
        $userId     = $this->getUserId();
        $postId     = $this->getRequest('id');
        $this->postRepo->doBuy($userId,$postId);
        $this->sendSuccessResult();
    }

    /**
     * 购买记录
     */
    public function buyLogsAction()
    {
        $userId     = $this->getUserId();
        $page       = $this->getRequest('page','int',1);
        $result     = $this->postRepo->getBuyLog($userId,$page);
        $this->sendSuccessResult($result);
    }

    /**
     * 点赞帖子
     * @throws \App\Exception\BusinessException
     */
    public function doLoveAction()
    {
        $userId     = $this->getUserId();
        $postId       = $this->getRequest('id');
        $result     = $this->postRepo->doLove($userId,$postId);
        $this->sendSuccessResult(array(
            'status' => $result?'y':'n'
        ));
    }

    /**
     * 收藏帖子
     * @throws \App\Exception\BusinessException
     */
    public function doFavoriteAction()
    {
        $userId     = $this->getUserId();
        $postId       = $this->getRequest('id');
        $result     = $this->postRepo->doFavorite($userId,$postId);
        $this->sendSuccessResult(array(
            'status' => $result?'y':'n'
        ));
    }

    /**
     * 收藏列表
     */
    public function favoriteAction()
    {
        $userId     = $this->getUserId();
        $page       = $this->getRequest('page','int',1);
        $result     = $this->postRepo->getFavorites($userId,$page);
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
        $this->postRepo->delFavorites($userId,$ids);
        $this->sendSuccessResult();
    }

    /**
     * 我的帖子
     */
    public function myAction()
    {
        $userId = $this->getUserId();
        $filter = array(
            'is_all' =>true,
            'status' => isset($_REQUEST['status']) && $_REQUEST['status']!==""?intval($_REQUEST['status']):1,
            'home_id' => $userId,
            'page'   => $this->getRequest('page','int',1)
        );
        $result = $this->postRepo->doSearch($userId,$filter);
        $this->sendSuccessResult($result);
    }


    /**
     * 删除
     * @throws \App\Exception\BusinessException
     */
    public function deleteAction()
    {
        $userId = $this->getUserId();
        $id = $this->getRequest('id');
        $this->postRepo->doDelete($userId,$id);
        $this->sendSuccessResult();
    }


    /**
     * 获取ai配置
     */
    public function aiConfigsAction()
    {
        $configs =$this->postRepo->getAiConfigs();
        $this->sendSuccessResult($configs);
    }

    /**
     * Ai 去衣
     * @throws \App\Exception\BusinessException
     */
    public function doQuyiAction()
    {
        $userId = $this->getUserId();
        $content = $this->getRequest('content');
        $images = $this->getRequest('images');
        $isPublic = $this->getRequest('is_public');
        if(empty($content) || empty($images)){
            $this->sendErrorResult('参数错误!');
        }
        $this->postRepo->doAiQuyi($userId,$images,$content,$isPublic=='y'?true:false);
        $this->sendSuccessResult();
    }

    /**
     * Ai 绘画
     * @throws \App\Exception\BusinessException
     */
    public function doHuihuaAction()
    {
        $userId = $this->getUserId();
        $content = $this->getRequest('content');
        $images = $this->getRequest('images');
        $num = $this->getRequest('num');
        $isPublic = $this->getRequest('is_public');
        if(empty($content) || empty($images) || $num<1){
            $this->sendErrorResult('参数错误!');
        }
        $this->postRepo->doAiHuihua($userId,$images,$content,$num,$isPublic=='y'?true:false);
        $this->sendSuccessResult();
    }
    /**
     * Ai 换脸
     * @throws \App\Exception\BusinessException
     */
    public function doChangeAction()
    {
        $userId = $this->getUserId();
        $content = $this->getRequest('content');
        $images = $this->getRequest('images');
        $sourceImages = $this->getRequest('source_images');
        $isPublic = $this->getRequest('is_public');
        if(empty($content) || empty($images) || empty($sourceImages)){
            $this->sendErrorResult('参数错误!');
        }
        $this->postRepo->doAiChange($userId,$images,$content,$sourceImages,$isPublic=='y'?true:false);
        $this->sendSuccessResult();
    }

    /**
     * Ai 换脸
     * @throws \App\Exception\BusinessException
     */
    public function doChangeVideoAction()
    {
        $userId = $this->getUserId();
        $content = $this->getRequest('content');
        $images = $this->getRequest('images');
        $videoImage = $this->getRequest('video_image');
        $videoValue = $this->getRequest('video_value');
        $isPublic = $this->getRequest('is_public');
        if(empty($content) || empty($images) || empty($videoImage)){
            $this->sendErrorResult('参数错误!');
        }
        $this->postRepo->doAiChangeVideo($userId,$images,$content,$videoImage,$videoValue,$isPublic=='y'?true:false);
        $this->sendSuccessResult();
    }

    /**
     * 我的AI定制
     */
    public function myAiAction()
    {
        $userId = $this->getUserId();
        $page = $this->getRequest('page','int',1);
        $result =$this->postRepo->getAiLogs($userId,$page);
        $this->sendSuccessResult($result);
    }


}