<?php

namespace App\Services;

use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 * 广告中心
 */
class AdvCenterService
{
    /**
     * 接口域名
     * @var string
     */
    private $pullUrl;
    /**
     * 推送域名
     * @var string
     */
    private $pushUrl;
    /**
     * 商户CODE
     * @var string
     */
    private $merid;

    /**
     * 部门CODE
     * @var string
     */
    private $deptid;

    /**
     * 应用CODE
     * @var string
     */
    private $appid;

    /**
     *
     * @var string
     */
    private $appkey;

    public function __construct(string $pullUrl,string $pushUrl,string $merid,string $deptid,string $appid, string $appkey)
    {
        $this->pullUrl = rtrim($pullUrl,'/');
        $this->pushUrl = rtrim($pushUrl,'/');
        $this->merid  = $merid;
        $this->deptid = $deptid;
        $this->appid  = $appid;
        $this->appkey = $appkey;
    }


    /**
     * 获取所有
     * @param bool $hasInner 由于广告系统有inner://前缀,而部分项目没有该前缀,通过此参数控制是否需要返回前缀, true 返回,false 不返回
     * @return array[]
     * @throws \Exception
     */
    public function getAll(bool $hasInner=false)
    {
        $data = [
            "merchantCode" => $this->merid,
            "appCode" =>  $this->appid
        ];
        $result = $this->doHttpRequest($this->pullUrl.'/openapi/getAdvertiseList',$data);
        $adv=[];
        $advPos=[];
        $advApp=[];
        foreach ($result as $adGroup) {
            if(strpos($adGroup['advertiseLocationCode'],'advapp_')===0){
                //应用中心
                foreach ($adGroup['adDetailInfoList'] as $adItem) {
                    //过滤过期应用
//                    if(time()<($adItem['startTimeStamp']/1000)||time()>($adItem['endTimeStamp']/1000)){
//                        continue;
//                    }
                    $advApp[]=[
                        'cid'           =>strval($adItem['advertiseCode']),
                        'name'          =>strval($adItem['advertiseName']),
                        'position'      =>strval(str_replace('advapp_','',$adGroup['advertiseLocationCode'])),
                        'image'         =>strval($adItem['advertiseIcon']),
                        'download_url'  =>value(function ()use($adItem,$hasInner){
                            $link = strval($adItem['advertiseUrl']);
                            //去掉前缀
                            if($hasInner==false){
                                $link=str_replace('inner://','',$link);
                            }
                            return $link;
                        }),
                        'download'      =>strval('0'),
                        'sort'          =>strval($adItem['sort']),
                        'is_disabled'   =>value(function ()use($adItem){
                            //转换为业务字段
                            //状态 0 下架 1 上架 2 未开始 3 已过期
                            //is_disabled:1关闭 0开启
                            if($adItem['status']==1){
                                return '0';
                            }
                            return '1';
                        }),
                        'is_hot'        =>value(function ()use($adItem){
                            if(empty($adItem['adExtData'])){
                                return '0';
                            }
                            $data = json_decode($adItem['adExtData'],true);
                            return strval($data['is_hot']);
                        }),
                        'is_self'       =>value(function ()use($adItem){
                            if(empty($adItem['adExtData'])){
                                return '0';
                            }
                            $data = json_decode($adItem['adExtData'],true);
                            return strval($data['is_self']);
                        })
                    ];
                }

            }else{
                //常规广告
                $advPos[]=[
                    'cid'           =>strval($adGroup['advertiseLocationCode']),
                    'name'          =>strval($adGroup['advertiseLocationName']),
                    'code'          =>strval($adGroup['advertiseLocationCode']),
                    'is_disabled'   =>value(function ()use($adGroup){
                        //转换为业务字段
                        //status:1开启 0关闭
                        //is_disabled:1关闭 0开启
                        if($adGroup['status']==1){
                            return '0';
                        }
                        return '1';
                    }),
                    'width'         =>strval($adGroup['advertiseWidth']),
                    'height'        =>strval($adGroup['advertiseHeight']),
                ];
                foreach ($adGroup['adDetailInfoList'] as $adItem) {
                    $adv[]=[
                        'cid'           =>strval($adItem['advertiseCode']),
                        'name'          =>strval($adItem['advertiseName']),
                        'description'   =>strval($adItem['advertiseDesc']),
                        'position_code' =>strval($adGroup['advertiseLocationCode']),
                        'type'          =>'image',
                        'right'         =>'all',
                        'content'       =>strval($adItem['advertiseIcon']),
                        'start_time'    =>strval($adItem['startTimeStamp']/1000),
                        'end_time'      =>strval($adItem['endTimeStamp']/1000),
                        'show_time'     =>'0',
                        'sort'          =>strval($adItem['sort']),
                        'is_disabled'   =>value(function ()use($adItem){
                            //转换为业务字段
                            //状态 0 下架 1 上架 2 未开始 3 已过期
                            //is_disabled:1关闭 0开启
                            if($adItem['status']==1){
                                return '0';
                            }
                            return '1';
                        }),
                        'link'          =>$this->linkDecode($adItem['advertiseUrl'],$hasInner)
                    ];
                }
            }
        }
        LogUtil::info(sprintf(__CLASS__ . " Sync adv_pos:%s条 adv:%s条 adv_app:%s条", count($advPos),count($adv),count($advApp)));

        return [
            'adv'=>$adv,
            'adv_app'=>$advApp,
            'adv_pos'=>$advPos,
        ];
    }

