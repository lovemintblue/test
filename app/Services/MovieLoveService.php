<?php


namespace App\Services;


use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Models\MovieFavoriteModel;
use App\Models\MovieLoveModel;
use App\Models\UserFollowModel;

/**
 * 视频收藏
 * Class MovieFavoriteService
 * @property MovieLoveModel $movieLoveModel
 * @property MovieService $movieService
 * @property CommonService $commonService
 * @property UserService $userService
 * @property QueueService $queueService
 * @package App\Services
 */
class MovieLoveService extends BaseService
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
        return $this->movieLoveModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->movieLoveModel->count($query);
    }


    /**
     * 通过id查询
     * @param  $id
     * @param  $fields
     * @return mixed
     */
    public function findByID($id, $fields = array())
    {
        return $this->movieLoveModel->findByID($id, '_id', $fields);
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            $this->movieLoveModel->update($data, array("_id" => $data['_id']));
            $cartoonId = $data['_id'];
        } else {
            $cartoonId = $this->movieLoveModel->insert($data);
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
        return $this->movieLoveModel->delete(['_id'=>$id]);
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
        $key = 'movie_love_'.$userId;
        $objectId = md5($userId.'_'.$movieId);
        if ($this->has($userId,$movieId)) {
            $this->delete($objectId);
            $redis->srem($key,$movieId);//set删除
            $this->movieService->handler(['action' => 'unLove','movie_id'=>$movieId]);
            return false;
        }

        $this->movieLoveModel->insert([
            '_id' => $objectId,
            'movie_id' => $movieId,
            'user_id' => intval($userId)
        ]);
        $redis->sAdd($key,$movieId);
        $redis->expire($key, 604800);
        $this->movieService->handler(['action' => 'love','movie_id'=>$movieId]);
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
        $key = 'movie_love_'.$userId;
        if($redis->exists($key) == 0){
            $movieIds = $this->getList(['user_id'=>$userId],['movie_id'],[],0,2000);
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
//        $objectId = md5($userId.'_'.$movieId);
//        $count = $this->count(array('_id' => $objectId));
//        $row = $this->findByID($objectId,['_id']);
        return (bool)$isFav;
    }

    /**
     * 删除所有
     * @param $userId
     * @return mixed
     */
    public function deleteAll($userId)
    {
        return $this->movieLoveModel->delete(['user_id'=>intval($userId)]);
    }

}