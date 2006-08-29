#! /usr/local/bin/php -q
<?php
/**
 *  (PHP 5)
 *
 *  @package PHPonTrax
 *  @version $Id: generate.php 199 2006-05-05 01:52:43Z haas $
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
 * The console gives you access to your Trax Environments|Environment 
 * where you can interact with the domain model. Here you¡¦ll have all 
 * parts of the application configured, just like it is when the application 
 * is running. You can inspect domain models, change values, and save to 
 * the database.
 * 
 * 
 */

// If command line arguments exist, parse them
if(array_key_exists('argv', $_SERVER)) {
    if(array_key_exists(1, $_SERVER['argv'])) {
        $environment = strtolower($_SERVER["argv"][1]);
    }
}

// Set the environment to load
$_SERVER['TRAX_ENV'] = !is_null($environment) ? $environment : 'development';

/**
 *  Load definitions of the Trax environment from {@link environment.php}
 */
require_once(dirname(dirname(__FILE__)) . "/config/environment.php");

// Make sure the TRAX_ENV index is set
ActiveRecord::$active_connections[TRAX_ENV] = null;

echo "Loading Trax ".TRAX_ENV." environment.\n";
include("php_shell.php");

?>
