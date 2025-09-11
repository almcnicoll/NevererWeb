<?php
namespace Basic {
    // TODO - expand use of table relationships so we can do cascading deletes etc. (started below)
    use InvalidArgumentException;
    use PDO,Exception,DateTime;
    class Model extends BaseClass {
        static $allowedOperators = ['=','!=','>','<','>=','<=','LIKE','IS','IS NOT','IN'];
        static $reAscDesc = "/\s+(asc|desc)/i";

        public ?int $id = null;
        public ?string $created = null;
        public ?string $modified = null;
        
        static string $tableName;
        static $fields = ['id','created','modified'];

        // Relationships - note that class names must be namespaced, so should be in the form SomeClass::class
        static $hasOne = []; // Single child object of these types
        static $hasMany = []; // Multiple child objects of these types
        static $belongsTo = null; // Single parent object

        /** Contains a unique identifier for when a PlacedClue has not yet been saved - retrieved only by getUniqueId() */
        protected ?string $__uniqueID = null;
  
        /** 
         * Returns an id that can be compared to those of other objects of the same class
         * @return int|string the numeric id of the record in the database or a string that is unique to this object
         */
        public function getUniqueId() : int|string {
          if (isset($this->id) && $this->id !== null) {
            // If we have a database ID, return that
            return $this->id;
          } else {
            // Otherwise return a unique string ID
            if ($this->__uniqueID === null) {
              // If the string ID field is unset, assign it a value
              $this->__uniqueID = uniqid('nev',true);
            }
            // Return string
            return $this->__uniqueID;
          }
        }

        /** Retrieves the table name from static context
         * @return string the table name in static::$tableName
         */
        protected function getTableName() : string {
            if (isset(static::$tableName)) {
                return static::$tableName;
            } else {
                return '';
            }
        }
  
        /**
         * Checks if two variables are actually referencing the same object
         */
        public function is(Model $comparisonObj) : bool {
          return ($this->getTableName() == $comparisonObj->getTableName()) && ($this->getUniqueId() === $comparisonObj->getUniqueId());
        }

        /**
         * Turns an orderBy variable (in our proprietary format) into a valid ORDER BY string (with leading space)
         * @param string $fields either an array of field names, or an array of arrays in the form ['field', 'asc|desc']
         * @returns an ORDER BY statement with backticked fields and leading space, or a blank string if no fields supplied
         */
        static function parseOrderBy($fields) : string {
            if (is_array($fields)) {
                // More than one field
                if (count($fields) == 0) { return ''; }
                $parsedFields = [];
                foreach ($fields as $f) {
                    if (is_array($f)) {
                        // Sort direction supplied
                        if ((strtolower($f[1])=='asc')||(strtolower($f[1])=='desc')) {
                            $parsedFields[] = "`{$f[0]}` {$f[1]}";
                        } else {
                            // ... but not correctly
                            throw new \Exception("Field order must be ASC or DESC: '{$f[0]}' supplied.");
                        }
                    } else {
                        // No sort direction
                        $parsedFields[] = "`{$f}`";
                    }
                }
                return ' ORDER BY '.implode(',',$parsedFields).' ';
            } else {
                // Only one field (and no sort direction)
                if (empty($fields)) { return ''; }
                return "`{$fields}`";
            }
        }

        /**
         * Gets the default ORDER BY statement for the relevant class/subclass
         * child classes can supply public static $defaultOrderBy as either an array of field names, or an array of arrays in the form ['field', 'asc|desc']
         * @return string a statement beginning with a space and 'ORDER BY'
         */
        public static function getDefaultOrderBy() : string {
            $calledClass = get_called_class();
            $soughtProperty = 'defaultOrderBy';
            if(property_exists($calledClass,$soughtProperty)) {
                $fields = $calledClass::$$soughtProperty;
                return static::parseOrderBy($fields);
            }
            return ''; // No defaultOrderBy set
        }

