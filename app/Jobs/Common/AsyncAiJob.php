<?php


namespace App\Jobs\Common;

use App\Constants\CacheKey;
use App\Jobs\BaseJob;
use App\Services\AccountService;
use App\Services\AiService;
use App\Services\ConfigService;
use App\Services\UserService;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 * Class AsyncAiJob
 * @property AiService $aiService
 * @property AccountService $accountService
 * @property UserService $userService
 * @property ConfigService $configService
 * @package App\Jobs\Common
 */
class AsyncAiJob extends BaseJob
{
    private $apiDomain = '';
    private $mediaDomain = '';
    private $apiKey = '';
    private $rate = 1;

    public function handler($uniqid)
    {
        $runTime = 297;//可执行时间/秒
        $startTime = time();
        while (true){
            if (time() - $startTime >= $runTime) {
                break;
            }
            if($this->asyncConfigs()===false)return;
            $rows = $this->aiService->getList(['status'=>['$in'=>[2,3]],'is_disabled'=>0],[],['created_at'=>1],0,2000);
            foreach($rows as $row){
                $this->asyncOne($row);
            }
            sleep(10);
        }
    }

    /**
     * 同步一个
     * @param $row
     * @return false|void
     */
    protected function asyncOne($row)
    {
        $extra = (array)$row['extra'];
        if($row['position']=='face_image'||$row['position']=='face_video'){//图片、视频换脸
            $queryUri = 'ai/queryFace';
            $submitUri = 'ai/face';
            $submitData = [
                'bid'=>strval($row['_id']),
                'title'=>strval($row['order_sn']),
                'source-path'=>$this->mediaDomain.$extra['source-path'],
                'target-path'=>value(function()use($extra,$row){
                    $targetPath = $extra['target-path'];
                    if($row['position']=='face_image'){
                        $targetPath = $this->mediaDomain.$extra['target-path'];
                    }
                    return $targetPath;
                }),
                'fee'=>strval($row['real_money']*$this->rate)
            ];
        }elseif($row['position']=='undress'){//去衣
            $queryUri = 'ai/queryUndress';
            $submitUri = 'ai/undress';
            $submitData = [
                'bid'=>strval($row['_id']),
                'title'=>strval($row['order_sn']),
                'source-path'=>$this->mediaDomain.$extra['source-path'],
                'fee'=>strval($row['real_money']*$this->rate),
                'method'=>strval($extra['method']?:'method_1'),
            ];
        }elseif($row['position']=='change'){//换装
            $queryUri = 'ai/queryChange';
            $submitUri = 'ai/change';
            $submitData = [
                'bid'=>strval($row['_id']),
                'title'=>strval($row['order_sn']),
                'source-path'=>$this->mediaDomain.$extra['source-path'],
                'fee'=>strval($row['real_money']*$this->rate),
                'method'=>strval($extra['method']),
            ];
        }elseif($row['position']=='generate'){//绘画
            $queryUri = 'ai/queryGenerate';
            $submitUri = 'ai/generate';
            $submitData = [
                'bid'=>strval($row['_id']),
                'title'=>strval($row['order_sn']),
                'source-path'=>$extra['source-path']?$this->mediaDomain.$extra['source-path']:'',
                'prompt'=>strval($extra['prompt']),
//                'negative-prompt'=>strval(''),
                'method'=>strval($extra['method']),
                'size'=>strval($extra['size']),
                'batch-count'=>strval($row['num']),
                'batch-size'=>strval(3),
                'fee'=>strval($row['real_money']*$this->rate),
            ];
        }elseif($row['position']=='novel'){//小说
            $queryUri = 'ai/queryNovel';
            $submitUri = 'ai/novel';
            $submitData = [
                'bid'=>strval($row['_id']),
                'title'=>strval($row['order_sn']),
                'background'=>strval($extra['background']),
                'description'=>strval($extra['description']),
                'other'=>strval($extra['scene']?$extra['scene'].' '.$extra['story']:$extra['story']),
                'fee'=>strval($row['real_money']*$this->rate),
                'method'=>strval($extra['method']?:'method_1'),
//                'history'=>strval(''),
            ];
        }elseif($row['position']=='emoji'){//表情
            $queryUri = 'ai/queryBq';
            $submitUri = 'ai/bq';
            $submitData = [
                'bid'=>strval($row['_id']),
                'title'=>strval($row['order_sn']),
                'source-path'=>$this->mediaDomain.$extra['source-path'],
                'target-path'=>strval($extra['target-path']),
                'fee'=>strval($row['real_money']*$this->rate),
            ];
        }elseif($row['position']=='image_to_video'){//图生视频
            $queryUri = 'ai/queryImageToVideo';
            $submitUri = 'ai/imageToVideo';
            $submitData = [
                'bid'=>strval($row['_id']),
                'title'=>strval($row['order_sn']),
                'code'=>strval($extra['method']),
                'source-path'=>$this->mediaDomain.$extra['source-path'],
                'fee'=>strval($row['real_money']*$this->rate),
            ];
        }else{
            return false;
        }

        $queryResult = $this->queryResult($queryUri,$row,$updateData);

        if(empty($queryResult)){
            $result = $this->doHttpRequest($submitData,$submitUri);
            if($result['status']!='y'){
                $updateData['status'] = 0;
                $updateData['error_msg'] = $result['error']??'接口异常！';
            }else{
                $updateData['status'] = 3;
            }
            LogUtil::info("id:{$row['_id']} order_sn:{$row['order_sn']} fee:{$row['real_money']}");
        }

        if(empty($updateData)){return false;}
        $this->aiService->aiModel->updateRaw(['$set'=>$updateData],['_id'=>$row['_id']]);
        delCache("ai_detail_{$row['_id']}");
    }

