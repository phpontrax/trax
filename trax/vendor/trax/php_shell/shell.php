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
 
/*
vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
(c) 2006 Jan Kneschke <jan@kneschke.de>

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
of the Software, and to permit persons to whom the Software is furnished to do
so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

/**
* A interactive PHP Shell
* 
* The more I work with other languages like python and ruby I like their way how they
* work on problems. While PHP is very forgiving on errors, it is weak on the debugging
* side. It was missing a simple to use interactive shell for years. Python and Ruby have
* their ipython and iruby shell which give you a direct way to interact with the objects.
* No need to write a script and execute it afterwards.
* 
* ChangeLog
* 
* 
* Starting the Shell:
* 
* If you have a php-cli at hand you can open the shell by defining 'SHELL' 
* and opening the PHP_Shell class file. 
* 
* <code>
* $ php -v
* PHP 5.1.4 (cli) (built: May  7 2006 20:52:45)
* Copyright (c) 1997-2006 The PHP Group
* Zend Engine v2.1.0, Copyright (c) 1998-2006 Zend Technologies
* 
* $ php -r "define('SHELL', 1); require 'PHP/Shell.php';" 
* </code>
* If you only have php-cgi write a php-script:
* 
* <code>
*     error_reporting(E_ALL);
* 
*     define("SHELL", 1);
*     ## in case your terminal support colours:
*     define("SHELL_HAS_COLOUR", 1);
* 
*     require "PHP/Shell.php";
* </code>
* 
* and execute it with:
* 
* <pre>
* $ php -q php-shell.php
* </pre>
* 
* Inline Help
*
* <pre>
* PHP-Shell - Version 0.2.0, with readline() support
* (c) 2006, Jan Kneschke <jan@kneschke.de>
* released under the terms of the PHP License 2.0
* 
* >> use '?' to open the inline help
* 
* >> ?
* "inline help for the PHP-shell
* 
*   >> ?
*     print this help
*   >> ? <topic>
*     get the doccomment for a class, method, property or function
*   >> p <var>
*     execute a verbose print (if implemented)
*   >> quit
*     leave shell
* "
* >> ? PHP_Shell
* </pre>
* Alternatives
* 
* - http://david.acz.org/phpa/
* - http://www.hping.org/phpinteractive/
* - the embedded interactive php-shell: $ php -a 
* 
* @package PHP
*/

/**
* PHP_Shell
*
* a interactive PHP Shell with tab-completion and history
* it can catch FATAL errors before executing the code
*
* to customize the operation of the shell you can either 
* extend the PHP_Shell class or declare a external autoload 
* or error-handler. If you want to use your own print-out
* functions declare __shell_print_vars(). 
*
* - __shell_error_handler() 
* - __autoload()
* - __shell_print_vars()
*
* To keep the namespace clashing between shell and your program 
* as small as possible all public variables and functions from
* the shell are prefixed with __shell:
* 
* - $__shell is the object of the shell
*   can be read, this is the shell object itself, don't touch it
* - $__shell_retval is the return value of the eval() before 
*   it is printed
*   can't be read, but overwrites existing vars with this name
* - $__shell_exception is the catched Exception on Warnings, Notices, ..
*   can't be read, but overwrites existing vars with this name
* 
* @package PHP
*/

require_once("php_shell/shell_prototypes.php");

class PHP_Shell {
    /** 
    * current code-buffer
    * @var string
    */
    protected $code; 

    /** 
    * set if 'p ...' is executed 
    * @var bool
    */
    protected $verbose; 

    /** 
    * set if readline support is enabled 
    * @var bool
    */
    protected $have_readline; 

    /** 
    * current version of the class 
    * @var string
    */
    protected $version = '0.2.7';

    /**
    * registered commands
    *
    * @var array
    * @see registerCommand
    */
    protected $commands;

    /**
    * does the use want to use the internal autoload ? 
    *
    * @var bool
    */
    protected $autoload = false;
    

    /**
    * shell colours
    *
    * @var array
    * @see setColourScheme
    */
    protected $colours;

    /**
    * shell colour schemes
    *
    * @var array
    * @see registerColourScheme
    */
    protected $colour_scheme;

    # shell colours
    const C_RESET = "\033[0m";

    const C_BLACK = "\033[0;30m";
    const C_RED = "\033[0;31m";
    const C_GREEN = "\033[0;32m";
    const C_BROWN = "\033[0;33m";
    const C_BLUE = "\033[0;34m";
    const C_PURPLE = "\033[0;35m";
    const C_CYAN = "\033[0;36m";
    const C_LIGHT_GRAY = "\033[0;37m";

