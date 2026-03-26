<?php

namespace App\Jobs\Center;

use App\Jobs\BaseJob;
use App\Models\AdvAppModel;
use App\Models\AdvModel;
use App\Models\AdvPosModel;
use App\Services\AdvCenterService;
use App\Services\ConfigService;
use App\Utils\LogUtil;

/**
 * 广告中心
 * @property AdvModel $advModel
 * @property AdvAppModel $advAppModel
 * @property AdvPosModel $advPosModel
 */
class CenterAdvJob extends CenterBaseJob
{

    /**
     * @var AdvCenterService
     */
    public $advCenterService;
    public $action;

    public function __construct($action)
    {
        $this->action = $action;
        $configs = $this->getCenterConfig('adv');
        $this->advCenterService =new AdvCenterService(
            $configs['pull_url'],
            $configs['push_url'],
            $configs['merid'],
            $configs['deptid'],
            $configs['appid'],
            $configs['appkey']
        );
    }

    public function handler($uniqid)
    {
        switch ($this->action) {
            case 'sync':
                $this->sync();
                break;
            /*case 'reportAdv':
                $this->reportAdv();
                break;
            case 'reportAdvApp':
                $this->reportAdvApp();
                break;
            case 'reportAdvPos':
                $this->reportAdvPos();
                break;*/
        }
    }

