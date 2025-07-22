<?php

namespace Crosswords {
    use Basic\Model, Basic\db;
    use Security\User;
    use UI\Grid, UI\GridRow, UI\GridSquare;
    use Exception, Exceptions\IllegalClueOverlapException;
    use InvalidArgumentException;
    use Logging\LoggedError;

    class Crossword extends Model {
        public int $user_id;
        public ?string $title = null;
        public ?int $rows = null;
        public ?int $cols = null;
        public int $rotational_symmetry_order = 2;

        static string $tableName = "crosswords";
        static $fields = ['id','user_id','title','rows','cols','rotational_symmetry_order','created','modified'];

        public static $defaultOrderBy = [['modified','DESC'],['id','DESC']];

        // Relationships
        public static $belongsTo = User::class;
        public static $hasMany = [PlacedClue::class];

        public function getUser() : User {
            /** @var User $uTmp */
            $uTmp = User::findFirst(['id','=',$this->user_id]);
            if ($uTmp == null) { throw new Exception("No matching user for this crossword"); }
            return $uTmp;
        }

        /**
         * Checks if the crossword is owned by the specified user
         * @param mixed $user the id of the user or a User object
         */
        public function isOwnedBy($user) : bool {
            if ($user instanceof User) {
                $user_id = $user->id;
            } elseif (is_numeric($user)) {
                $user_id = $user;
            } else {
                throw new Exception("Invalid input passed to isOwnedBy()");
            }
            return ($user_id == $this->user_id);
        }

        /**
         * Returns all PlacedClue objects associated with the crossword
         * @param int $sortOrder The order in which to return the clues (default is PlacedClue::ORDER_PLACENUMBER)
         */
        public function getPlacedClues(int $sortOrder = PlacedClue::ORDER_PLACENUMBER) : PlacedClue_List {
            $criteria = ['crossword_id','=',$this->id];
            switch ($sortOrder) {
                case PlacedClue::ORDER_PLACENUMBER:
                    $orderBy = [['place_number','asc'],['orientation','asc']];
                    break;
                case PlacedClue::ORDER_AD:
                    $orderBy = [['orientation','asc'],['place_number','asc']];
                    break;
                default:
                    $orderBy = null;
                    break;
            }
            
            $allClues = new PlacedClue_List(
                PlacedClue::find($criteria, $orderBy)
            );
            return $allClues;
        }

        public const SORT_ORDER_PLACE_NUMBER = 0;
        public const SORT_ORDER_AD = 1;
        /**
         * Function to sort compound CluePlace strings (Across3, Down7, etc) by number instead of alpha
         * @return int -1 or 1 as required by the sort
         */
        public static function sortCluePlaceByNumber($a,$b) {
            return ((int)preg_replace('/[^0-9]+/','',$a) < (int)preg_replace('/[^0-9]+/','',$b)) ? -1 : 1;
        }

        /**
         * Retrieves the PlacedClues from the crossword. Their place numbers are (re-)calculated in the process.
         * @param int $order specifies whether to return the clues in the form 1A,2D,3A,3D,4D,7A or 1A,3A,7A,2D,3D,4D
         */
        public function getSortedClueList(int $order = Crossword::SORT_ORDER_PLACE_NUMBER) : PlacedClue_List {
            $placedClues = $this->getPlacedClues();
            switch ($order) {
                case Crossword::SORT_ORDER_AD:
                    $placedClues->sortByAD();
                    break;
                case Crossword::SORT_ORDER_PLACE_NUMBER:
                    $placedClues->sortByNumber();
                    break;
                default:
                    throw new InvalidArgumentException("Invalid sorting argument: {$order}");
            }
            return $placedClues;
        }

