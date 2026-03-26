<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CommonValues;
use App\Core\Services\BaseService;
use App\Models\NovelChapterModel;
use App\Models\NovelChapterOrderModel;
use App\Models\NovelModel;
use App\Models\NovelOrderModel;
use App\Models\NovelTagModel;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 *  小说
 * @package App\Services
 *
 * @property  NovelModel $novelModel
 * @property NovelTagModel $novelTagModel
 * @property CommonService $commonService
 * @property NovelChapterModel $novelChapterModel
 * @property NovelTagService $novelTagService
 * @property ElasticService $elasticService
 * @property CommentService $commentService
 * @property NovelKeywordsService $novelKeywordsService
 * @property  NovelChapterOrderModel $novelChapterOrderModel
 * @property  NovelOrderModel $novelOrderModel
 * @property  AdvService $advService
 * @property  MrsSystemService $mrsSystemService
 */
class NovelService extends BaseService
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
        return $this->novelModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query = [])
    {
        return $this->novelModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->novelModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->novelModel->findByID($id);
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->novelModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->novelModel->insert($data);
        }
    }

    /**
     * 插入数据
     * @param $data
     * @return bool|int
     */
    public function insert($data)
    {
        return $this->novelModel->insert($data);
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $result = $this->novelModel->delete(array('_id' => $id));
        if ($result) {
            $this->elasticService->delete('novel', 'novel', $id);
        }
        return $result;
    }

    /**
     * 更新
     * @param array $document
     * @param array $where
     * @return mixed
     */
    public function updateRaw($document = array(), $where = array())
    {
        return $this->novelModel->updateRaw($document, $where);
    }

    /**
     * 根据id同步
     * @param $id
     * @return bool
     */
    public function asyncMrsById($id)
    {
        if (empty($id)) {
            return false;
        }
        $query = ['ids' => $id];
        $this->asyncMrs($query);
        return true;
    }

    /**
     * 同步媒资库
     * @param $query
     * @return array
     */
    public function asyncMrs($query)
    {
        $info = array(
            'count' => 0,
            'success' => 0,
        );
        $config=container()->get('config');
        if($config->mrs->source=='laosiji'){//老司机库
            $result = $this->mrsSystemService->getLsjNovelDetail(['id'=>$query['ids']]);
            $items = $result?[$result]:[];
        }else{
            $query['info_status'] = 1;
            $items = $this->mrsSystemService->getNovelList($query);
        }
        foreach ($items as $item) {
            $info['count'] += 1;
            if ($this->saveMrsData($item)) {
                LogUtil::info('Async novel ok:' . $item['name']);
                $info['success'] += 1;
            } else {
                LogUtil::error('Async novel error:' . $item['name']);
            }
        }
        return $info;
    }

    /**
     * 保存媒资库数据
     * @param $mrsData
     * @param $isPublic
     * @return bool
     */
    public function saveMrsData($mrsData, $isPublic = false)
    {
        if (empty($mrsData['id']) || empty($mrsData['name']) || empty($mrsData['img']) || empty($mrsData['chapter'])) {
            return false;
        }
        $tags = [];
        /***私有库同步标签  非私有不同步**/
        if (!$isPublic) {
            if ($mrsData['tags']) {
                foreach ($mrsData['tags'] as $tag) {
                    $tag['id'] = intval($tag['id']);
                    if (empty($tag)) {
                        continue;
                    }
                    $tagModel = $this->novelTagService->findFirst(['_id' => $tag['id']]);
                    if (empty($tagModel)) {
                        $tags[] = $this->novelTagService->insert([
                            '_id' => $tag['id'] * 1,
                            'name' => $tag['name'],
                            'is_hot' => 0,
                            'attribute' => $tag['group'] ? : ''
                        ]);
                    } else {
                        if (empty($tagModel['attribute']) && !empty($tag['group'])) {
                            $this->novelTagService->save(array(
                                '_id' => $tagModel['_id'],
                                'attribute' => $tag['group']
                            ));
                        }
                        $tags[] = $tagModel['_id'];
                    }
                }
            }
        }

        $config=container()->get('config');
        if($config->mrs->pay_type&&$config->mrs->pay_type=='free'){
            $mrsData['money'] = -1;
        }else{
            $mrsData['money'] = 0;
        }

        $mid = $mrsData['id'];
        $novelModel = $this->findFirst(['_id' => $mid]);
        if (empty($novelModel)) {
            $novelSaveData = [
                '_id' => $mid,
                'cat_id' => $mrsData['category'],
                'tags' => $tags,
                'name' => $mrsData['name'],
                'alias_name' => strval($mrsData['alias_name']),
                'author'=>strval($mrsData['author']),
                'img' => $mrsData['img'],
                'img_x' => strval($mrsData['img_x']),
                'status' => value(function ()use($config){
                    if($config->mrs->auto&&$config->mrs->auto == 'y'){
                        return 1;
                    }
                    return 0;
                }),
                'update_status' => intval($mrsData['update_status']??$mrsData['status']),
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
                'money' => intval($mrsData['money'])?:0,
                'pay_type' => CommonValues::getPayTypeByMoney(intval($mrsData['money'])),
                'free_chapter' => '',
                'update_date' => strval($mrsData['update_date']),
                'chapter_count' => count($mrsData['chapter']),
                'is_adult' => $mrsData['is_adult'] * 1,
                'description' => $mrsData['description'] ?: '',
                'show_at' => mt_rand(time()-24*3600,time()),
                'last_update' => strtotime($mrsData['last_update'])
            ];
            $this->novelModel->insert($novelSaveData);
        } else {
            $novelSaveData = array(
                'name' => $mrsData['name'],
                'author' => strval($mrsData['author']),
                'img' => $mrsData['img'],
                'update_status' => intval($mrsData['update_status']??$mrsData['status']),
                'last_update' => strtotime($mrsData['last_update']),
                'is_adult' => $mrsData['is_adult'] * 1,
                'update_date' => $mrsData['update_date'],
                'chapter_count' => count($mrsData['chapter'])
            );
            $this->novelModel->update($novelSaveData, array('_id' => $mid));
        }
        foreach ($mrsData['chapter'] as $chapter) {
            $chapterId = $chapter['id'];
            $checkItem = $this->novelChapterModel->findFirst(array('_id' => $chapterId), array('_id', 'updated_at'));
            unset($chapter['id']);
            $chapter['updated_at'] = $chapter['updated_at']?strtotime($chapter['updated_at']):time();
            $chapter['sort'] = intval($chapter['sort']);
            if ($checkItem) {
                if ($checkItem['updated_at'] != $chapter['updated_at']) {
                    $this->novelChapterModel->update($chapter, array('_id' => $chapterId));
                }
                continue;
            }
            $chapter['created_at'] = $chapter['created_at']?strtotime($chapter['created_at']):time();
            $chapter['_id'] = $chapterId;
            $chapter['novel_id'] = $mid;
            $chapter['is_audio'] = $chapter['is_audio']*1;
            $this->novelChapterModel->insert($chapter);
        }
        return true;
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
        $payType = strval($filter['pay_type']);
        $catId = strval($filter['cat_id']);
        $tagId = strval($filter['tag_id']);
        $isHot = strval($filter['is_hot']);
        $isNew = strval($filter['is_new']);
        $isEnd = strval($filter['is_end']);
        $ids = strval($filter['ids']);
        $notIds = strval($filter['not_ids']);
        $order = $filter['order'] ?: '';
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
                    '_script' => [
                        "script" => 'Math.random()',
                        "type" => "number",
                        "order" => "asc"
                    ]
                ];
                break;
            case "update_date":
                $query['sort'] = array(
                    'last_update' => array('order' => 'desc'),
                );
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
                    'fields' => ['name', 'alias_name', 'tags.name']
                ));
            $query['min_score'] = '1.2';
            $this->novelKeywordsService->do($keyword);
        }
        if (!empty($catId)) {
            $catId = strtolower($catId);
            array_push($query['query']['bool']['must'], ['term' => ['cat_id' =>$catId]]);
            unset($catId);
        }
        if (!empty($tagId)) {
            array_push($query['query']['bool']['must'], ['terms' => ['tags.id' => explode(',', $tagId)]]);
            unset($tagId);
        }
        if (!empty($payType)) {
            array_push($query['query']['bool']['must'], ['term' => ['pay_type' => $payType]]);
            unset($payType);
        }
        if (!empty($isHot)) {
            array_push($query['query']['bool']['must'], ['term' => ['is_hot' => $isHot == 'y' ? 1 : 0]]);
            unset($isHot);
        }
        if(!empty($isEnd)){
            array_push($query['query']['bool']['must'], ['term' => ['update_status' => $isEnd == 'y' ? 1 : 0]]);
            unset($isHot);
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
        $result = $this->elasticService->search($query, 'novel', 'novel');

        //获取计数器
        $redisCounterKeys = [];
        foreach ($result->hits->hits as $item) {
            $id = $item->_source->id;
            $redisCounterKeys[] = "novel_click_".$id;
            $redisCounterKeys[] = "novel_favorite_".$id;
            $redisCounterKeys[] = "novel_comment_".$id;;
        }
        $counterMap = $this->commonService->getRedisCounters($redisCounterKeys);

        foreach ($result->hits->hits as $item) {
            $item = $item->_source;
            $item = [
                'id' => strval($item->id),
                'name' => strval($item->name),
                'alias_name' => strval($item->alias_name),
                'author' => strval($item->author),
                'type' => 'novel',
                'img' => $this->commonService->getCdnUrl($item->img),
                'description' => strval($item->description),
                'pay_type' => strval($item->pay_type),
                'money' => strval($item->money),
                'sub_title' => '',
                'category' => $item->cat_id ?? '',
                'category_name' => empty($item->cat_id)?'':CommonValues::getNovelCategories($item->cat_id),
                'click' => value(function () use ($item,$counterMap) {
                    $keyName = 'novel_click_' . $item->id;
                    $real = $counterMap[$keyName] ?? 0;
                    return strval(CommonUtil::formatNum(intval($item->click + $real)));
                }),
                'favorite' => value(function () use ($item,$counterMap) {
                    $keyName = 'novel_favorite_' . $item->id;
                    $real = $counterMap[$keyName] ?? 0;
                    return strval(CommonUtil::formatNum(intval($item->favorite + $real)));
                }),
                'comment' => value(function () use ($item,$counterMap) {
                    $keyName = 'novel_comment_' . $item->id;
                    $real = $counterMap[$keyName] ?? 0;
                    return strval(CommonUtil::formatNum(intval($real)));
                }),
                'ico' => value(function () use ($item) {
                    if($item->cat_id=='audio'){
                        return 'audio';
                    }
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
                'status' => strval($item->status * 1),
                'status_text' => strval(CommonValues::getComicsStatus($item->status * 1)),
                'update_status' => strval($item->update_status * 1),
                'update_date' => strval($item->update_date),
                'chapter_count' => strval($item->chapter_count * 1),
                'is_adult' => $item->is_adult == 1 ? 'y' : 'n',
                'free_chapter' => empty($item->free_chapter)?'':$item->free_chapter,
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
            if($item['update_status']==1){
                $item['sub_title'] ='共'.$item['chapter_count'].'话';
            }else{
                $item['sub_title'] ='更新'.$item['chapter_count'].'话';
            }
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
        if ($row['status'] != 1) {
            $this->elasticService->delete("novel", "novel", $id);
            return true;
        }
        $tagIds = $row['tags'];
        $row['tags'] = [];
        //只储存一级标签
        $tagArr = $this->novelTagService->getAll();
        foreach ($tagIds as $tagId) {
            $tagId = $tagId * 1;
            if ($tagArr[$tagId]) {
                $row['tags'][$tagId] = [
                    'id' => $tagId,
                    'name' => $tagArr[$tagId]['name']
                ];
            }
        }
        $row['tags'] = array_values($row['tags']);
        $row['id'] = $row['_id'];

        $realComment = $this->commentService->count(['object_id' => $id, 'object_type' => 'comics']);
        $updated = array(
            'async_at' => time(),
            'click_total' => $row['real_click'] + $row['click'],
            'comment' => intval($realComment)
        );
        if ($updated) {
            $this->novelModel->updateRaw(array('$set' => $updated), array('_id' => $id));
        }

        $row['last_update'] = intval($row['last_update']);
        $row['show_at'] = intval($row['show_at']);

        $this->commonService->setRedisCounter("novel_click_" . $row['id'], $row['real_click']);
        $this->commonService->setRedisCounter("novel_favorite_" . $row['id'], $row['real_favorite']);
        $this->commonService->setRedisCounter("novel_comment_" . $row['id'], $realComment);

        unset($row['_id']);
        return $this->elasticService->save($row['id'], $row, 'novel', 'novel');
    }


    /**
     * 事件处理
     * @param $data
     */
    public function handler($data)
    {
        $novelId = $data['novel_id'];
        switch ($data['action']) {
            case 'click':
                $this->commonService->updateRedisCounter("novel_click_{$novelId}", 1);
                $this->updateRaw(array('$inc' => array('real_click' => 1)), array('_id' => $novelId));
                break;
            case 'buy':
                $this->updateRaw(array('$inc' => array('buy' => 1)), array('_id' => $novelId));
                break;
            case 'favorite':
                $this->commonService->updateRedisCounter("novel_favorite_{$novelId}", 1);
                $this->updateRaw(array('$inc' => array('real_favorite' => 1)), array('_id' => $novelId));
                break;
            case 'unFavorite':
                $this->commonService->updateRedisCounter("novel_favorite_{$novelId}", -1);
                $this->updateRaw(array('$inc' => array('real_favorite' => -1)), array('_id' => $novelId));
                break;
        }
    }


    /**
     * 获取详情
     * @param $novelId
     * @return array|bool|mixed|null
     */
    public function getDetail($novelId)
    {
        $keyName = "novel_detail_{$novelId}";
        $result = getCache($keyName);
        if (empty($result)) {
            $result = $this->doSearch(null, array("ids" => $novelId));
            $result = empty($result['data']) ? false : $result['data'][0];
            setCache($keyName, $result, mt_rand(90, 120));
        }
        if (empty($result)) {
            return null;
        }
        return $result;
    }

    /**
     * 获取漫画章节
     * @param $novelId
     * @return array
     */
    public function getChapterList($novelId)
    {
        if (empty($novelId)) {
            return [];
        }
        $cacheKey = 'novel_chapter_list_' . $novelId;
        $result = getCache($cacheKey);
        if ($result === null) {
            $result = $this->novelChapterModel->find(
                array('novel_id' => $novelId),
                array('_id', 'name', 'is_audio'),
                array('sort' => -1, 'created_at' => 1),
                0,
                520
            );
            setCache($cacheKey, $result, mt_rand(180, 240));
        }
        return $result;
    }

    /**
     * 获取章节详情
     * @param $id
     * @return mixed
     */
    public function getChapterDetail($id)
    {
        $cacheKey = 'novel_chapter_detail_' . $id;
        $result = getCache($cacheKey);
        if ($result === null) {
            $result = $this->novelChapterModel->findByID($id);
            setCache($cacheKey, $result, mt_rand(180, 240));
        }
        return $result;
    }

    /**
     * 格式章节名称
     * @param $name
     * @return string
     */
    public function formatChapterName($name)
    {
        $name = strval($name);
        if (is_numeric($name)) {
            return '第' . $name . '话';
        }
        return $name;
    }

    /**
     * 是否购买
     * @param $chapterId
     * @param $userId
     * @return bool
     */
    public function hasBuyChapter($chapterId,$userId)
    {
        $orderId = md5($chapterId.'_'.$userId);
        $result =$this->novelChapterOrderModel->count(array('_id'=>$orderId));
        return $result>0?true:false;
    }

    /**
     * 记录购买  扣款在函数之前  用两张表管理 便于各种场景显示和查询
     * @param  $novelId
     * @param $chapterId
     * @param $userId
     * @param $orderSn
     * @param $money
     * @return bool
     */
    public function buyChapter($novelId,$chapterId,$userId,$orderSn,$money)
    {
        $orderId = md5($chapterId.'_'.$userId);
        $data = array(
            '_id' => $orderId,
            'novel_id'=>$novelId,
            'chapter_id'=>$chapterId,
            'user_id' => $userId*1,
            'money' => $money,
            'order_sn' =>$orderSn
        );
        $this->novelChapterOrderModel->insert($data);
        $orderId = md5($novelId.'_'.$userId);
        $count = $this->novelOrderModel->count(array('_id'=>$orderId));
        if($count<1){
            $data = array(
                '_id' => $orderId,
                'novel_id'=>$novelId,
                'user_id' => $userId*1
            );
            $this->novelOrderModel->insert($data);
        }
        return true;
    }

    /**
     * 购买列表
     * @param $userId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getBuyLogs($userId, $page = 1, $pageSize = 20)
    {
        $result = array();
        $skip = ($page - 1) * $pageSize;
        $items = $this->novelOrderModel->find(['user_id' => intval($userId)], [], ['created_at' => -1], $skip, $pageSize);
        foreach ($items as $item) {
            $result[$item['novel_id']] = array(
                'novel_id' => strval($item['novel_id']),
                'date_label' => dateFormat($item['created_at'], 'Y-m-d'),
                'updated_time' => strval($item['created_at'])
            );
        }
        return $result;
    }

}