    /**
     * 推送广告
     * 适用于项目首次接入广告中心,全量推送自己系统的广告
     * @param array $advArr 所有广告数据
     * @param array $advPosArr 所有广告位数据
     * @return void
     * @throws \Exception
     */
    public function pushAdv(array $advArr=[],array $advPosArr=[])
    {
        $advPosArr = array_column($advPosArr,null,'code');
        $data= [];
        foreach ($advArr as $adv) {
            //字段校验
            if(empty($adv['name'])||empty($adv['link'])||empty($adv['_id'])||empty($adv['content'])||empty($adv['start_time'])||empty($adv['end_time'])||empty($adv['position_code'])){
                throw new \Exception("广告数据格式错误,缺少必填字段 _id:{$adv['_id']}");
            }

            //跳过没有广告位的广告
            if (!isset($advPosArr[$adv['position_code']])) {
                continue;
            }
            $data[]=[
                'merchantCode'=>$this->merid,
                'appCode'=>$this->appid,
                'customerCode'=>'',
                'deptCode'=>$this->deptid,
                'advertiseName'=>strval($adv['name']),
                'advertiseUrl'=>$this->linkEncode($adv['link']),
                'advertiseCode'=>strval($adv['_id']),
                'advertiseIcon'=>strval($adv['content']),
                'pcAdvertiseIcon'=>'',
                'advertiseDesc'=>strval($adv['description']),
                'advertiseType'=>3,//广告类型1:播放器、2:药台、3:炮台、4:黄游、5:直播、6:BC
                'startTime'=>date('Y-m-d H:i:s',$adv['start_time']),
                'endTime'=>date('Y-m-d H:i:s',$adv['end_time']),
                'sort'=>intval($adv['sort']),
                'advertiseLocationCode'=>strval($adv['position_code']),
                'advertiseLocationRemark'=>'',
                'advertiseLocationName'=>strval($advPosArr[$adv['position_code']]['name']),
                'status'=>1,
                'adMode'=>"",
                'advertiseHeight'=>'',
                'advertiseWidth'=>'',
                'materialCoverRatio'=>'',
                'adExtData'=>'',
                'adExtRaw'=>'',
                'adExtReserve'=>'',
            ];
        }
        $this->doHttpRequest($this->pushUrl.'/api/advertise/sync/syncAdData',$data);
    }

