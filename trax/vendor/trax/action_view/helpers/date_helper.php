<?php
/**
 *  File containing the DateHelper class and support functions
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
 *  @package PHPonTrax
 */
class DateHelper extends Helpers {
    public $selected_years = array();
    /**
     *
     */
    function __construct($object_name = null, $attribute_name = null) {
        parent::__construct();
        $this->object_name = $object_name;
        $this->attribute_name = $attribute_name;
    }

    /**
     *
     */
    private function value() {
        if(!$value = $this->check_request_for_value()) {
            $object = $this->object();
            if(is_object($object) && $this->attribute_name) {
                $value = $object->send($this->attribute_name);
            }
        }
        return $value;
    }
    
    private function check_request_for_value() {
        if(!$value = $_REQUEST[$this->object_name][$this->attribute_name]) {
            # check if this a date / datetime
            if($year_value = $_REQUEST[$this->object_name][$this->attribute_name."(1i)"]) {
                $this->request_years[$this->attribute_name] = $year_value;    
            }
            if($month_value = $_REQUEST[$this->object_name][$this->attribute_name."(2i)"]) {
                $this->request_months[$this->attribute_name] = $month_value;    
            }
            if($day_value = $_REQUEST[$this->object_name][$this->attribute_name."(3i)"]) {
                $this->request_days[$this->attribute_name] = $day_value;    
            }
            if($minute_value = $_REQUEST[$this->object_name][$this->attribute_name."(4i)"]) {
                $this->request_minutes[$this->attribute_name] = $minute_value;    
            }   
            if($hour_value = $_REQUEST[$this->object_name][$this->attribute_name."(5i)"]) {
                $this->request_hours[$this->attribute_name] = $hour_value;    
            }              
            if($second_value = $_REQUEST[$this->object_name][$this->attribute_name."(6i)"]) {
                $this->request_seconds[$this->attribute_name] = $second_value;    
            }                                                                   
        }   
        return $value;     
    }

    /**
     *
     */
    private function object($object_name = null) {
        $object_name = $object_name ? $object_name : $this->object_name;
        return $this->controller_object->$object_name;
    }

    /**
     *
     */
    private function select_html($type, $options, $prefix = null, $include_blank = false, $discard_type = false) {
        $select_html  = "<select name=\"$prefix";       
        if(!$discard_type) {
            if($prefix) $select_html .= "["; 
            $select_html .= $type;
            if($prefix) $select_html .= "]"; 
        }
        $select_html .= "\">\n";
        if($include_blank) $select_html .= "<option value=\"\"></option>\n";
        $select_html .= $options;
        $select_html .= "</select>\n";
        return $select_html;
    }

    /**
     *
     */
    private function leading_zero_on_single_digits($number) {
        return $number > 9 ? $number : "0$number";
    }
    
    function expiration_date_select($options = array()) {
        return $this->to_expiration_date_select_tag($options);      
    }
        
    /**
     *   datetime_select("post", "written_on")
     *   datetime_select("post", "written_on", array("start_year" => 1995))
     */
    function datetime_select($options = array()) {     
        return $this->to_datetime_select_tag($options);
    } 
    
    /**
     *   datetime_select("post", "written_on")
     *   datetime_select("post", "written_on", array("start_year" => 1995))
     */
    function date_select($options = array()) {   
        return $this->to_date_select_tag($options);
    }  
    
    function select_expiration_date($date = null, $options = array()) {
        $options['month_before_year'] = true;      
        $options['use_month_numbers'] = true;   
        $options['start_year'] = date("Y");
        $options['end_year'] = date("Y") + 7;
        $options['field_seperator'] = " / ";        
        $options['field_name'] = $options['year_name'] ? $options['year_name'] : "expiration_year"; 
        $date = ($_REQUEST[$options['field_name']]) ? date("Y-m-d", strtotime($_REQUEST[$options['field_name']]."-01-01")) : date("Y-m-d");
        $year_select = $this->select_year($date, $options);
        $options['field_name'] = $options['month_name'] ? $options['month_name'] : "expiration_month";
        $date = ($_REQUEST[$options['field_name']]) ? date("Y-m-d", strtotime("2006-".$_REQUEST[$options['field_name']]."-01")) : date("Y-m-d");
        $month_select = $this->select_month($date, $options);
        if($options['month_before_year']) {
            $select_html =  $month_select . $options['field_seperator'] .  $year_select;     
        } else {
            $select_html =  $year_select . $options['field_seperator'] .  $month_select;
        }
        return $select_html;
    }               

