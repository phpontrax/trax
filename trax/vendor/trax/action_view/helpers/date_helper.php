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
 *  Utility to help build HTML pulldown menus for date and time
 */
class DateHelper extends Helpers {

    /**
     *  Year values parsed from $_REQUEST
     *
     *  Set by {@link check_request_for_value()}.  An array whose keys
     *  are the names of attributes of the {@link ActiveRecord}
     *  subclass named by {@link $object_name}, and whose values are
     *  the strings parsed from $_REQUEST.
     *  @var string[]
     */
    public $request_years;

    /**
     *  Month values parsed from $_REQUEST
     *
     *  Set by {@link check_request_for_value()}.  An array whose keys
     *  are the names of attributes of the {@link ActiveRecord}
     *  subclass named by {@link $object_name}, and whose values are
     *  the strings parsed from $_REQUEST.
     *  @var string[]
     */
    public $request_months = array();

    /**
     *  Day of month values parsed from $_REQUEST
     *
     *  Set by {@link check_request_for_value()}.  An array whose keys
     *  are the names of attributes of the {@link ActiveRecord}
     *  subclass named by {@link $object_name}, and whose values are
     *  the strings parsed from $_REQUEST.
     *  @var string[]
     */
    public $request_days;

    /**
     *  Hour values parsed from $_REQUEST
     *
     *  Set by {@link check_request_for_value()}.  An array whose keys
     *  are the names of attributes of the {@link ActiveRecord}
     *  subclass named by {@link $object_name}, and whose values are
     *  the strings parsed from $_REQUEST.
     *  @var string[]
     */
    public $request_hours;

    /**
     *  Minute values parsed from $_REQUEST
     *
     *  Set by {@link check_request_for_value()}.  An array whose keys
     *  are the names of attributes of the {@link ActiveRecord}
     *  subclass named by {@link $object_name}, and whose values are
     *  the strings parsed from $_REQUEST.
     *  @var string[]
     */
    public $request_minutes;

    /**
     *  Second values parsed from $_REQUEST
     *
     *  Set by {@link check_request_for_value()}.  An array whose keys
     *  are the names of attributes of the {@link ActiveRecord}
     *  subclass named by {@link $object_name}, and whose values are
     *  the strings parsed from $_REQUEST.
     *  @var string[]
     */
    public $request_seconds;

    /**
     *  <b>FIXME:</b>  Dead code?
     */
    public $selected_years = array();

    /**
     *  Constructor
     *
     *  Construct an instance of Helpers with the same arguments
     *  @param string Name of an ActiveRecord subclass
     *  @param string Name of an attribute of $object
     */
    function __construct($object_name = null, $attribute_name = null) {
        parent::__construct($object_name, $attribute_name);
    }
    
    /**
     *  Check whether $_REQUEST holds value for this attribute
     *
     *  Called with the name of an ActiveRecord subclass in
     *  $this->object_name and the name of one of its attributes in
     *  $this->attribute_name.  Check whether $_REQUEST contains a
     *  value for this attribute; if so return it.
     *  @return mixed String value if attribute was found in
     *                $_REQUEST, otherwise false
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
        //error_log("check_request_for_value().  object name='"
        //          . $this->object_name ."'  attribute name = '"
        //          . $this->attribute_name ."'");

        //  If $_REQUEST[$this->object_name] does not exist,
        //  return false immediately
        if (!isset($_REQUEST) || !is_array($_REQUEST)
            || !array_key_exists($this->object_name, $_REQUEST)) {
            return false;
        }

        //  $_REQUEST[$this->object_name] exists.
        //  Look for the requested attribute
        if (array_key_exists($this->attribute_name,
                             $_REQUEST[$this->object_name])) {

            //  Requested attribute found, return it
            return $_REQUEST[$this->object_name][$this->attribute_name];
        }

        //  There is an element $_REQUEST[$this->object_name] but not
        //  $_REQUEST[$this->object_name][$this->attribute_name] so
        //  check for the individual components of a date/time

        //  Keep track of whether we find any components
        $found = false;

        //    Check for year component
        if (array_key_exists($this->attribute_name."(1i)",
                             $_REQUEST[$this->object_name])) {
            $this->request_years[$this->attribute_name] =
                $_REQUEST[$this->object_name][$this->attribute_name."(1i)"];
            $found = true;
        }

        //    Check for month component
        if (array_key_exists($this->attribute_name."(2i)",
                             $_REQUEST[$this->object_name])) {
            $this->request_months[$this->attribute_name] =
                $_REQUEST[$this->object_name][$this->attribute_name."(2i)"];
            $found = true;
        }

        //    Check for day component
        if (array_key_exists($this->attribute_name."(3i)",
                             $_REQUEST[$this->object_name])) {
            $this->request_days[$this->attribute_name] =
                $_REQUEST[$this->object_name][$this->attribute_name."(3i)"];
            $found = true;
        }

        //    Check for hour component
        if (array_key_exists($this->attribute_name."(4i)",
                             $_REQUEST[$this->object_name])) {
            $this->request_hours[$this->attribute_name] =
                $_REQUEST[$this->object_name][$this->attribute_name."(4i)"];
            $found = true;
        }   

        //    Check for minute component
        if (array_key_exists($this->attribute_name."(5i)",
                             $_REQUEST[$this->object_name])) {
            $this->request_minutes[$this->attribute_name] =
                $_REQUEST[$this->object_name][$this->attribute_name."(5i)"];
            $found = true;
        }              

        //    Check for second component
        if (array_key_exists($this->attribute_name."(6i)",
                             $_REQUEST[$this->object_name])) {
            $this->request_seconds[$this->attribute_name] =
                $_REQUEST[$this->object_name][$this->attribute_name."(6i)"];
            $found = true;
        }                                                                   
        return $found;
    }

    /**
     *  Generate HTML/XML for select to enclose option list
     *
     *  @param string   Name attribute for <samp><select name=... ></samp>
     *  @param string   <samp><option>...</option><samp> list
     *  @param string   Prefix of name attribute, to be enclosed in
     *                  square brackets
     *  @param boolean  Whether to include a blank in the list of
     *                  select options  
     *  @param boolean  Whether to discard the type
     *  @return string  Generated HTML
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
     *  Get attribute value from $_REQUEST if there, otherwise from database
     *
     *  When called, {@link $object_name} describes the
     *  {@link ActiveRecord} subclass and {@link $attribute_name}
     *  describes the attribute whose value is desired.
     *
     *  An attempt is made to find the value in $_REQUEST, where it
     *  would be found after the browser POSTed a form.  If no value
     *  is found there, then the database is accessed for the value.
     *  When accessing the database, the assumption is made that the
     *  {@link ActionController} object refers to a single
     *  {@link ActiveRecord} subclass object which correctly
     *  identifies the table and record containing the attribute
     *  value.
     *  @return mixed Attribute value if found
     *  @uses check_request_for_value()
     *  @uses attribute_name
     *  @uses object()
     *  @uses ActiveRecord::send()
     */
    protected function value() {
        //error_log("DateHelper::value()  object name={$this->object_name}"
        //          . "   attribute name={$this->attribute_name}");

        //  First try to get attribute value from $_REQUEST
        if(!$value = $this->check_request_for_value()) {

            //  Value not found in $_REQUEST so we need to
            //  go to the database.  Assume that the controller
            //  points to the right ActiveRecord object
            $object = $this->object();
            if(is_object($object) && $this->attribute_name) {
                $value = $object->send($this->attribute_name);
            }
        }
        return $value;
    }
    
