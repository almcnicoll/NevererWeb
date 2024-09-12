<?php

namespace UI {
    use Basic\Typed_List;
    class GridRow extends Typed_List {
        /** called when accessed like echo $list[$offset]; 
         * @return GridSquare the item from the array
         */
        public function offsetGet($offset) : GridSquare {
            return $this->protected_get($offset);
        }

        /** called when accessed like foreach($list as $item) { // $item is type GridSquare }
         * @return GridSquare the item from the collection
         */
        public function current() : GridSquare {
            return $this->protected_get($this->_position);
        }

        public function get_type() : string {
            return GridSquare::class;
        }

        /**
         * Convert the class object to an array for JSON-encoding
         */
        public function toArray() : mixed {
            $output = [];
            foreach ($this->_list as $item) {
                /** @var GridSquare $item */
                $output[] = $item->expose();
            }
            return $output;
        }
    }
}