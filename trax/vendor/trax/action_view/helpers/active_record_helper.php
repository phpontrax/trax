<?php
/**
 *  File containing ActiveRecordHelper class and support functions
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
 *
 *  @package PHPonTrax
 */

/**
 *  @todo Document this class
 */
class ActiveRecordHelper extends Helpers {
    
    /**
     *  Whether to generate scaffolding HTML
     *
     *  Set to true in {@link form_scaffolding.phtml}.  If true
     *  generate HTML scaffold otherwise generate final HTML
     *  @var boolean
     */
    public $scaffolding = false;

    /**
     *  Returns a default input tag for the type of object returned by the method. Example
     *  (title is a VARCHAR column and holds "Hello World"):
     *   input("post", "title") =>
     *     <input id="post_title" name="post[title]" size="30" type="text" value="Hello World" />
     *  @uses to_tag()
     */
    function input($object_name, $attribute_name, $options = array()) {
        return $this->to_tag($object_name, $attribute_name, $options);        
    }
    
    /**
     *  @todo Document this method
     *  @uses to_scaffold_tag()
     */
    function input_scaffolding($object_name, $attribute_name, $options = array()) {
        return $this->to_scaffold_tag($object_name, $attribute_name, $options);        
    }

    /**
     * Returns an entire form with input tags and everything for a specified Active Record object. Example
     * (post is a new record that has a title using VARCHAR and a body using TEXT):
     *   form("post") =>
     *     <form action='/post/create' method='post'>
     *       <p>
     *         <label for="post_title">Title</label><br />
     *         <input id="post_title" name="post[title]" size="30" type="text" value="Hello World" />
     *       </p>
     *       <p>
     *         <label for="post_body">Body</label><br />
     *         <textarea cols="40" id="post_body" name="post[body]" rows="20">
     *           Back to the hill and over it again!
     *         </textarea>
     *       </p>
     *       <input type='submit' value='Create' />
     *     </form>
     *
     *  It's possible to specialize the form builder by using a different action name and by supplying another
     *  block renderer. Example (entry is a new record that has a message attribute using VARCHAR):
     *
     *   form("entry", array('action' => "sign", 'input_block' => 
     *        'foreach($record->content_columns() as $column_name => $column) $contents .= Inflector::humanize($column_name) . ": " . input($record, $column) . "<br />"')) =>
     *
     *     <form action='/post/sign' method='post'>
     *       Message:
     *       <input id="post_title" name="post[title]" size="30" type="text" value="Hello World" /><br />
     *       <input type='submit' value='Sign' />
     *     </form>
     *
     *  It's also possible to add additional content to the form by giving it a block, such as:
     *
     *   form("entry", array('action' => "sign", 'block' =>
     *     content_tag("b", "Department") .
     *     collection_select("department", "id", $departments, "id", "name"))
     *   )
     *  @uses all_input_tags()
     *  @uses content_tag()
     *  @uses Helpers::object()
     */
    function form($record_name, $options = array()) {
        $record = $this->object($record_name);
        $options["action"] = $options[":action"] ? $options[":action"] : $record->is_new_record() ? "add" : "save";
        $action = url_for(array(':action' => $options[':action'], ':id' => $record->id));
        $submit_value = (isset($options['submit_value']) ? $options['submit_value'] : ucfirst(preg_replace('/[^\w]/', '', $options[':action'])));

        $contents = '';
        if(!$record->is_new_record()) $contents .= hidden_field($record_name, 'id');
        $contents .= $this->all_input_tags($record, $record_name, $options);
        if(isset($options['block'])) $contents .= eval($options['block']);
        $contents .= "<br>".submit_tag($submit_value)."<br><br>";

        return $this->content_tag('form', $contents, array('action' => $action, 'method' => 'post'));
    }

    /**
     *  Returns a string containing the error message attached to the +method+ on the +object+, if one exists.
     *  This error message is wrapped in a DIV tag, which can be specialized to include both a +prepend_text+ and +append_text+
     *  to properly introduce the error and a +css_class+ to style it accordingly. Examples (post has an error message
     *  "can't be empty" on the title attribute):
     *
     *   <?= error_message_on("post", "title") ?> =>
     *     <div class="formError">can't be empty</div>
     *
     *   <?= error_message_on "post", "title", "Title simply ", " (or it won't work)", "inputError" ?> =>
     *     <div class="inputError">Title simply can't be empty (or it won't work)</div>
     *  @uses attribute_name
     *  @uses controller_object
     *  @uses content_tag()
     *  @uses object_name
     */
    function error_message_on($object_name, $attribute_name, $prepend_text = "", $append_text = "", $css_class = "formError") {
        $this->object_name = $object_name;
        $this->attribute_name = $attribute_name;
        $object = $this->controller_object->$object_name;
        if($errors = $object->errors[$attribute_name]) {
            return $this->content_tag("div", $prepend_text . (is_array($errors) ? current($errors) : $errors) . $append_text, array('class' => $css_class));
        }
    }

