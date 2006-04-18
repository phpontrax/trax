<?php
/**
 *  File containing the ActiveRecord class
 *
 *  (PHP 5)
 *
 *  @package PHPonTrax
 *  @version $Id$
 *  @copyright (c) 2005 John Peterson
 *
 *  Permission is hereby granted, free of charge, to any person obtaining
 *  a copy of this software and associated documentation files (the
 *  "Software"), to deal in the Software without restriction, including
 *  without limitation the rights to use, copy, modify, merge, publish,
 *  distribute, sublicense, and/or sell copies of the Software, and to
 *  permit persons to whom the Software is furnished to do so, subject to
 *  the following conditions:
 *
 *  The above copyright notice and this permission notice shall be
 *  included in all copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 *  EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 *  MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 *  NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 *  LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 *  OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 *  WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 *  Load the {@link http://pear.php.net/manual/en/package.pear.php PEAR base class}
 */
require_once('PEAR.php');

/**
 *  Load the {@link http://pear.php.net/manual/en/package.database.db.php PEAR DB package}
 */
require_once('DB.php');

/**
 *  Base class for the ActiveRecord design pattern
 *
 *  <p>Each subclass of this class is associated with a database table
 *  in the Model section of the Model-View-Controller architecture.
 *  By convention, the name of each subclass is the CamelCase singular
 *  form of the table name, which is in the lower_case_underscore
 *  plural notation.  For example, 
 *  a table named "order_details" would be associated with a subclass
 *  of ActiveRecord named "OrderDetail", and a table named "people"
 *  would be associated with subclass "Person".</p>
 *
 *  <p>For a discussion of the ActiveRecord design pattern, see
 *  "Patterns of Enterprise 
 *  Application Architecture" by Martin Fowler, pp. 160-164.</p>
 *
 *  <p>Unit tester: {@link ActiveRecordTest}</p>
 *
 *  @tutorial PHPonTrax/ActiveRecord.cls
 */
class ActiveRecord {

    /**
     *  Reference to the object returned by PEAR DB::Connect()
     *
     *  @var object DB
     *  <b>FIXME: static should be after private</b>
     */
    static private $db = null;

    /**
     *  Description of a row in the associated table in the database
     *
     *  <p>Retrieved from the RDBMS by {@link set_content_columns()}.
     *  See {@link 
     *  http://pear.php.net/manual/en/package.database.db.db-common.tableinfo.php
     *  DB_common::tableInfo()} for the format.  <b>NOTE:</b> Some
     *  RDBMS's don't return all values.</p>
     *
     *  <p>An additional element 'human_name' is added to each column
     *  by {@link set_content_columns()}.  The actual value contained
     *  in each column is stored in an object variable with the name
     *  given by the 'name' element of the column description for each
     *  column.</p>
     *
     *  <p><b>NOTE:</b>The information from the database about which
     *  columns are primary keys is <b>not used</b>.  Instead, the
     *  primary keys in the table are listed in {@link $primary_keys},
     *  which is maintained independently.</p>
     *  @var string[]
     *  @see $primary_keys
     *  @see quoted_attributes()
     *  @see __set()
     */
    public $content_columns = null; # info about each column in the table

    /**
     *  Table name
     *
     *  Name of the table in the database associated with the subclass.
     *  Normally set to the pluralized lower case underscore form of
     *  the class name by the constructor.  May be overridden.
     *  @var string
     */
    public $table_name = null;

    /**
     *  Database name override
     *
     *  Name of the database to use, if you are not using the value
     *  read from file config/database.ini
     *  @var string
     */
    public $database_name = null;

    /**
     *  Mode to use when fetching data from database
     *
     *  See {@link
     *  http://pear.php.net/manual/en/package.database.db.db-common.setfetchmode.php
     *  the relevant PEAR DB class documentation}
     *  @var integer
     */
    public $fetch_mode = DB_FETCHMODE_ASSOC;

    /**
     *  Force reconnect to database
     *
     *  @var boolean
     */
    public $force_reconnect = false; # should we force a connection everytime
    public $index_on = "id"; # find_all returns an array of objects each object index is off of this field

    # Table associations
    /**
     *  @todo Document this API
     *  @var string[]
     */
    protected $has_many = null;

    /**
     *  @todo Document this API
     *  @var string[]
     */
    protected $has_one = null;

    /**
     *  @todo Document this API
     *  @var string[]
     */
    protected $has_and_belongs_to_many = null;

    /**
     *  @todo Document this API
     *  @var string[]
     */
    protected $belongs_to = null;

    /**
     *  @todo Document this API
     *  @var string[]
     */
    protected $habtm_attributes = null;

    /**
     *  @todo Document this property
     */
    protected $save_associations = array();
    
    /**
     *  @todo Document this property
     *  @var boolean
     */
    public $auto_save_associations = true; # where or not to auto save defined associations if set

    /**
     *  Whether this object represents a new record
     *
     *  true => This object was created without reading a row from the
     *          database, so use SQL 'INSERT' to put it in the database.
     *  false => This object was a row read from the database, so use
     *           SQL 'UPDATE' to update database with new values.
     *  @var boolean
     */
    protected $new_record = true;

    /**
     *  Names of automatic update timestamp columns
     *
     *  When a row containing one of these columns is updated and
     *  {@link $auto_timestamps} is true, update the contents of the
     *  timestamp columns with the current date and time.
     *  @see $auto_timestamps
     *  @see $auto_create_timestamps
     *  @var string[]
     */
    protected $auto_update_timestamps = array("updated_at","updated_on");

    /**
     *  Names of automatic create timestamp columns
     *
     *  When a row containing one of these columns is created and
     *  {@link $auto_timestamps} is true, store the current date and
     *  time in the timestamp columns.
     *  @see $auto_timestamps
     *  @see $auto_update_timestamps
     *  @var string[]
     */
    protected $auto_create_timestamps = array("created_at","created_on");

    /**
     *  Date format for use with auto timestamping
     *
     *  The format for this should be compatiable with the php date() function.
     *  http://www.php.net/date
     *  @var string 
     */
     protected $date_format = "Y-m-d";

    /**
     *  Time format for use with auto timestamping
     *
     *  The format for this should be compatiable with the php date() function.
     *  http://www.php.net/date
     *  @var string 
     */    
     protected $time_format = "H:i:s";
       
    /**
     *  Whether to keep date/datetime fields NULL if not set
     *
     *  true => If date field is not set it try to preserve NULL
     *  false => Don't try to preserve NULL if field is already NULL
     *  @var boolean
     */       
     protected $preserve_null_dates = true;

    /**
     *  SQL aggregate functions that may be applied to the associated
     *  table.
     *
     *  SQL defines aggregate functions AVG, COUNT, MAX, MIN and SUM.
     *  Not all of these functions are implemented by all DBMS's
     *  @var string[]
     */
    protected $aggregrations = array("count","sum","avg","max","min");
     
    /**
     *  Primary key of the associated table
     *
     *  Array element(s) name the primary key column(s), as used to
     *  specify the row to be updated or deleted.  To be a primary key
     *  a column must be listed both here and in {@link
     *  $content_columns}.  <b>NOTE:</b>This
     *  field is maintained by hand.  It is not derived from the table
     *  description read from the database.
     *  @var string[]
     *  @see $content_columns
     *  @see find()
     *  @see find_all()
     *  @see find_first()
     */
    public $primary_keys = array("id");

    /**
     *  Default for how many rows to return from {@link find_all()}
     *  @var integer
     */
    public $rows_per_page_default = 20;

    /**
     *  @todo Document this API
     */
    public $display = 10; # Pagination how many numbers in the list << < 1 2 3 4 > >>

