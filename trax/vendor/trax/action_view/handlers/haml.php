<?

class Haml extends HandlerBase {

    private $parser = null;

    function __construct() {
        if(file_exists(Trax::$vendor_path."/phphaml/includes/haml/HamlParser.class.php")) {
            $haml_compile_path = Trax::$tmp_path."/haml";
            if(!is_dir($haml_compile_path)) {
                exec("mkdir -p $haml_compile_path");
            }
            include_once(Trax::$vendor_path."/phphaml/includes/haml/HamlParser.class.php");
            HamlParser::$bRegisterSass = false;
            $this->parser = new HamlParser(false, $haml_compile_path);
        } else {
            Trax::$current_controller_object->raise("Missing phphaml in ".Trax::$vendor_path."<br />Please run 'git clone git@github.com:phpontrax/phphaml.git' from inside your apps vendor folder.", "HAML parser", "500");
        }
    }

    function render($path, $locals = array()) {
        if(count($locals)) {
            foreach($locals as $key => $value) {
                $this->parser->assign($key, $value);
            }
        }
        echo $this->parser->display($path);
    }

}

?>