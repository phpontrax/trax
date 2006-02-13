<?php
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

class FormHelper extends Helpers {

    function __construct($object_name, $attribute_name) {
        parent::__construct();
        $this->object_name = $object_name;
        $this->attribute_name = $attribute_name;
        $this->default_field_options = $GLOBALS['DEFAULT_FIELD_OPTIONS'] ? $GLOBALS['DEFAULT_FIELD_OPTIONS'] : array("size" => 30);
        $this->default_radio_options = $GLOBALS['DEFAULT_RADIO_OPTIONS'] ? $GLOBALS['DEFAULT_RADIO_OPTIONS'] : array();
        $this->default_text_area_options = $GLOBALS['DEFAULT_TEXT_AREA_OPTIONS'] ? $GLOBALS['DEFAULT_TEXT_AREA_OPTIONS'] : array("cols" => 40, "rows" => 20);
        $this->default_date_options = $GLOBALS['DEFAULT_DATE_OPTIONS'] ? $GLOBALS['DEFAULT_DATE_OPTIONS'] : array(":discard_type" => true);
    }

    function value() {
        if(!$value = $_REQUEST[$this->object_name][$this->attribute_name]) {
            $object = $this->object();
            if(is_object($object) && $this->attribute_name) {
                $value = $object->send($this->attribute_name);
            }
        }
        return $value;
    }

    function object($object_name = null) {
        $object_name = $object_name ? $object_name : $this->object_name;
        return $this->controller_object->$object_name;
    }

    function tag_name() {
        return "{$this->object_name}[{$this->attribute_name}]";
    }

    function tag_name_with_index($index) {
        return "{$this->object_name}[{$index}][{$this->attribute_name}]";
    }

    function tag_id() {
        return "{$this->object_name}_{$this->attribute_name}";
    }

    function tag_id_with_index($index) {
        return "{$this->object_name}_{$index}_{$this->attribute_name}";
    }

    function add_default_name_and_id($options) {
        if(array_key_exists("index", $options)) {
            $options["name"] = $options["name"] ? $options["name"] : $this->tag_name_with_index($options["index"]);
            $options["id"] = $options["id"] ? $options["id"] : $this->tag_id_with_index($options["index"]);
            unset($options["index"]);
        } elseif($this->auto_index) {
            $options["name"] = $options["name"] ? $options["name"] : $this->tag_name_with_index($this->auto_index);
            $options["id"] = $options["id"] ? $options["id"] : $this->tag_id_with_index($this->auto_index);
        } else {
            $options["name"] = $options["name"] ? $options["name"] : $this->tag_name();
            $options["id"] = $options["id"] ? $options["id"] : $this->tag_id();
        }
        return $options;
    }

    function to_input_field_tag($field_type, $options = array()) {
        $default_size = $options["maxlength"] ? $options["maxlength"] : $this->default_field_options['size'];
        $options["size"] = $options["size"] ? $options["size"] : $default_size;
        $options = array_merge($this->default_field_options, $options);
        if($field_type == "hidden") {
            unset($options["size"]);
        }
        $options["type"] = $field_type;
        if($field_type != "file") {
            $options["value"] = $options["value"] ? $options["value"] : $this->value();
        }
        $options = $this->add_default_name_and_id($options);
        return $this->error_wrapping($this->tag("input", $options),$this->object()->errors[$this->attribute_name]);
    }

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

    function to_text_area_tag($options = array()) {
        if ($options["size"]) {
            $size = explode('x', $options["size"]);
            $options["cols"] = reset($size);
            $options["rows"] = end($size);
            unset($options["size"]);
        }
        $options = array_merge($this->default_text_area_options, $options);
        $options = $this->add_default_name_and_id($options);
        return $this->error_wrapping($this->content_tag("textarea", htmlspecialchars($this->value()), $options),$this->object()->errors[$this->attribute_name]);
    }

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
        return $this->error_wrapping($this->tag("input", $options) . $this->tag("input", array("name" => $options["name"], "type" => "hidden", "value" => $unchecked_value)),$this->object()->errors[$this->attribute_name]);
    }

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
        return $this->error_wrapping($tag_text,$this->object()->errors[$this->attribute_name]);;
    }
    
    function error_wrapping($html_tag, $has_error) {
        return ($has_error ? '<span class="fieldWithErrors">' . $html_tag . '</span>' : $html_tag);
    }    

}