        /** Sets the place numbers for all clues in the crossword */
        public function setClueNumbers() {
            // Create SQL
            $table = PlacedClue::$tableName;
            $sql = <<<END_SQL
UPDATE `{$table}`
INNER JOIN 
(
SELECT `y`,`x`, ROW_NUMBER() OVER (PARTITION BY crossword_id ORDER BY `y`,`x` ASC) AS rownum
FROM `placedclues`
WHERE `crossword_id` = :crossword_id1
GROUP BY crossword_id,`y`,`x`
) `t_order` ON t_order.`y`=`placedclues`.`y` AND t_order.`x`=`placedclues`.`x`
SET `{$table}`.`place_number` = `t_order`.`rownum`
WHERE `crossword_id` = :crossword_id2;
END_SQL;
            // Initialise & configure connection
            $pdo = db::getPDO();
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            // Prepare the statement
            $stmt = $pdo->prepare($sql);
            // Apparently we can't reuse a parameter name, even if the value is the same (https://www.php.net/manual/en/pdo.prepare.php)
            $stmt->bindParam(':crossword_id1', $this->id, \PDO::PARAM_INT);
            $stmt->bindParam(':crossword_id2', $this->id, \PDO::PARAM_INT);
            try {
                // Run that query!
                $stmt->execute();
            } catch (\PDOException $pex) {
                LoggedError::log('PDOException',0,__FILE__,__LINE__,$pex->getCode()."\n".print_r($pex->errorInfo,true));
                error_log('PDOException',0,__FILE__,__LINE__,$pex->getCode()."\n".print_r($pex->errorInfo,true));
            } catch (Exception $ex) {
                LoggedError::log('Exception',$ex->getCode(),__FILE__,__LINE__,$ex->getMessage());
                error_log('Exception',$ex->getCode(),__FILE__,__LINE__,$ex->getMessage());
            }
        }

        /** Returns the index of the last column of the crossword
         * NB this is a utility method - it is always cols-1, but this makes other code more readable
         * @return int the index of the last column
         */
        public function lastCol() : int {
            return $this->cols - 1;
        }

        /** Returns the index of the last row of the crossword
         * NB this is a utility method - it is always rows-1, but this makes other code more readable
         * @return int the index of the last row
         */
        public function lastRow() : int {
            return $this->rows - 1;
        }

