<?php

namespace App\Jobs\Common;

use App\Constants\CommonValues;
use App\Jobs\BaseJob;
use App\Services\AgentSystemService;
use App\Services\DomainService;
use App\Utils\LogUtil;

/**
 * Class AsyncDomainJob
 * @property DomainService $domainService
 * @property AgentSystemService $agentSystemService
 * @package App\Jobs\Common
 */
class AsyncDomainJob extends BaseJob
{
    private $types = array(
        'h5_proxy' => 'h5_proxy',
        'h5' => 'h5',
        'private' => 'web',
        'web' => 'web',
        'channel' => 'channel_web',
        'api' => 'api',
    );

    public function handler($uniqid)
    {
        $autoChangeDomain = false;
        if(in_array(date('H'),[05,17])){
            $autoChangeDomain = true;
        }
        $filter = array();
        $count = $this->domainService->count($filter);
        $pageSize = 200;
        $totalPage = ceil($count / $pageSize);
        for ($page = 1; $page <= $totalPage; $page++) {
            LogUtil::info(sprintf('Async domain %s/%s', $page, $totalPage));
            $items = $this->domainService->getList($filter, array(), array(), ($page - 1) * $pageSize, $pageSize);
            $data = array();
            $updateDomain = [];
            foreach ($items as $item) {
                if($autoChangeDomain&&strpos($item['url'],'-')){
                    $url = $this->getRandDomain($item['url']);
                    $updateDomain[$url] = $item['url'];
                    $item['url'] = $url;
                }
                $data[] = array(
                    'domain' => $item['url'],
                    'type' => $this->types[$item['type']],
                    'is_https' => 1,
                    'status' => $item['status'],
                    'created_at' => $item['created_at'],
                    'updated_at' => $item['updated_at'],
                );
            }
            if (empty($data)) {
                break;
            }
            $result = $this->agentSystemService->doHttpPost('common/asyncDomain',$data);
            if (empty($result) || $result['status'] != 'y' || empty($result['data'])) {
                LogUtil::error('Async error!'.var_export($result,true));
                continue;
            }

            $queryData['domain'] = implode(',',array_column($data,'domain'));
            $result = $this->agentSystemService->doHttpPost('common/getDomainInfo',$queryData);
            if (empty($result) || $result['status'] != 'y' || empty($result['data'])) {
                LogUtil::error('Query error!'.var_export($result,true));
                continue;
            }

            $cities = $result['data']['cities'];
            if($cities!=CommonValues::getMonitorCities()){
                container()->get('redis')->set('monitor_cities', json_encode($cities));
            }
            foreach ($result['data']['items'] as $item) {
                if ($item['status'] == -1) {
                    LogUtil::info(sprintf('Domain %s has blocked!',$item['url']));
                }
                $update = array(
                    'status' => $item['status']*1
                );
                if($updateDomain){
                    $update['url'] = strval($item['domain']);
                }
                foreach ($cities as $city)
                {
                    $update[$city]=$item[$city]*1;
                }
                $this->domainService->domainModel->updateRaw(['$set'=>$update],['url'=>$updateDomain[$item['domain']]?:$item['domain']]);
//                $this->domainService->domainModel->update($update, array('url' => $item['domain']));
            }
        }
        LogUtil::info('Async domain ok!');
    }

    public function getRandDomain($url)
    {
        if(strpos($url,'http')===false){
            $url = 'https://'.$url;
        }
        $parsed = parse_url($url);
        if (!isset($parsed['host'])) {
            return false;
        }
        $topDomain = $parsed['host'];
        $hostParts = explode('.', $parsed['host']);
        $pre = $after = '';
        if (count($hostParts) > 2) {
            if(strpos($hostParts[0],'-')===false){
                return $topDomain;
            }
            $lastTwo = array_slice($hostParts, -2);
            $topDomain = implode('.', $lastTwo);
            $pre = $this->generateSecureRandomDomain(4);
            $after = $this->generateSecureRandomDomain(rand(1,2)).'.';
        }

        return $pre.'-'.$after.$topDomain;
    }

    function generateSecureRandomDomain($length = 10)
    {
        $bytes = random_bytes($length);
        $randomString = substr(bin2hex($bytes), 0, $length);
        return $randomString;
    }

    public function success($uniqid)
    {
        // TODO: Implement success() method.
    }

    public function error($uniqid)
    {
        // TODO: Implement error() method.
    }

}