    /**
     *  Call to_expiration_date_select_tag()
     *
     *  Alias for {@link to_expiration_date_select_tag()}
     *  @param mixed[]  Output format options
     *  @return string Generated HTML
     *  @uses to_expiration_date_select_tag()
     */
    function expiration_date_select($options = array()) {
        return $this->to_expiration_date_select_tag($options);      
    }
        
    /**
     *  Call to_datetime_select_tag()
     *
     *  Alias for {@link to_datetime_select_tag()}
     *  @param mixed[]  Output format options
     *  @return string Generated HTML
     *  @uses to_datetime_select_tag()
     */
    function datetime_select($options = array()) {     
        return $this->to_datetime_select_tag($options);
    } 
    
    /**
     *  Call to_date_select_tag()
     *
     *  Alias for {@link to_date_select_tag()}
     *  @param mixed[]  Output format options
     *  @return string Generated HTML
     *  @uses to_date_select_tag()
     */
    function date_select($options = array()) {   
        //error_log("date_select() object=$this->object_name"
        //          . "   attribute=$this->attribute_name");
        return $this->to_date_select_tag($options);
    }  
    
    /**
     *  Generate HTML/XML for expiration month and year selector
     *  pulldowns
     *
     *  Generates HTML for a month and year pulldown.  The year
     *  pulldown has a range of years from the initially selected year
     *  to seven years after.
     *
     *  When called, $_REQUEST[] may have initial date values in
     *  fields with default names of 'expiration_month' and
     *  'expiration_year'.  If these values exist they override the
     *  first parameter.
     *  @param string   Date to display as initially selected if none
     *    was found in $_REQUEST[].  If omitted, default value is the
     *    current calendar date.<b>FIXME:</b> this doesn't work
     *  @param mixed[]  Output format options:
     *  <ul>
     *    <li><samp>'field_separator' => '</samp><i>somestring</i><samp>'</samp><br />
     *      String to insert between the month and year selectors.  If
     *      none is specified, default value is <samp>' / '</samp></li>
     *    <li><samp>'month_before_year' => 'false'<br />
     *      Output year selector first, then month selector.
     *      If option not specified, the month selector will be output
     *      first.</li>
     *    <li><samp>'month_name' =>'</samp><i>somestring</i><samp>'</samp><br />
     *      Set the name of the generated month selector to
     *      <i>somestring</i>.  If option not specified, default name is
     *      <samp>expiration_month</samp></li>
     *    <li><samp>'year_name' => '</samp><i>somestring</i><samp>'</samp><br />
     *      Set the name of the generated year selector to
     *      <i>somestring</i>.  If option not specified, default name is
     *      <samp>expiration_year</samp></li>
     *  </ul>
     *  @return string Generated HTML
     *  @uses select_html()
     *  @uses select_month()
     *  @uses select_year()
     */
    function select_expiration_date($date = null, $options = array()) {
//        error_log("select_expiration_date('"
//                  . (is_null($date) ? 'null' : $date)
//                  ."', " . var_export($options,true));
        $options['month_before_year'] = true;      
        $options['use_month_numbers'] = true;   
        $options['start_year'] = date("Y");
        $options['end_year'] = date("Y") + 7;
        $options['field_separator'] = " / ";        

        //  Find name and initial value of year field,
        //  then generate year selector pulldown
        $options['field_name'] = array_key_exists('year_name',$options)
            ? $options['year_name'] : "expiration_year"; 
        $date = array_key_exists($options['field_name'], $_REQUEST)
            ? date("Y-m-d",
                   strtotime($_REQUEST[$options['field_name']]."-01-01"))
            : date("Y-m-d");
        $year_select = $this->select_year($date, $options);

        //  Find name and initial value of month field,
        //  then generate year selector pulldown
        $options['field_name'] = array_key_exists('month_name',$options)
            ? $options['month_name'] : "expiration_month";
        $date = array_key_exists($options['field_name'], $_REQUEST)
            ? date("Y-m-d",
                   strtotime("2006-".$_REQUEST[$options['field_name']]."-01"))
            : date("Y-m-d");
        $month_select = $this->select_month($date, $options);

        //  Output month and year selectors in desired order
        if($options['month_before_year']) {
            $select_html =  $month_select . $options['field_separator']
                .  $year_select;     
        } else {
            $select_html =  $year_select . $options['field_separator']
                .  $month_select;
        }
        return $select_html;
    }               

    /**
     *  Generate HTML/XML for year, month and day selector pull-down menus
     *
     *  Returns <samp><select>...</select></samp> HTML with options
     *  for a number of years, months and days.  The first argument,
     *  if present, specifies the initially selected date.  The second
     *  argument controls the format of the generated HTML.
     *
     *  Examples:
     *  <ul>
     *   <li><samp>select_date();</samp><br /> Generates a group of
     *     three pulldown menus in the order year, month and day with
     *     the current date initially selected.</li> 
     *   <li>
     *  <samp>select_date('August 4, 1998');</samp><br /> Generates a
     *    group of   three pulldown menus in the order year, month and
     *    day with the date August 4, 1998 initially selected.</li> 
     *  </ul>
     *
     *  @param string   Date to display as initially selected if none
     *    was found in
     *    {@link $request_years}[{@link $attribute_name}],
     *    {@link $request_months}[{@link $attribute_name}] and
     *    {@link $request_days}[{@link $attribute_name}].
     *    Character string is any US English date representation
     *    supported by {@link strtotime()}.  If omitted, the
     *    current date is initially selected.
     *
     *  @param mixed[] Output format options are all of the options of
     *    {@link select_year()}, {@link select_month()} and
     *    {@link select_day()}.
     *  @return string  Generated HTML
     *  @uses select_day()
     *  @uses select_month()
     *  @uses select_year()
     */
    function select_date($date = null, $options = array()) {
        $date = is_null($date) ? date("Y-m-d") : $date;
        return $this->select_year($date, $options) .
                $this->select_month($date, $options) .
                $this->select_day($date, $options);
    }

