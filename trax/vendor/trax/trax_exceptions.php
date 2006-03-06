<?php
/**
 *  File containing the TraxError class and its subclasses
 *
 *  (PHP 5)
 *
 *  @package PHPonTrax
 *  @version $Id: trax_exceptions.php 53 2005-10-29 14:49:53Z john $
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
 *  Trax base class for Exception handling
 *
 *  @package PHPonTrax
 */
class TraxError extends Exception {
    public function __construct($message, $heading, $code = "500") {
        parent::__construct($message, $code);
        $this->error_heading = $heading;
        $this->error_message = $message;
        $this->error_code = $code;
    }     
}

/**
 *  Active Record's Exception handling class
 *
 *  @package PHPonTrax
 */
class ActiveRecordError extends TraxError {}

/**
 * Action Controller's Exception handling class
 *
 *  @package PHPonTrax
 */
class ActionControllerError extends TraxError {}

?>