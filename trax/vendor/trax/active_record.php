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

class ActiveRecord {

    static private $db = null;              # Reference to Pear db object
    static protected $inflector = null;     # object to do class inflection
    public $table_info = null;              # info about each column in the table
    public $table_name = null;
    public $fetch_mode = DB_FETCHMODE_ASSOC;

    # Table associations
    protected $has_many = array();
    protected $has_one = array();
    protected $has_and_belongs_to_many = array();
    protected $belongs_to = array();
    protected $habtm_attributes = array();

    protected $new_record = true;  # whether or not to create a new record or just update
    protected $auto_update_timestamps = array("updated_at","updated_on");
    protected $auto_create_timestamps = array("created_at","created_on");
    protected $aggregrations = array("count","sum","avg","max","min");

    public $primary_keys = array("id");  # update / delete where clause keys
    public $rows_per_page_default = 20;  # Pagination rows to display per page
    public $display = 10; # Pagination how many numbers in the list << < 1 2 3 4 > >>
    public $errors = array(); 
    public $auto_timestamps = true; # whether or not to auto update created_at/on and updated_at/on fields
    public $auto_save_habtm = true; # auto insert / update $has_and_belongs_to_many tables

    # Transactions (only use if your db supports it)
    private $begin_executed = false; # this is for transactions only to let query() know that a 'BEGIN' has been executed
    public $use_transactions = false; # this will issue a rollback command if any sql fails

    # Constructor sets up need parameters for AR to function properly
    function __construct($attributes = null) {

        # Define static members
        if (self::$inflector == null) {
            self::$inflector = new Inflector();
        }

        # Open the database connection
        $this->establish_connection();

        # Set $table_name
        if($this->table_name == null) { 
            $this->set_table_name_using_class_name();
        }

        # Set column info
        if($this->table_name) {
            $this->set_table_info($this->table_name);
        }

        # If $attributes array is passed in update the class with its contents
        if(is_array($attributes)) {
            $this->update_attributes($attributes);
        }
    }

    # Override get() if they do $model->some_association->field_name dynamically load the requested
    # contents from the database.
    function __get($key) {

        if(is_string($this->has_many)) {
            if(preg_match("/$key/", $this->has_many)) {
                $this->$key = $this->find_all_has_many($key);
            }
        } elseif(is_array($this->has_many)) {
            if(array_key_exists($key, $this->has_many)) {
                $this->$key = $this->find_all_has_many($key, $this->has_many[$key]);
            }
        } elseif(is_string($this->has_one)) {
            if(preg_match("/$key/", $this->has_one)) {
                $this->$key = $this->find_one_has_one($key);
            }
        } elseif(is_array($this->has_one)) {
            if(array_key_exists($key, $this->has_one)) {
                $this->$key = $this->find_one_has_one($key, $this->has_one[$key]);
            }
        } elseif(is_string($this->belongs_to)) {
            if(preg_match("/$key/", $this->belongs_to)) {
                $this->$key = $this->find_one_belongs_to($key);
            }
        } elseif(is_array($this->belongs_to)) {
            if(array_key_exists($key, $this->belongs_to)) {
                $this->$key = $this->find_one_belongs_to($key, $this->belongs_to[$key]);
            }
        } elseif(is_string($this->has_and_belongs_to_many)) {
            if(preg_match("/$key/", $this->has_and_belongs_to_many)) {
                $this->$key = $this->find_all_habtm($key);
            }
        } elseif(is_array($this->has_and_belongs_to_many)) {
            if(array_key_exists($key, $this->has_and_belongs_to_many)) {
                $this->$key = $this->find_all_habtm($key);
            }
        }

        //echo "<pre>id: $this->id<br>getting: $key = ".$this->$key."<br></pre>";
        return $this->$key;
    }

    # Override set() if they set certain class variables do some action
    function __set($key, $value) {
        //echo "setting: $key = $value<br>";
        if($key == "table_name") {
            $this->set_table_info($value);
        }
        $this->$key = $value;
    }

