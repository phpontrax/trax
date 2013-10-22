<?

class Haml {

    private $parser = null;
    private $context = null;

    function __construct($context) {
        if(file_exists(Trax::$vendor_path."/phphaml/includes/haml/HamlParser.class.php")) {
            include_once(Trax::$vendor_path."/phphaml/includes/haml/HamlParser.class.php");
            HamlParser::$bRegisterSass = false;
            $this->parser = new HamlParser(false, Trax::$tmp_path."/haml");
            $this->context = $context;
        } else {
            $context->raise("Missing phphaml in ".Trax::$vendor_path."<br />Please run 'git clone git@github.com:phpontrax/phphaml.git' from inside your apps vendor folder.", "HAML parser", "500");
        }
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