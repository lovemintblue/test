<?php


namespace App\Repositories\Api;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\AccountService;
use App\Services\AdvService;
use App\Services\ComicsBlockService;
use App\Services\ComicsFavoriteService;
use App\Services\ComicsHistoryService;
use App\Services\ComicsKeywordsService;
use App\Services\ComicsService;
use App\Services\ComicsTagService;
use App\Services\CommonService;
use App\Services\DataCenterService;
use App\Services\UserService;
use App\Utils\CommonUtil;

/**
 *  漫画
 * Class ComicsRepository
 * @property  ComicsBlockService $comicsBlockService
 * @property AdvService $advService
 * @property ComicsService $comicsService
 * @property ComicsKeywordsService $comicsKeywordsService
 * @property ComicsTagService $comicsTagService
 * @property ComicsFavoriteService $comicsFavoriteService
 * @property  UserService $userService
 * @property CommonService $commonService
 * @property  ComicsHistoryService $comicsHistoryService
 * @property  AccountService $accountService
 * @package App\Repositories\Api
 */
class ComicsRepository extends BaseRepository
{

    /**
     * 首页按钮
     * @return array
     */
    public function getHomeButtons()
    {
        return [
            ['name' => '专题', 'ico' => 'special', 'show_type' => 'block', 'filter' => json_encode(array('is_hot' => 'y', 'max_num' => '6'))],
            ['name' => '限免', 'ico' => 'free', 'show_type' => 'list', 'filter' => json_encode(array('pay_type' => 'free', 'order' => 'update_date', 'can_cache' => 'y'))],
            ['name' => '每日', 'ico' => 'day', 'show_type' => 'day', 'filter' => ''],
            ['name' => '完结', 'ico' => 'end', 'show_type' => 'list', 'filter' => json_encode(array('update_status' => '1', 'order' => 'update_date', 'can_cache' => 'y'))],
        ];
    }

    /**
     *获取模块列表
     * @param $query
     * @return array
     */
    public function doSearchBlocks($query)
    {
        $blocks = $this->comicsBlockService->doSearch($query);
        $result = [];
        $ads    = $this->advService->getAll('comics_block_list_ad', 'n', 12);
        foreach ($blocks as $block) {
            $block['filter'] = json_decode($block['filter'], true);
            $block['filter'] = json_encode($block['filter']);
            if ($query['max_num'] && $query['max_num'] < $block['num']) {
                $block['num'] = $query['max_num'];
            }
            $result[] = [
                'id'        => strval($block['id']),
                'name'      => strval($block['name']),
                'style'     => strval($block['style']),
                'filter'    => strval($block['filter']),
                'ico'       => strval($block['ico']),
                'page_size' => strval($block['num']),
                'page'      => '1',
                'ad'        => empty($ads) ? null : $ads[mt_rand(0, count($ads) - 1)],
                'items'     => $this->getBlockItems($block)
            ];
        }
        return $result;
    }

