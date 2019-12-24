-- noinspection SqlNoDataSourceInspectionForFile

--
-- DROP TABLE IF EXISTS pre_user;
-- CREATE TABLE IF NOT EXISTS pre_user(
--   id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'ID',
--   username VARCHAR(50) NOT NULL DEFAULT '' COMMENT '帐号',
--   password_hash VARCHAR(255) NOT NULL DEFAULT '',
--   nickname VARCHAR(255) NOT NULL DEFAULT '' COMMENT '昵称',
--   avatar VARCHAR(255) NOT NULL DEFAULT '' COMMENT '头像',
--   email VARCHAR(255) NOT NULL DEFAULT '' COMMENT '邮箱',
--   mobile VARCHAR(50) NOT NULL DEFAULT '' COMMENT '手机',
--   remember_token VARCHAR(255) DEFAULT '' NOT NULL COMMENT '记住我',
--   status INT NOT NULL DEFAULT 10 COMMENT '状态',
--
--   created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '新增时间',
--   updated_at DATETIME COMMENT '修改时间',
--   KEY username (username(20)),
--   KEY email (email(20)),
--   KEY mobile (mobile),
--   KEY remember_token (remember_token(20))
-- )ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1001 COMMENT '用户';
--
--
-- DROP TABLE IF EXISTS pre_password_reset;
-- CREATE TABLE `pre_password_reset` (
--   `email` varchar(255)  NOT NULL,
--   `token` varchar(255)  NOT NULL,
--   created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '新增时间',
--   KEY email (email(20)),
--   KEY token (token(20))
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '邮箱重置密码';
--
--
-- DROP TABLE IF EXISTS pre_user_wechat;
-- CREATE TABLE IF NOT EXISTS pre_user_wechat(
--   `id` INT AUTO_INCREMENT PRIMARY KEY ,
--   `user_id` INT DEFAULT 0 NOT NULL,
--   `appid` VARCHAR(255) NOT NULL DEFAULT '',
--   `openid` VARCHAR(255) NOT NULL DEFAULT '',
--   created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '新增时间',
--   updated_at DATETIME COMMENT '修改时间',
--   unique (user_id, appid, openid)
-- )ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1001 COMMENT='绑定微信';

DROP TABLE IF EXISTS pre_admin;
CREATE TABLE IF NOT EXISTS pre_admin(
  id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'ID',
  username VARCHAR(50) NOT NULL DEFAULT '' COMMENT '帐号',
  password_hash VARCHAR(255) NOT NULL DEFAULT '',
  nickname VARCHAR(255) NOT NULL DEFAULT '' COMMENT '昵称',
  avatar VARCHAR(255) NOT NULL DEFAULT '' COMMENT '头像',
  email VARCHAR(255) NOT NULL DEFAULT '' COMMENT '邮箱',
  mobile VARCHAR(50) NOT NULL DEFAULT '' COMMENT '手机',
  remember_token VARCHAR(255) DEFAULT '' NOT NULL COMMENT '记住我',
  status INT NOT NULL DEFAULT 10 COMMENT '状态',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '新增时间',
  updated_at DATETIME COMMENT '修改时间',
  KEY username (username(20)),
  KEY email (email(20)),
  KEY mobile (mobile),
  KEY remember_token (remember_token(20))
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1001 COMMENT '管理员';


DROP TABLE IF EXISTS pre_admin_password_reset;
CREATE TABLE `pre_admin_password_reset` (
  `email` varchar(255)  NOT NULL,
  `token` varchar(255)  NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '新增时间',
  KEY email (email(20)),
  KEY token (token(20))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '邮箱重置密码';


DROP TABLE IF EXISTS pre_lang;
CREATE TABLE `pre_lang` (
  `id` int(11)  NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(50) DEFAULT NULL COMMENT '语言名称',
  `key` varchar(50) DEFAULT NULL COMMENT '唯一标识',
  `flag` varchar(100) DEFAULT NULL COMMENT '图标',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1001 COMMENT '语言站点';


DROP TABLE IF EXISTS pre_setting;
CREATE TABLE `pre_setting` (
  `id` int(11)  NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `lang` varchar(50) DEFAULT NULL COMMENT '语言',
  `name` varchar(50) DEFAULT NULL COMMENT '名称',
  `key` varchar(50) DEFAULT NULL COMMENT '健',
  `value` varchar(255) DEFAULT NULL COMMENT '值',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1001 COMMENT '网站配置';


DROP TABLE IF EXISTS pre_system_post;
CREATE TABLE `pre_system_post` (
  `id` int(11)  NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `lang` varchar(50) DEFAULT NULL COMMENT '语言',
  `key` varchar(50) DEFAULT NULL COMMENT '标识', -- | 关于我们 about 、 服务协议 agreement 、 帮助文档 help 等
  `filename` varchar(100) DEFAULT '' COMMENT '配图',
  `title` varchar(50)  DEFAULT '' COMMENT '标题',
  `content` text  COMMENT '正文',
  `view_count` int(11)  DEFAULT 0 COMMENT '阅读数',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1001 COMMENT '系统文章';


DROP TABLE IF EXISTS pre_post_category;
CREATE TABLE `pre_post_category` (
  `id` int(11)  NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `lang` varchar(50) DEFAULT NULL COMMENT '语言',
  `name` varchar(50) DEFAULT NULL COMMENT '名称',
  `status` int(11) DEFAULT 10 COMMENT '状态 | 10 显示 | 20 隐藏',
  `parent_id` int(11)  NOT NULL DEFAULT 0  COMMENT '父级',
  `weight` int(11) DEFAULT 0 COMMENT '排序权重',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1001 COMMENT '文章分类';



DROP TABLE IF EXISTS pre_post;
CREATE TABLE `pre_post` (
  `id` int(11)  NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `lang` varchar(50) DEFAULT NULL COMMENT '语言',
  `category_id` int(11)  NOT NULL DEFAULT 0 COMMENT '文章分类',
  `status` int(11) DEFAULT 10 COMMENT '发布 | 10 发布 | 20 隐藏',
  `filename` varchar(100) DEFAULT '' COMMENT '配图',
  `recommend` int(11) DEFAULT 10  COMMENT '置顶 | 10 不推荐 | 20 推荐',
  `author` varchar(50)  DEFAULT '' COMMENT '作者',
  `title` varchar(50)  DEFAULT '' COMMENT '标题',
  `summary` varchar(225)  DEFAULT '' COMMENT '简介',
  `content` text  COMMENT '正文',
  `view_count` int(11)  DEFAULT 0 COMMENT '阅读数',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1001 COMMENT '文章';



DROP TABLE IF EXISTS `pre_product_category`;

CREATE TABLE `pre_product_category` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `lang` varchar(50) DEFAULT NULL COMMENT '语言',
  `name` varchar(50) DEFAULT NULL COMMENT '名称',
  `weight` int(11) DEFAULT '0' COMMENT '权重',
  `status` int(11) DEFAULT '10' COMMENT '状态 | 10 显示 | 20 隐藏',
  `parent_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父级',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='产品分类';



DROP TABLE IF EXISTS pre_product;
CREATE TABLE `pre_product` (
  `id` int(11)  NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `lang` varchar(50) DEFAULT NULL COMMENT '语言',
  `category_id` INT DEFAULT 0 NOT NULL COMMENT '产品分类',
  `status` int(11) DEFAULT 10 COMMENT '发布 | 10 发布 | 20 隐藏',
  `recommend` int(11) DEFAULT 10  COMMENT '置顶 | 10 不推荐 | 20 推荐',
  `filename` varchar(100) DEFAULT '' COMMENT '配图',
  `title` varchar(50) DEFAULT NULL COMMENT '名称',
  `description` varchar(255) DEFAULT NULL COMMENT '描述',
  `config` text  COMMENT '主要配置',
  `content` text  COMMENT '详细信息',
	`content1` text  COMMENT '规格参数',
	`content2` text  COMMENT '包装',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1001 COMMENT '产品';



DROP TABLE IF EXISTS pre_product_image;
CREATE TABLE `pre_product_image` (
  `id` int(11)  NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `lang` varchar(50) DEFAULT NULL COMMENT '语言',
  `product_id` int(11)  NOT NULL   COMMENT '产品',
  `filename` varchar(100) DEFAULT '' COMMENT '图片',
  `status` int(11) DEFAULT 10 COMMENT '发布 | 10 显示 | 20 删除',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1001 COMMENT '产品图片';


DROP TABLE IF EXISTS pre_product_banner;
CREATE TABLE `pre_product_banner` (
  `id` int(11)  NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `lang` varchar(50) DEFAULT NULL COMMENT '语言',
  `product_id` int(11)  NOT NULL DEFAULT 0  COMMENT '绑定产品ID',
  `filename` varchar(100) DEFAULT '' COMMENT 'banner图',
  `weight` int(11)  NOT NULL DEFAULT 0  COMMENT '排序权重',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1001 COMMENT '横幅广告';


DROP TABLE IF EXISTS pre_upload_manager;
CREATE TABLE `pre_upload_manager` (
  `id` int(11)  NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `lang` varchar(50) DEFAULT NULL COMMENT '语言',
  `model` varchar(50) NOT NULL COMMENT '来源表',
  `model_id` int(11)  NOT NULL DEFAULT 0  COMMENT '来源id',
  `filename` varchar(100) DEFAULT '' COMMENT '文件',
  `status` int(11) DEFAULT 10 COMMENT '状态 | 10 有效 | 20 删除',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY filename (filename(20))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1001 COMMENT '文件资源管理';