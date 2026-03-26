<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongodb\MongoModel;

/**
 * 文章
 * @package App\Models
 * @property string _id 编号
 * @property string title 标题
 * @property string category_code 分类
 * @property string content 内容
 * @property string img 图片
 * @property string seo_keywords Seo关键字
 * @property string seo_description Seo描述
 * @property string url url链接
 * @property integer is_recommend 是否推荐
 * @property integer sort 排序
 * @property integer click 点击
 * @property integer created_at 创建时间
 * @property integer updated_at 更新时间
 * @property integer show_dialog 是否显示弹窗
 */
class ArticleModel extends MongoModel
{
    public function __construct()
    {
        parent::__construct();
        $this->setCollectionName();
    }

    public function setCollectionName(string $name='article'): MongoModel
    {
        $this->_collection=$name;
        return $this;
    }
}