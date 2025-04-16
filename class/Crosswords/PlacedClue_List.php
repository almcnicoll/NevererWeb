<?php

namespace Crosswords {
  use Basic\Typed_List;
  class PlacedClue_List extends Typed_List {
    /** called when accessed like echo $list[$offset]; 
     * @return PlacedClue the item from the array
    */
    public function offsetGet($offset) : PlacedClue {
      return $this->protected_get($offset);
    }

    /** called when accessed like foreach($list as $item) { // $item is type PlacedClue }
     * @return PlacedClue the item from the collection
     */
    public function current() : PlacedClue {
      return $this->protected_get($this->_position);
    }

    /**
     * Retrieves the current class
     * @return string the name of the contained class (PlacedClue)
     */
    public function get_type() : string {
      return PlacedClue::class;
    }

    /**
     * Orders an array of PlacedClues by Across then Down
     * @param PlacedClue $a
     * @param PlacedClue $b
     * @return int -1 or 1 as required by the sort
     */
    static function usortByAD($a, $b) : int {
      // Check Across/Down first, then clue number
      if ($a->orientation == PlacedClue::ACROSS && $b->orientation == PlacedClue::DOWN) { return -1; }
      if ($a->orientation == PlacedClue::DOWN && $b->orientation == PlacedClue::ACROSS) { return 1; }
      return ($a->y*100+$a->x) <=> ($b->y*100+$b->x);
    }
    /**
     * Orders an array of PlacedClues by Across then Down
     * @param PlacedClue $a
     * @param PlacedClue $b
     * @return int -1 or 1 as required by the sort
     */
    static function usortByNumber($a, $b) : int {
      // Check clue number first, then Across/Down
      if ($a->y == $b->y && $a->x == $b->x) {
        if ($a->orientation == PlacedClue::ACROSS && $b->orientation == PlacedClue::DOWN) { return -1; }
        if ($a->orientation == PlacedClue::DOWN && $b->orientation == PlacedClue::ACROSS) { return 1; }
        return 0;
      } else {
        return ($a->y*100+$a->x) <=> ($b->y*100+$b->x);
      }
    }

    public function sortByAD() {
      usort($this->_list, "Crosswords\\PlacedClue_List::usortByAD");
    }
    public function sortByNumber() {
      usort($this->_list, "Crosswords\\PlacedClue_List::usortByNumber");
    }
  }
}