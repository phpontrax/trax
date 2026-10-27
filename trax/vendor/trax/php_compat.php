<?php
/**
 *  Polyfills for functions removed in PHP 8 that are still called by
 *  PEAR (Mail, Console_Getopt) and other bundled third-party libraries.
 *
 *  create_function() is deliberately NOT polyfilled: libraries such as
 *  phpseclib check function_exists('create_function') and fall back to
 *  safe code paths when it is missing.
 *
 *  @package PHPonTrax
 */

# PHP 8.1 made mysqli throw exceptions by default; MDB2 expects the old
# behaviour of returning false and reporting errors through PEAR_Error
if(function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

if(!function_exists('each')) {
    # removed in PHP 8.0; same semantics, including advancing the
    # internal pointer, so nested each() loops keep working
    function each(&$array) {
        $key = key($array);
        if($key === null) {
            return false;
        }
        $value = current($array);
        next($array);
        return array(1 => $value, 'value' => $value, 0 => $key, 'key' => $key);
    }
}

if(!function_exists('get_magic_quotes_gpc')) {
    # removed in PHP 8.0; magic quotes have been off since PHP 5.4
    function get_magic_quotes_gpc() {
        return false;
    }
}

if(!function_exists('get_magic_quotes_runtime')) {
    # removed in PHP 8.0
    function get_magic_quotes_runtime() {
        return false;
    }
}

?>
