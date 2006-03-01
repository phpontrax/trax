#!/usr/bin/php -q
<?php
/**
 *  File for the SessionTest class
 *
 * (PHP 5)
 *
 * @package PHPonTraxTest
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @copyright (c) Walter O. Haas 2006
 * @version $Id$
 * @author Walt Haas <haas@xmission.com>
 */

echo "testing Session\n";

// Call SessionTest::main() if this source file is executed directly.
if (!defined("PHPUnit2_MAIN_METHOD")) {
    define("PHPUnit2_MAIN_METHOD", "SessionTest::main");
}

require_once "PHPUnit2/Framework/TestCase.php";
require_once "PHPUnit2/Framework/TestSuite.php";

// You may remove the following line when all tests have been implemented.
require_once "PHPUnit2/Framework/IncompleteTestError.php";

require_once "../vendor/trax/session.php";

/**
 * Test class for Session.
 */
class SessionTest extends PHPUnit2_Framework_TestCase {

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main() {
        require_once "PHPUnit2/TextUI/TestRunner.php";

        $suite  = new PHPUnit2_Framework_TestSuite("SessionTest");
        $result = PHPUnit2_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp() {
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown() {
    }

    /**
     * @todo Implement testGet()
     */
    public function testGet() {
        // Remove the following line when you complete this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     * @todo Implement testSet()
     */
    public function testSet() {
        // Remove the following line when you complete this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
      * @todo Implement testIs_valid_host()
     */
    public function testIs_valid_host() {
        // Remove the following line when you complete this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     * @todo Implement testIs_aol_host()
     */
    public function testIs_aol_host() {
        // Remove the following line when you complete this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     * @todo Implement testGet_hash()
     */
    public function testGet_hash() {
        // Remove the following line when you complete this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     * @todo Implement testStart()
     */
    public function testStart() {
        // Remove the following line when you complete this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     * @todo Implement testDestroy_session()
     */
    public function testDestroy_session() {
        // Remove the following line when you complete this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     * @todo Implement testUnset_session()
     */
    public function testUnset_session() {
        // Remove the following line when you complete this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     * @todo Implement testUnset_var()
     */
    public function testUnset_var() {
        // Remove the following line when you complete this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     * @todo Implement testIsset_var()
     */
    public function testIsset_var() {
        // Remove the following line when you complete this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }

    /**
     * @todo Implement testIsset_flash()
     */
    public function testIsset_flash() {
        // Remove the following line when you complete this test.
        throw new PHPUnit2_Framework_IncompleteTestError;
    }
}

// Call SessionTest::main() if this source file is executed directly.
if (PHPUnit2_MAIN_METHOD == "SessionTest::main") {
    SessionTest::main();
}

// -- set Emacs parameters --
// Local variables:
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