        /**
         * Gets the contents of the crossword as a Grid of GridSquares
         * @param int $xMin the minimum column to retrieve from (default: 0)
         * @param int $yMin the minimum row to retrieve from (default: 0)
         * @param ?int $xMax the maximum column to retrieve from (default: last col)
         * @param ?int $yMax the maximum row to retrieve from (default: last row)
         * @return Grid the grid object, ready for use or serialization
         */
        public function getGrid(int $xMin=0, int $yMin=0, ?int $xMax=null, ?int $yMax=null) : Grid {
            // Set numbering
            $this->setClueNumbers();
            // Get clues
            $allPClues = $this->getSortedClueList();
            //error_log("Clues in list: ".count($allPClues));
            if ($xMax == null) { $xMax = $this->cols-1; }
            if ($yMax == null) { $yMax = $this->rows-1; }
            if ($xMax<$xMin) { return new Grid(); }
            if ($yMax<$yMin) { return new Grid(); }

            $squares = new Grid();

            for ($y=$yMin; $y<=$yMax; $y++) {
                $squares[$y] = new GridRow();
                for ($x=$xMin; $x<=$xMax; $x++) {
                    $squares[$y][$x] = new GridSquare($x, $y, true);
                }
            }

            foreach ($allPClues as $placedClue) {
                $clue = $placedClue->getClue();
                $len = $clue->getLength();
                switch (strtolower($placedClue->orientation)) {
                    case PlacedClue::ACROSS:
                        $y = $placedClue->y;
                        if (($y<$yMin || $y>$yMax)) { continue; } // This may happen if we're pulling a partial grid
                        for ($ii=0; $ii<$len; $ii++) {
                            $x = $placedClue->x + $ii; if ($x>=$this->cols) {continue;}
                            if (($x<$xMin || $x>$xMax)) { continue; } // This may happen if we're pulling a partial grid
                            $newLetter = substr($clue->getAnswerLetters(),$ii,1); if ($newLetter == '?') { $newLetter = ''; }
                            $squares[$y][$x]->black_square = false; // It's not a black square
                            $squares[$y][$x]->placed_clue_ids[] = $placedClue->id; // It is part of this clue
                            $squares[$y][$x]->setIntersect(GridSquare::INTERSECTS_ACROSS); // It has an across clue intersecting it
                            if ($squares[$y][$x]->letter != '') {
                                // Don't overwrite existing letter
                                if (($newLetter != '') && ($squares[$y][$x]->letter != $newLetter)) {
                                    $squares[$y][$x]->setFlag(GridSquare::FLAG_CONFLICT); //Flag conflict if they're two different non-blanks
                                }
                            } else {
                                // Overwrite if what's there is blank
                                $squares[$y][$x]->letter = $newLetter;
                            }
                            if ($ii==0) { $squares[$y][$x]->clue_number = $placedClue->place_number; }
                        }
                        break;
                    case PlacedClue::DOWN:
                        $x = $placedClue->x;
                        if (($x<$xMin || $x>$xMax)) { continue; } // This may happen if we're pulling a partial grid
                        for ($ii=0; $ii<$len; $ii++) {
                            $y = $placedClue->y + $ii; if ($y>=$this->rows) {continue;}
                            if (($y<$yMin || $y>$yMax)) { continue; } // This may happen if we're pulling a partial grid
                            $newLetter = substr($clue->getAnswerLetters(),$ii,1); if ($newLetter == '?') { $newLetter = ''; }
                            $squares[$y][$x]->black_square = false; // It's not a black square
                            $squares[$y][$x]->placed_clue_ids[] = $placedClue->id; // It is part of this clue
                            $squares[$y][$x]->setIntersect(GridSquare::INTERSECTS_DOWN); // It has a down clue intersecting it
                            if ($squares[$y][$x]->letter != '') {
                                // Don't overwrite existing letter
                                if (($newLetter != '') && ($squares[$y][$x]->letter != $newLetter)) {
                                    $squares[$y][$x]->setFlag(GridSquare::FLAG_CONFLICT); //Flag conflict if they're two different non-blanks
                                }
                            } else {
                                // Overwrite if what's there is blank
                                $squares[$y][$x]->letter = $newLetter;
                            }
                            if ($ii==0) { $squares[$y][$x]->clue_number = $placedClue->place_number; }
                        }
                        break;
                    default:
                        // We have to ignore it I guess?
                        break;
                }
            }
            return $squares;
        }

        /**
         * Retrieves all existing clues from the crossword which overlap with the specified clue
         * @param PlacedClue $placedClue the clue to check
         * @param bool $problemsOnly whether to return only those clues which overlap in a problematic manner (same orientation)
         */
        public function getOverlapClues(PlacedClue $placedClue, $problemsOnly = true) : PlacedClue_List {
            $overlapClues = new PlacedClue_List();
            $allClues = $this->getPlacedClues();
            foreach ($allClues as $clue) {
                if ($placedClue->overlapsWith($clue)) {
                    if (!$problemsOnly || ($placedClue->orientation == $clue->orientation)) {
                        $overlapClues[] = $clue;
                    }
                }
            }
            return $overlapClues;
        }