    # Override call() to dynamically call the database associations
    function __call($method_name, $parameters) {
        if(method_exists($this,$method_name)) {
            # If the method exists, just call it
            $result = call_user_func(array($this,$method_name), $parameters);
        } else {
            # ... otherwise, check to see if the method call is one of our
            # special Trax methods ...
            # ... first check for method names that match any of our explicitly
            # declared associations for this model ( e.g. $this->has_many = array("movies" => null) ) ...
            if(is_string($this->has_many)) {
                if(preg_match("/$method_name/", $this->has_many)) {
                    $result = $this->find_all_has_many($method_name, $parameters);
                }
            } elseif(is_array($this->has_many)) {
                if(array_key_exists($method_name, $this->has_many)) {
                    $result = $this->find_all_has_many($method_name, $parameters);
                }
            } elseif(is_string($this->has_one)) {
                if(preg_match("/$method_name/", $this->has_one)) {
                    $result = $this->find_one_has_one($method_name, $parameters);
                }
            } elseif(is_array($this->has_one)) {
                if(array_key_exists($method_name, $this->has_one)) {
                    $result = $this->find_one_has_one($method_name, $parameters);
                }
            } elseif(is_string($this->belongs_to)) {
                if(preg_match("/$method_name/", $this->belongs_to)) {
                    $result = $this->find_one_belongs_to($method_name, $parameters);
                }
            } elseif(is_array($this->belongs_to)) {
                if(array_key_exists($method_name, $this->belongs_to)) {
                    $result = $this->find_one_belongs_to($method_name, $parameters);
                }
            } elseif(is_string($this->has_and_belongs_to_many)) {
                if(preg_match("/$method_name/", $this->has_and_belongs_to_many)) {
                    $result = $this->find_all_habtm($method_name, $parameters);
                }
            } elseif(is_array($this->has_and_belongs_to_many)) {
                if(array_key_exists($method_name, $this->has_and_belongs_to_many)) {
                    $result = $this->find_all_habtm($method_name, $parameters);
                }
            }
            # check for the [count,sum,avg,etc...]_all magic functions
            elseif(substr($method_name, -4) == "_all" && in_array(substr($method_name, 0, -4),$this->aggregrations)) {
                //echo "calling method: $method_name<br>";
                $result = $this->aggregrate_all($method_name, $parameters);
            }
            # check for the find_all_by_* magic functions
            elseif(strlen($method_name) > 11 && substr($method_name, 0, 11) == "find_all_by") {
                //echo "calling method: $method_name<br>";
                $result = $this->find_by($method_name, $parameters, true);
            }
            # check for the find_by_* magic functions
            elseif(strlen($method_name) > 7 && substr($method_name, 0, 7) == "find_by") {
                //echo "calling method: $method_name<br>";
                $result = $this->find_by($method_name, $parameters);
            }
        }
        return $result;
    }

    # Returns a the name of the join table that would be used for the two
    # tables.  The join table name is decided from the alphabetical order
    # of the two tables.  e.g. "genres_movies" because "g" comes before "m"
    #
    # Parameters: $first_table, $second_table: the names of two database tables,
    #   e.g. "movies" and "genres"
    function get_join_table_name($first_table, $second_table) {
        $tables = array();
        $tables["one"] = $first_table;
        $tables["many"] = $second_table;
        @asort($tables);
        return @implode("_", $tables);
    }

    # Find all records using a "has_and_belongs_to_many" relationship
    # (many-to-many with a join table in between).  Note that you can also
    # specify an optional "paging limit" by setting the corresponding "limit"
    # instance variable.  For example, if you want to return 10 movies from the
    # 5th movie on, you could set $this->movies_limit = "10, 5"
    #
    # Parameters: $this_table_name:  The name of the database table that has the
    #                                one row you are interested in.  E.g. `genres`
    #             $other_table_name: The name of the database table that has the
    #                                many rows you are interested in.  E.g. `movies`
    # Returns: An array of ActiveRecord objects. (e.g. Movie objects)
    function find_all_habtm($other_table_name, $parameters = null) {
        $other_class_name = self::$inflector->classify($other_table_name);
        # Instantiate an object to access find_all
        $results = new $other_class_name();

        # Prepare the join table name primary keys (fields) to do the join on
        $join_table = $this->get_join_table_name($this->table_name, $other_table_name);
        $this_foreign_key = self::$inflector->singularize($this->table_name)."_id";
        $other_foreign_key = self::$inflector->singularize($other_table_name)."_id";
        # Set up the SQL segments
        $conditions = "`{$join_table}`.`{$this_foreign_key}`={$this->id}";
        $orderings = null;
        $limit = null;
        $joins = "LEFT JOIN `{$join_table}` ON `{$other_table_name}`.id = `{$other_foreign_key}`";

        # Use any passed-in parameters
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

        # Get the list of other_class_name objects
        return $results->find_all($conditions, $orderings, $limit, $joins);
    }

