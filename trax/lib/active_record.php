<?
# $Id$
#
# Copyright (c) 2005 John Peterson
#
# Permission is hereby granted, free of charge, to any person obtaining
# a copy of this software and associated documentation files (the
# "Software"), to deal in the Software without restriction, including
# without limitation the rights to use, copy, modify, merge, publish,
# distribute, sublicense, and/or sell copies of the Software, and to
# permit persons to whom the Software is furnished to do so, subject to
# the following conditions:
#
# The above copyright notice and this permission notice shall be
# included in all copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
# EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
# MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
# NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
# LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
# OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
# WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

require_once('PEAR.php');
require_once('DB.php');
require_once('inflector.php');


if(isset($GLOBALS['DB_SETTINGS'][TRAX_MODE]['persistent'])) { 
    $GLOBALS['ACTIVE_RECORD_OPTIONS'] = $GLOBALS['DB_SETTINGS'][TRAX_MODE]['persistent'];
}

$GLOBALS['ACTIVE_RECORD_FETCHMODE'] = DB_FETCHMODE_ASSOC;

define("ACTIVE_RECORD_NODB",            -1);
define("ACTIVE_RECORD_CONNECT_ERR",     -2);
define("ACTIVE_RECORD_ERR",             -3);
define("ACTIVE_RECORD_QUERY_ERR",       -4);

class ActiveRecord extends DB {
    static private $db = null;              // Reference to current db
    static protected $inflector = null;     // object to do class inflection
    static public $table_info = null;    // info about each column in the table
    
    private $current_rs;                    // Reference to current record set

    protected $has_many = array();
    protected $has_one = array();
    protected $has_and_belongs_to_many = array();
    protected $belongs_to = array();
    protected $new_record = true;  // whether or not to create a new record or just update
    protected $auto_update_timestamps = array("updated_at","updated_on");
    protected $auto_create_timestamps = array("created_at","created_on");
    protected $aggregrations = array("count","sum","avg","max","min");
    protected $habtm_attributes = array();

    public $primary_keys = array("id");  // update where clause keys
    public $rows_per_page_default = 20;  // Pagination
    public $display = 10;
    public $errors = array();
    public $auto_timestamps = true; // whether or not to auto update created_at/on and updated_at/on fields
    public $auto_save_habtm = true; // auto insert / update has and belongs to many tables

    ########################################################################
    # __contruct()
    #  Use    : Class constructor... opens a database connection
    #  Params : $params     usually the HTTP REQUEST
    #  Returns: Nothing
    ########################################################################
    function __construct($params = null) {

        self::$inflector = new Inflector();

        if(is_array($params)) {
            $this->update_attributes($params);
        }

        // Open the database connection
        if ($this->isError($useResult = $this->useDB())) {
            echo "ActiveRecord Error:".$useResult->getMessage();
            exit;
        }

        $this->set_table_name_using_class_name();
        if($this->table_name) {
            $this->set_table_info($this->table_name);
        }
    }

    ########################################################################
    function __get($key) {
        if(array_key_exists($key, $this->has_many)) {
            $this->$key = $this->find_all_has_many($key, $this->has_many[$key]);
        } elseif(array_key_exists($key, $this->has_one)) {
            $this->$key = $this->find_one_has_one($key, $this->has_one[$key]);
        } elseif(array_key_exists($key, $this->has_and_belongs_to_many)) {
            $this->$key = $this->find_all_habtm($key);
        } elseif(array_key_exists($key, $this->belongs_to)) {
            $this->$key = $this->find_one_belongs_to($key, $this->belongs_to[$key]);
        }
        //echo "<pre>id: $this->id<br>getting: $key = ".$this->$key."<br></pre>";
        return $this->$key;
    }

    ########################################################################
    function __set($key, $value) {
        //echo "setting: $key = $value<br>";
        if($key == "table_name") {
            self::$table_info = $this->set_table_info($value);
        }
        $this->$key = $value;
    }

    ########################################################################
    function __call($method_name, $parameters) {
        if(method_exists($this,$method_name)) {
            // If the method exists, just call it
            return call_user_func(array($this,$method_name), $parameters);
        } else {
            // ... otherwise, check to see if the method call is one of our
            // special Trax methods ...
            // ... first check for method names that match any of our explicitly
            // declared associations for this model ( e.g. $has_many = array("movies" => null) ) ...
            if (array_key_exists($method_name, $this->has_many)) {
                return $this->find_all_has_many($method_name, $parameters);
            } elseif(array_key_exists($method_name, $this->has_one)) {
                return $this->find_one_has_one($method_name, $parameters);
            } elseif(array_key_exists($method_name, $this->has_and_belongs_to_many)) {
                return $this->find_all_habtm($method_name, $parameters);
            } elseif(array_key_exists($method_name, $this->belongs_to)) {
                return $this->find_one_belongs_to($method_name, $parameters);
            }
            // check for the [count,sum,avg,etc...]_all magic functions
            elseif(substr($method_name, -4) == "_all" && in_array(substr($method_name, 0, -4),$this->aggregrations)) {
                //echo "calling method: $method_name<br>";
                return $this->aggregrate_all($method_name, $parameters);
            }
            // ... and last, check for the find_all_by_* magic functions
            elseif(strlen($method_name) > 11 && substr($method_name, 0, 11) == "find_all_by") {
                //echo "calling method: $method_name<br>";
                return $this->find_all_by($method_name, $parameters);
            }
        }
    }

