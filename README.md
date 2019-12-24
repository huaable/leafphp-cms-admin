[文档](https://github.com/pfinal/leaf)

安装

	composer install

数据库在 document/db.sql

支持多语言站点，目前只有一个后台AdminBundle

src/AdminBundle/Controller/AuthController.php 
	 
	 /**
	     * 是否开启注册功能
	     *
	     * @return bool
	     */
	    protected function enableRegister()
	    {
	        return true;//注册好后台用户后，建议改成false禁止注册。
	    }


ApiBundle 按需求自定义吧。


vender/leafphp 使用定制版（主要修改了代码生成器、以及其他）
https://github.com/huaable/leaf