################################################################################################
## Avialble functions for use in views
################################################################################################
# Example: text_field("post", "title");
# Result: <input type="text" id="post_title" name="post[title]" value="$post->title" />
function text_field($object, $field, $options = array()) {
    $form = new FormHelper($object, $field);
    return $form->to_input_field_tag("text", $options);
}

# Works just like text_field, but returns a input tag of the "password" type instead.
# Example: password_field("user", "password");
# Result: <input type="password" id="user_password" name="user[password]" value="$user->password" />
function password_field($object, $field, $options = array()) {
    $form = new FormHelper($object, $field);
    return $form->to_input_field_tag("password", $options);
}

# Works just like text_field, but returns a input tag of the "hidden" type instead.
# Example: hidden_field("post", "title");
# Result: <input type="hidden" id="post_title" name="post[title]" value="$post->title" />
function hidden_field($object, $field, $options = array()) {
    $form = new FormHelper($object, $field);
    return $form->to_input_field_tag("hidden", $options);
}

# Works just like text_field, but returns a input tag of the "file" type instead, which won't have any default value.
function file_field($object, $field, $options = array()) {
    $form = new FormHelper($object, $field);
    return $form->to_input_field_tag("file", $options);
}

# Example: text_area("post", "body", array("cols" => 20, "rows" => 40));
# Result: <textarea cols="20" rows="40" id="post_body" name="post[body]">$post->body</textarea>
function text_area($object, $field, $options = array()) {
    $form = new FormHelper($object, $field);
    return $form->to_text_area_tag($options);
}

# Returns a checkbox tag tailored for accessing a specified attribute (identified by $field) on an object
# assigned to the template (identified by $object). It's intended that $field returns an integer and if that
# integer is above zero, then the checkbox is checked. Additional $options on the input tag can be passed as an
# array with $options. The $checked_value defaults to 1 while the default $unchecked_value
# is set to 0 which is convenient for boolean values. Usually unchecked checkboxes don't post anything.
# We work around this problem by adding a hidden value with the same name as the checkbox.
#
# Example: Imagine that $post->validated is 1:
#   check_box("post", "validated");
# Result:
#   <input type="checkbox" id="post_validate" name="post[validated] value="1" checked="checked" />
#   <input name="post[validated]" type="hidden" value="0" />
#
# Example: Imagine that $puppy->gooddog is no:
#   check_box("puppy", "gooddog", array(), "yes", "no");
# Result:
#     <input type="checkbox" id="puppy_gooddog" name="puppy[gooddog] value="yes" />
#     <input name="puppy[gooddog]" type="hidden" value="no" />
function check_box($object, $field, $options = array(), $checked_value = "1", $unchecked_value = "0") {
    $form = new FormHelper($object, $field);
    return $form->to_check_box_tag($options, $checked_value, $unchecked_value);
}

# Returns a radio button tag for accessing a specified attribute (identified by $field) on an object
# assigned to the template (identified by $object). If the current value of $field is $tag_value the
# radio button will be checked. Additional $options on the input tag can be passed as a
# hash with $options.
# Example: Imagine that $post->category is "trax":
#   radio_button("post", "category", "trax");
#   radio_button("post", "category", "java");
# Result:
#     <input type="radio" id="post_category" name="post[category] value="trax" checked="checked" />
#     <input type="radio" id="post_category" name="post[category] value="java" />
function radio_button($object, $field, $tag_value, $options = array()) {
    $form = new FormHelper($object, $field);
    return $form->to_radio_button_tag($tag_value, $options);
}

function boolean_select($object, $field, $options = array()) {
    $form = new FormHelper($object, $field);
    return $form->to_boolean_select_tag($options);        
}

?>