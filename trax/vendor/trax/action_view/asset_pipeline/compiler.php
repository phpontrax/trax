<?
class Compiler {

    private $compilers = array(
        'less' => 'Less',
        'scss' => 'Scss',
        'js' => 'Js'
    );
    protected $parser = null;

    public function __construct($source) {
        $this->source = $source;
        $this->extention = pathinfo($this->source, PATHINFO_EXTENSION);
    }

    public function process_file() {
        if(file_exists($this->source) && $this->extention != 'css') {
            $compiler = $this->get_compiler();
            return $compiler->process($this->source);
        }
        return '';
    }

    private function get_compiler() {
        if(array_key_exists($this->extention, $this->compilers)) {
            $class = $this->compilers[$this->extention];
            $compiler_file = TRAX_LIB_ROOT."/action_view/asset_pipeline/compilers/".strtolower($class).".php";
            if(file_exists($compiler_file)) {
                include_once($compiler_file);
                return new $class();
            }
        }
        Trax::$current_controller_object->raise("No compiler for for file with extention: {$this->extention}. Valid extentions are '".implode(",", array_keys($this->compilers))."'", "Asset Compiler Missing", "500");
    }

}

?>