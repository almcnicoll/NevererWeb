DELIMITER $$

USE `words`$$

DROP PROCEDURE IF EXISTS `SetOrderedWords`$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SetOrderedWords`()
    MODIFIES SQL DATA
BEGIN
UPDATE `sowpods` SET `ordered_word` = CONCAT(
REPEAT('a',`a`),REPEAT('b',`b`),REPEAT('c',`c`),REPEAT('d',`d`),
REPEAT('e',`e`),REPEAT('f',`f`),REPEAT('g',`g`),REPEAT('h',`h`),
REPEAT('i',`i`),REPEAT('j',`j`),REPEAT('k',`k`),REPEAT('l',`l`),
REPEAT('m',`m`),REPEAT('n',`n`),REPEAT('o',`o`),REPEAT('p',`p`),
REPEAT('q',`q`),REPEAT('r',`r`),REPEAT('s',`s`),REPEAT('t',`t`),
REPEAT('u',`u`),REPEAT('v',`v`),REPEAT('w',`w`),REPEAT('x',`x`),
REPEAT('y',`y`),REPEAT('z',`z`)
);
	END$$

DROP PROCEDURE IF EXISTS `GetAnagrams`$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetAnagrams`(
    original VARCHAR(64)
    )
    MODIFIES SQL DATA
BEGIN

DECLARE wordlen INT UNSIGNED DEFAULT 0;
DECLARE ii INT UNSIGNED DEFAULT 0;
DECLARE letter CHAR(1) DEFAULT '';
SET wordlen = LENGTH(original);

#Store original word for calculations
DROP TABLE IF EXISTS `__orig`;
CREATE TEMPORARY TABLE `__orig` LIKE `sowpods`;
#ALTER TABLE `__orig` ENGINE=memory;
INSERT INTO `__orig` (`word`, `ordered_word`, `len`) VALUES (LOWER(original),'',wordlen);
#Store vector distances
DROP TABLE IF EXISTS `__vectors`;
CREATE TEMPORARY TABLE `__vectors` LIKE `__orig`;
#__output table
DROP TABLE IF EXISTS `__output`;
CREATE TEMPORARY TABLE `__output` (`word` VARCHAR(64) NOT NULL DEFAULT '');

SET ii=0;

# START LOOP
lettercount: LOOP

#INCREMENT
SET ii = ii+1;

#SELECT wordlen AS `len`, ii AS `i`, MID(original,ii,1) AS `letter`;

SET letter = LOWER(MID(original,ii,1));
CASE
WHEN letter='a' THEN
UPDATE `__orig` SET `a`=IFNULL(`a`,0)+1;
WHEN letter='b' THEN
UPDATE `__orig` SET `b`=IFNULL(`b`,0)+1;
WHEN letter='c' THEN
UPDATE `__orig` SET `c`=IFNULL(`c`,0)+1;
WHEN letter='d' THEN
UPDATE `__orig` SET `d`=IFNULL(`d`,0)+1;
WHEN letter='e' THEN
UPDATE `__orig` SET `e`=IFNULL(`e`,0)+1;
WHEN letter='f' THEN
UPDATE `__orig` SET `f`=IFNULL(`f`,0)+1;
WHEN letter='g' THEN
UPDATE `__orig` SET `g`=IFNULL(`g`,0)+1;
WHEN letter='h' THEN
UPDATE `__orig` SET `h`=IFNULL(`h`,0)+1;
WHEN letter='i' THEN
UPDATE `__orig` SET `i`=IFNULL(`i`,0)+1;
WHEN letter='j' THEN
UPDATE `__orig` SET `j`=IFNULL(`j`,0)+1;
WHEN letter='k' THEN
UPDATE `__orig` SET `k`=IFNULL(`k`,0)+1;
WHEN letter='l' THEN
UPDATE `__orig` SET `l`=IFNULL(`l`,0)+1;
WHEN letter='m' THEN
UPDATE `__orig` SET `m`=IFNULL(`m`,0)+1;
WHEN letter='n' THEN
UPDATE `__orig` SET `n`=IFNULL(`n`,0)+1;
WHEN letter='o' THEN
UPDATE `__orig` SET `o`=IFNULL(`o`,0)+1;
WHEN letter='p' THEN
UPDATE `__orig` SET `p`=IFNULL(`p`,0)+1;
WHEN letter='q' THEN
UPDATE `__orig` SET `q`=IFNULL(`q`,0)+1;
WHEN letter='r' THEN
UPDATE `__orig` SET `r`=IFNULL(`r`,0)+1;
WHEN letter='s' THEN
UPDATE `__orig` SET `s`=IFNULL(`s`,0)+1;
WHEN letter='t' THEN
UPDATE `__orig` SET `t`=IFNULL(`t`,0)+1;
WHEN letter='u' THEN
UPDATE `__orig` SET `u`=IFNULL(`u`,0)+1;
WHEN letter='v' THEN
UPDATE `__orig` SET `v`=IFNULL(`v`,0)+1;
WHEN letter='w' THEN
UPDATE `__orig` SET `w`=IFNULL(`w`,0)+1;
WHEN letter='x' THEN
UPDATE `__orig` SET `x`=IFNULL(`x`,0)+1;
WHEN letter='y' THEN
UPDATE `__orig` SET `y`=IFNULL(`y`,0)+1;
WHEN letter='z' THEN
UPDATE `__orig` SET `z`=IFNULL(`z`,0)+1;
END CASE;


