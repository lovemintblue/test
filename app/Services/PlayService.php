<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKey;
use App\Core\Services\BaseService;
use App\Models\PlayModel;
use App\Utils\CommonUtil;

/**
 * 玩法管理
 * 
 * @package App\Services
 * @property ElasticService $elasticService
 * @property CommentService $commentService
 * @property CommonService $commonService
 * @property UserService $userService
 * @property  PlayModel $playModel
 */
class PlayService extends BaseService
{
    /**
     * 设备
     * @var array
     */
    public $deviceArr = [
        '安卓','IOS','PC'
    ];
    /**
     * 标签
     * @var array
     */
    public $tagArr = [
        'luoliao'=>[
            '学生',
            '主播',
            '福利姬',
        ],
        'yuepao'=>[
            'coser',
            '萝莉',
            '福利姬',
            '御姐',
            '学生',
            '网红',
        ]
    ];

    /**
     * 城市
     * @var array
     */
    public $cityArr = [
        'beijing'=>'北京',
        'shanghai'=>'上海',
        'guangzhou'=>'广州',
        'shenzhen'=>'深圳',
        'chengdu'=>'成都',
        'wuhan'=>'武汉',
        'hangzhou'=>'杭州',
        'xian'=>'西安',
        'chongqing'=>'重庆',
        'tianjin'=>'天津',
        'nanjing'=>'南京',
        'kunming'=>'昆明',
        'xiamen'=>'厦门',
        'shenyang'=>'沈阳',
        'changsha'=>'长沙',
        'suzhou'=>'苏州',
        'huizhou'=>'惠州',
        'zhengzhou'=>'郑州',
        'qingdao'=>'青岛',
        'dongguan'=>'东莞',
        'hefei'=>'合肥',
        'foshan'=>'佛山',
        'fuzhou'=>'福州',
        'wuxi'=>'无锡',
        'haerbin'=>'哈尔滨',
        'changchun'=>'长春',
        'nanchang'=>'南昌',
        'jinan'=>'济南',
        'ningbo'=>'宁波',
        'guiyang'=>'贵阳',
        'wenzhou'=>'温州',
        'shijiazhuang'=>'石家庄',
        'quanzhou'=>'泉州',
        'nanning'=>'南宁',
        'jinhua'=>'金华',
        'cahngzhou'=>'常州',
        'zhuhai'=>'珠海',
        'jiaxing'=>'嘉兴',
        'nantong'=>'南通',
        'zhongshan'=>'中山',
        'baoding'=>'保定',
        'lanzhou'=>'兰州',
        'taizhou'=>'台州',
        'xuzhou'=>'徐州',
        'taiyuan'=>'太原',
        'shaoxing'=>'绍兴',
        'yantai'=>'烟台',
        'langhaikou'=>'廊海口',
        'shantou'=>'汕头',
        'weifang'=>'潍坊',
        'yangzhou'=>'扬州',
        'luoyang'=>'洛阳',
        'wulumuqi'=>'乌鲁木齐',
        'linyi'=>'临沂',
        'tangshan'=>'唐山',
        'zhenjiang'=>'镇江',
        'yancheng'=>'盐城',
        'huzhou'=>'湖州',
        'ganzhou'=>'赣州',
        'zhangzhou'=>'漳州',
        'jieyang'=>'揭阳',
        'jiangmen'=>'江门',
        'guilin'=>'桂林',
        'handan'=>'邯郸',
        'ttaizhou'=>'泰州',
        'jining'=>'济宁',
        'huhehaote'=>'呼和浩特',
        'xianyang'=>'咸阳',
        'wuhu'=>'芜湖',
        'sanya'=>'三亚',
        'fuyang'=>'阜阳',
        'huaian'=>'淮安',
        'zunyi'=>'遵义',
        'yinchuan'=>'银川',
        'hengyang'=>'衡阳',
        'shangrao'=>'上饶',
        'liuzhou'=>'柳州',
        'zibo'=>'淄博',
        'putian'=>'莆田',
        'mianyang'=>'绵阳',
        'zhanjiang'=>'湛江',
        'shangqiu'=>'商丘',
        'yichang'=>'宜昌',
        'changzhou'=>'沧州',
        'lianyungang'=>'连云港',
        'nanyang'=>'南阳',
        'banghu'=>'蚌埠',
        'zhumadian'=>'驻马店',
        'chuzhou'=>'滁州',
        'xintai'=>'邢台',
        'chaozhou'=>'潮州',
        'qinhuangdao'=>'秦皇岛',
        'zhaoqin'=>'肇庆',
        'jinzhou'=>'荆州',
        'zhoukou'=>'周口',
        'maanshan'=>'马鞍山',
        'qingyuan'=>'清远',
        'szuzhou'=>'宿州',
        'weihai'=>'威海',
        'jiujiang'=>'九江',
        'xinxiang'=>'新乡',
        'xinyang'=>'信阳',
        'xiangyang'=>'襄阳',
        'yueyang'=>'岳阳',
        'anqin'=>'安庆',
        'heze'=>'菏泽',
        'yichun'=>'宜春',
        'huanggang'=>'黄冈',
        'taian'=>'泰安',
        'suqian'=>'宿迁',
        'zhuzhou'=>'株洲',
        'ningde'=>'宁德',
        'anshan'=>'鞍山',
        'luan'=>'六安',
        'daqin'=>'大庆',
        'zhoushanfang'=>'舟山坊',
    ];

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
        return $this->playModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->playModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->playModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->playModel->findByID(intval($id));
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
        return $this->playModel->updateRaw($document, $where);
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            $result = $this->playModel->update($data, array("_id" => $data['_id']));
            $id = $data['_id'];
        } else {
            $id = $result = $this->playModel->insert($data);

        }
        $this->asyncEs($id);
        delCache("play_detail_{$id}");
        return $result;
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $result = $this->playModel->delete(array('_id' => intval($id)));
        if($result){
            $this->elasticService->delete('play',"play", $id);
            delCache("play_detail_{$id}");
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
        $id =intval($id);
        $row=$this->findByID($id);
        if (empty($row)) {
            return false;
        }

        $row['id'] = $row['_id'];
        $row['params'] = $row['params']?implode("\n", $row['params']):'';

        $homeInfo = $this->userService->getInfoFromCache($row['user_id']);
        $row['nickname'] = strval($homeInfo['nickname']);
        $row['headico'] = strval($homeInfo['img']);
        $row['color']   = strval($homeInfo['color']);

//        $realComment = $this->commentService->sum($id,'play');

        $this->commonService->setRedisCounter("play_favorite_{$id}",$row['real_favorite']);

//        $this->commonService->setRedisCounter("play_comment_{$id}", $realComment);
//        $updated = array(
//            'comment'  => intval($realComment)
//        );
//        if ($updated) {
//            $this->playModel->updateRaw(array('$set' => $updated), array('_id' => $id));
//        }
        unset($row['_id']);
        return $this->elasticService->save($id, $row, 'play', 'play');
    }

    /**
     * 搜索
     * @param array $query
     * @return array|mixed
     */
    public function doSearch($query=[])
    {
        $page       = $query['page']?:1;
        $pageSize   = $query['page_size']?:24;
        $keyword    = $query['keywords'];
        $type       = $query['type']?:'yuepao';
        $tag        = $query['tag'];
        $city       = $query['city'];
        $ids        = $query['ids'];
        $notIds     = $query['not_ids'];
        $isRecommend = $query['is_recommend'];
        $order      = $query['order']?:'sort';
        $from = ($page - 1) * $pageSize;
        $source = array();
        $query = array(
            'from' => $from,
            'size' => $pageSize,
            'min_score' => 1.0,
            '_source' => $source,
            'query' => array()
        );

        $query['query']['bool']['must'][] = array(
            'term' => array('status' => 1),
        );
        $query['query']['bool']['must'][] = array(
            'term' => array('type' => $type)
        );
        switch ($order){
            case "sort":
                $query['sort'] = array(
                    'sort' => array('order' => 'desc'),
                    'created_at' => array('order' => 'desc'),
                );
                break;
            case 'new':
                $query['sort'] = array(
                    'created_at' => array('order' => 'desc'),
                );
                break;
            case "rand":
                $query['sort']=[
                    '_script'=>[
                        "script"=>'Math.random()',
                        "type"=>"number",
                        "order"=>"asc"
                    ]
                ];
                break;
        }
        //关键字
        if ($keyword) {
            $keyword = strtoupper($keyword);
            $query['query']['bool']['must'][] = array(
                'multi_match' => array(
                    'query' => $keyword,
                    "type" => "phrase",
                    'fields' => ['title', 'params']
                ));
            $query['min_score'] = '1.2';
        }
        if($isRecommend==1&&$page>1){
            return [
                'data'=>[]
            ];
        }
        if(!empty($tag)){
            array_push($query['query']['bool']['must'],['terms' => ['tag' => $tag]]);
            unset($tag);
        }
        if(!empty($city)){
            array_push($query['query']['bool']['must'],['term' => ['city' => $city]]);
            unset($city);
        }
        if(!empty($ids)){
            array_push($query['query']['bool']['must'],['terms' => ['id' => explode(',', $ids)]]);
            unset($ids);
        }
        if(!empty($notIds)){
            $query['query']['bool']['must_not'] = ['ids' => ['values' => explode(',', $notIds)]];
            unset($notIds);
        }

        $items = array();
        $result = $this->elasticService->search($query, 'play', 'play');
        foreach ($result->hits->hits as $item) {
            $item = $item->_source;
            $items[]=[
                'id'           => strval($item->id),
                'title'        => strval($item->title),
                'img_x'        => $this->commonService->getCdnUrl($item->img_x),
                'has_video'    => $item->video?'y':'n',
                'images_num'   => strval(count($item->images)),
                'description'  => strval($item->description),
                'price'        => strval($item->price),

                'favorite'  => value(function ()use($item){
                    $keyName = 'play_favorite_' . $item->id;
                    $real    = $this->commonService->getRedisCounter($keyName);
                    return strval(CommonUtil::formatNum(intval($item->favorite+$real)));
                })
            ];
        }
        $items = array_values($items);

        $result=[
            'data'=>$items,
            'total'=>value(function ()use($result){
                if(isset($result->hits->total->value)){
                    return strval($result->hits->total->value);
                }
                return $result->hits->total?strval($result->hits->total):'0';
            }),
            'current_page'=>(string)$page,
            'page_size'=>(string)$pageSize,
        ];
        $result['last_page']=(string)ceil($result['total']/$pageSize);
        return $result;
    }

    /**
     * 事件处理
     * @param $data
     */
    public function handler($data)
    {
        $playId=intval($data['play_id']);
        switch ($data['action']){
            case 'click':
//                $this->commonService->updateRedisCounter("play_click_{$playId}", 1);
                $this->updateRaw(array('$inc' => array('real_click' => 1)), array('_id' => $playId));
                break;
            case 'buy':
                $this->updateRaw(array('$inc' => array('buy' => 1)), array('_id' => $playId));
                break;
            case 'favorite':
                $this->commonService->updateRedisCounter("play_favorite_{$playId}", 1);
                $this->updateRaw(array('$inc' => array('real_favorite' => 1)), array('_id' => $playId));
                break;
            case 'unFavorite':
                $this->commonService->updateRedisCounter("play_favorite_{$playId}", -1);
                $this->updateRaw(array('$inc' => array('real_favorite' => -1)), array('_id' =>$playId));
                break;
        }
    }

}