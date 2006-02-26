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
    protected function tag_options($options) {
        if(count($options)) {
            $html = array();
            foreach($options as $key => $value) {
                $html[] = "$key=\"".@htmlspecialchars($value, ENT_COMPAT)."\"";
            }
            sort($html);
            $html = implode(" ", $html);
        }
        return $html;
    }

    /**
     *
     */
    protected function convert_options($options = array()) {
        foreach(array('disabled', 'readonly', 'multiple') as $a) {
            $this->boolean_attribute(&$options, $a);
        }
        return $options;
    }

    /**
     *
     */
    protected function boolean_attribute(&$options, $attribute) {
        if($options[$attribute]) {
            $options[$attribute] = $attribute;
        } else {
            unset($options[$attribute]);
        }
    }
    
    /**
     * 
     * Returns a CDATA section for the given +content+.  CDATA sections
     * are used to escape blocks of text containing characters which would
     * otherwise be recognized as markup. CDATA sections begin with the string
     * <tt>&lt;![CDATA[</tt> and end with (and may not contain) the string 
     * <tt>]]></tt>. 
     */
    function cdata_section($content) {
        return "<![CDATA[".$content."]]>";
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
        return $html."\n";
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
        return $html."\n";
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

function tag() {
    $helper = new Helpers();
    $args = func_get_args();
    return call_user_func_array(array($helper, 'tag'), $args);
}

function cdata_section() {
    $helper = new Helpers();
    $args = func_get_args();
    return call_user_func_array(array($helper, 'cdata_section'), $args);
}

?>