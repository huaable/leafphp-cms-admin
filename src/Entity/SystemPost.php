<?php

namespace Entity;

use Carbon\Carbon;
use Leaf\Application;
use Leaf\BaseObject;
use Leaf\Url;
use PFinal\Database\ModelTrait;

/**
 * 系统文章
 *
 * @property integer $id
 * @property string  $lang
 * @property string  $key
 * @property string  $filename
 * @property string  $title
 * @property string  $content
 * @property integer $view_count
 * @property string  $created_at
 * @property string  $updated_at
 */
class SystemPost extends BaseObject
{
    use ModelTrait;

    //    const STATUS_YES = 10;//有效
    //    const STATUS_NO = 20;//禁用
    const KEY_ABOUT = 'about';//禁用

    public static function labels()
    {
        return [
            'id' => 'ID',
            'lang' => '语言',
            'key' => '标识',
            'filename' => '配图',
            'title' => '标题',
            'content' => '正文',
            'view_count' => '阅读数',
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
     * @param bool $returnAll
     * @return array|string
     */
    public function keyAlias($returnAll = false)
    {
        $map = [
            self::KEY_ABOUT => self::KEY_ABOUT . ' : 关于我们',
        ];

        if ($returnAll) {
            return $map;
        }

        return isset($map[$this->key]) ? $map[$this->key] : '';
    }

    /**
     * 图片文件保存目录
     * @return string
     */
    public static function uploadPath()
    {
        return 'uploads/system-post';
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

    /**
     * 初始化模块
     */
    public static function install()
    {
        $lang = Lang::getLang();

        $find = self::where('lang=:lang', [':lang' => $lang])->findOne();

        if ($find != null) {
            return true;
        }

        $dataList = [
            ['title' => '关于我们', 'key' => self::KEY_ABOUT,],
        ];

        foreach ($dataList as $data) {
            $data['lang'] = $lang;
            $data['created_at'] = $data['updated_at'] = Carbon::now();
            self::insert($data);
        }

        return true;
    }
}