<?php

use Leaf\Route;
Route::get('/api', function () {

    dump(\Leaf\Application::$app['router']);
});
Route::group(['middleware' => []], function () {
    Route::annotation('ApiBundle\Controller\ProductController');
});