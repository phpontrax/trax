<?

include_once(TRAX_LIB_ROOT."/action_view/asset_pipeline/directives/require_directory.php");
include_once(TRAX_LIB_ROOT."/action_view/asset_pipeline/directives/require_file.php");
include_once(TRAX_LIB_ROOT."/action_view/asset_pipeline/directives/require_self.php");
include_once(TRAX_LIB_ROOT."/action_view/asset_pipeline/directives/require_tree.php");

class Directives {

    protected $files = array();
    protected $manifest_file = null;

    public function __construct($manifest_file) {
        $this->manifest_file = $manifest_file;
    }

    public function add($file) {
        $this->files[] = $file;
    }

    public function added($line) {
        $this->line = $line;
        $name = explode(' ', $this->line);
        $this->name = ($name) ? $name[0] : 'unknown';
        $param = strstr($this->line, " ");
        $this->param = ($param) ? substr($param, 1) : $param;
        return count($this->files) > 0;
    }

    public function process_basic($line) {
        if($this->added($line)) {
            return $this->files;
        }
    }

    public function get_include_path($filename) {
        return Trax::$assets_path."/".$this->include_path_type()."/".$filename;
    }

    public function get_files_in_folder($directory, $recursive = false, $level = 0) {
        $type = $this->include_path_type();
        if($type = 'stylesheets') {
            $search_extentions = array("less","scss","css");
        } elseif($type == 'javascripts') {
            $search_extentions = array("js");
        }
        $files = array();
        if(substr($directory, -1) == ".") {
            $directory = substr($directory, 0, -1);
        }
        if(substr($directory, 0, 1) != "/") {
            $directory = Trax::$assets_path."/".$type."/".$directory;
        }
        if(substr($directory, -1) == "/") {
            $directory = substr($directory, 0, -1);
        }
        if(is_dir($directory)) {
            foreach(glob("$directory/*") as $file) {
                if($file == ".." || $file == ".") {
                    continue;
                }
                if(is_dir($file)) {
                    if($recursive) {
                        foreach($this->get_files_in_folder($file, true, ++$level) as $nested_file) {
                            $files[] = $nested_file;
                        }
                    }
                } elseif(in_array($this->file_extention($file), $search_extentions)) {
                    $files[] = $file;
                }
            }
        }
        return array_unique($files);
    }

    protected function file_extention($file) {
        return pathinfo($file, PATHINFO_EXTENSION);
    }

    protected function include_path_type() {
        return AssetPipeline::include_path_type($this->manifest_file);
    }

}

?>