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

class UrlHelper extends Helpers {

    # Creates a link tag of the given +name+ using an URL created by the set of +options+.
    # It's also possible to pass a string instead of an options hash to
    # get a link tag that just points without consideration. If null is passed as a name, the link itself will become the name.
    # The $html_options have a special feature for creating javascript confirm alerts where if you pass ":confirm" => 'Are you sure?',
    # the link will be guarded with a JS popup asking that question. If the user accepts, the link is processed, otherwise not.
    #
    # Example:
    #   link_to("Delete this page", array(":action" => "delete", ":id" => $page->id), array(":confirm" => "Are you sure?"))
    function link_to($name, $options = array(), $html_options = array()) {
        $html_options = $this->convert_confirm_option_to_javascript($html_options);
        if(is_string($options)) {
            $href = array("href" => $options);
            if(count($html_options) > 0) {
                $html_options = array_merge($html_options, $href);
            } else {
                $html_options = $href;
            }
            if(!$name) {
                $name = $options;
            }
            $html = $this->content_tag("a", $name, $html_options);
        } else {
            $url = $this->url_for($options);
            if(!$name) {
                $name = $url;
            }
            $href = array("href" => $url);
            if(count($html_options) > 0) {
                $html_options = array_merge($html_options, $href);
            } else {
                $html_options = $href;
            }
            $html = $this->content_tag("a", $name, $html_options);
        }
        return $html;
    }

    # Returns the URL for the set of +options+ provided.
    function url_for($options = array()) {
        $url_base = null;
        $url = array();
        if(is_string($options)) {
            $url[] = $options;
        } else {
            if($_SERVER['SERVER_PORT'] == 443) {
                $url_base = "https://".$_SERVER['HTTP_HOST'];
            } else {
                $url_base = "http://".$_SERVER['HTTP_HOST'];
            }
            if(array_key_exists(":controller", $options)) {
                if($controller = $options[":controller"]) {
                    if(stristr($this->controller_path, $controller)) {
                        $url[] = $this->controller_path;
                    } else {
                        $url[] = $controller;
                    }
                }
            } else {
                $url[] = $this->controller_path;
            }
            if(array_key_exists(":action", $options)) {
                if($action = $options[":action"]) {
                    $url[] = $action;
                }
            }
            if(array_key_exists(":id", $options)) {
                if($id = $options[":id"]) {
                    $url[] = $id;
                }
            }
        }
        return $url_base . implode("/", $url);
    }

    function convert_confirm_option_to_javascript($html_options) {
        if($html_options['confirm']) {
            $html_options['onclick'] = "return confirm('".addslashes($html_options['confirm'])."');";
            unset($html_options['confirm']);
        }
        return $html_options;
    }

}

################################################################################################
## Avialble functions for use in views
################################################################################################
function link_to($name, $options = array(), $html_options = array()) {
    $url_helper = new UrlHelper();
    return $url_helper->link_to($name, $options, $html_options);
}


?>