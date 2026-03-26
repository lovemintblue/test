<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\PostRepository;
use App\Repositories\Backend\PostCategoryRepository;
use App\Services\PostCategoryService;

/**
 * 帖子
 *
 * @package App\Controller\Backend
 * @property  PostRepository $postRepository
 * @property  PostCategoryService $postCategoryService
 * @property  PostCategoryRepository $postCategoryRepository
 */
class PostController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/post');
    }

    protected function initData()
    {
        $this->view->setVar('categoryItems',$this->postCategoryRepository->getAll());
        $this->view->setVar('statusArr',CommonValues::getPostStatus());
        $this->view->setVar('payTypeArr',CommonValues::getPayTypes());
        $this->view->setVar('topArr',CommonValues::getTop());
        $this->view->setVar('hotArr',CommonValues::getHot());
        $this->view->setVar('positionArr',CommonValues::getPostPosition());
    }

    /**
     * 列表
     */
    public function listAction()
    {
        if($this->isPost()){
            $result = $this->postRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->initData();
    }

    /**
     * 列表
     */
    public function aiAction()
    {
        if($this->isPost()){
            $result = $this->postRepository->getList($_REQUEST,true);
            $this->sendSuccessResult($result);
        }
        $this->initData();
        $this->view->pick('post/list');
    }

    /**
     * 详情
     * @throws BusinessException
     */
    public function detailAction()
    {
        $id = $this->getRequest("_id");
        if (!empty($id)) {
            $result = $this->postRepository->getDetail($id);
            $this->view->setVar('row', $result);
        }
        /***视频转帖子时候传递的是视频的mid**/
        $mid = $this->getRequest('mid');
        if(!empty($mid)){
            $result = getCache('post_mid_'.$mid);
            if($result){
                $this->view->setVar('row', $result);
            }
        }
        $this->initData();
    }

    /**
     * 保存
     * @throws BusinessException
     */
    public function saveAction()
    {
        $result = $this->postRepository->save($_POST);
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
                $this->postRepository->delete($id);
            }
            return $this->sendSuccessResult();
        } elseif ($act == 'update') {
            $isHot   = $this->getRequest('is_hot');
            $isTop   = $this->getRequest('is_top');
            $status  = $this->getRequest('status');
            $createdAt = $this->getRequest('created_at');
            $categories = $this->getRequest('categories');
            $money = $this->getRequest('money');
            $userId     = $this->getRequest('user_id','int',0);
            if($userId>0){$update['user_id']=intval($userId);}
            if($isHot!==''){$update['is_hot']=intval($isHot);}
            if($isTop!==''){$update['is_top']=intval($isTop);}
            if($status!==''){$update['status']=intval($status);}
            if($money!==''){
                $update['money']=intval($money);
                $update['pay_type'] = CommonValues::getPayTypeByMoney($money);
            }
            if($createdAt){$update['created_at']=strtotime($createdAt);}
            if(!empty($categories)){
                foreach ($categories as $category){
                    $update['categories'][]= intval($category);
                }
                $postCategories = $this->postCategoryService->getAll();
                if(empty($postCategories[$update['categories'][0]])){
                    return $this->sendErrorResult("所属帖子板块不存在!");
                }
                $update['position'] = $postCategories[$update['categories'][0]]['position'];
                if(in_array($update['position'],['file','game'])){
                    return $this->sendErrorResult("种子和游戏不支持批量更新板块!");
                }
            }
            if(empty($update)){
                return $this->sendErrorResult("请输入您要修改的内容!");
            }
        } else if($act=='es') {

        }

        $ids = explode(',', $idStr);
        foreach ($ids as $id) {
            $update['_id'] = $id;
            $this->postRepository->update($update);
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

    /**
     *  视频转帖子
     */
    public function changeFromMovieAction()
    {
        $id = $this->getRequest('id','string');
        if(empty($id)){
            $this->sendErrorResult('请输入视频编号!');
        }
        $result= $this->postRepository->changeFromMovie($id);
        if($result){
            $this->sendSuccessResult();
        }
        $this->sendErrorResult('视频转帖子失败,请检查视频是否存在或者是否单剧集!');
    }

    /**
     * 同步帖子
     */
    public function asyncFromMrsAction()
    {
        $id = $this->getRequest('id','string');
        if(empty($id)){
            $this->sendErrorResult('请输入帖子编号!');
        }
        $result= $this->postRepository->asyncFromMrs($id);
        if($result){
            $this->sendSuccessResult();
        }
        $this->sendErrorResult('同步帖子失败,请检查帖子是否存!');
    }

}