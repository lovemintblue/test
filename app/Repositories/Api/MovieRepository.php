<?php


namespace App\Repositories\Api;


use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\AccountService;
use App\Services\AdvService;
use App\Services\ApiService;
use App\Services\CommonService;
use App\Services\DataCenterService;
use App\Services\ElasticService;
use App\Services\M3u8Service;
use App\Services\MovieBlockService;
use App\Services\MovieCategoryService;
use App\Services\MovieDownloadService;
use App\Services\MovieFavoriteService;
use App\Services\MovieHistoryService;
use App\Services\MovieKeywordsService;
use App\Services\MovieLoveService;
use App\Services\MovieService;
use App\Services\MovieSpecialService;
use App\Services\MovieTagService;
use App\Services\QueueService;
use App\Services\UserBuyLogService;
use App\Services\UserCouponService;
use App\Services\UserFollowService;
use App\Services\UserHobbyService;
use App\Services\UserService;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;


/**
 * Class CartoonRepository
 * @property MovieService $movieService
 * @property MovieBlockService $movieBlockService
 * @property MovieSpecialService $movieSpecialService
 * @property MovieKeywordsService $movieKeywordsService
 * @property MovieFavoriteService $movieFavoriteService
 * @property MovieHistoryService $movieHistoryService
 * @property MovieTagService $movieTagService
 * @property M3u8Service $m3u8Service
 * @property MovieCategoryService $movieCategoryService
 * @property MovieDownloadService $movieDownloadService
 * @property UserService $userService
 * @property UserBuyLogService $userBuyLogService
 * @property UserFollowService $userFollowService
 * @property UserCouponService $userCouponService
 * @property AccountService $accountService
 * @property ElasticService $elasticService
 * @property UserHobbyService $userHobbyService
 * @property AdvService $advService
 * @property CommonService $commonService
 * @property QueueService $queueService
 * @property  MovieLoveService $movieLoveService
 * @property ApiService $apiService
 * @package App\Repositories\Api
 */
class MovieRepository extends BaseRepository
{


