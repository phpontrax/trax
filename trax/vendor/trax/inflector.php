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


include_once("inflections.php");

/**
 *  Implement the Trax naming convention
 *
 *  This class provides static methods to implement the
 *  {@tutorial PHPonTrax/naming.pkg Trax naming convention}.
 *  Inflector is never instantiated.
 *  @tutorial PHPonTrax/Inflector.cls
 */
class Inflector {
	
	private static $cache = array(
		'plural' => array(), 
		'singular' => array()
	);

    /**
     *  Pluralize a word according to English rules
     *
     *  Convert a lower-case singular word to plural form.
     *  If $count > 0 then prefixes $word with the $count
     *
     *  @param  string $singular_word  Word to be pluralized
     *  @param  int $count How many of these $words are there
     *  @return string  Plural of $singular_word
     */
    public static function pluralize($singular_word, $count = 0, $plural_word = null) {
		if($count != 1) {
			if(is_null($plural_word)) {	
				$plural_word = $singular_word;	
				if(isset(self::$cache['plural'][$singular_word])) {
					$plural_word = self::$cache['plural'][$singular_word];
		        } elseif(!in_array($singular_word, Inflections::$uncountables)) {   
					foreach(Inflections::$plurals as $plural_rule) {
						if(preg_match($plural_rule['rule'], $singular_word)) {
							$plural_word = preg_replace($plural_rule['rule'], $plural_rule['replacement'], $plural_word);
							self::$cache['plural'][$singular_word] = $plural_word;
							break;
						}	
					}
		        } 
			}
        } else {
			$plural_word = self::singularize($singular_word);
		}
		return $plural_word;
    }

    /**
     *  Singularize a word according to English rules 
     *
     *  @param  string $plural_word  Word to be singularized
     *  @return string  Singular of $plural_word
     */
    public static function singularize($plural_word) {
		$singular_word = $plural_word;  
        if(isset(self::$cache['singular'][$plural_word])) {
			$singular_word = self::$cache['singular'][$plural_word];
		} elseif(!in_array($plural_word, Inflections::$uncountables)) {              
            foreach(Inflections::$singulars as $singular_rule) {
				if(preg_match($singular_rule['rule'], $plural_word)) {
					$singular_word = preg_replace($singular_rule['rule'], $singular_rule['replacement'], $singular_word);
					self::$cache['singular'][$plural_word] = $singular_word;
					break;
				}	
            }
        }
        return $singular_word;
    }

    /**
     *  Convert a phrase from the lower case and underscored form
     *  to the camel case form
     *
     *  @param string $lower_case_and_underscored_word  Phrase to
     *                                                  convert
     *  @return string  Camel case form of the phrase
     */
    public static function camelize($lower_case_and_underscored_word) {
        return str_replace(" ","",ucwords(str_replace("_"," ",$lower_case_and_underscored_word)));
    }

    /**
     *  Convert a word or phrase into a title format "Welcome To My Site"
     *
     *  @param string $word  A word or phrase
     *  @return string A string that has all words capitalized and splits on existing caps.
     */    
    public static function titleize($word) {
		return ucwords(self::humanize(self::underscore($word)));
    }

    /**
     *  Convert a phrase from the camel case form to the lower case
     *  and underscored form
     *
     *  Changes '::' to '/' to convert namespaces to paths. (php 5.3)
     * 
     *  Examples:
     *    Inflector::underscore("ActiveRecord") => "active_record"
     *    Inflector::underscore("ActiveRecord::Errors") => active_record/errors
     * 
     *  @param string $camel_cased_word  Phrase to convert
     *  @return string Lower case and underscored form of the phrase
     */
    public static function underscore($camel_cased_word) {
		$camel_cased_word = str_replace('::','/',$camel_cased_word);
        $camel_cased_word = preg_replace('/([A-Z]+)([A-Z])/','\1_\2',$camel_cased_word);
        return strtolower(preg_replace('/([a-z\d])([A-Z])/','\1_\2',$camel_cased_word));
    }

    /**
     *  Convert a word's underscores into dashes
     *
     *  @param string $underscored_word  Word to convert
     *  @return string All underscores converted to dashes
     */    
    public static function dasherize($underscored_word) {
        return str_replace('_', '-', self::underscore($underscored_word));
    }

    /**
     *  Generate a more human version of a lower case underscored word
     *
     *  @param string $lower_case_and_underscored_word  A word or phrase in
     *                                           lower_case_underscore form
     *  @return string The input value with underscores replaced by
     *  blanks and the first letter of each word capitalized
     */
    public static function humanize($lower_case_and_underscored_word) {
		if(count(Inflections::$humans) > 0) {
	        $original = $lower_case_and_underscored_word;   
	        foreach(Inflections::$humans as $human_rule) {
	            $lower_case_and_underscored_word = preg_replace($human_rule['rule'], $human_rule['replacement'], $word);
	            if($original != $lower_case_and_underscored_word) break;
	        }	
		}
        return self::capitalize(str_replace(array("_","_id"),array(" ",""),$lower_case_and_underscored_word));
    }

    /** 
     *  Removes the module part from the expression in the string. (php 5.3)
     * 
     *  Examples:
     *		Inflector::demodulize("ActiveRecord::CoreExtensions::String::Inflections") => "Inflections"
     *  	Inflector::demodulize("Inflections") => "Inflections"
     * 
	 */
	public static function demodulize($class_name_in_module) {
		return preg_replace("/^.*::/", '', $class_name_in_module);
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
    public static function tableize($class_name) {
        return self::pluralize(self::underscore($class_name));
    }

    /**
     *  Convert a table name to the corresponding class name
     *
     *  @param string $table_name Name of table in the database
     *  @return string Singular CamelCase form of $table_name
     */
    public static function classify($table_name) {
        return self::camelize(self::singularize($table_name));
    }

    /**
     *  Capitalize a word making it all lower case with first letter uppercase 
     *
     *  @param  string $word  Word to be capitalized
     *  @return string Capitalized $word
     */
    public static function capitalize($word) {
    	return ucfirst(strtolower($word));     
    }
    
    /**
     *  Get foreign key column corresponding to a table name
     *
     *  @param string $table_name Name of table referenced by foreign key
     *  @return string Column name of the foreign key column
     */
    public static function foreign_key($class_name) {
        return self::underscore(self::demodulize($class_name)) . "_id";
    }


    /**
     *  Add to a number st, nd, rd, th
     *
     *  @param integer $number Number to append to key
     *  @return string Number formatted with correct st, nd, rd, or th
     */    
    public static function ordinalize($number) {
        $number = intval($number);
		if(in_array(($number % 100), range(11, 13))) {
            $number = "{$number}th";
        } else {
            switch(($number % 10)) {
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

    /**
     *  Clears the cached words for pluralize and singularize
     *
     *  @param none
     *  @return nothing
     */
	public static function clear_cache() {
		self::$cache = array(
			'plural' => array(), 
			'singular' => array()
		);		
	}
    
}


?>