        /**
         * Retrieves the created field as a nullable DateTime
         * @return ?DateTime the created date
         */
        public function getCreated():?DateTime {
            // TODO - HIGH - needs casting, surely?
            return $this->created;
        }
        /**
         * Retrieves the modified field as a nullable DateTime
         * @return ?DateTime the modified date
         */
        public function getModified():?DateTime {
            // TODO - HIGH - needs casting, surely?
            return $this->modified;
        }

        /**
         * Retrieves the number of entities in the database
         * @return int the number of entities
         */
        public static function count() : int {
            $pdo = db::getPDO();
            
            $sql = "SELECT COUNT(*) AS c FROM `".static::$tableName."`"
                    .static::getDefaultOrderBy();
            $query = $pdo->query($sql);

            $result = $query->fetchColumn();
            return $result;
        }

        /**
         * Retrieves all the entities from the database
         * @return mixed an array of the entities
         */
        public static function getAll() : array {
            $pdo = db::getPDO();
            
            $sql = "SELECT * FROM `".static::$tableName."`"
                    .static::getDefaultOrderBy();
            $query = $pdo->query($sql);

            $query->setFetchMode(PDO::FETCH_CLASS, static::class);
            $results = $query->fetchAll();
            return $results;
        }

        /**
         * Determines if an entity with the specified id exists
         * @param int $id the id to check
         * @return bool true if the entity exists, otherwise false
         */
        public static function checkForId(int $id) : bool {
            $pdo = db::getPDO();

            $sql = "SELECT COUNT(id) as c FROM `".static::$tableName."` WHERE id=:id";
            $params = [
                "id" => $id,
            ];
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result === false) { return false; }
            return ($result['c']>0);
        }

        /**
         * Retrieves the entity with the specified id
         * @param int $id the id of the entity
         * @return ?static the entity, or null if no matching entity is found
         */
        public static function getById(int $id) : ?static {
            $pdo = db::getPDO();

            $sql = "SELECT * FROM `".static::$tableName."` WHERE id=:id";
            $params = [
                "id" => $id,
            ];
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $stmt->setFetchMode(PDO::FETCH_CLASS,static::class);
            $result = $stmt->fetch();
            if ($result === false) { return null; }
            return $result;
        }


        /**
         * Finds the first record that matches the specified criteria
         * @param array each criterion should be an array in the form [field,operator,value], and multiple criteria should be specified as an array of arrays
         * @param string $orderBy either an array of field names OR an array of arrays in the form ['field', 'asc|desc'] OR null to use default ordering
         * @return ?static an object of the class or subclass calling the function, or null if no record is found
         */
        public static function findFirst($criteria, $orderBy = null) : ?static {
            $values = static::find($criteria, $orderBy, 1);
            if (count($values)==0) { return null; }
            return $values[0];
        }

