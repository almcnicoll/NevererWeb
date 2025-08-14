DELIMITER $$

USE `nevererweb`$$

DROP PROCEDURE IF EXISTS `AnagramTest01`$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AnagramTest01`(
	originalWord VARCHAR(20),
	maxWords TINYINT(1)
    )
    COMMENT 'Let us try to get this working'
BEGIN
/* Variables for letter-loop */
DECLARE letterAsc INT DEFAULT 97;
DECLARE letterLower CHAR DEFAULT 'a';
DECLARE letterUpper CHAR DEFAULT 'A';

/* TODO - ensure that this matches the PHP code for generating bare_letters */
SET @originalWord = originalWord;
SET @originalBareLetters = REPLACE(REPLACE(REPLACE(originalWord,"'",''),'-',''),' ','');
SET @originalLength = LENGTH(@originalBareLetters);
IF maxWords>6 THEN SET @maxRounds = 6;
ELSEIF maxWords<1 THEN SET @maxRounds = 1;
ELSE SET @maxRounds = maxWords;
END IF;

/* Set up temporary tables */
DROP TEMPORARY TABLE IF EXISTS `orig`;
DROP TEMPORARY TABLE IF EXISTS `w1`;
DROP TEMPORARY TABLE IF EXISTS `wN`;
DROP TEMPORARY TABLE IF EXISTS `w2`;
DROP TEMPORARY TABLE IF EXISTS `w3`;
DROP TEMPORARY TABLE IF EXISTS `comp1`;
DROP TEMPORARY TABLE IF EXISTS `compNodd`;
DROP TEMPORARY TABLE IF EXISTS `compNeven`;
DROP TEMPORARY TABLE IF EXISTS `comp2`;
DROP TEMPORARY TABLE IF EXISTS `comp3`;
DROP TEMPORARY TABLE IF EXISTS `results`;
CREATE TEMPORARY TABLE `orig` LIKE `tome_entries`;
ALTER TABLE `orig` DROP COLUMN `created`, DROP COLUMN `modified`;
ALTER TABLE `orig` DROP INDEX `alphabetical`, DROP INDEX `filter`, DROP INDEX `filter2`;
CREATE TEMPORARY TABLE `w1` LIKE `orig`;
CREATE TEMPORARY TABLE `wN` LIKE `orig`; /* NEW */
CREATE TEMPORARY TABLE `w2` LIKE `orig`; /* LEGACY */
CREATE TEMPORARY TABLE `w3` LIKE `orig`; /* LEGACY */
CREATE TEMPORARY TABLE `comp1` LIKE `tome_entries`;
ALTER TABLE `comp1` DROP COLUMN `created`, DROP COLUMN `modified`;
ALTER TABLE `comp1` DROP INDEX `alphabetical`, DROP INDEX `filter`, DROP INDEX `filter2`;
#ALTER TABLE `comp1` ENGINE=MEMORY; /* Would be nice, but we run out of memory! */
ALTER TABLE `comp1` ADD COLUMN `composite_word` VARCHAR(100) NOT NULL DEFAULT '';
ALTER TABLE `comp1` ADD COLUMN `composite_bare_letters` VARCHAR(100) NOT NULL DEFAULT '';
ALTER TABLE `comp1` ADD COLUMN `last_length` TINYINT(2) UNSIGNED DEFAULT NULL;
CREATE TEMPORARY TABLE `compNodd` LIKE `comp1`; /* NEW */
CREATE TEMPORARY TABLE `compNeven` LIKE `comp1`; /* NEW */
CREATE TEMPORARY TABLE `comp2` LIKE `comp1`; /* LEGACY */
CREATE TEMPORARY TABLE `comp3` LIKE `comp1`; /* LEGACY */
CREATE TEMPORARY TABLE `results` (
`id` INT(10) UNSIGNED AUTO_INCREMENT,
`word` VARCHAR (100) COLLATE utf8mb4_unicode_ci NOT NULL,
`bare_letters` VARCHAR (100) COLLATE utf8mb4_unicode_ci NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; /* NEW */

/* We want our original word in a single-row table */
INSERT INTO `orig` (`id`,`tome_id`,`word`,`bare_letters`,`length`) SELECT 0,0,@originalWord,@originalBareLetters,@originalLength;
/* Populate letter-columns */
/* TODO - we could check if it exists in tome_entries and copy the row if it does */
letter_loop: WHILE letterAsc < 123 DO
	/* Populate CHAR variables */
	SET letterLower = CHAR(letterAsc);
	SET letterUpper = CHAR(letterAsc - 32);
	/* Prep the SQL */
	SET @sqlPopulateLetterCountsUpdate = CONCAT("UPDATE `orig` SET `",letterLower,"` = LENGTH(REPLACE(`bare_letters`,'",letterUpper,"','**')) - `length`;");
	PREPARE stmt FROM @sqlPopulateLetterCountsUpdate;
	EXECUTE stmt;
	DEALLOCATE PREPARE stmt;
	/* Now increment letter */
	SET letterAsc = letterAsc+1;
END WHILE;

/* Get max counts per letter */
SELECT `a`,`b`,`c`,`d`,`e`,`f`,`g`,`h`,`i`,`j`,`k`,`l`,`m`
,`n`,`o`,`p`,`q`,`r`,`s`,`t`,`u`,`v`,`w`,`x`,`y`,`z`
 INTO  @orig_a, @orig_b, @orig_c, @orig_d, @orig_e, @orig_f, @orig_g, @orig_h, @orig_i, @orig_j, @orig_k, @orig_l, @orig_m
, @orig_n, @orig_o, @orig_p, @orig_q, @orig_r, @orig_s, @orig_t, @orig_u, @orig_v, @orig_w, @orig_x, @orig_y, @orig_z
FROM `orig`
LIMIT 1
;
/* Populate first candidate-word table */
/*EXPLAIN EXTENDED
SELECT id,tome_id,`word`,`bare_letters`,`length`,`a`,`b`,`c`,`d`,`e`,`f`,`g`,`h`,`i`,`j`,`k`,`l`,`m`,`n`,`o`,`p`,`q`,`r`,`s`,`t`,`u`,`v`,`w`,`x`,`y`,`z`
FROM `tome_entries`
WHERE `length`<=@originalLength AND `a`<=@orig_a AND `b`<=@orig_b
 AND `c`<=@orig_c AND `d`<=@orig_d AND `e`<=@orig_e AND `f`<=@orig_f
 AND `g`<=@orig_g AND `h`<=@orig_h AND `i`<=@orig_i AND `j`<=@orig_j
 AND `k`<=@orig_k AND `l`<=@orig_l AND `m`<=@orig_m AND `n`<=@orig_n
 AND `o`<=@orig_o AND `p`<=@orig_p AND `q`<=@orig_q AND `r`<=@orig_r
 AND `s`<=@orig_s AND `t`<=@orig_t AND `u`<=@orig_u AND `v`<=@orig_v
 AND `w`<=@orig_w AND `x`<=@orig_x AND `y`<=@orig_y AND `z`<=@orig_z
;*/
INSERT INTO `wN`
SELECT id,tome_id,`word`,`bare_letters`,`length`,`a`,`b`,`c`,`d`,`e`,`f`,`g`,`h`,`i`,`j`,`k`,`l`,`m`,`n`,`o`,`p`,`q`,`r`,`s`,`t`,`u`,`v`,`w`,`x`,`y`,`z`
FROM `tome_entries`
WHERE `length`<=@originalLength AND `a`<=@orig_a AND `b`<=@orig_b
 AND `c`<=@orig_c AND `d`<=@orig_d AND `e`<=@orig_e AND `f`<=@orig_f
 AND `g`<=@orig_g AND `h`<=@orig_h AND `i`<=@orig_i AND `j`<=@orig_j
 AND `k`<=@orig_k AND `l`<=@orig_l AND `m`<=@orig_m AND `n`<=@orig_n
 AND `o`<=@orig_o AND `p`<=@orig_p AND `q`<=@orig_q AND `r`<=@orig_r
 AND `s`<=@orig_s AND `t`<=@orig_t AND `u`<=@orig_u AND `v`<=@orig_v
 AND `w`<=@orig_w AND `x`<=@orig_x AND `y`<=@orig_y AND `z`<=@orig_z
;
#SELECT * FROM `wN`; /* DEBUG */

/* The first round is slightly different, due to the initial join process */

/* Do our first join */
/*EXPLAIN EXTENDED
SELECT 0 AS tome_id, `wN`.`word` AS `word`, `wN`.`bare_letters` AS `bare_letters`, `wN`.`word` AS `composite_word`, `wN`.`bare_letters` AS `composite_bare_letters`
, `wN`.`length` AS `last_length`,`orig`.`length`-`wN`.`length` AS `length`
,`orig`.`a`-`wN`.`a` AS `a`,`orig`.`b`-`wN`.`b` AS `b`,`orig`.`c`-`wN`.`c` AS `c`,`orig`.`d`-`wN`.`d` AS `d`
,`orig`.`e`-`wN`.`e` AS `e`,`orig`.`f`-`wN`.`f` AS `f`,`orig`.`g`-`wN`.`g` AS `g`,`orig`.`h`-`wN`.`h` AS `h`
,`orig`.`i`-`wN`.`i` AS `i`,`orig`.`j`-`wN`.`j` AS `j`,`orig`.`k`-`wN`.`k` AS `k`,`orig`.`l`-`wN`.`l` AS `l`
,`orig`.`m`-`wN`.`m` AS `m`,`orig`.`n`-`wN`.`n` AS `n`,`orig`.`o`-`wN`.`o` AS `o`,`orig`.`p`-`wN`.`p` AS `p`
,`orig`.`q`-`wN`.`q` AS `q`,`orig`.`r`-`wN`.`r` AS `r`,`orig`.`s`-`wN`.`s` AS `s`,`orig`.`t`-`wN`.`t` AS `t`
,`orig`.`u`-`wN`.`u` AS `u`,`orig`.`v`-`wN`.`v` AS `v`,`orig`.`w`-`wN`.`w` AS `w`,`orig`.`x`-`wN`.`x` AS `x`
,`orig`.`y`-`wN`.`y` AS `y`,`orig`.`z`-`wN`.`z` AS `z`
FROM `orig`
INNER JOIN `wN` ON `wN`.`length`<=`orig`.`length`
 AND `wN`.`a`<=`orig`.`a` AND `wN`.`b`<=`orig`.`b` AND `wN`.`c`<=`orig`.`c` AND `wN`.`d`<=`orig`.`d`
 AND `wN`.`e`<=`orig`.`e` AND `wN`.`f`<=`orig`.`f` AND `wN`.`g`<=`orig`.`g` AND `wN`.`h`<=`orig`.`h`
 AND `wN`.`i`<=`orig`.`i` AND `wN`.`j`<=`orig`.`j` AND `wN`.`k`<=`orig`.`k` AND `wN`.`l`<=`orig`.`l`
 AND `wN`.`m`<=`orig`.`m` AND `wN`.`n`<=`orig`.`n` AND `wN`.`o`<=`orig`.`o` AND `wN`.`p`<=`orig`.`p`
 AND `wN`.`q`<=`orig`.`q` AND `wN`.`r`<=`orig`.`r` AND `wN`.`s`<=`orig`.`s` AND `wN`.`t`<=`orig`.`t`
 AND `wN`.`u`<=`orig`.`u` AND `wN`.`v`<=`orig`.`v` AND `wN`.`w`<=`orig`.`w` AND `wN`.`x`<=`orig`.`x`
 AND `wN`.`y`<=`orig`.`y` AND `wN`.`z`<=`orig`.`z`
 ;*/
INSERT INTO `compNodd`
(
  `tome_id`,`word`,`bare_letters`,`composite_word`,`composite_bare_letters`,`last_length`,`length`,`a`,`b`,`c`,`d`,`e`,`f`,`g`,`h`,`i`,`j`,`k`,`l`,`m`,`n`,`o`,`p`,`q`,`r`,`s`,`t`,`u`,`v`,`w`,`x`,`y`,`z`
)
SELECT 0 AS tome_id, `wN`.`word` AS `word`, `wN`.`bare_letters` AS `bare_letters`, `wN`.`word` AS `composite_word`, `wN`.`bare_letters` AS `composite_bare_letters`
, `wN`.`length` AS `last_length`,`orig`.`length`-`wN`.`length` AS `length`
,`orig`.`a`-`wN`.`a` AS `a`,`orig`.`b`-`wN`.`b` AS `b`,`orig`.`c`-`wN`.`c` AS `c`,`orig`.`d`-`wN`.`d` AS `d`
,`orig`.`e`-`wN`.`e` AS `e`,`orig`.`f`-`wN`.`f` AS `f`,`orig`.`g`-`wN`.`g` AS `g`,`orig`.`h`-`wN`.`h` AS `h`
,`orig`.`i`-`wN`.`i` AS `i`,`orig`.`j`-`wN`.`j` AS `j`,`orig`.`k`-`wN`.`k` AS `k`,`orig`.`l`-`wN`.`l` AS `l`
,`orig`.`m`-`wN`.`m` AS `m`,`orig`.`n`-`wN`.`n` AS `n`,`orig`.`o`-`wN`.`o` AS `o`,`orig`.`p`-`wN`.`p` AS `p`
,`orig`.`q`-`wN`.`q` AS `q`,`orig`.`r`-`wN`.`r` AS `r`,`orig`.`s`-`wN`.`s` AS `s`,`orig`.`t`-`wN`.`t` AS `t`
,`orig`.`u`-`wN`.`u` AS `u`,`orig`.`v`-`wN`.`v` AS `v`,`orig`.`w`-`wN`.`w` AS `w`,`orig`.`x`-`wN`.`x` AS `x`
,`orig`.`y`-`wN`.`y` AS `y`,`orig`.`z`-`wN`.`z` AS `z`
FROM `orig`
INNER JOIN `wN` ON `wN`.`length`<=`orig`.`length` AND `wN`.`a`<=`orig`.`a` AND `wN`.`b`<=`orig`.`b`
 AND `wN`.`c`<=`orig`.`c` AND `wN`.`d`<=`orig`.`d` AND `wN`.`e`<=`orig`.`e` AND `wN`.`f`<=`orig`.`f`
 AND `wN`.`g`<=`orig`.`g` AND `wN`.`h`<=`orig`.`h` AND `wN`.`i`<=`orig`.`i` AND `wN`.`j`<=`orig`.`j`
 AND `wN`.`k`<=`orig`.`k` AND `wN`.`l`<=`orig`.`l` AND `wN`.`m`<=`orig`.`m` AND `wN`.`n`<=`orig`.`n`
 AND `wN`.`o`<=`orig`.`o` AND `wN`.`p`<=`orig`.`p` AND `wN`.`q`<=`orig`.`q` AND `wN`.`r`<=`orig`.`r`
 AND `wN`.`s`<=`orig`.`s` AND `wN`.`t`<=`orig`.`t` AND `wN`.`u`<=`orig`.`u` AND `wN`.`v`<=`orig`.`v`
 AND `wN`.`w`<=`orig`.`w` AND `wN`.`x`<=`orig`.`x` AND `wN`.`y`<=`orig`.`y` AND `wN`.`z`<=`orig`.`z`
 ;

/* The only rows we want from here are the ones which are perfect anagrams */
INSERT INTO `results` (`word`,`bare_letters`)
SELECT `composite_word`,`composite_bare_letters`
FROM `compNodd`
WHERE `length`=0
ORDER BY `last_length` DESC
;

/* From hereon in, we want to loop up to @maxRounds, doing the same operation each time */
IF @maxRounds > 1 THEN
	/* We have further rounds to compute */
	SET @currentRound = 2;
	SET @oddRound = FALSE;
	rounds_loop: WHILE @currentRound <= @maxRounds DO
        /* Loop admin */
        /* To save unnecessary copying, move back and forth between odd and even tables */
        SET @oddRound = (@currentRound % 2 = 1);
        /* Get rid of candidate words that are too long */
        IF @oddRound THEN SELECT MAX(`last_length`) INTO @max_last_length FROM compNodd;
        ELSE SELECT MAX(`last_length`) INTO @max_last_length FROM compNeven;
        END IF;
        DELETE FROM `wN` WHERE `length` > @max_last_length;
        /* ...now join wN into comp table... */
        /* ...twice over: (a) where length is equal and word is alphabetically equal or later, (b) where length is shorter */
        IF @offRound THEN
            INSERT INTO `compNodd`
            (
              `tome_id`,`word`,`bare_letters`,`composite_word`,`composite_bare_letters`,`last_length`,`length`,`a`,`b`,`c`,`d`,`e`,`f`,`g`,`h`,`i`,`j`,`k`,`l`,`m`,`n`,`o`,`p`,`q`,`r`,`s`,`t`,`u`,`v`,`w`,`x`,`y`,`z`
            )
            SELECT 0 AS tome_id, `wN`.`word` AS `word`, `wN`.`bare_letters` AS `bare_letters`, CONCAT(`compNeven`.`word`,' ',`wN`.`word`) AS `composite_word`, CONCAT(compNeven.`bare_letters`,wN.`bare_letters`) AS `composite_bare_letters`
            , `wN`.`length` AS `last_length`,`compNeven`.`length`-`wN`.`length` AS `length`,`compNeven`.`a`-`wN`.`a` AS `a`,`compNeven`.`b`-`wN`.`b` AS `b`
            ,`compNeven`.`c`-`wN`.`c` AS `c`,`compNeven`.`d`-`wN`.`d` AS `d`,`compNeven`.`e`-`wN`.`e` AS `e`,`compNeven`.`f`-`wN`.`f` AS `f`
            ,`compNeven`.`g`-`wN`.`g` AS `g`,`compNeven`.`h`-`wN`.`h` AS `h`,`compNeven`.`i`-`wN`.`i` AS `i`,`compNeven`.`j`-`wN`.`j` AS `j`
            ,`compNeven`.`k`-`wN`.`k` AS `k`,`compNeven`.`l`-`wN`.`l` AS `l`,`compNeven`.`m`-`wN`.`m` AS `m`,`compNeven`.`n`-`wN`.`n` AS `n`
            ,`compNeven`.`o`-`wN`.`o` AS `o`,`compNeven`.`p`-`wN`.`p` AS `p`,`compNeven`.`q`-`wN`.`q` AS `q`,`compNeven`.`r`-`wN`.`r` AS `r`
            ,`compNeven`.`s`-`wN`.`s` AS `s`,`compNeven`.`t`-`wN`.`t` AS `t`,`compNeven`.`u`-`wN`.`u` AS `u`,`compNeven`.`v`-`wN`.`v` AS `v`
            ,`compNeven`.`w`-`wN`.`w` AS `w`,`compNeven`.`x`-`wN`.`x` AS `x`,`compNeven`.`y`-`wN`.`y` AS `y`,`compNeven`.`z`-`wN`.`z` AS `z`
            FROM `compNeven`
            INNER JOIN `wN` ON wN.`length`<=`compNeven`.`length`
             AND `wN`.`length` = `compNeven`.`last_length` AND `wN`.`bare_letters`>=`compNeven`.`bare_letters` /* Join to equal-length words that are alphabetically equal or later */
             AND `wN`.`a`<=`compNeven`.`a` AND `wN`.`b`<=`compNeven`.`b`
             AND `wN`.`c`<=`compNeven`.`c` AND `wN`.`d`<=`compNeven`.`d` AND `wN`.`e`<=`compNeven`.`e` AND `wN`.`f`<=`compNeven`.`f`
             AND `wN`.`g`<=`compNeven`.`g` AND `wN`.`h`<=`compNeven`.`h` AND `wN`.`i`<=`compNeven`.`i` AND `wN`.`j`<=`compNeven`.`j`
             AND `wN`.`k`<=`compNeven`.`k` AND `wN`.`l`<=`compNeven`.`l` AND `wN`.`m`<=`compNeven`.`m` AND `wN`.`n`<=`compNeven`.`n`
             AND `wN`.`o`<=`compNeven`.`o` AND `wN`.`p`<=`compNeven`.`p` AND `wN`.`q`<=`compNeven`.`q` AND `wN`.`r`<=`compNeven`.`r`
             AND `wN`.`s`<=`compNeven`.`s` AND `wN`.`t`<=`compNeven`.`t` AND `wN`.`u`<=`compNeven`.`u` AND `wN`.`v`<=`compNeven`.`v`
             AND `wN`.`w`<=`compNeven`.`w` AND `wN`.`x`<=`compNeven`.`x` AND `wN`.`y`<=`compNeven`.`y` AND `wN`.`z`<=`compNeven`.`z`
             ;
             INSERT INTO `compNodd`
            (
              `tome_id`,`word`,`bare_letters`,`composite_word`,`composite_bare_letters`,`last_length`,`length`,`a`,`b`,`c`,`d`,`e`,`f`,`g`,`h`,`i`,`j`,`k`,`l`,`m`,`n`,`o`,`p`,`q`,`r`,`s`,`t`,`u`,`v`,`w`,`x`,`y`,`z`
            )
            SELECT 0 AS tome_id, `wN`.`word` AS `word`, `wN`.`bare_letters` AS `bare_letters`, CONCAT(`compNeven`.`word`,' ',`wN`.`word`) AS `composite_word`, CONCAT(compNeven.`bare_letters`,wN.`bare_letters`) AS `composite_bare_letters`
            , `wN`.`length` AS `last_length`,`compNeven`.`length`-`wN`.`length` AS `length`,`compNeven`.`a`-`wN`.`a` AS `a`,`compNeven`.`b`-`wN`.`b` AS `b`
            ,`compNeven`.`c`-`wN`.`c` AS `c`,`compNeven`.`d`-`wN`.`d` AS `d`,`compNeven`.`e`-`wN`.`e` AS `e`,`compNeven`.`f`-`wN`.`f` AS `f`
            ,`compNeven`.`g`-`wN`.`g` AS `g`,`compNeven`.`h`-`wN`.`h` AS `h`,`compNeven`.`i`-`wN`.`i` AS `i`,`compNeven`.`j`-`wN`.`j` AS `j`
            ,`compNeven`.`k`-`wN`.`k` AS `k`,`compNeven`.`l`-`wN`.`l` AS `l`,`compNeven`.`m`-`wN`.`m` AS `m`,`compNeven`.`n`-`wN`.`n` AS `n`
            ,`compNeven`.`o`-`wN`.`o` AS `o`,`compNeven`.`p`-`wN`.`p` AS `p`,`compNeven`.`q`-`wN`.`q` AS `q`,`compNeven`.`r`-`wN`.`r` AS `r`
            ,`compNeven`.`s`-`wN`.`s` AS `s`,`compNeven`.`t`-`wN`.`t` AS `t`,`compNeven`.`u`-`wN`.`u` AS `u`,`compNeven`.`v`-`wN`.`v` AS `v`
            ,`compNeven`.`w`-`wN`.`w` AS `w`,`compNeven`.`x`-`wN`.`x` AS `x`,`compNeven`.`y`-`wN`.`y` AS `y`,`compNeven`.`z`-`wN`.`z` AS `z`
            FROM `compNeven`
            INNER JOIN `wN` ON wN.`length`<=`compNeven`.`length`
             AND `wN`.`length` < `compNeven`.`last_length` /* Join to shorter words */
             AND `wN`.`a`<=`compNeven`.`a` AND `wN`.`b`<=`compNeven`.`b`
             AND `wN`.`c`<=`compNeven`.`c` AND `wN`.`d`<=`compNeven`.`d` AND `wN`.`e`<=`compNeven`.`e` AND `wN`.`f`<=`compNeven`.`f`
             AND `wN`.`g`<=`compNeven`.`g` AND `wN`.`h`<=`compNeven`.`h` AND `wN`.`i`<=`compNeven`.`i` AND `wN`.`j`<=`compNeven`.`j`
             AND `wN`.`k`<=`compNeven`.`k` AND `wN`.`l`<=`compNeven`.`l` AND `wN`.`m`<=`compNeven`.`m` AND `wN`.`n`<=`compNeven`.`n`
             AND `wN`.`o`<=`compNeven`.`o` AND `wN`.`p`<=`compNeven`.`p` AND `wN`.`q`<=`compNeven`.`q` AND `wN`.`r`<=`compNeven`.`r`
             AND `wN`.`s`<=`compNeven`.`s` AND `wN`.`t`<=`compNeven`.`t` AND `wN`.`u`<=`compNeven`.`u` AND `wN`.`v`<=`compNeven`.`v`
             AND `wN`.`w`<=`compNeven`.`w` AND `wN`.`x`<=`compNeven`.`x` AND `wN`.`y`<=`compNeven`.`y` AND `wN`.`z`<=`compNeven`.`z`
             ;
             /* Save results */
             INSERT INTO `results` (`word`,`bare_letters`)
             SELECT `composite_word`,`composite_bare_letters`
             FROM `compNodd`
             WHERE `length`=0
             ORDER BY `last_length` DESC
             ;
        ELSE
            INSERT INTO `compNeven`
            (
              `tome_id`,`word`,`bare_letters`,`composite_word`,`composite_bare_letters`,`last_length`,`length`,`a`,`b`,`c`,`d`,`e`,`f`,`g`,`h`,`i`,`j`,`k`,`l`,`m`,`n`,`o`,`p`,`q`,`r`,`s`,`t`,`u`,`v`,`w`,`x`,`y`,`z`
            )
            SELECT 0 AS tome_id, `wN`.`word` AS `word`, `wN`.`bare_letters` AS `bare_letters`, CONCAT(`compNodd`.`word`,' ',`wN`.`word`) AS `composite_word`, CONCAT(compNodd.`bare_letters`,wN.`bare_letters`) AS `composite_bare_letters`
            , `wN`.`length` AS `last_length`,`compNodd`.`length`-`wN`.`length` AS `length`,`compNodd`.`a`-`wN`.`a` AS `a`,`compNodd`.`b`-`wN`.`b` AS `b`
            ,`compNodd`.`c`-`wN`.`c` AS `c`,`compNodd`.`d`-`wN`.`d` AS `d`,`compNodd`.`e`-`wN`.`e` AS `e`,`compNodd`.`f`-`wN`.`f` AS `f`
            ,`compNodd`.`g`-`wN`.`g` AS `g`,`compNodd`.`h`-`wN`.`h` AS `h`,`compNodd`.`i`-`wN`.`i` AS `i`,`compNodd`.`j`-`wN`.`j` AS `j`
            ,`compNodd`.`k`-`wN`.`k` AS `k`,`compNodd`.`l`-`wN`.`l` AS `l`,`compNodd`.`m`-`wN`.`m` AS `m`,`compNodd`.`n`-`wN`.`n` AS `n`
            ,`compNodd`.`o`-`wN`.`o` AS `o`,`compNodd`.`p`-`wN`.`p` AS `p`,`compNodd`.`q`-`wN`.`q` AS `q`,`compNodd`.`r`-`wN`.`r` AS `r`
            ,`compNodd`.`s`-`wN`.`s` AS `s`,`compNodd`.`t`-`wN`.`t` AS `t`,`compNodd`.`u`-`wN`.`u` AS `u`,`compNodd`.`v`-`wN`.`v` AS `v`
            ,`compNodd`.`w`-`wN`.`w` AS `w`,`compNodd`.`x`-`wN`.`x` AS `x`,`compNodd`.`y`-`wN`.`y` AS `y`,`compNodd`.`z`-`wN`.`z` AS `z`
            FROM `compNodd`
            INNER JOIN `wN` ON wN.`length`<=`compNodd`.`length`
             AND `wN`.`length` = `compNodd`.`last_length` AND `wN`.`bare_letters`>=`compNodd`.`bare_letters` /* Join to equal-length words that are alphabetically equal or later */
             AND `wN`.`a`<=`compNodd`.`a` AND `wN`.`b`<=`compNodd`.`b`
             AND `wN`.`c`<=`compNodd`.`c` AND `wN`.`d`<=`compNodd`.`d` AND `wN`.`e`<=`compNodd`.`e` AND `wN`.`f`<=`compNodd`.`f`
             AND `wN`.`g`<=`compNodd`.`g` AND `wN`.`h`<=`compNodd`.`h` AND `wN`.`i`<=`compNodd`.`i` AND `wN`.`j`<=`compNodd`.`j`
             AND `wN`.`k`<=`compNodd`.`k` AND `wN`.`l`<=`compNodd`.`l` AND `wN`.`m`<=`compNodd`.`m` AND `wN`.`n`<=`compNodd`.`n`
             AND `wN`.`o`<=`compNodd`.`o` AND `wN`.`p`<=`compNodd`.`p` AND `wN`.`q`<=`compNodd`.`q` AND `wN`.`r`<=`compNodd`.`r`
             AND `wN`.`s`<=`compNodd`.`s` AND `wN`.`t`<=`compNodd`.`t` AND `wN`.`u`<=`compNodd`.`u` AND `wN`.`v`<=`compNodd`.`v`
             AND `wN`.`w`<=`compNodd`.`w` AND `wN`.`x`<=`compNodd`.`x` AND `wN`.`y`<=`compNodd`.`y` AND `wN`.`z`<=`compNodd`.`z`
             ;
             INSERT INTO `compNeven`
            (
              `tome_id`,`word`,`bare_letters`,`composite_word`,`composite_bare_letters`,`last_length`,`length`,`a`,`b`,`c`,`d`,`e`,`f`,`g`,`h`,`i`,`j`,`k`,`l`,`m`,`n`,`o`,`p`,`q`,`r`,`s`,`t`,`u`,`v`,`w`,`x`,`y`,`z`
            )
            SELECT 0 AS tome_id, `wN`.`word` AS `word`, `wN`.`bare_letters` AS `bare_letters`, CONCAT(`compNodd`.`word`,' ',`wN`.`word`) AS `composite_word`, CONCAT(compNodd.`bare_letters`,wN.`bare_letters`) AS `composite_bare_letters`
            , `wN`.`length` AS `last_length`,`compNodd`.`length`-`wN`.`length` AS `length`,`compNodd`.`a`-`wN`.`a` AS `a`,`compNodd`.`b`-`wN`.`b` AS `b`
            ,`compNodd`.`c`-`wN`.`c` AS `c`,`compNodd`.`d`-`wN`.`d` AS `d`,`compNodd`.`e`-`wN`.`e` AS `e`,`compNodd`.`f`-`wN`.`f` AS `f`
            ,`compNodd`.`g`-`wN`.`g` AS `g`,`compNodd`.`h`-`wN`.`h` AS `h`,`compNodd`.`i`-`wN`.`i` AS `i`,`compNodd`.`j`-`wN`.`j` AS `j`
            ,`compNodd`.`k`-`wN`.`k` AS `k`,`compNodd`.`l`-`wN`.`l` AS `l`,`compNodd`.`m`-`wN`.`m` AS `m`,`compNodd`.`n`-`wN`.`n` AS `n`
            ,`compNodd`.`o`-`wN`.`o` AS `o`,`compNodd`.`p`-`wN`.`p` AS `p`,`compNodd`.`q`-`wN`.`q` AS `q`,`compNodd`.`r`-`wN`.`r` AS `r`
            ,`compNodd`.`s`-`wN`.`s` AS `s`,`compNodd`.`t`-`wN`.`t` AS `t`,`compNodd`.`u`-`wN`.`u` AS `u`,`compNodd`.`v`-`wN`.`v` AS `v`
            ,`compNodd`.`w`-`wN`.`w` AS `w`,`compNodd`.`x`-`wN`.`x` AS `x`,`compNodd`.`y`-`wN`.`y` AS `y`,`compNodd`.`z`-`wN`.`z` AS `z`
            FROM `compNodd`
            INNER JOIN `wN` ON wN.`length`<=`compNodd`.`length`
             AND `wN`.`length` < `compNodd`.`last_length` /* Join to shorter words */
             AND `wN`.`a`<=`compNodd`.`a` AND `wN`.`b`<=`compNodd`.`b`
             AND `wN`.`c`<=`compNodd`.`c` AND `wN`.`d`<=`compNodd`.`d` AND `wN`.`e`<=`compNodd`.`e` AND `wN`.`f`<=`compNodd`.`f`
             AND `wN`.`g`<=`compNodd`.`g` AND `wN`.`h`<=`compNodd`.`h` AND `wN`.`i`<=`compNodd`.`i` AND `wN`.`j`<=`compNodd`.`j`
             AND `wN`.`k`<=`compNodd`.`k` AND `wN`.`l`<=`compNodd`.`l` AND `wN`.`m`<=`compNodd`.`m` AND `wN`.`n`<=`compNodd`.`n`
             AND `wN`.`o`<=`compNodd`.`o` AND `wN`.`p`<=`compNodd`.`p` AND `wN`.`q`<=`compNodd`.`q` AND `wN`.`r`<=`compNodd`.`r`
             AND `wN`.`s`<=`compNodd`.`s` AND `wN`.`t`<=`compNodd`.`t` AND `wN`.`u`<=`compNodd`.`u` AND `wN`.`v`<=`compNodd`.`v`
             AND `wN`.`w`<=`compNodd`.`w` AND `wN`.`x`<=`compNodd`.`x` AND `wN`.`y`<=`compNodd`.`y` AND `wN`.`z`<=`compNodd`.`z`
             ;
             /* Save results */
             INSERT INTO `results` (`word`,`bare_letters`)
             SELECT `composite_word`,`composite_bare_letters`
             FROM `compNeven`
             WHERE `length`=0
             ORDER BY `last_length` DESC
             ;
        END IF;
	END WHILE;
END IF;



/* Below is legacy code, which was largely working, but needs refactoring to fit the new n-round format */

/* Now set up our next table - probably with even fewer entries than w1 */
INSERT INTO `w2` SELECT * FROM `w1` WHERE `length`<=(SELECT MAX(`last_length`) FROM `comp1`);

/* ...now join it into comp2 */
/* ...twice over: (a) where length is equal and word is alphabetically equal or later, (b) where length is shorter */
INSERT INTO `comp2`
(
  `tome_id`,`word`,`bare_letters`,`composite_word`,`composite_bare_letters`,`last_length`,`length`,`a`,`b`,`c`,`d`,`e`,`f`,`g`,`h`,`i`,`j`,`k`,`l`,`m`,`n`,`o`,`p`,`q`,`r`,`s`,`t`,`u`,`v`,`w`,`x`,`y`,`z`
)
SELECT 0 AS tome_id, `w2`.`word` AS `word`, `w2`.`bare_letters` AS `bare_letters`, CONCAT(`comp1`.`word`,' ',`w2`.`word`) AS `composite_word`, CONCAT(comp1.`bare_letters`,w2.`bare_letters`) AS `composite_bare_letters`
, `w2`.`length` AS `last_length`,`comp1`.`length`-`w2`.`length` AS `length`,`comp1`.`a`-`w2`.`a` AS `a`,`comp1`.`b`-`w2`.`b` AS `b`
,`comp1`.`c`-`w2`.`c` AS `c`,`comp1`.`d`-`w2`.`d` AS `d`,`comp1`.`e`-`w2`.`e` AS `e`,`comp1`.`f`-`w2`.`f` AS `f`
,`comp1`.`g`-`w2`.`g` AS `g`,`comp1`.`h`-`w2`.`h` AS `h`,`comp1`.`i`-`w2`.`i` AS `i`,`comp1`.`j`-`w2`.`j` AS `j`
,`comp1`.`k`-`w2`.`k` AS `k`,`comp1`.`l`-`w2`.`l` AS `l`,`comp1`.`m`-`w2`.`m` AS `m`,`comp1`.`n`-`w2`.`n` AS `n`
,`comp1`.`o`-`w2`.`o` AS `o`,`comp1`.`p`-`w2`.`p` AS `p`,`comp1`.`q`-`w2`.`q` AS `q`,`comp1`.`r`-`w2`.`r` AS `r`
,`comp1`.`s`-`w2`.`s` AS `s`,`comp1`.`t`-`w2`.`t` AS `t`,`comp1`.`u`-`w2`.`u` AS `u`,`comp1`.`v`-`w2`.`v` AS `v`
,`comp1`.`w`-`w2`.`w` AS `w`,`comp1`.`x`-`w2`.`x` AS `x`,`comp1`.`y`-`w2`.`y` AS `y`,`comp1`.`z`-`w2`.`z` AS `z`
FROM `comp1`
INNER JOIN `w2` ON w2.`length`<=`comp1`.`length`
 AND `w2`.`length` = `comp1`.`last_length` AND `w2`.`bare_letters`>=`comp1`.`bare_letters` /* Join to equal-length words that are alphabetically equal or later */
 AND `w2`.`a`<=`comp1`.`a` AND `w2`.`b`<=`comp1`.`b`
 AND `w2`.`c`<=`comp1`.`c` AND `w2`.`d`<=`comp1`.`d` AND `w2`.`e`<=`comp1`.`e` AND `w2`.`f`<=`comp1`.`f`
 AND `w2`.`g`<=`comp1`.`g` AND `w2`.`h`<=`comp1`.`h` AND `w2`.`i`<=`comp1`.`i` AND `w2`.`j`<=`comp1`.`j`
 AND `w2`.`k`<=`comp1`.`k` AND `w2`.`l`<=`comp1`.`l` AND `w2`.`m`<=`comp1`.`m` AND `w2`.`n`<=`comp1`.`n`
 AND `w2`.`o`<=`comp1`.`o` AND `w2`.`p`<=`comp1`.`p` AND `w2`.`q`<=`comp1`.`q` AND `w2`.`r`<=`comp1`.`r`
 AND `w2`.`s`<=`comp1`.`s` AND `w2`.`t`<=`comp1`.`t` AND `w2`.`u`<=`comp1`.`u` AND `w2`.`v`<=`comp1`.`v`
 AND `w2`.`w`<=`comp1`.`w` AND `w2`.`x`<=`comp1`.`x` AND `w2`.`y`<=`comp1`.`y` AND `w2`.`z`<=`comp1`.`z`
 ;
 INSERT INTO `comp2`
(
  `tome_id`,`word`,`bare_letters`,`composite_word`,`composite_bare_letters`,`last_length`,`length`,`a`,`b`,`c`,`d`,`e`,`f`,`g`,`h`,`i`,`j`,`k`,`l`,`m`,`n`,`o`,`p`,`q`,`r`,`s`,`t`,`u`,`v`,`w`,`x`,`y`,`z`
)
SELECT 0 AS tome_id, `w2`.`word` AS `word`, `w2`.`bare_letters` AS `bare_letters`, CONCAT(`comp1`.`word`,' ',`w2`.`word`) AS `composite_word`, CONCAT(comp1.`bare_letters`,w2.`bare_letters`) AS `composite_bare_letters`
, `w2`.`length` AS `last_length`,`comp1`.`length`-`w2`.`length` AS `length`,`comp1`.`a`-`w2`.`a` AS `a`,`comp1`.`b`-`w2`.`b` AS `b`
,`comp1`.`c`-`w2`.`c` AS `c`,`comp1`.`d`-`w2`.`d` AS `d`,`comp1`.`e`-`w2`.`e` AS `e`,`comp1`.`f`-`w2`.`f` AS `f`
,`comp1`.`g`-`w2`.`g` AS `g`,`comp1`.`h`-`w2`.`h` AS `h`,`comp1`.`i`-`w2`.`i` AS `i`,`comp1`.`j`-`w2`.`j` AS `j`
,`comp1`.`k`-`w2`.`k` AS `k`,`comp1`.`l`-`w2`.`l` AS `l`,`comp1`.`m`-`w2`.`m` AS `m`,`comp1`.`n`-`w2`.`n` AS `n`
,`comp1`.`o`-`w2`.`o` AS `o`,`comp1`.`p`-`w2`.`p` AS `p`,`comp1`.`q`-`w2`.`q` AS `q`,`comp1`.`r`-`w2`.`r` AS `r`
,`comp1`.`s`-`w2`.`s` AS `s`,`comp1`.`t`-`w2`.`t` AS `t`,`comp1`.`u`-`w2`.`u` AS `u`,`comp1`.`v`-`w2`.`v` AS `v`
,`comp1`.`w`-`w2`.`w` AS `w`,`comp1`.`x`-`w2`.`x` AS `x`,`comp1`.`y`-`w2`.`y` AS `y`,`comp1`.`z`-`w2`.`z` AS `z`
FROM `comp1`
INNER JOIN `w2` ON w2.`length`<=`comp1`.`length`
 AND `w2`.`length` < `comp1`.`last_length` /* Join to shorter words */
 AND `w2`.`a`<=`comp1`.`a` AND `w2`.`b`<=`comp1`.`b`
 AND `w2`.`c`<=`comp1`.`c` AND `w2`.`d`<=`comp1`.`d` AND `w2`.`e`<=`comp1`.`e` AND `w2`.`f`<=`comp1`.`f`
 AND `w2`.`g`<=`comp1`.`g` AND `w2`.`h`<=`comp1`.`h` AND `w2`.`i`<=`comp1`.`i` AND `w2`.`j`<=`comp1`.`j`
 AND `w2`.`k`<=`comp1`.`k` AND `w2`.`l`<=`comp1`.`l` AND `w2`.`m`<=`comp1`.`m` AND `w2`.`n`<=`comp1`.`n`
 AND `w2`.`o`<=`comp1`.`o` AND `w2`.`p`<=`comp1`.`p` AND `w2`.`q`<=`comp1`.`q` AND `w2`.`r`<=`comp1`.`r`
 AND `w2`.`s`<=`comp1`.`s` AND `w2`.`t`<=`comp1`.`t` AND `w2`.`u`<=`comp1`.`u` AND `w2`.`v`<=`comp1`.`v`
 AND `w2`.`w`<=`comp1`.`w` AND `w2`.`x`<=`comp1`.`x` AND `w2`.`y`<=`comp1`.`y` AND `w2`.`z`<=`comp1`.`z`
 ;

INSERT INTO `results` (`word`,`bare_letters`)
SELECT `composite_word`,`composite_bare_letters`
FROM `comp2`
WHERE `length`=0
ORDER BY `last_length` DESC
;

/* Now set up our next table - probably with even fewer entries than w1 */
INSERT INTO `w3` SELECT * FROM `w1` WHERE `length`<=(SELECT MAX(`last_length`) FROM `comp2`);

/* ...now join it into comp3 */
/* ...twice over: (a) where length is equal and word is alphabetically equal or later, (b) where length is shorter */
INSERT INTO `comp3`
(
  `tome_id`,`word`,`bare_letters`,`composite_word`,`composite_bare_letters`,`last_length`,`length`,`a`,`b`,`c`,`d`,`e`,`f`,`g`,`h`,`i`,`j`,`k`,`l`,`m`,`n`,`o`,`p`,`q`,`r`,`s`,`t`,`u`,`v`,`w`,`x`,`y`,`z`
)
SELECT 0 AS tome_id, `w3`.`word` AS `word`, `w3`.`bare_letters` AS `bare_letters`, CONCAT(`comp2`.`word`,' ',`w3`.`word`) AS `composite_word`, CONCAT(comp2.`bare_letters`,w3.`bare_letters`) AS `composite_bare_letters`
, `w3`.`length` AS `last_length`,`comp2`.`length`-`w3`.`length` AS `length`
,`comp2`.`a`-`w3`.`a` AS `a`,`comp2`.`b`-`w3`.`b` AS `b`
,`comp2`.`c`-`w3`.`c` AS `c`,`comp2`.`d`-`w3`.`d` AS `d`
,`comp2`.`e`-`w3`.`e` AS `e`,`comp2`.`f`-`w3`.`f` AS `f`
,`comp2`.`g`-`w3`.`g` AS `g`,`comp2`.`h`-`w3`.`h` AS `h`
,`comp2`.`i`-`w3`.`i` AS `i`,`comp2`.`j`-`w3`.`j` AS `j`
,`comp2`.`k`-`w3`.`k` AS `k`,`comp2`.`l`-`w3`.`l` AS `l`
,`comp2`.`m`-`w3`.`m` AS `m`,`comp2`.`n`-`w3`.`n` AS `n`
,`comp2`.`o`-`w3`.`o` AS `o`,`comp2`.`p`-`w3`.`p` AS `p`
,`comp2`.`q`-`w3`.`q` AS `q`,`comp2`.`r`-`w3`.`r` AS `r`
,`comp2`.`s`-`w3`.`s` AS `s`,`comp2`.`t`-`w3`.`t` AS `t`
,`comp2`.`u`-`w3`.`u` AS `u`,`comp2`.`v`-`w3`.`v` AS `v`
,`comp2`.`w`-`w3`.`w` AS `w`,`comp2`.`x`-`w3`.`x` AS `x`
,`comp2`.`y`-`w3`.`y` AS `y`,`comp2`.`z`-`w3`.`z` AS `z`
FROM `comp2`
INNER JOIN `w3` ON w3.`length`<=`comp2`.`length`
 AND `w3`.`length` = `comp2`.`last_length` AND `w3`.`bare_letters`>=`comp2`.`bare_letters` /* Join to equal-length words that are alphabetically equal or later */
 AND `w3`.`length` <= `comp2`.`last_length`
 AND `w3`.`a`<=`comp2`.`a` AND `w3`.`b`<=`comp2`.`b`
 AND `w3`.`c`<=`comp2`.`c` AND `w3`.`d`<=`comp2`.`d`
 AND `w3`.`e`<=`comp2`.`e` AND `w3`.`f`<=`comp2`.`f`
 AND `w3`.`g`<=`comp2`.`g` AND `w3`.`h`<=`comp2`.`h`
 AND `w3`.`i`<=`comp2`.`i` AND `w3`.`j`<=`comp2`.`j`
 AND `w3`.`k`<=`comp2`.`k` AND `w3`.`l`<=`comp2`.`l`
 AND `w3`.`m`<=`comp2`.`m` AND `w3`.`n`<=`comp2`.`n`
 AND `w3`.`o`<=`comp2`.`o` AND `w3`.`p`<=`comp2`.`p`
 AND `w3`.`q`<=`comp2`.`q` AND `w3`.`r`<=`comp2`.`r`
 AND `w3`.`s`<=`comp2`.`s` AND `w3`.`t`<=`comp2`.`t`
 AND `w3`.`u`<=`comp2`.`u` AND `w3`.`v`<=`comp2`.`v`
 AND `w3`.`w`<=`comp2`.`w` AND `w3`.`x`<=`comp2`.`x`
 AND `w3`.`y`<=`comp2`.`y` AND `w3`.`z`<=`comp2`.`z`
 ;
 INSERT INTO `comp3`
(
  `tome_id`,`word`,`bare_letters`,`composite_word`,`composite_bare_letters`,`last_length`,`length`,`a`,`b`,`c`,`d`,`e`,`f`,`g`,`h`,`i`,`j`,`k`,`l`,`m`,`n`,`o`,`p`,`q`,`r`,`s`,`t`,`u`,`v`,`w`,`x`,`y`,`z`
)
SELECT 0 AS tome_id, `w3`.`word` AS `word`, `w3`.`bare_letters` AS `bare_letters`, CONCAT(`comp2`.`word`,' ',`w3`.`word`) AS `composite_word`, CONCAT(comp2.`bare_letters`,w3.`bare_letters`) AS `composite_bare_letters`
, `w3`.`length` AS `last_length`,`comp2`.`length`-`w3`.`length` AS `length`
,`comp2`.`a`-`w3`.`a` AS `a`,`comp2`.`b`-`w3`.`b` AS `b`
,`comp2`.`c`-`w3`.`c` AS `c`,`comp2`.`d`-`w3`.`d` AS `d`
,`comp2`.`e`-`w3`.`e` AS `e`,`comp2`.`f`-`w3`.`f` AS `f`
,`comp2`.`g`-`w3`.`g` AS `g`,`comp2`.`h`-`w3`.`h` AS `h`
,`comp2`.`i`-`w3`.`i` AS `i`,`comp2`.`j`-`w3`.`j` AS `j`
,`comp2`.`k`-`w3`.`k` AS `k`,`comp2`.`l`-`w3`.`l` AS `l`
,`comp2`.`m`-`w3`.`m` AS `m`,`comp2`.`n`-`w3`.`n` AS `n`
,`comp2`.`o`-`w3`.`o` AS `o`,`comp2`.`p`-`w3`.`p` AS `p`
,`comp2`.`q`-`w3`.`q` AS `q`,`comp2`.`r`-`w3`.`r` AS `r`
,`comp2`.`s`-`w3`.`s` AS `s`,`comp2`.`t`-`w3`.`t` AS `t`
,`comp2`.`u`-`w3`.`u` AS `u`,`comp2`.`v`-`w3`.`v` AS `v`
,`comp2`.`w`-`w3`.`w` AS `w`,`comp2`.`x`-`w3`.`x` AS `x`
,`comp2`.`y`-`w3`.`y` AS `y`,`comp2`.`z`-`w3`.`z` AS `z`
FROM `comp2`
INNER JOIN `w3` ON w3.`length`<=`comp2`.`length`
 AND `w3`.`length` < `comp2`.`last_length` /* Join to shorter words */
 AND `w3`.`length` <= `comp2`.`last_length`
 AND `w3`.`a`<=`comp2`.`a` AND `w3`.`b`<=`comp2`.`b`
 AND `w3`.`c`<=`comp2`.`c` AND `w3`.`d`<=`comp2`.`d`
 AND `w3`.`e`<=`comp2`.`e` AND `w3`.`f`<=`comp2`.`f`
 AND `w3`.`g`<=`comp2`.`g` AND `w3`.`h`<=`comp2`.`h`
 AND `w3`.`i`<=`comp2`.`i` AND `w3`.`j`<=`comp2`.`j`
 AND `w3`.`k`<=`comp2`.`k` AND `w3`.`l`<=`comp2`.`l`
 AND `w3`.`m`<=`comp2`.`m` AND `w3`.`n`<=`comp2`.`n`
 AND `w3`.`o`<=`comp2`.`o` AND `w3`.`p`<=`comp2`.`p`
 AND `w3`.`q`<=`comp2`.`q` AND `w3`.`r`<=`comp2`.`r`
 AND `w3`.`s`<=`comp2`.`s` AND `w3`.`t`<=`comp2`.`t`
 AND `w3`.`u`<=`comp2`.`u` AND `w3`.`v`<=`comp2`.`v`
 AND `w3`.`w`<=`comp2`.`w` AND `w3`.`x`<=`comp2`.`x`
 AND `w3`.`y`<=`comp2`.`y` AND `w3`.`z`<=`comp2`.`z`
 ;

INSERT INTO `results` (`word`,`bare_letters`)
SELECT `composite_word`,`composite_bare_letters`
FROM `comp3`
WHERE `length`=0
ORDER BY `last_length` DESC
;

/* END LEGACY CODE */

SELECT *
FROM `results`
;

SELECT 'w1' AS `tbl`, COUNT(*) AS `rowcount` FROM w1
UNION ALL
SELECT 'w2' AS `tbl`, COUNT(*) AS `rowcount` FROM w2
UNION ALL
SELECT 'w3' AS `tbl`, COUNT(*) AS `rowcount` FROM w3
;

	END$$

DELIMITER ;