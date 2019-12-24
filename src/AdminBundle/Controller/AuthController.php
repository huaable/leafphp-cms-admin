<?php

namespace AdminBundle\Controller;



use Entity\Admin;
use Entity\Lang;
use Leaf\Application;
use Leaf\Cache;
use Leaf\DB;
use Leaf\Exception\HttpException;
use Leaf\Json;
use Leaf\Mail;
use Leaf\Redirect;
use Leaf\Request;
use Leaf\Route;
use Leaf\Session;
use Leaf\Template\Twig;
use Leaf\Url;
use Leaf\Util;
use Leaf\Validator;
use Leaf\View;
use Service\AuthAdmin;
use Util\SmsThrottle;

/**
 * 管理
 * @author  curd generator
 * @since   1.0
 */
class AuthController
{
    /**
     * 注册路由
     */
    public static function routes()
    {

        Route::any('admin/register', get_called_class() . '@register');
        Route::any('/', get_called_class() . '@login');
        Route::any('admin', get_called_class() . '@login');
        Route::any('admin/login', get_called_class() . '@login');
        Route::any('admin/logout', get_called_class() . '@logout');
        Route::any('admin/password/forgot', get_called_class() . '@forgot');
        Route::any('admin/password/reset', get_called_class() . '@reset');
        Route::any('admin/password/resetByMobile', get_called_class() . '@resetByMobile');
        Route::any('admin/password/sendSms', get_called_class() . '@sendSms');
    }



    /**
     * 是否开启注册功能
     *
     * @return bool
     */
    protected function enableRegister()
    {
        return true;
    }

    /**
     * 注册视图
     */
    protected function registerView()
    {
        return View::render('@AdminBundle/auth/register.twig');
    }

    /**
     * 登录视图
     */
    protected function loginView()
    {
        return View::render('@AdminBundle/auth/login.twig');
    }

    /**
     * 忘记密码视图
     */
    protected function forgotView()
    {
        return View::render('@AdminBundle/auth/password/forgot.twig');
    }


    /**
     * 重置密码视图
     */
    protected function resetView()
    {
        return View::render('@AdminBundle/auth/password/reset.twig');
    }

    /**
     * 重定向
     *
     * @param string $scene login|register|logout
     * @return string
     */
    protected function redirectTo($scene)
    {
        return Url::to('/admin/setting');
    }

    /**
     * 生成密码哈希
     * @param string $password
     * @return string
     */
    protected function passwordHash($password)
    {
        //return password_hash($password, PASSWORD_BCRYPT);
        return md5($password);
    }

    /**
     * 验证密码哈希
     * @param string $password
     * @param string $passwordHash
     * @return bool
     */
    protected function passwordVerify($password, $passwordHash)
    {
        //return password_verify($password, $passwordHash);
        if (strlen($password) < 4 || strlen($password) > 50) {
            return false;
        }
        return $this->passwordHash($password) === $passwordHash;
    }

    /**
     * 注册
     */
    public function register(Request $request)
    {
        //已登录状态，直接跳转
        if (AuthAdmin::check()) {
            return Redirect::to($this->redirectTo('register'));
        }

        if ($request->isMethod('get')) {
            return $this->registerView();
        }

        if (!$this->enableRegister()) {
            Session::setFlash('message', '系统未开放注册功能');
            return $this->registerView();
        }

        $data = $request->all();

        $rule = [
//            [['email', 'nickname'], 'trim'],
            [['email',], 'trim'],
            [['email', 'password', 'password_confirmation'], 'required'],
            ['email', 'email'],
//            ['nickname', 'string', 'length' => [2, 10]],
            [['password', 'password_confirmation'], 'string', 'length' => [6, 20]],
            ['password', 'compare', 'compareValue' => $data['password_confirmation'], 'message' => '两次密码必须相同'],
            ['email', 'unique', 'table' => Admin::tableName(), 'field' => 'email'],
        ];

        $labels = [
            'email' => '邮箱',
            'password' => '密码',
            'password_confirmation' => '确认密码',
        ];

        if (!Validator::validate($data, $rule, $labels)) {
            Session::setFlash('message', Validator::getFirstError());
            return $this->registerView();
        }

        $data['password_hash'] = $this->passwordHash($data['password']);
        $data['status'] = Admin::STATUS_ENABLE;
        $data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');

        unset($data['password']);
        unset($data['password_confirmation']);

        $adminId = DB::table(Admin::tableName())->insertGetId($data);

        if ($adminId > 0) {
            //安装默认语言
            Lang::install();
            AuthAdmin::loginUsingId($adminId);
            return Redirect::to($this->redirectTo('register'));
        } else {
            Session::setFlash('message', '系统错误，注册失败');
            return $this->registerView();
        }
    }

