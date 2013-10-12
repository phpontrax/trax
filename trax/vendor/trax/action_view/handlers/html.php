<?

class Html {

    private $context = null;

    function __construct($context) {
        $this->context = $context;
    }

    function render($path, $locals = array()) {
        if(count($locals)) {
            foreach($locals as $key => $value) {
                ${$key} = $value;
            }
        }
        include($path);
    }

    function render_partial() {
        if(is_object(Trax::$current_controller_object)) {
            return call_user_func_array(array(Trax::$current_controller_object, "render_partial"), func_get_args());
        }
        return null;
    }

}

?>