    # Find all records using a "has_many" relationship (one-to-many)
    #
    # Parameters: $other_table_name: The name of the other table that contains
    #                                many rows relating to this object's id.
    # Returns: An array of ActiveRecord objects. (e.g. Contact objects)
    function find_all_has_many($other_table_name, $parameters = null) {
        # Prepare the class name and primary key, e.g. if
        # customers has_many contacts, then we'll need a Contact
        # object, and the customer_id field name.
        if(@array_key_exists("foreign_key", $parameters))
            $foreign_key = $parameters['foreign_key'];
        else
            $foreign_key = self::$inflector->singularize($this->table_name)."_id";

        $other_class_name = self::$inflector->classify($other_table_name);
        $conditions = "`{$foreign_key}`=$this->id";

        # Use any passed-in parameters
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

        # Instantiate an object to access find_all
        $other_class_object = new $other_class_name();
        # Get the list of other_class_name objects
        $results = $other_class_object->find_all($conditions, $orderings, $limit, $joins);

        return $results;
    }

    # Find all records using a "has_one" relationship (one-to-one)
    # (the foreign key being in the other table)
    # Parameters: $other_table_name: The name of the other table that contains
    #                                many rows relating to this object's id.
    # Returns: An array of ActiveRecord objects. (e.g. Contact objects)
    function find_one_has_one($other_object_name, $parameters = null) {
        # Prepare the class name and primary key, e.g. if
        # customers has_many contacts, then we'll need a Contact
        # object, and the customer_id field name.
        $other_class_name = self::$inflector->camelize($other_object_name);
        if(@array_key_exists("foreign_key", $parameters))
            $foreign_key = $parameters['foreign_key'];
        else
            $foreign_key = self::$inflector->singularize($this->table_name)."_id";

        $conditions = "`$foreign_key`='{$this->id}'";
        # Instantiate an object to access find_all
        $results = new $other_class_name();
        # Get the list of other_class_name objects
        $results = $results->find_first($conditions, $orderings);
        # There should only be one result, an object, if so return it
        if(is_object($results)) {
            return $results;
        } else {
            return null;
        }
    }

    # Find all records using a "belongs_to" relationship (one-to-one)
    # (the foreign key being in the table itself)
    # Parameters: $other_object_name: The singularized version of a table name.
    #                                 E.g. If the Contact class belongs_to the
    #                                 Customer class, then $other_object_name
    #                                 will be "customer".
    function find_one_belongs_to($other_object_name, $parameters = null) {
        # Prepare the class name and primary key, e.g. if
        # customers has_many contacts, then we'll need a Contact
        # object, and the customer_id field name.
        $other_class_name = self::$inflector->camelize($other_object_name);
        if(@array_key_exists("foreign_key", $parameters))
            $foreign_key = $parameters['foreign_key'];
        else
            $foreign_key = $other_object_name."_id";

        $conditions = "id='".$this->$foreign_key."'";
        # Instantiate an object to access find_all
        $results = new $other_class_name();
        # Get the list of other_class_name objects
        $results = $results->find_first($conditions, $orderings);
        # There should only be one result, an object, if so return it
        if(is_object($results)) {
            return $results;
        } else {
            return null;
        }
    }

    # Used to run all the the *_all() aggregrate functions such as count_all() sum_all()
    # Return the result of the aggregration or 0.
    function aggregrate_all($aggregrate_type, $parameters = null) {
        $aggregrate_type = strtoupper(substr($aggregrate_type, 0, -4));
        ($parameters[0]) ? $field = $parameters[0] : $field = "*";
        $sql = "SELECT $aggregrate_type($field) AS agg_result FROM `$this->table_name` ";

        # Use any passed-in parameters
        if (!is_null($parameters)) {
            $conditions = $parameters[1];
            $joins = $parameters[2];
        }

        if(!empty($joins)) $sql .= ",`$joins` ";
        if(!empty($conditions)) $sql .= "WHERE $conditions ";

        //echo "sql:$sql<br>";
        if($this->is_error($rs = $this->query($sql))) {
            $this->raise($rs->getMessage());
        } else {
            $row = $rs->fetchRow();
            return $row["agg_result"];
        }
        return 0;
    }

