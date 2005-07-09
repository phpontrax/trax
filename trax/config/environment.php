<?                       
# start the session with a passed session id
if($_REQUEST['sess_id']) {
    session_id($_REQUEST['sess_id']);
}
# Start the session
session_start();

define("TRAX_MODE",        "development"); // production / development / test
define("TRAX_ROOT",        dirname(__FILE__) . "/../");
define("DEFAULT_LAYOUT",   "public");  // public is the default

$GLOBALS['TRAX_INCLUDES'] =
    array( "models" => "app/models",
           "views" => "app/views",
           "controllers" => "app/controllers",
           "helpers" => "app/helpers",
           "layouts" => "app/views/layouts",
           "config" => "config",
           "environments" => "config/environments",
	       "lib" => "lib",
	       "app" => "app" );

# Load databse settings
$GLOBALS['DB_SETTINGS'] = parse_ini_file(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['config']."/database.ini",true);

# Include Trax library files.
require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['lib']."/ActiveRecord.php");
require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['lib']."/ActionController.php");  
require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['lib']."/ActionMailer.php"); 
require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['lib']."/Dispatcher.php");
require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['lib']."/Inflector.php"); 
require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['lib']."/Router.php"); 

# Include the ApplicationMailer Class which extends ActionMailer for application specific mailing functions
if(file_exists(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['app']."/application_mailer.php"))
    require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['app']."/application_mailer.php");

# Include the application environment specific config file
if(file_exists(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['environments']."/".TRAX_MODE.".php"))
    require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['environments']."/".TRAX_MODE.".php"); 

######################################
# Auto Include Model Class Files
######################################
function __autoload($class_name) {
    $file = Inflector::underscore($class_name) . ".php";
    //error_log("__autoload(): file: $file");
    if(file_exists(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['models']."/$file")) {
        // Include Model Classes
        require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['models']."/$file");
    } 
}

?>
