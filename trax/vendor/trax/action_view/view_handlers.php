<?

class ViewHandlers {

    private static $extensions = array();

    static function register_extension($extension) {
        if(!in_array($extension, self::$extensions)) {
            if(file_exists(TRAX_LIB_ROOT."/action_view/handlers/{$extension}.php")) {
                include_once(TRAX_LIB_ROOT."/action_view/handlers/{$extension}.php");
                array_unshift(self::$extensions, $extension);
                Trax::$views_extension = $extension;
            }
        }
    }

    static function extensions() {
        return self::$extensions;
    }

    static function render($context, $path, $locals = array()) {
        if($view_path = self::view_path($path)) {
            $class = Inflector::camelize(self::file_extension($view_path));
            $view_handler = new $class($context);
            $view_handler->render($view_path, $locals);
            return true;
        } else {
            $context->raise("Missing template '".self::file_name($path)."' with handlers (".implode(",", self::$extensions)."). Searched in ".self::file_path($path), "Template is missing", "404");
        }
        return false;
    }

    static function view_path($path) {
        $path_info = pathinfo($path);
        $view_path = "{$path_info['dirname']}/{$path_info['filename']}";
        $view_extension = $path_info['extension'];
        foreach(self::extensions() as $extension) {
            $view_file = "{$view_path}.{$extension}";
            if(file_exists($view_file)) {
                return  $view_file;
            }
        }
        return false;
    }

    static function file_extension($path) {
        $path_info = pathinfo($path);
        return $path_info['extension'];
    }

    static function file_name($path) {
        $path_info = pathinfo($path);
        return $path_info['filename'];
    }

    static function file_path($path) {
        $path_info = pathinfo($path);
        return $path_info['dirname'];
    }

}

?>