    /**
     *  Generate HTML/XML for year-month-day-hour-minute selector pulldowns
     *
     *  Returns <samp><select>...</select></samp> HTML with options
     *  for a number of years, months, days, hours and minutes.  The
     *  first argument, if present, specifies the initially selected
     *  date.  The second argument controls the format of the
     *  generated HTML.
     *
     *  Examples:
     *  <ul>
     *   <li><samp>select_datetime();</samp><br /> Generates a group of
     *     five pulldown menus in the order year, month, day, hour and
     *     minute with the current date and time initially
     *    selected.</li> 
     *   <li>
     *  <samp>select_datetime('1998-04-08 13:21:17');</samp><br />
     *    Generates a group of five pulldown menus in the order year,
     *    month, day, hour and minute with the date/time
     *    1998 August 4 13:21 initially selected.</li> 
     *  </ul>
     *
     *  @param string   Date/time to display as initially selected.
     *    Character string is any US English date representation
     *    supported by {@link strtotime()}.  If omitted, the
     *    current date/time is initially selected.
     *
     *  @param mixed[] Output format options are all of the options of
     *    {@link select_year()}, {@link select_month()},
     *    {@link select_day()}, {@link select_hour()} and
     *    {@link select_minute()}. 
     *  @return string  Generated HTML
     *  @uses select_day()
     *  @uses select_hour()
     *  @uses select_minute()
     *  @uses select_month()
     *  @uses select_year()
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
     *  Generate HTML/XML for hour, minute and second selector pull-down menus
     *
     *  Returns <samp><select>...</select></samp> HTML with options
     *  for a number of hours, minutes and seconds.  The first argument,
     *  if present, specifies the initially selected time.  The second
     *  argument controls the format of the generated HTML.
     *
     *  Examples:
     *  <ul>
     *   <li><samp>select_time();</samp><br /> Generates two pulldown
     *     menus in the order hour : minute with
     *     the current time initially selected.</li> 
     *   <li>
     *  <samp>select_time('August 4, 1998 8:12');</samp><br /> Generates
     *    two pulldown menus in the order hour : minute with the 
     *    time 8:12 initially selected.</li> 
     *  </ul>
     *
     *  @param string   Time to display as initially selected if none
     *    was found in
     *    {@link $request_hours}[{@link $attribute_name}],
     *    {@link $request_minutes}[{@link $attribute_name}] and
     *    {@link $request_seconds}[{@link $attribute_name}].
     *    Character string is any US English date/time representation
     *    supported by {@link strtotime()}.  If omitted, the
     *    current time is initially selected.
     *
     *  @param mixed[] Output format options are all of the options of
     *    {@link select_hour()}, {@link select_minute()} and
     *    {@link select_second()}.
     *  @return string  Generated HTML
     *  @uses select_hour()
     *  @uses select_minute()
     *  @uses select_second()
     */
    function select_time($datetime = null, $options = array()) {
        $datetime = is_null($datetime) ? date("Y-m-d H:i:s") : $datetime;
        return $this->select_hour($datetime, $options) .
               $this->select_minute($datetime, $options) .
            (array_key_exists('include_seconds', $options)
             && $options['include_seconds']
             ? $this->select_second($datetime, $options) : '');
    }

    /**
     *  Generate HTML/XML for second selector pull-down menu
     *
     *  Returns <samp><select>...</select></samp> HTML with an option
     *  for each of the sixty seconds.  The first argument, if
     *  present, specifies the initially selected second.  The second
     *  argument controls the format of the generated HTML.
     *
     *  Examples:
     *  <ul>
     *   <li><samp>select_second();</samp><br />
     *     Generates menu '00', '01', ..., '59'.  Initially selected
     *     second is the second in
     *     {@link $request_seconds}[{@link $attribute_name}], or if that
     *     is not defined, the current second.</li>
     *   <li><samp>select_second(null,array('include_blank' => true));</samp>
     *    <br />Generates menu ' ', '00', '01',..., '59'.  Initially
     *    selected second same as above.</li>
     *  </ul>
     *
     *  @param string  Initially selected second as two-digit number.
     *  If a value for this field is specified in
     *  {@link $request_seconds}[{@link $attribute_name}], then that second
     *  is initially selected regardless of the value of this argument.
     *  Otherwise, if the first argument is present and is a character
     *  string of two decimal digits with a value in the range
     *  '00'..'59' then that second is initially selected.  If this
     *  argument is absent or invalid, the current second is
     *  initially selected.
     *  @param mixed[] Output format options:
     *  <ul>
     *    <li><samp>'include_blank' => true</samp> Show a blank
     *      as the first option.</li>
     *    <li><samp>'field_name' => '</samp><i>somestring</i><samp>'</samp>
     *      Generate output<br />
     *      <samp><select name="</samp><i>somestring</i><samp>">...</select></samp> .
     *      <br />If absent, generate output<br />
     *      <samp><select name="second">...</select></samp>.</li>
     *    <li><samp>'discard_type' => ???</samp> FIXME</li>
     *    <li><samp>'prefix' => ???</samp> FIXME</li>
     *  </ul>
     *
     *  @return string  Generated HTML
     *  @uses attribute_name
     *  @uses leading_zero_on_single_digits()
     *  @uses request_seconds
     *  @uses select_html()
     */
    function select_second($datetime=null, $options = array()) {
        //error_log("select_second() \$datetime=$datetime  \$options="
        //          .var_export($options,true));
        $second_options = "";
        
        if($this->request_seconds[$this->attribute_name]) {
            $datetime_sec = $this->request_seconds[$this->attribute_name];    
        } elseif(strlen($datetime) == 2 && is_numeric($datetime)) {
            $datetime_sec = $datetime;
        } else {                  
            $datetime = $options['value'] ? $options['value'] :
                ($datetime ? $datetime : date("Y-m-d H:i:s")); 
            $datetime_sec = date("s",strtotime($datetime));
        }
        
        for($second = 0; $second <= 59; $second++) {          
            $second_options .= ($datetime && ($datetime_sec == $second)) ?
            "<option value=\"".$this->leading_zero_on_single_digits($second)."\"  selected=\"selected\">".$this->leading_zero_on_single_digits($second)."</option>\n" :
            "<option value=\"".$this->leading_zero_on_single_digits($second)."\">".$this->leading_zero_on_single_digits($second)."</option>\n";
        }
        $field_name = array_key_exists('field_name',$options)
                       ? $options['field_name'] : 'second';
        return $this->select_html($field_name, $second_options,
                                  array_key_exists('prefix',$options)
                                  ? $options['prefix'] : null,
                                  array_key_exists('include_blank',$options)
                                  ? $options['include_blank'] : false,
                                  array_key_exists('discard_type',$options)
                                  ? $options['discard_type'] : false);
    }

