<?
# $Id$
#
# Copyright (c) 2005 John Peterson
#
# Permission is hereby granted, free of charge, to any person obtaining
# a copy of this software and associated documentation files (the
# "Software"), to deal in the Software without restriction, including
# without limitation the rights to use, copy, modify, merge, publish,
# distribute, sublicense, and/or sell copies of the Software, and to
# permit persons to whom the Software is furnished to do so, subject to
# the following conditions:
#
# The above copyright notice and this permission notice shall be
# included in all copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
# EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
# MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
# NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
# LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
# OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
# WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

class ActionController {

    private
        $controller,
        $action,
        $id,
        $controllers_path,
        $helpers_path,
        $helpers_base_path,
        $layouts_path,
        $url_path,
        $layout_file,
        $default_layout_file,
        $helper_file,
        $application_controller_file,
        $application_helper_file,
        $loaded = false,
        $router_loaded = false,
        $helpers = array(),
        $before_filters = array(),
        $after_filters = array();
    protected
        $before_filter = null,
        $after_filter = null;
    public
        $controller_file,
        $view_file,
        $views_path,
        $controller_class, $controller_object,
        $views_file_extention = "phtml";

    function __construct() {
        if(!is_object($this->router)) {
            $this->load_router();
        }
    }

    function __set($key, $value) {
        #echo "setting: $key = $value<br>";
        if($key == "before_filter") {
            $this->add_before_filter($value);
        } elseif($key == "after_filter") {
            $this->add_after_filter($value);
        } elseif($key == "helper") {
            $this->add_helper($value);
        } else {
            $this->$key = $value;
        }
    }

    function load_router() {
        $this->router_loaded = false;
        $router = new Router();
        # Load the routes
        require_once(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['config']."/routes.php");
        $this->router = $router;
        if(is_object($this->router)) {
            $this->router_loaded = true;
        }
    }

    function recognize_route() {

        if(!$this->router_loaded) {
            $this->load_router();
        }

        # current url
        $browser_url = $_SERVER['REDIRECT_URL'];

        # strip off url prefix, if any
        if (!is_null(TRAX_URL_PREFIX)) {
            $browser_url = str_replace(TRAX_URL_PREFIX,"",$browser_url);
        }

        # strip leading slash
        $browser_url = substr($browser_url,1);

        # strip trailing slash (if any)
        if(substr($browser_url, -1) == "/") {
            $browser_url = substr($browser_url, 0, -1);
        }

        if($browser_url) {
            $this->url_path = explode("/", $browser_url);
        } else {
            $this->url_path = array();
        }

        if($this->router->routes_count > 0) {
            $this->controllers_path = TRAX_ROOT . $GLOBALS['TRAX_INCLUDES']['controllers'];
            $this->helpers_path = $this->helpers_base_path = TRAX_ROOT . $GLOBALS['TRAX_INCLUDES']['helpers'];
            $this->application_controller_file = $this->controllers_path . "/application.php";
            $this->application_helper_file = $this->helpers_path . "/application_helper.php";
            $this->layouts_path = $this->layouts_base_path = TRAX_ROOT . $GLOBALS['TRAX_INCLUDES']['layouts'];
            $this->default_layout_file = $this->layouts_base_path . "/application." . $this->views_file_extention;
            $this->views_path = TRAX_ROOT . $GLOBALS['TRAX_INCLUDES']['views'];

            $route = $this->router->find_route($browser_url);
            if(is_array($route)) {
                $this->set_paths();
                $route_path = explode("/",$route['path']);
                $route_params = $route['params'];

                if(@array_key_exists(":controller",$route_params)) {
                    $this->controller = $route_params[":controller"];
                } elseif(@in_array(":controller",$route_path)) {
                    $this->controller = strtolower($this->url_path[@array_search(":controller", $route_path)]);
                }

                if(@array_key_exists(":action",$route_params)) {
                    $this->action = $route_params[':action'];
                } elseif(@in_array(":action",$route_path)) {
                    $this->action = strtolower($this->url_path[@array_search(":action", $route_path)]);
                }

                if(@in_array(":id",$route_path)) {
                    $this->id = strtolower($this->url_path[@array_search(":id", $route_path)]);
                    if($this->id != "") {
                        $_REQUEST['id'] = $this->id;
                    }
                }

                $this->views_path .= "/" . $this->controller;
                $this->controller_file = $this->controllers_path . "/" .  $this->controller . "_controller.php";
                $this->controller_class = Inflector::camelize($this->controller) . "Controller";
                $this->helper_file = $this->helpers_path . "/" .  $this->controller . "_helper.php";
            }
        }

        if(file_exists($this->controller_file)) {
            $this->loaded = true;
            return true;
        } else {
            $this->loaded = false;
            return false;
        }
    }