    /**
     *  Description of non-fatal errors found
     *
     *  For every non-fatal error found, an element describing the
     *  error is added to $errors.  Initialized to an empty array in 
     *  {@link valid()} before validating object.  When an error
     *  message is associated with a particular attribute, the message
     *  should be stored with the attribute name as its key.  If the
     *  message is independent of attributes, store it with a numeric
     *  key beginning with 0.
     *  
     *  @var string[]
     *  @see add_error()
     *  @see get_errors()
     */
    public $errors = array();

    /**
     *  Whether to automatically update timestamps in certain columns
     *
     *  @see $auto_create_timestamps
     *  @see $auto_update_timestamps
     *  @var boolean
     */
    public $auto_timestamps = true;

    /**
     *  @todo Document this API
     */
    public $auto_save_habtm = true; # auto insert / update $has_and_belongs_to_many tables

    /**
     *  @todo Document this API
     */    
    public $auto_delete_habtm = true; # auto delete $has_and_belongs_to_many associations

    /**
     *  Transactions (only use if your db supports it)
     *  <b>FIXME: static should be after private</b>
     */
    static private $begin_executed = false; # this is for transactions only to let query() know that a 'BEGIN' has been executed

    /**
     *  <b>FIXME: static should be after public</b>
     */
    static public $use_transactions = false; # this will issue a rollback command if any sql fails

    /**
     *  Construct an ActiveRecord object
     *
     *  <ol>
     *    <li>Establish a connection to the database</li>
     *    <li>Find the name of the table associated with this object</li>
     *    <li>Read description of this table from the database</li>
     *    <li>Optionally apply update information to column attributes</li>
     *  </ol>
     *  @param string[] $attributes Updates to column attributes
     *  @uses establish_connection()
     *  @uses set_content_columns()
     *  @uses $table_name
     *  @uses set_table_name_using_class_name()
     *  @uses update_attributes()
     */
    function __construct($attributes = null) { 
        # Open the database connection
        $this->establish_connection();

        # Set $table_name
        if($this->table_name == null) {
            $this->set_table_name_using_class_name();
        }

        # Set column info
        if($this->table_name) {
            $this->set_content_columns($this->table_name);
        }

        # If $attributes array is passed in update the class with its contents
        if(is_array($attributes)) {
            $this->update_attributes($attributes);
        }
    }

    /**
     *  @todo Document this API
     *  Override get() if they do $model->some_association->field_name
     *  dynamically load the requested contents from the database.
     *  @uses $belongs_to
     *  @uses get_association_type()
     *  @uses $has_and_belongs_to_many
     *  @uses $has_many
     *  @uses $has_one
     *  @uses find_all_has_many()
     *  @uses find_all_habtm()
     *  @uses find_one_belongs_to()
     *  @uses find_one_has_one()
     */
    function __get($key) {
        $association_type = $this->get_association_type($key);
        if (is_null($association_type)) {
            return null;
        }
        switch($association_type) {
            case "has_many":
                $parameters = is_array($this->has_many) ? $this->has_many[$key] : null;
                $this->$key = $this->find_all_has_many($key, $parameters);
                break;
            case "has_one":
                $parameters = is_array($this->has_one) ? $this->has_one[$key] : null;
                $this->$key = $this->find_one_has_one($key, $parameters);
                break;
            case "belongs_to":
                $parameters = is_array($this->belongs_to) ? $this->belongs_to[$key] : null;
                $this->$key = $this->find_one_belongs_to($key, $parameters);
                break;
            case "has_and_belongs_to_many":  
                $parameters = is_array($this->has_and_belongs_to_many) ? $this->has_and_belongs_to_many[$key] : null;
                $this->$key = $this->find_all_habtm($key, $parameters); 
                break;            
        }
        //echo "<pre>id: $this->id<br>getting: $key = ".$this->$key."<br></pre>";
        return $this->$key;
    }

    /**
     *  Store column value or description of the table format
     *
     *  If called with key 'table_name', $value is stored as the
     *  description of the table format in $content_columns.
     *  Any other key causes an object variable with the same name to
     *  be created and stored into.  If the value of $key matches the
     *  name of a column in content_columns, the corresponding object
     *  variable becomes the content of the column in this row.
     *  @uses $auto_save_associations
     *  @uses get_association_type()
     *  @uses set_content_columns()
     */
    function __set($key, $value) {
        //echo "setting: $key = $value<br>";
        if($key == "table_name") {
            $this->set_content_columns($value);
            # this elseif checks if first its an object if its parent is ActiveRecord
        } elseif(is_object($value) && get_parent_class($value) == __CLASS__ && $this->auto_save_associations) {
            if($association_type = $this->get_association_type($key)) {
                $this->save_associations[$association_type][] = $value;
                if($association_type == "belongs_to") {
                    $foreign_key = Inflector::singularize($value->table_name)."_id";
                    $this->$foreign_key = $value->id; 
                }
            }
            # this elseif checks if its an array of objects and if its parent is ActiveRecord                
        } elseif(is_array($value) && $this->auto_save_associations) {
            if($association_type = $this->get_association_type($key)) {
                $this->save_associations[$association_type][] = $value;
            }
        }         
        
	//  Assignment to something else, do it
        $this->$key = $value;
    }

