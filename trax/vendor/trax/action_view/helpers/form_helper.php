<?php
/**
 *  File containing the FormHelper class
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
 */
class FormHelper extends Helpers {

    /**
     *  Default attributes for input fields
     *  @var string[]
     */
    private $default_field_options = array();

    /**
     *  Default attributes for radio buttons
     *  @var string[]
     */
    private $default_radio_options = array();

    /**
     *  Default attributes for text areas
     *  @var string[]
     */
    private $default_text_area_options = array();

    /**
     *  Default attributes for dates
     *  @var string[]
     */
    private $default_date_options = array();

    /**
     *  @todo Document this method
     *  @uses default_date_options
     *  @uses default_field_options
     *  @uses default_radio_options
     *  @uses default_text_area_options
     */
    function __construct($object_name, $attribute_name) {
        parent::__construct($object_name, $attribute_name);

        //  Set default attributes for input fields
        $this->default_field_options = 
            array_key_exists('DEFAULT_FIELD_OPTIONS',$GLOBALS)
            ? $GLOBALS['DEFAULT_FIELD_OPTIONS']
            : array("size" => 30);

        //  Set default attributes for radio buttons
        $this->default_radio_options =
            array_key_exists('DEFAULT_RADIO_OPTIONS',$GLOBALS)
            ? $GLOBALS['DEFAULT_RADIO_OPTIONS']
            : array();

        //  Set default attributes for text areas
        $this->default_text_area_options =
            array_key_exists('DEFAULT_TEXT_AREA_OPTIONS',$GLOBALS)
            ? $GLOBALS['DEFAULT_TEXT_AREA_OPTIONS']
            : array("cols" => 40, "rows" => 20);

        //  Set default attributes for dates
        $this->default_date_options =
            array_key_exists('DEFAULT_Date_OPTIONS',$GLOBALS)
            ? $GLOBALS['DEFAULT_DATE_OPTIONS']
            : array(":discard_type" => true);
    }

    /**
     *  @todo Document this method
     */
    function tag_name() {
        return "{$this->object_name}[{$this->attribute_name}]";
    }

    /**
     *  @todo Document this method
     */
    function tag_name_with_index($index) {
        return "{$this->object_name}[{$index}][{$this->attribute_name}]";
    }

    /**
     *  @todo Document this method
     */
    function tag_id() {
        return "{$this->object_name}_{$this->attribute_name}";
    }

    /**
     *  @todo Document this method
     */
    function tag_id_with_index($index) {
        return "{$this->object_name}_{$index}_{$this->attribute_name}";
    }

    /**
     *  @todo Document this method
     *  @param string[]
     *  @uses auto_index
     *  @uses tag_id
     *  @uses tag_name
     *  @uses tag_id_with_index()
     *  @uses tag_name_with_index()
     */
    function add_default_name_and_id($options) {  
        $name_option_exists = array_key_exists('name', $options);	
       	if(array_key_exists("index", $options)) {
            $options['name'] = $name_option_exists
                ? $options['name']
                : $this->tag_name_with_index($options['index']);
            $options['id'] = array_key_exists('id', $options)
                ? $options['id']
                : $this->tag_id_with_index($options['index']);
            unset($options['index']);
        } elseif($this->auto_index) {
            $options['name'] = $name_option_exists
                ? $options['name']
                : $this->tag_name_with_index($this->auto_index);
            $options['id'] = array_key_exists('id', $options)
                ? $options['id']
                : $this->tag_id_with_index($this->auto_index);
        } else {
            $options['name'] = $name_option_exists
                ? $options['name']
                : $this->tag_name();
            $options['id'] = array_key_exists('id', $options)
                ? $options['id']
                : $this->tag_id();
        }
        if(array_key_exists('multiple', $options) && !$name_option_exists) {
            $options['name'] .= "[]";           
        }
        return $options;
    }

