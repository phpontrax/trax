<?php
/**
 *  File for the ActiveRecordTest class
 *
 * (PHP 5)
 *
 * @package PHPonTraxTest
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @copyright (c) Walter O. Haas 2006
 * @version $Id$
 * @author Walt Haas <haas@xmission.com>
 */

echo "testing ActiveRecord\n";
require_once 'testenv.php';

//  We need to load a mock DB class to test ActiveRecord.
//  Change the include path to put the mockDB/ directory first, so
//  that when ActiveRecord loads it will pick up the mock class.
@ini_set('include_path','./mockDB:'.ini_get('include_path'));
require_once "active_record.php";

// Call ActiveRecordTest::main() if this source file is executed directly.
if (!defined("PHPUnit2_MAIN_METHOD")) {
    define("PHPUnit2_MAIN_METHOD", "ActiveRecordTest::main");
}

require_once "PHPUnit2/Framework/TestCase.php";
require_once "PHPUnit2/Framework/TestSuite.php";

/**
 *  Require classes that are too trivial to bother making mocks
 */
require_once 'trax_exceptions.php';
require_once 'inflector.php';

// You may remove the following line when all tests have been implemented.
require_once "PHPUnit2/Framework/IncompleteTestError.php";

// Set Trax operating mode
define("TRAX_MODE",   "development");

/**
 *  Regression tester for the ActiveRecord class
 *
 *  This class is used only in regression testing of the ActiveRecord
 *  class, but you might find some useful examples by reading it.
 */
  class PersonName extends ActiveRecord {
      //  Function to validate prefix attribute
      function validate_prefix() {
          if ($this->prefix == '') {
              return array(false, "prefix empty");
          } else {
              return array(true);
          }
      }

      //  Create another copy of this class
      function new_obj($attributes) {
          return $this->create($attributes);
      }
}

class DB_find_all_result extends DB_result {
    // Data to be returned by fetchMode
    private $data = array(array('id' => '17',
                                'prefix' => '',
                                'first_name' => 'Ben',
                                'mi' => '',
                                'last_name' => 'Dover',
                                'suffix' => ''),
                          array('id' => '23',
                                'prefix' => '',
                                'first_name' => 'Eileen',
                                'mi' => '',
                                'last_name' => 'Dover',
                                'suffix' => '')
                          );

    private $row_num = 0;

    function fetchRow() {
        if ($this->row_num >= count($this->data)) {
            return null;
        }
        return $this->data[$this->row_num++];
    }
}

/**
 * Test class for {@link ActiveRecord}
 */
class ActiveRecordTest extends PHPUnit2_Framework_TestCase {

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main() {
        require_once "PHPUnit2/TextUI/TestRunner.php";

        $suite  = new PHPUnit2_Framework_TestSuite("ActiveRecordTest");
        $result = PHPUnit2_TextUI_TestRunner::run($suite);
    }

    /**
     *  Set the environment ActiveRecord expects
     */
    protected function setUp() {

        //  Force constructor to get a connection
        $GLOBALS['ACTIVE_RECORD_DB'] = null;

        // Set up information that normally comes from database.ini
        $GLOBALS['TRAX_DB_SETTINGS'][TRAX_MODE]
            = array('phptype'    => 'mysql',
                    'database'   => 'database_development',
                    'hostspec'   => 'localhost',
                    'username'   => 'root',
                    'password'   => '',
                    'persistent' => true);
    }

    /**
     *  This method is called after a test is executed.
     */
    protected function tearDown() {
    }

    /**
     *  Test constructor
     */
    public function test__construct() {
        $p = new PersonName;
        $this->assertEquals(get_class($p), 'PersonName');
        $this->assertEquals($p->table_name, 'person_names');
        $this->assertTrue($GLOBALS['ACTIVE_RECORD_DB']->options['persistent']);
        //  We don't completely check content_columns
        $this->assertTrue(is_array($p->content_columns));
        $this->assertEquals(count($p->content_columns),6);
        //  There are a lot of notice level error messages in normal
        //  operation.  We know about them and don't want to confuse
        //  testing with them. 
        error_reporting(E_USER_WARNING);

        $p = new PersonName(array('id' => '17', 'first_name' => 'Boris',
                                  'last_name' => 'Tudeth'));
        $this->assertEquals($p->first_name,'Boris');
        $this->assertEquals($p->last_name,'Tudeth');
        error_reporting(E_USER_NOTICE);
    }

