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

      /** Retrieves the clue for this PlacedClue, creating it if need be, and caches it for repeat use
       * @return Clue the underlying Clue object
       */
      public function getClue() : Clue {
        if ($this->__captiveClue != null) { return $this->__captiveClue; }
        $clue = Clue::findFirst(['placedclue_id','=',$this->id]);
        if ($clue == null) {
          error_log("Couldn't find clue with placedclue_id {$this->id}");
          $this->__captiveClue = new Clue();
          return $this->__captiveClue;
        }
        // NB the line below is key. Otherwise repeat calls to getClue() on the same PlacedClue will re-retrieve the object from the database, losing any local changes already made
        $this->__captiveClue = $clue;
        return $clue;
      }

      /**
       * Retrieves the length of the underlying clue
       * @return int the length of the clue
       */
      public function getLength() : int {
        return $this->getClue()->getLength();
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
        $clueLength = $this->getLength();
        // Do rotation
        switch ($degrees) {
          case 0:
            $pcReflect0 = new PlacedClue();
            $pcReflect0->crossword_id = $this->crossword_id;
            $pcReflect0->orientation = $this->orientation;
            $pcReflect0->x = $this->x;
            $pcReflect0->y = $this->y;
            $pcReflect0->setClue($this->getClue()->blankClone(),false);
            $pcReflect0->__tag = 0;
            return $pcReflect0;
          case 90:
            // TODO - HIGH this maths is sometimes wrong
            // Calculate endpoints of the clue, ignoring the concept of start and end
            $x1 = $x2 = $this->x;
            $y1 = $y2 = $this->y;
            if ($this->orientation == PlacedClue::ACROSS) { $x2 += $this->getLength()-1; } else { $y2 += $this->getLength()-1; }
            // Rotate them
            $rx1 = $crossword->lastRow()-$y1;
            $ry1 = $x1;
            $rx2 = $crossword->lastRow()-$y2;
            $ry2 = $x2;

            // Make the new clue
            $pcReflect90 = new PlacedClue();
            $pcReflect90->crossword_id = $this->crossword_id;
            $pcReflect90->orientation = PlacedClue::invertOrientation($this->orientation);
            // Use the minimum value of each of the two x and y points because we want the START of the clue
            $pcReflect90->x = min($rx1,$rx2);
            $pcReflect90->y = min($ry1,$ry2);
            $pcReflect90->setClue($this->getClue()->blankClone(),false);
            $pcReflect90->__tag = 90;
            return $pcReflect90;
          case 180:
            $pcReflect180 = new PlacedClue();
            $pcReflect180->crossword_id = $this->crossword_id;
            $pcReflect180->orientation = $this->orientation;
            $pcReflect180->x = $crossword->cols-$this->x-1;
            $pcReflect180->y = $crossword->rows-$this->y-1;
            $pcReflect180->setClue($this->getClue()->blankClone(),false);
            $pcReflect180->__tag = 180;
            // This gives us the END of the new clue - we want the START
            if ($pcReflect180->orientation == PlacedClue::ACROSS) { $pcReflect180->x -= ($clueLength-1); } else { $pcReflect180->y -= ($clueLength-1); }
            return $pcReflect180;
            break;
          case 270:
            // TODO - HIGH this maths is sometimes wrong
            // Calculate endpoints of the clue, ignoring the concept of start and end
            $x1 = $x2 = $this->x;
            $y1 = $y2 = $this->y;
            if ($this->orientation == PlacedClue::ACROSS) { $x2 += $this->getLength()-1; } else { $y2 += $this->getLength()-1; }
            // Rotate them
            $rx1 = $y1;
            $ry1 = $crossword->lastCol()-$x1;
            $rx2 = $y2;
            $ry2 = $crossword->lastRow()-$x2;

            // Make the new clue
            $pcReflect270 = new PlacedClue();
            $pcReflect270->crossword_id = $this->crossword_id;
            $pcReflect270->orientation = PlacedClue::invertOrientation($this->orientation);
            // Use the minimum value of each of the two x and y points because we want the START of the clue
            $pcReflect270->x = min($rx1,$rx2);
            $pcReflect270->y = min($ry1,$ry2);
            $pcReflect270->setClue($this->getClue()->blankClone(),false);
            $pcReflect270->__tag = 270;
            return $pcReflect270;
            break;
          default:
            // This won't happen, as it's covered above
            throw new InvalidArgumentException("Cannot rotate by {$degrees} degrees: valid values are ".implode(', ',$validRotations));
        }
      }

      /**
       * Checks if the current PlacedClue overlaps with the comparison clue
       */
      public function overlapsWith(PlacedClue $comparisonClue) : bool {
        // If clues are the same clue, we define them as not overlapping
        if ($this->is($comparisonClue)) { return false; }
        // Populate variables for current clue
        $Ax1 = $this->x; $Ax2 = $this->x;
        $Ay1 = $this->y; $Ay2 = $this->y;
        if ($this->orientation == PlacedClue::ACROSS) { $Ax2 += ($this->getLength()-1); } else { $Ay2 += ($this->getLength()-1); }
        // Populate variables for comparison clue
        $Bx1 = $comparisonClue->x; $Bx2 = $comparisonClue->x;
        $By1 = $comparisonClue->y; $By2 = $comparisonClue->y;
        if ($comparisonClue->orientation == PlacedClue::ACROSS) { $Bx2 += ($comparisonClue->getLength()-1); } else { $By2 += ($comparisonClue->getLength()-1); }
        // Compare
        if ($Ax2 < $Bx1) { return false; } // A wholly to the left of B
        if ($Ax1 > $Bx2) { return false; } // A wholly to the right of B
        if ($Ay2 < $By1) { return false; } // A wholly above B
        if ($Ay1 > $By2) { return false; } // A wholly below B
        return true; // Otherwise they overlap
      }


      /**
       * Access the object in a JSON-encodable form
       * Extends the base method to also retrieve and output Clue object
       */
      public function expose($includeClue = true) : mixed {
        $output = parent::expose();
        if ($includeClue) {
          $clue = $this->getClue();
          $output['clue'] = $clue->expose();
        }
        return $output;
      }

      /**
       * Clears the letters between the specified points, replacing them with question-mark placeholders
       * @param int $start the zero-based start point
       * @param int $end the zero-based end point
       * @param bool $save whether to save the clue after the substitution is done
       */
      public function clearBetween($start, $end, $save = false) : void {
        // Get answer
        $answer = $this->getClue()->answer;
        // Check that the start is valid
        if ($start < 0) { $start = 0; }
        if ($start >= strlen($answer)) { $start = strlen($answer)-1; }
        // Check that our endpoint isn't too big
        if ($end >= strlen($answer)) { $end = strlen($answer)-1; }
        // Now splice the answer with question marks
        $clue = $this->getClue();
        $newAnswer = substr($answer,0,$start) . str_repeat('?',$end-$start+1) . substr($answer,$end+1);
        $clue->answer = $newAnswer;
        // Save if so requested
        if ($save) {
          $clue->save();
        }
      }

      /**
       * Gets clues that are symmetry-linked to the current one
       * @return PlacedClue_List the list of clues
       */
      function getSymmetryClues() : PlacedClue_List {
        $symClues = new PlacedClue_List();
        $crossword = $this->getCrossword();
        return $crossword->getExistingSymmetryClues($this);
      }
  }
}