    /**
     *  Generate HTML/XML for minute selector pull-down menu
     *
     *  Returns <samp><select>...</select></samp> HTML with an option
     *  for each of the sixty minutes.  The first argument, if
     *  present, specifies the initially selected minute.  The second
     *  argument controls the format of the generated HTML.
     *
     *  Examples:
     *  <ul>
     *   <li><samp>select_minute();</samp><br />
     *     Generates menu '00', '01', ..., '59'.  Initially selected
     *     minute is the minute in
     *     {@link $request_minutes}[{@link $attribute_name}], or if that
     *     is not defined, the current minute.</li>
     *   <li><samp>select_minute(null,array('include_blank' => true));</samp>
     *    <br />Generates menu ' ', '00', '01',..., '59'.  Initially
     *    selected minute same as above.</li>
     *  </ul>
     *
     *  @param string  Initially selected minute as two-digit number.
     *  If a value for this field is specified in
     *  {@link $request_minutes}[{@link $attribute_name}], then that minute
     *  is initially selected regardless of the value of this argument.
     *  Otherwise, if the first argument is present and is a character
     *  string of two decimal digits with a value in the range
     *  '00'..'59' then that minute is initially selected.  If this
     *  argument is absent or invalid, the current minute is
     *  initially selected.
     *  @param mixed[] Output format options:
     *  <ul>
     *    <li><samp>'include_blank' => true</samp> Show a blank
     *      as the first option.</li>
     *    <li><samp>'field_name' => '</samp><i>somestring</i><samp>'</samp>
     *      Generate output<br />
     *      <samp><select name="</samp><i>somestring</i><samp>">...</select></samp> .
     *      <br />If absent, generate output<br />
     *      <samp><select name="minute">...</select></samp>.</li>
     *    <li><samp>'discard_type' => ???</samp> FIXME</li>
     *    <li><samp>'prefix' => ???</samp> FIXME</li>
     *  </ul>
     *
     *  @return string  Generated HTML
     *  @uses attribute_name
     *  @uses leading_zero_on_single_digits()
     *  @uses request_minutes
     *  @uses select_html()
     */
    function select_minute($datetime=null, $options = array()) {
        $minute_options = "";
        
        if($this->request_minutes[$this->attribute_name]) {
            $datetime_min = $this->request_minutes[$this->attribute_name];    
        } elseif(strlen($datetime) == 2 && is_numeric($datetime)) {
            $datetime_min = $datetime;
        } else {                  
            #$datetime = $datetime ? $datetime : date("Y-m-d H:i:s"); 
            $datetime = $options['value'] ? $options['value'] :
                ($datetime ? $datetime : date("Y-m-d H:i:s"));            
            $datetime_min = date("i",strtotime($datetime));
        }
        
        for($minute = 0; $minute <= 59; $minute++) {        
            $minute_options .= ($datetime && ($datetime_min == $minute)) ?
            "<option value=\"".$this->leading_zero_on_single_digits($minute)."\"  selected=\"selected\">".$this->leading_zero_on_single_digits($minute)."</option>\n" :
            "<option value=\"".$this->leading_zero_on_single_digits($minute)."\">".$this->leading_zero_on_single_digits($minute)."</option>\n";
        }
        $field_name = array_key_exists('field_name', $options)
            ? $options['field_name'] : 'minute';
        return $this->select_html($field_name, $minute_options,
                                  array_key_exists('prefix', $options)
                                  ? $options['prefix'] : null,
                                  array_key_exists('include_blank', $options)
                                  ? $options['include_blank'] : false,
                                  array_key_exists('discard_type', $options)
                                  ? $options['discard_type'] : false);
    }

    /**
     *  Generate HTML/XML for hour selector pull-down menu
     *
     *  Returns <samp><select>...</select></samp> HTML with an option
     *  for each of the twenty-four hours.  The first argument, if
     *  present, specifies the initially selected hour.  The second
     *  argument controls the format of the generated HTML.
     *
     *  Examples:
     *  <ul>
     *   <li><samp>select_hour();</samp><br />
     *     Generates menu '00', '01', ..., '23'.  Initially selected
     *     hour is the hour in
     *     {@link $request_hours}[{@link $attribute_name}], or if that
     *     is not defined, the current hour.</li>
     *   <li><samp>select_hour(null,array('include_blank' => true));</samp>
     *    <br />Generates menu ' ', '00', '01',..., '23'.  Initially
     *    selected hour same as above.</li>
     *  </ul>
     *
     *  @param string  Initially selected hour as two-digit number.
     *  If a value for this field is specified in
     *  {@link $request_hours}[{@link $attribute_name}], then that hour
     *  is initially selected regardless of the value of this argument.
     *  Otherwise, if the first argument is present and is a character
     *  string of two decimal digits with a value in the range
     *  '00'..'23' then that hour is initially selected.  If this
     *  argument is absent or invalid, the current hour is
     *  initially selected.
     *  @param mixed[] Output format options:
     *  <ul>
     *    <li><samp>'include_blank' => true</samp> Show a blank
     *      as the first option.</li>
     *    <li><samp>'field_name' => '</samp><i>somestring</i><samp>'</samp>
     *      Generate output<br />
     *      <samp><select name="</samp><i>somestring</i><samp>">...</select></samp> .
     *      <br />If absent, generate output<br />
     *      <samp><select name="hour">...</select></samp>.</li>
     *    <li><samp>'discard_type' => ???</samp> FIXME</li>
     *    <li><samp>'prefix' => ???</samp> FIXME</li>
     *  </ul>
     *
     *  @return string  Generated HTML
     *  @uses attribute_name
     *  @uses leading_zero_on_single_digits()
     *  @uses request_hours
     *  @uses select_html()
     */
    function select_hour($datetime=null, $options = array()) {
        //error_log("DateTime::select_hour() \$datetime=$datetime \$options="
        //          .var_export($options,true));
        $hour_options = "";
        
        //  If a value for this attribute was parsed from $_REQUEST,
        //  use it as initially selected and ignore first argument
        if($this->request_hours[$this->attribute_name]) {
            $datetime_hour = $this->request_hours[$this->attribute_name];    
        }

        //  No value in $_REQUEST so look at the first argument.
        //  If it is valid use it as initially selected
        elseif(strlen($datetime) == 2 && is_numeric($datetime)) {
            $datetime_hour = $datetime;
        }

        //  First argument is missing or invalid,
        //  initially select current hour
        else {                  
            #$datetime = $datetime ? $datetime : date("Y-m-d H:i:s"); 
            $datetime = $options['value'] ? $options['value'] :
                ($datetime ? $datetime : date("Y-m-d H:i:s"));
            $datetime_hour = date("H",strtotime($datetime)); 
        }

        //  Generate <option>...</option> HTML for each hour
        for($hour = 0; $hour <= 23; $hour++) {
            $hour_options .= ($datetime && ($datetime_hour == $hour)) ?
            "<option value=\"".$this->leading_zero_on_single_digits($hour)."\"  selected=\"selected\">".$this->leading_zero_on_single_digits($hour)."</option>\n" :
            "<option value=\"".$this->leading_zero_on_single_digits($hour)."\">".$this->leading_zero_on_single_digits($hour)."</option>\n";
        }

        //  Return finished HTML
        $field_name = array_key_exists('field_name', $options)
            ? $options['field_name'] : 'hour';
        return $this->select_html($field_name, $hour_options,
                                  array_key_exists('prefix', $options)
                                  ? $options['prefix'] : null,
                                  array_key_exists('include_blank', $options)
                                  ? $options['include_blank'] : false,
                                  array_key_exists('discard_type', $options)
                                  ? $options['discard_type'] : false);
    }