    /**
     *  Returns a string with a div containing all the error messages for the object located as an instance variable by the name
     *  of <tt>object_name</tt>. This div can be tailored by the following options:
     *
     * <tt>header_tag</tt> - Used for the header of the error div (default: h2)
     * <tt>id</tt> - The id of the error div (default: errorExplanation)
     * <tt>class</tt> - The class of the error div (default: errorExplanation)
     *  @param mixed object_name  The name of a PHP class, or
     *                            an object instance of that class
     *  @param string[] options Set of options: 'header_tag', 'id', 'class', 'header_message', 'header_sub_message'
     *  @uses content_tag()
     *  @uses object_name
     *  @uses Inflector::humanize()
     */
    function error_messages_for($object_name, $options = array()) {
        if(is_object($object_name)) {
            $object_name = get_class($object_name);
            //echo "object name:".$object_name;
        }
        $this->object_name = $object_name;
        $object = $this->controller_object->$object_name;
        if(!empty($object->errors)) {
            $id = isset($options['id']) ? $options['id'] : "ErrorExplanation";
            $class = isset($options['class']) ? $options['class'] : "ErrorExplanation";
            $header_tag = isset($options['header_tag']) ? $options['header_tag'] : "h2";
            $header_message = isset($options['header_message']) ? 
                $options['header_message'] : "%s prohibited this %s from being saved";
            $header_sub_message = isset($options['header_sub_message']) ?
                $options['header_sub_message'] : "There were problems with the following fields:";
               
            return $this->content_tag("div",
                $this->content_tag(
                    $header_tag,
                    sprintf($header_message, Inflector::pluralize("error", count($object->errors)), Inflector::humanize($object_name))
                ) .
                $this->content_tag("p", $header_sub_message) .
                $this->content_tag("ul", array_reduce($object->errors, create_function('$v,$w', 'return ($v ? $v : "") . content_tag("li", $w);'), '')),
                array("id" => $id, "class" => $class)
            );
        }
    }

    /**
     *  @todo Document this method
     *  @uses default_input_block()
     */
    function all_input_tags($record, $record_name, $options) {
        //if($record_name) $this->object_name = $record_name;
        $input_block = (isset($options['input_block']) ? $options['input_block'] : $this->default_input_block());
        $contents = '';
        if(is_array($record->content_columns)) {
            foreach($record->content_columns as $column) {
                //$contents .= "<p><label for=\"".$record_name."_".$column['name']."\">";
                //$contents .= Inflector::humanize($column['name']) . ":</label><br />";
                //$contents .= input($record_name, $column['name']) . "</p>\n";
                if(!in_array($column['name'], $record->primary_keys)) {
                    eval($input_block) . "\n";    
                }         
            }
        } 
        return $contents;
    }

    /**
     *  @todo Document this method
     *  @uses scaffolding
     *  @uses input_scaffolding()
     */
    function default_input_block() {
        if($this->scaffolding) {
            return '$contents .= "<p><label for=\"{$record_name}_{$column[\'name\']}\">" . Inflector::humanize($column[\'name\']) . ":</label><br/>\n<?= " . input_scaffolding($record_name, $column[\'name\']) . " ?></p>\n";';
        } else {
            return '$contents .= "<p><label for=\"{$record_name}_{$column[\'name\']}\">" . Inflector::humanize($column[\'name\']) . ":</label><br/>\n" . input($record_name, $column[\'name\']) . "</p>\n";';
        }
    }
    