/*CHECK IF WE'RE DONE*/
IF (ii >= wordlen) THEN
	LEAVE lettercount;
END IF;

#ITERATE
ITERATE lettercount;
END LOOP lettercount;


# BUILD ORDERED WORD
UPDATE `__orig` SET `ordered_word` = CONCAT(
REPEAT('a',`a`),REPEAT('b',`b`),REPEAT('c',`c`),REPEAT('d',`d`),
REPEAT('e',`e`),REPEAT('f',`f`),REPEAT('g',`g`),REPEAT('h',`h`),
REPEAT('i',`i`),REPEAT('j',`j`),REPEAT('k',`k`),REPEAT('l',`l`),
REPEAT('m',`m`),REPEAT('n',`n`),REPEAT('o',`o`),REPEAT('p',`p`),
REPEAT('q',`q`),REPEAT('r',`r`),REPEAT('s',`s`),REPEAT('t',`t`),
REPEAT('u',`u`),REPEAT('v',`v`),REPEAT('w',`w`),REPEAT('x',`x`),
REPEAT('y',`y`),REPEAT('z',`z`)
);

# CALCULATE VECTOR DISTANCES FROM ORIG
INSERT INTO `__vectors`
SELECT /*t1.`word`,t1.`ordered_word`,
t1.a - t2.a,t1.b - t2.b,t1.c - t2.c,t1.d - t2.d,
t1.e - t2.e,t1.f - t2.f,t1.g - t2.g,t1.h - t2.h,
t1.i - t2.i,t1.j - t2.j,t1.k - t2.k,t1.l - t2.l,
t1.m - t2.m,t1.n - t2.n,t1.o - t2.o,t1.p - t2.p,
t1.q - t2.q,t1.r - t2.r,t1.s - t2.s,t1.t - t2.t,
t1.u - t2.u,t1.v - t2.v,t1.w - t2.w,t1.x - t2.x,
t1.y - t2.y,t1.z - t2.z,
t1.`len`*/
t1.*
FROM `__orig` t2 CROSS JOIN `sowpods` t1
WHERE t1.`len` <= wordlen
AND t1.`a` <= t2.`a` AND t1.`b` <= t2.`b` AND t1.`c` <= t2.`c` AND t1.`d` <= t2.`d`
AND t1.`e` <= t2.`e` AND t1.`f` <= t2.`f` AND t1.`g` <= t2.`g` AND t1.`h` <= t2.`h`
AND t1.`i` <= t2.`i` AND t1.`j` <= t2.`j` AND t1.`k` <= t2.`k` AND t1.`l` <= t2.`l`
AND t1.`m` <= t2.`m` AND t1.`n` <= t2.`n` AND t1.`o` <= t2.`o` AND t1.`p` <= t2.`p`
AND t1.`q` <= t2.`q` AND t1.`r` <= t2.`r` AND t1.`s` <= t2.`s` AND t1.`t` <= t2.`t`
AND t1.`u` <= t2.`u` AND t1.`v` <= t2.`v` AND t1.`w` <= t2.`w` AND t1.`x` <= t2.`x`
AND t1.`y` <= t2.`y` AND t1.`z` <= t2.`z`
;

#EXACT matches
INSERT INTO `__output`
SELECT `word` FROM `__vectors` WHERE `ordered_word` = (SELECT `ordered_word` FROM `__orig`);

#SELECT CONCAT("Vectors count is ",COUNT(`word`)," rows") AS `msg` FROM __vectors GROUP BY NULL;

