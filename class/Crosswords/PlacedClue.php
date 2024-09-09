<?php
namespace Crosswords {
  use Basic\Model;
  use Exception, InvalidArgumentException;
  class PlacedClue extends Model {
      public int $crossword_id;
      // NB co-ordinates are ZERO-based
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

      /** Contains a Clue object for when a PlacedClue is being created from scratch */
      protected ?Clue $__captiveClue = null;

      /** 
       * Extends the built-in save method:
       * - updates the clue numbers once saved (can't do this beforehand as the clue may not yet be in the db, or may be about to be updated)
       * - saves any "captive clue" (not yet in db)
       * @return ?int the id of the saved record or null if the save failed
       */
      public function save() : ?int {
        if (!isset($this->place_number)) { $this->place_number = 0; } // Don't let's have this throw an error
        
        $returnVal = parent::save(); // Call parent save logic
        $crossword = $this->getCrossword(); // Retrieve the crossword
        // Save any "captive" clue that hasn't yet been saved
        if ($this->__captiveClue != null) {
          $this->__captiveClue->placedclue_id = $this->id;
          $this->__captiveClue->save();
          $this->__captiveClue = null;
        }
        if ($crossword !== null) { $crossword->setClueNumbers(); } // Update the place_numbers of all clues in the crossword
        return $returnVal;
      }

      /** Gets the crossword to which this clue is linked
       * @return Crossword the Crossword object
       * @throws Will throw an exception if the crossword specified by the crossword_id field cannot be found in the database
       */
      public function getCrossword() : Crossword {
          /** @var Crossword $cTmp */  
          $cTmp = Crossword::findFirst(['id','=',$this->crossword_id]);
          if ($cTmp == null) { throw new Exception("No matching crossword for this clue"); }
          return $cTmp;
      }

      /** Retrieves the clue for this PlacedClue, creating it if need be
       * @return Clue the underlying Clue object
       */
      public function getClue() : Clue {
        if ($this->__captiveClue != null) { error_log("We have an unsaved 'captive' clue in PlacedClue id {$this->id}"); return $this->__captiveClue; }
        $clue = Clue::findFirst(['placedclue_id','=',$this->id]);
        if ($clue == null) {
          error_log("Couldn't find clue with placedclue_id {$this->id}");
          $this->__captiveClue = new Clue();
          return $this->__captiveClue;
        }
        return $clue;
      }

      /**
       * Sets the captive clue for the PlacedClue when it already exists
       */
      public function setClue(Clue $clue, bool $safety = true) : void {
        if ($safety) {
          if ($safety && ($this->__captiveClue != null)) { throw new Exception("Captive clue already exists - will not overwrite in safety mode"); }
        }
        $this->__captiveClue = $clue;
        if (isset($this->id) || ($this->id != null)) { $this->__captiveClue->placedclue_id = $this->id; }
      }

      /**
       * Returns the opposite clue orientation from the one passed in
       * @param string $originalOrientation the orientation of which we want to find the opposite - it is recommended to use the PlacedClue::ACROSS and PlacedClue::DOWN constants
       * @return string the opposite orientation
       */
      public static function invertOrientation(string $originalOrientation) : string {
        switch($originalOrientation) {
          case PlacedClue::ACROSS:
            return PlacedClue::DOWN;
          case PlacedClue::DOWN:
            return PlacedClue::ACROSS;
          default:
            throw new InvalidArgumentException("Value supplied was neither across nor down. Please use the class constants PlacedClue::ACROSS / PlacedClue::DOWN to avoid this.");
        }
      }

      /**
       * Creates a PlacedClue that is based on the current PlacedClue but rotated by the specified number of degrees
       * @param int $degrees the number of degrees to rotate: 0, 90, 180, 270
       * @return PlacedClue a new (blank) PlacedClue object
       */
      public function getRotatedClue(int $degrees) : PlacedClue {
        // Check arguments
        if (!isset($this->crossword_id)) {
          throw new InvalidArgumentException("Supplied template clue does not link to a Crossword object");
        }
        $validRotations = [0,90,180,270];
        if (!in_array($degrees, $validRotations)) {
          throw new InvalidArgumentException("Cannot rotate by {$degrees} degrees: valid values are ".implode(', ',$validRotations));
        }
        // Retrieve variables
        $crossword = $this->getCrossword();
        $clueLength = strlen($this->getClue()->answer);
        // Do rotation
        switch ($degrees) {
          case 0:
            $pcReflect0 = new PlacedClue();
            $pcReflect0->crossword_id = $this->crossword_id;
            $pcReflect0->orientation = $this->orientation;
            $pcReflect0->x = $this->x;
            $pcReflect0->y = $this->y;
            $pcReflect0->setClue($this->getClue()->blankClone(),false);
            return $pcReflect0;
          case 90:
            $pcReflect90 = new PlacedClue();
            $pcReflect90->crossword_id = $this->crossword_id;
            $pcReflect90->orientation = PlacedClue::invertOrientation($this->orientation);
            $pcReflect90->x = $this->y;
            $pcReflect90->y = $crossword->cols-$this->x;
            $pcReflect90->setClue($this->getClue()->blankClone(),false);
            // If it's a DOWN clue, this gives us the END of the new clue - we want the START
            if ($this->orientation == PlacedClue::DOWN) { $pcReflect90->x -= ($clueLength-1); }
            return $pcReflect90;
          case 180:
            $pcReflect180 = new PlacedClue();
            $pcReflect180->crossword_id = $this->crossword_id;
            $pcReflect180->orientation = $this->orientation;
            $pcReflect180->x = $crossword->cols-$this->x-1;
            $pcReflect180->y = $crossword->rows-$this->y-1;
            $pcReflect180->setClue($this->getClue()->blankClone(),false);
            // This gives us the END of the new clue - we want the START
            if ($pcReflect180->orientation == PlacedClue::ACROSS) { $pcReflect180->x -= ($clueLength-1); } else { $pcReflect180->y -= ($clueLength-1); }
            return $pcReflect180;
            break;
          case 270:
            $pcReflect270 = new PlacedClue();
            $pcReflect270->crossword_id = $this->crossword_id;
            $pcReflect270->orientation = PlacedClue::invertOrientation($this->orientation);
            $pcReflect270->x = $crossword->rows-$this->y;
            $pcReflect270->y = $this->x;
            $pcReflect270->setClue($this->getClue()->blankClone(),false);
            // If it's an ACROSS clue, this gives us the END of the new clue - we want the START
            if ($this->orientation == PlacedClue::ACROSS) { $pcReflect270->y -= ($clueLength-1); }
            return $pcReflect270;
            break;
          default:
            // This won't happen, as it's covered above
            throw new InvalidArgumentException("Cannot rotate by {$degrees} degrees: valid values are ".implode(', ',$validRotations));
        }
      }
  }
}