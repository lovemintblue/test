<?php


namespace App\Repositories\Api;


use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\AccountService;
use App\Services\AdvService;
use App\Services\ChatService;
use App\Services\CommonService;
use App\Services\DataCenterService;
use App\Services\ElasticService;
use App\Services\PlayFavoriteService;
use App\Services\PlayService;
use App\Services\UserBuyLogService;
use App\Services\UserFollowService;
use App\Services\UserService;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;


/**
 * 玩法
 *
 * @property PlayService $playService
 * @property ElasticService $elasticService
 * @property CommonService $commonService
 * @property UserService $userService
 * @property UserBuyLogService $userBuyLogService
 * @property AccountService $accountService
 * @property ChatService $chatService
 * @property AdvService $advService
 * @property UserFollowService $userFollowService
 * @property PlayFavoriteService $playFavoriteService
 * @package App\Repositories\Api
 */
class PlayRepository  extends BaseRepository
{
    /**
     * 获取约炮框架
     * @param $userId
     * @return array
     */
    public function getYuepaoFrame($userId)
    {
        $userInfo = $this->userService->getInfoFromCache($userId);
        $result =[
            'banners'=>$this->advService->getAll('app_yuepao_home',$userInfo['is_vip'],15),
            'city'=>value(function(){
                $items = [];
                foreach($this->playService->cityArr as $key=>$val){
                    $items[] = [
                        'name'=>strval($val),
                        'code'=>strval($key),
                        'filter'=>json_encode(['city'=>strval($key)])
                    ];
                }
                return $items;
            }),
        ];
        return $result;
    }

    /**
     * 搜索
     * @param array $query
     * @return mixed
     */
    public function doSearch($query=[])
    {
        $query['page'] = $this->getRequest($query, "page", "int", 1);
        $query['page_size'] = $this->getRequest($query, "page_size", "int", 24);
        $query['keywords'] = $this->getRequest($query, "keywords", "string");
        $query['type']  = $this->getRequest($query, "type", "string");
        $query['tag']  = $this->getRequest($query, "tag", "string");
        $query['city'] = $this->getRequest($query, "city", "string");
        $query['ids']   = $this->getRequest($query, 'ids', 'string', '');
        $query['not_ids']= $this->getRequest($query, 'not_ids', 'string', '');
        $query['order'] = $this->getRequest($query, 'order', 'string');
        $query['is_recommend'] = $this->getRequest($query, 'is_recommend');
        return $this->playService->doSearch($query);
    }

    /**
     * 详情
     * @param $playId
     * @param $userId
     * @return array|mixed
     * @throws BusinessException
     */
    public function getDetail($playId,$userId)
    {
        $userInfo = $this->userService->getInfoFromCache($userId);
        $this->userService->checkUser($userInfo);
        $keyName  = "play_detail_{$playId}";
        $result   = getCache($keyName);
        if(is_null($result)){
            $result = $this->elasticService->get($playId, 'play', 'play');
            setCache($keyName,$result,300);
        }
        if(empty($result)){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '内容不存在!');
        }