    /**
     * 查询ai处理结果
     * @param $uri
     * @param $row
     * @param $updateData
     * @return bool
     */
    public function queryResult($uri,$row,&$updateData)
    {
        if($row['status']==2){return false;}
        $queryResult = $this->doHttpRequest([
            'bid'=>strval($row['_id'])
        ],$uri);

        if($queryResult['data']['status']=='-1'){
            $result = 'fail';
            $updateData['status'] = -1;
            $updateData['error_msg'] = $queryResult['error'];
            $updateData['updated_at'] = time();
            $this->aiService->refund($row,$row['real_money']);
        }elseif($queryResult['data']['status']=='2'){
            $result = 'success';
            $updateData['status'] = 1;
            $updateData['error_msg'] = '';
            $updateData['updated_at'] = time();
            $updateData['zip_pwd'] = $queryResult['data']['zip_pwd'];
            if(is_array($queryResult['data']['out_data'])){
                $updateData['out_data'] = $queryResult['data']['out_data'];
            }else{
                $updateData['out_data'][] = $queryResult['data']['out_data'];
            }
        }
        LogUtil::info("id:{$row['_id']} order_sn:{$row['order_sn']} fee:{$row['real_money']} result:".$result??'waiting...');
        return true;
    }

    /**
     * 配置文件
     * @return false|void
     */
    protected function asyncConfigs()
    {
        $configs = getConfigs();
        $this->apiDomain = $configs['ai_api_domain'];
        $this->apiKey = $configs['ai_api_key'];
        $this->mediaDomain = $configs['media_url_open'];
        if(container()->get('config')->app->rate){
            $this->rate = container()->get('config')->app->rate;
        }
        if(empty($this->apiKey)||empty($this->apiDomain)||empty($this->mediaDomain)){
            LogUtil::error("配置有误!");
            return false;
        }

        //远程配置
        $keyName = 'ai_configs';
        $cacheData = container()->get('redis')->get($keyName);
        $cacheData = !empty($cacheData)?json_decode($cacheData,true):[];
        if ($cacheData['time']<time()) {
            $result = $this->doHttpRequest([
                'face_tpl_id'=>$configs['ai_tpl_id']??'2',
                'bq_tpl_id'=>$configs['ai_tpl_id']??'2',
                'img_to_video_tpl_id'=>$configs['ai_tpl_id']??'2',
                'text_to_voice_tpl_id'=>$configs['ai_tpl_id']??'2',
            ],'ai/config');
            if($result['status']!='y'){
                LogUtil::error("AI配置接口请求失败!");
                return false;
            }

            container()->get('redis')->set($keyName, json_encode(['time'=>time()+600,'data'=>$result['data']]));
        }
    }

    /**
     * 接口返回status=0待处理 1处理中 2成功 -1失败
     * @param $data
     * @param $uri
     * @param int $retry
     * @return bool
     */
    public function doHttpRequest($data,$uri,$retry = 5)
    {
        $baseUrl    = sprintf('%s/cxapi/%s?key=%s',$this->apiDomain,$uri,$this->apiKey);
        try{
            $result = CommonUtil::httpPost($baseUrl,$data);
            $result = json_decode($result,true);
            if($result['status']!='y'){
                LogUtil::error("接口请求异常 url:{$baseUrl} error:{$result['error']} retry:{$retry}");
                if ($retry>1){throw new \Exception();}
            }
            return $result;
//            return $result['status']=='y'?true:false;
        }catch (\Exception $e){
            return $this->doHttpRequest($data,$uri,--$retry);
        }
    }

    public function success($uniqid)
    {

    }

    public function error($uniqid)
    {

    }
}