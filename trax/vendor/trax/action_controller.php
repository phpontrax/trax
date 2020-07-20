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
     *  Set by {@link recognize_route()} by parsing the URL and the
     *  routes in {@link routes.php}.  The value of this string is set
     *  before any attempt is made to find the file containing the
     *  controller.
     *  @var string
     */
    protected $controller;

    /**
     *  Name of the action method in the controller class
     *
     *  Set by {@link recognize_route()}
     *  @var string
     */
    protected $action;

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
     *  Loaded plugin
     *
     *  Set by {@link recognize_route()}
     *  @var string
     */
    public $plugin = null;

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
    public $before_filters = array();

    public $before_filter_options = array();

    /**
     *  List of filters to execute after calling action method
     *
     *  Set by {@link add_after_filters()
     *  @var string[]
     */
    private $after_filters = array();

    private $after_filter_options = array();

    /**
     *  @todo Document this attribute
     */
    private $render_performed = false;

    /**
     *  @todo Document this attribute
     */
    private $action_called = false;

    /**
     *  @todo Document this attribute
     */
    #protected $before_filter = null;

    /**
     *  @todo Document this attribute
     */
    #protected $after_filter = null;

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
     *  @var string
     */
    public $asset_host = null;

    /**
     *  Render controllers layout
     *
     *  Can be overridden in the child controller to false
     *  @var boolean
     */
    public $render_layout = true;

    /**
     *  Whether to keep flash message after displaying it
     *  @var boolean
     */
    public $keep_flash = false;

    /**
     *  Keeps track of open blocks when calling content_for()
     *  @var array
     */
    public $content_for_open_blocks = array();

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
        #if(is_string($value) || is_array($value)) {
            #error_log("__set($key, $value)");
        #}
        if($key == "before_filter") {
            $this->add_before_filter($value);
            unset($this->$key);
        } elseif($key == "after_filter") {
            $this->add_after_filter($value);
        } elseif($key == "helper") {
            $this->add_helper($value);
        } elseif($key == "render_text") {
            $this->render_text($value);
        } elseif($key == "redirect_to") {
            $this->redirect_to($value);
        } elseif($key == "layout") {
            $this->layout = $value;
            $this->determine_layout();
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
            #error_log("calling method:{$method_name} - params:".print_r($parameters, true));
            # If the method exists, just call it
            $result = call_user_func_array(array($this, $method_name), $parameters);
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
        require(Trax::$config_path."/routes.php");
        // Load any plugins routes
        if(count(Trax::$plugins)) {
            foreach(Trax::$plugins as $plugin) {
                require(Trax::$plugins_path."/{$plugin}/config/routes.php");
            }
        }
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
     *  @uses $helper_file
     *  @uses $helpers_path
     *  @uses $id
     *  @uses $layouts_path
     *  @uses $loaded
     *  @uses $router
     *  @uses $router_loaded
     *  @uses set_paths()
     *  @uses $url_path
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
        if(isset($_SERVER['REDIRECT_URL']) && !stristr($_SERVER['REDIRECT_URL'], 'dispatch.php')) {
            $browser_url = $_SERVER['REDIRECT_URL'];
        } elseif(isset($_SERVER['REQUEST_URI'])) {
            $browser_url = strstr($_SERVER['REQUEST_URI'], "?") ?
                substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], "?")) :
                $_SERVER['REQUEST_URI'];
        }

        //error_log('browser url='.$browser_url);
        # strip off url prefix, if any
        if(!is_null(Trax::$url_prefix)) {
            $browser_url = str_replace(Trax::$url_prefix, "", $browser_url);
        }

        # strip leading slash (if any)
        if(substr($browser_url, 0, 1) == "/") {
            $browser_url = substr($browser_url, 1);
        }

        # strip trailing slash (if any)
        if(substr($browser_url, -1) == "/") {
            $browser_url = substr($browser_url, 0, -1);
        }

        if($browser_url) {
            $this->url_path = explode("/", !is_null(Trax::$url_word_seperator) ?
                # for seo to be able to put dashes in the url
                str_replace(Trax::$url_word_seperator, "_", $browser_url) :
                $browser_url
            );
        } else {
            $this->url_path = array();
        }

        if($this->router->routes_count > 0) {
            $this->controllers_path = Trax::$controllers_path;
            $this->helpers_path = $this->helpers_base_path = Trax::$helpers_path;
            $this->application_controller_file = $this->controllers_path . "/application.php";
            $this->application_helper_file = $this->helpers_path . "/application_helper.php";
            $this->layouts_path = Trax::$layouts_path;
            $this->views_path = Trax::$views_path;

            $route = $this->router->find_route($browser_url);
            #echo "browser_url:$browser_url Found Route:".print_r($route, true)."<br>";
            //  find_route() returns an array if it finds a path that
            //  matches the URL, null if no match found
            if(is_array($route)) {

                //  Matching route found.  Try to get
                //  controller and action from route and URL
                #print_r($this->router);
                #echo "<br>";
                if(isset($route['plugin'])) {
                    $this->plugin = $route['plugin'];
                    $this->controllers_path = Trax::$plugins_path."/{$route['plugin']}/app/controllers";
                    $this->helpers_path = $this->helpers_base_path = Trax::$plugins_path."/{$route['plugin']}/app/helpers";
                    $this->layouts_path = Trax::$plugins_path."/{$route['plugin']}/app/views/layouts";
                    $this->views_path = Trax::$plugins_path."/{$route['plugin']}/app/views";
                }
                $this->set_paths();
                $route_path = explode("/",$route['path']);
                $route_params = $route['params'];

                //  Find the controller from the route and URL
                if(is_array($route_params)
                   && array_key_exists(":controller",$route_params)) {

                    //  ':controller' in route params overrides URL
                    $this->controller = $route_params[":controller"];
                    if(stristr($route_params[":controller"], "/")) {
                        $controller_path_parts = explode("/", $route_params[":controller"]);
                        $this->controller = array_pop($controller_path_parts);
                        $this->controllers_path .= ("/" .  implode("/", $controller_path_parts));
                    }
                } elseif(is_array($route_path)
                         && in_array(":controller",$route_path)
                         && (count($this->url_path)>0)) {

                    //  Set controller from URL if that field exists
                    $this->controller = strtolower($this->url_path[array_search(":controller", $route_path)]);
                }
                //error_log('controller='.$this->controller);

                //  Find the action from the route and URL
                if(is_array($route_params)
                   && array_key_exists(":action",$route_params)) {

                    //  ':action' in route params overrides URL
                    $this->action = $route_params[':action'];
                } elseif(is_array($route_path)
                         && in_array(":action",$route_path)
                         && array_key_exists(@array_search(":action",
                                                           $route_path),
                                             $this->url_path)) {

                    //  Get action from URL if that field exists
                    $this->action = strtolower($this->url_path[@array_search(":action", $route_path)]);
                }
                //error_log('action='.$this->action);

                //  FIXME: RoR uses :name as a keyword parameter, id
                //  is not treated as a special case.
                //  Do we want to do the same?
                $id = null;
                if(is_array($route_params)
                   && array_key_exists(":id",$route_params)) {

                    //  ':id' in route params overrides URL
                    $id = $route_params[':id'];
                } elseif(@in_array(":id",$route_path)) {
                    #print_r($this->url_path);
                    #print_r($route_path);
                    #if(count($this->extra_path)) {
                        #foreach(array_reverse($this->extra_path) as $extra_path) {
                            #array_unshift($this->url_path, $extra_path);
                        #}
                    #}
                    #print_r($this->url_path);

                    $idPathKey = @array_search(":id", $route_path);

                    if(isset($this->url_path[$idPathKey]))
                        $id = strtolower($this->url_path[$idPathKey]);
                    else
                        $id = null;
                }
                //  For historical reasons, continue to pass id
                //  in $_REQUEST
                if($id != "") {
                    $_REQUEST['id'] = $id;
                }
                //error_log('id='.$id);

                $this->views_path .= "/" . $this->controller;
                $this->controller_file = $this->controllers_path . "/" .  $this->controller . "_controller.php";
                $this->controller_class = Inflector::camelize(Inflector::demodulize($this->controller)) . "Controller";
                $this->helper_file = $this->helpers_path . "/" .  $this->controller . "_helper.php";
                # error_log("controller:{$this->controller} - controller_file:{$this->controller_file} - controller_class:{$this->controller_class} - views_path:{$this->views_path}");
            }
        }

        if(is_file($this->controller_file)) {
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
     *  @uses $views_path
     *  @return boolean true
     */
    function process_route() {

        # First try to load the routes and setup the paths to everything
        if(!$this->loaded) {
            if(!$this->recognize_route()) {
                $this->raise("Failed to load any defined routes",
                 "Controller ".$this->controller." not found",
                 "404");
            }
        }
        //error_log('process_route(): controller="'.$this->controller
        //          .'"  action="'.$this->action.'"');

        # Include main application controller file
        if(file_exists($this->application_controller_file)) {
            include_once($this->application_controller_file);
        }

        # If controller is loaded then start processing
        if($this->loaded) {

            include_once($this->controller_file);
            if(class_exists($this->controller_class, false)) {
                $class = $this->controller_class;
                $this->controller_object = new $class();
            }

            if(is_object($this->controller_object)) {
                $this->controller_object->controller = $this->controller;
                $this->controller_object->action = $this->action;
                $this->controller_object->controller_path = "$this->added_path/$this->controller";
                $this->controller_object->views_path = $this->views_path;
                $this->controller_object->layouts_path = $this->layouts_path;
                $this->controller_object->plugin = $this->plugin;
                Trax::$current_controller_path = "$this->added_path/$this->controller";
                Trax::$current_controller_name = $this->controller;
                Trax::$current_action_name = $this->action;
                Trax::$current_controller_object =& $this->controller_object;
                # Which layout should we use?
                $this->controller_object->determine_layout();
                # Check if there is any defined scaffolding to load
                if(isset($this->controller_object->scaffold)) {
                    $scaffold = $this->controller_object->scaffold;
                    if(file_exists(TRAX_LIB_ROOT."/scaffold_controller.php")) {
                        include_once(TRAX_LIB_ROOT."/scaffold_controller.php");
                        $this->controller_object = new ScaffoldController($scaffold);
                        Trax::$current_controller_object =& $this->controller_object;
                        $render_options['scaffold'] = true;
                        if(!file_exists($this->controller_object->layout_file)) {
                            # the generic scaffold layout
                            $this->controller_object->layout_file = TRAX_LIB_ROOT . "/templates/scaffolds/layout.phtml";
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
                if(count($this->controller_object->helpers) > 0) {
                    foreach($this->controller_object->helpers as $helper) {
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

                # Suppress output
                ob_start();
                #error_log('started capturing HTML');

                # Call the controller method based on the URL
                if($this->controller_object->execute_before_filters()) {
                    $controller_layout = null;
                    if(isset($this->controller_object->layout)) {
                        $controller_layout = $this->controller_object->layout;
                    }

                    #Get PUBLIC methods from controller object
                    $all_methods = get_class_methods($this->controller_object);

                    # Get Inherited methods from active_controller
                    $inherited_methods = array_merge(
                        get_class_methods(__CLASS__),
                        $this->controller_object->before_filters,
                        $this->controller_object->after_filters
                    );

                    # Get non-inherited methods
                    $action_methods = array_diff($all_methods, $inherited_methods);
                    #error_log("available methods:".print_r($action_methods, true));

                    if(in_array($this->action, $action_methods)) {
                        #error_log('method '.$this->action.' exists, calling it');
                        $action = $this->controller_object->called_action = $this->action;
                        #error_log('calling action routine '
                        #          . get_class($this->controller_object)
                        #          .'::'.$action.'()');
                        $this->controller_object->$action();
                    } elseif(file_exists($this->views_path . "/" . $this->action . "." . Trax::$views_extension)) {
                        #error_log('views file "'.$this->action.'"');
                        $action = $this->controller_object->called_action = $this->action;
                    } elseif(method_exists($this->controller_object, "index")) {
                        #error_log('calling action routine '
                        #          . get_class($this->controller_object)
                        #          .'::index()');
                        $action = $this->controller_object->called_action = "index";
                        $this->controller_object->index();
                    } else {
                        //error_log('no action');
                        $methods_size = count($action_methods);
                        if($methods_size > 1) {
                            $last_method = ($methods_size > 2 ? "," : '')." and ".array_pop($action_methods);
                        }
                        $this->raise("No action responded to ".$this->action.". Actions:".implode(", ",$action_methods).$last_method, "Unknown action", "404");
                    }

                    if(isset($this->controller_object->layout)) {
                        if($controller_layout != $this->controller_object->layout) {
                            # layout was set in the action need to redetermine the layout file to use.
                            $this->controller_object->determine_layout();
                        }
                    }

                    #$this->controller_object->execute_after_filters();

                    $this->controller_object->action_called = true;

                    # Find out if there was a redirect to some other page
                    if(isset($this->controller_object->redirect_to)
                        && $this->controller_object->redirect_to != '') {
                        $this->redirect_to($this->controller_object->redirect_to);
                        # execution will end here redirecting to new page
                    }

                    # If render_text was defined as a string render it
                    if(isset($this->controller_object->render_text)
                       && $this->controller_object->render_text != "") {
                        $this->render_text($this->controller_object->render_text);
                        # execution will end here rendering only the text no layout
                    }

                    # If defined string render_action use that instead
                    if(isset($this->controller_object->render_action)
                       && $this->controller_object->render_action != '') {
                        $action = $this->controller_object->render_action;
                    }

                    # Render the action / view
                    if(!$this->controller_object->render_action($action,
                          isset($render_options) ? $render_options : null )) {
                        $this->raise("No view file found $action ($this->view_file).", "Unknown view", "404");
                    }

                    $this->controller_object->execute_after_filters();

                    # Grab all the html from the view to put into the layout
                    $content_for_layout = ob_get_contents();
                    ob_end_clean();
                    //error_log("captured ".strlen($content_for_layout)." bytes\n");
                    if(isset($this->controller_object->render_layout)
                       && ($this->controller_object->render_layout !== false)
                       && $this->controller_object->layout_file) {
                        $locals['content_for_layout'] = $content_for_layout;
                        # render the layout
                        #error_log("rendering layout: ".$this->controller_object->layout_file);
                        if(!$this->controller_object->render_file($this->controller_object->layout_file, false, $locals)) {
                            # No layout template so just echo out whatever is in $content_for_layout
                            //echo "HERE";
                            echo $content_for_layout;
                        }
                    } else {
                        # Can't find any layout so throw an exception
                        # $this->raise("No layout file found.", "Unknown layout", "404");
                        # No layout template so just echo out whatever is in $content_for_layout
                        //error_log("no layout found: ".$this->controller_object->layout_file);
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
            unset($_SESSION['flash']);
            Session::unset_var('flash');
        }

        return true;
    } // function process_route()

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
     */
    function set_paths() {
        if(is_array($this->url_path)) {
            $path_size = count($this->url_path) - 1;
            $test_path = $this->controllers_path;
            $url_path = $this->url_path;
            foreach($url_path as $position => $path) {
                $test_path .= "/$path";
                $test_file = $test_path."_controller.php";
                if(is_file($test_file)) {
                    $next = isset($url_path[$position+1]) ?
                        $test_path ."/".$url_path[$position+1] :
                        null;
                    if(!is_null($next)) {
                        if(is_file($next."_controller.php") || is_dir($next)) {
                            $extra_path[] = $path;
                            array_shift($this->url_path);
                        }
                    } else {
                        #error_log("found controller path:$path");
                        break;
                    }
                } elseif(is_dir($test_path)) {
                    $extra_path[] = $path;
                    array_shift($this->url_path);
                }
            }
            if(isset($extra_path) && is_array($extra_path)) {
                #error_log("extra_path:".print_r($extra_path, true));
                $this->extra_path = $extra_path;
                $extra_path = implode("/", $extra_path);
                $this->added_path = $extra_path;
                $this->controllers_path .= "/$extra_path";
                $this->helpers_path .= "/$extra_path";
                $this->views_path .= "/$extra_path";
                $this->layouts_path .= "/$extra_path";
            }
        }
    }

    /**
     *  Execute the before filters
     *  @uses $before_filters
     */
    function execute_before_filters() {

        #if(isset($this->before_filter)) {
            #$this->add_before_filter($this->before_filter);
        #}
        #error_log("before_filters:".print_r($this->before_filters, true));
        #error_log("before_filter_options:".print_r($this->before_filter_options, true));
        $return = true;
        if(count($this->before_filters) > 0) {
            $action = $this->action ? $this->action : "index";
            foreach($this->before_filters as $filter_function) {
                if(array_key_exists($filter_function, $this->before_filter_options)) {
                    if(is_array($options = $this->before_filter_options[$filter_function])) {
                        if(array_key_exists('except', $options)) {
                            if(preg_match("/\b$action\b/", $options['except'])) {
                                #error_log("before filter except match: action:{$action} == ".$options['except']);
                                continue;
                            }
                        }
                        if(array_key_exists('only', $options)) {
                            if(!preg_match("/\b$action\b/", $options['only'])) {
                                #error_log("before filter only non match: action:{$action} == ".$options['only']);
                                continue;
                            }
                        }
                    }
                }
                if(method_exists($this, $filter_function)) {
                    if(false === $this->$filter_function()) {
                        //error_log("execute_before_filters(): returning false");
                        $return = false;
                    }
                }
            }
        }
        return $return;
    }

    /**
     *  Append a before filter to the filter chain
     *
     *  @param mixed $filter_function_name  String with the name of
     *  one filter function, or array of strings with the names of
     *  several filter functions.
     *  @uses $before_filters
     */
    function add_before_filter($filter_function_name, $options = array(), $prepend = false) {
        //error_log("adding before filter: $filter_function_name");
        if(is_string($filter_function_name) && !empty($filter_function_name)) {
            if(!in_array($filter_function_name, $this->before_filters)) {
                if($prepend) {
                    array_unshift($this->before_filters, $filter_function_name);
                } else {
                    array_push($this->before_filters, $filter_function_name);
                }
                if(count($options)) {
                    if(count($this->before_filter_options[$filter_function_name])) {
                        $this->before_filter_options[$filter_function_name] = array_merge($this->before_filter_options[$filter_function_name], $options);
                    } else {
                        $this->before_filter_options[$filter_function_name] = $options;
                    }
                }
            }
        } elseif(is_array($filter_function_name)) {
            foreach($filter_function_name as $filter_name => $options) {
                if(!in_array($filter_name, $this->before_filters)) {
                    if($prepend) {
                        array_unshift($this->before_filters, $filter_name);
                    } else {
                        array_push($this->before_filters, $filter_name);
                    }
                    if(count($options)) {
                        if(count($this->before_filter_options[$filter_name])) {
                            $this->before_filter_options[$filter_name] = array_merge($this->before_filter_options[$filter_name], $options);
                        } else {
                            $this->before_filter_options[$filter_name] = $options;
                        }
                    }
                }
            }
        }
    }

    function prepend_before_filter($filter_function_name, $options = array()) {
        $this->add_before_filter($filter_function_name, $options, true);
    }

    /**
     *  Execute the after filters
     *  @uses $after_filters
     */
    function execute_after_filters() {

        #if(isset($this->after_filter)) {
            #$this->add_after_filter($this->after_filter);
        #}
        #error_log("after_filters:".print_r($this->after_filters, true));
        #error_log("after_filter_options:".print_r($this->after_filter_options, true));
        $return = true;
        if(count($this->after_filters) > 0) {
            $action = $this->action ? $this->action : "index";
            foreach($this->after_filters as $filter_function) {
                if(array_key_exists($filter_function, $this->after_filter_options)) {
                    if(is_array($options = $this->after_filter_options[$filter_function])) {
                        if(array_key_exists('except', $options)) {
                            if(preg_match("/\b$action\b/", $options['except'])) {
                                #error_log("after filter except match: action:{$action} == ".$options['except']);
                                continue;
                            }
                        }
                        if(array_key_exists('only', $options)) {
                            if(!preg_match("/\b$action\b/", $options['only'])) {
                                #error_log("after filter only non match: action:{$action} == ".$options['only']);
                                continue;
                            }
                        }
                    }
                }
                if(method_exists($this, $filter_function)) {
                    if(false === $this->$filter_function()) {
                        //error_log("execute_after_filters(): returning false");
                        $return = false;
                    }
                }
            }
        }
        return $return;
    }

    /**
     *  Append an after filter to the filter chain
     *
     *  @param mixed $filter_function_name  String with the name of
     *  one filter function, or array of strings with the names of
     *  several filter functions.
     *  @uses $after_filters
     */
    function add_after_filter($filter_function_name, $options = array(), $prepend = false) {
        //error_log("adding after filter: $filter_function_name");
        if(is_string($filter_function_name) && !empty($filter_function_name)) {
            if(!in_array($filter_function_name, $this->after_filters)) {
                if($prepend) {
                    array_unshift($this->after_filters, $filter_function_name);
                } else {
                    array_push($this->after_filters, $filter_function_name);
                }
                if(count($options)) {
                    if(count($this->after_filter_options[$filter_function_name])) {
                        $this->after_filter_options[$filter_function_name] = array_merge($this->after_filter_options[$filter_function_name], $options);
                    } else {
                        $this->after_filter_options[$filter_function_name] = $options;
                    }
                }
            }
        } elseif(is_array($filter_function_name)) {
            foreach($filter_function_name as $filter_name => $options) {
                if(!in_array($filter_name, $this->after_filters)) {
                    if($prepend) {
                        array_unshift($this->after_filters, $filter_name);
                    } else {
                        array_push($this->after_filters, $filter_name);
                    }
                    if(count($options)) {
                        if(count($this->after_filter_options[$filter_name])) {
                            $this->after_filter_options[$filter_name] = array_merge($this->after_filter_options[$filter_name], $options);
                        } else {
                            $this->after_filter_options[$filter_name] = $options;
                        }
                    }
                }
            }
        }
    }

    function prepend_after_filter($filter_function_name, $options = array()) {
        $this->add_after_filter($filter_function_name, $options, true);
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
        if(!in_array($helper_name, $this->helpers)) {
            $this->helpers[] = $helper_name;
        }
    }

    /**
     *
     * Renders the content that will be returned to the browser as the response body.
     *
     */
    function render($options = array(), $locals = array(), $return_as_string = false) {

        if($this->render_performed && !$this->action_called) {
            return true;
        }

        if($return_as_string) {
            # start to buffer output
            ob_start();
        }

        if(is_string($options)) {
            $this->render_file($options, true, $locals);
        } elseif(is_array($options)) {
            $options['locals'] = $options['locals'] ? $options['locals'] : array();
            $options['use_full_path'] = !$options['use_full_path'] ? true : $options['use_full_path'];

            if($options['text']) {
                $this->render_text($options['text']);
            } else {
                if($options['action']) {
                    $this->render_action($options['action'], $options);
                } elseif($options['file']) {
                    $this->render_file($options['file'], $options['use_full_path'], $options['locals']);
                } elseif($options['partial']) {
                    $this->render_partial($options['partial'], $options);
                } elseif($options['nothing']) {
                    # Safari doesn't pass the headers of the return if the response is zero length
                    $this->render_text(" ");
                }
            }
        }

        $this->render_performed = true;

        if($return_as_string) {
            $result = ob_get_contents();
            ob_end_clean();
            $this->render_performed = false;
            return $result;
        }
    }

    /**
     *
     * Rendering of text is usually used for tests or for rendering prepared content.
     * By default, text rendering is not done within the active layout.
     *
     *   # Renders the clear text "hello world"
     *   render(array("text" => "hello world!"))
     *
     *   # Renders the clear text "Explosion!"
     *   render(array("text" => "Explosion!"))
     *
     *   # Renders the clear text "Hi there!" within the current active layout (if one exists)
     *   render(array("text" => "Explosion!", "layout" => true))
     *
     *   # Renders the clear text "Hi there!" within the layout
     *   # placed in "app/views/layouts/special.phtml"
     *   render(array("text" => "Explosion!", "layout" => "special"))
     *
     */
    function render_text($text, $options = array()) {
        if($options['layout']) {
            $locals['content_for_layout'] = $text;
            $layout = $this->determine_layout();
            $this->render_file($layout, false, $locals);
        } else {
            echo $text;
        }
        exit;
    }

    /**
     *
     * Action rendering is the most common form and the type used automatically by
     * Action Controller when nothing else is specified. By default, actions are
     * rendered within the current layout (if one exists).
     *
     *   # Renders the template for the action "goal" within the current controller
     *   render(array("action" => "goal"))
     *
     *   # Renders the template for the action "short_goal" within the current controller,
     *   # but without the current active layout
     *   render(array("action" => "short_goal", "layout" => false))
     *
     *   # Renders the template for the action "long_goal" within the current controller,
     *   # but with a custom layout
     *   render(array("action" => "long_goal", "layout" => "spectacular"))
     *
     */
    function render_action($action, $options = array()) {
        if($this->render_performed) {
            return true;
        }
        if($options['layout']) {
            $this->layout = $options['layout'];
        }
        if($options['scaffold']) {
            $this->view_file = TRAX_LIB_ROOT."/templates/scaffolds/".$action.".phtml";
        } else {
            $this->view_file = $this->views_path . "/" . $action . "." . Trax::$views_extension;
        }
        //error_log(get_class($this)." - render_action() view_file: $this->view_file");
        return $this->render_file($this->view_file);
    }

    /**
     *
     * Renders according to the same rules as render, but returns the result in a string
     * instead of sending it as the response body to the browser.
     *
     */
    function render_to_string($options = array(), $locals = array()) {
        return $this->render($options, $locals, true);
    }

    /**
     *
     * File rendering works just like action rendering except that it takes a filesystem path.
     * By default, the path is assumed to be absolute, and the current layout is not applied.
     *
     *   # Renders the template located at the absolute filesystem path
     *   render(array("file" => "/path/to/some/template.phtml"))
     *   render(array("file" => "c:/path/to/some/template.phtml"))
     *
     *   # Renders a template within the current layout
     *   render(array("file" => "/path/to/some/template.rhtml", "layout" => true))
     *   render(array("file" => "c:/path/to/some/template.rhtml", "layout" => true))
     *
     *   # Renders a template relative to app/views
     *   render(array("file" => "some/template", "use_full_path" => true))
     */
    function render_file($path, $use_full_path = false, $locals = array()) {
        if($this->render_performed && !$this->action_called) {
            return true;
        }
        # Renders a template relative to app/views
        if($use_full_path) {
            $path = $this->views_path."/".$path.".".Trax::$views_extension;
        }
        # Pull all the class vars out and turn them from $this->var to $var
        if(is_object($this)) {
            $controller_locals = get_object_vars($this);
        }
        if(is_array($controller_locals)) {
            $locals = array_merge($controller_locals, $locals);
        }
        if(count($locals)) {
            foreach($locals as $tmp_key => $tmp_value) {
                ${$tmp_key} = $tmp_value;
            }
            unset($tmp_key);
            unset($tmp_value);
        }
        include($path);
        $this->render_performed = true;
        return true;
        #$this->render_performed = ViewHandlers::render($path, $locals);
        #return $this->render_performed;
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
     *      located at app/views/controller/_win.phtml</li>
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
     *    <li><samp>render_partial("win", array("collection" => $wins,
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
            $file_with_path = Trax::$views_path."/".$path."/_".$file.".".Trax::$views_extension;
        } else {
            $file = $path;
            $file_with_path = $this->views_path."/_".$file.".".Trax::$views_extension;
        }

        #if(file_exists($file_with_path)) {

            if(array_key_exists("spacer_template", $options)) {
                $spacer_path = $options['spacer_template'];
                if(strstr($spacer_path, "/")) {
                    $spacer_file = substr(strrchr($spacer_path, "/"), 1);
                    $spacer_path = substr($spacer_path, 0, strripos($spacer_path, "/"));
                    $spacer_file_with_file = Trax::$views_path."/".$spacer_path."/_".$spacer_file.".".Trax::$views_extension;
                } else {
                    $spacer_file = $spacer_path;
                    $spacer_file_with_file = $this->views_path."/_".$spacer_file.".".Trax::$views_extension;
                }
                if(file_exists($spacer_file_with_file)) {
                    $add_spacer = true;
                }
            }

            $locals = array_key_exists("locals", $options) ? $options['locals'] : array();
            if(array_key_exists("collection", $options) && is_array($options['collection'])) {
                foreach($options['collection'] as $tmp_value) {
                    ${$file."_counter"}++;
                    $locals[$file] = $tmp_value;
                    $locals[$file."_counter"] = ${$file."_counter"};
                    unset($tmp_value);
                    $this->render_performed = false;
                    $this->render_file($file_with_path, false, $locals);
                    if($add_spacer && (${$file."_counter"} < count($options['collection']))) {
                        $this->render_performed = false;
                        $this->render_file($spacer_file_with_file, false, $locals);
                    }
                }
                $this->render_performed = true;
            } else {
                $this->render_file($file_with_path, false, $locals);
            }
        #}
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
    function determine_layout($full_path = true) {

        //  If the controller defines $layout and sets it
        //  to NULL, that indicates it doesn't want a layout
        if(isset($this->layout) && is_null($this->layout)) {
            //error_log('controller->layout absent');
            return null;
        }
        # $layout will be the layout defined in the current controller
        # or try to use the controller name for the layout
        $layout = (isset($this->layout)
                   && $this->layout != '')
            ? $this->layout : $this->controller;

        # Check if a method has been defined to determine the layout at runtime
        if(method_exists($this, $layout)) {
            $layout = $this->$layout();
        }

        # Default settings
        $layouts_base_path = Trax::$layouts_path;

        if(!$full_path && $layout) {
            return $layout;
        } elseif($layout) {
            # Is this layout for from a different controller
            if(strstr($layout, "/")) {
                $file = substr(strrchr($layout, "/"), 1);
                $path = substr($layout, 0, strripos($layout, "/"));
                $layout = $layouts_base_path."/".$path."/".$file.".".Trax::$views_extension;
            } elseif($this->plugin && file_exists(Trax::$plugins_path."/{$this->plugin}/views/layouts/{$layout}.".Trax::$views_extension)) {
                $layout = Trax::$plugins_path."/{$this->plugin}/views/layouts/{$layout}.".Trax::$views_extension;
            } elseif(file_exists($this->layouts_path."/".$layout.".".Trax::$views_extension)) {
                # Is there a layout for the current controller
                $layout = $this->layouts_path."/".$layout.".".Trax::$views_extension;
            } else {
                $layout = $layouts_base_path."/".$layout.".".Trax::$views_extension;
            }
            if(file_exists($layout)) {
                $layout_file = $layout;
            }
        }

        # No defined layout found so just use the default layout
        # app/views/layouts/application.phtml
        if(!isset($layout_file)) {
            if($this->plugin && file_exists(Trax::$plugins_path."/{$this->plugin}/views/layouts/application.".Trax::$views_extension)) {
                $layout_file = Trax::$plugins_path."/{$this->plugin}/views/layouts/application.".Trax::$views_extension;
            } else {
                $layout_file = $layouts_base_path . "/application." . Trax::$views_extension;
            }
        }
        $this->layout_file = $layout_file;
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
    function redirect_to($options = null, $post = false) {
        if($options == "back") {
            $url = $_SERVER["HTTP_REFERER"];
        } else {
            $url = url_for($options);
        }

        if($post) {
            echo "<form id=\"trax_redirect\" method=\"post\" action=\"{$url}\"></form><script>document.getElementById('trax_redirect').submit();</script>";
        } elseif(headers_sent()) {
            echo "<html><head><META HTTP-EQUIV=\"REFRESH\" CONTENT=\"0; URL=".$url."\"></head></html>";
        } else {
            header("Location: ".$url);
        }

        exit;
    }

    /**
     *  Sets content passed in or echoed after calling this function and sets it
     *  to a variable named $content_for_"$name" to be used in the layout.
     *  Example:
     *  View:
     *  <? $this->content_for("navigation") ?>
     *      <li><?= link_to('Login', array(":action" => 'login')) ?></li>
     *  <? $this->end_content_for() ?>
     *  Layout:
     *  <?= $content_for_navigation ?>
     *
     *  @param string $name variable name to be set for use in layout
     *  @param string $content (optional) content for the variable to be set
     */
    function content_for($name, $content = null) {
        array_push($this->content_for_open_blocks, $name);
        ob_start();
        if($content) {
            echo $content;
            $this->end_content_for();
        }
    }

    /**
     *  Ends an open block call by content_for() and sets the content of the buffer
     *  to the variable $content_for_"name"
     */
    function end_content_for() {
        if(count($this->content_for_open_blocks)) {
            if($name = array_pop($this->content_for_open_blocks)) {
                $content = ob_get_contents();
                ob_end_clean();
                $this->{"content_for_".$name} = $content;
            } else {
                ob_end_clean();
            }
        }
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
        header("HTTP/1.0 {$error_code} {$error_heading}");
        # check for user's layout for errors
        if(TRAX_ENV == "development" || Trax::$show_trax_errors) {
            if(file_exists(Trax::$layouts_path."/trax_error.".Trax::$views_extension)) {
                include(Trax::$layouts_path."/trax_error.".Trax::$views_extension);
            } elseif(file_exists(TRAX_LIB_ROOT."/templates/error.phtml")) {
                # use default layout for errors
                include(TRAX_LIB_ROOT."/templates/error.phtml");
            } else {
                echo "<font face=\"verdana, arial, helvetica, sans-serif\">\n";
                echo "<h1>$error_heading</h1>\n";
                echo "<p>$error_message</p>\n";
                if($trace) {
                    echo "<pre style=\"background-color: #eee;padding:10px;font-size: 11px;\">";
                    echo "<code>$trace</code></pre>\n";
                }
                echo "</font>\n";
            }
        } else {
            if(file_exists(Trax::$public_path."/{$error_code}.html")) {
                include(Trax::$public_path."/{$error_code}.html");
            } elseif($error_code == "404") {
                echo "<h2>404 Error - File not found.</h2>";
            } else {
                echo "<font face=\"verdana, arial, helvetica, sans-serif\">\n";
                echo "<h2>Application Error</h2>Trax application failed to start properly";
                echo "</font>\n";
            }
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