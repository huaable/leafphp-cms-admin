<?php

namespace Entity;

use Leaf\Application;
use Leaf\BaseObject;
use Leaf\Url;
use PFinal\Database\ModelTrait;

/**
 * 产品图片
 *
 * @property integer $id
 * @property integer $product_id
 * @property string  $filename
 * @property string  $status
 * @property string  $created_at
 * @property string  $updated_at
 */
class ProductImage extends BaseObject
{
    use ModelTrait;
    const STATUS_YES = 10;//有效
    const STATUS_NO = 20;//删除

    public static function labels()
    {
        return [
            'id' => 'ID',
            'product_id' => '产品ID',
            'filename' => '图片',
            'status' => '状态',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }

    /**
     * 图片文件保存目录
     * @return string
     */
    public static function uploadPath()
    {
        return 'uploads/product-image';
    }

    /**
     * 配图完整URL
     * @return string
     */
    public function url()
    {
        $app = Application::$app;
        $upFile = self::uploadPath() . DIRECTORY_SEPARATOR . $this->filename;
        if (isset($app['storage'])) {
            return $app['storage']->url($upFile);
        }
        return Url::to('', true) . $upFile;
    }


    //    /**
    //     * @param bool $returnAll
    //     * @return array|string
    //     */
    //    public function statusAlias($returnAll = false)
    //    {
    //        $map = [
    //            self::STATUS_YES => '有效',
    //            self::STATUS_NO => '删除',
    //        ];
    //
    //        if ($returnAll) {
    //            return $map;
    //        }
    //
    //        return isset($map[$this->status]) ? $map[$this->status] : '';
    //    }
}