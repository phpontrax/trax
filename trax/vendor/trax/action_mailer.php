<?php
/**
 *  File for ActionMailer class
 *
 *  (PHP 5)
 *
 *  @package PHPonTrax
 *  @version $Id$
 *  @copyright (c) 2005 John Peterson
 *
 *  Permission is hereby granted, free of charge, to any person obtaining
 *  a copy of this software and associated documentation files (the
 *  "Software"), to deal in the Software without restriction, including
 *  without limitation the rights to use, copy, modify, merge, publish,
 *  distribute, sublicense, and/or sell copies of the Software, and to
 *  permit persons to whom the Software is furnished to do so, subject to
 *  the following conditions:
 *
 *  The above copyright notice and this permission notice shall be
 *  included in all copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 *  EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 *  MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 *  NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 *  LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 *  OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 *  WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 *
 */
include_once( "Mail.php" );
include_once( "Mail/mime.php" );

/**
 *
 *  @package PHPonTrax
 */
class ActionMailer {

    private
        $mail_mime = null,  # Mail_mime object
        $mail = null, # Mail object to deliver mail
        $mime_params = array(), # params for charset
        $errors = array(); # array to hold any errors
    public
        $smtp_settings = array( 
            'host'      => 'localhost', # The server to connect.
            'port'      => 25,  # The port to connect.
            'persist'   => false, # Indicates whether or not the SMTP connection should persist over multiple sends.
            'auth'      => false, # Whether or not to use SMTP authentication.
            'username'  => null, # The username to use for SMTP authentication.
            'password'  => null, # The password to use for SMTP authentication.            
        ),
        $sendmail_settings = array(
            'path' => '/usr/sbin/sendmail',
            'args' => '-i -t'
        ),
        $delivery_method = "mail", # mail | sendmail | smtp | test
        $perform_deliveries = true, # true will attempt to deliver mail | false will not deliver mail
        $default_charset = "utf-8", # default charset for email.
        $head_charset = null, # charset for email headers.
        $html_charset = null, # charset for html email body.
        $text_charset = null, # charset for text email body.
        $template = null, # view file to use for body of email.
        $template_root = null, # template_root determines the base from which template references will be made.
        $deliveries = array(), # if delivery_method is "test" it will not deliver but store emails in this array.
        $recipients = null, # to address(es)
        $subject = null, # email subject
        $from = null, # from email address
        $default_from = null, # if no from specified use this as the from.
        $body = array(), # array set in child class of values for view template.
        $preparse_body = array(), # holder for orginal body array from child class
        $headers = array(), # email headers
        $crlf = "\r\n", # linefeed char
        $content_type = "text"; # text | html | both

    /**
     *  ActionMailer constructor.
     *  @todo Document this API
     */ 
    function __construct() {
        $this->mail_mime = new Mail_mime($this->crlf);               
    }

    /**
     *  Override call() to do some magic where you can call create_*() and deliver_*().
     *  @todo Document this API
     */     
    function __call($method_name, $parameters) {
        if(method_exists($this, $method_name)) {
            # If the method exists, just call it
            $result = call_user_func_array(array($this,$method_name), $parameters);
        } else {
            if(preg_match("/^create_([_a-z]\w*)/", $method_name, $matches)) {
                $result = $this->create_mail($matches[1], $parameters);
            } elseif(preg_match("/^deliver_([_a-z]\w*)/", $method_name, $matches)) {              
                $result = $this->create_mail($matches[1], $parameters);
                if(!$this->deliver($result)) {
                    $result = false;    
                }
            }
        }
        return $result;
    }

    /**
     *  Set all the necessary email headers
     *  @todo Document this API
     */     
    private function set_headers() {
        if(!is_null($this->recipients)) {
            $recipients = $this->format_emails($this->recipients, "To");
            $this->set_header_line("To", $recipients);            
        }
        
        if(!is_null($this->cc)) {
            $cc = $this->format_emails($this->cc, "Cc");
            $this->set_header_line("Cc", $cc);            
        }

        if(!is_null($this->bcc)) {
            $bcc = $this->format_emails($this->bcc, "Bcc");
            $this->set_header_line("Bcc", $bcc);            
        }

        if(!is_null($this->reply_to)) {
            $reply_to = $this->format_emails($this->reply_to, "Reply-To");
            $this->set_header_line("Reply-To", $reply_to);            
        }
        
        if(is_null($this->from) || $this->from == '') {
            $this->from = $this->default_from;            
        } 
        $from = $this->format_emails($this->from, "From");
        $this->set_header_line("From", $from);
        
        if(!is_null($this->subject))  {
            $this->set_header_line("Subject", ereg_replace("[\n\r]", "", $this->subject));
        } else {
            $this->set_header_line("Subject", "");
        }

        if(!array_key_exists("Date", $this->headers)) {
            $this->set_header_line("Date", date("r"));
        }
        
        if(!array_key_exists("Return-Path", $this->headers) && !is_null($this->from_address)) {
            $this->set_header_line("Return-Path", $this->from_address);
        }  
          
        if(!array_key_exists("Reply-To", $this->headers) && !is_null($this->from_address)) {
            $this->set_header_line("Reply-To", $this->from_address);            
        }
    }

