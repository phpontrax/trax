<?php
/**
 *  File containing the JavaScriptHelper class and support functions
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
class JavaScriptHelper extends Helpers {

    /**
     * 
     *
     */
    function __construct() {
        parent::__construct();
        $this->javascript_callbacks = is_array($GLOBALS['JAVASCRIPT_CALLBACKS']) ? $GLOBALS['JAVASCRIPT_CALLBACKS'] : array('uninitialized', 'loading', 'loaded', 'interactive', 'complete', 'failure', 'success');    
        $this->ajax_options = array_merge(array('before', 'after', 'condition', 'url', 'asynchronous', 'method', 'insertion', 'position', 'form', 'with', 'update', 'script'), $this->javascript_callbacks);
        $this->javascript_path = dirname(__FILE__).'/javascripts';

    }

    protected function options_for_javascript($options) {
        $javascript = array();
        if(is_array($options)) {
            $javascript = array_map(create_function('$k, $v', 'return "{$k}:{$v}";'), array_keys($options), array_values($options));
            sort($javascript);
        }
        return '{' . implode(', ', $javascript) . '}';
    }
    
    private function array_or_string_for_javascript($option) {
        if(is_array($option)) {
            $js_option = "['" . implode('\',\'', $option) . "']";
        } elseif (!is_null($option)) {
            $js_option = "'{$option}'";
        }
        return $js_option;
    }
    
    private function options_for_ajax($options) {
        $js_options = $this->build_callbacks($options);    
        $js_options['asynchronous'] = ($options['type'] != 'synchronous') ? "true" : "false";
        if($options['method']) {
            $js_options['method'] = $this->method_option_to_s($options['method']);
        }
        if($options['position']) {
            $js_options['insertion'] = "Insertion." . Inflector::camelize($options['position']);
        }
        $js_options['evalScripts'] = $options['script'] ? $options['script'] : "true";
        
        if($options['form']) {
            $js_options['parameters'] = "Form.serialize(this)";
        } elseif($options['submit']) {
            $js_options['parameters'] = "Form.serialize(document.getElementById('{$options['submit']}'))";
        } elseif($options['with']) {
            $js_options['parameters'] = $options['with'];
        }
        return $this->options_for_javascript($js_options);
    }
    
    private function method_option_to_s($method) {
        return ((is_string($method) && !strstr($method, "'")) ? "'{$method}'" : $method);
    }
    
    private function build_observer($klass, $name, $options = array()) {
        if($options['update']) {
            if(!$options['with']) {
                #$options['with'] = 'value';
		$options['with'] = "'value=' + value";
            }
        }
        $callback = $this->remote_function($options);
        $javascript  = "new {$klass}('{$name}', ";
        if($options['frequency']) {
            $javascript .= "{$options['frequency']}, ";
        }
        $javascript .= "function(element, value) {";
        $javascript .= "{$callback}})";
        return $this->javascript_tag($javascript);
    }
    
    private function build_callbacks($options) {
        $callbacks = array();
        foreach($options as $callback => $code) {
            if(in_array($callback, $this->javascript_callbacks)) {
                $name = 'on' . Inflector::capitalize($callback);
                $callbacks[$name] = "function(request){{$code}}";
            }
        }
        return $callbacks;
    }
    
    private function remove_ajax_options($options) {
        if(is_array($options)) {
            $GLOBALS['ajax_options'] = $this->ajax_options;
            foreach($options as $option_key => $option_value) {
                if(!in_array($option_key, $this->ajax_options)) {
                    $new_options[$option_key] = $option_value;  
                }  
            } 
            if(is_array($new_options)) {
                $options = $new_options; 
            }           
        }    
        return $options;    
    }
       
    # Returns a link that'll trigger a javascript $function using the 
    # onclick handler and return false after the fact.
    #
    # Examples:
    #   link_to_function("Greeting", "alert('Hello world!')")
    #   link_to_function(image_tag("delete"), "if confirm('Really?'){ do_delete(); }")
    function link_to_function($name, $function, $html_options = array()) {
        return $this->content_tag("a", $name, array_merge(array('href' => "#", 'onclick' => "{$function}; return false;"), $html_options));
    }
    
    # Returns a link to a remote action defined by <tt>$options['url']</tt> 
    # (using the url_for() format) that's called in the background using 
    # XMLHttpRequest. The result of that request can then be inserted into a
    # DOM object whose id can be specified with <tt>$options['update']</tt>. 
    # Usually, the result would be a partial prepared by the controller with
    # render_partial. 
    #
    # Examples:
    #  link_to_remote("Delete this post", array("update" => "posts", array("url" => array(":action" => "destroy", ":id" => $post->id)))
    #  link_to_remote(image_tag("refresh"), array("update" => "emails", "url" => array(":action" => "list_emails")))
    #  link_to_remote(image_tag("refresh"), array("update" => "emails", "url" => "/posts/list_emails"))
    #
    # You can also specify a hash for <tt>$options['update']</tt> to allow for
    # easy redirection of output to an other DOM element if a server-side error occurs:
    #
    # Example:
    #  link_to_remote("Delete this post", array(
    #      "url" => array(":action" => "destroy", ":id" => $post->id),
    #      "update" => array("success" => "posts", "failure" => "error")
    #      ))
    #
    # Optionally, you can use the <tt>$options['position']</tt> parameter to influence
    # how the target DOM element is updated. It must be one of 
    # <tt>before</tt>, <tt>top</tt>, <tt>bottom</tt>, or <tt>after</tt>.
    #
    # By default, these remote requests are processed asynchronous during 
    # which various JavaScript callbacks can be triggered (for progress indicators and
    # the likes). All callbacks get access to the <tt>request</tt> object,
    # which holds the underlying XMLHttpRequest. 
    #
    # To access the server response, use <tt>request.responseText</tt>, to
    # find out the HTTP status, use <tt>request.status</tt>.
    #
    # Example:
    #   link_to_remote($word, array(
    #       "url" => array(":action" => "undo", "n" => $word_counter ),
    #       "complete" => "undoRequestCompleted(request)"))
    #
    # The callbacks that may be specified are (in order):
    #
    # <tt>loading</tt>::       Called when the remote document is being 
    #                           loaded with data by the browser.
    # <tt>loaded</tt>::        Called when the browser has finished loading
    #                           the remote document.
    # <tt>interactive</tt>::   Called when the user can interact with the 
    #                           remote document, even though it has not 
    #                           finished loading.
    # <tt>success</tt>::       Called when the XMLHttpRequest is completed,
    #                           and the HTTP status code is in the 2XX range.
    # <tt>failure</tt>::       Called when the XMLHttpRequest is completed,
    #                           and the HTTP status code is not in the 2XX
    #                           range.
    # <tt>complete</tt>::      Called when the XMLHttpRequest is complete 
    #                           (fires after success/failure if they are present).,
    #                     
    # You can further refine <tt>success</tt> and <tt>failure</tt> by adding additional 
    # callbacks for specific status codes:
    #
    # Example:
    #   link_to_remote($word,
    #       "url" => array(":action" => "action"),
    #       "failure" => "alert('HTTP Error ' + request.status + '!')")
    #
    # A status code callback overrides the success/failure handlers if present.
    #
    # If you for some reason or another need synchronous processing (that'll
    # block the browser while the request is happening), you can specify 
    # <tt>$options['type'] = "synchronous"</tt>.
    #
    # You can customize further browser side call logic by passing
    # in JavaScript code snippets via some optional parameters. In
    # their order of use these are:
    #
    # <tt>confirm</tt>::      Adds confirmation dialog.
    # <tt>condition</tt>::    Perform remote request conditionally
    #                          by this expression. Use this to
    #                          describe browser-side conditions when
    #                          request should not be initiated.
    # <tt>before</tt>::       Called before request is initiated.
    # <tt>after</tt>::        Called immediately after request was
    #                          initiated and before <tt>:loading</tt>.
    # <tt>submit</tt>::       Specifies the DOM element ID that's used
    #                          as the parent of the form elements. By 
    #                          default this is the current form, but
    #                          it could just as well be the ID of a
    #                          table row or any other DOM element.
    function link_to_remote($name, $options = array(), $html_options = array()) {
        return $this->link_to_function($name, $this->remote_function($options), $html_options);
    }
    
    # Periodically calls the specified url (<tt>$options['url']</tt>) every <tt>options[:frequency]</tt> seconds (default is 10).
    # Usually used to update a specified div (<tt>$options['update']</tt>) with the results of the remote call.
    # The options for specifying the target with 'url' and defining callbacks is the same as link_to_remote().
    function periodically_call_remote($options = array()) {
        $frequency = $options['frequency'] ? $options['frequency'] : 10; # every ten seconds by default
        $code = "new PeriodicalExecuter(function() {" . $this->remote_function($options) . "}, {$frequency})";
        return $this->javascript_tag($code);
    }
    
    # Returns a form tag that will submit using XMLHttpRequest in the background instead of the regular 
    # reloading POST arrangement. Even though it's using JavaScript to serialize the form elements, the form submission 
    # will work just like a regular submission as viewed by the receiving side (all elements available in $_REQUEST).
    # The options for specifying the target with :url and defining callbacks is the same as link_to_remote().
    #
    # A "fall-through" target for browsers that doesn't do JavaScript can be specified with the :action/:method options on :html
    #
    #   form_remote_tag("html" => array("action" => url_for(":controller" => "some", ":action" => "place")))
    # The Hash passed to the 'html' key is equivalent to the options (2nd) argument in the FormTagHelper::form_tag() method.
    #
    # By default the fall-through action is the same as the one specified in the 'url' (and the default method is 'post').
    function form_remote_tag($options = array()) {
        $options['form'] = true;       
        if (!$options['html']) {
            $options['html'] = array();
        }
        $options['html']['onsubmit'] = $this->remote_function($options) . "; return false;";
        if($options['html']['action']) {
            $url_for_options = $options['html']['action'];
        } else {
            $url_for_options = url_for($options['url']);    
        }
        if(!$options['html']['method']) {
            $options['html']['method'] = "post";
        }
        return $this->tag("form", $options['html'], true);
    }
        
    # Returns a button input tag that will submit form using XMLHttpRequest in the background instead of regular
    # reloading POST arrangement. <tt>$options</tt> argument is the same as in <tt>form_remote_tag()</tt>
    function submit_to_remote($name, $value, $options = array()) {
        if(!isset($options['with'])) {
            $options['with'] = 'Form.serialize(this.form)';
        }
        if(!$options['html']) {
            $options['html'] = array();
        }
        $options['html']['type'] = 'button';
        $options['html']['onclick'] = $this->remote_function($options) . "; return false;";
        $options['html']['name'] = $name;
        $options['html']['value'] = $value;      
        return $this->tag("input", $options['html']);
    }
    
    # Returns a Javascript function (or expression) that'll update a DOM element according to the options passed.
    #
    # * <tt>content</tt>: The content to use for updating. Can be left out if using block, see example.
    # * <tt>action</tt>: Valid options are :update (assumed by default), :empty, :remove
    # * <tt>position</tt> If the :action is :update, you can optionally specify one of the following positions: :before, :top, :bottom, :after.
    #
    # Examples:
    #   javascript_tag(update_element_function(
    #         "products", :position => :bottom, :content => "<p>New product!</p>")) 
    #
    #    replacement_function = update_element_function("products") do 
    #     <p>Product 1</p>
    #     <p>Product 2</p>
    #    end 
    #   javascript_tag(replacement_function) 
    #
    # This method can also be used in combination with remote method call where the result is evaluated afterwards to cause
    # multiple updates on a page. Example:
    #
    #   # Calling view
    #    form_remote_tag(array("url" => array(":action" => "buy"), "complete" => evaluate_remote_response())) 
    #    all the inputs here...
    #
    #   # Controller action
    #   function buy() {
    #       $product = new Product;
    #       $this->product = $product->find(1);
    #   }
    #
    #   # Returning view
    #    update_element_function(
    #         "cart", array(":action" => "update", "position" => "bottom", 
    #         "content" => "<p>New Product: #{$product->name}</p>")) 
    #    update_element_function("status", array("binding" => $binding) do 
    #     You've bought a new product!
    #    end 
    #
    function update_element_function($element_id, $options = array(), $block = null) {   
        $content = $this->escape_javascript(($options['content'] ? $options['content'] : null));
        if(!is_null($block)) {
            $content = $this->escape_javascript($this->capture($block));
        }
        switch((isset($options['action']) ? $options['action'] : 'update')) {
            case 'update':
                if($options['position']) {
                    $javascript_function = "new Insertion." . Inflector::camelize($options['position']) . "('{$element_id}','{$content}')";
                } else {
                    $javascript_function = "$('{$element_id}').innerHTML = '{$content}'";
                }
                break;
            case 'empty':
                $javascript_function = "$('{$element_id}').innerHTML = ''";
                break;
            case 'remove':
                $javascript_function = "Element.remove('{$element_id}')";
                break;
            default:
                $this->controller_object->raise("Invalid action, choose one of 'update', 'remove', 'empty'", "ArgumentError");
        }       
        $javascript_function .= ";\n";
        return ($options['binding'] ? $javascript_function . $options['binding'] : $javascript_function);
    }
    
    # Returns 'eval(request.responseText)' which is the Javascript function that form_remote_tag can call in :complete to
    # evaluate a multiple update return document using update_element_function calls.
    function evaluate_remote_response() {
        return "eval(request.responseText)";
    }
    
    
    /*
    # Returns the javascript needed for a remote function.
    # Takes the same arguments as link_to_remote.
    # 
    # Example:
    #   <select id="options" onchange="<?= remote_function(array("update" => "options", "url" => array(":action" => "update_options"))) ?>">
    #     <option value="0">Hello</option>
    #     <option value="1">World</option>
    #   </select>
    */
    function remote_function($options) {
        $javascript_options = $this->options_for_ajax($options);       
        $update = '';
        if(is_array($options['update'])) {
            $update  = array();
            if(isset($options['update']['success'])) {
                $update[] = "success:'{$options['update']['success']}'";
            }
            if($options['update']['failure']) {
                $update[] = "failure:'{$options['update']['failure']}'";
            }
            $update = '{' . implode(',', $update) . '}';
        } elseif($options['update']) {
            $update .= "'{$options['update']}'";
        }   
            
        $function  = empty($update) ? "new Ajax.Request(" : "new Ajax.Updater({$update}, ";
        $function .= "'" . url_for($options['url']) . "'";
        $function .= ", " . $javascript_options . ")";
        
        if($options['before']) {
            $function = "{$options['before']}; {$function}";
        }
        if($options['after']) { 
            $function = "{$function}; {$options['after']}";
        }
        if($options['condition']) {
            $function = "if ({$options['condition']}) { {$function}; }";
        }
        if($options['confirm']) {
            $function = "if (confirm('" . $this->escape_javascript($options['confirm']) . "')) { {$function}; }";
        }
        return $function;
    }
    
    # Includes the Action Pack JavaScript libraries inside a single <script> 
    # tag. The function first includes prototype.js and then its core extensions,
    # (determined by filenames starting with "prototype").
    # Afterwards, any additional scripts will be included in random order.
    #
    # Note: The recommended approach is to copy the contents of
    # action_view/helpers/javascripts/ into your application's
    # public/javascripts/ directory, and use javascript_include_tag() to 
    # create remote <script> links.
    function define_javascript_functions() {
        $javascript = '<script type="text/javascript">';
        
        # load prototype.js and all .js files
        $prototype_libs = glob($this->javascript_path.'/*.js');
        if(count($prototype_libs)) {
            rsort($prototype_libs);
            foreach($prototype_libs as $filename) { 
                $javascript .= "\n" . file_get_contents($filename);
            }
        }
        
        return $javascript . '</script>';
    }
    
    # Observes the field with the DOM ID specified by +field_id+ and makes
    # an AJAX call when its contents have changed.
    # 
    # Required $options are:
    # <tt>url</tt>::       +url_for+-style options for the action to call
    #                       when the field has changed.
    # 
    # Additional options are:
    # <tt>frequency</tt>:: The frequency (in seconds) at which changes to
    #                       this field will be detected. Not setting this
    #                       option at all or to a value equal to or less than
    #                       zero will use event based observation instead of
    #                       time based observation.
    # <tt>update</tt>::    Specifies the DOM ID of the element whose 
    #                       innerHTML should be updated with the
    #                       XMLHttpRequest response text.
    # <tt>with</tt>::      A JavaScript expression specifying the
    #                       parameters for the XMLHttpRequest. This defaults
    #                       to 'value', which in the evaluated context 
    #                       refers to the new field value.
    #
    # Additionally, you may specify any of the options documented in
    # link_to_remote().
    function observe_field($field_id, $options = array()) {
        if($options['frequency'] > 0) {
            return $this->build_observer('Form.Element.Observer', $field_id, $options);
        } else {
            return $this->build_observer('Form.Element.EventObserver', $field_id, $options);
        }
    }
    
    # Like observe_field(), but operates on an entire form identified by the
    # DOM ID $form_id. $options are the same as observe_field(), except 
    # the default value of the <tt>with</tt> option evaluates to the
    # serialized (request string) value of the form.
    function observe_form($form_id, $options = array()) {
        if($options['frequency']) {
            return $this->build_observer('Form.Observer', $form_id, $options);
        } else {
            return $this->build_observer('Form.EventObserver', $form_id, $options);
        }
    }
    
    # Returns a JavaScript snippet to be used on the AJAX callbacks for starting
    # visual effects.
    #
    # This method requires the inclusion of the script.aculo.us JavaScript library.
    #
    # Example:
    #   link_to_remote("Reload", array("update" => "posts", 
    #         "url" => array(":action" => "reload"), 
    #         "complete" => visual_effect("highlight", "posts", array("duration" => 0.5)))
    #
    # If no element_id is given, it assumes "element" which should be a local
    # variable in the generated JavaScript execution context. This can be used
    # for example with drop_receiving_element:
    #
    #   drop_receving_element (...), "loading" => visual_effect("fade")
    #
    # This would fade the element that was dropped on the drop receiving element.
    #
    # You can change the behaviour with various options, see
    # http://script.aculo.us for more documentation.
    function visual_effect($name, $element_id = false, $js_options = array()) {
        $element = ($element_id ? "'{$element_id}'" : "element");
        if($js_options['queue']) {
            $js_options['queue'] = "'{$js_options['queue']}'";
        }
        return "new Effect." . Inflector::camelize($name) . "({$element}," . $this->options_for_javascript($js_options) . ");";
    }
    
    # Makes the element with the DOM ID specified by +element_id+ sortable
    # by drag-and-drop and make an AJAX call whenever the sort order has
    # changed. By default, the action called gets the serialized sortable
    # element as parameters.
    #
    # This method requires the inclusion of the script.aculo.us JavaScript library.
    #
    # Example:
    #    sortable_element("my_list", array("url" => array(":action" => "order"))) 
    #
    # In the example, the action gets a "my_list" array parameter 
    # containing the values of the ids of elements the sortable consists 
    # of, in the current order.
    #
    # You can change the behaviour with various options, see
    # http://script.aculo.us for more documentation.
    function sortable_element($element_id, $options = array()) {
        if(!$options['with']) {
            $options['with'] = "Sortable.serialize('{$element_id}')";
        }
        if(!$options['onUpdate']) {
            $options['onUpdate'] = "function(){" . $this->remote_function($options) . "}";
        }
        $options = $this->remove_ajax_options($options);
        foreach(array('tag', 'overlap', 'constraint', 'handle') as $option) {
            if($options[$option]) {
                $options[$option] = "'{$options[$option]}'";
            }
        }
        
        if($options['containment']) {
            $options['containment'] = $this->array_or_string_for_javascript($options['containment']);
        }
        if($options['only']) {
            $options['only'] = $this->array_or_string_for_javascript($options['only']);
        }
        return $this->javascript_tag("Sortable.create('{$element_id}', " . $this->options_for_javascript($options) . ")");
    }
    
    # Makes the element with the DOM ID specified by $element_id draggable.
    #
    # This method requires the inclusion of the script.aculo.us JavaScript library.
    #
    # Example:
    #    draggable_element("my_image", array("revert" => true))
    # 
    # You can change the behaviour with various options, see
    # http://script.aculo.us for more documentation. 
    function draggable_element($element_id, $options = array()) {
        return $this->javascript_tag("new Draggable('{$element_id}', " . $this->options_for_javascript($options) . ")");
    }
    
    # Makes the element with the DOM ID specified by $element_id receive
    # dropped draggable elements (created by draggable_element).
    # and make an AJAX call  By default, the action called gets the DOM ID of the
    # element as parameter.
    #
    # This method requires the inclusion of the script.aculo.us JavaScript library.
    #
    # Example:
    #    drop_receiving_element("my_cart", array("url" => array(":controller" => "cart", ":action" => "add"))) 
    #
    # You can change the behaviour with various options, see
    # http://script.aculo.us for more documentation.
    function drop_receiving_element($element_id, $options = array()) {
        if(!$options['with']) {
            $options['with'] = "'id=' + encodeURIComponent(element.id)";
        }
        if(!$options['onUpdate']) {
            $options['onUpdate'] = "function(element){" . $this->remote_function($options) . "}";
        }
        $options = $this->remove_ajax_options($options);
        if($options['accept']) {
            $options['accept'] = $this->array_or_string_for_javascript($options['accept']);  
        }  
        if($options['hoverclass']) {
            $options['hoverclass'] = "'{$options['hoverclass']}'";
        }
        return $this->javascript_tag("Droppables.add('{$element_id}', " . $this->options_for_javascript($options) . ")");
    }
    
    # Escape carrier returns and single and double quotes for JavaScript segments.
    function escape_javascript($javascript) {
        $escape = array(
            "\r\n"  => '\n',
            "\r"    => '\n',
            "\n"    => '\n',
            '"'     => '\"',
            "'"     => "\\'"
        );
        return str_replace(array_keys($escape), array_values($escape), $javascript); 
        #return preg_replace('/\r\n|\n|\r/', "\\n",
        #       preg_replace_callback('/["\']/', create_function('$m', 'return "\\{$m}";'),
        #       (!is_null($javascript) ? $javascript : '')));
    }
    
    # Returns a JavaScript tag with the $content inside. Example:
    #   javascript_tag("alert('All is good')") => <script type="text/javascript">alert('All is good')</script>
    function javascript_tag($content) {
        return $this->content_tag("script", $this->javascript_cdata_section($content), array('type' => "text/javascript"));
    }
    
    function javascript_cdata_section($content) {
        return "\n//" . $this->cdata_section("\n{$content}\n//") . "\n";
    }
    
}