#TWO-WORD matches
#we can now dispense with words equal in length to or longer than the original word
#SELECT CONCAT("Deleting words longer than or equal to ",wordlen) AS `msg` FROM DUAL;
DELETE FROM __vectors WHERE `len` >= wordlen;
#SELECT CONCAT("Reduced vectors to ",COUNT(`word`)," rows") AS `msg` FROM __vectors GROUP BY NULL;

#SELECT * FROM __vectors;

INSERT INTO `__output`
SELECT CONCAT(v1.`word`,' ',v2.`word`) AS `word` FROM `__orig` CROSS JOIN `__vectors` v1 INNER JOIN `__vectors` v2
ON v1.`len`+v2.`len` = __orig.`len`
AND v1.`a`+v2.`a` = __orig.`a` AND v1.`b`+v2.`b` = __orig.`b` AND v1.`c`+v2.`c` = __orig.`c` AND v1.`d`+v2.`d` = __orig.`d`
AND v1.`e`+v2.`e` = __orig.`e` AND v1.`f`+v2.`f` = __orig.`f` AND v1.`g`+v2.`g` = __orig.`g` AND v1.`h`+v2.`h` = __orig.`h`
AND v1.`i`+v2.`i` = __orig.`i` AND v1.`j`+v2.`j` = __orig.`j` AND v1.`k`+v2.`k` = __orig.`k` AND v1.`l`+v2.`l` = __orig.`l`
AND v1.`m`+v2.`m` = __orig.`m` AND v1.`n`+v2.`n` = __orig.`n` AND v1.`o`+v2.`o` = __orig.`o` AND v1.`p`+v2.`p` = __orig.`p`
AND v1.`q`+v2.`q` = __orig.`q` AND v1.`r`+v2.`r` = __orig.`r` AND v1.`s`+v2.`s` = __orig.`s` AND v1.`t`+v2.`t` = __orig.`t`
AND v1.`u`+v2.`u` = __orig.`u` AND v1.`v`+v2.`v` = __orig.`v` AND v1.`w`+v2.`w` = __orig.`w` AND v1.`x`+v2.`x` = __orig.`x`
AND v1.`y`+v2.`y` = __orig.`y` AND v1.`z`+v2.`z` = __orig.`z`
AND v1.`word`>=v2.`word`
#ORDER BY `v1`.`len` DESC, v1.`word` ASC, v2.`len` DESC, v2.`word` ASC
;

#THREE-WORD matches
#we can now dispense with words equal in length to or longer than the original word's length - 1
#SELECT CONCAT("Deleting words longer than or equal to ",wordlen) AS `msg` FROM DUAL;
DELETE FROM __vectors WHERE `len` >= (wordlen-1);
#SELECT CONCAT("Reduced vectors to ",COUNT(`word`)," rows") AS `msg` FROM __vectors GROUP BY NULL;

INSERT INTO `__output`
SELECT CONCAT(v1.`word`,' ',v2.`word`,' ',v3.`word`) AS `word` FROM `__orig` CROSS JOIN `__vectors` v1 CROSS JOIN `__vectors` v2 INNER JOIN `__vectors` v3
ON v1.`len`+v2.`len`+v3.`len` = __orig.`len`
AND v1.`a`+v2.`a`+v3.`a` = __orig.`a` AND v1.`b`+v2.`b`+v3.`b` = __orig.`b` AND v1.`c`+v2.`c`+v3.`c` = __orig.`c` AND v1.`d`+v2.`d`+v3.`d` = __orig.`d`
AND v1.`e`+v2.`e`+v3.`e` = __orig.`e` AND v1.`f`+v2.`f`+v3.`f` = __orig.`f` AND v1.`g`+v2.`g`+v3.`g` = __orig.`g` AND v1.`h`+v2.`h`+v3.`h` = __orig.`h`
AND v1.`i`+v2.`i`+v3.`i` = __orig.`i` AND v1.`j`+v2.`j`+v3.`j` = __orig.`j` AND v1.`k`+v2.`k`+v3.`k` = __orig.`k` AND v1.`l`+v2.`l`+v3.`l` = __orig.`l`
AND v1.`m`+v2.`m`+v3.`m` = __orig.`m` AND v1.`n`+v2.`n`+v3.`n` = __orig.`n` AND v1.`o`+v2.`o`+v3.`o` = __orig.`o` AND v1.`p`+v2.`p`+v3.`p` = __orig.`p`
AND v1.`q`+v2.`q`+v3.`q` = __orig.`q` AND v1.`r`+v2.`r`+v3.`r` = __orig.`r` AND v1.`s`+v2.`s`+v3.`s` = __orig.`s` AND v1.`t`+v2.`t`+v3.`t` = __orig.`t`
AND v1.`u`+v2.`u`+v3.`u` = __orig.`u` AND v1.`v`+v2.`v`+v3.`v` = __orig.`v` AND v1.`w`+v2.`w`+v3.`w` = __orig.`w` AND v1.`x`+v2.`x`+v3.`x` = __orig.`x`
AND v1.`y`+v2.`y`+v3.`y` = __orig.`y` AND v1.`z`+v2.`z`+v3.`z` = __orig.`z`
AND v1.`word`>=v2.`word` AND v2.`word`>=v3.`word`
#ORDER BY `v1`.`len` DESC, v1.`word` ASC, v2.`len` DESC, v2.`word` ASC, v3.`len` DESC, v3.`word` ASC
;

