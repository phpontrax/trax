<?php
/**
 *  File containing the Helpers class and associated functions
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
 *
 *  @package PHPonTrax
 */
class Helpers {

    /**
     *
     */
    function __construct() {
        $this->controller_name = $GLOBALS['current_controller_name'];
        $this->controller_path = $GLOBALS['current_controller_path'];
        $this->controller_object = $GLOBALS['current_controller_object'];
    }

    /**
     *
     */
    function tag_options($options) {
        if(count($options)) {
            $html = array();
            foreach($options as $key => $value) {
                $html[] = "$key=\"".@htmlspecialchars($value, ENT_QUOTES)."\"";
            }
            sort($html);
            $html = implode(" ", $html);
        }
        return $html;
    }

    /**
     *  Generate an HTML or XML tag with optional attributes
     *
     *  Example: tag("br");
     *   Results: <br />
     *  Example: tag("input", array("type" => "text"));
     * <input type="text" />
     */
    function tag($name, $options = array(), $open = false) {
        $html = "<$name ";
        $html .= $this->tag_options($options);
        $html .= $open ? ">" : " />";
        return $html;
    }

    /**
     *  Generate an open/close pair of tags with content between
     *
     *  Example: content_tag("p", "Hello world!");
     *  Result: <p>Hello world!</p>
     *  Example: content_tag("div", content_tag("p", "Hello world!"), array("class" => "strong")) =>
     *  Result:<div class="strong"><p>Hello world!</p></div>
     */
    function content_tag($name, $content, $options = array()) {
        $html .= "<$name ";
        $html .= $this->tag_options($options);
        $html .= ">$content</$name>";
        return $html;
    }

    /**
     *  Return the URL for the set of $options provided.
     */
    function url_for($options = array()) {
        $url_base = null;
        $url = array();
        if(is_string($options)) {
            //$url[] = $options;
            return $options;
        } else {
            $url_base = $_SERVER['HTTP_HOST'];
            if(substr($url_base, -1) == "/") {
                # remove the ending slash
                $url_base = substr($url_base, 0, -1);                             
            }           
            
            if($_SERVER['SERVER_PORT'] == 443) {
                $url_base = "https://".$url_base;
            } else {
                $url_base = "http://".$url_base;
            }
            if(!is_null(TRAX_URL_PREFIX)) {
                $url_base .= "/".TRAX_URL_PREFIX;
            }
            
            if(array_key_exists(":controller", $options)) {
                if($controller = $options[":controller"]) {
                    $url[] = $controller; 
                }
            } else {
                $controller = $this->controller_path;
                if(substr($controller, 0, 1) == "/") {
                    # remove the beginning slash
                    $controller = substr($controller, 1);        
                }
                $url[] = $controller;
            }
            
            if(count($url)) {
                if(array_key_exists(":action", $options)) {
                    if($action = $options[":action"]) {
                        $url[] = $action;
                    }
                } 
            }
            if(count($url) > 1) {
                if(array_key_exists(":id", $options)) {
                    if(is_object($options[":id"])) {
                        if($id = $options[":id"]->id) {
                            $url[] = $id;
                        }
                    } else {
                        if($id = $options[":id"]) {
                            $url[] = $id;
                        }
                    }
                }
            }
        }
        
        if(count($url) && substr($url_base,-1) != "/") {
            $url_base .= "/";    
        } 

        return $url_base . implode("/", $url);
    }

}

/**
 *  Avialble functions for use in views
 */
function content_tag() {
    $helper = new Helpers();
    $args = func_get_args();
    return call_user_func_array(array($helper, 'content_tag'), $args);
}

/**
 *
 */
function url_for($options = array()) {
    $helper = new Helpers();
    return $helper->url_for($options);
}

?>
