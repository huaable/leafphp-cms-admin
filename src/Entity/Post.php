<?php

namespace Entity;

use Carbon\Carbon;
use Leaf\Application;
use Leaf\BaseObject;
use Leaf\Url;
use PFinal\Database\ModelTrait;
use Service\UploadManagerTrait;

/**
 * 文章
 *
 * @property integer $id
 * @property integer $category_id
 * @property string  $author
 * @property string  $title
 * @property string  $summary
 * @property string  $filename
 * @property string  $content
 * @property integer $view_count
 * @property integer $status
 * @property integer $recommend
 * @property string  $created_at
 * @property string  $updated_at
 */
class Post extends BaseObject
{
    use ModelTrait, UploadManagerTrait;

    const STATUS_YES = 10;//发布
    const STATUS_NO = 20;//隐藏

    const RECOMMEND_NO = 10;//否
    const RECOMMEND_YES = 20;//置顶

    const SYSTEM_POST_MAX_ID = 1000;//系统文章最大ID


    public static function labels()
    {
        return [
            'id' => 'ID',
            'category_id' => '文章分类',
            'author' => '作者',
            'title' => '标题',
            'summary' => '简介',
            'filename' => '配图',
            'content' => '正文',
            'view_count' => '阅读数',
            'status' => '发布状态',
            'recommend' => '置顶',
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
            self::RECOMMEND_YES => '置顶',
            self::RECOMMEND_NO => '否',
        ];

        if ($returnAll) {
            return $map;
        }

        return isset($map[$this->recommend]) ? $map[$this->recommend] : '';
    }


    /**
     * 显示分类名
     * @return string
     */
    public function showCategory()
    {
        $category = PostCategory::findByPk($this->category_id);
        return $category ? $category->name : '';
    }

    /**
     * 图片文件保存目录
     * @return string
     */
    public static function uploadPath()
    {
        return 'uploads/post';
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

        //        $find = self::where('lang=:lang', [':lang' => $lang])->findOne();
        //
        //        if ($find != null) {
        //            return true;
        //        }
        //
        //        $dataList = [
        //            ['title' => '关于我们'],
        //        ];
        //
        //        foreach ($dataList as $data) {
        //            $data['lang'] = $lang;
        //            $data['created_at'] = $data['updated_at'] = Carbon::now();
        //            self::insert($data);
        //        }

        return true;
    }


}