    function process_route() {

        # First try to load the routes and setup the pathes to everything
        if(!$this->loaded) {
            if(!$this->recognize_route()) {
                $this->raise("Failed to load any defined routes", "Controller ".$this->controller." not found", "404");
            }
        }

        # Surpress output
        ob_start();

        # Include main application controller file
        if(file_exists($this->application_controller_file)) {
            include_once($this->application_controller_file);
        }

        # Include the controller file and execute action
        if(file_exists($this->controller_file)) {
            include_once($this->controller_file);
            if(class_exists($this->controller_class,false)) {
                $class = $this->controller_class;
                $this->controller_object = new $class();
                if(is_object($this->controller_object)) {
                    $GLOBALS['current_controller_path'] = "$this->added_path/$this->controller";
                    $GLOBALS['current_controller_name'] = $this->controller;
                    $GLOBALS['current_controller_object'] = $this->controller_object;
                }

                # Which layout should we use?
                $this->layout_file = $this->determine_layout();

                # Check if there is any defined scaffolding to load
                if($this->controller_object->scaffold) {
                    $scaffold = $this->controller_object->scaffold;
                    if(file_exists(TRAX_LIB_ROOT."/scaffold_controller.php")) {
                        include_once(TRAX_LIB_ROOT."/scaffold_controller.php");
                        $GLOBALS['current_controller_object'] = $this->controller_object = new ScaffoldController($scaffold, $this->controller, $this->action);
                        if($this->action) {
                            $this->view_file = TRAX_LIB_ROOT . "/templates/scaffolds/".$this->action.".phtml";
                        } else {
                            $this->view_file = TRAX_LIB_ROOT . "/templates/scaffolds/index.phtml";
                        }
                        if(!file_exists($this->layout_file)) {
                            # the generic scaffold layout
                            $this->layout_file = TRAX_LIB_ROOT . "/templates/scaffolds/layout.phtml";
                        }
                    }
                }
            }

            # Include main application helper file
            if(file_exists($this->application_helper_file)) {
                include_once($this->application_helper_file);
            }

            # Include helper file for this controller
            if(file_exists($this->helper_file)) {
                include_once($this->helper_file);
            }

            # Include any extra helper files defined in this controller
            if(count($this->helpers) > 0) {
                foreach($this->helpers as $helper) {
                    if(strstr($helper, "/")) {
                        $file = substr(strrchr($helper, "/"), 1);
                        $path = substr($helper, 0, strripos($helper, "/"));
                        $helper_path_with_file = $this->helpers_base_path."/".$path."/".$file."_helper.php";
                    } else {
                        $helper_path_with_file = $this->helpers_base_path."/".$helper."_helper.php";
                    }
                    if(file_exists($helper_path_with_file)) {
                        # Include the helper file
                        include($helper_path_with_file);
                    }
                }
            }

            if(is_object($this->controller_object)) {
                # Call the controller method based on the URL
                $this->execute_before_filters();
                if(method_exists($this->controller_object, $this->action)) {
                    $action = $this->action;
                    $this->controller_object->$action();
                } elseif(file_exists($this->views_path . "/" . $this->action . "." . $this->views_file_extention)) {
                    $action = $this->action;
                } elseif(method_exists($this->controller_object, "index")) {
                    $this->controller_object->index();
                } else {
                    $this->raise("No action responded to ".$this->action, "Unknown action", "404");
                }
                $this->execute_after_filters();

                # Find out if there was a redirect to some other page
                if($this->controller_object->redirect_to) {
                    echo "<html><head><META HTTP-EQUIV=\"REFRESH\" CONTENT=\"0; URL=".$this->controller_object->redirect_to."\"></head></html>";
                    #header("Location: ".$this->controller_object->redirect_to);
                    exit;
                } else {
                    # Pull all the class vars out and turn them from $this->var to $var
                    extract(get_object_vars($this->controller_object));
                }

                if($this->controller_object->render_text != "") {
                    echo $this->controller_object->render_text;
                } else {
                    # If this isn't a scaffolding then get the view file to include
                    if(!$scaffold) { 
                        # Normal processing of the view
                        if($this->controller_object->render_action) {
                            $this->view_file = $this->views_path . "/" . $this->controller_object->render_action . "." . $this->views_file_extention;
                        } elseif($action) {
                            $this->view_file = $this->views_path . "/" . $action . "." . $this->views_file_extention;
                        } else {
                            $this->view_file = $this->views_path . "/" . "index" . "." . $this->views_file_extention;
                        }
                    }

                    if(file_exists($this->view_file)) {
                        # grab view html
                        include($this->view_file);
                    } else {
                        $this->raise("No view file found $action ($this->view_file).", "Unknown view", "404");
                    }

                    # Grab all the html from the view to put into the layout
                    $content_for_layout .= ob_get_contents();
                    ob_end_clean();

                    if(file_exists($this->layout_file) && $render_layout !== false) {
                        # render the layout
                        include($this->layout_file);
                    } else {
                        # Can't find any layout so throw an exception
                        # $this->raise("No layout file found.", "Unknown layout", "404"); 
                        # No layout template so just echo out whatever is in $content_for_layout
                        echo $content_for_layout;
                    }
                }
            } else {
                $this->raise("Failed to instantiate controller object \"".$this->controller."\".", "ActionController Error", "500");
            }
        } else {
            $this->raise("No controller found.", "Unknown controller", "404");
        }

        if(!$this->keep_flash) {
            # Nuke the flash
            Session::unset_var('flash');
        }

        return true;
    }

