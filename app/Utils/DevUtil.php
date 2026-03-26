<?php

declare(strict_types=1);

namespace App\Utils;

use MongoDB\Driver\Exception\Exception;

class DevUtil
{
    /**
     * 生成model
     * @param string $table
     */
    public function autoCreateModels($table='')
    {
        $path = APP_PATH . '/Resource/tables/';
        foreach (glob($path . ($table?"{$table}.json":'*.json')) as $filePath) {
            $this->parseConfig($filePath);
        }
    }

    protected function parseConfig($configFile)
    {
        $config = file_get_contents($configFile);
        $config = json_decode($config, true);
        $tableName = $config['table_name'];
        $className = $config['class_name'];
        $fields = $config['fields'];
        $description = $config['description'];
        LogUtil::info('Create table:'.$tableName);
        $indexes = array();
        $tpl = '<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * {$description}
 * @package App\Models
{$property}
 */
class {$className} extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name=\'{$tableName}\'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}';
        $tpl = str_replace('{$description}', $description, $tpl);
        $tpl = str_replace('{$className}', $className, $tpl);
        $tpl = str_replace('{$tableName}', $tableName, $tpl);
        $property = array();
        foreach ($fields as $field) {
            $type = $this->getMongoType($field['type']);
            $property[] = " * @property " . $type . " " . $field["name"] . " " . $field["description"];
            if (!empty($field['index'])) {
                $indexes[$field["name"]] = $field['index'];
            }
        }
        $tpl = str_replace('{$property}', join($property, "\r\n"), $tpl);
        $filePath = BASE_PATH . "/app/Models/{$className}.php";
        file_put_contents($filePath, $tpl);
        try {
                $this->initTable($tableName, $indexes);
        } catch (Exception $e) {

        }
    }

    /**
     * 创建集合和修复索引
     * @param $tableName
     * @param $indexes
     * @param string $connectName
     */
    protected function initTable($tableName, $indexes,$connectName='default')
    {
        $cmd = array(
            'listCollections' => 1,
        );
        $connectName = "mongodb_{$connectName}";
        $result = container()->get($connectName)->executeCommand($cmd)->toArray();
        $tables = array();
        foreach ($result as $item) {
            $tables[] = $item->name;
        }

        //创建集合
        if (!in_array($tableName, $tables)) {
            $cmd = [
                'create' => $tableName,
            ];
            container()->get($connectName)->executeCommand($cmd)->toArray();
        }

        //创建索引
        foreach ($indexes as $indexName => $indexType) {
            $indexType = strtolower($indexType);
            $opt = array('key' => array("{$indexName}" => 1), 'name' => "{$indexName}");
            if ($indexType == 'unique') {
                $opt['unique'] = true;
            } else {
                $opt['unique'] = false;
            }
            $cmd = array(
                'createIndexes' => $tableName,
                'indexes' => array(
                    $opt
                )
            );
            container()->get($connectName)->executeCommand($cmd)->toArray();
        }
    }

    /**
     * 转化成描述类型
     * @param $type
     * @return string
     */
    protected function getMongoType($type)
    {
        if ($type == 'int') {
            return "integer";
        } elseif ($type == "string") {
            return "string";
        } elseif ($type == 'double') {
            return "double";
        } elseif ($type == 'array') {
            return "array";
        } else {
            return "string";
        }
    }
}