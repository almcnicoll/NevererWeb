<?php
/**
 * a <generic> list. This is the base class for all typed lists 
 * Extend this abstract class and implement:
 * offsetGet() : Your_Return_Type and current() : Your_Return_Type
 * and then use your new subclass of type Your_Return_Type.
 * EG:
 * class User_List extends Typed_List {
 *   public function offsetGet($index) : User {
 *     return $this->protected_get($index);
 *   }
 *   public function current() : User {
 *     return $this->protected_get($this->_position);
 *   }
 * }
 */
abstract class Typed_List extends BaseClass implements \ArrayAccess, \Iterator, \Countable, \SeekableIterator {

    protected string $_type = "mixed";
    protected $_position = 0;
    protected array $_list;

    private bool $_is_associated = false;
    private $_keys;

    private static function array_clone($array) {
        return array_slice($array, 0, null, true);
    }
    
    public function __construct(array $list = []) {
        $this->_list = $list;
        $this->_type = $this->get_type();
    }

    /**
     * Example: echo $list[$index]
     * @param mixed $index index may be numeric or hash key
     * @return $this->_type cast this to your subclass type
     */
    #[\ReturnTypeWillChange]
    public abstract function offsetGet($index) : mixed;

    /**
     * Example: foreach ($list as $key => $value)
     * @return mixed cast this to your subclass type at the current iterator index
     */
    #[\ReturnTypeWillChange]
    public abstract function current() : mixed;


    /**
     * @return string the name of the type list supports or mixed
     */
    public abstract function get_type() : string;


    /**
     * return a new instance of the subclass with the given list
     * @param array $list 
     * @return static 
     */
    public static function of(array $list) : static {
        return new static($list);
    }

    /**
     * clone the current list into a new object
     * @return static new instance of subclass
     */
    #[\ReturnTypeWillChange]
    public function clone() {
        $new = new static();
        $new->_list = Typed_List::array_clone($this->_list);
        return $new;
    }

    /**
     * Example count($list);
     * @return int<0, \max> - the number of elements in the list
     */
    public function count(): int {
        return count($this->_list);
    }

    /**
     * SeekableIterator implementation
     * @param mixed $position - seek to this position in the list
     * @throws OutOfBoundsException - if the element does not exist
     */
    public function seek($position) : void {
        if (!isset($this->_list[$position])) {
            throw new OutOfBoundsException("invalid seek position ($position)");
        }
  
        $this->_position = $position;
    }

    /**
     * SeekableIterator implementation. seek internal pointer to the first element
     * @param mixed $position - seek to this position in the list
     */
    public function rewind() : void {
        if ($this->_is_associated) {
            $this->_keys = array_keys($this->_list);
            $this->_position = array_shift($this->_keys);
        } else {
            $this->_position = 0;
        }
    }

    /**
     * SeekableIterator implementation. equivalent of calling current()
     * @return mixed - the pointer to the current element
     */
    public function key() : mixed {
        return $this->_position;
    }

    /**
     * SeekableIterator implementation. equivalent of calling next()
     */
    public function next(): void {
        if ($this->_is_associated) {
            $this->_position = array_shift($this->_keys);
        } else {
            ++$this->_position;
        }
    }

    /**
     * SeekableIterator implementation. check if the current position is valid
     */
    public function valid() : bool {
        if (isset($this->_list[$this->_position])) {
            if ($this->_type != "mixed") {
                return $this->_list[$this->_position] instanceof $this->_type;
            }
            return true;
        }
        return false;
    }

    /**
     * Example: $list[1] = "data";  $list[] = "data2";
     * ArrayAccess implementation. set the value at a specific index
     * @throws 
     */
    public function offsetSet($index, $value) : void {
        // type checking
        if ($this->_type != "mixed") {
            if (! $value instanceof $this->_type) {
                $msg = get_class($this) . " only accepts objects of type \"" . $this->_type . "\", \"" . gettype($value) . "\" passed";
                throw new InvalidArgumentException($msg, 1);
            }
        }
        if (empty($index)) {
            $this->_list[] = $value;
        } else {
            $this->_is_associated = true;
            $this->_list[$index] = $value;
            /*if ($index instanceof Hash_Code) {
                $this->_list[$index->hash_code()] = $value;
            } else {
                $this->_list[$index] = $value;
            }*/
        }
    }

    /**
     * unset($list[$value]);
     * ArrayAccess implementation. unset the value at a specific index
     */
    public function offsetUnset($index) : void {
        unset($this->_list[$index]);
    }

    /**
     * ArrayAccess implementation. check if the value at a specific index exist
     */
    public function offsetExists($index) : bool {
        return isset($this->_list[$index]);
    }

    /**
     * example $data = array_map($fn, $list->raw());
     * @return array - the internal array structure
     */
    public function &raw() : array {
        return $this->_list;
    }


    /**
     * sort the list
     * @return static - the current instance sorted
     */
    public function ksort(int $flags = SORT_REGULAR): static {
        ksort($this->_list, $flags);
        return $this;
    }

    /**
     * @return bool - true if the list is empty
     */
    public function empty() : bool {
        return empty($this->_list);
    }

    /**
     * helper method to be used by offsetGet() and current(), does bounds and key type checking
     * @param mixed $key 
     * @throws OutOfBoundsException - if the key is out of bounds
     */
    protected function protected_get($key) {
        if ($this->_is_associated) {
            if (isset($this->_list[$key])) {
                return $this->_list[$key];
            }
        }
        else {
            if ($key <= count($this->_list)) {
                return $this->_list[$key];
            }
        }

        throw new OutOfBoundsException("invalid key [$key]");
    }


   /**
     * filter the list using the given function 
     * @param callable $fn 
     * @return static
     */
    public function filter(callable $fn, bool $clone = false) {
        //assert(fn_takes_x_args($fn, 1), last_assert_err() . " in " . get_class($this) . "->map()"); 
        //assert(fn_arg_x_is_type($fn, 0, $this->_type), last_assert_err() . " in " . get_class($this) . "->map()");
        if ($clone) {
            return new static(array_filter(Typed_List::array_clone($this->_list), $fn));
        }
        $this->_list = array_filter($this->_list, $fn);
        return $this;
    }

    /**
     * json encoded version of the list
     * @return string json encoded version of first 5 elements
     */
    public function __toString() : string {
        return json_encode(array_slice($this->_list, 0, 5));
    }
}?>