    const C_GRAY = "\033[1;30m";
    const C_LIGHT_RED = "\033[1;31m";
    const C_LIGHT_GREEN = "\033[1;32m";
    const C_YELLOW = "\033[1;33m";
    const C_LIGHT_BLUE = "\033[1;34m";
    const C_LIGHT_PURPLE = "\033[1;35m";
    const C_LIGHT_CYAN = "\033[1;36m";
    const C_WHITE = "\033[1;37m";

    /**
    * init the shell and change if readline support is available
    */ 
    public function __construct() {
        $this->code = '';
        $this->vars = array();

        $this->stdin = null;

        $this->have_readline = function_exists('readline');

        if ($this->have_readline) {
            readline_completion_function('__shell_readline_complete');
        }

        $this->use_readline = true;

        $this->commands = array();

        $this->registerCommand('#^quit$#', 'cmdQuit', 'quit', 'leaves the shell');
        $this->registerCommand('#^\?$#', 'cmdHelp', '?', 'show this help');
        $this->registerCommand('#^\? #', 'cmdHelp', '? <var>', 'show the DocComment a Class, Method or Function'.PHP_EOL.'    e.g.: ? fopen(), ? PHP_Shell, ? $__shell');
        $this->registerCommand('#^p #', 'cmdPrint', 'p <var>', 'print the variable verbosly');
        $this->registerCommand('#^:set #', 'cmdSet', ':set <var>', 'set a shell variable');

        $this->registerColourScheme(
            "plain", array( 
                "default"   => "", "value"     => "",
                "exception" => "", "reset"     => ""));

        $this->registerColourScheme(
            "dark", array( 
                "default"   => PHP_SHELL::C_YELLOW, 
                "value"     => PHP_SHELL::C_WHITE,
                "exception" => PHP_SHELL::C_PURPLE));

        $this->registerColourScheme(
            "light", array( 
                "default"   => PHP_SHELL::C_BLACK, 
                "value"     => PHP_SHELL::C_BLUE,
                "exception" => PHP_SHELL::C_RED));

    }

    /**
    * register your own command for the shell
    *
    * @param string $regex a regex to match against the input line
    * @param string $callback a method in this class to call of the regex matches
    * @param string $cmd the command string for the help
    * @param string $help the full help description for this command
    */
    public function registerCommand($regex, $callback, $cmd, $help) {
        $this->commands[] = array(
            'regex' => $regex,
            'method' => $callback,
            'command' => $cmd,
            'description' => $help
        );
    }
    
