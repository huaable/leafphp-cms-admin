<?php

namespace Entity;

use Carbon\Carbon;
use Leaf\Application;
use Leaf\BaseObject;
use Leaf\Session;
use Leaf\Url;
use PFinal\Database\ModelTrait;

/**
 * 语言站点
 *
 * @property integer $id
 * @property string  $name
 * @property string  $key
 * @property string  $flag
 * @property string  $created_at
 * @property string  $updated_at
 */
class Lang extends BaseObject
{
    use ModelTrait;

    //    const STATUS_YES = 10;//有效
    //    const STATUS_NO = 20;//禁用

    public static function labels()
    {
        return [
            'id' => 'ID',
            'name' => '语言名称',
            'key' => '唯一标识',
            'flag' => '图标',
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
     * 图片文件保存目录
     * @return string
     */
    public static function uploadPath()
    {
        return 'uploads/lang';
    }

    /**
     * 配图完整URL
     * @return string
     */
    public function url()
    {
        $app = Application::$app;
        $upFile = self::uploadPath() . DIRECTORY_SEPARATOR . $this->flag;
        if (isset($app['storage'])) {
            return $app['storage']->url($upFile);
        }
        return Url::to('', true) . $upFile;
    }


    /**
     * 后台管理员注册初始化
     * @param string $lang
     * @return bool
     */
    public static function install($lang = '')
    {
        $find = self::where('`key`=:key ', [':key' => $lang])->findOne();

        if ($find == null) {

            $lang = 'cn';

            $data = ['name' => '简体中文', 'key' => $lang, 'flag' => ''];

            $data['created_at'] = $data['updated_at'] = Carbon::now();
            if (self::insert($data)) {
                $find = self::where('`key`=:key ', [':key' => $lang])->findOne();
            } else {
                return false;
            }
        }

        //切换语言
        self::setLang($find->key);
        //初始化模块
        Setting::install();
        SystemPost::install();
        Post::install();
        return true;
    }

    /**
     * 设置语言
     * @param string $key
     */
    public static function setLang($key)
    {
        Session::set('lang', $key);
    }

    /**
     * 当前语言
     * @return mixed
     */
    public static function getLang()
    {
        $lang = Session::get('lang');

        if ($lang == null) {
            $one = self::findOne();
            $lang = $one->key;
            self::setLang($lang);
        }

        return $lang;
    }
}