    /**
     *  Generate HTML/XML for day selector pull-down menu
     *
     *  Returns <samp><select>...</select></samp> HTML with an option
     *  for each of the thirty-one days.  The first argument, if
     *  present, specifies the initially selected day.  The second
     *  argument controls the format of the generated HTML.
     *
     *
     *  Examples:
     *  <ul>
     *   <li><samp>select_day();</samp><br />
     *     Generates menu '01', '02', ..., '31'.  Initially selected
     *     day is the day in
     *     {@link $request_days}[{@link $attribute_name}], or if that
     *     is not defined, the current calendar day.</li>
     *   <li><samp>select_day(null,array('include_blank' => true));</samp>
     *    <br />Generates menu ' ', '01', '02',..., '31'.  Initially
     *    selected day same as above.</li>
     *  </ul>
     *
     *  @param string  Initially selected day as two-digit number.
     *  If a value for this field is specified in
     *  {@link $request_days}[{@link $attribute_name}], then that day
     *  is initially selected regardless of the value of this argument.
     *  Otherwise, if the first argument is present and is a character
     *  string of two decimal digits with a value in the range
     *  '01'..'31' then that day is initially selected.  Otherwise, if
     *  the first argument is a date in some US English date format,
     *  the day of the month from that date is initially selected.  If
     *  this argument is absent or invalid, the current calendar day
     *  is initially selected.
     *  @param mixed[] Output format options:
     *  <ul>
     *    <li><samp>'include_blank' => true</samp> Show a blank
     *      as the first option.</li>
     *    <li><samp>'field_name' => '</samp><i>somestring</i><samp>'</samp>
     *      Generate output<br />
     *      <samp><select name="</samp><i>somestring</i><samp>">...</select></samp> .
     *      <br />If absent, generate output<br />
     *      <samp><select name="day">...</select></samp>.</li>
     *    <li><samp>'discard_type' => ???</samp> FIXME</li>
     *    <li><samp>'prefix' => ???</samp> FIXME</li>
     *  </ul>
     *
     *  @return string  Generated HTML
     *  @uses attribute_name
     *  @uses leading_zero_on_single_digits()
     *  @uses request_days
     *  @uses select_html()
     */
    function select_day($datetime=null, $options = array()) {
        $day_options = "";
        
        //  If a value for this attribute was parsed from $_REQUEST,
        //  use it as initially selected and ignore first argument
        if($this->request_days[$this->attribute_name]) {
            $datetime_day = $this->request_days[$this->attribute_name];    
        }

        //  No value in $_REQUEST so look at the first argument.
        //  If it is valid use it as initially selected
        elseif(strlen($datetime) == 2 && is_numeric($datetime)) {
            $datetime_day = $datetime;
        }

        //  First argument is missing or invalid,
        //  initially select current day
        else {                  
            #$datetime = $datetime ? $datetime : date("Y-m-d H:i:s");
            $datetime = $options['value'] ? $options['value'] :
                ($datetime ? $datetime : date("Y-m-d H:i:s"));
            $datetime_day = date("d",strtotime($datetime));  
        }
        
        //  Generate <option>...</option> HTML for each day
        for($day = 1; $day <= 31; $day++) {        
            $day_options .= ($datetime && ($datetime_day == $day)) ?
            "<option value=\"".$this->leading_zero_on_single_digits($day)."\"  selected=\"selected\">".$this->leading_zero_on_single_digits($day)."</option>\n" :
            "<option value=\"".$this->leading_zero_on_single_digits($day)."\">".$this->leading_zero_on_single_digits($day)."</option>\n";
        }

        //  Return finished HTML
        $field_name = array_key_exists('field_name', $options)
            ? $options['field_name'] : 'day';
        return $this->select_html($field_name, $day_options,
                                  array_key_exists('prefix',$options)
                                  ? $options['prefix'] : null,
                                  array_key_exists('include_blank', $options)
                                  ? $options['include_blank'] : false,
                                  array_key_exists('discard_type', $options)
                                  ? $options['discard_type'] : false);
    }

    /**
     *  Generate HTML/XML for month selector pull-down menu
     *
     *  Returns <samp><select>...</select></samp> HTML with an option
     *  for each of the twelve months.  The first argument, if
     *  present, specifies the initially selected month.  The second
     *  argument controls the format of the generated HTML.
     *
     *
     *  Examples:
     *  <ul>
     *   <li><samp>select_month();</samp> Generates menu January,
     *    February etc.</li>
     *   <li><samp>select_month(null,array('use_month_number' => true));</samp>
     *    Generates menu 1, 2 etc.</li>
     *   <li><samp>select_month(null,array('add_month_number' => true));</samp>
     *    Generates menu 1 - January, 2 - February etc.</li>
     *  </ul>
     *
     *  @param string  Initially selected month as two-digit number.
     *  If a value for this field is specified in
     *  {@link $request_months}[{@link $attribute_name}], then that month
     *  is initially selected regardless of the value of this argument.
     *  Otherwise, if the first argument is present and is a character
     *  string of two decimal digits with a value in the range
     *  '01'..'12' then that month is initially selected.  Otherwise,
     *  if the first argument is a date in some US English date
     *  format, the month from that date is initially selected. If
     *  this argument is absent or invalid, the current calendar month
     *  is initially selected.
     *  @param mixed[] Output format options:
     *  <ul>
     *    <li><samp>'include_blank' => true</samp> Show a blank
     *      as the first option.</li>
     *    <li><samp>'use_month_number' => true</samp> Show months in
     *      the menu by their month number (1, 2 ...).  Default is to
     *      show English month name (January, February ...).</li>
     *    <li><samp>'add_month_number' => true</samp> Show both month
     *      number and month name in the menu.</li>
     *    <li><samp>'field_name' => '</samp><i>somestring</i><samp>'</samp>
     *      Generate output<br />
     *      <samp><select name="</samp><i>somestring</i><samp>">...</select></samp> .
     *      <br />If absent, generate output<br />
     *      <samp><select name="month">...</select></samp>.</li>
     *    <li><samp>'discard_type' => ???</samp> FIXME</li>
     *    <li><samp>'prefix' => ???</samp> FIXME</li>
     *  </ul>
     *  In all cases the value sent to the server is the two digit
     *  month number in the range '01'..'12'.
     *
     *  @return string  Generated HTML
     *  @uses attribute_name
     *  @uses leading_zero_on_single_digits()
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

        //  Parse initially selected month from US English description
        //  in first argument if present, otherwise select current month
        else {
            $date = $options['value'] ? $options['value'] :
                ($date ? $date : date("Y-m-d H:i:s"));
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
     *  Generate HTML/XML for year selector pull-down menu
     *
     *  Returns <samp><select>...</select></samp> HTML with options
     *  for a number of years.  The first argument, if present,
     *  specifies the initially selected year.  The second 
     *  argument controls the format of the generated HTML.
     *
     *  Examples:
     *  <ul>
     *   <li><samp>select_year();</samp><br /> Generates a pulldown menu with
     *     with a range of +/- five years.  If a year is specified in
     *     {@link $request_years}[{@link $attribute_name}] then it is
     *     selected initially, otherwise the current calendar year is
     *     selected.</li> 
     *   <li>
     *  <samp>select_year(null,array('start_year' => '1900));</samp><br />
     *    Generates year options from 1900 to five years after the
     *    initially selected year, which is chosen as in the previous
     *    example.</li> 
     *   <li><samp>select_year(null,array('start_year'=>date('Y')+5, 'end_year'=>date('Y')-5);</samp><br />
     *    Generates year options starting five years after the current year,
     *    ending five years before the current year.
     *  </ul>
     *
     *  @param string   Year to display as initially selected if none
     *    was found in {@link $request_years}[{@link $attribute_name}].
     *    Character string is either exactly four decimal
     *    digits or some English date representation.  If omitted, the
     *    current year is initially selected.
     *
     *  @param mixed[] Output format options:
     *  <ul>
     *    <li><samp>'start_year'=>'</samp><i>startyear</i><samp>'</samp>
     *      If specified, <i>startyear</i> will be the first year in
     *      the output menu, otherwise the first year in the menu will
     *      be five years before the initially selected year.</li>
     *    <li><samp>'end_year'=>'</samp><i>endyear</i><samp>'</samp>
     *      If specified, <i>endyear</i> will be the last year in
     *      the output menu, otherwise the last year in the menu will
     *      be five years after the initially selected year.</li>
     *    <li><samp>'field_name' => '</samp><i>somestring</i><samp>'</samp>
     *      Generate output<br />
     *      <samp><select name="</samp><i>somestring</i><samp>">...</select></samp> .
     *      <br />If absent, generate output<br />
     *      <samp><select name="year">...</select></samp>.</li>
     *    <li><samp>'discard_type' => ???</samp> FIXME</li>
     *    <li><samp>'prefix' => ???</samp> FIXME</li>
     *  </ul>
     *  To generate a list with most recent year first, define that
     *  year as the start year and the oldest year as the end year.
     *  @return string  Generated HTML
     *  @uses attribute_name
     *  @uses request_years
     *  @uses select_html
     *  @uses year_option()
     */
    function select_year($date=null, $options = array()) {
        //error_log("select_year('" . (is_null($date) ? 'null' : $date)
        //          ."', " . var_export($options,true));
        //error_log('request_years='
        //     .var_export($this->request_years[$this->attribute_name],true));
        $year_options = "";

        //  Find the year to display.
        if($this->request_years[$this->attribute_name]) {

            //  There was a value for this attribute in $_REQUEST
            //  so display it as the initial choice
            $date_year = $this->request_years[$this->attribute_name];    
        } elseif(strlen($date) == 4 && is_numeric($date)) {

            //  The first argument is exactly four decimal digits
            //  so interpret that as a year
            $date_year = $date;     
        } else {

            //  If a first argument was specified, assume that it is
            //  some English date representation and convert it for
            //  use as the initial value.
            //  If first argument was null, use the current year for
            //  the initial value.
            $date = $options['value'] ? $options['value'] :
                ($date ? $date : date("Y-m-d H:i:s"));
            $date_year = date("Y",strtotime($date));
        } 

        //  Set first year to appear in the option list
        $start_year = array_key_exists('start_year',$options)
                       ? $options['start_year'] : $date_year - 5;

        //  Set last year to appear in the option list
        $end_year = array_key_exists('end_year',$options)
                     ? $options['end_year'] : $date_year + 5;

        if ($start_year < $end_year) {
            for($year = $start_year; $year <= $end_year; $year++) {
                $year_options .= $this->year_option($year, $date_year);
            }
        } else {
            for($year = $start_year; $year >= $end_year; $year--) {
                $year_options .= $this->year_option($year, $date_year);
            }
        }

        $field_name = array_key_exists('field_name',$options)
                      ? $options['field_name'] : 'year';
        return $this->select_html($field_name, $year_options,
                                  array_key_exists('prefix',$options)
                                  ? $options['prefix'] : null,
                                  array_key_exists('include_blank',$options)
                                  ? $options['include_blank'] : false,
                                  array_key_exists('discard_type',$options)
                                  ? $options['discard_type'] : false);

    }