    /**
     *  @todo Document this method
     *
     *  @param string object_name    Name of an ActiveRecord subclass
     *  @param string attribute_name Name of an attribute of $object_name
     *  @param string[] options
     *  @uses attribute_name
     *  @uses column_type()
     *  @uses error_wrapping()
     *  @uses object_name
     *  @uses DateHelper::to_date_select_tag()
     *  @uses FormHelper::to_boolean_select_tag()
     *  @uses FormHelper::to_input_field_tag()
     *  @uses FormHelper::to_text_area_tag()
     *  @uses to_datetime_select_tag()
     *  @uses object()
     */
    function to_tag($object_name, $attribute_name, $options = array()) {
        $this->object_name = $object_name;
        $this->attribute_name = $attribute_name;
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
        case 'real':
            $results = $form->to_input_field_tag("text", $options);
            break;

        case 'date':
            $form = new DateHelper($object_name, $attribute_name);
            $results = $form->to_date_select_tag($options);
            break;

        case 'datetime':
        case 'timestamp':
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

    /**
     *  @todo Document this method
     *
     *  @uses attribute_name
     *  @uses column_type()
     *  @uses error_wrapping
     *  @uses object()
     *  @uses object_name
     */
    function to_scaffold_tag($object_name, $attribute_name, $options = array()) {
        $this->object_name = $object_name;
        $this->attribute_name = $attribute_name;
        switch($this->column_type()) {
        case 'string':
        case 'varchar':
        case 'varchar2':
            $field_type = (eregi("password", $this->attribute_name) ? "password" : "text");
            $results = $field_type."_field(\"$object_name\", \"$attribute_name\")";
            break;

        case 'text':
        case 'blob':
            $results = "text_area(\"$object_name\", \"$attribute_name\")";
            break;

        case 'integer':
        case 'int':
        case 'number':
        case 'float':
        case 'real':
            $results = "text_field(\"$object_name\", \"$attribute_name\")";
            break;

        case 'date':
            $results = "date_select(\"$object_name\", \"$attribute_name\")";
            break;

        case 'year':
            $results = "year_select(\"$object_name\", \"$attribute_name\")";
            break;

        case 'datetime':
        case 'timestamp':
            $results = "datetime_select(\"$object_name\", \"$attribute_name\")";
            break;

        case 'time':
            $results = "time_select(\"$object_name\", \"$attribute_name\")";
            break;

        case 'boolean':
        case 'bool':
            $results = "boolean_select(\"$object_name\", \"$attribute_name\")";
            break;

        default:
            echo "No case statement for ".$this->column_type()."\n";
        }
        if(count($this->object()->errors)) {
            $results = $this->error_wrapping($results, 
                              $this->object()->errors[$this->attribute_name]);
        }
        return $results;
    }

    /**
     *  @todo Document this method
     *
     *  @uses tag()
     */
    function tag_without_error_wrapping() {
        $args = func_get_args();
        return call_user_func_array(array(parent, 'tag'), $args);
    }

    /**
     *  @todo Document this method
     *
     *  @uses error_wrapping()
     *  @uses object()
     *  @uses tag_without_error_wrapping()
     */
    function tag($name, $options = array()) {
        if(count($this->object()->errors)) {
            return $this->error_wrapping($this->tag_without_error_wrapping($name, $options), $this->object()->errors[$this->attribute_name]);
        } else {
            return $this->tag_without_error_wrapping($name, $options);
        }
    }

    /**
     *  @todo Document this method
     *
     *  @uses content_tag()
     */
    function content_tag_without_error_wrapping() {
        $args = func_get_args();
        return call_user_func_array('content_tag', $args);
    }

    /**
     *  @todo Document this method
     *
     *  @uses object()
     *  @uses error_wrapping()
     *  @uses content_tag_without_error_wrapping()
     */
    function content_tag($name, $value, $options = array()) {
        if (count($this->object()->errors)) {
            return $this->error_wrapping(
          $this->content_tag_without_error_wrapping($name, $value, $options),
            array_key_exists($this->attribute_name,$this->object()->errors)
            ? true : false);
        } else {
            return $this->content_tag_without_error_wrapping($name, $value,
                                                             $options);
        }
    }

    /**
     *  @todo Document this method
     *
     *  @uses object_name
     *  @uses attribute_name
     *  @uses DateHelper::to_date_select_tag()
     */
    function to_date_select_tag_without_error_wrapping() {
        $form = new DateHelper($this->object_name, $this->attribute_name);
        $args = func_get_args();
        return call_user_func_array(array($form, 'to_date_select_tag'), $args);
    }

    /**
     *  @todo Document this method
     */
    function to_date_select_tag($options = array()) {
        if (count($this->object()->errors)) {
            return $this->error_wrapping($this->to_date_select_tag_without_error_wrapping($options), $this->object()->errors[$this->attribute_name]);
        } else {
            return $this->to_date_select_tag_without_error_wrapping($options);
        }
    }

    /**
     *  @todo Document this method
     *
     *  @uses attribute_name
     *  @uses object_name
     *  @uses DateHelper::to_datetime_select_tag()
     */
    function to_datetime_select_tag_without_error_wrapping() {
        $form = new DateHelper($this->object_name, $this->attribute_name);
        $args = func_get_args();
        return call_user_func_array(array($form, 'to_datetime_select_tag'),
                                    $args);
    }

    /**
     *  @todo Document this method
     *
     *  @uses attribute_name
     *  @uses error_wrapping()
     *  @uses object()
     *  @uses to_datetime_select_tag_without_error_wrapping
     */
    function to_datetime_select_tag($options = array()) {
        if (count($this->object()->errors)) {
            return $this->error_wrapping($this->to_datetime_select_tag_without_error_wrapping($options), $this->object()->errors[$this->attribute_name]);
        } else {
            return $this->to_datetime_select_tag_without_error_wrapping($options);
        }
    }

    /**
     *  @todo Document this method
     *
     *  @uses attribute_name
     *  @uses object()
     */
    function error_message() {
        return $this->object()->errors[$this->attribute_name];
    }

    /**
     *  @todo Document this method
     *
     *  @uses attribute_name
     *  @uses object()
     *  @uses ActiveRecord::column_type()
     */
    function column_type() {
        return $this->object()->column_type($this->attribute_name);
    }
    
    /**
     * Paging html functions
     *  @todo Document this API
     */
    function pagination_limit_select($object_name_or_object, $default_text = "per page:") {

        if(is_object($object_name_or_object)) {
            $object = $object_name_or_object;
        } else {
            $object = $this->object($object_name_or_object);  
        }

        if(!is_object($object)) {
            return null;    
        }

        if($object->pages > 0) {
            $html .= "
                <select name=\"per_page\" onChange=\"document.location = '?".escape_javascript($object->paging_extra_params)."&per_page=' + this.options[this.selectedIndex].value;\">
                    <option value=\"$object->rows_per_page\" selected>$default_text</option>
                    <option value=10>10</option>
                    <option value=20>20</option>
                    <option value=50>50</option>
                    <option value=100>100</option>
                    <option value=999999999>ALL</option>
                </select>
            ";
        }
        return $html;
    }

    /**
     *  @todo Document this API
     *
     *  @return string HTML to link to previous and next pages
     *  @uses $display
     *  @uses $page
     *  @uses $pages
     *  @uses $paging_extra_params
     *  @uses rows_per_page
     */
    function pagination_links($object_name_or_object) {
        
        if(is_object($object_name_or_object)) {
            $object = $object_name_or_object;
        } else {
            $object = $this->object($object_name_or_object);  
        }

        if(!is_object($object)) {
            return null;    
        }
           
        //$html  = "<pre>".print_r($object,1);
        /* Print the first and previous page links if necessary */
        if(($object->page != 1) && ($object->page)) {
            $html .= link_to("<<", 
                                  "?$object->paging_extra_params&page=1&per_page=$object->rows_per_page",
                                  array(
                                    "class" => "pageList",
                                    "title" => "First page"
                                  ))." ";
        }
        if(($object->page-1) > 0) {
            $html .= link_to("<", 
                                  "?$object->paging_extra_params&page=".($object->page-1)."&per_page=$object->rows_per_page",
                                  array(
                                    "class" => "pageList",
                                    "title" => "Previous Page"                                    
                                  ));
        }
        
        if($object->pages < $object->display) {
            $object->display = $object->pages;
        }
        
        if($object->page == $object->pages) {
            if(($object->pages - $object->display) == 0) {
                $start = 1;
            } else {
                $start = $object->pages - $object->display;
            }
            $end = $object->pages;
        } else {
            if($object->page >= $object->display) {
                $start = $object->page - ($object->display / 2);
                $end   = $object->page + (($object->display / 2) - 1);
            } else {
                $start = 1;
                $end   = $object->display;
            }
        }

        if($end >= $object->pages) {
            $end = $object->pages;
        }
                
        /* Print the numeric page list; make the current page unlinked and bold */
        if($end != 1) {
            for($i=$start; $i<=$end; $i++) {
                if($i == $object->page) {
                    $html .= "<span class=\"pageList\"><b>".$i."</b></span>";
                } else {
                    $html .= link_to($i,
                                          "?$object->paging_extra_params&page=$i&per_page=$object->rows_per_page",
                                          array(
                                            "class" => "pageList",
                                            "title" => "Page $i"                                           
                                          ));
                }
                $html .= " ";
            }
        }

        /* Print the Next and Last page links if necessary */
        if(($object->page+1) <= $object->pages) {
            $html .= link_to(">",
                                  "?$object->paging_extra_params&page=".($object->page+1)."&per_page=$object->rows_per_page",
                                  array(
                                    "class" => "pageList",
                                    "title" => "Next Page"                                    
                                  ));
        }
        if(($object->page != $object->pages) && ($object->pages != 0)) {
            $html .= link_to(">>",
                                  "?$object->paging_extra_params&page=".$object->pages."&per_page=$object->rows_per_page",
                                  array(
                                    "class" => "pageList",
                                    "title" => "Last Page"                                    
                                  ));
        }
        $html .= "\n";

        return $html;
    }    
        
    function pagination_range_text($object_name_or_object, $format = "Showing %d - %d of %d items.") {
        if(is_object($object_name_or_object)) {
            $object = $object_name_or_object;
        } else {
            $object = $this->object($object_name_or_object);
        }

        if(!is_object($object)) {
            return null;
        }
        $end = $object->rows_per_page * $object->page;
        $start = $end - ($object->rows_per_page - 1);
        if($end >= $object->pagination_count) {
            $end = $object->pagination_count;
        }

        return $object->pagination_count ? sprintf($format, $start, $end, $object->pagination_count) : null;
    }    

}

/**
 *  Avialble functions for use in views
 * error_message_on($object, $attribute_name, $prepend_text = "", $append_text = "", $css_class = "formError")
 *  @uses ActiveRecordHelper::error_message_on()
 */
function error_message_on() {
    $ar_helper = new ActiveRecordHelper();
    $args = func_get_args();
    return call_user_func_array(array($ar_helper, 'error_message_on'), $args);
}

/**
 *  error_messages_for($object_name, $options = array())
 *  @uses ActiveRecordHelper::error_messages_for()
 */
function error_messages_for() {
    $ar_helper = new ActiveRecordHelper();
    $args = func_get_args();
    return call_user_func_array(array($ar_helper, 'error_messages_for'), $args);
}

/**
 *  form($record_name, $options = array())
 *  @uses ActiveRecordHelper::form()
 */
function form() {
    $ar_helper = new ActiveRecordHelper();
    $args = func_get_args();
    return call_user_func_array(array($ar_helper, 'form'), $args);
}

/**
 *  Returns a default input tag for the type of object returned by the method. Example
 *  (title is a VARCHAR column and holds "Hello World"):
 *   input("post", "title") =>
 *    <input id="post_title" name="post[title]" size="30" type="text" value="Hello World" />
 *  @uses ActiveRecordHelper::input()
 */
function input() {
    $ar_helper = new ActiveRecordHelper();
    $args = func_get_args();
    return call_user_func_array(array($ar_helper, 'input'), $args);
}

/**
 *
 *  @uses ActiveRecordHelper::input_scaffolding()
 */
function input_scaffolding() {
    $ar_helper = new ActiveRecordHelper();
    $args = func_get_args();
    return call_user_func_array(array($ar_helper, 'input_scaffolding'), $args);
}

/**
 *
 *  @uses ActiveRecordHelper::pagination_limit_select()
 */
function pagination_limit_select() {
    $ar_helper = new ActiveRecordHelper();
    $args = func_get_args();
    return call_user_func_array(array($ar_helper, 'pagination_limit_select'), $args);
}

/**
 *
 *  @uses ActiveRecordHelper::pagination_links()
 */
function pagination_links() {
    $ar_helper = new ActiveRecordHelper();
    $args = func_get_args();
    return call_user_func_array(array($ar_helper, 'pagination_links'), $args);
}

/**
 *
 *  @uses ActiveRecordHelper::pagination_range_text()
 */
function pagination_range_text() {
    $ar_helper = new ActiveRecordHelper();
    $args = func_get_args();
    return call_user_func_array(array($ar_helper, 'pagination_range_text'), $args);
}

// -- set Emacs parameters --
// Local variables:
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
