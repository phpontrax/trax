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
     */
    function convert_confirm_option_to_javascript($html_options) {
        if($html_options['confirm']) {
            $html_options['onclick'] = "return confirm('".addslashes($html_options['confirm'])."');";
            unset($html_options['confirm']);
        }
        return $html_options;
    }

    /**
     *  @todo Document this method
     */
    function convert_boolean_attributes(&$html_options, $bool_attrs) {
        foreach($bool_attrs as $x) {
            if(in_array($x, $html_options)) {
                $html_options[$x] = $x;
                unset($html_options[$x]);
            }
        }
        return $html_options;
    }

    /**
     *  @todo Document this method
     */
    function button_to($name, $options = array(), $html_options = null) {
        $html_options = (!is_null($html_options) ? $html_options : array());
        $this->convert_boolean_attributes($html_options, array($disabled));
        $this->convert_confirm_option_to_javascript($html_options);
        if (is_string($options)) {
            $url = $options;
            $name = (!is_null($name) ? $name : $options);
        } else {
            $url = url_for($options);
            $name = (!is_null($name) ? $name : $this->url_for($options));
        }

        $html_options = array_merge($html_options, array("type" => "submit", "value" => $name));
        return "<form method=\"post\" action=\"" .  htmlspecialchars($url) . "\" class=\"button-to\"><div>" .
            $this->tag("input", $html_options) . "</div></form>";
    }

    /**
     * This tag is deprecated. Combine the link_to and AssetTagHelper::image_tag yourself instead, like:
     *   link_to(image_tag("rss", :size => "30x45", :border => 0), "http://www.example.com")
     */
    function link_image_to($src, $options = array(), $html_options = array(), $parameters_for_method_reference = array()) {
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

        return $this->link_to($this->tag("img", $image_options), $options, $html_options, $parameters_for_method_reference);
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

?>
