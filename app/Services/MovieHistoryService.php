<?php


namespace App\Services;


use App\Core\Services\BaseService;
use App\Models\MovieHistoryModel;
use App\Utils\CommonUtil;
use App\Utils\DevUtil;
use App\Utils\LogUtil;

/**
 * Class MovieHistoryService
 * @property MovieHistoryModel $movieHistoryModel
 * @property UserService $userService
 * @property MovieService $movieService
 * @property CommonService $commonService
 * @property ChannelReportService $channelReportService
 * @property ElasticService $elasticService
 * @package App\Services
 */
class MovieHistoryService extends BaseService
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
        return $this->movieHistoryModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 通过id查询
     * @param  $id
     * @param  $fields
     * @return mixed
     */
    public function findByID($id, $fields = array())
    {
        return $this->movieHistoryModel->findByID($id, '_id', $fields);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->movieHistoryModel->count($query);
    }


    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->movieHistoryModel->delete(['_id'=>$id]);
    }

    /**
     * @param $userId
     * @param $movieId
     * @param $time
     * @return bool|int|mixed
     */
    public function do($userId,$movieId,$time)
    {
        $userId    = intval($userId);
        $count     = intval($time);
        $itemId    = md5($userId . '_' . $movieId);

        $movieRow = $this->elasticService->get($movieId, 'movie', 'movie');
        $movieName = $movieRow['name']??'';
        $movieCatId = $movieRow['categories']['id']??'';
        $movieCatName = $movieRow['categories']['name']??'';
        $movieTagIds = $movieRow['tags']? array_column($movieRow['tags'], 'id') :[];
        $movieTagNames = $movieRow['tags']? array_column($movieRow['tags'], 'name'):[];
        DataCenterService::doMoviePlayEvent($movieId,$movieName,$movieCatId,$movieCatName,$movieTagIds,$movieTagNames,$movieRow['duration'],$time,'video_play');
        if ($this->findByID($itemId,['_id'])) {
            return $this->movieHistoryModel->updateRaw([
                '$set'=>['status'=>1,'label'=>date("Y-m-d"),'time'=>$count,'updated_at'=>time()]
            ],['_id'=>$itemId]);
        }else{
            //观影次数统计,每人每天每个视频只记录一次
            $userInfo = $this->userService->getInfoFromCache($userId);
            if($userInfo&&$userInfo['id']>0){
                $this->channelReportService->doView($userInfo['channel_name']);
            }

            return $this->movieHistoryModel->insert([
                '_id'       => $itemId,
                'user_id'   => $userId,
                'movie_id'  => $movieId,
                'time'     =>  $time,
                'status'    => 1,
                'label' => date("Y-m-d"),
            ]);
        }
    }


    /**
     * 观看列表
     * @param $userId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getHistories($userId, $page = 1, $pageSize = 20)
    {
        $result = array();
        $skip = ($page - 1) * $pageSize;
        $items = $this->getList(['user_id' => intval($userId)], [], ['updated_at' => -1], $skip, $pageSize);
        foreach ($items as $item) {
            $result[$item['movie_id']] = array(
                'movie_id'      => strval($item['movie_id']),
                'time'          => strval($item['time']),
                'date_label'    => dateFormat($item['updated_at'],'Y-m-d'),
                'updated_time'  => strval($item['updated_at'])
            );
        }
        return $result;
    }

    /**
     * 删除一个
     * @param $userId
     * @param $movieId
     * @return bool
     */
    public function delFirst($userId, $movieId)
    {
        $itemId = md5($userId . '_' . $movieId);
        $this->movieHistoryModel->delete(['_id'=>$itemId]);
        return true;
    }

    /**
     * 删除所有
     * @param $userId
     * @return mixed
     */
    public function deleteAll($userId)
    {
        return $this->movieHistoryModel->delete(['user_id'=>intval($userId)]);
    }


    /**
     * 获取已经播放次数
     * @param $userId
     * @param $id
     * @return mixed
     */
    public function getPlayNum($userId, $id = 0)
    {
        $startTime = CommonUtil::getTodayZeroTime();
        $countWhere = array(
            'user_id' => intval($userId),
            'updated_at' => array('$gte' => intval($startTime)),
            'count'   => intval(1)
        );
        if ($id) {
            $countWhere ['movie_id'] = array(
                '$ne' => intval($id),
            );
        }
        $count = $this->movieHistoryModel->count($countWhere);
        return $count;
    }

    /**
     * @return int
     */
    public function getCanPlayNum()
    {
        $canPlayNum = $this->commonService->getConfig('can_play_num');
        return intval($canPlayNum);
    }

    private function setTable($userId)
    {
        $tableName = "movie_history_".CommonUtil::getIdTable($userId,DevUtil::$subTableNum['movie_history']);
        $this->movieHistoryModel->connect('history')->setCollectionName($tableName);
    }
}