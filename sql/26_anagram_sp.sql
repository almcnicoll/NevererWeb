 /* PREPAREMODE */
CREATE DEFINER=CURRENT_USER PROCEDURE `AnagramMethod01`(
	originalWord VARCHAR(20),
	maxWords TINYINT(1)
    )
    COMMENT "It works, but it's quite slow for longer words - stick to <=9 letters"
BEGIN
/* Variables for letter-loop */
DECLARE letterAsc INT DEFAULT 97;
DECLARE letterLower CHAR DEFAULT 'a';
DECLARE letterUpper CHAR DEFAULT 'A';

/* NB - this is not an exact copy of the JS function as we don't have access to Unicode normalisation */
SET @originalWord = originalWord;
SET @originalBareLetters = REPLACE(REPLACE(REPLACE(originalWord,"'",''),'-',''),' ','');
SET @originalLength = LENGTH(@originalBareLetters);
IF maxWords>6 THEN SET @maxRounds = 6;
ELSEIF maxWords<1 THEN SET @maxRounds = 1;
ELSE SET @maxRounds = maxWords;
END IF;

/* Set up temporary tables */
DROP TEMPORARY TABLE IF EXISTS `orig`;
DROP TEMPORARY TABLE IF EXISTS `wN`;
DROP TEMPORARY TABLE IF EXISTS `comp1`;
DROP TEMPORARY TABLE IF EXISTS `compNodd`;
DROP TEMPORARY TABLE IF EXISTS `compNeven`;
DROP TEMPORARY TABLE IF EXISTS `comp2`;
DROP TEMPORARY TABLE IF EXISTS `comp3`;
DROP TEMPORARY TABLE IF EXISTS `results`;
/* DROP TEMPORARY TABLE IF EXISTS `debug`; */
CREATE TEMPORARY TABLE `orig` LIKE `tome_entries`;
ALTER TABLE `orig` DROP COLUMN `created`, DROP COLUMN `modified`;
ALTER TABLE `orig` DROP INDEX `alphabetical`, DROP INDEX `filter`, DROP INDEX `filter2`;
CREATE TEMPORARY TABLE `wN` LIKE `orig`; /* NEW */
CREATE TEMPORARY TABLE `comp1` LIKE `tome_entries`;
ALTER TABLE `comp1` DROP COLUMN `created`, DROP COLUMN `modified`;
ALTER TABLE `comp1` DROP INDEX `alphabetical`, DROP INDEX `filter`, DROP INDEX `filter2`;
ALTER TABLE `comp1` ADD COLUMN `composite_word` VARCHAR(100) NOT NULL DEFAULT '';
ALTER TABLE `comp1` ADD COLUMN `composite_bare_letters` VARCHAR(100) NOT NULL DEFAULT '';
ALTER TABLE `comp1` ADD COLUMN `last_length` TINYINT(2) UNSIGNED DEFAULT NULL;
CREATE TEMPORARY TABLE `compNodd` LIKE `comp1`; /* NEW */
CREATE TEMPORARY TABLE `compNeven` LIKE `comp1`; /* NEW */
CREATE TEMPORARY TABLE `results` (
`id` INT(10) UNSIGNED AUTO_INCREMENT,
`word` VARCHAR (100) COLLATE utf8mb4_unicode_ci NOT NULL,
`bare_letters` VARCHAR (100) COLLATE utf8mb4_unicode_ci NOT NULL,
 PRIMARY KEY (`id`)
) /*ENGINE=MEMORY*/ DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; /* NEW */
/*CREATE TEMPORARY TABLE `debug` (
`id` INT(10) UNSIGNED AUTO_INCREMENT,
`message` VARCHAR (400) COLLATE utf8mb4_unicode_ci NOT NULL,
PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;*/ /* DEBUG */

/* Start timer */
SET @timeStart = NOW(6);

