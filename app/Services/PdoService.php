<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Services\BaseService;
use App\Utils\LogUtil;
use PDO;

/**
 * pdo操作
 * Class PdoService
 * @package App\Services
 */
class PdoService extends BaseService
{
    private  $dsn  = '';
    private  $user     = '';
    private  $password = '';

    protected $db = null;


    public function __construct()
    {
        $config = container()->get('config');
        $configs = $config->mysql_backup->toArray();
        $this->dsn = sprintf('mysql:dbname=%s;host=%s;port=%s',$configs['db'],$configs['host'],$configs['port']);
        LogUtil::info($this->dsn);
        $this->user =$configs['user'];
        $this->password =$configs['password'];
    }

    public function getDb()
    {
        if($this->db==null){
            $params = [
                PDO::MYSQL_ATTR_INIT_COMMAND => "set names utf8", //设置编码
                PDO::ATTR_EMULATE_PREPARES   => false, //使用预处理
            ];
            $this->db = new PDO($this->dsn, $this->user, $this->password, $params);
        }
        return $this->db;
    }

    public function getOne($sql)
    {
        $result =$this->getDb()->query($sql);
        return $result->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll($sql)
    {
        $result =$this->getDb()->query($sql);
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }

}