    /**
     *获取模块列表
     * @param $positionCode
     * @param int $page
     * @param $pageSize
     * @return array
     */
    public function getBlockList($positionCode, $page = 1, $pageSize = 6)
    {
        $blocks = $this->comicsBlockService->getListByCode($positionCode, $page, $pageSize);
        $result = [];
        $ads    = $this->advService->getAll('comics_block_list_ad', 'n', 6);
        foreach ($blocks as $block) {
            $block['filter'] = json_decode($block['filter'], true);
            $block['filter'] = json_encode($block['filter']);
            $result[]        = [
                'id'        => strval($block['id']),
                'name'      => strval($block['name']),
                'style'     => strval($block['style']),
                'filter'    => strval($block['filter']),
                'ico'       => strval($block['ico']),
                'page_size' => strval($block['num']),
                'page'      => '1',
                'ad'        => empty($ads) ? null : $ads[mt_rand(0, count($ads) - 1)],
                'items'     => $this->getBlockItems($block)
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
        $keyName = "block_comics_{$block['id']}";
        $result  = getCache($keyName);
        if (is_null($result)) {
            $filter              = json_decode($block['filter'], true);
            $filter['page_size'] = $block['num'];
            $filter['order']     = 'rand';
            $result              = $this->doSearch(null, $filter)['data'];
            setCache($keyName, $result, mt_rand(120, 180));
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
        $query['page']        = $this->getRequest($query, "page", "int", 1);
        $query['page_size']   = $this->getRequest($query, "page_size", "int", 24);
        $query['keywords']    = $this->getRequest($query, "keywords", "string");
        $query['pay_type']    = $this->getRequest($query, "pay_type", "string");
        $query['ad_code']     = $this->getRequest($query, 'ad_code', 'string');
        $query['cat_id']      = $this->getRequest($query, "cat_id", "string");
        $query['tag_id']      = $this->getRequest($query, "tag_id", "string");
        $query['is_hot']      = $this->getRequest($query, 'is_hot', 'string');
        $query['is_new']      = $this->getRequest($query, 'is_new', 'string');
        $query['is_end']      = $this->getRequest($query, 'is_end', 'string');
        $query['ids']         = $this->getRequest($query, 'ids', 'string', '');
        $query['not_ids']     = $this->getRequest($query, 'not_ids', 'string', '');
        $query['order']       = $this->getRequest($query, 'order', 'string');
        $query['update_date'] = $this->getRequest($query, 'update_date', 'string');

        $canCache = $this->getRequest($query, 'can_cache', 'string');

        //file_put_contents('debug_q.txt',var_export($_REQUEST,true).PHP_EOL,FILE_APPEND);
        if ($query['_url'] && strpos($query['_url'], 'search')) {
            if ($query['page'] == 1 && !empty($query['cat_id'])) {
                $canCache = 'y';
            }
            if ($query['page'] == 1 && !empty($query['tag_id'])) {
                $canCache = 'y';
            }
            if ($query['page'] == 1 && !empty($query['pay_type'])) {
                $canCache = 'y';
            }
        }

        //根据前端缓存一些数据到redis
        if ($canCache && $canCache == 'y' && $query['page'] == 1) {
            $cacheKey = 'comics_items_cache_' . md5(json_encode($query));
            $result   = getCache($cacheKey);
            if (empty($result)) {
                $result = $this->comicsService->doSearch($userId, $query);
                setCache($cacheKey, $result, mt_rand(60, 90));
            }
        } else {
            $result = $this->comicsService->doSearch($userId, $query);
        }
        if ($result['data'] && $query['ad_code']) {
            $this->insertAdToArray($result['data'], $query['ad_code'], $query['page'] * 1);
        }
        return $result;
    }

    /**
     *   插入广告到数组
     * @param $data
     * @param $adCode
     * @param int $page
     */
    public function insertAdToArray(&$data, $adCode, $page = 1)
    {
        if ($data && $adCode && in_array($adCode, ['video_mix_horizontal', 'video_mix_vertical'])) {
            $adItems = $this->advService->getAll($adCode, 'n', 4);
            if ($adItems) {
                foreach ($adItems as $key => $adItem) {//组装广告数据
                    $adItems[$key] = $this->comicsService->getAdItem($adItem);
                }
                $adCount = count($adItems);
                $adPage  = $page % 2;
                if ($adPage === 0 && $adCount == 3) {
                    array_shift($adItems);
                } elseif ($adPage === 0 && $adCount >= 4) {
                    array_shift($adItems);
                    array_shift($adItems);
                }
                $itemCount = count($data);
                //小于12个的数据插入一条  大于12个的插入两条
                if ($itemCount >= 12) {
                    $firstAd = array_shift($adItems);
                    $data    = CommonUtil::insertToArray($data, 5, [$firstAd]);
                    $twoAd   = array_shift($adItems);
                    $twoAd   = empty($twoAd) ? $firstAd : $twoAd;
                    $data    = CommonUtil::insertToArray($data, 12, [$twoAd]);
                } else {
                    $data[] = $adItems[mt_rand(0, count($adItems) - 1)];
                }
            }
            $data = array_values($data);
        }
    }


    /**
     * 获取每日更新界面信息
     * @return array
     */
    public function getDayPageInfo()
    {
        $result    = array(
            ["name" => "周日", "filter" => json_encode(["update_date" => '周日']), "is_selected" => "n"],
            ["name" => "周一", "filter" => json_encode(["update_date" => '周一']), "is_selected" => "n"],
            ["name" => "周二", "filter" => json_encode(["update_date" => '周二']), "is_selected" => "n"],
            ["name" => "周三", "filter" => json_encode(["update_date" => '周三']), "is_selected" => "n"],
            ["name" => "周四", "filter" => json_encode(["update_date" => '周四']), "is_selected" => "n"],
            ["name" => "周五", "filter" => json_encode(["update_date" => '周五']), "is_selected" => "n"],
            ["name" => "周六", "filter" => json_encode(["update_date" => '周六']), "is_selected" => "n"]
        );
        $today     = CommonUtil::getWeek(time(), false);
        $yesterday = CommonUtil::getWeek(CommonUtil::getTodayZeroTime() - 10, false);
        foreach ($result as $index => $item) {
            if ($item['name'] == $today['name']) {
                $item['name']        = '今天';
                $item['is_selected'] = 'y';
            }
            if ($item['name'] == $yesterday['name']) {
                $item['name'] = '昨天';
            }
            $item['filter']              = json_decode($item['filter'], true);
            $item['filter']['order']     = 'update_date';
            $item['filter']['can_cache'] = 'y';
            $item['filter']              = json_encode($item['filter']);
            $result[$index]              = $item;
        }
        return array_values($result);
    }

    /**
     * 获取热门关键字
     * @param int $size
     * @return array
     */
    public function getHotKeywords($size = 18)
    {
        return $this->comicsKeywordsService->getHotList($size);
    }

    /**
     * 搜索条件
     * @return array
     */
    public function getSearchFilter()
    {
        $result     = array();
        $categories = array(
            array('name' => '全部分类', 'value' => '', 'code' => 'cat_id')
        );
        foreach (CommonValues::getComicsCategories() as $category) {
            $categories[] = array('name' => $category, 'value' => $category, 'code' => 'cat_id');
        }
        $result[] = $categories;

        $tags   = $this->comicsTagService->getAll(true);
        $tagArr = array(
            array('name' => '全部标签', 'value' => '', 'code' => 'tag_id'),
        );
        foreach ($tags as $tag) {
            $tagArr[] = array('name' => $tag['name'], 'value' => strval($tag['id']), 'code' => 'tag_id');
        }
        $result[] = $tagArr;

        $result[] = array(
            array('name' => '综合排序', 'value' => '', 'code' => 'order'),
            array('name' => '观看最多', 'value' => 'hot', 'code' => 'order'),
            array('name' => '最新上架', 'value' => 'new', 'code' => 'order'),
            array('name' => '收藏最多', 'value' => 'ranking', 'code' => 'order')
        );
        return $result;
    }


    /**
     * 获取漫画详情
     * @param $comicsId
     * @param $userId
     * @return array|mixed
     * @throws BusinessException
     */
    public function getDetail($comicsId, $userId)
    {
        if (empty($comicsId) || empty($userId)) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '数据获取错误!');
        }
        $userInfo = $this->userService->getInfoFromCache($userId);
        $this->userService->checkUser($userInfo);
        $result = $this->comicsService->getDetail($comicsId);
        if (empty($result)) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '漫画不存在!');
        }
        $result['user']              = array(
            'id'       => $userInfo['id'],
            'username' => $userInfo['username'],
            'nickname' => $userInfo['nickname'],
            'img'      => $this->commonService->getCdnUrl($userInfo['img']),
            'is_vip'   => $userInfo['is_vip'],
            'balance'  => $userInfo['balance']
        );
        $result['is_new_user']       = $this->userService->isNewUser($userInfo) ? 'y' : 'n';
        $result['new_user_end_time'] = strval($this->userService->getNewUserTime($userInfo));
        $result['has_favorite']      = $this->comicsFavoriteService->has($userId, $comicsId) ? 'y' : 'n';
        $result['last_chapter_id']   = '';
        $result['chapter_show_num']  = '3';
        $result['chapter']           = [];
        $chapters                    = $this->comicsService->getChapterList($comicsId);

