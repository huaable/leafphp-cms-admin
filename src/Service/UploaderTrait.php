<?php

namespace Service;

use Entity\Post;
use Entity\SystemPost;
use Entity\UploadManager;
use Leaf\Application;
use Leaf\Cache;
use Leaf\Log;
use Leaf\UploadedFile;
use Leaf\Url;
use Leaf\Util;

/**
 * 文件上传
 *
 * use \Service\UploaderTrait;
 *
 * //上传到临时目录
 * return Json::render($this->uploadToPath('file'));
 *
 *
 * //配置存储目录 先安装组件 composer require pfinal/storage
 * $app['storage'] = function () use ($app) {
 *   return new \PFinal\Storage\Local([
 *       'basePath' => $app['path'] . '/web/',
 *       'baseUrl' => \Leaf\Url::asset('/', true),
 *   ]);
 * };
 *
 * @author  xiaohua
 */
trait UploaderTrait
{

    /**
     * @param string $name
     * @param array  $config
     * @param Post   $className 上传记录保存在 upload_manager 中
     * @return mixed
     */
    protected function uploadToPath($name = 'file', $config = array(), $className = '')
    {
        if (class_exists($className)) {
            $config['basePath'] = $className::uploadPath();
        }
        $up = $this->uploadToTemp($name, $config);
        if ($up['status']) {
            if (class_exists($className)) {
                //数据库添加上传记录
                UploadManager::addTemp($className, $up['file']['name']);
            }
        }
        return $up;
    }


    /**
     * 上传文件到目录
     * @param string $name
     * @param array  $config 例如
     * $config = [
     *     'thumb' => [
     *         'm' => array('w' => 400, 'h' => 400, 'resize' => true),
     *     ],
     * ];
     * @return array
     */
    protected function uploadToTemp($name = 'file', $config = array())
    {
        $config += array(
            'basePath' => 'temp',
            'rootPath' => Application::$app['path'] . '/web/',
            'baseUrl' => Url::asset('/'),
            'thumb' => array(),
        );

        if (isset(Application::$app['storage'])) {
//            $config['basePath'] = 'temp';
            $config['rootPath'] = Application::$app->getRuntimePath() . '/';
        } else {
            //预览用临时图片
//            $config['thumb'] = $config['thumb'] + array('_temp' => array('w' => 400, 'h' => 400, 'resize' => true));
        }

        $up = new UploadedFile($config);

        if ($up->doUpload($name)) {

            //上传后的文件信息
            $file = $up->getFile();

            //生成fileKey传递给前端
            $file['fileKey'] = Util::guid();

            Cache::set($file['fileKey'], $file, 60 * 10);
            Cache::set($file['fileKey'] . '.config', $config, 60 * 10);

            //云存储
            if (isset(Application::$app['storage'])) {
                $tempFile = rtrim($config['rootPath'], '/\\') . DIRECTORY_SEPARATOR . rtrim($file['basePath'], '/\\') . DIRECTORY_SEPARATOR . $file['name'];
                $bool = Application::$app['storage']->put($file['basePath'] . $file['name'], file_get_contents($tempFile));
                $file['url'] = Application::$app['storage']->url($file['basePath'] . $file['name']);
//                $file['thumbnailUrl'] = ['_temp' => $file['url']];

                @unlink($tempFile);

                if ($bool) {
                    return array('status' => true, 'file' => $file);
                } else {
                    Log::debug(Application::$app['storage']->error());
                    return array('status' => false, 'message' => Application::$app['storage']->error());
                }
            }

            //{"status":true,"file":{"originalName":"touxiang2.jpg","name":"201708/16/5993ad13c9823.jpg","basename":"5993ad13c9823.jpg","basePath":"temp/","subPath":"201708/16/","size":179327,"type":"image/jpeg","url":"/yuntu/web/temp/201708/16/5993ad13c9823.jpg","thumbnailUrl":{"_temp":"/yuntu/web/temp/201708/16/_temp/5993ad13c9823.jpg"},"fileKey":"1011A64B-CE45-6744-C326-1BE83FDD3AA3"}}
            return array('status' => true, 'file' => $file);

        } else {
            return array('status' => false, 'message' => $up->getError());
        }
    }

    /**
     * 返回 文件名
     * @param        $fileKey
     * @param string $dir
     * @return string
     */
    protected function fileKeyToFilename($fileKey, $dir = 'uploads')
    {
        $fileKey = trim($fileKey);

        if (empty($fileKey) || ($fileInfo = Cache::get($fileKey)) == null) {
            return '';
        }

        Cache::delete($fileKey);
        Cache::delete($fileKey . '.config');

        //云存储
        if (isset(Application::$app['storage'])) {
            $bool = Application::$app['storage']->rename($fileInfo['basePath'] . $fileInfo['name'], rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $fileInfo['name']);
            if ($bool) {
                return $fileInfo['name'];
            } else {
                Log::debug(Application::$app['storage']->error());
                return '';
            }
        }

        return $fileInfo['name'];

    }

}
