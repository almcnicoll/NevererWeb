<?php

class PlacedClue_List extends Typed_List {
  // called when accessed like echo $list[$offset];
  public function offsetGet($offset) : PlacedClue {
    return $this->protected_get($offset);
  }

  // called when accessed like foreach($list as $item) { // $item is type PlacedClue }
  public function current() : PlacedClue {
    // TODO [31-Aug-2024 14:17:35 UTC] PHP Fatal error:  Uncaught Error: Cannot call abstract method Typed_List::current() in C:\Bitnami\wampstack-8.0.9-0\apache2\htdocs\neverer-web\class\PlacedClue_List.php:11
    return parent::current($this->_position);
  }

  public function get_type() : string {
    return PlacedClue::class;
  }
}