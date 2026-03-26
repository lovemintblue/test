<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Repositories\Backend\MovieRepository;
use App\Repositories\Backend\MovieCategoryRepository;
use App\Repositories\Backend\MovieTagRepository;
use App\Services\CdnService;


/**
 * 视频
 * Class MovieController
 * @package App\Controller\Backend
 * @property MovieRepository $movieRepository
 * @property MovieCategoryRepository $movieCategoryRepository
 * @property MovieTagRepository $movieTagRepository
 * @property CdnService $cdnService
 */
class MovieController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/movie');
    }

    protected function initData(){
        $this->view->setVar('statusArr',CommonValues::getMovieStatus());
        $this->view->setVar('catArr',$this->movieCategoryRepository->getAll());
        $this->view->setVar('tagArr',$this->movieTagRepository->getGroupAttrAll());
        $this->view->setVar('hotArr',CommonValues::getHot());
        $this->view->setVar('newArr',CommonValues::getNew());
        $this->view->setVar('posArr',CommonValues::getMoviePosition());
        $this->view->setVar('canvasArr',CommonValues::getMovieCanvas());
        $this->view->setVar('payTypeArr',CommonValues::getPayTypes());
        $this->view->setVar('linkTypeArr',CommonValues::getMovieLinkType());
        $this->view->setVar('sourceArr',CommonValues::getMovieSource());
    }

    /**
     * 列表
     */
    public function listAction()
    {
        if($this->isPost()){
            $result = $this->movieRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('defaultStatus',1);
        $this->initData();
    }

    /**
     * 仓库
     */
    public function warehouseAction()
    {
        if($this->isPost()){
            $result = $this->movieRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('defaultStatus',0);
        $this->initData();
        $this->view->pick('movie/list');
    }

    /**
     * 视频审核
     */
    public function reviewAction()
    {
        if($this->isPost()){
            $result = $this->movieRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('defaultStatus',2);
        $this->initData();
        $this->view->pick('movie/list');
    }

    /**
     * 视频回收站
     */
    public function recycleAction()
    {
        if($this->isPost()){
            $result = $this->movieRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('defaultStatus',-1);
        $this->initData();
        $this->view->pick('movie/list');
    }

    /**
     * 详情
     * @throws \App\Exception\BusinessException
     */
    public function detailAction()
    {
        $id = $this->getRequest("_id");
        if (!empty($id)) {
            $result = $this->movieRepository->getDetail($id);
            if($result['source']=='laosiji'){
                $this->view->setVar('mediaPath',container()->get('config')->mrs->laosiji_aws_path);
                $this->view->setVar('mediaUrlVideoSign',$this->cdnService->getLsjUrl('',''));
            }
            $this->view->setVar('row',$result);
        }
        $this->initData();
    }

    /**
     * 剧集
     * @throws \App\Exception\BusinessException
     */
    public function moreLinkAction()
    {
        $id = $this->getRequest("_id");
        if (!empty($id)) {
            $result = $this->movieRepository->getLinks($id);
            $this->view->setVar('rows',$result);
            $result = $this->movieRepository->getDetail($id);
            if($result['source']=='laosiji'){
                $this->view->setVar('mediaPath',container()->get('config')->mrs->laosiji_aws_path);
                $this->view->setVar('mediaUrlVideoSign',$this->cdnService->getLsjUrl('',''));
            }
            $this->view->setVar('movie',$result);
        }
        $this->initData();
    }

    /**
     * 保存
     * @throws \App\Exception\BusinessException
     */
    public function saveAction()
    {
        $result = $this->movieRepository->save($_POST);
        if ($result) {
            return $this->sendSuccessResult();
        }
        return $this->sendErrorResult("保存错误!");
    }

    /**
     * 同步媒资库
     */
    public function asyncAction()
    {
        $idStr = $this->getRequest("id");
        $source = $this->getRequest("source");
        if(empty($idStr)){
            $this->sendErrorResult("请输入媒资库ID!");
        }
        if(empty($source)){
            $this->sendErrorResult("请选择视频来源!");
        }
        $ids = explode("\n",$idStr);
        foreach ($ids as $id)
        {
            $this->movieRepository->asyncMrs($id,$source);
        }
        $this->sendSuccessResult();
    }

    /**
     * 同步公共媒资库
     */
    public function asyncCommonAction()
    {
        $idStr = $this->getRequest("id");
        if(empty($idStr)){
            $this->sendErrorResult("请输入媒资库ID!");
        }
        $ids = explode("\n",$idStr);
        foreach ($ids as $id)
        {
            $this->movieRepository->asyncCommonMrs($id);
        }
        $this->sendSuccessResult();

    }

    /**
     * 各种操作
     */
    public function doAction()
    {
        $ids = $this->getRequest("id");
        $act = $this->getRequest("act");

        if (empty($ids) || empty($act)) {
            return $this->sendErrorResult("参数错误!");
        }
        if($act=='del') {
            $ids = explode(',', $ids);
            foreach ($ids as $id) {
                $this->movieRepository->delete($id);
            }
            return $this->sendSuccessResult();
        }else if($act=='up') {
            $update = [
                'status'    => 1,
                'show_at'   => time(),
            ];
        } else if($act=='down') {
            $update = [
                'status'    => -1,
            ];
        } else if($act=='clearTag') {
            $update = [
                'tags'    => [],
            ];
        } elseif ($act == 'update') {
            $tags       =$_REQUEST['tags'];
            $categories =$this->getRequest('categories','string','');
            $click      =$this->getRequest('click','string','');
            $money      =$this->getRequest('money','string','');
            $favorite   =$this->getRequest('favorite','string','');
            $sort       =$this->getRequest('sort','string','');
            $isHot      =$this->getRequest('is_hot','string','');
            $isNew      =$this->getRequest('is_new','string','');
            $position   =$this->getRequest('position','string');
            $status     =$this->getRequest('status','string','');
            $showAt     =$this->getRequest('show_at','string');
            $number     =$this->getRequest('number','string');
            $canvas     =$this->getRequest('canvas','string');
            $userId     = $this->getRequest('user_id','int',0);
            $update=[];
            if($number!==''){$update['number']=strval($number);}
            if($categories!==''){$update['categories']=intval($categories);}
            if($click!==''){$update['click']=intval($click);}
            if($favorite!==''){$update['favorite']=intval($favorite);}
            if($sort!==''){$update['sort']=intval($sort);}
            if($isHot!==''){$update['is_hot']=intval($isHot);}
            if($isNew!==''){$update['is_new']=intval($isNew);}
            if($status!==''){$update['status']=intval($status);}
            if($position!==''){$update['position']=$position;}
            if($canvas!==''){$update['canvas']=$canvas;}
            if($showAt!==''){$update['show_at']=intval(strtotime($showAt));}
            if($userId){$update['user_id']=$userId;}
            if($money!==''){
                $update['money']=intval($money);
                $update['pay_type']=CommonValues::getPayTypeByMoney($money);
            }
            if($update||$tags){
                if($tags){
                    foreach ($tags as &$tag) {
                        if(empty($tag)){continue;}
                        $tag=intval($tag);
                        unset($tag);
                    }
                    $ids = explode(',', $ids);
                    foreach ($ids as $id) {
                        if(empty($id)){continue;}
                        $movie = $this->movieRepository->movieService->findByID($id);
                        if(!empty($movie['tags'])){
                            $update['tags']= array_unique(array_merge($movie['tags']?:[],$tags));
                        }else{
                            $update['tags']= $tags;
                        }
                        $update['tags']=array_values($update['tags']);
                        $update['_id'] = $id;
                        $this->movieRepository->update($update);
                        $this->movieRepository->asyncEs($id);
                        $keyName = 'movie_detail_' . $id;
                        delCache($keyName);
                    }
                    return $this->sendSuccessResult();
                }
            }

            if(empty($update)){
                return $this->sendErrorResult("请输入您要修改的内容!");
            }
        } else if($act=='es') {

        }
        $ids = explode(',', $ids);
        foreach ($ids as $id) {
            if (!empty($update)) {
                $update['_id'] =$id;
                $this->movieRepository->update($update);
            }
            $this->movieRepository->asyncEs($id);
            $keyName = 'movie_detail_' . $id;
            delCache($keyName);
        }
        return $this->sendSuccessResult();

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
     * 更改链接是否免费
     */
    public function updateLinksAction()
    {
        $movieId = $this->getRequest('movie_id');
        $freeLinks = $_REQUEST['free_links'];
        $result = $this->movieRepository->updateLinks($movieId,$freeLinks);
        if($result){
            $this->sendSuccessResult();
        }
        $this->sendErrorResult('更新错误!');
    }

    /**
     * widget
     */
    public function widgetAction()
    {
        $type = $this->getRequest('type','string');
        if(empty($type)){
            $this->sendErrorResult("参数错误!");
        }
        $this->initData();
        $this->view->pick("movie/widget/{$type}");
    }


}