    # Returns PEAR result set of one record with only the passed in column in the result set.
    function send($column) {
        if($column != "") {
            # Run the query to grab a specific columns value.
            $result = self::$db->getOne("SELECT `$column` FROM `$this->table_name` WHERE id='$this->id'");
            if($this->is_error($result)) {
                $this->raise($result->getMessage());
            }
        }
        return $result;
    }

    # Only used if you want to do transactions and your db supports transactions
    function begin() {
        self::$db->query("BEGIN");
        $this->begin_executed = true;
    }

    # Only used if you want to do transactions and your db supports transactions
    function commit() {
        self::$db->query("COMMIT"); 
        $this->begin_executed = false;
    }

    # Only used if you want to do transactions and your db supports transactions
    function rollback() {
        self::$db->query("ROLLBACK");
    }

    # Uses PEAR::DB's query to run the query and returns the DB result set
    function query($sql) {
        # Run the query
        $rs = self::$db->query($sql);
        if ($this->is_error($rs)) {
            if($this->use_transactions && $this->begin_executed) {
                $this->rollback();
            }
            $this->raise($rs->getMessage());
        }
        return $rs;
    }

    # This will return all the records matched by the options used. 
    # If no records are found, an empty array is returned.
    function find_all($conditions = null, $orderings = null, $limit = null, $joins = null) {
        if (is_array($limit)) {
            list($this->page, $this->rows_per_page) = $limit;
            if($this->page <= 0) $this->page = 1;
            # Default for rows_per_page:
            if ($this->rows_per_page == null) $this->rows_per_page = $this->rows_per_page_default;
            # Set the LIMIT string segment for the SQL in the find_all
            $this->offset = ($this->page - 1) * $this->rows_per_page;
            # mysql 3.23 doesn't support OFFSET
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
                    if($this->is_error($rs = $this->query($sql))) {
                        $this->raise($rs->getMessage());
                    } else {
                        # Set number of total pages in result set without the LIMIT
                        if($count = $rs->numRows())
                            $this->pages = (($count % $this->rows_per_page) == 0) ? $count / $this->rows_per_page : floor($count / $this->rows_per_page) + 1;
                    }
                }
                $sql .= "LIMIT $limit";
            }
        }

        //echo "ActiveRecord::find_all() - sql: $sql\n<br>";
        if($this->is_error($rs = $this->query($sql))) {
            $this->raise($rs->getMessage());
        }

        $objects = array();
        while($row = $rs->fetchRow()) {
            $class = get_class($this);
            $object = new $class();
            $object->new_record = false;
            foreach($row as $field => $val) {
                $object->$field = $val;
                if($field == "id") {
                    $objects_key = $val;
                }
            }
            $objects[$objects_key] = $object;
            unset($object);
            unset($objects_key);
        }
        return $objects;
    }

    # This can either be a specific id (1), or an array of ids (array(5, 6, 10)). 
    # If no record can be found for Returns an object if id isn't an array otherwise 
    # returns an array of objects.
    function find($id, $orderings = null, $limit = null, $joins = null) {
        if(is_array($id)) {
            $conditions = "id IN(".implode(",",$id).")";
        } elseif(stristr($id,"=")) { # has an = so must be a where clause
            $conditions = $id;
        } else {
            $conditions = "id='$id'";
        }

        if(is_array($id)) {
            return $this->find_all($conditions, $orderings, $limit, $joins);
        } else {
            return $this->find_first($conditions, $orderings, $limit, $joins);
        }
    }

    # This will return the first record matched by the options used. 
    # These options can either be specific conditions or merely an order. 
    # If no record can matched, false is returned.
    function find_first($conditions, $orderings = null, $limit = null, $joins = null) {
        $result = $this->find_all($conditions, $orderings, $limit, $joins);
        return @current($result);
    }

    # Works like find_all(), but requires a complete SQL string.
    function find_by_sql($sql) {
        return $this->find_all($sql);
    }

    # *Magical* function that is dynamically built according to you.
    # Works like find_all() or find().  
    # Example:
    #   $im_an_object = $model->find_by_fname("John");
    #   $im_an_array_of_objects = $model->find_all_by_fname_and_state("John","UT"); 
    function find_by($method_name, $parameters, $find_all = false) {
        $method_parts = explode("_",substr($method_name, 12));
        if(is_array($method_parts)) {
            $param_cnt = 0;
            $part_cnt = 1;
            $and_cnt = substr_count(strtolower($method_name), "_and_");
            $or_cnt = substr_count(strtolower($method_name), "_or_");
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

            if($find_all) {
            	return $this->find_all($method_params, $orderings);
            } else {
                return $this->find_first($method_params, $orderings);
            }
        }
    }

    # Reloads the attributes of this object from the database.
    function reload($conditions = null) {
        if(is_null($conditions)) {
            $conditions = $this->get_primary_key_conditions();
        }
        $object = $this->find($conditions);
        if(is_object($object)) {
            foreach($object as $key => $value) {
                $this->$key = $value;
            }
        }
    }

    # Creates an object, instantly saves it as a record (if the validation permits it).
    # If the save fails under validations it returns false and $errors array gets set.
    function create($attributes, $dont_validate = false) {
        if(is_array($attributes)) {
            foreach($attributes as $attr) {
                $this->create($attr, $dont_validate);
            }
        } else {
            $class = get_class($this);
            $object = new $class();
            $object->save($attributes, $dont_validate);
        }
    }

    # Finds the record from the passed id, instantly saves it with the passed attributes 
    # (if the validation permits it). Returns true on success and false on error.
    function update($id, $attributes, $dont_validate = false) {
        if(is_array($id)) {
            foreach($id as $update_id) {
                $this->update($update_id, $attributes[$update_id], $dont_validate);
            }
        } else {
            $object = $this->find($id);
            return $object->save($attributes, $dont_validate);
        }
    }

    # Updates all records with the SET-part of an SQL update statement in updates and 
    # returns an integer with the number of rows updates. A subset of the records can 
    # be selected by specifying conditions. 
    # Example:
    #   $model->update_all("category = 'cooldude', approved = 1", "author = 'John'");
    function update_all($updates, $conditions = null) {
        $sql = "UPDATE `$this->table_name` SET $updates WHERE $conditions";
        $result = $this->query($sql);
        if ($this->is_error($result)) {
            $this->raise($result->getMessage());
        } else {
            return true;
        }
    }

    # Save without valdiating anything.
    function save_without_validation($attributes = null) {
        return $this->save($attributes, true);
    }

    # $attributes is an array passed in from the html form usually. Where key is the column name
    # and value is the new value to INSERT or UPDATE in the database.  
    function save($attributes = null, $dont_validate = false) {
        if(!is_null($attributes)) {
            $this->update_attributes($attributes);
        }
        if ($dont_validate || $this->valid()) {
            return $this->add_record_or_update_record();
        } else {
            return false;
        }
    }

    # Just determines if this save should be an INSERT or an UPDATE
    function add_record_or_update_record() { 
        $this->before_save();
        if($this->new_record) {
            $this->before_create();
            $result = $this->add_record();   
            $this->after_create(); 
        } else {
            $this->before_update();
            $result = $this->update_record();
            $this->after_update();
        }
        $this->after_save();
        return $result;
    }

    # Add a record in the table represented by this model
    function add_record() {
        $this->before_create();
        $attributes = $this->quoted_attributes();
        $fields = @implode(', ', array_keys($attributes));
        $values = @implode(', ', array_values($attributes));
        $sql = "INSERT INTO `$this->table_name` ($fields) VALUES ($values)";
        //echo "add_record: SQL: $sql<br>";

        $result = $this->query($sql);
        if ($this->is_error($result)) {
            $this->raise($results->getMessage());
        } else {
            $id = $this->get_insert_id();
            if($id > 0 && $this->auto_save_habtm) {
                $habtm_result = $this->add_habtm_records($id);
            }

            return ($result && $habtm_result);
        }
    }

    # Updates a record in the table represented by this model
    function update_record() {
        $updates = $this->get_updates_sql();
        $conditions = $this->get_primary_key_conditions();
        $sql = "UPDATE `$this->table_name` SET $updates WHERE $conditions";
        //echo "update_record: SQL: $sql<br>";
        $result = $this->query($sql);
        if($this->is_error($result)) {
            $this->raise($results->getMessage());
        } else {
            if($this->id > 0 && $this->auto_save_habtm) {
                $habtm_result = $this->update_habtm_records($this->id);
            }
            return ($result && $habtm_result);
        }
    }

    # Deletes the record with the given $id or if you have done a
    # $model = $model->find($id), then $model->delete() it will delete
    # the record it just loaded from the find() without passing anything
    # to delete(). If an array of ids is provided, all ids in array are deleted.
    function delete($id = null) {
        if($this->id > 0 && is_null($id)) {
            $id = $this->id;
        }

        if(is_null($id)) {
            $this->errors[] = "No id specified to delete on.";
            return false;
        }

        $this->before_delete();
        $result = $this->delete_all("id IN ($id)");
        $this->after_delete();

        return $result;
    }

    # Deletes all the records that matches the $conditions
    # Example:
    # $model->delete_all("person_id = 2 AND (category = 'Toasters' OR category = 'Microwaves')");
    function delete_all($conditions = null) {
        if(is_null($conditions)) {
            $this->errors[] = "No conditions specified to delete on.";
            return false;
        }

        # Delete the record(s)
        if($this->is_error($rs = $this->query("DELETE FROM `$this->table_name` WHERE $conditions"))) {
            $this->raise($rs->getMessage());
        }

        $this->id = 0;
        $this->new_record = true;
        return true;
    }

    function set_habtm_attributes($attributes) {
        if(is_array($attributes)) {
            $this->habtm_attributes = array();
            foreach($attributes as $key => $habtm_array) {
                if(is_array($habtm_array)) {
                    if(array_key_exists($key, $this->has_and_belongs_to_many)) {
                        $this->habtm_attributes[$key] = $habtm_array;
                    }
                }
            }
        }
    }

    function update_habtm_records($this_foreign_value) {
        return $this->add_habtm_records($this_foreign_value);
    }

    function add_habtm_records($this_foreign_value) {
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
                        if ($this->is_error($result)) {
                            $this->raise($result->getMessage());
                        }
                    }
                }
            }
        }
        return true;
    }

    function delete_habtm_records($this_foreign_value) {
        if($this_foreign_value > 0 && count($this->habtm_attributes) > 0) {
            reset($this->habtm_attributes);
            foreach($this->habtm_attributes as $other_table_name => $values) {
                $table_name = $this->get_join_table_name($this->table_name,$other_table_name);
                $this_foreign_key = self::$inflector->singularize($this->table_name)."_id";
                $sql = "DELETE FROM $table_name WHERE $this_foreign_key = $this_foreign_value";
                //echo "delete_habtm_records: SQL: $sql<br>";
                $result = $this->query($sql);
                if ($this->is_error($result)) {
                    $this->raise($result->getMessage());
                }
            }
        }
        return true;
    }

    # Checks to see if $auto_timestamps is true, If yes and there exists a field
    # with a name in matching a name in the auto_create_timestamps or auto_update_timestamps arrays
    # then it will return a valid datetime format to insert/update into the database.
    # This is only called from quoted_attributes().
    function check_datetime($field, $value) {
        if($this->auto_timestamps) {
            if(is_array($this->table_info)) {
                foreach($this->table_info as $field_info) {
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

    # Updates all the attributes(class vars representing the table columns)
    # from the passed array $attributes.
    function update_attributes($attributes) {
        foreach($attributes as $field => $val) {
            $this->$field = $val;
        }
        $this->set_habtm_attributes($attributes);
    }

    # If $this->set_table_info() was previously called, which will mean that 
    # $table_info will be an array of containing the column info about the database
    # table this model is representing.  This will return an array where the keys
    # the column names and the values are the values from those columns.  
    function get_attributes() {
        $attributes = array();
        if(is_array($this->table_info)) {
            foreach($this->table_info as $info) {
                //echo "attribute: $info[name] -> {$this->$info[name]}<br>";
                $attributes[$info['name']] = $this->$info['name'];
            }
        }
        return $attributes;
    }

    # Returns an array of all the table columns with the key being the
    # the database column name and the value being the database column
    # value.  The value will be single quoted if appropriate.
    function quoted_attributes($attributes = null) {
        if(is_null($attributes)) {
            $attributes = $this->get_attributes();
        }
        $return = array();
        foreach ($attributes as $key => $value) {
            $value = $this->check_datetime($key, $value);
            # If the value isn't a function or null quote it.
            if (!(preg_match('/^\w+\(.*\)$/U', $value)) && !(strcasecmp($value, 'NULL') == 0)) {
                $value = str_replace("\\\"","\"",$value);
                $value = str_replace("\'","'",$value);
                $value = str_replace("\\\\","\\",$value);
                $return[$key] = "'" . addslashes($value) . "'";
            } else {
                $return[$key] = $value;
            }
        }
        return $return;
    }

    # Returns an a string in the format to put into a WHERE clause for updating
    # or deleting records.  It builds the clause from the $primary_keys array.
    # Example:
    #   $primary_keys = array("id", "ssn"); would be turned into the string
    #   "id = '5' AND ssn = '555-55-5555'"
    function get_primary_key_conditions() {
        $conditions = null;
        $attributes = $this->quoted_attributes();
        if(count($attributes) > 0) {
            $conditions = array();
            # run through our fields and join them with their values
            foreach($attributes as $key => $value) {
                if(in_array($key,$this->primary_keys)) {
                    $conditions[] = "$key = $value";
                }
            }
            $conditions = implode(" AND ", $conditions);
        }
        return $conditions;
    }

    # Returns an a string in the format to put into the SET-part of an SQL update statement
    # Should return a string formated for the UPDATE. 
    # Example:
    #   "id = '5', ssn = '555-55-5555'"
    function get_updates_sql() {
        $updates = null;
        $attributes = $this->quoted_attributes();
        if(count($attributes) > 0) {
            $updates = array();
            # run through our fields and join them with their values
            foreach($attributes as $key => $value) {
                if($key && $value) {
                    $updates[] = "$key = $value";
                }
            }
            $updates = implode(", ", $updates);
        }
        return $updates;
    }

    # Sets the $table_name varible from the class name of the child object (the Model)
    # used in all queries throughout ActiveRecord
    function set_table_name_using_class_name() {
        if(!isset($this->table_name)) {
            $this->table_name = self::$inflector->tableize(get_class($this));
        }
    }

    # Populates the model object with information about the table it represents
    function set_table_info($table_name) {
        $this->table_info = self::$db->tableInfo($table_name);
        if(is_array($this->table_info)) {
            $i = 0;
            foreach($this->table_info as $info) {
                $this->table_info[$i++]['human_name'] = self::$inflector->humanize($info['name']);
            }
        }
    }

    # Returns the autogenerated id from the last insert query
    function get_insert_id() {
        $id = self::$db->getOne("SELECT LAST_INSERT_ID();");
        if ($this->is_error($id)) {
            $this->raise($id->getMessage());
        }
        return $id;
    }

    # Calls DB::Connect() to open a database connection. It uses $GLOBALS['TRAX_DB_SETTINGS'][TRAX_MODE] 
    # If it finds a connection in ACTIVE_RECORD_DB it uses it.
    function establish_connection() {
        # Set optional Pear parameters
        if(isset($GLOBALS['TRAX_DB_SETTINGS'][TRAX_MODE]['persistent'])) { 
            $GLOBALS['ACTIVE_RECORD_OPTIONS'] = $GLOBALS['TRAX_DB_SETTINGS'][TRAX_MODE]['persistent'];
        }
        # Connect to the database and throw an error if the connect fails.
        if(!is_object($GLOBALS['ACTIVE_RECORD_DB'])) {
            if(array_key_exists("use", $GLOBALS['TRAX_DB_SETTINGS'][TRAX_MODE])) {
                $connection_settings = $GLOBALS['TRAX_DB_SETTINGS'][$GLOBALS['TRAX_DB_SETTINGS'][TRAX_MODE]['use']];
            } else {
                $connection_settings = $GLOBALS['TRAX_DB_SETTINGS'][TRAX_MODE];
            }
            $GLOBALS['ACTIVE_RECORD_DB'] =& DB::Connect($connection_settings, $GLOBALS['ACTIVE_RECORD_OPTIONS']);
        }
        if(!$this->is_error($GLOBALS['ACTIVE_RECORD_DB'])) {
            self::$db = $GLOBALS['ACTIVE_RECORD_DB'];
        } else {
            $this->raise($GLOBALS['ACTIVE_RECORD_DB']->getMessage());
        }
        self::$db->setFetchMode($this->fetch_mode);
        return self::$db;
    }

    # Tests to see if an object is either a PEAR Error or a DB Error object.
    function is_error($obj) {
        if((PEAR::isError($obj)) || (DB::isError($obj))) {
            return true;
        } else {
            return false;
        }
    }

    function raise($message) {
        $error_message  = "Model Class: ".get_class($this)."<br>";
        $error_message .= "Error Message: ".$message;
        throw new ActiveRecordError($error_message, "ActiveRecord Error", "500");        
    }

    # Add an error to Active Record
    function add_error($error, $key = null) {
        if(!is_null($key)) 
            $this->errors[$key] = $error;
        else
            $this->errors[] = $error;
    }

    # Return the errors array or if the first param is true then
    # returns it as a string seperated by the second param.
    function get_errors($return_string = false, $seperator = "<br>") {
        if($return_string && count($this->errors) > 0) {
            return implode($seperator, $this->errors);
        } else {
            return $this->errors;
        }
    }

    # Return errors as a string.
    function get_errors_as_string($seperator = "<br>") {
        return $this->get_errors(true, $seperator);
    }

    # Runs validate and validate_on_create or validate_on_update 
    # and returns true if no errors were added otherwise false.
    function valid() {
        # first clear the errors array
        $this->errors = array();

        if($this->new_record) {
            $this->before_validation();
            $this->before_validation_on_create();
            $this->validate();
            $this->validate_model_attributes();
            $this->after_validation();
            $this->validate_on_create(); 
            $this->after_validation_on_create();
        } else {
            $this->before_validation();
            $this->before_validation_on_update();
            $this->validate();
            $this->validate_model_attributes();
            $this->after_validation();
            $this->validate_on_update();
            $this->after_validation_on_update();
        }

        return count($this->errors) ? false : true;
    }

    # Successively calls all functions that begin with "validate_" to
    # validate each field.  The "validate_*" functions should return an
    # array whose first element is true or false (indicating whether or
    # not the validation succeeded), and whose second element is the
    # error message to display on validation failure.
    #
    # Parameters: none
    #
    # Returns: true if all validations succeeded, false otherwise
    function validate_model_attributes() {
        $validated_ok = true;
        $attrs = $this->get_attributes();
        $methods = get_class_methods(get_class($this));
        foreach($methods as $method) {
            if(preg_match('/^validate_(.+)/', $method, $matches)) {
                # If we find, for example, a method named validate_name, then
                # we know that that function is validating the 'name' attribute
                # (as found in the (.+) part of the regular expression above).
                $validate_on_attribute = $matches[1];
                # Check to see if the string found (e.g. 'name') really is
                # in the list of attributes for this object...
                if(array_key_exists($validate_on_attribute, $attrs)) {
                    # ...if so, then call the method to see if it validates to true...
                    $result = $this->$method();
                    if(is_array($result)) {
                        # $result[0] is true if validation went ok, false otherwise
                        # $result[1] is the error message if validation failed
                        if($result[0] == false) {
                            # ... and if not, then validation failed
                            $validated_ok = false;
                            # Mark the corresponding entry in the error array by
                            # putting the error message in for the attribute,
                            #   e.g. $this->errors['name'] = "can't be empty"
                            #   when 'name' was an empty string.
                            $this->errors[$validate_on_attribute] = $result[1];
                        }
                    }
                }
            }
        }
        return $validated_ok;
    }

    # Overwrite this method for validation checks on all saves and
    # use $this->errors[] = "My error message."; or
    # for invalid attributes $this->errors['attribute'] = "Attribute is invalid.";
    function validate() {}

    # Override this method for validation checks used only on creation.
    function validate_on_create() {}

    # Override this method for validation checks used only on updates.
    function validate_on_update() {}

    # Is called before validate().
    function before_validation() {}

    # Is called after validate().
    function after_validation() {}

    # Is called before validate() on new objects that haven't been saved yet (no record exists).
    function before_validation_on_create() {}

    # Is called after validate() on new objects that haven't been saved yet (no record exists).
    function after_validation_on_create()  {}

    # Is called before validate() on existing objects that has a record.
    function before_validation_on_update() {}

    # Is called after validate() on existing objects that has a record.
    function after_validation_on_update()  {}

    # Is called before save() (regardless of whether its a create or update save).
    function before_save() {}

    # Is called after save (regardless of whether its a create or update save).
    function after_save() {}

    # Is called before save() on new objects that havent been saved yet (no record exists).
    function before_create() {}

    # Is called after save() on new objects that havent been saved yet (no record exists).
    function after_create() {}

    # Is called before save() on existing objects that has a record.
    function before_update() {}

    # Is called after save() on existing objects that has a record.
    function after_update() {}

    # Is called before delete().
    function before_delete() {}

    # Is called after delete().
    function after_delete() {}

    #########################################################################
    # Paging html functions

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