    /**
     *  Test the get_attributes() method
     */
    public function testGet_attributes() {
        $p = new PersonName;
        //  Constructor initializes all attributes to null
        //  There are a lot of notice level error messages in normal
        //  operation.  We know about them and don't want to confuse
        //  testing with them. 
        error_reporting(E_USER_WARNING);
        $attrs = $p->get_attributes();
        $this->assertEquals($attrs,array('id'         => null,
                                         'prefix'     => null,
                                         'first_name' => null,
                                         'mi'         => null,
                                         'last_name'  => null,
                                         'suffix'     => null));
        //  Assign some attribute values
        $p->id         = 17;
        $p->prefix     = 'Dr.';
        $p->first_name = 'Anon';
        $p->mi         = 'E';
        $p->last_name  = 'Moose';
        $p->suffix     = 'Ph.D.';

        //  This shouldn't produce notice level messages
        error_reporting(E_USER_NOTICE);

        //  get_attributes() should return the same values
        $attrs = $p->get_attributes();
        $this->assertEquals($attrs,array('id'         => 17,
                                         'prefix'     => 'Dr.',
                                         'first_name' => 'Anon',
                                         'mi'         => 'E',
                                         'last_name'  => 'Moose',
                                         'suffix'     => 'Ph.D.'));
    }

	/**
	 *  Test the update_attributes() method
     *  @todo Figure out the datetime thing and how to test it
	 */
    public function testUpdate_attributes() {
        $p = new PersonName;
        $p->update_attributes(array('id'         => 17,
                                    'prefix'     => 'Dr.',
                                    'first_name' => 'Anon',
                                    'mi'         => 'E',
                                    'last_name'  => 'Moose',
                                    'suffix'     => 'Ph.D.'));
        $attrs = $p->get_attributes();
        $this->assertEquals($attrs,array('id'         => 17,
                                         'prefix'     => 'Dr.',
                                         'first_name' => 'Anon',
                                         'mi'         => 'E',
                                         'last_name'  => 'Moose',
                                         'suffix'     => 'Ph.D.'));
        // Remove the following line when you complete this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
	}

    /**
     *  Test the quoted_attributes() method
     *  @todo Figure out how to test timestamp updating
     */
    public function testQuoted_attributes() {
        $p = new PersonName;
        //  Constructor initializes all attributes to null.
        //  quoted_attributes() returns null as null string
        $attrs = $p->quoted_attributes();
        $this->assertEquals($attrs,array('id'         => "''",
                                         'prefix'     => "''",
                                         'first_name' => "''",
                                         'mi'         => "''",
                                         'last_name'  => "''",
                                         'suffix'     => "''"));
        //  Assign some attribute values
        $p->id         = 17;
        $p->prefix     = '"Dr."';
        $p->first_name = 'Nobody';
        $p->mi         = 'X';
        $p->last_name  = 'O\'Reilly';
        $p->suffix     = 'Back\\slash';
        //  Get attributes with quotes
        $attrs = $p->quoted_attributes();
        $this->assertEquals($attrs,array('id'         => "'17'",
                                         'prefix'     => "'\\\"Dr.\\\"'",
                                         'first_name' => "'Nobody'",
                                         'mi'         => "'X'",
                                         'last_name'  => "'O\'Reilly'",
                                         'suffix'     => "'Back\\\\slash'"));
        // Test the optional argument
        $p = new PersonName;
        $attrs = $p->quoted_attributes(
                               array('id'         => 17,
                                     'prefix'     => '"Dr."',
                                     'first_name' => 'Nobody',
                                     'mi'         => 'X',
                                     'last_name'  => 'O\'Reilly',
                                     'suffix'     => 'Back\\slash'));
        $this->assertEquals($attrs,array('id'         => "'17'",
                                         'prefix'     => "'\\\"Dr.\\\"'",
                                         'first_name' => "'Nobody'",
                                         'mi'         => "'X'",
                                         'last_name'  => "'O\'Reilly'",
                                         'suffix'     => "'Back\\\\slash'"));
    }

