<?php

namespace Entity;

use Leaf\BaseObject;
use Leaf\Url;
use PFinal\Database\ModelTrait;
use Service\UploadManagerTrait;

/**
 * 文件资源管理
 *
 * @property integer $id
 * @property string  $model
 * @property integer $model_id
 * @property string  $filename
 * @property integer $status
 * @property string  $created_at
 * @property string  $updated_at
 */
class UploadManager extends BaseObject
{
    use ModelTrait, UploadManagerTrait;

    const STATUS_YES = 10;//有效
    const STATUS_NO = 20;//删除

    public static function labels()
    {
        return [
            'id' => 'ID',
            'model' => '来源',
            'model_id' => '来源ID',
            'filename' => '文件',
            'status' => '状态',
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
            self::STATUS_YES => '有效',
            self::STATUS_NO => '可删除',
        ];

        if ($returnAll) {
            return $map;
        }

        return isset($map[$this->status]) ? $map[$this->status] : '';
    }


}