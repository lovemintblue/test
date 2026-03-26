<?php


namespace App\Services;

use App\Constants\CommonValues;
use App\Core\Services\BaseService;
use App\Models\MovieLinkModel;
use App\Models\MovieModel;
use App\Utils\CommonUtil;
use App\Utils\HanziConvert;
use App\Utils\LogUtil;

/**
 * Class CartoonService
 * @package App\Services
 * @property MovieModel $movieModel
 * @property MovieCategoryService $movieCategoryService
 * @property MovieTagService $movieTagService
 * @property MovieKeywordsService $movieKeywordsService
 * @property AnalysisMovieService $analysisMovieService
 * @property UserService $userService
 * @property AdvService $advService
 * @property CommentService $commentService
 * @property CommonService $commonService
 * @property MovieDayService $movieDayService
 * @property MovieLinkModel $movieLinkModel
 * @property ElasticService $elasticService
 * @property YcService $ycService
 * @property MrsSystemService $mrsSystemService
 */
class MovieService extends BaseService
{
    /**
     * 获取列表
     * @param array $query
     * @param array $fields
     * @param array $sort
     * @param int $skip
     * @param int $limit
     * @return array
     */
    public function getList($query = array(), $fields = array(), $sort = array(), $skip = 0, $limit = 10)
    {
        return $this->movieModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query = [])
    {
        return $this->movieModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->movieModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->movieModel->findByID($id);
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            $this->movieModel->update($data, array("_id" => $data['_id']));
            $movieId = $data['_id'];
        } else {
            if (strlen($data['mid']) > 16) {
                $movieId = substr($data['mid'], 8, 16);
            } else {
                $movieId = $data['mid'];
            }
            $data['_id'] = $movieId;
            $this->movieModel->insert($data);
        }
        return $movieId;
    }

    /**
     * 查找并更新数据
     * @param array $query
     * @param array $update
     * @param array $fields
     * @param bool $upsert
     * @return mixed
     */
    public function findAndModify($query = array(), $update = array(), $fields = array(), $upsert = false)
    {
        return $this->movieModel->findAndModify($query, $update, $fields, $upsert);
    }

    /**
     * 修改数据(可以使用操作符)
     * @param  $document
     * @param  $where
     * @return mixed
     * @throws
     */
    public function updateRaw($document = array(), $where = array())
    {
        return $this->movieModel->updateRaw($document, $where);
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $query = ['_id' => $id];
        $result = $this->movieModel->delete($query);
        if ($result) {
            $this->elasticService->delete('movie', 'movie', $id);
            $this->delHotMovie($id);
            delCache("movie_detail_{$id}");
        }
        return $result;
    }

    /**
     * 搜索
     * @param $userId
     * @param array $filter
     * @return array|mixed
     */
    public function doSearch($userId, $filter = [])
    {
        $page = $filter['page'] ?: 1;
        $pageSize = $filter['page_size'] ?: 24;
        $keyword = strval($filter['keywords']);
        $number = strval($filter['number']);
        $payType = strval($filter['pay_type']);
        $position = strval($filter['position']);
        $catId = strval($filter['cat_id']);
        $tagId = strval($filter['tag_id']);
        $isHot = strval($filter['is_hot']);
        $isDay = strval($filter['is_day']);//优选
        $canvas = strval($filter['canvas']);
        $thumb = strval($filter['thumb']);
        $isNew = strval($filter['is_new']);
        $ids = strval($filter['ids']);
        $notIds = strval($filter['not_ids']);
        $order = $filter['order'] ?: '';
        $homeId = $filter['home_id'];
        $homeIds = $filter['home_ids'];
        $imgType = $filter['img_type'];
        $duration = $filter['min_duration'] * 1;
        $from = ($page - 1) * $pageSize;
        $source = array();
        $query = array(
            'from' => $from,
            'size' => $pageSize,
            'min_score' => 1.0,
            '_source' => $source,
            'query' => [
                'bool' => [
                    'must' => []
                ]
            ]
        );
        if (empty($filter['is_all'])) {
            $query['query']['bool']['must'][] = array(
                'term' => array('status' => 1)
            );
        }
        switch ($order) {
            case "ranking":
                $query['sort'] = array(
                    'real_favorite' => array('order' => 'desc'),
                );
                break;
            case "click":
                $query['sort'] = array(
                    'click_total' => array('order' => 'desc'),
                );
                break;
            case 'hot':
                $query['sort'] = array(
                    'is_hot' => array('order' => 'desc'),
                    'sort' => array('order' => 'desc'),
                    'show_at' => array('order' => 'desc')
                );
                break;
            case 'buy':
                $query['sort'] = array(
                    'buy' => array('order' => 'desc'),
                    'sort' => array('order' => 'desc')
                );
                break;
            case "sort":
                $query['sort'] = array(
                    'sort' => array('order' => 'desc'),
                );
                break;
            case 'new':
                $query['sort'] = array(
                    'show_at' => array('order' => 'desc'),
                );
                break;
            case "rand":
                $query['sort'] = [
//                    '_script' => [
//                        "script" => 'Math.random()',
//                        "type" => "number",
//                        "order" => "asc"
//                    ]
                    'show_at' => array('order' => 'desc'),
                    'sort' => array('order' => 'desc'),
                ];
                break;
            default:
                $query['sort'] = array(
                    'sort' => array('order' => 'desc'),
                    'show_at' => array('order' => 'desc')
                );
                break;
        }
        if ($isNew == 'y') {
            $query['sort'] = array(
                'show_at' => array('order' => 'desc')
            );
        }
        //关键字
        if ($keyword) {
            $keyword = strtolower($keyword);
            $query['query']['bool']['must'][] = array(
                'multi_match' => array(
                    'query' => $keyword,
                    "type" => "phrase",
                    'fields' => ['name', 'number', 'tags.name', 'categories.name']
                ));
            $query['min_score'] = '1.2';
            $this->movieKeywordsService->do($keyword);
        }
        if (!empty($isDay) && $page <= 3) {//每日优选-限定第一页数据才有效
            $isRec = $userId ? $this->getEveryDayFirstMovies($userId) : [];
            if (!empty($isRec)) {
                $query['from'] = 0;
                $ids = join(',', $isRec);
            } else {
                $randHot = $this->getRandomHotMovies($pageSize);
                if (!empty($randHot)) {
                    $query['from'] = 0;
                    $query['sort'] = [];
                    $ids = join(',', $randHot);
                }
            }
        }
        if (!empty($catId)) {
            array_push($query['query']['bool']['must'], ['terms' => ['categories.id' => explode(',', $catId)]]);
            unset($catId);
        }
        if (!empty($tagId)) {
            array_push($query['query']['bool']['must'], ['terms' => ['tags.id' => explode(',', $tagId)]]);
            unset($tagId);
        }
        if (!empty($homeId)) {
            array_push($query['query']['bool']['must'], ['term' => ['user_id' => $homeId]]);
            unset($homeId);
        }
        if (!empty($homeIds)) {
            array_push($query['query']['bool']['must'], ['terms' => ['user_id' => explode(',', $homeIds)]]);
            unset($homeIds);
        }
        if (!empty($payType)) {
            array_push($query['query']['bool']['must'], ['term' => ['pay_type' => $payType]]);
            unset($payType);
        }
        if (!empty($canvas)) {
            array_push($query['query']['bool']['must'], ['term' => ['canvas' => $canvas]]);
            unset($canvas);
        }
        if (!empty($imgType)) {
            array_push($query['query']['bool']['must'], ['term' => ['img_type' => $imgType]]);
            unset($imgType);
        }
        if (!empty($position)) {
            array_push($query['query']['bool']['must'], ['terms' => ['position' => ['all', $position]]]);
            unset($position);
        }
        if (!empty($isHot)) {
            array_push($query['query']['bool']['must'], ['term' => ['is_hot' => $isHot == 'y' ? 1 : 0]]);
            unset($isHot);
        }

        if (!empty($number)) {
            array_push($query['query']['bool']['must'], ['term' => ['number' => strtolower($number)]]);
            unset($number);
        }
        if (!empty($duration) && $duration > 0) {
            array_push($query['query']['bool']['must'], ['range' => ['duration' => ['gte' => $duration]]]);
            unset($duration);
        }

        if (!empty($ids)) {
            $idArr = explode(',', $ids);
            $query['query']['bool']['must'][] = array(
                'terms' => array('id' => $idArr)
            );
            unset($ids, $idArr);
        }
        if (!empty($notIds)) {
            $notIds = explode(',', $notIds);
            foreach ($notIds as $key => $notId) {
                if ($notId) {
                    $notIds[$key] = intval($notId);
                } else {
                    unset($notIds[$key]);
                }
            }
            $query['query']['bool']['must_not'][] = array(
                'ids' => array('values' => $notIds)
            );
            unset($notIds);
        }
        $items = array();
        $result = $this->elasticService->search($query, 'movie', 'movie');

        //获取计数器
        $redisCounterKeys = [];
        foreach ($result->hits->hits as $item) {
            $id = $item->_source->id;
            $redisCounterKeys[] = "movie_click_".$id;
            $redisCounterKeys[] = "movie_favorite_".$id;
            $redisCounterKeys[] = "movie_comment_".$id;;
        }
        $counterMap = $this->commonService->getRedisCounters($redisCounterKeys);

        foreach ($result->hits->hits as $item) {
            $item = $item->_source;
            $item = [
                'id' => strval($item->id),
                'name' => strval($item->name),
                'type' => 'video',
                'name_tw' => strval($item->name_tw),
                'description' => strval($item->description),
                'img_x' => $this->commonService->getCdnUrl((($canvas == 'short' || $thumb == 'short') && !empty($item->img_y)) ? $item->img_y : $item->img_x),
                'img_y' => $this->commonService->getCdnUrl((($canvas == 'short' || $thumb == 'short') && !empty($item->img_y)) ? $item->img_y : $item->img_x),
                'pay_type' => strval($item->pay_type),
                'money' => strval($item->money),
                'category' => $item->categories->name ?? '',
                'click' => value(function () use ($item,$counterMap) {
                    $keyName = 'movie_click_' . $item->id;
                    $real = $counterMap[$keyName] ?? 0;
                    return strval(CommonUtil::formatNum(intval($item->click + $real)));
                }),
                'favorite' => value(function () use ($item,$counterMap) {
                    $keyName = 'movie_favorite_' . $item->id;
                    $real = $counterMap[$keyName] ?? 0;
                    return strval(CommonUtil::formatNum(intval($item->favorite + $real)));
                }),
                'comment' => value(function () use ($item,$counterMap) {
                    $keyName = 'movie_comment_' . $item->id;
                    $real = $counterMap[$keyName] ?? 0;
                    return strval(CommonUtil::formatNum(intval($real)));
                }),
                //小图标
                'duration' => strval(CommonUtil::parseSecond($item->duration)),
                'ico' => value(function () use ($item) {
                    if ($item->pay_type == 'free') {
                        return 'free';
                    } elseif (/*$item->is_new*/
                        $item->show_at + 86400 * 3 > time()) {
                        return 'new';
                    } elseif ($item->is_hot) {
                        return 'hot';
                    }
                    return '';
                }),
                'width' => strval($item->width),
                'height' => strval($item->height),
                'canvas' => strval($item->canvas),
                'img_type' => strval($item->img_type),
                'status' => strval($item->status * 1),
                'status_text' => strval(CommonValues::getMovieStatus($item->status * 1)),
                'time_label' => CommonUtil::showTimeDiff($item->show_at),
                'link' => '',
                'tags' => value(function () use ($item) {
                    if (empty($item->tags)) {
                        return array();
                    }
                    $tags = array();
                    $index = 0;
                    foreach ($item->tags as $tag) {
                        if ($index > 3) {
                            break;
                        }
                        $tags[] = array(
                            'id' => strval($tag->id),
                            'name' => strval($tag->name)
                        );
                        $index++;
                    }
                    return $tags;
                })
            ];
            $items[] = $item;
        }
        $items = array_values($items);
        $result = [
            'data' => $items,
            'total' => value(function () use ($result) {
                if (isset($result->hits->total->value)) {
                    return strval($result->hits->total->value);
                }
                return $result->hits->total ? strval($result->hits->total) : '0';
            }),
            'current_page' => (string)$page,
            'page_size' => (string)$pageSize,
        ];
        $result['last_page'] = (string)ceil($result['total'] / $pageSize);

        if($keyword) {
            DataCenterService::doKeywordSearch($keyword,$result['total']);
        }

        return $result;
    }

    /**
     * 广告
     * @param $ad
     * @return array
     */
    public function getAdItem($ad)
    {
        $row = [
            'id' => strval('ad_' . $ad['id']),
            'name' => strval($ad['name']),
            'type' => 'ad',
            'name_tw' => '',
            'description' => '',
            'img_x' => $ad['content'],
            'img_y' => $ad['content'],
            'pay_type' => '',
            'money' => '0',
            'category' => '广告',
            'click' => CommonUtil::formatNum(mt_rand(200000, 300000)),
            'favorite' => CommonUtil::formatNum(mt_rand(5000, 10000)),
            'comment' => '0',
            'duration' => '0',
            'ico' => 'ad',
            'width' => '0',
            'height' => '0',
            'canvas' => '',
            'status' => '0',
            'status_text' => '',
            'time_label' => '广告',
            'link' => strval($ad['link'])
        ];
        return $row;
    }

    /**
     * 获取当天优选视频
     * @param $userId
     * @return array
     */
    protected function getEveryDayFirstMovies($userId)
    {
        //查询今日优选、优先展示此20部视频
        $date = date('Y-m-d');
        //记录用户当天是否第一次进入APP
        $keyName = md5('first_' . $date . '_' . $userId);
        $isHave = $this->commonService->getRedis()->get($keyName);
        //用户不是第一次
        if ($isHave) {
            return array();
        }
        $expAt = CommonUtil::getTodayEndTime() - time();
        $this->commonService->getRedis()->set($keyName, 1, $expAt);
        $rows = $this->movieDayService->getList(['label' => $date], ['movie_id'], [], 0, 20);
        $ids = array_column($rows, 'movie_id');
        return array_values($ids);
    }

    /**
     * 获取随机热门数据
     * @param $limit
     * @return array|bool|mixed|string
     */
    public function getRandomHotMovies($limit)
    {
        return $this->commonService->getRedis()->sRandMember('recommend_movies', $limit);
    }

    /**
     * 获取对应分类
     * @param $ids
     * @return array
     */
    public function getCategoriesByIds($ids)
    {
        if (empty($ids)) {
            return [];
        }
        $rows = $this->movieCategoryService->getList(['_id' => ['$in' => $ids]], ['_id', 'name'], [], 0, 1000);
        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id' => $row['_id'],
                'name' => $row['name'],
            ];
        }
        return $result;
    }

    /**
     * 获取对应标签
     * @param $ids
     * @return array
     */
    public function getTagsByIds($ids)
    {
        if (empty($ids)) {
            return [];
        }
        $rows = $this->movieTagService->getList(['_id' => ['$in' => $ids]], ['_id', 'name'], [], 0, 1000);
        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id' => $row['_id'],
                'name' => $row['name'],
            ];
        }
        return $result;
    }

    /**
     * 同步到es
     * @param $id
     * @return bool
     */
    public function asyncEs($id)
    {
        $row = $this->findByID($id);
        if (empty($row)) {
            return false;
        }
        $tagIds = $row['tags'];
        $row['tags'] = [];
        $row['categories'] = value(function () use ($row) {
            $category = $this->movieCategoryService->findByID(intval($row['categories']));
            return $category ? ['id' => $category['_id'], 'name' => $category['name'],] : [];
        });
        //只储存一级标签
        foreach ($tagIds as $tagId) {
            $tag = $this->movieTagService->findByID(intval($tagId));
            if ($tag) {
                //标签转换
                $row['tags'][$tag['_id']] = [
                    'id' => $tag['_id'],
                    'name' => $tag['name']
                ];
            }
        }
        $row['tags'] = array_values($row['tags']);

        $row['id'] = $row['_id'];
        $row['is_more_link'] = $row['is_more_link'] * 1;
        $realComment = $this->commentService->count(['object_id' => $id, 'object_type' => 'movie']);
        $updated = array(
            'async_at' => time(),
            'click_total' => $row['real_click'] + $row['click'],
            'comment' => intval($realComment)
        );
        if ($updated) {
            $this->movieModel->updateRaw(array('$set' => $updated), array('_id' => $id));
        }
        $row['show_at']= intval($row['show_at']);
        $row['issue_date_time']=$row['issue_date']?strtotime($row['issue_date']):0;
        $row['issue_date']=$row['issue_date_time']?date('Y-m-d',$row['issue_date_time']):null;
        if($row['issue_date_time']<strtotime('1985-02-01')){
            $row['issue_date_time']=0;
        }
        $row['issue_date_year']=0;
        $row['issue_date_month']=0;
        if($row['issue_date_time']>0){
            $row['issue_date_month'] = strtotime(date('Y-m-01',$row['issue_date_time']));
            $row['issue_date_year'] = strtotime(date('Y-01-01',$row['issue_date_time']));
        }

        $this->commonService->setRedisCounter("movie_click_" . $row['id'], $row['real_click']);
        $this->commonService->setRedisCounter("movie_favorite_" . $row['id'], $row['real_favorite']);
        $this->commonService->setRedisCounter("movie_comment_" . $row['id'], $realComment);

        unset($row['_id']);
        return $this->elasticService->save($row['id'], $row, 'movie', 'movie');
    }

    /**
     * 从热门集合中移除数据
     * @param $id
     * @return  boolean
     */
    public function delHotMovie($id)
    {
        $this->commonService->getRedis()->sRem('recommend_movies', $id);
        return true;
    }

    /**
     * 添加视频到热门集合
     * @param $id
     * @return bool
     */
    public function setHotMovie($id)
    {
        $this->commonService->getRedis()->sAdd('recommend_movies', $id);
        return true;
    }

    public function asyncCommonMrs($query)
    {
        $info = array(
            'count' => 0,
            'success' => 0,
        );
        $mediaUrl = $this->commonService->getConfig('common_media_url');
        $mediaKey = $this->commonService->getConfig('common_media_key');
        $url = $mediaUrl . "/cxapi/av/list?key={$mediaKey}";
        $result = json_decode(CommonUtil::httpPost($url, $query), true);
        if ($result['status'] != 'y' || empty($result['data']['items'])) {
            return $info;
        }
        foreach ($result['data']['items'] as $item) {
            $info['count'] += 1;
            if ($this->saveMrsData($item, true)) {
                LogUtil::info('Async movie ok:' . $item['name']);
                $info['success'] += 1;
            } else {
                LogUtil::error('Async movie error:' . $item['name']);
            }
        }
        return $info;
    }

    /**
     * 同步媒资库
     * @param $query
     * @return array
     */
    public function asyncMrs($query,$isUpdateLink=false)
    {
        $info = array(
            'count' => 0,
            'success' => 0,
        );

        if($query['source']=='laosiji'){//老司机库
            $result = $this->mrsSystemService->getLsjMovieDetail($query);
            $items = $result?[$result]:[];
//            $isPublic = false;
        }else{
            $items = $this->mrsSystemService->getMovieList($query);
//            $isPublic = false;
        }

        foreach ($items as $item) {
            $info['count'] += 1;
            //只是更新链接和封面
            if($isUpdateLink){
                $saveResult = $this->updateMrsLink($item);
            }else{
                $saveResult = $this->saveMrsData($item);
            }
            if($saveResult){
                LogUtil::info('Async movie ok:'.$item['name']);
                $info['success']+=1;
            }else{
                LogUtil::error('Async movie error:'.$item['name']);
            }
        }
        return $info;
    }
    /**
     *  更新封面和视频的链接
     * @param $mrsData
     * @return bool
     */
    public function updateMrsLink($mrsData)
    {
        if (empty($mrsData['id']) || empty($mrsData['name']) ||empty($mrsData['img'])) {
            return false;
        }
        if (empty($mrsData['is_more_link']) && (empty($mrsData['m3u8_url']) || empty($mrsData['preview_m3u8_url']))) {
            return false;
        }
        if ($mrsData['is_more_link'] && empty($mrsData['links'])) {
            return false;
        }

        $config=container()->get('config');
        if($config->mrs->position&&!in_array($mrsData['position'],explode(',',$config->mrs->position))){
            return false;
        }
        if($config->mrs->cat_id&&!in_array($mrsData['cat_id'],explode(',',$config->mrs->cat_id))){
            return false;
        }

        $mid=$mrsData['id'];
        $movieModel=$this->findFirst(['mid'=>$mid]);
        if(!empty($movieModel)){
            $movieId = $movieModel['_id'];
            $movieSaveData=[
                '_id'       => $movieId,
                'img_x'     =>$mrsData['img'],
                'img_y'     =>$mrsData['img_y'],
                'm3u8_url'   =>$mrsData['m3u8_url'],
                'duration'=>$mrsData['duration'],
                'preview_m3u8_url'    =>$mrsData['preview_m3u8_url'],
                'width'     =>$mrsData['width'],
                'height'    =>$mrsData['height'],
                'description'=> $mrsData['description']?:'',
                'preview_images' => $mrsData['preview_images']?:'',
                'img_width' =>$mrsData['img_width']*1,
                'img_height'=>$mrsData['img_height']*1,
                'img_type'  =>$mrsData['img_type'],
            ];
            $this->save($movieSaveData);
            if ($mrsData['is_more_link']&&$mrsData['links']) {
                $this->asyncLinks($movieId, $mrsData['links']);
            }
            $this->asyncEs($movieId);
        }
        return true;
    }

    /**
     * 保存媒资库数据
     * @param $mrsData
     * @param $isPublic
     * @return bool
     */
    public function saveMrsData($mrsData, $isPublic = false)
    {

        if (empty($mrsData['id']) || empty($mrsData['name']) || empty($mrsData['img'])) {
            return false;
        }
        if (empty($mrsData['is_more_link']) && (empty($mrsData['m3u8_url']) || empty($mrsData['preview_m3u8_url']))) {
            return false;
        }
        if ($mrsData['is_more_link'] && empty($mrsData['links'])) {
            return false;
        }

        if (php_sapi_name() == 'cli'){
            $config=container()->get('config');
            if($config->mrs->position&&!in_array($mrsData['position'],explode(',',$config->mrs->position))){
                return false;
            }
            if($config->mrs->cat_id&&!in_array($mrsData['cat_id'],explode(',',$config->mrs->cat_id))){
                return false;
            }
        }

        $tags = [];
        $catId = "";
        $tagGroup = "";
        /***私有库同步标签  非私有不同步**/
        if (!$isPublic) {
            if ($mrsData['cat_id']) {
                $code = strval($mrsData['cat_name']??$mrsData['cat_id']);
                $category = $this->movieCategoryService->findFirst(['code' => $code]);
                if (empty($category)) {
                    $catId = $this->movieCategoryService->save([
                        'code' => $code,
                        'name' => $code,
                        'position' => 'all',
                        'is_hot' => 0
                    ]);
                } else {
                    $catId = $category['_id'];
                }
            }
            if ($mrsData['tags']) {
                foreach ($mrsData['tags'] as $tag) {
                    $tag['id'] = intval($tag['id']);
                    if (empty($tag)) {
                        continue;
                    }
                    $tagModel = $this->movieTagService->findFirst(['name' => strval($tag['name'])]);
                    if (empty($tagModel)) {
                        $tags[] = $this->movieTagService->insert([
//                            '_id' => $tag['id'] * 1,
                            'name' => strval($tag['name']),
                            'is_hot' => 0,
                            'parent_id' => 0,
                            'attribute' => $tag['group']?:'',
                            'series' => 'all',
                        ]);
                    } else {
                        if (empty($tagModel['attribute']) && !empty($tag['group'])) {
                            $this->movieTagService->save(array(
                                '_id' => $tagModel['_id'],
                                'attribute' => $tag['group']?:'',
                            ));
                        }
                        $tags[] = $tagModel['_id'];
                    }
                    if(!empty($tag['group'])&&substr_count($tag['group'],'综艺')){
                        $tagGroup = 'zongyi';
                    }
                    if(!empty($tag['group'])&&substr_count($tag['group'],'UP')){
                        $tagGroup = 'fuliji';
                    }
                }
            }
        }
        /***兼容公共库、小组库的 position**/
        if(empty($mrsData['position'])){
            //position
            if($mrsData['cat_id']=='DM'){
                $mrsData['position']='cartoon';
            }elseif ($mrsData['cat_id']=='DSP'){
                $mrsData['position']='douyin';
            }elseif ($mrsData['cat_id']=='GC' && (in_array(600,$mrsData['tags'])||in_array(623,$mrsData['tags'])||in_array(1012,$mrsData['tags']))){
                $mrsData['position']='bl';
            }elseif ($mrsData['cat_id']=='GC' && $mrsData['img_type']=='short'){
                $mrsData['position']='douyin';
            }else{
                $mrsData['position']='normal';
            }
            $blTags = [
                623,600,1011,852,1010,854,855,850,856,853,851,857,1012
            ];
            $awTags =[
                649,559,562,647,834,1062,1059,1077,648,1075,832,646,731,830,833,1060,1071,1072,1074,1070,1176
            ];
            foreach ($tags as $tag){
                if(in_array($tag,$blTags)){
                    $mrsData['position']='bl';
                    break;
                }
                if(in_array($tag,$awTags)){
                    $mrsData['position']='dark';
                    break;
                }
            }
            //canvas
            if($mrsData['cat_id']=='DJ' || $mrsData['cat_id']=='CGDM' || $mrsData['is_more_link']){
                $mrsData['canvas']='long';
            }
        }
        if($mrsData['position']=='movie'){//影视免费
            $mrsData['money'] = -1;
            $mrsData['position'] = 'short';
        }
        if ($mrsData['is_more_link']) {
            $firstLink = $mrsData['links'][0];
            $mrsData['m3u8_url'] = $firstLink['m3u8_url'];
            $mrsData['preview_m3u8_url'] = $firstLink['preview_m3u8_url'];
            $mrsData['duration'] = $firstLink['duration'] * 1;
            $mrsData['width'] = $firstLink['width'] * 1;
            $mrsData['height'] = $firstLink['height'] * 1;
        }
        $mid = $mrsData['id'];
        $movieModel = $this->findFirst(['mid' => $mid]);
        if (empty($movieModel)) {
            $positions = array_keys(CommonValues::getMoviePosition());
            $movieSaveData = [
                'mid' => $mid,
                'categories' => $catId ?? null,
                'tags' => $tags,
                'name' => $mrsData['name'],
                'actor' => $mrsData['actor'],
                'level' => $mrsData['level'] * 1,
                'number' => $mrsData['number'] ?: uniqid('FH_'),
                'img_x' => $mrsData['img'],
                'img_y' => $mrsData['img_y'],
                'sort' => 0,
                'is_new' => 0,
                'is_hot' => 0,
                'favorite' => rand(3000, 5000),
                'real_favorite' => 0,
                'click' => rand(800000, 1100000),
                'real_click' => 0,
                'favorite_rate' => 0,
                'score' => rand(92, 96),
                'buy' => 0,
                'comment' => 0,
                'money' => intval($mrsData['money']),
                'pay_type' => CommonValues::getPayTypeByMoney(intval($mrsData['money'])),
                'm3u8_url' => $mrsData['m3u8_url'],
                'duration' => $mrsData['duration']*1,
                'preview_m3u8_url' => $mrsData['preview_m3u8_url'],
                'width' => $mrsData['width'] * 1,
                'height' => $mrsData['height'] * 1,
                'position' => $mrsData['position']&&in_array($mrsData['position'],$positions) ? $mrsData['position'] : 'normal',
                'canvas' => $mrsData['width'] > $mrsData['height'] ? 'long' : 'short',
                'status' => 0,//默认未上架
                'description' => $mrsData['description'] ?: '',
                'show_at' => 0,
                'preview_images' => $mrsData['preview_images'] ?: '',
                'img_width' =>$mrsData['img_width']*1,
                'img_height'=>$mrsData['img_height']*1,
                'img_type'  =>$mrsData['img_type'],
                'is_more_link' => $mrsData['is_more_link'] * 1,
                'update_status' => $mrsData['update_status'] * 1,
                'publisher'  => strval($mrsData['publisher']),
                'issue_date' => strval($mrsData['issue_date']),
                'language' => '',
                'director' => '',
                'area' => '',
                'suggest_tags'=> strval($mrsData['suggest_tags']),
                'source'=> strval($mrsData['source']?:'media'),
            ];
            $movieId = $this->save($movieSaveData);
        } else {
            $movieSaveData = array(
                '_id' => $movieModel['_id'],
                'name' => $mrsData['name'],
                'img_x' => $mrsData['img'],
                'img_y' => $mrsData['img_y'],
                'm3u8_url' => $mrsData['m3u8_url'],
                'duration' => $mrsData['duration']*1,
                'preview_m3u8_url' => $mrsData['preview_m3u8_url'],
                'width' => $mrsData['width']*1,
                'height' => $mrsData['height']*1,
                'img_width' =>$mrsData['img_width']*1,
                'img_height'=>$mrsData['img_height']*1,
                'img_type'  =>$mrsData['img_type'],
                'is_more_link' => $mrsData['is_more_link'] * 1,
                'update_status' => $mrsData['update_status'] * 1,
                'publisher'  => strval($mrsData['publisher']),
                'issue_date' => strval($mrsData['issue_date']),
                'suggest_tags'=> strval($mrsData['suggest_tags']),
                'source'=> strval($mrsData['source']?:'media'),
            );
            $movieId = $this->save($movieSaveData);
        }
        if ($mrsData['is_more_link']&&$mrsData['links']) {
            $this->asyncLinks($movieId, $mrsData['links']);
        }


        $config=container()->get('config');
        if($config->mrs->auto&&$config->mrs->auto == 'y'){
            /***自动上架**/
            if($mrsData['source']=='laosiji'&&$mrsData['is_auto']){
                if($config->mrs->up_uids){
                    $userIds = explode(',',$config->mrs->up_uids);
                    $userId = $userIds?$userIds[mt_rand(0,count($userIds)-1)]*1:0;
                    $updateData = [
                        'status'=>$userId?1:0,
                        'show_at' => mt_rand(time()-24*3600*20,time()),
                        'user_id' => $userId,
                    ];
                    $this->updateRaw(['$set'=>$updateData],['_id'=>$movieId]);
                    $this->asyncEs($movieId);
                }else{
                    if($mrsData['nickname']){
                        $deviceId = 'up_'.$mrsData['nickname'];
                        $sign = '';
                        $categories[] = 'video';
                        if($mrsData['position']=='movie'){
                            $sign = sprintf('Hi,我是「%s」,每日更新全网热播影视,快来点个关注,防止迷路吧!', $mrsData['nickname']);
                        }elseif($mrsData['position']=='douyin'){
                            $categories[] = 'douyin';
                        }elseif($mrsData['position']=='av'&&$mrsData['cat_id']=='1'){
                            $categories[] = 'jp_av';
                        }elseif($mrsData['position']=='av'&&$mrsData['cat_id']=='12'){
                            $categories[] = 'om_av';
                        }elseif($mrsData['position']=='guochan'){
                            $categories[] = 'gc_av';
                        }
                        if($mrsData['cat_id']=='4'||$tagGroup=='zongyi'){
                            $categories[] = 'zongyi';
                        }elseif($tagGroup=='fuliji'){
                            $categories[] = 'fuliji';
                        }
                        $userId = $this->userService->createUpUser($deviceId,$mrsData['nickname'],implode(',',$categories),$sign);
                        if(empty($userId)){
                            $configs = getConfigs();
                            $userIds = CommonUtil::parseUserIds($configs['post_user_ids']);
                            $userId = $userIds?$userIds[mt_rand(0,count($userIds)-1)]*1:0;
                        }
                        $updateData = [
                            'status'=>$userId?1:0,
                            'show_at' => mt_rand(time()-24*3600*20,time()),
                            'user_id' => $userId,
                        ];
                        $this->updateRaw(['$set'=>$updateData],['_id'=>$movieId]);
                        $this->asyncEs($movieId);
                    }
                }

            }else{
                $this->ycService->handleMovie($movieId);
            }
        }


        return true;
    }

    /**
     * 保存影视媒资库数据
     * @param $mrsData
     * @return mixed
     */
    public function saveMovieMrsData($mrsData)
    {
        if (empty($mrsData['id']) || empty($mrsData['name']) || empty($mrsData['img'])) {
            return null;
        }
        if (empty($mrsData['links'])) {
            return null;
        }
        $tags = [];
        $catId = "";

        //分类+2000  避免和其他冲突
        if ($mrsData['cat_id']) {
            $mrsData['cat_id'] +=2000;
            $category = $this->movieCategoryService->findFirst(['code' => $mrsData['cat_id']]);
            if (empty($category)) {
                $catId = $this->movieCategoryService->save([
                    'code' => $mrsData['cat_id'],
                    'name' => $mrsData['cat_text'],
                    'position' => 'all',
                    'is_hot' => 0
                ]);
            } else {
                $catId = $category['_id'];
            }
        }
        if ($mrsData['type']) {
            $tagModel = $this->movieTagService->findFirst(['name' => $mrsData['type'],'attribute'=>$mrsData['cat_text']]);
            if (empty($tagModel)) {
                $tags[] = $this->movieTagService->insert([
                    'name' => $mrsData['type'],
                    'is_hot' => 0,
                    'parent_id' => 0,
                    'attribute' => $mrsData['cat_text'],
                    'series' => 'all',
                ]);
            } else {
                $tags[] = $tagModel['_id'];
            }
            //自动注册用户关联
            $deviceId= 'mms_' . $mrsData['type'];
            $checkItem = $this->userService->findFirst(['device_id' => $deviceId]);
            if (empty($checkItem)) {
                $user = $this->userService->getDefaultUserRow();
                $user['phone'] = $deviceId;
                $user['device_id'] =$deviceId;
                $user['nickname'] = '影视君-'.$mrsData['type'];
                $user['sign'] = sprintf('Hi,我是P站%,每日更新全网热播影视,快来点个关注,防止迷路吧!', $user['nickname']);
                $user['is_up'] = 1;
                $username = '';
                LogUtil::info('Save user:' . $deviceId);
                $mrsData['user_id'] = $this->userService->save($user, $username);
            }else{
                $mrsData['user_id'] = $checkItem['_id'] *1;
            }
        }
        $mid = $mrsData['id'];
        $movieModel = $this->findFirst(['mid' => $mid]);
        if (empty($movieModel)) {
            $movieSaveData = [
                'mid' => $mid,
                'categories' => $catId ?? null,
                'tags' => $tags,
                'name' => $mrsData['name'],
                'actor' => $mrsData['actor'],
                'level' => $mrsData['level'] * 1,
                'user_id' => $mrsData['user_id']*1,
                'number' => $mrsData['number'] ?: uniqid('FH_'),
                'img_x' => $mrsData['img'],
                'img_y' => $mrsData['img_y'],
                'sort' => 0,
                'is_new' => 0,
                'is_hot' => 0,
                'favorite' => rand(3000, 5000),
                'real_favorite' => 0,
                'love' => rand(3000, 5000),
                'real_love' => 0,
                'click' => rand(800000, 1100000),
                'real_click' => 0,
                'favorite_rate' => 0,
                'score' => rand(92, 96),
                'buy' => 0,
                'comment' => 0,
                'money' => 0,
                'pay_type' => 'free',
                'm3u8_url' => '',
                'duration' => -1,
                'preview_m3u8_url' => '',
                'width' => 0,
                'height' =>0,
                'position' => 'movie',
                'canvas' => 'long',
                'status' => $mrsData['user_id']?1:0,//默认未上架
                'description' => $mrsData['description'] ?: '',
                'show_at' => strtotime($mrsData['created_at']),
                'preview_images' => $mrsData['preview_images'] ?: '',
                'img_width' =>0,
                'img_height'=>0,
                'img_type'  =>'short',
                'is_more_link' => 1,
                'update_status' => $mrsData['status'] * 1,
                'language' => strval($mrsData['language']),
                'director' => strval($mrsData['director']),
                'area' => strval($mrsData['area']),
                'created_at' => strtotime($mrsData['created_at']),
                'updated_at' => strtotime($mrsData['updated_at']),
                'last_at' => strtotime($mrsData['updated_at']),
                'publisher'  =>'',
                'issue_date' => strval($mrsData['release_at'])
            ];
            $movieId = $this->save($movieSaveData);
        } else {
            $movieSaveData = array(
                '_id' => $movieModel['_id'],
                'name' => $mrsData['name'],
                'user_id' => $mrsData['user_id']*1,
                'update_status' => $mrsData['status'] * 1,
                'last_at' => strtotime($mrsData['updated_at']),
                'issue_date' => strval($mrsData['release_at'])
            );
            $movieId = $this->save($movieSaveData);
        }
        if ($mrsData['links']['haiwaikan']) {
            $this->asyncLinks($movieId,$mrsData['links']['haiwaikan']);
        }else{
            $this->asyncLinks($movieId,$mrsData['links']['inner']);
        }
        $this->asyncEs($movieId);
        return $movieId;
    }

    /**
     * 同步视频链接
     * @param $movieId
     * @param $links
     * @return bool
     */
    public function asyncLinks($movieId, $links)
    {
        foreach ($links as $link) {
            $checkItem = $this->movieLinkModel->findByID($link['id']);
            if ($checkItem) {
                $updateData = array();
                if ($link['sort'] != $checkItem['sort']) {
                    $updateData['sort'] = $link['sort'] * 1;
                }
                if (!empty($updateData)) {
                    $this->movieLinkModel->updateRaw(array('$set' => $updateData), array('_id' => $link['id']));
                }
            } else {
                $data = array(
                    '_id' => $link['id'],
                    'name' => $link['name'],
                    'movie_id' => $movieId,
                    'sort' => $link['sort'] * 1,
                    'duration' => $link['duration'] * 1,
                    'preview_m3u8_url' => $link['preview_m3u8_url'],
                    'm3u8_url' => $link['m3u8_url'],
                    'height' => $link['height'] * 1,
                    'width' => $link['width'] * 1,
                    'created_at' => $link['created_at'] * 1,
                    'updated_at' => $link['updated_at'] * 1
                );
                $this->movieLinkModel->insert($data);
            }
        }
        return true;
    }

    /**
     * 获取多剧集
     * @param $movieId
     * @return array|bool|mixed
     */
    public function getLinks($movieId)
    {
        $keyName = 'movie_links_' . $movieId;
        $result = getCache($keyName);
        if (empty($result)) {
            $links = $this->movieLinkModel->find(array('movie_id' => $movieId), array(), array('sort' => -1, 'created_at' => 1), 0, 2000);
            $result = [];
            foreach ($links as $link) {
                $result[$link['_id']] = array(
                    'id' => $link['_id'],
                    'name' => $link['name'],
                    'preview_m3u8_url' => $link['preview_m3u8_url'],
                    'm3u8_url' => $link['m3u8_url'],
                );
            }
            setCache($keyName, $result, mt_rand(60, 120));
        }
        return $result;
    }

    /**
     * 设置评分
     * @param $movieId
     * @param $score
     * @return mixed
     */
    public function setScore($movieId, $score)
    {
        return $this->updateRaw(['$set' => ['score' => intval($score)]], ['_id' => intval($movieId)]);
    }

    /**
     * 设置收藏率
     * @param $movieId
     * @param $rate
     * @return mixed
     */
    public function setFavoriteRate($movieId, $rate)
    {
        return $this->updateRaw(['$set' => ['favorite_rate' => intval($rate)]], ['_id' => $movieId]);
    }

    /**
     * 事件处理
     * @param $data
     */
    public function handler($data)
    {
        $movieId = $data['movie_id'];
        $movie = $this->elasticService->get($movieId, 'movie', 'movie');
        $movieName = $movie['name']??'';
        $movieCatId = $movie['categories']['id']??'';
        $movieCatName = $movie['categories']['name']??'';
        switch ($data['action']) {
            case 'click':
                $this->commonService->updateRedisCounter("movie_click_{$movieId}", 1);
                $this->updateRaw(array('$inc' => array('real_click' => 1)), array('_id' => $movieId));
                $this->analysisMovieService->inc($movieId, 'click', 1);
                break;
            case 'buy':
                $this->updateRaw(array('$inc' => array('buy' => 1)), array('_id' => $movieId));
                $this->analysisMovieService->inc($movieId, 'buy_num', 1);
                $this->analysisMovieService->inc($movieId, 'buy_total', intval($data['money']));
                DataCenterService::doMovieBuy($movieId, $movieName,$movieCatId,$movieCatName,$data['order_sn'],$data['money']);
                break;
            case 'favorite':
                $this->commonService->updateRedisCounter("movie_favorite_{$movieId}", 1);
                $this->updateRaw(array('$inc' => array('real_favorite' => 1)), array('_id' => $movieId));
                $this->analysisMovieService->inc($movieId, 'favorite', 1);
                DataCenterService::doMovieFavorite($movieId, $movieName,$movieCatId,$movieCatName,true);
                break;
            case 'unFavorite':
                $this->commonService->updateRedisCounter("movie_favorite_{$movieId}", -1);
                $this->updateRaw(array('$inc' => array('real_favorite' => -1)), array('_id' => $movieId));
                $this->analysisMovieService->inc($movieId, 'favorite', -1);
                DataCenterService::doMovieFavorite($movieId, $movieName,$movieCatId,$movieCatName,false);
                break;
            case 'download':
                $this->updateRaw(array('$inc' => array('download' => 1)), array('_id' => $movieId));
                break;
            case 'love':
                DataCenterService::doMovieLove($movieId, $movieName,$movieCatId,$movieCatName,true);
                break;
            case 'unLove':
                DataCenterService::doMovieLove($movieId, $movieName,$movieCatId,$movieCatName,false);
                break;
        }
    }

    /**
     * 更新链接是否免费
     * @param $movieId
     * @param $links
     * @return bool
     */
    public function updateLinks($movieId,$links)
    {
        if(empty($links) || !is_array($links) || empty($movieId)){
            return false;
        }
        $newLinks = [];
        foreach ($links as $link)
        {
            if(!empty($link)){
               $newLinks[] = $link;
            }
        }
        $this->movieModel->updateRaw(['$set'=>['free_links'=>join(',',$newLinks)]],['_id'=>$movieId]);
        $this->asyncEs($movieId);
        return true;
    }


    /**
     * 格式化剧集的顺序
     * @param $links
     * @return mixed
     */
    public function formatLinks($links)
    {
        //两个不分前后 无所谓
        if (count($links) <= 3) {
            return $links;
        }
        //获取对应的集数name
        $numberNames = array();  //数字类型
        $otherNames = array();   //非数字类型
        foreach ($links as $link) {
            $name = $link['name'];
            if (strpos($name, '-') !== false) {
                $tempName = explode('-', $name);
                $name = $tempName[0];
            }
            if (strpos($name, '_') !== false) {
                $tempName = explode('_', $name);
                $name = $tempName[0];
            }
            if (strpos($name, '00') === 0) {
                $name = substr($name, 2);
            }
            if (strpos($name, '0') === 0) {
                $name = substr($name, 1);
            }
            if (preg_match('/^\d+$/i', $name)) {
                $numberNames[intval($name)] = $link;
            } else {
                $otherNames[$name] = $link;
            }
        }
        //如果为搜索到数字类的 或者数字类的少于非数字的 直接返回
        if (empty($numberNames) || count($numberNames) < count($otherNames)) {
            return $links;
        }
        ksort($numberNames, SORT_NUMERIC);
        return array_merge(array_values($numberNames), array_values($otherNames));
    }

}