    // Returns a the name of the join table that would be used for the two
    // tables.  The join table name is decided from the alphabetical order
    // of the two tables.  e.g. "genres_movies" because "g" comes before "m"
    //
    // Parameters: $first_table, $second_table: the names of two database tables,
    //   e.g. "movies" and "genres"
    function get_join_table_name($first_table, $second_table) {
        $tables = array();
        $tables["one"] = $first_table;
        $tables["many"] = $second_table;
        @asort($tables);
        return @implode("_", $tables);
    }

    // Find all records using a "has_and_belongs_to_many" relationship
    // (many-to-many with a join table in between).  Note that you can also
    // specify an optional "paging limit" by setting the corresponding "limit"
    // instance variable.  For example, if you want to return 10 movies from the
    // 5th movie on, you could set $this->movies_limit = "10, 5"
    //
    // Parameters: $this_table_name:  The name of the database table that has the
    //                                one row you are interested in.  E.g. `genres`
    //             $other_table_name: The name of the database table that has the
    //                                many rows you are interested in.  E.g. `movies`
    // Returns: An array of ActiveRecord objects. (e.g. Movie objects)
    function find_all_habtm($other_table_name, $parameters = null) {

        $other_class_name = self::$inflector->classify($other_table_name);

        // Instantiate an object to access find_all
        $results = new $other_class_name();

        // Prepare the join table name primary keys (fields) to do the join on
        $join_table = $this->get_join_table_name($this->table_name, $other_table_name);
        $this_foreign_key = self::$inflector->singularize($this->table_name)."_id";
        $other_foreign_key = self::$inflector->singularize($other_table_name)."_id";
        // Set up the SQL segments
        $conditions = "`{$join_table}`.`{$this_foreign_key}`={$this->id}";
        $orderings = null;
        $limit = null;
        $joins = "LEFT JOIN `{$join_table}` ON `{$other_table_name}`.id = `{$other_foreign_key}`";

        // Use any passed-in parameters
        if (!is_null($parameters)) {
            if(@array_key_exists("conditions", $parameters))
                $additional_conditions = $parameters['conditions'];
            elseif($parameters[0] != "")
                $additional_conditions = $parameters[0];

            if(@array_key_exists("orderings", $parameters))
                $orderings = $parameters['orderings'];
            elseif($parameters[1] != "")
                $orderings = $parameters[1];

            if(@array_key_exists("limit", $parameters))
                $limit = $parameters['limit'];
            elseif($parameters[2] != "")
                $limit = $parameters[2];

            if(@array_key_exists("joins", $parameters))
                $additional_joins = $parameters['joins'];
            elseif($parameters[3] != "")
                $additional_joins = $parameters[3];

            if (!empty($additional_conditions))
                $conditions .= " AND (" . $additional_conditions . ")";
            if (!empty($additional_joins))
                $joins .= " " . $additional_joins;
        }

        // Get the list of other_class_name objects
        return $results->find_all($conditions, $orderings, $limit, $joins);
    }

    // Find all records using a "has_many" relationship (one-to-many)
    //
    // Parameters: $other_table_name: The name of the other table that contains
    //                                many rows relating to this object's id.
    // Returns: An array of ActiveRecord objects. (e.g. Contact objects)
    function find_all_has_many($other_table_name, $parameters = null) {
        // Prepare the class name and primary key, e.g. if
        // customers has_many contacts, then we'll need a Contact
        // object, and the customer_id field name.
        if(@array_key_exists("foreign_key", $parameters))
            $foreign_key = $parameters['foreign_key'];
        else
            $foreign_key = self::$inflector->singularize($this->table_name)."_id";

        $other_class_name = self::$inflector->classify($other_table_name);
        $conditions = "`{$foreign_key}`=$this->id";

        // Use any passed-in parameters
        if (!is_null($parameters)) {
            //echo "<pre>";print_r($parameters);
            if(@array_key_exists("conditions", $parameters))
                $additional_conditions = $parameters['conditions'];
            elseif($parameters[0] != "")
                $additional_conditions = $parameters[0];

            if(@array_key_exists("orderings", $parameters))
                $orderings = $parameters['orderings'];
            elseif($parameters[1] != "")
                $orderings = $parameters[1];

            if(@array_key_exists("limit", $parameters))
                $limit = $parameters['limit'];
            elseif($parameters[2] != "")
                $limit = $parameters[2];

            if(@array_key_exists("joins", $parameters))
                $additional_joins = $parameters['joins'];
            elseif($parameters[3] != "")
                $additional_joins = $parameters[3];

            if(!empty($additional_conditions))
                $conditions .= " AND (" . $additional_conditions . ")";
            if(!empty($additional_joins))
                $joins .= " " . $additional_joins;
        }

        // Instantiate an object to access find_all
        $other_class_object = new $other_class_name();
        // Get the list of other_class_name objects
        $results = $other_class_object->find_all($conditions, $orderings, $limit, $joins);

        return $results;
    }

