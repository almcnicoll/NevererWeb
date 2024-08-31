<?php

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
}