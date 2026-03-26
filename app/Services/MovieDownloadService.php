<?php


namespace App\Services;


use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Models\MovieDownloadModel;
use App\Utils\CommonUtil;

/**
 * Class MovieDownloadService
 * @property MovieDownloadModel $movieDownloadModel
 * @property UserGroupService $userGroupService
 * @property MovieService $movieService
 * @property UserService $userService
 * @property CommonService $commonService
 * @property M3u8Service $m3u8Service
 * @property UserBuyLogService $userBuyLogService
 * @package App\Services
 */
class MovieDownloadService extends BaseService
{
    /**
     * 下载视频
     * @param $userId
     * @param $movieId
     * @return array
     * @throws BusinessException
     */
    public function do($userId,$movieId)
    {
        $userId   = intval($userId);
        $userInfo = $this->userService->getInfoFromCache($userId);
        $this->userService->checkUser($userInfo);
        $movieInfo= $this->movieService->findByID($movieId);
        if(empty($movieInfo)||$movieInfo['status']!=1){
            throw new BusinessException(StatusCode::DATA_ERROR, '视频已下架!');
        }

        //是否已经缓存
        $idValue = md5($movieId . '_' . $userId);
        $hasDown = $this->movieDownloadModel->count(array('_id' => $idValue));
        if($hasDown==0){
            //判断视频类型
            if($movieInfo['pay_type']=='money'){
                if (!$this->userBuyLogService->has($userId,$movieId,'movie')) {
                    throw new BusinessException(StatusCode::DATA_ERROR, '请购买后下载!');
                }
            }else{
                //是否vip
                if($userInfo['is_vip']=='n'){
                    throw new BusinessException(StatusCode::DATA_ERROR, '只有VIP才能使用缓存功能!');
                }
                $positionName = CommonValues::getMoviePosition($movieInfo['position']);
                $playError = '升级'.$positionName.'VIP缓存该视频! ';
                if($userInfo['level']<=1 && !in_array($movieInfo['position'],array('normal'))){
                    throw new BusinessException(StatusCode::DATA_ERROR, $playError);
                }elseif ($userInfo['level']==2 && !in_array($movieInfo['position'],array('normal','deep'))){
                    throw new BusinessException(StatusCode::DATA_ERROR, $playError);
                }
            }
            $maxNum = $this->getDownloadMaxNum($userInfo['group_id']);
            $usedNum = $this->getDownloadUsedNum($userId);
            if ($usedNum >= $maxNum) {
                throw new BusinessException(StatusCode::DATA_ERROR, '您本周次数已经用完!');
            }
        }
        $data = array(
            '_id'       => $idValue,
            'status'    => 1,
            'movie_id'  => $movieId,
            'user_id'   => intval($userId),
            'link'      => $movieInfo['m3u8_url'],
            'img'       => $movieInfo['img_x'],
            'pay_type'  => $movieInfo['pay_type'],
            'duration'  => $movieInfo['original_duration'] * 1,
            'label'     => date('Y-m-d'),
            'name'      => $movieInfo['name'],
            'created_at'=> time(),
            'updated_at'=> time(),
        );
        $this->movieDownloadModel->findAndModify(['_id'=>$data['_id']],['$set'=>$data],[],true);
        $m3u8Info  = $this->m3u8Service->doDownload($data['link'],$movieInfo['source']);
        if(empty($movieInfo) ||empty($m3u8Info['files'])){
            throw new BusinessException(StatusCode::DATA_ERROR, '下载错误!');
        }
        $this->movieService->handler(['action' => 'download','movie_id'=>$movieId]);
        return [
            'task_id'    => strval($data['_id']),
            'content'    => $m3u8Info['content'],
            'files'      => $m3u8Info['files'],
        ];
    }

    /**
     * 删除缓存
     * @param $userId
     * @param $movieId
     * @return bool
     */
    public function delDownload($userId,$movieId)
    {
        $idValue = md5($movieId . '_' . $userId);
        $this->movieDownloadModel->update(array('status' => -1), array('_id' => $idValue));
        return true;
    }

    /**
     * 删除所有
     * @param $userId
     * @return mixed
     */
    public function deleteAll($userId)
    {
        return $this->movieDownloadModel->update(array('status' => -1), array('user_id'=>intval($userId)));
    }

    /**
     * 缓存列表
     * @param $userId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getDownloadList($userId, $page = 1, $pageSize = 15)
    {

        $result = array();
        $skip = ($page - 1) * $pageSize;
        $items = $this->movieDownloadModel->find(array('user_id' => $userId, 'status' => 1), array(), array('_id' => -1), $skip, $pageSize);
        foreach ($items as $item) {
            $result[$item['movie_id']] = array(
                'task_id'       => $item['_id'],
                'updated_time'  => strval($item['updated_at'])
            );
        }
        return $result;
    }


    /**
     * 获取可缓存的次数
     * @param $groupId
     * @return int
     */
    public function getDownloadMaxNum($groupId)
    {
        $group = $this->userGroupService->getInfo($groupId);
        if (empty($group)) {
            return 0;
        }
        return $group['download_num'] * 1;
    }

    /**
     * 获取本周已经下载的次数
     * @param $userId
     * @return mixed
     */
    public function getDownloadUsedNum($userId)
    {
        $startTime = CommonUtil::getWeekFirst();
        $startTime = intval($startTime);
        $countWhere = [
            'user_id' => intval($userId),
            'created_at' => array('$gte' => $startTime)
        ];
        return $this->movieDownloadModel->count($countWhere);
    }


    /**
     * 解析m3u8
     * @param $link
     * @return array
     * @throws BusinessException
     */
    private function parseM3u8($link)
    {
//        $link = $this->commonService->getCdnUrl($link, 'video');
        return $this->m3u8Service->parse($link);
    }
}