    // Find all records using a "has_one" relationship (one-to-one)
    // (the foreign key being in the other table)
    // Parameters: $other_table_name: The name of the other table that contains
    //                                many rows relating to this object's id.
    // Returns: An array of ActiveRecord objects. (e.g. Contact objects)
    function find_one_has_one($other_object_name, $parameters = null) {

        // Prepare the class name and primary key, e.g. if
        // customers has_many contacts, then we'll need a Contact
        // object, and the customer_id field name.
        $other_class_name = self::$inflector->camelize($other_object_name);
        if(@array_key_exists("foreign_key", $parameters))
            $foreign_key = $parameters['foreign_key'];
        else
            $foreign_key = self::$inflector->singularize($this->table_name)."_id";

        $conditions = "`$foreign_key`='{$this->id}'";
        // Instantiate an object to access find_all
        $results = new $other_class_name();
        // Get the list of other_class_name objects
        $results = $results->find_first($conditions, $orderings);
        // There should only be one result, an object, if so return it
        if(is_object($results)) {
            return $results;
        } else {
            return null;
        }
    }

    // Find all records using a "belongs_to" relationship (one-to-one)
    // (the foreign key being in the table itself)
    // Parameters: $other_object_name: The singularized version of a table name.
    //                                 E.g. If the Contact class belongs_to the
    //                                 Customer class, then $other_object_name
    //                                 will be "customer".
    function find_one_belongs_to($other_object_name, $parameters = null) {

        // Prepare the class name and primary key, e.g. if
        // customers has_many contacts, then we'll need a Contact
        // object, and the customer_id field name.
        $other_class_name = self::$inflector->camelize($other_object_name);
        if(@array_key_exists("foreign_key", $parameters))
            $foreign_key = $parameters['foreign_key'];
        else
            $foreign_key = $other_object_name."_id";

        $conditions = "id='".$this->$foreign_key."'";
        // Instantiate an object to access find_all
        $results = new $other_class_name();
        // Get the list of other_class_name objects
        $results = $results->find_first($conditions, $orderings);
        // There should only be one result, an object, if so return it
        if(is_object($results)) {
            return $results;
        } else {
            return null;
        }
    }

    function aggregrate_all($aggregrate_type, $parameters = null) {

        $aggregrate_type = strtoupper(substr($aggregrate_type, 0, -4));
        ($parameters[0]) ? $field = $parameters[0] : $field = "*";
        $sql  = "SELECT $aggregrate_type($field) AS agg_result FROM `$this->table_name` ";

        // Use any passed-in parameters
        if (!is_null($parameters)) {
            $conditions = $parameters[1];
            $joins = $parameters[2];
        }

        if(!empty($joins)) $sql .= ",`$joins` ";
        if(!empty($conditions)) $sql .= "WHERE $conditions ";

        //echo "sql:$sql<br>";
        if($this->isError($rs = $this->query($sql))) {
            echo "ActiveRecord Error: ".$rs->getMessage()."<br>";
        } else {
            $row = $rs->fetchRow();
            return $row["agg_result"];
        }
        return 0;
    }

    function send($column) {
        // Run the query to grab a specific columns value.
        $result = self::$db->getOne("SELECT $column FROM `$this->table_name` WHERE id='$this->id'");

        if ($this->isError($result)) {
            echo ($this->raiseError($rs->getUserInfo(), 'query', __LINE__, ACTIVE_RECORD_ERR, PEAR_ERROR_RETURN));
            die;
        } else {
            return $result;
        }
    }

    ########################################################################
    # query()
    #  Use    : Used to run a sql statement when you don''t want the wrapper
    #                       to do any processing
    #  Params : $sql                SQL to run on the current database
    #  Returns: Nothing
    ########################################################################
    function query($sql) {
        if (!$this->_hasCurrentDB()) {
            return $this->raiseError('No database selected, run useDB first', 'query', __LINE__, ACTIVE_RECORD_NODB, PEAR_ERROR_RETURN);
        }

        // Run the query
        $rs = self::$db->query($sql);

        if ($this->isError($rs)) {
            echo "Error in ActiveRecord::query();<br>";
            $error = $this->raiseError($rs->getUserInfo(), 'query', __LINE__, ACTIVE_RECORD_ERR, PEAR_ERROR_RETURN);
            echo $error->message;
            die;
        } else {
            $this->_setCurrentRS($rs);
        }

        return $rs;
    }

    function find_all($conditions = null, $orderings = null, $limit = null, $joins = null) {
        $objects = array();

        if (is_array($limit)) {
            list($this->page, $this->rows_per_page) = $limit;
            if($this->page <= 0) $this->page = 1;
            // Default for rows_per_page:
            if ($this->rows_per_page == null) $this->rows_per_page = $this->rows_per_page_default;
            // Set the LIMIT string segment for the SQL in the find_all
            $this->offset = ($this->page - 1) * $this->rows_per_page;
            // mysql 3.23 doesn't support OFFSET
            //$limit = "$rows_per_page OFFSET $offset";
            $limit = "$this->offset, $this->rows_per_page";
            $set_pages = true;
        }

        if(stristr($conditions, "SELECT")) {
            $sql = $conditions;
        } else {
            $sql  = "SELECT * FROM `$this->table_name` ";
            if(!is_null($joins)) {
                if(substr($joins,0,4) != "LEFT") $sql .= ",";
                $sql .= " $joins ";
            }
            if(!is_null($conditions)) $sql .= "WHERE $conditions ";
            if(!is_null($orderings)) $sql .= "ORDER BY $orderings ";
            if(!is_null($limit)) {
                if($set_pages) {
                    //echo "ActiveRecord::find_all() - sql: $sql\n<br>";
                    if($this->isError($rs = $this->query($sql))) {
                        echo "ActiveRecord Error: ".$rs->getMessage()."<br>";
                    } else {
                        // Set number of total pages in result set without the LIMIT
                        if($count = $rs->numRows())
                            $this->pages = (($count % $this->rows_per_page) == 0) ? $count / $this->rows_per_page : floor($count / $this->rows_per_page) + 1;
                    }
                }
                $sql .= "LIMIT $limit";
            }
        }

        //echo "ActiveRecord::find_all() - sql: $sql\n<br>";
        if($this->isError($rs = $this->query($sql))) {
            echo "ActiveRecord Error: ".$rs->getMessage()."<br>";
        }

        while($row = $rs->fetchRow()) {
            $class = get_class($this);
            $obj = new $class();
            $obj->new_record = false;
            foreach($row as $field => $val) {
                $obj->$field = $val;
                if($field == "id") {
                    $objectsKey = $val;
                }
            }
            $objects[$objectsKey] = $obj;
            unset($obj);
            unset($objectsKey);
        }
        return $objects;
    }

