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
 *  @todo Document this class
 */
class DateHelper extends Helpers {

    /**
     *  @todo Document this variable
     */
    public $request_years;

    /**
     *  Month value parsed from $_REQUEST
     */
    public $request_months = array();

    /**
     *  @todo Document this variable
     */
    public $request_days;

    /**
     *  @todo Document this variable
     */
    public $request_hours;

    /**
     *  @todo Document this variable
     */
    public $request_minutes;

    /**
     *  @todo Document this variable
     */
    public $request_seconds;

    /**
     *  @todo Document this variable
     */
    public $selected_years = array();

    /**
     *  @todo Document this method
     */
    function __construct($object_name = null, $attribute_name = null) {
        parent::__construct($object_name, $attribute_name);
    }
    
    /**
     *  @todo Document this method
     *  @uses attribute_name
     *  @uses object_name
     *  @uses request_years
     *  @uses request_months
     *  @uses request_days
     *  @uses request_hours
     *  @uses request_minutes
     *  @uses request_seconds
     */
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
     *  @todo Document this method
     *  @param string   Type of select
     *  @param string[] Options to appear in the select
     *  @param string   
     *  @param boolean  Whether to include a blank in the list of
     *                  select options  
     *  @param boolean  Whether to ignore type
     */
    private function select_html($type, $options, $prefix = null,
                                 $include_blank = false,
                                 $discard_type = false) {
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
     *  Prefix a leading zero to single digit numbers
     *  @param string   A number
     *  @return string  Number with zero prefix if value <= 9
     */
    private function leading_zero_on_single_digits($number) {
        return $number > 9 ? $number : "0$number";
    }

    /**
     *
     *  @todo Document this method
     */
    protected function value() {
        if(!$value = $this->check_request_for_value()) {
            $object = $this->object();
            if(is_object($object) && $this->attribute_name) {
                $value = $object->send($this->attribute_name);
            }
        }
        return $value;
    }
    
    /**
     *  @todo Document this method
     */
    function expiration_date_select($options = array()) {
        return $this->to_expiration_date_select_tag($options);      
    }
        
    /**
     *   datetime_select("post", "written_on")
     *   datetime_select("post", "written_on", array("start_year" => 1995))
     *  @todo Document this method
     */
    function datetime_select($options = array()) {     
        return $this->to_datetime_select_tag($options);
    } 
    
    /**
     *   datetime_select("post", "written_on")
     *   datetime_select("post", "written_on", array("start_year" => 1995))
     *  @todo Document this method
     */
    function date_select($options = array()) {   
        return $this->to_date_select_tag($options);
    }  
    
    /**
     *  @todo Document this method
     *  @uses select_html
     */
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
     *  @todo Document this method
     */
    function select_date($date = null, $options = array()) {
        $date = is_null($date) ? date("Y-m-d") : $date;
        return $this->select_year($date, $options) .
                $this->select_month($date, $options) .
                $this->select_day($date, $options);
    }

    /**
     * Returns a set of html select-tags (one for year, month, day, hour, and minute) preselected the +datetime+.
     *  @todo Document this method
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
     *  @todo Document this method
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
     *  @todo Document this method
     *  @uses select_html
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
     *  @todo Document this method
     *  @uses select_html
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
     *  @todo Document this method
     *  @uses select_html
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
     *  @todo Document this method
     *  @uses leading_zero_on_single_digits
     *  @uses request_days
     *  @uses select_html
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
        $field_name = array_key_exists('field_name', $options)
            ? $options['field_name'] : 'day';
        return $this->select_html($field_name, $day_options,
                                  $options['prefix'],
                                  array_key_exists('include_blank', $options)
                                  ? $options['include_blank'] : false,
                                  $options['discard_type']);
    }

    /**
     *  Generate HTML/XML for month selector pull-down menu
     *
     *  Returns <samp><select>...</select></samp> HTML with an option
     *  for each of the twelve months.  The first argument, if
     *  present, specifies the initially selected month.  The second
     *  argument controls the format of the generated HTML.
     *
     *  <b>First argument: initially selected month</b> If a value for
     *  this field is specified in
     *  <samp>$_REQUEST[</samp><i>classname</i><samp>][</samp><i>attributename</i><samp>]</samp>
     *  and <samp>$_REQUEST</samp> has been parsed by a previous call
     *  to {@link check_request_for_value()}, then that month
     *  is initially selected regardless of the argument value.
     *  Otherwise, if the first argument is present and is a character
     *  string of two decimal digits with a value in the range
     *  '01'..'12' then that month is initially selected.  If this
     *  argument is absent or invalid, the current calendar month is
     *  initially selected.
     *
     *  <b>Second argument: output format</b> If omitted, visible
     *  months are shown as 'January' through 'December'.  If 
     *  <samp>use_month_number</samp> is specified (as
     *  <samp>array('use_month_number' => 1)</samp>) then the name of
     *  the month is replaced by the month number.  If
     *  <samp>add_month_number</samp> is specified the month number is
     *  shown before the month name.  In all cases the value sent to
     *  the server is the two digit month number in the range
     *  '01'..'12'.
     *
     *  Other options:<br />
     *  <b>field_name</b> if present  (as
     *  <samp>array('field_name' => 'somestring')</samp> generate<br />
     *  <samp><select name="somestring">...</samp> otherwise generate<br />
     *  <samp><select name="month">...</samp>.<br />
     *  <b>Also:</b> {@link select_html()} options.
     *
     *  Examples:
     *  <ul>
     *   <li><samp>select_month();</samp> Generates menu January,
     *    February etc.</li>
     *   <li><samp>select_month(null,array('use_month_number' => 1));</samp>
     *    Generates menu 1, 2 etc.</li>
     *   <li><samp>select_month(null,array('add_month_number' => 1));</samp>
     *    Generates menu 1 - January, 2 - February etc.</li>
     *  </ul>
     *
     *  @param string  Selected month as two-digit number
     *  @param string[] Output format options
     *  @return string  Generated HTML
     *  @uses request_days
     *  @uses request_months
     *  @uses select_html
     */
    function select_month($date = null, $options = array()) {
       
        $month_options = "";    // will accumulate <option>s
        
        //  If a value for this attribute was parsed from $_REQUEST,
        //  use it as initially selected and ignore first argument
        if(array_key_exists($this->attribute_name,$this->request_months)) {
            $date_month = $this->request_months[$this->attribute_name];    
        }

        //  No value in $_REQUEST so look at the first argument.
        //  If it is valid use it as initially selected
        elseif(strlen($date) == 2 && is_numeric($date)
               && $date >=1 && $date <= 12 ) {
            $date_month = $date;
        }

        //  First argument is missing or invalid,
        //  initially select current month
        else {
            $date_month = date("m",strtotime($date));    
        }
   
        //  Generate <option>...</option> HTML for each month
        for($month_number = 1; $month_number <= 12; $month_number++) {
            if(array_key_exists('use_month_numbers',$options)) {
                $month_name = $month_number;
            } elseif(array_key_exists('add_month_numbers',$options)) {
                $month_name = $month_number. ' - '
                    . date("F",strtotime("2005-" . $month_number
                                       . "-01"));
            } else {
                $month_name = date("F",strtotime("2005-" . $month_number
                                                 ."-01"));
            }

            $month_options .= ($date_month == $month_number ?
                               "<option value=\""
                          .$this->leading_zero_on_single_digits($month_number)
                       ."\" selected=\"selected\">$month_name</option>\n" :
                               "<option value=\""
                          .$this->leading_zero_on_single_digits($month_number)
                               ."\">$month_name</option>\n");
        }

        //  Return finished HTML
        $field_name = array_key_exists('field_name', $options)
                       ? $options['field_name'] : 'month';
        return $this->select_html($field_name, $month_options,
                                  array_key_exists('prefix', $options)
                                  ? $options['prefix'] : null,
                                  array_key_exists('include_blank', $options)
                                  ? $options['include_blank'] : false,
                                  array_key_exists('discard_type', $options)
                                  ? $options['discard_type'] : false);
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
     *  @todo Document this method
     *  @uses select_html
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

        $start_year = ($options['start_year']) ? $options['start_year'] : $date_year - 5;
        $end_year = ($options['end_year']) ? $options['end_year'] : $date_year + 5;

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
     *  @todo Document this method
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
            $seperator = array_key_exists('field_seperator',$options)
                ? $options['field_seperator'] : " ";
            $date_select = implode($seperator, $date_select);            
        }

        return $date_select;
    }

