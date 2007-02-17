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
 *  Load the {@link http://pear.php.net/manual/en/package.database.mdb2.php PEAR MDB2 package}
 *  PEAR::DB is now deprecated.
 *  (This package(DB) been superseded by MDB2 but is still maintained for bugs and security fixes)
 */
require_once('MDB2.php');

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
 *  would be associated with subclass "Person".  See the tutorial
 *  {@tutorial PHPonTrax/naming.pkg}</p>
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
     *  Reference to the database object
     *
     *  Reference to the database object returned by
     *  {@link http://pear.php.net/manual/en/package.database.mdb2.intro-connect.php  PEAR MDB2::Connect()}
     *  @var object DB
     *  see
     *  {@link http://pear.php.net/manual/en/package.database.mdb2.php PEAR MDB2}
     */
    protected static $db = null;

    /**
     *  Description of a row in the associated table in the database
     *
     *  <p>Retrieved from the RDBMS by {@link set_content_columns()}.
     *  See {@link 
     *  http://pear.php.net/package/MDB2/docs/2.3.0/MDB2/MDB2_Driver_Reverse_Common.html#methodtableInfo
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
     *  Table Info
     *
     *  Array to hold all the info about table columns.  Indexed on $table_name.
     *  @var array
     */    
    public static $table_info = array();

    /**
     *  Class name
     *
     *  Name of the child class. (this is optional and will automatically be determined)
     *  Normally set to the singular camel case form of the table name.  
     *  May be overridden.
     *  @var string
     */
    public $class_name = null; 

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
     *  Table prefix
     *
     *  Name to prefix to the $table_name. May be overridden.
     *  @var string
     */
    public $table_prefix = null;

    /**
     *  Database name override
     *
     *  Name of the database to use, if you are not using the value
     *  read from file config/database.ini
     *  @var string
     */
    public $database_name = null;
    
    /**
     *  Index into the $active_connections array
     *
     *  Name of the index to use to return or set the current db connection
     *  Mainly used if you want to connect to different databases between
     *  different models.
     *  @var string
     */    
    public $connection_name = TRAX_ENV;

	/**
	 * Stores the database settings
	 */
	public static $database_settings = array();
	
	/**
	 * Stores the active connections. Indexed on $connection_name.
	 */
	public static $active_connections = array();

    /**
     *  Mode to use when fetching data from database
     *
     *  See {@link
     *  http://pear.php.net/package/MDB2/docs/2.3.0/MDB2/MDB2_Driver_Common.html#methodsetFetchMode
     *  the relevant PEAR MDB2 class documentation}
     *  @var integer
     */
    public $fetch_mode = MDB2_FETCHMODE_ASSOC;

    /**
     *  Force reconnect to database every page load
     *
     *  @var boolean
     */
    public $force_reconnect = false;

    /**
     *  find_all() returns an array of objects, 
     *  each object index is off of this field
     *
     *  @var string
     */    
    public $index_on = "id"; 

    /**
     *  Not yet implemented (page 222 Rails books)
     *
     *  @var boolean
     */    
    public $lock_optimistically = true;
    
    /**
     *  Composite custom user created objects
     *  @var mixed
     */    
    public $composed_of = null;

    # Table associations
    /**
     *  @todo Document this variable
     *  @var string[]
     */
    protected $has_many = null;

    /**
     *  @todo Document this variable
     *  @var string[]
     */
    protected $has_one = null;

    /**
     *  @todo Document this variable
     *  @var string[]
     */
    protected $has_and_belongs_to_many = null;

    /**
     *  @todo Document this variable
     *  @var string[]
     */
    protected $belongs_to = null;

    /**
     *  @todo Document this variable
     *  @var string[]
     */
    protected $habtm_attributes = null;

    /**
     *  @todo Document this property
     */
    protected $save_associations = array();
    
    /**
     *  Whether or not to auto save defined associations if set
     *  @var boolean
     */
    public $auto_save_associations = true;

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
    public $auto_update_timestamps = array("updated_at","updated_on");

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
    public $auto_create_timestamps = array("created_at","created_on");

    /**
     *  Date format for use with auto timestamping
     *
     *  The format for this should be compatiable with the php date() function.
     *  http://www.php.net/date
     *  @var string 
     */
     public $date_format = "Y-m-d";

    /**
     *  Time format for use with auto timestamping
     *
     *  The format for this should be compatiable with the php date() function.
     *  http://www.php.net/date
     *  @var string 
     */    
     public $time_format = "H:i:s";
       
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
    protected $aggregations = array("count","sum","avg","max","min");
               
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
     *  Pagination how many numbers in the list << < 1 2 3 4 > >>
     */
    public $display = 10;

    /**
     *  @todo Document this variable
     */    
    public $pagination_count = 0;

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
     * An array with all the default error messages.
     */
    public $default_error_messages = array(
    	'inclusion' => "is not included in the list",
    	'exclusion' => "is reserved",
        'invalid' => "is invalid",
        'confirmation' => "doesn't match confirmation",
        'accepted ' => "must be accepted",
        'empty' => "can't be empty",
        'blank' => "can't be blank",
        'too_long' => "is too long (max is %d characters)",
        'too_short' => "is too short (min is %d characters)",
        'wrong_length' => "is the wrong length (should be %d characters)",
        'taken' => "has already been taken",
        'not_a_number' => "is not a number",
        'not_an_integer' => "is not an integer"
    );

	/**
     * An array of all the builtin validation function calls.
     */    
    protected $builtin_validation_functions = array(
        'validates_acceptance_of',
        'validates_confirmation_of',
        'validates_exclusion_of',        
        'validates_format_of',
        'validates_inclusion_of',        
        'validates_length_of',
        'validates_numericality_of',        
        'validates_presence_of',        
        'validates_uniqueness_of'
    );

    /**
     *  Whether to automatically update timestamps in certain columns
     *
     *  @see $auto_create_timestamps
     *  @see $auto_update_timestamps
     *  @var boolean
     */
    public $auto_timestamps = true;

    /**
     *  Auto insert / update $has_and_belongs_to_many tables
     */
    public $auto_save_habtm = true;

    /**
     *  Auto delete $has_and_belongs_to_many associations
     */    
    public $auto_delete_habtm = true; 

    /**
     *  Transactions (only use if your db supports it)
     *  This is for transactions only to let query() know that a 'BEGIN' has been executed
     */
    private static $begin_executed = false;

    /**
     *  Transactions (only use if your db supports it)
     *  This will issue a rollback command if any sql fails.
     */
    public static $use_transactions = false; 
    
    /**
     *  Keep a log of queries executed if in development env
     */    
    public static $query_log = array();

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
        if(!is_null($attributes)) {
            $this->update_attributes($attributes);
        }
        
        # If callback is defined in model run it.
        # this could hurt performance...
        if(method_exists($this, 'after_initialize')) {
            $this->after_initialize();    
        }        
    }

    /**
     *  Override get() if they do $model->some_association->field_name
     *  dynamically load the requested contents from the database.
     *  @todo Document this API
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
        if($association_type = $this->get_association_type($key)) {
            //error_log("association_type:$association_type");
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
        } elseif($this->is_composite($key)) {            
            $composite_object = $this->get_composite_object($key);
            if(is_object($composite_object)) {
                $this->$key = $composite_object;    
            }                                
        } 
        //echo "<pre>getting: $key = ".$this->$key."<br></pre>";
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
                    $primary_key = $value->primary_keys[0];
                    $foreign_key = Inflector::singularize($value->table_name)."_".$primary_key;
                    $this->$foreign_key = $value->$primary_key; 
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
     *  Override call() to dynamically call the database associations
     *  @todo Document this API
     *  @uses $aggregations
     *  @uses aggregate_all()
     *  @uses get_association_type()
     *  @uses $belongs_to
     *  @uses $has_one
     *  @uses $has_and_belongs_to_many
     *  @uses $has_many
     *  @uses find_all_by()
     *  @uses find_by()
     */
    function __call($method_name, $parameters) {
        if(method_exists($this, $method_name)) {
            # If the method exists, just call it
            $result = call_user_func_array(array($this, $method_name), $parameters);
        } else {
            # ... otherwise, check to see if the method call is one of our
            # special Trax methods ...
            # ... first check for method names that match any of our explicitly
            # declared associations for this model ( e.g. public $has_many = "movies" ) ...
            if(is_array($parameters[0])) {
                $parameters = $parameters[0];    
            }
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
            if(substr($method_name, -4) == "_all" && in_array(substr($method_name, 0, -4), $this->aggregations)) {
                //echo "calling method: $method_name<br>";
                $result = $this->aggregate_all($method_name, $parameters);
            }
            # check for the find_all_by_* magic functions
            elseif(strlen($method_name) > 11 && substr($method_name, 0, 11) == "find_all_by") {
                //echo "calling method: $method_name<br>";
                $result = $this->find_by($method_name, $parameters, "all");
            }
            # check for the find_by_* magic functions
            elseif(strlen($method_name) > 7 && substr($method_name, 0, 7) == "find_by") {
                //echo "calling method: $method_name<br>";
                $result = $this->find_by($method_name, $parameters);
            }
            # check for find_or_create_by_* magic functions
            elseif(strlen($method_name) > 17 && substr($method_name, 0, 17) == "find_or_create_by") {
                $result = $this->find_by($method_name, $parameters, "find_or_create");        
            }
        }
        return $result;
    }
    
    /**
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
     *  @todo Document this API
     */
    private function find_all_habtm($other_table_name, $parameters = null) {
        $additional_conditions = null;
        # Use any passed-in parameters
        if(!is_null($parameters)) {
            if(@array_key_exists("conditions", $parameters)) {
                $additional_conditions = " AND (".$parameters['conditions'].")";
            } elseif($parameters[0] != "") {
                $additional_conditions = " AND (".$parameters[0].")";
            }
            if(@array_key_exists("order", $parameters)) {
                $order = $parameters['order'];
            } elseif($parameters[1] != "") {
                $order = $parameters[1];
            }
            if(@array_key_exists("limit", $parameters)) {
                $limit = $parameters['limit'];
            } elseif($parameters[2] != "") {
                $limit = $parameters[2];
            }    
            if(@array_key_exists("class_name", $parameters)) {
                $other_object_name = $parameters['class_name'];
            }            
            if(@array_key_exists("join_table", $parameters)) {
                $join_table = $parameters['join_table'];
            } 
            if(@array_key_exists("foreign_key", $parameters)) {
                $this_foreign_key = $parameters['foreign_key'];
            }
            if(@array_key_exists("association_foreign_key", $parameters)) {
                $other_foreign_key = $parameters['association_foreign_key'];
            }            
            if(@array_key_exists("finder_sql", $parameters)) {
                $finder_sql = $parameters['finder_sql'];
            }   
        }
        
        if(!is_null($other_object_name)) {
            $other_class_name = Inflector::camelize($other_object_name);    
            $other_table_name = Inflector::tableize($other_object_name);    
        } else {
            $other_class_name = Inflector::classify($other_table_name);
        }
        
        # Instantiate an object to access find_all
        $other_class_object = new $other_class_name();

        # If finder_sql is specified just use it instead of determining the joins/sql
        if(!is_null($finder_sql)) {
            $conditions = $finder_sql;    
            $order = null;
            $limit = null;
            $joins = null;
        } else {
            # Prepare the join table name primary keys (fields) to do the join on
            if(is_null($join_table)) {
                $join_table = $this->get_join_table_name($this->table_name, $other_table_name);
            }
            
            # Primary keys
            $this_primary_key  = $this->primary_keys[0];
            $other_primary_key = $other_class_object->primary_keys[0];
            
            # Foreign keys
            if(is_null($this_foreign_key)) {
                $this_foreign_key = Inflector::singularize($this->table_name)."_".$this_primary_key;
            }
            if(is_null($other_foreign_key)) {
                $other_foreign_key = Inflector::singularize($other_table_name)."_".$other_primary_key;
            }
            
            # Primary key value
            if($this->attribute_is_string($this_primary_key)) {
                $this_primary_key_value = "'".$this->$this_primary_key."'";                    
            } elseif(is_numeric($this->$this_primary_key)) {
                $this_primary_key_value = $this->$this_primary_key;
            } else {
                #$this_primary_key_value = 0;
                # no primary key value so just return empty array same as find_all()
                return array();
            }

            # Set up the SQL segments
            $conditions = "{$join_table}.{$this_foreign_key} = {$this_primary_key_value}".$additional_conditions;
            $joins = "LEFT JOIN {$join_table} ON {$other_table_name}.{$other_primary_key} = {$join_table}.{$other_foreign_key}";
        }
        
        # Get the list of other_class_name objects
        return $other_class_object->find_all($conditions, $order, $limit, $joins);
    }

    /**
     *  Find all records using a "has_many" relationship (one-to-many)
     *
     *  Parameters: $other_table_name: The name of the other table that contains
     *                                 many rows relating to this object's id.
     *  Returns: An array of ActiveRecord objects. (e.g. Contact objects)
     *  @todo Document this API
     */
    private function find_all_has_many($other_table_name, $parameters = null) {
        $additional_conditions = null;
        # Use any passed-in parameters
        if(is_array($parameters)) {
            if(@array_key_exists("conditions", $parameters)) {
                $additional_conditions = " AND (".$parameters['conditions'].")";
            } elseif($parameters[0] != "") {
                $additional_conditions = " AND (".$parameters[0].")";
            }
            if(@array_key_exists("order", $parameters)) {
                $order = $parameters['order'];
            } elseif($parameters[1] != "") {
                $order = $parameters[1];
            }
            if(@array_key_exists("limit", $parameters)) {
                $limit = $parameters['limit'];
            } elseif($parameters[2] != "") {
                $limit = $parameters[2];
            }
            if(@array_key_exists("foreign_key", $parameters)) {
                $foreign_key = $parameters['foreign_key'];
            }             
            if(@array_key_exists("class_name", $parameters)) {
                $other_object_name = $parameters['class_name'];
            }  
            if(@array_key_exists("finder_sql", $parameters)) {
                $finder_sql = $parameters['finder_sql'];
            }
        }

        if(!is_null($other_object_name)) {
            $other_class_name = Inflector::camelize($other_object_name);    
        } else {
            $other_class_name = Inflector::classify($other_table_name);
        }

        # Instantiate an object to access find_all
        $other_class_object = new $other_class_name();
        
        # If finder_sql is specified just use it instead of determining the association
        if(!is_null($finder_sql)) {
            $conditions = $finder_sql;  
            $order = null;
            $limit = null;
            $joins = null; 
        } else {          
            # This class primary key
            $this_primary_key = $this->primary_keys[0];
    
            if(!$foreign_key) {
                # this should end up being like user_id or account_id but if you specified
                # a primaray key other than 'id' it will be like user_field
                $foreign_key = Inflector::singularize($this->table_name)."_".$this_primary_key;
            }
            
            $foreign_key_value = $this->$this_primary_key;
            if($other_class_object->attribute_is_string($foreign_key)) {
                $conditions = "{$foreign_key} = '{$foreign_key_value}'";                    
            } elseif(is_numeric($foreign_key_value)) {
                $conditions = "{$foreign_key} = {$foreign_key_value}";
            } else {
                #$conditions = "{$foreign_key} = 0";
                # no primary key value so just return empty array same as find_all()
                return array();                
            }            
            $conditions .= $additional_conditions; 
        }
                         
        # Get the list of other_class_name objects
        return $other_class_object->find_all($conditions, $order, $limit, $joins);
    }

    /**
     *  Find all records using a "has_one" relationship (one-to-one)
     *  (the foreign key being in the other table)
     *  Parameters: $other_table_name: The name of the other table that contains
     *                                 many rows relating to this object's id.
     *  Returns: An array of ActiveRecord objects. (e.g. Contact objects)
     *  @todo Document this API
     */
    private function find_one_has_one($other_object_name, $parameters = null) {       
        $additional_conditions = null;
        # Use any passed-in parameters
        if(is_array($parameters)) {
            //echo "<pre>";print_r($parameters);
            if(@array_key_exists("conditions", $parameters)) {
                $additional_conditions = " AND (".$parameters['conditions'].")";
            } elseif($parameters[0] != "") {
                $additional_conditions = " AND (".$parameters[0].")";
            }
            if(@array_key_exists("order", $parameters)) {
                $order = $parameters['order'];
            } elseif($parameters[1] != "") {
                $order = $parameters[1];
            }
            if(@array_key_exists("foreign_key", $parameters)) {
                $foreign_key = $parameters['foreign_key'];
            }         
            if(@array_key_exists("class_name", $parameters)) {
                $other_object_name = $parameters['class_name'];
            }  
        }
        
        $other_class_name = Inflector::camelize($other_object_name);
        
        # Instantiate an object to access find_all
        $other_class_object = new $other_class_name();

        # This class primary key
        $this_primary_key = $this->primary_keys[0];
        
        if(!$foreign_key){
            $foreign_key = Inflector::singularize($this->table_name)."_".$this_primary_key;
        }

        $foreign_key_value = $this->$this_primary_key;
        if($other_class_object->attribute_is_string($foreign_key)) {
            $conditions = "{$foreign_key} = '{$foreign_key_value}'";                    
        } elseif(is_numeric($foreign_key_value)) {
            $conditions = "{$foreign_key} = {$foreign_key_value}";
        } else {
            #$conditions = "{$foreign_key} = 0";
            return null;
        }

        $conditions .= $additional_conditions; 
        
        # Get the list of other_class_name objects
        return $other_class_object->find_first($conditions, $order);
    }

    /**
     *  Find all records using a "belongs_to" relationship (one-to-one)
     *  (the foreign key being in the table itself)
     *  Parameters: $other_object_name: The singularized version of a table name.
     *                                  E.g. If the Contact class belongs_to the
     *                                  Customer class, then $other_object_name
     *                                  will be "customer".
     *  @todo Document this API
     */
    private function find_one_belongs_to($other_object_name, $parameters = null) {

        $additional_conditions = null;
        # Use any passed-in parameters
        if(is_array($parameters)) {
            //echo "<pre>";print_r($parameters);
            if(@array_key_exists("conditions", $parameters)) {
                $additional_conditions = " AND (".$parameters['conditions'].")";
            } elseif($parameters[0] != "") {
                $additional_conditions = " AND (".$parameters[0].")";
            }
            if(@array_key_exists("order", $parameters)) {
                $order = $parameters['order'];
            } elseif($parameters[1] != "") {
                $order = $parameters[1];
            }
            if(@array_key_exists("foreign_key", $parameters)) {
                $foreign_key = $parameters['foreign_key'];
            }         
            if(@array_key_exists("class_name", $parameters)) {
                $other_object_name = $parameters['class_name'];
            }  
        }
        
        $other_class_name = Inflector::camelize($other_object_name);
     
        # Instantiate an object to access find_all
        $other_class_object = new $other_class_name();

        # This class primary key
        $other_primary_key = $other_class_object->primary_keys[0];

        if(!$foreign_key) {
            $foreign_key = $other_object_name."_".$other_primary_key;
        }
        
        $other_primary_key_value = $this->$foreign_key;
        if($other_class_object->attribute_is_string($other_primary_key)) {
            $conditions = "{$other_primary_key} = '{$other_primary_key_value}'";                    
        } elseif(is_numeric($other_primary_key_value)) {
            $conditions = "{$other_primary_key} = {$other_primary_key_value}";
        } else {
            #$conditions = "{$other_primary_key} = 0";
            return null;
        }
        $conditions .= $additional_conditions;
        
        # Get the list of other_class_name objects
        return $other_class_object->find_first($conditions, $order);
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
     *  the strings in {@link $aggregations}. 
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
    private function aggregate_all($aggregate_type, $parameters = null) {
        $aggregate_type = strtoupper(substr($aggregate_type, 0, -4));
        ($parameters[0]) ? $field = $parameters[0] : $field = "*";
        $sql = "SELECT {$aggregate_type}({$field}) AS agg_result FROM {$this->table_prefix}{$this->table_name} ";
        
        # Use any passed-in parameters
        if(is_array($parameters[1])) {
            extract($parameters[1]);   
        } elseif(!is_null($parameters)) {
            $conditions = $parameters[1];
            $order = $parameters[2];
            $joins = $parameters[3];
        }

        if(!empty($joins)) $sql .= " $joins ";
        if(!empty($conditions)) $sql .= " WHERE $conditions ";
        if(!empty($order)) $sql .= " ORDER BY $order ";

        # echo "$aggregate_type sql:$sql<br>";
        if($this->is_error($rs = $this->query($sql))) {
            $this->raise($rs->getMessage());
        } else {
            $row = $rs->fetchRow();
            if($row["agg_result"]) {
                return $row["agg_result"];    
            }
        }
        return 0;
    }

    /**
     *  Returns a the name of the join table that would be used for the two
     *  tables.  The join table name is decided from the alphabetical order
     *  of the two tables.  e.g. "genres_movies" because "g" comes before "m"
     *
     *  Parameters: $first_table, $second_table: the names of two database tables,
     *   e.g. "movies" and "genres"
     *  @todo Document this API
     */
    public function get_join_table_name($first_table, $second_table) {
        $tables = array($first_table, $second_table);
        @sort($tables);
        return $this->table_prefix.@implode("_", $tables);
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
    *  get the attributes for a specific column.
    *  @uses $content_columns
    *  @todo Document this API
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
    *  get the columns  data type.
    *  @uses column_for_attribute()
    *  @todo Document this API
    */    
    function column_type($attribute) {
        $column = $this->column_for_attribute($attribute);
        if(isset($column['type'])) {
            return $column['type'];    
        }            
        return null;
    }
    
    /**
     *  Check whether a column exists in the associated table
     *
     *  When called, {@link $content_columns} lists the columns in
     *  the table described by this object.
     *  @param string Name of the column
     *  @return boolean true=>the column exists; false=>it doesn't
     *  @uses content_columns
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
     *  Get contents of one column of record selected by id and table
     *
     *  When called, {@link $id} identifies one record in the table
     *  identified by {@link $table}.  Fetch from the database the
     *  contents of column $column of this record.
     *  @param string Name of column to retrieve
     *  @uses $db
     *  @uses column_attribute_exists()
     *  @throws {@link ActiveRecordError}
     *  @uses is_error()
     */
    function send($column) {
        if($this->column_attribute_exists($column) && ($conditions = $this->get_primary_key_conditions())) {
            # Run the query to grab a specific columns value.
            $sql = "SELECT {$column} FROM {$this->table_prefix}{$this->table_name} WHERE {$conditions}";
            $this->log_query($sql);
            $result = self::$db->queryOne($sql);
            if($this->is_error($result)) {
                $this->raise($result->getMessage());
            }
        }
        return $result;
    }

    /**
     * Only used if you want to do transactions and your db supports transactions
     *
     *  @uses $db
     *  @todo Document this API
     */
    function begin() {
        self::$db->query("BEGIN");
        $this->begin_executed = true;
    }

    /**
     *  Only used if you want to do transactions and your db supports transactions
     *
     *  @uses $db
     *  @todo Document this API
     */
    function commit() {
        self::$db->query("COMMIT"); 
        $this->begin_executed = false;
    }

    /**
     *  Only used if you want to do transactions and your db supports transactions
     *
     *  @uses $db
     *  @todo Document this API
     */
    function rollback() {
        self::$db->query("ROLLBACK");
    }

    /**
     *  Perform an SQL query and return the results
     *
     *  @param string $sql  SQL for the query command
     *  @return $mdb2->query {@link http://pear.php.net/manual/en/package.database.mdb2.intro-query.php}
     *    Result set from query
     *  @uses $db
     *  @uses is_error()
     *  @uses log_query()
     *  @throws {@link ActiveRecordError}
     */
    function query($sql) {
        # Run the query
        $this->log_query($sql);
        $rs =& self::$db->query($sql);
        if ($this->is_error($rs)) {
            if(self::$use_transactions && self::$begin_executed) {
                $this->rollback();
            }
            $this->raise($rs->getMessage());
        }
        return $rs;
    }

    /**
     *  Implement find_by_*() and =_* methods
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
    private function find_by($method_name, $parameters, $find_type = null) {
        if($find_type == "find_or_create") {
            $explode_len = 18;
        } elseif($find_type == "all") {
            $explode_len = 12;
        } else {
            $explode_len = 8;     
        }
        $method_name = substr(strtolower($method_name), $explode_len);
	    $method_parts = explode("|", str_replace("_and_", "|AND|", $method_name));
        if(count($method_parts)) {
            $options = array();
            $create_fields = array();
            $param_index = 0;
            foreach($method_parts as $part) {
                if($part == "AND") {
                    $conditions .= " AND ";
                    $param_index++;
                } else {
                    $value = $this->attribute_is_string($part) ? 
                        "'".$parameters[$param_index]."'" : 
                        $parameters[$param_index];                    
                    $create_fields[$part] = $parameters[$param_index];  
                    $conditions .= "{$part} = {$value}";
                } 
            }
            # If last param exists and is a string set it as the ORDER BY clause            
            # or if the last param is an array set it as the $options
            if($last_param = $parameters[++$param_index]) {
                if(is_string($last_param)) {
                    $options['order'] = $last_param;        
                } elseif(is_array($last_param)) {
                    $options = $last_param;    
                }
            }  
            # Set the conditions
            if($options['conditions'] && $conditions) {
                $options['conditions'] = "(".$options['conditions'].") AND (".$conditions.")";    
            } else {
                $options['conditions'] = $conditions;    
            }

            # Now do the actual find with condtions from above
            if($find_type == "find_or_create") {
                # see if we can find a record with specified parameters
                $object = $this->find($options);
                if(is_object($object)) {
                    # we found a record with the specified parameters so return it
                    return $object;    
                } elseif(count($create_fields)) { 
                    # can't find a record with specified parameters so create a new record 
                    # and return new object       
                    foreach($create_fields as $field => $value) {
                        $this->$field = $value;    
                    }
                    $this->save();
                    return $this->find($options);
                }
            } elseif($find_type == "all") {
            	return $this->find_all($options);
            } else {
                return $this->find($options);
            }
        }
    }

	/**
	 *  Builds a sql statement.
	 *  
     *  @uses $rows_per_page_default
     *  @uses $rows_per_page
     *  @uses $offset
     *  @uses $page
	 *
	 */
	function build_sql($conditions = null, $order = null, $limit = null, $joins = null) {
	    
		$offset = null;
        $per_page = null;
        $select = null;

        # this is if they passed in an associative array to emulate
        # named parameters.
        if(is_array($conditions)) {
            if(@array_key_exists("per_page", $conditions) && !is_numeric($conditions['per_page'])) {
                extract($conditions); 
                $per_page = 0;   
            } else {
                extract($conditions);     
            }
            # If conditions wasn't in the array set it to null
            if(is_array($conditions)) {
                $conditions = null;    
            }  
        }

        # Test source of SQL for query
        if(stristr($conditions, "SELECT")) {
            # SQL completely specified in argument so use it as is
            $sql = $conditions;
        } else {

            # If select fields not specified just do a SELECT *
            if(is_null($select)) {
                $select = "*";
            } 

            # SQL will be built from specifications in argument
            $sql  = "SELECT {$select} FROM {$this->table_prefix}{$this->table_name} ";         
            
            # If join specified, include it
            if(!is_null($joins)) {
                $sql .= " $joins ";
            }

            # If conditions specified, include them
            if(!is_null($conditions)) {
                $sql .= "WHERE $conditions ";
            }

            # If ordering specified, include it
            if(!is_null($order)) {
                $sql .= "ORDER BY $order ";
            }

            # Is output to be generated in pages?
            if(is_numeric($limit) || is_numeric($offset) || is_numeric($per_page)) {

                if(is_numeric($limit)) {    
                    $this->rows_per_page = $limit;        
                }
                if(is_numeric($per_page)) {
                    $this->rows_per_page = $per_page;            
                }
                # Default for rows_per_page:
                if ($this->rows_per_page <= 0) {
                    $this->rows_per_page = $this->rows_per_page_default;
                }
				
				# Only use request's page if you are calling from find_all_with_pagination() and if it is int
				if(strval(intval($_REQUEST['page'])) == $_REQUEST['page']) {
					$this->page = $_REQUEST['page'];
				}
				
                if($this->page <= 0) {
                    $this->page = 1;
                }
                                
                # Set the LIMIT string segment for the SQL
				if(is_null($offset)) {
                    $offset = ($this->page - 1) * $this->rows_per_page;
                }

                $sql .= "LIMIT {$this->rows_per_page} OFFSET {$offset}";
                # $sql .= "LIMIT $offset, $this->rows_per_page";
				
				# Set number of total pages in result set
				if($count = $this->count_all($this->primary_keys[0], $conditions, $joins)) {
					$this->pagination_count = $count;
					$this->pages = (($count % $this->rows_per_page) == 0)
						? $count / $this->rows_per_page
						: floor($count / $this->rows_per_page) + 1; 
				}
            }
        }
		
		return $sql;
	}

    /**
     *  Return rows selected by $conditions
     *
     *  If no rows match, an empty array is returned.
     *  @param string SQL to use in the query.  If
     *    $conditions contains "SELECT", then $order, $limit and
     *    $joins are ignored and the query is completely specified by
     *    $conditions.  If $conditions is omitted or does not contain
     *    "SELECT", "SELECT * FROM" will be used.  If $conditions is
     *    specified and does not contain "SELECT", the query will
     *    include "WHERE $conditions".  If $conditions is null, the
     *    entire table is returned.
     *  @param string Argument to "ORDER BY" in query.
     *    If specified, the query will include
     *    "ORDER BY $order". If omitted, no ordering will be
     *    applied.  
     *  @param integer[] Page, rows per page???
     *  @param string ???
     *  @todo Document the $limit and $joins parameters
     *  @uses is_error()
     *  @uses $new_record
     *  @uses query()
     *  @return object[] Array of objects of the same class as this
     *    object, one object for each row returned by the query.
     *    If the column 'id' was in the results, it is used as the key
     *    for that object in the array.
     *  @throws {@link ActiveRecordError}
     */
    function find_all($conditions = null, $order = null, $limit = null, $joins = null) {
        //error_log("find_all(".(is_null($conditions)?'null':$conditions)
        //          .', ' . (is_null($order)?'null':$order)
        //          .', ' . (is_null($limit)?'null':var_export($limit,true))
        //          .', ' . (is_null($joins)?'null':$joins).')');

		# Placed the sql building code in a separate function
		$sql = $this->build_sql($conditions, $order, $limit, $joins);

        # echo "ActiveRecord::find_all() - sql: $sql\n<br>";
        # echo "query: $sql\n";
        # error_log("ActiveRecord::find_all -> $sql");
        if($this->is_error($rs = $this->query($sql))) {
            $this->raise($rs->getMessage());
        }

        $objects = array();
        while($row = $rs->fetchRow()) {
            $class_name = $this->get_class_name();
            $object = new $class_name();
            $object->new_record = false;
            $objects_key = null;
            foreach($row as $field => $value) {
                $object->$field = $value;
                if($field == $this->index_on) {
                    $objects_key = $value;
                }
            }
            $objects[$objects_key] = $object;
            # If callback is defined in model run it.
            # this will probably hurt performance...
            if(method_exists($object, 'after_find')) {
                $object->after_find();    
            }
            unset($object);
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
     *  @param string $order Argument to "ORDER BY" in query.
     *    If specified, the query will include "ORDER BY
     *    $order". If omitted, no ordering will be applied.
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
    function find($id, $order = null, $limit = null, $joins = null) {
        $find_all = false;
        if(is_array($id)) {
            if($id[0]) {
                # passed in array of numbers array(1,2,4,23)
                $primary_key = $this->primary_keys[0];
                $primary_key_values = $this->attribute_is_string($primary_key) ? 
                    "'".implode("','", $id)."'" : 
                    implode(",", $id);
                $options['conditions'] = "{$primary_key} IN({$primary_key_values})";
                $find_all = true;
            } else {
                # passed in an options array
                $options = $id;    
            }
        } elseif(stristr($id, "=")) { 
            # has an "=" so must be a WHERE clause
            $options['conditions'] = $id;
        } else {
            # find an single record with id = $id
            $primary_key = $this->primary_keys[0];
            $primary_key_value = $this->attribute_is_string($primary_key) ? "'".$id."'" : $id ;
            $options['conditions'] = "{$primary_key} = {$primary_key_value}";
        }
        if(!is_null($order)) $options['order'] = $order; 
        if(!is_null($limit)) $options['limit'] = $limit;
        if(!is_null($joins)) $options['joins'] = $joins;


        if($find_all) {
            return $this->find_all($options);
        } else {
            return $this->find_first($options);
        }
    }

    /**
     *  Return first row selected by $conditions
     *
     *  If no rows match, null is returned.
     *  @param string $conditions SQL to use in the query.  If
     *    $conditions contains "SELECT", then $order, $limit and
     *    $joins are ignored and the query is completely specified by
     *    $conditions.  If $conditions is omitted or does not contain
     *    "SELECT", "SELECT * FROM" will be used.  If $conditions is
     *    specified and does not contain "SELECT", the query will
     *    include "WHERE $conditions".  If $conditions is null, the
     *    entire table is returned.
     *  @param string $order Argument to "ORDER BY" in query.
     *    If specified, the query will include
     *    "ORDER BY $order". If omitted, no ordering will be
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
    function find_first($conditions = null, $order = null, $limit = 1, $joins = null) {
        if(is_array($conditions)) {
            $options = $conditions;    
        } else {
            $options['conditions'] = $conditions;    
        }
        if(!is_null($order)) $options['order'] = $order; 
        if(!is_null($limit)) $options['limit'] = $limit;
        if(!is_null($joins)) $options['joins'] = $joins;

        $result = @current($this->find_all($options));
        return (is_object($result) ? $result : null);        
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
     *  Reloads the attributes of this object from the database.
     *  @uses get_primary_key_conditions()
     *  @todo Document this API
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
     *  Creates an object, instantly saves it as a record (if the validation permits it).
     *  If the save fails under validations it returns false and $errors array gets set.
     */
    function create($attributes, $dont_validate = false) {
        $class_name = $this->get_class_name();
        $object = new $class_name();
        $result = $object->save($attributes, $dont_validate);
        return ($result ? $object : false);
    }

    /**
     *  Finds the record from the passed id, instantly saves it with the passed attributes 
     *  (if the validation permits it). Returns true on success and false on error.
     *  @todo Document this API
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
     *  Updates all records with the SET-part of an SQL update statement in updates and 
     *  returns an integer with the number of rows updates. A subset of the records can 
     *  be selected by specifying conditions. 
     *  Example:
     *    $model->update_all("category = 'cooldude', approved = 1", "author = 'John'");
     *  @uses is_error()
     *  @uses query()
     *  @throws {@link ActiveRecordError}
     *  @todo Document this API
     */
    function update_all($updates, $conditions = null) {
        $sql = "UPDATE {$this->table_prefix}{$this->table_name} SET {$updates} WHERE {$conditions}";
        $result = $this->query($sql);
        if ($this->is_error($result)) {
            $this->raise($result->getMessage());
        } else {
            return true;
        }
    }

    /**
     *  Save without valdiating anything.
     *  @todo Document this API
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
        //error_log("ActiveRecord::save() \$attributes="
        //          . var_export($attributes,true));
        $this->update_attributes($attributes);
        if($dont_validate || $this->valid()) {
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
        //error_log('add_record_or_update_record()');
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
     *  @uses $auto_save_habtm
     *  @uses add_habtm_records()
     *  @uses before_create()
     *  @uses get_insert_id()
     *  @uses is_error()
     *  @uses query()
     *  @uses get_inserts()
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
        self::$db->loadModule('Extended', null, true);                
        # $primary_key_value may either be a quoted integer or php null
        $primary_key_value = self::$db->getBeforeID("{$this->table_prefix}{$this->table_name}", $this->primary_keys[0]);
        if($this->is_error($primary_key_value)) {
            $this->raise($primary_key_value->getMessage());
        }
        $this->update_composite_attributes();
        $attributes = $this->get_inserts();
        $fields = @implode(', ', array_keys($attributes));
        $values = @implode(', ', array_values($attributes));
        $sql = "INSERT INTO {$this->table_prefix}{$this->table_name} ({$fields}) VALUES ({$values})";
        //echo "add_record: SQL: $sql<br>";
        //error_log("add_record: SQL: $sql");
        $result = $this->query($sql);
        
        if($this->is_error($result)) {
            $this->raise($results->getMessage());
        } else {
            $habtm_result = true;
            $primary_key = $this->primary_keys[0];
            # $id is now equivalent to the value in the id field that was inserted
            $primary_key_value = self::$db->getAfterID($primary_key_value, "{$this->table_prefix}{$this->table_name}", $this->primary_keys[0]);
            if($this->is_error($primary_key_value)) {
                $this->raise($primary_key_value->getMessage());
            }            
            $this->$primary_key = $primary_key_value;
            if($primary_key_value != '') {
                if($this->auto_save_habtm) {
                    $habtm_result = $this->add_habtm_records($primary_key_value);
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
        //error_log('update_record()');
        $this->update_composite_attributes();
        $updates = $this->get_updates_sql();
        $conditions = $this->get_primary_key_conditions();
        $sql = "UPDATE {$this->table_prefix}{$this->table_name} SET {$updates} WHERE {$conditions}";
        //echo "update_record:$sql<br>";
        //error_log("update_record: SQL: $sql");
        $result = $this->query($sql);
        if($this->is_error($result)) {
            $this->raise($results->getMessage());
        } else {
            $habtm_result = true;
            $primary_key = $this->primary_keys[0];
            $primary_key_value = $this->$primary_key;
            if($primary_key_value > 0) { 
                if($this->auto_save_habtm) {
                    $habtm_result = $this->update_habtm_records($primary_key_value);
                }
                $this->save_associations();
            }         
            return ($result && $habtm_result);
        }
    }

    /**
     *  Loads the model values into composite object
     *  @todo Document this API
     */    
    private function get_composite_object($name) {
        $composite_object = null;
        $composite_attributes = array();
        if(is_array($this->composed_of)) { 
            if(array_key_exists($name, $this->composed_of)) {
                $class_name = Inflector::classify(($this->composed_of[$name]['class_name'] ? 
                    $this->composed_of[$name]['class_name'] : $name));           

                $mappings = $this->composed_of[$name]['mapping'];
                if(is_array($mappings)) {
                    foreach($mappings as $database_name => $composite_name) {
                        $composite_attributes[$composite_name] = $this->$database_name;                      
                    }    
                }   
            }    
        } elseif($this->composed_of == $name) {
            $class_name = $name;
            $composite_attributes[$name] = $this->$name;        
        } 
        
        if(class_exists($class_name)) {                     
            $composite_object = new $class_name;        
            if($composite_object->auto_map_attributes !== false) {
                //echo "auto_map_attributes<br>";
                foreach($composite_attributes as $name => $value) {
                    $composite_object->$name = $value;    
                }                                      
            }           
            if(method_exists($composite_object, '__construct')) {
                //echo "calling constructor<br>";
                $composite_object->__construct($composite_attributes);       
            }         
        } 
        return $composite_object;
    }
    
    /**
     *  returns the association type if defined in child class or null
     *  @todo Document this API
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
            if(preg_match("/\b$association_name\b/", $this->has_many)) {
                $type = "has_many";    
            }
        } elseif(is_array($this->has_many)) {
            if(array_key_exists($association_name, $this->has_many)) {
                $type = "has_many";     
            }
        }
        if(is_string($this->has_one)) {
            if(preg_match("/\b$association_name\b/", $this->has_one)) {
                $type = "has_one";     
            }
        } elseif(is_array($this->has_one)) {
            if(array_key_exists($association_name, $this->has_one)) {
                $type = "has_one";     
            }
        }
        if(is_string($this->belongs_to)) { 
            if(preg_match("/\b$association_name\b/", $this->belongs_to)) {
                $type = "belongs_to";      
            }
        } elseif(is_array($this->belongs_to)) {
            if(array_key_exists($association_name, $this->belongs_to)) {
                $type = "belongs_to";      
            }
        }
        if(is_string($this->has_and_belongs_to_many)) {
            if(preg_match("/\b$association_name\b/", $this->has_and_belongs_to_many)) {
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
     *  Saves any associations objects assigned to this instance
     *  @uses $auto_save_associations
     *  @todo Document this API
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
     *  save the association to the database
     *  @todo Document this API
     */
    private function save_association($object, $type) {
        if(is_object($object) && get_parent_class($object) == __CLASS__ && $type) {
            //echo get_class($object)." - type:$type<br>";
            switch($type) {
                case "has_many":
                case "has_one":
                    $primary_key = $this->primary_keys[0];
                    $foreign_key = Inflector::singularize($this->table_name)."_".$primary_key;
                    $object->$foreign_key = $this->$primary_key; 
                    //echo "fk:$foreign_key = ".$this->$primary_key."<br>";
                    break;
            }
            $object->save();        
        }            
    }

    /**
     *  Deletes the record with the given $id or if you have done a
     *  $model = $model->find($id), then $model->delete() it will delete
     *  the record it just loaded from the find() without passing anything
     *  to delete(). If an array of ids is provided, all ids in array are deleted.
     *  @uses $errors
     *  @todo Document this API
     */
    function delete($id = null) {
        $deleted_ids = array();
        $primary_key_value = null;
        $primary_key = $this->primary_keys[0];
        if(is_null($id)) {
            # Primary key's where clause from already loaded values
            $conditions = $this->get_primary_key_conditions();
            $deleted_ids[] = $this->$primary_key;
        } elseif(!is_array($id)) {         
            $deleted_ids[] = $id;
            $id = $this->attribute_is_string($primary_key) ? "'".$id."'" : $id;
            $conditions = "{$primary_key} = {$id}";
        } elseif(is_array($id)) {
            $deleted_ids = $id;
            $ids = ($this->attribute_is_string($primary_key)) ? 
                "'".implode("','", $id)."'" : 
                implode(',', $id);
            $conditions = "{$primary_key} IN ({$ids})";
        }

        if(is_null($conditions)) {
            $this->errors[] = "No conditions specified to delete on.";
            return false;
        }

        $this->before_delete(); 
        if($result = $this->delete_all($conditions)) {
            foreach($deleted_ids as $id) {
                if($this->auto_delete_habtm && $id != '') {
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
            }
            $this->after_delete();
        }
        
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
        if($this->is_error($rs = $this->query("DELETE FROM {$this->table_prefix}{$this->table_name} WHERE {$conditions}"))) {
            $this->raise($rs->getMessage());
        }
        
        $this->new_record = true;
        return true;
    }

    /**
     *  @uses $has_and_belongs_to_many
     *  @todo Document this API
     */
    private function set_habtm_attributes($attributes) {
        if(is_array($attributes)) {
            $this->habtm_attributes = array();
            foreach($attributes as $key => $habtm_array) {
                if(is_array($habtm_array)) {
                    if(is_string($this->has_and_belongs_to_many)) {
                        if(preg_match("/\b$key\b/", $this->has_and_belongs_to_many)) {
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
     *  @uses is_error()
     *  @uses query()
     *  @throws {@link ActiveRecordError}
     *  @todo Document this API
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
     *
     *  @uses is_error()
     *  @uses query()
     *  @throws {@link ActiveRecordError}
     *  @todo Document this API
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
            $sql = "DELETE FROM {$habtm_table_name} WHERE {$this_foreign_key} = {$this_foreign_value}";
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
                                return null;    
                            }
                        } elseif(!$this->new_record) {
                            if(in_array($field, $this->auto_update_timestamps)) {
                                return date($format);
                            } elseif($this->preserve_null_dates && is_null($value) && !stristr($field_info['flags'], "not_null")) {
                                return null;    
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
     *
     *  The elements of $attributes are parsed and assigned to
     *  attributes of the ActiveRecord object.  Date/time fields are
     *  treated according to the
     *  {@tutorial PHPonTrax/naming.pkg#naming.naming_forms}.
     *  @param string[] $attributes List of name => value pairs giving
     *    name and value of attributes to set.
     *  @uses $auto_save_associations
     *  @todo Figure out and document how datetime fields work
     */
    function update_attributes($attributes) {
        //error_log('update_attributes()');
        if(is_array($attributes)) {
            $datetime_fields = array();
            //  Test each attribute to be updated
            //  and process according to its type
            foreach($attributes as $field => $value) {
                # datetime / date parts check
                if(preg_match('/^\w+\(.*i\)$/i', $field)) {
                    //  The name of this attribute ends in "(?i)"
                    //  indicating that it's part of a date or time
                    $datetime_field = substr($field, 0, strpos($field, "("));
                    if(!in_array($datetime_field, $datetime_fields)) {
                        $datetime_fields[] = $datetime_field;
                    }                                             
                    # this elseif checks if first its an object if its parent is ActiveRecord            
                } elseif(is_object($value) && get_parent_class($value) == __CLASS__ && $this->auto_save_associations) {
                    if($association_type = $this->get_association_type($field)) {
                        $this->save_associations[$association_type][] = $value;
                        if($association_type == "belongs_to") {
                            $primary_key = $value->primary_keys[0];
                            $foreign_key = Inflector::singularize($value->table_name)."_".$primary_key;
                            $this->$foreign_key = $value->$primary_key; 
                        }
                    }
                    # this elseif checks if its an array of objects and if its parent is ActiveRecord                
                } elseif(is_array($value) && $this->auto_save_associations) {
                    if($association_type = $this->get_association_type($field)) {
                        $this->save_associations[$association_type][] = $value;
                    }
                } else {
                    //  Just a simple attribute, copy it
                    $this->$field = $value;
                }
            }
    
            //  If any date/time fields were found, assign the
            //  accumulated values to corresponding attributes
            if(count($datetime_fields)) {
                foreach($datetime_fields as $datetime_field) {
                    $datetime_format = '';
                    $datetime_value = '';
                    if($attributes[$datetime_field."(1i)"]
                        && $attributes[$datetime_field."(2i)"]
                        && $attributes[$datetime_field."(3i)"]) {
                        $datetime_value = $attributes[$datetime_field."(1i)"]
                        . "-" . $attributes[$datetime_field."(2i)"]
                        . "-" . $attributes[$datetime_field."(3i)"];
                        $datetime_format = $this->date_format;
                    }
                    $datetime_value .= " ";
                    if($attributes[$datetime_field."(4i)"]
                        && $attributes[$datetime_field."(5i)"]) {
                        $datetime_value .= $attributes[$datetime_field."(4i)"]
                        . ":" . $attributes[$datetime_field."(5i)"];
                        $datetime_format .= " ".$this->time_format;                        
                    }    
                    if($datetime_value = trim($datetime_value)) {
                        $datetime_value = date($datetime_format, strtotime($datetime_value));
                        //error_log("($field) $datetime_field = $datetime_value");
                        $this->$datetime_field = $datetime_value;    
                    }
                }    
            }
            $this->set_habtm_attributes($attributes);
        }
    }
    
    /**
     * If a composite object was specified via $composed_of, then its values 
     * mapped to the model will overwrite the models values.
     *
     */
    function update_composite_attributes() {
        if(is_array($this->composed_of)) {
            foreach($this->composed_of as $name => $options) {
                $composite_object = $this->$name;
                if(is_array($options) && is_object($composite_object)) {
                    if(is_array($options['mapping'])) {
                        foreach($options['mapping'] as $database_name => $composite_name) {
                            $this->$database_name = $composite_object->$composite_name;                          
                        }    
                    }        
                }       
            }    
        }    
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
        foreach($attributes as $name => $value) {           
            $return[$name] = $this->quote_attribute($name, $value);
        }
        return $return;
    }

    /**
     *  Quotes a single attribute for use in an sql statement.
     *
     */    
    function quote_attribute($attribute, $value = null) {
        $value = is_null($value) ? $this->$attribute : $value;
        $value = $this->check_datetime($attribute, $value);        
        $column = $this->column_for_attribute($attribute);
    	if(isset($column['mdb2type'])) {
    		$type = $column['mdb2type'];
    	} else {
    		$type = $this->attribute_is_string($attribute, $column) ? 
    		    "text" : is_float($attribute) ? "float" : "integer"; 
    	}            
        $value = self::$db->quote($value, $type);    
        if($value === 'NULL' && stristr($column['flags'], "not_null")) {
            $value = "''";    
        } 
        return $value;               
    }

    /**
     *  Escapes a string for use in an sql statement.
     *
     */    
    function escape($string) {
        return(self::$db->escape($string));
    }    

    /**
     *  Return column values for SQL insert statement
     *
     *  Return an array containing the column names and values of this
     *  object, filtering out the primary keys, which are not set.
     *
     *  @uses $primary_keys
     *  @uses quoted_attributes()
     */
    function get_inserts() {
        $attributes = $this->quoted_attributes();
    	$inserts = array();
    	foreach($attributes as $key => $value) {
    		if(!in_array($key, $this->primary_keys) || ($value != "''" && isset($value))) {
    			$inserts[$key] = $value;
    		}
    	}
        return $inserts;
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
     *  the string "id = 5 AND ssn = '123-45-6789'" would be returned.
     *  @uses $primary_keys
     *  @uses quoted_attributes()
     *  @return string Column name = 'value' [ AND name = 'value']...
     */
    function get_primary_key_conditions($operator = "=") {
        $conditions = null;
        $attributes = $this->quoted_attributes();
        if(count($attributes) > 0) {
            $conditions = array();
            # run through our fields and join them with their values
            foreach($attributes as $key => $value) {
                if(in_array($key, $this->primary_keys) && isset($value) && $value != "''") {
                    $conditions[] = "{$key} {$operator} {$value}";    
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
                if($key && isset($value) && !in_array($key, $this->primary_keys)) {
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
            $class_name = $this->get_class_name();
            $this->table_name = Inflector::tableize($class_name);
        }
    }

    /**
     *  Get class name of child object 
     *
     *  this will return the manually set name or get_class($this)
     *  @return string child class name
     */    
    private function get_class_name() {
        return !is_null($this->class_name) ? $this->class_name : get_class($this);                
    }

    /**
     *  Populate object with information about the table it represents 
     *
     *  Call {@link 
     *  http://pear.php.net/manual/en/package.database.db.db-common.tableinfo.php
     *  DB_common::tableInfo()} to get a description of the table and
     *  store it in {@link $content_columns}.  Add a more human
     *  friendly name to the element for each column.
     *  @uses $db
     *  @uses $content_columns
     *  @uses Inflector::humanize()
     *  @see __set()
     *  @param string $table_name  Name of table to get information about
     */
    function set_content_columns($table_name) {
        if(!is_null($this->table_prefix)) {
            $table_name = $this->table_prefix.$table_name;
        }
        if(isset(self::$table_info[$table_name])) {
            $this->content_columns = self::$table_info[$table_name];  
        } else {
            self::$db->loadModule('Reverse', null, true);
            $this->content_columns = self::$db->reverse->tableInfo($table_name);
            if($this->is_error($this->content_columns)) {
                $this->raise($this->content_columns->getMessage());        
            }
            if(is_array($this->content_columns)) {
                $i = 0;
                foreach($this->content_columns as $column) {
                    $this->content_columns[$i++]['human_name'] = Inflector::humanize($column['name']);
                }                
                self::$table_info[$table_name] = $this->content_columns;
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
        // fetch the last inserted id via autoincrement or current value of a sequence
        if(self::$db->supports('auto_increment') === true) {
            $id = self::$db->lastInsertID("{$this->table_prefix}{$this->table_name}", $this->primary_keys[0]);   
            if($this->is_error($id)) {
                $this->raise($id->getMessage());
            } 
            return $id;                
        }

        return null;
    }

    /**
     *  Open a database connection if one is not currently open
     *
     *  The name of the database normally comes from
     *  $database_settings which is set in {@link
     *  environment.php} by reading file config/database.ini. The
     *  database name may be overridden by assigning a different name
     *  to {@link $database_name}. 
     *  
     *  If there is a connection now open, as indicated by the saved
     *  value of a MDB2 object in $active_connections[$connection_name], and
     *  {@link force_reconnect} is not true, then set the database
     *  fetch mode and return.
     *
     *  If there is no connection, open one and save a reference to
     *  it in $active_connections[$connection_name].
     *
     *  @uses $db
     *  @uses $database_name
     *  @uses $force_reconnect
     *  @uses $active_connections
     *  @uses is_error()
     *  @throws {@link ActiveRecordError}
     */
    function establish_connection() {
        $connection =& self::$active_connections[$this->connection_name];
        if(!is_object($connection) || $this->force_reconnect) {
            $connection_settings = array();
            $connection_options = array();
            if(array_key_exists($this->connection_name, self::$database_settings)) {
                 # Use a different custom sections settings ?
                if(array_key_exists("use", self::$database_settings[$this->connection_name])) {
                    $connection_settings = self::$database_settings[self::$database_settings[$this->connection_name]['use']];
                } else {
                    # Custom defined db settings in database.ini 
                    $connection_settings = self::$database_settings[$this->connection_name];
                }
            } else {
                # Just use the current TRAX_ENV's environment db settings
                # $this->connection_name's default value is TRAX_ENV so
                # if should never really get here unless override $this->connection_name
                # and you define a custom db section in database.ini and it can't find it.
                $connection_settings = self::$database_settings[TRAX_ENV];
            }
            # Override database name if param is set
            if($this->database_name) {
                $connection_settings['database'] = $this->database_name;               
            }            
            # Set optional Pear parameters
            if(isset($connection_settings['persistent'])) {
                $connection_options['persistent'] = $connection_settings['persistent'];
            }
            # Connect to the database and throw an error if the connect fails.
            $connection =& MDB2::Connect($connection_settings, $connection_options);
            //static $connect_cnt;  $connect_cnt++; error_log("connection #".$connect_cnt);
            
            # For Postgres schemas (http://www.postgresql.org/docs/8.0/interactive/ddl-schemas.html)
            if(isset($connection_settings['schema_search_path'])){
                if(!$this->is_error($connection)) {
                    # Set the schema search path to a string of comma-separated schema names.
                    # First strip out all the whitespace
                    $connection->query('SET search_path TO '.preg_replace('/\s+/', '', $connection_settings['schema_search_path']));
                }
            } 
        }
        if(!$this->is_error($connection)) {
            self::$active_connections[$this->connection_name] =& $connection;
            self::$db =& $connection;
            self::$db->setFetchMode($this->fetch_mode);
        } else {
            $this->raise($connection->getMessage());
        }      
        return self::$db;
    }

    /**
     *  Determine if passed in attribute (table column) is a string
     *  @param string $attribute Name of the table column
     *  @uses column_for_attribute()
     */    
    function attribute_is_string($attribute, $column = null) {
        $column = is_null($column) ? $this->column_for_attribute($attribute) : $column;
        switch(strtolower($column['mdb2type'])) {
            case 'text':
            case 'timestamp':
            case 'date':
            case 'time':
            case 'blob':
            case 'clob':
                return true;       
        }
        return false;        
    }

    /**
     *  Determine if passed in name is a composite class or not
     *  @param string $name Name of the composed_of mapping
     *  @uses $composed_of
     */    
    private function is_composite($name) {
        if(is_array($this->composed_of)) {
            if(array_key_exists($name, $this->composed_of)) {
                return true;     
            }
        }        
        return false;
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
     *  @uses validate_builtin();
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
            $this->validate_builtin();            
            $this->after_validation();
            $this->validate_on_create(); 
            $this->after_validation_on_create();
        } else {
            $this->before_validation();
            $this->before_validation_on_update();
            $this->validate();
            $this->validate_model_attributes();
            $this->validate_builtin();
            $this->after_validation();
            $this->validate_on_update();
            $this->validate_on_update_builtin();
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
        $methods = get_class_methods($this->get_class_name());
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
     *  Overwrite this method for validation checks on all saves and
     *  use $this->errors[] = "My error message."; or
     *  for invalid attributes $this->errors['attribute'] = "Attribute is invalid.";
     *  @todo Document this API
     */
    function validate() {}

    /**
     *  Override this method for validation checks used only on creation.
     *  @todo Document this API
     */
    function validate_on_create() {}

    /**
     *  Override this method for validation checks used only on updates.
     *  @todo Document this API
     */
    function validate_on_update() {}

    /**
     *  Is called before validate().
     *  @todo Document this API
     */
    function before_validation() {}

    /**
     *  Is called after validate().
     *  @todo Document this API
     */
    function after_validation() {}

    /**
     *  Is called before validate() on new objects that haven't been saved yet (no record exists).
     *  @todo Document this API
     */
    function before_validation_on_create() {}

    /**
     *  Is called after validate() on new objects that haven't been saved yet (no record exists).
     *  @todo Document this API
     */
    function after_validation_on_create()  {}

    /**
     *  Is called before validate() on existing objects that has a record.
     *  @todo Document this API
     */
    function before_validation_on_update() {}

    /**
     *  Is called after validate() on existing objects that has a record.
     *  @todo Document this API
     */
    function after_validation_on_update()  {}

    /**
     *  Is called before save() (regardless of whether its a create or update save)
     *  @todo Document this API
     */
    function before_save() {}

    /**
     *  Is called after save (regardless of whether its a create or update save).
     *  @todo Document this API
     */
    function after_save() {}

    /**
     *  Is called before save() on new objects that havent been saved yet (no record exists).
     *  @todo Document this API
     */
    function before_create() {}

    /**
     *  Is called after save() on new objects that havent been saved yet (no record exists).
     *  @todo Document this API
     */
    function after_create() {}

    /**
     *  Is called before save() on existing objects that has a record.
     *  @todo Document this API
     */
    function before_update() {}

    /**
     *  Is called after save() on existing objects that has a record.
     *  @todo Document this API
     */
    function after_update() {}

    /**
     *  Is called before delete().
     *  @todo Document this API
     */
    function before_delete() {}

    /**
     *  Is called after delete().
     *  @todo Document this API
     */
    function after_delete() {}


	/**
     *  Validates any builtin validates_* functions defined as 
     *  class variables in child model class.
     *
     *  eg. 
     *  public $validates_presence_of = array(
     *      'first_name' => array(
     *          'message' => "is not optional.",
     *          'on' => 'update'
     *      ),
     *      'last_name' => null,
     *      'password' => array(
     *          'on' => 'create'
     *      )
     *  );
     *
     *  @uses $builtin_validation_functions
     */
    function validate_builtin() {
        foreach($this->builtin_validation_functions as $method_name) {
            $validation_name = $this->$method_name;
            if(is_string($validation_name)) {
                $validation_name = explode(",", $validation_name);    
            }
            if(method_exists($this, $method_name) && is_array($validation_name)) {
                foreach($validation_name as $attribute_name => $options) {
                    if(!is_array($options)) {
                        $attribute_name = $options;
                        $options = array();
                    }               
                    $attribute_name = trim($attribute_name);     
                    $parameters = array();
                    $on = array_key_exists('on', $options) ? 
                        $options['on'] : 'save';
                    $message = array_key_exists('message', $options) ? 
                        $options['message'] : null;                  
                    switch($method_name) {
                        case 'validates_acceptance_of':
                            $accept = array_key_exists('accept', $options) ? $options['accept'] : 1;
                            $parameters = array($attribute_name, $message, $accept);
                            break;
                        case 'validates_confirmation_of':
                            $parameters = array($attribute_name, $message);                            
                            break;
                        case 'validates_exclusion_of': 
                            $in = array_key_exists('in', $options) ? $options['in'] : array();
                            $parameters = array($attribute_name, $in, $message);  
                            break;     
                        case 'validates_format_of':
                            $with = array_key_exists('with', $options) ? $options['with'] : '';
                            $parameters = array($attribute_name, $with, $message);
                            break;
                        case 'validates_inclusion_of':   
                            $in = array_key_exists('in', $options) ? $options['in'] : array();
                            $parameters = array($attribute_name, $in, $message);   
                            break;  
                        case 'validates_length_of':
                            $parameters = array($attribute_name, $options);
                            break;
                        case 'validates_numericality_of':  
                            $only_integer = array_key_exists('only_integer', $options) ? 
                                $options['only_integer'] : false;
                            $allow_null = array_key_exists('allow_null', $options) ? 
                                $options['allow_null'] : false;                          
                            $parameters = array($attribute_name, $message, $only_integer, $allow_null);
                            break;      
                        case 'validates_presence_of':   
                            $parameters = array($attribute_name, $message);  
                            break;   
                        case 'validates_uniqueness_of':  
                            $parameters = array($attribute_name, $message);
                            break;                       
                    }
                    if(count($parameters)) { 
                        $call = false;
                        if($on == 'create' && $this->new_record) {
                            $call = true;
                        } elseif($on == 'update' && !$this->new_record) {
                            $call = true;
                        } elseif($on == 'save') {
                            $call = true;         
                        }       
                        if($call) {
                            # error_log("calling $method_name(".implode(",",$parameters).")");
                            call_user_func_array(array($this, $method_name), $parameters);        
                        }                              
                    }
                }
            }             
        }        
    }

	/**
     * Validates that a checkbox is clicked.
     * eg. validates_acceptance_of('eula')
     *
     * @param string|array $attribute_names
     * @param string $message
     * @param string $accept
     */
    function validates_acceptance_of($attribute_names, $message = null, $accept = 1) {
		$message = $this->get_error_message_for_validation($message, 'acceptance');		
        foreach((array) $attribute_names as $attribute_name) {					
            if($this->$attribute_name != $accept) {
                $attribute_human = Inflector::humanize($attribute_name);
				$this->add_error("{$attribute_human} {$message}", $attribute_name);
            }
        }
    }

	/**
     * Validates that a field has the same value as its corresponding confirmation field.
     * eg. validates_confirmation_of('password')
     *
     * @param string|array $attribute_names
     * @param string $message
     */
    function validates_confirmation_of($attribute_names, $message = null) {
		$message = $this->get_error_message_for_validation($message, 'confirmation');
        foreach((array) $attribute_names as $attribute_name) {			
            $attribute_confirmation = $attribute_name . '_confirmation';
            if($this->$attribute_confirmation != $this->$attribute_name) {
                $attribute_human = Inflector::humanize($attribute_name);
			    $this->add_error("{$attribute_human} {$message}", $attribute_name);
            }
        }
    }

	/**
     * Validates that specified attributes are NOT in an array of elements.
     * eg. validates_exclusion_of('age, 'in' => array(13, 19))
     *
     * @param string|array $attribute_names
     * @param mixed $in array(1,2,3,4,5) or string 1..5
     * @param string $message
     */
    function validates_exclusion_of($attribute_names, $in = array(), $message = null) {
		$message = $this->get_error_message_for_validation($message, 'exclusion');
        foreach((array) $attribute_names as $attribute_name) {					
            if(is_string($in)) {
			    list($minimum, $maximum) = explode('..', $in);
			    if($this->$attribute_name >= $minimum && $this->$attribute_name <= $maximum) {
			        $attribute_human = Inflector::humanize($attribute_name);
			        $this->add_error("{$attribute_human} {$message}", $attribute_name);        
			    }
		    } elseif(is_array($in)) {
		        if(in_array($this->$attribute_name, $in)) {
		            $attribute_human = Inflector::humanize($attribute_name);
				    $this->add_error("{$attribute_human} {$message}", $attribute_name);
                }
            }   
        }
    }

	/**
     * Validates that specified attributes matches a regular expression
     * eg. validates_format_of('email', '/^(+)@((?:[-a-z0-9]+\.)+[a-z]{2,})$/i')
     *
     * @param string|array $attribute_names
     * @param string $regex
     * @param string $message
     */
    function validates_format_of($attribute_names, $regex, $message = null) {
		$message = $this->get_error_message_for_validation($message, 'invalid');		
        foreach((array) $attribute_names as $attribute_name) {								
			$value = $this->$attribute_name;		
			# Was there an error?
			if(!preg_match($regex, $value)) {
			    $attribute_human = Inflector::humanize($attribute_name);
				$this->add_error("{$attribute_human} {$message}", $attribute_name);
			}
        }
    }

	/**
     * Validates that specified attributes are in an array of elements.
     * eg. validates_inclusion_of('gender', array('m', 'f'))
     *
     * @param string|array $attribute_names
	 * @param mixed $in array(1,2,3,4,5) or string 1..5
     * @param string $message
     */
    function validates_inclusion_of($attribute_names, $in = array(), $message = null) {
		$message = $this->get_error_message_for_validation($message, 'inclusion');
        foreach((array) $attribute_names as $attribute_name) {					
            if(is_string($in)) {
			    list($minimum, $maximum) = explode('..', $in);
			    if(!($this->$attribute_name >= $minimum && $this->$attribute_name <= $maximum)) {
			        $attribute_human = Inflector::humanize($attribute_name);
			        $this->add_error("{$attribute_human} {$message}", $attribute_name);        
			    }
		    } elseif(is_array($in)) {
		        if(!in_array($this->$attribute_name, $in)) {
		            $attribute_human = Inflector::humanize($attribute_name);
				    $this->add_error("{$attribute_human} {$message}", $attribute_name);
                }
            } 
        }
    }

	/**
     * Validates that specified attributes are of some length
     * eg. validates_length_of('password', array('minimum' => 8))
     *
     * @param string|array $attribute_names
     * @param array $options
     */	
	function validates_length_of($attribute_names, $options = array(
		'too_short' => null, 'too_long' => null, 'wrong_length' => null, 'message' => null)) {        				
		# Convert 'in' to 'minimum' and 'maximum'
		if(isset($options['in'])) {
			list($options['minimum'], $options['maximum']) = explode('..', $options['in']);
		}
		# If 'message' is set see if we need to override other messages
		if(isset($options['message'])) {
		    if(!isset($options['too_short'])) $options['too_short'] = $options['message'];
		    if(!isset($options['too_long'])) $options['too_long'] = $options['message'];
		    if(!isset($options['wrong_length'])) $options['wrong_length'] = $options['message'];        
		}
				
		foreach((array) $attribute_names as $attribute_name) {			
			# Attribute string length
			$len = strlen($this->$attribute_name);
			$attribute_human = Inflector::humanize($attribute_name);
			
			# If you have set the min length option
			if(isset($options['minimum'])) {
				$message = $this->get_error_message_for_validation($options['too_short'], 'too_short', $options['minimum']);
				if($len < $options['minimum']) {
					$this->add_error("{$attribute_human} {$message}", $attribute_name);
				}
			}
			
			# If you have set the max length option
			if(isset($options['maximum'])) {
				$message = $this->get_error_message_for_validation($options['too_long'], 'too_long', $options['maximum']);
				if($len > $options['maximum']) {
					$this->add_error("{$attribute_human} {$message}", $attribute_name);
				}
			}
			
			# If you have set an exact length option
			if(isset($options['is'])) {
				$message = $this->get_error_message_for_validation($options['wrong_length'], 'wrong_length', $options['is']);
				if($len != $options['is']) {
					$this->add_error("{$attribute_human} {$message}", $attribute_name);
				}
			}
		}
	}

	/**
     * Validates that specified attributes are numbers
     * eg. validates_numericality_of('value')
     *
     * @param string|array $attribute_names
     * @param string $message
     */
    function validates_numericality_of($attribute_names, $message = null, $only_integer = false, $allow_null = false) {		 
        foreach((array) $attribute_names as $attribute_name) {	
            $value = $this->$attribute_name;				
			# Skip validation if you allow null
			if($allow_null && is_null($value)) {
				break;
			}			
			if($only_integer) {
				$message = $this->get_error_message_for_validation($message, 'not_an_integer');
				if(!is_integer($value)) {
				    $attribute_human = Inflector::humanize($attribute_name);
					$this->add_error("{$attribute_human} {$message}", $attribute_name);
				}
			} else {
				$message = $this->get_error_message_for_validation($message, 'not_a_number');
				if(!is_numeric($value)) {
				    $attribute_human = Inflector::humanize($attribute_name);
					$this->add_error("{$attribute_human} {$message}", $attribute_name);
				}
			}
        }
    }
		
	/**
     * Validates that specified attributes are not blank
     * eg. validates_presence_of(array('firstname', 'lastname'))
	 *
     * @param string|array $attribute_names
     * @param string $message
     */
    function validates_presence_of($attribute_names, $message = null) {
		$message = $this->get_error_message_for_validation($message, 'empty');		
        foreach((array) $attribute_names as $attribute_name) {				
            if($this->$attribute_name === '' || is_null($this->$attribute_name)) {
                $attribute_human = Inflector::humanize($attribute_name);
				$this->add_error("{$attribute_human} {$message}", $attribute_name);
            }
        }
    }
	
	/**
     * Validates that specified attributes are unique in the model database table
     * eg. validates_uniqueness_of('username')
     *
     * @param string|array $attribute_names
     * @param string $message
     */
    function validates_uniqueness_of($attribute_names, $message = null) {
		$message = $this->get_error_message_for_validation($message, 'taken');
        foreach((array) $attribute_names as $attribute_name) {				        
            $quoted_value = $this->quote_attribute($attribute_name);
			# Conditions for new and existing record
			if($this->new_record) {
				$conditions = sprintf("%s = %s", $attribute_name, $quoted_value);
			} else {
				$conditions = sprintf("%s = %s AND %s", $attribute_name, 
					$quoted_value, $this->get_primary_key_conditions("!="));
			}	
            if($this->find_first($conditions)) {
                $attribute_human = Inflector::humanize($attribute_name);
				$this->add_error("{$attribute_human} {$message}", $attribute_name);
            }
        }
    }
		
	/**
     * Return the error message for a validation function
     *
     * @param string $message
     * @param string $key
     * @param string $value
     * @return string
     */
	private function get_error_message_for_validation($message, $key, $value = null) {
		if(is_null($message)) {
		    # Return default error message
			return sprintf($this->default_error_messages[$key], $value);
		} else { 
		    # Return your custom error message
			return $message;
		}
	}

    /**
     *  Test whether argument is a PEAR Error object or a MDB2 Error object.
     *
     *  @param object $obj Object to test
     *  @return boolean  Whether object is one of these two errors
     */
    function is_error($obj) {
        if((PEAR::isError($obj)) || (MDB2::isError($obj))) {
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
        $error_message  = "Model Class: ".$this->get_class_name()."<br>";
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
        if(!is_null($key)) {
            $this->errors[$key] = $error;
        } else {
            $this->errors[] = $error;
        }
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
     *  Log SQL query in development mode
     *
     *  If running in development mode, log the query to self::$query_log
     *  @param string SQL to be logged
     */
    function log_query($query) {
        if(TRAX_ENV == "development" && $query) {
            self::$query_log[] = $query;       
        }    
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