    /**
     *  Format an array of emails into a correct string / validate emails.
     *  @todo Document this API
     */    
    private function format_emails($emails, $type = null) {
     
        $email_addresses = null;     
        if(!is_null($emails) && is_string($emails)) {
            if(strstr($emails, ",")) {
                $emails = explode(",", $emails);        
            } else {
                $emails = array($emails);    
            }    
        }        
        if(is_array($emails)) {
            foreach($emails as $email) {
                if($this->validate_email($email)) {
                    $email_addresses[] = $email;
                } else {
                    if($type) {
                        $type = "$type ";    
                    }
                    $this->errors[] = "Invalid ".$type."email address: ".$email;    
                }
            }
            if(is_array($email_addresses)) {
                $email_addresses = implode(",", $email_addresses);
            }                     
        }      

        return $email_addresses;           
    }

    /**
     *  Set the text body of the email.
     *  @todo Document this API
     */    
    private function set_text_body($text) {
        if(strlen($text) > 0) {
            $this->mail_mime->setTxtBody($text);
        }
    }

    /**
     *  Set the html body of the email.
     *  @todo Document this API
     */
    private function set_html_body($html) {
        if(strlen($html) > 0) {
            $this->mail_mime->setHTMLBody($html);
        }
    }    

    /**
     *  Sets up default class variables for this mailer.  Classes extending
     *  ActionMailer can override these values.
     *  @todo Document this API
     */
    private function initialize_defaults($method_name) {       
        $this->template_root = Trax::$views_path;
        $this->template_path = "{$this->template_root}/".Inflector::underscore(get_class($this));
        $this->template = $this->template ? $this->template : $method_name;        
        $this->headers = $this->headers ? $this->headers : array();
        $this->body = $this->body ? $this->body : array();
        $this->default_from = "nobody@".$_SERVER['HTTP_HOST'];
        $this->head_charset = $this->head_charset ? $this->head_charset : $this->default_charset;
        $this->html_charset = $this->html_charset ? $this->html_charset : $this->default_charset;
        $this->text_charset = $this->text_charset ? $this->text_charset : $this->default_charset;        
    }

    /**
     *  Sets up and creates the email for deliver().
     *  @todo Document this API
     */
    private function create_mail($method_name, $parameters = array()) {  
        $this->initialize_defaults($method_name);
        if(method_exists($this, $method_name)) {
            //echo "calling $method_name<br>";
            call_user_func_array(array($this, $method_name), $parameters);   
        } 
        $this->mime_params = array(
            'head_charset' => $this->head_charset, 
            'html_charset' =>  $this->html_charset,
            'text_charset' =>  $this->text_charset
        );            
        $this->set_headers();
        $body = $this->preparse_body = $this->body;
        if(!is_string($body)) {
            $body = $this->render_message($method_name, $body);
        }
        if($this->content_type == "html") {
            $this->set_html_body($body);       
        } elseif($this->content_type == "both") {
            $this->set_html_body($body); 
            $this->set_text_body($body);   
        } else {
            $this->set_text_body($body);        
        }
           
        $this->body = $this->mail_mime->get($this->mime_params);      
        $this->headers = $this->mail_mime->headers($this->headers);  

        if($this->delivery_method == "sendmail") {
            $this->mail =& Mail::factory("sendmail", $this->sendmail_settings);
        } elseif($this->delivery_method == "smtp") {
            $this->mail =& Mail::factory("smtp", $this->smtp_settings);
        } else {
            $this->mail =& Mail::factory("mail");    
        }

        return $this;
    }

    /**
     *  Load the template view file for the body of the email.
     *  @todo Document this API
     */
    function render_message($method_name, $body = array()) {
        if(strstr($method_name, "/")) {
            $template = "{$this->template_root}/{$method_name}.".Trax::$views_extension;
        } else {
            $template = "{$this->template_path}/{$method_name}.".Trax::$views_extension;
        }

        if(file_exists($template)) {
            # start to buffer output
            ob_start(); 
            if(count($body)) {
                extract($body);  
            }
            include($template);
            $result = ob_get_contents();
            ob_end_clean();
        }
        return $result;        
    }