	/**
	 *  Test the validate_model_attributes() method
	 */
    public function testValidate_model_attributes() {
        $p = new PersonName;
        $p->update_attributes(array('id'         => 17,
                                    'prefix'     => 'Dr.',
                                    'first_name' => 'Anon',
                                    'mi'         => 'E',
                                    'last_name'  => 'Moose',
                                    'suffix'     => 'Ph.D.'));
        //  With failing validation, should return false and error msg
        $p->prefix = '';
        $result = $p->validate_model_attributes();
        $this->assertFalse($result);
        $this->assertEquals(count($p->errors),1);
        $this->assertEquals($p->errors['prefix'], 'prefix empty');
	}

    /**
     *  Test the query() method
     */
    public function testQuery() {
        //  Test normal case: send query, get result
        $p = new PersonName;
        $GLOBALS['ACTIVE_RECORD_DB']->expect_query('foo','bar');
        $result = $p->query('foo');
        $this->assertEquals($result,'bar');
    }

    /**
     *  Test the get_insert_id() method
     */
    public function testGet_insert_id() {
        $p = new PersonName;
        $GLOBALS['ACTIVE_RECORD_DB']->expect_query("SELECT LAST_INSERT_ID();",
                                                   '17');
        $result =& $p->get_insert_id();
        $this->assertEquals($result,'17');
    }

    /**
     *  Test the is_error() method
     */
    public function testIs_error() {
        $p = new PersonName;
        //  Create a new harmless object, test it's not an error
        $obj = new PHPUnit2_Framework_Assert;
        $this->assertFalse($p->is_error($obj));
        //  Create a PHP 4 error, test it is detected
        $obj = new PEAR_Error('testing');
        $this->assertTrue($p->is_error($obj));
        //  Create a DB error, test it is detected
        $obj = new DB_Error('testing');
        $this->assertTrue($p->is_error($obj));
    }

    /**
     *  Test the get_primary_key_conditions() method
     */
    public function testGet_primary_key_conditions() {
        $p = new PersonName;
        //  Default is primary key is 'id', no value
        $result = $p->get_primary_key_conditions();
        $this->assertEquals($result,"id = ''");
        //  Now give the primary key a value
        $p->id = 11;
        $result = $p->get_primary_key_conditions();
        $this->assertEquals($result,"id = '11'");
        //  Try a different column as primary key
        $p->primary_keys=array('last_name');
        $result = $p->get_primary_key_conditions();
        $this->assertEquals($result,"last_name = ''");
        $p->last_name = "Smith";
        $result = $p->get_primary_key_conditions();
        $this->assertEquals($result,"last_name = 'Smith'");
        //  Try two columns as primary key
        $p->primary_keys=array('id', 'last_name');
        $result = $p->get_primary_key_conditions();
        $this->assertEquals($result,"id = '11' AND last_name = 'Smith'");
    }

    /**
     *  Test the get_updates_sql() method
     */
    public function testGet_updates_sql() {
        //  Apply some attributes
        $p = new PersonName;
        $p->id         = 17;
        $p->prefix     = 'Dr.';
        $p->first_name = 'Anon';
        $p->mi         = 'E';
        $p->last_name  = 'Moose';
        $p->suffix     = 'Ph.D.';
        $result = $p->get_updates_sql();
        $this->assertEquals($result,
                       "prefix = 'Dr.', first_name = 'Anon',"
                      ." mi = 'E', last_name = 'Moose', suffix = 'Ph.D.'");
        //  Assign some attribute values that need to be quoted
        $p = new PersonName;
        $p->id         = 17;
        $p->prefix     = '"Dr."';
        $p->first_name = 'Nobody';
        $p->mi         = 'X';
        $p->last_name  = 'O\'Reilly';
        $p->suffix     = 'Back\\slash';
        $result = $p->get_updates_sql();
        $this->assertEquals($result,
             "prefix = '\\\"Dr.\\\"', first_name = 'Nobody',"
            ." mi = 'X', last_name = 'O\'Reilly', suffix = 'Back\\\\slash'");
    }

