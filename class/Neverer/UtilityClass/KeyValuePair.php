<?php
namespace Neverer\UtilityClass;

class KeyValuePair
{
    public $key = null;
    public $value = null;

    public function __construct($k, $v)
    {
        $this->key = $k;
        $this->value = $v;
    }
}