    /**
     *  Uses ActionControllers render_partial method.
     *  @todo Document this API
     */      
    function render_partial($path, $options = array()) {
        $locals = $this->preparse_body;
        if(is_array($options['locals']) && is_array($locals)) {
            $options['locals'] = array_merge($locals, $options['locals']);
        } elseif(is_array($locals)) {
            $options['locals'] = $locals;    
        }
        $ar = new ActionController();
        $ar->views_path = $this->template_path;
        $ar->render_partial($path, $options);                     
    }

    /**
     *  Return a text version of the email currently loaded.
     *  @todo Document this API
     */    
    function encoded($add_pre_tags = false) {                   
        if(!count($this->errors)) {
            list(, $text_headers) = $this->mail->prepareHeaders($this->headers);
            $email = $text_headers.$this->crlf.$this->crlf.$this->body;
            if($add_pre_tags) {
                $email = "<pre>".$email."</pre>";    
            }
        } else {
            $email = $this->get_errors_as_string("\n");    
        }
        return $email;
    }

    /**
     *  Sends the email loaded into this object via create_mail().
     *  @todo Document this API
     */    
    function deliver($mail = null) {
        if(is_null($mail)) {
            $mail =& $this;               
        } 
        if($this->perform_deliveries) {
            if($this->delivery_method == "test") {
                $this->deliveries[] = $mail->encoded();
                return true;    
            }
            if(!count($this->errors)) { 
                $result = $mail->mail->send($mail->headers['To'], $mail->headers, $mail->body);
                if(is_object($result)) { 
                    $this->errors[] = $result->getMessage();
                    return false;
                } 
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     *  Add an attachment to an email.
     *  @todo Document this API
     */
    function add_attachment($file, $content_type ='application/octet-stream', $file_name = '', $is_file = true, $encoding = 'base64') {
        $this->mail_mime->addAttachment($file, $content_type, $file_name, $is_file, $encoding);
    }

    /**
     *  Validates a single email address
     *
     *  @param string $email
     *    Validates the input $email is in format:
     *      user@domain.com or "John Smith <user@domain.com>"
     *  @return boolean 
     *    <ul>
     *      <li>true => Valid email, no errors found.
     *      <li>false => Email not valid</li>
     *    </ul>
     */
    function validate_email($email) {
        if(eregi("^[a-zA-Z0-9._-]+@([a-zA-Z0-9._-]+\.)+([a-zA-Z0-9_-]){2,4}$", $email) ||
           eregi("^([ '_a-zA-Z0-9]\w*)+<[a-zA-Z0-9._-]+@([a-zA-Z0-9._-]+\.)+([a-zA-Z0-9_-]){2,4}>$", $email)) {
            return true;
        }
        return false;
    }

    /**
     *  Set a single line for the header of an email
     *
     *  @uses $headers
     *  @param string $header_key
     *    key for the header line (To:, From:, Subject:, etc)
     *  @param string $header_value
     *    value for the $header_key 
     */
    function set_header_line($header_key, $header_value) {
        if($header_key && $header_value) {
            $this->headers[$header_key] = $header_value;
        }
    }
    
    /**
     *  Add or overwrite description of an error to the list of errors
     *  @param string $error Error message text
     *  @param string $key Key to associate with the error (in the
     *    simple case, column name).  If omitted, numeric keys will be
     *    assigned starting with 0.  If specified and the key already
     *    exists in $errors, the old error message will be overwritten
     *    with the value of $error.
     *  @uses $errors
     */
    function add_error($error, $key = null) {
        if(!is_null($key)) { 
            $this->errors[$key] = $error;
        } else {
            $this->errors[] = $error;
        }
    }
    
    /**
     *  Return description of non-fatal errors
     *
     *  @uses $errors
     *  @param boolean $return_string
     *    <ul>
     *      <li>true => Concatenate all error descriptions into a string
     *        using $seperator between elements and return the
     *        string</li>
     *      <li>false => Return the error descriptions as an array</li>
     *    </ul>
     *  @param string $seperator  String to concatenate between error
     *    descriptions if $return_string == true
     *  @return mixed Error description(s), if any
     */
    function get_errors($return_string = false, $seperator = "<br>") {
        if($return_string && count($this->errors) > 0) {
            return implode($seperator, $this->errors);
        } else {
            return $this->errors;
        }
    }

    /**
     *  Return errors as a string.
     *
     *  Concatenate all error descriptions into a stringusing
     *  $seperator between elements and return the string.
     *  @param string $seperator  String to concatenate between error
     *    descriptions
     *  @return string Concatenated error description(s), if any
     */
    function get_errors_as_string($seperator = "<br>") {
        return $this->get_errors(true, $seperator);
    }

}


?>