        /**
         * Finds records matching the specified criteria
         * @param array each criterion should be an array in the form [field,operator,value], and multiple criteria should be specified as an array of arrays
         * @param string $orderBy either an array of field names OR an array of arrays in the form ['field', 'asc|desc'] OR null to use default ordering
         * @param int $limit the number of rows to retrieve
         * @param int $offset the number of rows to offset by
         * @param mixed $extras an associative array of other query tweaks - currently supported are useIndex and forceIndex
         * @return mixed an array of objects of the class or subclass calling the function - an empty array if there are no matches
         */
        public static function find($criteria, $orderBy = null, $limit = null, $offset = null, $extras = null) : array {
            $pdo = db::getPDO();

            // Check arguments
            if (!is_array($criteria)) {
                throw new Exception("Find method requires an array of three-element arrays to operate");
            }
            if ($extras == null) { $extras = []; }
            // Allow for people passing in a single criterion without the enclosing array
            if (count($criteria) == 3) {
                // To minimise potential for error, only do this where the intent is really clear:
                //  to qualify, all three elements must be non-array
                if(
                    !is_array($criteria[0]) &&
                    !is_array($criteria[1]) &&
                    !is_array($criteria[2])
                ) {
                    // Yep - that's what they've done. Enclose it as 0th element of a container array
                    $tmp_criteria = serialize($criteria);
                    $criteria = [
                        0 => unserialize($tmp_criteria),
                    ];
                }
            }
            // Now process the criteria
            $criteria_strings = [];
            $criteria_values = [];
            foreach ($criteria as $criterion) {
                if ( (!is_array($criterion)) || count($criterion)!=3) {
                    throw new Exception("Find method requires an array of three-element arrays to operate");
                }
                // Format - field, operator, value
                list($field,$operator,$value) = $criterion;
                
                if (strpos($field,'`')!==false) {
                    throw new Exception("Field names in criteria cannot contain backticks (`)");
                }
                if (!in_array(strtoupper($operator),Model::$allowedOperators)) {
                    throw new Exception("Operator {$operator} is not allowed");
                }

                // Need special treatment for IS NULL / IS NOT NULL / IN ()
                if ((strtoupper($operator) == 'IS') || (strtoupper($operator) == 'IS NOT')) {
                    if (is_null($value) || trim(strtoupper($value))=='NULL') {
                        $criteria_strings[] = "`{$field}` {$operator} NULL";
                    } else {
                        throw new Exception("Operator {$operator} can only take NULL as its argument (supplied as literal null or 'NULL')");
                    }
                } elseif (strtoupper($operator) == 'IN') {
                    $value_array = $value;
                    //error_log("Value: ".print_r($value,true));
                    if (!is_array($value)) { $value_array = [[0]=>$value]; }
                    //error_log("Value array: ".print_r($value_array,true));
                    if (count($value_array) == 0) {
                        $criteria_strings[] = "FALSE"; // IN () would throw an error
                    } else {
                        $qmarks = array_fill(0, count($value), '?');
                        $criteria_strings[] = "`{$field}` {$operator} (".implode(',',$qmarks).")";
                        $criteria_values = array_merge($criteria_values,$value_array);
                    }
                } else {
                    $criteria_strings[] = "`{$field}` {$operator} ?";
                    if ($value instanceof DateTime) { $value = $value->format('Y-m-d H:i:s'); } // Can't pass in as DateTime apparently
                    $criteria_values[] = $value;
                }
            }

            if ($orderBy === null) {
                $orderSql = static::getDefaultOrderBy();
            } else {
                $orderSql = static::parseOrderBy($orderBy);
            }

            if ($limit === null) {
                $limitSql = '';
            } else {
                if (!is_numeric($limit)) { throw new InvalidArgumentException("Invalid value for limit: {$limit}"); }
                $limitNum = intval($limit);
                if ($offset === null) {
                    $limitSql = ' LIMIT '.(string)$limitNum.' ';
                } else {
                    if (!is_numeric($offset)) { throw new InvalidArgumentException("Invalid value for offset: {$offset}"); }
                    $offsetNum = intval($offset);
                    $limitSql = ' LIMIT '.(string)$limitNum.' OFFSET '.(string)$offsetNum.' ';
                }
            }
            
            $tableExtra = '';
            if (array_key_exists('forceIndex',$extras)) {
                $idx = $extras['forceIndex'];
                if (strpos($idx,'`') !== false) { throw new InvalidArgumentException("Index name cannot contain backticks ($idx)"); }
                $tableExtra = " FORCE INDEX (`{$idx}`) ";
            } else if (array_key_exists('useIndex',$extras)) {
                $idx = $extras['useIndex'];
                if (strpos($idx,'`') !== false) { throw new InvalidArgumentException("Index name cannot contain backticks ($idx)"); }
                $tableExtra = " USE INDEX (`{$idx}`) ";
            }

            $sql = "SELECT * FROM `".static::$tableName."` {$tableExtra} "
                    ."WHERE ".implode(" AND ", $criteria_strings)
                    .$orderSql
                    .$limitSql;
            //error_log($sql);
            //error_log(print_r($criteria_values,true));
            $stmt = $pdo->prepare($sql);
            $stmt->execute($criteria_values);

            $stmt->setFetchMode(PDO::FETCH_CLASS, static::class);
            $results = $stmt->fetchAll();
            return $results;
        }

