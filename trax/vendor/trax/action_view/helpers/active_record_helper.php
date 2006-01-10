<?php


class ActiveRecordHelper extends Helpers {

    # Returns a default input tag for the type of object returned by the method. Example
    # (title is a VARCHAR column and holds "Hello World"):
    #   input("post", "title") =>
    #     <input id="post_title" name="post[title]" size="30" type="text" value="Hello World" />
    function input($object_name, $attribute_name, $options = array()) {
        return $this->to_tag($object_name, $attribute_name, $options);
    }

    # Returns an entire form with input tags and everything for a specified Active Record object. Example
    # (post is a new record that has a title using VARCHAR and a body using TEXT):
    #   form("post") =>
    #     <form action='/post/create' method='post'>
    #       <p>
    #         <label for="post_title">Title</label><br />
    #         <input id="post_title" name="post[title]" size="30" type="text" value="Hello World" />
    #       </p>
    #       <p>
    #         <label for="post_body">Body</label><br />
    #         <textarea cols="40" id="post_body" name="post[body]" rows="20">
    #           Back to the hill and over it again!
    #         </textarea>
    #       </p>
    #       <input type='submit' value='Create' />
    #     </form>
    #
    # It's possible to specialize the form builder by using a different action name and by supplying another
    # block renderer. Example (entry is a new record that has a message attribute using VARCHAR):
    #
    #   form("entry", array('action' => "sign", 'input_block' => 
    #        'foreach($record->content_columns() as $column_name => $column) $contents .= Inflector::humanize($column_name) . ": " . input($record, $column) . "<br />"')) =>
    #
    #     <form action='/post/sign' method='post'>
    #       Message:
    #       <input id="post_title" name="post[title]" size="30" type="text" value="Hello World" /><br />
    #       <input type='submit' value='Sign' />
    #     </form>
    #
    # It's also possible to add additional content to the form by giving it a block, such as:
    #
    #   form("entry", array('action' => "sign", 'block' =>
    #     content_tag("b", "Department") .
    #     collection_select("department", "id", $departments, "id", "name"))
    #   )
    function form($record_name, $options = array()) {
        $record = $this->object($record_name);
        $options["action"] = $options[":action"] ? $options[":action"] : $record->is_new_record() ? "add" : "save";
        $action = $this->url_for(array(':action' => $options[':action'], ':id' => $record->id));
        $submit_value = (isset($options['submit_value']) ? $options['submit_value'] : ucfirst(preg_replace('/[^\w]/', '', $options[':action'])));

        $contents = '';
        if(!$record->is_new_record()) $contents .= hidden_field($record_name, 'id');
        $contents .= $this->all_input_tags($record, $record_name, $options);
        if(isset($options['block'])) $contents .= eval($options['block']);
        $contents .= "<br>".submit_tag($submit_value)."<br><br>";

        return $this->content_tag('form', $contents, array('action' => $action, 'method' => 'post'));
    }

    /* # Returns a string containing the error message attached to the +method+ on the +object+, if one exists.
    # This error message is wrapped in a DIV tag, which can be specialized to include both a +prepend_text+ and +append_text+
    # to properly introduce the error and a +css_class+ to style it accordingly. Examples (post has an error message
    # "can't be empty" on the title attribute):
    #
    #   <?= error_message_on("post", "title") ?> =>
    #     <div class="formError">can't be empty</div>
    #
    #   <?= error_message_on "post", "title", "Title simply ", " (or it won't work)", "inputError" ?> =>
    #     <div class="inputError">Title simply can't be empty (or it won't work)</div> */
    function error_message_on($object_name, $attribute_name, $prepend_text = "", $append_text = "", $css_class = "formError") {
        $this->object_name = $object_name;
        $this->attribute_name = $attribute_name;
        $object = $this->controller_object->$object_name;
        if($errors = $object->errors[$attribute_name]) {
            return $this->content_tag("div", $prepend_text . (is_array($errors) ? current($errors) : $errors) . $append_text, array('class' => $css_class));
        }
    }

    # Returns a string with a div containing all the error messages for the object located as an instance variable by the name
    # of <tt>object_name</tt>. This div can be tailored by the following options:
    #
    # * <tt>header_tag</tt> - Used for the header of the error div (default: h2)
    # * <tt>id</tt> - The id of the error div (default: errorExplanation)
    # * <tt>class</tt> - The class of the error div (default: errorExplanation)
    function error_messages_for($object_name, $options = array()) {
        if(is_object($object_name)) {
            $object_name = get_class($object_name);
            //echo "object name:".$object_name;
        }
        $this->object_name = $object_name;
        $object = $this->controller_object->$object_name;
        if(!empty($object->errors)) {
            $errors = ($num_errors = count($object->errors)) > 1 ? "$num_errors errors" : "$num_errors error";
            return $this->content_tag("div",
                $this->content_tag(
                    (isset($options['header_tag']) ? $options['header_tag'] : "h2"),
                    $errors . 
                    " prohibited this " . Inflector::humanize($object_name) . " from being saved"
                ) .
                $this->content_tag("p", "There were problems with the following fields:") .
                $this->content_tag("ul", array_reduce($object->errors, create_function('$v,$w', 'return ($v ? $v : "") . content_tag("li", $w);'), '')),
                array("id" => (isset($options['id']) ? $options['id'] : "ErrorExplanation"), "class" => (isset($options['class']) ? $options['class'] : "ErrorExplanation"))
            );
        }
    }

