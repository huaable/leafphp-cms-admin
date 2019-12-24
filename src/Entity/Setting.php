<?php

namespace Entity;

use Carbon\Carbon;
use Leaf\BaseObject;
use Leaf\Session;
use PFinal\Database\ModelTrait;

/**
 * Setting
 *
 * @property integer $id
 * @property string  $name
 * @property string  $key
 * @property string  $value
 * @property string  $created_at
 * @property string  $updated_at
 */
class Setting extends BaseObject
{
    use ModelTrait;

    public static function labels()
    {
        return [
            'id' => 'Id',
            'name' => '名称',
            'key' => '健',
            'value' => '值',
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
            ['name' => '网站标题', 'key' => 'title',],
            ['name' => '网站关键字', 'key' => 'keywords',],
            ['name' => '网站描述', 'key' => 'description',],
            ['name' => '版权所有', 'key' => 'copyright',],
            ['name' => '备案号', 'key' => 'recordcode',],
            ['name' => '技术支持', 'key' => 'powered',],
            ['name' => '邮箱', 'key' => 'email',],
            ['name' => '地址', 'key' => 'address',],
            ['name' => '联系电话', 'key' => 'phone',],
        ];

        foreach ($dataList as $data) {
            $data['lang'] = $lang;
            $data['created_at'] = $data['updated_at'] = Carbon::now();
            self::insert($data);
        }

        return true;
    }
}