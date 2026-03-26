<?php

namespace App\Repositories\Api;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\AdvService;
use App\Services\AiBlockService;
use App\Services\AiCategoryService;
use App\Services\AiContentTemplateService;
use App\Services\AiMessageService;
use App\Services\AiPromptWordService;
use App\Services\AiResourceTemplateService;
use App\Services\AiService;
use App\Services\ApiService;
use App\Services\CommonService;
use App\Services\UserService;
use App\Utils\CommonUtil;

/**
 * Ai秀
 * Class AiRepository
 * @property AiService $aiService
 * @property AiBlockService $aiBlockService
 * @property ApiService $apiService
 * @property AiCategoryService $aiCategoryService
 * @property AiMessageService $aiMessageService
 * @property AiResourceTemplateService $aiResourceTemplateService
 * @property AiContentTemplateService $aiContentTemplateService
 * @property AiPromptWordService $aiPromptWordService
 * @property AdvService $advService
 * @property CommonService $commonService
 * @property UserService $userService
 * @package App\Repositories\Api
 */
class AiRepository extends BaseRepository
{
    /**
     *  Ai主页
     * @return array
     */
    public function getHome()
    {
        return [
//            'banner'=>$this->advService->getAll('post_banner','n',15),
            'banner'=>[],
            'top_nav'=>value(function(){
                $topNav = [];
                $blocks = $this->aiBlockService->getAll();
                $aiCategories = $this->aiCategoryService->getAll(true,'',true);
                foreach($blocks as $block){
                    if($this->apiService->getVersion()<$block['min_version']){continue;}
                    $categories = [];
                    $filter = ['position'=>$block['position']];
                    foreach($aiCategories as $aiCategory){
                        if($aiCategory['position']!=$block['position']){continue;}
                        $categories[] = [
                            'id'=>strval($aiCategory['id']),
                            'name'=>strval($aiCategory['name']),
                            'filter'=>json_encode(array_merge($filter,['cat_id'=>$aiCategory['id']])),
                        ];
                    }
                    $topNav[] = [
                        'name'=>strval($block['name']),
                        'img_x'=>$this->commonService->getCdnUrl($block['img_x']),
                        'url'=>strval($block['url']),
                        'ico'=>strval($block['ico']),
                        'type'=>strval($block['position']),
                        'filter'=>json_encode($filter),
                        'categories'=>$categories
                    ];
                }
                return $topNav;
            }),
            'sort_nav'=>[
                ['name'=>'推荐','key'=>'order','value'=>'sort'],
                ['name'=>'最新','key'=>'order','value'=>'new'],
                ['name'=>'点击','key'=>'order','value'=>'hot'],
                ['name'=>'随机','key'=>'order','value'=>'rand'],
            ],
        ];
    }

    /**
     * 获取分类
     * @param $position
     * @return array
     */
    public function getCategories($position)
    {
        $categories = [];
        $aiCategories = $this->aiCategoryService->getAll(true,'',true);
        foreach($aiCategories as $aiCategory){
            if($aiCategory['position']!=$position){continue;}
            $categories[] = [
                'id'=>strval($aiCategory['id']),
                'name'=>strval($aiCategory['name']),
                'filter'=>json_encode(['position'=>$position,'cat_id'=>$aiCategory['id']]),
            ];
        }
        return $categories;
    }

    /**
     * ai相关配置
     * @return array
     */
    public function getConfigs()
    {
        return $this->aiService->getConfigs();
    }

    /**
     * 资源模版
     * @param $catId
     * @param $position
     * @param $page
     * @param $pageSize
     * @return array
     */
    public function resourceTemplate($catId,$position,$page,$pageSize=16)
    {
        $query = [
            'is_disabled'=>0,
        ];
        if(!empty($catId)){
            $query['categories'] = intval($catId);
        }
        if(!empty($position)){
            $query['position'] = strval($position);
        }
        $rows = $this->aiResourceTemplateService->getList($query,[],['sort'=>-1],($page-1)*$pageSize,$pageSize);
        $result = [];
        foreach ($rows as $row) {
            $row = [
                'id'        => strval($row['_id']),
                'name'      => strval($row['name']),
                'tips'      => strval($row['tips']),
                'is_porn'   => strval($row['is_porn']?'y':'n'),
                'image_url' => $this->commonService->getCdnUrl($row['img']),
                'image_value' => strval($row['img']),
                'video_url' => $this->commonService->getVideoCdnUrl($row['video']),
                'video_value' => strval($row['video']),
                'money'=> strval($row['money']*1),
                'width'=> strval($row['width']?:16),
                'height'=> strval($row['height']?:9),
            ];
            $result[] = $row;
        }
        return $result;
    }