    /**
    * parse the PHP code
    *
    * we parse before we eval() the code to
    * - fetch fatal errors before they come up
    * - know about where we have to wait for closing braces
    *
    * @return int 0 if a executable statement is in the code-buffer, non-zero otherwise
    */
    public function parse() {
        ## remove empty lines
        $this->code = trim($this->code);
        if ($this->code == '') return 1;

        $t = token_get_all('<?php '.$this->code.' ?>');
  
        $need_semicolon = 1; /* do we need a semicolon to complete the statement ? */
        $need_return = 1;    /* can we prepend a return to the eval-string ? */
        $eval = '';          /* code to be eval()'ed later */
        $braces = array();   /* to track if we need more closing braces */

        $methods = array();  /* to track duplicate methods in a class declaration */
        $ts = array();       /* tokens without whitespaces */
        
        foreach ($t as $ndx => $token) {
            if (is_array($token)) {
                $ignore = 0;
      
                switch($token[0]) {
                case T_WHITESPACE:
                case T_OPEN_TAG:
                case T_CLOSE_TAG:
                    $ignore = 1;
                    break;
                case T_FOREACH:
                case T_DO:
                case T_WHILE:
                case T_FOR:

                case T_IF:
                case T_RETURN:
                    
                case T_CLASS:
                case T_FUNCTION:
                case T_INTERFACE:

                case T_PRINT:
                case T_ECHO:

                case T_COMMENT:
                case T_UNSET:

                case T_INCLUDE:
                case T_REQUIRE:
                case T_INCLUDE_ONCE:
                case T_REQUIRE_ONCE:
                case T_TRY:
                    $need_return = 0;
                    break;
                case T_VARIABLE:
                case T_STRING:
                case T_NEW:
                case T_EXTENDS:
                case T_IMPLEMENTS:
                case T_OBJECT_OPERATOR:
                case T_DOUBLE_COLON:
                case T_INSTANCEOF:

                case T_CATCH:

                case T_ELSE:
                case T_AS:
                case T_LNUMBER:
                case T_DNUMBER:
                case T_CONSTANT_ENCAPSED_STRING:
                case T_ENCAPSED_AND_WHITESPACE:
                case T_CHARACTER:
                case T_ARRAY:
                case T_DOUBLE_ARROW:

                case T_CONST:
                case T_PUBLIC:
                case T_PROTECTED:
                case T_PRIVATE:
                case T_ABSTRACT:
                case T_STATIC:
                case T_VAR:

                case T_INC:
                case T_DEC:
                case T_SL:
                case T_SL_EQUAL:
                case T_SR:
                case T_SR_EQUAL:

                case T_IS_EQUAL:
                case T_IS_IDENTICAL:
                case T_IS_GREATER_OR_EQUAL:
                case T_IS_SMALLER_OR_EQUAL:
                    
                case T_BOOLEAN_OR:
                case T_LOGICAL_OR:
                case T_BOOLEAN_AND:
                case T_LOGICAL_AND:
                case T_LOGICAL_XOR:
                case T_MINUS_EQUAL:
                case T_PLUS_EQUAL:
                case T_MUL_EQUAL:
                case T_DIV_EQUAL:
                case T_MOD_EQUAL:
                case T_XOR_EQUAL:
                case T_AND_EQUAL:
                case T_OR_EQUAL:

                case T_FUNC_C:
                case T_CLASS_C:
                case T_LINE:
                case T_FILE:

                    /* just go on */
                    break;
                default:
                    /* debug unknown tags*/
                    error_log(sprintf("unknown tag: %d (%s): %s".PHP_EOL, $token[0], token_name($token[0]), $token[1]));
                    
                    break;
                }
                if (!$ignore) {
                    $eval .= $token[1]." ";
                    $ts[] = array("token" => $token[0], "value" => $token[1]);
                }
            } else {
                $ts[] = array("token" => $token, "value" => '');

                $last = count($ts) - 1;

                switch ($token) {
                case '(':
                    /* walk backwards through the tokens */

                    if ($last >= 3 &&
                        $ts[$last - 1]['token'] == T_STRING &&
                        $ts[$last - 2]['token'] == T_OBJECT_OPERATOR &&
                        $ts[$last - 3]['token'] == T_VARIABLE ) {

                        /* $object->method( */

                        /* $object has to exist and has to be a object */
                        $objname = $ts[$last - 3]['value'];
                       
                        if (!isset($GLOBALS[ltrim($objname, '$')])) {
                            throw new Exception(sprintf('Variable \'%s\' is not set', $objname));
                        }
                        $object = $GLOBALS[ltrim($objname, '$')];

                        if (!is_object($object)) {
                            throw new Exception(sprintf('Variable \'%s\' is not a class', $objname));
                        }
                        
                        $method = $ts[$last - 1]['value'];

                        /* obj */
                        
                        if (!method_exists($object, $method)) {
                            throw new Exception(sprintf("Variable %s (Class '%s') doesn't have a method named '%s'", 
                                $objname, get_class($object), $method));
                        }
                    } else if ($last >= 3 &&
                        $ts[$last - 1]['token'] == T_VARIABLE &&
                        $ts[$last - 2]['token'] == T_OBJECT_OPERATOR &&
                        $ts[$last - 3]['token'] == T_VARIABLE ) {

                        /* $object->$method( */

                        /* $object has to exist and has to be a object */
                        $objname = $ts[$last - 3]['value'];
                       
                        if (!isset($GLOBALS[ltrim($objname, '$')])) {
                            throw new Exception(sprintf('Variable \'%s\' is not set', $objname));
                        }
                        $object = $GLOBALS[ltrim($objname, '$')];

                        if (!is_object($object)) {
                            throw new Exception(sprintf('Variable \'%s\' is not a class', $objname));
                        }
                        
                        $methodname = $ts[$last - 1]['value'];

                        if (!isset($GLOBALS[ltrim($methodname, '$')])) {
                            throw new Exception(sprintf('Variable \'%s\' is not set', $methodname));
                        }
                        $method = $GLOBALS[ltrim($methodname, '$')];

                        /* obj */
                        
                        if (!method_exists($object, $method)) {
                            throw new Exception(sprintf("Variable %s (Class '%s') doesn't have a method named '%s'", 
                                $objname, get_class($object), $method));
                        }

                    } else if ($last >= 6 &&
                        $ts[$last - 1]['token'] == T_STRING &&
                        $ts[$last - 2]['token'] == T_OBJECT_OPERATOR &&
                        $ts[$last - 3]['token'] == ']' &&
                            /* might be anything as index */
                        $ts[$last - 5]['token'] == '[' &&
                        $ts[$last - 6]['token'] == T_VARIABLE ) {

                        /* $object[...]->method( */

                        /* $object has to exist and has to be a object */
                        $objname = $ts[$last - 6]['value'];
                       
                        if (!isset($GLOBALS[ltrim($objname, '$')])) {
                            throw new Exception(sprintf('Variable \'%s\' is not set', $objname));
                        }
                        $array = $GLOBALS[ltrim($objname, '$')];

                        if (!is_array($array)) {
                            throw new Exception(sprintf('Variable \'%s\' is not a array', $objname));
                        }

                        $andx = $ts[$last - 4]['value'];

                        if (!isset($array[$andx])) {
                            throw new Exception(sprintf('%s[\'%s\'] is not set', $objname, $andx));
                        }

                        $object = $array[$andx];

                        if (!is_object($object)) {
                            throw new Exception(sprintf('Variable \'%s\' is not a class', $objname));
                        }
                        
                        $method = $ts[$last - 1]['value'];

                        /* obj */
                        
                        if (!method_exists($object, $method)) {
                            throw new Exception(sprintf("Variable %s (Class '%s') doesn't have a method named '%s'", 
                                $objname, get_class($object), $method));
                        }

                    } else if ($last >= 3 &&
                        $ts[$last - 1]['token'] == T_STRING &&
                        $ts[$last - 2]['token'] == T_DOUBLE_COLON &&
                        $ts[$last - 3]['token'] == T_STRING ) {

                        /* Class::method() */

                        /* $object has to exist and has to be a object */
                        $classname = $ts[$last - 3]['value'];
                       
                        if (!class_exists($classname)) {
                            throw new Exception(sprintf('Class \'%s\' doesn\'t exist', $classname));
                        }
                        
                        $method = $ts[$last - 1]['value'];

                        if (!in_array($method, get_class_methods($classname))) {
                            throw new Exception(sprintf("Class '%s' doesn't have a method named '%s'", 
                                $classname, $method));
                        }
                    } else if ($last >= 3 &&
                        $ts[$last - 1]['token'] == T_VARIABLE &&
                        $ts[$last - 2]['token'] == T_DOUBLE_COLON &&
                        $ts[$last - 3]['token'] == T_STRING ) {

                        /* Class::method() */

                        /* $object has to exist and has to be a object */
                        $classname = $ts[$last - 3]['value'];
                       
                        if (!class_exists($classname)) {
                            throw new Exception(sprintf('Class \'%s\' doesn\'t exist', $classname));
                        }
                        
                        $methodname = $ts[$last - 1]['value'];

                        if (!isset($GLOBALS[ltrim($methodname, '$')])) {
                            throw new Exception(sprintf('Variable \'%s\' is not set', $methodname));
                        }
                        $method = $GLOBALS[ltrim($methodname, '$')];

                        if (!in_array($method, get_class_methods($classname))) {
                            throw new Exception(sprintf("Class '%s' doesn't have a method named '%s'", 
                                $classname, $method));
                        }

                    } else if ($last >= 2 &&
                        $ts[$last - 1]['token'] == T_STRING &&
                        $ts[$last - 2]['token'] == T_NEW ) {

                        /* new Class() */

                        $classname = $ts[$last - 1]['value'];

                        if (!class_exists($classname)) {
                            throw new Exception(sprintf('Class \'%s\' doesn\'t exist', $classname));
                        }

                        $r = new ReflectionClass($classname);

                        if ($r->isAbstract()) {
                            throw new Exception(sprintf("Can't instantiate abstract Class '%s'", $classname));
                        }

                        if (!$r->isInstantiable()) {
                            throw new Exception(sprintf('Class \'%s\' can\'t be instantiated. Is the class abstract ?', $classname));
                        }

                    } else if ($last >= 2 &&
                        $ts[0]['token'] != T_CLASS &&
                        $ts[$last - 1]['token'] == T_STRING &&
                        $ts[$last - 2]['token'] == T_FUNCTION ) {

                        /* make sure we are not a in class definition */

                        /* function a() */

                        $func = $ts[$last - 1]['value'];

                        if (function_exists($func)) {
                            throw new Exception(sprintf('Function \'%s\' is already defined', $func));
                        }
                    } else if ($last >= 4 &&
                        $ts[0]['token'] == T_CLASS &&
                        $ts[1]['token'] == T_STRING &&
                        $ts[$last - 1]['token'] == T_STRING &&
                        $ts[$last - 2]['token'] == T_FUNCTION ) {

                        /* make sure we are not a in class definition */

                        /* class a { .. function a() ... } */

                        $func = $ts[$last - 1]['value'];
                        $classname = $ts[1]['value'];

                        if (isset($methods[$func])) {
                            throw new Exception(sprintf("Can't redeclare method '%s' in Class '%s'", $func, $classname));
                        }

                        $methods[$func] = 1;

                    } else if ($last >= 1 &&
                        $ts[$last - 1]['token'] == T_STRING ) {
                        /* func() */
                        $funcname = $ts[$last - 1]['value'];
                        
                        if (!function_exists($funcname)) {
                            throw new Exception(sprintf("Function %s() doesn't exist", $funcname));
                        }
                    } else if ($last >= 1 &&
                        $ts[$last - 1]['token'] == T_VARIABLE ) {
    
                        /* $object has to exist and has to be a object */
                        $funcname = $ts[$last - 1]['value'];
                       
                        if (!isset($GLOBALS[ltrim($funcname, '$')])) {
                            throw new Exception(sprintf('Variable \'%s\' is not set', $funcname));
                        }
                        $func = $GLOBALS[ltrim($funcname, '$')];

                        if (!function_exists($func)) {
                            throw new Exception(sprintf("Function %s() doesn't exist", $func));
                        }

                    }
                    
                    array_push($braces, $token);
                    break;
                case '{':
                    $need_return = 0;

                    if ($last >= 2 &&
                        $ts[$last - 1]['token'] == T_STRING &&
                        $ts[$last - 2]['token'] == T_CLASS ) {

                        /* class name { */

                        $classname = $ts[$last - 1]['value'];

                        if (class_exists($classname, false)) {
                            throw new Exception(sprintf("Class '%s' can't be redeclared", $classname));
                        }
                    } else if ($last >= 4 &&
                        $ts[$last - 1]['token'] == T_STRING &&
                        $ts[$last - 2]['token'] == T_EXTENDS &&
                        $ts[$last - 3]['token'] == T_STRING &&
                        $ts[$last - 4]['token'] == T_CLASS ) {

                        /* class classname extends classname { */

                        $classname = $ts[$last - 3]['value'];
                        $extendsname = $ts[$last - 1]['value'];

                        if (class_exists($classname, false)) {
                            throw new Exception(sprintf("Class '%s' can't be redeclared", 
                                $classname));
                        }
                        if (!class_exists($extendsname, false)) {
                            throw new Exception(sprintf("Can't extend '%s' from not existing Class '%s'", 
                                $classname, $extendsname));
                        }
                    } else if ($last >= 4 &&
                        $ts[$last - 1]['token'] == T_STRING &&
                        $ts[$last - 2]['token'] == T_IMPLEMENTS &&
                        $ts[$last - 3]['token'] == T_STRING &&
                        $ts[$last - 4]['token'] == T_CLASS ) {

                        /* class name implements interface { */

                        $classname = $ts[$last - 3]['value'];
                        $implements = $ts[$last - 1]['value'];

                        if (class_exists($classname, false)) {
                            throw new Exception(sprintf("Class '%s' can't be redeclared", 
                                $classname));
                        }
                        if (!interface_exists($implements, false)) {
                            throw new Exception(sprintf("Can't implement not existing Interface '%s' for Class '%s'", 
                                $implements, $classname));
                        }
                    }

                    array_push($braces, $token);
                    break;
                case '}':
                    $need_return = 0;
                case ')':
                    array_pop($braces);
                    break;
                }
                  
                $eval .= $token;
            }    
        }

        $last = count($ts) - 1;
        if ($last >= 2 &&
            $ts[$last - 0]['token'] == T_STRING &&
            $ts[$last - 1]['token'] == T_DOUBLE_COLON &&
            $ts[$last - 2]['token'] == T_STRING ) {

            /* Class::constant */

            /* $object has to exist and has to be a object */
            $classname = $ts[$last - 2]['value'];
           
            if (!class_exists($classname)) {
                throw new Exception(sprintf('Class \'%s\' doesn\'t exist', $classname));
            }
            
            $constname = $ts[$last - 0]['value'];

            $c = new ReflectionClass($classname);
            if (!$c->hasConstant($constname)) {
                throw new Exception(sprintf("Class '%s' doesn't have a constant named '%s'", 
                    $classname, $constname));
            }
        } else if ($last == 0 &&
            $ts[$last - 0]['token'] == T_VARIABLE ) {

            /* $var */

            $varname = $ts[$last - 0]['value'];
           
            if (!isset($GLOBALS[ltrim($varname, '$')])) {
                throw new Exception(sprintf('Variable \'%s\' is not set', $varname));
            }
        }


        $need_more = count($braces);
        
        if ($need_more || ';' === $token) {
            $need_semicolon = 0;
        }  
  
        if ($need_return) {
            $eval = "return ".$eval;
        }
 
        /* add a traling ; if necessary */ 
        if ($need_semicolon) $eval .= ';';
        
        if (!$need_more) {
            $this->code = $eval;
        }
                
        return $need_more;
    }
    