/**
  *  Avialble functions for use in views
  *  link_to_remote($name, $options = array(), $html_options = array())
  */
function link_to_remote() {
    $javascript_helper = new JavaScriptHelper();
    $args = func_get_args();
    return call_user_func_array(array($javascript_helper, 'link_to_remote'), $args);
}

/**
  *  link_to_function($name, $function, $html_options = array())
  */
function link_to_function() {
    $javascript_helper = new JavaScriptHelper();
    $args = func_get_args();
    return call_user_func_array(array($javascript_helper, 'link_to_function'), $args);
}


/**
  *  periodically_call_remote($options = array())
  */
function periodically_call_remote() {
    $javascript_helper = new JavaScriptHelper();
    $args = func_get_args();
    return call_user_func_array(array($javascript_helper, 'periodically_call_remote'), $args);
}

/**
  *  form_remote_tag($options = array())
  */
function form_remote_tag() {
    $javascript_helper = new JavaScriptHelper();
    $args = func_get_args();
    return call_user_func_array(array($javascript_helper, 'form_remote_tag'), $args);
} 

/**
  *  submit_to_remote($name, $value, $options = array())
  */
function submit_to_remote() {
    $javascript_helper = new JavaScriptHelper();
    $args = func_get_args();
    return call_user_func_array(array($javascript_helper, 'submit_to_remote'), $args);
} 