    /**
     *  Return one HTML/XML year option, selected if so specified
     *  @param integer Year to put in the option
     *  @param integer Year that should be selected
     *  @return string HTML for one year option
     */
    function year_option($year, $date_year) {
        return "<option value=\"$year\""
            . (($date_year == $year) ? "  selected=\"selected\">" : ">")
            . "$year</option>\n";
    }

    /**
     *  Generate HTML/XML for day/month/year selector pull-down menus
     *
     *  When called, {@link $object_name} describes the
     *  {@link ActiveRecord} subclass and {@link $attribute_name}
     *  describes the attribute whose value will be set by the
     *  generated pull-down menus.  The value to be displayed
     *  initially in each menu is from $_REQUEST if present, otherwise
     *  from the database.
     *
     *  @param mixed[] Output format options
     *  <ul>
     *    <li><samp>'discard_day' => true</samp> Don't show a day of
     *      month menu. If absent or false, day menu will be output.</li>
     *    <li><samp>'discard_month' => true</samp> Don't show a month
     *      or day of the month menu.  If absent or false, month menu
     *      will be output.</li>
     *    <li><samp>'discard_type' => true</samp> (true is the 
     *      default) Don't show name of individual field, for example
     *      <samp>[year]</samp>, as part of the <samp>name</samp> value
     *      of the generated <samp><select ...></samp>.  The
     *      information to identify the field is available as a suffix 
     *      <samp>(</samp><i>n</i><samp>i)</samp> of the attribute
     *      name.</li> 
     *    <li><samp>'discard_year' => true</samp> Don't show a year
     *      menu.  If absent or false, year menu will be output.</li>
     *    <li><samp>'field_separator' => '</samp><i>string</i><samp>'</samp>
     *      String to insert between the submenus in the output.  If
     *      absent, one blank will be inserted.</li> 
     *    <li><samp>'include_blank' => true</samp> Initially show a blank
     *      selection in the menu.  If absent or false, the current  
     *      date will be shown as the initial selection.  If a value
     *      was parsed from $_REQUEST, it will be used for the initial
     *      selection regardless of this option.</li>
     *    <li><samp>'month_before_year' => true</samp>  Equivalent to
     *      <samp>'order' => array('month', 'day', 'year')</samp></li>
     *    <li><samp>'order' => array(</samp><i>elements</i><samp>)</samp>
     *      A list of the elements <samp>'month', 'day'</samp> and
     *      <samp>'year'</samp> in the order in which the menus should
     *      appear in the output. Default is
     *      <samp>'year', 'month', 'day'</samp></li>
     *    <li><b>Also:</b> options provided by {@link select_month()},
     *      {@link select_day()} and {@link select_year()}
     *  </ul>
     *  @return string Generated HTML
     *  @uses select_day()
     *  @uses select_month()
     *  @uses select_year()
     *  @uses value()
     */
    function to_date_select_tag($options = array()) {
//        error_log("to_date_select_tag() object='$this->object_name'"
//                  . " attribute='$this->attribute_name'");
//        error_log("options=".var_export($options,true));

        //  Handle historically misspelled options
        if (array_key_exists('field_seperator', $options)) {
            $options['field_separator'] = $options['field_seperator'];
            unset($options['field_seperator']);
        }
        $defaults = array('discard_type' => true);
        $options  = array_merge($defaults, $options);
        $options_with_prefix = array();

        //  Set the name of each submenu in the form
        for($i=1 ; $i <= 3 ; $i++) {
            $options_with_prefix[$i] = array_merge($options, array('prefix' =>
                  "{$this->object_name}[{$this->attribute_name}({$i}i)]"));
        }        
        
        //  Test for output option 'include_blank' == true
        if(array_key_exists('include_blank', $options)
           && $options['include_blank']) {

            //  'include_blank' is present so if no value for this
            //  attribute was parsed from $_REQUEST, show blank initially
            //  FIXME: this doesn't actually work
            $value = $this->value();
            $date = $value ? $value : null;
        } else {

            //  'include_blank' is not present so if no value for this
            //  attribute was parsed from $_REQUEST, show today's date
            //  initially
            $value = $this->value();
            $date = $value ? $value : date("Y-m-d");
        }

        //  Test for output option 'month_before_year' == true
        $date_select = array();
        if(array_key_exists('month_before_year', $options)
           && $options['month_before_year']) {

            //  'month_before_year' is present so set the default
            //  ordering of output menus accordingly
            $options['order'] = array('month', 'year', 'day');
        } elseif(!array_key_exists('order',$options)
                 ||!$options['order']) {

            //  If 'order' option not present set order from default
            $options['order'] = array('year', 'month', 'day');
        }

        $position = array('year' => 1, 'month' => 2, 'day' => 3);

        //  Evaluate 'discard_field' options to see which fields
        //  should not be represented by menus in the output
        $discard = array();
        if(array_key_exists('discard_year',$options)
           && $options['discard_year']) $discard['year']  = true;
        if(array_key_exists('discard_month',$options)
           && $options['discard_month']) $discard['month'] = true;
        if( (array_key_exists('discard_day',$options)
             &&$options['discard_day'])
            || (array_key_exists('discard_month',$options)
                && $options['discard_month'])) $discard['day'] = true;

        //  Build HTML for menus in the order determined above,
        //  except for fields to be discarded.
        foreach($options['order'] as $param) {
            if(!array_key_exists($param,$discard) || !$discard[$param]) {
                $date_select[] = call_user_func(array($this, "select_$param"),
                              $date, $options_with_prefix[$position[$param]]);
            }
        }
        
        //  HTML for each menu is in an element of $date_select[].
        //  Join the pieces of HTML with an optional field separator
        //  (default blank)
        if(count($date_select)) {
            $separator = array_key_exists('field_separator',$options)
                ? $options['field_separator'] : " ";
            $date_select = implode($separator, $date_select);            
        }

        return $date_select;
    }

