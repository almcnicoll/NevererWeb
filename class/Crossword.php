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

    public function getPlacedClues() : PlacedClue_List {
        $criteria = ['crossword_id','=',$this->id];
        $allClues = new PlacedClue_List(
            PlacedClue::find($criteria)
        );
        return $allClues;
    }

    public const SORT_ORDER_PLACE_NUMBER = 0;
    public const SORT_ORDER_AD = 1;
    public static function sortCluePlaceByNumber($a,$b) {
        return ((int)preg_replace('/[^0-9]+/','',$a) < (int)preg_replace('/[^0-9]+/','',$b)) ? -1 : 1;
    }
    /**
     * Retrieves the PlacedClues from the crossword. Their place numbers are (re-)calculated in the process.
     * @param int $order specifies whether to return the clues in the form 1A,2D,3A,3D,4D,7A or 1A,3A,7A,2D,3D,4D
     */
    public function getSortedClueList(int $order = Crossword::SORT_ORDER_PLACE_NUMBER) : PlacedClue_List {
        $lastOrder = -1;
        $clueIncrement = 0;
        $placedClues = $this->getPlacedClues();

        for($i=0; $i<count($placedClues); $i++) {
            $pc = $placedClues[$i];
            $ord = $pc->getOrder();

            if ($ord != $lastOrder) {
                $clueIncrement++;
            }

            $sc = [];
            if ($pc->orientation != 'Unset') {
                $k = $pc->orientation.$clueIncrement;
                while (array_key_exists($k, $sc)) {
                    // This happens when we mirror clues that are on an axis of symmetry and we end up with two across/down clues starting in the same place - just ignore and increment as often as needed
                    $clueIncrement++;
                    $k = $pc->orientation.$clueIncrement;
                }
                $pc->placeNumber = $clueIncrement;
                $sc[$k] = $pc;
            }

            $lastOrder = $ord;
        }
        if ($order == Crossword::SORT_ORDER_AD) {
            // Order by Across then Down
            ksort($sc,SORT_STRING);
        } else {
            // Order by place number
            uksort($sc,[Crossword::class,'sortCluePlaceByNumber']);
        }
        $clues = new PlacedClue_List($sc);
        return $clues;
    }

    /**
     * Gets the contents of the crossword as a Grid of GridSquares - all in JSON format
     * @return string the JSON-encoded object
     */
    public function getGridJson($xMin=0, $yMin=0, $xMax=null, $yMax=null) : string {
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
            for ($i=0; $i<$len; $i++) {
                switch ($placed_clue->orientation) {
                    case 'Across':
                        $y = $placed_clue->y;
                        for ($ii=0; $ii<$len; $ii++) {
                            $x = $placed_clue->x + $ii;
                            $squares[$y][$x]->black_square = false;
                            if ($squares[$y][$x]->letter != '') { $squares[$y][$x]->setFlag(GridSquare::FLAG_CONFLICT); } // If already set
                            $squares[$y][$x]->letter = substr($clue->getAnswerLetters(),$i,1);
                            if ($ii=0) { $squares[$y][$x]->clue_number = $placed_clue->getOrder(); }
                        }
                        break;
                    case 'Down':
                        $x = $placed_clue->x;
                        for ($ii=0; $ii<$len; $ii++) {
                            $y = $placed_clue->y + $ii;
                            $squares[$y][$x]->black_square = false;
                            if ($squares[$y][$x]->letter != '') { $squares[$y][$x]->setFlag(GridSquare::FLAG_CONFLICT); } // If already set
                            $squares[$y][$x]->letter = substr($clue->getAnswerLetters(),$i,1);
                            if ($ii=0) { $squares[$y][$x]->clue_number = $placed_clue->getOrder(); }
                        }
                        break;
                    default:
                        // We have to ignore it I guess?
                        break;
                }
            }
        }

        return json_encode($squares);
    }

    public function getGridHtml($include_answers) : string {
        // Consider sending blank grid, to be populated by AJAX call
        $allClues = $this->getSortedClueList();
        foreach ($allClues as $pc) {
            
        /*    $clues[$pc->orientation][] = new HtmlTag('td', $pc->placeNumber . ' ' . $pc->clueText);

            switch ($style) {
                case 'empty_grid':
                    // White-out clue area
                    switch ($pc->orientation) {
                        case 'down':
                            for ($yy = 0; $yy < $pc->clue->length; $yy++) {
                                $cells[$pc->x + $firstPuzzleCol][$pc->y + $yy + $firstPuzzleRow] = new HtmlTag('td', '', $letterAttr, $letterStyle);
                            }
                            break;
                        case 'across':
                            for ($xx = 0; $xx < $pc->clue->length; $xx++) {
                                $cells[$pc->x + $xx + $firstPuzzleCol][$pc->y + $firstPuzzleRow] = new HtmlTag('td', '', $letterAttr, $letterStyle);
                            }
                            break;
                    }
                    $cells[$pc->x + $firstPuzzleCol][$pc->y + $firstPuzzleRow] = new HtmlTag('td', $pc->placeNumber, $numberAttr, $numberStyle);
                    break;
                case 'grid_with_answers':
                    // Enter answer into grid
                    switch ($pc->orientation) {
                        case 'down':
                            for ($yy = 0; $yy < $pc->clue->length; $yy++) {
                                $cells[$pc->x + $firstPuzzleCol][$pc->y + $yy + $firstPuzzleRow] = new HtmlTag('td', $pc->clue->letters[$yy], $letterAttr, $letterStyle);
                            }
                            break;
                        case 'across':
                            for ($xx = 0; $xx < $pc->clue->length; $xx++) {
                                $cells[$pc->x + $xx + $firstPuzzleCol][$pc->y + $firstPuzzleRow] = new HtmlTag('td', $pc->clue->letters[$xx], $letterAttr, $letterStyle);
                            }
                            break;
                    }
                    break;
            }
        */
        }
        
        $html = "<table class='crossword-grid'></table>";
        return $html;
    }
}