/* We want our original word in a single-row table */
INSERT INTO `orig` (`id`,`tome_id`,`word`,`bare_letters`,`length`) SELECT 0,0,@originalWord,@originalBareLetters,@originalLength;
/* Populate letter-columns */
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
WHERE `length` BETWEEN 0 AND @originalLength AND `a` BETWEEN 0 AND @orig_a AND `b` BETWEEN 0 AND @orig_b
 AND `c` BETWEEN 0 AND @orig_c AND `d` BETWEEN 0 AND @orig_d AND `e` BETWEEN 0 AND @orig_e AND `f` BETWEEN 0 AND @orig_f
 AND `g` BETWEEN 0 AND @orig_g AND `h` BETWEEN 0 AND @orig_h AND `i` BETWEEN 0 AND @orig_i AND `j` BETWEEN 0 AND @orig_j
 AND `k` BETWEEN 0 AND @orig_k AND `l` BETWEEN 0 AND @orig_l AND `m` BETWEEN 0 AND @orig_m AND `n` BETWEEN 0 AND @orig_n
 AND `o` BETWEEN 0 AND @orig_o AND `p` BETWEEN 0 AND @orig_p AND `q` BETWEEN 0 AND @orig_q AND `r` BETWEEN 0 AND @orig_r
 AND `s` BETWEEN 0 AND @orig_s AND `t` BETWEEN 0 AND @orig_t AND `u` BETWEEN 0 AND @orig_u AND `v` BETWEEN 0 AND @orig_v
 AND `w` BETWEEN 0 AND @orig_w AND `x` BETWEEN 0 AND @orig_x AND `y` BETWEEN 0 AND @orig_y AND `z` BETWEEN 0 AND @orig_z
;
/* INSERT INTO `debug` (`message`) SELECT CONCAT('Populated wN with ',COUNT(*),' rows') FROM `wN`; */ /* DEBUG */

/* The first round is slightly different, due to the initial join process */
/* INSERT INTO `debug` (`message`) VALUES (''); */ /* DEBUG */
/* INSERT INTO `debug` (`message`) VALUES ('Starting round 1'); */ /* DEBUG */
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
INNER JOIN `wN` ON `wN`.`length` BETWEEN 0 AND `orig`.`length` AND `wN`.`a` BETWEEN 0 AND `orig`.`a` AND `wN`.`b` BETWEEN 0 AND `orig`.`b`
 AND `wN`.`c` BETWEEN 0 AND `orig`.`c` AND `wN`.`d` BETWEEN 0 AND `orig`.`d` AND `wN`.`e` BETWEEN 0 AND `orig`.`e` AND `wN`.`f` BETWEEN 0 AND `orig`.`f`
 AND `wN`.`g` BETWEEN 0 AND `orig`.`g` AND `wN`.`h` BETWEEN 0 AND `orig`.`h` AND `wN`.`i` BETWEEN 0 AND `orig`.`i` AND `wN`.`j` BETWEEN 0 AND `orig`.`j`
 AND `wN`.`k` BETWEEN 0 AND `orig`.`k` AND `wN`.`l` BETWEEN 0 AND `orig`.`l` AND `wN`.`m` BETWEEN 0 AND `orig`.`m` AND `wN`.`n` BETWEEN 0 AND `orig`.`n`
 AND `wN`.`o` BETWEEN 0 AND `orig`.`o` AND `wN`.`p` BETWEEN 0 AND `orig`.`p` AND `wN`.`q` BETWEEN 0 AND `orig`.`q` AND `wN`.`r` BETWEEN 0 AND `orig`.`r`
 AND `wN`.`s` BETWEEN 0 AND `orig`.`s` AND `wN`.`t` BETWEEN 0 AND `orig`.`t` AND `wN`.`u` BETWEEN 0 AND `orig`.`u` AND `wN`.`v` BETWEEN 0 AND `orig`.`v`
 AND `wN`.`w` BETWEEN 0 AND `orig`.`w` AND `wN`.`x` BETWEEN 0 AND `orig`.`x` AND `wN`.`y` BETWEEN 0 AND `orig`.`y` AND `wN`.`z` BETWEEN 0 AND `orig`.`z`
 ;
