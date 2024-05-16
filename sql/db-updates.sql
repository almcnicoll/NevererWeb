/* UPDATE */
/* VERSION 1 */
CREATE TABLE `authmethods` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `methodName` VARCHAR(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `handler` VARCHAR(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` VARCHAR(4000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` DATETIME DEFAULT NULL,
  `modified` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `methodName` (`methodName`)
) ENGINE=INNODB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `errors` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `number` MEDIUMINT(9) DEFAULT NULL,
  `message` VARCHAR(4000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file` VARCHAR(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `line` SMALLINT(5) UNSIGNED DEFAULT NULL,
  `created` DATETIME DEFAULT NULL,
  `modified` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `faqs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `question` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `answer` varchar(4000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rank` int(11) NOT NULL DEFAULT 0,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ListIndex` (`rank`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `model` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `created` DATETIME DEFAULT NULL,
  `modified` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `users` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `authmethod_id` INT(10) UNSIGNED NOT NULL,
  `identifier` VARCHAR(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` VARCHAR(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_name` VARCHAR(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_url` VARCHAR(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` DATETIME DEFAULT NULL,
  `modified` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `LoginLookup` (`authmethod_id`,`identifier`)
) ENGINE=INNODB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/* UPDATE */
/* VERSION 2 */
INSERT INTO `authmethods` (`methodName`,`handler`,`image`,`created`,`modified`)
VALUES ('google','login-google.php','img/logins/google.png',NOW(),NOW());
ALTER TABLE `authmethods` MODIFY COLUMN `image` varchar(4000) NULL;
UPDATE `authmethods` SET `handler`='', `image`='<div style="width:400px; margin:auto;"><div id="g_id_onload" data-client_id="113954362296-1t4ieb2ghbcoqejmphksgqq7u7nhcp83.apps.googleusercontent.com" data-context="use" data-ux_mode="popup" data-login_uri="/NevererWeb/login-google.php" data-auto_select="true" data-itp_support="true"></div><div class="g_id_signin" data-type="standard" data-shape="pill" data-theme="filled_blue" data-text="continue_with" data-size="large" data-logo_alignment="left"></div></div>' WHERE `methodName`='google';