    /**
     * Returns a set of html select-tags (one for year, month, and day) pre-selected with the +date+.
     */
    function select_date($date = null, $options = array()) {
        $date = is_null($date) ? date("Y-m-d") : $date;
        return $this->select_year($date, $options) .
                $this->select_month($date, $options) .
                $this->select_day($date, $options);
    }

    /**
     * Returns a set of html select-tags (one for year, month, day, hour, and minute) preselected the +datetime+.
     */
    function select_datetime($datetime = null, $options = array()) {
        $datetime = is_null($datetime) ? date("Y-m-d H:i:s") : $datetime;
        return $this->select_year($datetime, $options) .
                $this->select_month($datetime, $options) .
                $this->select_day($datetime, $options) .
                $this->select_hour($datetime, $options) .
                $this->select_minute($datetime, $options);
    }

    /**
     * Returns a set of html select-tags (one for hour and minute)
     */
    function select_time($datetime = null, $options = array()) {
        $datetime = is_null($datetime) ? date("Y-m-d H:i:s") : $datetime;
        return $this->select_hour($datetime, $options) .
               $this->select_minute(datetime, options) .
               ($options['include_seconds'] ? $this->select_second($datetime, $options) : '');
    }

    /**
     *  Returns a select tag with options for each of the seconds 0 through 59 with the current second selected.
     *  The <tt>second</tt> can also be substituted for a second number.
     *  Override the field name using the <tt>:field_name</tt> option, 'second' by default.
     */
    function select_second($datetime, $options = array()) {
        $second_options = "";
        
        if($this->request_seconds[$this->attribute_name]) {
            $datetime_sec = $this->request_seconds[$this->attribute_name];    
        } elseif(strlen($datetime) == 2 && is_numeric($datetime)) {
            $datetime_sec = $datetime;
        } else {                  
            $datetime = $datetime ? $datetime : date("Y-m-d H:i:s"); 
            $datetime_sec = date("s",strtotime($datetime));
        }
        
        for($second = 0; $second <= 59; $second++) {          
            $second_options .= ($datetime && ($datetime_sec == $second)) ?
            "<option value=\"".$this->leading_zero_on_single_digits($second)."\"  selected=\"selected\">".$this->leading_zero_on_single_digits($second)."</option>\n" :
            "<option value=\"".$this->leading_zero_on_single_digits($second)."\">".$this->leading_zero_on_single_digits($second)."</option>\n";
        }
        $field_name = ($options['field_name']) ? $options['field_name'] : 'second';
        return $this->select_html($field_name, $second_options, $options['prefix'], $options['include_blank'], $options['discard_type']);
    }

    /**
     *  Returns a select tag with options for each of the minutes 0 through 59 with the current minute selected.
     *  Also can return a select tag with options by <tt>minute_step</tt> from 0 through 59 with the 00 minute selected
     *  The <tt>minute</tt> can also be substituted for a minute number.
     *  Override the field name using the <tt>:field_name</tt> option, 'minute' by default.
     */
    function select_minute($datetime, $options = array()) {
        $minute_options = "";
        
        if($this->request_minutes[$this->attribute_name]) {
            $datetime_min = $this->request_minutes[$this->attribute_name];    
        } elseif(strlen($datetime) == 2 && is_numeric($datetime)) {
            $datetime_min = $datetime;
        } else {                  
            $datetime = $datetime ? $datetime : date("Y-m-d H:i:s"); 
            $datetime_min = date("i",strtotime($datetime));
        }
        
        for($minute = 0; $minute <= 59; $minute++) {        
            $minute_options .= ($datetime && ($datetime_min == $minute)) ?
            "<option value=\"".$this->leading_zero_on_single_digits($minute)."\"  selected=\"selected\">".$this->leading_zero_on_single_digits($minute)."</option>\n" :
            "<option value=\"".$this->leading_zero_on_single_digits($minute)."\">".$this->leading_zero_on_single_digits($minute)."</option>\n";
        }
        $field_name = ($options['field_name']) ? $options['field_name'] : 'minute';
        return $this->select_html($field_name, $minute_options, $options['prefix'], $options['include_blank'], $options['discard_type']);
    }