    /**
     *  @todo Document this API
     *  Override call() to dynamically call the database associations
     *  @uses $aggregrations
     *  @uses aggregrate_all()
     *  @uses get_association_type()
     *  @uses $belongs_to
     *  @uses $has_one
     *  @uses $has_and_belongs_to_many
     *  @uses $has_many
     *  @uses find_all_by()
     *  @uses find_by()
     */
    function __call($method_name, $parameters) {
        if(method_exists($this,$method_name)) {
            # If the method exists, just call it
            $result = call_user_func(array($this,$method_name), $parameters);
        } else {
            # ... otherwise, check to see if the method call is one of our
            # special Trax methods ...
            # ... first check for method names that match any of our explicitly
            # declared associations for this model ( e.g. $this->has_many = array("movies" => null) ) ...
            $association_type = $this->get_association_type($method_name);
            switch($association_type) {
                case "has_many":
                    $result = $this->find_all_has_many($method_name, $parameters);
                    break;
                case "has_one":
                    $result = $this->find_one_has_one($method_name, $parameters);
                    break;
                case "belongs_to":
                    $result = $this->find_one_belongs_to($method_name, $parameters);
                    break;
                case "has_and_belongs_to_many":  
                    $result = $this->find_all_habtm($method_name, $parameters); 
                    break;            
            }

            # check for the [count,sum,avg,etc...]_all magic functions
            if(substr($method_name, -4) == "_all" && in_array(substr($method_name, 0, -4),$this->aggregrations)) {
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

    /**
     *  @todo Document this API
     *  Returns a the name of the join table that would be used for the two
     *  tables.  The join table name is decided from the alphabetical order
     *  of the two tables.  e.g. "genres_movies" because "g" comes before "m"
     *
     *  Parameters: $first_table, $second_table: the names of two database tables,
     *   e.g. "movies" and "genres"
     */
    private function get_join_table_name($first_table, $second_table) {
        $tables = array();
        $tables["one"] = $first_table;
        $tables["many"] = $second_table;
        @asort($tables);
        return @implode("_", $tables);
    }

    /**
     *  @todo Document this API
     *  Find all records using a "has_and_belongs_to_many" relationship
     * (many-to-many with a join table in between).  Note that you can also
     *  specify an optional "paging limit" by setting the corresponding "limit"
     *  instance variable.  For example, if you want to return 10 movies from the
     *  5th movie on, you could set $this->movies_limit = "10, 5"
     *
     *  Parameters: $this_table_name:  The name of the database table that has the
     *                                 one row you are interested in.  E.g. genres
     *              $other_table_name: The name of the database table that has the
     *                                 many rows you are interested in.  E.g. movies
     *  Returns: An array of ActiveRecord objects. (e.g. Movie objects)
     */
    private function find_all_habtm($other_table_name, $parameters = null) {
        $other_class_name = Inflector::classify($other_table_name);
        # Instantiate an object to access find_all
        $results = new $other_class_name();

        # Prepare the join table name primary keys (fields) to do the join on
        $join_table = $this->get_join_table_name($this->table_name, $other_table_name);
        $this_foreign_key = Inflector::singularize($this->table_name)."_id";
        $other_foreign_key = Inflector::singularize($other_table_name)."_id";
        # Set up the SQL segments
        $conditions = "{$join_table}.{$this_foreign_key}=".intval($this->id);
        $orderings = null;
        $limit = null;
        $joins = "LEFT JOIN {$join_table} ON {$other_table_name}.id = {$other_foreign_key}";

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

    /**
     *  @todo Document this API
     *  Find all records using a "has_many" relationship (one-to-many)
     *
     *  Parameters: $other_table_name: The name of the other table that contains
     *                                 many rows relating to this object's id.
     *  Returns: An array of ActiveRecord objects. (e.g. Contact objects)
     */
    private function find_all_has_many($other_table_name, $parameters = null) {
        # Prepare the class name and primary key, e.g. if
        # customers has_many contacts, then we'll need a Contact
        # object, and the customer_id field name.
        if(@array_key_exists("foreign_key", $parameters))
            $foreign_key = $parameters['foreign_key'];
        else
            $foreign_key = Inflector::singularize($this->table_name)."_id";

        $other_class_name = Inflector::classify($other_table_name);
        $conditions = "{$foreign_key}=$this->id";

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

    /**
     *  @todo Document this API
     *  Find all records using a "has_one" relationship (one-to-one)
     *  (the foreign key being in the other table)
     *  Parameters: $other_table_name: The name of the other table that contains
     *                                 many rows relating to this object's id.
     *  Returns: An array of ActiveRecord objects. (e.g. Contact objects)
     */
    private function find_one_has_one($other_object_name, $parameters = null) {
        # Prepare the class name and primary key, e.g. if
        # customers has_many contacts, then we'll need a Contact
        # object, and the customer_id field name.
        $other_class_name = Inflector::camelize($other_object_name);
        if(@array_key_exists("foreign_key", $parameters))
            $foreign_key = $parameters['foreign_key'];
        else
            $foreign_key = Inflector::singularize($this->table_name)."_id";

        $conditions = "$foreign_key='{$this->id}'";
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

    /**
     *  @todo Document this API
     *  Find all records using a "belongs_to" relationship (one-to-one)
     *  (the foreign key being in the table itself)
     *  Parameters: $other_object_name: The singularized version of a table name.
     *                                  E.g. If the Contact class belongs_to the
     *                                  Customer class, then $other_object_name
     *                                  will be "customer".
     */
    private function find_one_belongs_to($other_object_name, $parameters = null) {
        # Prepare the class name and primary key, e.g. if
        # customers has_many contacts, then we'll need a Contact
        # object, and the customer_id field name.
        $other_class_name = Inflector::camelize($other_object_name);
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

    /**
     *  Implement *_all() functions (SQL aggregate functions)
     *
     *  Apply one of the SQL aggregate functions to a column of the
     *  table associated with this object.  The SQL aggregate
     *  functions are AVG, COUNT, MAX, MIN and SUM.  Not all DBMS's
     *  implement all of these functions.
     *  @param string $agrregrate_type SQL aggregate function to
     *    apply, suffixed '_all'.  The aggregate function is one of
     *  the strings in {@link $aggregrations}. 
     *  @param string[] $parameters  Conditions to apply to the
     *    aggregate function.  If present, must be an array of three
     *    strings:<ol>
     *     <li>$parameters[0]: If present, expression to apply
     *       the aggregate function to.  Otherwise, '*' will be used.
     *       <b>NOTE:</b>SQL uses '*' only for the COUNT() function,
     *       where it means "including rows with NULL in this column".</li>
     *     <li>$parameters[1]: argument to WHERE clause</li>
     *     <li>$parameters[2]: joins??? @todo Document this parameter</li>
     *    </ol>
     *  @throws {@link ActiveRecordError}
     *  @uses query()
     *  @uses is_error()
     */
    private function aggregrate_all($aggregrate_type, $parameters = null) {
        $aggregrate_type = strtoupper(substr($aggregrate_type, 0, -4));
        ($parameters[0]) ? $field = $parameters[0] : $field = "*";
        $sql = "SELECT $aggregrate_type($field) AS agg_result FROM $this->table_name ";
        
        # Use any passed-in parameters
        if (!is_null($parameters)) {
            $conditions = $parameters[1];
            $joins = $parameters[2];
        }

        if(!empty($joins)) $sql .= ",$joins ";
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

    /**
     *  Test whether this object represents a new record
     *  @uses $new_record
     *  @return boolean Whether this object represents a new record
     */
   function is_new_record() {
        return $this->new_record;
    }

   /**
    *  @todo Document this API
    *  get the attributes for a specific column.
    *  @uses $content_columns
    */
    function column_for_attribute($attribute) {
        if(is_array($this->content_columns)) {
            foreach($this->content_columns as $column) {
                if($column['name'] == $attribute) {
                    return $column;
                }
            }
        }
        return null;
    }
    
    /**
     *  checks if a column exists or not in the table
     */
    function column_attribute_exists($attribute) {
        if(is_array($this->content_columns)) {
            foreach($this->content_columns as $column) {
                if($column['name'] == $attribute) {
                    return true;
                }
            }
        } 
        return false;     
    }

    /**
     *  @todo Document this API
     *  Returns PEAR result set of one record with only the passed in column in the result set.
     *
     *  @uses $db
     *  @throws {@link ActiveRecordError}
     *  @uses is_error()
     */
    function send($column) {
        if($this->column_attribute_exists($column)) {
            # Run the query to grab a specific columns value.
            $sql = "SELECT $column FROM $this->table_name WHERE id='$this->id'";
            $this->log_query($sql);
            $result = self::$db->getOne($sql);
            if($this->is_error($result)) {
                $this->raise($result->getMessage());
            }
        }
        return $result;
    }

    /**
     *  @todo Document this API
     * Only used if you want to do transactions and your db supports transactions
     *
     *  @uses $db
     */
    function begin() {
        self::$db->query("BEGIN");
        $this->begin_executed = true;
    }

    /**
     *  @todo Document this API
     *  Only used if you want to do transactions and your db supports transactions
     *
     *  @uses $db
     */
    function commit() {
        self::$db->query("COMMIT"); 
        $this->begin_executed = false;
    }

    /**
     *  @todo Document this API
     *  Only used if you want to do transactions and your db supports transactions
     *
     *  @uses $db
     */
    function rollback() {
        self::$db->query("ROLLBACK");
    }

    /**
     *  Perform an SQL query and return the results
     *
     *  @param string $sql  SQL for the query command
     *  @return mixed {@link http://pear.php.net/manual/en/package.database.db.db-result.php object DB_result}
     *    Result set from query
     *  @uses $db
     *  @uses is_error()
     *  @throws {@link ActiveRecordError}
     */
    function query($sql) {
        # Run the query
        $this->log_query($sql);
        $rs = self::$db->query($sql);
        if ($this->is_error($rs)) {
            if(self::$use_transactions && self::$begin_executed) {
                $this->rollback();
            }
            $this->raise($rs->getMessage());
        }
        return $rs;
    }

    /**
     *  Implement find_by_*() and find_all_by_* methods
     *  
     *  Converts a method name beginning 'find_by_' or 'find_all_by_'
     *  into a query for rows matching the rest of the method name and
     *  the arguments to the function.  The part of the method name
     *  after '_by' is parsed for columns and logical relationships
     *  (AND and OR) to match.  For example, the call
     *    find_by_fname('Ben')
     *  is converted to
     *    SELECT * ... WHERE fname='Ben'
     *  and the call
     *    find_by_fname_and_lname('Ben','Dover')
     *  is converted to
     *    SELECT * ... WHERE fname='Ben' AND lname='Dover'
     *  
     *  @uses find_all()
     *  @uses find_first()
     */
    private function find_by($method_name, $parameters, $find_all = false) {
	$method_parts = explode("_",substr($method_name, ($find_all ? 12 : 8)));
        if(is_array($method_parts)) {
            $param_cnt = 0;
            $part_cnt = 1;
            $and_cnt = substr_count(strtolower($method_name), "_and_");
            $or_cnt = substr_count(strtolower($method_name), "_or_");
            $part_size = count($method_parts) - $and_cnt - $or_cnt;
            // FIXME: This loop doesn't work right for either
	    // find_by_first_name_and_last_name or
	    // find_all_by_first_name_and_last_name
            foreach($method_parts as $part) {
                if(strtoupper($part) == "AND") {
                    $method_params .= implode("_",$field)."='".$parameters[$param_cnt++]."' AND ";
                    $part_cnt--;
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

    /**
     *  Return rows selected by $conditions
     *
     *  If no rows match, an empty array is returned.
     *  @param string $conditions SQL to use in the query.  If
     *    $conditions contains "SELECT", then $orderings, $limit and
     *    $joins are ignored and the query is completely specified by
     *    $conditions.  If $conditions is omitted or does not contain
     *    "SELECT", "SELECT * FROM" will be used.  If $conditions is
     *    specified and does not contain "SELECT", the query will
     *    include "WHERE $conditions".  If $conditions is null, the
     *    entire table is returned.
     *  @param string $orderings Argument to "ORDER BY" in query.
     *    If specified, the query will include
     *    "ORDER BY $orderings". If omitted, no ordering will be
     *    applied.  
     *  @param integer[] $limit Page, rows per page???
     *  @todo Document the $limit and $joins parameters
     *  @param string $joins ???
     *  @uses $rows_per_page_default
     *  @uses $rows_per_page
     *  @uses $offset
     *  @uses $page
     *  @uses is_error()
     *  @uses $new_record
     *  @uses query()
     *  @return object[] Array of objects of the same class as this
     *    object, one object for each row returned by the query.
     *    If the column 'id' was in the results, it is used as the key
     *    for that object in the array.
     *  @throws {@link ActiveRecordError}
     */
    function find_all($conditions = null, $orderings = null, $limit = null, $joins = null) {
        if (is_array($limit)) {
            list(self::$page, self::$rows_per_page) = $limit;
            if(self::$$page <= 0) self::$page = 1;
            # Default for rows_per_page:
            if (self::$rows_per_page == null) self::$rows_per_page = self::$rows_per_page_default;
            # Set the LIMIT string segment for the SQL in the find_all
            self::$offset = (self::$page - 1) * self::$rows_per_page;
            # mysql 3.23 doesn't support OFFSET
            //$limit = "$rows_per_page OFFSET $offset";
            $limit = self::$offset.", ".self::$rows_per_page;
            $set_pages = true;
        }

        if(stristr($conditions, "SELECT")) {
            $sql = $conditions;
        } else {
            $sql  = "SELECT * FROM ".$this->table_name." ";
            if(!is_null($joins)) {
                if(substr($joins,0,4) != "LEFT") $sql .= ",";
                $sql .= " $joins ";
            }
            if(!is_null($conditions)) $sql .= "WHERE $conditions ";
            if(!is_null($orderings)) $sql .= "ORDER BY $orderings ";
            if(!is_null($limit)) {
                if($set_pages) {
                    //echo "ActiveRecord::find_all() - sql: $sql\n<br>";
                    if(self::$is_error($rs = self::query($sql))) {
                        self::raise($rs->getMessage());
                    } else {
                        # Set number of total pages in result set without the LIMIT
                        if($count = $rs->numRows())
                            self::$pages = (($count % self::$rows_per_page) == 0) ? $count / self::$rows_per_page : floor($count / self::$rows_per_page) + 1;
                    }
                }
                $sql .= "LIMIT $limit";
            }
        }

        //echo "ActiveRecord::find_all() - sql: $sql\n<br>";
        if(self::is_error($rs = self::query($sql))) {
            self::raise($rs->getMessage());
        }

        $objects = array();
        while($row = $rs->fetchRow()) {
            #$class = get_class($this);
            $class = Inflector::classify($this->table_name);
            $object = new $class();
            $object->new_record = false;
            foreach($row as $field => $val) {
                $object->$field = $val;
                if($field == $this->index_on) {
                    $objects_key = $val;
                }
            }
            $objects[$objects_key] = $object;
            unset($object);
            unset($objects_key);
        }
        return $objects;
    }

    /**
     *  Find row(s) with specified value(s)
     *
     *  Find all the rows in the table which match the argument $id.
     *  Return zero or more objects of the same class as this
     *  class representing the rows that matched the argument.
     *  @param mixed[] $id  If $id is an array then a query will be
     *    generated selecting all of the array values in column "id".
     *    If $id is a string containing "=" then the string value of
     *    $id will be inserted in a WHERE clause in the query.  If $id
     *    is a scalar not containing "=" then a query will be generated 
     *    selecting the first row WHERE id = '$id'.
     *    <b>NOTE</b> The column name "id" is used regardless of the
     *    value of {@link $primary_keys}.  Therefore if you need to
     *    select based on some column other than "id", you must pass a
     *    string argument ready to insert in the SQL SELECT.
     *  @param string $orderings Argument to "ORDER BY" in query.
     *    If specified, the query will include "ORDER BY
     *    $orderings". If omitted, no ordering will be applied.
     *  @param integer[] $limit Page, rows per page???
     *  @param string $joins ???
     *  @todo Document the $limit and $joins parameters
     *  @uses find_all()
     *  @uses find_first()
     *  @return mixed Results of query.  If $id was a scalar then the
     *    result is an object of the same class as this class and
     *    matching $id conditions, or if no row matched the result is
     *    null. 
     *
     *    If $id was an array then the result is an array containing
     *    objects of the same class as this class and matching the
     *    conditions set by $id.  If no rows matched, the array is
     *    empty.
     *  @throws {@link ActiveRecordError}
     */
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

    /**
     *  Return first row selected by $conditions
     *
     *  If no rows match, null is returned.
     *  @param string $conditions SQL to use in the query.  If
     *    $conditions contains "SELECT", then $orderings, $limit and
     *    $joins are ignored and the query is completely specified by
     *    $conditions.  If $conditions is omitted or does not contain
     *    "SELECT", "SELECT * FROM" will be used.  If $conditions is
     *    specified and does not contain "SELECT", the query will
     *    include "WHERE $conditions".  If $conditions is null, the
     *    entire table is returned.
     *  @param string $orderings Argument to "ORDER BY" in query.
     *    If specified, the query will include
     *    "ORDER BY $orderings". If omitted, no ordering will be
     *    applied.  
     *  FIXME This parameter doesn't seem to make sense
     *  @param integer[] $limit Page, rows per page??? @todo Document this parameter
     *  FIXME This parameter doesn't seem to make sense
     *  @param string $joins ??? @todo Document this parameter
     *  @uses find_all()
     *  @return mixed An object of the same class as this class and
     *    matching $conditions, or null if none did.
     *  @throws {@link ActiveRecordError}
     */
    function find_first($conditions, $orderings = null, $limit = null, $joins = null) {
        $result = $this->find_all($conditions, $orderings, $limit, $joins);
        return @current($result);
    }

    /**
     *  Return all the rows selected by the SQL argument
     *
     *  If no rows match, an empty array is returned.
     *  @param string $sql SQL to use in the query.
     */
    function find_by_sql($sql) {
        return $this->find_all($sql);
    }

    /**
     *  @todo Document this API
     *  Reloads the attributes of this object from the database.
     *  @uses get_primary_key_conditions()
     */
    function reload($conditions = null) {
        if(is_null($conditions)) {
            $conditions = $this->get_primary_key_conditions();
        }
        $object = $this->find($conditions);
        if(is_object($object)) {
            foreach($object as $key => $value) {
                $this->$key = $value;
            }
            return true;
        }
        return false;
    }

    /**
     *  Loads into current object values from the database.
     */
    function load($conditions = null) {
        return $this->reload($conditions);        
    }

    /**
     *  @todo Document this API.  What's going on here?  It appears to
     *        either create a row with all empty values, or it tries
     *        to recurse once for each attribute in $attributes.
     *  FIXME: resolve calling sequence
     *  Creates an object, instantly saves it as a record (if the validation permits it).
     *  If the save fails under validations it returns false and $errors array gets set.
     */
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

    /**
     *  @todo Document this API
     *  Finds the record from the passed id, instantly saves it with the passed attributes 
     *  (if the validation permits it). Returns true on success and false on error.
     */
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

    /**
     *  @todo Document this API
     *  Updates all records with the SET-part of an SQL update statement in updates and 
     *  returns an integer with the number of rows updates. A subset of the records can 
     *  be selected by specifying conditions. 
     *  Example:
     *    $model->update_all("category = 'cooldude', approved = 1", "author = 'John'");
     *  @uses is_error()
     *  @uses query()
     *  @throws {@link ActiveRecordError}
     */
    function update_all($updates, $conditions = null) {
        $sql = "UPDATE $this->table_name SET $updates WHERE $conditions";
        $result = $this->query($sql);
        if ($this->is_error($result)) {
            $this->raise($result->getMessage());
        } else {
            return true;
        }
    }

    /**
     *  @todo Document this API
     *  Save without valdiating anything.
     */
    function save_without_validation($attributes = null) {
        return $this->save($attributes, true);
    }

    /**
     *  Create or update a row in the table with specified attributes
     *
     *  @param string[] $attributes List of name => value pairs giving
     *    name and value of attributes to set.
     *  @param boolean $dont_validate true => Don't call validation
     *    routines before saving the row.  If false or omitted, all 
     *    applicable validation routines are called.
     *  @uses add_record_or_update_record()
     *  @uses update_attributes()
     *  @uses valid()
     *  @return boolean
     *          <ul>
     *            <li>true => row was updated or inserted successfully</li>
     *            <li>false => insert failed</li>
     *          </ul>
     */
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

    /**
     *  Create or update a row in the table
     *
     *  If this object represents a new row in the table, insert it.
     *  Otherwise, update the exiting row.  before_?() and after_?()
     *  routines will be called depending on whether the row is new.
     *  @uses add_record()
     *  @uses after_create()
     *  @uses after_update()
     *  @uses before_create()
     *  @uses before_save()
     *  @uses $new_record
     *  @uses update_record()
     *  @return boolean
     *          <ul>
     *            <li>true => row was updated or inserted successfully</li>
     *            <li>false => insert failed</li>
     *          </ul>
     */
    private function add_record_or_update_record() { 
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

    /**
     *  Insert a new row in the table associated with this object
     *
     *  Build an SQL INSERT statement getting the table name from
     *  {@link $table_name}, the column names from {@link
     *  $content_columns} and the values from object variables.
     *  Send the insert to the RDBMS.
     *  FIXME: Shouldn't we be saving the insert ID value as an object
     *  variable $this->id?
     *  @uses $auto_save_habtm
     *  @uses add_habtm_records()
     *  @uses before_create()
     *  @uses get_insert_id()
     *  @uses is_error()
     *  @uses query()
     *  @uses quoted_attributes()
     *  @uses raise()
     *  @uses $table_name
     *  @return boolean
     *          <ul>
     *            <li>true => row was inserted successfully</li>
     *            <li>false => insert failed</li>
     *          </ul>
     *  @throws {@link ActiveRecordError}
     */
    private function add_record() {
        $attributes = $this->quoted_attributes();
        $fields = @implode(', ', array_keys($attributes));
        $values = @implode(', ', array_values($attributes));
        $sql = "INSERT INTO $this->table_name ($fields) VALUES ($values)";
        //echo "add_record: SQL: $sql<br>";
        $result = $this->query($sql);
        if ($this->is_error($result)) {
            $this->raise($results->getMessage());
        } else {
            $this->id = $this->get_insert_id();
            if($this->id > 0) {
                if($this->auto_save_habtm) {
                    $habtm_result = $this->add_habtm_records($this->id);
                }
                $this->save_associations();
            }          
            return ($result && $habtm_result);
        }
    }

    /**
     *  Update the row in the table described by this object
     *
     *  The primary key attributes must exist and have appropriate
     *  non-null values.  If a column is listed in {@link
     *  $content_columns} but no attribute of that name exists, the
     *  column will be set to the null string ''.
     *  @todo Describe habtm automatic update
     *  @uses is_error()
     *  @uses get_updates_sql()
     *  @uses get_primary_key_conditions()
     *  @uses query()
     *  @uses raise()
     *  @uses update_habtm_records()
     *  @return boolean
     *          <ul>
     *            <li>true => row was updated successfully</li>
     *            <li>false => update failed</li>
     *          </ul>
     *  @throws {@link ActiveRecordError}
     */
    private function update_record() {
        $updates = $this->get_updates_sql();
        $conditions = $this->get_primary_key_conditions();
        $sql = "UPDATE $this->table_name SET $updates WHERE $conditions";
        //echo "update_record: SQL: $sql<br>";
        $result = $this->query($sql);
        if($this->is_error($result)) {
            $this->raise($results->getMessage());
        } else {
            if($this->id > 0) {
                if($this->auto_save_habtm) {
                    $habtm_result = $this->update_habtm_records($this->id);
                }
                $this->save_associations();
            }         
            return ($result && $habtm_result);
        }
    }
    
    /**
     *  returns the association type if defined in child class or null
     *  @todo Document this API
     *  @todo <b>FIXME:</b> does the match algorithm match a substring
     *        of what we want to match?
     *  @uses $belongs_to
     *  @uses $has_and_belongs_to_many
     *  @uses $has_many
     *  @uses $has_one
     *  @return mixed Association type, one of the following:
     *  <ul>
     *    <li>"belongs_to"</li>
     *    <li>"has_and_belongs_to_many"</li>
     *    <li>"has_many"</li>
     *    <li>"has_one"</li>
     *  </ul>
     *  if an association exists, or null if no association
     */
    function get_association_type($association_name) {
        $type = null;
        if(is_string($this->has_many)) {
            if(preg_match("/$association_name/", $this->has_many)) {
                $type = "has_many";    
            }
        } elseif(is_array($this->has_many)) {
            if(array_key_exists($association_name, $this->has_many)) {
                $type = "has_many";     
            }
        }
        if(is_string($this->has_one)) {
            if(preg_match("/$association_name/", $this->has_one)) {
                $type = "has_one";     
            }
        } elseif(is_array($this->has_one)) {
            if(array_key_exists($association_name, $this->has_one)) {
                $type = "has_one";     
            }
        }
        if(is_string($this->belongs_to)) { 
            if(preg_match("/$association_name/", $this->belongs_to)) {
                $type = "belongs_to";      
            }
        } elseif(is_array($this->belongs_to)) {
            if(array_key_exists($association_name, $this->belongs_to)) {
                $type = "belongs_to";      
            }
        }
        if(is_string($this->has_and_belongs_to_many)) {
            if(preg_match("/$association_name/", $this->has_and_belongs_to_many)) {
                $type = "has_and_belongs_to_many";      
            }
        } elseif(is_array($this->has_and_belongs_to_many)) {
            if(array_key_exists($association_name, $this->has_and_belongs_to_many)) {
                $type = "has_and_belongs_to_many";      
            }
        }   
        return $type;   
    }
    
    /**
     *  @todo Document this API
     *  Saves any associations objects assigned to this instance
     *  @uses $auto_save_associations
     */
    private function save_associations() {      
        if(count($this->save_associations) && $this->auto_save_associations) {
            foreach(array_keys($this->save_associations) as $type) {
                if(count($this->save_associations[$type])) {
                    foreach($this->save_associations[$type] as $object_or_array) {
                        if(is_object($object_or_array)) {
                            $this->save_association($object_or_array, $type);     
                        } elseif(is_array($object_or_array)) {
                            foreach($object_or_array as $object) {
                                $this->save_association($object, $type);    
                            }    
                        }
                    }
                }
            }    
        }       
    }
    
    /**
     *  @todo Document this API
     *  save the association to the database
     */
    private function save_association($object, $type) {
        if(is_object($object) && get_parent_class($object) == __CLASS__ && $type) {
            //echo get_class($object)." - type:$type<br>";
            switch($type) {
                case "has_many":
                case "has_one":
                    $foreign_key = Inflector::singularize($this->table_name)."_id";
                    $object->$foreign_key = $this->id; 
                    echo "fk:$foreign_key = $this->id<br>";
                    break;
            }
            $object->save();        
        }            
    }

    /**
     *  @todo Document this API
     *  Deletes the record with the given $id or if you have done a
     *  $model = $model->find($id), then $model->delete() it will delete
     *  the record it just loaded from the find() without passing anything
     *  to delete(). If an array of ids is provided, all ids in array are deleted.
     *  @uses $errors
     */
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
        if($this->auto_delete_habtm) {
            if(is_string($this->has_and_belongs_to_many)) {
                $habtms = explode(",", $this->has_and_belongs_to_many);
                foreach($habtms as $other_table_name) {
                    $this->delete_all_habtm_records(trim($other_table_name), $id);                             
                }
            } elseif(is_array($this->has_and_belongs_to_many)) {
                foreach($this->has_and_belongs_to_many as $other_table_name => $values) {
                    $this->delete_all_habtm_records($other_table_name, $id);                             
                }
            } 
        }
        $this->after_delete();

        return $result;
    }

    /**
     *  Delete from table all rows that match argument
     *
     *  Delete the row(s), if any, matching the argument.
     *  @param string $conditions SQL argument to "WHERE" describing
     *                the rows to delete
     *  @return boolean
     *          <ul>
     *            <li>true => One or more rows were deleted</li>
     *            <li>false => $conditions was omitted</li>
     *          </ul>
     *  @uses is_error()
     *  @uses $new_record
     *  @uses $errors
     *  @uses query()
     *  @throws {@link ActiveRecordError}
     */
    function delete_all($conditions = null) {
        if(is_null($conditions)) {
            $this->errors[] = "No conditions specified to delete on.";
            return false;
        }

        # Delete the record(s)
        if($this->is_error($rs = $this->query("DELETE FROM $this->table_name WHERE $conditions"))) {
            $this->raise($rs->getMessage());
        }

        //  <b>FIXME: We don't know whether this row was deleted.
        //    What are the implications of making this a new record?</b>
        $this->id = 0;
        $this->new_record = true;
        return true;
    }

    /**
     *  @todo Document this API
     * 
     *  @uses $has_and_belongs_to_many
     */
    private function set_habtm_attributes($attributes) {
        if(is_array($attributes)) {
            $this->habtm_attributes = array();
            foreach($attributes as $key => $habtm_array) {
                if(is_array($habtm_array)) {
                    if(is_string($this->has_and_belongs_to_many)) {
                        if(preg_match("/$key/", $this->has_and_belongs_to_many)) {
                            $this->habtm_attributes[$key] = $habtm_array;
                        }
                    } elseif(is_array($this->has_and_belongs_to_many)) {
                        if(array_key_exists($key, $this->has_and_belongs_to_many)) {
                            $this->habtm_attributes[$key] = $habtm_array;
                        }
                    }
                }
            }
        }
    }

    /**
     *
     *  @todo Document this API
     */
    private function update_habtm_records($this_foreign_value) {
        return $this->add_habtm_records($this_foreign_value);
    }

    /**
     *
     *  @todo Document this API
     *  @uses is_error()
     *  @uses query()
     *  @throws {@link ActiveRecordError}
     */
    private function add_habtm_records($this_foreign_value) {
        if($this_foreign_value > 0 && count($this->habtm_attributes) > 0) {
            if($this->delete_habtm_records($this_foreign_value)) {
                reset($this->habtm_attributes);
                foreach($this->habtm_attributes as $other_table_name => $other_foreign_values) {
                    $table_name = $this->get_join_table_name($this->table_name,$other_table_name);
                    $other_foreign_key = Inflector::singularize($other_table_name)."_id";
                    $this_foreign_key = Inflector::singularize($this->table_name)."_id";
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

    /**
     *  @todo Document this API
     *
     *  @uses is_error()
     *  @uses query()
     *  @throws {@link ActiveRecordError}
     */
    private function delete_habtm_records($this_foreign_value) {
        if($this_foreign_value > 0 && count($this->habtm_attributes) > 0) {
            reset($this->habtm_attributes);
            foreach($this->habtm_attributes as $other_table_name => $values) {
                $this->delete_all_habtm_records($other_table_name, $this_foreign_value);
            }
        }
        return true;
    }
    
    private function delete_all_habtm_records($other_table_name, $this_foreign_value) {
        if($other_table_name && $this_foreign_value > 0) {
            $habtm_table_name = $this->get_join_table_name($this->table_name,$other_table_name);
            $this_foreign_key = Inflector::singularize($this->table_name)."_id";
            $sql = "DELETE FROM $habtm_table_name WHERE $this_foreign_key = $this_foreign_value";
            //echo "delete_all_habtm_records: SQL: $sql<br>";
            $result = $this->query($sql);
            if($this->is_error($result)) {
                $this->raise($result->getMessage());
            }            
        }
    }

    /**
     *  Apply automatic timestamp updates
     *
     *  If automatic timestamps are in effect (as indicated by
     *  {@link $auto_timestamps} == true) and the column named in the
     *  $field argument is of type "timestamp" and matches one of the
     *  names in {@link auto_create_timestamps} or {@link
     *  auto_update_timestamps}(as selected by {@link $new_record}),
     *  then return the current date and  time as a string formatted
     *  to insert in the database.  Otherwise return $value.
     *  @uses $new_record
     *  @uses $content_columns
     *  @uses $auto_timestamps
     *  @uses $auto_create_timestamps
     *  @uses $auto_update_timestamps
     *  @param string $field Name of a column in the table
     *  @param mixed $value Value to return if $field is not an
     *                      automatic timestamp column
     *  @return mixed Current date and time or $value
     */
    private function check_datetime($field, $value) {
        if($this->auto_timestamps) {
            if(is_array($this->content_columns)) {
                foreach($this->content_columns as $field_info) {
                    if(($field_info['name'] == $field) && stristr($field_info['type'], "date")) {
                        $format = ($field_info['type'] == "date") ? $this->date_format : "{$this->date_format} {$this->time_format}";
                        if($this->new_record) {
                            if(in_array($field, $this->auto_create_timestamps)) {
                                return date($format);
                            } elseif($this->preserve_null_dates && is_null($value) && !stristr($field_info['flags'], "not_null")) {
                                return 'NULL';    
                            }
                        } elseif(!$this->new_record) {
                            if(in_array($field, $this->auto_update_timestamps)) {
                                return date($format);
                            } elseif($this->preserve_null_dates && is_null($value) && !stristr($field_info['flags'], "not_null")) {
                                return 'NULL';    
                            }
                        }
                    }  
                }
            }
        }
        return $value;
    }

    /**
     *  Update object attributes from list in argument
     *  @param string[] $attributes List of name => value pairs giving
     *    name and value of attributes to set.
     *  @uses $auto_save_associations
     *  @todo Figure out and document how datetime fields work
     */
    function update_attributes($attributes) {
        foreach($attributes as $field => $value) {
            # datetime / date parts check
            if(preg_match('/^\w+\(.*i\)$/i', $field)) {
                $datetime_key = substr($field, 0, strpos($field, "("));
                if($datetime_key != $old_datetime_key) {
                    $old_datetime_key = $datetime_key;                     
                    $datetime_value = "";   
                } 
                if(strstr($field, "2i") || strstr($field, "3i")) {
                    $datetime_value .= "-".$value;    
                } elseif(strstr($field, "4i")) {
                    $datetime_value .= " ".$value;        
                } elseif(strstr($field, "5i")) {
                    $datetime_value .= ":".$value;        
                } else {
                    $datetime_value .= $value;    
                }  
                $datetime_fields[$old_datetime_key] = $datetime_value;     
                # this elseif checks if first its an object if its parent is ActiveRecord            
            } elseif(is_object($value) && get_parent_class($value) == __CLASS__ && $this->auto_save_associations) {
                if($association_type = $this->get_association_type($field)) {
                    $this->save_associations[$association_type][] = $value;
                    if($association_type == "belongs_to") {
                        $foreign_key = Inflector::singularize($value->table_name)."_id";
                        $this->$foreign_key = $value->id; 
                    }
                }
                # this elseif checks if its an array of objects and if its parent is ActiveRecord                
            } elseif(is_array($value) && $this->auto_save_associations) {
                if($association_type = $this->get_association_type($field)) {
                    $this->save_associations[$association_type][] = $value;
                }
            } else {
                $this->$field = $value;
            }
        }
        if(isset($datetime_fields)
           && is_array($datetime_fields)) {
            foreach($datetime_fields as $field => $value) {
                $this->$field = $value;    
            }    
        }
        $this->set_habtm_attributes($attributes);
    }

    /**
     *  Return pairs of column-name:column-value
     *
     *  Return the contents of the object as an array of elements
     *  where the key is the column name and the value is the column
     *  value.  Relies on a previous call to
     *  {@link set_content_columns()} for information about the format
     *  of a row in the table.
     *  @uses $content_columns
     *  @see set_content_columns
     *  @see quoted_attributes()
     */
    function get_attributes() {
        $attributes = array();
        if(is_array($this->content_columns)) {
            foreach($this->content_columns as $column) {
                //echo "attribute: $info[name] -> {$this->$info[name]}<br>";
                $attributes[$column['name']] = $this->$column['name'];
            }
        }
        return $attributes;
    }

    /**
     *  Return pairs of column-name:quoted-column-value
     *
     *  Return pairs of column-name:quoted-column-value where the key
     *  is the column name and the value is the column value with
     *  automatic timestamp updating applied and characters special to
     *  SQL quoted.
     *  
     *  If $attributes is null or omitted, return all columns as
     *  currently stored in {@link content_columns()}.  Otherwise,
     *  return the name:value pairs in $attributes.
     *  @param string[] $attributes Name:value pairs to return.
     *    If null or omitted, return the column names and values
     *    of the object as stored in $content_columns.
     *  @return string[] 
     *  @uses get_attributes()
     *  @see set_content_columns()
     */
    function quoted_attributes($attributes = null) {
        if(is_null($attributes)) {
            $attributes = $this->get_attributes();
        }
        $return = array();
        foreach ($attributes as $key => $value) {
            $value = $this->check_datetime($key, $value);
            # If the value isn't a function or null quote it.
            if(!(preg_match('/^\w+\(.*\)$/U', $value)) && !(strcasecmp($value, 'NULL') == 0)) {
                $value = str_replace("\\\"","\"",$value);
                $value = str_replace("\'","'",$value);
                $value = str_replace("\\\\","\\",$value);
                $return[$key] = "'" . addslashes($value) . "'";
            } else {
                $return[$key] = $value;
            }
            //$return[$key] = self::$db->quoteSmart($value);
        }
        return $return;
    }

    /**
     *  Return argument for a "WHERE" clause specifying this row
     *
     *  Returns a string which specifies the column(s) and value(s)
     *  which describe the primary key of this row of the associated
     *  table.  The primary key must be one or more attributes of the
     *  object and must be listed in {@link $content_columns} as
     *  columns in the row.
     *
     *  Example: if $primary_keys = array("id", "ssn") and column "id"
     *  has value "5" and column "ssn" has value "123-45-6789" then
     *  the string "id = '5' AND ssn = '123-45-6789'" would be returned.
     *  @uses $primary_keys
     *  @uses quoted_attributes()
     *  @return string Column name = 'value' [ AND name = 'value']...
     */
    function get_primary_key_conditions() {
        $conditions = null;
        $attributes = $this->quoted_attributes();
        if(count($attributes) > 0) {
            $conditions = array();
            # run through our fields and join them with their values
            foreach($attributes as $key => $value) {
                if(in_array($key, $this->primary_keys)) {
                    if(!is_numeric($value) && !strstr($value, "'")) {
                        $conditions[] = "$key = '$value'";
                    } else {
                        $conditions[] = "$key = $value";    
                    }
                }
            }
            $conditions = implode(" AND ", $conditions);
        }
        return $conditions;
    }

    /**
     *  Return column values of object formatted for SQL update statement
     *
     *  Return a string containing the column names and values of this
     *  object in a format ready to be inserted in a SQL UPDATE
     *  statement.  Automatic update has been applied to timestamps if
     *  enabled and characters special to SQL have been quoted.
     *  @uses quoted_attributes()
     *  @return string Column name = 'value', ... for all attributes
     */
    function get_updates_sql() {
        $updates = null;
        $attributes = $this->quoted_attributes();
        if(count($attributes) > 0) {
            $updates = array();
            # run through our fields and join them with their values
            foreach($attributes as $key => $value) {
                if($key && $value && !in_array($key, $this->primary_keys)) {
                    $updates[] = "$key = $value";
                }
            }
            $updates = implode(", ", $updates);
        }
        return $updates;
    }

    /**
     *  Set {@link $table_name} from the class name of this object
     *
     *  By convention, the name of the database table represented by
     *  this object is derived from the name of the class.
     *  @uses Inflector::tableize()
     */
    function set_table_name_using_class_name() {
        if(!$this->table_name) {
            $this->table_name = Inflector::tableize(get_class($this));
        }
    }

    /**
     *  Populate object with information about the table it represents 
     *
     *  Call {@link 
     *  http://pear.php.net/manual/en/package.database.db.db-common.tableinfo.php
     *  DB_common::tableInfo()} to get a description of the table and
     *  store it in {@link $content_columns}.  Add a more human
     *  friendly name to the element for each column.
     *  <b>FIXME: should throw an exception if tableInfo() fails</b>
     *  @uses $db
     *  @uses $content_columns
     *  @uses Inflector::humanize()
     *  @see __set()
     *  @param string $table_name  Name of table to get information about
     */
    function set_content_columns($table_name) {
        $this->content_columns = self::$db->tableInfo($table_name);
        if(is_array($this->content_columns)) {
            $i = 0;
            foreach($this->content_columns as $column) {
                $this->content_columns[$i++]['human_name'] = Inflector::humanize($column['name']);
            }
        }
    }

    /**
     *  Returns the autogenerated id from the last insert query
     *
     *  @uses $db
     *  @uses is_error()
     *  @uses raise()
     *  @throws {@link ActiveRecordError}
     */
    function get_insert_id() {
        $id = self::$db->getOne("SELECT LAST_INSERT_ID();");
        if ($this->is_error($id)) {
            $this->raise($id->getMessage());
        }
        return $id;
    }

    /**
     *  Open a database connection if one is not currently open
     *
     *  The name of the database normally comes from
     *  $GLOBALS['TRAX_DB_SETTINGS'] which is set in {@link
     *  environment.php} by reading file config/database.ini. The
     *  database name may be overridden by assigning a different name
     *  to {@link $database_name}. 
     *  
     *  If there is a connection now open, as indicated by the saved
     *  value of a DB object in $GLOBALS['ACTIVE_RECORD_DB'], and
     *  {@link force_reconnect} is not true, then set the database
     *  fetch mode and return.
     *
     *  If there is no connection, open one and save a reference to
     *  it in $GLOBALS['ACTIVE_RECORD_DB'].
     *
     *  @uses $db
     *  @uses $database_name
     *  @uses $force_reconnect
     *  @uses is_error()
     *  @throws {@link ActiveRecordError}
     */
    function establish_connection() {
        # Connect to the database and throw an error if the connect fails.
      if(!array_key_exists('ACTIVE_RECORD_DB',$GLOBALS)
	 || !is_object($GLOBALS['ACTIVE_RECORD_DB'])
	 || $this->force_reconnect) {
            if(array_key_exists("use", $GLOBALS['TRAX_DB_SETTINGS'][TRAX_MODE])) {
                $connection_settings = $GLOBALS['TRAX_DB_SETTINGS'][$GLOBALS['TRAX_DB_SETTINGS'][TRAX_MODE]['use']];
            } else {
                $connection_settings = $GLOBALS['TRAX_DB_SETTINGS'][TRAX_MODE];
            }
            # Override database name if param is set
            if($this->database_name) {
                $connection_settings['database'] = $this->database_name;               
            }            
            # Set optional Pear parameters
            if(isset($connection_settings['persistent'])) {
                $connection_options['persistent'] =
                    $connection_settings['persistent'];
            }
            $GLOBALS['ACTIVE_RECORD_DB'] =& DB::Connect($connection_settings, $connection_options);
        }
        if(!$this->is_error($GLOBALS['ACTIVE_RECORD_DB'])) {
            self::$db = $GLOBALS['ACTIVE_RECORD_DB'];
        } else {
            $this->raise($GLOBALS['ACTIVE_RECORD_DB']->getMessage());
        }
        self::$db->setFetchMode($this->fetch_mode);
        return self::$db;
    }

    /**
     *  Test whether argument is a PEAR Error object or a DB Error object.
     *
     *  @param object $obj Object to test
     *  @return boolean  Whether object is one of these two errors
     */
    function is_error($obj) {
        if((PEAR::isError($obj)) || (DB::isError($obj))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *  Throw an exception describing an error in this object
     *
     *  @throws {@link ActiveRecordError}
     */
    function raise($message) {
        $error_message  = "Model Class: ".get_class($this)."<br>";
        $error_message .= "Error Message: ".$message;
        throw new ActiveRecordError($error_message, "ActiveRecord Error", "500");
    }

    /**
     *  Add or overwrite description of an error to the list of errors
     *  @param string $error Error message text
     *  @param string $key Key to associate with the error (in the
     *    simple case, column name).  If omitted, numeric keys will be
     *    assigned starting with 0.  If specified and the key already
     *    exists in $errors, the old error message will be overwritten
     *    with the value of $error.
     *  @uses $errors
     */
    function add_error($error, $key = null) {
        if(!is_null($key)) 
            $this->errors[$key] = $error;
        else
            $this->errors[] = $error;
    }

    /**
     *  Return description of non-fatal errors
     *
     *  @uses $errors
     *  @param boolean $return_string
     *    <ul>
     *      <li>true => Concatenate all error descriptions into a string
     *        using $seperator between elements and return the
     *        string</li>
     *      <li>false => Return the error descriptions as an array</li>
     *    </ul>
     *  @param string $seperator  String to concatenate between error
     *    descriptions if $return_string == true
     *  @return mixed Error description(s), if any
     */
    function get_errors($return_string = false, $seperator = "<br>") {
        if($return_string && count($this->errors) > 0) {
            return implode($seperator, $this->errors);
        } else {
            return $this->errors;
        }
    }

    /**
     *  Return errors as a string.
     *
     *  Concatenate all error descriptions into a stringusing
     *  $seperator between elements and return the string.
     *  @param string $seperator  String to concatenate between error
     *    descriptions
     *  @return string Concatenated error description(s), if any
     */
    function get_errors_as_string($seperator = "<br>") {
        return $this->get_errors(true, $seperator);
    }

    /**
     *  Runs validation routines for update or create
     *
     *  @uses after_validation();
     *  @uses after_validation_on_create();
     *  @uses after_validation_on_update();
     *  @uses before_validation();
     *  @uses before_validation_on_create();
     *  @uses before_validation_on_update();
     *  @uses $errors
     *  @uses $new_record
     *  @uses validate();
     *  @uses validate_model_attributes();
     *  @uses validate_on_create(); 
     *  @return boolean 
     *    <ul>
     *      <li>true => Valid, no errors found.
     *        {@link $errors} is empty</li>
     *      <li>false => Not valid, errors in {@link $errors}</li>
     *    </ul>
     */
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

    /**
     *  Call every method named "validate_*()" where * is a column name
     *
     *  Find and call every method named "validate_something()" where
     *  "something" is the name of a column.  The "validate_something()"
     *  functions are expected to return an array whose first element
     *  is true or false (indicating whether or not the validation
     *  succeeded), and whose second element is the error message to
     *  display if the first element is false.
     *
     *  @return boolean 
     *    <ul>
     *      <li>true => Valid, no errors found.
     *        {@link $errors} is empty</li>
     *      <li>false => Not valid, errors in {@link $errors}.
     *        $errors is an array whose keys are the names of columns,
     *        and the value of each key is the error message returned
     *        by the corresponding validate_*() method.</li>
     *    </ul>
     *  @uses $errors
     *  @uses get_attributes()
     */
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

    /**
     *  @todo Document this API
     *  Overwrite this method for validation checks on all saves and
     *  use $this->errors[] = "My error message."; or
     *  for invalid attributes $this->errors['attribute'] = "Attribute is invalid.";
     */
    function validate() {}

    /**
     *  @todo Document this API
     *  Override this method for validation checks used only on creation.
     */
    function validate_on_create() {}

    /**
     *  @todo Document this API
     *  Override this method for validation checks used only on updates.
     */
    function validate_on_update() {}

    /**
     *  @todo Document this API
     *  Is called before validate().
     */
    function before_validation() {}

    /**
     *  @todo Document this API
     *  Is called after validate().
     */
    function after_validation() {}

    /**
     *  @todo Document this API
     *  Is called before validate() on new objects that haven't been saved yet (no record exists).
     */
    function before_validation_on_create() {}

    /**
     *  @todo Document this API
     *  Is called after validate() on new objects that haven't been saved yet (no record exists).
     */
    function after_validation_on_create()  {}

    /**
     *  @todo Document this API
     *  Is called before validate() on existing objects that has a record.
     */
    function before_validation_on_update() {}

    /**
     *  @todo Document this API
     *  Is called after validate() on existing objects that has a record.
     */
    function after_validation_on_update()  {}

    /**
     *  @todo Document this API
     *  Is called before save() (regardless of whether its a create or update save)
     */
    function before_save() {}

    /**
     *  @todo Document this API
     *  Is called after save (regardless of whether its a create or update save).
     */
    function after_save() {}

    /**
     *  @todo Document this API
     *  Is called before save() on new objects that havent been saved yet (no record exists).
     */
    function before_create() {}

    /**
     *  @todo Document this API
     *  Is called after save() on new objects that havent been saved yet (no record exists).
     */
    function after_create() {}

    /**
     *  @todo Document this API
     *  Is called before save() on existing objects that has a record.
     */
    function before_update() {}

    /**
     *  @todo Document this API
     *  Is called after save() on existing objects that has a record.
     */
    function after_update() {}

    /**
     *  @todo Document this API
     *  Is called before delete().
     */
    function before_delete() {}

    /**
     *  @todo Document this API
     *  Is called after delete().
     */
    function after_delete() {}

    function log_query($sql) {
        if(TRAX_MODE == "development" && $sql) {
            $GLOBALS['ACTIVE_RECORD_SQL_LOG'][] = $sql;       
        }    
    }

    /**
     *  @todo Document this API
     * Paging html functions
     */
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

    /**
     *  @todo Document this API
     *
     */
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

// -- set Emacs parameters --
// Local variables:
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
