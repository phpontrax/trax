<?php
/**
 *  File for mock DB class
 *
 *  This file has the same name as the file holding the {@link
 *  http://pear.php.net/package/DB PEAR DB class}.
 *  To use the mock DB, put this file in the PHP include path ahead of
 *  the PEAR library, so that any class which requires DB.php will
 *  load this version.
 *
 * (PHP 5)
 *
 * @package PHPonTraxTest
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @copyright (c) Walter O. Haas 2006
 * @version $Id$
 * @author Walt Haas <haas@xmission.com>
 */

require_once 'PEAR.php';
require_once 'PHPUnit2/Framework/Assert.php';

/**
 * The code returned by many methods upon success
 */
define('DB_OK', 1);

/**
 * Unkown error
 */
define('DB_ERROR', -1);

/**
 * Syntax error
 */
define('DB_ERROR_SYNTAX', -2);

/**
 * Tried to insert a duplicate value into a primary or unique index
 */
define('DB_ERROR_CONSTRAINT', -3);

/**
 * An identifier in the query refers to a non-existant object
 */
define('DB_ERROR_NOT_FOUND', -4);

/**
 * Tried to create a duplicate object
 */
define('DB_ERROR_ALREADY_EXISTS', -5);

/**
 * The current driver does not support the action you attempted
 */
define('DB_ERROR_UNSUPPORTED', -6);

/**
 * The number of parameters does not match the number of placeholders
 */
define('DB_ERROR_MISMATCH', -7);

/**
 * A literal submitted did not match the data type expected
 */
define('DB_ERROR_INVALID', -8);

/**
 * The current DBMS does not support the action you attempted
 */
define('DB_ERROR_NOT_CAPABLE', -9);

/**
 * A literal submitted was too long so the end of it was removed
 */
define('DB_ERROR_TRUNCATED', -10);

/**
 * A literal number submitted did not match the data type expected
 */
define('DB_ERROR_INVALID_NUMBER', -11);

/**
 * A literal date submitted did not match the data type expected
 */
define('DB_ERROR_INVALID_DATE', -12);

/**
 * Attempt to divide something by zero
 */
define('DB_ERROR_DIVZERO', -13);

/**
 * A database needs to be selected
 */
define('DB_ERROR_NODBSELECTED', -14);

/**
 * Could not create the object requested
 */
define('DB_ERROR_CANNOT_CREATE', -15);

/**
 * Could not drop the database requested because it does not exist
 */
define('DB_ERROR_CANNOT_DROP', -17);

/**
 * An identifier in the query refers to a non-existant table
 */
define('DB_ERROR_NOSUCHTABLE', -18);

/**
 * An identifier in the query refers to a non-existant column
 */
define('DB_ERROR_NOSUCHFIELD', -19);

/**
 * The data submitted to the method was inappropriate
 */
define('DB_ERROR_NEED_MORE_DATA', -20);

/**
 * The attempt to lock the table failed
 */
define('DB_ERROR_NOT_LOCKED', -21);

/**
 * The number of columns doesn't match the number of values
 */
define('DB_ERROR_VALUE_COUNT_ON_ROW', -22);

/**
 * The DSN submitted has problems
 */
define('DB_ERROR_INVALID_DSN', -23);

/**
 * Could not connect to the database
 */
define('DB_ERROR_CONNECT_FAILED', -24);

/**
 * The PHP extension needed for this DBMS could not be found
 */
define('DB_ERROR_EXTENSION_NOT_FOUND',-25);

/**
 * The present user has inadequate permissions to perform the task requestd
 */
define('DB_ERROR_ACCESS_VIOLATION', -26);

/**
 * The database requested does not exist
 */
define('DB_ERROR_NOSUCHDB', -27);

/**
 * Tried to insert a null value into a column that doesn't allow nulls
 */
define('DB_ERROR_CONSTRAINT_NOT_NULL',-29);

/**
 * Identifiers for the placeholders used in prepared statements.
 * @see prepare()
 */

/**
 * Indicates a scalar (<kbd>?</kbd>) placeholder was used
 *
 * Quote and escape the value as necessary.
 */
define('DB_PARAM_SCALAR', 1);

/**
 * Indicates an opaque (<kbd>&</kbd>) placeholder was used
 *
 * The value presented is a file name.  Extract the contents of that file
 * and place them in this column.
 */
define('DB_PARAM_OPAQUE', 2);

/**
 * Indicates a misc (<kbd>!</kbd>) placeholder was used
 *
 * The value should not be quoted or escaped.
 */
define('DB_PARAM_MISC',   3);

/**
 * The different ways of returning binary data from queries.
 */

/**
 * Sends the fetched data straight through to output
 */
define('DB_BINMODE_PASSTHRU', 1);

/**
 * Lets you return data as usual
 */
define('DB_BINMODE_RETURN', 2);

/**
 * Converts the data to hex format before returning it
 *
 * For example the string "123" would become "313233".
 */
define('DB_BINMODE_CONVERT', 3);

/**
 * Fetchmode constants
 */
define('DB_FETCHMODE_DEFAULT', 0);
define('DB_FETCHMODE_ORDERED', 1);
define('DB_FETCHMODE_ASSOC', 2);
define('DB_FETCHMODE_OBJECT', 3);

/**
 * For multi-dimensional results, make the column name the first level
 * of the array and put the row number in the second level of the array
 *
 * This is flipped from the normal behavior, which puts the row numbers
 * in the first level of the array and the column names in the second level.
 */
define('DB_FETCHMODE_FLIPPED', 4);

/**
 * Old fetch modes.  Left here for compatibility.
 */
define('DB_GETMODE_ORDERED', DB_FETCHMODE_ORDERED);
define('DB_GETMODE_ASSOC',   DB_FETCHMODE_ASSOC);
define('DB_GETMODE_FLIPPED', DB_FETCHMODE_FLIPPED);

/**
 * The type of information to return from the tableInfo() method.
 *
 * Bitwised constants, so they can be combined using <kbd>|</kbd>
 * and removed using <kbd>^</kbd>.
 *
 * @see tableInfo()
 */
define('DB_TABLEINFO_ORDER', 1);
define('DB_TABLEINFO_ORDERTABLE', 2);
define('DB_TABLEINFO_FULL', 3);

/**
 * The type of query to create with the automatic query building methods.
 * @see autoPrepare(), autoExecute()
 */
define('DB_AUTOQUERY_INSERT', 1);
define('DB_AUTOQUERY_UPDATE', 2);

/**
 * Portability Modes.
 *
 * Bitwised constants, so they can be combined using <kbd>|</kbd>
 * and removed using <kbd>^</kbd>.
 *
 * @see setOption()
 */

/**
 * Turn off all portability features
 */
define('DB_PORTABILITY_NONE', 0);

/**
 * Convert names of tables and fields to lower case
 * when using the get*(), fetch*() and tableInfo() methods
 */
define('DB_PORTABILITY_LOWERCASE', 1);

/**
 * Right trim the data output by get*() and fetch*()
 */
define('DB_PORTABILITY_RTRIM', 2);

/**
 * Force reporting the number of rows deleted
 */
define('DB_PORTABILITY_DELETE_COUNT', 4);

/**
 * Enable hack that makes numRows() work in Oracle
 */
define('DB_PORTABILITY_NUMROWS', 8);

/**
 * Makes certain error messages in certain drivers compatible
 * with those from other DBMS's
 *
 * + mysql, mysqli:  change unique/primary key constraints
 *   DB_ERROR_ALREADY_EXISTS -> DB_ERROR_CONSTRAINT
 *
 * + odbc(access):  MS's ODBC driver reports 'no such field' as code
 *   07001, which means 'too few parameters.'  When this option is on
 *   that code gets mapped to DB_ERROR_NOSUCHFIELD.
 */
define('DB_PORTABILITY_ERRORS', 16);

/**
 * Convert null values to empty strings in data output by
 * get*() and fetch*()
 */
define('DB_PORTABILITY_NULL_TO_EMPTY', 32);

/**
 * Turn on all portability features
 */
define('DB_PORTABILITY_ALL', 63);

/**
 *  Mock DB class for testing
 *
 *  This class is a mock version of the
 *  {@link http://pear.php.net/package/DB PEAR DB class}.  It is
 *  intended to provide the same interface as the real DB class, plus
 *  a small database sufficient to test software.
 */

class DB {

    /**
     * Create a new DB object for the specified database type but don't
     * connect to the database
     *
     * @param string $type     the database type (eg "mysql")
     * @param array  $options  an associative array of option names and values
     * @return object  a new DB object.  A DB_Error object on failure.
     * @see DB_common::setOption()
     *  @todo Implement mock DB::factory
     */
    public function &factory($type, $options = false)
    {
//        if (!is_array($options)) {
//            $options = array('persistent' => $options);
//        }
//
//        if (isset($options['debug']) && $options['debug'] >= 2) {
//            // expose php errors with sufficient debug level
//            include_once "DB/{$type}.php";
//        } else {
//            @include_once "DB/{$type}.php";
//        }
//
//        $classname = "DB_${type}";
//
//        if (!class_exists($classname)) {
//            $tmp = PEAR::raiseError(null, DB_ERROR_NOT_FOUND, null, null,
//                                    "Unable to include the DB/{$type}.php"
//                                    . " file for '$dsn'",
//                                    'DB_Error', true);
//            return $tmp;
//        }
//
//        @$obj =& new $classname;
//
//        foreach ($options as $option => $value) {
//            $test = $obj->setOption($option, $value);
//            if (DB::isError($test)) {
//                return $test;
//            }
//        }
//
//        return $obj;
    }

    /**
     * Create a new DB object including a connection to the specified database
     *
     * @param mixed $dsn      the string "data source name" or array in the
     *                         format returned by DB::parseDSN()
     * @param array $options  an associative array of option names and values
     * @return object  a new DB object.  A DB_Error object on failure.
     * @uses DB::parseDSN(), DB_common::setOption(), PEAR::isError()
     *  @todo Implement mock DB::connect
     */
    function &connect($dsn, $options = array())
    {
        $dsninfo = DB::parseDSN($dsn);
        $type = $dsninfo['phptype'];

        // only support MySQL at the moment
        PHPUnit2_Framework_Assert::assertEquals($type,'mysql');
        @$obj =& new DB_mysql;

        foreach ($options as $option => $value) {
            $test = $obj->setOption($option, $value);
            if (DB::isError($test)) {
                return $test;
            }
        }

//        $err = $obj->connect($dsninfo, $obj->getOption('persistent'));
//        if (DB::isError($err)) {
//            $err->addUserInfo($dsn);
//            return $err;
//        }
//
        return $obj;
    }

    /**
     * Return the DB API version
     *
     * @return string  the DB API version number
     */
    function apiVersion()
    {
        return '1.7.6';
    }

    /**
     * Determines if a variable is a DB_Error object
     *
     * @param mixed $value  the variable to check
     * @return bool  whether $value is DB_Error object
     */
    function isError($value)
    {
        return is_a($value, 'DB_Error');
    }

    /**
     * Determines if a value is a DB_<driver> object
     *
     * @param mixed $value  the value to test
     * @return bool  whether $value is a DB_<driver> object
     *  @todo Implement mock DB::isConnection
     */
    function isConnection($value)
    {
//        return (is_object($value) &&
//                is_subclass_of($value, 'db_common') &&
//                method_exists($value, 'simpleQuery'));
    }

    /**
     * Tell whether a query is a data manipulation or data definition query
     *
     * @param string $query  the query
     * @return boolean  whether $query is a data manipulation query
     */
    function isManip($query)
    {
        $manips = 'INSERT|UPDATE|DELETE|REPLACE|'
                . 'CREATE|DROP|'
                . 'LOAD DATA|SELECT .* INTO|COPY|'
                . 'ALTER|GRANT|REVOKE|'
                . 'LOCK|UNLOCK';
        if (preg_match('/^\s*"?(' . $manips . ')\s+/i', $query)) {
            return true;
        }
        return false;
    }

