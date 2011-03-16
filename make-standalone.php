#! /usr/local/bin/php
<?php
/**
 *  Make a standalone ready to go PHPonTrax application 
 *
 *  (PHP 5)
 *
 *  Simply run <b>php make-standalone.php</b>.
 *  Once ran this script will generate a folder "trax-standalone" and a tar file of that folder.
 *  That folder is the standalone version that you can used to build an app just as if you ran
 *  the "trax ." command and generated a fresh app.
 *
 *  This script is mainly for me to make the standalone version easily, but included
 *  it for anyone that wants to play with it. 
 *
 *  Required to use this is Unix-type system (I think), wget, tar, svn, PHPonTrax already installed.
 *
 *  @package PHPonTrax
 *  @version $Id$
 */

echo "cleaning up old builds of trax-standalone\n";
exec("rm -Rf trax-standalone*");
echo "creating folder trax-standalone\n";
mkdir("trax-standalone");

if(!is_dir("trax-standalone")) {
	echo "no trax-standalone dir\n";
	exit;
}

chdir("trax-standalone");

echo "creating trax skeleton files\n";
exec("trax ".dirname(__FILE__)."/trax-standalone");

echo "fetching README\n";
exec("wget http://www.phpontrax.com/downloads/README-standalone.txt");
rename("README-standalone.txt", "README");
 
chdir("vendor");
echo "fetching PEAR files\n";
exec("wget http://www.phpontrax.com/downloads/PEAR.tar.gz");
echo "untarring PEAR files\n";
exec("tar zxvf PEAR.tar.gz");
echo "removing PEAR tar file.\n";
unlink("PEAR.tar.gz");
echo "creating vendor/trax folder\n";
mkdir("trax");
echo "copying Trax files to vendor folder\n";
exec("cp -Rp ../../trax/vendor/trax/* trax");
#echo "checking out edge Trax from svn\n";
#exec("svn co svn://svn.phpontrax.com/trax/trunk/trax/vendor/trax trax");

chdir("../");
echo "updating config files\n";
$htaccess = file_get_contents("./public/.htaccess");
file_put_contents("./public/.htaccess", str_replace(dirname(__FILE__)."/trax-standalone/config", "/home/username/trax/config", $htaccess));
$environment = file_get_contents("./config/environment.php");
$environment = str_replace("# define(\"PHP_LIB_ROOT\",    \"/usr/local/lib/php\");", "define(\"PHP_LIB_ROOT\",    dirname(dirname(__FILE__)).\"/vendor/PEAR\");", $environment);
$environment = str_replace("# define(\"TRAX_ROOT\",       dirname(dirname(__FILE__)));", "define(\"TRAX_ROOT\",       dirname(dirname(__FILE__)));", $environment);
file_put_contents("./config/environment.php", $environment);
include("./vendor/trax/trax.php");
$version = Trax::version();
chdir("../");
exec("tar zcvf trax-standalone-".$version.".tar.gz trax-standalone");

echo "done creating standalone Trax\n";

?>