    /**
     * 推送应用广告
     * 适用于项目首次接入广告中心,全量推送自己系统的广告
     * @param array $advAppArr
     * @return void
     * @throws \Exception
     */
    public function pushAdvApp(array $advAppArr=[])
    {
        $data= [];
        foreach ($advAppArr as $adv) {
            $adv['position']='advapp_'.$adv['position'];

            $data[]=[
                'merchantCode'=>$this->merid,
                'appCode'=>$this->appid,
                'customerCode'=>'',
                'deptCode'=>$this->deptid,
                'advertiseName'=>strval($adv['name']),
                'advertiseUrl'=>$this->linkEncode($adv['download_url']),
                'advertiseCode'=>strval($adv['_id']),
                'advertiseIcon'=>strval($adv['image']),
                'pcAdvertiseIcon'=>'',
                'advertiseDesc'=>$adv['description'],
                'advertiseType'=>3,//广告类型1:播放器、2:药台、3:炮台、4:黄游、5:直播、6:BC
                'startTime'=>date('Y-m-d H:i:s',$adv['created_at']),
                'endTime'=>'2035-12-31 23:59:59',
                'sort'=>intval($adv['sort']),
                'advertiseLocationCode'=>strval($adv['position']),
                'advertiseLocationRemark'=>'',
                'advertiseLocationName'=>strval($adv['position']),
                'status'=>1,
                'adMode'=>"",
                'advertiseHeight'=>'',
                'advertiseWidth'=>'',
                'materialCoverRatio'=>'1:1',
                'adExtData'=>value(function ()use($adv){
                    $adExtData=[];
                    if(!isset($adv['is_self'])){
                        $adExtData['is_self'] = $adv['is_self'];
                    }
                    if(!isset($adv['is_hot'])){
                        $adExtData['is_hot'] = $adv['is_hot'];
                    }
                    return json_encode($adExtData);
                }),
                'adExtRaw'=>'',
                'adExtReserve'=>'',
            ];
        }
        $this->doHttpRequest($this->pushUrl.'/api/advertise/sync/syncAdData',$data);
    }

    /**
     * 上报广告位
     * 适用于新项目上线前同步广告位至广告中心
     * @param array $advPosArr
     * @return array|null
     * @throws \Exception
     */
    public function pushAdvPos(array $advPosArr=[])
    {
        $data = [];
        foreach ($advPosArr as $item) {
            $data[]=[
                'merchantCode'  =>$this->merid,
                'appCode'  =>$this->appid,
                'deptCode'  =>$this->deptid,
                'advertiseLocationCode'=>strval($item['code']),
                'advertiseLocationRemark'=>'',
                'advertiseLocationName'=>strval($item['name']),
                'advertiseHeight'=>intval($item['height']),
                'advertiseWidth'=>intval($item['width']),
                'materialCoverRatio'=>"",
            ];
        }
        return $this->doHttpRequest($this->pushUrl.'/api/advertise/sync/syncAdLocationData',$data);
    }

    /**
     * 链接转换
     * @param string $link
     * @return string
     */
    public function linkEncode(string $link)
    {
        $link = trim($link ?? '');

        // 1. inner://http://xx.com 或 inner://https://xx.com 暂时不做处理，直接返回
        if (strpos($link, 'inner://http://') === 0 || strpos($link, 'inner://https://') === 0) {
            return $link;
        }

        // 2. 外部跳转 http / https，直接返回
        if (stripos($link, 'http://') === 0 || stripos($link, 'https://') === 0) {
            return $link;
        }

        // ===== 下面是内部跳转处理 =====

        // 3. 去掉 inner://
        if (strpos($link, 'inner://') === 0) {
            $link = substr($link, strlen('inner://'));
        }

        // 4. 用 parse_url 解析
        $parts = parse_url($link);

        if (!empty($parts['scheme'])) {

            $scheme = $parts['scheme'];          // aa
            $host   = $parts['host'] ?? '';      // sdfk
            $path   = $parts['path'] ?? '';      // /sd
            $query  = $parts['query'] ?? '';     // a=1
            $frag   = $parts['fragment'] ?? '';  // xxxxx

            // 组装后半部分
            $rest = '';

            if ($host !== '') {
                $rest .= '/' . $host;
            }

            if ($path !== '') {
                $rest .= $path;
            }

            // 去最前面的 /
            $rest = ltrim($rest, '/');

            // 拼回 query
            if ($query !== '') {
                $rest .= '?' . $query;
            }

            // 拼回 fragment
            if ($frag !== '') {
                $rest .= '#' . $frag;
            }

            // xhp/xh/rech?a=1
            if ($rest !== '') {
                $link = $scheme . '/' . $rest;
            } else {
                $link = $scheme;
            }
        }

        // 5. 添加 inner:// 前缀
        return 'inner://' . $link;
    }


