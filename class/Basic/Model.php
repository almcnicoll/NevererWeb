<?php
namespace Basic {
    use PDO,Exception,DateTime;
    class Model extends BaseClass {
        static $allowedOperators = ['=','!=','>','<','>=','<=','LIKE','IS','IS NOT','IN'];
        static $reAscDesc = "/\s+(asc|desc)/i";

        public ?int $id = null;
        public ?string $created = null;
        public ?string $modified = null;
        
        static string $tableName;
        static $fields = ['id','created','modified'];

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
            return $this->created;
        }
        /**
         * Retrieves the modified field as a nullable DateTime
         * @return ?DateTime the modified date
         */
        public function getModified():?DateTime {
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
            if ($result === false) { return null; }
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
         * @return an object of the class or subclass calling the function, or null if no record is found
         */
        public static function findFirst($criteria, $orderBy = null) : ?static {
            // TODO - would be better (more efficient in db) to run query with LIMIT 1 instead of this
            $values = static::find($criteria, $orderBy);
            if (count($values)==0) { return null; }
            return $values[0];
        }

        /**
         * Finds records matching the specified criteria
         * @param array each criterion should be an array in the form [field,operator,value], and multiple criteria should be specified as an array of arrays
         * @param string $orderBy either an array of field names OR an array of arrays in the form ['field', 'asc|desc'] OR null to use default ordering
         * @return mixed an array of objects of the class or subclass calling the function - an empty array if there are no matches
         */
        public static function find($criteria, $orderBy = null) : array {
            $pdo = db::getPDO();

            // Check arguments
            if (!is_array($criteria)) {
                throw new Exception("Find method requires an array of three-element arrays to operate");
            }
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
                    $criteria_values[] = $value;
                }
            }

            if ($orderBy === null) {
                $orderSql = static::getDefaultOrderBy();
            } else {
                $orderSql = static::parseOrderBy($orderBy);
            }
            
            $sql = "SELECT * FROM `".static::$tableName."` "
                    ."WHERE ".implode(" AND ", $criteria_strings)
                    .$orderSql;
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

            // TODO - pay attention to related tables

            $sql = "DELETE FROM `".static::$tableName."` WHERE id=:id";
            $params = [
                "id" => $this->id,
            ];
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            // TODO - pay attention to results
            return true;
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
            $stmt->execute($params);
            
            // TODO - pay attention to results
            return true;
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
    }
}