    /**
    * show the prompt and fetch a single line
    * 
    * uses readline() if avaialbe
    * 
    * @return string a input-line
    */
    public function readline() {
        if (empty($this->code)) print PHP_EOL;

        $prompt = (empty($this->code)) ? '>> ' : '.. ';

        if ($this->have_readline) {
            $l = readline($prompt);

            readline_add_history($l);
        } else {
            print $prompt;

            if (is_null($this->stdin)) {
                if (false === ($this->stdin = fopen("php://stdin", "r"))) {
                    return false;
                }
            }
            $l = fgets($this->stdin);
        }
        return $l;
    }

    /**
    * get the inline help
    *
    * @return string the inline help as string 
    */
    public function getHelp() {
        $o = 'Inline Help:'.PHP_EOL;

        foreach ($this->commands as $cmd) {
            $o .= sprintf('  >> %s'.PHP_EOL.'    %s'.PHP_EOL,
                $cmd['command'],
                $cmd['description']
            );
        }

        return $o;
    }

    /**
    * handle the 'quit' command
    *
    * @return bool false to leave the input() call
    * @see input
    */
    protected function cmdQuit($l) {
        return false;
    }

    /**
    * handle the 'p ' command
    *
    * set the verbose flag
    *
    * @return string the pure command-string without the 'p ' command
    */
    protected function cmdPrint($l) {
        $this->verbose = 1;
        $cmd = substr($l, 2);

        return $cmd;
    }