    function find($id, $conditions = null) {
        if(is_array($id)) {
            $where = "id IN(".implode(",",$id).")";
        } else {
            $where = "id='$id'";
        }
        if($conditions) {
            $where .= " AND " . $conditions;
        }

        if(is_array($id)) {
            return $this->find_all($where);
        } else {
            return $this->find_first($where);
        }
    }

    function find_by_sql($sql) {
        $objects = $this->find_all($sql);
        if(count($objects) == 1)
            return @current($objects);
        else
            return $objects;
    }

    function find_first($conditions = null, $orderings = null) {
        $result = $this->find_all($conditions, $orderings);
        return @current($result);
    }

    function find_all_by($method_name, $parameters) {

        $method_parts = explode("_",substr($method_name, 12));
        if(is_array($methodParts)) {
            $param_cnt = 0;
            $part_cnt = 1;
            $and_cnt = substr_count(strtolower($methodName), "_and_");
            $or_cnt = substr_count(strtolower($methodName), "_or_");
            $part_size = count($method_parts) - $and_cnt - $or_cnt;
            foreach($method_parts as $part) {
                if(strtoupper($part) == "AND") {
                    $method_params .= implode("_",$field)."='".$parameters[$param_cnt++]."' AND ";
                    $partCnt--;
                    unset($field);
                } elseif(strtoupper($part) == "OR") {
                    $method_params .= implode("_",$field)."='".$parameters[$param_cnt++]."' OR ";
                    $part_cnt--;
                    unset($field);
                } else {
                    $field[] = $part;
                    if($part_size == $part_cnt) {
                        $method_params .= implode("_",$field)."='".$parameters[$param_cnt++]."'";
                        if($parameters[$param_cnt]) {
                            $orderings = $parameters[$param_cnt];
                        }
                    }
                }
                $part_cnt++;
            }

            return $this->find_all($method_params, $orderings);
        }
    }

    // Successively calls all functions that begin with "validate_" to
    // validate each field.  The "validate_*" functions should return an
    // array whose first element is true or false (indicating whether or
    // not the validation succeeded), and whose second element is the
    // error message to display on validation failure.
    //
    // Parameters: none
    //
    // Returns: true if all validations succeeded, false otherwise
    function validate() {
        $validated_ok = true;
        $attrs = $this->get_attributes();
        $methods = get_class_methods(get_class($this));
        foreach($methods as $method) {
            if (preg_match('/^validate_(.+)/', $method, $matches)) {
                // If we find, for example, a method named validate_name, then
                // we know that that function is validating the 'name' attribute
                // (as found in the (.+) part of the regular expression above).
                $validate_on_attribute = $matches[1];
                // Check to see if the string found (e.g. 'name') really is
                // in the list of attributes for this object...
                if (array_key_exists($validate_on_attribute, $attrs)) {
                    // ...if so, then call the method to see if it validates to true...
                    $result = $this->$method();
                    if (is_array($result)) {
                        // $result[0] is true if validation went ok, false otherwise
                        // $result[1] is the error message if validation failed
                        if ($result[0] == false) {
                            // ... and if not, then validation failed
                            $validated_ok = false;
                            // Mark the corresponding entry in the error array by
                            // putting the error message in for the attribute,
                            //   e.g. $this->errors['name'] = "can't be empty"
                            //   when 'name' was an empty string.
                            $this->errors[$validate_on_attribute] = $result[1];
                        }
                    } else {
                        if ($result == false) {
                            $validated_ok = false;
                            $this->errors[$validate_on_attribute] = "is invalid";
                        }
                    }
                } else {
                    // ... otherwise, this is a validate method that isn't associated
                    // with any of the attributes (db fields) in the class, so just
                    // call the method and make sure it returns true
                    $result = $this->$method();
                    if ($result == false) {
                        $validated_ok = false;
                    }
                }
            }
        }
        return $validated_ok;
    }

    function update_attributes($params) {
        foreach($params as $field => $val) {
            $this->$field = $val;
        }
        $this->set_habtm_attributes($params);
    }

    function create($params = null) {
        return $this->save($params);
    }
    
    function update($params) {
        return $this->save($params);
    }

    function save($params = null) {
        if(!is_null($params)) {
            $this->update_attributes($params);
        }
        if ($this->validate()) {
            return $this->add_record_or_update_record();
        } else {
            return false;
        }
    }

    function add_record_or_update_record() {
        //echo "new record: $this->new_record<br>";
        return ($this->new_record) ? $this->add_record() : $this->update_record();
    }

