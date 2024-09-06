<?php

namespace UI {
  class BootstrapMenuItem_List extends \Typed_List {
    /** called when accessed like echo $list[$offset]; 
     * @return BootstrapMenuItem the item from the array
    */
    public function offsetGet($offset) : BootstrapMenuItem {
      return $this->protected_get($offset);
    }

    /** called when accessed like foreach($list as $item) { // $item is type BootstrapMenuItem }
     * @return BootstrapMenuItem the item from the collection
     */
    public function current() : BootstrapMenuItem {
      return $this->protected_get($this->_position);
    }

    /**
     * Retrieves the current class
     * @return string the name of the contained class (BootstrapMenuItem)
     */
    public function get_type() : string {
      return BootstrapMenuItem::class;
    }
  }
}