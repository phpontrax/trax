<?php
/**
 *  File containing ActionController class
 *
 *  (PHP 5)
 *
 *  @package PHPonTrax
 *  @version $Id$
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
 *  Action controller
 *
 *  <p>The ActionController base class operates as follows:</p>
 *  <ol>
 *    <li>Accept a URL as input</li>
 *    <li>Translate the URL into a controller and action</li>
 *    <li>Create the indicated controller object (which is a subclass
 *      of ActionController) and call its action method</li>
 *    <li>Render the output of the action method</li>
 *    <li>Redirect to the next URL</li>
 *  </ol>
 *
 *  For details see the
 *  {@tutorial PHPonTrax/ActionController.cls class tutorial}
 */
class ActionController {

    /**
     *  Name of the controller (without the _controller.php)
     *
     *  Set by {@link recognize_route()}
     *  @var string
     */
    private $controller;

    /**
     *  Name of the action method in the controller class
     *
     *  Set by {@link recognize_route()}
     *  @var string
     */
    private $action;

    /**
     *  Value of :id parsed from URL then forced to lower case
     *
     *  Set by {@link recognize_route()}
     *  @var string
     */
    private $id;

    /**
     *  Path to add to other filesystem paths
     *
     *  Set by {@link recognize_route()}
     *  @var string
     */
    private $added_path = '';
     
    /**
     *  Filesystem path to ../app/controllers/ directory
     *
     *  Set by {@link recognize_route()}
     *  @var string
     */
    private $controllers_path;

    /**
     *  Filesystem path to ../app/helpers/<i>extras</i> directory
     *
     *  Set by {@link recognize_route()}, {@link set_paths()}
     *  @var string
     */
    private $helpers_path;

    /**
     *  Filesystem path to ../app/helpers/ directory
     *
     *  Set by {@link recognize_route()}
     *  @var string
     */
    private $helpers_base_path;

    /**
     *  Filesystem path to ../app/views/layouts/<i>extras</i> directory
     *
     *  Set by {@link recognize_route()}, {@link set_paths()}
     *  @var string
     */
    private $layouts_path;

    /**
     *  Filesystem path to ../app/views/layouts/ directory
     *
     *  Set by {@link recognize_route()}
     *  @var string
     */
    private $layouts_base_path;

    /**
     *  User's URL in components
     *
     *  Contains user's URL stripped of TRAX_URL_PREFIX and leading
     *  and trailing slashes, then exploded into an array on slash
     *  boundaries.
     *  @var string[]
     */
    private $url_path;

    /**
     *  Filesystem path to the default layouts file application.phtml
     *
     *  Set by {@link recognize_route()}
     *  @var string
     */
    private $default_layout_file;

    /**
     *  Filesystem path to the controllername_helper.php file
     *
     *  Set by {@link recognize_route()}
     *  @var string
     */
    private $helper_file;

    /**
     *  Filesystem path to application.php file
     *
     *  Set by {@link recognize_route()}
     *  @see $controller_file
     *  @var string
     */
    private $application_controller_file;

    /**
     *  Filesystem path to application_helper.php file
     *
     *  Set by {@link recognize_route()}
     *  @var string
     */
    private $application_helper_file;

    /**
     *  URL recognized, paths resoved, controller file found
     *
     *  Set by {@link recognize_route()}
     *  @var boolean
     */
    private $loaded = false;

    /**
     *  Whether a Router object was loaded
     *
     *  @var boolean
     *  <ul>
     *    <li>true => $router points to the Router object</li>
     *    <li>false => no Router object exists</li>
     *  </ul>
     *  @todo <b>FIXME:</b> No declaration of $router so no place to hang
     *    its documentation.
     */
    private $router_loaded = false;

    /**
     *  List of additional helper files for this controller object
     *
     *  Set by {@link add_helper()}
     *  @var string[]
     */
    private $helpers = array();

    /**
     *  List of filters to execute before calling action method
     *
     *  Set by {@link add_before_filters()
     *  @var string[]
     */
    private $before_filters = array();

