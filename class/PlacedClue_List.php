<?php

class PlacedClue_List extends Typed_List {
  // called when accessed like echo $list[$offset];
  public function offsetGet($offset) : PlacedClue {
    return $this->protected_get($offset);
  }

  // called when accessed like foreach($list as $item) { // $item is type PlacedClue }
  public function current() : PlacedClue {
    return parent::current($this->_position);
  }

  public function get_type() : string {
    return PlacedClue::class;
  }
}