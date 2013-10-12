<?

class Html {

    private $context = null;

    function __construct($context) {
        $this->context = $context;
    }

    function render($path, $locals = array()) {
        if(count($locals)) {
            foreach($locals as $key => $value) {
                ${$key} = $value;
            }
        }
        include($path);
    }

}

?>