<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\ComicsRepository;
use App\Repositories\Backend\ComicsTagRepository;
use App\Repositories\Backend\NovelRepository;

/**
 *
 *
 * @package App\Controller\Backend
 *
 * @property  NovelRepository $novelRepo
 */
class NovelController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/novel');
    }

    /**
     * 公共
     */
    public function initData()
    {
        $this->view->setVar('hotArr', CommonValues::getHot());
        $this->view->setVar('newArr', CommonValues::getNew());
        $this->view->setVar('payTypeArr', CommonValues::getPayTypes());
        $this->view->setVar('statusArr', CommonValues::getNovelStatus());
        $this->view->setVar('updateStatusArr', CommonValues::getNovelUpdateStatus());
        $this->view->setVar('catArr', CommonValues::getNovelCategories());
        $this->view->setVar('tagArr', $this->novelRepo->getGroupAttrAll());
    }


    /**
     * 列表
     */
    public function listAction()
    {
        if ($this->isPost()) {
            $result = $this->novelRepo->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('defaultStatus', 1);
        $this->initData();
    }

    /**
     * 仓库
     */
    public function warehouseAction()
    {
        if ($this->isPost()) {
            $result = $this->novelRepo->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('defaultStatus', 0);
        $this->initData();
        $this->view->pick('novel/list');
    }

    /**
     * 视频回收站
     */
    public function recycleAction()
    {
        if ($this->isPost()) {
            $result = $this->novelRepo->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('defaultStatus', -1);
        $this->initData();
        $this->view->pick('novel/list');
    }

    /**
     * 详情
     * @throws BusinessException
     */
    public function detailAction()
    {
        $id = $this->getRequest("_id");
        if (!empty($id)) {
            $result = $this->novelRepo->getDetail($id);
            $this->view->setVar('row', $result);
            $this->view->setVar('chapterList',$this->novelRepo->getChapterList($id));
        }
        $this->initData();
    }

    /**
     * 章节详情
     */
    public function chapterDetailAction()
    {
        $id = $this->getRequest("id");
        if (empty($id)) {
           $this->sendErrorResult('章节错误!');
        }
        $chapter = $this->novelRepo->getChapterDetail($id);
        if(empty($chapter)){
            $this->sendErrorResult('章节错误!');
        }
        $this->sendSuccessResult($chapter);
    }

    /**
     * 保存
     * @throws BusinessException
     */
    public function saveAction()
    {
        $result = $this->novelRepo->save($_POST);
        if ($result) {
            $this->sendSuccessResult();
        }
        $this->sendErrorResult("保存错误!");
    }


    /**
     * 批量操作
     */
    public function doAction()
    {
        $idStr = $this->getRequest("id");
        $act = $this->getRequest("act");
        if (empty($idStr) || empty($act)) {
            $this->sendErrorResult("操作错误!");
        }
        $update = array();
        if ($act == 'del') {
            $ids = explode(',', $idStr);
            foreach ($ids as $id) {
                $this->novelRepo->delete($id);
            }
        } else if ($act == 'up') {
            $update = [
                'status' => 1,
                'show_at' => time(),
            ];
        } else if ($act == 'down') {
            $update = [
                'status' => -1,
            ];
        } elseif ($act == 'update') {
            $status = $this->getRequest('status', 'string', '');
            $isHot = $this->getRequest('is_hot', 'string', '');
            $isNew = $this->getRequest('is_new', 'string', '');
            $showAt = $this->getRequest('show_at', 'string', '');
            $money = $this->getRequest('money', 'string', '');
            $catId = $this->getRequest('cat_id', 'string', '');
            $click = $this->getRequest('click', 'string', '');
            $favorite = $this->getRequest('favorite', 'string', '');
            $sort = $this->getRequest('sort', 'string', '');
            $tags = empty($_REQUEST['tags']) ? array() : $_REQUEST['tags'];
            $newTags = array();
            foreach ($tags as $tag) {
                if ($tag) {
                    $newTags[] = $tag * 1;
                }
            }
            if ($status !== "") {
                $update['status'] = intval($status);
            }
            if ($isHot !== "") {
                $update['is_hot'] = intval($isHot);
            }
            if ($isNew !== "") {
                $update['is_new'] = intval($isNew);
            }
            if ($isNew !== "") {
                $update['is_new'] = intval($isNew);
            }
            if ($click !== '') {
                $update['click'] = intval($click);
            }
            if ($favorite !== '') {
                $update['favorite'] = intval($favorite);
            }
            if ($sort !== '') {
                $update['sort'] = intval($sort);
            }
            if ($showAt) {
                $update['show_at'] = strtotime($showAt);
            }
            if ($catId) {
                $update['cat_id'] = $catId;
            }
            if ($newTags) {
                $update['tags'] = $newTags;
            }
            if ($money !== "") {
                $update['money'] = intval($money);
                $update['pay_type'] = CommonValues::getPayTypeByMoney($money);
            }
        }
        $ids = explode(',', $idStr);
        foreach ($ids as $id) {
            if (!empty($update)) {
                $update['_id'] = $id;
                $this->novelRepo->novelService->save($update);
            }
            $this->novelRepo->asyncEs($id);
            $keyName = 'comics_detail_' . $id;
            delCache($keyName);
        }
        $this->sendSuccessResult();
    }

    /**
     * 批量设置
     */
    public function updateAction()
    {
        $this->view->setVar('ids', $this->getRequest('ids', 'string'));
        $this->initData();
    }

    /**
     * 同步数据
     */
    public function asyncAction()
    {
        $ids = $this->getRequest('ids');
        if (empty($ids)) {
            $this->sendErrorResult("参数错误!");
        }
        $ids = explode("\n", $ids);
        foreach ($ids as $id) {
            $this->novelRepo->asyncMrs(trim($id));
        }
        $this->sendSuccessResult();
    }
}