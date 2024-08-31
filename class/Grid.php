<?php

class Grid extends Typed_List {
    /** called when accessed like echo $list[$offset]; 
     * @return GridRow the item from the array
     */
    public function offsetGet($offset) : GridRow {
        return $this->protected_get($offset);
    }

    /** called when accessed like foreach($list as $item) { // $item is type GridRow }
     * @return GridRow the item from the collection
     */
    public function current() : GridRow {
        return $this->protected_get($this->_position);
    }

    public function get_type() : string {
        return GridRow::class;
    }

    /**
     * Convert the class object to an array for JSON-encoding
     */
    public function toArray() : mixed {
        $output = [];
        foreach ($this->_list as $item) {
            $output[] = $item->toArray();
        }
        return $output;
    }
}