    /**
     *  Returns a select tag with options for each of the hours 0 through 23 with the current hour selected.
     *  The <tt>hour</tt> can also be substituted for a hour number.
     *  Override the field name using the <tt>:field_name</tt> option, 'hour' by default.
     */
    function select_hour($datetime, $options = array()) {
        $hour_options = "";
        
        if($this->request_hours[$this->attribute_name]) {
            $datetime_hour = $this->request_hours[$this->attribute_name];    
        } elseif(strlen($datetime) == 2 && is_numeric($datetime)) {
            $datetime_hour = $datetime;
        } else {                  
            $datetime = $datetime ? $datetime : date("Y-m-d H:i:s"); 
            $datetime_hour = date("H",strtotime($datetime)); 
        }

        for($hour = 0; $hour <= 23; $hour++) {
            
            $hour_options .= ($datetime && ($datetime_hour == $hour)) ?
            "<option value=\"".$this->leading_zero_on_single_digits($hour)."\"  selected=\"selected\">".$this->leading_zero_on_single_digits($hour)."</option>\n" :
            "<option value=\"".$this->leading_zero_on_single_digits($hour)."\">".$this->leading_zero_on_single_digits($hour)."</option>\n";
        }
        $field_name = ($options['field_name']) ? $options['field_name'] : 'hour';
        return $this->select_html($field_name, $hour_options, $options['prefix'], $options['include_blank'], $options['discard_type']);
    }

    /**
     *  Returns a select tag with options for each of the days 1 through 31 with the current day selected.
     *  The <tt>date</tt> can also be substituted for a hour number.
     *  Override the field name using the <tt>:field_name</tt> option, 'day' by default.
     */
    function select_day($datetime, $options = array()) {
        $day_options = "";
        
        if($this->request_days[$this->attribute_name]) {
            $datetime_day = $this->request_days[$this->attribute_name];    
        } elseif(strlen($datetime) == 2 && is_numeric($datetime)) {
            $datetime_day = $datetime;
        } else {                  
            $datetime = $datetime ? $datetime : date("Y-m-d H:i:s"); 
            $datetime_day = date("d",strtotime($datetime));  
        }
        
        for($day = 1; $day <= 31; $day++) {        
            $day_options .= ($datetime && ($datetime_day == $day)) ?
            "<option value=\"".$this->leading_zero_on_single_digits($day)."\"  selected=\"selected\">".$this->leading_zero_on_single_digits($day)."</option>\n" :
            "<option value=\"".$this->leading_zero_on_single_digits($day)."\">".$this->leading_zero_on_single_digits($day)."</option>\n";
        }
        $field_name = ($options['field_name']) ? $options['field_name'] : 'day';
        return $this->select_html($field_name, $day_options, $options['prefix'], $options['include_blank'], $options['discard_type']);
    }

