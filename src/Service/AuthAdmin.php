<?php

namespace Service;

use Entity\Admin;
use Leaf\Auth\AuthManager;
use Leaf\DB;

class AuthAdmin extends AuthManager
{

    /**
     * 通过id取回用户
     *
     * @param  int $id
     * @return Admin
     */
    protected static function retrieveById($id)
    {
        return DB::table(Admin::tableName())->where(['status' => Admin::STATUS_ENABLE])->asEntity(Admin::className())->findByPk($id);
    }

    /**
     * 通过token取回用户
     *
     * @param string $token
     * @return Admin|null
     */
    public static function retrieveByToken($token)
    {
        return DB::table(Admin::tableName())->where('remember_token=?', [$token])->asEntity(Admin::className())->findOne();
    }

    /**
     * 更新token
     *
     * @param int $userId
     * @param string $token
     * @return bool
     */
    public static function saveRememberToken($userId, $token)
    {
        return 1 == DB::table(Admin::tableName())->where('id=?', [$userId])->update(['remember_token' => $token]);
    }

    /**
     * 登录前置操作，如果返回false,将允许登录
     * @param Admin $admin
     * @param bool $fromRemember 是否来自记住我功能
     * @return bool
     */
    public static function beforeLogin($admin, $fromRemember)
    {
        return $admin->status == Admin::STATUS_ENABLE;
    }
}