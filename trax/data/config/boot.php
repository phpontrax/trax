<?php
/**
 *  @package PHPonTrax
 *
 *  Don't change this file. Configuration is done in
 *  config/environment.php and config/environments/*.php
 */

# Determine Trax environment 
if(!defined('TRAX_ENV')) {
    # Sets environment from the Apache Vhost (SetEnv TRAX_ENV development)
    # or change the string to manually set it. (development | test | production)
    define('TRAX_ENV', $_SERVER['TRAX_ENV'] ? $_SERVER['TRAX_ENV'] : "development");
} 

# Determine the path to this applications trax folder 
if(!defined('TRAX_ROOT')) {
    define("TRAX_ROOT", dirname(dirname(__FILE__)));    
}

# Determine where your system php libs path
if(!defined('PHP_LIB_ROOT')) {
    $php_dir = trim(exec('pear config-get php_dir'));
    if(is_dir($php_dir)) {
        define('PHP_LIB_ROOT',  $php_dir);    
    } else {
        define('PHP_LIB_ROOT',  '/usr/local/lib/php');    
    } 
    unset($php_dir);       
}

# Should we use local copy of the Trax libs in vendor/trax or
# the server Trax libs in the php libs dir defined in PHP_LIB_ROOT
if(is_dir(TRAX_ROOT."/vendor/trax")) {
    define("TRAX_LIB_ROOT", TRAX_ROOT."/vendor/trax");
} elseif(is_dir(PHP_LIB_ROOT."/PHPonTrax/vendor/trax")) {
    define("TRAX_LIB_ROOT", PHP_LIB_ROOT."/PHPonTrax/vendor/trax");           
} else {
    echo "Can't determine where your Trax Libs are located.";
    exit;    
}

# Set up Trax environment, framework, and default configuration
include_once(TRAX_LIB_ROOT."/trax.php");
Trax::initialize();

?>