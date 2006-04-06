<?php
/**
 *  File containing the UrlHelper class and support functions
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
 *  @todo Document this class
 *  @package PHPonTrax
 */
class UrlHelper extends Helpers {

    /**
     * Creates a link tag of the given +name+ using an URL created by the set of +options+.
     * It's also possible to pass a string instead of an options hash to
     * get a link tag that just points without consideration. If null is passed as a name, the link itself will become the name.
     * The $html_options have a special feature for creating javascript confirm alerts where if you pass ":confirm" => 'Are you sure?',
     * the link will be guarded with a JS popup asking that question. If the user accepts, the link is processed, otherwise not.
     *
     * Example:
     *   link_to("Delete this page", array(":action" => "delete", ":id" => $page->id), array(":confirm" => "Are you sure?"))
     */
    function link_to($name, $options = array(), $html_options = array()) {
        $html_options = $this->convert_confirm_option_to_javascript($html_options);
        if(is_string($options)) {
            $href = array("href" => $options);
            if(count($html_options) > 0) {
                $html_options = array_merge($html_options, $href);
            } else {
                $html_options = $href;
            }
            if(!$name) {
                $name = $options;
            }
            $html = $this->content_tag("a", $name, $html_options);
        } else {
            $url = $this->url_for($options);
            if(!$name) {
                $name = $url;
            }
            $href = array("href" => $url);
            if(count($html_options) > 0) {
                $html_options = array_merge($html_options, $href);
            } else {
                $html_options = $href;
            }
            $html = $this->content_tag("a", $name, $html_options);
        }
        return $html;
    }

    /**
     *  @todo Document this method
     *  @param string[] Options
     *  @return string
     */
    function convert_confirm_option_to_javascript($html_options) {
        if(array_key_exists('confirm', $html_options)) {
            $html_options['onclick'] =
                "return confirm('".addslashes($html_options['confirm'])."');";
            unset($html_options['confirm']);
        }
        return $html_options;
    }

    /**
     *  @todo Document this method
     */
    function convert_boolean_attributes(&$html_options, $bool_attrs) {
        foreach($bool_attrs as $x) {
            if(@array_key_exists($x, $html_options)) {
                $html_options[$x] = $x;
            }
        }
        return $html_options;
    }

    /**
     *  @todo Document this method
     */
    function button_to($name, $options = array(), $html_options = null) {
        $html_options = (!is_null($html_options) ? $html_options : array());
        $this->convert_boolean_attributes($html_options, array('disabled'));
        $this->convert_confirm_option_to_javascript($html_options);
        if (is_string($options)) {
            $url = $options;
            $name = (!is_null($name) ? $name : $options);
        } else {
            $url = url_for($options);
            $name = (!is_null($name) ? $name : url_for($options));
        }

        $html_options = array_merge($html_options, array("type" => "submit", "value" => $name));
        return "<form method=\"post\" action=\"" .  htmlspecialchars($url) . "\" class=\"button-to\"><div>" .
            $this->tag("input", $html_options) . "</div></form>";
    }

    /**
     * This tag is deprecated. Combine the link_to and AssetTagHelper::image_tag yourself instead, like:
     *   link_to(image_tag("rss", array("size" => "30x45"), array("border" => 0)), "http://www.example.com")
     */
    function link_image_to($src, $options = array(), $html_options = array()) {
        $image_options = array("src" => (ereg("/", $src) ? $src : "/images/$src"));
        if (!ereg(".", $image_options["src"])) $image_options["src"] .= ".png";

        if (isset($html_options["alt"])) {
            $image_options["alt"] = $html_options["alt"];
            unset($html_options["alt"]);
        } else {
            $image_options["alt"] = ucfirst(end(explode("/", $src)));
        }

        if (isset($html_options["size"])) {
            $image_options["width"]  = current(explode("x", $html_options["size"]));
            $image_options["height"] = end(explode("x", $html_options["size"]));
            unset($html_options["size"]);
        }

        if (isset($html_options["border"])) {
            $image_options["border"] = $html_options["border"];
            unset($html_options["border"]);
        }

        if (isset($html_options["align"])) {
            $image_options["align"] = $html_options["align"];
            unset($html_options["align"]);
        }

        return $this->link_to($this->tag("img", $image_options), $options, $html_options);
    }

    /**
     *  Return the URL for the set of $options provided.
     */
    function url_for($options = array()) {
        $url_base = null;
        $url = array();
        $extra_params = array();
        if(is_string($options)) {
            //$url[] = $options;
            return $options;
        } elseif(is_array($options)) {
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
            
            #if(count($options)) {
            #    foreach($options as $key => $value) {
            #        if(!strstr($key, ":")) {
            #            $extra_params[$key] = $value; 
            #        }       
            #    }    
            #}
        }
        
        if(count($url) && substr($url_base,-1) != "/") {
            $url_base .= "/";    
        } 
        return $url_base . implode("/", $url) . (count($extra_params) ? "?".http_build_query($extra_params) : null);
    }    

}

/**
 *  Avialble functions for use in views
 *  @todo Document this function
 */
function link_to($name, $options = array(), $html_options = array()) {
    $url_helper = new UrlHelper();
    return $url_helper->link_to($name, $options, $html_options);
}

function link_image_to($src, $options = array(), $html_options = array()) {
    $url_helper = new UrlHelper();
    return $url_helper->link_image_to($src, $options, $html_options);        
}

function button_to($name, $options = array(), $html_options = null) {
    $url_helper = new UrlHelper();
    return $url_helper->button_to($name, $options, $html_options);    
}

function url_for($options = array()) {
    $url_helper = new UrlHelper();
    return $url_helper->url_for($options);
}

// -- set Emacs parameters --
// Local variables:
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
