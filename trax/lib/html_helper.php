<?
# HTML Helper Functions

# Put each value of an array inside quotes, preserving the keys
function wrap_values_in_quotes($arr) {
  $new_array = array();
  foreach($arr as $key => $value) {
    $new_array[$key] = "\"$value\"";
  }
  return $new_array;
}

function join_keys_to_values($glue, $pieces) {
  $new_array = array();
  foreach($pieces as $key => $value) {
    $new_array[] = "{$key}{$glue}{$value}";
  }
  return $new_array;
}

function object2array($object) {
    $array = array();
    if(is_object($object)) {
       $array = convert_object($object);  
    } elseif(is_array($object)) {
        foreach($object as $value) {
            if(is_object($value)) {
                $array = array_merge(convert_object($value), $array);    
            }    
        }    
    }
    return $array;
}

function convert_object($object) {
    foreach($object as $key => $value) {
        $array[$key] = $value;    
    } 
    return $array;        
}

# A select-box input field.  $context should always be "$this".
# $values should be "key"=>"value", which translates into:
#       <option value=$key>$value</option>
function select_box($context, $object, $method, $options = array(), $values = array()) {
  $selected = $context->controller_object->$object->$method;
  $include_blank = false;
  if (count($options) > 0) {
    # If the value is set in the options array, override the default:
    if ($options['value']) {
      $selected = $options['value'];
      unset($options['value']);
    }
    # If there is a "include_blank" option, then pull it out:
    if ($options['include_blank']) {
        $include_blank = true;
        $include_blank_value = $options['include_blank'];
        unset($options['include_blank']);
    }
    # If the id is not set, default it to the method name
    if (!isset($options['id']))
        $options['id'] = $method;
   
    # Set any optional attributes for the INPUT tag:
    $optional = implode(" ", join_keys_to_values("=", wrap_values_in_quotes($options)));
  }
  $field  = "\n<select name=\"{$object}[{$method}]\" $optional>\n";
  if ($include_blank)
    $field .= "<option value=\"\">$include_blank_value</option>";

  foreach ($values as $key => $value) {
    if(is_object($value)) {
        $value = $key = $value->$method;       
    }
    $field .= "<option value=\"$key\"".(($key==$selected && $selected!="")?" selected":"").">$value</option>\n";
  }
  $field .= "</select>\n";
  return $field;
}

function multiple_select_box($context, $object, $method, $options = array(), $values = array()) {
    $selected = $context->controller_object->$object->$method;
    if (count($options) > 0) {
      # If the id is not set, default it to the method name
      if (!isset($options['id']))
          $options['id'] = $method;
      # Set any optional attributes for the INPUT tag:
      $optional = implode(" ", join_keys_to_values("=", wrap_values_in_quotes($options)));
    }
    $field  = "\n<select name=\"{$object}[{$method}][]\" $optional multiple>\n";
    foreach ($values as $key => $value)
      $field .= "<option value=\"$key\"".(stristr($selected, $key)?" selected":"").">$value</option>\n";
    $field .= "</select>\n";
    return $field;
}

function get_states_as_array() {
    // needs an sql table of states
    $state = new State();
    $states = $state->find_all();
    $state_array = array();
    foreach($states as $state) {
        $state_array[$state->id] = $state->name;
    }
    return $state_array;
}

function get_countries_as_array() {
    // needs an sql table of countries
    $country = new Country();
    $countries = $country->find_all();
    $country_array = array();
    foreach($countries as $country) {
        $country_array[$country->id] = $country->name;
    }
    return $country_array;
}

# A select box with the US states in it (uses the State model)
function select_state($context, $object, $method, $options = array()) {
    $values = get_states_as_array();
    return select_box($context, $object, $method, $options, $values);
}

# A select box with the US states in it (uses the State model)
function select_country($context, $object, $method, $options = array()) {
    $values = get_countries_as_array();
    return select_box($context, $object, $method, $options, $values);
}

function select_boolean($context, $object, $method, $options = array(), $true = "Yes", $false = "No") {
    $values = array("1" => $true, "0" => $false);
    return select_box($context, $object, $method, $options, $values);
}

# A password input field.  $context should always be "$this".
function password_field($context, $object, $method, $options = array()) {
  $value = $context->controller_object->$object->$method;
  if (count($options) > 0) {
    # If the value is set in the options array, override the default:
    if ($options['value']) {
      $value = $options['value'];
      unset($options['value']);
    }
    # Set any optional attributes for the INPUT tag:
    $optional = implode(" ", join_keys_to_values("=", wrap_values_in_quotes($options)));
  }
  $field = "<input type=\"password\" name=\"{$object}[{$method}]\" value=\"$value\" $optional>";
  return $field;
}

# A text input field.  $context should always be "$this".
function text_field($context, $object, $method, $options = array()) {
  $value = $context->controller_object->$object->$method;
  if (count($options) > 0) {
    # If the value is set in the options array, override the default:
    if ($options['value']) {
      $value = $options['value'];
      unset($options['value']);
    }
    # If the id is not set, default it to the method name
    if (!isset($options['id']))
        $options['id'] = $method;
    # Set any optional attributes for the INPUT tag:
    $optional = implode(" ", join_keys_to_values("=", wrap_values_in_quotes($options)));
  }
  $field = "<input type=\"text\" name=\"{$object}[{$method}]\" value=\"$value\" $optional>";
  return $field;
}

# A text input field.  $context should always be "$this".
function text_area($context, $object, $method, $options = array()) {
  $value = $context->controller_object->$object->$method;
  if (count($options) > 0) {
    # If the value is set in the options array, override the default:
    if ($options['value']) {
      $value = $options['value'];
      unset($options['value']);
    }
    # If the id is not set, default it to the method name
    if (!isset($options['id']))
        $options['id'] = $method;
    # Set any optional attributes for the INPUT tag:
    $optional = implode(" ", join_keys_to_values("=", wrap_values_in_quotes($options)));
  }
  $field = "<textarea name=\"{$object}[{$method}]\" $optional>".stripslashes($value)."</textarea>";
  return $field;
}

function check_box($context, $object, $method, $options = array(), $checked_for = 1) {
    $checked = ($context->controller_object->$object->$method == $checked_for ? "checked" : "");
    if (count($options) > 0) {
      # If the value is set in the options array, override the default:
      if ($options['value']) {
        $value = $options['value'];
        unset($options['value']);
      }
      # If the id is not set, default it to the method name
      if (!isset($options['id']))
          $options['id'] = $method;
      # Set any optional attributes for the INPUT tag:
      $optional = implode(" ", join_keys_to_values("=", wrap_values_in_quotes($options)));
    }
    $field = "<input type=\"checkbox\" name=\"{$object}[{$method}]\" value=\"$checked_for\" $optional $checked>";
    return $field;
}

?>