#FOUR-WORD matches
#we can now dispense with words equal in length to or longer than the original word's length - 2
#SELECT CONCAT("Deleting words longer than or equal to ",wordlen) AS `msg` FROM DUAL;
DELETE FROM __vectors WHERE `len` >= (wordlen-2);
#SELECT CONCAT("Reduced vectors to ",COUNT(`word`)," rows") AS `msg` FROM __vectors GROUP BY NULL;
/*
INSERT INTO `__output`
SELECT CONCAT(v1.`word`,' ',v2.`word`,' ',v3.`word`,' ',v4.`word`) AS `word` FROM `__orig` CROSS JOIN `__vectors` v1
	CROSS JOIN `__vectors` v2 CROSS JOIN `__vectors` v3 INNER JOIN `__vectors` v4
ON v1.`len`+v2.`len`+v3.`len` = __orig.`len`
AND v1.`a`+v2.`a`+v3.`a`+v4.`a` = __orig.`a` AND v1.`b`+v2.`b`+v3.`b`+v4.`b` = __orig.`b` AND v1.`c`+v2.`c`+v3.`c`+v4.`c` = __orig.`c` AND v1.`d`+v2.`d`+v3.`d`+v4.`d` = __orig.`d`
AND v1.`e`+v2.`e`+v3.`e`+v4.`e` = __orig.`e` AND v1.`f`+v2.`f`+v3.`f`+v4.`f` = __orig.`f` AND v1.`g`+v2.`g`+v3.`g`+v4.`g` = __orig.`g` AND v1.`h`+v2.`h`+v3.`h`+v4.`h` = __orig.`h`
AND v1.`i`+v2.`i`+v3.`i`+v4.`i` = __orig.`i` AND v1.`j`+v2.`j`+v3.`j`+v4.`j` = __orig.`j` AND v1.`k`+v2.`k`+v3.`k`+v4.`k` = __orig.`k` AND v1.`l`+v2.`l`+v3.`l`+v4.`l` = __orig.`l`
AND v1.`m`+v2.`m`+v3.`m`+v4.`m` = __orig.`m` AND v1.`n`+v2.`n`+v3.`n`+v4.`n` = __orig.`n` AND v1.`o`+v2.`o`+v3.`o`+v4.`o` = __orig.`o` AND v1.`p`+v2.`p`+v3.`p`+v4.`p` = __orig.`p`
AND v1.`q`+v2.`q`+v3.`q`+v4.`q` = __orig.`q` AND v1.`r`+v2.`r`+v3.`r`+v4.`r` = __orig.`r` AND v1.`s`+v2.`s`+v3.`s`+v4.`s` = __orig.`s` AND v1.`t`+v2.`t`+v3.`t`+v4.`t` = __orig.`t`
AND v1.`u`+v2.`u`+v3.`u`+v4.`u` = __orig.`u` AND v1.`v`+v2.`v`+v3.`v`+v4.`v` = __orig.`v` AND v1.`w`+v2.`w`+v3.`w`+v4.`w` = __orig.`w` AND v1.`x`+v2.`x`+v3.`x`+v4.`x` = __orig.`x`
AND v1.`y`+v2.`y`+v3.`y`+v4.`y` = __orig.`y` AND v1.`z`+v2.`z`+v3.`z`+v4.`z` = __orig.`z`
ORDER BY `v1`.`len` DESC, v1.`word` ASC, v2.`len` DESC, v2.`word` ASC, v3.`len` DESC, v3.`word` ASC, v4.`len` DESC, v4.`word` ASC
;
*/

SELECT * FROM `__output`;

END$$


DELIMITER ;