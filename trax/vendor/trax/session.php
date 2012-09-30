<?php
/**
 *  File containing the Session class
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
 *  Keep track of state of the client's session with the server
 *
 *  Since there is no continuous connection between the client and the
 *  web server, there must be some way to carry information forward
 *  from one page to the next.  PHP does this with a global array variable
 *  {@link http://www.php.net/manual/en/reserved.variables.php#reserved.variables.session $_SESSION}
 *  which is automatically restored from an area of the server's hard disk
 *  indicated by the contents of a cookie stored on the client's computer.
 *  This class is a static class with convenience methods for accessing the
 *  contents of $_SESSION.
 *   @tutorial PHPonTrax/Session.cls
 */
class Session {

    /**
     *  Name of the session (used as cookie name).
     */
    const TRAX_SESSION_NAME = "TRAXSESSID";

    /**
     *  Lifetime in seconds of cookie or, if 0, until browser is restarted.
     */
    const TRAX_SESSION_LIFETIME = "0";

    /**
     *  After this number of minutes, stored data will be seen as
     *  'garbage' and cleaned up by the garbage collection process.
     */
    const TRAX_SESSION_MAXLIFETIME_MINUTES = "20";

    /**
     *  IP Address of client
     *  @var string
     */
    private static $ip = null;

    /**
     *  User Agent (OS, Browser, etc) of client
     *  @var string
     */
    private static $user_agent = null;

    /**
     *  Session started
     *  @var boolean
     */
    private static $started = false;

    /**
     *  Session ID
     *  @var string
     */
    public static $id = null;

    /**
     *  Setup basic session information
     *
     *  Fetch the contents from a specified element of
     *  {@link http://www.php.net/manual/en/reserved.variables.php#reserved.variables.session $_SESSION}
     *  @uses Trax::$session_name
     *  @uses Trax::$session_lifetime
	 *  @uses Trax::$session_maxlifetime_minutes
     */
	function init() {
        Trax::$session_name = Trax::$session_name ? Trax::$session_name : self::TRAX_SESSION_NAME;
        Trax::$session_lifetime = Trax::$session_lifetime ? Trax::$session_lifetime : self::TRAX_SESSION_LIFETIME;
        Trax::$session_maxlifetime_minutes = Trax::$session_maxlifetime_minutes ? Trax::$session_maxlifetime_minutes : self::TRAX_SESSION_MAXLIFETIME_MINUTES;

        # set the session default for this app
        ini_set('session.name', Trax::$session_name);
		ini_set('session.use_cookies', 1);
   		if(Trax::$session_cookie_domain) {
			ini_set('session.cookie_domain',  Trax::$session_cookie_domain);
		}
        ini_set('session.cookie_lifetime', Trax::$session_lifetime);
        ini_set('session.gc_probability', 1);
        ini_set('session.gc_maxlifetime', Trax::$session_maxlifetime_minutes * 60);
		ini_set('session.use_trans_sid', 0);
		ini_set('session.auto_start', 0);

		if(Trax::$session_store == 'active_record_store') {
			ini_set('session.save_handler', 'user');
			include_once("session/active_record_store.php");
			$session_class_name = Trax::$session_class_name ? Trax::$session_class_name : 'ActiveRecordStore';
			$ar_session = new $session_class_name;
			session_set_save_handler(
	            array(&$ar_session, 'open'),
	            array(&$ar_session, 'close'),
	            array(&$ar_session, 'read'),
	            array(&$ar_session, 'write'),
	            array(&$ar_session, 'destroy'),
	            array(&$ar_session, 'gc')
			);
		} else {
			# file store
			ini_set('session.save_handler', 'files');
			if(Trax::$session_save_path) {
				ini_set('session.save_path', Trax::$session_save_path);
			}
		}
	}

    /**
     *  Get a session variable
     *
     *  Fetch the contents from a specified element of
     *  {@link http://www.php.net/manual/en/reserved.variables.php#reserved.variables.session $_SESSION}
     *  @param mixed $key Key to identify one particular session variable
     *                    of potentially many for this session
     *  @return mixed Content of the session variable with the specified
     *                key if the variable exists; otherwise null.
     *  @uses get_hash()
     *  @uses is_valid_host()
     */
    function get($key) {
        if(self::is_valid_host()) {
            return $_SESSION[self::get_hash()][$key];
        }
        return null;
    }

    /**
     *  Set a session variable
     *
     *  Store a value in a specified element of
     *  {@link http://www.php.net/manual/en/reserved.variables.php#reserved.variables.session $_SESSION}
     *  @param mixed $key Key to identify one particular session variable
     *                    of potentially many for this session
     *  @param string $value Value to store in the session variable
     *                       identified by $key
     *  @uses get_hash()
     *  @uses is_valid_host()
     *
     */
    function set($key, $value) {
        if(self::is_valid_host()) {
            $_SESSION[self::get_hash()][$key] = $value;
        }
     }

    /**
     *  Test whether the user host is as expected for this session
     *
     *  Compare the REMOTE_ADDR and HTTP_USER_AGENT elements of
     *  {@link http://www.php.net/manual/en/reserved.variables.php#reserved.variables.server $_SERVER}
     *  to the expected values for this session.
     *  @uses $ip
     *  @uses $user_agent
     *  @return boolean
     *          <ul>
     *            <li>true =>  User host is as expected</li>
     *            <li>false => User host NOT as expected</li>
     *          </ul>
     */
    function is_valid_host() {
        if($_SERVER['REMOTE_ADDR'] == self::$ip &&
           $_SERVER['HTTP_USER_AGENT'] == self::$user_agent) {
            return true;
        }
        return false;
    }