        /**
         * Examines the supplied clue and the crossword's symmetry setting and then determines if any additional clues need adding at the same time
         * @param PlacedClue $newClue the clue being created
         * @param ?int $overrideSymmetry the symmetry order to use, or null to use the crossword's current symmetry setting
         * @return PlacedClue_List the new clues to be created - returns an empty PlacedClue_List if none match
         * @throws IllegalClueOverlapException if the clues to be created would overlap illegally with an existing clue or each other (except if they would be exact duplicates)
         */
        public function getNewSymmetryClues(PlacedClue $newClue, ?int $overrideSymmetry = null) : PlacedClue_List {
            /** @var int $useSymmetry */
            if ($overrideSymmetry === null) {
                $useSymmetry = $this->rotational_symmetry_order;
            } else {
                $useSymmetry = $overrideSymmetry;
            }
            $newClues = new PlacedClue_List();
            $clueLength = strlen($newClue->getClue()->answer);
            if ($useSymmetry > 1) { // Otherwise there's no symmetry, so return blank list
                // Logic for 2-fold
                $pcReflect180 = $newClue->getRotatedClue(180);
                // Now check for clashes with existing clues
                if (count($this->getOverlapClues($pcReflect180, true))>0) {
                    throw new IllegalClueOverlapException("2-fold symmetry clue overlaps illegally");
                }
                // If no clashes with existing clues, check it doesn't overlap itself
                if ($pcReflect180->overlapsWith($newClue)) {
                    // The mirrored clue will have the same orientation and length, so if it starts in the same place then it's not a problem - but we don't want a second clue added
                    // However, if it starts in a different place, it's going to cause a partial overlap, which should throw an error
                    if (($newClue->x != $pcReflect180->x) || ($newClue->y != $pcReflect180->y)) {
                        throw new IllegalClueOverlapException("2-fold symmetry clue overlaps illegally");
                    }
                    // Otherwise just keep going without doing anything
                } else {
                    // All good - just add it
                    $newClues[] = $pcReflect180;
                }
                if ($useSymmetry > 2) {
                    // Logic for 4-fold
                    // Symmetry here shouldn't really result in bad overlaps, unless people manually mess with the symmetry of the puzzle - but we need to allow for that
                    $pcReflect90 = $newClue->getRotatedClue(90);
                    $pcReflect270 = $newClue->getRotatedClue(270);
                    // Check that the new clues don't overlap illegally with other existing clues
                    if ((count($this->getOverlapClues($pcReflect90, true))>0) || (count($this->getOverlapClues($pcReflect270, true))>0)) {
                        throw new IllegalClueOverlapException("4-fold symmetry clue overlaps illegally");
                    }
                    // Check that they don't overlap illegally with each other
                    if ($pcReflect90->overlapsWith($pcReflect270)) {
                        // The clues will have the same orientation and length, so if they start in the same place then it's not a problem - but only add one of them
                        // However, if they start in different places, it's going to cause a partial overlap, which should throw an error
                        if (($pcReflect90->x != $pcReflect270->x) || ($pcReflect90->y != $pcReflect270->y)) {
                            throw new IllegalClueOverlapException("4-fold symmetry clue overlaps illegally");
                        }
                        // Otherwise just add the one clue
                        $newClues[] = $pcReflect90;
                    } else {
                        // They don't overlap each other, so add both
                        $newClues[] = $pcReflect90;
                        $newClues[] = $pcReflect270;
                    }
                }
            }
            return $newClues;
        }

        /**
         * Examines the supplied clue and the crossword's symmetry setting and then determines if there are existing clues that match symmetry rules
         * @param PlacedClue $placedClue the clue whose rotations are to be checked
         * @param ?int $overrideSymmetry the symmetry order to use, or null to use the crossword's current symmetry setting
         * @return PlacedClue_List any clues that match the symmetry rules - returns an empty PlacedClue_List if none match
         */
        public function getExistingSymmetryClues(PlacedClue $placedClue, ?int $overrideSymmetry = null) : PlacedClue_List {
            /** @var int $useSymmetry */
            if ($overrideSymmetry === null) {
                $useSymmetry = $this->rotational_symmetry_order;
            } else {
                $useSymmetry = $overrideSymmetry;
            }
            $existingClues = new PlacedClue_List();
            // If there's no symmetry, there's no symmetry clues
            if ($useSymmetry == 1) { return $existingClues; }
            // Define the 180 rotation
            $templateClues = new PlacedClue_List();
            $pcReflect180 = $placedClue->getRotatedClue(180);
            // Consider this a symmetry clue if it isn't an exact overlap of the original
            if (($pcReflect180->x != $placedClue->x) || ($pcReflect180->y != $placedClue->y)) {
                $templateClues[] = $pcReflect180;
            }
            if ($useSymmetry > 2) {
                // Define the 90 & 270 rotations
                $pcReflect90 = $placedClue->getRotatedClue(90);
                $pcReflect270 = $placedClue->getRotatedClue(270);
                $templateClues[] = $pcReflect90;
                // Consider the 270 a symmetry clue if it isn't an exact overlap of the 90
                if (($pcReflect90->x != $pcReflect270->x) || ($pcReflect90->y != $pcReflect270->y)) {
                    $templateClues[] = $pcReflect270;
                }
            }
            // If we've not found any valid rotations to check, there's no symmetry clues
            if (count($templateClues) == 0) { return $existingClues; }

            // Otherwise, loop through existing clues, checking them against the templates
            $checkClues = $this->getPlacedClues();
            foreach ($checkClues as $check) {
                foreach ($templateClues as $template) {
                    if (
                        ($check->orientation === $template->orientation) &&
                        ($check->x === $template->x) &&
                        ($check->y === $template->y) &&
                        ($check->getLength() == $template->getLength())
                    ) {
                        $check->__tag = $template->__tag; // Pass along the rotation information
                        $existingClues[] = $check;
                    }
                }
            }
            // Now return what we've found
            return $existingClues;
        }