        $configs                              = getConfigs();
        $result['detail_page_ad_show_method'] = empty($configs['detail_page_ad_show_method']) ? 'banner' : strval($configs['detail_page_ad_show_method']);
        $result['ads']                        = $this->advService->getAll('app_play_ad', $userInfo['is_vip'], 6);
        $result['ico_ads']                    = $this->advService->getAll('common_ico', $userInfo['is_vip'], 20);

        $lastChapterId = "";
        $logId         = md5($userId . '_' . $comicsId);
        $logItem       = $this->comicsHistoryService->findByID($logId);
        if ($logItem && $logItem['chapter_id']) {
            $lastChapterId = $logItem['chapter_id'];
        }
        //强制广告
        $advFloatTime = $configs['adv_float'] > 0 ? intval($configs['adv_float']) : 0;
        $advFullNum   = $configs['adv_full'] > 0 ? intval($configs['adv_full']) : 0;

        if($userInfo['is_vip'] == 'y'){
            $advFloatTime = $advFullNum = 0;
        }

        $ads_full       = $this->advService->getAll('chapter_full_ad', $userInfo['is_vip'], 10);
        $ads_float = $this->advService->getAll('chapter_float_ad', $userInfo['is_vip'], 10);

        //浮动
        $result['adv_float'] = [
            'ads'  => $advFloatTime ? $ads_float : [],
            'time' => strval($advFloatTime)
        ];
        $result['adv_full']  = [
            'ads'  => $advFullNum ? $ads_full : [],
            'time' => '10'
        ];