    /**
    * handle the '?' commands
    *
    * With the help of the Reflection Class we extract the DocComments and display them
    * For internal Functions we extract the prototype from the php source.
    *
    * ? Class::method()
    * ? $obj->method()
    * ? Class::property
    * ? $obj::property
    * ? Class
    * ? $obj
    * ? function()
    *
    * The license of the PHP_Shell class
    * ? license
    *
    * @return string the help text
    */
    protected function cmdHelp($l) {
        if ("? " == substr($l, 0, strlen("? "))) {
            $str = substr($l, 2);

            $cmd = '';
            
            if (preg_match('#^([A-Za-z0-9_]+)::([a-zA-Z0-9_]+)\(\s*\)\s*#', $str, $a)) {
                /* ? Class::method() */

                $class = $a[1];
                $method = $a[2];

                if (false !== ($proto = PHP_ShellPrototypes::getInstance()->get($class.'::'.$method))) {

                    $cmd = sprintf("/**\n* %s\n\n* @params %s\n* @return %s\n*/\n",
                        $proto['description'],
                        $proto['params'],
                        $proto['return']
                    );
                } else if (class_exists($class, false)) {
                    $c = new ReflectionClass($class);

                    if ($c->hasMethod($method)) {
                        $cmd = $c->getMethod($method)->getDocComment();
                    }
                }
            } else if (preg_match('#^\$([A-Za-z0-9_]+)->([a-zA-Z0-9_]+)\(\s*\)\s*#', $str, $a)) {
                /* ? $obj->method() */
                if (isset($GLOBALS[$a[1]]) && is_object($GLOBALS[$a[1]])) {
                    $class = get_class($GLOBALS[$a[1]]);
                    $method = $a[2];
                    
                    $c = new ReflectionClass($class);
    
                    if ($c->hasMethod($method)) {
                        $cmd = $c->getMethod($method)->getDocComment();
                    }
                }
            } else if (preg_match('#^([A-Za-z0-9_]+)::([a-zA-Z0-9_]+)\s*$#', $str, $a)) { 
                /* ? Class::property */
                $class = $a[1];
                $property = $a[2];
                if (class_exists($class, false)) {
                    $c = new ReflectionClass($class);

                    if ($c->hasProperty($property)) {
                        $cmd = $c->getProperty($property)->getDocComment();
                    }
                }
            } else if (preg_match('#^\$([A-Za-z0-9_]+)->([a-zA-Z0-9_]+)\s*$#', $str, $a)) { 
                /* ? $obj->property */
                if (isset($GLOBALS[$a[1]]) && is_object($GLOBALS[$a[1]])) {
                    $class = get_class($GLOBALS[$a[1]]);
                    $method = $a[2];
                    
                    $c = new ReflectionClass($class);

                    if ($c->hasProperty($property)) {
                        $cmd = $c->getProperty($property)->getDocComment();
                    }

                }
            } else if (preg_match('#^([A-Za-z0-9_]+)$#', $str, $a)) {
                /* ? Class */
                if (class_exists($a[1], false)) {
                    $c = new ReflectionClass($a[1]);
                    $cmd = $c->getDocComment();
                } else if ($a[1] == 'license') {
                    $cmd = <<<EOF
(c) 2006 Jan Kneschke <jan@kneschke.de>

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
of the Software, and to permit persons to whom the Software is furnished to do
so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

EOF;
                }
            } else if (preg_match('#^\$([A-Za-z0-9_]+)$#', $str, $a)) {
                /* ? $object */
                $obj = $a[1];
                if (isset($GLOBALS[$obj]) && is_object($GLOBALS[$obj])) {
                    $class = get_class($GLOBALS[$obj]);

                    $c = new ReflectionClass($class);
                    $cmd = $c->getDocComment();
                }

            } else if (preg_match('#^([A-Za-z0-9_]+)\(\s*\)$#', $str, $a)) {
                /* ? function() */
                $func = $a[1];

                if (false !== ($proto = PHP_ShellPrototypes::getInstance()->get($func))) {
                    $cmd = sprintf("/**\n* %s\n*\n* @params %s\n* @return %s\n*/\n",
                        $proto['description'],
                        $proto['params'],
                        $proto['return']
                    );
                } else if (function_exists($func)) {
                    $c = new ReflectionFunction($func);
                    $cmd = $c->getDocComment();
                }
            }

            if ($cmd == '') {
                $cmd = var_export(sprintf('no help found for \'%s\'', $str), 1);
            } else {
                $cmd = var_export($cmd, 1);
            }
        } else if ("?" == $l) {
            $cmd = $this->getHelp();
            $cmd = var_export($cmd, 1);
        }

        return $cmd;
    }

