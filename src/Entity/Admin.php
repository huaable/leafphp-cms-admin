<?php

namespace Entity;

use Leaf\BaseObject;
use PFinal\Database\ModelTrait;

/**
 * 后台管理员
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $nickname
 * @property string $avatar
 * @property string $email
 * @property string $mobile
 * @property string $remember_token
 * @property integer $status
 * @property string $created_at
 * @property string $updated_at
 */
class Admin extends \Leaf\Auth\User
{
    use ModelTrait;

    //状态
    const STATUS_ENABLE = 10;   // 有效
    const STATUS_DISABLE = 20;  // 禁用

    public static function labels()
    {
        return [
            'id' => 'Id',
            'username' => '帐号',
            'password_hash' => 'PasswordHash',
            'nickname' => '昵称',
            'avatar' => '头像',
            'email' => '邮箱',
            'mobile' => '手机',
            'remember_token' => '记住我',
            'status' => '状态',
            'created_at' => '新增时间',
            'updated_at' => '修改时间',
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
}