    /**
     *  Returns a select tag with options for each of the months January through December with the current month selected.
     *  The month names are presented as keys (what's shown to the user) and the month numbers (1-12) are used as values
     *  (what's submitted to the server). It's also possible to use month numbers for the presentation instead of names --
     *  set the <tt>:use_month_numbers</tt> key in +options+ to true for this to happen. If you want both numbers and names,
     *  set the <tt>:add_month_numbers</tt> key in +options+ to true. Examples:
     *
     *   select_month(Date.today)                             # Will use keys like "January", "March"
    *   select_month(Date.today, :use_month_numbers => true) # Will use keys like "1", "3"
    *   select_month(Date.today, :add_month_numbers => true) # Will use keys like "1 - January", "3 - March"
    *
    *  Override the field name using the <tt>:field_name</tt> option, 'month' by default.
   */
    function select_month($date, $options = array()) {
        $month_options = "";
        
        if($this->request_months[$this->attribute_name]) {
            $date_month = $this->request_months[$this->attribute_name];    
        } elseif(strlen($date) != 2 && !is_numeric($date)) {
            $date = $date ? $date : date("Y-m-d"); 
            $date_month = date("m",strtotime($date));    
        }
   
        for($month_number = 1; $month_number <= 12; $month_number++) {
            if($options['use_month_numbers']) {
                $month_name = $month_number;
            } elseif($options['add_month_numbers']) {
                $month_number .= ' - ' + date("F",strtotime("01-".$month_number."-2005"));
            } else {
                $month_name = date("F",strtotime("2005-".$this->leading_zero_on_single_digits($month_number)."-01"));
            }

            $month_options .= ($date && ($date_month == $month_number)) ?
            "<option value=\"".$this->leading_zero_on_single_digits($month_number)."\" selected=\"selected\">$month_name</option>\n" :
            "<option value=\"".$this->leading_zero_on_single_digits($month_number)."\">$month_name</option>\n";
        }
        $field_name = ($options['field_name']) ? $options['field_name'] : 'month';
        return $this->select_html($field_name, $month_options, $options['prefix'], $options['include_blank'], $options['discard_type']);
    }

    /**
     * Returns a select tag with options for each of the five years on each side of the current, which is selected. The five year radius
     * can be changed using the <tt>:start_year</tt> and <tt>:end_year</tt> keys in the +options+. Both ascending and descending year
     * lists are supported by making <tt>:start_year</tt> less than or greater than <tt>:end_year</tt>. The <tt>date</tt> can also be
     * substituted for a year given as a number. Example:
     *
     *   select_year(Date.today, :start_year => 1992, :end_year => 2007)  # ascending year values
    *   select_year(Date.today, :start_year => 2005, :end_year => 1900)  # descending year values
    *
    * Override the field name using the <tt>:field_name</tt> option, 'year' by default.
    */
    function select_year($date, $options = array()) {
        $year_options = "";

        if($this->request_years[$this->attribute_name]) {
            $date_year = $this->request_years[$this->attribute_name];    
        } elseif(strlen($date) == 4 && is_numeric($date)) {
            $date_year = $date;     
        } else {
            $date_year = $date ? date("Y",strtotime($date)) : date("Y");
        } 

        $start_year = ($options['start_year']) ? $options['start_year'] : $y - 5;
        $end_year = ($options['end_year']) ? $options['end_year'] : $y + 5;

        for($year = $start_year; $year <= $end_year; $year++) {
            $year_options .= ($date && ($date_year == $year)) ?
            "<option value=\"$year\" selected=\"selected\">$year</option>\n" :
            "<option value=\"$year\">$year</option>\n";
        }

        $field_name = ($options['field_name']) ? $options['field_name'] : 'year';
        return $this->select_html($field_name, $year_options, $options['prefix'], $options['include_blank'], $options['discard_type']);
    }

    /**
     *
     */
    function to_date_select_tag($options = array()) {
        $defaults = array('discard_type' => true);
        $options  = array_merge($defaults, $options);
        $options_with_prefix = array();
        for($i=1 ; $i <= 3 ; $i++) {
            $options_with_prefix[$i] = array_merge($options, array('prefix' => "{$this->object_name}[{$this->attribute_name}({$i}i)]"));
        }        
        
        if($options['include_blank']) {
            $value = $this->value();
            $date = $value ? $value : null;
        } else {
            $value = $this->value();
            $date = $value ? $value : date("Y-m-d");
        }

        $date_select = array();
        if($options['month_before_year']) {
            $options['order'] = array('month', 'year', 'day');
        } elseif(!$options['order']) {
            $options['order'] = array('year', 'month', 'day');
        }

        $position = array('year' => 1, 'month' => 2, 'day' => 3);

        $discard = array();
        if($options['discard_year']) $discard['year']  = true;
        if($options['discard_month']) $discard['month'] = true;
        if($options['discard_day'] || $options['discard_month']) $discard['day'] = true;

        foreach($options['order'] as $param) {
            if(!$discard[$param]) {
                $date_select[] = call_user_func(array($this, "select_$param"),  $date, $options_with_prefix[$position[$param]]);
            }
        }
        
        if(count($date_select)) {
            $seperator = $options['field_seperator'] ? $options['field_seperator'] : " ";
            $date_select = implode($seperator, $date_select);            
        }

        return $date_select;
    }

