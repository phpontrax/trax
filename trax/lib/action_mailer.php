<?
# $Id$
#
# Copyright (c) 2005 John Peterson
#
# Permission is hereby granted, free of charge, to any person obtaining
# a copy of this software and associated documentation files (the
# "Software"), to deal in the Software without restriction, including
# without limitation the rights to use, copy, modify, merge, publish,
# distribute, sublicense, and/or sell copies of the Software, and to
# permit persons to whom the Software is furnished to do so, subject to
# the following conditions:
#
# The above copyright notice and this permission notice shall be
# included in all copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
# EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
# MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
# NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
# LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
# OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
# WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

include_once( "Mail.php" );
include_once( "Mail/mime.php" );

class ActionMailer {

    public $crlf = "\r\n";
    public $smtp_params = array("host"=>"localhost", "port"=>"25");
    public $send_type = "mail"; // smtp or mail
    public $subject = null; // email subject
    public $from_address = "no-reply@nodomain.com";
    public $from_name = null;
    public $error = null;
    private $mail_mime;  // Mail_mime object
    private $to_addresses, $cc_addresses, $bcc_addresses, $replyto_addresses;

    function __construct($crlf = null) {
        if(!is_null($crlf)) {
            $this->crlf = $crlf;
        }
        $this->mail_mime = new Mail_mime();
    }

    function add_to_address($address, $name = null) {
        $cur = count($this->to_addresses);
        $this->to_addresses[$cur][0] = trim($address);
        $this->to_addresses[$cur][1] = trim($name);
    }

    function add_cc_address($address, $name = null) {
        $cur = count($this->cc);
        $this->cc_addresses[$cur][0] = trim($address);
        $this->cc_addresses[$cur][1] = trim($name);
    }

    function add_bcc_address($address, $name = null) {
        $cur = count($this->bcc);
        $this->bcc_addresses[$cur][0] = trim($address);
        $this->bcc_addresses[$cur][1] = trim($name);
    }

    function add_replyto_address($address, $name = null) {
        $cur = count($this->ReplyTo);
        $this->replyto_addresses[$cur][0] = trim($address);
        $this->replyto_addresses[$cur][1] = trim($name);
    }

    function set_from_address($address, $name = null) {
        $this->from_address = trim($address);
        if(!is_null($name))
            $this->from_name = trim($name);
    }

    function set_text_body($text) {
        if(strlen($text) > 0)
            $this->mail_mime->setTxtBody($text);
    }

    function set_html_body($html) {
        if(strlen($html) > 0)
            $this->mail_mime->setHTMLBody($html);
    }
    
    function set_subject($subject) {
        $this->subject = $subject;    
    }

    function add_attachment($file, $content_type = null, $file_name = null, $is_file = null, $encoding = null) {
        if(file_exists($file))
            $this->mail_mime->addAttachment($file, $content_type, $file_name, $is_file, $encoding);
    }

    function format_address($address) {
        if(empty($address[1]))
            $formatted = $address[0];
        else
            $formatted = sprintf('"%s" <%s>', $address[1], $address[0]);

        // now validate if the email address
        if(!$this->validate_email($address[0])) {
            $this->error .= "Invalid email address: ".$address[0]."<br>";
        }

        return $formatted;
    }

    function validate_email($email) {
        if(eregi("^[a-zA-Z0-9._-]+@([a-zA-Z0-9._-]+\.)+([a-zA-Z0-9_-]){2,4}$", $email)) {
            return true;
        }
        return false;
    }

    function set_header_line($header_key, $header_value) {
        if($header_key && $header_value) {
            $this->headers[$header_key] = $header_value;
        }
    }

    function set_headers($extra_headers = null) {
        if(is_array($this->to_addresses)) {
            foreach($this->to_addresses as $to_address) {
                $to_addresses[] = $this->format_address($to_address);
            }
            if(is_array($to_addresses)) $to_addresses = implode(",", $to_addresses);
            if(!empty($to_addresses))
            $this->set_header_line("To", $to_addresses);
        }

        if(is_array($this->cc_addresses)) {
            foreach($this->cc_addresses as $cc_address) {
                $cc_addresses[] = $this->format_address($cc_address);
            }
            if(is_array($cc_addresses)) $cc_addresses = implode(",", $cc_addresses);
            if(!empty($cc_addresses))
            $this->set_header_line("Cc", $cc_addresses);
        }

        if(is_array($this->bcc_addresses)) {
            foreach($this->bcc_addresses as $bcc_address) {
                $bcc_addresses[] = $this->format_address($bcc_address);
            }
            if(is_array($bcc_addresses)) $bcc_addresses = implode(",", $bcc_addresses);
            if(!empty($bcc_addresses))
            $this->set_header_line("Bcc", $bcc_addresses);
        }

        if(is_array($this->replyto_addresses)) {
            foreach($this->replyto_addresses as $replyto_address) {
                $replyto_addresses[] = $this->format_address($replyto_address);
            }
            if(is_array($replyto_addresses)) $replyto_addresses = implode(",", $replyto_addresses);
            if(!empty($replyto_addresses))
            $this->set_header_line("Reply-To", $replyto_addresses);
        }

        if(!is_null($this->subject))  {
            $this->set_header_line("Subject", $this->subject);
        }  else {
            $this->set_header_line("Subject", "");
        }

        if(!is_null($this->from_address)) {
            $from_address = $this->format_address(array("0"=>$this->from_address, "1"=>$this->from_name));
            $this->set_header_line("From", $from_address);
        }

        if(is_array($extra_headers)) {
            foreach($extra_headers as $extra_key => $extra_value) {
                if(!empty($extra_key) && !empty($extra_value))
                $this->set_header_line($extra_key, $extra_value);
            }
        }

        if(!array_key_exists("Date", $this->headers))
            $this->set_header_line("Date", date("r"));
        
        if(!array_key_exists("Return-Path", $this->headers) && !is_null($this->from_address))
            $this->set_header_line("Return-Path", $this->from_address);
            
        if(!array_key_exists("Reply-To", $this->headers) && !is_null($this->from_address))
            $this->set_header_line("Reply-To", $this->from_address);            
    }

    function send($to_address = null, $subject = null, $text_body = null, $html_body = null, $extra_headers = null) {
        if(!is_null($to_address)) $this->add_to_address($to_address);
        if(!is_null($subject)) $this->set_subject($subject);
        if(!is_null($html_body)) $this->set_html_body($html_body);
        if(!is_null($text_body)) $this->set_text_body($text_body);

        $body = $this->mail_mime->get();
        $this->set_headers($extra_headers);
        $headers = $this->mail_mime->headers($this->headers);       

        if($this->send_type == "smtp") {
            $mail =& Mail::factory("smtp", $this->smtp_params);
        } else {
            $mail =& Mail::factory("mail");
        }

        if(!$this->error) {
            $result = $mail->send(null, $headers, $body);
            if(is_object($result)) {
                $this->error = $result->getMessage();
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

}


?>