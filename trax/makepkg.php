#!@PHP-BIN@
<?php
/**
 *  Make a Pear installable package of the PHPonTrax distribution
 *
 *  (PHP 5)
 *
 *  To make a package, connect to the top directory and type
 *  <b>php makepkg.php</b> (or on Unix-type systems, <b>./makepkg.php</b>).
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
 *  PackageFileManager has several undocumented limitations that
 *  seriously affect what you can do with it:
 *  <ul>
 *    <li>PackageFileManager will not add an empty directory to a
 *      package.  Therefore you need to put at least one file in any
 *      directory that is to go into a package.</li>
 *    <li>The Pear Installer will not install an empty file. Therefore
 *      you need to put at least one character into any file to be
 *      installed as part of a package.</li> 
 *    <li>The PackageFileManager options 'include' and 'ignore' use a
 *      regular expression match to identify the files and directories
 *      that they affect.  For each file and directory managed by
 *      Subversion, PackageFileManager first attempts to apply the
 *      RE pattern as coded.  Then it appends leading and trailing '/'
 *      to the pattern and tries again.  The results are hard to
 *      predict.</li>
 *  </ul>
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
                'version' => '221svn',
                'packagedirectory' => '.',
                'state' => 'alpha',
                'filelistgenerator' => 'svn', // generate from svn
                'notes' => 'We\'ve implemented many new and exciting features',
                'dir_roles' => array('doc' => 'doc',
                                     'test' => 'test',
                                     'data' => 'data'),
                'exceptions' => array('pear-trax' => 'script',
                                      'pear-trax.bat' => 'script',
 'vendor/trax/templates/error.phtml' => 'php',
 'vendor/trax/templates/view.phtml' => 'php',
 'vendor/trax/templates/mailer_view.phtml' => 'php',
 'vendor/trax/templates/scaffolds/add.phtml' => 'php',
 'vendor/trax/templates/scaffolds/edit.phtml' => 'php',
 'vendor/trax/templates/scaffolds/index.phtml' => 'php',
 'vendor/trax/templates/scaffolds/layout.phtml' => 'php',
 'vendor/trax/templates/scaffolds/show.phtml' => 'php',
 'vendor/trax/templates/scaffolds/scaffold.css' => 'php',
 'vendor/trax/templates/scaffolds/generator_templates/form_scaffolding.phtml' => 'php',
 'vendor/trax/templates/scaffolds/generator_templates/layout.phtml' => 'php',
 'vendor/trax/templates/scaffolds/generator_templates/view_add.phtml' => 'php',
 'vendor/trax/templates/scaffolds/generator_templates/view_edit.phtml' => 'php',
 'vendor/trax/templates/scaffolds/generator_templates/view_index.phtml' => 'php',
 'vendor/trax/templates/scaffolds/generator_templates/view_show.phtml' => 'php',
 'vendor/trax/templates/scaffolds/generator_templates/style.css' => 'php',
),
                'installexceptions' => array('pear-trax' => '/',
                                             'dispatch.php' => 'public'),
                'installas' => array('pear-trax' => 'trax',
                                     'pear-trax.bat' => 'trax')
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
$e = $packagexml->addDependency('PHPUnit2','1.0');
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

$e = $packagexml->addPlatformException('pear-trax', '*ix|*ux|*BSD|Darwin');
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
