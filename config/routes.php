<?php

use Leaf\Route;
use Leaf\View;

//Route::get('/', function () {
//    return View::render('home.twig');
//});

//访问时动态生成缩略图
//http://example.com/uploads/201703/07/2.jpg
//http://example.com/uploads/post/201703/07/2.jpg.m
Route::any('uploads/:uploadPath/:month/:day/:file.:ext.:rule', function (\Leaf\Application $app, $month, $day, $uploadPath, $rule, $file, $ext) {
    $fullFileName = 'uploads/' . $uploadPath . '/' . $month . '/' . $day . '/' . $file . '.' . $ext;
    if (isset($app['storage'])) {
        return $app['storage']->thumb($fullFileName, $rule, [
            's' => ['w' => 120, 'h' => 120, 'cut' => true],
            'm' => ['w' => 400, 'h' => 400,],
            'l' => ['w' => 800, 'h' => 800,],
        ]);
    }

    return View::render('error404.twig');
});
