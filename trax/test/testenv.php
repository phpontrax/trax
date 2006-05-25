<?php
/**
 *  Environment for Trax regression tests
 *
 * (PHP 5)
 *
 * @package PHPonTraxTest
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @copyright (c) Walter O. Haas 2006
 * @version $Id$
 * @author Walt Haas <haas@xmission.com>
 */

//  Test whether this is the Pear installed environment or the
//  development environment.  The comparison below will succeed in the
//  development environment but fail in the installed environment
//  because of symbol substitution by the Pear installer.
if ('@PHP-DIR@' == '@'.'PHP-DIR'.'@') {
    //  Development environment
    define("TRAX_LIB_ROOT", dirname(dirname(__FILE__))
            . DIRECTORY_SEPARATOR . 'vendor'
           . DIRECTORY_SEPARATOR . 'trax');
} else {
    //  Pear installed environment
    define("TRAX_LIB_ROOT", "@PHP-DIR@"
           . DIRECTORY_SEPARATOR . 'PHPonTrax'
           . DIRECTORY_SEPARATOR . 'vendor'
           . DIRECTORY_SEPARATOR . 'trax');
}
ini_set('include_path', '.' . PATH_SEPARATOR
        . dirname(__FILE__) . PATH_SEPARATOR
        . TRAX_LIB_ROOT . PATH_SEPARATOR . ini_get('include_path'));

# Bootstrap the Trax environment, framework, and default configuration
include_once(dirname(dirname(__FILE__)
             . DIRECTORY_SEPARATOR . 'config'
             . DIRECTORY_SEPARATOR . 'boot.php');

// -- set Emacs parameters --
// Local variables:
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>