    /**
     *  Generate HTML/XML for date/time pulldown menus
     *
     *  Returns <samp><select>...</select></samp> HTML with options
     *  for a number of years, months, days, hours and minutes.  The
     *  first argument, if present, specifies the initially selected
     *  date.  The second argument controls the format of the
     *  generated HTML.
     *
     *  Examples:
     *  <ul>
     *   <li><samp>to_datetime_select_tag();</samp><br /> Generates a
     *     group of five pulldown menus in the order year, month, day,
     *     hour and minute with the current date and time initially
     *     selected.</li> 
     *   <li>
     *   <li><samp>to_datetime_select_tag(array('discard_second' => false);</samp><br />
     *     Generates a group of six pulldown menus in the order year,
     *     month, day, hour, minute and second with the current date
     *     and time initially selected.</li> 
     *   <li>
     *  <samp>to_datetime_select_tag('1998-04-08 13:21:17');</samp><br />
     *    Generates a group of five pulldown menus in the order year,
     *    month, day, hour and minute with the date/time
     *    1998 August 4 13:21 initially selected.</li> 
     *  </ul>
     *
     *  @param string   Date/time to display as initially selected.
     *    Character string is any US English date representation
     *    supported by {@link strtotime()}.  If omitted, the
     *    current date/time is initially selected.
     *
     *  @param mixed[] Output format options:
     *  <ul>
     *    <li><samp>'discard_month' => true</samp><br />
     *      Output selector for only the year.</li>
     *    <li><samp>'discard_day' => true</samp><br />
     *      Output selector for only the year and month.</li>
     *    <li><samp>'discard_hour' => true</samp><br />
     *      Output selector for only the year, month and day.</li>
     *    <li><samp>'discard_minute' => true</samp><br />
     *      Output selector for only the year, month, day and
     *      hour.</li> 
     *    <li><samp>'discard_second' => false</samp><br />
     *      Output selector for year, month, day, hour, minute and
     *      second.</li> 
     *  </ul>
     *  @return string Generated HTML
     *  @uses request_days
     *  @uses request_hours
     *  @uses request_minutes
     *  @uses request_months
     *  @uses request_seconds
     *  @uses request_years
     *  @uses select_day()
     *  @uses select_hour()
     *  @uses select_minute()
     *  @uses select_month()
     *  @uses select_second()
     *  @uses select_year()
     *  @uses value()
     */
    function to_datetime_select_tag($options = array()) {
        $defaults = array('discard_type'   => true,
                          'discard_second' => true);
        $options = array_merge($defaults, $options);
        $options_with_prefix = array();
        for($i=1 ; $i <= 6 ; $i++) {
            $options_with_prefix[$i] =
                array_merge($options, array('prefix' =>
                   "{$this->object_name}[{$this->attribute_name}({$i}i)]"));
        }

        //  FIXME: this doesn't work
        if(array_key_exists('include_blank', $options)
           && $options['include_blank']) {
            $value = $this->value();
            $datetime = $value ? $value : null;
        } else {
            $value = $this->value();
            $datetime = $value ? $value : date("Y-m-d H:i:s");
        }
    
        //  Generate year pulldown
        $datetime_select = $this->select_year($datetime,
                                              $options_with_prefix[1]);

        //  Generate month pulldown if not discarded
        if(!array_key_exists('discard_month', $options)
           || !$options['discard_month']) {
            $datetime_select .= $this->select_month($datetime,
                                                    $options_with_prefix[2]);

            //  Generate day pulldown if not discarded
            if(!array_key_exists('discard_day', $options)
               || !($options['discard_day'] || $options['discard_month'])) {
                $datetime_select .= $this->select_day($datetime,
                                                      $options_with_prefix[3]);

                //  Generate hour pulldown if not discarded
                if(!array_key_exists('discard_hour', $options)
                   || !$options['discard_hour']) {
                    $datetime_select .= ' &mdash; '
                        . $this->select_hour($datetime,
                                         $options_with_prefix[4]);

                    //  Generate minute pulldown if not discarded
                    if(!array_key_exists('discard_minute', $options)
                       || !$options['discard_minute']) {
                        $datetime_select .= ' : '
                            . $this->select_minute($datetime,
                                                   $options_with_prefix[5]);

                        //  Generate second pulldown if not discarded
                        if(!array_key_exists('discard_second', $options)
                           || !$options['discard_second']) {
                            $datetime_select .= ' : '
                                . $this->select_second($datetime,
                                                    $options_with_prefix[6]);
                        }  // second
                    }      // minute
                }          // hour
            }              // day
        }                  // month
        return $datetime_select;
    }
    
