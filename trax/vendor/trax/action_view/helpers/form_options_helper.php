<?php
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

# All the countries included in the country_options output.
if(!$GLOBALS['COUNTRIES']) {
    $GLOBALS['COUNTRIES'] = 
        array("Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra", "Angola", "Anguilla", 
        "Antarctica", "Antigua And Barbuda", "Argentina", "Armenia", "Aruba", "Australia", 
        "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", 
        "Belgium", "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia", "Bosnia and Herzegowina", 
        "Botswana", "Bouvet Island", "Brazil", "British Indian Ocean Territory", 
        "Brunei Darussalam", "Bulgaria", "Burkina Faso", "Burma", "Burundi", "Cambodia", 
        "Cameroon", "Canada", "Cape Verde", "Cayman Islands", "Central African Republic", 
        "Chad", "Chile", "China", "Christmas Island", "Cocos (Keeling) Islands", "Colombia", 
        "Comoros", "Congo", "Congo, the Democratic Republic of the", "Cook Islands", 
        "Costa Rica", "Cote d'Ivoire", "Croatia", "Cuba", "Cyprus", "Czech Republic", "Denmark", 
        "Djibouti", "Dominica", "Dominican Republic", "East Timor", "Ecuador", "Egypt", 
        "El Salvador", "England", "Equatorial Guinea", "Eritrea", "Espana", "Estonia", 
        "Ethiopia", "Falkland Islands", "Faroe Islands", "Fiji", "Finland", "France", 
        "French Guiana", "French Polynesia", "French Southern Territories", "Gabon", "Gambia", 
        "Georgia", "Germany", "Ghana", "Gibraltar", "Great Britain", "Greece", "Greenland", 
        "Grenada", "Guadeloupe", "Guam", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana", 
        "Haiti", "Heard and Mc Donald Islands", "Honduras", "Hong Kong", "Hungary", "Iceland", 
        "India", "Indonesia", "Ireland", "Israel", "Italy", "Iran", "Iraq", "Jamaica", "Japan", "Jordan", 
        "Kazakhstan", "Kenya", "Kiribati", "Korea, Republic of", "Korea (South)", "Kuwait", 
        "Kyrgyzstan", "Lao People's Democratic Republic", "Latvia", "Lebanon", "Lesotho", 
        "Liberia", "Liechtenstein", "Lithuania", "Luxembourg", "Macau", "Macedonia", 
        "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", 
        "Martinique", "Mauritania", "Mauritius", "Mayotte", "Mexico", 
        "Micronesia, Federated States of", "Moldova, Republic of", "Monaco", "Mongolia", 
        "Montserrat", "Morocco", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepal", 
        "Netherlands", "Netherlands Antilles", "New Caledonia", "New Zealand", "Nicaragua", 
        "Niger", "Nigeria", "Niue", "Norfolk Island", "Northern Ireland", 
        "Northern Mariana Islands", "Norway", "Oman", "Pakistan", "Palau", "Panama", 
        "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Pitcairn", "Poland", 
        "Portugal", "Puerto Rico", "Qatar", "Reunion", "Romania", "Russia", "Rwanda", 
        "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent and the Grenadines", 
        "Samoa (Indep}ent)", "San Marino", "Sao Tome and Principe", "Saudi Arabia", 
        "Scotland", "Senegal", "Serbia and Montenegro", "Seychelles", "Sierra Leone", "Singapore", 
        "Slovakia", "Slovenia", "Solomon Islands", "Somalia", "South Africa", 
        "South Georgia and the South Sandwich Islands", "South Korea", "Spain", "Sri Lanka", 
        "St. Helena", "St. Pierre and Miquelon", "Suriname", "Svalbard and Jan Mayen Islands", 
        "Swaziland", "Sweden", "Switzerland", "Taiwan", "Tajikistan", "Tanzania", "Thailand", 
        "Togo", "Tokelau", "Tonga", "Trinidad", "Trinidad and Tobago", "Tunisia", "Turkey", 
        "Turkmenistan", "Turks and Caicos Islands", "Tuvalu", "Uganda", "Ukraine", 
        "United Arab Emirates", "United Kingdom", "United States", 
        "United States Minor Outlying Islands", "Uruguay", "Uzbekistan", "Vanuatu", 
        "Vatican City State (Holy See)", "Venezuela", "Viet Nam", "Virgin Islands (British)", 
        "Virgin Islands (U.S.)", "Wales", "Wallis and Futuna Islands", "Western Sahara", 
        "Yemen", "Zambia", "Zimbabwe");
}