    function set_paths() {
        if(is_array($this->url_path)) {
            foreach($this->url_path as $path) {
                if(file_exists($this->controllers_path . "/$path")) {
                    $extra_path[] = $path;
                } else {
                    $new_path[] = $path;
                }
            }
            if(is_array($extra_path)) {
                $extra_path = implode("/", $extra_path);
                $this->added_path = $extra_path;
                $this->controllers_path .= "/$extra_path";
                $this->helpers_path .= "/$extra_path";
                $this->views_path .= "/$extra_path";
                $this->layouts_path .= "/$extra_path";
            }
            if(is_array($new_path)) {
                $this->url_path = $new_path;
            }
        }
    }

    function execute_before_filters() {
        if(count($this->before_filters) > 0) {
            foreach($this->before_filters as $filter_function) {
                if(method_exists($this->controller_object, $filter_function)) {
                    $this->controller_object->$filter_function();
                }
            }
        }
    }

    function add_before_filter($filter_function_name) {
        if(is_string($filter_function_name) && !empty($filter_function_name)) {
            $this->before_filters[] = $filter_function_name;
        } elseif(is_array($filter_function_name)) {
            if(count($this->before_filters) > 0) {
                $this->before_filters = array_merge($this->before_filters, $filter_function_name);
            } else {
                $this->before_filters = $filter_function_name;
            }
        }
    }

