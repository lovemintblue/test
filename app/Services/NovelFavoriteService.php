<?php


namespace App\Services;


use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Models\ComicsFavoriteModel;
use App\Models\MovieFavoriteModel;
use App\Models\NovelFavoriteModel;
use App\Models\UserFollowModel;

/**
 * 漫画收藏
 * Class NovelFavoriteService
 * @property NovelFavoriteModel $novelFavoriteModel
 * @property NovelService $novelService
 * @property CommonService $commonService
 * @property UserService $userService
 * @property QueueService $queueService
 * @package App\Services
 */
class NovelFavoriteService extends BaseService
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
        return $this->novelFavoriteModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->novelFavoriteModel->count($query);
    }


    /**
     * 通过id查询
     * @param  $id
     * @param  $fields
     * @return mixed
     */
    public function findByID($id, $fields = array())
    {
        return $this->novelFavoriteModel->findByID($id, '_id', $fields);
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            $this->novelFavoriteModel->update($data, array("_id" => $data['_id']));
            $cartoonId = $data['_id'];
        } else {
            $cartoonId = $this->novelFavoriteModel->insert($data);
        }
        return $cartoonId;
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->novelFavoriteModel->delete(['_id'=>$id]);
    }

    /**
     * 收藏
     * @param $userId
     * @param $novelId
     * @return bool
     * @throws BusinessException
     */
    public function do($userId,$novelId)
    {
        if (!$this->novelService->findFirst(['_id'=>$novelId],['_id'])) {
            throw new BusinessException(StatusCode::DATA_ERROR, '小说不存在!');
        }
        $redis = $this->getRedis();
        $key = 'novel_fav_'.$userId;
        $favoriteId = md5($userId.'_'.$novelId);
        if ($this->has($userId,$novelId)) {
            $this->delete($favoriteId);
            $redis->srem($key,$novelId);//set删除
            $this->novelService->handler(['action' => 'unFavorite','novel_id'=>$novelId]);
            return false;
        }

        $this->novelFavoriteModel->insert([
            '_id' => $favoriteId,
            'novel_id' => $novelId,
            'user_id' => intval($userId)
        ]);
        $redis->sAdd($key,$novelId);
        $redis->expire($key, 604800);
        $this->novelService->handler(['action' => 'favorite','novel_id'=>$novelId]);
        return true;
    }

    /**
     * 是否收藏
     * @param $userId
     * @param $novelId
     * @return bool
     */
    public function has($userId,$novelId)
    {
        //优化为不用每次去大表查1条数据，接近5kw数据，即便查主键也很慢
        $redis = $this->getRedis();
        //第一次访问：如果 key 不存在 → 从 DB 拉该用户的收藏列表
        $key = 'novel_fav_'.$userId;
        if($redis->exists($key) == 0){
            $novelIds = $this->getList(['user_id'=>$userId],['novel_id'],['created_at'=>-1],0,2000);
            $novelIds = array_column($novelIds,'novel_id');
            if(!empty($novelIds)) {
                $redis->sAdd($key,...$novelIds);
            }else{
                //防穿透
                $redis->sAdd($key, '__EMPTY__');
                $redis->sRem($key, '__EMPTY__');
            }
            $redis->expire($key, 604800);
        }
        $isFav = $redis->sismember($key,$novelId);
//        $favoriteId = md5($userId.'_'.$comicsId);
//        $count = $this->count(array('_id' => $favoriteId));
//        $row = $this->findByID($favoriteId,['_id']);
        return (bool)$isFav;
    }

    /**
     * 获取收藏列表
     * @param $userId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getFavorites($userId,$page = 1, $pageSize = 15)
    {
        $skip   = ($page - 1) * $pageSize;
        $result = [];
        $items  = $this->novelFavoriteModel->find(['user_id' => intval($userId)], [], ['created_at' => -1], $skip, $pageSize);
        foreach ($items as $item) {
            $result[$item['novel_id']] = array(
                'date_label'    => dateFormat($item['updated_at'],'Y-m-d'),
                'updated_time'  => strval($item['updated_at'])
            );
        }
        return $result;
    }

    /**
     * 删除一个
     * @param $userId
     * @param $comicsId
     * @return bool
     */
    public function delFirst($userId, $comicsId)
    {
        $favoriteId = md5($userId.'_'.$comicsId);
        $this->delete($favoriteId);
        return true;
    }

    /**
     * 删除所有
     * @param $userId
     * @return mixed
     */
    public function deleteAll($userId)
    {
        return $this->novelFavoriteModel->delete(['user_id'=>intval($userId)]);
    }

}