    /**
     *  Test the save() method
     *  @todo Write test of save() of existing row
     */
    public function testSave() {
        //  A valid new row should be inserted
        $p = new PersonName;
        $GLOBALS['ACTIVE_RECORD_DB']->expect_queries(array(
          array('query' => "INSERT INTO person_names"
             ." (id, prefix, first_name, mi, last_name, suffix)"
             ." VALUES ('', 'Dr.', 'Anon', 'E', 'Moose', 'Ph.D.')",
                'result' => DB_OK),
          array('query' => "SELECT LAST_INSERT_ID();",
                'result' => '17')));
        $result = $p->save(array('prefix'     => 'Dr.',
                                 'first_name' => 'Anon',
                                 'mi'         => 'E',
                                 'last_name'  => 'Moose',
                                 'suffix'     => 'Ph.D.'));
        $this->assertTrue($result);
        //  Verify DB received all expected queries
        $GLOBALS['ACTIVE_RECORD_DB']->tally_queries();

        // An invalid row should fail immediately
        $p = new PersonName;
        $result = $p->save(array('first_name' => 'Anon',
                                 'mi'         => 'E',
                                 'last_name'  => 'Moose',
                                 'suffix'     => 'Ph.D.'));
        $this->assertFalse($result);
        $this->assertEquals(count($p->errors),1);
        $this->assertEquals($p->errors['prefix'], 'prefix empty');

        //  An invalid new row with validation disabled should be inserted
        $p = new PersonName;
        $GLOBALS['ACTIVE_RECORD_DB']->expect_queries(array(
          array('query' => "INSERT INTO person_names"
             ." (id, prefix, first_name, mi, last_name, suffix)"
             ." VALUES ('', '', 'Anon', 'E', 'Moose', 'Ph.D.')",
                'result' => DB_OK),
          array('query' => "SELECT LAST_INSERT_ID();",
                'result' => '17')));
        $result = $p->save(array('first_name' => 'Anon',
                                 'mi'         => 'E',
                                 'last_name'  => 'Moose',
                                 'suffix'     => 'Ph.D.'),true);
        $this->assertTrue($result);
        //  Verify DB received all expected queries
        $GLOBALS['ACTIVE_RECORD_DB']->tally_queries();
        // Remove the following line when you complete this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the create() method
     *  @todo Implement testCreate() (figure out create() first)
     */
    public function testCreate() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the add_error() method
     */
    public function testAdd_error() {
        $p = new PersonName;
        $this->assertTrue(is_array($p->errors));
        $this->assertEquals(count($p->errors),0);
        $p->add_error('mumble is scrogged','mumble');
        $this->assertEquals($p->errors,
                            array('mumble' => 'mumble is scrogged'));
        $p->add_error('veeblefitzer foobar');
        $this->assertEquals($p->errors,
                            array('mumble' => 'mumble is scrogged',
                                  '0' => 'veeblefitzer foobar'));
    }


    /**
     *  Test the find_all() method
     *  @todo Tests for limit, joins parameters
     */
    public function testFind_all() {

        //  Test return of the entire table
        $p = new PersonName;
        $GLOBALS['ACTIVE_RECORD_DB']->expect_query(
                 "SELECT * FROM person_names ",
                 new DB_find_all_result);
        $result = $p->find_all();
        $this->assertTrue(is_array($result));
        $this->assertEquals(count($result),2);
        $this->assertEquals(get_class($result[17]),'PersonName');
        $this->assertEquals($result[17]->first_name,'Ben');
        $this->assertEquals(get_class($result[23]),'PersonName');
        $this->assertEquals($result[23]->first_name,'Eileen');

        //  Conditions including "SELECT" should pass thru unedited
        $p = new PersonName;
        $GLOBALS['ACTIVE_RECORD_DB']->expect_query(
                 "SELECT mumble,foo FROM person_names",
                 new DB_find_all_result);
        $result = $p->find_all("SELECT mumble,foo FROM person_names");

        //  Conditions without "SELECT" should appear in WHERE clause
        $p = new PersonName;
        $GLOBALS['ACTIVE_RECORD_DB']->expect_query(
                 "SELECT * FROM person_names WHERE last_name = 'Dover' ",
                 new DB_find_all_result);
        $result = $p->find_all("last_name = 'Dover'");

        //  Orderings should appear in ORDER BY clause
        $p = new PersonName;
        $GLOBALS['ACTIVE_RECORD_DB']->expect_query(
                 "SELECT * FROM person_names ORDER BY last_name ",
                 new DB_find_all_result);
        $result = $p->find_all(null, "last_name");

        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the find_first() method
     *  @todo Tests for joins parameter
     */
    public function testFind_first() {
        $p = new PersonName;
        $GLOBALS['ACTIVE_RECORD_DB']->expect_query(
                 "SELECT * FROM person_names WHERE last_name = 'Dover' ",
                 new DB_find_all_result);
        $result = $p->find_first("last_name = 'Dover'");
        $this->assertEquals(get_class($result),'PersonName');
        $this->assertEquals($result->id,'17');
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the find() method
     *  @todo Tests for limit, joins parameters
     */
    public function testFind() {
        $p = new PersonName;

        //  Find by a single id value
        $GLOBALS['ACTIVE_RECORD_DB']->expect_query(
                 "SELECT * FROM person_names WHERE id='17' ",
                 new DB_find_all_result);
        $result = $p->find(17);
        $this->assertEquals(get_class($result),'PersonName');
        $this->assertEquals($result->id,'17');

        //  Find by an array of id values
        $GLOBALS['ACTIVE_RECORD_DB']->expect_query(
                 "SELECT * FROM person_names WHERE id IN(17,23) ",
                 new DB_find_all_result);
        $result = $p->find(array(17,23));
        $this->assertTrue(is_array($result));
        $this->assertEquals(count($result),2);
        $this->assertEquals(get_class($result[17]),'PersonName');
        $this->assertEquals($result[17]->first_name,'Ben');
        $this->assertEquals(get_class($result[23]),'PersonName');
        $this->assertEquals($result[23]->first_name,'Eileen');

        //  Find by WHERE clause expression
        $GLOBALS['ACTIVE_RECORD_DB']->expect_query(
                 "SELECT * FROM person_names WHERE last_name='Dover' ",
                 new DB_find_all_result);
        $result = $p->find("last_name='Dover'");
        //  First matching row should come back
        $this->assertEquals(get_class($result),'PersonName');
        $this->assertEquals($result->id,'17');
        // Remove the following line when you complete this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the after_create() method
     *  @todo Implement testAfter_create()
     */
    public function testAfter_create() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the after_delete() method
     *  @todo Implement testAfter_delete()
     */
    public function testAfter_delete() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the after_save() method
     *  @todo Implement testAfter_save()
     */
    public function testAfter_save() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the after_update() method
     *  @todo Implement testAfter_update()
     */
    public function testAfter_update() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the after_validation() method
     *  @todo Implement testAfter_validation()
     */
    public function testAfter_validation() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the after_validation_on_create() method
     *  @todo Implement testAfter_validation_on_create()
     */
    public function testAfter_validation_on_create() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the after_validation_on_update() method
     *  @todo Implement testAfter_validation_on_update()
     */
    public function testAfter_validation_on_update() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

	/**
	 *  Test the avg_all() method
	 *  @todo Implement testAvg_all()
	 */
    public function testAvg_all() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
	}

    /**
     *  Test the before_create() method
     *  @todo Implement testBefore_create()
     */
    public function testBefore_create() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the before_delete() method
     *  @todo Implement testBefore_delete()
     */
    public function testBefore_delete() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the before_save() method
     *  @todo Implement testBefore_save()
     */
    public function testBefore_save() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the before_update() method
     *  @todo Implement testBefore_update()
     */
    public function testBefore_update() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the before_validation() method
     *  @todo Implement testBefore_validation()
     */
    public function testBefore_validation() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the before_validation_on_create() method
     *  @todo Implement testBefore_validation_on_create()
     */
    public function testBefore_validation_on_create() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the before_validation_on_update() method
     *  @todo Implement testBefore_validation_on_update()
     */
    public function testBefore_validation_on_update() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the begin() method
     *  @todo Implement testBegin()
     */
    public function testBegin() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the column_for_attribute() method
     *  @todo Implement testColumn_for_attribute()
     */
    public function testColumn_for_attribute() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the commit() method
     *  @todo Implement testCommit()
     */
    public function testCommit() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

	/**
	 *  Test the count_all() method
	 *  @todo Implement testCount_all()
	 */
    public function testCount_all() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
	}

    /**
     *  Test the delete() method
     *  @todo Implement testDelete()
     */
    public function testDelete() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the delete_all() method
     *  @todo Implement testDelete_all()
     */
    public function testDelete_all() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the establish_connection() method
     *  @todo Implement testEstablish_connection()
     */
    public function testEstablish_connection() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the find_by_*() and find_all_by_*() methods
     */
    public function testFind_by() {
        // Test find_by_first_name()
        $p = new PersonName;
        $GLOBALS['ACTIVE_RECORD_DB']->expect_query(
                 "SELECT * FROM person_names WHERE first_name='Ben' ",
                 new DB_find_all_result);
        $result = $p->find_by_first_name('Ben');
        // Test find_by_first_name_and_last_name()
        $p = new PersonName;
        $GLOBALS['ACTIVE_RECORD_DB']->expect_query(
                 "SELECT * FROM person_names"
                 ." WHERE first_name='Ben' AND last_name='Dover' ",
                 new DB_find_all_result);
        $result = $p->find_by_first_name_and_last_name('Ben','Dover');
        // Test find_all_by_last_name()
        $p = new PersonName;
        $GLOBALS['ACTIVE_RECORD_DB']->expect_query(
                 "SELECT * FROM person_names WHERE last_name='Dover' ",
                 new DB_find_all_result);
        $result = $p->find_all_by_last_name('Dover');
        // Test find_all_by_first_name_and_last_name()
        $p = new PersonName;
        $GLOBALS['ACTIVE_RECORD_DB']->expect_query(
                 "SELECT * FROM person_names"
                 ." WHERE first_name='Ben' AND last_name='Dover' ",
                 new DB_find_all_result);
        $result = $p->find_all_by_first_name_and_last_name('Ben','Dover');
    }

    /**
     *  Test the find_by_sql() method
     *  @todo Implement testFind_by_sql()
     */
    public function testFind_by_sql() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the get_errors() method
     *  @todo Implement testGet_errors()
     */
    public function testGet_errors() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the get_errors_as_string() method
     *  @todo Implement testGet_errors_as_string()
     */
    public function testGet_errors_as_string() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the is_new_record() method
     *  @todo Implement testIs_new_record()
     */
    public function testIs_new_record() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the limit_select() method
     *  @todo Implement testLimit_select()
     */
    public function testLimit_select() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

	/**
	 *  Test the max_all() method
	 *  @todo Implement testMax_all()
	 */
    public function testMax_all() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
	}

	/**
	 *  Test the min_all() method
	 *  @todo Implement testMin_all()
	 */
    public function testMin_all() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
	}

