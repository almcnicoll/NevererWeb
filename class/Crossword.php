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

    public function getSortedClueList() : PlacedClue_List {
        // Code here - NB PlacedClue_List not working currently
    }

    public function getGridHtml($include_answers) : string {
        foreach ($this->getSortedClueList() as $pc) {
            $clues[$pc->orientation][] = new HtmlTag('td', $pc->placeNumber . ' ' . $pc->clueText);

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
        }
        
        $html = "<table class='crossword-grid'></table>";
        return $html;
    }
}