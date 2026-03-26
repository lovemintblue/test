<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Constants\CommonValues;
use App\Controller\BaseBackendController;
use App\Exception\BusinessException;
use App\Repositories\Backend\PlayRepository;
use App\Services\PlayService;

/**
 * 玩法管理
 *
 * @package App\Controller\Backend
 *
 * @property  PlayRepository $playRepository
 * @property  PlayService $playService
 */
class PlayController extends BaseBackendController
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->checkPermission('/play');
    }

    public function initData($type)
    {
        $this->view->setVar('type', $type);
//        $this->view->setVar('tagArr', $this->playService->tagArr[$type]);
        $this->view->setVar('cityArr', $this->playService->cityArr);
        $this->view->setVar('statusArr',CommonValues::getPlayStatus());
        $this->view->setVar('payTypeArr',CommonValues::getPayTypes());
    }

    /**
     * 游戏
     */
    public function gameAction()
    {
        if ($this->isPost()) {
            $result = $this->playRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->view->setVar('type', 'game');
        $this->view->pick('play/list');
    }

    /**
     * 裸聊
     */
    public function luoliaoAction()
    {
        if ($this->isPost()) {
            $result = $this->playRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->initData('luoliao');
        $this->view->pick('play/list');
    }

    /**
     * 约炮
     */
    public function yuepaoAction()
    {
        if ($this->isPost()) {
            $result = $this->playRepository->getList($_REQUEST);
            $this->sendSuccessResult($result);
        }
        $this->initData('yuepao');
        $this->view->pick('play/list');
    }

    /**
     * 详情
     * @throws BusinessException
     */
    public function detailAction()
    {
        $id = $this->getRequest("_id");
        $type = $this->getRequest("type");
        if (!empty($id)) {
            $result = $this->playRepository->getDetail($id);
            if($result['params']){$result['params']=implode("\n", $result['params']);}
            $this->view->setVar('row', $result);
        }
        $this->initData($type);
    }

    /**
     * 保存
     * @throws BusinessException
     */
    public function saveAction()
    {
        $result = $this->playRepository->save($_POST);
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
                $this->playRepository->delete($id);
            }
        }elseif ($act == 'update') {
            $money   = $this->getRequest('money');
            $status  = $this->getRequest('status');
            $contact  = $this->getRequest('contact');
            if($money!=''){
                $update['money'] = intval($money==0?-1:$money);//vip转换为免费
                $update['pay_type'] = CommonValues::getPayTypeByMoney($update['money']);
            }
            if($status!=''){$update['status']=intval($status);}
            if($contact!=''){$update['contact']=strval($contact);}
            if(empty($update)){
                return $this->sendErrorResult("请输入您要修改的内容!");
            }
            $ids = explode(',', $idStr);
            foreach ($ids as $id) {
                $update['_id'] = intval($id);
                $this->playRepository->update($update);
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
        $this->initData($this->getRequest("type"));
    }

}