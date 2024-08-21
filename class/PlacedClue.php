<?php

class PlacedClue extends Model {
    public int $crossword_id;
    public int $x;
    public int $y;
    public string $orientation = 'Unset';
    public int $place_number;
    public int $status = 0;

    static string $tableName = "placedclues";
    static $fields = ['id','crossword_id','x','y','orientation','place_number','status','created','modified'];

    public static $defaultOrderBy = ['y','x','orientation'];
    public int $placeNumber = 0;

    public function getCrossword() : Crossword {
        $cTmp = Crossword::findFirst(['id','=',$this->crossword_id]);
        if ($cTmp == null) { throw new Exception("No matching crossword for this clue"); }
        return $cTmp;
    }

    public static function calculateOrder($row, $col) {
      return ($row) + $col;
    }

    public function getOrder() : int {
      return $this->calculateOrder($this->y, $this->x);
    }
}