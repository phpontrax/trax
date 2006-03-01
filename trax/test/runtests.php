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
               'ActionControllerTest.php',
               'ActionControllerErrorTest.php',
               'ActionMailerTest.php',
               'ActiveRecordTest.php',
               'ActiveRecordErrorTest.php',
               'ActiveRecordHelperTest.php',
               'ApplicationControllerTest.php',
               'ApplicationMailerTest.php',
               'AssetTagHelperTest.php',
               'DateHelperTest.php',
               'DispatcherTest.php',
               'FormHelperTest.php',
               'FormOptionsHelperTest.php',
               'FormTagHelperTest.php',
               'HelpersTest.php',
               'InflectorTest.php',
               'InputFilterTest.php',
               'JavaScriptHelperTest.php',
               'RouterTest.php',
               'ScaffoldControllerTest.php',
               'SessionTest.php',
               'TraxErrorTest.php',
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