    /**
     * 同步广告
     * @return void
     * @throws \Exception
     */
    public function sync()
    {
        //TODO 如果项目有inner://这种协议,需要改成true
        $result = $this->advCenterService->getAll(false);
        $advArr = $result['adv'];
        $advAppArr = $result['adv_app'];
        $advPosArr = $result['adv_pos'];

        //cid=中心id
        /**====================清空历史广告start=======================**/
        if(count($advPosArr)>0){
            ///处理广告中心修改广告位问题
            ///因为业务侧分adv和adv_app,而广告中心是没区分的,如果adv填到adv_app,再改回,则业务端没删除adv_app中数据
            $cidArr=array_column($advPosArr,'cid');
            $this->advPosModel->delete(['code'=>['$nin'=>$cidArr]]);
        }

        if(count($advArr)>0){
            ///处理广告中心修改广告位问题
            ///因为业务侧分adv和adv_app,而广告中心是没区分的,如果adv填到adv_app,再改回,则业务端没删除adv_app中数据
            $cidArr=array_column($advArr,'cid');
            $this->advModel->delete(['_id'=>['$nin'=>$cidArr]]);
        }

        if(count($advAppArr)>0){
            ///处理广告中心修改广告位问题
            ///因为业务侧分adv和adv_app,而广告中心是没区分的,如果adv填到adv_app,再改回,则业务端没删除adv_app中数据
            $cidArr=array_column($advAppArr,'cid');
            $this->advAppModel->delete(['_id'=>['$nin'=>$cidArr]]);
        }
        /**====================清空历史广告end=======================**/

        foreach ($advPosArr as $item) {
            $hasRow =  $this->advPosModel->findFirst(['code'=>$item['cid']]);
            if(empty($hasRow)){
                $this->advPosModel->insert([
                    'code'=>strval($item['cid']),
                    'name'=>strval($item['name']),
                    'is_disabled'=>intval($item['is_disabled']),
                    'width'=>intval($item['width']),
                    'height'=>intval($item['height']),
                ]);
            }else{
                $update=[];
                //是否需要更新
                if($hasRow['name']!=$item['name']){
                    $update['name']=strval($item['name']);
                }
                if($hasRow['is_disabled']!=$item['is_disabled']){
                    $update['is_disabled']=intval($item['is_disabled']);
                }
                if($hasRow['width']!=$item['width']){
                    $update['width']=intval($item['width']);
                }
                if($hasRow['height']!=$item['height']){
                    $update['height']=intval($item['height']);
                }
                if(!empty($update)){
                    $this->advPosModel->update($update,['_id'=>$hasRow['_id']]);
                }
            }

        }

        foreach ($advArr as $item) {
            $hasRow = $this->advModel->findFirst(['_id'=>$item['cid']]);
            if(empty($hasRow)){
                $this->advModel->insert([
                    '_id'=>strval($item['cid']),
                    'name'=>strval($item['name']),
                    'description'=>strval($item['description']),
                    'position_code'=>strval($item['position_code']),
                    'type'=>strval($item['type']),
                    'right'=>strval($item['right']),
                    'channel_code'=>'',
                    'content'=>strval($item['content']),
                    'start_time'=>intval($item['start_time']),
                    'end_time'=>intval($item['end_time']),
                    'show_time'=>intval($item['show_time']),
                    'sort'=>intval($item['sort']),
                    'click'=>intval(0),
                    'link'=>strval($item['link']),
                    'is_disabled'=>intval($item['is_disabled']),
                ]);
            }else{
                $update=[];
                //是否需要更新
                if($hasRow['name']!=$item['name']){
                    $update['name']=strval($item['name']);
                }
                if($hasRow['description']!=$item['description']){
                    $update['description']=strval($item['description']);
                }
                if($hasRow['position_code']!=$item['position_code']){
                    $update['position_code']=strval($item['position_code']);
                }
                if($hasRow['type']!=$item['type']){
                    $update['type']=strval($item['type']);
                }
                if($hasRow['content']!=$item['content']){
                    $update['content']=strval($item['content']);
                }
                if($hasRow['start_time']!=$item['start_time']){
                    $update['start_time']=intval($item['start_time']);
                }
                if($hasRow['end_time']!=$item['end_time']){
                    $update['end_time']=intval($item['end_time']);
                }
                if($hasRow['show_time']!=$item['show_time']){
                    $update['show_time']=intval($item['show_time']);
                }
                if($hasRow['sort']!=$item['sort']){
                    $update['sort']=intval($item['sort']);
                }
                if($hasRow['link']!=$item['link']){
                    $update['link']=strval($item['link']);
                }
                if($hasRow['is_disabled']!=$item['is_disabled']){
                    $update['is_disabled']=intval($item['is_disabled']);
                }
                if(!empty($update)){
                    $this->advModel->update($update,['_id'=>$hasRow['_id']]);
                }
            }
        }

        foreach ($advAppArr as $item) {
            $hasRow = $this->advAppModel->findFirst(['_id'=>$item['cid']]);
            if(empty($hasRow)){
                $this->advAppModel->insert([
                    '_id'=>strval($item['cid']),
                    'name'=>strval($item['name']),
                    'position'=>strval($item['position']),
                    'image'=>strval($item['image']),
                    'download_url'=>strval($item['download_url']),
                    'download'=>$item['download']? strval($item['download']):strval(rand(200000, 1000000)),
                    'description'=>strval($item['description']),
                    'sort'=>intval($item['sort']),
                    'is_hot'=>intval($item['is_hot']),
//                    'is_self'=>intval($item['is_self']),//需要就打开
                    'is_disabled'=>intval($item['is_disabled']),
                ]);
            }else{
                $update=[];
                //是否需要更新
                if($hasRow['name']!=$item['name']){
                    $update['name']=$item['name'];
                }

                if(is_array($hasRow['position'])){
                    if(in_array($item['position'],$hasRow['position'])==false){
                        $update['position']=[strval($item['position'])];
                    }
                }else{
                    if($item['position']!=$hasRow['position']){
                        $update['position']=strval($item['position']);
                    }
                }

                if($hasRow['image']!=$item['image']){
                    $update['image']=strval($item['image']);
                }
                if($hasRow['download_url']!=$item['download_url']){
                    $update['download_url']=strval($item['download_url']);
                }
                if($hasRow['description']!=$item['description']){
                    $update['description']=strval($item['description']);
                }
                if($hasRow['sort']!=$item['sort']){
                    $update['sort']=intval($item['sort']);
                }
                if($hasRow['is_hot']!=$item['is_hot']){
                    $update['is_hot']=intval($item['is_hot']);
                }
                if($hasRow['is_disabled']!=$item['is_disabled']){
                    $update['is_disabled']=intval($item['is_disabled']);
                }
                if(!empty($update)){
                    $this->advAppModel->update($update,['_id'=>$hasRow['_id']]);
                }
            }
        }

    }

    /**
     * 上报广告
     * @return void
     * @throws \Exception
     */
    public function reportAdv()
    {
        $advPosArr = $this->advPosModel->find([],[],[],0,1000);
        $advArr = $this->advModel->find([],[],[],0,1000);
        $this->advCenterService->pushAdv($advArr,$advPosArr);
    }

    /**
     * 上报应用广告
     * @return void
     * @throws \Exception
     */
    public function reportAdvApp()
    {
        $advArr = $this->advAppModel->find([],[],[],0,1000);
        $this->advCenterService->pushAdvApp($advArr);
    }

    /**
     * 上报广告位
     * @return void
     * @throws \Exception
     */
    public function reportAdvPos()
    {
        $advPosArr = $this->advPosModel->find([],[],[],0,1000);
        $this->advCenterService->pushAdvPos($advPosArr);
    }



    public function success($uniqid)
    {

    }

    public function error($uniqid)
    {

    }
}
