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

    public const ACROSS = 'across';
    public const DOWN = 'down';

    // TODO - some way of having a Clue object linked to a PlacedClue object without saving either to the database (so they can save at the same time)

    /** 
     * Extends the built-in save method to update the clue numbers once saved
     * - we can't do this easily beforehand as the clue may not yet be in the database, or may be about to be updated  
     * @return ?int the id of the saved record or null if the save failed
     */
    public function save() : ?int {
      if (!isset($this->place_number)) { $this->place_number = 0; } // Don't let's have this throw an error
      $returnVal = parent::save(); // Call parent save logic
      $crossword = $this->getCrossword(); // Retrieve the crossword
      if ($crossword !== null) { $crossword->setPlaceNumbers(); } // Update the place_numbers of all clues in the crossword
      return $returnVal;
    }

    /** Gets the crossword to which this clue is linked
     * @return Crossword the Crossword object
     * @throws Will throw an exception if the crossword specified by the crossword_id field cannot be found in the database
     */
    public function getCrossword() : Crossword {
        $cTmp = Crossword::findFirst(['id','=',$this->crossword_id]);
        if ($cTmp == null) { throw new Exception("No matching crossword for this clue"); }
        return $cTmp;
    }

    /**
     * Gets a value for the given x and y co-ords which can be used to order a clue within a crossword
     * @param int $row the row of the clue
     * @param int $col the column of the clue
     * @return int the ordering value
     */
    static function calculateOrder(int $row, int $col) : int {
      return ($row*1000) + $col;
    }

    /**
     * Gets a value that orders the clue within the crossword
     * @return int a number which orders the clue within the crossword
     */
    function getOrderingValue() : int {
      return $this->calculateOrder($this->y, $this->x);
    }

    public function getClue() : Clue {
      return Clue::findFirst(['placedclue_id','=',$this->id]);
    }
}