        public function getGridHtml($include_answers) : string {
            // Consider sending blank grid, to be populated by AJAX call
            $html = "<table id='crossword-edit' class='crossword-grid'>\n";
            for ($y=0; $y<$this->rows; $y++) {
                $html .= "\t<tr class='crossword-grid-row' id='row-{$y}'>\n";
                for ($x=0; $x<$this->cols; $x++) {
                    $html .= "\t\t<td class='crossword-grid-square black-square' id='square-{$y}-{$x}'>\n";
                    $html .= "\t\t\t<div class='clue-number'></div>\n";
                    $html .= "\t\t\t<span class='letter-holder'></span>\n";
                    $html .= "\t\t</td>\n";
                }
                $html .= "\t</tr>\n";
            }
            $html .= "</table>\n";
            return $html;
        }

        public function getCluesHtml($include_answers) : string {
            $html = "<table id='clue-list' class='clue-grid'>\n";
            $html .= "\t<tr id='clues-across' class='clue-orientation-header'><th colspan='2'>Across</th></tr>\n";
            $html .= "\t<tbody id='clues-across-container' class='clue-orientation-container'></tbody>\n";
            $html .= "\t<tr id='clues-down' class='clue-orientation-header'><th colspan='2'>Down</th></tr>\n";
            $html .= "\t<tbody id='clues-down-container' class='clue-orientation-container'></tbody>\n";
            $html .= "</table>\n";
            return $html;
        }

        /**
         * @param int $x the x coordinate of the square to search
         * @param int $y the y coordinate of the square to search
         * @param string $orientation the type of clue to look for ('across'/'down')
         * @return ?PlacedClue the clue, if one is found, otherwise null
         */
        public function findClueFromXY(int $x, int $y, string $orientation) : ?PlacedClue {
            // Check values
            if ($x < 0) { throw_error("x value ({$x}) is too small"); }
            if ($y < 0) { throw_error("y value ({$y}) is too small"); }
            if ($x >= $this->cols) { throw_error("x value ({$x}) is too big"); }
            if ($y >= $this->rows) { throw_error("y value ({$y}) is too big"); }
            $orientation = strtolower($orientation);

            $criteria = [['crossword_id','=',$this->id],];
            if ($orientation == 'down') {
                $criteria[] = ['orientation','=','down'];
                $criteria[] = ['x','=',$x];
                $criteria[] = ['y','<=',$y];
            } else {
                $criteria[] = ['orientation','=','across'];
                $criteria[] = ['y','=',$y];
                $criteria[] = ['x','<=',$x];
            }
            $allPossibleClues = new PlacedClue_List(
                PlacedClue::find($criteria)
            );
            if (count($allPossibleClues) == 0) { return null; }
            //@var ?PlacedClue $pc
            foreach ($allPossibleClues as $pc) {
                // Check if this clue meets the remaining criterion (max on the dimension in which it runs)
                $l = $pc->getLength();
                if ($orientation == 'down') {
                    // Check max y
                    if ($y < $pc->y + $l) { return $pc; }
                } else {
                    // Check max x
                    if ($x < $pc->x + $l) { return $pc; }
                }
            }
            // No matches
            return null;
        }
    }
}