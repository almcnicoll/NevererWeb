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
/* UPDATE */
/* VERSION 3 */
INSERT INTO `authmethods` (`methodName`,`handler`,`image`,`created`,`modified`)
VALUES ('neverer','login-neverer.php','img/logins/neverer.png',NOW(),NOW());
/* UPDATE */
/* VERSION 4 */
CREATE TABLE `passwords`
(
id INT(10) NOT NULL AUTO_INCREMENT,
user_id INT(10) NOT NULL,
`hash` VARCHAR(255) NOT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `user_id` (`user_id`),
UNIQUE KEY `user_password` (`user_id`,`hash`)
) COMMENT 'Uses PHP password hashing' ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/* UPDATE */
/* VERSION 5 */
DELETE FROM authmethods WHERE methodName='google';
/* UPDATE */
/* VERSION 6 */
ALTER TABLE passwords ADD COLUMN `created` DATETIME DEFAULT NULL,
ADD COLUMN `modified` DATETIME DEFAULT NULL;
/* UPDATE */
/* VERSION 7 */
CREATE TABLE `crosswords` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `title` VARCHAR(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cols` TINYINT(2) UNSIGNED NOT NULL,
  `rows` TINYINT(2) UNSIGNED NOT NULL,
  `rotational_symmetry_order` TINYINT(2) UNSIGNED NOT NULL,
  `created` DATETIME DEFAULT NULL,
  `modified` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Listing` (`user_id`,`id`,`title`)
) ENGINE=INNODB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `placedclues` (
  `id` BIGINT(15) UNSIGNED NOT NULL AUTO_INCREMENT,
  `crossword_id` INT(10) UNSIGNED NOT NULL,
  `x` TINYINT(2) UNSIGNED NOT NULL,
  `y` TINYINT(2) UNSIGNED NOT NULL,
  `orientation` VARCHAR(6) NOT NULL DEFAULT 'Unset',
  `place_number` TINYINT(2) UNSIGNED NOT NULL,
  `status` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `created` DATETIME DEFAULT NULL,
  `modified` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `OnGrid` (`crossword_id`,`id`,`y`,`x`)
) ENGINE=INNODB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `clues` (
  `id` BIGINT(15) UNSIGNED NOT NULL AUTO_INCREMENT,
  `placedclue_id` BIGINT(15) UNSIGNED DEFAULT NULL,
  `question` VARCHAR(2000) DEFAULT NULL,
  `answer` VARCHAR(2000) DEFAULT NULL,
  `pattern` VARCHAR(200) DEFAULT NULL,
  `created` DATETIME DEFAULT NULL,
  `modified` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Lookup` (`placedclue_id`,`id`)
) ENGINE=INNODB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/* UPDATE */
/* VERSION 8 */
ALTER TABLE `clues`
ADD CONSTRAINT `FK_PLACEDCLUE_ID` FOREIGN KEY (placedclue_id) REFERENCES placedclues (id)
  ON DELETE SET NULL
  ON UPDATE CASCADE;
ALTER TABLE `placedclues`
ADD CONSTRAINT `FK_CROSSWORD_ID` FOREIGN KEY (crossword_id) REFERENCES crosswords (id)
  ON DELETE CASCADE
  ON UPDATE CASCADE;
ALTER TABLE `crosswords`
ADD CONSTRAINT `FK_USER_ID` FOREIGN KEY (user_id) REFERENCES users (id)
  ON DELETE CASCADE
  ON UPDATE CASCADE;
/* UPDATE */
/* VERSION 9 */
ALTER TABLE `clues`
ADD COLUMN `explanation` VARCHAR(2000) DEFAULT NULL
AFTER `pattern`;
/* UPDATE */
/* VERSION 10 */
CREATE TABLE `sowpods` (
  `word` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ordered_word` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `a` tinyint(4) NOT NULL DEFAULT 0,
  `b` tinyint(4) NOT NULL DEFAULT 0,
  `c` tinyint(4) NOT NULL DEFAULT 0,
  `d` tinyint(4) NOT NULL DEFAULT 0,
  `e` tinyint(4) NOT NULL DEFAULT 0,
  `f` tinyint(4) NOT NULL DEFAULT 0,
  `g` tinyint(4) NOT NULL DEFAULT 0,
  `h` tinyint(4) NOT NULL DEFAULT 0,
  `i` tinyint(4) NOT NULL DEFAULT 0,
  `j` tinyint(4) NOT NULL DEFAULT 0,
  `k` tinyint(4) NOT NULL DEFAULT 0,
  `l` tinyint(4) NOT NULL DEFAULT 0,
  `m` tinyint(4) NOT NULL DEFAULT 0,
  `n` tinyint(4) NOT NULL DEFAULT 0,
  `o` tinyint(4) NOT NULL DEFAULT 0,
  `p` tinyint(4) NOT NULL DEFAULT 0,
  `q` tinyint(4) NOT NULL DEFAULT 0,
  `r` tinyint(4) NOT NULL DEFAULT 0,
  `s` tinyint(4) NOT NULL DEFAULT 0,
  `t` tinyint(4) NOT NULL DEFAULT 0,
  `u` tinyint(4) NOT NULL DEFAULT 0,
  `v` tinyint(4) NOT NULL DEFAULT 0,
  `w` tinyint(4) NOT NULL DEFAULT 0,
  `x` tinyint(4) NOT NULL DEFAULT 0,
  `y` tinyint(4) NOT NULL DEFAULT 0,
  `z` tinyint(4) NOT NULL DEFAULT 0,
  `len` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`word`),
  UNIQUE KEY `ordered_word_lookup` (`ordered_word`,`word`),
  KEY `word_length` (`len`,`word`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;
/* UPDATE */
/* VERSION 11 */
CREATE TABLE `displaymessages` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `level` SMALLINT(5) UNSIGNED NOT NULL,
  `message` VARCHAR(2000) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `displayCount` SMALLINT(5) UNSIGNED NOT NULL,
  `created` DATETIME DEFAULT NULL,
  `modified` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=INNODB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;