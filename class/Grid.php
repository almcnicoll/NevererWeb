<?php

class Grid extends Typed_List {
    // called when accessed like echo $list[$offset];
    public function offsetGet($offset) : GridRow {
        return $this->protected_get($offset);
    }

    // called when accessed like foreach($list as $item) { // $item is type GridRow }
    public function current() : GridRow {
        return parent::current($this->_position);
    }

    public function get_type() : string {
        error_log("get_type returning ".GridRow::class);
        return GridRow::class;
    }
}