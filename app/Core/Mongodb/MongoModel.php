<?php


namespace App\Core\Mongodb;


use App\Utils\LogUtil;
use MongoDB\Driver\Session;

abstract class MongoModel
{
    /**
     * @var MongoDbConnection $_connect
     */
    private $_connect;

    /**
     * 当前连接
     * @var
     */
    protected $_collection;

    public function __construct()
    {
        $this->connect();
    }

    /**
     * 设置数据源
     * @param string $name
     * @return mixed
     */
    public abstract function setCollectionName(string $name):MongoModel ;

    /**
     * 获取数据源
     * @return string
     */
    public function getCollectionName():string
    {
        return $this->_collection;
    }

    /**
     * @param string $db
     * @return $this|null
     */
    public function connect($db='default')
    {
        $keyName = "mongodb_".$db;
        if (!container()->offsetExists($keyName)) {
            return null;
        }
        $this->_connect=container()->get($keyName);
        return $this;
    }

    /**
     * 获取自增id
     * @param  $collectionName
     * @return  mixed
     * @throws
     */
    public function getInsertId($collectionName)
    {
        $cmd = array(
            'findAndModify' => 'collection_ids',
            'query' => array('name' => $collectionName),
            'update' => array('$inc' => array('id' => 1)),
            'upsert' => true,
            'new' => true
        );
        $item = $this->_connect->executeCommand($cmd)->toArray();
        if (isset($item[0]->value->id)) {
            return $item[0]->value->id * 1;
        }
        return 1;
    }

    /**
     * @param array $query
     * @param array $fields
     * @param array $sort
     * @param int $skip
     * @param int $limit
     * @return array
     */
    public function find($query = array(), $fields = array(), $sort = array(), $skip = 0, $limit = 10)
    {
        if($skip<0){
            $skip=0;
        }
        $tableName = $this->getCollectionName();
        $opts = [
            'find' => $tableName, // collection表名
            'limit' => $limit,
            'skip' => $skip
        ];
        if ($query) {
            $opts['filter'] = $query;
        }
        if ($sort) {
            $opts['sort'] = $sort;
        }
        if ($fields) {
            $projection = array();
            foreach ($fields as $field) {
                $projection[$field] = 1;
            }
            $opts['projection'] = $projection;
        }
        $items = array();
        $result = $this->_connect->executeCommand($opts);
        foreach ($result as $item) {
            $items[] = (array)$item;
        }
        return $items;
    }

    /**
     * 查找一条数据
     * @param array $query
     * @param array $fields
     * @return array
     * @throws
     */
    public function findFirst($query = array(), $fields = array())
    {
        $result = $this->find($query, $fields, array(), 0, 1);
        return empty($result) ? null : $result[0];
    }

    /**
     * 通过id查询
     * @param  $id
     * @param string $idName
     * @param $fields
     * @return mixed
     */
    public function findByID($id, $idName = '_id', $fields = array())
    {
        $item = $this->findFirst(array($idName => $id), $fields);
        return empty($item) ? null : $item;
    }

    /**
     * 查找并修改
     * @param array $query
     * @param array $update 用$inc  $set 包裹
     * @param array $fields
     * @param bool $upsert
     * @return mixed
     * @throws
     */
    public function findAndModify($query = array(), $update = array(), $fields = array(), $upsert = false)
    {
        $tableName = $this->getCollectionName();
        $opts = array(
            'findAndModify' => $tableName,
            'query' => $query,
            'update' => $update,
            'upsert' => $upsert
        );
        if ($fields) {
            $projection = array();
            foreach ($fields as $field) {
                $projection[$field] = 1;
            }
            $opts['fields'] = $projection;
        }
        $result = $this->_connect->executeCommand($opts)->toArray();
        if (isset($result[0]->value) && !empty($result[0]->value)) {
            $result = $result[0]->value;
            return (array)$result;
        }
        return null;
    }

    /**
     * 获取mong
     * @param array $document
     * @param bool $autoId
     * @param Session $session
     * @return bool|int
     * @throws
     */
    public function insert($document = array(), $autoId = true, $session = null)
    {
        $document['created_at'] = !isset($document['created_at']) ? time() : $document['created_at'];
        $document['updated_at'] = !isset($document['updated_at']) ? time() : $document['updated_at'];
        $tableName = $this->getCollectionName();
        if ($autoId) {
            if (empty($document['_id'])) {
                $document['_id'] = intval($this->getInsertId($tableName));
                if ($document['_id'] <= 0) {
                    $document['_id'] = 1;
                }
            }
        }
        $cmd = [
            'insert' => $tableName, // collection表名
            'documents' => [$document]
        ];
        $result = $this->_connect->executeCommand($cmd)->toArray();
        if($result[0]->n==0){
            $cmd['error']=sprintf('%s in %s line %s',$result[0]->writeErrors[0]->errmsg, __FILE__,__LINE__);
            LogUtil::error($cmd);
        }
        return $result[0]->n > 0 ? $document['_id'] : null;
    }

