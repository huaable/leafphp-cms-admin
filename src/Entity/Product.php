<?php

namespace Entity;

use Leaf\Application;
use Leaf\BaseObject;
use Leaf\Url;
use PFinal\Database\ModelTrait;
use Service\ProductTrait;
use Service\UploadManagerTrait;

/**
 * 产品
 *
 * @property integer $id
 * @property integer $category_id
 * @property integer $status
 * @property integer $recommend
 * @property string  $title
 * @property string  $description
 * @property string  $image
 * @property string  $config
 * @property string  $content
 * @property string  $content1
 * @property string  $content2
 * @property string  $created_at
 * @property string  $updated_at
 */
class Product extends BaseObject
{
    use  ModelTrait, ProductTrait, UploadManagerTrait;

    const STATUS_YES = 10;//发布
    const STATUS_NO = 20;//隐藏

    const RECOMMEND_NO = 10;//不推荐
    const RECOMMEND_YES = 20;//推荐


    public static function labels()
    {
        return [
            'id' => 'ID',
            'category_id' => '产品分类',
            'status' => '发布',
            'recommend' => '置顶',
            'title' => '名称',
            'filename' => '配图',
            'description' => '描述',
            'config' => '主要配置',
            'content' => '详细信息',
            'content1' => '规格参数',
            'content2' => '包装',
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
            self::STATUS_YES => '发布',
            self::STATUS_NO => '隐藏',
        ];

        if ($returnAll) {
            return $map;
        }

        return isset($map[$this->status]) ? $map[$this->status] : '';
    }

    /**
     * @param bool $returnAll
     * @return array|string
     */
    public function recommendAlias($returnAll = false)
    {

        $map = [
            self::RECOMMEND_YES => '是',
            self::RECOMMEND_NO => '否',
        ];

        if ($returnAll) {
            return $map;
        }

        return isset($map[$this->recommend]) ? $map[$this->recommend] : '';
    }

    /**
     * 图片文件保存目录
     * @return string
     */
    public static function uploadPath()
    {
        return 'uploads/product';
    }

//    /**
//     * 配图完整URL
//     * @return string
//     */
//    public function Url()
//    {
//        $app = Application::$app;
//        $upFile = self::uploadPath() . DIRECTORY_SEPARATOR . $this->filename;
//        if (isset($app['storage'])) {
//            return $app['storage']->url($upFile);
//        }
//        return Url::to('', true) . $upFile;
//    }
//
  /**
     * 配图完整URL
     * @return string
     */
    public function faceUrl()
    {
        $app = Application::$app;
        $upFile = ProductImage::uploadPath() . DIRECTORY_SEPARATOR . $this->filename;
        if (isset($app['storage'])) {
            return $app['storage']->url($upFile);
        }
        return Url::to('', true) . $upFile;
    }


}