    /**
    * set a shell-var
    *
    * :set al to enable autoload
    * :set bg=dark to enable highlighting with a dark backgroud
    */
    public function cmdSet($l) {
        if (!preg_match('#:set\s+([a-z]+)\s*(?:=\s*([a-z0-9]+)\s*)?$#i', $l, $a)) {
            print('unknown :set syntax');
            return;
        }

        $key = $a[1];

        switch ($key) {
        case 'bg':
        case 'background':
            if (!isset($a[2])) {
                print(':set '.$key.' failed: a value is required, example: :set '.$key.'=dark');
                return;
            }
            if (false == $this->applyColourScheme($a[2])) {
                print('setting colourscheme failed: colourscheme '.$a[2].' is unknown');
                return;
            }
            break;
        case 'al':
        case 'autoload':
            if (function_exists('__autoload')) {
                print('can\'t enabled autoload as a external __autoload() function is already defined');
                return;
            }

            if ($this->autoload) {
                print('autload is already enabled');
                return;
            }

            $this->autoload = true;
            break;
        default:
            print (':set '.$key.' failed: unknown key');
            return;
        }
    }
    
    /**
    * handle the input line
    *
    * read the input and handle the commands of the shell
    *
    * @return bool false on 'quit' or EOF, true otherwise
    */
    public function input() {
        $l = $this->readline();

        /* got EOF ? */
        if (false === $l) return false;

        $l = trim($l);
        
        if (empty($this->code)) {
            $this->verbose = 0;

            foreach ($this->commands as $cmd) {
                if (preg_match($cmd['regex'], $l)) {
                    $func = $cmd['method'];

                    if (false === ($l = $this->$func($l))) {
                        ## quit
                        return false;
                    }
                    break;
                }
            }
        }
       
        $this->appendCode($l); 

        return true;
    }