    /**
     * Return a textual error message for a DB error code
     *
     * @param integer $value  the DB error code
     * @return string  the error message or false if the error code was
     *                  not recognized
     *  @todo Implement mock DB::errorMessage
     */
    public function errorMessage($value)
    {
        static $errorMessages;
        if (!isset($errorMessages)) {
            $errorMessages = array(
                DB_ERROR                    => 'unknown error',
                DB_ERROR_ACCESS_VIOLATION   => 'insufficient permissions',
                DB_ERROR_ALREADY_EXISTS     => 'already exists',
                DB_ERROR_CANNOT_CREATE      => 'can not create',
                DB_ERROR_CANNOT_DROP        => 'can not drop',
                DB_ERROR_CONNECT_FAILED     => 'connect failed',
                DB_ERROR_CONSTRAINT         => 'constraint violation',
                DB_ERROR_CONSTRAINT_NOT_NULL=> 'null value violates not-null constraint',
                DB_ERROR_DIVZERO            => 'division by zero',
                DB_ERROR_EXTENSION_NOT_FOUND=> 'extension not found',
                DB_ERROR_INVALID            => 'invalid',
                DB_ERROR_INVALID_DATE       => 'invalid date or time',
                DB_ERROR_INVALID_DSN        => 'invalid DSN',
                DB_ERROR_INVALID_NUMBER     => 'invalid number',
                DB_ERROR_MISMATCH           => 'mismatch',
                DB_ERROR_NEED_MORE_DATA     => 'insufficient data supplied',
                DB_ERROR_NODBSELECTED       => 'no database selected',
                DB_ERROR_NOSUCHDB           => 'no such database',
                DB_ERROR_NOSUCHFIELD        => 'no such field',
                DB_ERROR_NOSUCHTABLE        => 'no such table',
                DB_ERROR_NOT_CAPABLE        => 'DB backend not capable',
                DB_ERROR_NOT_FOUND          => 'not found',
                DB_ERROR_NOT_LOCKED         => 'not locked',
                DB_ERROR_SYNTAX             => 'syntax error',
                DB_ERROR_UNSUPPORTED        => 'not supported',
                DB_ERROR_TRUNCATED          => 'truncated',
                DB_ERROR_VALUE_COUNT_ON_ROW => 'value count on row',
                DB_OK                       => 'no error',
            );
        }

//        if (DB::isError($value)) {
//            $value = $value->getCode();
//        }
//
//        return isset($errorMessages[$value]) ? $errorMessages[$value]
//                     : $errorMessages[DB_ERROR];
    }

    /**
     * Parse a data source name
     *
     * @param string $dsn Data Source Name to be parsed
     * @return array an associative array with the following keys:
     *  + phptype:  Database backend used in PHP (mysql, odbc etc.)
     *  + dbsyntax: Database used with regards to SQL syntax etc.
     *  + protocol: Communication protocol to use (tcp, unix etc.)
     *  + hostspec: Host specification (hostname[:port])
     *  + database: Database to use on the DBMS server
     *  + username: User name for login
     *  + password: Password for login
     *  @todo Implement mock DB::parseDSN
     */
    public function parseDSN($dsn)
    {
        $parsed = array(
            'phptype'  => false,
            'dbsyntax' => false,
            'username' => false,
            'password' => false,
            'protocol' => false,
            'hostspec' => false,
            'port'     => false,
            'socket'   => false,
            'database' => false,
        );

        if (is_array($dsn)) {
            $dsn = array_merge($parsed, $dsn);
            if (!$dsn['dbsyntax']) {
                $dsn['dbsyntax'] = $dsn['phptype'];
            }
            return $dsn;
        }

        // Find phptype and dbsyntax
        if (($pos = strpos($dsn, '://')) !== false) {
            $str = substr($dsn, 0, $pos);
            $dsn = substr($dsn, $pos + 3);
        } else {
            $str = $dsn;
            $dsn = null;
        }

        // Get phptype and dbsyntax
        // $str => phptype(dbsyntax)
        if (preg_match('|^(.+?)\((.*?)\)$|', $str, $arr)) {
            $parsed['phptype']  = $arr[1];
            $parsed['dbsyntax'] = !$arr[2] ? $arr[1] : $arr[2];
        } else {
            $parsed['phptype']  = $str;
            $parsed['dbsyntax'] = $str;
        }

        if (!count($dsn)) {
            return $parsed;
        }

        // Get (if found): username and password
        // $dsn => username:password@protocol+hostspec/database
        if (($at = strrpos($dsn,'@')) !== false) {
            $str = substr($dsn, 0, $at);
            $dsn = substr($dsn, $at + 1);
            if (($pos = strpos($str, ':')) !== false) {
                $parsed['username'] = rawurldecode(substr($str, 0, $pos));
                $parsed['password'] = rawurldecode(substr($str, $pos + 1));
            } else {
                $parsed['username'] = rawurldecode($str);
            }
        }

        // Find protocol and hostspec

        if (preg_match('|^([^(]+)\((.*?)\)/?(.*?)$|', $dsn, $match)) {
            // $dsn => proto(proto_opts)/database
            $proto       = $match[1];
            $proto_opts  = $match[2] ? $match[2] : false;
            $dsn         = $match[3];

        } else {
            // $dsn => protocol+hostspec/database (old format)
            if (strpos($dsn, '+') !== false) {
                list($proto, $dsn) = explode('+', $dsn, 2);
            }
            if (strpos($dsn, '/') !== false) {
                list($proto_opts, $dsn) = explode('/', $dsn, 2);
            } else {
                $proto_opts = $dsn;
                $dsn = null;
            }
        }

        // process the different protocol options
        $parsed['protocol'] = (!empty($proto)) ? $proto : 'tcp';
        $proto_opts = rawurldecode($proto_opts);
        if ($parsed['protocol'] == 'tcp') {
            if (strpos($proto_opts, ':') !== false) {
                list($parsed['hostspec'],
                     $parsed['port']) = explode(':', $proto_opts);
            } else {
                $parsed['hostspec'] = $proto_opts;
            }
        } elseif ($parsed['protocol'] == 'unix') {
            $parsed['socket'] = $proto_opts;
        }

        // Get dabase if any
        // $dsn => database
        if ($dsn) {
            if (($pos = strpos($dsn, '?')) === false) {
                // /database
                $parsed['database'] = rawurldecode($dsn);
            } else {
                // /database?param1=value1&param2=value2
                $parsed['database'] = rawurldecode(substr($dsn, 0, $pos));
                $dsn = substr($dsn, $pos + 1);
                if (strpos($dsn, '&') !== false) {
                    $opts = explode('&', $dsn);
                } else { // database?param1=value1
                    $opts = array($dsn);
                }
                foreach ($opts as $opt) {
                    list($key, $value) = explode('=', $opt);
                    if (!isset($parsed[$key])) {
                        // don't allow params overwrite
                        $parsed[$key] = rawurldecode($value);
                    }
                }
            }
        }

        return $parsed;
    }
}

/**
 *  Mock DB_common for testing
 *  @todo Implement mock DB_common class
 */
class DB_common extends PEAR {

    /**
     *  Mock Database
     */
    protected static $database =
        array('person_names' =>
              array('info' =>
                    array(array('table' => 'person_names',
                                'name'  => 'id',
                                'type'  => 'int',
                                'len'   => '11',
                                'flags' => 'primary_key not_null'),
                          array('table' => 'person_names',
                                'name'  => 'prefix',
                                'type'  => 'string',
                                'len'   => '20',
                                'flags' => ''),
                          array('table' => 'person_names',
                                'name'  => 'first_name',
                                'type'  => 'string',
                                'len'   => '40',
                                'flags' => ''),
                          array('table' => 'person_names',
                                'name'  => 'mi',
                                'type'  => 'string',
                                'len'   => '1',
                                'flags' => ''),
                          array('table' => 'person_names',
                                'name'  => 'last_name',
                                'type'  => 'string',
                                'len'   => '40',
                                'flags' => ''),
                          array('table' => 'person_names',
                                'name'  => 'suffix',
                                'type'  => 'string',
                                'len'   => '20',
                                'flags' => ''),
                          ),
                    'data' =>
                    array()
                    )
              );

    /**
     * Run-time configuration options
     *
     * @var array
     * @see DB_common::setOption()
     */
    var $options = array(
        'result_buffering' => 500,
        'persistent' => false,
        'ssl' => false,
        'debug' => 0,
        'seqname_format' => '%s_seq',
        'autofree' => false,
        'portability' => DB_PORTABILITY_NONE,
        'optimize' => 'performance',  // Deprecated.  Use 'portability'.
    );

    /**
     *  List of expected queries and returns
     */
    private $expected_list = null;

    /**
     *  Cursor in list of expected queries and returns
     */
    private $expected_list_cursor = null;

    /**
     *  Expected query
     *  @var string
     */
    private $expected_query = null;

    /**
     *  Result to be returned from expected query
     *  @var string
     */
    private $expected_result = null;

    /**
     * This constructor calls <kbd>$this->PEAR('DB_Error')</kbd>
     *
     * @return void
     */
    function DB_common()
    {
        $this->PEAR('DB_Error');
    }

    /**
     * Automatically indicates which properties should be saved
     * when PHP's serialize() function is called
     *
     * @return array  the array of properties names that should be saved
     *  @todo Implement mock DB_common::__sleep
     */
    function __sleep()
    {
//        if ($this->connection) {
//            // Don't disconnect(), people use serialize() for many reasons
//            $this->was_connected = true;
//        } else {
//            $this->was_connected = false;
//        }
//        if (isset($this->autocommit)) {
//            return array('autocommit',
//                         'dbsyntax',
//                         'dsn',
//                         'features',
//                         'fetchmode',
//                         'fetchmode_object_class',
//                         'options',
//                         'was_connected',
//                   );
//        } else {
//            return array('dbsyntax',
//                         'dsn',
//                         'features',
//                         'fetchmode',
//                         'fetchmode_object_class',
//                         'options',
//                         'was_connected',
//                   );
//        }
    }

    /**
     * Automatically reconnects to the database when PHP's unserialize()
     * function is called
     *
     * @return void
     *  @todo Implement mock DB_common::__wakeup
     */
    function __wakeup()
    {
//        if ($this->was_connected) {
//            $this->connect($this->dsn, $this->options);
//        }
    }

    /**
     * Automatic string conversion for PHP 5
     *
     * @return string  a string describing the current PEAR DB object
     *  @todo Implement mock DB_common::__toString
     */
    public function __toString()
    {
//        $info = strtolower(get_class($this));
//        $info .=  ': (phptype=' . $this->phptype .
//                  ', dbsyntax=' . $this->dbsyntax .
//                  ')';
//        if ($this->connection) {
//            $info .= ' [connected]';
//        }
//        return $info;
    }

    /**
     * Quotes a string so it can be safely used as a table or column name
     *
     * @param string $str  the identifier name to be quoted
     * @return string  the quoted identifier
     */
    public function quoteIdentifier($str)
    {
        return '"' . str_replace('"', '""', $str) . '"';
    }

    /**
     * Formats input so it can be safely used in a query
     *
     * @see DB_common::escapeSimple()
     *  @todo Implement mock DB_common::quoteSmart
     */
    public function quoteSmart($in)
    {
//        if (is_int($in) || is_double($in)) {
//            return $in;
//        } elseif (is_bool($in)) {
//            return $in ? 1 : 0;
//        } elseif (is_null($in)) {
//            return 'NULL';
//        } else {
//            return "'" . $this->escapeSimple($in) . "'";
//        }
    }