/**
  *  update_element_function($element_id, $options = array(), $block = null)
  */
function update_element_function() {
    $javascript_helper = new JavaScriptHelper();
    $args = func_get_args();
    return call_user_func_array(array($javascript_helper, 'update_element_function'), $args);
} 

/**
  *  evaluate_remote_response()
  */
function evaluate_remote_response() {
    $javascript_helper = new JavaScriptHelper();
    $args = func_get_args();
    return call_user_func_array(array($javascript_helper, 'evaluate_remote_response'), $args);
} 

/**
  *  remote_function($options)
  */
function remote_function() {
    $javascript_helper = new JavaScriptHelper();
    $args = func_get_args();
    return call_user_func_array(array($javascript_helper, 'remote_function'), $args);
} 

/**
  *  observe_field($field_id, $options = array())
  */
function observe_field() {
    $javascript_helper = new JavaScriptHelper();
    $args = func_get_args();
    return call_user_func_array(array($javascript_helper, 'observe_field'), $args);
} 

/**
  *  observe_form($form_id, $options = array())
  */
function observe_form() {
    $javascript_helper = new JavaScriptHelper();
    $args = func_get_args();
    return call_user_func_array(array($javascript_helper, 'observe_form'), $args);
} 

/**
  *  visual_effect($name, $element_id = false, $js_options = array())
  */