    /**
     *
     *  @todo Document this method
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
    
    /**
     *  @todo Document this method
     */
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
  *  @todo Document this function
  */
function select_date() {
    $date_helper = new DateHelper();
    $args = func_get_args();
    return call_user_func_array(array($date_helper, 'select_date'), $args);
}

/**
 *  Select_datetime($datetime = null, $options = array())
 *  @todo Document this function
 */
function select_datetime() {
    $date_helper = new DateHelper();
    $args = func_get_args();
    return call_user_func_array(array($date_helper, 'select_datetime'), $args);
}

/**
 * select_expiration_date($datetime = null, $options = array())
 *  @todo Document this function
 */
function select_expiration_date() {
    $date_helper = new DateHelper();
    $args = func_get_args();
    return call_user_func_array(array($date_helper, 'select_expiration_date'), $args);        
}

/**
 *
 *  @todo Document this function
 */
function datetime_select($object, $attribute, $options = array()) {
    $date_helper = new DateHelper($object, $attribute);
    return $date_helper->datetime_select($options);    
}

/**
 *
 *  @todo Document this function
 */
function date_select($object, $attribute, $options = array()) {
    $date_helper = new DateHelper($object, $attribute);
    return $date_helper->date_select($options);    
}

/**
 *
 *  @todo Document this function
 */
function expiration_date_select($object, $attribute, $options = array()) {
    $date_helper = new DateHelper($object, $attribute);
    return $date_helper->expiration_date_select($options);        
}


/**
 *
 *  @todo Document this function
 */
function select_year() {
    $date_helper = new DateHelper();
    $args = func_get_args();
    return call_user_func_array(array($date_helper, 'select_year'), $args);    
}


/**
 *
 *  @todo Document this function
 */
function select_month() {
    $date_helper = new DateHelper();
    $args = func_get_args();
    return call_user_func_array(array($date_helper, 'select_month'), $args);    
}


/**
 *  @todo Document this function
 */
function select_day() {
    $date_helper = new DateHelper();
    $args = func_get_args();
    return call_user_func_array(array($date_helper, 'select_day'), $args);    
}

// -- set Emacs parameters --
// Local variables:
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>