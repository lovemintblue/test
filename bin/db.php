<?php

use MongoDB\Driver\Cursor;

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
date_default_timezone_set('PRC');

class Db
{
    protected $config = [];
    protected $tables = [];
    protected $conn = null;
    protected $backDir = '';

    public function __construct()
    {
        $this->config = [
            'username' => '',
            'password' => '',
            'host' => '127.0.0.1',
            'port' => 27017,
            'db' => 'yjmh',
            'authMechanism' => 'SCRAM-SHA-256',
            'replica' => '',
        ];
        $this->tables = [
            'admin_user'
        ];
        $this->backDir = dirname(__FILE__) . '/db';
        if (!file_exists($this->backDir)) {
            mkdir($this->backDir);
        }
    }

    /**
     * 导入
     */
    public function import()
    {
        $this->log('Start import..');
        foreach ($this->tables as $table) {
            $this->log('Import ' . $table);
            $backFile = $this->backDir . '/' . $table . '.json';
            if(!file_exists($backFile)){
                continue;
            }
            $content = file_get_contents($backFile);
            $items = json_decode($content,true);
            foreach ($items as $item)
            {
                if(isset($item['_id']['$oid'])){
                    $item['_id'] = new  \MongoDB\BSON\ObjectId($item['_id']['$oid']);
                }
                $checkItem = $this->find($table, array('_id'=>$item['_id']), array(), array(), 0, 1);
                if(empty($checkItem)){
                    $this->insert($table,$item);
                }
            }
        }
        $this->log('End import');
    }

    /**
     * 导出
     */
    public function export()
    {
        $this->log('Start export..');
        foreach ($this->tables as $table) {
            $this->log('Export ' . $table);
            $items = $this->find($table, array(), array(), array(), 0, 1000);
            $backFile = $this->backDir . '/' . $table . '.json';
            file_put_contents($backFile, json_encode($items,JSON_UNESCAPED_UNICODE));
        }
        $this->log('End export');
    }

    protected function log($msg)
    {
        echo $msg . PHP_EOL;
    }

    /**
     * 获取mongodb连接
     * @return \MongoDB\Driver\Manager
     */
    protected function getClient()
    {
        if ($this->conn == null) {
            $uri = "mongodb://";
            if ($this->config['username']) {
                $uri .= "{$this->config['username']}:{$this->config['password']}@";
            }
            $host = empty($this->config['host']) ? "127.0.0.1" : $this->config['host'];
            $port = empty($this->config['port']) ? "27017" : $this->config['port'];
            $uri .= "{$host}:{$port}";
            if ($this->config['replica']) {
                $uri .= "/?replicaSet={$this->config['replica']}";
            }
            $this->conn = new \MongoDB\Driver\Manager($uri);
        }
        return $this->conn;
    }

    /**
     * 执行命令
     * @param $opts
     * @return Cursor
     * @throws \MongoDB\Driver\Exception\Exception
     */
    protected function executeCommand($opts)
    {
        $cmd = new \MongoDB\Driver\Command($opts);
        return $this->getClient()->executeCommand($this->config['db'], $cmd);
    }

    /**
     * 获取mong
     * @param string $collection
     * @param array $document
     * @return bool|int
     * @throws
     */
    protected function insert($collection, $document = array())
    {
        $cmd = [
            'insert' => $collection, // collection表名
            'documents' => [$document]
        ];
        $result = $this->executeCommand($cmd)->toArray();
        return $result[0]->n > 0 ? $document['_id'] : null;
    }


    /**
     * 统计
     * @param string $collection
     * @param array $query
     * @return integer
     * @throws
     */
    protected function count($collection, $query = array())
    {
        $cmd = array(
            'count' => $collection,
        );
        if ($query) {
            $cmd['query'] = $query;
        }
        $result = $this->executeCommand($cmd)->toArray();
        return $result[0]->n * 1;
    }


    /**
     * 查找数据
     * @param string $collection
     * @param array $query
     * @param array $fields
     * @param array $sort
     * @param  $skip
     * @param  $limit
     * @return array
     * @throws
     */
    protected function find($collection, $query = array(), $fields = array(), $sort = array(), $skip = 0, $limit = 10)
    {
        $opts = [
            'find' => $collection, // collection表名
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
        $result = $this->executeCommand($opts);
        foreach ($result as $item) {
            $items[] = (array)$item;
        }
        return $items;
    }
}

if (!isset($argv[1]) || empty($argv[1])) {
    exit('Please enter action!' . PHP_EOL);
}

$db = new Db();

if ($argv[1] == 'import') {
    $db->import();
} elseif ($argv[1] == 'export') {
    $db->export();
}