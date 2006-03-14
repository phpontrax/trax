<?php
/**
 *  File containing the Router class
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
 *  Convert a URL to an action
 *  @tutorial PHPonTrax/Router.cls
 */
class Router {

    /**
     *  Route table
     *
     *  For a description of the structure, see
     *  {@tutorial PHPonTrax/Router.cls#table the Router tutorial}.
     *  Routes are added by calling {@link connect()} and looked up
     *  by calling {@link find_route()}.
     *  <b>FIXME:</b> Should we have a Route class to describe an
     *  entry in the route table?
     *  @var string[][]
     */
    private $routes = array();

    /**
     *  Last route found by a call to find_route()
     *  @var string[]
     */
    private $selected_route = null;

    /**
     *  Default route path
     *
     *  This route path is added to the route table if the table is
     *  empty when find_route() is called.
     *  @var string constant
     */
    private $default_route_path = ":controller/:action/:id";

    /**
     *  Count of the number of elements in $routes
     *  @var integer
     */
    public $routes_count = 0;

    /**
     *  Accessor method to return contents of $selected_route
     *  @return string[] Contents of $selected_route
     *  @uses $selected_route
     */
    function get_selected_route() {
        return $this->selected_route;
    }

    /**
     *  Accessor method to add a route to the route table
     *
     *  The route is added to the end of
     *  {@link $routes the route table}. If $params is not an array,
     *  NULL is stored in the route parameter area.
     *  @param string $path
     *  @param mixed[] $params
     *  @uses $routes
     *  @uses $routes_count
     */
    function connect($path, $params = null) {
        if(!is_array($params)) $params = null;
        $this->routes[$this->routes_count]['path'] = $path;
        $this->routes[$this->routes_count]['params'] = $params;
        $this->routes_count = count($this->routes);
    }

    /**
     *  Find first route in route table with path that matches argument
     *
     *  First, assure that the route table {@link $routes} has at
     *  least one route by adding
     *  {@link $default_route_path the default route} if the table is
     *  empty.  Then search the table to find the first route in the
     *  table whose path matches the argument $url. If $url is an
     *  empty string, it matches a path that is an empty string.
     *  Otherwise, try to match $url to the path part of the table
     *  entry according to
     *  {@link http://www.php.net/manual/en/ref.pcre.php Perl regular expression}
     *  rules.  If a matching route is found, return it any to the caller, and
     *  also save a copy in {@link $selected_route}; if no matching
     *  route is found return null.
     *  @param string $url
     *  @uses build_route_regexp()
     *  @uses $default_route_path
     *  @uses $routes
     *  @uses $routes_count
     *  @uses $selected_route
     *  @return mixed Matching route or null.  Path is in return['path'],
     *                   params in return['params'],
     */
    function find_route($url) {
        //error_log('url='.$url);
        // ensure at least one route (the default route) exists
        if($this->routes_count == 0) {
            $this->connect($this->default_route_path);
        }

        $this->selected_route = null;

        foreach($this->routes as $route) {
            unset($route_regexp);
            unset($reg_exp);
            $route_regexp = $this->build_route_regexp($route['path']);
            //error_log("route regexp=/$route_regexp/");
            if($url == "" && $route_regexp == "") {
                //error_log('selected');
                $this->selected_route = $route;
                break;
            } elseif(preg_match("/$route_regexp/",$url) && $route_regexp != "") {
                //error_log('selected');
                $this->selected_route = $route;
                break;
            } elseif($route['path'] == $this->default_route_path) {
                //error_log('defaulted');
                $this->selected_route = $route;
                break;
            }
        }
        //error_log('selected route='.var_export($this->selected_route,true));
        return $this->selected_route;
    }                                 // function find_route($url)

    /**
     *  Build a regular expression that matches a route
     *
     *  @todo <b>FIXME:</b> Should this method be private?
     *  @todo <b>FIXME:</b> Shouldn't the regexp match be the same as
     *  for a PHP variable name? '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*'
     *  @param string $route_path  A route path.
     *  @return string Regular expression that matches the route in
     *                $route_path 
     */
    function build_route_regexp($route_path) {
        //        echo "entering build_route_regexp(), \$route_path is '$route_path'\n";

        $route_regexp = null;

        if(!is_array($route_path)) {
            $route_path = explode("/",$route_path);
        }
        //error_log("route path:\n".var_export($route_path,true));
        if(count($route_path) > 0) {
            foreach($route_path as $path_element) {
                if(preg_match('/:[a-z0-9_\-]+/',$path_element)) {
                    $reg_exp[] = '[a-z0-9_\-]+';
                } else {
                    $reg_exp[] = $path_element;
                }
            }
            if(is_array($reg_exp)) {
                $route_regexp = "^".implode("\/",$reg_exp)."$";
            }
        }
        return $route_regexp;
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