        foreach ($chapters as $index => $chapter) {
            $newChapter = array(
                'id'            => $chapter['_id'],
                'name'          => $this->comicsService->formatChapterName($chapter['name']),
                'type'          => 'vip',
                'money'         => "0",
                'can_view'      => 'y',
                'button_text'   => '观看',
                'img'           => empty($chapter['img']) ? $result['img'] : $this->commonService->getCdnUrl($chapter['img']),
                'show_adv_full' =>  $advFullNum && ($index + 1) % $advFullNum == 0 ? 'y' : 'n',
            );
            if ($lastChapterId == $newChapter['id']) {
                $newChapter['button_text'] = '上次';
            }
            $newChapter          = $this->getCanViewChapter($result, $newChapter, $userInfo);
            $result['chapter'][] = $newChapter;
        }

        if (empty($lastChapterId) && $chapters) {
            $lastChapterId = $chapters[0]['_id'];
        }

        $result['last_chapter_id'] = $lastChapterId;
        //查询最后一话
        $query = array(
            'cat_id' => $result['category']
        );
        if ($result['tags']) {
            $query = ['tag_id' => $result['tags'][0]['id']];
        }
        $query['page_size']       = '9';
        $query['order']           = 'rand';
        $result['related_filter'] = json_encode($query);
        $query['can_cache']       = 'y';
        $related                  = $this->doSearch($userId, $query);
        $result['related_items']  = empty($related['data']) ? [] : $related['data'];