    /**
     * 获取模块列表
     * @param $positionCode
     * @param int $page
     * @param string $position
     * @param int $pageSize
     * @return array
     * @throws BusinessException
     */
    public function getSimpleBlockList($positionCode, $page = 1, $position = '', $pageSize = 6)
    {
        $blockPosition = $this->movieBlockService->getBlockPostInfoByCode($positionCode);
        if (empty($blockPosition)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '模块位置不存在!');
        }
        $blocks = $this->movieBlockService->getListByCode($positionCode, $page, $pageSize);
        $result = array(
            'name' => strval($blockPosition['name']),
            'code' => $positionCode,
            'nav_items' => array(),
            'block_items' => array()
        );
        $blockPositionFilter = json_decode($blockPosition['filter'], true);
        $blockPositionFilter['can_cache'] = 'y';
        $blockPositionFilter['order'] = 'new';
        $result['nav_items'][] = array(
            'name' => '最新更新',
            'filter' => json_encode($blockPositionFilter)
        );
        $blockPositionFilter['order'] = 'hot';
        $result['nav_items'][] = array(
            'name' => '本周最热',
            'filter' => json_encode($blockPositionFilter)
        );
        $blockPositionFilter['order'] = 'rand';
        $result['nav_items'][] = array(
            'name' => '最多观看',
            'filter' => json_encode($blockPositionFilter)
        );
        $blockPositionFilter['min_duration'] = '600';
        $result['nav_items'][] = array(
            'name' => '十分钟以上',
            'filter' => json_encode($blockPositionFilter)
        );
        foreach ($blocks as $block) {
            $block['filter'] = json_decode($block['filter'], true);
            $block['filter']['position'] = $position;
            $block['filter'] = json_encode($block['filter']);
            $result['block_items'][] = [
                'id' => strval($block['id']),
                'name' => strval($block['name']),
                'style' => strval($block['style']),
                'filter' => strval($block['filter']),
                'ico' => strval($block['ico'])
            ];
        }
        return $result;
    }

    /**
     *获取模块列表
     * @param $positionCode
     * @param int $page
     * @param string $position
     * @param $pageSize
     * @return array
     */
    public function getBlockList($positionCode, $page = 1, $position = '', $pageSize = 6)
    {
        $blocks = $this->movieBlockService->getListByCode($positionCode, $page, $pageSize);
        $result = [];
        $ads = $this->advService->getAll('block_list_ad', 'n', 6);
        foreach ($blocks as $block) {
            $block['filter'] = json_decode($block['filter'], true);
            $block['filter']['position'] = $position;
            $block['filter'] = json_encode($block['filter']);
            $result[] = [
                'id' => strval($block['id']),
                'name' => strval($block['name']),
                'style' => strval($block['style']),
                'filter' => strval($block['filter']),
                'ico' => strval($block['ico']),
                'page_size' => strval($block['num']),
                'page' => '1',
                'ad' => empty($ads) ? null : $ads[mt_rand(0, count($ads) - 1)],
                'items' => $this->getBlockItems($block)
            ];
        }
        return $result;
    }


    /**
     * 获取模块资源
     * @param $block
     * @return mixed
     */
    protected function getBlockItems($block)
    {
        $keyName = "block_movie_{$block['id']}";
        $result = getCache($keyName);
        if (is_null($result)) {
            $filter = json_decode($block['filter'], true);
            $filter['page_size'] = $block['num'];
            $filter['order'] = 'rand';
            $result = $this->doSearch(null, $filter)['data'];
            setCache($keyName, $result, mt_rand(120, 180));
        }
        return empty($result) ? array() : $result;
    }


    /**
     * 专题列表
     * @param $position
     * @param int $page
     * @return array|mixed
     */
    public function specialList($position, $page = 1)
    {
        $rows = $this->movieSpecialService->get($position, $page);
        return $rows;
    }

    /**
     * 获取热门关键字
     * @param int $size
     * @param  string $position
     * @return array
     */
    public function getHotKeywords($size = 18, $position = '')
    {
        return $this->movieKeywordsService->getHotList($size, $position);
    }

    /**
     * 搜索条件
     * @param $userId
     * @param  $position
     * @return array
     */
    public function getSearchFilter($userId, $position)
    {
        $configs = getConfigs();
        $result = array();
        $positions = array();
        $userInfo = $this->userService->getInfoFromCache($userId);
        if ($this->userService->isVip($userInfo)) {
            if ($userInfo['level'] > 1) {
                $positions[] = array('name' => '视频', 'value' => 'normal', 'code' => 'position');
                $positions[] = array('name' => '暗网', 'value' => 'deep', 'code' => 'position');
            }
        }
        if ($positions) {
            $result[] = $positions;
        }

        $result[] = array(
            array('name' => '全部类型', 'value' => '', 'code' => 'pay_type'),
            array('name' => 'VIP', 'value' => 'vip', 'code' => 'pay_type'),
            array('name' => '付费解锁', 'value' => 'money', 'code' => 'pay_type'),
            array('name' => '免费', 'value' => 'free', 'code' => 'pay_type'),
        );

        $tagsIds = $position == 'cartoon' ? $configs['cartoon_filter_tag_ids'] : $configs['movie_filter_tag_ids'];
        if ($tagsIds) {
            $tagsIds = explode(',', $tagsIds);
            $tags = $this->getTags('all', true);
            $tagArr = array(
                array('name' => '全部标签', 'value' => '', 'code' => 'tag_id'),
            );
            foreach ($tags as $tag) {
                if (in_array($tag['id'], $tagsIds)) {
                    $tagArr[] = array('name' => $tag['name'], 'value' => strval($tag['id']), 'code' => 'tag_id');
                }
            }
            $result[] = $tagArr;

        }

        $result[] = array(
            array('name' => '综合排序', 'value' => '', 'code' => 'order'),
            array('name' => '播放最多', 'value' => 'hot', 'code' => 'order'),
            array('name' => '最新上架', 'value' => 'new', 'code' => 'order'),
            array('name' => '收藏最多', 'value' => 'ranking', 'code' => 'order')
        );
        return $result;
    }

    /**
     * 专题详情
     * @param $id
     * @return array
     * @throws BusinessException
     */
    public function specialDetail($id)
    {
        try {
            $row = $this->movieSpecialService->findByID($id);
            if (empty($row)) {
                throw new \Exception();
            }
            $filter = json_decode($row['filter'], true);

            if (strstr($row['position'], 'media') !== false) {
                $filter['position'] = 'media';
            } elseif (strstr($row['position'], 'video') !== false) {
                $filter['position'] = 'video';
            }
            $result = [
                'id' => strval($row['_id']),
                'name' => strval($row['name']),
                'img' => $this->commonService->getCdnUrl($row['img']),
                'bg_img' => $this->commonService->getCdnUrl($row['bg_img'] ?: $row['img']),
                'description' => strval($row['description']),
                'categories' => [
                    ['name' => '最新', 'filter' => value(function () use ($filter) {
                        $filter['order'] = 'new';
                        $filter['page_size'] = 24;
                        return json_encode($filter);
                    })],
                    ['name' => '推荐', 'filter' => value(function () use ($filter) {
                        $filter['order'] = 'sort';
                        $filter['page_size'] = 24;
                        return json_encode($filter);
                    })],
                    ['name' => '逛逛', 'filter' => value(function () use ($filter) {
                        $filter['order'] = 'rand';
                        $filter['page_size'] = 24;
                        return json_encode($filter);
                    })],
                    ['name' => '排行', 'filter' => value(function () use ($filter) {
                        $filter['order'] = 'ranking';
                        $filter['page_size'] = 24;
                        return json_encode($filter);
                    })],
                ]
            ];

            return $result;
        } catch (\Exception $e) {
            throw new BusinessException(StatusCode::DATA_ERROR, '专题信息不存在!');
        }
    }

    /**
     * @param $movieId
     * @param $userId
     * @param  $linkId
     * @param  $adCode
     * @return array|mixed
     * @throws BusinessException
     */
    public function getDetail($movieId, $userId, $linkId = '',$adCode='')
    {
        if (empty($movieId)) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '当前视频不存在!');
        }
        $userInfo = $this->userService->getInfoFromCache($userId);
        $this->userService->checkUser($userInfo);
        $keyName = "movie_detail_{$movieId}";
        $result = getCache($keyName);
        if (is_null($result) || true) {
            $result = $this->elasticService->get($movieId, 'movie', 'movie');
            setCache($keyName, $result, 300);
        }
        if (empty($result) || $result['status'] != 1) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '当前视频已经下架!');
        }
        $videoUser = $this->userService->getInfoFromCache($result['user_id']);
        if (empty($videoUser)) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '当前视频已经下架!');
        }

        $configs = getConfigs();

        $tagIds = array();
        $playLinks = array(
            'm3u8_url' => $result['m3u8_url'],
            'preview_m3u8_url' => $result['preview_m3u8_url']
        );
        $freeLinks = explode(',',strval($result['free_links']));
        $result = [
            'id' => strval($result['id']),
            'name' => strval($result['name']),
            'source' => strval($result['source']),
            'categories' => $result['categories']['name'] ?: "",
            'position' => value(function () use ($result) {
                return $result['position'] == 'all' ? '' : $result['position'];
            }),
            'description' => strval($result['description']),
            'tags' => value(function () use ($result) {
                //处理标签、只展示10个
                $rows = [];
                foreach ($result['tags'] as $index => $item) {
                    if ($index >= 5) {
                        continue;
                    }
                    //截取
                    if (mb_strpos($item['name'], '-') !== false) {
                        $item['name'] = mb_substr($item['name'], mb_strpos($item['name'], '-') + 1);
                    }
                    $rows[] = [
                        'id' => strval($item['id']),
                        'name' => strval($item['name']),
                    ];
                    $tagIds[] = $item['id'];
                }
                return $rows;
            }),
            'img_x' => $this->commonService->getCdnUrl(($result['canvas'] == 'short' && $result['img_y']) ? $result['img_x'] : $result['img_x']),
            'img_y' => $this->commonService->getCdnUrl(($result['canvas'] == 'short' && $result['img_y']) ? $result['img_x'] : $result['img_x']),
            'ico' => value(function () use ($result) {
                if ($result['is_new']) {
                    return 'new';
                } elseif ($result['pay_type'] == 'free') {
                    return 'free';
                } elseif ($result['is_hot']) {
                    return 'hot';
                } elseif ($result['pay_type'] == 'vip') {
                    return 'vip';
                } elseif ($result['pay_type'] == 'money') {
                    return 'money';
                }
                return '';
            }),
            'click' => value(function () use ($result) {
                $real = $this->commonService->getRedisCounter("movie_click_{$result['id']}");
                return strval(CommonUtil::formatNum(intval($result['click'] + $real)));
            }),
            'has_favorite' => $this->movieFavoriteService->has($userId, $movieId) ? 'y' : 'n',
            'favorite' => value(function () use ($result) {
                $real = $this->commonService->getRedisCounter('movie_favorite_' . $result['id']);
                return strval(CommonUtil::formatNum(intval($result['favorite'] + $real)));
            }),
            'has_love' => $this->movieLoveService->has($userId, $movieId) ? 'y' : 'n',
            'comment' => value(function () use ($result) {
                $real = $this->commonService->getRedisCounter('movie_comment_' . $result['id']);
                return strval(CommonUtil::formatNum($real));
            }),
            'score' => strval($result['score']),
            'duration' => strval(CommonUtil::parseSecond($result['duration'])),
            'played_duration' => '0',
            'pay_type' => strval($result['pay_type']),
//            'money' => strval($result['money'] * 1),
            'money'=>value(function ()use($userInfo,$result){
                //获取VIP折扣
                $discountRate = $userInfo['group_rate'] < 50 ? 50 : $userInfo['group_rate'];
                $discountRate = $discountRate > 100 ? 100 : $discountRate;
                $discountMoney = round($result['money'] * (100 - $discountRate) / 100, 0);
                $money = $discountMoney > 0 ? $discountMoney : $result['money'];
                return strval($money);
            }),
            'width' => strval($result['width']),
            'height' => strval($result['height']),
            'ad' => $this->advService->getRandAd('app_play_ad', $userInfo['is_vip'], 6),
            'detail_page_ad_show_method'=>$configs['detail_page_ad_show_method']=='ico'?'ico':'banner',
            'ads' => $this->advService->getAll('app_play_ad', $userInfo['is_vip'], 6),
            'ico_ads' => $this->advService->getAll('common_ico',$userInfo['is_vip'],20),
            'play_ad_show_time'=>empty($configs['play_ad_show_time'])?'5':strval($configs['play_ad_show_time']*1),
            'play_ad_auto_jump'=>empty($configs['play_ad_auto_jump'])?'n':strval($configs['play_ad_auto_jump']),
            'play_ads' => $this->advService->getAll('play_ads',$userInfo['is_vip'],6),
            'canvas' => strval($result['canvas']),
            'share_info' => $this->userService->getShareInfo($userId),
            'relation_video' => array(),
            'comment_count' => '0',
            'time_label' => CommonUtil::showTimeDiff($result['show_at']),
            'user' => array(
                'id' => strval($userInfo['id']),
                'username' => $userInfo['username'],
                'nickname' => $userInfo['nickname'],
                'img' => $this->commonService->getCdnUrl($userInfo['img']),
                'is_vip' => $userInfo['is_vip'],
                'level' => strval($userInfo['level'] * 1),
                'balance' => strval($userInfo['balance'] * 1)
            ),
            'is_new_user'=> $this->userService->isNewUser($userInfo)?'y':'n',
            'new_user_end_time' => strval($this->userService->getNewUserTime($userInfo)),
            'video_user' => [
                'id' => strval($videoUser['id']),
                'nickname' => strval($videoUser['nickname']),
                'img' => $this->commonService->getCdnUrl($videoUser['img']),
                'sex' => strval($videoUser['sex']),
                'is_vip' => $this->userService->isVip($videoUser) ? 'y' : 'n',
                'is_up' => strval($videoUser['is_up']),
                'is_follow' => $this->userFollowService->has($userId, $videoUser['id']) ? 'y' : 'n'
            ],
            'is_more_link' => $result['is_more_link'] ? 'y' : 'n',
            'links' => []
        ];

        if($this->userService->isVip($userInfo)){
            $result['play_ads']=[];
        }

        //读取多剧集
        if ($result['is_more_link'] == 'y') {
            $links = $this->movieService->getLinks($movieId);
            if (empty($links)) {
                throw  new BusinessException(StatusCode::PARAMETER_ERROR, '当前视频无可用播放资源!');
            }
            if (empty($linkId)) {
                $linkId = array_keys($links)[0];
            }
            if (empty($links[$linkId])) {
                throw  new BusinessException(StatusCode::PARAMETER_ERROR, '当前视频无可用播放资源!');
            }
            $playLinks['m3u8_url'] = $links[$linkId]['m3u8_url'];
            $playLinks['preview_m3u8_url'] = $links[$linkId]['preview_m3u8_url'];
            $payType = $result['pay_type'];
            foreach ($links as $link)
            {
                unset($link['m3u8_url']);
                unset($link['preview_m3u8_url']);
                $link['is_selected'] = $linkId==$link['id']?'y':'n';
                $link['type']= $payType;
                $link['fee'] = strval($result['money']);
                if($freeLinks && in_array($link['id'],$freeLinks)){
                    $link['type'] ='free';
                    $link['fee']='0';
                }
                if($link['is_selected']=='y' && $link['type']=='free'){
                    $result['pay_type']='free';
                }
                $result['links'][] = $link;
            }
            $result['links'] = $this->movieService->formatLinks($result['links']);
        }

        //关联视频
        $result['relation_video'] = $this->getRelationVideosByTag($tagIds, $userId, $movieId, $result['canvas'], 12);
        $this->insertAdToArray($result['relation_video'],$adCode,1);

        /*
         * 处理播放线路
         * 1. 如果是vip视频则判断是否vip  同时判断是否具备该区域的观看权限  浅网 深网  暗网
         * 2. 如果是金币视频需要判断是否已经购买
         */
        $playErrorType = 'none';
        $playError = '';
        if ($result['pay_type'] == 'money') {
            $needCheckBought=true;
            //获取用户的组  折扣是-1和-2 就完全免费
            if($userInfo['group_id']){
                $groupInfo = $this->userService->getGroupInfo($userInfo['group_id']);
                if($this->userService->isVip($userInfo) && ($groupInfo['rate']==-1 || $groupInfo['rate']==-2)){
                    $needCheckBought=false;
                }
            }
            if($needCheckBought){
                if($result['is_more_link']=='y'){
                    $hasBuy = $this->userBuyLogService->has($userId, $movieId.'_'.$linkId, 'movie_link');
                }else{
                    $hasBuy = $this->userBuyLogService->has($userId, $movieId, 'movie');
                }
                if (!$hasBuy) {
                    $playError = $result['money'] . '金币解锁观看完整版!';
                    $playErrorType = 'need_buy';
                    $playLinks['m3u8_url'] = '';
                }
            }
        } elseif ($result['pay_type'] == 'vip') {
            $isVip = $this->userService->isVip($userInfo);
            //$positionName = CommonValues::getMoviePosition($result['position']);
            if (!$isVip) {
                if ($result['position'] == 'dark') {
                    $playError = '升级黑金VIP观看完整版 ';
                } else {
                    $playError = '升级VIP观看完整版 ';
                }
                $playErrorType = 'need_vip';
                $playLinks['m3u8_url'] = '';
            } else {
                if ($userInfo['level'] <= 1 && !in_array($result['position'], array('normal','cartoon','short'))) {
                    $playError = '升级VIP观看完整版 ';
                    $playLinks['m3u8_url'] = '';
                    $playErrorType = 'need_vip';
                } elseif ($userInfo['level'] == 2 && !in_array($result['position'], array('normal', 'deep','cartoon','dark','short'))) {
                    $playError = '升级黑金VIP观看完整版 ';
                    $playLinks['m3u8_url'] = '';
                    $playErrorType = 'need_vip';
                }
            }
        }
        $playLink = $playLinks['m3u8_url']?:$playLinks['preview_m3u8_url'];
        $line1 = $this->commonService->getVideoCdnUrl($playLink,'default',$result['source']);
        $line2 = $this->commonService->getVideoCdnUrl($playLink,'overseas',$result['source']);
        $result['play_error_type'] = $playErrorType;
        $result['play_error'] = $playError;
        $result['play_links'] = array(
            array(
                'id' => 'line1',
                'name' => '线路1',
                'preview_m3u8_url' => $line1,
                'm3u8_url' => $line1,
            ),
            array(
                'id' => 'line2',
                'name' => '线路2',
                'preview_m3u8_url' => $line2,
                'm3u8_url' => $line2,
            )
        );
        $handleKey = md5(sprintf('%s-movie-click-%s-%s', date('Y-m-d'), $userId, $movieId));
        if (!getCache($handleKey)) {
            setCache($handleKey, 1, 3600 * 2);
            $this->movieService->handler(array('movie_id' => $movieId, 'action' => 'click'));
        }
        return $result;
    }

    /**
     * 通过标签获取推荐数据
     * @param $tagIds
     * @param $userId
     * @param string $excludeId
     * @param string $canvas
     * @param int $size
     * @return array|mixed|null
     */
    public function getRelationVideosByTag($tagIds, $userId, $excludeId = '', $canvas = '', $size = 8)
    {
        $query = array(
            'tag_id' => join(',', $tagIds),
            'canvas' => $canvas,
            'not_ids' => $excludeId,
            'order' => 'rand',
            'page_size' => $size
        );
        $cacheKey = md5(join('-', array_values($query)));
        $result = getCache($cacheKey);
        if ($result === null) {
            $items = $this->doSearch($userId, $query);
            $result = empty($items['data']) ? array() : $items['data'];
            setCache($cacheKey, $result, mt_rand(30, 90));
        }
        return empty($result) ? array() : $result;
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
        $query['page_size'] = $this->getRequest($query, "page_size", "int", 24);
        $query['keywords'] = $this->getRequest($query, "keywords", "string");
        $query['number'] = $this->getRequest($query, "number", "string");
        $query['index'] = $this->getRequest($query, "index", "string");
        $query['pay_type'] = $this->getRequest($query, "pay_type", "string");
        $query['position'] = $this->getRequest($query, "position", "string");
        $query['ad_code'] = $this->getRequest($query, 'ad_code', 'string');
        $query['cat_id'] = $this->getRequest($query, "cat_id", "string");
        $query['is_day'] = $this->getRequest($query, 'is_day', 'string');//优选
        $query['tag_id'] = $this->getRequest($query, "tag_id", "string");
        $query['is_hot'] = $this->getRequest($query, 'is_hot', 'string');
        $query['is_new'] = $this->getRequest($query, 'is_new', 'string');
        $query['is_end'] = $this->getRequest($query, 'is_end', 'string');
        $query['home_id'] = $this->getRequest($query, 'home_id', 'string');
        $query['home_ids'] = $this->getRequest($query, 'home_ids', 'string');
        $query['canvas'] = $this->getRequest($query, 'canvas', 'string');
        $query['img_type'] = $this->getRequest($query, "img_type", "string");
        $query['min_duration'] = $this->getRequest($query, 'min_duration', 'string');
        $query['ids'] = $this->getRequest($query, 'ids', 'string', '');
        $query['not_ids'] = $this->getRequest($query, 'not_ids', 'string', '');
        $query['order'] = $this->getRequest($query, 'order', 'string');

        $canCache = $this->getRequest($query, 'can_cache', 'string');

        //个人主页按照最新排序
        if ($query['home_id'] && empty($query['order'])) {
            $query['order'] = 'new';
        }

        //根据前端缓存一些数据到redis
        if ($canCache && $canCache == 'y' && $query['page'] == 1) {
            $cacheKey = 'movie_items_cache_' . md5(json_encode($query));
            $result = getCache($cacheKey);
            if (empty($result)) {
                $result = $this->movieService->doSearch($userId, $query);
                setCache($cacheKey, $result, mt_rand(60, 90));
            }
        } else {
            $result = $this->movieService->doSearch($userId, $query);
        }
        //插入广告
        $adCode = $query['ad_code'];
        $this->insertAdToArray($result['data'],$adCode,$query['page']*1);

        return $result;
    }

    /**
     *   插入广告到数组
     * @param $data
     * @param $adCode
     * @param int $page
     */
    public function insertAdToArray(&$data,$adCode,$page=1)
    {
        if($data && $adCode && in_array($adCode,['video_mix_horizontal','video_mix_vertical','movie_list_ad','comic_list_ad'])){
            $adItems = $this->advService->getAll($adCode, 'n', 4);
            if ($adItems) {
                foreach ($adItems as $key => $adItem) {//组装广告数据
                    $adItems[$key] = $this->movieService->getAdItem($adItem);
                }
                $adCount = count($adItems);
                $adPage = $page%2;
                if($adPage===0 && $adCount==3){
                    array_shift($adItems);
                }elseif ($adPage===0 && $adCount>=4){
                    array_shift($adItems);
                    array_shift($adItems);
                }
                $itemCount = count($data);
                //小于12个的数据插入一条  大于12个的插入两条
                if($itemCount>=12){
                    $firstAd = array_shift($adItems);
                    $data = CommonUtil::insertToArray($data,5,[$firstAd]);
                    $twoAd = array_shift($adItems);
                    $twoAd = empty($twoAd)?$firstAd:$twoAd;
                    $data= CommonUtil::insertToArray($data,12,[$twoAd]);
                }else{
                    $data[] = $adItems[mt_rand(0,count($adItems)-1)];
                }
            }
            $data = array_values($data);
        }
    }


    /**
     * 去收藏
     * @param $userId
     * @param $movieId
     * @return bool
     * @throws BusinessException
     */
    public function doFavorite($userId, $movieId)
    {
        return $this->movieFavoriteService->do($userId, $movieId);
    }

    /**
     * 去收藏
     * @param $userId
     * @param $movieId
     * @return bool
     * @throws BusinessException
     */
    public function doLove($userId, $movieId)
    {
        return $this->movieLoveService->do($userId, $movieId);
    }

    /**
     * 购买视频
     * @param $userId
     * @param $movieId
     * @param $linkId
     * @return bool
     * @throws BusinessException
     */
    public function doBuy($userId, $movieId,$linkId='')
    {
        if (empty($movieId)) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '请选择要购买的视频!');
        }
        if(empty($linkId)){
            $hasBuy = $this->userBuyLogService->has($userId, $movieId, 'movie');
            if ($hasBuy) {
                throw  new BusinessException(StatusCode::PARAMETER_ERROR, '视频已购买,无需重复购买!');
            }
        }else{
            $hasBuy = $this->userBuyLogService->has($userId, $movieId.'_'.$linkId, 'movie_link');
            if ($hasBuy) {
                throw  new BusinessException(StatusCode::PARAMETER_ERROR, '视频已购买,无需重复购买!');
            }
        }
        $movieInfo = $this->movieService->findByID($movieId);
        if (empty($movieInfo) || $movieInfo['status'] != 1) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '视频已下架!');
        }
        if($movieInfo['is_more_link'] && empty($linkId)){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '必要参数错误!');
        }
        $money = $movieInfo['money'];
        if ($money < 1) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '此视频无需购买!');
        }
        //免费剧集
        if($linkId && $movieInfo['free_links'] && strpos($movieInfo['free_links'],$linkId)){
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '此视频是免费无需购买!');
        }
        $userInfo = $this->userService->findByID($userId);
        $this->userService->checkUser($userInfo);

        //获取用户的组  折扣是-1和-2 就完全免费
        if($userInfo['group_id']){
            $groupInfo = $this->userService->getGroupInfo($userInfo['group_id']);
            if($this->userService->isVip($userInfo) && ($groupInfo['rate']==-1 || $groupInfo['rate']==-2)){
                throw  new BusinessException(StatusCode::PARAMETER_ERROR, '尊贵的特权用户您无需购买!');
            }
        }

        $discountMoney = 0;
        $discountRate = 0;
        if ($this->userService->isVip($userInfo) && $userInfo['group_rate'] > 0) {
            //获取VIP折扣
            $discountRate = $userInfo['group_rate'] < 50 ? 50 : $userInfo['group_rate'];
            $discountRate = $discountRate > 100 ? 100 : $discountRate;
            $discountMoney = round($money * (100 - $discountRate) / 100, 0);
            $money = $discountMoney > 0 ? $discountMoney : $money;
        }
        $money = $money < 0 ? 0 : $money;
        if ($userInfo['balance'] < $money) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '可用余额不足!');
        }
        $orderSn = CommonUtil::createOrderNo('MV');
        if ($money > 0) {
            $remark = "购买视频消耗:{$money}金币";
            if ($discountMoney > 0) {
                $remark = "购买视频消耗:{$money}金币";
            }
            $result = $this->accountService->reduceBalance($userInfo, $orderSn, $money, 8, $remark, json_encode(array('old_money' => $movieInfo['money'], 'rate' => $discountRate, 'movie_id' => $movieId)));
            if (empty($result)) {
                throw  new BusinessException(StatusCode::PARAMETER_ERROR, '购买失败!');
            }
            DataCenterService::doReduceBalance($movieId,$movieInfo['name'],$money,$userInfo['balance'],($userInfo['balance']-$money),'video_unlock',$orderSn,time());
            $this->addMovieUserMoney($movieInfo['user_id'], $money, $movieId, $orderSn);
            $this->userService->setInfoToCache($userId);
        }
        if($linkId){
            $this->userBuyLogService->do($orderSn, $userInfo, $movieId.'_'.$linkId, 'movie_link', $movieInfo['img_x'], $money, $movieInfo['money']);
            //同时在插入一条视频的购买记录 便于购买日志
            $hasLog = $this->userBuyLogService->has($userId,$movieId,'movie');
            file_put_contents('xx.txt',$hasLog.'=>'.$movieId.'=>'.$userId);
            if(!$hasLog){
                $orderSn = CommonUtil::createOrderNo('MV');
                $this->userBuyLogService->do($orderSn, $userInfo, $movieId, 'movie', $movieInfo['img_x'], 0,0);
            }
        }else{
            $this->userBuyLogService->do($orderSn, $userInfo, $movieId, 'movie', $movieInfo['img_x'], $money, $movieInfo['money']);
        }
        $this->movieService->handler(['action' => 'buy', 'movie_id' => $movieId, 'money' => $money,'order_sn' => $orderSn]);
        return true;
    }

    /**
     * @param $userId
     * @param $money
     * @param $movieId
     * @param string $orderSn
     * @return bool
     */
    public function addMovieUserMoney($userId, $money, $movieId, $orderSn = '')
    {
        $userId = intval($userId);
        if (empty($userId)) {
            return false;
        }
        $userInfo = $this->userService->findByID($userId);
        if (empty($userInfo) || $userInfo['is_disabled']) {
            return false;
        }
        $rate = $userInfo['movie_fee_rate'] * 1;
        if ($rate <= 0) {
            $configs = getConfigs();
            $rate = $configs['movie_fee_rate'] * 1;
            if ($rate <= 0) {
                return true;
            }
        }
        $rate = $rate <= 0 ? 20 : $rate;
        $rate = $rate > 60 ? 60 : $rate;
        $money = round($money * $rate / 100, 0);
        if ($money < 1) {
            return true;
        }
        $this->accountService->addBalance($userInfo, $orderSn, $money, 10, '视频收入', json_encode(['movie_id' => $movieId, 'rate' => $rate]));
        $this->userService->updateRaw(array('$inc' => array('income' => $money)), array('_id' => $userId));
        $this->userService->setInfoToCache($userId);
        return true;
    }

    /**
     * 获取购买记录
     * @param $userId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getBuyLog($userId, $page = 1, $pageSize = 12)
    {
        $result = $this->userBuyLogService->log($userId, 'movie', $page, $pageSize);
        if (empty($result)) {
            return [];
        }
        $query = array('ids' => join(',', array_keys($result)), 'page' => 1, 'page_size' => $pageSize);
        $movies = $this->doSearch($userId, $query);
        return $this->mergeItems($result, $movies['data']);
    }

    /**
     * @param $userId
     * @param $movieId
     * @return array
     * @throws BusinessException
     */
    public function doDownload($userId, $movieId)
    {
        return $this->movieDownloadService->do($userId, $movieId);
    }

    /**
     * 删除缓存
     * @param $userId
     * @param $movieIds
     * @return mixed
     */
    public function delDownload($userId, $movieIds)
    {
        if ($movieIds == 'all') {
            $this->movieDownloadService->deleteAll($userId);
        } else {
            $movieIds = explode(',', $movieIds);
            foreach ($movieIds as $movieId) {
                $this->movieDownloadService->delDownload($userId, $movieId);
            }
        }
        return true;
    }

    /**
     * 缓存列表
     * @param $userId
     * @param int $page
     * @return array
     */
    public function getDownloadList($userId, $page = 1)
    {
        $result = $this->movieDownloadService->getDownloadList($userId, $page);
        if (empty($result)) {
            return array();
        }
        $query = array('ids' => join(',', array_keys($result)));
        $movies = $this->doSearch($userId, $query);
        return empty($movies) ? array() : $movies['data'];
    }


    /**
     * 获取热门标签
     * @param $position
     * @param bool $hot
     * @return array
     */
    public function getTags($position, $hot = true)
    {
        $rows = $this->movieTagService->getAll(false, '');

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id' => strval($row['id']),
                'name' => strval($row['name'])
            ];
        }
        return $result;
    }

    /**
     * 获取热门分类
     * @param $position
     * @param bool $hot
     * @return array
     */
    public function getCategories($position, $hot = true)
    {
        $rows = $this->movieCategoryService->getAll($hot, $position);
        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id' => strval($row['id']),
                'name' => strval($row['name'])
            ];
        }
        return $result;
    }


    /**
     * 获取收藏列表
     * @param $userId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getFavorites($userId, $page = 1, $pageSize = 10)
    {
        $result = $this->movieFavoriteService->getFavorites($userId, $page, $pageSize);
        if (empty($result)) {
            return [];
        }
        $query = array('ids' => join(',', array_keys($result)), 'page' => 1, 'page_size' => $pageSize);
        $movies = $this->doSearch($userId, $query);
        return empty($movies) ? array() : $movies['data'];
    }

    /**
     * 删除收藏
     * @param $userId
     * @param null $ids
     * @return bool
     */
    public function delFavorites($userId, $ids = null)
    {
        //bug 未减少计数器
        if ($ids == 'all') {
            $this->movieFavoriteService->deleteAll($userId);
        } else {
            $ids = explode(',', $ids);
            foreach ($ids as $id) {
                $this->movieFavoriteService->delFirst($userId, $id);
            }
        }
        return true;
    }

    /**
     * 添加播放记录
     * @param $userId
     * @param $id
     * @param $time
     * @return bool
     */
    public function doHistory($userId, $id, $time)
    {
        $this->movieHistoryService->do($userId, $id, $time);
        return true;
    }

    /**
     * 获取历史记录
     * @param $userId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getHistories($userId, $page = 1, $pageSize = 10)
    {
        $result = $this->movieHistoryService->getHistories($userId, $page, $pageSize);
        if (empty($result)) {
            return array();
        }
        $query = array('ids' => join(',', array_keys($result)), 'page_size' => $pageSize);
        $movies = $this->doSearch($userId, $query)['data'];
        return $this->mergeItems($result, $movies);
    }

    /**
     * 删除收藏
     * @param $userId
     * @param null $ids
     * @return bool
     */
    public function delHistories($userId, $ids = null)
    {
        if ($ids == 'all') {
            $this->movieHistoryService->deleteAll($userId);
        } else {
            $ids = explode(',', $ids);
            foreach ($ids as $id) {
                $this->movieHistoryService->delFirst($userId, $id);
            }
        }
        return true;
    }

    /**
     * 相关视频搜索条件
     * @param $movie
     * @return false|string
     */
    public function getRecommendMovies($movie)
    {
        $query = [];
        //相同分类 或 相同标签
        if (!empty($movie['tags'])) {
            $query['tag_id'] = join(',', array_column($movie['tags'], 'id'));
        } elseif (!empty($movie['categories'])) {
            $query['cat_id'] = $movie['categories']['id'];
        }
        $query['order'] = 'rand';
        $query['canvas'] = 'long';
        $query['page_size'] = 6;
        return json_encode($query);
    }

    /**
     * @param $items
     * @param $movies
     * @return array
     */
    protected function mergeItems($items, $movies)
    {
        $result = [];
        foreach ($movies as $movie) {
            if (!isset($items[$movie['id']])) {
                continue;
            }
            $result[$movie['id']] = array_merge($movie, $items[$movie['id']]);
        }
        $result = array_values($result);

        array_multisort(array_column($result, 'updated_time'), SORT_DESC, $result);
        return $result;
    }

    /**
     * 获取我的视频
     * @param $query
     * @param $userId
     * @return array|mixed
     */
    public function getMyList($query, $userId)
    {
        $filter = array(
            'home_id' => intval($userId),
            'is_all' => 'y'
        );
        $filter['page'] = $this->getRequest($query, 'page', 'int', 1);
        $result = $this->movieService->doSearch($userId, $filter);
        return empty($result['data']) ? array() : $result['data'];
    }


    /**
     * 下架我的视频
     * @param $userId
     * @param $movieId
     * @return bool
     * @throws BusinessException
     */
    public function delMy($userId, $movieId)
    {
        $movieInfo = $this->movieService->findByID($movieId);
        if (empty($movieInfo) || $movieInfo['user_id'] != $userId) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '视频不存在!');
        }
        $this->movieService->save(array('status' => -1, '_id' => $movieId));
        $this->movieService->asyncEs($movieId);
        return true;
    }

    /**
     * 上传视频
     * @param $userId
     * @param $data
     * @return bool|int|mixed
     * @throws BusinessException
     */
    public function doAdd($userId, $data)
    {
        if (empty($data['quality']) || empty($data['duration']) || empty($data['tag_ids'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '视频信息错误!');
        }
        $quality = explode('X', $data['quality']);
        $movieSaveData = [
            'mid' => CommonUtil::getId(),
            'user_id' => intval($userId),
            'categories' => 2,
            'tags' => array(),
            'name' => $this->getRequest($data, 'name'),
            'actor' => '',
            'level' => 1,
            'number' => uniqid('UU_'),
            'img_x' => $this->getRequest($data, 'img'),
            'img_y' => '',
            'sort' => 0,
            'is_new' => 0,
            'is_hot' => 0,
            'favorite' => rand(16800, 19000),
            'real_favorite' => 0,
            'click' => rand(15800, 19000),
            'real_click' => 0,
            'favorite_rate' => 0,
            'score' => rand(92, 96),
            'buy' => 0,
            'comment' => 0,
            'money' => $data['money'] > 0 ? intval($data['money']) : 0,
            'pay_type' => 'vip',
            'm3u8_url' => $this->getRequest($data, 'm3u8_url'),
            'duration' => $this->getRequest($data, 'duration', 'int', 0),
            'preview_m3u8_url' => $this->getRequest($data, 'preview_m3u8_url'),
            'width' => $quality[0] * 1,
            'height' => $quality[1] * 1,
            'position' => 'normal',
            'canvas' => intval($quality[0]) > intval($quality[1]) ? 'long' : 'short',
            'img_type'=> intval($quality[0]) > intval($quality[1]) ? 'long' : 'short',
            'status' => 2,//默认未上架
            'description' => '',
            'show_at' => 0,
            'preview_images' => '',
            'is_user_upload' => 1
        ];
        if ($movieSaveData['money'] > 0) {
            $movieSaveData['pay_type'] = 'money';
        }
        if (empty($movieSaveData['name']) || empty($movieSaveData['img_x']) || empty($movieSaveData['m3u8_url']) || empty($movieSaveData['preview_m3u8_url'])) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '请检查必填信息是否完整!');
        }
        $tags = explode(',', $data['tag_ids']);
        foreach ($tags as $tag) {
            $movieSaveData['tags'][] = intval($tag);
        }
        $movieId = $this->movieService->save($movieSaveData);
        if ($movieId) {
            $this->movieService->asyncEs($movieId);
        }
        return $movieId;
    }
}