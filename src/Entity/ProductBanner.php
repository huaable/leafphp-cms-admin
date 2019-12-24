<?php

namespace Entity;

use Leaf\Application;
use Leaf\BaseObject;
use Leaf\Url;
use PFinal\Database\ModelTrait;

/**
 * 横幅广告
 *
 * @property integer $id
 * @property integer $product_id
 * @property string  $filename
 * @property integer  $weight
 * @property string  $created_at
 * @property string  $updated_at
 */
class ProductBanner extends BaseObject
{
    use ModelTrait;

    //    const STATUS_YES = 10;//有效
    //    const STATUS_NO = 20;//禁用

    public static function labels()
    {
        return [
            'id' => 'ID',
            'product_id' => '绑定产品ID',
            'filename' => 'banner图',
            'weight' => '排序权重',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }

    //    /**
    //     * @param bool $returnAll
    //     * @return array|string
    //     */
    //    public function statusAlias($returnAll = false)
    //    {
    //        $map = [
    //            self::STATUS_YES => '有效',
    //            self::STATUS_NO => '禁用',
    //        ];
    //
    //        if ($returnAll) {
    //            return $map;
    //        }
    //
    //        return isset($map[$this->status]) ? $map[$this->status] : '';
    //    }


    /**
     * 文件上传保存目录
     * @return string
     */
    public static function uploadPath()
    {
        return 'uploads/product-banner';
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




}