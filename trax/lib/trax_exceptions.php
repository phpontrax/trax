<?
# Trax Exception Handling Classes

# Trax base class for Exception handling
class TraxError extends Exception {
    public function __construct($message, $heading, $code = "500") {
        parent::__construct($message, $code);
        $this->error_heading = $heading;
        $this->error_message = $message;
        $this->error_code = $code;
    }     
}

# Active Record's Exception handling class
class ActiveRecordError extends TraxError {}

# Action Controller's Exception handling class
class ActionControllerError extends TraxError {}

?>