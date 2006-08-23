<?php
/**
 *  (PHP 5)
 *
 *  @package PHPonTrax
 *  @version $Id:$
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
 */
 
@ob_end_clean();
error_reporting(E_ALL);
set_time_limit(0);

require_once("php_shell/shell.php");
    
/**
* default error-handler
*
* Instead of printing the NOTICE or WARNING from php we wan't the turn non-FATAL
* messages into exceptions and handle them in our own way.
*
* you can set your own error-handler by createing a function named
* __shell_error_handler
*
* @param integer $errno Error-Number
* @param string $errstr Error-Message
* @param string $errfile Filename where the error was raised
* @param interger $errline Line-Number in the File
* @param mixed $errctx ...
*/
function __shell_default_error_handler($errno, $errstr, $errfile, $errline, $errctx) {
    ## ... what is this errno again ?
    if ($errno == 2048) return;
  
    throw new Exception(sprintf("%s:%d\r\n%s", $errfile, $errline, $errstr));
}

set_error_handler("__shell_default_error_handler");

$__shell = new PHP_Shell();

$f = <<<EOF
>> use '?' to open the inline help 
EOF;

printf($f, 
    $__shell->getVersion(), 
    $__shell->hasReadline() ? ', with readline() support' : '');
unset($f);

print $__shell->getColour("default");
while($__shell->input()) {
    try {
        if ($__shell->parse() == 0) {
            ## we have a full command, execute it

            if ($__shell->isAutoloadEnabled() && !function_exists('__autoload')) {
                /**
                * default autoloader
                *
                * If a class doesn't exist try to load it by guessing the filename
                * class PHP_Shell should be located in PHP/Shell.php.
                *
                * you can set your own autoloader by defining __autoload() before including
                * this file
                * 
                * @param string $classname name of the class
                */

                function __autoload($classname) {
                    include str_replace('_', '/', $classname).'.php';
                }
            }

            $__shell_retval = eval($__shell->getCode());        
            if (isset($__shell_retval)) {
                print $__shell->getColour("value");

                if (function_exists("__shell_print_var")) {
                    __shell_print_var($__shell_retval, $__shell->getVerbose());
                } else {
                    var_export($__shell_retval);
                }
            }
            ## cleanup the variable namespace
            unset($__shell_retval);
            $__shell->resetCode();
        }
    } catch(Exception $__shell_exception) {
        print $__shell->getColour("exception");
        print $__shell_exception->getMessage();
        
        $__shell->resetCode();

        ## cleanup the variable namespace
        unset($__shell_exception);
    }
    print $__shell->getColour("default");
}

print $__shell->getColour("reset");
 
?>