        /**
         * Saves the entity
         * @return ?int the id of the created entity
         */
        public function save() : ?int {
            $pdo = db::getPDO();

            // If id is set and record exists then update; otherwise, create new
            $criteria_strings = [];
            $criteria_values = [];
            $insert_placeholders = [];

            $is_insert = ( (empty($this->id) || !static::checkForId($this->id)) );

            // Loop through all properties
            foreach (static::$fields as $field) {
                $criteria_strings[] = "`{$field}` = ?";
                //echo "`{$field}` = ".$this->{$field}."\n";
                if ($field == 'created' && $is_insert) {
                    $criteria_values[] = date('Y-m-d H:i:s');
                } elseif ($field == 'modified') {
                    $criteria_values[] = date('Y-m-d H:i:s');
                } else {
                    $criteria_values[] = $this->{$field};
                }
                $insert_placeholders[] = '?';
            }

            if ($is_insert) {
                // Create record
                $sql = "INSERT INTO `".static::$tableName."` (`".implode('`,`',static::$fields)."`) VALUES (".implode(',',$insert_placeholders).")";
            } else {
                // Update record
                $sql = "UPDATE `".static::$tableName."` SET ".implode(',',$criteria_strings)." WHERE id=?";
                $criteria_values[] = $this->id;
            }
            //echo "{$sql}\n";
            //print_r($criteria_values);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($criteria_values);

            if ($is_insert) {
                $this->id = $pdo->lastInsertId();
            }
            return $this->id;
        }

        /**
         * Deletes the entity
         * @return bool whether the entity was successfully deleted
         */
        public function delete() : bool {
            $pdo = db::getPDO();

            $is_new = ( (empty($this->id) || !static::checkForId($this->id)) );

            if ($is_new) {
                throw new Exception("Could not delete: no matching record in database");
            }

            $sql = "DELETE FROM `".static::$tableName."` WHERE id=:id";
            $params = [
                "id" => $this->id,
            ];
            $stmt = $pdo->prepare($sql);
            return $stmt->execute($params); // Return success or failure of the delete
        }

        /**
         * Deletes an entity by id
         * @return bool whether the entity was successfully deleted
         */
        public static function deleteById($id) : bool {
            $pdo = db::getPDO();

            $unmatched = ( (empty($id) || !static::checkForId($id)) );

            if ($unmatched) {
                throw new Exception("Could not delete: no matching record in database");
            }

            $sql = "DELETE FROM `".static::$tableName."` WHERE id=:id";
            $params = [
                "id" => $id,
            ];
            $stmt = $pdo->prepare($sql);
            return $stmt->execute($params); // Return success or failure of the delete
        }

        /**
         * Clones the entity
         * @return static the cloned object
         */
        public function clone($preserveID = false, $preserveCreated = false, $preserveModified = false) {
            $clone = new static();

            $handleManually = ['id','created','modified'];
            // Copy all fields except those specified above
            foreach(static::$fields as $f) {
                if (!in_array($f, $handleManually)) {
                    $clone->$f = $this->$f;
                }
            }

            // Handle special fields according to arguments passed
            if ($preserveID) {
                $clone->id = $this->id;
            } else {
                $clone->id = null;
            }
            if ($preserveCreated) {
                $clone->created = $this->created;
            } else {
                $clone->created = (new DateTime())->format('Y-m-d H:i:s');
            }
            if ($preserveModified) {
                $clone->modified = $this->modified;
            } else {
                $clone->modified = (new DateTime())->format('Y-m-d H:i:s');
            }

            return $clone;
        }

