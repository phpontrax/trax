#! /usr/local/bin/php
<?php
/**
 *  Command line script to generate a Trax application
 *
 *  (PHP 5)
 *
 *  @package PHPonTrax
 *  @version $Id$
 *  @copyright (c) 2005 John Peterson
 *
 *  Permission is hereby granted, free of charge, to any person obtaining
 *  a copy of this software and associated documentation files (the
 *  "Software"), to deal in the Software without restriction, including
 *  without limitation the rights to use, copy, modify, merge, publish,
 *  distribute, sublicense, and/or sell copies of the Software, and to
 *  permit persons to whom the Software is furnished to do so, subject to
 *  the following conditions:
 *
 *  The above copyright notice and this permission notice shall be
 *  included in all copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 *  EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 *  MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 *  NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 *  LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 *  OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 *  WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 *
 *  <p>Sets up the Trax environment, creates a {@link TraxGenerator}
 *  object and calls its run() method to process the command line
 *  arguments to the script</p>
 *
 *  <p>Invoked from the command line by</p>
 *  <p>
 *  <samp>php script/generate.php</samp> <i>command [ arguments... ]</i>
 *  </p>
 *
 *  <p>See the {@link TraxGenerator} class definition for valid values
 *  of <i>command [ arguments... ]</i></p>
 */
if (substr(PHP_OS, 0, 3) == 'WIN') {
    ini_set("include_path",ini_get("include_path").";".dirname(dirname(__FILE__))."/lib"); 
} else {
    ini_set("include_path",ini_get("include_path").":".dirname(dirname(__FILE__)) . "/lib");
} 

/**
 *  Load definitions of the Trax environment from {@link environment.php}
 */
require_once(dirname(dirname(__FILE__)) . "/config/environment.php");

/**
 *  Load definition of the {@link TraxGenerator} class
 */
require_once("trax_generator.php");

$generator = new TraxGenerator();
$generator->run();

?>