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

class Router {

    private $routes = array();
    private $selected_route = null;
    private $default_route_path = ":controller/:action/:id";
    public $routes_count = 0;

    function get_selected_route() {
        return $this->selected_route;
    }

    function connect($path, $params = null) {
        if(!is_array($params)) $params = null;
        $this->routes[$this->routes_count]['path'] = $path;
        $this->routes[$this->routes_count]['params'] = $params;
        $this->routes_count = count($this->routes);
    }

    function find_route($url) {

        // ensure at least one route (the default route) exists
        if($this->routes_count == 0) {
            $this->routes['path'] = $this->default_route_path;
            $this->routes['params'] = null;
        }

        $this->selected_route = null;

        foreach($this->routes as $route) {
            unset($route_regexp);
            unset($reg_exp);
            $route_regexp = $this->build_route_regexp($route['path']);

            if($url == "" && $route_regexp == "") {
                $this->selected_route = $route;
                break;
            } elseif(preg_match("/$route_regexp/",$url) && $route_regexp != "") {
                $this->selected_route = $route;
                break;
            } elseif($route['path'] == $this->default_route_path) {
                $this->selected_route = $route;
                break;
            }
        }

        return $this->selected_route;
    }

    function build_route_regexp($route_path) {

        $route_regexp = null;

        if(!is_array($route_path)) {
            $route_path = explode("/",$route_path);
        }

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

?>