    /**
     * 提示词
     * @return array
     */
    public function promptWords()
    {
        return $this->aiPromptWordService->getGroupAttrAll();
    }

    /**
     * 随机内容
     * @return array
     */
    public function contentTemplate($userId)
    {
        return $this->aiContentTemplateService->getRandContent($userId);
    }

    /**
     * 图片换脸
     * @param $userId
     * @param $data
     * @return true
     * @throws BusinessException
     */
    public function doFaceImage($userId,$data)
    {
        $images = $this->getRequest($data,'images');//人脸参照
        $templateId = $this->getRequest($data,'template_id');//模版id
        $sourceImages = $this->getRequest($data,'source_images');//图片地址
        if(empty($images) || (empty($templateId)&&empty($sourceImages))){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }
        if(!empty($templateId)){
            $template = $this->aiResourceTemplateService->findByID($templateId);
            if(empty($template)||$template['is_disabled']!=0){
                throw  new BusinessException(StatusCode::PARAMETER_ERROR, '模版异常!');
            }
            $money = $template['money'];
            $sourceImages = $template['img'];
        }else{
            $configs = $this->getConfigs();
            $money = $configs['ai_face_image_price'];
        }

        return $this->aiService->doSave($userId,[
            'remark'=>'AI图片换脸',
            'position'=>'face_image',
            'money'=>intval($money),
            'num'=>1,
            'extra'=>[
                'template_id'=>$templateId,
                'source-path'=>$images,
                'target-path'=>$sourceImages,
            ]
        ]);
    }

    /**
     * 视频换脸
     * @param $userId
     * @param $data
     * @return true
     * @throws BusinessException
     */
    public function doFaceVideo($userId,$data)
    {
        $images = $this->getRequest($data,'images');//人脸参照
        $templateId = $this->getRequest($data,'template_id');//模版id
        if(empty($images) || empty($templateId)){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }
        $template = $this->aiResourceTemplateService->findByID($templateId);
        if(empty($template)||$template['is_disabled']!=0){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '模版异常!');
        }
        $money = $template['money'];
        $video = $template['video'];

