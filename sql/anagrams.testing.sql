/*
word is RELATED
Maximum component words = 3
*/

SELECT orig.`word` AS `Original`, `w1`.`word` AS `Word 1`, `w2`.`word` AS `Word 2`, `w3`.`word` AS `Word 3`
FROM `tome_entries` `orig`
INNER JOIN `tome_entries` w1 ON w1.`length`<=`orig`.`length`
 AND `w1`.`a`<=`orig`.`a` AND `w1`.`b`<=`orig`.`b`
 AND `w1`.`c`<=`orig`.`c` AND `w1`.`d`<=`orig`.`d`
 AND `w1`.`e`<=`orig`.`e` AND `w1`.`f`<=`orig`.`f`
 AND `w1`.`g`<=`orig`.`g` AND `w1`.`h`<=`orig`.`h`
 AND `w1`.`i`<=`orig`.`i` AND `w1`.`j`<=`orig`.`j`
 AND `w1`.`k`<=`orig`.`k` AND `w1`.`l`<=`orig`.`l`
 AND `w1`.`m`<=`orig`.`m` AND `w1`.`n`<=`orig`.`n`
 AND `w1`.`o`<=`orig`.`o` AND `w1`.`p`<=`orig`.`p`
 AND `w1`.`q`<=`orig`.`q` AND `w1`.`r`<=`orig`.`r`
 AND `w1`.`s`<=`orig`.`s` AND `w1`.`t`<=`orig`.`t`
 AND `w1`.`u`<=`orig`.`u` AND `w1`.`v`<=`orig`.`v`
 AND `w1`.`w`<=`orig`.`w` AND `w1`.`x`<=`orig`.`x`
 AND `w1`.`y`<=`orig`.`y` AND `w1`.`z`<=`orig`.`z`
LEFT JOIN `tome_entries` w2 ON w2.`length`<=`w1`.`length`
 AND `w2`.`a`<=(`orig`.`a`-`w1`.`a`) AND `w2`.`b`<=(`orig`.`b`-`w1`.`b`)
 AND `w2`.`c`<=(`orig`.`c`-`w1`.`c`) AND `w2`.`d`<=(`orig`.`d`-`w1`.`d`)
 AND `w2`.`e`<=(`orig`.`e`-`w1`.`e`) AND `w2`.`f`<=(`orig`.`f`-`w1`.`f`)
 AND `w2`.`g`<=(`orig`.`g`-`w1`.`g`) AND `w2`.`h`<=(`orig`.`h`-`w1`.`h`)
 AND `w2`.`i`<=(`orig`.`i`-`w1`.`i`) AND `w2`.`j`<=(`orig`.`j`-`w1`.`j`)
 AND `w2`.`k`<=(`orig`.`k`-`w1`.`k`) AND `w2`.`l`<=(`orig`.`l`-`w1`.`l`)
 AND `w2`.`m`<=(`orig`.`m`-`w1`.`m`) AND `w2`.`n`<=(`orig`.`n`-`w1`.`n`)
 AND `w2`.`o`<=(`orig`.`o`-`w1`.`o`) AND `w2`.`p`<=(`orig`.`p`-`w1`.`p`)
 AND `w2`.`q`<=(`orig`.`q`-`w1`.`q`) AND `w2`.`r`<=(`orig`.`r`-`w1`.`r`)
 AND `w2`.`s`<=(`orig`.`s`-`w1`.`s`) AND `w2`.`t`<=(`orig`.`t`-`w1`.`t`)
 AND `w2`.`u`<=(`orig`.`u`-`w1`.`u`) AND `w2`.`v`<=(`orig`.`v`-`w1`.`v`)
 AND `w2`.`w`<=(`orig`.`w`-`w1`.`w`) AND `w2`.`x`<=(`orig`.`x`-`w1`.`x`)
 AND `w2`.`y`<=(`orig`.`y`-`w1`.`y`) AND `w2`.`z`<=(`orig`.`z`-`w1`.`z`)
LEFT JOIN `tome_entries` w3 ON w3.`length`<=`w2`.`length`
 AND `w3`.`a`<=(`orig`.`a`-`w1`.`a`-`w2`.`a`) AND `w3`.`b`<=(`orig`.`b`-`w1`.`b`-`w2`.`b`)
 AND `w3`.`c`<=(`orig`.`c`-`w1`.`c`-`w2`.`c`) AND `w3`.`d`<=(`orig`.`d`-`w1`.`d`-`w2`.`d`)
 AND `w3`.`e`<=(`orig`.`e`-`w1`.`e`-`w2`.`e`) AND `w3`.`f`<=(`orig`.`f`-`w1`.`f`-`w2`.`f`)
 AND `w3`.`g`<=(`orig`.`g`-`w1`.`g`-`w2`.`g`) AND `w3`.`h`<=(`orig`.`h`-`w1`.`h`-`w2`.`h`)
 AND `w3`.`i`<=(`orig`.`i`-`w1`.`i`-`w2`.`i`) AND `w3`.`j`<=(`orig`.`j`-`w1`.`j`-`w2`.`j`)
 AND `w3`.`k`<=(`orig`.`k`-`w1`.`k`-`w2`.`k`) AND `w3`.`l`<=(`orig`.`l`-`w1`.`l`-`w2`.`l`)
 AND `w3`.`m`<=(`orig`.`m`-`w1`.`m`-`w2`.`m`) AND `w3`.`n`<=(`orig`.`n`-`w1`.`n`-`w2`.`n`)
 AND `w3`.`o`<=(`orig`.`o`-`w1`.`o`-`w2`.`o`) AND `w3`.`p`<=(`orig`.`p`-`w1`.`p`-`w2`.`p`)
 AND `w3`.`q`<=(`orig`.`q`-`w1`.`q`-`w2`.`q`) AND `w3`.`r`<=(`orig`.`r`-`w1`.`r`-`w2`.`r`)
 AND `w3`.`s`<=(`orig`.`s`-`w1`.`s`-`w2`.`s`) AND `w3`.`t`<=(`orig`.`t`-`w1`.`t`-`w2`.`t`)
 AND `w3`.`u`<=(`orig`.`u`-`w1`.`u`-`w2`.`u`) AND `w3`.`v`<=(`orig`.`v`-`w1`.`v`-`w2`.`v`)
 AND `w3`.`w`<=(`orig`.`w`-`w1`.`w`-`w2`.`w`) AND `w3`.`x`<=(`orig`.`x`-`w1`.`x`-`w2`.`x`)
 AND `w3`.`y`<=(`orig`.`y`-`w1`.`y`-`w2`.`y`) AND `w3`.`z`<=(`orig`.`z`-`w1`.`z`-`w2`.`z`)

WHERE `orig`.`word`='RELATED'
AND (
	`w1`.`length` = `orig`.`length`
	OR
	`w1`.`length`+`w2`.`length` = `orig`.`length`
	OR
	`w1`.`length`+`w2`.`length`+`w3`.`length` = `orig`.`length`
)
ORDER BY `w1`.`length` DESC, `w2`.`length` DESC, `w3`.`length` DESC
;