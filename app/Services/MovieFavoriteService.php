<?php


namespace App\Services;


use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Models\MovieFavoriteModel;
use App\Models\UserFollowModel;

/**
 * 视频收藏
 * Class MovieFavoriteService
 * @property MovieFavoriteModel $movieFavoriteModel
 * @property MovieService $movieService
 * @property CommonService $commonService
 * @property UserService $userService
 * @property QueueService $queueService
 * @package App\Services
 */
class MovieFavoriteService extends BaseService
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
        return $this->movieFavoriteModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->movieFavoriteModel->count($query);
    }


    /**
     * 通过id查询
     * @param  $id
     * @param  $fields
     * @return mixed
     */
    public function findByID($id, $fields = array())
    {
        return $this->movieFavoriteModel->findByID($id, '_id', $fields);
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            $this->movieFavoriteModel->update($data, array("_id" => $data['_id']));
            $cartoonId = $data['_id'];
        } else {
            $cartoonId = $this->movieFavoriteModel->insert($data);
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
        return $this->movieFavoriteModel->delete(['_id'=>$id]);
    }

    /**
     * 收藏
     * @param $userId
     * @param $movieId
     * @return bool
     * @throws BusinessException
     */
    public function do($userId,$movieId)
    {
        if (!$this->movieService->findFirst(['_id'=>$movieId],['_id'])) {
            throw new BusinessException(StatusCode::DATA_ERROR, '视频不存在!');
        }

        $redis = $this->getRedis();
        $key = 'movie_fav_'.$userId;
        $favoriteId = md5($userId.'_'.$movieId);
        if ($this->has($userId,$movieId)) {
            $this->delete($favoriteId);
            $redis->srem($key,$movieId);//set删除
            $this->movieService->handler(['action' => 'unFavorite','movie_id'=>$movieId]);
            return false;
        }
        $this->movieFavoriteModel->insert([
            '_id' => $favoriteId,
            'movie_id' => $movieId,
            'user_id' => intval($userId)
        ]);
        $redis->sAdd($key,$movieId);
        $redis->expire($key, 604800);
        $this->movieService->handler(['action' => 'favorite','movie_id'=>$movieId]);
        return true;
    }

    /**
     * 是否收藏
     * @param $userId
     * @param $movieId
     * @return bool
     */
    public function has($userId,$movieId)
    {
        //优化为不用每次去大表查1条数据，接近5kw数据，即便查主键也很慢
        $redis = $this->getRedis();
        //第一次访问：如果 key 不存在 → 从 DB 拉该用户的收藏列表
        $key = 'movie_fav_'.$userId;
        if($redis->exists($key) == 0){
            $movieIds = $this->getList(['user_id'=>$userId],['movie_id'],['created_at'=>-1],0,2000);
            $movieIds = array_column($movieIds,'movie_id');
            if(!empty($movieIds)) {
                $redis->sAdd($key,...$movieIds);
            }else{
                //防穿透
                $redis->sAdd($key, '__EMPTY__');
                $redis->sRem($key, '__EMPTY__');
            }
            $redis->expire($key, 604800);
        }
        $isFav = $redis->sismember($key,$movieId);
//        $favoriteId = md5($userId.'_'.$movieId);
//        $count = $this->count(array('_id' => $favoriteId));
//        $row = $this->findByID($favoriteId, ['_id']);

        return (bool)$isFav;


    }

    /**
     * @param $userId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getFavorites($userId,$page = 1, $pageSize = 15)
    {
        $skip   = ($page - 1) * $pageSize;
        $result = [];
        $items  = $this->movieFavoriteModel->find(['user_id' => intval($userId)], [], ['created_at' => -1], $skip, $pageSize);
        foreach ($items as $item) {
            $result[$item['movie_id']] = array(
                'date_label'    => dateFormat($item['updated_at'],'Y-m-d'),
                'updated_time'  => strval($item['updated_at'])
            );
        }
        return $result;
    }

    /**
     * 删除一个
     * @param $userId
     * @param $cartoonId
     * @return bool
     */
    public function delFirst($userId, $cartoonId)
    {
        $favoriteId = md5($userId.'_'.$cartoonId);
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
        return $this->movieFavoriteModel->delete(['user_id'=>intval($userId)]);
    }

}