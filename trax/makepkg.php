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
 *  @version $Id$
 */

require_once('PEAR/PackageFileManager2.php');
require_once('PEAR/Packager.php');

$packagexml = new PEAR_PackageFileManager2;

// Set package options
$e = $packagexml->setOptions(array(
    'baseinstalldir' => 'PHPonTrax',
    'packagedirectory' => '.',
    'filelistgenerator' => 'svn', // generate from svn or file
	'dir_roles' => array(
		'doc' => 'doc',
		'test' => 'test',
		'data' => 'data'
	),
	'exceptions' => array(
		'pear-trax' => 'script',
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
		'vendor/trax/templates/scaffolds/generator_templates/style.css' => 'php'
	),
	'installexceptions' => array(
		'pear-trax' => '/',
		'dispatch.php' => 'public'
	)
));
$packagexml->setPackage('PHPonTrax');
$packagexml->setSummary('Rapid Application Development Made Easy');
$packagexml->setDescription('PHP port of Ruby on Rails');
$packagexml->setNotes('We\'ve implemented many new and exciting features');
$packagexml->setChannel('pear.phpontrax.com');
$packagexml->setReleaseVersion('0.14.0');
$packagexml->setAPIVersion('0.14.0');
$packagexml->setReleaseStability('stable');
$packagexml->setAPIStability('stable');
$packagexml->setLicense('MIT License', 'http://www.opensource.org/licenses/mit-license.php');
$packagexml->setPackageType('php'); // this is a PEAR-style php script package

// Depends on PHP 5
$packagexml->setPhpDep('5.0.3');

// Depends on Pear 1.4.0 or greater
$packagexml->setPearinstallerDep('1.4.0');

// Depends on these PEAR packages
$packagexml->addPackageDepWithChannel('required', 'MDB2', 'pear.php.net', '2.0');
$packagexml->addPackageDepWithChannel('required', 'Mail', 'pear.php.net', '1.0');
$packagexml->addPackageDepWithChannel('required', 'Mail_Mime', 'pear.php.net', '1.0');

// Who maintains this package
$packagexml->addMaintainer('lead', 'john', 'John Peterson', 'john@mytechsupport.com');
$packagexml->addMaintainer('developer', 'haas', 'Walt Haas', 'haas@xmission.com');

// Substitute local configuration values for these symbols
$packagexml->addGlobalReplacement('pear-config', '@BIN-DIR@', 'bin_dir');
$packagexml->addGlobalReplacement('pear-config', '@DOC-DIR@', 'doc_dir');
$packagexml->addGlobalReplacement('pear-config', '@PHP-DIR@', 'php_dir');
$packagexml->addGlobalReplacement('pear-config', '@DATA-DIR@', 'data_dir');
$packagexml->addGlobalReplacement('pear-config', '@PHP-BIN@', 'php_bin');
$packagexml->addGlobalReplacement('pear-config', '@TEST-DIR@', 'test_dir');

// Platform-dependent command lines
$packagexml->addRelease(); // set up a release section
$packagexml->setOSInstallCondition('windows');
$packagexml->addInstallAs('pear-trax.bat', 'trax');
$packagexml->addIgnoreToRelease('pear-trax');
$packagexml->addRelease(); // add another release section for all other OSes
$packagexml->addInstallAs('pear-trax', 'trax');
$packagexml->addIgnoreToRelease('pear-trax.bat');

// create the <contents> tag
$packagexml->generateContents();

// Study the Subversion .svn directories to see what goes in the
// package, then write package.xml
$e = $packagexml->writePackageFile();
if(PEAR::isError($e)) {
    die($e->getMessage());
}

// Make a tarball of the files listed in package.xml
$packager = new PEAR_Packager;
$e = $packager->package();
if(PEAR::isError($e)) {
    die($e->getMessage());
}

// -- set Emacs parameters --
// Local variables:
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:

?>