        return $this->aiService->doSave($userId,[
            'remark'=>'AI视频换脸',
            'position'=>'face_video',
            'money'=>intval($money),
            'num'=>1,
            'extra'=>[
                'template_id'=>$templateId,
                'source-path'=>$images,
                'target-path'=>$video,
            ]
        ]);
    }

    /**
     * 去衣
     * @param $userId
     * @param $data
     * @return true
     * @throws BusinessException
     */
    public function doUndress($userId,$data)
    {
        $images = $this->getRequest($data,'images');
        $method = $this->getRequest($data,'method');//method_1 method_2
        if(empty($images) || empty($method)){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }

        $configs = $this->getConfigs();
        $money = $configs['ai_undress_price'];

        return $this->aiService->doSave($userId,[
            'remark'=>'AI去衣',
            'position'=>'undress',
            'money'=>intval($money),
            'num'=>1,
            'extra'=>[
                'source-path'=>$images,
                'method'=>$method,
            ]
        ]);
    }

    /**
     * 换装
     * @param $userId
     * @param $data
     * @return true
     * @throws BusinessException
     */
    public function doChange($userId,$data)
    {
        $images = $this->getRequest($data,'images');
        $templateId = $this->getRequest($data,'template_id');//模版id
        if(empty($images) || empty($templateId)){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }

        $template = $this->aiResourceTemplateService->findByID($templateId);
        if(empty($template)||$template['is_disabled']!=0){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '模版异常!');
        }
        $money = $template['money'];
        $method = $template['aid'];
        $sourceImages = $template['img'];

        return $this->aiService->doSave($userId,[
            'remark'=>'AI换装',
            'position'=>'change',
            'money'=>intval($money),
            'num'=>1,
            'extra'=>[
                'template_id'=>$templateId,
                'source-path'=>$images,
                'target-path'=>$sourceImages,
                'method'=>$method,
            ]
        ]);
    }

    /**
     * 绘画
     * @param $userId
     * @param $data
     * @return true
     * @throws BusinessException
     */
    public function doGenerate($userId,$data)
    {
        $prompt = $this->getRequest($data,'prompt');
        $content = $this->getRequest($data,'content');
        $size = $this->getRequest($data,'size');
        $images = $this->getRequest($data,'images');
        $templateId = $this->getRequest($data,'template_id');//模版id
        $num = $this->getRequest($data,'num');
        if(empty($prompt) || empty($size) || empty($templateId) || $num<1){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }

        $template = $this->aiResourceTemplateService->findByID($templateId);
        if(empty($template)||$template['is_disabled']!=0){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '模版异常!');
        }
        $money = $template['money'];
        $method = $template['aid'];
        $sourceImages = $template['img'];

        return $this->aiService->doSave($userId,[
            'remark'=>'AI绘画',
            'position'=>'generate',
            'money'=>intval($money),
            'num'=>intval($num),
            'extra'=>[
                'template_id'=>$templateId,
                'source-path'=>$images,
                'target-path'=>$sourceImages,
                'method'=>$method,
                'content'=>$content,
                'prompt'=>$prompt,
                'size'=>$size,
            ]
        ]);
    }

    /**
     * 小说
     * @param $userId
     * @param $data
     * @return true
     * @throws BusinessException
     */
    public function doNovel($userId,$data)
    {
        $background = $this->getRequest($data,'background');//人物设定
        $scene = $this->getRequest($data,'scene');//场景地点
        $story = $this->getRequest($data,'story');//故事情节
        $description = $this->getRequest($data,'description');//细节说明
        $method  = $this->getRequest($data,'method');//ai作者
        if(empty($description)||empty($story)||empty($method)){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }

        $configs = $this->getConfigs();
        $money = $configs['ai_novel_price'];

        return $this->aiService->doSave($userId,[
            'remark'=>'AI小说',
            'position'=>'novel',
            'money'=>intval($money),
            'num'=>1,
            'extra'=>[
                'background'=>$background,
                'scene'=>$scene,
                'story'=>$story,
                'description'=>$description,
                'method'=>$method,
            ]
        ]);
    }

    /**
     * 表情
     * @param $userId
     * @param $data
     * @return true
     * @throws BusinessException
     */
    public function doEmoji($userId,$data)
    {
        $images = $this->getRequest($data,'images');//人脸参照
        $templateId = $this->getRequest($data,'template_id');//模版id
        if(empty($images) || empty($templateId)){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }

        $template = $this->aiResourceTemplateService->findByID($templateId);
        if(empty($template)||$template['is_disabled']!=0){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '模版异常!');
        }
        $money = $template['money'];
        $video = $template['video'];

        return $this->aiService->doSave($userId,[
            'remark'=>'AI表情',
            'position'=>'emoji',
            'money'=>intval($money),
            'num'=>1,
            'extra'=>[
                'template_id'=>$templateId,
                'source-path'=>$images,
                'target-path'=>$video,
            ]
        ]);
    }

    /**
     * 图生视频
     * @param $userId
     * @param $data
     * @return true
     * @throws BusinessException
     */
    public function doImageToVideo($userId,$data)
    {
        $images = $this->getRequest($data,'images');
        $templateId = $this->getRequest($data,'template_id');//模版id
        if(empty($images) || empty($templateId)){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '参数错误!');
        }

        $template = $this->aiResourceTemplateService->findByID($templateId);
        if(empty($template)||$template['is_disabled']!=0){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '模版异常!');
        }
        $money = $template['money'];
        $method = $template['aid'];

        return $this->aiService->doSave($userId,[
            'remark'=>'图生视频',
            'position'=>'image_to_video',
            'money'=>intval($money),
            'num'=>1,
            'extra'=>[
                'template_id'=>$templateId,
                'source-path'=>$images,
                'method'=>$method,
            ]
        ]);
    }

    public function doVoice($userId,$data)
    {
        print_r($data);exit;
        $content = $this->getRequest('content');


        $images = $this->getRequest('images');//人脸参照
        $templateId = $this->getRequest('template_id');//模版id
        $isPublic = $this->getRequest('is_public','string','n');
        if(empty($content) || empty($images) || (empty($templateId)&&empty($sourceImages))){
            $this->sendErrorResult('参数错误!');
        }
    }

    /**
     * ai女友授权url
     * @param $userId
     * @return array|false|mixed
     * @throws BusinessException
     */
    public function getGirlFriendAuthUrl($userId)
    {
        return $this->aiService->getGirlFriendAuthUrl($userId);
    }

    /**
     * 带出余额
     * @param $userId
     * @return bool
     * @throws BusinessException
     */
    public function girlFriendBringOutAssets($userId)
    {
        return $this->aiService->girlFriendBringOutAssets($userId);
    }

    /**
     * 棋牌游戏授权url
     * @param $userId
     * @param $deviceType
     * @return array|false|mixed
     * @throws BusinessException
     */
    public function getGameQpAuthUrl($userId,$deviceType)
    {
        return $this->aiService->getGameQpAuthUrl($userId,$deviceType);
    }

    /**
     * Ai 发送消息
     * @param $userId
     * @param $aiModel
     * @param $type
     * @param $content
     * @return bool
     * @throws BusinessException
     */
    public function sendAiMessage($userId, $aiModel, $type, $content)
    {
        return $this->aiMessageService->doSave($userId, $aiModel, $type, $content);
    }

    /**
     * Ai 获取消息
     * @param $userId
     * @param $page
     * @return array
     */
    public function getAiMessages($userId, $page)
    {
        return $this->aiMessageService->getAiMessages($userId, $page);
    }

    /**
     * 我的作品
     * @param $userId
     * @param $position
     * @param $page
     * @return array
     */
    public function getMy($userId)
    {
        $topNav = [];
        $blocks = $this->aiBlockService->getAll();
        foreach($blocks as $block){
            if($this->apiService->getVersion()<$block['min_version']){continue;}
            if(!empty($block['url'])){continue;}
            if($block['position']=='face'){
                $types = CommonValues::getAiFaceType();
                foreach($types as $key=>$val){
                    $topNav[] = [
                        'name'=>strval($val),
                        'type'=>strval($key),
                        'filter'=>json_encode(['home_id'=>$userId,'position'=>strval($key)]),
                    ];
                }
                continue;
            }
            $topNav[] = [
                'name'=>strval($block['name']),
                'type'=>strval($block['position']),
                'filter'=>json_encode(['home_id'=>$userId,'position'=>strval($block['position'])]),
            ];
        }
        return $topNav;
    }

    /**
     * 搜索
     * @param $userId
     * @param array $query
     * @return mixed
     */
    public function doSearch($userId, $query = [])
    {
        $query['page'] = $this->getRequest($query, "page", "int", 1);
        $query['page_size'] = $this->getRequest($query, "page_size", "int", 16);
        $query['home_id'] = $this->getRequest($query, "home_id", "string");
        $query['position'] = $this->getRequest($query, "position", "string");
        return $this->aiService->doSearch($userId,$query);
    }

    /**
     * 作品详情
     * @param $id
     * @param $userId
     * @return array|mixed
     * @throws BusinessException
     */
    public function getDetail($id, $userId)
    {
        if (empty($id)) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '作品不存在!');
        }
        $userInfo = $this->userService->getInfoFromCache($userId);
        $this->userService->checkUser($userInfo);

        $result = $this->aiService->getDetail($id);
        if (empty($result) || $userId!=$result['user_id'] || $result['is_disabled']!=0) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '作品不存在!');
        }
        if (empty($result['out_data'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '作品还在制作中!');
        }

        $extra = (array)$result['extra'];
        $files = [];
        $appendixArr = [];
        $content = '';
        $configs = getConfigs();
        foreach($result['out_data'] as $outData){
            if(in_array($result['position'],['novel'])){
                $content = $outData;
                continue;
            }
            $ext =  CommonUtil::getFileExtName($outData);
            if($ext=='.m3u8'){
                $type = 'video';
                $imageUrl = $this->commonService->getCdnUrl($extra['source-path']);
                $videoUrl = $this->commonService->getVideoCdnUrl($outData);
            }elseif($ext=='.zip'){
                $zipPwd = $result['zip_pwd']?:'56ai.me';
                $appendixArr[] = "资源下载地址:{$configs['resource_url_cdn']}$outData 解压密码是:{$zipPwd}";
                continue;
            }else{
                $type = 'image';
                $imageUrl = $this->commonService->getCdnUrl($outData);
                $videoUrl = '';
            }
            $files[] = [
                'type' => $type,
                'image_url' => $imageUrl,
                'video_url' => $videoUrl,
                'appendix' => '',
                'can_download'=>'y',
            ];
        }
        foreach($files as $key=>&$file){
            $file['appendix'] = $appendixArr[$key];
        }

        $result = [
            'id' => strval($result['_id']),
            'position' => strval($result['position']),
            'content' => strval($content),
            'files' => $files,
        ];
        return $result;
    }

    /**
     * 删除作品
     * @param $userId
     * @param $position
     * @param $ids
     * @return true
     */
    public function doDelete($userId,$position,$ids)
    {
        if ($ids == 'all') {
            $this->aiService->aiModel->updateRaw(['$set'=>['is_disabled'=>1]],['user_id'=>intval($userId),'position'=>strval($position)]);
        } else {
            $ids = explode(',', $ids);
            $this->aiService->aiModel->updateRaw(['$set'=>['is_disabled'=>1]],['_id'=>['$in'=>$ids],'position'=>strval($position)]);
        }
        return true;
    }

}