    /**
     * 链接转换
     * @param string $link
     * @param bool $hasInner
     * @return string
     */
    public function linkDecode(string $link,bool $hasInner=false)
    {
        $link = trim($link);
        if ($link === '') {
            return '';
        }

        // 1. 纯 http/https，外部跳转，保持原样
        if (stripos($link, 'http://') === 0 || stripos($link, 'https://') === 0) {
            return $link;
        }

        // 2. inner://http://xx.com 或 inner://https://xx.com，保持原样
        if (strpos($link, 'inner://http://') === 0 || strpos($link, 'inner://https://') === 0) {
            return $link;
        }

        // 3. 不是 inner:// 开头的，说明不是我们转换过来的协议，保持原样
        if (strpos($link, 'inner://') !== 0) {
            return $link;
        }

        // 4. 如果项目本身就是基于 inner:// 的，直接返回，不做还原
        if ($hasInner) {
            return $link;
        }

        // 5. 需要还原：去掉 inner:// 前缀
        $raw = substr($link, strlen('inner://'));  // 比如 xhp/xh/rech 或 aiGirlFriend/18ai

        // 按第一个 / 分成两部分： scheme + 剩下
        $pos = strpos($raw, '/');

        if ($pos === false) {
            // inner://xhp → xhp://
            return $raw . '://';
        }

        $scheme = substr($raw, 0, $pos);  // xhp
        $rest   = substr($raw, $pos + 1); // xh/rech 或 18ai 等

        return $scheme . '://' . $rest;
    }

    /**
     * @param $uri
     * @param $query
     * @return array|null
     */
    private function doHttpRequest($uri,$query=[]){
        $requestUrl = $uri;
        $requestData = json_encode($query,JSON_UNESCAPED_UNICODE);
        LogUtil::info(sprintf(__CLASS__ . " Request url: %s query:%s", $requestUrl,$requestData));
        if(empty($requestUrl)){
            throw new \Exception('请求URL为空');
        }
        $result = CommonUtil::httpPost($requestUrl,$requestData,5,[
            'Content-Type: application/json',
            'Content-Length: ' . strlen($requestData)
        ]);
        if(empty($result)){
            throw new \Exception("请求错误");
        }
        $result = json_decode($result, true);
        if ($result["code"] != 0 || empty($result["data"])){
            throw new \Exception($result['msg']??"数据为空");
        }
        $result = $this->aesGcmDecrypt($result["data"], $this->appkey);
        if (empty($result)){
            throw new \Exception("数据解码失败");
        }
        return json_decode($result, true);
    }


    /**
     * AES aes-256-gcm解密
     */
    private function aesGcmDecrypt($data, $key)
    {
        // 1. Base64 解码
        $data = base64_decode($data);
        // 2. 拆分 IV(12) + CipherText+Tag
        $iv = substr($data, 0, 12);
        $ct_with_tag = substr($data, 12);
        // CipherText 和 Tag（最后 16 字节）
        $tag = substr($ct_with_tag, -16);
        $ciphertext = substr($ct_with_tag, 0, -16);
        // 3. 密钥 Base64 解码（必须 32 字节）
        $key = base64_decode($key);
        // 4. 使用 GCM 解密
        $plain = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        return $plain;
    }
}