class FormOptionsHelper extends FormHelper {
    
    # Accepts a container (hash, array, enumerable, your type) and returns a string of option tags. Given a container
    # where the elements respond to first and last (such as a two-element array), the "lasts" serve as option values and
    # the "firsts" as option text. Hashes are turned into this form automatically, so the keys become "firsts" and values
    # become lasts. If +selected+ is specified, the matching "last" or element will get the selected option-tag.  +Selected+
    # may also be an array of values to be selected when using a multiple select.
    #
    # Examples (call, result):
    #   options_for_select([["Dollar", "$"], ["Kroner", "DKK"]])
    #     <option value="$">Dollar</option>\n<option value="DKK">Kroner</option>
    #
    #   options_for_select([ "VISA", "MasterCard" ], "MasterCard")
    #     <option>VISA</option>\n<option selected="selected">MasterCard</option>
    #
    #   options_for_select({ "Basic" => "$20", "Plus" => "$40" }, "$40")
    #     <option value="$20">Basic</option>\n<option value="$40" selected="selected">Plus</option>
    #
    #   options_for_select([ "VISA", "MasterCard", "Discover" ], ["VISA", "Discover"])
    #     <option selected="selected">VISA</option>\n<option>MasterCard</option>\n<option selected="selected">Discover</option>
    #
    # NOTE: Only the option tags are returned, you have to wrap this call in a regular HTML select tag.
    function options_for_select($choices, $selected = null) {
        $options = array();
        if(is_array($choices)) {
            foreach($choices as $choice_value => $choice_text) {
                if(!empty($choice_value)) {
                    $is_selected = ($choice_value == $selected) ? true : false;   
                } else {
                    $is_selected = ($choice_text == $selected) ? true : false;        
                }
                if($is_selected) {
                    $options[] = "<option value=\"".htmlspecialchars($choice_value)."\" selected=\"selected\">".htmlspecialchars($choice_text)."</option>";
                } else {
                    $options[] = "<option value=\"".htmlspecialchars($choice_value)."\">".htmlspecialchars($choice_text)."</option>";
                }                        
            }    
        }
        return implode("\n", $options);
    }
    
    # Returns a string of option tags that have been compiled by iterating over the +collection+ and assigning the
    # the result of a call to the +value_method+ as the option value and the +text_method+ as the option text.
    # If +selected_value+ is specified, the element returning a match on +value_method+ will get the selected option tag.
    #
    # Example (call, result). Imagine a loop iterating over each +person+ in <tt>@project.people</tt> to generate an input tag:
    #   options_from_collection_for_select(@project.people, "id", "name")
    #     <option value="#{person.id}">#{person.name}</option>
    #
    # NOTE: Only the option tags are returned, you have to wrap this call in a regular HTML select tag.
    function options_from_collection_for_select($collection, $attribute_value, $attribute_text, $selected_value = null) {
        $options = array();
        if(is_array($collection)) {
            foreach($collection as $object) {
                if(is_object($object)) {
                    $options[$object->send($attribute_value)] = $object->send($attribute_text);            
                }
            }    
        }       
        return $this->options_for_select($options, $selected_value);
    }
    
