<?php
/**
 *  File containing the Inflections class and default inflections
 *
 *  (PHP 5)
 *
 *  @package PHPonTrax
 *  @version $Id: inflector.php 195 2006-04-03 22:27:26Z haas $
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


# Start Default Inflections

Inflections::plural('/$/', 's');
Inflections::plural('/s$/i', 's');
Inflections::plural('/(ax|test)is$/i', '\1es');
Inflections::plural('/(octop|vir)us$/i', '\1i');
Inflections::plural('/(alias|status)$/i', '\1es');
Inflections::plural('/(bu)s$/i', '\1ses');
Inflections::plural('/(buffal|tomat)o$/i', '\1oes');
Inflections::plural('/([ti])um$/i', '\1a');
Inflections::plural('/sis$/i', 'ses');
Inflections::plural('/(?:([^f])fe|([lr])f)$/i', '\1\2ves');
Inflections::plural('/(hive)$/i', '\1s');
Inflections::plural('/([^aeiouy]|qu)y$/i', '\1ies');
Inflections::plural('/([^aeiouy]|qu)ies$/i', '\1y');
Inflections::plural('/(x|ch|ss|sh)$/i', '\1es');
Inflections::plural('/(matr|vert|ind)ix|ex$/i', '\1ices');
Inflections::plural('/([m|l])ouse$/i', '\1ice');
Inflections::plural('/^(ox)$/i', '\1en');
Inflections::plural('/(quiz)$/i', '\1zes');

Inflections::singular('/s$/i', '');
Inflections::singular('/(n)ews$/i', '\1ews'); 
Inflections::singular('/([ti])a$/i', '\1um'); 
Inflections::singular('/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i', '\1\2sis'); 
Inflections::singular('/(^analy)ses$/i', '\1sis'); 
Inflections::singular('/([^f])ves$/i', '\1fe'); 
Inflections::singular('/(hive)s$/i', '\1'); 
Inflections::singular('/(tive)s$/i', '\1'); 
Inflections::singular('/([lr])ves$/i', '\1f'); 
Inflections::singular('/([^aeiouy]|qu)ies$/i', '\1y'); 
Inflections::singular('/(s)eries$/i', '\1eries'); 
Inflections::singular('/(m)ovies$/i', '\1ovie'); 
Inflections::singular('/(x|ch|ss|sh)es$/i', '\1'); 
Inflections::singular('/([m|l])ice$/i', '\1ouse'); 
Inflections::singular('/(bus)es$/i', '\1'); 
Inflections::singular('/(o)es$/i', '\1'); 
Inflections::singular('/(shoe)s$/i', '\1'); 
Inflections::singular('/(cris|ax|test)es$/i', '\1is'); 
Inflections::singular('/([octop|vir])i$/i', '\1us'); 
Inflections::singular('/(alias|status)es$/i', '\1'); 
Inflections::singular('/^(ox)en/i', '\1'); 
Inflections::singular('/(vert|ind)ices$/i', '\1ex'); 
Inflections::singular('/(matr)ices$/i', '\1ix'); 
Inflections::singular('/(quiz)zes$/i', '\1'); 
 
Inflections::irregular('person', 'people'); 
Inflections::irregular('man', 'men'); 
Inflections::irregular('child', 'children'); 
Inflections::irregular('sex', 'sexes'); 
Inflections::irregular('move', 'moves'); 

Inflections::uncountable('equipment', 'information', 'rice', 'money', 'species', 'series', 'fish', 'sheep');

# End Default Inflections


/**
 *  Implement the Trax naming convention
 *
 *  Inflections is never instantiated.
 */
class Inflections {

    public static $plurals = array();

    public static $singulars = array();

    public static $uncountables = array();

    # Specifies a new pluralization rule and its replacement. The rule can either be a string or a regular expression. 
    # The replacement should always be a string that may include references to the matched data from the rule.
    function plural($rule, $replacement) {
        array_unshift(self::$plurals, array("rule" => $rule, "replacement" => $replacement));
    }
    
    # Specifies a new singularization rule and its replacement. The rule can either be a string or a regular expression. 
    # The replacement should always be a string that may include references to the matched data from the rule.
    function singular($rule, $replacement) {
        array_unshift(self::$singulars, array("rule" => $rule, "replacement" => $replacement));
    }
    
    # Specifies a new irregular that applies to both pluralization and singularization at the same time. This can only be used
    # for strings, not regular expressions. You simply pass the irregular in singular and plural form.
    # 
    # Examples:
    #   Inflections::irregular('octopus', 'octopi')
    #   Inflections::irregular('person', 'people')
    function irregular($singular, $plural) {
        self::plural('/('.preg_quote(substr($singular,0,1)).')'.preg_quote(substr($singular,1)).'$/i', '\1'.preg_quote(substr($plural,1)));
        self::singular('/('.preg_quote(substr($plural,0,1)).')'.preg_quote(substr($plural,1)).'$/i', '\1'.preg_quote(substr($singular,1)));
    }
    
    # Add uncountable words that shouldn't be attempted inflected.
    # 
    # Examples:
    #   Inflections::uncountable("money")
    #   Inflections::uncountable("money", "information")
    #   Inflections::uncountable(array("money", "information", "rice"))
    function uncountable() {
        $args = func_get_args();
        if(is_array($args[0])) {
            $args = $args[0];    
        }
        foreach($args as $word) {
            self::$uncountables[] = $word;    
        }     
    }
    
    # Clears the loaded inflections within a given scope (functionault is :all). Give the scope as a symbol of the inflection type,
    # the options are: "plurals", "singulars", "uncountables"
    #
    # Examples:
    #   Inflections::clear("all")
    #   Inflections::clear("plurals")
    function clear($scope = "all") {
        if($scope == "all") {
            self::$plurals = self::$singulars = self::$uncountables = array();
        } else {
            self::$$scope = array();
        }
    }

}

?>