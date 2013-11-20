<?
class HandlerBase {

    protected $context = null;
    protected static $vars = array();

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
        if(in_array($key, self::$vars)) {
            return self::$vars[$key];
        } elseif(property_exists($this, $key)) {
            return $this->$key;
        } elseif(property_exists(Trax::$current_controller_object, $key)) {
            return Trax::$current_controller_object->{$key};
        }
    }

    function __set($key, $value) {
        self::$vars[$key] = $value;
        $this->{$key} = $value;
    }

    function render($path, $locals = array()) {
        echo "Please implement me in child class!";
    }
}
?>