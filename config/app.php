<?php

//应用基准目录
$app['path'] = dirname(__DIR__);

$app->register(new \Leaf\Provider\LogServiceProvider());
$app->register(new \Leaf\Provider\DatabaseServiceProvider());
$app->register(new \Leaf\Provider\TwigServiceProvider());
$app->register(new \Leaf\Provider\SessionProvider());
$app->register(new \Leaf\Provider\CaptchaProvider());
$app->register(new \Leaf\Provider\CacheProvider());
$app->register(new \Leaf\Provider\QueueProvider());

$app->registerBundle(new \AdminBundle\AdminBundle());
$app->registerBundle(new \ApiBundle\ApiBundle());


$app->register(new \Leaf\Provider\MailServiceProvider(), ['mail.config' => [
    'host' => 'smtp.qq.com',
    'username' => '739775705@qq.com',
    'password' => 'cmbcebeefaevbffb',
    'name' => 'huaable',
    'port' => '465',
    'encryption' => 'ssl', //ssl、tls
]]);


//如果开启路由缓存，则不支持使用闭包路由
$app['route.cache'] = false;

// 全局中间件
$app['middleware'] = array_merge($app['middleware'], ['Middleware\CorsMiddleware']);

//中间件
$app['auth'] = 'Middleware\AuthMiddleware';
$app['csrf'] = 'Middleware\CsrfMiddleware';
$app['auth-admin'] = 'Middleware\AuthAdminMiddleware';
//
$app['storage'] = function () use ($app) {
    return new \PFinal\Storage\Local([
        'basePath' => $app['path'] . '/web/',
        'baseUrl' => \Leaf\Url::asset('/', true),
//        'baseUrl' => 'http://www.x.x/',
    ]);
};

//模板中获取当前登录用户 {{app.user.username}}
$app['twig.app'] = $app->extend('twig.app', function ($twigApp, $app) {


    $twigApp['user'] = function () {
        return \Service\Auth::getUser();
    };

    $twigApp['admin'] = function () {
        return \Service\AuthAdmin::getUser();
    };

    $twigApp['lang'] = function () {
        $lang = \Entity\Lang::getLang();
        $entity = \Entity\Lang::findOne(['key' => $lang]);
        return $entity;
    };

    $twigApp['controller'] = function () use ($app) {

        $pathinfo = \Leaf\Application::$app['request']->getPathInfo();
        $r = explode('/', $pathinfo);
        //$controller == 'admin/setting';
        return $r[1] . '/' . $r[2];
    };

    return $twigApp;
});

//数据库连接配置
$app['db.config'] = array(
    'host' => '127.0.0.1',
    'database' => 'demo',
    'username' => 'root',
    'password' => 'root',
    'charset' => 'utf8',
    'tablePrefix' => 'pre_',
);

if (file_exists(__DIR__ . '/app-local.php')) {
    require __DIR__ . '/app-local.php';
}

//事件
include __DIR__ . '/event.php';
