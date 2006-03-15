#!/usr/bin/php
<?php
/**
 *  Make a Pear installable package of the PHPonTrax distribution
 *
 *  (PHP 5)
 *  Requires Pear package PEAR_PackageFileManager.  It's Subversion
 *  plugin uses XML_Tree.  Unfortunately XML_Tree has a couple of
 *  methods named clone which is a reserved word in PHP 5.  The fix is
 *  easy, just edit XML_Tree to change every use of 'clone' to 'clone4'.
 *
 *  To make a package, connect to the top directory and type
 *  php makepkg.php (or in Unix-type systems, ./makepkg.php)
 *
 *  @package PHPonTrax
 *  @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *  @copyright (c) Walter O. Haas 2006
 *  @version $Id$
 *  @author Walt Haas <haas@xmission.com>
 */

require_once('PEAR/PackageFileManager.php');
require_once('PEAR/Packager.php');

$packagexml = new PEAR_PackageFileManager;

$e = $packagexml->setOptions(
          array('package' => 'PHPonTrax',
                'summary' => 'Rapid Application Development Made Easy',
                'description' => 'PHP port of Ruby on Rails',
                'baseinstalldir' => 'PHPonTrax',
                'version' => '0.12.2',
                'packagedirectory' => '.',
                'state' => 'alpha',
                'filelistgenerator' => 'svn', // generate from svn
                'notes' => 'We\'ve implemented many new and exciting features',
                'ignore' => array('app/', 'components/', 'config/', 'db/',
                                  'log/'),
//  'installexceptions' => array('phpdoc' => '/*'), // baseinstalldir ="/" for phpdoc
                'dir_roles' => array('doc' => 'doc',
                                     'tutorials' => 'doc',
                                     'test' => 'test'),
//                'exceptions' => array('README' => 'doc')
                ));
if (PEAR::isError($e)) {
    echo $e->getMessage();
    die();

 }

//  Depends on PHP 5
$e = $packagexml->addDependency('php','5.0.3','ge','php','no');
if (PEAR::isError($e)) {
    echo $e->getMessage();
    die();

 }

//  Depends on these PEAR modules
$e = $packagexml->addDependency('DB','1.0');
if (PEAR::isError($e)) {
    echo $e->getMessage();
    die();

 }
$e = $packagexml->addDependency('Mail','1.0');
if (PEAR::isError($e)) {
    echo $e->getMessage();
    die();

 }
$e = $packagexml->addDependency('Mail_Mime','1.0');
if (PEAR::isError($e)) {
    echo $e->getMessage();
    die();

 }

//  Optionally uses these PEAR modules
$e = $packagexml->addDependency('PhpDocumentor','1.3.0RC4','ge','pkg','yes');
if (PEAR::isError($e)) {
    echo $e->getMessage();
    die();

 }
$e = $packagexml->addDependency('PHPUnit2','1.0','ge','pkg','yes');
if (PEAR::isError($e)) {
    echo $e->getMessage();
    die();

 }

//  Who maintains this package
$e = $packagexml->addMaintainer('john','lead','John Peterson',
                                'john@mytechsupport.com');
if (PEAR::isError($e)) {
    echo $e->getMessage();
    die();

 }

$e = $packagexml->addMaintainer('haas','developer','Walt Haas',
                                'haas@xmission.com');
if (PEAR::isError($e)) {
    echo $e->getMessage();
    die();

 }

/*
 * $e = $test->addPlatformException('pear-phpdoc.bat', 'windows');
 * if (PEAR::isError($e)) {
 *     echo $e->getMessage();
 *     exit;
 * }
 */
//$packagexml->addRole('pkg', 'doc'); // add a new role mapping
//if (PEAR::isError($e)) {
//    echo $e->getMessage();
//    exit;
// }

/*
 * // replace @PHP-BIN@ in this file with the path to php executable!  pretty neat
 * $e = $test->addReplacement('pear-phpdoc', 'pear-config', '@PHP-BIN@', 'php_bin');
 * if (PEAR::isError($e)) {
 *     echo $e->getMessage();
 *     exit;
 * }
 * $e = $test->addReplacement('pear-phpdoc.bat', 'pear-config', '@PHP-BIN@', 'php_bin');
 * if (PEAR::isError($e)) {
 *     echo $e->getMessage();
 *     exit;
 * }
 */
// note use of {@link debugPackageFile()} - this is VERY important
//if (isset($_GET['make']) || (isset($_SERVER['argv'][2]) &&
//       $_SERVER['argv'][2] == 'make')) {
     $e = $packagexml->writePackageFile();
// } else {

//  Needs: XML_Tree with patch s/clone/clone4/g
//$e = $packagexml->debugPackageFile();

// }

if (PEAR::isError($e)) {
    echo $e->getMessage();
    die();
 }

$packager = new PEAR_Packager;

$packager->package();

// -- set Emacs parameters --
// Local variables:
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:

?>
