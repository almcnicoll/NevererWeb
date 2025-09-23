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
/* UPDATE */
/* VERSION 12 */
INSERT INTO `errors` (`type`,`number`,`message`,`file`,`line`,`created`,`modified`)
VALUES ('sys',0,"Testing Git post-deployment script on remote server",'',0,NOW(),NOW())
;
/* UPDATE */
/* VERSION 13 */
CREATE TABLE `tomes` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(400) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'New Tome',
  `source` VARCHAR(2000) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `source_type` VARCHAR(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'url',
  `source_format` VARCHAR(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `readable` SMALLINT(5) UNSIGNED NOT NULL,
  `writeable` SMALLINT(5) UNSIGNED NOT NULL,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `last_updated` DATETIME DEFAULT NULL,
  `created` DATETIME DEFAULT NULL,
  `modified` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=INNODB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/* UPDATE */
/* VERSION 14 */
CREATE TABLE `tome_entries` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tome_id` INT(10) UNSIGNED NOT NULL,
  `word` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bare_letters` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` DATETIME DEFAULT NULL,
  `modified` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
);
CREATE TABLE `tome_clues` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tomeentry_id` INT(10) UNSIGNED NOT NULL,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `question` VARCHAR(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cryptic` BOOLEAN NOT NULL DEFAULT TRUE,
  `created` DATETIME DEFAULT NULL,
  `modified` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
);
/* UPDATE */
/* VERSION 15 */
CREATE UNIQUE INDEX alphabetical
ON `tome_entries` (tome_id, bare_letters, word);
CREATE INDEX user_readable
ON `tomes` (user_id,readable,id);
CREATE UNIQUE INDEX by_entry
ON `tome_clues` (tomeentry_id, cryptic, question);
/* UPDATE */
/* VERSION 16 */
/*include 16_sowpods_tome.sql*/
/* UPDATE */
/* VERSION 17 */
ALTER TABLE `tome_entries`
  MODIFY COLUMN `word` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  MODIFY COLUMN `bare_letters` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL
;
ALTER TABLE `tome_clues` 
  MODIFY `question` VARCHAR(500) COLLATE utf8mb4_unicode_ci NOT NULL
;
/* UPDATE */
/* VERSION 18 */
CREATE INDEX `filter`
ON `tome_entries` (tome_id, modified);
/* UPDATE */
/* VERSION 19 */
CREATE INDEX `filter2`
ON `tome_entries` (modified, tome_id, bare_letters, word);
/* UPDATE */
/* VERSION 20 */
ALTER TABLE `tome_entries`
ADD COLUMN `length` TINYINT(2) UNSIGNED NULL,
ADD COLUMN `a` TINYINT(2) UNSIGNED NULL,ADD COLUMN `b` TINYINT(2) UNSIGNED NULL,
ADD COLUMN `c` TINYINT(2) UNSIGNED NULL,ADD COLUMN `d` TINYINT(2) UNSIGNED NULL,
ADD COLUMN `e` TINYINT(2) UNSIGNED NULL,ADD COLUMN `f` TINYINT(2) UNSIGNED NULL,
ADD COLUMN `g` TINYINT(2) UNSIGNED NULL,ADD COLUMN `h` TINYINT(2) UNSIGNED NULL,
ADD COLUMN `i` TINYINT(2) UNSIGNED NULL,ADD COLUMN `j` TINYINT(2) UNSIGNED NULL,
ADD COLUMN `k` TINYINT(2) UNSIGNED NULL,ADD COLUMN `l` TINYINT(2) UNSIGNED NULL,
ADD COLUMN `m` TINYINT(2) UNSIGNED NULL,ADD COLUMN `n` TINYINT(2) UNSIGNED NULL,
ADD COLUMN `o` TINYINT(2) UNSIGNED NULL,ADD COLUMN `p` TINYINT(2) UNSIGNED NULL,
ADD COLUMN `q` TINYINT(2) UNSIGNED NULL,ADD COLUMN `r` TINYINT(2) UNSIGNED NULL,
ADD COLUMN `s` TINYINT(2) UNSIGNED NULL,ADD COLUMN `t` TINYINT(2) UNSIGNED NULL,
ADD COLUMN `u` TINYINT(2) UNSIGNED NULL,ADD COLUMN `v` TINYINT(2) UNSIGNED NULL,
ADD COLUMN `w` TINYINT(2) UNSIGNED NULL,ADD COLUMN `x` TINYINT(2) UNSIGNED NULL,
ADD COLUMN `y` TINYINT(2) UNSIGNED NULL,ADD COLUMN `z` TINYINT(2) UNSIGNED NULL
;
/* UPDATE */
/* VERSION 21 */
CREATE DEFINER=CURRENT_USER PROCEDURE `PopulateLetterCounts`()
    MODIFIES SQL DATA
    SQL SECURITY INVOKER
    COMMENT 'Populates letter-counts for words with none, n rows at a time'
BEGIN

/* Declare variables */
DECLARE rowLimit INT DEFAULT 100000;
DECLARE rowCount INT DEFAULT 0;
DECLARE rowsToGo INT DEFAULT 0;
DECLARE letterAsc INT DEFAULT 97;
DECLARE letterLower CHAR DEFAULT 'a';
DECLARE letterUpper CHAR DEFAULT 'A';
#DECLARE sqlUpdate VARCHAR(1000) DEFAULT ''; 

/* Get target rows */
DROP TEMPORARY TABLE IF EXISTS `tmpEntries`;
CREATE TEMPORARY TABLE `tmpEntries` LIKE `tome_entries`;

/* Pick working rows */
INSERT INTO `tmpEntries` SELECT * FROM `tome_entries` WHERE `z` IS NULL LIMIT rowLimit;
SELECT COUNT(*) INTO rowCount FROM `tmpEntries`;

/* Set length */
UPDATE `tmpEntries` SET `length` = LENGTH(`bare_letters`);

/* Set letter-counts */
letter_loop: WHILE letterAsc < 123 DO
	/* Populate CHAR variables */
	SET letterLower = CHAR(letterAsc);
	SET letterUpper = CHAR(letterAsc - 32);
	/* Prep the SQL */
	SET @sqlPopulateLetterCountsUpdate = CONCAT("UPDATE `tmpEntries` SET `",letterLower,"` = LENGTH(REPLACE(`bare_letters`,'",letterUpper,"','**')) - `length`;");
	PREPARE stmt FROM @sqlPopulateLetterCountsUpdate;
	EXECUTE stmt;
	DEALLOCATE PREPARE stmt;
	/* Now increment letter */
	SET letterAsc = letterAsc+1;
END WHILE;

/* Debugging output */
#SELECT * FROM `tmpEntries`;

/* Push results */
UPDATE `tome_entries` dest INNER JOIN `tmpEntries` src ON `src`.`id`=`dest`.`id`
SET `dest`.`length`=`src`.`length`,
`dest`.`a`=`src`.`a`,`dest`.`b`=`src`.`b`,
`dest`.`c`=`src`.`c`,`dest`.`d`=`src`.`d`,
`dest`.`e`=`src`.`e`,`dest`.`f`=`src`.`f`,
`dest`.`g`=`src`.`g`,`dest`.`h`=`src`.`h`,
`dest`.`i`=`src`.`i`,`dest`.`j`=`src`.`j`,
`dest`.`k`=`src`.`k`,`dest`.`l`=`src`.`l`,
`dest`.`m`=`src`.`m`,`dest`.`n`=`src`.`n`,
`dest`.`o`=`src`.`o`,`dest`.`p`=`src`.`p`,
`dest`.`q`=`src`.`q`,`dest`.`r`=`src`.`r`,
`dest`.`s`=`src`.`s`,`dest`.`t`=`src`.`t`,
`dest`.`u`=`src`.`u`,`dest`.`v`=`src`.`v`,
`dest`.`w`=`src`.`w`,`dest`.`x`=`src`.`x`,
`dest`.`y`=`src`.`y`,`dest`.`z`=`src`.`z`
;

SELECT COUNT(*) INTO rowsToGo FROM `tome_entries` WHERE `z` IS NULL;

SELECT rowCount AS `RowsCompleted`, rowsToGo AS `RowsToGo`;

DROP TEMPORARY TABLE `tmpEntries`;

	END
/* UPDATE */
/* VERSION 22 */
/* PREPAREMODE */
CALL PopulateLetterCounts();
/* UPDATE */
/* VERSION 23 */
/* PREPAREMODE */
CALL PopulateLetterCounts();
/* UPDATE */
/* VERSION 24 */
CALL PopulateLetterCounts();
/* PREPAREMODE */
/* UPDATE */
/* VERSION 25 */
CALL PopulateLetterCounts();
/* PREPAREMODE */
/* UPDATE */
/* VERSION 26 */
/*include 26_anagram_sp.sql*/
/* UPDATE */
/* VERSION 27 */
INSERT INTO `faqs` (`question`,`answer`,`rank`,`created`,`modified`)
VALUES ("What does 'synchronising words' mean, and why is it taking so long?","There's a bunch of word-based operations which Neverer needs to do to support crossword-making - in particular, looking for words or phrases which match a particular gap in your crossword.<br /><br />Sending these all to the server to query would be (a) slow and (b) costly in terms of server resources. Instead, Neverer downloads a list of words from the server to your local machine.<br /><br />Once those words are stored locally on your machine, Neverer will only need to download any changes, which will most likely be small in number. However, the initial synchronisation could take anything from a few minutes to an hour. The system is useable in the meantime, but a few features will be unavailable.",10,NOW(),NOW());
/* UPDATE */
/* VERSION 28 */
/*include 28_idiom_tome.sql*/
/* UPDATE */
/* VERSION 29 */
/*include 29_name_tome.sql*/
/* UPDATE */
/* VERSION 30 */
INSERT INTO `authmethods` (`methodName`,`handler`,`image`,`created`,`modified`)
VALUES ('google','login-google.php','img/logins/google.png',NOW(),NOW());
/* UPDATE */
/* VERSION 31 */
INSERT INTO `faqs` (`question`,`answer`,`rank`,`created`,`modified`)
VALUES ("Can I save my crossword as a PDF?","A PDF is a great way to distribute a crossword. However, making PDFs look 'just right' can be time-consuming and requires use of third-party code that has limitations.<br /><br />Most modern browsers have the option to print to PDF, so effort has been put into making the crossword print well. To produce a PDF, click the print icon (top-right when editing) and choose 'Save to PDF' (or similar).<br /><br />See <a href='https://xodo.com/blog/how-to-save-webpage-as-pdf' target='_blank'>this link</a> for more details. <em>(try <a href='https://web.archive.org/web/20250627063533/https://xodo.com/blog/how-to-save-webpage-as-pdf' target='_blank'>here</a> if that link is dead)</em>",20,NOW(),NOW());