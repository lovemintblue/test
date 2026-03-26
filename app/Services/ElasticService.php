<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Services\BaseService;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 * es操作类
 * Class ElasticService
 * @package App\Services
 */
class  ElasticService extends BaseService
{
    /**
     * 获取前缀
     * @param $indexName
     * @return string
     */
    public function getPrefix($indexName)
    {
        $prefix=$this->container->get('config')->path('elastic.prefix');
        return $prefix.$indexName;
    }

    /**
     * 保存数据
     * @param  $documentId
     * @param  $document
     * @param  $typeName
     * @param  $indexName
     * @return boolean
     */
    public function save($documentId, $document, $typeName, $indexName)
    {
        $indexName=$this->getPrefix($indexName);
        $url = "/{$indexName}/{$typeName}/{$documentId}";
        $result = $this->doRequest($url, 'POST', $document);
        if ($result) {
            $result = json_decode($result);
            if ($result->_version) {
                return true;
            }
        }
        LogUtil::error("同步es失败:{$indexName} {$typeName} $documentId");
        LogUtil::error($result);
        return false;
    }

    /**
     * 更新部分字段
     * @param  $documentId
     * @param  $document
     * @param  $typeName
     * @param  $indexName
     * @return boolean
     */
    public function update($documentId, $document, $typeName, $indexName)
    {
        $indexName=$this->getPrefix($indexName);
        $url = "/{$indexName}/{$typeName}/{$documentId}/_update";
        $document = array(
            'doc' => $document
        );
        $result = $this->doRequest($url, 'POST', $document);
        if ($result) {
            $result = json_decode($result);
            if ($result->_version) {
                return true;
            }
        }
        return false;
    }

    /**
     * 查询
     * @param  $query
     * @param  $typeName
     * @param  $indexName
     * @return mixed
     */
    public function search($query, $typeName, $indexName)
    {
        $indexName=$this->getPrefix($indexName);
        foreach ($query['query'] as $key => $item) {
            if (empty($item)) {
                unset($query['query'][$key]);
            }
        }
        $url = "/{$indexName}/{$typeName}/_search";
        $result = $this->doRequest($url, 'GET', $query);
        if ($result) {
            $result = json_decode($result);
            return $result;
        }
        return null;
    }

    /**
     * 查询
     * @param  $id
     * @param  $typeName
     * @param  $indexName
     * @return array
     */
    public function get($id, $typeName, $indexName)
    {
        $indexName=$this->getPrefix($indexName);
        $url = "/{$indexName}/{$typeName}/" . $id;
        $result = $this->doRequest($url, 'GET');
        if ($result) {
            $result = json_decode($result, true);
            if ($result['found'] > 0) {
                return $result['_source'];
            }
        }
        return array();
    }


    /**
     * 删除数据
     * @param  $typeName
     * @param  $indexName
     * @param  $documentId
     * @return boolean
     */
    public function delete($typeName, $indexName, $documentId = null)
    {
        $indexName=$this->getPrefix($indexName);
        $url = "/{$indexName}/{$typeName}";
        if ($documentId) {
            $url .= '/' . $documentId;
            $result = $this->doRequest($url, 'DELETE');
        } else {
            $url .= '/_delete_by_query?conflicts=proceed';
            $filter = new \stdClass();
            $result = $this->doRequest($url, 'POST', array('query' => array('match_all' => $filter)));
        }
        if ($result) {
            $result = json_decode($result);
            if ($result->result == 'deleted' || isset($result->deleted)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 执行请求
     * @param  $url
     * @param  $method
     * @param  $data
     * @param  $timeout
     * @return  mixed
     */
    protected function doRequest($url, $method, $data = array(), $timeout = 40)
    {
        $config = container()->get('config');
        $connection = 'http://localhost:9200';
        if (isset($config->elastic->host)) {
            $connection = $config->elastic->host;
        }
        $url = $connection . $url;
        if (!in_array($method, array('DELETE', 'PUT', 'GET', 'POST'))) {
            return null;
        }
        if (is_array($data)) {
            $data = json_encode($data);
        }
        $header = array(
            'X-HTTP-Method-Override:' . $method
        );
        if ($data) {
            $header[] = 'Content-Type: application/json; charset=utf-8';
            $header[] = 'Content-Length: ' . strlen($data);
        }
        $ch = CommonUtil::initCurl($url, $header, $timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); //设置请求方式
        if($data){
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $rs = curl_exec($ch);
        curl_close($ch);
        return $rs;
    }
}