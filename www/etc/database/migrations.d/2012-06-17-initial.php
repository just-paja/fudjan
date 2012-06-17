<?

$this->sql("
	CREATE TABLE IF NOT EXISTS `attr` (
		`id_attr` int(11) NOT NULL AUTO_INCREMENT,
		`id_attr_set` int(11) NOT NULL,
		`id_attr_group` int(11) NOT NULL,
		`type` char(128) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
		`object_type` char(63) COLLATE utf8_unicode_ci DEFAULT NULL,
		`seoname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
		`id_tag_root` int(11) NOT NULL,
		`max_tag_depth` int(11) NOT NULL DEFAULT '0',
		`visible` tinyint(1) NOT NULL DEFAULT '0',
		`order` int(11) NOT NULL DEFAULT '0',
		`created_at` datetime NOT NULL,
		`updated_at` datetime NOT NULL,
		`required` tinyint(1) NOT NULL DEFAULT '0',
		PRIMARY KEY (`id_attr`),
		KEY (`id_attr_set`),
		KEY (`id_attr_group`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
");


$this->sql("
	CREATE TABLE IF NOT EXISTS `attr_group` (
		`id_attr_group` int(11) NOT NULL AUTO_INCREMENT,
		`id_attr_set` int(11) NOT NULL,
		`name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`visible` tinyint(1) NOT NULL DEFAULT '0',
		`order` int(11) NOT NULL DEFAULT '0',
		`created_at` datetime NOT NULL,
		`updated_at` datetime NOT NULL,
		PRIMARY KEY (`id_attr_group`),
		KEY (`id_attr_set`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
");

$this->sql("
	CREATE TABLE IF NOT EXISTS `attr_set` (
		`id_attr_set` int(11) NOT NULL AUTO_INCREMENT,
		`name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`object_class` char(128) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
		`object_type` char(63) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
		`visible` tinyint(1) NOT NULL DEFAULT '0',
		`created_at` datetime NOT NULL,
		`updated_at` datetime NOT NULL,
		PRIMARY KEY (`id_attr_set`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
");


$this->sql("
	CREATE TABLE IF NOT EXISTS `attr_value` (
		`id_attr_value` int(11) NOT NULL AUTO_INCREMENT,
		`id_attr` int(11) NOT NULL,
		`object_id` int(11) NOT NULL,
		`value_string` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`value_text` text COLLATE utf8_unicode_ci NOT NULL,
		`value_int` int(11) NOT NULL,
		`value_float` float NOT NULL,
		`value_datetime` datetime NOT NULL,
		`created_at` datetime NOT NULL,
		`updated_at` datetime NOT NULL,
		PRIMARY KEY (`id_attr_value`),
		KEY `id_attr` (`id_attr`,`object_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
");

$this->sql("
	CREATE TABLE IF NOT EXISTS `database_migration` (
		`id_database_migration` int(11) NOT NULL AUTO_INCREMENT,
		`seoname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`desc` tinytext COLLATE utf8_unicode_ci,
		`md5_sum` char(40) COLLATE utf8_unicode_ci NOT NULL,
		`status` enum('new','ok','failed') COLLATE utf8_unicode_ci NOT NULL,
		`date` datetime NOT NULL,
		`created_at` datetime NOT NULL,
		`updated_at` datetime NOT NULL,
		PRIMARY KEY (`id_database_migration`),
		UNIQUE KEY `md5_sum` (`md5_sum`),
		KEY `name` (`name`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=20 ;
");

$this->sql("
	CREATE TABLE IF NOT EXISTS `mailer` (
		`id_mailer` int(11) NOT NULL AUTO_INCREMENT,
		`name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`trigger_name` char(63) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
		`template_name` char(128) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
		`run_count` int(11) NOT NULL DEFAULT '0',
		`used` tinyint(1) NOT NULL DEFAULT '0',
		`deleted` tinyint(1) NOT NULL DEFAULT '0',
		`order` int(11) NOT NULL DEFAULT '100',
		`created_at` datetime NOT NULL,
		`updated_at` datetime NOT NULL,
		`visible` tinyint(1) NOT NULL DEFAULT '0',
		PRIMARY KEY (`id_mailer`),
		KEY `trigger_name` (`trigger_name`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
");

$this->sql("
	CREATE TABLE IF NOT EXISTS `mailer_body` (
		`id_mailer_body` int(11) NOT NULL AUTO_INCREMENT,
		`subject` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
		`from` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
		`reply_to` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`cc` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`bcc` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`id_mailer` int(11) NOT NULL,
		`rcpt` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
		`type` enum('email','sms') CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
		`template_name` varchar(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
		`visible` tinyint(1) NOT NULL DEFAULT '0',
		`created_at` datetime NOT NULL,
		`updated_at` datetime NOT NULL,
		PRIMARY KEY (`id_mailer_body`),
		UNIQUE KEY `id_mailer_2` (`id_mailer`,`type`),
		KEY `id_mailer` (`id_mailer`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
");

$this->sql("
	CREATE TABLE IF NOT EXISTS `mailer_queue` (
		`id_mailer_queue_item` int(11) NOT NULL AUTO_INCREMENT,
		`id_mailer` int(11) NOT NULL,
		`id_mailer_body` int(11) NOT NULL,
		`id_user_invoker` int(11) NOT NULL,
		`headers` text COLLATE utf8_unicode_ci NOT NULL,
		`body` text COLLATE utf8_unicode_ci NOT NULL,
		`status` enum('ready','sending','sent','failed') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'ready',
		`return_message` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
		`created_at` datetime NOT NULL,
		`updated_at` datetime NOT NULL,
		PRIMARY KEY (`id_mailer_queue_item`),
		KEY `id_mailer` (`id_mailer`,`id_mailer_body`,`id_user_invoker`),
		KEY `id_mailer_body` (`id_mailer_body`),
		KEY `id_user_invoker` (`id_user_invoker`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
");


$this->sql("
	CREATE TABLE IF NOT EXISTS `text` (
		`id_text` int(11) NOT NULL AUTO_INCREMENT,
		`name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`lang` varchar(21) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
		`text` text COLLATE utf8_unicode_ci NOT NULL,
		`created_at` datetime NOT NULL,
		`updated_at` datetime NOT NULL,
		PRIMARY KEY (`id_text`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
");

$this->sql("
	CREATE TABLE IF NOT EXISTS `user` (
		`id_user` int(11) NOT NULL AUTO_INCREMENT,
		`login` varchar(127) COLLATE utf8_unicode_ci NOT NULL,
		`password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`nick` varchar(127) COLLATE utf8_unicode_ci NOT NULL,
		`first_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`last_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`default_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
		`emails` text COLLATE utf8_unicode_ci,
		`phones` text COLLATE utf8_unicode_ci,
		`instant_messengers` text COLLATE utf8_unicode_ci,
		`sites` text COLLATE utf8_unicode_ci,
		`avatar` text COLLATE utf8_unicode_ci,
		`sitecom_email` tinyint(1) NOT NULL DEFAULT '0',
		`sitecom_sms` tinyint(1) NOT NULL DEFAULT '0',
		`created_at` datetime NOT NULL,
		`updated_at` datetime NOT NULL,
		`last_login` datetime DEFAULT NULL,
		`about` text COLLATE utf8_unicode_ci,
		PRIMARY KEY (`id_user`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
");

$this->sql("
	CREATE TABLE IF NOT EXISTS `user_group` (
		`id_user_group` int(11) NOT NULL AUTO_INCREMENT,
		`name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`created_at` datetime DEFAULT NULL,
		`updated_at` datetime DEFAULT NULL,
		PRIMARY KEY (`id_user_group`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
");

$this->sql("
	CREATE TABLE IF NOT EXISTS `user_group_assignment` (
		`id_user` int(11) NOT NULL,
		`id_user_group` int(11) NOT NULL,
		PRIMARY KEY (`id_user`,`id_user_group`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
");


$this->sql("
	CREATE TABLE IF NOT EXISTS `user_perm` (
		`id_user_perm` int(11) NOT NULL AUTO_INCREMENT,
		`type` enum('module','action') CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'action',
		`trigger` varchar(127) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
		`id_user_group` int(11) NOT NULL DEFAULT '0',
		`id_author` int(11) NOT NULL DEFAULT '0',
		`public` tinyint(1) NOT NULL DEFAULT '0',
		`created_at` datetime NOT NULL,
		`updated_at` datetime NOT NULL,
		PRIMARY KEY (`id_user_perm`),
		KEY `id_user_group` (`id_user_group`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
");


$this->sql("
	ALTER TABLE `mailer_body`
		ADD FOREIGN KEY (`id_mailer`) REFERENCES `mailer` (`id_mailer`) ON DELETE CASCADE ON UPDATE NO ACTION;
");


$this->sql("
	ALTER TABLE `user_perm`
		ADD FOREIGN KEY (`id_user_group`) REFERENCES `user_group` (`id_user_group`) ON DELETE CASCADE ON UPDATE CASCADE;
");


$this->sql("
	ALTER TABLE `user_group_assignment`
		ADD FOREIGN KEY (`id_user_group`) REFERENCES `user_group` (`id_user_group`) ON DELETE CASCADE ON UPDATE CASCADE,
		ADD FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;
");

$this->sql("
	ALTER TABLE `attr_group`
		ADD FOREIGN KEY (`id_attr_set`) REFERENCES `attr_set` (`id_attr_set`) ON DELETE CASCADE ON UPDATE CASCADE;
");

$this->sql("
	ALTER TABLE `attr`
		ADD FOREIGN KEY (`id_attr_set`) REFERENCES `attr_set` (`id_attr_set` ) ON DELETE CASCADE ON UPDATE NO ACTION,
		ADD FOREIGN KEY (`id_attr_group`) REFERENCES `attr_group` (`id_attr_group`) ON DELETE CASCADE ON UPDATE NO ACTION;
");