    function set_habtm_attributes($params) {
        if(is_array($params)) {
            unset($this->habtm_attributes);
            foreach($params as $key => $habtm_array) {
                if(is_array($habtm_array)) {
                    if(array_key_exists($key, $this->has_and_belongs_to_many)) {
                        $habtm_attributes[$key] = $habtm_array;
                    }
                }
            }
            $this->habtm_attributes = $habtm_attributes;
        }
    }

    function add_record() {

        $attributes = $this->quoted_attributes();
        $fields = @implode(', ', array_keys($attributes));
        $values = @implode(', ', array_values($attributes));
        $sql = "INSERT INTO $this->table_name ($fields) VALUES ($values)";
        //echo "add_record: SQL: $sql<br>";

        $result = $this->query($sql);
        if ($this->isError($result)) {
            return false;
        } else {
            $id = $this->getInsertId();
            if($id > 0 && $this->auto_save_habtm) {
                $habtm_result = $this->add_habtm_records($id);
            }

            return ($result && $habtm_result);
        }
    }

    function add_habtm_records($this_foreign_value) {
        $failed = false;
        if($this_foreign_value > 0 && count($this->habtm_attributes) > 0) {
            if($this->delete_habtm_records($this_foreign_value)) {
                reset($this->habtm_attributes);
                foreach($this->habtm_attributes as $other_table_name => $other_foreign_values) {
                    $table_name = $this->get_join_table_name($this->table_name,$other_table_name);
                    $other_foreign_key = self::$inflector->singularize($other_table_name)."_id";
                    $this_foreign_key = self::$inflector->singularize($this->table_name)."_id";
                    foreach($other_foreign_values as $other_foreign_value) {
                        unset($attributes);
                        $attributes[$this_foreign_key] = $this_foreign_value;
                        $attributes[$other_foreign_key] = $other_foreign_value;
                        $attributes = $this->quoted_attributes($attributes);
                        $fields = @implode(', ', array_keys($attributes));
                        $values = @implode(', ', array_values($attributes));
                        $sql = "INSERT INTO $table_name ($fields) VALUES ($values)";
                        //echo "add_habtm_records: SQL: $sql<br>";
                        $result = $this->query($sql);
                        if ($this->isError($result)) {
                            $failed = true;
                        }
                    }
                }
            } else {
                $failed = true;
            }
        }

        if($failed) {
            return false;
        } else {
            return true;
        }
    }

    function update_record() {

        $attributes = $this->quoted_attributes();
        // run through our fields and join them with their values
        foreach ($attributes as $key => $value) {
            if(in_array($key,$this->primary_keys)) {
                $where[] = "$key = $value";
                $id = str_replace("'","",$value);
            } else {
                $update[] = "$key = $value";
            }
        }

        $update = @implode(', ', $update);
        $where = @implode(' AND ', $where);
        $sql = "UPDATE $this->table_name SET $update WHERE $where";
        //echo "update_record: SQL: $sql<br>";
        $result = $this->query($sql);
        if($this->isError($result)) {
            return false;
        } else {
            if($id > 0 && $this->auto_save_habtm) {
                $habtm_result = $this->update_habtm_records($id);
            }
            return ($result && $habtm_result);
        }
    }

    function update_habtm_records($this_foreign_value) {
        return $this->add_habtm_records($this_foreign_value);
    }

    function delete($conditions = null) {
        // Check for valid ids
        if(count($this->primary_keys) <= 0 && is_null($conditions))
            return false;

        if(is_null($conditions)) {
            $conditions = array();
            $attributes = $this->quoted_attributes();
            // run through our fields and join them with their values
            foreach ($attributes as $key => $value) {
                if(in_array($key,$this->primary_keys)) {
                    $conditions[] = "$key = $value";
                } 
            }            
            $conditions = implode(" AND ", $conditions);
        } elseif($this->id > 0) {
            $conditions = "id='$this->id'";
        } else {
            return false;
        }

        // Delete their info record
        if($this->isError($this->query("DELETE FROM $this->table_name WHERE $conditions"))) {
            return false;
        }

        $this->id = 0;
        $this->new_record = true;
        return true;
    }

    function delete_habtm_records($this_foreign_value) {
        $failed = false;
        if($this_foreign_value > 0 && count($this->habtm_attributes) > 0) {
            reset($this->habtm_attributes);
            foreach($this->habtm_attributes as $other_table_name => $values) {
                $table_name = $this->get_join_table_name($this->table_name,$other_table_name);
                $this_foreign_key = self::$inflector->singularize($this->table_name)."_id";
                $sql = "DELETE FROM $table_name WHERE $this_foreign_key = $this_foreign_value";
                //echo "delete_habtm_records: SQL: $sql<br>";

                $result = $this->query($sql);
                if ($this->isError($result)) {
                    $failed = true;
                }
            }
        }

        if($failed) {
            return false;
        } else {
            return true;
        }
    }

    function check_datetime($field, $value) {
        if($this->auto_timestamps) {
            if(is_array(self::$table_info)) {
                foreach(self::$table_info as $field_info) {
                    if(($field_info['name'] == $field) && ($field_info['type'] == "datetime")) {
                        if($this->new_record && in_array($field, $this->auto_create_timestamps))
                            return date("Y-m-d H:i:s");
                        elseif(!$this->new_record && in_array($field, $this->auto_update_timestamps))
                            return date("Y-m-d H:i:s");
                    }
                }
            }
        }
        return $value;
    }

