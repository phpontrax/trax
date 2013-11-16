<?php
/**
 *  File containing the Trax class for framework configs
 *
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

/**
 *  @todo Document this class
 *  @package PHPonTrax
 */
class Trax {

    const
        MAJOR = 0,
        MINOR = 18,
        TINY = 0;

    public static
        $models_path = null,
        $views_path = null,
        $controllers_path = null,
        $helpers_path = null,
        $layouts_path = null,
        $config_path = null,
        $environments_path = null,
        $lib_path = null,
        $app_path = null,
        $log_path = null,
        $plugins_path = null,
        $vendor_path = null,
        $public_path = null,
	    $tmp_path = null,
        $assets_path = null,
        $url_prefix = null,
        $url_word_seperator = null, # used to put dashes in the url for seo
        $views_extension = 'phtml',
        $path_seperator = ":", # default is Unix
        $current_controller_path = null,
        $current_controller_name = null,
        $current_action_name = null,
        $current_controller_object = null,
	    $session_store = "file_store",
	    $session_class_name = "ActiveRecordStore",
        $session_save_path = "",
        $session_name = "TRAXSESSID",
        $session_cookie_domain = null,
        $session_lifetime = "0",
	    $session_maxlifetime_minutes = "20",
        $version = null,
        $show_trax_errors = false,
        $server_default_include_path = null,
        $include_paths = array(),
        $autoload_function = null,
        $plugins = array(),
        $assets_compress = false,
        $assets_compile = false;

    function initialize() {

        self::$version = self::version();

        if(substr(PHP_OS, 0, 3) == 'WIN') {
            # Windows
            self::$path_seperator = ";";
        }

        # Set include paths
        self::$models_path       = TRAX_ROOT."/app/models";
        self::$views_path        = TRAX_ROOT."/app/views";
        self::$controllers_path  = TRAX_ROOT."/app/controllers";
        self::$helpers_path      = TRAX_ROOT."/app/helpers";
        self::$layouts_path      = TRAX_ROOT."/app/views/layouts";
        self::$assets_path       = TRAX_ROOT."/app/assets";
        self::$config_path       = TRAX_ROOT."/config";
        self::$environments_path = TRAX_ROOT."/config/environments";
        self::$lib_path          = TRAX_ROOT."/lib";
        self::$app_path          = TRAX_ROOT."/app";
        self::$log_path          = TRAX_ROOT."/log";
        self::$vendor_path       = TRAX_ROOT."/vendor";
        self::$plugins_path      = TRAX_ROOT."/vendor/plugins";
        self::$public_path       = TRAX_ROOT."/public";
	    self::$tmp_path		     = TRAX_ROOT."/tmp";

        # Set which file to log php errors to for this application
        # As well in your application you can do error_log("whatever") and it will go to this log file.
        ini_set("log_errors", "On");
        ini_set("error_log", self::$log_path."/".TRAX_ENV.".log");

        if(TRAX_ENV == "development") {
            # Display errors to browser if in development mode for debugging
            ini_set("display_errors", "On");
        } else {
            # Hide errors from browser if not in development mode
            ini_set("display_errors", "Off");
        }

        # Get the include_path so we know what the original path was
        self::$server_default_include_path = ini_get("include_path");
        # Set the include_paths
        self::set_default_include_paths();

        # Include Trax library files.
        include_once(TRAX_LIB_ROOT."/session.php");
        include_once(TRAX_LIB_ROOT."/input_filter.php");
        include_once(TRAX_LIB_ROOT."/trax_exceptions.php");
        include_once(TRAX_LIB_ROOT."/inflector.php");
        include_once(TRAX_LIB_ROOT."/active_record.php");
        include_once(TRAX_LIB_ROOT."/action_controller.php");
        include_once(TRAX_LIB_ROOT."/action_view.php");
        include_once(TRAX_LIB_ROOT."/action_mailer.php");
        include_once(TRAX_LIB_ROOT."/dispatcher.php");
        include_once(TRAX_LIB_ROOT."/router.php");

        self::load_active_record_connections_config();

        ViewHandlers::register_extension('html');
        # legancy phtml extension
        ViewHandlers::register_extension('phtml');
    }

    function add_include_path($path, $prepend = false, $use_trax_root = false) {
        if(is_array($path)) {
            foreach($path as $new_path) {
                if(!in_array($new_path, self::$include_paths)) {
                    $new_paths[] = $use_trax_root ? TRAX_ROOT."/".$new_path : $new_path;
                }
            }
        } elseif(!in_array($path, self::$include_paths)) {
            $new_paths[] = $use_trax_root ? TRAX_ROOT."/".$path : $path;
        }
        if(is_array($new_paths) && is_array(self::$include_paths)) {
            foreach($new_paths as $path) {
                if($prepend) {
                    array_unshift(self::$include_paths, $path);
                } else {
                    array_push(self::$include_paths, $path);
                }
            }
            ini_set("include_path", implode(self::$path_seperator, self::$include_paths));
        }
    }