        $result = [
            'id'           => strval($result['id']),
            'title'        => strval($result['title']),
            'pay_type'     => strval($result['pay_type']),
            'layer_type'   => '',
            'money'        => strval($result['money']),
            'price'        => strval($result['price']),
            'contact'      => strval($result['contact']),
            'description'  => strval($result['description']),
            'bio_list'     => value(function ()use($result){
                $list = [];
                if($result['video']){
                    $list[] = [
                        'video'=>$this->commonService->getCdnUrl($result['video'],'video'),
                        'image'=>$this->commonService->getCdnUrl($result['img_x']),
                    ];
                }
                if($result['images']){
                    foreach($result['images'] as $key=>$val){
                        $list[] = [
                            'video'=>'',
                            'image'=>$this->commonService->getCdnUrl($val),
                        ];
                    }
                }
                return $list;
            }),

            'user_id'     => strval($result['user_id']),
            'nickname'    => strval($result['nickname']),
            'city'        => strval($this->playService->cityArr[$result['city']]),
            'headico'     => $this->commonService->getCdnUrl($result['headico']),


            'has_favorite'  => $this->playFavoriteService->has($userId,$playId)?'y':'n',
            'has_follow'    => $this->userFollowService->has($userId,$result['user_id'])?'y':'n',
            'has_buy'       => $userId==$result['user_id']?'y':($this->userBuyLogService->has($userId,$playId,'play'))?'y':'n',

            'ad'=>$this->advService->getRandAd('app_play_detail',$userInfo['is_vip'],6),
            'recommend_filter'=>value(function ()use($result){
                //相同分类 或 相同标签
                if(!empty($result['city'])){
                    $query['city_id']=$result['city'][0]['id'];
                }
                $query['order']='rand';
                $query['is_recommend']=1;
                $query['not_ids']=$result['id'];
                $query['page_size']=10;
                return json_encode($query);
            }),
        ];
        //判断用户权限
        if($result['has_buy']=='n'&&$result['pay_type']!='free'&&!in_array($userInfo['level'],[13])) {
            if($result['pay_type']=='money'){
                $result['layer_type']='money';//购买
                $result['contact'] ='';
            }else{
//                if($userInfo['is_vip']=='n') {
//                    $result['layer_type']='vip';//购买
//                    $result['contact'] ='';
//                }
            }
        }
        $this->playService->handler(['action' => 'click','play_id'=>$playId]);
        return $result;
    }

    /**
     * 链接处理
     * @param $url
     * @return string
     */
    public function thunderUrl($url)
    {
        if(empty($url)){
            return  '';
        }
        if (strpos($url,'http')!==false){
            return $url;
        }

        $url = $this->commonService->getConfig('game_url') .'/'. $url;
        return $url;
        return 'thunder://'.base64_encode('AA'.$url.'ZZ');
    }

    /**
     * @param $userId
     * @param $data
     * @return bool|int|mixed
     * @throws BusinessException
     */
    public function doSave($userId,$data)
    {
        $row = [
            'title'     => $this->getRequest($data,'title','string'),
            'type'      => 'yuepao',
            'price'     => $this->getRequest($data,'price'),
            'contact'   => $this->getRequest($data,'contact'),
            'description' => $this->getRequest($data,'description'),
            'city'      => $this->getRequest($data,'city','string'),
            'images'    => value(function ()use($data){
                $images = explode(',', $this->getRequest($data,'images'));
                foreach ($images as $key=>$item) {
                    if(empty($item)){
                        unset($images[$key]);
                    }
                }
                return $images;
            }),
            'mid'       => $this->getRequest($data,'mid', 'string'),
            'user_id'   => intval($userId),

        ];

        //商家认证才能发布
        if (!$this->userService->checkMerchant($userId)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '该功能仅对认证商家开放!');
        }
        if(empty($row['title'])){
            throw new BusinessException(StatusCode::DATA_ERROR, '请输入标题!');
        }
        if(mb_strlen($row['title'])>20){
            throw new BusinessException(StatusCode::DATA_ERROR, '标题不能超过20个字符!');
        }
        if(empty($row['price'])){
            throw new BusinessException(StatusCode::DATA_ERROR, '请输入服务价格!');
        }
        if(empty($row['contact'])){
            throw new BusinessException(StatusCode::DATA_ERROR, '请输入联系方式!');
        }
        if(empty($row['description'])){
            throw new BusinessException(StatusCode::DATA_ERROR, '请填写资料!');
        }
        if(empty($row['images'])){
            throw new BusinessException(StatusCode::DATA_ERROR, '请至少上传一张图片!');
        }
        if(count($row['images'])>9){
            throw new BusinessException(StatusCode::DATA_ERROR, '最多上传9张图片!');
        }
        if(empty($row['city'])){
            throw new BusinessException(StatusCode::DATA_ERROR, '请选择城市!');
        }

        $row['love']        = 0;
        $row['real_love']   = 0;
        $row['click']       = 0;
        $row['real_click']  = 0;
        $row['favorite']    = rand(0,10);
        $row['real_favorite'] = 0;
        $row['comment']     = 0;
        $row['last_comment']= 0;
        $row['img_x']       = $row['images'][0];
        $row['video']       = '';
        $row['duration']    = '';
        $row['money']       = -1;
        $row['pay_type']    = 'free';
        $row['deny_msg']    = '';
        $row['status']      = $row['mid']?-2:0;
        $row['mid']         = $row['mid']?'upload_'.time().'_'.$row['mid']:'';
        $row['sort']        = 0;
        $row['is_top']      = 0;
        $row['params']      = [];

        return $this->playService->save($row);
    }

    /**
     * 去收藏
     * @param $userId
     * @param $play
     * @return bool
     * @throws BusinessException
     */
    public function doFavorite($userId,$play)
    {
        return $this->playFavoriteService->do($userId,$play);
    }

    /**
     * 购买游戏
     * @param $userId
     * @param $playId
     * @return bool
     * @throws BusinessException
     */
    public function doPlay($userId,$playId)
    {
        if(empty($playId)){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '请选择要购买的内容!');
        }
        $hasBuy = $this->userBuyLogService->has($userId,$playId,'play');
        if($hasBuy){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '已购买,无需重复购买!');
        }
        $playInfo = $this->playService->findByID($playId);
        if(empty($playInfo)){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '已下架!');
        }
        $money = $playInfo['money'];
        if($money <1){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '无需购买!');
        }
        $userInfo = $this->userService->findByID($userId);
        $this->userService->checkUser($userInfo);

        if($userInfo['balance'] < $money){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '可用余额不足!');
        }
        $orderSn=CommonUtil::createOrderNo('PLAY');
        if($money>0) {
            $this->accountService->reduceBalance($userInfo,$orderSn,$money,8,"购买约啪信息消费：{$money} 金币,编号：{$playId}");
        }
        $this->userBuyLogService->do($orderSn,$userInfo,$playId,'play',$playInfo['img_x'],$money,$playInfo['money']);
        $this->playService->handler(['action' => 'buy','play_id'=>$playId]);
        DataCenterService::doReduceBalance($playId,'购买约啪信息消费',$money,$userInfo['balance'],($userInfo['balance']-$money),'content_purchase',$orderSn,time());

        return true;
    }


}