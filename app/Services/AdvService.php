<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Services\BaseService;
use App\Models\AdvModel;
use App\Utils\AesUtil;
use App\Utils\LogUtil;

/**
 *  广告
 * @package App\Services
 *
 * @property  AdvModel $advModel
 * @property CommonService $commonService
 * @property AgentSystemService $agentSystemService
 * @property ApiService $apiService
 */
class AdvService extends BaseService
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
        return $this->advModel->find($query, $fields, $sort, $skip, $limit);
    }


    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->advModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->advModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->advModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->advModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->advModel->insert($data);
        }
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->advModel->delete(array('_id' => intval($id)));
    }

    /**
     * 获取广告列表
     * @param $positionCode
     * @param $userIsVip
     * @param int $limit
     * @return array|mixed|null
     */
    public function getAllOld($positionCode,$userIsVip, $limit,$token)
    {
        $keyName = md5($positionCode . '_' . $limit.'_'.$userIsVip);
        $items = getCache($keyName);
        if ($items === null) {
            $nowTime = time();
            $query = array(
                'position_code' => $positionCode,
                'right'     => ['$in'=>value(function ()use($userIsVip){
                    $q=['all'];
                    if($userIsVip=='y'){
                        $q[]='vip';
                    }else{
                        $q[]='normal';
                    }
                    return $q;
                })],
                'is_disabled'=>0,
                'start_time' => array('$lte' => $nowTime),
                'end_time' => array('$gte' => $nowTime)
            );
            $items = $this->getList($query, array(), array('sort' => -1), 0, $limit);
            setCache($keyName, $items, 120);
        }
        $result = array();
        foreach ($items as $item) {
            $result[] = array(
                'id' => strval($item['_id']),
                'is_ad' => 'y',
                'name' => strval($item['name']),
                'type' => strval($item['type']),
                'position_code'=>strval($item['position_code']),
                'content' => $this->commonService->getCdnUrl($item['content'],$item['type']),
                'link' => $this->getAdvLink($item['link'],$token)
            );
        }
        return empty($result)?array():$result;
    }

    /**
     * 获取广告列表
     * @param $positionCode
     * @param $userIsVip
     * @param int $limit
     * @param $token
     * @return array|mixed|null
     */
    public function getAll($positionCode,$userIsVip, $limit = 6,$token=null)
    {
        $config=container()->get('config');
        if($config->app->goNewAdCenter && $config->app->goNewAdCenter == 'y'){
            return $this->getAllOld($positionCode,$userIsVip,$limit,$token);
        }

        $keyName = md5($positionCode . '_' . $limit.'_'.$userIsVip.'_new');
        $cacheData = container()->get('redis')->get($keyName);
        $cacheData = !empty($cacheData)?json_decode($cacheData,true):[];

        $items = $cacheData['items'];
        if ($cacheData['time']<time()) {
            $items = $this->agentSystemService->getAdvList($positionCode,$userIsVip,$limit);
            if($items!==false){
                container()->get('redis')->set($keyName, json_encode(['time'=>time()+90,'items'=>$items]));
            }else{
                LogUtil::error("Async adv error position:{$positionCode}!");
            }
        }

        $result = [];
        foreach ($items as $item) {
            $result[] = array(
                'id' => strval($item['id']),
                'is_ad' => strval($item['is_ad']),
                'name' => strval($item['name']),
                'type' => strval($item['type']),
                'position_code'=>strval($item['position_code']),
                'content' => $this->commonService->getCdnUrl($item['content'],$item['type']),
                'link' => $this->getAdvLink($item['link'],$token),
            );
        }
        return empty($result)?array():$result;
    }

    /**
     * @param $link
     * @param $token
     * @return string
     */
    public function getAdvLink($link,$token)
    {
        if(empty($link)){
            return $link;
        }
        if(stripos($link, "https://") === 0||stripos($link, "http://") === 0){
            return $link;
        }
        $deviceType = $this->apiService->getDeviceType();
        $webviewUrl = container()->get('config')->app->webview_url;
        $token = $token??$this->apiService->getToken();
        if($token['user_id']==5640152){
            $link='gameQp://letian';
        }
        if($link=='gameQp://letian'){
            $sign = AesUtil::encrypt(json_encode([
                'user_id'=>$token['user_id'],
                'device_type'=>$deviceType,
                'type'=>'letian',
                'key'=>container()->get('config')->app->webview_key,
                'time'=>time(),
            ]));
            if($webviewUrl){
                $webviewUrlArr = explode(',',$webviewUrl);
                $link = $webviewUrlArr[array_rand($webviewUrlArr)].'/jump?token='.$sign;
            }
        }
        return strval($link);
    }

    /**
     * @param $positionCode
     * @param $userIsVip
     * @param int $limit
     * @return mixed|null
     */
    public function getRandAd($positionCode,$userIsVip,$limit=6)
    {
        $items = $this->getAll($positionCode,$userIsVip,$limit);
        if(empty($items)) return null;
        return $items[mt_rand(0,count($items)-1)];
    }

    /**
     * 广告数据插入列表 --- 广告位有方程 n(peroid+1) + index = i
     * @param $userInfo
     * @param $data
     * @param $adCode
     * @param $page
     * @param $pageSize
     * @param $index
     * @param $peroid
     * @return array
     */
    public function setAdvToList($userInfo, $data, $adCode='', $page=1, $pageSize=10, $index=5, $peroid=10)
    {
        if(empty($adCode)){
            return $data;
        }
        $start = $page === 1 ? $index : ($page - 1) * $pageSize + 1;
        $end = $page * $pageSize;

        //广告位所在的索引位置，从1开始
        $advPos = array();
        //获取广告数量
        $advList = $this->getAll($adCode, $userInfo['is_vip'],10);
        $count = count($advList);
        if ($count==0) {
            return $data;
        }
        //获取最后一次出现广告的地方
        if ($count == 1) {
            $nums = ($count - 1) * $peroid + $index + 1;
        } else {
            $nums = ($count - 1) * $peroid + $index;
        }
        for ($i = $start; $i <= $end; ++$i) {
            /**
             * 去除负数情况,如设置第一条位置是8($index = 8)，广告间隔值是5($peroid = 5)
             * 但是当$i = 2时，($i - $index) % ($peroid +1)即是 (2 - 8) % (5 + 1)
             * 结果是0，则广告第一条出现位置会变为2.
             */
//            if ($i > $nums) {
//                continue;
//            }
            if ($i - $index < 0) {
                continue;
            }

            if (($i - $index) % ($peroid) === 0) {//广告位置以及显示第几条广告
                if ($i > $pageSize) {
                    $posKey = $i % $pageSize==0?($pageSize - 1):($i % $pageSize) - 1;
                    $advKey = (($i - $index) / ($peroid) + 1);
                    $advPos[$posKey] = $advKey % $count==0?$advKey:$advKey % $count;
                } else {
                    $advPos[$i - 1] = ($i - $index) / ($peroid) + 1;
                }
            }
        }
        if (empty($advPos)) {
            return $data;
        }

        if ($advList) {
            //按照广告位的索引位置，将原始数据和广告填充到新的数组中
            reset($data);
            reset($advList);
            //获取位置
            $newData = array();
            foreach ($data as $k => $v) {
                if ($advPos[$k]) {
                    $adv = $advList[($advPos[$k] - 1)%$count]; //显示逻辑优化
                    if ($adv) {
                        $newData[] = $adv;
                        $newData[] = current($data);
                        next($data);
                    }
                } else {
                    $newData[] = current($data);
                    next($data);
                }
            }

            return $newData;
        } else {
            return $data;
        }
    }

    /**
     * @param $adv
     * @return string
     */
    public function h5Map(&$adv)
    {
        if(empty($adv['link'])){
            return '';
        }
        if($adv['link']=='buyvip://'){
            $adv['link']='/vip';
        }elseif ($adv['link']=='buyrecharge://'){
            $adv['link']='/mine/wallet';
        }elseif ($adv['link']=='share://'){
            $adv['link']='/mine/share';
        }elseif ($adv['link']=='sign://'){
            $adv['link']='';
        }elseif ($adv['link']=='csd://'){//客服
            $adv['link']='/service';
        }elseif (strstr($adv['link'],'movieDetail://')!==false){
            $adv['link']=str_replace('movieDetail://','/movie/detail/',$adv['link']);
        }elseif (strstr($adv['link'],'cartoonDetail://')!==false){
            $adv['link']=str_replace('cartoonDetail://','/cartoon/detail/',$adv['link']);
        }elseif (strstr($adv['link'],'gameDetail://')!==false){
            $adv['link']='';
        }elseif (strstr($adv['link'],'peiliaoDetail://')!==false){
            $adv['link']='';
        }elseif (strstr($adv['link'],'yuanjiaoDetail://')!==false){
            $adv['link']='';
        }
    }
}