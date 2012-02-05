<?php
/**
 *  File containing the ActiveRecord Session Store class
 *
 *  (PHP 5)
 *
 *  @package PHPonTrax
 *  @version $Id:$
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
 * A session store backed by an Active Record class.
 */

/**
 * 
 * Session Table Schema:
 *
 * CREATE TABLE sessions (
 *   id varchar(100) NOT NULL default '',
 *   client_ip varchar(20) default NULL,
 *   http_user_agent varchar(150) default NULL,
 *   data text default NULL,
 *   created_at datetime default NULL,
 *   updated_at datetime default NULL,
 *   PRIMARY KEY (id)
 * )
 * 
 */
class ActiveRecordStore extends ActiveRecord {
	
	public $table_name = 'sessions';
	
    function open($save_path, $session_name) {
        return true;   
    }

	function close() {
	    return true;
	}

	function read($sess_id) {
		$data = '';
	    # Select the data belonging to session $sess_id from the session table
		if(($session = $this->find($sess_id)) instanceof ActiveRecordStore) {
			$data = $session->data;
		}		
	    return $data;
	}

	function write($sess_id, $data) {
		
		# Select the data belonging to session $sess_id from the session table
		$session = $this->find($sess_id);
		$session = ($session instanceof ActiveRecordStore) ? $session : $this;
		$session->id = $sess_id;
		$session->data = $data;
		$session->client_ip = $this->escape($_SERVER['REMOTE_ADDR']);
		$session->http_user_agent = $this->escape($_SERVER['HTTP_USER_AGENT']);
		# Write the serialized session data ($data) to the session table

		return $session->save() ? true : false;
	}

	function destroy($sess_id) {
	    // Delete from the table all data for the session $sess_id
		return $this->delete($sess_id) ? true : false;
	}

	function gc($max_lifetime) {
	    # Old values are values with a Unix less than now - $max_lifetime
	    $old = time() - (Trax::$session_maxlifetime_minutes * 60);
	    # Delete old values from the MySQL session table
		$this->delete_all("UNIX_TIMESTAMP(created_at) < {$old}");
	    return true;
	}
		
}