    public function isAutoloadEnabled() {
        return $this->autoload;
    }
    
    /**
    * get the code-buffer
    * 
    * @return string the code-buffer
    */
    public function getCode() {
        return $this->code;
    }
    
    /**
    * reset the code-buffer
    */
    public function resetCode() {
        $this->code = '';
    }
 
    /**
    * append code to the code-buffer
    *
    * @param string $code input buffer
    */
    public function appendCode($code) {
        $this->code .= $code;
    }
   
    /**
    * check if we have a verbose print-out
    *
    * @return bool 1 if verbose, 0 otherwise
    */
    public function getVerbose() {
        return $this->verbose;
    }

    /**
    * check if readline support is enabled
    *
    * @return bool true if enabled, false otherwise
    */
    public function hasReadline() {
        return $this->have_readline;
    }

    /**
    * get version of the class
    *
    * @return string version-string
    */
    public function getVersion() {
        return $this->version;
    }

    /**
    * get a colour for the shell
    *
    * @param string $type one of (value|exception|reset|default)
    * @return string a colour string or a empty string
    */
    public function getColour($type) {
        return isset($this->colour[$type]) ? $this->colour[$type] : '';
    }

    /**
    * apply a colour scheme to the current shell
    *
    * @param string $scheme name of the scheme
    * @return false if colourscheme is not known, otherwise true
    */
    public function applyColourScheme($scheme) {
        if (!isset($this->colour_scheme[$scheme])) return false;

        $this->colour = $this->colour_scheme[$scheme];

        return true;
    }

