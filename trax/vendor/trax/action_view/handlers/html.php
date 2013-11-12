<?

class Html {

    private $context = null;

    function __construct($context) {
        $this->context = $context;
    }

    function __call($method_name, $parameters) {
        if(method_exists($this, $method_name)) {
            # If the method exists, just call it
            $result = call_user_func_array(array($this, $method_name), $parameters);
        } elseif(method_exists(Trax::$current_controller_object, $method_name)) {
            $result = call_user_func_array(array(Trax::$current_controller_object, $method_name), $parameters);
        }
        return $result;
    }

    function __get($key) {
        if(property_exists($this, $key)) {
            return $this->$key;
        } elseif(property_exists(Trax::$current_controller_object, $key)) {
            return Trax::$current_controller_object->{$key};
        }
    }

    function render($path, $locals = array()) {
        if(count($locals)) {
            foreach($locals as $key => $value) {
                ${$key} = $value;
            }
        }
        $controller = Trax::$current_controller_name;
        $action = Trax::$current_action_name;
        include($path);
    }

    function render_partial() {
        if(is_object(Trax::$current_controller_object)) {
            return call_user_func_array(array(Trax::$current_controller_object, "render_partial"), func_get_args());
        }
        return null;
    }

    function content_for() {
        if(is_object(Trax::$current_controller_object)) {
            return call_user_func_array(array(Trax::$current_controller_object, "content_for"), func_get_args());
        }
        return null;
    }

    function end_content_for() {
        if(is_object(Trax::$current_controller_object)) {
            return call_user_func_array(array(Trax::$current_controller_object, "end_content_for"), func_get_args());
        }
        return null;
    }

}

?>