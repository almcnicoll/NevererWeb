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

    public function getCrossword() : Crossword {
        $cTmp = Crossword::findFirst(['id','=',$this->crossword_id]);
        if ($cTmp == null) { throw new Exception("No matching crossword for this clue"); }
        return $cTmp;
    }

    public static $defaultOrderBy = ['y','x','orientation'];
}

class PlacedClue_List extends Typed_List {

    // called when accessed like echo $list[$offset];
    public function offsetGet($offset) : PlacedClue {
      return $this->list[$offset];
    }
  
    // called when accessed like foreach($list as $item) { // $item is type User }
    public function current() : PlacedClue {
      return $this->list[$this->position];
    }
  }