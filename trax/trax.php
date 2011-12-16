<?php
/**
 *  Create Trax application work area
 *
 *  (PHP 5)
 *
 *  @package PHPonTrax
 *  @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *  @copyright (c) Walter O. Haas 2006
 *  @version $Id$
 *  @author Walt Haas <haas@xmission.com>
 */

/**
 *  Define where to find files to copy to the work area
 *
 *  Set automatically by the Pear installer when you install Trax with
 *  the <b>pear install</b> command.  If you are prevented from using
 *  <b>pear install</b>, change "@DATA-DIR@/PHPonTrax" by hand to the
 *  full filesystem path of the location where you installed the Trax
 *  distribution
 */
define("SOURCE_DIR", "@DATA-DIR@/PHPonTrax/data/");

/**
 *  Symbol substitution tables
 *
 *  $search and $replace below are used to perform substitutions of
 *  symbols in a file being copied.  $search is an array of
 *  Perl-compatible regular expressions, and $replace is a congruent
 *  array of replacements for RE matches.  So everywhere that the RE
 *  in, for example, $search[3] is matched in a file, the matching
 *  string is replaced by the contents of $replace[3].
 */
$search = array(
                '/@TRAX-CONFIG@/' // symbol for the full filesystem path
                                // to the Trax config/ directory in
                                // the user's work area
                );

$replace = array(
                 ''             // actual value of the full filesystem
                                // path to the Trax config/ directory
                                // in the user's work area
                 );


function trax() {

    global $search, $replace, $quiet;

    //  Get command line argument, if any
    if (!array_key_exists('argc',$GLOBALS) || ($GLOBALS['argc'] < 2)) {
        usage(); // print Usage message and exit
    }

	$first_param_is_option = (substr($GLOBALS['argv'][1], 0, 1) == "-") ? true : false;

    //  Check for excess arguments
    if ($GLOBALS['argc'] > 3 && !$first_param_is_option) {
        echo "unrecognized command argument ".$GLOBALS['argv'][2]."\n";
        usage();
    }

	if($first_param_is_option) {
		foreach($GLOBALS['argv'] as $arg) {
			if($arg == '-v' || $arg == '--version') {
				include(dirname(__FILE__)."/vendor/trax/trax.php");
				echo "Trax ".Trax::version()."\n";
				exit;
			} elseif($arg == '-h' || $arg == '--help') {
				usage();
			}
		}
	} else {
	    //  Destination directory on command line
	    $dstdir = $GLOBALS['argv'][1];
		if($GLOBALS['argv'][2] == '-q' || $GLOBALS['argv'][2] == '--quiet') {
			$quiet = true;
		}
	}

    //  Guarantee it ends with DIRECTORY_SEPARATOR
    if (substr($dstdir,-1,1) != DIRECTORY_SEPARATOR) {
        $dstdir .= DIRECTORY_SEPARATOR;
    }
    if (!create_dir($dstdir)) {
        return;
    }

    //  Assign real values for symbol substitution
    $replace[0] = realpath($dstdir).'/config'; // actual value of
                                // the full filesystem path to the
                                // Trax config/ directory in the
                                // user's work area

    $srcdir = SOURCE_DIR;
    //  copy source directory to destination directory
    copy_dir($srcdir, $dstdir);
}

/**
 *  Copy a directory with all its contents
 *
 *  When a file whose filename ends '.log' is created, its permissions
 *  are set to be world writable.
 *  @param string $src_path  Path to source directory
 *  @param string $dst_path  Path to destination directory
 *  @return boolean true=>success, false=>failure.
 */