/* INSERT INTO `debug` (`message`) SELECT CONCAT('Populated compNodd with ',COUNT(*),' rows') FROM `compNodd`; */ /* DEBUG */
/* INSERT INTO `debug` (`message`) SELECT CONCAT('Outputting ',COUNT(*),' rows to results') FROM `compNodd` WHERE `length`=0; */ /* DEBUG */

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
        /* INSERT INTO `debug` (`message`) VALUES (''); */ /* DEBUG */
        /* INSERT INTO `debug` (`message`) SELECT CONCAT('Starting round ',@currentRound); */ /* DEBUG */
        /* To save unnecessary copying, move back and forth between odd and even tables */
        SET @oddRound = (@currentRound % 2 = 1);
        /* Get rid of candidate words that are too long */
        IF @oddRound THEN SELECT MAX(`last_length`) INTO @max_last_length FROM compNeven;
        ELSE SELECT MAX(`last_length`) INTO @max_last_length FROM compNodd;
        END IF;
        /* Get max counts per letter */
        IF @oddRound THEN
            SELECT MAX(`a`),MAX(`b`),MAX(`c`),MAX(`d`),MAX(`e`),MAX(`f`),MAX(`g`),MAX(`h`),MAX(`i`),MAX(`j`),MAX(`k`),MAX(`l`),MAX(`m`)
                ,MAX(`n`),MAX(`o`),MAX(`p`),MAX(`q`),MAX(`r`),MAX(`s`),MAX(`t`),MAX(`u`),MAX(`v`),MAX(`w`),MAX(`x`),MAX(`y`),MAX(`z`)
             INTO  @max_a, @max_b, @max_c, @max_d, @max_e, @max_f, @max_g, @max_h, @max_i, @max_j, @max_k, @max_l, @max_m
                , @max_n, @max_o, @max_p, @max_q, @max_r, @max_s, @max_t, @max_u, @max_v, @max_w, @max_x, @max_y, @max_z
            FROM `compNeven`;
        ELSE
            SELECT MAX(`a`),MAX(`b`),MAX(`c`),MAX(`d`),MAX(`e`),MAX(`f`),MAX(`g`),MAX(`h`),MAX(`i`),MAX(`j`),MAX(`k`),MAX(`l`),MAX(`m`)
                ,MAX(`n`),MAX(`o`),MAX(`p`),MAX(`q`),MAX(`r`),MAX(`s`),MAX(`t`),MAX(`u`),MAX(`v`),MAX(`w`),MAX(`x`),MAX(`y`),MAX(`z`)
             INTO  @max_a, @max_b, @max_c, @max_d, @max_e, @max_f, @max_g, @max_h, @max_i, @max_j, @max_k, @max_l, @max_m
                , @max_n, @max_o, @max_p, @max_q, @max_r, @max_s, @max_t, @max_u, @max_v, @max_w, @max_x, @max_y, @max_z
            FROM `compNodd`;
        END IF;
        /* INSERT INTO `debug` (`message`) SELECT CONCAT("Longest word inserted last round: ",IFNULL(@max_last_length,'NULL')," letters; cutting wN to ",COUNT(*)," rows") FROM `wN` WHERE `length` <= @max_last_length OR `a`>@max_a OR `b`>@max_b
            OR `c`>@max_c OR `d`>@max_d OR `e`>@max_e OR `f`>@max_f
            OR `g`>@max_g OR `h`>@max_h OR `i`>@max_i OR `j`>@max_j
            OR `k`>@max_k OR `l`>@max_l OR `m`>@max_m OR `n`>@max_n
            OR `o`>@max_o OR `p`>@max_p OR `q`>@max_q OR `r`>@max_r
            OR `s`>@max_s OR `t`>@max_t OR `u`>@max_u OR `v`>@max_v
            OR `w`>@max_w OR `x`>@max_x OR `y`>@max_y OR `z`>@max_z; */ /* DEBUG */
        DELETE FROM `wN` WHERE `length` > @max_last_length OR `a`>@max_a OR `b`>@max_b
            OR `c`>@max_c OR `d`>@max_d OR `e`>@max_e OR `f`>@max_f
            OR `g`>@max_g OR `h`>@max_h OR `i`>@max_i OR `j`>@max_j
            OR `k`>@max_k OR `l`>@max_l OR `m`>@max_m OR `n`>@max_n
            OR `o`>@max_o OR `p`>@max_p OR `q`>@max_q OR `r`>@max_r
            OR `s`>@max_s OR `t`>@max_t OR `u`>@max_u OR `v`>@max_v
            OR `w`>@max_w OR `x`>@max_x OR `y`>@max_y OR `z`>@max_z;
        /* ...now join wN into comp table... */
        /* ...twice over: (a) where length is equal and word is alphabetically equal or later, (b) where length is shorter */
        IF @oddRound THEN
            TRUNCATE TABLE `compNodd`;
            INSERT INTO `compNodd`
            (
              `tome_id`,`word`,`bare_letters`,`composite_word`,`composite_bare_letters`,`last_length`,`length`,`a`,`b`,`c`,`d`,`e`,`f`,`g`,`h`,`i`,`j`,`k`,`l`,`m`,`n`,`o`,`p`,`q`,`r`,`s`,`t`,`u`,`v`,`w`,`x`,`y`,`z`
            )
            SELECT 0 AS tome_id, `wN`.`word` AS `word`, `wN`.`bare_letters` AS `bare_letters`, CONCAT(`compNeven`.`composite_word`,' ',`wN`.`word`) AS `composite_word`, CONCAT(compNeven.`composite_bare_letters`,wN.`bare_letters`) AS `composite_bare_letters`
            , `wN`.`length` AS `last_length`,`compNeven`.`length`-`wN`.`length` AS `length`,`compNeven`.`a`-`wN`.`a` AS `a`,`compNeven`.`b`-`wN`.`b` AS `b`
            ,`compNeven`.`c`-`wN`.`c` AS `c`,`compNeven`.`d`-`wN`.`d` AS `d`,`compNeven`.`e`-`wN`.`e` AS `e`,`compNeven`.`f`-`wN`.`f` AS `f`
            ,`compNeven`.`g`-`wN`.`g` AS `g`,`compNeven`.`h`-`wN`.`h` AS `h`,`compNeven`.`i`-`wN`.`i` AS `i`,`compNeven`.`j`-`wN`.`j` AS `j`
            ,`compNeven`.`k`-`wN`.`k` AS `k`,`compNeven`.`l`-`wN`.`l` AS `l`,`compNeven`.`m`-`wN`.`m` AS `m`,`compNeven`.`n`-`wN`.`n` AS `n`
            ,`compNeven`.`o`-`wN`.`o` AS `o`,`compNeven`.`p`-`wN`.`p` AS `p`,`compNeven`.`q`-`wN`.`q` AS `q`,`compNeven`.`r`-`wN`.`r` AS `r`
            ,`compNeven`.`s`-`wN`.`s` AS `s`,`compNeven`.`t`-`wN`.`t` AS `t`,`compNeven`.`u`-`wN`.`u` AS `u`,`compNeven`.`v`-`wN`.`v` AS `v`
            ,`compNeven`.`w`-`wN`.`w` AS `w`,`compNeven`.`x`-`wN`.`x` AS `x`,`compNeven`.`y`-`wN`.`y` AS `y`,`compNeven`.`z`-`wN`.`z` AS `z`
            FROM `compNeven`
            INNER JOIN `wN` ON wN.`length` BETWEEN 0 AND `compNeven`.`length`
             AND `wN`.`length` = `compNeven`.`last_length` AND `wN`.`bare_letters`>=`compNeven`.`bare_letters` /* Join to equal-length words that are alphabetically equal or later */
             AND `wN`.`a` BETWEEN 0 AND `compNeven`.`a` AND `wN`.`b` BETWEEN 0 AND `compNeven`.`b`
             AND `wN`.`c` BETWEEN 0 AND `compNeven`.`c` AND `wN`.`d` BETWEEN 0 AND `compNeven`.`d` AND `wN`.`e` BETWEEN 0 AND `compNeven`.`e` AND `wN`.`f` BETWEEN 0 AND `compNeven`.`f`
             AND `wN`.`g` BETWEEN 0 AND `compNeven`.`g` AND `wN`.`h` BETWEEN 0 AND `compNeven`.`h` AND `wN`.`i` BETWEEN 0 AND `compNeven`.`i` AND `wN`.`j` BETWEEN 0 AND `compNeven`.`j`
             AND `wN`.`k` BETWEEN 0 AND `compNeven`.`k` AND `wN`.`l` BETWEEN 0 AND `compNeven`.`l` AND `wN`.`m` BETWEEN 0 AND `compNeven`.`m` AND `wN`.`n` BETWEEN 0 AND `compNeven`.`n`
             AND `wN`.`o` BETWEEN 0 AND `compNeven`.`o` AND `wN`.`p` BETWEEN 0 AND `compNeven`.`p` AND `wN`.`q` BETWEEN 0 AND `compNeven`.`q` AND `wN`.`r` BETWEEN 0 AND `compNeven`.`r`
             AND `wN`.`s` BETWEEN 0 AND `compNeven`.`s` AND `wN`.`t` BETWEEN 0 AND `compNeven`.`t` AND `wN`.`u` BETWEEN 0 AND `compNeven`.`u` AND `wN`.`v` BETWEEN 0 AND `compNeven`.`v`
             AND `wN`.`w` BETWEEN 0 AND `compNeven`.`w` AND `wN`.`x` BETWEEN 0 AND `compNeven`.`x` AND `wN`.`y` BETWEEN 0 AND `compNeven`.`y` AND `wN`.`z` BETWEEN 0 AND `compNeven`.`z`
             ;
             INSERT INTO `compNodd`
            (
              `tome_id`,`word`,`bare_letters`,`composite_word`,`composite_bare_letters`,`last_length`,`length`,`a`,`b`,`c`,`d`,`e`,`f`,`g`,`h`,`i`,`j`,`k`,`l`,`m`,`n`,`o`,`p`,`q`,`r`,`s`,`t`,`u`,`v`,`w`,`x`,`y`,`z`
            )
            SELECT 0 AS tome_id, `wN`.`word` AS `word`, `wN`.`bare_letters` AS `bare_letters`, CONCAT(`compNeven`.`composite_word`,' ',`wN`.`word`) AS `composite_word`, CONCAT(compNeven.`composite_bare_letters`,wN.`bare_letters`) AS `composite_bare_letters`
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
             AND `wN`.`a` BETWEEN 0 AND `compNeven`.`a` AND `wN`.`b` BETWEEN 0 AND `compNeven`.`b`
             AND `wN`.`c` BETWEEN 0 AND `compNeven`.`c` AND `wN`.`d` BETWEEN 0 AND `compNeven`.`d` AND `wN`.`e` BETWEEN 0 AND `compNeven`.`e` AND `wN`.`f` BETWEEN 0 AND `compNeven`.`f`
             AND `wN`.`g` BETWEEN 0 AND `compNeven`.`g` AND `wN`.`h` BETWEEN 0 AND `compNeven`.`h` AND `wN`.`i` BETWEEN 0 AND `compNeven`.`i` AND `wN`.`j` BETWEEN 0 AND `compNeven`.`j`
             AND `wN`.`k` BETWEEN 0 AND `compNeven`.`k` AND `wN`.`l` BETWEEN 0 AND `compNeven`.`l` AND `wN`.`m` BETWEEN 0 AND `compNeven`.`m` AND `wN`.`n` BETWEEN 0 AND `compNeven`.`n`
             AND `wN`.`o` BETWEEN 0 AND `compNeven`.`o` AND `wN`.`p` BETWEEN 0 AND `compNeven`.`p` AND `wN`.`q` BETWEEN 0 AND `compNeven`.`q` AND `wN`.`r` BETWEEN 0 AND `compNeven`.`r`
             AND `wN`.`s` BETWEEN 0 AND `compNeven`.`s` AND `wN`.`t` BETWEEN 0 AND `compNeven`.`t` AND `wN`.`u` BETWEEN 0 AND `compNeven`.`u` AND `wN`.`v` BETWEEN 0 AND `compNeven`.`v`
             AND `wN`.`w` BETWEEN 0 AND `compNeven`.`w` AND `wN`.`x` BETWEEN 0 AND `compNeven`.`x` AND `wN`.`y` BETWEEN 0 AND `compNeven`.`y` AND `wN`.`z` BETWEEN 0 AND `compNeven`.`z`
             ;
             /* Save results */             
            /* INSERT INTO `debug` (`message`) SELECT CONCAT('Populated compNodd with ',COUNT(*),' rows') FROM `compNodd`; */ /* DEBUG */
            /* INSERT INTO `debug` (`message`) SELECT CONCAT('Outputting ',COUNT(*),' rows to results') FROM `compNodd` WHERE `length`=0; */ /* DEBUG */
            INSERT INTO `results` (`word`,`bare_letters`)
             SELECT `composite_word`,`composite_bare_letters`
             FROM `compNodd`
             WHERE `length`=0
             ORDER BY `last_length` DESC
             ;
        ELSE
            TRUNCATE TABLE `compNeven`;
            INSERT INTO `compNeven`
            (
              `tome_id`,`word`,`bare_letters`,`composite_word`,`composite_bare_letters`,`last_length`,`length`,`a`,`b`,`c`,`d`,`e`,`f`,`g`,`h`,`i`,`j`,`k`,`l`,`m`,`n`,`o`,`p`,`q`,`r`,`s`,`t`,`u`,`v`,`w`,`x`,`y`,`z`
            )
            SELECT 0 AS tome_id, `wN`.`word` AS `word`, `wN`.`bare_letters` AS `bare_letters`, CONCAT(`compNodd`.`composite_word`,' ',`wN`.`word`) AS `composite_word`, CONCAT(compNodd.`composite_bare_letters`,wN.`bare_letters`) AS `composite_bare_letters`
            , `wN`.`length` AS `last_length`,`compNodd`.`length`-`wN`.`length` AS `length`,`compNodd`.`a`-`wN`.`a` AS `a`,`compNodd`.`b`-`wN`.`b` AS `b`
            ,`compNodd`.`c`-`wN`.`c` AS `c`,`compNodd`.`d`-`wN`.`d` AS `d`,`compNodd`.`e`-`wN`.`e` AS `e`,`compNodd`.`f`-`wN`.`f` AS `f`
            ,`compNodd`.`g`-`wN`.`g` AS `g`,`compNodd`.`h`-`wN`.`h` AS `h`,`compNodd`.`i`-`wN`.`i` AS `i`,`compNodd`.`j`-`wN`.`j` AS `j`
            ,`compNodd`.`k`-`wN`.`k` AS `k`,`compNodd`.`l`-`wN`.`l` AS `l`,`compNodd`.`m`-`wN`.`m` AS `m`,`compNodd`.`n`-`wN`.`n` AS `n`
            ,`compNodd`.`o`-`wN`.`o` AS `o`,`compNodd`.`p`-`wN`.`p` AS `p`,`compNodd`.`q`-`wN`.`q` AS `q`,`compNodd`.`r`-`wN`.`r` AS `r`
            ,`compNodd`.`s`-`wN`.`s` AS `s`,`compNodd`.`t`-`wN`.`t` AS `t`,`compNodd`.`u`-`wN`.`u` AS `u`,`compNodd`.`v`-`wN`.`v` AS `v`
            ,`compNodd`.`w`-`wN`.`w` AS `w`,`compNodd`.`x`-`wN`.`x` AS `x`,`compNodd`.`y`-`wN`.`y` AS `y`,`compNodd`.`z`-`wN`.`z` AS `z`
            FROM `compNodd`
            INNER JOIN `wN` ON wN.`length` BETWEEN 0 AND `compNodd`.`length`
             AND `wN`.`length` = `compNodd`.`last_length` AND `wN`.`bare_letters`>=`compNodd`.`bare_letters` /* Join to equal-length words that are alphabetically equal or later */
             AND `wN`.`a` BETWEEN 0 AND `compNodd`.`a` AND `wN`.`b` BETWEEN 0 AND `compNodd`.`b`
             AND `wN`.`c` BETWEEN 0 AND `compNodd`.`c` AND `wN`.`d` BETWEEN 0 AND `compNodd`.`d` AND `wN`.`e` BETWEEN 0 AND `compNodd`.`e` AND `wN`.`f` BETWEEN 0 AND `compNodd`.`f`
             AND `wN`.`g` BETWEEN 0 AND `compNodd`.`g` AND `wN`.`h` BETWEEN 0 AND `compNodd`.`h` AND `wN`.`i` BETWEEN 0 AND `compNodd`.`i` AND `wN`.`j` BETWEEN 0 AND `compNodd`.`j`
             AND `wN`.`k` BETWEEN 0 AND `compNodd`.`k` AND `wN`.`l` BETWEEN 0 AND `compNodd`.`l` AND `wN`.`m` BETWEEN 0 AND `compNodd`.`m` AND `wN`.`n` BETWEEN 0 AND `compNodd`.`n`
             AND `wN`.`o` BETWEEN 0 AND `compNodd`.`o` AND `wN`.`p` BETWEEN 0 AND `compNodd`.`p` AND `wN`.`q` BETWEEN 0 AND `compNodd`.`q` AND `wN`.`r` BETWEEN 0 AND `compNodd`.`r`
             AND `wN`.`s` BETWEEN 0 AND `compNodd`.`s` AND `wN`.`t` BETWEEN 0 AND `compNodd`.`t` AND `wN`.`u` BETWEEN 0 AND `compNodd`.`u` AND `wN`.`v` BETWEEN 0 AND `compNodd`.`v`
             AND `wN`.`w` BETWEEN 0 AND `compNodd`.`w` AND `wN`.`x` BETWEEN 0 AND `compNodd`.`x` AND `wN`.`y` BETWEEN 0 AND `compNodd`.`y` AND `wN`.`z` BETWEEN 0 AND `compNodd`.`z`
             ;
             INSERT INTO `compNeven`
            (
              `tome_id`,`word`,`bare_letters`,`composite_word`,`composite_bare_letters`,`last_length`,`length`,`a`,`b`,`c`,`d`,`e`,`f`,`g`,`h`,`i`,`j`,`k`,`l`,`m`,`n`,`o`,`p`,`q`,`r`,`s`,`t`,`u`,`v`,`w`,`x`,`y`,`z`
            )
            SELECT 0 AS tome_id, `wN`.`word` AS `word`, `wN`.`bare_letters` AS `bare_letters`, CONCAT(`compNodd`.`composite_word`,' ',`wN`.`word`) AS `composite_word`, CONCAT(compNodd.`composite_bare_letters`,wN.`bare_letters`) AS `composite_bare_letters`
            , `wN`.`length` AS `last_length`,`compNodd`.`length`-`wN`.`length` AS `length`,`compNodd`.`a`-`wN`.`a` AS `a`,`compNodd`.`b`-`wN`.`b` AS `b`
            ,`compNodd`.`c`-`wN`.`c` AS `c`,`compNodd`.`d`-`wN`.`d` AS `d`,`compNodd`.`e`-`wN`.`e` AS `e`,`compNodd`.`f`-`wN`.`f` AS `f`
            ,`compNodd`.`g`-`wN`.`g` AS `g`,`compNodd`.`h`-`wN`.`h` AS `h`,`compNodd`.`i`-`wN`.`i` AS `i`,`compNodd`.`j`-`wN`.`j` AS `j`
            ,`compNodd`.`k`-`wN`.`k` AS `k`,`compNodd`.`l`-`wN`.`l` AS `l`,`compNodd`.`m`-`wN`.`m` AS `m`,`compNodd`.`n`-`wN`.`n` AS `n`
            ,`compNodd`.`o`-`wN`.`o` AS `o`,`compNodd`.`p`-`wN`.`p` AS `p`,`compNodd`.`q`-`wN`.`q` AS `q`,`compNodd`.`r`-`wN`.`r` AS `r`
            ,`compNodd`.`s`-`wN`.`s` AS `s`,`compNodd`.`t`-`wN`.`t` AS `t`,`compNodd`.`u`-`wN`.`u` AS `u`,`compNodd`.`v`-`wN`.`v` AS `v`
            ,`compNodd`.`w`-`wN`.`w` AS `w`,`compNodd`.`x`-`wN`.`x` AS `x`,`compNodd`.`y`-`wN`.`y` AS `y`,`compNodd`.`z`-`wN`.`z` AS `z`
            FROM `compNodd`
            INNER JOIN `wN` ON wN.`length` BETWEEN 0 AND `compNodd`.`length`
             AND `wN`.`length` < `compNodd`.`last_length` /* Join to shorter words */
             AND `wN`.`a` BETWEEN 0 AND `compNodd`.`a` AND `wN`.`b` BETWEEN 0 AND `compNodd`.`b`
             AND `wN`.`c` BETWEEN 0 AND `compNodd`.`c` AND `wN`.`d` BETWEEN 0 AND `compNodd`.`d` AND `wN`.`e` BETWEEN 0 AND `compNodd`.`e` AND `wN`.`f` BETWEEN 0 AND `compNodd`.`f`
             AND `wN`.`g` BETWEEN 0 AND `compNodd`.`g` AND `wN`.`h` BETWEEN 0 AND `compNodd`.`h` AND `wN`.`i` BETWEEN 0 AND `compNodd`.`i` AND `wN`.`j` BETWEEN 0 AND `compNodd`.`j`
             AND `wN`.`k` BETWEEN 0 AND `compNodd`.`k` AND `wN`.`l` BETWEEN 0 AND `compNodd`.`l` AND `wN`.`m` BETWEEN 0 AND `compNodd`.`m` AND `wN`.`n` BETWEEN 0 AND `compNodd`.`n`
             AND `wN`.`o` BETWEEN 0 AND `compNodd`.`o` AND `wN`.`p` BETWEEN 0 AND `compNodd`.`p` AND `wN`.`q` BETWEEN 0 AND `compNodd`.`q` AND `wN`.`r` BETWEEN 0 AND `compNodd`.`r`
             AND `wN`.`s` BETWEEN 0 AND `compNodd`.`s` AND `wN`.`t` BETWEEN 0 AND `compNodd`.`t` AND `wN`.`u` BETWEEN 0 AND `compNodd`.`u` AND `wN`.`v` BETWEEN 0 AND `compNodd`.`v`
             AND `wN`.`w` BETWEEN 0 AND `compNodd`.`w` AND `wN`.`x` BETWEEN 0 AND `compNodd`.`x` AND `wN`.`y` BETWEEN 0 AND `compNodd`.`y` AND `wN`.`z` BETWEEN 0 AND `compNodd`.`z`
             ;
             /* Save results */
            /* INSERT INTO `debug` (`message`) SELECT CONCAT('Populated compNeven with ',COUNT(*),' rows') FROM `compNeven`; */ /* DEBUG */
            /* INSERT INTO `debug` (`message`) SELECT CONCAT('Outputting ',COUNT(*),' rows to results') FROM `compNeven` WHERE `length`=0; */ /* DEBUG */
            INSERT INTO `results` (`word`,`bare_letters`)
             SELECT `composite_word`,`composite_bare_letters`
             FROM `compNeven`
             WHERE `length`=0
             ORDER BY `last_length` DESC
             ;
        END IF;
        SET @currentRound = @currentRound + 1;
	END WHILE;
END IF;

SET @timeEnd = NOW(6);
INSERT INTO `debug` (`message`) SELECT CONCAT("Execution took ",TIMESTAMPDIFF(MICROSECOND,@timeStart,@timeEnd)/1000000.0," secs");

SELECT `message`
FROM `debug`
;

SELECT *
FROM `results`
;

	END
;