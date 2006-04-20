<?php
/**
 *  File for mock ActiveRecord class
 *
 *  This file has the same name as the file holding the
 *  {@link ActiveRecord} class.  To use the mock ActiveRecord, put
 *  this file in the PHP include path ahead of the Trax library, so
 *  that any class which requires active_record.php will load this
 *  version.
 *
 * (PHP 5)
 *
 * @package PHPonTraxTest
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @copyright (c) Walter O. Haas 2006
 * @version $Id$
 * @author Walt Haas <haas@xmission.com>
 */

/**
 * Mock ActiveRecord class for testing
 */
class ActiveRecord {

    /**
     *  Expected query
     *  @var string
     */
    private $expected_query = null;

    /**
     *  Expected result
     *  @var string
     */
    private $expected_result = null;

    /**
     *  Set expected query and return
     *
     *  This is a test routine that does not exist in the real
     *  ActiveRecord.
     *  @param string $expected Expected query
     *  @param string $result Result to be returned when expected
     *  query is received.
     */
    public function expect_query($expected, $result) {
        $this->expected_query = $expected;
        $this->expected_result = $result;
    }

    /**
     *  Get contents of one column of record selected by id and table
     *
     *  When called, {@link $id} identifies one record in the table
     *  identified by {@link $table}.  Fetch from the database the
     *  contents of column $column of this record.
     *  @param string Name of column to retrieve
     *  @return string Column contents
     *  @expected_query
     */
    function send($column) {
        if ($column != $this->expected_query) {
            PHPUnit2_Framework_Assert::fail('ActiveRecord::send() called with'
                 .' "'.$column.'", expected "'.$this->expected_query.'"');
        }
        return $this->expected_result;
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
