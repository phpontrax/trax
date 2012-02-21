<?php
/**
 *  File containing the NumberHelper class, translated from Ruby on Rails.
 *
 *  (PHP 5)
 *
 *  @package PHPonTrax
 *  @version $Id: number_helper.php $
 *  @copyright (c) 2007 Mirek Rusin translated from Ruby on Rails
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

# Provides methods for converting a numbers into formatted strings.
# Methods are provided for phone numbers, currency, percentage,
# precision, positional notation, and file size.
class NumberHelper extends Helpers {

    # Formats a +number+ into a US phone number. You can customize the format
    # in the +options+ hash.
    # * <tt>area_code</tt>  - Adds parentheses around the area code.
    # * <tt>delimiter</tt>  - Specifies the delimiter to use, defaults to "-".
    # * <tt>extension</tt>  - Specifies an extension to add to the end of the generated number
    # * <tt>country_code</tt>  - Sets the country code for the phone number.
    #
    #  number_to_phone(1235551234) => 123-555-1234
    #  number_to_phone(1235551234, array('area_code' => true))   => (123) 555-1234
    #  number_to_phone(1235551234, array('delimiter' => " "))    => 123 555 1234
    #  number_to_phone(1235551234, array('area_code' => true, 'extension' => 555))  => (123) 555-1234 x 555
    #  number_to_phone(1235551234, array('country_code' => 1))
    public static function number_to_phone($number, $options = array()) {
        if (strlen($number) > 0) {
            $number = trim("$number");
            $area_code = array_key_exists('area_code', $options) ? $options['area_code'] : null;
            $delimiter = array_key_exists('delimiter', $options) ? $options['delimiter'] : '-';
            $extension = array_key_exists('extension', $options) ? trim("$options[extension]") : null;
            $country_code = array_key_exists('country_code', $options) ? trim("$options[country_code]") : null;
            $str = "";
            if (strlen($country_code) > 0) $str .= "+$country_code$delimiter";
            if (strlen($area_code) > 0)
                $str .= preg_replace('/([0-9]{1,3})([0-9]{3})([0-9]{4}$)/', "(\\1) \\2$delimiter\\3", $number);
            else
                $str .= preg_replace('/([0-9]{1,3})([0-9]{3})([0-9]{4})$/', "\\1$delimiter\\2$delimiter\\3", $number);
            if (strlen($extension)) $str .= " x $extension";
            return $str;
        } else {
            return null;
        }
    }

    # Formats a +number+ into a currency string. You can customize the format
    # in the +options+ hash.
    # * <tt>precision</tt>  -  Sets the level of precision, defaults to 2
    # * <tt>unit</tt>  - Sets the denomination of the currency, defaults to "$"
    # * <tt>separator</tt>  - Sets the separator between the units, defaults to "."
    # * <tt>delimiter</tt>  - Sets the thousands delimiter, defaults to ","
    #
    #  number_to_currency(1234567890.50)     => $1,234,567,890.50
    #  number_to_currency(1234567890.506)    => $1,234,567,890.51
    #  number_to_currency(1234567890.506, array('precision' => 3))    => $1,234,567,890.506
    #  number_to_currency(1234567890.50, array('unit' => "&pound;", 'separator' => ",", 'delimiter' => ""))
    #     => &pound;1234567890,50
    public static function number_to_currency($number, $options = array()) {
        $precision = array_key_exists('precision', $options) ? $options['precision'] : 2;
        $unit = array_key_exists('unit', $options) ? $options['unit'] : '$';
        $separator = $precision > 0 ? ($options['separator'] !== null ? $options['separator'] : '.') : "";
        $delimiter = array_key_exists('delimiter', $options) ? $options['delimiter'] : ',';
        $parts = split(".", self::number_with_precision($number, $precision));
        return $unit . self::number_with_delimiter($parts[0], $delimiter) . $separator . $parts[1];
    }

    # Formats a +number+ as a percentage string. You can customize the
    # format in the +options+ hash.
    # * <tt>precision</tt>  - Sets the level of precision, defaults to 3
    # * <tt>separator</tt>  - Sets the separator between the units, defaults to "."
    #
    #  number_to_percentage(100)    => 100.000%
    #  number_to_percentage(100, array('precision' => 0))   => 100%
    #  number_to_percentage(302.0574, array('precision' => 2))   => 302.06%
    public static function number_to_percentage($number, $options = array()) {
        $precision = array_key_exists("precision", $options) ? $options["precision"] : 3;
        $separator = array_key_exists("separator", $options) ? $options["separator"] : ".";
        $number = self::number_with_precision($number, $precision);
        $parts = split(".", $number);
        if ($parts[1] === null) {
            return $parts[0] . "%";
        } else {
            return $parts[0] . $separator . $parts[1] . "%";
        }
    }


    # Formats a +number+ with grouped thousands using +delimiter+. You
    # can customize the format using optional <em>delimiter</em> and <em>separator</em> parameters.
    # * <tt>delimiter</tt>  - Sets the thousands delimiter, defaults to ","
    # * <tt>separator</tt>  - Sets the separator between the units, defaults to "."
    #
    #  number_with_delimiter(12345678)      => 12,345,678
    #  number_with_delimiter(12345678.05)   => 12,345,678.05
    #  number_with_delimiter(12345678, ".")   => 12.345.678
    public static function number_with_delimiter($number, $delimiter = ",", $separator = ".") {
        $parts = split(".", $number);
        $parts[0] = preg_replace('/(\d)(?=(\d\d\d)+(?!\d))/', "\\1$delimiter", $parts[0]);
        return join($separator, $parts);
    }

    # Formats a +number+ with the specified level of +precision+. The default
    # level of precision is 3.
    #
    #  number_with_precision(111.2345)    => 111.235
    #  number_with_precision(111.2345, 2) => 111.24
    public static function number_with_precision($number, $precision = 3) {
        return sprintf("%01.${precision}f", $number);
    }
    
    # Formats the bytes in +size+ into a more understandable representation.
    # Useful for reporting file sizes to users. This method returns nil if 
    # +size+ cannot be converted into a number. You can change the default 
    # precision of 1 in +precision+.
    # 
    #  number_to_human_size(123)           => 123 Bytes
    #  number_to_human_size(1234)          => 1.2 KB
    #  number_to_human_size(12345)         => 12.1 KB
    #  number_to_human_size(1234567)       => 1.2 MB
    #  number_to_human_size(1234567890)    => 1.1 GB
    #  number_to_human_size(1234567890123) => 1.1 TB
    #  number_to_human_size(1234567, 2)    => 1.18 MB
    public static function number_to_human_size($size, $precision = 1) {
        $size = float($size);
        $return = null;
        if ($size == 1) $return = "1 Byte";
        elseif ($size < 1024) $return = sprintf("%d Bytes", $size);
        elseif ($size < 1024 * 1024) $return = sprintf("%.${precision}f KB", $size / (1024 * 1024));
        elseif ($size < 1024 * 1024 * 1024) $return = sprinf("%.${precision}f MB", $size / (1024 * 1024 * 1024));
        elseif ($size < 1024 * 1024 * 1024 * 1024) $return = sprinf("%.${precision}f GB", $size / (1024 * 1024 * 1024 * 1024));
        else $return = sprintf("%.${precision}f TB", $size / (1024 * 1024 * 1024 * 1024 * 1024));
        return str_replace(".0", "", $return);
    }
}

function number_to_phone($number, $options=array()) {
  return NumberHelper::number_to_phone($number, $options);
}

function number_to_currency($number, $options = array()) {
  return NumberHelper::number_to_currency($number, $options);
}

function number_to_percentage($number, $options = array()) {
  return NumberHelper::number_to_percentage($number, $options);
}

function number_with_delimiter($number, $delimiter = ",", $separator = ".") {
  return NumberHelper::number_with_delimiter($number, $delimiter, $separator);
}

function number_with_precision($number, $precision = 3) {
  return NumberHelper::number_with_precision($number, $precision);
}

function number_to_human_size($size, $precision = 1) {
  return NumberHelper::number_to_human_size($size, $precision = 1);
}

?>