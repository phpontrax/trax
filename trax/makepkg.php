#!@PHP-BIN@
<?php
/**
 *  Make a Pear installable package of the PHPonTrax distribution
 *
 *  (PHP 5)
 *
 *  To make a package, connect to the top directory and type
 *  php makepkg.php (or on Unix-type systems, ./makepkg.php)
 *  Information about how to build the package and what to put in it
 *  comes from two sources: this script, and the information
 *  maintained by {@link http://subversion.tigris.org Subversion} in
 *  the various .svn directories that identifies which files are part
 *  of the distribution.
 *  
 *  Requires Pear package
 *  {@link http://pear.php.net/package/PEAR_PackageFileManager PEAR_PackageFileManager} .
 *  The Subversion plugin uses
 *  {@link http://pear.php.net/package/XML_Tree XML_Tree} .
 *  Unfortunately XML_Tree has a couple of methods named
 *  {@link http://www.php.net/manual/en/language.oop5.cloning.php clone}
 *  which is a reserved word in PHP 5.  The fix is 
 *  easy, just edit XML_Tree to change every use of 'clone' to 'clone4'.
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
                'dir_roles' => array('doc' => 'doc',
                                     'test' => 'test',
                                     'data' => 'data'),
                'exceptions' => array('pear-trax' => 'script'),
                'installexceptions' => array('pear-trax' => '/'),
                'installas' => array('pear-trax' => 'trax')
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

//  Substitute local configuration values for these symbols
$e = $packagexml->addGlobalReplacement('pear-config', '@BIN-DIR@',
                                       'bin_dir');
if (PEAR::isError($e)) {
    echo $e->getMessage();
    die();

 }

$e = $packagexml->addGlobalReplacement('pear-config', '@DOC-DIR@',
                                       'doc_dir');
if (PEAR::isError($e)) {
    echo $e->getMessage();
    die();

 }

$e = $packagexml->addGlobalReplacement('pear-config', '@PHP-DIR@',
                                       'php_dir');
if (PEAR::isError($e)) {
    echo $e->getMessage();
    die();

 }

$e = $packagexml->addGlobalReplacement('pear-config', '@DATA-DIR@',
                                       'data_dir');
if (PEAR::isError($e)) {
    echo $e->getMessage();
    die();

 }

$e = $packagexml->addGlobalReplacement('pear-config', '@PHP-BIN@',
                                       'php_bin');
if (PEAR::isError($e)) {
    echo $e->getMessage();
    die();

 }

$e = $packagexml->addGlobalReplacement('pear-config', '@TEST-DIR@',
                                       'test_dir');
if (PEAR::isError($e)) {
    echo $e->getMessage();
    die();

 }

//  Platform-dependent command lines
$e = $packagexml->addPlatformException('pear-trax.bat', 'windows');
if (PEAR::isError($e)) {
    echo $e->getMessage();
    exit;
 }

$e = $packagexml->addPlatformException('pear-trax', '*ix|*ux');
if (PEAR::isError($e)) {
    echo $e->getMessage();
    exit;
 }

//  Study the Subversion .svn directories to see what goes in the
//  package, then write package.xml
//  (Needs: XML_Tree with patch s/clone/clone4/g)
$e = $packagexml->writePackageFile();
if (PEAR::isError($e)) {
    echo $e->getMessage();
    die();
 }

//  Make a tarball of the files listed in package.xml
$packager = new PEAR_Packager;
$e = $packager->package();
if (PEAR::isError($e)) {
    echo $e->getMessage();
    die();
 }

// -- set Emacs parameters --
// Local variables:
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:

?>
