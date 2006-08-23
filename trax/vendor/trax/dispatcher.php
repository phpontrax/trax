<?php
/**
 *  File containing the Dispatcher class
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
 *  Singleton class to call controller selected by HTTP request
 *
 *  @tutorial PHPonTrax/Dispatcher.cls
 */
class Dispatcher {

    /**
     *  Dispatch a request from Apache
     *
     *  Called from file dispatch.php, which is invoked by
     *  {@link http://httpd.apache.org/docs/2.0/mod/mod_rewrite.html Apache mod_rewrite}
     *  whenever a client makes a request.  Actions:
     *  <ol>
     *    <li>Remove forbidden tags and attributes from
     *      {@link http://www.php.net/reserved.variables#reserved.variables.get $_GET},
     *      {@link http://www.php.net/reserved.variables#reserved.variables.post $_POST} and
     *      {@link http://www.php.net/reserved.variables#reserved.variables.request $_REQUEST}.
</li>
     *    <li>Start a session to keep track of state between requests from
     *      the client.</li>
     *    <li>Construct an ActionController to process the action.</li>
     *    <li>Process the route</li>
     *  </ol>
     *  @uses ActionController::__construct()
     *  @uses ActionController::process_route()
     *  @uses ActionController::process_with_exception()
     *  @uses InputFilter::process_all()
     *  @uses Session::start()
     */
    function dispatch() {
        try {
            InputFilter::process_all();
            Session::start();
            $ac = new ActionController();
            $ac->process_route();
        } catch(Exception $e) {
            $ac->process_with_exception($e);
        }
    }

}

?>