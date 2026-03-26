<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 账号日志日志
 * @package App\Models
 * @property integer _id 编号
 * @property string name 名称
 * @property integer id 自增
 */
class CollectionIdsModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='collection_ids'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}