    /**
     *
     */
    function to_datetime_select_tag($options = array()) {
        $defaults = array('discard_type' => true);
        $options = array_merge($defaults, $options);
        $options_with_prefix = array();
        for($i=1 ; $i < 6 ; $i++) {
            $options_with_prefix[$i] = array_merge($options, array('prefix' => "{$this->object_name}[{$this->attribute_name}({$i}i)]"));
        }

        if($options['include_blank']) {
            $value = $this->value();
            $datetime = $value ? $value : null;
        } else {
            $value = $this->value();
            $datetime = $value ? $value : date("Y-m-d H:i:s");
        }

        $datetime_select = $this->select_year($datetime, $options_with_prefix[1]);
        if(!$options['discard_month'])
            $datetime_select .= $this->select_month($datetime, $options_with_prefix[2]);
        if(!($options['discard_day'] || $options['discard_month']))
            $datetime_select .= $this->select_day($datetime, $options_with_prefix[3]);
        if(!$options['discard_hour'])
            $datetime_select .= ' &mdash; ' . $this->select_hour($datetime, $options_with_prefix[4]);
        if(!($options['discard_minute'] || $options['discard_hour']))
            $datetime_select .= ' : ' . $this->select_minute($datetime, $options_with_prefix[5]);

        return $datetime_select;
    }
    
    function to_expiration_date_select_tag($options = array()) {
        $options['discard_day'] = true; 
        $options['month_before_year'] = true;      
        $options['use_month_numbers'] = true;   
        $options['start_year'] = date("Y");
        $options['end_year'] = date("Y") + 7;
        $options['field_seperator'] = " / ";
        return $this->to_date_select_tag($options);               
    }  

}

/**
  *  Avialble functions for use in views
  *  select_date($date = null, $options = array())
  */
function select_date() {
    $date_helper = new DateHelper();
    $args = func_get_args();
    return call_user_func_array(array($date_helper, 'select_date'), $args);
}

/**
 *  Select_datetime($datetime = null, $options = array())
 */
function select_datetime() {
    $date_helper = new DateHelper();
    $args = func_get_args();
    return call_user_func_array(array($date_helper, 'select_datetime'), $args);
}

/**
 * select_expiration_date($datetime = null, $options = array())
 */
function select_expiration_date() {
    $date_helper = new DateHelper();
    $args = func_get_args();
    return call_user_func_array(array($date_helper, 'select_expiration_date'), $args);        
}

/**
 *
 */
function datetime_select($object, $attribute, $options = array()) {
    $date_helper = new DateHelper($object, $attribute);
    return $date_helper->datetime_select($options);    
}

/**
 *
 */
function date_select($object, $attribute, $options = array()) {
    $date_helper = new DateHelper($object, $attribute);
    return $date_helper->date_select($options);    
}

function expiration_date_select($object, $attribute, $options = array()) {
    $date_helper = new DateHelper($object, $attribute);
    return $date_helper->expiration_date_select($options);        
}

function select_year() {
    $date_helper = new DateHelper();
    $args = func_get_args();
    return call_user_func_array(array($date_helper, 'select_year'), $args);    
}

function select_month() {
    $date_helper = new DateHelper();
    $args = func_get_args();
    return call_user_func_array(array($date_helper, 'select_month'), $args);    
}

function select_day() {
    $date_helper = new DateHelper();
    $args = func_get_args();
    return call_user_func_array(array($date_helper, 'select_day'), $args);    
}

?>
