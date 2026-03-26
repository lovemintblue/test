<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\BaseApiController;
use App\Exception\BusinessException;
use App\Repositories\Api\MovieRepository;
use App\Repositories\Api\PlayRepository;
use App\Repositories\Api\SystemRepository;
use App\Utils\CaptchaUtil;
use App\Utils\LogUtil;

/**
 *系统相关接口
 * Class SystemController
 * @package App\Controller\Api
 * @property SystemRepository $systemRepository
 * @property MovieRepository $movieRepository
 * @property PlayRepository $playRepository
 */
class SystemController extends BaseApiController
{

    /**
     * @throws \App\Exception\BusinessException
     */
    public function infoAction()
    {
        $data = array(
            'device_info' => $this->getRequest('device_info'),
            'app_code' => $this->getRequest('app_code'),
            'clipboard_text' => $this->getRequest('clipboard_text'),
            'channel_code' => $this->getRequest('channel_code'),
            'captcha_key' => $this->getRequest('captcha_key'),
            'captcha_value' => $this->getRequest('captcha_value'),
            'ad_method' => $this->getRequest('ad_method','int',1)
        );
        $info = $this->systemRepository->info($data);
        $info['normal_video_nav'] = $this->systemRepository->getBlockPosByGroup('normal');
        $info['cartoon_video_nav'] = $this->systemRepository->getBlockPosByGroup('cartoon');
        $info['dark_video_nav'] = $this->systemRepository->getBlockPosByGroup('dark');
        $info['post_nav'] = $this->systemRepository->getPostBlock('normal');
        $info['comics_nav'] = $this->systemRepository->getBlockPosByGroup('comics');
        $info['novel_nav'] = $this->systemRepository->getBlockPosByGroup('novel');
        $info['short_nav'] = $this->systemRepository->getBlockPosByGroup('short');

        $this->sendSuccessResult($info);
    }

    /**
     * cdn更新接口
     */
    public function cdnAction()
    {
        $name = $this->getRequest("name");
        if (empty($name)) {
            $this->sendErrorResult("参数错误!");
        }
        $this->sendSuccessResult();
    }

    /**
     * 文章列表
     */
    public function articleAction()
    {
        $result = $this->systemRepository->getArticleList();
        $this->sendSuccessResult($result);
    }

    /**
     * 短信接口
     * @throws BusinessException
     */
    public function sendSmsAction()
    {
        $type = $this->getRequest("type");
        $phone = $this->getRequest("phone");
        $token = $this->getToken();
        $result = $this->systemRepository->doSendSms($phone, $type, $token['user_id']);
        if ($result) {
            $this->sendSuccessResult();
        }
        $this->sendErrorResult('短信发送错误!');
    }

    /**
     * 应用中心
     */
    public function appStoreAction()
    {
        $page = $this->getRequest('page', 'int', 1);
        $result = $this->systemRepository->getAppStores($page, 15);
        $ads = $this->systemRepository->getAdsByCode('appstore_banner', 'n', 6);
        $result = array(
            'ads' => empty($ads) ? [] : $ads,
            'items' => empty($result) ? [] : $result
        );
        $this->sendSuccessResult($result);
    }

    /**
     * 上报错误数据
     */
    public function reportErrorAction()
    {
        $this->sendSuccessResult();
        $content = $this->getRequest('content');
        $this->systemRepository->addAppError($content);
        $this->sendSuccessResult();
    }

    /**
     * 事件上报
     */
    public function eventAction()
    {
        $type = $this->getRequest('type');
        $data = $this->getRequest('type');
        $this->sendSuccessResult();
    }

    /**
     * 去关注|取消关注
     * @throws \App\Exception\BusinessException
     */
    public function doFollowAction()
    {
        $userId = $this->getUserId();
        $objectId = $this->getRequest('object_id', 'string');
        $objectType = $this->getRequest('object_type', 'string');
        if (empty($objectId) || empty($objectType)) {
            $this->sendErrorResult('请检查参数!');
        }
        $result = $this->systemRepository->doFollow($userId, $objectId, $objectType);
        $this->sendSuccessResult(array('status' => $result ? 'y' : 'n'));
    }

    /**
     * 图片验证码
     */
    public function captchaAction()
    {
        $key = $this->getRequest("key");
        if (empty($key)) {
            $this->sendErrorResult("参数错误!");
        }
        $captcha = new CaptchaUtil();
        $captcha->setOutBase64(true);
        $data = [
            'key' => $key,
            'value' => $captcha->doImg()
        ];
        $key = 'captcha_image_' . $key;
        setCache($key, $captcha->getCode(), 60 * 10);
        $this->sendSuccessResult($data);
    }

    /**
     * 解除限流
     */
    public function unlockAction()
    {
        $key = $this->getRequest('key');
        $value = $this->getRequest('value');
        if (empty($key) || empty($value)) {
            $this->sendErrorResult("解除错误!");
        }
        $key = 'captcha_image_' . $key;
        $checkValue = getCache($key);
        if (empty($checkValue) || strtolower($checkValue) != strtolower($value)) {
            $this->sendErrorResult("解除错误!");
        }
        $userId = $this->getUserId(false);
        $key = 'user_comics_detail_' . $userId;
        $this->systemRepository->commonService->unlockActionLimit($key);

        $key = 'user_novel_detail_'.$userId;
        $this->systemRepository->commonService->unlockActionLimit($key);
        $this->sendSuccessResult();
    }

    /**
     * 数据跟踪
     */
    public function trackAction()
    {
        $userId = $this->getUserId();
        $type = $this->getRequest('object_type');
        $id   = $this->getRequest('object_id');
        $name   = $this->getRequest('object_name');
        $this->systemRepository->addTrackQueue($userId,$type,$id,$name);
        $this->sendSuccessResult();
    }

    /**
     * 用户行为
     */
    public function doLogsAction()
    {
        $userId = $this->getUserId();
        $act = $this->getRequest('act');
        $this->systemRepository->addActQueue($userId,$act);
        $this->sendSuccessResult();
    }
}