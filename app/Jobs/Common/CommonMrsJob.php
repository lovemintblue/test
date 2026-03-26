<?php


namespace App\Jobs\Common;

use App\Jobs\BaseJob;
use App\Services\AccountService;
use App\Services\PostService;
use App\Services\UserService;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 * Class CommonMrsJob
 * @property PostService $postService
 * @property AccountService $accountService
 * @property UserService $userService
 * @package App\Jobs\Common
 */
class CommonMrsJob extends BaseJob
{
    protected function asyncByCatId($catId,$configs)
    {
        $query = array(
            'categories' => intval($catId),
            'status'=>3
        );
        $count =  $this->postService->count($query);
        $pageSize = 100;
        $totalPage = ceil($count/$pageSize);
        for ($page=1;$page<=$totalPage;$page++)
        {
            $skip = ($page-1)*$pageSize;
            $items = $this->postService->getList($query,array(),array(),$skip,$pageSize);
            foreach ($items as $item)
            {
                LogUtil::info(sprintf('Async post Id %s',$item['_id']));
                $data = array(
                    'id' => $item['_id'],
                    'title' => $item['title'],
                    'created_at' => $item['created_at'],
                    'media_url' => $configs['media_url'],
                    'fee' => $item['total_fee'],
                    'num' => $item['num_ai']*1
                );
                if($item['created_at']>time()-60*5){
                    LogUtil::info(sprintf('Need waiting post Id %s',$item['_id']));
                    continue;
                }
                if(in_array($configs['ai_quyi_cat_id'],$item['categories'])){
                    $data['images'] = $item['images'];
                    $data['type'] ='quyi';
                }elseif (in_array($configs['ai_huihua_cat_id'],$item['categories'])){
                    $data['images'] = $item['images'];
                    $data['num'] = $item['num_ai']*1;
                    $data['type'] ='huihua';
                }elseif (in_array($configs['ai_change_cat_id'],$item['categories'])){
                    if($item['type']=='image'){
                        $data['images'] = $item['images'];
                        $data['face_img']=explode(',',$item['ai_face_img']);
                        $data['type'] ='change_image';
                    }else{
                        $data['images'] = $item['images'];
                        $data['face_img'] = $item['ai_face_img'];
                        $data['video'] = $item['video_path'];
                        $data['type'] ='change_video';
                    }
                }
                $url = $configs['common_media_url'].'/cxapi/ai/async?is_json=1&key='.$configs['common_media_key'];
                $result = CommonUtil::httpJson($url,$data);
                $result = json_decode($result,true);
                if($result['status']=='y'){
                    $result = $result['data'];
                    if($result['status']==1){
                        $this->updatePost($result);
                    }elseif ($result['status']==-1){
                        $this->refundPost($result['id']);
                    }
                    LogUtil::info('Async ok:'.$result['id']);
                    continue;
                }
                LogUtil::error(sprintf('Post Id %s=>%s',$item['_id'],empty($result)?'接口异常=>'.$url:$result['error']));
            }
        }
    }

    /**
     * 处理失败退款
     * @param $postId
     * @return bool
     */
    protected function refundPost($postId)
    {
        $post = $this->postService->findByID($postId);
        if(empty($post) ||$post['status']!=3){
            return false;
        }
        $user = $this->userService->findByID($post['user_id']);
        if($post['total_fee']>0 && $user){
            $totalFee = $post['total_fee'];
            $orderSn=$post['order_sn'];
            $remark = sprintf("退款%s金币",$totalFee);
            $this->accountService->addBalance($user,$orderSn,$totalFee,3,$remark,json_encode(array('post_id'=>$postId,'type'=>$remark)));
            $this->userService->setInfoToCache($user['_id']);
        }
        $this->postService->updateRaw(array('$set'=>array('status'=>2)),array('_id'=>$postId));
        return true;
    }

    /**
     * 更新ai处理结果
     * @param $result
     * @return boolean
     */
    protected function updatePost($result)
    {
        $post = $this->postService->findByID($result['id']);
        if(empty($post) ||$post['status']!=3){
            return false;
        }
        $updatedData = array();
        if($result['type']=='quyi'){
            $images = $post['images'];
            foreach ($result['images'] as $image){
                $images[] = $image;
            }
            $updatedData['status'] =1;
            $updatedData['images'] = array_unique($images);
        }elseif ($result['type']=='huihua'){
            $updatedData['status'] =1;
            $updatedData['images'] = $result['images'];
        }elseif ($result['type']=='change_image'){
            $images = $post['images'];
            foreach ($result['images'] as $image){
                $images[] = $image;
            }
            $updatedData['status'] =1;
            $updatedData['images'] = array_unique($images);
        }elseif ($result['type']=='change_video'){
            $updatedData['status'] =1;
            $updatedData['video'] = $result['video'];
        }
        if($updatedData){
            $this->postService->updateRaw(array('$set'=>$updatedData),array('_id'=>$result['id']));
            $this->postService->asyncEs($result['id']);
            return true;
        }
        return false;
    }


    public function handler($uniqid)
    {
        $configs = getConfigs();
        $catIds= array(
            intval($configs['ai_quyi_cat_id']),
            intval($configs['ai_huihua_cat_id']),
            intval($configs['ai_change_cat_id'])
        );
        foreach ($catIds as $catId)
        {
            $this->asyncByCatId($catId,$configs);
        }

    }

    public function success($uniqid)
    {

    }

    public function error($uniqid)
    {

    }
}