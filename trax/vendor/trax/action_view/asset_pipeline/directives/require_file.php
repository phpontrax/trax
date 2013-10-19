<?php

class RequireFile extends Directives {

    public function process($filename) {
        if($this->added("require $filename")) {
            return $this->files;
        }
        return array($this->get_include_path($filename));
    }

}

?>