<?

class Haml {

    private $parser = null;
    private $context = null;

    function __construct($context) {
        if(file_exists(Trax::$vendor_path."/phphaml/includes/haml/HamlParser.class.php")) {
            $haml_compile_path = Trax::$tmp_path."/haml";
            if(!is_dir($haml_compile_path)) {
                exec("mkdir -p $haml_compile_path");
            }
            include_once(Trax::$vendor_path."/phphaml/includes/haml/HamlParser.class.php");
            HamlParser::$bRegisterSass = false;
            $this->parser = new HamlParser(false, $haml_compile_path);
            $this->context = $context;
        } else {
            $context->raise("Missing phphaml in ".Trax::$vendor_path."<br />Please run 'git clone git@github.com:phpontrax/phphaml.git' from inside your apps vendor folder.", "HAML parser", "500");
        }
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

    function render($path, $locals = array()) {
        if(count($locals)) {
            foreach($locals as $key => $value) {
                $this->parser->assign($key, $value);
            }
        }
        $this->parser->assign("controller", Trax::$current_controller_name);
        $this->parser->assign("action", Trax::$current_action_name);
        echo $this->parser->display($path);
    }

}

?>