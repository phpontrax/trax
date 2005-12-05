<?php

# $Id:$
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

class Session {

    # Name of the session (used as cookie name).
    const TRAX_SESSION_NAME = "TRAXSESSID";
    # Lifetime in seconds of cookie or, if 0, until browser is restarted.
    const TRAX_SESSION_LIFETIME = "0";
    # After this number of minutes, stored data will be seen as 'garbage' and
    # cleaned up by the garbage collection process.
    const TRAX_SESSION_MAXLIFETIME_MINUTES = "20";

    private static
        # IP Address of client
        $ip = null,
        # User Agent (OS, Browser, etc) of client
        $user_agent = null;

    public static
        # Session ID
        $id = null;


    function get($key) {
        if(self::is_valid_host()) {
            return $_SESSION[self::get_hash()][$key];
        }
        return null;
    }

    function set($key, $value) {
        if(self::is_valid_host()) {
            $_SESSION[self::get_hash()][$key] = $value;
        }
    }

    function is_valid_host() {
        if(($_SERVER['REMOTE_ADDR'] == self::$ip || self::is_aol_host()) &&
           $_SERVER['HTTP_USER_AGENT'] == self::$user_agent) {
            return true;
        }
        return false;
    }

    function is_aol_host() {
        if(ereg("proxy\.aol\.com$", gethostbyaddr($_SERVER['REMOTE_ADDR'])) ||
           stristr($_SERVER['HTTP_USER_AGENT'], "AOL")) {
            return true;
        }
        return false;
    }

    function get_hash() {
        $key = session_id().$_SERVER['HTTP_USER_AGENT'];
        if(!self::is_aol_host()) {
            $key .= $_SERVER['REMOTE_ADDR'];
        }
        return md5($key);
    }

    function start() {
        # set the session default for this app
        ini_set('session.name', TRAX_SESSION_NAME);
        ini_set('session.cookie_lifetime', TRAX_SESSION_LIFETIME);
        ini_set('session.gc_probability', 1);
        ini_set('session.gc_maxlifetime', TRAX_SESSION_MAXLIFETIME_MINUTES * 60);

        header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');

        self::$ip = $_SERVER['REMOTE_ADDR'];
        self::$user_agent = $_SERVER['HTTP_USER_AGENT'];

        if(self::is_valid_host() && $_REQUEST['sess_id']) {
            session_id($_REQUEST['sess_id']);
        }

        session_cache_limiter("must-revalidate");
        session_start();
        self::$id = session_id();
    }

    function unset_session() {
        session_unset($_SESSION[self::get_hash()]);
    }

    function destory_session() {
        session_destroy();
    }

    function flash($value) {
        if(self::is_valid_host()) {
            if($value) {
                $_SESSION[self::get_hash()]['flash'] = $value;
            } else {
                $value = $_SESSION[self::get_hash()]['flash'];
                unset($_SESSION[self::get_hash()]['flash']);
                return $value;
            }
        }
    }

}

?>