    /**
     * 登录
     * @Route admin/login
     */
    public function login(Request $request)
    {
        //已登录状态，直接跳转
        if (AuthAdmin::check()) {
            return Redirect::to($this->redirectTo('login'));
        }

        if ($request->isMethod('get')) {
            return $this->loginView();
        }

        $query = DB::table(Admin::tableName())
            ->asEntity(Admin::className());

        $loginId = $request->get('loginId', '');

        //同时支持邮箱手机和帐号登录
        if (Util::isEmail($loginId)) {
            $query->where('email=?', [$loginId]);
        } else if (Util::isMobile($loginId)) {
            $query->where('mobile=?', [$loginId]);
        } else {
            $query->where('username=?', [$loginId]);
        }

        /** @var $admin Admin */
        $admin = $query->where(['status' => Admin::STATUS_ENABLE])->findOne();

        if ($this->passwordVerify($request->get('password'), $admin['password_hash'])) {

            AuthAdmin::login($admin, $request->get('remember'));

            Session::set('admin_last_login_at', @date('Y-m-d H:i:s'));

            return Redirect::to(Session::getFlash('returnUrl', $this->redirectTo('login')));
        }

        Session::setFlash('message', '用户名或密码不匹配');

        return $this->loginView();
    }

    /**
     * 注销
     */
    public function logout()
    {
        AuthAdmin::logout();
        return Redirect::to($this->redirectTo('admin/logout'));
    }

    /**
     * 邮件找回密码
     */
    public function forgot(Request $request)
    {

        if (AuthAdmin::check()) {
            return Redirect::to($this->redirectTo('admin/login')); //已登录
        }

        if ($request->isMethod('get')) {
            return $this->forgotView();
        }

        $email = $request->get('email');

        if (!Util::isEmail($email)) {

            if (Util::isMobile($email)) {
                return Redirect::to(Url::to('admin/password/resetByMobile', ['mobile' => $email]));
            }

            Session::setFlash('message', '请输入正确邮箱');
            return $this->forgotView();
        }

        $admin = DB::table(Admin::tableName())->where('email=?', [$email])->findOne();

        if ($admin == null) {
            Session::setFlash('message', '邮箱未注册');
            return $this->forgotView();
        }

        $token = strtolower(str_replace('-', '', Util::guid()));
        $bool = DB::table('admin_password_reset')->insert(['email' => $admin['email'], 'token' => $token, 'created_at' => date('Y-m-d H:i:s')]);
        if (!$bool) {
            Session::setFlash('message', '系统错误，请稍后再试');
            return $this->forgotView();
        }

        $data = [
            'site' => '',//站点的名称
            'username' => $admin['nickname'] ? $admin['nickname'] : $admin['username'],
            'url' => Url::to('admin/password/reset', ['token' => $token], true),
        ];



        $bool = Mail::send($email, '密码重置连接', View::render('@AdminBundle/auth/password/email.twig', $data));

        if ($bool) {
            Session::setFlash('message', '邮件发送成功，请根据邮件提示操作');
        } else {
            Session::setFlash('message', '邮件发送失败');
        }

        return $this->forgotView();
    }

