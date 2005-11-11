<?
# $Id$
#
# Copyright (c) 2005 John Peterson
#
# Permission is hereby granted, free of charge, to any person obtaining
# a copy of this software and associated documentation files (the
# "Software"), to deal in the Software without restriction, including
# without limitation the rights to use, copy, modify, merge, publish,
# distribute, sublicense, and/or sell copies of the Software, and to
# permit persons to whom the Software is furnished to do so, subject to
# the following conditions:
#
# The above copyright notice and this permission notice shall be
# included in all copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
# EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
# MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
# NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
# LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
# OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
# WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

class Inflector {

    private static $plural_rules =
        array(  '/(x|ch|ss|sh)$/' => '\1es',            # search, switch, fix, box, process, address
                '/series$/' => '\1series',
                '/([^aeiouy]|qu)ies$/' => '\1y',
                '/([^aeiouy]|qu)y$/' => '\1ies',        # query, ability, agency
                '/(?:([^f])fe|([lr])f)$/' => '\1\2ves', # half, safe, wife
                '/sis$/' => 'ses',                      # basis, diagnosis
                '/([ti])um$/' => '\1a',                 # datum, medium
                '/person$/' => 'people',                # person, salesperson
                '/man$/' => 'men',                      # man, woman, spokesman
                '/child$/' => 'children',               # child
                '/(.+)status$/' => '\1statuses',
                '/s$/' => 's',                          # no change (compatibility)
                '/$/' => 's'
        );

    private static $singular_rules =
        array(  '/(x|ch|ss)es$/' => '\1',
                '/movies$/' => 'movie',
                '/series$/' => 'series',
                '/([^aeiouy]|qu)ies$/' => '\1y',
                '/([lr])ves$/' => '\1f',
                '/([^f])ves$/' => '\1fe',
                '/(analy|ba|diagno|parenthe|progno|synop|the)ses$/' => '\1sis',
                '/([ti])a$/' => '\1um',
                '/people$/' => 'person',
                '/men$/' => 'man',
                '/(.+)status$/' => '\1status',
                '/children$/' => 'child',
                '/news$/' => 'news',
                '/s$/' => ''
        );

    function pluralize($word) {
        $original = $word;
        foreach(self::$plural_rules as $rule => $replacement) {
            $word = preg_replace($rule,$replacement,$word);
            if($original != $word) break;
        }
        return $word;
    }

    function singularize($word) {
        $original = $word;
        foreach(self::$singular_rules as $rule => $replacement) {
            $word = preg_replace($rule,$replacement,$word);
            if($original != $word) break;
        }
        return $word;
    }

    function camelize($lower_case_and_underscored_word) {
        return str_replace(" ","",ucwords(str_replace("_"," ",$lower_case_and_underscored_word)));
    }

    function underscore($camel_cased_word) {
        $camel_cased_word = preg_replace('/([A-Z]+)([A-Z])/','\1_\2',$camel_cased_word);
        return strtolower(preg_replace('/([a-z])([A-Z])/','\1_\2',$camel_cased_word));
    }

    function humanize($lower_case_and_underscored_word) {
        return ucwords(str_replace("_"," ",$lower_case_and_underscored_word));
    }

    function tableize($class_name) {
        return self::pluralize(self::underscore($class_name));
    }

    function classify($table_name) {
        return self::camelize(self::singularize($table_name));
    }

    function foreign_key($class_name) {
        return self::underscore($class_name) . "_id";
    }

}


?>