    /**
     * Escapes a string according to the current DBMS's standards
     *
     * @param string $str  the string to be escaped
     * @return string  the escaped string
     * @see DB_common::quoteSmart()
     *  @todo Implement mock DB_common::escapeSimple
     */
    public function escapeSimple($str)
    {
//        return str_replace("'", "''", $str);
    }

    /**
     * Tells whether the present driver supports a given feature
     *
     * @param string $feature  the feature you're curious about
     * @return bool  whether this driver supports $feature
     *  @todo Implement mock DB_common::provides
     */
    public function provides($feature)
    {
//        return $this->features[$feature];
    }

    /**
     * Sets the fetch mode that should be used by default for query results
     *
     * @param integer $fetchmode    DB_FETCHMODE_ORDERED, DB_FETCHMODE_ASSOC
     *                               or DB_FETCHMODE_OBJECT
     * @param string $object_class  the class name of the object to be returned
     *                               by the fetch methods when the
     *                               DB_FETCHMODE_OBJECT mode is selected.
     *                               If no class is specified by default a cast
     *                               to object from the assoc array row will be
     *                               done.  There is also the posibility to use
     *                               and extend the 'DB_row' class.
     *
     * @see DB_FETCHMODE_ORDERED, DB_FETCHMODE_ASSOC, DB_FETCHMODE_OBJECT
     *  @todo Implement mock DB_common::setFetchMode
     */
    public function setFetchMode($fetchmode, $object_class = 'stdClass')
    {
//        switch ($fetchmode) {
//            case DB_FETCHMODE_OBJECT:
//                $this->fetchmode_object_class = $object_class;
//            case DB_FETCHMODE_ORDERED:
//            case DB_FETCHMODE_ASSOC:
//                $this->fetchmode = $fetchmode;
//                break;
//            default:
//                return $this->raiseError('invalid fetchmode mode');
//        }
    }

    /**
     * Sets run-time configuration options for PEAR DB
     *
     * @param string $option option name
     * @param mixed  $value value for the option
     * @return int  DB_OK on success.  A DB_Error object on failure.
     * @see DB_common::$options
     *  @todo Implement mock DB_common::setOption
     */
    public function setOption($option, $value)
    {
        if (isset($this->options[$option])) {
            $this->options[$option] = $value;
            return DB_OK;
        }
        PHPUnit2_Framework_Assert::fail("DB_common::setOption called"
                                        ." with unknown option $option");
    }

    /**
     * Returns the value of an option
     *
     * @param string $option  the option name you're curious about
     * @return mixed  the option's value
     *  @todo Implement mock DB_common::getOption
     */
    public function getOption($option)
    {
//        if (isset($this->options[$option])) {
//            return $this->options[$option];
//        }
//        return $this->raiseError("unknown option $option");
    }

    /**
     * Prepares a query for multiple execution with execute()
     *
     * @param string $query  the query to be prepared
     * @return mixed  DB statement resource on success. A DB_Error object
     *                 on failure.
     * @see DB_common::execute()
     *  @todo Implement mock DB_common::prepare
     */
    public function prepare($query)
    {
        PHPUnit2_Framework_Assert::fail("DB does not support"
                                        . " multiple execution");
//        $tokens   = preg_split('/((?<!\\\)[&?!])/', $query, -1,
//                               PREG_SPLIT_DELIM_CAPTURE);
//        $token     = 0;
//        $types     = array();
//        $newtokens = array();
//
//        foreach ($tokens as $val) {
//            switch ($val) {
//                case '?':
//                    $types[$token++] = DB_PARAM_SCALAR;
//                    break;
//                case '&':
//                    $types[$token++] = DB_PARAM_OPAQUE;
//                    break;
//                case '!':
//                    $types[$token++] = DB_PARAM_MISC;
//                    break;
//                default:
//                    $newtokens[] = preg_replace('/\\\([&?!])/', "\\1", $val);
//            }
//        }
//
//        $this->prepare_tokens[] = &$newtokens;
//        end($this->prepare_tokens);
//
//        $k = key($this->prepare_tokens);
//        $this->prepare_types[$k] = $types;
//        $this->prepared_queries[$k] = implode(' ', $newtokens);
//
//        return $k;
    }


    /**
     * Automaticaly generates an insert or update query and pass it to
     * prepare() 
     *
     * @param string $table         the table name
     * @param array  $table_fields  the array of field names
     * @param int    $mode          a type of query to make:
     *                               DB_AUTOQUERY_INSERT or DB_AUTOQUERY_UPDATE
     * @param string $where         for update queries: the WHERE clause to
     *                               append to the SQL statement.  Don't
     *                               include the "WHERE" keyword.
     *
     * @return resource  the query handle
     * @uses DB_common::prepare(), DB_common::buildManipSQL()
     *  @todo Implement mock DB_common::autoPrepare
     */
    public function autoPrepare($table, $table_fields, $mode = DB_AUTOQUERY_INSERT,
                         $where = false)
    {
//        $query = $this->buildManipSQL($table, $table_fields, $mode, $where);
//        if (DB::isError($query)) {
//            return $query;
//        }
//        return $this->prepare($query);
    }

    /**
     * Automaticaly generates an insert or update query and call prepare()
     * and execute() with it
     *
     * @param string $table         the table name
     * @param array  $fields_values the associative array where $key is a
     *                               field name and $value its value
     * @param int    $mode          a type of query to make:
     *                               DB_AUTOQUERY_INSERT or DB_AUTOQUERY_UPDATE
     * @param string $where         for update queries: the WHERE clause to
     *                               append to the SQL statement.  Don't
     *                               include the "WHERE" keyword.
     *
     * @return mixed  a new DB_result object for successful SELECT queries
     *                 or DB_OK for successul data manipulation queries.
     *                 A DB_Error object on failure.
     *
     * @uses DB_common::autoPrepare(), DB_common::execute()
     *  @todo Implement mock DB_common::autoExecute
     */
    public function autoExecute($table, $fields_values, $mode = DB_AUTOQUERY_INSERT,
                         $where = false)
    {
        PHPUnit2_Framework_Assert::fail("DB does not support"
                                        . " multiple execution");
//        $sth = $this->autoPrepare($table, array_keys($fields_values), $mode,
//                                  $where);
//        if (DB::isError($sth)) {
//            return $sth;
//        }
//        $ret =& $this->execute($sth, array_values($fields_values));
//        $this->freePrepared($sth);
//        return $ret;
    }

    /**
     * Produces an SQL query string for autoPrepare()
     *
     * @param string $table         the table name
     * @param array  $table_fields  the array of field names
     * @param int    $mode          a type of query to make:
     *                               DB_AUTOQUERY_INSERT or DB_AUTOQUERY_UPDATE
     * @param string $where         for update queries: the WHERE clause to
     *                               append to the SQL statement.  Don't
     *                               include the "WHERE" keyword.
     *
     * @return string  the sql query for autoPrepare()
     *  @todo Implement mock DB_common::buildManipSQL
     */
    public function buildManipSQL($table, $table_fields, $mode, $where = false)
    {
//        if (count($table_fields) == 0) {
//            return $this->raiseError(DB_ERROR_NEED_MORE_DATA);
//        }
//        $first = true;
//        switch ($mode) {
//            case DB_AUTOQUERY_INSERT:
//                $values = '';
//                $names = '';
//                foreach ($table_fields as $value) {
//                    if ($first) {
//                        $first = false;
//                    } else {
//                        $names .= ',';
//                        $values .= ',';
//                    }
//                    $names .= $value;
//                    $values .= '?';
//                }
//                return "INSERT INTO $table ($names) VALUES ($values)";
//            case DB_AUTOQUERY_UPDATE:
//                $set = '';
//                foreach ($table_fields as $value) {
//                    if ($first) {
//                        $first = false;
//                    } else {
//                        $set .= ',';
//                    }
//                    $set .= "$value = ?";
//                }
//                $sql = "UPDATE $table SET $set";
//                if ($where) {
//                    $sql .= " WHERE $where";
//                }
//                return $sql;
//            default:
//                return $this->raiseError(DB_ERROR_SYNTAX);
//        }
    }

    /**
     * Executes a DB statement prepared with prepare()
     *
     * @param resource $stmt  a DB statement resource returned from prepare()
     * @param mixed    $data  array, string or numeric data to be used in
     *                         execution of the statement.  Quantity of items
     *                         passed must match quantity of placeholders in
     *                         query:  meaning 1 placeholder for non-array
     *                         parameters or 1 placeholder per array element.
     *
     * @return mixed  a new DB_result object for successful SELECT queries
     *                 or DB_OK for successul data manipulation queries.
     *                 A DB_Error object on failure.
     *
     * @see DB_common::prepare()
     *  @todo Implement mock DB_common::execute
     */
    public function &execute($stmt, $data = array())
    {
        PHPUnit2_Framework_Assert::fail("DB does not support"
                                        . " multiple execution");
//        $realquery = $this->executeEmulateQuery($stmt, $data);
//        if (DB::isError($realquery)) {
//            return $realquery;
//        }
//        $result = $this->simpleQuery($realquery);
//
//        if ($result === DB_OK || DB::isError($result)) {
//            return $result;
//        } else {
//            $tmp =& new DB_result($this, $result);
//            return $tmp;
//        }
    }

    /**
     * Emulates executing prepared statements if the DBMS not support them
     *
     * @param resource $stmt  a DB statement resource returned from execute()
     * @param mixed    $data  array, string or numeric data to be used in
     *                         execution of the statement.  Quantity of items
     *                         passed must match quantity of placeholders in
     *                         query:  meaning 1 placeholder for non-array
     *                         parameters or 1 placeholder per array element.
     *
     * @return mixed  a string containing the real query run when emulating
     *                 prepare/execute.  A DB_Error object on failure.
     *
     * @see DB_common::execute()
     *  @todo Implement mock DB_common::executeEmulateQuery
     */
    protected function executeEmulateQuery($stmt, $data = array())
    {
        PHPUnit2_Framework_Assert::fail("DB does not support"
                                        . " multiple execution");
//        $stmt = (int)$stmt;
//        $data = (array)$data;
//        $this->last_parameters = $data;
//
//        if (count($this->prepare_types[$stmt]) != count($data)) {
//            $this->last_query = $this->prepared_queries[$stmt];
//            return $this->raiseError(DB_ERROR_MISMATCH);
//        }
//
//        $realquery = $this->prepare_tokens[$stmt][0];
//
//        $i = 0;
//        foreach ($data as $value) {
//            if ($this->prepare_types[$stmt][$i] == DB_PARAM_SCALAR) {
//                $realquery .= $this->quoteSmart($value);
//            } elseif ($this->prepare_types[$stmt][$i] == DB_PARAM_OPAQUE) {
//                $fp = @fopen($value, 'rb');
//                if (!$fp) {
//                    return $this->raiseError(DB_ERROR_ACCESS_VIOLATION);
//                }
//                $realquery .= $this->quoteSmart(fread($fp, filesize($value)));
//                fclose($fp);
//            } else {
//                $realquery .= $value;
//            }
//
//            $realquery .= $this->prepare_tokens[$stmt][++$i];
//        }
//
//        return $realquery;
    }

    /**
     * Performs several execute() calls on the same statement handle
     *
     * @param resource $stmt  query handle from prepare()
     * @param array    $data  numeric array containing the
     *                         data to insert into the query
     * @return int  DB_OK on success.  A DB_Error object on failure.
     * @see DB_common::prepare(), DB_common::execute()
     *  @todo Implement mock DB_common::executeMultiple
     */
    function executeMultiple($stmt, $data)
    {
        PHPUnit2_Framework_Assert::fail("DB does not support"
                                        . " multiple execution");
//        foreach ($data as $value) {
//            $res =& $this->execute($stmt, $value);
//            if (DB::isError($res)) {
//                return $res;
//            }
//        }
//        return DB_OK;
    }

