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
 *  Basic helper functions
 *
 *  A collection of methods used to generate basic HTML/XML.
 */
class Helpers {

    /**
     *  @todo Document this variable
     *  @var boolean
     */
    public $auto_index;

    /**
     *  @todo Document this variable
     *  Name of a PHP class(?)
     *  @var string
     */
    public $object_name;

    /**
     *  @todo Document this variable
     */
    public $attribute_name;

    /**
     *  Current controller object
     *
     *  Local copy of Trax::$current_controller_object<br />
     *  <b>NB:</b> {@link object()} faults if this does not contain a
     *  valid instance of ActionController.
     *  @var ActionController
     */
    public $controller_object;

    /**
     *  Current controller name
     *
     *  Local copy of Trax::$current_controller_name
     *  @var string
     */
    public $controller_name;

    /**
     *  Current controller path
     *
     *  Local copy of Trax::$current_controller_path
     *  @var string
     */
    public $controller_path;


    /**
     *  Construct a Helpers object
     *
     *  @param string Name of ActiveRecord subclass
     *  @param string Attribute of ActiveRecord subclass
     *  @uses auto_index
     *  @uses object_name
     *  @uses attribute_name
     *  @uses controller_name
     *  @uses controller_path
     *  @uses controller_object
     */
    function __construct($object_name = null, $attribute_name = null) {
    	if(substr($object_name, -2) == "[]") {
            $auto_index = true;
    	} else {
            $auto_index = false;
        }
    	$this->auto_index = false;
        $this->object_name = str_replace("[]", "", $object_name);     
        $this->attribute_name = $attribute_name;        

        //  Copy controller information from $GLOBALS
        $this->controller_name =
            !is_null(Trax::$current_controller_name)           
            ? Trax::$current_controller_name : null;
        $this->controller_path =
            !is_null(Trax::$current_controller_path)
            ? Trax::$current_controller_path : null;
        $this->controller_object =
            (!is_null(Trax::$current_controller_object) 
            && is_object(Trax::$current_controller_object))
            ? Trax::$current_controller_object : null;
    	if($auto_index) {
        	$object = $this->object();
            if(is_object($object)) {
                $index = $object->index_on; # should be primary key (usually id field)
                $this->auto_index = $object->$index;  	
           	}  
        }         
    }

    /**
     *  Get value of current attribute in the current ActiveRecord object
     *
     *  If there is a value in $_REQUEST[][], return it.
     *  Otherwise fetch the value from the database.
     *  @uses attribute_name
     *  @uses object()
     *  @uses object_name
     *  @uses ActiveRecord::send()
     */
    protected function value() {
        if (array_key_exists($this->object_name, $_REQUEST)
            && array_key_exists($this->attribute_name,
                                 $_REQUEST[$this->object_name])) {
            $value = $_REQUEST[$this->object_name][$this->attribute_name];
        } else {

            //  Attribute value not found in $_REQUEST.  Find the
            //  ActiveRecord subclass instance and query it.
            $object = $this->object();
            if(is_object($object) && $this->attribute_name) {
                //$value = $object->send($this->attribute_name);
                $value = $object->{$this->attribute_name};
            }
        }
        return $value;
    }

    /**
     *  Given the name of an ActiveRecord subclass, find an instance
     *
     *  Finds the AR instance from the ActionController instance.
     *  Assumes that if a $object_name is defined either as the
     *  argument or an instance variable, then there must be
     *  a controller object instance which points to a single instance
     *  of the ActiveRecord.
     *  <b>FIXME:</b> Handle errors better.
     *  @param string Name of an ActiveRecord subclass or null
     *  @return mixed Instance of the subclass, or null if
     *                object not available.
     *  @uses controller_object
     *  @uses object_name
     */
    protected function object($object_name = null) {
        $object_name = $object_name ? $object_name : $this->object_name;
        if($object_name
           && isset($this->controller_object)
           && isset($this->controller_object->$object_name)) {
            return $this->controller_object->$object_name;
        }
        return null;
    }   
    
    /**
     *  Convert array of tag attribute names and values to string
     *
     *  @param string[] $options 
     *  @return string
     */
    protected function tag_options($options) {
        if(count($options)) {
            $html = array();
            foreach($options as $key => $value) {
                $html[] = "$key=\"".@htmlspecialchars($value, ENT_COMPAT)."\"";
            }
            sort($html);
            $html = implode(" ", $html);
            return $html;
        } else {
            return '';
        }
    }

    /**
     *  Convert selected attributes to proper XML boolean form
     *
     *  @uses boolean_attribute()
     *  @param string[] $options
     *  @return string[] Input argument with selected attributes converted
     *                   to proper XML boolean form
     */
    protected function convert_options($options = array()) {
        foreach(array('disabled', 'readonly', 'multiple') as $a) {
            $this->boolean_attribute($options, $a);
        }
        return $options;
    }

