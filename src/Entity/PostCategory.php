<?php

namespace Entity;

use Leaf\BaseObject;
use PFinal\Database\ModelTrait;
use Service\CategoryTrait;

/**
 * 文章分类
 *
 * @property integer $id
 * @property string  $name
 * @property integer $weight
 * @property integer $status
 * @property integer $parent_id
 * @property string  $created_at
 * @property string  $updated_at
 */
class PostCategory extends BaseObject
{
    use ModelTrait, CategoryTrait;

    const STATUS_YES = 10; //有效
    const STATUS_NO = 20; //禁用

    public static function labels()
    {
        return [
            'id' => 'ID',
            'name' => '名称',
            'weight' => '排序权重',
            'status' => '状态',
            'parent_id' => '父级',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }

    /**
     * @param bool $returnAll
     * @return array|string
     */
    public function statusAlias($returnAll = false)
    {
        $map = [
            self::STATUS_YES => '显示',
            self::STATUS_NO => '隐藏',
        ];

        if ($returnAll) {
            return $map;
        }

        return isset($map[$this->status]) ? $map[$this->status] : '';
    }

}