    function all_input_tags($record, $record_name, $options) {
        $input_block = (isset($options['input_block']) ? $options['input_block'] : $this->default_input_block());
        $contents = '';
        foreach($record->content_columns as $column) {
            //$contents .= "<p><label for=\"".$record_name."_".$column['name']."\">";
            //$contents .= Inflector::humanize($column['name']) . ":</label><br />";
            //$contents .= input($record_name, $column['name']) . "</p>\n";
            if(!in_array($column['name'], $record->primary_keys)) {
                eval($input_block) . "\n";    
            }         
        } 
        return $contents;
    }

    function default_input_block() {
        return '$contents .= "<p><label for=\"{$record_name}_{$column[\'name\']}\">" . Inflector::humanize($column[\'name\']) . ":</label><br />" . input($record_name, $column[\'name\']) . "</p>";';
    }

    function to_tag($object_name, $attribute_name, $options = array()) {
        $this->object_name = $object_name;
        $this->attribute_name = $attribute_name;
        #this will be fixed once the column object is developed
        $form = new FormHelper($object_name, $attribute_name);
        switch($this->column_type()) {
            case 'string':
            case 'varchar':
            case 'varchar2':
                $field_type = (eregi("password", $this->attribute_name) ? "password" : "text");
                $results = $form->to_input_field_tag($field_type, $options);
                break;
            case 'text':
            case 'blob':
                $results = $form->to_text_area_tag($options);
                break;
            case 'integer':
            case 'int':
            case 'number':
            case 'float':
                $results = $form->to_input_field_tag("text", $options);
                break;
            case 'date':
                $form = new DateHelper($object_name, $attribute_name);
                $results = $form->to_date_select_tag($options);
                break;
            case 'datetime':
                $results = $this->to_datetime_select_tag($options);
                break;
            case 'boolean':
            case 'bool':
                $results = $form->to_boolean_select_tag($options);
                break;
        }
        if(count($this->object()->errors)) {
            $results = $this->error_wrapping($results, $this->object()->errors[$this->attribute_name]);
        }
        return $results;
    }

    function object($object_name = null) {
        $object_name = $object_name ? $object_name : $this->object_name;
        if($object_name) {
            return $this->controller_object->$object_name;
        }
    }

    function tag_without_error_wrapping() {
        $args = func_get_args();
        return call_user_func_array(array(parent, 'tag'), $args);
    }

    function tag($name, $options = array()) {
        if(count($this->object()->errors)) {
            return $this->error_wrapping($this->tag_without_error_wrapping($name, $options), $this->object()->errors[$this->attribute_name]);
        } else {
            return $this->tag_without_error_wrapping($name, $options);
        }
    }

    function content_tag_without_error_wrapping() {
        $args = func_get_args();
        return call_user_func_array(array(parent, 'content_tag'), $args);
    }

    function content_tag($name, $value, $options = array()) {
        if (count($this->object()->errors)) {
            return $this->error_wrapping($this->content_tag_without_error_wrapping($name, $value, $options), $this->object()->errors[$this->attribute_name]);
        } else {
            return $this->content_tag_without_error_wrapping($name, $value, $options);
        }
    }

    function to_date_select_tag_without_error_wrapping() {
        $form = new DateHelper($this->object_name, $this->attribute_name);
        $args = func_get_args();
        return call_user_func_array(array($form, 'to_date_select_tag'), $args);
    }

    function to_date_select_tag($options = array()) {
        if (count($this->object()->errors)) {
            return $this->error_wrapping($this->to_date_select_tag_without_error_wrapping($options), $this->object()->errors[$this->attribute_name]);
        } else {
            return $this->to_date_select_tag_without_error_wrapping($options);
        }
    }

    function to_datetime_select_tag_without_error_wrapping() {
        $form = new DateHelper($this->object_name, $this->attribute_name);
        $args = func_get_args();
        return call_user_func_array(array($form, 'to_datetime_select_tag'), $args);
    }

    function to_datetime_select_tag($options = array()) {
        if (count($this->object()->errors)) {
            return $this->error_wrapping($this->to_datetime_select_tag_without_error_wrapping($options), $this->object()->errors[$this->attribute_name]);
        } else {
            return $this->to_datetime_select_tag_without_error_wrapping($options);
        }
    }

    function error_wrapping($html_tag, $has_error) {
        return ($has_error ? '<div class="fieldWithErrors">' . $html_tag . '</div>' : $html_tag);
    }

    function error_message() {
        return $this->object()->errors[$this->attribute_name];
    }

    function column_type() {
        $column = $this->object()->column_for_attribute($this->attribute_name);
        return $column['type'];
    }

}

################################################################################################
## Avialble functions for use in views
################################################################################################
# error_message_on($object, $attribute_name, $prepend_text = "", $append_text = "", $css_class = "formError")
function error_message_on() {
    $ar_helper = new ActiveRecordHelper();
    $args = func_get_args();
    return call_user_func_array(array($ar_helper, 'error_message_on'), $args);
}

# error_messages_for($object_name, $options = array())
function error_messages_for() {
    $ar_helper = new ActiveRecordHelper();
    $args = func_get_args();
    return call_user_func_array(array($ar_helper, 'error_messages_for'), $args);
}

# form($record_name, $options = array())
function form() {
    $ar_helper = new ActiveRecordHelper();
    $args = func_get_args();
    return call_user_func_array(array($ar_helper, 'form'), $args);
}

# Returns a default input tag for the type of object returned by the method. Example
# (title is a VARCHAR column and holds "Hello World"):
#   input("post", "title") =>
#     <input id="post_title" name="post[title]" size="30" type="text" value="Hello World" />
function input() {
    $ar_helper = new ActiveRecordHelper();
    $args = func_get_args();
    return call_user_func_array(array($ar_helper, 'input'), $args);
}

?>