        /**
         * Ensures that the specified field is set (often used before saving)
         * @param string $fieldName the name of the field to check
         * @param mixed $valueIfUnset the value to use if the field isn't set
         * @param mixed $unsetValue the field is considered unset if it fails isset() OR if it matches this value (default null)
         * @return static the original object, to allow for easy method chaining
         */
        public function ensureFieldSet(string $fieldName, mixed $valueIfUnset = '', mixed $unsetValue = null) : static {
            // Check that the property exists
            if (!property_exists($this, $fieldName)) { throw new InvalidArgumentException("No field {$fieldName} in class ".static::class); }
            if (!isset($this->$$fieldName)) {
                // If it's unset, set it to the specified value
                $this->$$fieldName = $valueIfUnset;
            } elseif ($this->$$fieldName == $unsetValue) {
                // If it's equal to the unsetValue, set it to the specified value
                $this->$$fieldName = $valueIfUnset;
            }
            // Return the original object
            return $this;
        }

        /**
         * Returns the parent's fully-qualified class name, loading the class if needed
         * @return ?string the parent class name, which should include any namespace prefix, or null if there is no parent class specified
         * @throws Will throw an exception if the parent class does not exist (autoloading if needed)
         */
        public function getParentClassName() : ?string {
            // Check if there's a parent class
            if (static::$belongsTo === null) { return null; }
            // Ensure the relevant class exists and is loaded
            if (!class_exists(static::$belongsTo)) { throw new Exception("Class ".static::$belongsTo." is specified as the parent of ".get_class($this). " but the class does not exist."); }
            return static::$belongsTo;
        }

        /**
         * Returns the fully-qualified class names of any child objects, loading the classes if needed
         * @return string[] the parent class name, which should include any namespace prefix, or null if there is no parent class specified
         * @throws Will throw an exception if the parent class does not exist (autoloading if needed)
         */
        public function getChildClassNames() : array {
            // Check if there are child classes
            $childClasses = array_merge(static::$hasOne,static::$hasMany);
            foreach ($childClasses as $childClass) {
                // Ensure the relevant class exists and is loaded
                if (!class_exists($childClass)) { throw new Exception("Class ".static::$belongsTo." is specified as a child of ".get_class($this). " but the class does not exist."); }
            }
            return $childClasses;
        }

        /**
         * Returns the parent object
         * @return ?Model the parent object, or null if there is none
         * @throws Will throw an exception if there is no parent relationship defined
         * @throws Will throw an exception (in getParentClassName function) if the parent class does not exist
         * @throws Will throw an exception if the parent class does not inherit from Model
         * @throws Will throw an exception if the current class does not contain a link field in the format parentclass_id
         */
        public function getParent() : ?Model {
            // Retrieve parent class name (and load class if needed)
            $parentClassName = $this->getParentClassName();

            // If there's no parent specified, throw an error
            if ($parentClassName === null) { throw new Exception("Class ".get_class($this). " does not have a defined parent relationship."); }

            // If the parent class doesn't inherit from Basic\Model, throw an error
            if (!is_subclass_of($parentClassName,Model::class,true)) { throw new Exception("Parent class ".$parentClassName." does not inherit from ".Model::class.". This function can only be called on subclasses of ".Model::class."."); }

            // Work out our link field ([parentclass]_id)
            $reflect = new \ReflectionClass($parentClassName);
            $parentShortName = $reflect->getShortName(); // Needed for link field name
            $linkField = strtolower($parentShortName).'_id';

            // Check we have the link field
            if(!property_exists(static::class, $linkField)) { throw new Exception("Class ".$parentClassName." is specified as the parent of ".get_class($this). " but there is no link field ".$linkField."."); }

            // Now do the actual lookup
            $parent = call_user_func($parentClassName.'::getById', $this->{$linkField});

            // Return the result
            return $parent;
        }