    /**
     *  Generate an HTML or XML input tag with optional attributes
     *
     *  @param string  Type of input field (<samp>'text'</samp>,
     *                 <samp>'password'</samp>, <samp>'hidden'</samp>
     *                 <i>etc.</i>)
     *  @param string[] Attributes to apply to the input tag:<br>
     *    <samp>array('attr1' => 'value1'[, 'attr2' => 'value2']...)</samp>
     *  @return string
     *   <samp><input type="</samp><i>type</i>
     *   <samp>" maxlength="</samp><i>maxlength</i><samp>" .../>\n</samp>
     *  @uses add_default_name_and_id()
     *  @uses attribute_name
     *  @uses error_wrapping
     *  @uses default_field_options
     *  @uses object()
     *  @uses tag()
     *  @uses value()
     */
    function to_input_field_tag($field_type, $options = array()) {
        $default_size = array_key_exists("maxlength", $options)
            ? $options["maxlength"] : $this->default_field_options['size'];
        $options["size"] = array_key_exists("size", $options)
            ? $options["size"]: $default_size;
        $options = array_merge($this->default_field_options, $options);
        if($field_type == "hidden") {
            unset($options["size"]);
        }
        $options["type"] = $field_type;
        if($field_type != "file") {
            $options["value"] = array_key_exists("value", $options)
                ? $options["value"] : $this->value();
        }
        $options = $this->add_default_name_and_id($options);
        return $this->error_wrapping(
                     $this->tag("input", $options),
                     @array_key_exists($this->attribute_name,
                                      $this->object()->errors)
                     ? true : false);
    }

    /**
     *  @todo Document this method
     *  @uses add_default_name_and_id()
     */
    function to_radio_button_tag($tag_value, $options = array()) {
        $options = array_merge($this->default_radio_options, $options);
        $options["type"] = "radio";
        $options["value"] = $tag_value;
        if($this->value() == $tag_value) {
            $options["checked"] = "checked";
        }
        $pretty_tag_value = preg_replace('/\s/', "_", preg_replace('/\W/', "", strtolower($tag_value)));
        $options["id"] = $this->auto_index ?
            "{$this->object_name}_{$this->auto_index}_{$this->attribute_name}_{$pretty_tag_value}" :
            "{$this->object_name}_{$this->attribute_name}_{$pretty_tag_value}";
        $options = $this->add_default_name_and_id($options);
        return $this->error_wrapping($this->tag("input", $options),$this->object()->errors[$this->attribute_name]);
    }

    /**
     *  @todo Document this method
     *  @uses add_default_name_and_id()
     */
    function to_text_area_tag($options = array()) {
        if (array_key_exists("size", $options)) {
            $size = explode('x', $options["size"]);
            $options["cols"] = reset($size);
            $options["rows"] = end($size);
            unset($options["size"]);
        }
        $options = array_merge($this->default_text_area_options, $options);
        $options = $this->add_default_name_and_id($options);
        return $this->error_wrapping(
           $this->content_tag("textarea",
                              htmlspecialchars($this->value(), ENT_COMPAT),
                              $options),
           array_key_exists($this->attribute_name,$this->object()->errors)
           ? $this->object()->errors[$this->attribute_name] : false);
    }

    /**
     *  @todo Document this method
     *  @uses add_default_name_and_id()
     */
    function to_check_box_tag($options = array(), $checked_value = "1", $unchecked_value = "0") {
        $options["type"] = "checkbox";
        $options["value"] = $checked_value;
        switch(gettype($this->value())) {
            case 'boolean':
                $checked = $this->value();
                break;
            case 'NULL':
                $checked = false;
                break;
            case 'integer':
                $checked = ($this->value() != 0);
                break;
            case 'string':
                $checked = ($this->value() == $checked_value);
                break;
            default:
                $checked = ($this->value() != 0);
        }

        if ($checked || $options["checked"] == "checked") {
            $options["checked"] = "checked";
        } else {
            unset($options["checked"]);
        }

        $options = $this->add_default_name_and_id($options);
        return $this->error_wrapping($this->tag("input", array("name" => $options["name"], "type" => "hidden", "value" => $unchecked_value)) . $this->tag("input", $options),$this->object()->errors[$this->attribute_name]);
    }

    /**
     *  @todo Document this method
     *  @uses add_default_name_and_id()
     */
    function to_boolean_select_tag($options = array()) {
        $options = $this->add_default_name_and_id($options);
        $tag_text = "<select ";
        $tag_text .= $this->tag_options($options);
        $tag_text .= ">\n";
        $tag_text .= "<option value=\"0\"";
        if($this->value() == false) {
            $tag_text .= " selected";
        }
        $tag_text .= ">False</option>\n";
        $tag_text .= "<option value=\"1\"";
        if($this->value()) {
            $tag_text .= " selected";
        }
        $tag_text .= ">True</option>\n";
        $tag_text .= "</select>\n";
        return $this->error_wrapping($tag_text,$this->object()->errors[$this->attribute_name]);
    }
    
}


