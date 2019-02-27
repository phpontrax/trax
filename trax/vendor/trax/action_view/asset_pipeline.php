<?

include_once(TRAX_LIB_ROOT."/action_view/asset_pipeline/directives.php");
include_once(TRAX_LIB_ROOT."/action_view/asset_pipeline/compiler.php");

class AssetPipeline {

    private $manifest_file = null;

    public function __construct($context = null) {
        $this->context = $context;
    }

    public function compile() {
        return Trax::$assets_compile;
    }

    public function compress() {
        return Trax::$assets_compress;
    }

    public function files_for_stylesheet_link_tag() {
        $css_urls = array();
        if($css_files = $this->get_files_from_manifest(Trax::$assets_path."/stylesheets/application.css")) {
            self::reset_assets_dir("stylesheets");
            $compiled_css = array();
            foreach($css_files as $file) {
                if($this->compress()) {
                    # write files to disk compressed
                    $compiler = new Compiler($file);
                    $compiled_css[$file] = $compiler->process_file();
                } else {
                    # copy to assets folder
                    $css_parts = explode("/assets/stylesheets/", $file);
                    $css_urls[] = $this->public_url($css_parts[1]);
                    $this->copy_file($file);
                }
            }
            if($this->compress()) {
                $fingerprint = self::fingerprint($css_files);
                $filename = "application-{$fingerprint}.css";
                if($this->write_file($filename, implode("\n", $compiled_css))) {
                    $css_urls = array($this->public_url($filename));
                }
            }
        }
        #print_r($css_urls);
        return $css_urls;
    }

    public function get_files_from_manifest($manifest_file) {
        $this->manifest_file = $manifest_file;
        $file_list = array();
        $lines = ($manifest_file) ? file($manifest_file) : array();
        foreach($lines as $line) {
            $files = $this->find_files_from_directive($line);
            if($files) {
                $file_list = array_merge($file_list, $files);
            }
        }
        return array_unique($file_list);
    }

    private function find_files_from_directive($line) {
        if(!$line = ltrim($line)) {
            return false;
        }
        foreach(array('//=', '*=') as $token) {
            if(strpos($line, $token) === 0) {
                $directive = trim(substr($line, strlen($token)));
                return $this->process_directive($directive);
            }
        }
        return false;
    }

    private function process_directive($line) {
        $directives = array(
            'require ' => new RequireFile($this->manifest_file),
            'require_directory' => new RequireDirectory($this->manifest_file),
            'require_tree' => new RequireTree($this->manifest_file),
            'require_self' => new RequireSelf($this->manifest_file)
        );
        foreach($directives as $directive_name => $directive) {
            $param = $this->get_directive_param($directive_name, $line);
            if(!is_null($param)) {
                return $directive->process($param);
            }
        }
        $directive = new Directive($this->manifest_file);
        return $directive->process_basic($line);
    }

    private function get_directive_param($directive_name, $directive) {
        if(strpos($directive, $directive_name) === 0) {
            $param = trim(substr($directive, strlen($directive_name)));
            return ($param) ? $param : true;
        }
        return null;
    }

    private function fingerprint($content) {
        if(is_array($content)) {
            $string = implode("", $content);
        } else {
            $string = $content;
        }
        return md5($string);
    }

    private function reset_assets_dir($directory) {
        $directory = Trax::$public_path."/assets/$directory";
        if(is_dir($directory)) {
            exec("rm -rf ".$directory);
        }
        #echo "creating dir: ".$directory;
        exec("mkdir -p ".$directory);
    }

    private function write_file($filename, $data) {
        $filename = $this->public_filename($filename);
        $this->ensure_path_exists($filename);
        return file_put_contents($filename, $data);
    }

    private function copy_file($source) {
        $destination = $this->public_filename($source);
        $this->ensure_path_exists($destination);
        if(stristr($source, 'application')) {
            $data = file_get_contents($source);
            $data = preg_replace('/^(.*)\*\=.*?(?:\n|$)/m', '${1}*'."\n", $data);
            return file_put_contents($destination, $data);
        } else {
            return copy($source, $destination);
        }
    }

    private function public_filename($filename) {
        $type = $this->include_path_type($filename);
        $extension = $type == 'stylesheets' ? 'css' : 'js';
        $filename_parts = explode("/assets/$type/", $filename);
        $filename = $filename_parts[1] ? $filename_parts[1] : $filename_parts[0];
        $file_info = pathinfo(Trax::$public_path."/assets/$type/$filename");
        return $file_info['dirname']."/".$file_info['filename'].".".$extension;
    }

    private function public_url($filename) {
        $type = $this->include_path_type($filename);
        $extension = $type == 'stylesheets' ? 'css' : 'js';
        $filename_parts = explode("/assets/$type/", $filename);
        $file_info = pathinfo($filename_parts[1] ? $filename_parts[1] : $filename_parts[0]);
        $dirname = '';
        if(!in_array($file_info['dirname'], array(".","",null))) {
            $dirname = $file_info['dirname']."/";
        }
        return "assets/$type/".$dirname.$file_info['filename'].".".$extension;
    }

    private function ensure_path_exists($filename) {
        $path = pathinfo($filename, PATHINFO_DIRNAME);
        if(!is_dir($path)) {
            exec("mkdir -p $path");
        }
    }

    public function include_path_type($filename) {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        if($extension == 'js' || strpos('.js', $filename) !== false) {
            return 'javascripts';
        } elseif(in_array($extension, array('css', 'less', 'scss')) || strpos('.css', $filename) !== false) {
            return 'stylesheets';
        }
        return '';
    }

}

?>