    /**
    * registers a colour scheme
    *
    * @param string $scheme name of the colour scheme
    * @param array a array of colours
    */
    public function registerColourScheme($scheme, $colours) {
        if (!is_array($colours)) return;

        /* set a reset colour if it is not supplied from the outside */
        if (!isset($colours["reset"])) $colours["reset"] = PHP_SHELL::C_RESET;

        $this->colour_scheme[$scheme] = $colours;
    }
}

/**
* a readline completion callback
*
* @param string $str linebuffer
* @param integer $pos position in linebuffer
* @return array list of possible matches
*/
function __shell_readline_complete($str, $pos) {
    $in = readline_info('line_buffer');

    /**
    * parse the line-buffer backwards to see if we have a 
    * - constant
    * - function 
    * - variable
    */

    $m = array();

    if (preg_match('#\$([A-Za-z0-9_]+)->#', $in, $a)) {
        /* check for $o->... */
        $name = $a[1];

        if (isset($GLOBALS[$name]) && is_object($GLOBALS[$name])) {
            $c = get_class_methods($GLOBALS[$name]);

            foreach ($c as $v) {
                $m[] = $v.'(';
            }
            $c = get_class_vars(get_class($GLOBALS[$name]));

            foreach ($c as $k => $v) {
                $m[] = $k;
            }

            return $m;
        }
    } else if (preg_match('#\$([A-Za-z0-9_]+)\[([^\]]+)\]->#', $in, $a)) {
        /* check for $o[...]->... */
        $name = $a[1];

        if (isset($GLOBALS[$name]) && 
            is_array($GLOBALS[$name]) &&
            isset($GLOBALS[$name][$a[2]])) {

            $c = get_class_methods($GLOBALS[$name][$a[2]]);

            foreach ($c as $v) {
                $m[] = $v.'(';
            }
            $c = get_class_vars(get_class($GLOBALS[$name][$a[2]]));

            foreach ($c as $k => $v) {
                $m[] = $k;
            }
            return $m;
        }

    } else if (preg_match('#([A-Za-z0-9_]+)::#', $in, $a)) {
        /* check for Class:: */
        $name = $a[1];

        if (class_exists($name, false)) {
            $c = get_class_methods($name);

            foreach ($c as $v) {
                $m[] = sprintf('%s::%s(', $name, $v);
            }

            $cl = new ReflectionClass($name);
            $c = $cl->getConstants();

            foreach ($c as $k => $v) {
                $m[] = sprintf('%s::%s', $name, $k);
            }

            return $m;
        }
    } else if (preg_match('#\$([a-zA-Z]?[a-zA-Z0-9_]*)$#', $in)) {
        $m = array_keys($GLOBALS);

        return $m;
    } else if (preg_match('#new #', $in)) {
        $c = get_declared_classes();
    
        foreach ($c as $v) {
            $m[] = $v.'(';
        }

        return $m;
    } else if (preg_match('#^:set #', $in)) {
        $m[] = 'autoload';
        $m[] = 'background=';

        return $m;
    }

    $f = get_defined_functions();

    foreach ($f['internal'] as $v) {
        $m[] = $v.'(';
    }

    foreach ($f['user'] as $v) {
        $m[] = $v.'(';
    }
    
    $c = get_declared_classes();

    foreach ($c as $v) {
        $m[] = $v.'::';
    }

    $c = get_defined_constants();

    foreach ($c as $k => $v) {
        $m[] = $k;
    }

    $m[] = 'foreach (';
    $m[] = 'require';

    # printf("%s ... %s\n", $str, $pos);
    return $m;
}


