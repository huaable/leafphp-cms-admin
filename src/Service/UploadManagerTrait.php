<?php

namespace Service;

use Carbon\Carbon;
use Entity\Lang;
use Entity\Post;
use Entity\UploadManager;
use Leaf\Application;
use Leaf\BaseObject;
use Leaf\Url;
use Leaf\Util;
use PFinal\Database\ModelTrait;


/**
 * 模型的资源管理
 *
 * Trait UploadManagerTrait
 * @package Service
 */
trait UploadManagerTrait
{

    /**
     * 资源文件保存目录
     * @return string
     */
    //    public static function uploadPath()
    //    {
    //        return 'uploads/post';
    //    }

    /**
     * 整理清理资源
     * 包括 img 中引用的图片、字段中引用的图片
     *
     * @param  BaseObject|array $entity
     * @param  array            $fields 检查字段(默认全部检查字符类型字段)
     */
    public static function clear($entity, $fields = [])
    {

        if (gettype($entity) == 'array') {
            foreach ($entity as $item) {
                self::clearItem($item, $fields);
            }
        }

        if (gettype($entity) == 'object') {
            self::clearItem($entity, $fields);
        }

    }


    /**
     * 清理整理
     * @param  BaseObject $entity
     * @param array       $fields
     */
    protected static function clearItem($entity, $fields = [])
    {

        //最新引用记录
        $useFiles = self::getFiles($entity, $fields);
        //旧记录
        $originFiles = Util::arrayColumn(self::getOriginFiles($entity), 'filename');

        //处理新增
        $diffInsert = array_diff($useFiles, $originFiles);

        foreach ($diffInsert as $filename) {


            //查找临时文件
            $find = self::where([
                'model' => $entity::className(),
                'filename' => $filename,
            ])->findOne();

            //引用图片  与 model_id 关联起来
            if ($find == null) {
                $data = [
                    'model' => $entity::className(),
                    'model_id' => $entity->id,
                    'filename' => $filename,
                    'status' => self::STATUS_YES,
                ];
                $data['lang'] = Lang::getLang();
                $data['created_at'] = $data['updated_at'] = Carbon::now();
                self::insert($data);

            } else {
                //引用图片  与 model_id 关联起来
                $data = [
                    'model' => $entity::className(),
                    'model_id' => $entity->id,
                    'filename' => $filename,
                    'status' => self::STATUS_YES,
                    'updated_at' => Carbon::now(),
                ];

                self::wherePk($find->id)->update($data);
            }
        }

        //软删除
        $diffDel = array_diff($originFiles, $useFiles);
        self::whereIn('filename', $diffDel)->update([
            'model' => $entity::className(),
            'model_id' => $entity->id,
            'status' => self::STATUS_NO,
            'updated_at' => Carbon::now()
        ]);


    }


    /**
     * 添加临时的可清理的资源记录 返回
     * @param $className
     * @param $filename
     * @return bool|UploadManager
     */
    public static function addTemp($className, $filename)
    {

        $data = [
            'model' => $className,
            'model_id' => 0,
            'filename' => $filename,
            'status' => UploadManager::STATUS_NO,
        ];
        $data['lang'] = Lang::getLang();
        $data['created_at'] = $data['updated_at'] = Carbon::now();
        return UploadManager::insertGetId($data);
    }


    /**
     * 删除资源
     * @return mixed
     */
    public function deleteFile()
    {

        $className = $this->model;
        if (isset(Application::$app['storage'])) {

            Application::$app['storage']->delete($className::uploadPath() . DIRECTORY_SEPARATOR . $this->filename);
        } else {
            $file = Application::$app['path'] . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . $className::uploadPath() . DIRECTORY_SEPARATOR . $this->filename;
            if (file_exists($file)) {
                @unlink($file);
            }
        }


        return self::wherePk($this->id)->delete();
    }


    /**
     * 获取当前引用到的图片
     * @param $entity
     * @param $fields
     * @return array
     */
    protected static function getFiles($entity, $fields)
    {
        $className = $entity::className();

        //再次查询，对象是否删除了
        $entity = $className::findByPk($entity->id);
        if ($entity == null) {
            //全部软删除
            return [];
        }

        $arr = [];

        //查找 html img 的引用
        foreach ($entity as $field => $value) {
            //检查每一个字段，匹配找到所有上传附件的文件名

            if (!empty($fields) && !in_array($field, $fields)) {
                continue;
            }
            if (gettype($entity->$field) == 'string') {

                //201912/15/6014ff0b0b15fc8a930e6561c57b5f1d.png
                $pregRule = "/^([0-9a-z\/]*(?:(\.jpg|\.jpeg|\.png|\.gif|\.bmp)))$/";
                preg_match_all($pregRule, $entity->$field, $matches);
                if (!empty($matches[1])) {
                    $arr = array_merge($arr, $matches[1]);
                    continue;
                }

                //匹配img标签 src 引用图片
                $pregRule = "/<[img|IMG].*?src=[\'|\"][0-9a-z\/\:]*?" . preg_quote($className::uploadPath(), '/') . "\/(.*?(?:[\.jpg|\.jpeg|\.png|\.gif|\.bmp]))[\'|\"].*?[\/]?>/i";
                preg_match_all($pregRule, $entity->$field, $matches);

                if (!empty($matches[1])) {
                    foreach ($matches[1] as $filename) {
                        //排除 带有.. 的路径
                        if (strpos($filename, '..') === false) {
                            $arr[] = $filename;
                        }
                    }
                    continue;
                }


            }
        }
        return $arr;

    }


    /**
     * 获取上传旧的图片
     * @param $entity
     * @return mixed
     */
    protected static function getOriginFiles($entity)
    {
        return self::where(
            'model=:model and model_id=:model_id and status=:status',
            [
                ':model' => $entity::className(),
                ':model_id' => $entity->id,
                ':status' => self::STATUS_YES
            ]
        )->findAll();

    }


    /**
     * 拼接路径字符串
     * @param $uploadPath
     * @param $filename
     * @return string
     */
    protected static function usePath($uploadPath, $filename)
    {
        return Application::$app['path'] . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . $uploadPath . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * 显示完整URL路径
     * @return string
     */
    public function filenameUrl()
    {
        $className = $this->model;

        return Url::to('', true) . $className::uploadPath() . DIRECTORY_SEPARATOR . $this->filename;
    }

    /**
     * 说明
     * @return string
     */
    public function showDesc()
    {

        if ($this->model_id == 0) {
            //临时文件可以存在 temp 或 uploads ，富文本编辑器上传直接存在 uploads
            return '临时文件';
        }

        if ($this->status == self::STATUS_NO) {
            return '放弃引用';
        }

        if ($this->status == self::STATUS_YES) {
            return '正在引用';
        }
    }


}