    /**
     *  List of filters to execute after calling action method
     *
     *  Set by {@link add_after_filters()
     *  @var string[]
     */
    private $after_filters = array();     

    /**
     *  @todo Document this attribute
     */
    protected $before_filter = null;

    /**
     *  @todo Document this attribute
     */
    protected $after_filter = null;

    /**
     *  Filesystem path to the PHP program file for this controller
     *
     *  Set by {@link recognize_route()}
     *  @see $application_controller_file
     *  @var string
     */
    public $controller_file;

    /**
     *  Filesystem path to the view file selected for this action
     *
     *  Set by {@link process_route()
     *  @var string
     */
    public $view_file;

    /**
     *  Filesystem path to the ../app/views/ directory
     *
     *  Set by {@link recognize_route()}
     *  @var string
     */
    public $views_path;

    /**
     *  Class name of the controller
     *
     *  Set by {@link recognize_route()}.
     *  Derived from contents of {@link $controller}.
     *  @var string
     */
    public $controller_class;

    /**
     *  Instance of the controller class
     *
     *  Set by {@link process_route()}
     *  @var object
     */
    public $controller_object;

    /**
     *  @todo Document this attribute
     *  @todo <b>FIXME:</b> Not referenced in this class - is it used
     *        by subclasses?  If so, for what?
     */
    public $asset_host = null;

    /**
     *  File extension appended to view files
     *
     *  Set from a define in {@link environment.php}.  Usually phtml
     *  @var string
     */
    public $views_file_extention = TRAX_VIEWS_EXTENTION;

    /**
     *  Whether to keep flash message after displaying it
     *  @var boolean
     */
    public $keep_flash = false;

    /**
     *  Build a Router object and load routes from config/route.php
     *  @uses load_router()
     */
    function __construct() {
        if(!isset($this->router) || !is_object($this->router)) {
            $this->load_router();
        }
    }