        return $result;
    }

    /**
     * 获取是否可以查看章节
     * @param $comicsDetail
     * @param $chapter
     * @param $user
     * @return mixed
     */
    public function getCanViewChapter($comicsDetail, $chapter, $user)
    {
        $freeChapters    = empty($comicsDetail['free_chapter']) ? [] : explode(',', $comicsDetail['free_chapter']);
        $chapter['type'] = 'vip';
        if ($comicsDetail['pay_type'] == 'free') {
            $chapter['type'] = 'free';
        } elseif ($comicsDetail['pay_type'] == 'vip') {
            $chapter['type'] = 'vip';
            if ($user['is_vip'] != 'y') {
                if (in_array($chapter['id'], $freeChapters)) {
                    $chapter['type'] = 'free';
                } else {
                    $chapter['can_view'] = 'n';
                }
            }
        } elseif ($comicsDetail['pay_type'] == 'money') {
            $chapter['money'] = strval($comicsDetail['money'] * 1);
            $chapter['type']  = 'money';
            if (in_array($chapter['id'], $freeChapters)) {
                $chapter['type'] = 'free';
            } else {
                $hasBuy = $this->comicsService->hasBuyChapter($chapter['id'], $user['id']);
                if (!$hasBuy) {
                    $chapter['can_view'] = 'n';
                } else {
                    $chapter['button_text'] = '已购';
                }
            }
        }
        return $chapter;
    }

    /**
     * 获取章节详情
     * @param $id
     * @param $userId
     * @return array
     * @throws BusinessException
     */
    public function getChapterDetail($id, $userId)
    {
        $result = array(
            'type'       => 'vip',
            'can_view'   => 'y',
            'error_tips' => '',
            'chapter'    => [],
            'adv_float'  => [],
            'adv_full'   => [],
        );


        $userInfo = $this->userService->getInfoFromCache($userId);
        $this->userService->checkUser($userInfo);

        $chapter = $this->comicsService->getChapterDetail($id);
        if (empty($chapter)) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '章节不存在!');
        }
        $chapter['id'] = $chapter['_id'];
        $comics        = $this->comicsService->getDetail($chapter['comics_id']);
        if (empty($comics) || $comics['status'] != 1) {
            throw  new BusinessException(StatusCode::PARAMETER_ERROR, '漫画不存在!');
        }

        $configs = getConfigs();
        $maxNum  = empty($configs['comics_max_limit_view']) ? 8 : $configs['comics_max_limit_view'] * 1;
        /***限流**/
        $limitKey = 'user_comics_detail_' . $userId;
        if (!$this->commonService->checkActionLimit($limitKey, 180, $maxNum)) {
            $result['can_view'] = 'n';
            $result['type']     = 'captcha';
            return $result;
        }
        /**判断是否能购买**/
        $checkChapter   = $this->getCanViewChapter($comics, $chapter, $userInfo);
        $result['type'] = $checkChapter['type'];
        if ($checkChapter['can_view'] == 'n' && $checkChapter['type'] == 'vip') {
            $result['can_view']   = 'n';
            $result['error_tips'] = '请先购买会员!';
            return $result;
        } elseif ($checkChapter['can_view'] == 'n' && $checkChapter['type'] == 'money') {
            $result['can_view']   = 'n';
            $result['error_tips'] = '金币不足!';
            //购买章节
            $user = $this->userService->findByID($userId);
            if (empty($user) || $user['balance'] < $comics['money']) {
                return $result;
            }
            $orderSn      = CommonUtil::createOrderNo('CO');
            $remark       = sprintf('购买漫画%s(%s)', $comics['name'], $chapter['name']);
            $reduceResult = $this->accountService->reduceBalance($user, $orderSn, $comics['money'] * 1, 3, $remark);
            if (!$reduceResult) {
                return $result;
            }
            DataCenterService::doReduceBalance($comics['id'], $comics['name'], $comics['money'], $user['balance'], ($user['balance'] - $comics['money']), 'content_purchase',$orderSn,time());
            $this->comicsService->buyChapter($comics['id'], $chapter['id'], $userId, $orderSn, $comics['money'] * 1);
            $result['can_view']   = 'y';
            $result['error_tips'] = '';

        }
        $result['chapter'] = array(
            'id'      => $chapter['_id'],
            'name'    => $this->comicsService->formatChapterName($chapter['name']),
            'img'     => empty($chapter['img']) ? $comics['img'] : $this->commonService->getCdnUrl($chapter['img']),
            'content' => array()
        );
        $contentCacheKey   = 'chapter_content_' . $chapter['_id'];
        $contents          = getCache($contentCacheKey);
        if (empty($contents)) {
            $contents = [];
            foreach ($chapter['content'] as $imgItem) {
                $contents[] = array(
                    'f' => $this->commonService->getCdnUrl($imgItem->f),
                    'w' => strval($imgItem->w),
                    'h' => strval($imgItem->h)
                );
            }
            setCache($contentCacheKey, $contents, mt_rand(200, 300));
        }
        $result['chapter']['content'] = $contents;
        //浏览日志
        $this->comicsHistoryService->do($userId, $chapter['comics_id'], $chapter);
        return $result;
    }

    /**
     * 删除日志
     * @param $userId
     * @param string $ids
     * @return bool
     */
    public function delHistories($userId, $ids = 'all')
    {
        if ($ids == 'all') {
            $this->comicsHistoryService->deleteAll($userId);
        } else {
            $ids = explode(',', $ids);
            foreach ($ids as $id) {
                $this->comicsHistoryService->delFirst($userId, $id);
            }
        }
        return true;
    }

    /**
     * 获取历史记录
     * @param $userId
     * @param $page
     * @param int $pageSize
     * @return array
     */
    public function getHistories($userId, $page, $pageSize = 15)
    {
        $result = $this->comicsHistoryService->getHistories($userId, $page, $pageSize);
        if (empty($result)) {
            return [];
        }
        $query  = array('ids' => join(',', array_keys($result)), 'page' => 1, 'page_size' => $pageSize);
        $movies = $this->doSearch($userId, $query);
        return empty($movies) ? array() : $movies['data'];
    }

    /**
     * 去收藏
     * @param $userId
     * @param $comicsId
     * @return bool
     * @throws BusinessException
     */
    public function doFavorite($userId, $comicsId)
    {
        return $this->comicsFavoriteService->do($userId, $comicsId);
    }

    /**
     * 获取收藏列表
     * @param $userId
     * @param $page
     * @param  $pageSize
     * @return array
     */
    public function getFavorites($userId, $page, $pageSize = 15)
    {
        $result = $this->comicsFavoriteService->getFavorites($userId, $page, $pageSize);
        if (empty($result)) {
            return [];
        }
        $query  = array('ids' => join(',', array_keys($result)), 'page' => 1, 'page_size' => $pageSize);
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
            $this->comicsFavoriteService->deleteAll($userId);
        } else {
            $ids = explode(',', $ids);
            foreach ($ids as $id) {
                $this->comicsFavoriteService->delFirst($userId, $id);
            }
        }
        return true;
    }

    /**
     * 获取购买历史
     * @param $userId
     * @param $page
     * @param int $pageSize
     * @return array
     */
    public function getBuyLog($userId, $page, $pageSize = 15)
    {
        $result = $this->comicsService->getBuyLogs($userId, $page, $pageSize);
        if (empty($result)) {
            return [];
        }
        $query  = array('ids' => join(',', array_keys($result)), 'page' => 1, 'page_size' => $pageSize);
        $movies = $this->doSearch($userId, $query);
        return empty($movies) ? array() : $movies['data'];
    }
}