    /**
     *  Convert an attribute to proper XML boolean form
     *
     *  @param string[] $options
     *  @param string $attribute
     *  @return void Contents of $options have been converted
     */
    protected function boolean_attribute(&$options, $attribute) {
        if(array_key_exists($attribute,$options)
           && $options[$attribute]) {
            $options[$attribute] = $attribute;
        } else {
            unset($options[$attribute]);
        }
    }
    
    /**
     *  Wrap CDATA begin and end tags around argument
     *
     *  Returns a CDATA section for the given content.  CDATA sections
     *  are used to escape blocks of text containing characters which would
     *  otherwise be recognized as markup. CDATA sections begin with the string
     *  <samp><![CDATA[</samp> and end with (and may not contain) the string 
     *  <samp>]]></samp>. 
     *  @param string $content  Content to wrap
     *  @return string          Wrapped argument
     */
    function cdata_section($content) {
        return "<![CDATA[".$content."]]>";
    }    

    /**
     *  Generate an HTML or XML tag with optional attributes and self-ending
     *
     *  <ul>
     *   <li>Example: <samp>tag("br");</samp><br>
     *       Returns: <samp><br  />\n</samp></li>
     *   <li> Example: <samp>tag("div", array("class" => "warning"), true);</samp><br>
     *       Returns: <samp><div class="warning">\n</samp></li>
     *  </ul>
     *  @param string $name      Tag name
     *  @param string[] $options Tag attributes to apply, specified as
     *                  array('attr1' => 'value1'[, 'attr2' => 'value2']...) 
     *  @param boolean $open
     *  <ul>
     *    <li>true =>  make opening tag (end with '>')</li>
     *    <li>false => make self-terminating tag (end with ' \>')</li>
     *  </ul>
     *  @return string The generated tag, followed by "\n"
     *  @uses tag_options()
     */
    function tag($name, $options = array(), $open = false) {
        $html = "<$name ";
        $html .= $this->tag_options($options);
        $html .= $open ? ">" : " />";
        return $html."\n";
    }

    /**
     *  Generate an open/close pair of tags with optional attributes and content between
     *
     *  <ul>
     *   <li>Example: <samp>content_tag("p", "Hello world!");</samp><br />
     *       Returns: <samp><p>Hello world!</p>\n</samp><li>
     *   <li>Example:
     *     <samp>content_tag("div",
     *                       content_tag("p", "Hello world!"),
     *                       array("class" => "strong"));</samp><br />
     *     Returns:
     *     <samp><div class="strong"><p>Hello world!</p></div>\n</samp></li>
     *  </ul>
     *  @uses tag_options()
     *  @param string $name    Tag to wrap around $content
     *  @param string $content Text to put between tags
     *  @param string[] $options Tag attributes to apply, specified as
     *                  array('attr1' => 'value1'[, 'attr2' => 'value2']...) 
     *  @return string Text wrapped with tag and attributes,
     *                 followed by "\n"
     */
    function content_tag($name, $content, $options = array()) {
        $html = "<$name ";
        $html .= $this->tag_options($options);
        if(isset($options['strip_slashes'])) {
            $content = stripslashes($content);    
        }
        $html .= ">$content</$name>";
        return $html."\n";
    }
    
    /**
     *
     *  @uses content_tag()
     *  @uses value()
     */    
    function to_content_tag($tag_name, $options = array()) {
        return $this->content_tag($tag_name, $this->value(), $options);
    } 
    
    /**
     *  If this tag has an error, wrap it with a visual indicator
     *
     *  @param string HTML to be wrapped
     *  @param boolean  true=>error, false=>no error
     *  @return string
     */
    function error_wrapping($html_tag, $has_error) {
        return ($has_error ? '<span class="fieldWithErrors">' . eregi_replace("[\n\r]", '', $html_tag) . '</span>' : $html_tag);
    }         

}

/**
 *  Create a Helpers object and call its content_tag() method
 *
 *  @see Helpers::content_tag()
 *  @param string $name    Tag to wrap around $content
 *  @param string $content Text to put between tags
 *  @param string[] $options Tag attributes to apply
 *  @return string Text wrapped with tag and attributes,
 *                 followed by "\n"
 */
function content_tag() {
    $helper = new Helpers();
    $args = func_get_args();
    return call_user_func_array(array($helper, 'content_tag'), $args);
}

/**
 *  Create a Helpers object and call its tag() method
 *
 *  @see Helpers::tag()
 *  @param string $name    Tag name
 *  @param string[] $options Tag attributes to apply
 *  @param boolean $open
 *  <ul>
 *    <li>true =>  make opening tag (end with '>')</li>
 *    <li>false => make self-terminating tag (end with ' \>')</li>
 *  </ul>
 *  @return string The tag, followed by "\n"
 */
function tag() {
    $helper = new Helpers();
    $args = func_get_args();
    return call_user_func_array(array($helper, 'tag'), $args);
}

/**
 *  Create a Helpers object and call its cdata_section() method
 */
function cdata_section() {
    $helper = new Helpers();
    $args = func_get_args();
    return call_user_func_array(array($helper, 'cdata_section'), $args);
}

// -- set Emacs parameters --
// Local variables:
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
