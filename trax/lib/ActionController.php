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

    private $controller, $action, $id;
    private $controllers_path, $helpers_path, $views_path, $layouts_path, $url_path;
    private $layout_file, $default_layout_file, $controller_file, $helper_file;
    private $controller_class, $controller_object;
    private $application_controller_file, $application_helper_file;
    private $loaded = false;
    private $router_loaded = false;
    protected $before_filter = array();
    protected $after_filter = array();
    public $views_file_extention = "phtml";

    function __construct() {
        if(!is_object($this->router)) {
            $this->load_router();
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

        //strip leading slash
        $browser_url = substr($_SERVER['REDIRECT_URL'],1);
        //trailing leading slash (if any)
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
            $this->layouts_path = TRAX_ROOT . $GLOBALS['TRAX_INCLUDES']['layouts'];
            $this->default_layout_file = $this->layouts_path . "/" . DEFAULT_LAYOUT . "." . $this->views_file_extention;
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

        // First try to load the routes and setup the pathes to everything
        if(!$this->loaded) {
            if(!$this->recognize_route()) {
                $this->error("404", "Controller ".$this->controller." not found", "Failed to load any defined routes");
            }
        }

        // Surpress output
        ob_start();

        // Include main application controller file
        if(file_exists($this->application_controller_file)) {
            include_once($this->application_controller_file);
        }

        // Include main application helper file
        if(file_exists($this->application_helper_file)) {
            include_once($this->application_helper_file);
        }

        // Include the controller file and execute action
        if(file_exists($this->controller_file)) {
            include_once($this->controller_file);
            if(class_exists($this->controller_class,false)) {
                $class = $this->controller_class;
                $this->controller_object = new $class();
            }

            if($this->id != "") {
                $_REQUEST['id'] = $this->id;
            }

            // Include helper file for this controller
            if(file_exists($this->helper_file)) {
                include_once($this->helper_file);
            }

            // Include any extra helper files
            $this->include_extra_helpers();

            if(is_object($this->controller_object)) {
                // Call the controller method based on the URL
                $this->execute_before_filters();
                if(method_exists($this->controller_object, $this->action)) {
                    $action = $this->action;
                    $this->controller_object->$action();
                } elseif(file_exists($this->views_path . "/" . $this->action . "." . $this->views_file_extention)) {
                    $action = $this->action;
                } elseif(method_exists($this->controller_object, "index")) {
                    $this->controller_object->index();
                } else {
                    $this->error("404", "Unknown action", "No action responded to ".$this->action);
                }
                $this->execute_after_filters();

                // Find out if there was a redirect to some other page
                if($this->controller_object->redirect_to) {
                    echo "<html><head><META HTTP-EQUIV=\"REFRESH\" CONTENT=\"0; URL=".$this->controller_object->redirect_to."\"></head></html>";
                    //header("Location: ".$this->controller_object->redirect_to);
                    exit;
                } else {
                    // Pull all the class vars out and turn them from $this->var to $var
                    extract(get_object_vars($this->controller_object));
                }
            }

            if($this->controller_object->render_text != "") {
                echo $this->controller_object->render_text;
            } else {
                if($this->controller_object->render_action) {
                    $this->view_file = $this->views_path . "/" . $this->controller_object->render_action . "." . $this->views_file_extention;
                } elseif($action) {
                    $this->view_file = $this->views_path . "/" . $action . "." . $this->views_file_extention;
                } else {
                    $this->view_file = $this->views_path . "/" . "index" . "." . $this->views_file_extention;
                }

                if(file_exists($this->view_file)) {
                    // grab view html
                    include($this->view_file);
                } else {
                    $this->error("404", "Unknown view", "No view file found.");
                }

                $content_for_layout .= ob_get_contents();
                ob_end_clean();

                $this->layout_file = $this->layouts_path . "/" . $this->controller_object->layout . "." . $this->views_file_extention;
                if(file_exists($this->layout_file)) {
                    // render view
                    include($this->layout_file);
                } elseif(file_exists($this->default_layout_file)) {
                    // render view
                    include($this->default_layout_file);
                } else {
                    $this->error("404", "Unknown layout", "No layout file found.");
                }
            }
        } else {
            $this->error("404", "Unknown controller", "No controller found.");
        }

        if(!$this->keep_flash) {
            // Nuke the flash array
            unset($_SESSION['flash']);
        }

        return true;
    }

    function set_paths() {
        if(is_array($this->url_path)) {
            foreach($this->url_path as $path) {
                if(file_exists($this->controllers_path . "/$path")) {
                    $this->controllers_path .= "/$path";
                    $this->helpers_path .= "/$path";
                    $this->views_path .= "/$path";
                } else {
                    $new_path[] = $path;
                }
            }

            if(is_array($new_path)) {
                $this->url_path = $new_path;
            }
        }
    }

    function execute_before_filters() {
        if(is_array($this->controller_object->before_filter)) {
            foreach($this->controller_object->before_filter as $filter_function) {
                if(method_exists($this->controller_object, $filter_function)) {
                    $this->controller_object->$filter_function();
                }
            }
        }
    }

    function add_before_filter($filter_function_name) {
        if (is_array($filter_function_name)) {
            $this->before_filter = $filter_function_name;
        } else {
            $this->before_filter[] = $filter_function_name;
        }
    }

    function execute_after_filters() {
        if(is_array($this->controller_object->after_filter)) {
            foreach($this->controller_object->after_filter as $filter_function) {
                if(method_exists($this->controller_object, $filter_function)) {
                    $this->controller_object->$filter_function();
                }
            }
        }
    }

    function add_after_filter($filter_function_name) {
        if (is_array($filter_function_name)) {
            $this->after_filter = $filter_function_name;
        } else {
            $this->after_filter[] = $filter_function_name;
        }
    }

    function include_extra_helpers() {
        if(is_array($this->controller_object->helpers)) {
            foreach($this->controller_object->helpers as $helper) {
                if(file_exists($this->helpers_base_path . "/$helper")) {
                    include_once($this->helpers_base_path . "/$helper");
                }
            }
        }
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
            include($path_with_file);
        }
    }

    function error($error_code, $error_heading, $error_message) {
        header("HTTP/1.0 {$error_code} {$error_heading}");
        // check for user's layout for errors
        if(file_exists(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['layouts']."/trax_error.phtml")) {
            include(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['layouts']."/trax_error.phtml");
        } elseif(file_exists(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['lib']."/templates/error.phtml")) {
            // use default layout for errors
            include(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['lib']."/templates/error.phtml");
        } else {
            echo "<h1>$error_heading</h1><br>$error_message";
        }
        exit;
    }

}

?>