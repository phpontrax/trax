<?php

class RequireTree extends Directives {

    public function process($directory) {
        if($this->added("require_tree $directory")) {
            return $this->files;
        }
        if(strpos($directory, '/') === 0) {
            Trax::$current_controller_object->raise("Directory cannot start with a /", "Asset Pipeline Directive Error", "500");
        }
        if(str_replace('..', '', $directory) !== $directory) {
            Trax::$current_controller_object->raise("Directory cannot have relative paths like .. in it", "Asset Pipeline Directive Error", "500");
        }
        return $this->get_files_in_folder($directory, true);
    }

}

?>