<?php

namespace Service;

use Entity\ProductCategory;
use Entity\ProductImage;
use PFinal\Database\ModelTrait;


trait ProductTrait
{

    /**
     * 显示分类名
     * @return string
     */
    public function showCategory()
    {
        $category = ProductCategory::findByPk($this->category_id);
        if ($category != null) {
            return $category->name;
        }
        return '';
    }

    /**
     * 获取轮播图
     * @return array
     */
    public function getImages(){
        return ProductImage::where(
            'product_id=:product_id and status=:status',
            [
                ':product_id' => $this->id,
                ':status' => ProductImage::STATUS_YES
            ]
        )->findAll();
    }
}