    /**
     * Frees the internal resources associated with a prepared query
     *
     * @param resource $stmt           the prepared statement's PHP resource
     * @param bool     $free_resource  should the PHP resource be freed too?
     *                                  Use false if you need to get data
     *                                  from the result set later.
     * @return bool  TRUE on success, FALSE if $result is invalid
     * @see DB_common::prepare()
     *  @todo Implement mock DB_common::freePrepared
     */
    function freePrepared($stmt, $free_resource = true)
    {
        PHPUnit2_Framework_Assert::fail("DB does not support"
                                        . " multiple execution");
//        $stmt = (int)$stmt;
//        if (isset($this->prepare_tokens[$stmt])) {
//            unset($this->prepare_tokens[$stmt]);
//            unset($this->prepare_types[$stmt]);
//            unset($this->prepared_queries[$stmt]);
//            return true;
//        }
//        return false;
    }

    /**
     * Changes a query string for various DBMS specific reasons
     *
     * @param string $query  the query string to modify
     * @return string  the modified query string
     * @see DB_mysql::modifyQuery(), DB_oci8::modifyQuery(),
     *      DB_sqlite::modifyQuery()
     *  @todo Implement mock DB_common::modifyQuery
     */
    protected function modifyQuery($query)
    {
//        return $query;
    }

    /**
     * Adds LIMIT clauses to a query string according to current DBMS standards
     *
     * @param string $query   the query to modify
     * @param int    $from    the row to start to fetching (0 = the first row)
     * @param int    $count   the numbers of rows to fetch
     * @param mixed  $params  array, string or numeric data to be used in
     *                         execution of the statement.  Quantity of items
     *                         passed must match quantity of placeholders in
     *                         query:  meaning 1 placeholder for non-array
     *                         parameters or 1 placeholder per array element.
     *
     * @return string  the query string with LIMIT clauses added
     *  @todo Implement mock DB_common::modifyLimitQuery
     */
    protected function modifyLimitQuery($query, $from, $count, $params = array())
    {
//        return $query;
    }

    /**
     *  Set expected query and return
     *
     *  This is a test routine that does not exist in the PEAR DB package.
     *  @param string $expected Expected query
     *  @param string $result Result to be returned when expected
     *  query is received.
     */
    public function expect_query($expected, $result) {
        $this->expected_query = $expected;
        $this->expected_result = $result;
    }

    /**
     *  Set list of expected queries and returns
     *
     *  This is a test routine that does not exist in the PEAR DB package.
     *  @param string $list Expected queries and returns
     */
    public function expect_queries($list) {
        $this->expected_list = $list;
        $this->expected_list_cursor = 0;
        $this->expect_query($this->expected_list[0]['query'],
                            $this->expected_list[0]['result']);
    }

    /**
     *  Verify that all expected queries have been received
     *
     *  This is a test routine that does not exist in the PEAR DB package.
     */
    public function tally_queries() {
        if ($this->expected_list_cursor < count($this->expected_list)) {
            PHPUnit2_Framework_Assert::fail("DB_mysql::expected query was"
                          ." not received. expected $this->expected_query");
        }
    }

    /**
     * Sends a query to the database server
     *
     * @param string $query   the SQL query or the statement to prepare
     * @param mixed  $params  array, string or numeric data to be used in
     *                         execution of the statement.  Quantity of items
     *                         passed must match quantity of placeholders in
     *                         query:  meaning 1 placeholder for non-array
     *                         parameters or 1 placeholder per array element.
     *
     * @return mixed  a new DB_result object for successful SELECT queries
     *                 or DB_OK for successul data manipulation queries.
     *                 A DB_Error object on failure.
     *
     * @see DB_result, DB_common::prepare(), DB_common::execute()
     *  @todo Implement mock DB_common::query
     */
    public function &query($query, $params = array())
    {
        $params = (array)$params;
        if (sizeof($params) > 0) {
            PHPUnit2_Framework_Assert::fail("DB does not support"
                                            . " multiple execution");
        }
        if (!is_null($this->expected_list)) {
            //  We are working through a list of queries.  If the
            //  number of queries received is greater than the number
            //  on the list, that's an error
            if ($this->expected_list_cursor >= count($this->expected_list)) {
                PHPUnit2_Framework_Assert::fail(
                              "DB_mysql::query called with"
                             ."$query, exceeding number of queries expected");
                }            
        }
        if ($query != $this->expected_query) {
            PHPUnit2_Framework_Assert::fail('DB_mysql::query() called with'
                 .' "'.$query.'", expected "'.$this->expected_query.'"');
        }
        $result = $this->expected_result;
        if (!is_null($this->expected_list)) {
            //  More queries are expected.  Advance the cursor
            $this->expected_list_cursor++;
            $this->expect_query(
              $this->expected_list[$this->expected_list_cursor]['query'],
              $this->expected_list[$this->expected_list_cursor]['result']);
        }
        return $result;
//        if (sizeof($params) > 0) {
//            $sth = $this->prepare($query);
//            if (DB::isError($sth)) {
//                return $sth;
//            }
//            $ret =& $this->execute($sth, $params);
//            $this->freePrepared($sth, false);
//            return $ret;
//        } else {
//            $this->last_parameters = array();
//            $result = $this->simpleQuery($query);
//            if ($result === DB_OK || DB::isError($result)) {
//                return $result;
//            } else {
//                $tmp =& new DB_result($this, $result);
//                return $tmp;
//            }
//        }
    }

    /**
     * Generates and executes a LIMIT query
     *
     * @param string $query   the query
     * @param intr   $from    the row to start to fetching (0 = the first row)
     * @param int    $count   the numbers of rows to fetch
     * @param mixed  $params  array, string or numeric data to be used in
     *                         execution of the statement.  Quantity of items
     *                         passed must match quantity of placeholders in
     *                         query:  meaning 1 placeholder for non-array
     *                         parameters or 1 placeholder per array element.
     *
     * @return mixed  a new DB_result object for successful SELECT queries
     *                 or DB_OK for successul data manipulation queries.
     *                 A DB_Error object on failure.
     *  @todo Implement mock DB_common::limitQuery
     */
    public function &limitQuery($query, $from, $count, $params = array())
    {
//        $query = $this->modifyLimitQuery($query, $from, $count, $params);
//        if (DB::isError($query)){
//            return $query;
//        }
//        $result =& $this->query($query, $params);
//        if (is_a($result, 'DB_result')) {
//            $result->setOption('limit_from', $from);
//            $result->setOption('limit_count', $count);
//        }
//        return $result;
    }

    /**
     * Fetches the first column of the first row from a query result
     *
     * @param string $query   the SQL query
     * @param mixed  $params  array, string or numeric data to be used in
     *                         execution of the statement.  Quantity of items
     *                         passed must match quantity of placeholders in
     *                         query:  meaning 1 placeholder for non-array
     *                         parameters or 1 placeholder per array element.
     *
     * @return mixed  the returned value of the query.
     *                 A DB_Error object on failure.
     *  @todo Implement mock DB_common::getOne
     */
    function &getOne($query, $params = array())
    {
        return $this->query($query,$params);

//            $sth = $this->prepare($query);
//            if (DB::isError($sth)) {
//                return $sth;
//            }
//            $res =& $this->execute($sth, $params);
//            $this->freePrepared($sth);
//        } else {
//            $res =& $this->query($query);
//        }
//
//        if (DB::isError($res)) {
//            return $res;
//        }
//
//        $err = $res->fetchInto($row, DB_FETCHMODE_ORDERED);
//        $res->free();
//
//        if ($err !== DB_OK) {
//            return $err;
//        }
//
//        return $row[0];
    }

    /**
     * Fetches the first row of data returned from a query result
     *
     * @param string $query   the SQL query
     * @param mixed  $params  array, string or numeric data to be used in
     *                         execution of the statement.  Quantity of items
     *                         passed must match quantity of placeholders in
     *                         query:  meaning 1 placeholder for non-array
     *                         parameters or 1 placeholder per array element.
     * @param int $fetchmode  the fetch mode to use
     *
     * @return array  the first row of results as an array.
     *                 A DB_Error object on failure.
     *  @todo Implement mock DB_common::getRow
     */
    public function &getRow($query, $params = array(),
                     $fetchmode = DB_FETCHMODE_DEFAULT)
    {
//        // compat check, the params and fetchmode parameters used to
//        // have the opposite order
//        if (!is_array($params)) {
//            if (is_array($fetchmode)) {
//                if ($params === null) {
//                    $tmp = DB_FETCHMODE_DEFAULT;
//                } else {
//                    $tmp = $params;
//                }
//                $params = $fetchmode;
//                $fetchmode = $tmp;
//            } elseif ($params !== null) {
//                $fetchmode = $params;
//                $params = array();
//            }
//        }
//        // modifyLimitQuery() would be nice here, but it causes BC issues
//        if (sizeof($params) > 0) {
//            $sth = $this->prepare($query);
//            if (DB::isError($sth)) {
//                return $sth;
//            }
//            $res =& $this->execute($sth, $params);
//            $this->freePrepared($sth);
//        } else {
//            $res =& $this->query($query);
//        }
//
//        if (DB::isError($res)) {
//            return $res;
//        }
//
//        $err = $res->fetchInto($row, $fetchmode);
//
//        $res->free();
//
//        if ($err !== DB_OK) {
//            return $err;
//        }
//
//        return $row;
    }

    /**
     * Fetches a single column from a query result and returns it as an
     * indexed array
     *
     * @param string $query   the SQL query
     * @param mixed  $col     which column to return (integer [column number,
     *                         starting at 0] or string [column name])
     * @param mixed  $params  array, string or numeric data to be used in
     *                         execution of the statement.  Quantity of items
     *                         passed must match quantity of placeholders in
     *                         query:  meaning 1 placeholder for non-array
     *                         parameters or 1 placeholder per array element.
     *
     * @return array  the results as an array.  A DB_Error object on failure.
     *
     * @see DB_common::query()
     *  @todo Implement mock DB_common::getCol
     */
    public function &getCol($query, $col = 0, $params = array())
    {
//        $params = (array)$params;
//        if (sizeof($params) > 0) {
//            $sth = $this->prepare($query);
//
//            if (DB::isError($sth)) {
//                return $sth;
//            }
//
//            $res =& $this->execute($sth, $params);
//            $this->freePrepared($sth);
//        } else {
//            $res =& $this->query($query);
//        }
//
//        if (DB::isError($res)) {
//            return $res;
//        }
//
//        $fetchmode = is_int($col) ? DB_FETCHMODE_ORDERED : DB_FETCHMODE_ASSOC;
//
//        if (!is_array($row = $res->fetchRow($fetchmode))) {
//            $ret = array();
//        } else {
//            if (!array_key_exists($col, $row)) {
//                $ret =& $this->raiseError(DB_ERROR_NOSUCHFIELD);
//            } else {
//                $ret = array($row[$col]);
//                while (is_array($row = $res->fetchRow($fetchmode))) {
//                    $ret[] = $row[$col];
//                }
//            }
//        }
//
//        $res->free();
//
//        if (DB::isError($row)) {
//            $ret = $row;
//        }
//
//        return $ret;
    }

