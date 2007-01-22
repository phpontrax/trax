<?php
/**
 *  File containing the Inflector class
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


include_once(TRAX_LIB_ROOT . "/inflections.php");

/**
 *  Implement the Trax naming convention
 *
 *  This class provides static methods to implement the
 *  {@tutorial PHPonTrax/naming.pkg Trax naming convention}.
 *  Inflector is never instantiated.
 *  @tutorial PHPonTrax/Inflector.cls
 */
class Inflector {

    /**
     *  Pluralize a word according to English rules
     *
     *  Convert a lower-case singular word to plural form.
     *  If $count > 0 then prefixes $word with the $count
     *
     *  @param  string $word  Word to be pluralized
     *  @param  int $count How many of these $words are there
     *  @return string  Plural of $word
     */
    function pluralize($word, $count = 0) {
        if($count == 0 || $count > 1) {          
            if(!in_array($word, Inflections::$uncountables)) { 
                $original = $word;   
                foreach(Inflections::$plurals as $plural_rule) {
                    $word = preg_replace($plural_rule['rule'], $plural_rule['replacement'], $word);
                    if($original != $word) break;
                }
            }
        }
        return ($count >= 1 ? "{$count} {$word}" : $word);
    }

    /**
     *  Singularize a word according to English rules 
     *
     *  @param  string $word  Word to be singularized
     *  @return string  Singular of $word
     */
    function singularize($word) {
        if(!in_array($word, Inflections::$uncountables)) { 
            $original = $word;   
            foreach(Inflections::$singulars as $singular_rule) {
                $word = preg_replace($singular_rule['rule'], $singular_rule['replacement'], $word);
                if($original != $word) break;
            }
        }
        return $word;
    }

    /**
     *  Capitalize a word making it all lower case with first letter uppercase 
     *
     *  @param  string $word  Word to be capitalized
     *  @return string Capitalized $word
     */
    function capitalize($word) {
        return ucfirst(strtolower($word));     
    }

    /**
     *  Convert a phrase from the lower case and underscored form
     *  to the camel case form
     *
     *  @param string $lower_case_and_underscored_word  Phrase to
     *                                                  convert
     *  @return string  Camel case form of the phrase
     */
    function camelize($lower_case_and_underscored_word) {
        return str_replace(" ","",ucwords(str_replace("_"," ",$lower_case_and_underscored_word)));
    }

    /**
     *  Convert a phrase from the camel case form to the lower case
     *  and underscored form
     *
     *  @param string $camel_cased_word  Phrase to convert
     *  @return string Lower case and underscored form of the phrase
     */
    function underscore($camel_cased_word) {
        $camel_cased_word = preg_replace('/([A-Z]+)([A-Z])/','\1_\2',$camel_cased_word);
        return strtolower(preg_replace('/([a-z])([A-Z])/','\1_\2',$camel_cased_word));
    }

    /**
     *  Generate a more human version of a lower case underscored word
     *
     *  @param string $lower_case_and_underscored_word  A word or phrase in
     *                                           lower_case_underscore form
     *  @return string The input value with underscores replaced by
     *  blanks and the first letter of each word capitalized
     */
    function humanize($lower_case_and_underscored_word) {
        return ucwords(str_replace("_"," ",$lower_case_and_underscored_word));
    }
    
    /**
     *  Convert a word or phrase into a title format "Welcome To My Site"
     *
     *  @param string $word  A word or phrase
     *  @return string A string that has all words capitalized and splits on existing caps.
     */    
    function titleize($word) {
        return preg_replace('/\b([a-z])/', self::capitalize('$1'), self::humanize(self::underscore($word)));
    }

    /**
     *  Convert a word's underscores into dashes
     *
     *  @param string $underscored_word  Word to convert
     *  @return string All underscores converted to dashes
     */    
    function dasherize($underscored_word) {
        return str_replace('_', '-', self::underscore($underscored_word));
    }

    /**
     *  Convert a class name to the corresponding table name
     *
     *  The class name is a singular word or phrase in CamelCase.
     *  By convention it corresponds to a table whose name is a plural
     *  word or phrase in lower case underscore form.
     *  @param string $class_name  Name of {@link ActiveRecord} sub-class
     *  @return string Pluralized lower_case_underscore form of name
     */
    function tableize($class_name) {
        return self::pluralize(self::underscore($class_name));
    }

    /**
     *  Convert a table name to the corresponding class name
     *
     *  @param string $table_name Name of table in the database
     *  @return string Singular CamelCase form of $table_name
     */
    function classify($table_name) {
        return self::camelize(self::singularize($table_name));
    }

    /**
     *  Get foreign key column corresponding to a table name
     *
     *  @param string $table_name Name of table referenced by foreign
     *    key
     *  @return string Column name of the foreign key column
     */
    function foreign_key($class_name) {
        return self::underscore($class_name) . "_id";
    }


    /**
     *  Add to a number st, nd, rd, th
     *
     *  @param integer $number Number to append to
     *    key
     *  @return string Number formatted with correct st, nd, rd, or th
     */    
    function ordinalize($number) {
        $test = (intval($number) % 100);
        if($test >= 11 && $test <= 13) {
            $number = "{$number}th";
        } else {
            switch((intval($number) % 10)) {
                case 1:
                    $number = "{$number}st";
                    break;
                case 2:
                    $number = "{$number}nd";
                    break;
                case 3:
                    $number = "{$number}rd";
                    break;
                default:
                    $number = "{$number}th"; 
            }    
        }
        return $number;
    }
    
}


?>