function copy_dir($src_path, $dst_path) {

	global $quiet;

    //  Make sure we have directories as arguments
    if (!is_dir($src_path)) {
        echo $src_path." is not a directory\n";
        return false;
    }
    if (!is_dir($dst_path)) {
        echo $dst_path." is not a directory\n";
        return false;
    }

    //  Open the source directory
    $src_handle = opendir($src_path);
    if (!$src_handle) {
        echo "unable to open $src_path\n";
        return false;
    }

    //  Copy contents of source directory
    while (false !== ($src_file = readdir($src_handle))) {
        if (!is_dir($src_path . $src_file)) {

            //  If this file exists only to make the directory
            //  non-empty so that PackageFileManager will add it to
            //  the installable package, don't bother to copy it.
            if ($src_file == '.delete_this_file') {
                continue;
            }

            //  This is a regular file, need to copy it
            if (file_exists( $dst_path . $src_file )) {

                //  A destination file or directory with this name exists
                if (is_file( $dst_path . $src_file )) {

                    //  A regular destination file with this name exists.
                    //  Check whether it's different from source.
                    $src_content = file_get_contents($src_path . $src_file);
                    $dst_content = file_get_contents($dst_path . $src_file);
                    if ($src_content == $dst_content) {
                        //  Source and destination are identical
                        if(!$quiet) echo "\texists $dst_path$src_file\n";
                        continue;
                    }
                }

                //  New and old files differ.  Save the old file.
                $stat = stat($dst_path.$src_file);
                $new_name = $dst_path.$src_file.'.'.$stat[9];
                if (!rename($dst_path.$src_file,$new_name)) {
                    echo "unable to rename $dst_path$src_file to $new_name\n";
                    return false;
                }
                if(!$quiet) echo "\trenamed $src_file to ".($src_file.'.'.$stat[9])."\n";
            }

            //  Destination file does not exist.  Create it
            if (!copy_file($src_path . $src_file, $dst_path . $src_file)) {
                return false;
            }

            //  Log files need to be world writeable
            if (substr($src_file,-4,4) == '.log') {
                chmod($dst_path . $src_file, 0666);
            }

            // Generator & Console needs to be executable
            if ($src_file == 'generate.php' || $src_file == 'console.php') {
                chmod($dst_path . $src_file, 0754);
            }

            if(!$quiet) echo "\tcreate $dst_path$src_file\n";
        } else {

            //  This is a directory.  Ignore '.' and '..'
            if ( ($src_file == '.') || ($src_file == '..') ) {
                continue;
            }

            //  This directory needs to be copied.
            if (!create_dir( $dst_path . $src_file )) {
                return false;
            }

            //  Recursive call to copy directory
            if (!copy_dir($src_path . $src_file . DIRECTORY_SEPARATOR,
                          $dst_path . $src_file . DIRECTORY_SEPARATOR)) {
                return false;
            }
        }
    }
    closedir($src_handle);
    return true;
}

/**
 *  Create a directory if it doesn't exist
 *  @param string $dst_dir  Path of directory to create
 *  @param string $perms Chmod permissions (0775)
 *  @return boolean  true=>success, false=>failed
 */
function create_dir($dst_dir) {

	global $quiet;

    //  Does a directory of this name exist?
    if (file_exists( $dst_dir )) {

        //  A destination file or directory with this name exists
        if (is_dir( $dst_dir )) {

            //  A destination directory with this name exists.
            if(!$quiet) echo "\texists $dst_dir\n";
            return true;
        }

        //  There is an old destination file with the same
        //  name as the new destination directory.
        //  Save the old file.
        $stat = stat($dst_dir);
        $new_name = $dst_dir.'.'.$stat[9];
        if (!rename($dst_dir, $new_name)) {
            echo "unable to rename $dst_dir to $new_name\n";
            return false;
        }
        if(!$quiet) echo "\trenamed $dst_dir to $new_name\n";
    }

    //  Destination directory does not exist.  Create it
    if (!mkdir($dst_dir, 0775, true)) {
        return false;
    }
    if(!$quiet) echo "\tcreate ".$dst_dir."\n";
    return true;
}

/**
 *  Copy a Trax file into user's work area, substituting @TRAX-...@
 *
 *  @param string $src_path  Path to source file
 *  @param string $dst_path  Path to destination file
 *  @return boolean true=>success, false=>failure.
 */
function copy_file($src_path, $dst_path) {

    global $search, $replace;

    //  Read source file into a string
    if (!$file = file_get_contents($src_path)) {
        return false;
    }

    //  Substitute @TRAX-...@ symbols for appropriate values
    $file = preg_replace($search, $replace, $file);

    //  Write out file contents
    @file_put_contents($dst_path, $file);
    return true;
}

/**
 *  Output a Usage message and exit
 */
function usage() {
    echo "Usage: @BIN-DIR@".DIRECTORY_SEPARATOR."trax"
        ." ".DIRECTORY_SEPARATOR."path".DIRECTORY_SEPARATOR."to"
        .DIRECTORY_SEPARATOR."your".DIRECTORY_SEPARATOR."app [options]

Options:
	-q, --quiet			Suppress normal output.
	-v, --version			Show the Trax version number and quit.
	-h, --help			Show this help message and quit.

Description:
    The 'trax' command creates a new Trax application with a default
    directory structure and configuration at the path you specify.

Example:
    trax ".DIRECTORY_SEPARATOR."var".DIRECTORY_SEPARATOR."www"
        .DIRECTORY_SEPARATOR."html

    This generates a skeletal Trax installation in "
    .DIRECTORY_SEPARATOR."var".DIRECTORY_SEPARATOR."www"
    .DIRECTORY_SEPARATOR."html.
    See the README in the newly created application to get going.
\n";
    exit;
}

/**
 *  Main program
 */
trax();

// -- set Emacs parameters --
// Local variables:
// mode: php
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:

?>