    /**
     * Fetches an entire query result and returns it as an
     * associative array using the first column as the key
     *
     * @param string $query        the SQL query
     * @param bool   $force_array  used only when the query returns
     *                              exactly two columns.  If true, the values
     *                              of the returned array will be one-element
     *                              arrays instead of scalars.
     * @param mixed  $params       array, string or numeric data to be used in
     *                              execution of the statement.  Quantity of
     *                              items passed must match quantity of
     *                              placeholders in query:  meaning 1
     *                              placeholder for non-array parameters or
     *                              1 placeholder per array element.
     * @param int   $fetchmode     the fetch mode to use
     * @param bool  $group         if true, the values of the returned array
     *                              is wrapped in another array.  If the same
     *                              key value (in the first column) repeats
     *                              itself, the values will be appended to
     *                              this array instead of overwriting the
     *                              existing values.
     *
     * @return array  the associative array containing the query results.
     *                A DB_Error object on failure.
     *  @todo Implement mock DB_common::getAssoc
     */
    public function &getAssoc($query, $force_array = false, $params = array(),
                       $fetchmode = DB_FETCHMODE_DEFAULT, $group = false)
    {
//        $params = (array)$params;
//        if (sizeof($params) > 0) {
//            $sth = $this->prepare($query);
//
//            if (DB::isError($sth)) {
//                return $sth;
//            }
//
//            $res =& $this->execute($sth, $params);
//            $this->freePrepared($sth);
//        } else {
//            $res =& $this->query($query);
//        }
//
//        if (DB::isError($res)) {
//            return $res;
//        }
//        if ($fetchmode == DB_FETCHMODE_DEFAULT) {
//            $fetchmode = $this->fetchmode;
//        }
//        $cols = $res->numCols();
//
//        if ($cols < 2) {
//            $tmp =& $this->raiseError(DB_ERROR_TRUNCATED);
//            return $tmp;
//        }
//
//        $results = array();
//
//        if ($cols > 2 || $force_array) {
//            // return array values
//            // XXX this part can be optimized
//            if ($fetchmode == DB_FETCHMODE_ASSOC) {
//                while (is_array($row = $res->fetchRow(DB_FETCHMODE_ASSOC))) {
//                    reset($row);
//                    $key = current($row);
//                    unset($row[key($row)]);
//                    if ($group) {
//                        $results[$key][] = $row;
//                    } else {
//                        $results[$key] = $row;
//                    }
//                }
//            } elseif ($fetchmode == DB_FETCHMODE_OBJECT) {
//                while ($row = $res->fetchRow(DB_FETCHMODE_OBJECT)) {
//                    $arr = get_object_vars($row);
//                    $key = current($arr);
//                    if ($group) {
//                        $results[$key][] = $row;
//                    } else {
//                        $results[$key] = $row;
//                    }
//                }
//            } else {
//                while (is_array($row = $res->fetchRow(DB_FETCHMODE_ORDERED))) {
//                    // we shift away the first element to get
//                    // indices running from 0 again
//                    $key = array_shift($row);
//                    if ($group) {
//                        $results[$key][] = $row;
//                    } else {
//                        $results[$key] = $row;
//                    }
//                }
//            }
//            if (DB::isError($row)) {
//                $results = $row;
//            }
//        } else {
//            // return scalar values
//            while (is_array($row = $res->fetchRow(DB_FETCHMODE_ORDERED))) {
//                if ($group) {
//                    $results[$row[0]][] = $row[1];
//                } else {
//                    $results[$row[0]] = $row[1];
//                }
//            }
//            if (DB::isError($row)) {
//                $results = $row;
//            }
//        }
//
//        $res->free();
//
//        return $results;
    }

    /**
     * Fetches all of the rows from a query result
     *
     * @param string $query      the SQL query
     * @param mixed  $params     array, string or numeric data to be used in
     *                            execution of the statement.  Quantity of
     *                            items passed must match quantity of
     *                            placeholders in query:  meaning 1
     *                            placeholder for non-array parameters or
     *                            1 placeholder per array element.
     * @param int    $fetchmode  the fetch mode to use:
     *                            + DB_FETCHMODE_ORDERED
     *                            + DB_FETCHMODE_ASSOC
     *                            + DB_FETCHMODE_ORDERED | DB_FETCHMODE_FLIPPED
     *                            + DB_FETCHMODE_ASSOC | DB_FETCHMODE_FLIPPED
     *
     * @return array  the nested array.  A DB_Error object on failure.
     *  @todo Implement mock DB_common::getAll
     */
    public function &getAll($query, $params = array(),
                     $fetchmode = DB_FETCHMODE_DEFAULT)
    {
//        // compat check, the params and fetchmode parameters used to
//        // have the opposite order
//        if (!is_array($params)) {
//            if (is_array($fetchmode)) {
//                if ($params === null) {
//                    $tmp = DB_FETCHMODE_DEFAULT;
//                } else {
//                    $tmp = $params;
//                }
//                $params = $fetchmode;
//                $fetchmode = $tmp;
//            } elseif ($params !== null) {
//                $fetchmode = $params;
//                $params = array();
//            }
//        }
//
//        if (sizeof($params) > 0) {
//            $sth = $this->prepare($query);
//
//            if (DB::isError($sth)) {
//                return $sth;
//            }
//
//            $res =& $this->execute($sth, $params);
//            $this->freePrepared($sth);
//        } else {
//            $res =& $this->query($query);
//        }
//
//        if ($res === DB_OK || DB::isError($res)) {
//            return $res;
//        }
//
//        $results = array();
//        while (DB_OK === $res->fetchInto($row, $fetchmode)) {
//            if ($fetchmode & DB_FETCHMODE_FLIPPED) {
//                foreach ($row as $key => $val) {
//                    $results[$key][] = $val;
//                }
//            } else {
//                $results[] = $row;
//            }
//        }
//
//        $res->free();
//
//        if (DB::isError($row)) {
//            $tmp =& $this->raiseError($row);
//            return $tmp;
//        }
//        return $results;
    }

