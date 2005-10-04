<?                       
# start the session with a passed session id
if($_REQUEST['sess_id']) {
    session_id($_REQUEST['sess_id']);
}
# Start the session
session_start();

if($_SERVER['TRAX_MODE']) { // Set in the Apache Vhost (SetEnv TRAX_MODE development)
    define("TRAX_MODE",   $_SERVER['TRAX_MODE']); // production / development / test
} else {
    define("TRAX_MODE",   "development"); // production / development / test        
}
define("TRAX_ROOT",        dirname(__FILE__) . "/../");
define("TRAX_URL_PREFIX",  null); 
define("DEFAULT_LAYOUT",   "public");  // public is the default

$GLOBALS['TRAX_INCLUDES'] =
    array( "models" => "app/models",
           "views" => "app/views",
           "controllers" => "app/controllers",
           "helpers" => "app/helpers",
           "libs" => "app/libs", 
           "layouts" => "app/views/layouts",
           "config" => "config",
           "environments" => "config/environments",
	       "lib" => "lib",
	       "app" => "app",
	       "log" => "log" );

# Load databse settings
$GLOBALS['DB_SETTINGS'] = parse_ini_file(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['config']."/database.ini",true);

# Include Trax library files.
require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['lib']."/active_record.php");
require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['lib']."/action_controller.php");  
require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['lib']."/action_mailer.php"); 
require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['lib']."/dispatcher.php");
require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['lib']."/inflector.php"); 
require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['lib']."/router.php"); 
require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['lib']."/html_helper.php");

# Include the ApplicationMailer Class which extends ActionMailer for application specific mailing functions
if(file_exists(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['app']."/application_mailer.php"))
    require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['app']."/application_mailer.php");

# Include the application environment specific config file
if(file_exists(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['environments']."/".TRAX_MODE.".php"))
    require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['environments']."/".TRAX_MODE.".php"); 

# Add to the include path non framework libs
if(file_exists(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['libs']))
	ini_set("include_path",ini_get("include_path").":".TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['libs']);

# Set which file to log to php errors for this application to
ini_set("error_log", TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['log']."/".TRAX_MODE.".log");


##############################################
# Auto Include Model / Controller Class Files
##############################################
function __autoload($class_name) {
    $file = Inflector::underscore($class_name) . ".php";
    //error_log("__autoload(): file: $file");
    if(file_exists(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['models']."/$file")) {
        // Include Model Classes
        require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['models']."/$file");
    } elseif(file_exists(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['controllers']."/$file")) {
        // Include Extra Controller Classes
        require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['controllers']."/$file");
	} elseif(file_exists(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['libs']."/$file")) {
        // Include Application libs
        require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['libs']."/$file");
	}
}

?>