    /**
     * 发送手机短信验证码
     *
     * method
     *      POST
     *
     * params
     *      mobile         手机号码
     *      captcha_code   图形验证码,默认传入空字符串即可。当频繁调用时,此字段启用
     *      captcha_key    图形验证码key,默认传入空字符串即可。调用`api/image/captcha`接口得到
     *
     * response
     *      {"status": true,  "data": "发送成功"}
     *
     *      图片验证码无效
     *      {"status": false, "data": "图片验证码错误", "code": "INVALID_CAPTCHA"}
     *
     */
    public function sendSms(Request $request, Application $app)
    {
        $mobile = $request->get('mobile');
        if (!Util::isMobile($mobile)) {
            return Json::renderWithFalse('手机格式不正确');
        }

        $ips = $request->getClientIps();
        $extra = ['ip' => end($ips)];

        $smsThrottle = SmsThrottle::instance();

        //是否启用图型验证码
        if ($smsThrottle->enableCaptcha($mobile, $extra)) {

            $bool = \Util\Captcha::validateCode($request->get('captcha_key', ''), $request->get('captcha_code', ''));
            if (!$bool) {
                return Json::renderWithFalse('图片验证码错误', 'INVALID_CAPTCHA');
            }
        }

        //同一号码发送频率检测
        if (!$smsThrottle->check($mobile, $extra, $error)) {
            return Json::renderWithFalse($error);
        }

        $code = rand(100000, 999999);

        //开发环境不发送
        $isSend = $app->getEnv() != 'dev';
        //$isSend = false;

        if ($isSend) {
            $error = '';
            $bool = $app['sms']->sendCode($mobile, $code, $error);
            if ($bool) {
                $message = '发送成功，请查收短信';
            } else {
                $message = '发送失败 ' . $error;
                return Json::renderWithFalse($message);
            }
        } else {
            $message = '您的验证码为:' . $code . ' [短信服务未启用]';
        }

        //过期时间(秒)
        $expiresIn = 60 * 10;

        $key = str_replace('-', '', Util::guid());
        Cache::set('verification:' . $key, ['mobile' => (string)$mobile, 'verificationCode' => (string)$code], $expiresIn);

        return Json::renderWithTrue(['verificationKey' => $key, 'message' => $message, 'expires_in' => $expiresIn]);
    }

    /**
     * 手机短信重置密码
     */
    public function resetByMobile(Request $request)
    {
        if ($request->isMethod('post')) {

            $mobile = $request->get('mobile');
            $password = $request->get('password');

            //验证码
            $verificationKey = (string)$request->get('verificationKey', '');
            $verificationCode = (string)trim($request->get('verificationCode', ''));

            //对比服务端存放的验证码信息
            $mobileAndCode = Cache::get('verification:' . $verificationKey);
            if ($mobileAndCode == null
                || $mobileAndCode['mobile'] !== $mobile
                || empty($verificationCode)
                || $verificationCode !== $mobileAndCode['verificationCode']) {

                return Json::renderWithFalse('短信验证码无效', 'INVALID_VERIFICATION_CODE');
            }
            Cache::delete('verification:' . $verificationKey);

            /** @var Admin $admin */
            $admin = Admin::where('mobile=?', $mobile)->findOne();
            if ($admin == null) {
                return Json::renderWithFalse('手机号未注册');
            }

            if ($admin->status != Admin::STATUS_ENABLE) {
                return Json::renderWithFalse('用户状态异常');
            }

            $data = [
                'password_hash' => static::passwordHash($password),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if (DB::table(Admin::tableName())->where('id=?', [$admin->getId()])->update($data)) {
                return Json::renderWithTrue('操作成功');
            } else {
                return Json::renderWithFalse('重置密码失败');
            }

        }

        return View::render('@AdminBundle/auth/password/mobile.twig');
    }

    /**
     * 重置密码
     */
    public function reset(Request $request)
    {
        if (AuthAdmin::check()) {
            return Redirect::to($this->redirectTo('login')); //已登录
        }

        if ($request->isMethod('get')) {
            return $this->resetView();
        }

        $token = $request->get('token');
        $email = $request->get('email');
        $password = $request->get('password');
        $passwordConfirm = $request->get('password_confirm');

        if ($password !== $passwordConfirm) {
            Session::setFlash('message', '两次密码不匹配');
            return $this->resetView();
        }

        $info = DB::table('admin_password_reset')->where('token=?', [$token])->findOne();

        if ($info == null || time() - strtotime($info['created_at']) > 60 * 60 * 24) {
            Session::setFlash('message', '连接已失效');
            return $this->resetView();
        }

        if ($email !== $info['email']) {
            Session::setFlash('message', '邮箱错误');
            return $this->resetView();
        }

        $admin = DB::table(Admin::tableName())->asEntity(Admin::className())->where('email=?', [$email])->findOne();
        if ($admin == null) {
            throw new HttpException(500, '无此用户信息');
        }

        $data = [
            'password_hash' => static::passwordHash($password),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (DB::table(Admin::tableName())->where('id=?', [$admin->getId()])->update($data)) {

            // 让"记住我"失效
            AuthAdmin::saveRememberToken($admin->getId(), '');

            // 清空token
            DB::table('admin_password_reset')->where('token=?', [$token])->delete();

            Session::setFlash('message', '重置密码成功');
        } else {
            Session::setFlash('message', '系统错误');
        }

        return $this->resetView();
    }

    protected function init(){


    }


}