    /**
     *  Test the page_list() method
     *  @todo Implement testPage_list()
     */
    public function testPage_list() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the raise() method
     *  @todo Implement testRaise()
     */
    public function testRaise() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the reload() method
     *  @todo Implement testReload()
     */
    public function testReload() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the rollback() method
     *  @todo Implement testRollback()
     */
    public function testRollback() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the save_without_validation() method
     *  @todo Implement testSave_without_validation()
     */
    public function testSave_without_validation() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the send() method
     *  @todo Implement testSend()
     */
    public function testSend() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     *  Test the set_content_columns() method
     *  @todo Implement testSet_content_columns()
     */
    public function testSet_content_columns() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

	/**
	 *  Test the set_table_name_using_class_name() method
	 *  @todo Implement testSet_table_name_using_class_name()
	 */
    public function testSet_table_name_using_class_name() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
	}

	/**
	 *  Test the sum_all() method
	 *  @todo Implement testSum_all()
	 */
    public function testSum_all() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
	}

	/**
	 *  Test the update() method
	 *  @todo Implement testUpdate()
	 */
    public function testUpdate() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
	}

	/**
	 *  Test the update_all() method
	 *  @todo Implement testUpdate_all()
	 */
    public function testUpdate_all() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
	}

	/**
	 *  Test the valid() method
	 *  @todo Implement testValid()
	 */
    public function testValid() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
	}

	/**
	 *  Test the validate() method
	 *  @todo Implement testValidate()
	 */
    public function testValidate() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
	}

	/**
	 *  Test the validate_on_create() method
	 *  @todo Implement testValidate_on_create()
	 */
    public function testValidate_on_create() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
	}

	/**
	 *  Test the validate_on_update() method
	 *  @todo Implement testValidate_on_update()
	 */
    public function testValidate_on_update() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
	}

	/**
	 *  Test the __call() method
	 *  @todo Implement test__call()
	 */
    public function test__call() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
	}

	/**
	 *  Test the _get() method
	 *  @todo Implement test_get()
	 */
    public function test_get() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
	}

	/**
	 *  Test the __set() method
	 *  @todo Implement test__set()
	 */
    public function test__set() {
        // Remove the following line when you implement this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
	}
}

// Call ActiveRecordTest::main() if this source file is executed directly.
if (PHPUnit2_MAIN_METHOD == "ActiveRecordTest::main") {
    ActiveRecordTest::main();
}

// -- set Emacs parameters --
// Local variables:
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
