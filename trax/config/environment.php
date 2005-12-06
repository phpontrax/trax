<?php

# include path for your php libs (PEAR etc)
define("PHP_LIB_ROOT",      "/usr/local/lib/php");
define("TRAX_ROOT",         dirname(dirname(__FILE__)) . "/");
define("TRAX_URL_PREFIX",   null);
# public is the default
define("DEFAULT_LAYOUT",    "public");

# Set in the Apache Vhost (SetEnv TRAX_MODE development)
if($_SERVER['TRAX_MODE']) {
    # Set from Env production / development / test
    define("TRAX_MODE",   $_SERVER['TRAX_MODE']);
} else {
    # Manually set production / development / test
    define("TRAX_MODE",   "development");
}

$GLOBALS['TRAX_INCLUDES'] =
    array( "models" => "app/models",
           "views" => "app/views",
           "controllers" => "app/controllers",
           "helpers" => "app/helpers",
           "layouts" => "app/views/layouts",
           "config" => "config",
           "environments" => "config/environments",
           "app" => "app",
           "lib" => "lib",
           "log" => "log",
           "vendor" => "vendor" );

if (substr(PHP_OS, 0, 3) == 'WIN') {
    # Windows
    define("TRAX_PATH_SEPERATOR", ";");
} else {
    # Unix
    define("TRAX_PATH_SEPERATOR", ":");
}

# Set which file to log php errors to for this application
# As well in your application you can do error_log("whatever") and it will go to this log file.
ini_set("log_errors", "On");
ini_set("error_log", TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['log']."/".TRAX_MODE.".log"); 

if(TRAX_MODE == "development") {
    define("DEBUG", true);
    # Display errors to browser if in development mode for debugging
    ini_set("display_errors", "On");
} else {
    define("DEBUG", false);
    # Hide errors from browser if not in development mode
    ini_set("display_errors", "Off");
}

# Load databse settings
$GLOBALS['TRAX_DB_SETTINGS'] = parse_ini_file(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['config']."/database.ini",true);

# Should we use local copy of the Trax libs in vendor/trax or
# the server Trax libs in the php libs dir defined in public/.htaccess
if(file_exists(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['vendor']."/trax")) {
    define("TRAX_LIB_ROOT", TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['vendor']."/trax");
} elseif(file_exists(PHP_LIB_ROOT."/trax")) {
    define("TRAX_LIB_ROOT", PHP_LIB_ROOT."/trax");
} else {
    echo "Can't determine where your Trax Libs are located.";
    exit;
}

# Set the include_path
ini_set("include_path",
        ".".TRAX_PATH_SEPERATOR.   # current directory
        TRAX_LIB_ROOT.TRAX_PATH_SEPERATOR.  # trax libs (vendor/trax or server trax libs)
        PHP_LIB_ROOT.TRAX_PATH_SEPERATOR.  # php libs dir (ex: /usr/local/lib/php)
	TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['config'].TRAX_PATH_SEPERATOR. # lib dir for app specific custom libs
        ini_get("include_path")); # add on old include_path to end

# Include Trax library files.
require_once("trax_exceptions.php");
require_once("inflector.php");
require_once("active_record.php");
require_once("action_controller.php");
require_once("action_mailer.php");
require_once("dispatcher.php");
require_once("router.php");

# Include the ApplicationMailer Class which extends ActionMailer for application specific mailing functions
if(file_exists(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['app']."/application_mailer.php")) {
    require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['app']."/application_mailer.php");
}

# Include the application environment specific config file
if(file_exists(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['environments']."/".TRAX_MODE.".php")) {
    require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['environments']."/".TRAX_MODE.".php");
}

##############################################
# Auto include model / controller / other app specific libs files
##############################################
function __autoload($class_name) {
    $file = Inflector::underscore($class_name).".php";
    $file_org = $class_name.".php";

    if(file_exists(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['models']."/$file")) {
        # Include model classes
        require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['models']."/$file");
    } elseif(file_exists(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['controllers']."/$file")) {
        # Include extra controller classes
        require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['controllers']."/$file");
    } elseif(file_exists(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['lib']."/$file")) {
        # Include users application libs
        require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['lib']."/$file");
    } elseif(file_exists(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['lib']."/$file_org")) {
        # Include users application libs
        require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['lib']."/$file_org");
    }
}

?>
