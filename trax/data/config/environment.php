<?php
/**
 *  @package PHPonTrax
 */
# Trax should be able to figure the following 2 settings out
# automatically, but if you have trouble you can set them manually
# define("PHP_LIB_ROOT",    "/usr/local/lib/php");
# define("TRAX_ROOT",       dirname(dirname(__FILE__)));

# Uncomment below to force Trax into production mode when 
# you don't control web/app server and can't set it the proper way
# Sets environment from the Apache Vhost (SetEnv TRAX_ENV production)
# or change the string to manually set it. (development | test | production)
# define('TRAX_ENV', $_SERVER['TRAX_ENV'] ? $_SERVER['TRAX_ENV'] : "production");

# Bootstrap the Trax environment, framework, and default configuration
include_once(dirname(__FILE__)."/boot.php");

# Override the Trax framework default values
# Settings in config/environments/* take precedence those specified here
# Trax::$path_seperator = ":";
# Trax::$url_prefix = "~username";

# Include the application environment specific config file
Trax::include_env_config();
        
# Add new inflection rules using the following format 
# (all these examples are active by default):
# Inflections::plural('/^(ox)$/i', '\1en');
# Inflections::singular('/^(ox)en/i', '\1');
# Inflections::irregular('person', 'people');
# Inflections::uncountable('fish', 'sheep', ['more words'] ...);

# Include your application configuration below

?>