    /**
     *  Generate HTML/XML for expiration month and year pulldown.
     *
     *  Calls {@link to_date_select_tag()} with options for month with
     *  number, followed by year starting this year and going seven
     *  years in the future.
     *  @param mixed[] Output format options
     *  @return string Generated HTML
     *  @uses to_date_select_tag()
     */
    function to_expiration_date_select_tag($options = array()) {
        $options['discard_day'] = true; 
        $options['month_before_year'] = true;      
        $options['use_month_numbers'] = true;   
        $options['start_year'] = date("Y");
        $options['end_year'] = date("Y") + 7;
        $options['field_separator'] = " / ";
        return $this->to_date_select_tag($options);               
    }  

    /**
     *  Generate HTML/XML for time pulldown
     *
     *  When called, {@link $object_name} describes the
     *  {@link ActiveRecord} subclass and {@link $attribute_name}
     *  describes the attribute whose value will be set by the
     *  generated pull-down menu.  The value to be displayed initially
     *  is from $_REQUEST if present, otherwise from the database.
     *
     *  @param mixed[] Output format options
     *  @return string Generated HTML
     *  @uses request_hours
     *  @uses request_minutes
     *  @uses request_seconds
     *  @uses select_time()
     *  @uses value()
     */
    function time_select($options=array()) {
        $defaults = array('discard_type' => true,
                          'discard_second' => true);
        $options = array_merge($defaults,$options);
        $options_with_prefix = array();
        for($i=4 ; $i <= 6 ; $i++) {
            $options_with_prefix[$i] =
                array_merge($options, array('prefix' =>
                 "{$this->object_name}[{$this->attribute_name}({$i}i)]"));
        }

        //  If no value for this attribute found in $_REQUEST
        //  or the model, show current time initially 
        $time = $this->value();
        $time = $time ? $time : date('H:i:s');

        //  Generate HTML for hour
        $time_select = $this->select_hour($time, $options_with_prefix[4]);

        //  Generate HTML for minute if not discarded
        if (!(array_key_exists('discard_minute', $options)
              && $options['discard_minute'])) {
            $time_select .= ' : '
                . $this->select_minute($time, $options_with_prefix[5]);

            //  Generate HTML for second if not discarded
            if (!(array_key_exists('discard_second', $options)
                  && $options['discard_second'])) {
                $time_select .= ' : '
                    . $this->select_second($time, $options_with_prefix[6]);
            }
        }
        return $time_select;
    }

    /**
     *  Generate HTML/XML for year pulldown
     *
     *  When called, {@link $object_name} describes the
     *  {@link ActiveRecord} subclass and {@link $attribute_name}
     *  describes the attribute whose value will be set by the
     *  generated pull-down menu.  The value to be displayed initially
     *  is from $_REQUEST if present, otherwise from the database.
     *
     *  @param mixed[] Output format options
     *  @return string Generated HTML
     *  @uses select_year()
     *  @uses value()
     */
    function year_select($options=array()) {
        $defaults = array('discard_type' => true,
             'prefix' =>
                   "{$this->object_name}[{$this->attribute_name}(1i)]");
        $options = array_merge($defaults,$options);

        //  If no value for this attribute found in $_REQUEST
        //  or the model, show today's date initially 
        $year = $this->value();
        if (!$year) {
            $year =
            (is_array($this->request_years)
             && array_key_exists($this->attribute_name,$this->request_years))
            ? $this->request_years[$this->attribute_name]
            : date("Y");
        }
        return $this->select_year($year.'-01-01',$options);
    }
}

/**
 *  Make a new DateHelper object and call its select_date() method
 *  @uses DateHelper::select_date()
  */
function select_date() {
    $date_helper = new DateHelper();
    $args = func_get_args();
    return call_user_func_array(array($date_helper, 'select_date'), $args);
}

/**
 *  Make a new DateHelper object and call its select_datetime() method
 *  @uses DateHelper::select_datetime()
 */
function select_datetime() {
    $date_helper = new DateHelper();
    $args = func_get_args();
    return call_user_func_array(array($date_helper, 'select_datetime'), $args);
}

/**
 *  Make a new DateHelper object and call its select_expiration_date() method
 *  @uses DateHelper::select_expiration_date()
 */
function select_expiration_date() {
    $date_helper = new DateHelper();
    $args = func_get_args();
    return call_user_func_array(array($date_helper, 'select_expiration_date'), $args);        
}

/**
 *  Make a new DateHelper object and call its datetime_select() method
 *  @param string Name of an ActiveRecord subclass
 *  @param string Name of an attribute of $object
 *  @param mixed[] Format options
 *  @uses DateHelper::datetime_select()
 *  @see ActiveRecordHelper::to_scaffold_tag()
 */
function datetime_select($object, $attribute, $options = array()) {
    $date_helper = new DateHelper($object, $attribute);
    return $date_helper->datetime_select($options);    
}

/**
 *  Make a new DateHelper object and call its date_select() method
 *  @param string Name of an ActiveRecord subclass
 *  @param string Name of an attribute of $object
 *  @param mixed[]  Output format options
 *  @return string Generated HTML
 *  @uses DateHelper::date_select()
 *  @see ActiveRecordHelper::to_scaffold_tag()
 */
function date_select($object, $attribute, $options = array()) {
    $date_helper = new DateHelper($object, $attribute);
    return $date_helper->date_select($options);    
}

/**
 *  Make a new DateHelper object and call its year_select() method
 *  @param string Name of an ActiveRecord subclass
 *  @param string Name of an attribute of $object
 *  @param mixed[] Format options
 *  @uses DateHelper::year_select()
 *  @see ActiveRecordHelper::to_scaffold_tag()
 */
function year_select($object, $attribute, $options = array()) {
    $date_helper = new DateHelper($object, $attribute);
    return $date_helper->year_select($options);    
}

/**
 *  Make a new DateHelper object and call its select_time() method
 *  @param string Name of an ActiveRecord subclass
 *  @param string Name of an attribute of $object
 *  @param mixed[] Format options
 *  @uses DateHelper::select_time()
 *  @see ActiveRecordHelper::to_scaffold_tag()
 */
function time_select($object, $attribute, $options = array()) {
    $date_helper = new DateHelper($object, $attribute);
    return $date_helper->time_select($options);    
}

/**
 *  Make a new DateHelper object and call its expiration_date_select() method
 *  @param string Name of an ActiveRecord subclass
 *  @param string Name of an attribute of $object
 *  @param mixed[] Format options
 *  @uses DateHelper::expiration_date_select()
 */
function expiration_date_select($object, $attribute, $options = array()) {
    $date_helper = new DateHelper($object, $attribute);
    return $date_helper->expiration_date_select($options);        
}

/**
 *  Make a new DateHelper object and call its select_month() method
 *
 *  Generate HTML/XML for month selector pull-down menu using only
 *  explicit month specification.<br />
 *  <b>NB:</b>  An attempt to get value of an attribute will always
 *  fail because there is no way to set
 *  {@link DateHelper::object_name} and
 *  {@link DateHelper::attribute_name}.
 *  @uses DateHelper::select_month()
 */
function select_month() {
    $date_helper = new DateHelper();
    $args = func_get_args();
    return call_user_func_array(array($date_helper, 'select_month'), $args);    
}

/**
 *  Make a new DateHelper object and call its select_day() method
 *  @uses DateHelper::select_day()
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