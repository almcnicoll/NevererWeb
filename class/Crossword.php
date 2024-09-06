<?php

class Crossword extends Model {
    public int $user_id;
    public ?string $title = null;
    public ?int $rows = null;
    public ?int $cols = null;
    public int $rotational_symmetry_order = 2;

    static string $tableName = "crosswords";
    static $fields = ['id','user_id','title','rows','cols','rotational_symmetry_order','created','modified'];

    public static $defaultOrderBy = [['modified','DESC'],['id','DESC']];

    public function getUser() : User {
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

    public function getPlacedClues() : PlacedClue_List {
        $criteria = ['crossword_id','=',$this->id];
        $orderBy = [['place_number','asc'],['orientation','asc']];
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
        /*
        // NB In theory this code shouldn't be necessary, as place_number should now be set every time a clue is saved
        $lastOrder = -1;
        $clueIncrement = 0;
        $placedClues = $this->getPlacedClues();
        $sorted_clues = [];

        for($i=0; $i<count($placedClues); $i++) {
            $pc = $placedClues[$i];
            $ord = $pc->getOrderingValue();

            if ($ord != $lastOrder) {
                $clueIncrement++;
            }

            if ($pc->orientation != 'Unset') {
                $k = $pc->orientation.$clueIncrement;
                while (array_key_exists($k, $sorted_clues)) {
                    // This happens when we mirror clues that are on an axis of symmetry and we end up with two across/down clues starting in the same place - just ignore and increment as often as needed
                    $clueIncrement++;
                    $k = $pc->orientation.$clueIncrement;
                }
                $pc->placeNumber = $clueIncrement;
                $sorted_clues[$k] = $pc;
            }

            $lastOrder = $ord;
        }
        if ($order == Crossword::SORT_ORDER_AD) {
            // Order by Across then Down
            ksort($sorted_clues,SORT_STRING);
        } else {
            // Order by place number
            uksort($sorted_clues,[Crossword::class,'sortCluePlaceByNumber']);
        }
        $clues = new PlacedClue_List($sorted_clues);
        return $clues;
        */
        $placedClues = $this->getPlacedClues();
        return $placedClues;
    }

    /** Sets the place numbers for all clues in the crossword */
    public function setPlaceNumbers() {
        // Create SQL
        $table = PlacedClue::$tableName;
        $sql = <<<END_SQL
UPDATE `{$table}`
INNER JOIN 
(
SELECT `y`*1000+`x` AS ordering_value, ROW_NUMBER() OVER () AS rownum
FROM `{$table}`
WHERE `crossword_id`=?
GROUP BY ordering_value
ORDER BY ordering_value ASC
) `t_order` ON t_order.ordering_value=(`y`*1000+`x`)
SET `{$table}`.`place_number` = `t_order`.`rownum`
WHERE `crossword_id`=?
ORDER BY `y`,`x`
;
END_SQL;
        $pdo = db::getPDO();
        $criteria_values = [$this->id,$this->id];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($criteria_values);
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
        // Get clues
        $allPClues = $this->getSortedClueList();
        if ($xMax == null) { $xMax = $this->cols-1; }
        if ($yMax == null) { $yMax = $this->rows-1; }
        if ($xMax<$xMin) { return json_encode([]); }
        if ($yMax<$yMin) { return json_encode([]); }

        $squares = new Grid();

        for ($y=$yMin; $y<=$yMax; $y++) {
            $squares[$y] = new GridRow();
            for ($x=$xMin; $x<=$xMax; $x++) {
                $squares[$y][$x] = new GridSquare($x, $y, true);
            }
        }

        foreach ($allPClues as $placed_clue) {
            $clue = $placed_clue->getClue();
            $len = $clue->getLength();
            switch (strtolower($placed_clue->orientation)) {
                case PlacedClue::ACROSS:
                    $y = $placed_clue->y;
                    for ($ii=0; $ii<$len; $ii++) {
                        $x = $placed_clue->x + $ii;
                        $squares[$y][$x]->black_square = false;
                        if ($squares[$y][$x]->letter != '') { $squares[$y][$x]->setFlag(GridSquare::FLAG_CONFLICT); } // If already set
                        $squares[$y][$x]->letter = substr($clue->getAnswerLetters(),$ii,1);
                        if ($ii==0) { $squares[$y][$x]->clue_number = $placed_clue->place_number; }
                    }
                    break;
                case PlacedClue::DOWN:
                    $x = $placed_clue->x;
                    for ($ii=0; $ii<$len; $ii++) {
                        $y = $placed_clue->y + $ii;
                        $squares[$y][$x]->black_square = false;
                        if ($squares[$y][$x]->letter != '') { $squares[$y][$x]->setFlag(GridSquare::FLAG_CONFLICT); } // If already set
                        $squares[$y][$x]->letter = substr($clue->getAnswerLetters(),$ii,1);
                        if ($ii==0) { $squares[$y][$x]->clue_number = $placed_clue->place_number; }
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
        // TODO - overlap logic here - return all clues that overlap with the specified one
        // TODO - ensure that comparison doesn't compare clue with itself - the supplied PlacedClue might or might not be in the database! (use id field, allowing that it might be unset)
        return $overlapClues;
    }

    /**
     * Examines the supplied clue and the crossword's symmetry setting and then determines if any additional clues need adding at the same time
     * @param PlacedClue $newClue the clue being created
     * @return PlacedClue_List the new clues to be created - returns an empty PlacedClue_List if none match
     */
    public function getNewSymmetryClues(PlacedClue $newClue) : PlacedClue_List {
        $newClues = new PlacedClue_List();
        // TODO - symmetry logic here
        // TODO - decide whether to throw an error if the new symmetry clue clashes with an existing clue of the same orientation
        // TODO - if so, also throw an error if the new symmetry clue partially overlaps with the supplied clue
        // NOTE - should not throw an error if it totally overlaps - just don't return an additional clue
        $clueLength = strlen($newClue->getClue()->answer);
        if ($this->rotational_symmetry_order > 1) { // Otherwise there's no symmetry, so return blank list
            // TODO - work out how to create the Clue objects in the getRotatedClue function instead of here (where they're not actually used!)
            // Logic for 2-fold
            $cReflect180 = $newClue->getClue()->blankClone();
            $pcReflect180 = $newClue->getRotatedClue(180);
            // Now check for clashes with existing clues
            if (count($this->getOverlapClues($pcReflect180, true))>0) {
                throw new IllegalClueOverlapException("2-fold symmetry clue overlaps illegally");
            }
            // If no clashes with existing clues, check it doesn't overlap itself
            if (
                ($pcReflect180->orientation == PlacedClue::ACROSS && $pcReflect180->y == $newClue->y)
                ||
                ($pcReflect180->orientation == PlacedClue::DOWN && $pcReflect180->x == $newClue->x)
            ) {
                // Check if it's partial overlap (throw error) or full overlap (don't add duplicate)

            } else {
                // All good - just add it
                $newClues[] = $pcReflect180;
            }
            if ($this->rotational_symmetry_order > 2) {
                // Logic for 4-fold
                $cReflect90 = $newClue->getClue()->blankClone();
                $pcReflect90 = $newClue->getRotatedClue(90);
                $cReflect270 = $newClue->getClue()->blankClone();
                $pcReflect270 = $newClue->getRotatedClue(270);
            }
            // Add 90-degree rotation, then check if 270-degree rotation duplicates (we should already have handled non-duplicate but clashing in errors above)
            // TODO - implement bracketed comment above
            $newClues[] = $pcReflect90;
            if (
                ($pcReflect90->orientation == PlacedClue::ACROSS && $pcReflect90->y != $pcReflect270->y)
                ||
                ($pcReflect90->orientation == PlacedClue::DOWN && $pcReflect90->x != $pcReflect270->x)
            ) {
                $newClues[] = $pcReflect270;
            }
        }
        return $newClues;
    }

    /**
     * Examines the supplied clue and the crossword's symmetry setting and then determines if there are existing clues that match symmetry rules
     * @param PlacedClue $newClue the clue being created
     * @return PlacedClue_List any clues that match the symmetry rules - returns an empty PlacedClue_List if none match
     */
    public function getExistingSymmetryClues(PlacedClue $newClue) : PlacedClue_List {
        $newClues = new PlacedClue_List();
        // TODO - symmetry logic here
        if ($this->rotational_symmetry_order > 1) { // Otherwise there's no symmetry, so return blank list
            //
        }
        return $newClues;
    }

    public function getGridHtml($include_answers) : string {
        // Consider sending blank grid, to be populated by AJAX call
        $allClues = $this->getSortedClueList();
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
        return '<pre>Not yet implemented</pre>\n';
    }
}