    /**
     *  @todo Document this method
     *  @uses add_after_filter()
     *  @uses add_before_filter()
     *  @uses add_helper()
     */
    function __set($key, $value) {
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

    /**
     *  @todo Document this method
     *  Implement before_filter(), after_filter(), helper()
     */
    function __call($method_name, $parameters) {
        if(method_exists($this, $method_name)) {
            # If the method exists, just call it
            $result = call_user_func(array($this, $method_name), $parameters);
        } else {        
            if($method_name == "before_filter") {
                $result = call_user_func(array($this, 'add_before_filter'), $parameters);
            } elseif($method_name == "after_filter") {
                $result = call_user_func(array($this, 'add_after_filter'), $parameters);
            } elseif($method_name == "helper") {
                $result = call_user_func(array($this, 'add_helper'), $parameters);
            } 
        }
        return $result;
    }

    /**
     *  Load routes from configuration file config/routes.php
     *
     *  Routes are loaded by requiring {@link routes.php} from the
     *  configuration directory.  The file routes.php contains
     *  statements of the form "$router->connect(path,params);" where
     *  (path,params) describes the route being added by the
     *  statement. Route syntax is described in
     *  {@tutorial PHPonTrax/Router.cls the Router class tutorial}.
     *
     *  @uses Router
     *  @uses $router
     *  @uses $router_loaded
     */
    function load_router() {
        $this->router_loaded = false;
        $router = new Router();

        // Load the routes.
        require(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['config']."/routes.php");
        $this->router = $router;
        if(is_object($this->router)) {
            $this->router_loaded = true;
        }
    }

    /**
     *  Convert URL to controller, action and id
     *
     *  Parse the URL in
     *  {@link
     *  http://www.php.net/manual/en/reserved.variables.php#reserved.variables.server $_SERVER}['REDIRECT_URL']
     *  into elements.
     *  Compute filesystem paths to the various components used by the
     *  URL and store the paths in object private variables.
     *  Verify that the controller exists.
     *
     *  @uses load_router()
     *  @uses $action
     *  @uses $application_controller_file
     *  @uses $controller
     *  @uses $controller_class
     *  @uses $controller_file
     *  @uses $controllers_path
     *  @uses $default_layout_file
     *  @uses $helper_file
     *  @uses $helpers_path
     *  @uses $id
     *  @uses $layouts_path
     *  @uses $loaded
     *  @uses $router
     *  @uses $router_loaded
     *  @uses set_paths()
     *  @uses $url_path
     *  @uses $views_file_extention
     *  @uses $views_path
     *  @return boolean
     *  <ul>
     *    <li>true =>  route recognized, controller found.</li>
     *    <li>false => failed, route not recognized.</li>
     *  </ul>
     */
    function recognize_route() {
        if(!$this->router_loaded) {
            $this->load_router();
        }

        # current url
        $browser_url = $_SERVER['REDIRECT_URL'];
        //error_log('browser url='.$browser_url);
        # strip off url prefix, if any
        if(!is_null(TRAX_URL_PREFIX)) {
            // FIXME: Do we know for sure that the
            // initial '/' will be there?
            $browser_url = str_replace('/'.TRAX_URL_PREFIX,"",$browser_url);
        }
        //error_log('browser url='.$browser_url);

        # strip leading slash
        // FIXME: Do we know for sure that the
        // initial '/' will be there?
        $browser_url = substr($browser_url,1);
        //error_log('browser url='.$browser_url);

        # strip trailing slash (if any)
        if(substr($browser_url, -1) == "/") {
            $browser_url = substr($browser_url, 0, -1);
        }
        //error_log('browser url='.$browser_url);

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

            //  find_route() returns an array if it finds a path that
            //  matches the URL, null if no match found
            if(is_array($route)) {
                $this->set_paths();
                $route_path = explode("/",$route['path']);
                $route_params = $route['params'];

                if(@array_key_exists(":controller",$route_params)) {
                    $this->controller = $route_params[":controller"];
                } elseif(@in_array(":controller",$route_path)) {
                    $this->controller = strtolower($this->url_path[@array_search(":controller", $route_path)]);
                }
                //error_log('controller='.$this->controller);
                if(@array_key_exists(":action",$route_params)) {
                    $this->action = $route_params[':action'];
                } elseif(@in_array(":action",$route_path)
                   && array_key_exists(@array_search(":action", $route_path),
                                       $this->url_path)) {
                    $this->action = strtolower($this->url_path[@array_search(":action", $route_path)]);
                }
                //error_log('action='.$this->action);
                //  FIXME: RoR uses :name as a keyword parameter, id
                //  is not treated as a special case.
                //  Do we want to do the same?
                if(@in_array(":id",$route_path)
                   && array_key_exists(@array_search(":id", $route_path),
                                       $this->url_path)) {
                    $this->id = strtolower($this->url_path[@array_search(":id", $route_path)]);
                    if($this->id != "") {
                        $_REQUEST['id'] = $this->id;
                    }
                }
                //error_log('id='.$this->id);
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

    /**
     *  Parse URL, extract controller and action and execute them
     *
     *  @uses $action
     *  @uses $application_controller_file
     *  @uses $application_helper_file
     *  @uses $controller
     *  @uses $controller_class
     *  @uses $controller_file
     *  @uses $controller_object
     *  @uses determine_layout()
     *  @uses execute_after_filters()
     *  @uses $helpers
     *  @uses $helper_file
     *  @uses $helpers_base_path
     *  @uses $keep_flash
     *  @uses $loaded
     *  @uses recognize_route()
     *  @uses raise()
     *  @uses ScaffoldController
     *  @uses Session::unset_var()
     *  @uses $view_file
     *  @uses $views_file_extention
     *  @uses $views_path
     *  @return boolean true
     */
    function process_route() {

        $render_layout = true;
        # First try to load the routes and setup the paths to everything
        if(!$this->loaded) {
            if(!$this->recognize_route()) {
                $this->raise("Failed to load any defined routes",
			     "Controller ".$this->controller." not found",
			     "404");
            }
        }

        # Suppress output
        ob_start();

        # Include main application controller file
        if(file_exists($this->application_controller_file)) {
            include_once($this->application_controller_file);
        }

        //error_log('process_route() controller="'.$this->controller
        //          .'"  action="'.$this->action.'"');
        # Include the controller file and execute action
        // FIXME: redundant, recognize_route() already test for file exists
        if (file_exists($this->controller_file)) {
            include_once($this->controller_file);
            if(class_exists($this->controller_class,false)) {
                $class = $this->controller_class;
                $this->controller_object = new $class();
                if(is_object($this->controller_object)) {
                    $this->controller_object->controller = $this->controller;
                    $this->controller_object->action = $this->action;
                    $this->controller_object->controller_path = "$this->added_path/$this->controller";
                    $GLOBALS['current_controller_path'] = "$this->added_path/$this->controller";
                    $GLOBALS['current_controller_name'] = $this->controller;
                    $GLOBALS['current_action_name'] = $this->action;
                    $GLOBALS['current_controller_object'] =& $this->controller_object;
                    // error_log('$GLOBALS[\'current_action_name\']='
                    //           .$GLOBALS['current_action_name']);
                    // error_log('$GLOBALS[\'current_controller_name\']='
                    //           .$GLOBALS['current_controller_name']);
                    // error_log('$GLOBALS[\'current_controller_path\']='
                    //           .$GLOBALS['current_controller_path']);
                }

                # Which layout should we use?
                $layout_file = $this->determine_layout();
                // error_log('layout_file="'.$layout_file.'"');
                // # Check if there is any defined scaffolding to load
                if(isset($this->controller_object->scaffold)) {
                    $scaffold = $this->controller_object->scaffold;
                    if(file_exists(TRAX_LIB_ROOT."/scaffold_controller.php")) {
                        include_once(TRAX_LIB_ROOT."/scaffold_controller.php");
                        $this->controller_object = new ScaffoldController($scaffold);
                        $GLOBALS['current_controller_object'] =& $this->controller_object;
                        if($this->action) {
                            $this->view_file = TRAX_LIB_ROOT . "/templates/scaffolds/".$this->action.".phtml";
                        } else {
                            $this->view_file = TRAX_LIB_ROOT . "/templates/scaffolds/index.phtml";
                        }
                        if(!file_exists($layout_file)) {
                            # the generic scaffold layout
                            $layout_file = TRAX_LIB_ROOT . "/templates/scaffolds/layout.phtml";
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
                // FIXME: shouldn't we check return here?
                if(method_exists($this->controller_object, $this->action)) {
                    //error_log('controller has method "'.$this->action.'"');
                    $action = $this->action;
                    $this->controller_object->$action();
                } elseif(file_exists($this->views_path . "/" . $this->action . "." . $this->views_file_extention)) {
                    // error_log('views file "'.$this->action.'"');
                    $action = $this->action;
                } elseif(method_exists($this->controller_object, "index")) {
                    //error_log('calling index()');
                    $this->controller_object->index();
                } else {
                    //error_log('no action');
                    $this->raise("No action responded to ".$this->action, "Unknown action", "404");
                }
                $this->execute_after_filters();

                # Find out if there was a redirect to some other page
                if(isset($this->controller_object->redirect_to)
                    && $this->controller_object->redirect_to != '') {
                    $this->redirect_to($this->controller_object->redirect_to);
                    //  redirect_to() exits instead of returning
                } else {
                    # Pull all the class vars out and turn them from $this->var to $var
                    extract(get_object_vars($this->controller_object));
                }

                if(isset($this->controller_object->render_text)
                   && $this->controller_object->render_text != "") {
                    echo $this->controller_object->render_text;
                } else {
                    # If this isn't a scaffolding then get the view file to include
                    if(!isset($scaffold)) { 
                        // error_log('not scaffolding, looking for view file');
                        # Normal processing of the view
                        if(isset($this->controller_object->render_action)
                           && $this->controller_object->render_action != '' ) {
                            $this->view_file = $this->views_path . "/" . $this->controller_object->render_action . "." . $this->views_file_extention;
                        } elseif(isset($action)
                                 && $action != '') {
                            $this->view_file = $this->views_path . "/" . $action . "." . $this->views_file_extention;
                        } else {
                            $this->view_file = $this->views_path . "/" . "index" . "." . $this->views_file_extention;
                        }
                        // error_log('view file='.$this->view_file);
                    }

                    if(file_exists($this->view_file)) {
                        # grab view html
                        include($this->view_file);
                    } else {
                        $this->raise("No view file found $action ($this->view_file).", "Unknown view", "404");
                    }

                    # Grab all the html from the view to put into the layout
                    $content_for_layout = ob_get_contents();
                    ob_end_clean();

                    //error_log('layout file='.$layout_file
                    //          .'  render_layout='
                    //          .var_export($render_layout,true));
                    if(file_exists($layout_file) && $render_layout !== false) {
                        # render the layout
                        include($layout_file);
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

        // error_log('keep flash='.var_export($this->keep_flash,true));
        if(!$this->keep_flash) {
            # Nuke the flash
            Session::unset_var('flash');
        }

        return true;
    }                                // function process_route()

    /**
     *  Extend the search path for components
     *
     *  On entry, $url_path is set according to the browser's URL and 
     *  $controllers_path has been set according to the configuration
     *  in {@link environment.php config/environment.php} .  Examine
     *  the $controllers_path directory for files or directories that
     *  match any component of the URL.  If one is found, add that
     *  component to all paths.  Replace the contents of $url_path
     *  with the list of URL components that did NOT match any files
     *  or directories.
     *  @uses $added_path
     *  @uses $controllers_path
     *  @uses $helpers_path
     *  @uses $layouts_path
     *  @uses $views_path
     *  @uses $url_path
     *  @todo <b>FIXME:</b> Creating a file or directory in
     *        app/controllers with the same name as a controller, action or
     *        other URL element will hijack the browser!
     */
    function set_paths() {
        if(is_array($this->url_path)) {
            foreach($this->url_path as $path) {
                if(file_exists($this->controllers_path . "/$path")) {
                    $extra_path[] = $path;
                } else {
                    $new_path[] = $path;
                }
            }
            if(isset($extra_path) && is_array($extra_path)) {
                $extra_path = implode("/", $extra_path);
                $this->added_path = $extra_path;
                $this->controllers_path .= "/$extra_path";
                $this->helpers_path .= "/$extra_path";
                $this->views_path .= "/$extra_path";
                $this->layouts_path .= "/$extra_path";
            }
            if(isset($new_path)
               && is_array($new_path)) {
                $this->url_path = $new_path;
            }
        }
    }

    /**
     *  Execute the before filters
     *  @uses $before_filters
     */
    function execute_before_filters() {
        if(count($this->controller_object->before_filters) > 0) { 
            foreach($this->controller_object->before_filters as $filter_function) {
                if(method_exists($this->controller_object, $filter_function)) {
                    $this->controller_object->$filter_function();
                }
            }
        }
    }

    /**
     *  Append a before filter to the filter chain
     *
     *  @param mixed $filter_function_name  String with the name of
     *  one filter function, or array of strings with the names of
     *  several filter functions.
     *  @uses $before_filters
     */
    function add_before_filter($filter_function_name) {
        if(is_string($filter_function_name) && !empty($filter_function_name)) {
            if(!in_array($filter_function_name, $this->before_filters)) {
                $this->before_filters[] = $filter_function_name;
            }                        
        } elseif(is_array($filter_function_name)) {
            if(count($this->before_filters) > 0) {
                $this->before_filters = array_merge($this->before_filters, $filter_function_name);
            } else {
                $this->before_filters = $filter_function_name;
            }
        }
    }

    /**
     *  Execute the after filters
     *  @uses $after_filters
     */
    function execute_after_filters() {
        if(count($this->controller_object->after_filters) > 0) {
            foreach($this->controller_object->after_filters as $filter_function) {
                if(method_exists($this->controller_object, $filter_function)) {
                    $this->controller_object->$filter_function();
                }
            }
        }
    }


    /**
     *  Append an after filter to the filter chain
     *
     *  @param mixed $filter_function_name  String with the name of
     *  one filter function, or array of strings with the names of
     *  several filter functions.
     *  @uses $after_filters
     */
    function add_after_filter($filter_function_name) {
        if(is_string($filter_function_name) && !empty($filter_function_name)) {
            if(!in_array($filter_function_name, $this->controller_object->after_filters)) {
                $this->controller_object->after_filters[] = $filter_function_name;
            }
        } elseif(is_array($filter_function_name)) {
            if(count($this->controller_object->after_filters) > 0) {
                $this->controller_object->after_filters = array_merge($this->controller_object->after_filters, $filter_function_name);
            } else {
                $this->controller_object->after_filters = $filter_function_name;
            }
        }
    }

    /**
     *  Add a helper to the list of helpers used by a controller
     *  object
     *
     *  @param $helper_name string Name of a helper to add to the list
     *  @uses  $helpers
     *  @uses  $controller_object
     */
    function add_helper($helper_name) {
        if(!in_array($helper_name, $this->controller_object->helpers)) {
            $this->controller_object->helpers[] = $helper_name;
        }
    }

    /**
     *  Rendering partials
     * 
     *  <p>Partial rendering is most commonly used together with Ajax
     *  calls that only update one or a few elements on a page without
     *  reloading. Rendering of partials from the controller makes it
     *  possible to use the same partial template in both the
     *  full-page rendering (by calling it from within the template)
     *  and when sub-page updates happen (from the controller action
     *  responding to Ajax calls). By default, the current layout is
     *  not used.</p>
     *
     *  <ul>
     *    <li><samp>render_partial("win");</samp><br>
     *      Renders the partial
     *      located at app/views/controller/_win.r(html|xml)</li>
     *
     *    <li><samp>render_partial("win",
     *       array("locals" => array("name" => "david")));</samp><br>
     *      Renders the same partial but also makes a local variable
     *      available to it</li>
     *     
     *    <li><samp>render_partial("win",
     *      array("collection" => array(...)));</samp><br>
     *      Renders a collection of the same partial by making each
     *      element of the collection available through the local variable
     *      "win" as it builds the complete response </li>
     *
     *    <li><samp>render_partial("win", array("collection" => @wins,
     *      "spacer_template" => "win_divider"));</samp><br>
     *      Renders the same collection of partials, but also renders
     *      the win_divider partial in between each win partial.</li>
     *  </ul>
     *  @param string $path Path to file containing partial view
     *  @param string[] $options Options array
     */
    function render_partial($path, $options = array()) {
        if(strstr($path, "/")) {
            $file = substr(strrchr($path, "/"), 1);
            $path = substr($path, 0, strripos($path, "/"));
            $file_with_path = TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['views']."/".$path."/_".$file.".".$this->views_file_extention;
        } else {
            $file = $path;
            $file_with_path = $this->views_path."/_".$file.".".$this->views_file_extention;
        }

        if(file_exists($file_with_path)) {
            
            if(array_key_exists("spacer_template", $options)) {
                $spacer_path = $options['spacer_template'];
                if(strstr($spacer_path, "/")) {
                    $spacer_file = substr(strrchr($spacer_path, "/"), 1);
                    $spacer_path = substr($spacer_path, 0, strripos($spacer_path, "/"));
                    $spacer_file_with_file = TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['views']."/".$spacer_path."/_".$spacer_file.".".$this->views_file_extention;
                } else {
                    $spacer_file = $spacer_path;
                    $spacer_file_with_file = $this->views_path."/_".$spacer_file.".".$this->views_file_extention;
                }  
                if(file_exists($spacer_file_with_file)) {
                    $add_spacer = true;    
                }
            }          

            # Pull all the class vars out and turn them from $this->var to $var
            if(is_object($this->controller_object)) {
                extract(get_object_vars($this->controller_object));
            }
            if(array_key_exists("collection", $options)) {
                foreach($options['collection'] as $tmp_value) {
                    ${$file."_counter"}++;
                    ${$file} = $tmp_value;
                    unset($tmp_value);
                    include($file_with_path);    
                    if($add_spacer && (${$file."_counter"} < count($options['collection']))) {
                        include($spacer_file_with_file);        
                    }         
                }    
            } else {
                if(array_key_exists("locals", $options)) {
                    foreach($options['locals'] as $tmp_key => $tmp_value) {
                        ${$tmp_key} = $tmp_value;                
                    }    
                    unset($tmp_key);
                    unset($tmp_value);
                }
                include($file_with_path);        
            }           
        }
    }

    /**
     *  Select a layout file based on the controller object
     *
     *  @uses $controller_object
     *  @uses $layouts_base_path
     *  @uses $layouts_path
     *  @return mixed Layout file or null if none
     *  @todo <b>FIXME:</b> Should this method be private?
     */
    function determine_layout() {
        # I guess you don't want any layout
        if($this->controller_object->layout == "null") {
            //error_log('controller->layout absent');
            return null;
        }
        # $layout will be the layout defined in the current controller
        # or try to use the controller name for the layout
        $layout = (isset($this->controller_object->layout)
                   && $this->controller_object->layout != '')
            ? $this->controller_object->layout : $this->controller;
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
        //  No defined layout found so just use the default layout
        //  app/views/layouts/application.phtml 
        //  FIXME: this file isn't in the distribution so
        //  this reference will fail
        if(!isset($layout_file)) {
            $layout_file = $this->default_layout_file;
        }
        return $layout_file;
    }

    /**
     *  Redirect the browser to a specified target
     *
     *  Redirect the browser to the target specified in $options. This
     *  parameter can take one of three forms:
     *  <ul>
     *    <li>Array: The URL will be generated by calling
     *      {@link url_for()} with the options.</li>
     *    <li>String starting with a protocol:// (like http://): Is
     *      passed straight through as the target for redirection.</li>
     *    <li>String not containing a protocol: The current protocol
     *      and host is prepended to the string.</li>
     *    <li>back: Back to the page that issued the request. Useful
     *      for forms that are triggered from multiple
     *      places. Short-hand for redirect_to(request.env["HTTP_REFERER"]) 
     *   </ul>
     *
     *  Examples:
     *  <ul>
     *    <li>redirect_to(array(":action" => "show", ":id" => 5))</li>
     *    <li>redirect_to("http://www.rubyonrails.org")</li>
     *    <li>redirect_to("/images/screenshot.jpg")</li>
     *    <li>redirect_to("back")</li>
     *  </ul>
     *
     *  @param mixed $options array or string url  
     *  @todo <b>FIXME:</b> Make header configurable
     */    
    function redirect_to($options = null) {
        if($options == "back") {
            $url = $_SERVER["HTTP_REFERER"];
        } else {
            $url = url_for($options);     
        }
        echo "<html><head><META HTTP-EQUIV=\"REFRESH\" CONTENT=\"0; URL=".$url."\"></head></html>";
        #header("Location: ".$url);   
        exit;            
    }

    /**
     *  Raise an ActionControllerError exception
     *
     *  @param string $error_message Error message
     *  @param string $error_heading Error heading
     *  @param string $error_code    Error code
     *  @throws ActionControllerError
     */
    function raise($error_message, $error_heading, $error_code = "404") {
        throw new ActionControllerError("Error Message: ".$error_message, $error_heading, $error_code);
    }

    /**
     *  Generate an HTML page describing an error
     */
    function process_with_exception(&$exception) {
        $error_code = $exception->error_code;
        $error_heading = $exception->error_heading;
        $error_message = $exception->error_message;
        $trace = $exception->getTraceAsString();
        header('HTTP/1.0 {$error_code} {$error_heading}');
        header('status: {$error_code} {$error_heading}'); 
        # check for user's layout for errors
        if(DEBUG && file_exists(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['layouts']."/trax_error.".TRAX_VIEWS_EXTENTION)) {
            include(TRAX_ROOT.$GLOBALS['TRAX_INCLUDES']['layouts']."/trax_error.".TRAX_VIEWS_EXTENTION);
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

// -- set Emacs parameters --
// Local variables:
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>