    function get_attributes() {
        $attributes = array();
        if(is_array(self::$table_info)) {
            foreach(self::$table_info as $info) {
                //echo "attribute: $info[name] -> {$this->$info[name]}<br>";
                $attributes[$info['name']] = $this->$info['name'];
            }
        }
        return $attributes;
    }

    function quoted_attributes($array = null) {
        if(is_null($array)) {
            $array = $this->get_attributes();
        }
        foreach ($array as $key => $value) {
            $value = $this->check_datetime($key, $value);
            // If the value isn't a function or null quote it...
            if (!(preg_match('/^\w+\(.*\)$/U', $value)) && !(strcasecmp($value, 'NULL') == 0)) {
                $value = str_replace("\\\"","\"",$value);
                $value = str_replace("\'","'",$value);
                $value = str_replace("\\\\","\\",$value);
                $array[$key] = "'" . addslashes($value) . "'";
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    function set_table_name_using_class_name() {
        if(!isset($this->table_name)) {
            $this->table_name = self::$inflector->tableize(get_class($this));
        }
    }

    function set_table_info($table_name) {
        self::$table_info = self::$db->tableInfo($table_name);
        if(is_array(self::$table_info)) {
            $i = 0;
            foreach(self::$table_info as $info) {
                self::$table_info[$i]['human_name'] = self::$inflector->humanize($info['name']);
                ++$i;
            }
        }
    }

    // The following function overrides simply call their corresponding functions for the currend db we're using.
    // They dynamically pass all arguments given to them on to the real function etc... If pear DB::Common or DB::Result change
    // We need only add or remove functions from here...
    /*
        * DB_common -- Interface for database access
        * DB_common::affectedRows() -- Finds the number of affected rows
        * DB_common::autoExecute() -- Automatically performs insert or update queries
        * DB_common::autoPrepare() -- Automatically prepare an insert or update query
        * DB_common::createSequence() -- Create a new sequence
        * DB_common::disconnect() -- Disconnect from a database
        * DB_common::dropSequence() -- Deletes a sequence
        * DB_common::escapeSimple() -- Escape a string according to the current DBMS's standards
        * DB_common::execute() -- Executes a prepared SQL statment
        * DB_common::executeMultiple() -- Repeated execution of a prepared SQL statment
        * DB_common::freePrepared() -- Release resources associated with a prepared SQL statement
        * DB_common::getAll() -- Fetch all rows
        * DB_common::getAssoc() -- Fetch result set as associative array
        * DB_common::getCol() -- Fetch a single column
        * DB_common::getListOf() -- View database system information
        * DB_common::getOne() -- Fetch the first column of the first row
        * DB_common::getRow() -- Fetch the first row
        * DB_common::limitQuery() -- Send a limited query to the database
        * DB_common::nextId() -- Returns the next free id of a sequence
        * DB_common::prepare() -- Prepares a SQL statement
        * DB_common::provides() -- Checks if a DBMS supports a particular feature
        * DB_common::query() -- Send a query to the database
        * DB_common::quote() -- DEPRECATED: Quotes a string
        * DB_common::quoteIdentifier() -- Format string so it can be safely used as an identifier
        * DB_common::quoteSmart() -- Format input so it can be safely used as a literal
        * DB_common::setFetchMode() -- Sets the default fetch mode
        * DB_common::setOption() -- Set run-time configuration options for PEAR DB
        * DB_common::tableInfo() -- Get info about columns in a table or a query result
        */
    function affectedRows()
    { if (!$this->_hasCurrentDB()) return false; $args = func_get_args(); return(call_user_func_array(array(self::$db, 'affectedRows'), $args)); }
    function autoExecute()
    { if (!$this->_hasCurrentDB()) return false; $args = func_get_args(); return(call_user_func_array(array(self::$db, 'autoExecute'), $args)); }
    function autoPrepare()
    { if (!$this->_hasCurrentDB()) return false; $args = func_get_args(); return(call_user_func_array(array(self::$db, 'autoPrepare'), $args)); }
    function createSequence()
    { if (!$this->_hasCurrentDB()) return false; $args = func_get_args(); return(call_user_func_array(array(self::$db, 'createSequence'), $args)); }
    function disconnect()
    { if (!$this->_hasCurrentDB()) return false; $args = func_get_args(); return(call_user_func_array(array(self::$db, 'disconnect'), $args)); }
    function dropSequence()
    { if (!$this->_hasCurrentDB()) return false; $args = func_get_args(); return(call_user_func_array(array(self::$db, 'dropSequence'), $args)); }
    function escapeSimple()
    { if (!$this->_hasCurrentDB()) return false; $args = func_get_args(); return(call_user_func_array(array(self::$db, 'escapeSimple'), $args)); }
    function execute() {
        if (!$this->_hasCurrentDB()) return false;
        $args = func_get_args();
        $rs = call_user_func_array(array(self::$db, 'execute'), $args);
        if($this->isError($rs))
            return($this->raiseError($obj->getUserInfo(), 'execute', __LINE__, ACTIVE_RECORD_QUERY_ERR, PEAR_ERROR_RETURN));
        else
            $this->_setCurrentRS($rs);
        return($rs);
    }
    function executeMultiple() {
        if (!$this->_hasCurrentDB()) return false;
        $args = func_get_args();
        $rs = call_user_func_array(array(self::$db, 'executeMultiple'), $args);
        if($this->isError($rs))
            return($this->raiseError($obj->getUserInfo(), 'executeMultiple', __LINE__, ACTIVE_RECORD_CONNECT_ERR, PEAR_ERROR_RETURN));
        else
            $this->_setCurrentRS($rs);
        return($rs);
    }
    function freePrepared()
    { if (!$this->_hasCurrentDB()) return false; $args = func_get_args(); return(call_user_func_array(array(self::$db, 'freePrepared'), $args)); }
    function getAll()
    { if (!$this->_hasCurrentDB()) return false; $args = func_get_args(); return(call_user_func_array(array(self::$db, 'getAll'), $args)); }
    function getAssoc()
    { if (!$this->_hasCurrentDB()) return false; $args = func_get_args(); return(call_user_func_array(array(self::$db, 'getAssoc'), $args)); }
    function getCol()
    { if (!$this->_hasCurrentDB()) return false; $args = func_get_args(); return(call_user_func_array(array(self::$db, 'getCol'), $args)); }
    function getListOf()
    { if (!$this->_hasCurrentDB()) return false; $args = func_get_args(); return(call_user_func_array(array(self::$db, 'getListOf'), $args)); }
    function getOne()
    { if (!$this->_hasCurrentDB()) return false; $args = func_get_args(); return(call_user_func_array(array(self::$db, 'getOne'), $args)); }
    function getRow()
    { if (!$this->_hasCurrentDB()) return false; $args = func_get_args(); return(call_user_func_array(array(self::$db, 'getRow'), $args)); }
    function limitQuery()
    { if (!$this->_hasCurrentDB()) return false; $args = func_get_args(); return(call_user_func_array(array(self::$db, 'limitQuery'), $args)); }
    function nextId()
    { if (!$this->_hasCurrentDB()) return false; $args = func_get_args(); return(call_user_func_array(array(self::$db, 'nextId'), $args)); }
    function prepare()
    { if (!$this->_hasCurrentDB()) return false; $args = func_get_args(); return(call_user_func_array(array(self::$db, 'prepare'), $args)); }
    function provides()
    { if (!$this->_hasCurrentDB()) return false; $args = func_get_args(); return(call_user_func_array(array(self::$db, 'provides'), $args)); }
    function quote()
    { if (!$this->_hasCurrentDB()) return false; $args = func_get_args(); return(call_user_func_array(array(self::$db, 'quote'), $args)); }
    function quoteIdentifier()
    { if (!$this->_hasCurrentDB()) return false; $args = func_get_args(); return(call_user_func_array(array(self::$db, 'quoteIdentifier'), $args)); }
    function quoteSmart()
    { if (!$this->_hasCurrentDB()) return false; $args = func_get_args(); return(call_user_func_array(array(self::$db, 'quoteSmart'), $args)); }
    function setFetchMode()
    { if (!$this->_hasCurrentDB()) return false; $args = func_get_args(); return(call_user_func_array(array(self::$db, 'setFetchMode'), $args)); }
    function setOption()
    { if (!$this->_hasCurrentDB()) return false; $args = func_get_args(); return(call_user_func_array(array(self::$db, 'setOption'), $args)); }
    function tableInfo()
    { if (!$this->_hasCurrentDB()) return false; $args = func_get_args(); return(call_user_func_array(array(self::$db, 'tableInfo'), $args)); }
    /*
        * DB_result -- DB result set
        * DB_result::fetchInto() -- Fetch a row into a variable
        * DB_result::fetchRow() -- Fetch a row
        * DB_result::free() -- Release a result set
        * DB_result::nextResult() -- Get result sets from multiple queries
        * DB_result::numCols() -- Get number of columns
        * DB_result::numRows() -- Get number of rows
        */
    function fetchInto()
    { if (!$this->_hasCurrentRS()) return false; $args = func_get_args(); return(call_user_func_array(array($this->current_rs, 'fetchInto'), $args)); }
    function fetchRow()
    { if (!$this->_hasCurrentRS()) return false; $args = func_get_args(); return(call_user_func_array(array($this->current_rs, 'fetchRow'), $args)); }
    function free()
    { if (!$this->_hasCurrentRS()) return false; $args = func_get_args(); return(call_user_func_array(array($this->current_rs, 'free'), $args)); }
    function nextResult()
    { if (!$this->_hasCurrentRS()) return false; $args = func_get_args(); return(call_user_func_array(array($this->current_rs, 'nextResult'), $args)); }
    function numCols()
    { if (!$this->_hasCurrentRS()) return false; $args = func_get_args(); return(call_user_func_array(array($this->current_rs, 'numCols'), $args)); }
    function numRows()
    { if (!$this->_hasCurrentRS()) return false; $args = func_get_args(); return(call_user_func_array(array($this->current_rs, 'numRows'), $args)); }

    ########################################################################
    # getInsertId()
    #  Use    : Returns the autogenerated id from the last insert query
    #  Params : None
    #  Returns: id          integer id
    ########################################################################
    function getInsertId() {
        $id = $this->getOne("SELECT LAST_INSERT_ID();");

        if ($this->isError($id))
            return($this->raiseError($id->getUserInfo(), 'getInsertId', __LINE__, ACTIVE_RECORD_ERR, PEAR_ERROR_RETURN));

        return $id;
    }

    ########################################################################
    # useDB()
    #  Use    : Calls DB::Connect to open a database connection. It uses
    #                       our global ACTIVE_RECORD_DEFAULTDB if no db argument is given. If it
    #                       finds connection information in ACTIVE_RECORD_DATABASES it uses it
    #                       otherwise it returns an error
    #  Returns: PEAR::db object
    ########################################################################
    function useDB() {

        // Connect to the database and throw an error if the connect fails...
        if(!is_object($GLOBALS['ACTIVE_RECORD_DB'])) {
            $GLOBALS['ACTIVE_RECORD_DB'] =& DB::Connect($GLOBALS['DB_SETTINGS'][TRAX_MODE], $GLOBALS['ACTIVE_RECORD_OPTIONS']);   
        } 
        
        if(!$this->isError($GLOBALS['ACTIVE_RECORD_DB'])) {
            self::$db = $GLOBALS['ACTIVE_RECORD_DB'];
        } else {
            return($this->raiseError($GLOBALS['ACTIVE_RECORD_DB']->getUserInfo(), 'useDB', __LINE__, ACTIVE_RECORD_CONNECT_ERR, PEAR_ERROR_RETURN));
        }

        self::$db->setFetchMode($GLOBALS['ACTIVE_RECORD_FETCHMODE']);

        return self::$db;
    }

    ########################################################################
    # _setCurrentRS()
    #  Use    : Sets the current recordset pointer
    #  Params : $rs                 reference to the recordset to make current
    #  Returns: Nothing
    ########################################################################
    function _setCurrentRS(&$rs) {
        $this->current_rs = $rs;
    }

    ########################################################################
    # raiseError()
    #  Use    : Sends an error string to PEAR::raiseError
    #  Params : $message            error message to send
    #                       $method                 function error occurred in
    #                       $line                   line error occurred on (use __LINE__)
    #                       $errno                  internal error number
    #                       $do                             pear error action (die print return etc...)
    #  Returns: Nothing
    ########################################################################
    function &raiseError($message, $method, $line, $errno, $do) {
        $error = PEAR::raiseError(
                                    sprintf("Error: %s on line %d of %s::%s()",
                                            $message, $line, /*get_class($this)*/'ActiveRecord', $method), $errno, $do);
        return($error);
    }

    ########################################################################
    # isError()
    #  Use    : Tests to see if an object is either a pear error or a db error
    #                       object...
    #  Params : $obj                        database to open
    #  Returns: Nothing
    ########################################################################
    function isError($obj) {
        return((PEAR::isError($obj)) || (DB::isError($obj)));
    }

    function _hasCurrentDB() {
        if (is_object(self::$db))
            return true;
        else
            return false;
    }

    function _hasCurrentRS() {
        if (is_object($this->current_rs))
            return true;
        else
            return false;
    }

    function limit_select($controller =null, $additional_query = null) {
        if($this->pages > 0) {
            $html = "
                <select name=\"per_page\" onChange=\"document.location = '?$this->paging_extra_params&per_page=' + this.options[this.selectedIndex].value;\">
                    <option value=\"$this->rows_per_page\" selected>per page:</option>
                    <option value=10>10</option>
                    <option value=20>20</option>
                    <option value=50>50</option>
                    <option value=100>100</option>
                    <option value=999999999>ALL</option>
                </select>
            ";
        }
        return $html;
    }

    function page_list(){
        $page_list  = "";

        /* Print the first and previous page links if necessary */
        if(($this->page != 1) && ($this->page))
            $page_list .= "<a href=\"?$this->paging_extra_params&page=1&per_page=$this->rows_per_page\" class=\"page_list\" title=\"First Page\"><<</a> ";

        if(($this->page-1) > 0)
            $page_list .= "<a href=\"?$this->paging_extra_params&page=".($this->page-1)."&per_page=$this->rows_per_page\" class=\"page_list\" title=\"Previous Page\"><</a> ";

        if($this->pages < $this->display)
            $this->display = $this->pages;

        if($this->page == $this->pages) {
            if($this->pages - $this->display == 0)
                $start = 1;
            else
                $start = $this->pages - $this->display;
            $max = $this->pages;
        } else {
            if($this->page >= $this->display) {
                $start = $this->page - ($this->display / 2);
                $max   = $this->page + (($this->display / 2)-1);
            } else {
                $start = 1;
                $max   = $this->display;
            }
        }

        if($max >= $this->pages)
            $max = $this->pages;

        /* Print the numeric page list; make the current page unlinked and bold */
        if($max != 1) {
            for ($i=$start; $i<=$max; $i++) {
                if ($i == $this->page)
                    $page_list .= "<span class=\"pageList\"><b>".$i."</b></span>";
                else
                    $page_list .= "<a href=\"?$this->paging_extra_params&page=$i&per_page=$this->rows_per_page\" class=\"page_list\" title=\"Page ".$i."\">".$i."</a>";

                $page_list .= " ";
            }
        }

        /* Print the Next and Last page links if necessary */
        if(($this->page+1) <= $this->pages)
            $page_list .= "<a href=\"?$this->paging_extra_params&page=".($this->page+1)."&per_page=$this->rows_per_page\" class=\"page_list\" title=\"Next Page\">></a> ";

        if(($this->page != $this->pages) && ($this->pages != 0))
            $page_list .= "<a href=\"?$this->paging_extra_params&page=".$this->pages."&per_page=$this->rows_per_page\" class=\"page_list\" title=\"Last Page\">>></a> ";

        $page_list .= "\n";

        //error_log("Page list=[$page_list]");
        return $page_list;
    }

}

?>