    /**
     * Enables or disables automatic commits
     *
     * @param bool $onoff  true turns it on, false turns it off
     * @return int  DB_OK on success.  A DB_Error object if the driver
     *               doesn't support auto-committing transactions.
     *  @todo Implement mock DB_common::autoCommit
     */
    public function autoCommit($onoff = false)
    {
//        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    /**
     * Commits the current transaction
     *
     * @return int  DB_OK on success.  A DB_Error object on failure.
     *  @todo Implement mock DB_common::commit
     */
    public function commit()
    {
//        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    /**
     * Reverts the current transaction
     *
     * @return int  DB_OK on success.  A DB_Error object on failure.
     *  @todo Implement mock DB_common::rollback
     */
    public function rollback()
    {
//        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    /**
     * Determines the number of rows in a query result
     *
     * @param resource $result  the query result idenifier produced by PHP
     * @return int  the number of rows.  A DB_Error object on failure.
     *  @todo Implement mock DB_common::numRows
     */
    public function numRows($result)
    {
//        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    /**
     * Determines the number of rows affected by a data maniuplation query
     *
     * 0 is returned for queries that don't manipulate data.
     *
     * @return int  the number of rows.  A DB_Error object on failure.
     *  @todo Implement mock DB_common::affectedRows
     */
    public function affectedRows()
    {
//        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    /**
     * Generates the name used inside the database for a sequence
     *
     * @param string $sqn  the sequence's public name
     * @return string  the sequence's name in the backend
     * @see DB_common::createSequence(), DB_common::dropSequence(),
     *      DB_common::nextID(), DB_common::setOption()
     *  @todo Implement mock DB_common::getSequenceName
     */
    protected function getSequenceName($sqn)
    {
//        return sprintf($this->getOption('seqname_format'),
//                       preg_replace('/[^a-z0-9_.]/i', '_', $sqn));
    }

    /**
     * Returns the next free id in a sequence
     *
     * @param string  $seq_name  name of the sequence
     * @param boolean $ondemand  when true, the seqence is automatically
     *                            created if it does not exist
     *
     * @return int  the next id number in the sequence.
     *               A DB_Error object on failure.
     *
     * @see DB_common::createSequence(), DB_common::dropSequence(),
     *      DB_common::getSequenceName()
     *  @todo Implement mock DB_common::nextID
     */
    public function nextId($seq_name, $ondemand = true)
    {
//        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    /**
     * Creates a new sequence
     *
     * @param string $seq_name  name of the new sequence
     * @return int  DB_OK on success.  A DB_Error object on failure.
     * @see DB_common::dropSequence(), DB_common::getSequenceName(),
     *      DB_common::nextID()
     *  @todo Implement mock DB_common::createSequence
     */
    public function createSequence($seq_name)
    {
//        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    /**
     * Deletes a sequence
     *
     * @param string $seq_name  name of the sequence to be deleted
     * @return int  DB_OK on success.  A DB_Error object on failure.
     * @see DB_common::createSequence(), DB_common::getSequenceName(),
     *      DB_common::nextID()
     *  @todo Implement mock DB_common::dropSequence
     */
    public function dropSequence($seq_name)
    {
//        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    /**
     * Communicates an error and invoke error callbacks, etc
     *
     * Basically a wrapper for PEAR::raiseError without the message string.
     *
     * @param mixed   integer error code, or a PEAR error object (all
     *                 other parameters are ignored if this parameter is
     *                 an object
     * @param int     error mode, see PEAR_Error docs
     * @param mixed   if error mode is PEAR_ERROR_TRIGGER, this is the
     *                 error level (E_USER_NOTICE etc).  If error mode is
     *                 PEAR_ERROR_CALLBACK, this is the callback function,
     *                 either as a function name, or as an array of an
     *                 object and method name.  For other error modes this
     *                 parameter is ignored.
     * @param string  extra debug information.  Defaults to the last
     *                 query and native error code.
     * @param mixed   native error code, integer or string depending the
     *                 backend
     *
     * @return object  the PEAR_Error object
     * @see PEAR_Error
     *  @todo Implement mock DB_common::raiseError
     */
    public function &raiseError($code = DB_ERROR, $mode = null, $options = null,
                         $userinfo = null, $nativecode = null)
    {
//        // The error is yet a DB error object
//        if (is_object($code)) {
//            // because we the static PEAR::raiseError, our global
//            // handler should be used if it is set
//            if ($mode === null && !empty($this->_default_error_mode)) {
//                $mode    = $this->_default_error_mode;
//                $options = $this->_default_error_options;
//            }
//            $tmp = PEAR::raiseError($code, null, $mode, $options,
//                                    null, null, true);
//            return $tmp;
//        }
//
//        if ($userinfo === null) {
//            $userinfo = $this->last_query;
//        }
//
//        if ($nativecode) {
//            $userinfo .= ' [nativecode=' . trim($nativecode) . ']';
//        } else {
//            $userinfo .= ' [DB Error: ' . DB::errorMessage($code) . ']';
//        }
//
//        $tmp = PEAR::raiseError(null, $code, $mode, $options, $userinfo,
//                                'DB_Error', true);
//        return $tmp;
    }

    /**
     * Gets the DBMS' native error code produced by the last query
     *
     * @return mixed  the DBMS' error code.  A DB_Error object on failure.
     *  @todo Implement mock DB_common::errorNative
     */
    public function errorNative()
    {
//        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    /**
     * Maps native error codes to DB's portable ones
     *
     * Uses the <var>$errorcode_map</var> property defined in each driver.
     *
     * @param string|int $nativecode  the error code returned by the DBMS
     *
     * @return int  the portable DB error code.  Return DB_ERROR if the
     *               current driver doesn't have a mapping for the
     *               $nativecode submitted.
     *  @todo Implement mock DB_common::errorCode
     */
    public function errorCode($nativecode)
    {
//        if (isset($this->errorcode_map[$nativecode])) {
//            return $this->errorcode_map[$nativecode];
//        }
        // Fall back to DB_ERROR if there was no mapping.
        return DB_ERROR;
    }

    /**
     * Maps a DB error code to a textual message
     *
     * @param integer $dbcode  the DB error code
     * @return string  the error message corresponding to the error code
     *                  submitted.  FALSE if the error code is unknown.
     * @see DB::errorMessage()
     *  @todo Implement mock DB_common::errorMessage
     */
    public function errorMessage($dbcode)
    {
//        return DB::errorMessage($this->errorcode_map[$dbcode]);
    }

    /**
     * Returns information about a table or a result set
     *
     * @param object|string  $result  DB_result object from a query or a
     *                                string containing the name of a table.
     *                                While this also accepts a query result
     *                                resource identifier, this behavior is
     *                                deprecated.
     * @param int  $mode   either unused or one of the tableInfo modes:
     *                     <kbd>DB_TABLEINFO_ORDERTABLE</kbd>,
     *                     <kbd>DB_TABLEINFO_ORDER</kbd> or
     *                     <kbd>DB_TABLEINFO_FULL</kbd> (which does both).
     *                     These are bitwise, so the first two can be
     *                     combined using <kbd>|</kbd>.
     *
     * @return array  an associative array with the information requested.
     *                 A DB_Error object on failure.
     *
     * @see DB_common::setOption()
     *  @todo Implement mock DB_common::tableInfo
     */
    public function tableInfo($result, $mode = null)
    {
        /*
         * If the DB_<driver> class has a tableInfo() method, that one
         * overrides this one.  But, if the driver doesn't have one,
         * this method runs and tells users about that fact.
         */
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    /**
     * Lists internal database information
     *
     * @param string $type  type of information being sought.
     *                       Common items being sought are:
     *                       tables, databases, users, views, functions
     *                       Each DBMS's has its own capabilities.
     *
     * @return array  an array listing the items sought.
     *                 A DB DB_Error object on failure.
     *  @todo Implement mock DB_common::getListOf
     */
    public function getListOf($type)
    {
//        $sql = $this->getSpecialQuery($type);
//        if ($sql === null) {
//            $this->last_query = '';
//            return $this->raiseError(DB_ERROR_UNSUPPORTED);
//        } elseif (is_int($sql) || DB::isError($sql)) {
//            // Previous error
//            return $this->raiseError($sql);
//        } elseif (is_array($sql)) {
//            // Already the result
//            return $sql;
//        }
//        // Launch this query
//        return $this->getCol($sql);
    }

    /**
     * Obtains the query string needed for listing a given type of objects
     *
     * @param string $type  the kind of objects you want to retrieve
     * @return string  the SQL query string or null if the driver doesn't
     *                  support the object type requested
     * @see DB_common::getListOf()
     *  @todo Implement mock DB_common::getSpecialQuery
     */
    protected function getSpecialQuery($type)
    {
//        return $this->raiseError(DB_ERROR_UNSUPPORTED);
    }

    /**
     * Right-trims all strings in an array
     *
     * @param array $array  the array to be trimmed (passed by reference)
     * @return void
     */
    protected function _rtrimArrayValues(&$array)
    {
        foreach ($array as $key => $value) {
            if (is_string($value)) {
                $array[$key] = rtrim($value);
            }
        }
    }

    /**
     * Converts all null values in an array to empty strings
     *
     * @param array  $array  the array to be de-nullified (passed by reference)
     * @return void
     */
    protected function _convertNullArrayValuesToEmpty(&$array)
    {
        foreach ($array as $key => $value) {
            if (is_null($value)) {
                $array[$key] = '';
            }
        }
    }
}

/**
 *  Mock DB_Error
 *  @todo Implement mock DB_Error class
 */
class DB_Error extends PEAR_Error
{
    /**
     * DB_Error constructor
     *
     * @param mixed $code       DB error code, or string with error message
     * @param int   $mode       what "error mode" to operate in
     * @param int   $level      what error level to use for $mode &
     *                           PEAR_ERROR_TRIGGER
     * @param mixed $debuginfo  additional debug info, such as the last query
     * @see PEAR_Error
     *  @todo Implement DB_Error::constructor
     */
    function DB_Error($code = DB_ERROR, $mode = PEAR_ERROR_RETURN,
                      $level = E_USER_NOTICE, $debuginfo = null)
    {
//        if (is_int($code)) {
//            $this->PEAR_Error('DB Error: ' . DB::errorMessage($code), $code,
//                              $mode, $level, $debuginfo);
//        } else {
//            $this->PEAR_Error("DB Error: $code", DB_ERROR,
//                              $mode, $level, $debuginfo);
//        }
    }
}

/**
 *  Mock DB_result
 *  @todo Implement mock DB_result
 */
class DB_result
{

    /**
     * This constructor sets the object's properties
     *
     * @param object   &$dbh     the DB object reference
     * @param resource $result   the result resource id
     * @param array    $options  an associative array with result options
     * @return void
     *  @todo Implement mock DB_result::constructor
     */
    function DB_result(&$dbh, $result, $options = array())
    {
    }

    /**
     * Set options for the DB_result object
     *
     * @param string $key    the option to set
     * @param mixed  $value  the value to set the option to
     * @return void
     *  @todo Implement mock DB_result::setOption()
     */
    function setOption($key, $value = null)
    {
        switch ($key) {
            case 'limit_from':
//                $this->limit_from = $value;
                break;
            case 'limit_count':
//                $this->limit_count = $value;
        }
    }

    /**
     * Fetch a row of data and return it by reference into an array
     *
     * @param int $fetchmode  the constant indicating how to format the data
     * @param int $rownum     the row number to fetch (index starts at 0)
     *
     * @return mixed  an array or object containing the row's data,
     *                 NULL when the end of the result set is reached
     *                 or a DB_Error object on failure.
     *
     * @see DB_common::setOption(), DB_common::setFetchMode()
     *  @todo Implement mock DB_result::fetchRow()
     */
    function &fetchRow($fetchmode = DB_FETCHMODE_DEFAULT, $rownum = null)
    {
//        if ($fetchmode === DB_FETCHMODE_DEFAULT) {
//            $fetchmode = $this->fetchmode;
//        }
//        if ($fetchmode === DB_FETCHMODE_OBJECT) {
//            $fetchmode = DB_FETCHMODE_ASSOC;
//            $object_class = $this->fetchmode_object_class;
//        }
//        if ($this->limit_from !== null) {
//            if ($this->row_counter === null) {
//                $this->row_counter = $this->limit_from;
//                // Skip rows
//                if ($this->dbh->features['limit'] === false) {
//                    $i = 0;
//                    while ($i++ < $this->limit_from) {
//                        $this->dbh->fetchInto($this->result, $arr, $fetchmode);
//                    }
//                }
//            }
//            if ($this->row_counter >= ($this->limit_from + $this->limit_count))
//            {
//                if ($this->autofree) {
//                    $this->free();
//                }
//                $tmp = null;
//                return $tmp;
//            }
//            if ($this->dbh->features['limit'] === 'emulate') {
//                $rownum = $this->row_counter;
//            }
//            $this->row_counter++;
//        }
//        $res = $this->dbh->fetchInto($this->result, $arr, $fetchmode, $rownum);
//        if ($res === DB_OK) {
//            if (isset($object_class)) {
//                // The default mode is specified in the
//                // DB_common::fetchmode_object_class property
//                if ($object_class == 'stdClass') {
//                    $arr = (object) $arr;
//                } else {
//                    $arr = &new $object_class($arr);
//                }
//            }
//            return $arr;
//        }
//        if ($res == null && $this->autofree) {
//            $this->free();
//        }
//        return $res;
    }

    /**
     * Fetch a row of data into an array which is passed by reference
     *
     * @param array &$arr       the variable where the data should be placed
     * @param int   $fetchmode  the constant indicating how to format the data
     * @param int   $rownum     the row number to fetch (index starts at 0)
     * @return mixed  DB_OK if a row is processed, NULL when the end of the
     *                 result set is reached or a DB_Error object on failure
     *
     * @see DB_common::setOption(), DB_common::setFetchMode()
     *  @todo Implement mock DB_result::fetchInto()
     */
    function fetchInto(&$arr, $fetchmode = DB_FETCHMODE_DEFAULT, $rownum = null)
    {
//        if ($fetchmode === DB_FETCHMODE_DEFAULT) {
//            $fetchmode = $this->fetchmode;
//        }
//        if ($fetchmode === DB_FETCHMODE_OBJECT) {
//            $fetchmode = DB_FETCHMODE_ASSOC;
//            $object_class = $this->fetchmode_object_class;
//        }
//        if ($this->limit_from !== null) {
//            if ($this->row_counter === null) {
//                $this->row_counter = $this->limit_from;
//                // Skip rows
//                if ($this->dbh->features['limit'] === false) {
//                    $i = 0;
//                    while ($i++ < $this->limit_from) {
//                        $this->dbh->fetchInto($this->result, $arr, $fetchmode);
//                    }
//                }
//            }
//            if ($this->row_counter >= (
//                    $this->limit_from + $this->limit_count))
//            {
//                if ($this->autofree) {
//                    $this->free();
//                }
//                return null;
//            }
//            if ($this->dbh->features['limit'] === 'emulate') {
//                $rownum = $this->row_counter;
//            }
//
//            $this->row_counter++;
//        }
//        $res = $this->dbh->fetchInto($this->result, $arr, $fetchmode, $rownum);
//        if ($res === DB_OK) {
//            if (isset($object_class)) {
//                // default mode specified in the
//                // DB_common::fetchmode_object_class property
//                if ($object_class == 'stdClass') {
//                    $arr = (object) $arr;
//                } else {
//                    $arr = new $object_class($arr);
//                }
//            }
//            return DB_OK;
//        }
//        if ($res == null && $this->autofree) {
//            $this->free();
//        }
//        return $res;
    }

    /**
     * Get the the number of columns in a result set
     *
     * @return int  the number of columns.  A DB_Error object on failure.
     *  @todo Implement mock DB_result::numCols()
     */
    function numCols()
    {
//        return $this->dbh->numCols($this->result);
    }

    /**
     * Get the number of rows in a result set
     *
     * @return int  the number of rows.  A DB_Error object on failure.
     *  @todo Implement mock DB_result::numRows()
     */
    function numRows()
    {
//        if ($this->dbh->features['numrows'] === 'emulate'
//            && $this->dbh->options['portability'] & DB_PORTABILITY_NUMROWS)
//        {
//            if ($this->dbh->features['prepare']) {
//                $res = $this->dbh->query($this->query, $this->parameters);
//            } else {
//                $res = $this->dbh->query($this->query);
//            }
//            if (DB::isError($res)) {
//                return $res;
//            }
//            $i = 0;
//            while ($res->fetchInto($tmp, DB_FETCHMODE_ORDERED)) {
//                $i++;
//            }
//            return $i;
//        } else {
//            return $this->dbh->numRows($this->result);
//        }
    }

    /**
     * Get the next result if a batch of queries was executed
     *
     * @return bool  true if a new result is available or false if not
     *  @todo Implement mock DB_result::nextResult()
     */
    function nextResult()
    {
//        return $this->dbh->nextResult($this->result);
    }

    /**
     * Frees the resources allocated for this result set
     *
     * @return bool  true on success.  A DB_Error object on failure.
     *  @todo Implement mock DB_result::free()
     */
    function free()
    {
//        $err = $this->dbh->freeResult($this->result);
//        if (DB::isError($err)) {
//            return $err;
//        }
//        $this->result = false;
//        $this->statement = false;
//        return true;
    }

    /**
     * Determine the query string that created this result
     *
     * @return string  the query string
     *  @todo Implement mock DB_result::getQuery()
     */
    function getQuery()
    {
//        return $this->query;
    }

    /**
     * Tells which row number is currently being processed
     *
     * @return integer  the current row being looked at.  Starts at 1.
     *  @todo Implement mock DB_result::getRowCounter()
     */
    function getRowCounter()
    {
//        return $this->row_counter;
    }
}

/**
 *  Mock DB_row
 *  @todo Implement mock DB_row
 */
class DB_row
{

    /**
     * The constructor places a row's data into properties of this object
     *
     * @param array  the array containing the row's data
     * @return void
     *  @todo Implement mock DB_row constructor
     */
    function DB_row(&$arr)
    {
//        foreach ($arr as $key => $value) {
//            $this->$key = &$arr[$key];
//      }
    }
}

/**
 *  Mock DB_mysql class
 */
class DB_mysql extends DB_common
{

    /**
     * This constructor calls <kbd>$this->DB_common()</kbd>
     *
     * @return void
     */
    function DB_mysql()
    {
        $this->DB_common();
    }

    /**
     * Connect to the database server, log in and open the database
     *
     * @param array $dsn         the data source name
     * @param bool  $persistent  should the connection be persistent?
     * @return int  DB_OK on success. A DB_Error object on failure.
     *  @todo Implement mock DB_mysql::connect()
     */
    function connect($dsn, $persistent = false)
    {
//        if (!PEAR::loadExtension('mysql')) {
//            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);
//        }
//
//        $this->dsn = $dsn;
//        if ($dsn['dbsyntax']) {
//            $this->dbsyntax = $dsn['dbsyntax'];
//        }
//
//        $params = array();
//        if ($dsn['protocol'] && $dsn['protocol'] == 'unix') {
//            $params[0] = ':' . $dsn['socket'];
//        } else {
//            $params[0] = $dsn['hostspec'] ? $dsn['hostspec']
//                         : 'localhost';
//            if ($dsn['port']) {
//                $params[0] .= ':' . $dsn['port'];
//            }
//        }
//        $params[] = $dsn['username'] ? $dsn['username'] : null;
//        $params[] = $dsn['password'] ? $dsn['password'] : null;
//
//        if (!$persistent) {
//            if (isset($dsn['new_link'])
//                && ($dsn['new_link'] == 'true' || $dsn['new_link'] === true))
//            {
//                $params[] = true;
//            } else {
//                $params[] = false;
//            }
//        }
//        if (version_compare(phpversion(), '4.3.0', '>=')) {
//            $params[] = isset($dsn['client_flags'])
//                        ? $dsn['client_flags'] : null;
//        }
//
//        $connect_function = $persistent ? 'mysql_pconnect' : 'mysql_connect';
//
//        $ini = ini_get('track_errors');
//        $php_errormsg = '';
//        if ($ini) {
//            $this->connection = @call_user_func_array($connect_function,
//                                                      $params);
//        } else {
//            ini_set('track_errors', 1);
//            $this->connection = @call_user_func_array($connect_function,
//                                                      $params);
//            ini_set('track_errors', $ini);
//        }
//
//        if (!$this->connection) {
//            if (($err = @mysql_error()) != '') {
//                return $this->raiseError(DB_ERROR_CONNECT_FAILED,
//                                         null, null, null, 
//                                         $err);
//            } else {
//                return $this->raiseError(DB_ERROR_CONNECT_FAILED,
//                                         null, null, null,
//                                         $php_errormsg);
//            }
//        }
//
//        if ($dsn['database']) {
//            if (!@mysql_select_db($dsn['database'], $this->connection)) {
//                return $this->mysqlRaiseError();
//            }
//            $this->_db = $dsn['database'];
//        }
//
//        return DB_OK;
    }

    /**
     * Disconnects from the database server
     *
     * @return bool  TRUE on success, FALSE on failure
     *  @todo Implement mock DB_mysql::disconnect()
     */
    function disconnect()
    {
//        $ret = @mysql_close($this->connection);
//        $this->connection = null;
//        return $ret;
    }

    /**
     * Sends a query to the database server
     *
     * Generally uses mysql_query().  If you want to use
     * mysql_unbuffered_query() set the "result_buffering" option to 0 using
     * setOptions().  This option was added in Release 1.7.0.
     *
     * @param string  the SQL query string
     *
     * @return mixed  + a PHP result resrouce for successful SELECT queries
     *                + the DB_OK constant for other successful queries
     *                + a DB_Error object on failure
     *  @todo Implement mock DB_mysql::simpleQuery()
     */
    function simpleQuery($query)
    {
//        $ismanip = DB::isManip($query);
//        $this->last_query = $query;
//        $query = $this->modifyQuery($query);
//        if ($this->_db) {
//            if (!@mysql_select_db($this->_db, $this->connection)) {
//                return $this->mysqlRaiseError(DB_ERROR_NODBSELECTED);
//            }
//        }
//        if (!$this->autocommit && $ismanip) {
//            if ($this->transaction_opcount == 0) {
//                $result = @mysql_query('SET AUTOCOMMIT=0', $this->connection);
//                $result = @mysql_query('BEGIN', $this->connection);
//                if (!$result) {
//                    return $this->mysqlRaiseError();
//                }
//            }
//            $this->transaction_opcount++;
//        }
//        if (!$this->options['result_buffering']) {
//            $result = @mysql_unbuffered_query($query, $this->connection);
//        } else {
//            $result = @mysql_query($query, $this->connection);
//        }
//        if (!$result) {
//            return $this->mysqlRaiseError();
//        }
//        if (is_resource($result)) {
//            return $result;
//        }
        return DB_OK;
    }

    /**
     * Move the internal mysql result pointer to the next available result
     *
     * This method has not been implemented yet.
     *
     * @param a valid sql result resource
     *
     * @return false
     */
    function nextResult($result)
    {
        return false;
    }

    /**
     * Places a row from the result set into the given array
     *
     * @param resource $result    the query result resource
     * @param array    $arr       the referenced array to put the data in
     * @param int      $fetchmode how the resulting array should be indexed
     * @param int      $rownum    the row number to fetch (0 = first row)
     * @return mixed  DB_OK on success, NULL when the end of a result set is
     *                 reached or on failure
     *
     * @see DB_result::fetchInto()
     *  @todo Implement mock DB_mysql::fetchInto()
     */
    function fetchInto($result, &$arr, $fetchmode, $rownum = null)
    {
//        if ($rownum !== null) {
//            if (!@mysql_data_seek($result, $rownum)) {
//                return null;
//            }
//        }
//        if ($fetchmode & DB_FETCHMODE_ASSOC) {
//            $arr = @mysql_fetch_array($result, MYSQL_ASSOC);
//            if ($this->options['portability'] & DB_PORTABILITY_LOWERCASE && $arr) {
//                $arr = array_change_key_case($arr, CASE_LOWER);
//            }
//        } else {
//            $arr = @mysql_fetch_row($result);
//        }
//        if (!$arr) {
//            return null;
//        }
//        if ($this->options['portability'] & DB_PORTABILITY_RTRIM) {
//            /*
//             * Even though this DBMS already trims output, we do this because
//             * a field might have intentional whitespace at the end that
//             * gets removed by DB_PORTABILITY_RTRIM under another driver.
//             */
//            $this->_rtrimArrayValues($arr);
//        }
//        if ($this->options['portability'] & DB_PORTABILITY_NULL_TO_EMPTY) {
//            $this->_convertNullArrayValuesToEmpty($arr);
//        }
        return DB_OK;
    }

    /**
     * Deletes the result set and frees the memory occupied by the result set
     *
     * This method is not meant to be called directly.  Use
     * DB_result::free() instead.  It can't be declared "protected"
     * because DB_result is a separate object.
     *
     * @param resource $result  PHP's query result resource
     *
     * @return bool  TRUE on success, FALSE if $result is invalid
     *
     * @see DB_result::free()
     *  @todo Implement mock DB_mysql::freeResult()
     */
    function freeResult($result)
    {
//        return @mysql_free_result($result);
    }


    /**
     * Gets the number of columns in a result set
     *
     * This method is not meant to be called directly.  Use
     * DB_result::numCols() instead.  It can't be declared "protected"
     * because DB_result is a separate object.
     *
     * @param resource $result  PHP's query result resource
     *
     * @return int  the number of columns.  A DB_Error object on failure.
     *
     * @see DB_result::numCols()
     *  @todo Implement mock DB_mysql::numCols()
     */
    function numCols($result)
    {
//        $cols = @mysql_num_fields($result);
//        if (!$cols) {
//            return $this->mysqlRaiseError();
//        }
//        return $cols;
    }

    /**
     * Gets the number of rows in a result set
     *
     * This method is not meant to be called directly.  Use
     * DB_result::numRows() instead.  It can't be declared "protected"
     * because DB_result is a separate object.
     *
     * @param resource $result  PHP's query result resource
     *
     * @return int  the number of rows.  A DB_Error object on failure.
     *
     * @see DB_result::numRows()
     *  @todo Implement mock DB_mysql::numRows()
     */
    function numRows($result)
    {
//        $rows = @mysql_num_rows($result);
//        if ($rows === null) {
//            return $this->mysqlRaiseError();
//        }
//        return $rows;
    }

    /**
     * Enables or disables automatic commits
     *
     * @param bool $onoff  true turns it on, false turns it off
     *
     * @return int  DB_OK on success.  A DB_Error object if the driver
     *               doesn't support auto-committing transactions.
     *  @todo Implement mock DB_mysql::autoCommit()
     */
    function autoCommit($onoff = false)
    {
//        // XXX if $this->transaction_opcount > 0, we should probably
//        // issue a warning here.
//        $this->autocommit = $onoff ? true : false;
        return DB_OK;
    }

    /**
     * Commits the current transaction
     *
     * @return int  DB_OK on success.  A DB_Error object on failure.
     *  @todo Implement mock DB_mysql::committ()
     */
    function commit()
    {
//        if ($this->transaction_opcount > 0) {
//            if ($this->_db) {
//                if (!@mysql_select_db($this->_db, $this->connection)) {
//                    return $this->mysqlRaiseError(DB_ERROR_NODBSELECTED);
//                }
//            }
//            $result = @mysql_query('COMMIT', $this->connection);
//            $result = @mysql_query('SET AUTOCOMMIT=1', $this->connection);
//            $this->transaction_opcount = 0;
//            if (!$result) {
//                return $this->mysqlRaiseError();
//            }
//        }
        return DB_OK;
    }

    /**
     * Reverts the current transaction
     *
     * @return int  DB_OK on success.  A DB_Error object on failure.
     *  @todo Implement mock DB_mysql::rollback()
     */
    function rollback()
    {
//        if ($this->transaction_opcount > 0) {
//            if ($this->_db) {
//                if (!@mysql_select_db($this->_db, $this->connection)) {
//                    return $this->mysqlRaiseError(DB_ERROR_NODBSELECTED);
//                }
//            }
//            $result = @mysql_query('ROLLBACK', $this->connection);
//            $result = @mysql_query('SET AUTOCOMMIT=1', $this->connection);
//            $this->transaction_opcount = 0;
//            if (!$result) {
//                return $this->mysqlRaiseError();
//            }
//        }
        return DB_OK;
    }

    /**
     * Determines the number of rows affected by a data maniuplation query
     *
     * 0 is returned for queries that don't manipulate data.
     *
     * @return int  the number of rows.  A DB_Error object on failure.
     *  @todo Implement mock DB_mysql::affectedRows()
     */
    function affectedRows()
    {
//        if (DB::isManip($this->last_query)) {
//            return @mysql_affected_rows($this->connection);
//        } else {
//            return 0;
//        }
     }

    /**
     * Returns the next free id in a sequence
     *
     * @param string  $seq_name  name of the sequence
     * @param boolean $ondemand  when true, the seqence is automatically
     *                            created if it does not exist
     *
     * @return int  the next id number in the sequence.
     *               A DB_Error object on failure.
     *
     * @see DB_common::nextID(), DB_common::getSequenceName(),
     *      DB_mysql::createSequence(), DB_mysql::dropSequence()
     *  @todo Implement mock DB_mysql::nextId()
     */
    function nextId($seq_name, $ondemand = true)
    {
//        $seqname = $this->getSequenceName($seq_name);
//        do {
//            $repeat = 0;
//            $this->pushErrorHandling(PEAR_ERROR_RETURN);
//            $result = $this->query("UPDATE ${seqname} ".
//                                   'SET id=LAST_INSERT_ID(id+1)');
//            $this->popErrorHandling();
//            if ($result === DB_OK) {
//                // COMMON CASE
//                $id = @mysql_insert_id($this->connection);
//                if ($id != 0) {
//                    return $id;
//                }
//                // EMPTY SEQ TABLE
//                // Sequence table must be empty for some reason, so fill
//                // it and return 1 and obtain a user-level lock
//                $result = $this->getOne("SELECT GET_LOCK('${seqname}_lock',10)");
//                if (DB::isError($result)) {
//                    return $this->raiseError($result);
//                }
//                if ($result == 0) {
//                    // Failed to get the lock
//                    return $this->mysqlRaiseError(DB_ERROR_NOT_LOCKED);
//                }
//
//                // add the default value
//                $result = $this->query("REPLACE INTO ${seqname} (id) VALUES (0)");
//                if (DB::isError($result)) {
//                    return $this->raiseError($result);
//                }
//
//                // Release the lock
//                $result = $this->getOne('SELECT RELEASE_LOCK('
//                                        . "'${seqname}_lock')");
//                if (DB::isError($result)) {
//                    return $this->raiseError($result);
//                }
//                // We know what the result will be, so no need to try again
//                return 1;
//
//            } elseif ($ondemand && DB::isError($result) &&
//                $result->getCode() == DB_ERROR_NOSUCHTABLE)
//            {
//                // ONDEMAND TABLE CREATION
//                $result = $this->createSequence($seq_name);
//                if (DB::isError($result)) {
//                    return $this->raiseError($result);
//                } else {
//                    $repeat = 1;
//                }
//
//            } elseif (DB::isError($result) &&
//                      $result->getCode() == DB_ERROR_ALREADY_EXISTS)
//            {
//                // BACKWARDS COMPAT
//                // see _BCsequence() comment
//                $result = $this->_BCsequence($seqname);
//                if (DB::isError($result)) {
//                    return $this->raiseError($result);
//                }
//                $repeat = 1;
//            }
//        } while ($repeat);
//
//        return $this->raiseError($result);
    }

    /**
     * Creates a new sequence
     *
     * @param string $seq_name  name of the new sequence
     *
     * @return int  DB_OK on success.  A DB_Error object on failure.
     *
     * @see DB_common::createSequence(), DB_common::getSequenceName(),
     *      DB_mysql::nextID(), DB_mysql::dropSequence()
     *  @todo Implement mock DB_mysql::createSequence()
     */
    function createSequence($seq_name)
    {
//        $seqname = $this->getSequenceName($seq_name);
//        $res = $this->query('CREATE TABLE ' . $seqname
//                            . ' (id INTEGER UNSIGNED AUTO_INCREMENT NOT NULL,'
//                            . ' PRIMARY KEY(id))');
//        if (DB::isError($res)) {
//            return $res;
//        }
//        // insert yields value 1, nextId call will generate ID 2
//        $res = $this->query("INSERT INTO ${seqname} (id) VALUES (0)");
//        if (DB::isError($res)) {
//            return $res;
//        }
//        // so reset to zero
//        return $this->query("UPDATE ${seqname} SET id = 0");
    }

    /**
     * Deletes a sequence
     *
     * @param string $seq_name  name of the sequence to be deleted
     *
     * @return int  DB_OK on success.  A DB_Error object on failure.
     *
     * @see DB_common::dropSequence(), DB_common::getSequenceName(),
     *      DB_mysql::nextID(), DB_mysql::createSequence()
     *  @todo Implement mock DB_mysql::dropSequence()
     */
    function dropSequence($seq_name)
    {
//        return $this->query('DROP TABLE ' . $this->getSequenceName($seq_name));
    }

    /**
     * Backwards compatibility with old sequence emulation implementation
     * (clean up the dupes)
     *
     * @param string $seqname  the sequence name to clean up
     *
     * @return bool  true on success.  A DB_Error object on failure.
     *  @todo Implement mock DB_mysql::_BCsequence()
     */
    private function _BCsequence($seqname)
    {
//        // Obtain a user-level lock... this will release any previous
//        // application locks, but unlike LOCK TABLES, it does not abort
//        // the current transaction and is much less frequently used.
//        $result = $this->getOne("SELECT GET_LOCK('${seqname}_lock',10)");
//        if (DB::isError($result)) {
//            return $result;
//        }
//        if ($result == 0) {
//            // Failed to get the lock, can't do the conversion, bail
//            // with a DB_ERROR_NOT_LOCKED error
//            return $this->mysqlRaiseError(DB_ERROR_NOT_LOCKED);
//        }
//
//        $highest_id = $this->getOne("SELECT MAX(id) FROM ${seqname}");
//        if (DB::isError($highest_id)) {
//            return $highest_id;
//        }
//        // This should kill all rows except the highest
//        // We should probably do something if $highest_id isn't
//        // numeric, but I'm at a loss as how to handle that...
//        $result = $this->query('DELETE FROM ' . $seqname
//                               . " WHERE id <> $highest_id");
//        if (DB::isError($result)) {
//            return $result;
//        }
//
//        // If another thread has been waiting for this lock,
//        // it will go thru the above procedure, but will have no
//        // real effect
//        $result = $this->getOne("SELECT RELEASE_LOCK('${seqname}_lock')");
//        if (DB::isError($result)) {
//            return $result;
//        }
        return true;
    }

    /**
     * Quotes a string so it can be safely used as a table or column name
     *
     * MySQL can't handle the backtick character (<kbd>`</kbd>) in
     * table or column names.
     *
     * @param string $str  identifier name to be quoted
     * @return string  quoted identifier string
     * @see DB_common::quoteIdentifier()
     * @access private
     */
    function quoteIdentifier($str)
    {
        return '`' . $str . '`';
    }

    /**
     * Escapes a string according to the current DBMS's standards
     *
     * @param string $str  the string to be escaped
     * @return string  the escaped string
     * @see DB_common::quoteSmart()
     *  @todo Implement mock DB_mysql::escapeSimple()
     */
    function escapeSimple($str)
    {
//        if (function_exists('mysql_real_escape_string')) {
//            return @mysql_real_escape_string($str, $this->connection);
//        } else {
//            return @mysql_escape_string($str);
//        }
    }

    /**
     * Changes a query string for various DBMS specific reasons
     *
     * This little hack lets you know how many rows were deleted
     * when running a "DELETE FROM table" query.  Only implemented
     * if the DB_PORTABILITY_DELETE_COUNT portability option is on.
     *
     * @param string $query  the query string to modify
     * @return string  the modified query string
     * @see DB_common::setOption()
     *  @todo Implement mock DB_mysql::modifyQuery()
     */
    protected function modifyQuery($query)
    {
//        if ($this->options['portability'] & DB_PORTABILITY_DELETE_COUNT) {
//            // "DELETE FROM table" gives 0 affected rows in MySQL.
//            // This little hack lets you know how many rows were deleted.
//            if (preg_match('/^\s*DELETE\s+FROM\s+(\S+)\s*$/i', $query)) {
//                $query = preg_replace('/^\s*DELETE\s+FROM\s+(\S+)\s*$/',
//                                      'DELETE FROM \1 WHERE 1=1', $query);
//            }
//        }
//        return $query;
    }

    /**
     * Adds LIMIT clauses to a query string according to current DBMS standards
     *
     * @param string $query   the query to modify
     * @param int    $from    the row to start to fetching (0 = the first row)
     * @param int    $count   the numbers of rows to fetch
     * @param mixed  $params  array, string or numeric data to be used in
     *                         execution of the statement.  Quantity of items
     *                         passed must match quantity of placeholders in
     *                         query:  meaning 1 placeholder for non-array
     *                         parameters or 1 placeholder per array element.
     * @return string  the query string with LIMIT clauses added
     *  @todo Implement mock DB_mysql::modifyLimitQuery()
     */
    protected function modifyLimitQuery($query, $from, $count, $params = array())
    {
//        if (DB::isManip($query)) {
//            return $query . " LIMIT $count";
//        } else {
//            return $query . " LIMIT $from, $count";
//        }
    }

    /**
     * Produces a DB_Error object regarding the current problem
     *
     * @param int $errno  if the error is being manually raised pass a
     *                     DB_ERROR* constant here.  If this isn't passed
     *                     the error information gathered from the DBMS.
     *
     * @return object  the DB_Error object
     * @see DB_common::raiseError(),
     *      DB_mysql::errorNative(), DB_common::errorCode()
     *  @todo Implement mock DB_mysql::mysqlRaiseError()
     */
    function mysqlRaiseError($errno = null)
    {
//        if ($errno === null) {
//            if ($this->options['portability'] & DB_PORTABILITY_ERRORS) {
//                $this->errorcode_map[1022] = DB_ERROR_CONSTRAINT;
//                $this->errorcode_map[1048] = DB_ERROR_CONSTRAINT_NOT_NULL;
//                $this->errorcode_map[1062] = DB_ERROR_CONSTRAINT;
//            } else {
//                // Doing this in case mode changes during runtime.
//                $this->errorcode_map[1022] = DB_ERROR_ALREADY_EXISTS;
//                $this->errorcode_map[1048] = DB_ERROR_CONSTRAINT;
//                $this->errorcode_map[1062] = DB_ERROR_ALREADY_EXISTS;
//            }
//            $errno = $this->errorCode(mysql_errno($this->connection));
//        }
//        return $this->raiseError($errno, null, null, null,
//                                 @mysql_errno($this->connection) . ' ** ' .
//                                 @mysql_error($this->connection));
    }

    /**
     * Gets the DBMS' native error code produced by the last query
     *
     * @return int  the DBMS' error code
     *  @todo Implement mock DB_mysql::errorNative()
     */
    function errorNative()
    {
//        return @mysql_errno($this->connection);
    }

    /**
     * Returns information about a table or a result set
     *
     * @param object|string  $result  DB_result object from a query or a
     *                                 string containing the name of a table.
     *                                 While this also accepts a query result
     *                                 resource identifier, this behavior is
     *                                 deprecated.
     * @param int            $mode    a valid tableInfo mode
     *
     * @return array  an associative array with the information requested.
     *                 A DB_Error object on failure.
     *
     * @see DB_common::tableInfo()
     *  @todo Implement mock DB_mysql::tableInfo()
     */
    function tableInfo($result, $mode = null)
    {
        // We only support the default mode
        PHPUnit2_Framework_Assert::assertNull($mode);

        // We only support table name as first argument
        PHPUnit2_Framework_Assert::assertTrue(is_string($result));

        // Look up table name in the mock database
        foreach(self::$database as $table => $value) {
            if ($result == $table) {
                return $value['info'];
            }
        }
        PHPUnit2_Framework_Assert::fail("DB_mysql::tableInfo called"
                                        ." with unknown table $result");
    }

    /**
     * Obtains the query string needed for listing a given type of objects
     *
     * @param string $type  the kind of objects you want to retrieve
     *
     * @return string  the SQL query string or null if the driver doesn't
     *                  support the object type requested
     *
     * @see DB_common::getListOf()
     */
    protected function getSpecialQuery($type)
    {
        switch ($type) {
            case 'tables':
                return 'SHOW TABLES';
            case 'users':
                return 'SELECT DISTINCT User FROM mysql.user';
            case 'databases':
                return 'SHOW DATABASES';
            default:
                return null;
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
