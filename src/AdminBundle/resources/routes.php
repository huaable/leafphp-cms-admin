<?php

use Leaf\Route;

\AdminBundle\Controller\AuthController::routes();

Route::group(['middleware' => ['auth-admin', 'csrf']], function () {

    //注释路由声明-》自动加载
    Route::annotation('AdminBundle\Controller\LangController');
    Route::annotation('AdminBundle\Controller\SettingController');
    Route::annotation('AdminBundle\Controller\SystemPostController');
    Route::annotation('AdminBundle\Controller\PostCategoryController');
    Route::annotation('AdminBundle\Controller\PostController');
    Route::annotation('AdminBundle\Controller\ProductCategoryController');
    Route::annotation('AdminBundle\Controller\ProductController');
    Route::annotation('AdminBundle\Controller\ProductBannerController');
    Route::annotation('AdminBundle\Controller\UploadManagerController');

});
