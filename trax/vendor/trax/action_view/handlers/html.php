<?

class Html extends HandlerBase {

    function render($path, $locals = array()) {
        if(count((array)$locals)) {
            foreach($locals as $key => $value) {
                ${$key} = $value;
            }
        }
        include($path);
    }

}

?>