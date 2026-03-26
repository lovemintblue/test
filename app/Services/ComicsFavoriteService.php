<?php


namespace App\Services;


use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Models\ComicsFavoriteModel;
use App\Models\MovieFavoriteModel;
use App\Models\UserFollowModel;

/**
 * 漫画收藏
 * Class MovieFavoriteService
 * @property ComicsFavoriteModel $comicsFavoriteModel
 * @property ComicsService $comicsService
 * @property CommonService $commonService
 * @property UserService $userService
 * @property QueueService $queueService
 * @package App\Services
 */
class ComicsFavoriteService extends BaseService
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
        return $this->comicsFavoriteModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->comicsFavoriteModel->count($query);
    }


    /**
     * 通过id查询
     * @param  $id
     * @param  $fields
     * @return mixed
     */
    public function findByID($id, $fields = array())
    {
        return $this->comicsFavoriteModel->findByID($id, '_id', $fields);
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            $this->comicsFavoriteModel->update($data, array("_id" => $data['_id']));
            $cartoonId = $data['_id'];
        } else {
            $cartoonId = $this->comicsFavoriteModel->insert($data);
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
        return $this->comicsFavoriteModel->delete(['_id'=>$id]);
    }

    /**
     * 收藏
     * @param $userId
     * @param $comicsId
     * @return bool
     * @throws BusinessException
     */
    public function do($userId,$comicsId)
    {
        if (!$this->comicsService->findByID($comicsId)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '漫画不存在!');
        }

        $redis = $this->getRedis();
        $key = 'comics_fav_'.$userId;
        $favoriteId = md5($userId.'_'.$comicsId);
        if ($this->has($userId,$comicsId)) {
            $this->delete($favoriteId);
            $redis->srem($key,$comicsId);//set删除
            $this->comicsService->handler(['action' => 'unFavorite','comics_id'=>$comicsId]);
            return false;
        }

        $this->comicsFavoriteModel->insert([
            '_id' => $favoriteId,
            'comics_id' => $comicsId,
            'user_id' => intval($userId)
        ]);
        $redis->sAdd($key,$comicsId);
        $redis->expire($key, 604800);
        $this->comicsService->handler(['action' => 'favorite','comics_id'=>$comicsId]);
        return true;
    }

    /**
     * 是否收藏
     * @param $userId
     * @param $comicsId
     * @return bool
     */
    public function has($userId,$comicsId)
    {
        //优化为不用每次去大表查1条数据，接近5kw数据，即便查主键也很慢
        $redis = $this->getRedis();
        //第一次访问：如果 key 不存在 → 从 DB 拉该用户的收藏列表
        $key = 'comics_fav_'.$userId;
        if($redis->exists($key) == 0){
            $comicsIds = $this->getList(['user_id'=>$userId],['comics_id'],['created_at'=>-1],0,2000);
            $comicsIds = array_column($comicsIds,'comics_id');
            if(!empty($comicsIds)) {
                $redis->sAdd($key,...$comicsIds);
            }else{
                //防穿透
                $redis->sAdd($key, '__EMPTY__');
                $redis->sRem($key, '__EMPTY__');
            }
            $redis->expire($key, 604800);
        }
        $isFav = $redis->sismember($key,$comicsId);
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
        $items  = $this->comicsFavoriteModel->find(['user_id' => intval($userId)], [], ['created_at' => -1], $skip, $pageSize);
        foreach ($items as $item) {
            $result[$item['comics_id']] = array(
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
        return $this->comicsFavoriteModel->delete(['user_id'=>intval($userId)]);
    }

}