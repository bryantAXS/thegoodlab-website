
CREATE TABLE IF NOT EXISTS `exp_user_activation_group` (
  `member_id` int(10) unsigned NOT NULL,
  `group_id` int(10) unsigned NOT NULL,
  KEY `member_id` (`member_id`),
  KEY `group_id` (`group_id`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;


CREATE TABLE IF NOT EXISTS `exp_user_authors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `author_id` int(10) unsigned NOT NULL DEFAULT '0',
  `entry_id` int(10) unsigned NOT NULL DEFAULT '0',
  `principal` char(1) NOT NULL DEFAULT 'n',
  `entry_date` int(10) NOT NULL DEFAULT '0',
  `hash` varchar(40) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  KEY `entry_id` (`entry_id`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;


CREATE TABLE IF NOT EXISTS `exp_user_cache` (
  `cache_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(150) NOT NULL,
  `entry_date` int(10) NOT NULL,
  `data` text NOT NULL,
  PRIMARY KEY (`cache_id`),
  KEY `type` (`type`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;


CREATE TABLE IF NOT EXISTS `exp_user_category_posts` (
  `member_id` int(10) unsigned NOT NULL DEFAULT '0',
  `cat_id` int(10) unsigned NOT NULL DEFAULT '0',
  KEY `member_id` (`member_id`),
  KEY `cat_id` (`cat_id`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_user_keys` (
  `key_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `author_id` int(10) unsigned NOT NULL,
  `member_id` int(10) unsigned NOT NULL,
  `group_id` int(10) unsigned NOT NULL,
  `date` int(10) NOT NULL,
  `email` varchar(150) NOT NULL,
  `hash` varchar(8) NOT NULL,
  PRIMARY KEY (`key_id`),
  KEY `email` (`email`),
  KEY `hash` (`hash`),
  KEY `author_id` (`author_id`),
  KEY `member_id` (`member_id`),
  KEY `group_id` (`group_id`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_user_params` (
  `params_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hash` varchar(25) NOT NULL,
  `entry_date` int(10) NOT NULL,
  `data` text NOT NULL,
  PRIMARY KEY (`params_id`),
  KEY `hash` (`hash`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;


CREATE TABLE IF NOT EXISTS `exp_user_preferences` (
  `preference_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `preference_name` varchar(50) NOT NULL,
  `preference_value` text NOT NULL,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`preference_id`),
  KEY `site_id` (`site_id`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;


CREATE TABLE IF NOT EXISTS `exp_user_search` (
  `search_id` varchar(32) NOT NULL,
  `member_id` int(10) unsigned NOT NULL,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `ip_address` varchar(16) NOT NULL,
  `search_date` int(10) unsigned NOT NULL,
  `total_results` int(8) unsigned NOT NULL,
  `keywords` varchar(200) NOT NULL,
  `categories` text NOT NULL,
  `member_ids` text NOT NULL,
  `fields` text NOT NULL,
  `cfields` text NOT NULL,
  `query` text NOT NULL,
  PRIMARY KEY (`search_id`),
  KEY `member_id` (`member_id`),
  KEY `site_id` (`site_id`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_user_welcome_email_list` (
  `member_id` int(10) unsigned NOT NULL,
  `group_id` int(10) unsigned NOT NULL,
  `email_sent` char(1) NOT NULL DEFAULT 'n',
  KEY `member_id` (`member_id`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;