    /**
     *  Get key that uniquely identifies this session
     *
     *  Calculate a unique session key based on the session ID and
     *  user agent, plus the user's IP address.
     *  @uses md5()
     *  @uses session_id()
     */
    function get_hash() {
        $key = session_id().$_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR'];
        // error_log('get_hash() returns '.md5($key));
        return md5($key);
    }

	/**
	 *  Alias to Session::start()
	 *
	 *  @uses start()
	 */
    function start() {
		self::start_session();
    }

    /**
     *  Start or continue a session
     *
     *  @uses ini_set()
     *  @uses $ip
     *  @uses is_valid_host()
     *  @uses session_id()
     *  @uses session_start()
     *  @uses $user_agent
     */
	function start_session() {

        if(!self::$started) {

			self::init();

            header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');

            self::$ip = $_SERVER['REMOTE_ADDR'];
            self::$user_agent = $_SERVER['HTTP_USER_AGENT'];

            if(self::is_valid_host() && array_key_exists('sess_id',$_REQUEST)) {
                session_id($_REQUEST['sess_id']);
            }

            session_cache_limiter("must-revalidate");
            session_start();
            self::$id = session_id();
            self::$started = true;
        }
        $hash = self::get_hash();
        if(!isset($_SESSION[$hash])) {
            $_SESSION[$hash] = array();
        }        
	}

	/**
	 *  Alias to Session::destroy_session()
	 *
	 *  @uses destroy_session()
	 */
    function destroy() {
		return self::destroy_session();
    }

    /**
     *  Destroy the user's session
     *
     *  Destroy all data registered to a session
     *
     *  @uses session_destroy()
     */
    function destroy_session() {
        session_destroy();
		#self::init();
    }

    /**
     *  Free all session variables currently registered
     *
     *  @uses get_hash()
     *  @uses session_unset()
     */
    function unset_session() {
        $_SESSION[self::get_hash()] = array();
    }

    /**
     *  Unset a session variable
     *
     *  Unset the variable in
     *  {@link http://www.php.net/manual/en/reserved.variables.php#reserved.variables.session $_SESSION}
     *  identified by key $key
     *  @uses get_hash()
     *  @uses is_valid_host()
     */
    function unset_var($key) {
         // error_log('Session::unset_var("'.$key.'")');
        if(self::is_valid_host()) {
            // error_log('before unsetting SESSION='.var_export($_SESSION,true));
            unset($_SESSION[self::get_hash()][$key]);
            // error_log('after unsetting SESSION='.var_export($_SESSION,true));
        }
    }

    /**
     *  Test whether a session variable is defined in $_SESSION
     *
     *  Check the
     *  {@link http://www.php.net/manual/en/reserved.variables.php#reserved.variables.session $_SESSION}
     *  array for the existance of a variable identified by $key
     *  @param mixed $key Key to identify one particular session variable
     *                    of potentially many for this session
     *  @return boolean
     *          <ul>
     *            <li>true =>  The specified session variable is
     *                         defined.</li>
     *            <li>false => The specified session variable is
     *                         not defined.</li>
     *          </ul>
     *  @uses get_hash()
     *  @uses is_valid_host()
     */
    function isset_var($key) {
        if(self::is_valid_host()) {
            if(isset($_SESSION[self::get_hash()][$key])) {
                return true;
            }
        }
        return false;
    }

    /**
     *  Test whether there is a flash message to be shown
     *
     *  Check whether the
     *  {@link http://www.php.net/manual/en/reserved.variables.php#reserved.variables.session $_SESSION}
     *  array for this session contains a
     *  flash message to be shown to the user.
     *  @param mixed $key Key to identify one particular flash message
     *                    of potentially many for this session
     *  @return boolean
     *          <ul>
     *            <li>true =>  A flash message is present</li>
     *            <li>false => No flash message is present</li>
     *          </ul>
     *  @uses get_hash()
     *  @uses is_valid_host()
     */
    function isset_flash($key) {
        if(self::is_valid_host()) {
			$hash = self::get_hash();
			if(isset($_SESSION[$hash]['flash'][$key])) {
				return true;
			}
			#if(array_key_exists($hash, $_SESSION)
            #   && array_key_exists('flash', $_SESSION[$hash])
            #   && array_key_exists($key, $_SESSION[$hash]['flash'])) {
            #    return true;
            #}
        }
        return false;
    }

    /**
     *  Get or set a flash message
     *
     *  A flash message is a message that will appear prominently on
     *  the next screen to be sent to the user. Flash
     *  messages are intended to be shown to the user once then erased.
     *  They are stored in the
     *  {@link http://www.php.net/manual/en/reserved.variables.php#reserved.variables.session $_SESSION}
     *  array for the user's session.
     *
     *  @param mixed $key Key to identify one particular flash message
     *                    of potentially many for this session
     *  @param string $value Content of the flash message if present
     *  @return mixed Content of the flash message with the specified
     *                key if $value is null; otherwise null.
     *  @uses get_hash()
     *  @uses is_valid_host()
     */
    function flash($key, $value = null) {
        if(self::is_valid_host()) {
			$hash = self::get_hash();
            if($value) {
                $_SESSION[$hash]['flash'][$key] = $value;
            } else {
                $value = @$_SESSION[$hash]['flash'][$key];
                unset($_SESSION[$hash]['flash'][$key]);
                return $value;
            }
        }
    }

    /**
     *  Debugging function to see what's in the session
     *
     *  Does a dump of the session to log file and optionally to screen
     *
     *  @param boolean $screen Display dump to screen
     */
	function debug($screen = false) {
		$msg = "Session::debug() => ".print_r($_SESSION, true);
		error_log($msg);
		if($screen) {
			echo "<p><pre>".$msg."</pre></p>";
		}
	}
}

// -- set Emacs parameters --
// Local variables:
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>