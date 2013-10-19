<?

class Scss {

    function __construct() {
        if(file_exists(Trax::$vendor_path."/scssphp/scss.inc.php")) {
            include_once(Trax::$vendor_path."/scssphp/scss.inc.php");
            $this->parser = new scssc;
        } else {
            Trax::$current_controller_object->raise("Missing scssphp in ".Trax::$vendor_path."<br />Please run 'git clone git@github.com:phpontrax/scssphp.git' from inside your apps vendor folder.", "SASS parser", "500");
        }
    }

    function process($input) {
        return $this->parser->compile(file_get_contents($input));
    }

}

?>