    function execute_after_filters() {
        if(count($this->after_filters) > 0) {
            foreach($this->after_filters as $filter_function) {
                if(method_exists($this->controller_object, $filter_function)) {
                    $this->controller_object->$filter_function();
                }
            }
        }
    }

    function add_after_filter($filter_function_name) {
        if(is_string($filter_function_name) && !empty($filter_function_name)) {
            $this->after_filters[] = $filter_function_name;
        } elseif(is_array($filter_function_name)) {
            if(count($this->after_filters) > 0) {
                $this->after_filters = array_merge($this->after_filters, $filter_function_name);
            } else {
                $this->after_filters = $filter_function_name;
            }
        }
    }

    function add_helper($helper_name) {
        $this->helpers[] = $helper_name;
    }

    function render_partial($path) {
        if(strstr($path, "/")) {
            $file = substr(strrchr($path, "/"), 1);
            $path = substr($path, 0, strripos($path, "/"));
            $path_with_file = TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['views']."/".$path."/_".$file.".".$this->views_file_extention;
        } else {
            $path_with_file = $this->views_path."/_".$path.".".$this->views_file_extention;
        }

        if(file_exists($path_with_file)) {
            # Pull all the class vars out and turn them from $this->var to $var
            extract(get_object_vars($this->controller_object));
            include($path_with_file);
        }
    }

    function determine_layout() {
        # I guess you don't want any layout
        if($this->controller_object->layout == "null") {
            return null;
        }
        # $layout will be the layout defined in the current controller
        # or try to use the controller name for the layout
        $layout = $this->controller_object->layout ? $this->controller_object->layout : $this->controller;
        # Check if a method has been defined to determine the layout at runtime
        if(method_exists($this->controller_object, $layout)) {
            $layout = $this->controller_object->$layout();
        }
        if($layout) {
            # Is this layout for from a different controller
            if(strstr($layout, "/")) {
                $file = substr(strrchr($layout, "/"), 1);
                $path = substr($layout, 0, strripos($layout, "/"));
                $layout = $this->layouts_base_path."/".$path."/".$file.".".$this->views_file_extention;
            } else {
                # Is there a layout for the current controller
                $layout = $this->layouts_path."/".$layout.".".$this->views_file_extention;
            }
            if(file_exists($layout)) {
                $layout_file = $layout;
            }
        }
        # No defined layout found so just use the default layout app/views/layouts/application.phtml
        if(!$layout_file) {
            $layout_file = $this->default_layout_file;
        }
        return $layout_file;
    }

    function raise($error_message, $error_heading, $error_code = "404") {
        throw new ActionControllerError("Error Message: ".$error_message, $error_heading, $error_code);
    }

    function process_with_exception(&$exception) {
        $error_code = $exception->error_code;
        $error_heading = $exception->error_heading;
        $error_message = $exception->error_message;
        $trace = $exception->getTraceAsString();
        header("HTTP/1.0 {$error_code} {$error_heading}");
        # check for user's layout for errors
        if(DEBUG && file_exists(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['layouts']."/error.phtml")) {
            include(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['layouts']."/error.phtml");
        } elseif(DEBUG && file_exists(TRAX_LIB_ROOT."/templates/error.phtml")) {
            # use default layout for errors
            include(TRAX_LIB_ROOT."/templates/error.phtml");
        } elseif(DEBUG) {
            echo "<font face=\"verdana, arial, helvetica, sans-serif\">\n";
            echo "<h1>$error_heading</h1>\n";
            echo "<p>$error_message</p>\n";
            if($trace) {
                echo "<pre style=\"background-color: #eee;padding:10px;font-size: 11px;\">";
                echo "<code>$trace</code></pre>\n";
            }
            echo "</font>\n";
        } else {
            echo "<font face=\"verdana, arial, helvetica, sans-serif\">\n";
            echo "<h2>Application Error</h2>Trax application failed to start properly";
            echo "</font>\n";
        }
    }

}

?>