    # Returns a string of option tags for pretty much any country in the world. Supply a country name as +selected+ to
    # have it marked as the selected option tag. You can also supply an array of countries as +priority_countries+, so
    # that they will be listed above the rest of the (long) list.
    #
    # NOTE: Only the option tags are returned, you have to wrap this call in a regular HTML select tag.
    function country_options_for_select($selected = null, $priority_countries = array()) {
        $country_options = "";
        
        if(count($priority_countries)) {
            $country_options .= $this->options_for_select($priority_countries, $selected);
            $country_options .= "<option value=\"\">-------------</option>\n";
            foreach($priority_countries as $country) { 
                unset($GLOBALS['COUNTRIES'][array_search($country, $GLOBALS['COUNTRIES'])]);
            }
        }
        
        $country_options .= $this->options_for_select($GLOBALS['COUNTRIES'], $selected);
 
        return $country_options;
    }
    
    function to_select_tag($choices, $options, $html_options) {
        $html_options = $this->add_default_name_and_id($html_options);
        return $this->content_tag("select", $this->add_options($this->options_for_select($choices, $this->value()), $options, $this->value()), $html_options);
    }
    
    function to_collection_select_tag($collection, $attribute_value, $attribute_text, $options, $html_options) {
        $html_options = $this->add_default_name_and_id($html_options);
        return $this->content_tag("select", $this->add_options($this->options_from_collection_for_select($collection, $attribute_value, $attribute_text, $this->value()), $options, $this->value()), $html_options);
    }
    
    function to_country_select_tag($priority_countries, $options, $html_options) {
        $html_options = $this->add_default_name_and_id($html_options);
        return $this->content_tag("select", $this->add_options($this->country_options_for_select($this->value(), $priority_countries), $options, $this->value), $html_options);
    }

    private function add_options($option_tags, $options, $value = null) {
        if($options["include_blank"] == true) {
            $option_tags = "<option value=\"\"></option>\n" . $option_tags;
        } 
        $value = $this->value();
        if(empty($value) && $options['prompt']) {
            $text = $options['prompt'] ? $options['prompt'] : "Please select";
            return ("<option value=\"\">$text</option>\n" . $option_tags);
        } else {
            return $option_tags;
        }        
    }
}

################################################################################################
## Avialble functions for use in views
################################################################################################
# Create a select tag and a series of contained option tags for the provided object and method.
# The option currently held by the object will be selected, provided that the object is available.
# See options_for_select for the required format of the choices parameter.
#
# Example with @post.person_id => 1:
#   select("post", "person_id", Person.find_all.collect {|p| [ p.name, p.id ] }, { :include_blank => true })
#
# could become:
#
#   <select name="post[person_id">
#     <option></option>
#     <option value="1" selected="selected">David</option>
#     <option value="2">Sam</option>
#     <option value="3">Tobias</option>
#   </select>
#
# This can be used to provide a functionault set of options in the standard way: before r}ering the create form, a
# new model instance is assigned the functionault options and bound to @model_name. Usually this model is not saved
# to the database. Instead, a second model object is created when the create request is received.
# This allows the user to submit a form page more than once with the expected results of creating multiple records.
# In addition, this allows a single partial to be used to generate form inputs for both edit and create forms.
function select($object_name, $attribute_name, $choices, $options = array(), $html_options = array()) {
    $form = new FormOptionsHelper($object_name, $attribute_name);
    return $form->to_select_tag($choices, $options, $html_options);
}

# Return select and option tags for the given object and method using 
# options_from_collection_for_select to generate the list of option tags.
function collection_select($object_name, $attribute_name, $collection, $attribute_value, $attribute_text, $options = array(), $html_options = array()) {
    $form = new FormOptionsHelper($object_name, $attribute_name);
    return $form->to_collection_select_tag($collection, $attribute_value, $attribute_text, $options, $html_options);
}

# Return select and option tags for the given object and method, using country_options_for_select to generate the list of option tags.
function country_select($object_name, $attribute_name, $priority_countries = null, $options = array(), $html_options = array()) {
    $form = new FormOptionsHelper($object_name, $attribute_name);
    return $form->to_country_select_tag($priority_countries, $options, $html_options);
}

?>