<?php

class RequireSelf extends Directives {

    public function process() {
        if($this->added("require_self")) {
            return $this->files;
        }
        return array($this->get_include_path(basename($this->manifest_file)));
    }

}

?>