    function set_default_include_paths() {
        # first clear out all the current paths
        self::$include_paths = array();
        # now add the default paths
        self::add_include_path(array(
            ".",                                # current directory
            TRAX_LIB_ROOT,                      # trax libs (vendor/trax or server trax libs)
            PHP_LIB_ROOT,                       # php libs dir (ex: /usr/local/lib/php)
            self::$lib_path,                    # app specific libs extra libs to include
	        self::$plugins_path,                # plugins / engines
            self::$vendor_path,                 # 3rd party libs to include in vendor (vendor/third_party)
            self::$server_default_include_path  # tack on the old include_path to the end
        ));
    }

    function load_active_record_connections_config() {
        # Make sure database settings are cleared out
        ActiveRecord::$database_settings = array();
        ActiveRecord::clear_all_connections();
        if(file_exists(self::$config_path."/database.ini")) {
            # Load databse settings
            ActiveRecord::$database_settings = parse_ini_file(self::$config_path."/database.ini", true);
            #error_log("db settings:".print_r(ActiveRecord::$database_settings, true));
        }
	    ActiveRecord::$environment = TRAX_ENV;
        ActiveRecord::$logger = new Trax;
    }

    function include_env_config() {
        # Include the application environment specific config file
        if(file_exists(self::$environments_path."/".TRAX_ENV.".php")) {
            include_once(self::$environments_path."/".TRAX_ENV.".php");
        }
    }

    function load_plugins($plugins = array()) {
        $plugins = count($plugins) ? $plugins : Trax::$plugins;
        foreach((array)$plugins as $plugin) {
            if(file_exists(self::$plugins_path."/{$plugin}/init.php")) {
                include_once(self::$plugins_path."/{$plugin}/init.php");
                $loaded_plugins[] = $plugin;
            }
        }
        Trax::$plugins = (array)$loaded_plugins;
    }

    function version() {
        return implode('.', array(self::MAJOR, self::MINOR, self::TINY));
    }

    function log($message, $filename = '') {
        $date = date("[d-M-Y H:i:s]");
        $message = "$date $message\n";
        $log_file = self::$log_path.'/'.($filename != '' ? $filename : TRAX_ENV).'.log';
        if(file_exists($log_file)) {
            error_log($message, 3, $log_file);
        } else {
            exec("touch {$log_file}");
            error_log($message, 3, $log_file);
        }
    }

}

###################################################################
# Auto include model / controller / other app specific libs files
###################################################################
function __autoload($class_name) {
    $file = Inflector::underscore($class_name).".php";
    $file_org = $class_name.".php";

    if(file_exists(Trax::$models_path."/$file")) {
        # Include model classes
        include_once(Trax::$models_path."/$file");
    } elseif(file_exists(Trax::$controllers_path."/$file")) {
        # Include extra controller classes
        include_once(Trax::$controllers_path."/$file");
    } elseif(file_exists(Trax::$lib_path."/$file")) {
        # Include users application libs
        include_once(Trax::$lib_path."/$file");
    } elseif(file_exists(Trax::$lib_path."/$file_org")) {
        # Include users application libs
        include_once(Trax::$lib_path."/$file_org");
    }

    if(count(Trax::$plugins)) {
        foreach(Trax::$plugins as $plugin) {
            $model_path = Trax::$plugins_path."/$plugin/app/models";
            $controller_path = Trax::$plugins_path."/$plugin/app/controllers";
            $lib_path = Trax::$plugins_path."/$plugin/lib";
            if(file_exists("$model_path/$file")) {
                # Include model classes
                include_once("$model_path/$file");
            } elseif(file_exists("$controller_path/$file")) {
                # Include extra controller classes
                include_once("$controller_path/$file");
            } elseif(file_exists("$lib_path/$file")) {
                # Include users application libs
                include_once("$lib_path/$file");
            } elseif(file_exists("$lib_path/$file_org")) {
                # Include users application libs
                include_once("$lib_path/$file_org");
            }
        }
    }

	# add to the __autoload function from Trax
   	# just define _autoload()
	if(function_exists(Trax::$autoload_function) && Trax::$autoload_function != '__autoload') {
	    call_user_func(Trax::$autoload_function, $class_name);
	}
}

?>