function visual_effect() {
    $javascript_helper = new JavaScriptHelper();
    $args = func_get_args();
    return call_user_func_array(array($javascript_helper, 'visual_effect'), $args);
} 

/**
  *  sortable_element($element_id, $options = array())
  */
function sortable_element() {
    $javascript_helper = new JavaScriptHelper();
    $args = func_get_args();
    return call_user_func_array(array($javascript_helper, 'sortable_element'), $args);
} 

/**
  *  draggable_element($element_id, $options = array())
  */
function draggable_element() {
    $javascript_helper = new JavaScriptHelper();
    $args = func_get_args();
    return call_user_func_array(array($javascript_helper, 'draggable_element'), $args);
}

/**
  *  drop_receiving_element($element_id, $options = array()) 
  */
function drop_receiving_element() {
    $javascript_helper = new JavaScriptHelper();
    $args = func_get_args();
    return call_user_func_array(array($javascript_helper, 'drop_receiving_element'), $args);
}

/**
  *  escape_javascript($javascript)
  */
function escape_javascript() {
    $javascript_helper = new JavaScriptHelper();
    $args = func_get_args();
    return call_user_func_array(array($javascript_helper, 'escape_javascript'), $args);
}

/**
  *  javascript_tag($content)
  */
function javascript_tag() {
    $javascript_helper = new JavaScriptHelper();
    $args = func_get_args();
    return call_user_func_array(array($javascript_helper, 'javascript_tag'), $args);
}

/**
  *  javascript_cdata_section($content)
  */
function javascript_cdata_section() {
    $javascript_helper = new JavaScriptHelper();
    $args = func_get_args();
    return call_user_func_array(array($javascript_helper, 'javascript_cdata_section'), $args);
}

?>