    /**
     * 修改数据(只修改提交的数据)
     * @param  $document
     * @param  $where
     * @return mixed
     * @throws
     */
    public function update($document = array(), $where = array(), $multi=true)
    {
        unset($document['_id']);
        $document['updated_at'] = !isset($document['updated_at']) ? time() : $document['updated_at'];
        $tableName = $this->getCollectionName();
        $cmd = [
            'update' => $tableName, // collection表名
            'updates' => array(
                array(
                    'q' => $where,
                    'u' => array('$set' => $document),
                    'multi' => $multi
                )
            )
        ];
        $result = $this->_connect->executeCommand($cmd)->toArray();
        if($result[0]->n==0){
            $cmd['error']=sprintf('%s in %s line %s',$result[0]->writeErrors[0]->errmsg, __FILE__,__LINE__);
            LogUtil::error($cmd);
            return false;
        }
        return $result[0]->ok == 1;
    }

    /**
     * 修改数据(可以使用操作符)
     * @param  $document
     * @param  $where
     * @return mixed
     * @throws
     */
    public function updateRaw($document = array(), $where = array())
    {
        $tableName = $this->getCollectionName();
        $cmd = [
            'update' => $tableName, // collection表名
            'updates' => array(
                array(
                    'q' => $where,
                    'u' => $document,
                    'multi' => true
                )
            )
        ];
        $result = $this->_connect->executeCommand($cmd)->toArray();
        if($result[0]->n==0 && !empty($result[0]->writeErrors)){
            $cmd['error']=sprintf('%s in %s line %s',$result[0]->writeErrors[0]->errmsg, __FILE__,__LINE__);
            LogUtil::error($cmd);
            return false;
        }
        return $result[0]->ok == 1;
    }

    /**
     * 统计
     * @param array $query
     * @return integer
     * @throws
     */
    public function count($query = array())
    {
        $tableName = $this->getCollectionName();
        $cmd = array(
            'count' => $tableName,
        );
        if ($query) {
            $cmd['query'] = $query;
        }
        $result = $this->_connect->executeCommand($cmd)->toArray();
        return $result[0]->n * 1;
    }

    /**
     * 删除数据
     * @param  $query
     * @param  $limit
     * @return  mixed
     * @throws
     */
    public function delete($query = array(), $limit = 0)
    {
        $tableName = $this->getCollectionName();
        $cmd = array(
            'delete' => $tableName,
            'deletes' => array(
                array(
                    'q' => $query,
                    'limit' => $limit
                )
            )
        );
        $result = $this->_connect->executeCommand($cmd)->toArray();
        return $result[0]->ok == 1;
    }

    /**
     * 删除集合
     * @return mixed
     * @throws
     */
    public function drop()
    {
        $tableName = $this->getCollectionName();
        $cmd = array(
            'drop' => $tableName,
        );
        $result = $this->_connect->executeCommand($cmd)->toArray();
        return $result[0]->ok == 1;
    }

    /**
     * 创建索引
     * array('key' =>array( 'sex'=>1), 'name' => 'sex','unique'=>false)
     * @param  $index
     * @return  mixed
     * @throws
     */
    public function ensureIndex($index)
    {
        $tableName = $this->getCollectionName();
        $cmd = array(
            'createIndexes' => $tableName,
            'indexes' => array(
                $index
            )
        );
        $result = $this->_connect->executeCommand($cmd)->toArray();
        return $result[0]->ok == 1;
    }

    /**
     * 聚合
     * @param $pipeline
     * @return mixed
     * @throws
     */
    public function aggregate($pipeline)
    {
        $tableName = $this->getCollectionName();
        try {
            $cmd = array(
                'aggregate' => $tableName,
                'pipeline' => $pipeline,
                'cursor' => new \stdClass
            );
            $result = $this->_connect->executeCommand($cmd)->toArray();
            return empty($result) ? null : $result[0];
        } catch (\Exception $exception) {

        }
        return null;
    }

    /**
     * 聚合
     * @param $pipeline
     * @return mixed
     * @throws
     */
    public function aggregates($pipeline)
    {
        $tableName = $this->getCollectionName();
        try {
            $cmd = array(
                'aggregate' => $tableName,
                'pipeline' => $pipeline,
                'cursor' => new \stdClass
            );
            $result = $this->_connect->executeCommand($cmd)->toArray();
            return empty($result) ? null : $result;
        } catch (\Exception $exception) {

        }
        return null;
    }

    public function getConnection()
    {
        return $this->_connect;
    }
}