/**
 *  Generate HTML/XML for <input type="text" /> in a view file
 *
 *  Example: In the view file, code
 *           <code><?= text_field("Person", "fname"); ?></code>
 *  Result: <input id="Person_fname" name="Person[fname]" size="30" type="text" value="$Person->fname" />
 *  @param string  Class name of the object being processed
 *  @param string  Name of attribute in the object being processed
 *  @param string[]  Attributes to apply to the generated input tag as:<br>
 *    <samp>array('attr1' => 'value1'[, 'attr2' => 'value2']...)</samp>
 *  @uses FormHelper::to_input_field_tag()
 */
function text_field($object, $field, $options = array()) {
    $form = new FormHelper($object, $field);
    return $form->to_input_field_tag("text", $options);
}

/**
 *  Works just like text_field, but returns a input tag of the "password" type instead.
 * Example: password_field("user", "password");
 *  Result: <input type="password" id="user_password" name="user[password]" value="$user->password" />
 *  @uses FormHelper::to_input_field_tag()
 */
function password_field($object, $field, $options = array()) {
    $form = new FormHelper($object, $field);
    return $form->to_input_field_tag("password", $options);
}

/**
 *  Works just like text_field, but returns a input tag of the "hidden" type instead.
 *  Example: hidden_field("post", "title");
 *  Result: <input type="hidden" id="post_title" name="post[title]" value="$post->title" />
 *  @uses FormHelper::to_input_field_tag()
 */
function hidden_field($object, $field, $options = array()) {
    $form = new FormHelper($object, $field);
    return $form->to_input_field_tag("hidden", $options);
}

/**
 * Works just like text_field, but returns a input tag of the "file" type instead, which won't have any default value.
 *  @uses FormHelper::to_input_field_tag()
 */
function file_field($object, $field, $options = array()) {
    $form = new FormHelper($object, $field);
    return $form->to_input_field_tag("file", $options);
}

/**
 *  Example: text_area("post", "body", array("cols" => 20, "rows" => 40));
 *  Result: <textarea cols="20" rows="40" id="post_body" name="post[body]">$post->body</textarea>
 *  @uses FormHelper::to_text_area_tag()
 */
function text_area($object, $field, $options = array()) {
    $form = new FormHelper($object, $field);
    return $form->to_text_area_tag($options);
}

/**
 * Returns a checkbox tag tailored for accessing a specified attribute (identified by $field) on an object
 * assigned to the template (identified by $object). It's intended that $field returns an integer and if that
 * integer is above zero, then the checkbox is checked. Additional $options on the input tag can be passed as an
 * array with $options. The $checked_value defaults to 1 while the default $unchecked_value
 * is set to 0 which is convenient for boolean values. Usually unchecked checkboxes don't post anything.
 * We work around this problem by adding a hidden value with the same name as the checkbox.
#
 * Example: Imagine that $post->validated is 1:
 *   check_box("post", "validated");
 * Result:
 *   <input type="checkbox" id="post_validate" name="post[validated] value="1" checked="checked" />
 *   <input name="post[validated]" type="hidden" value="0" />
#
 * Example: Imagine that $puppy->gooddog is no:
 *   check_box("puppy", "gooddog", array(), "yes", "no");
 * Result:
 *     <input type="checkbox" id="puppy_gooddog" name="puppy[gooddog] value="yes" />
 *     <input name="puppy[gooddog]" type="hidden" value="no" />
   *  @uses FormHelper::to_check_box_tag()
 */
function check_box($object, $field, $options = array(), $checked_value = "1", $unchecked_value = "0") {
    $form = new FormHelper($object, $field);
    return $form->to_check_box_tag($options, $checked_value, $unchecked_value);
}

/**
 * Returns a radio button tag for accessing a specified attribute (identified by $field) on an object
 * assigned to the template (identified by $object). If the current value of $field is $tag_value the
 * radio button will be checked. Additional $options on the input tag can be passed as a
 * hash with $options.
 * Example: Imagine that $post->category is "trax":
 *   radio_button("post", "category", "trax");
 *   radio_button("post", "category", "java");
 * Result:
 *     <input type="radio" id="post_category" name="post[category] value="trax" checked="checked" />
 *     <input type="radio" id="post_category" name="post[category] value="java" />
 *  @uses FormHelper::to_radio_button_tag()
 */
function radio_button($object, $field, $tag_value, $options = array()) {
    $form = new FormHelper($object, $field);
    return $form->to_radio_button_tag($tag_value, $options);
}

/**
 *  Make a new FormHelper object and call its to_boolean_select_tag method
 *  @uses FormHelper::to_boolean_select_tag()
 */
function boolean_select($object, $field, $options = array()) {
    $form = new FormHelper($object, $field);
    return $form->to_boolean_select_tag($options);        
}

// -- set Emacs parameters --
// Local variables:
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>