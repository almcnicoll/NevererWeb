<?php

class GridRow extends Typed_List {
    // called when accessed like echo $list[$offset];
    public function offsetGet($offset) : GridSquare {
        return $this->protected_get($offset);
    }

    // called when accessed like foreach($list as $item) { // $item is type GridSquare }
    public function current() : GridSquare {
        return parent::current($this->_position);
    }

    public function get_type() : string {
        return GridSquare::class;
    }
}