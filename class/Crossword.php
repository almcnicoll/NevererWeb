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

    public function getSortedClueList() : PlacedClue_List {
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
        $clues = new PlacedClue_List($sc);
        return $clues;
    }

    public function getGridHtml($include_answers) : string {
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