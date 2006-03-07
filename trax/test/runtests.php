#!/usr/bin/php -q
<?php
/**
 *  Regression test for the {@link PHPonTrax} package
 *
 * (PHP 5)
 *
 * @package PHPonTraxTest
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @copyright (c) Walter O. Haas 2006
 * @version $Id$
 * @author Walt Haas <haas@xmission.com>
 */

//  Control order of tests
$tests = array(
               //  TraxError used by ActiveRecordError, ActionControllerError
               'TraxErrorTest.php',
               'ActionControllerErrorTest.php',
               'ActiveRecordErrorTest.php',
               //  Inflector is used by many classes
               'InflectorTest.php',
               //  Router is used by ActionController
               'RouterTest.php',
               'ActionControllerTest.php',
               //  ScaffoldController extends ActionController
               'ScaffoldControllerTest.php',
               //  ApplicationController extends ActionController
               'ApplicationControllerTest.php',
               'ActionMailerTest.php',
               'ActiveRecordTest.php',
               'ActiveRecordHelperTest.php',
               'ApplicationMailerTest.php',
               'AssetTagHelperTest.php',
               'DateHelperTest.php',
               'DispatcherTest.php',
               'FormHelperTest.php',
               'FormOptionsHelperTest.php',
               'FormTagHelperTest.php',
               'HelpersTest.php',
               'InputFilterTest.php',
               'JavaScriptHelperTest.php',
               'SessionTest.php',
               'TraxGeneratorTest.php',
               'UrlHelperTest.php',
			   );

foreach ($tests as $test) {
    passthru("phpunit $test",$rc);
    if ($rc) {
        echo "Test Failed!!!\n";
        break;
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
