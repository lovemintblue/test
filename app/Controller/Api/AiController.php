<?php

namespace App\Controller\Api;

use App\Controller\BaseApiController;
use App\Repositories\Api\AiRepository;
use App\Services\UserActiveService;

/**
 * Ai秀
 * Class AiController
 * @property AiRepository $aiRepository
 * @property UserActiveService $userActiveService
 * @package App\Controller\Api
 */
class AiController extends BaseApiController
{
    /**
     * 主页
     */
    public function homeAction()
    {
        $result = $this->aiRepository->getHome();
        $this->sendSuccessResult($result);
    }

    /**
     * 获取ai配置
     */
    public function configsAction()
    {
        $configs =$this->aiRepository->getConfigs();
        $this->sendSuccessResult($configs);
    }

    /**
     * 分类
     */
    public function categoriesAction()
    {
        $position = $this->getRequest('position');
        if(empty($position)){
            $this->sendErrorResult('参数错误!');
        }
        $result = $this->aiRepository->getCategories($position);
        $this->sendSuccessResult($result);
    }

    /**
     * 资源模版
     * @return void
     */
    public function resourceTemplateAction()
    {
        $catId = $this->getRequest('cat_id');
        $position = $this->getRequest('position');
        $page = $this->getRequest('page','int',1);
        $pageSize = $this->getRequest('page_size','int',16);
        if(empty($catId)&&empty($position)){
            $this->sendErrorResult('参数错误!');
        }
        $result =$this->aiRepository->resourceTemplate($catId,$position,$page,$pageSize);
        $this->sendSuccessResult($result);
    }

    /**
     * 提示词模版
     * @return void
     */
    public function promptWordsAction()
    {
        $result =$this->aiRepository->promptWords();
        $this->sendSuccessResult($result);
    }

    /**
     * 随机内容模版
     * @return void
     */
    public function contentTemplateAction()
    {
        $userId = $this->getUserId();
        $result =$this->aiRepository->contentTemplate($userId);
        $this->sendSuccessResult($result);
    }

    /**
     * 图片换脸
     * @return void
     */
    public function doFaceImageAction()
    {
        $userId = $this->getUserId();
        $this->aiRepository->doFaceImage($userId,$_REQUEST);
        $this->sendSuccessResult();
    }

    /**
     * 视频换脸
     * @return void
     */
    public function doFaceVideoAction()
    {
        $userId = $this->getUserId();
        $this->aiRepository->doFaceVideo($userId,$_REQUEST);
        $this->sendSuccessResult();
    }

    /**
     * 去衣
     * @return void
     */
    public function doUndressAction()
    {
        $userId = $this->getUserId();
        $this->aiRepository->doUndress($userId,$_REQUEST);
        $this->sendSuccessResult();
    }

    /**
     * 换装
     * @return void
     */
    public function doChangeAction()
    {
        $userId = $this->getUserId();
        $this->aiRepository->doChange($userId,$_REQUEST);
        $this->sendSuccessResult();
    }

    /**
     * 绘画
     * @return void
     */
    public function doGenerateAction()
    {
        $userId = $this->getUserId();
        $this->aiRepository->doGenerate($userId,$_REQUEST);
        $this->sendSuccessResult();
    }

    /**
     * 小说
     * @return void
     */
    public function doNovelAction()
    {
        $userId = $this->getUserId();
        $this->aiRepository->doNovel($userId,$_REQUEST);
        $this->sendSuccessResult();
    }

    /**
     * 表情
     * @return void
     */
    public function doEmojiAction()
    {
        $userId = $this->getUserId();
        $this->aiRepository->doEmoji($userId,$_REQUEST);
        $this->sendSuccessResult();
    }

    /**
     * 图生视频
     * @return void
     */
    public function doImageToVideoAction()
    {
        $userId = $this->getUserId();
        $this->aiRepository->doImageToVideo($userId,$_REQUEST);
        $this->sendSuccessResult();
    }

    /**
     * 语音
     * @return void
     */
    public function doVoiceAction()
    {
        $userId = $this->getUserId();
        $this->aiRepository->doVoice($userId,$_REQUEST);
        $this->sendSuccessResult();
    }

    /**
     * ai女友授权url
     * @return void
     * @throws \App\Exception\BusinessException
     */
    public function getGirlFriendAuthUrlAction()
    {
        $userId = $this->getUserId();
        $result = $this->aiRepository->getGirlFriendAuthUrl($userId);
        $this->sendSuccessResult($result);
    }

    /**
     * 带出余额
     * @return void
     * @throws \App\Exception\BusinessException
     */
    public function girlFriendBringOutAssetsAction()
    {
        $userId = $this->getUserId();
        $this->aiRepository->girlFriendBringOutAssets($userId);
        $this->sendSuccessResult();
    }

    /**
     * 棋牌游戏授权url
     * @return void
     * @throws \App\Exception\BusinessException
     */
    public function getGameQpAuthUrlAction()
    {
        $userId = $this->getUserId();
        $result = $this->aiRepository->getGameQpAuthUrl($userId,$this->apiService->getDeviceType());
        $this->sendSuccessResult($result);
    }

    /**
     * Ai 发送消息
     */
    public function sendAiMessageAction()
    {
        $userId = $this->getUserId();
        $type = $this->getRequest('type','string','text');
        $aiModel = $this->getRequest('ai_model','string','');
        $content = $this->getRequest('content','string','');
        if (empty($aiModel) || empty($type) || empty($content)) {
            $this->sendErrorResult("请检查输入完整性!");
        }
        if (mb_strlen($content, 'utf8') > 100) {
            $this->sendErrorResult("输入不能超过100个字!");
        }
        $result = $this->aiRepository->sendAiMessage($userId, $aiModel, $type, $content);
        $this->sendSuccessResult(['reply_content'=>$result]);
    }

    /**
     * Ai 消息列表
     */
    public function aiMessagesAction()
    {
        $userId     = $this->getUserId();
        $page       = $this->getRequest('page','int',1);
        $result = $this->aiRepository->getAiMessages($userId,$page);
        $this->sendSuccessResult($result);
    }

    /**
     * 我的作品
     */
    public function myAction()
    {
        $userId = $this->getUserId();
        $result =$this->aiRepository->getMy($userId);
        $this->sendSuccessResult($result);
    }

    /**
     * 作品搜索
     */
    public function searchAction()
    {
        $userId = $this->getUserId();
        $result = $this->aiRepository->doSearch($userId,$_REQUEST);
        $this->sendSuccessResult($result);
    }

    /**
     * 作品详情
     */
    public function detailAction()
    {
        $userId = $this->getUserId();
        $id     = $this->getRequest('id','string');
        $result = $this->aiRepository->getDetail($id,$userId);
        $this->userActiveService->do($userId);
        $this->sendSuccessResult($result);
    }

    /**
     * 删除作品
     */
    public function deleteAction()
    {
        $userId = $this->getUserId();
        $ids = $this->getRequest('ids');
        $position = $this->getRequest('position');
        if(empty($ids)||empty($position)){$this->sendErrorResult('参数错误');}
        $this->aiRepository->doDelete($userId,$position,$ids);
        $this->sendSuccessResult();
    }

}