        /**
         * Returns child objects
         * @param ?string $type the type (fully-qualified, including namespace) of child to return
         * @return Model[] the parent object, or null if there is none
         * @throws Will throw an exception if there is no child relationship defined
         * @throws Will throw an exception (in getParentClassName function) if a child class does not exist
         * @throws Will throw an exception if a child class does not inherit from Model
         * @throws Will throw an exception if the child class does not contain a link field in the format thisclass_id
         */
        public function getChildren(?string $type = null) : array {
            // Retrieve child class names (and load classes if needed)
            $childClassNames = $this->getChildClassNames();

            // If there's no children specified, throw an error
            if (count($childClassNames) == 0) { throw new Exception("Class ".get_class($this). " does not have any defined child relationships."); }

            // Get own short name
            $classReflect = new \ReflectionClass(static::class);
            $classShortName = $classReflect->getShortName(); // Needed for link field name

            $children = [];
            /** @var ?string $returnKey */
            $returnKey = null;

            foreach ($childClassNames as $childClassName) {
                if (($type !== null) && ($type != $childClassName)) { continue; } // If $type specified, only return children of that type
                // If the child class doesn't inherit from Basic\Model, throw an error
                if (!is_subclass_of($childClassName,Model::class,true)) { throw new Exception("Child class ".$childClassName." does not inherit from ".Model::class.". This function can only be called on subclasses of ".Model::class."."); }
                // Work out our link field ([thisclass]_id)
                $linkField = strtolower($classShortName).'_id';
                // Check we have the link field
                if(!property_exists($childClassName, $linkField)) { throw new Exception("Class ".$childClassName." is specified as a child of ".get_class($this). " but there is no link field ".$linkField."."); }
                
                // Now do the actual lookup
                $childReflect = new \ReflectionClass($childClassName);
                $childShortName = $childReflect->getShortName(); // Needed for array index
                if ($type !== null) { $returnKey = $childShortName; } // Needed to return simplified array where $type specified
                $criteria = [$linkField,'=',$this->id]; // Specify criteria (linkfield of child object equals id of this object)
                $children[$childShortName] = call_user_func($childClassName.'::find', $criteria);
            }

            // Return the result
            if ($type===null || $returnKey===null) {
                return $children;
            } else {
                return $children[$returnKey];
            }
        }

        /**
         * Access the object and all its descendants in a JSON-encodable form
         * Unless overridden, this will simply map to calling exposeTree() on all children
         */
        public function exposeTree() : mixed {
            $childClassNames = $this->getChildClassNames();
            
            foreach ($childClassNames as $childClassName) {
                try {
                    $children = $this->getChildren($childClassName); // Need to check if this is namespaced or not
                    if (is_array($children) && count($children)>0) {
                        $shortClassName = end(explode('\\', $childClassName));
                        $this->{$shortClassName} = [];
                        foreach ($children as $child) {
                            $this->{$shortClassName}[] = $child->exposeTree();
                        }
                    }
                } catch (Exception $e) {
                    // No children - ignore
                }
            }
            
            return parent::exposeTree();
        }

        /**
         * Creates a new object from the specified associative array or object
         * @param mixed $data an associative array or object containing the relevant keys
         * @return static an object of the specified class
         */
        public static function hydrateNewFrom(mixed $data, bool $nullId = false) : static {
            $obj = new static();
            $obj->hydrateFrom($data, $nullId);
            return $obj;
        }
        /**
         * Populates the current object from the specified associative array or object
         * @param mixed $data an associative array or object containing the relevant keys
         */
        public function hydrateFrom(mixed $data, bool $nullId = false) : void
        {
            if (is_array($data)) {
                $iterable = $data;
            } elseif (is_object($data)) {
                // Cast to array so we can iterate over public properties too
                $iterable = get_object_vars($data);
            } else {
                throw new InvalidArgumentException(
                    __METHOD__ . " expects array or object, " . gettype($data) . " given."
                );
            }

            foreach ($iterable as $key => $value) {
                // Only assign if the property exists on this object
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
            if ($nullId) { $this->id = null; }
        }
    }
}