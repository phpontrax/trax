<?php
/**
 *  File containing the AssetTagHelper class and support functions
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
 *  @todo Document this class
 *  @package PHPonTrax
 */
class AssetTagHelper extends Helpers {

    /**
     * 
     *
     */
    function __construct() {
        parent::__construct();
        $this->javascript_default_sources = $GLOBALS['JAVASCRIPT_DEFAULT_SOURCES'] ? $GLOBALS['JAVASCRIPT_DEFAULT_SOURCES'] : array('prototype', 'effects', 'dragdrop', 'controls');    
    }
    
    /**
     * 
     *
     */
    private function compute_public_path($source, $dir, $ext) {
        if(!preg_match('/^[-a-z]+:\/\//', $source)) {
            if($source{0} != '/') {
                $source = "/{$dir}/{$source}";
            }
            if(!strstr($source, '.')) {
                $source = "{$source}.{$ext}";
            }
            if(!is_null(TRAX_URL_PREFIX)) {
                $prefix = TRAX_URL_PREFIX;
                if($prefix{0} != "/") {
                    $prefix = "/$prefix";
                }
                $source = $prefix . ((substr($prefix, -1) == "/") ? substr($source, 1) : $source);
            }            
        }
        return $this->controller_object->asset_host . $source;
    }
    
    /**
     * Returns path to a javascript asset. Example:
     *
     *  javascript_path "xmlhr" # => /javascripts/xmlhr.js
     */
    function javascript_path($source) {
        return $this->compute_public_path($source, 'javascripts', 'js');
    }
    
    /**
     * Returns a script include tag per source given as argument. Examples:
     *
     *  javascript_include_tag("xmlhr") # =>
     *   <script type="text/javascript" src="/javascripts/xmlhr.js"></script>
     *
     *  javascript_include_tag("common.javascript", "/elsewhere/cools") # =>
     *   <script type="text/javascript" src="/javascripts/common.javascript"></script>
     *   <script type="text/javascript" src="/elsewhere/cools.js"></script>
     *
     *  javascript_include_tag("defaults") # =>
     *   <script type="text/javascript" src="/javascripts/prototype.js"></script>
     *   <script type="text/javascript" src="/javascripts/effects.js"></script>
     *   <script type="text/javascript" src="/javascripts/controls.js"></script>
     *   <script type="text/javascript" src="/javascripts/dragdrop.js"></script>   
     */
    function javascript_include_tag() {
        if(func_num_args() > 0) {
            $sources = func_get_args();     
            $options = (is_array(end($sources)) ? array_pop($sources) : array());          
            if(in_array('defaults', $sources)) {
                if(is_array($this->javascript_default_sources)) {
                    $sources = array_merge($this->javascript_default_sources, $sources);    
                }                  
                if(file_exists(TRAX_PUBLIC. "/javascripts/application.js")) {
                    $sources[] = 'application';
                }
            }
            $contents = array();
            foreach($sources as $source) {
                $source = $this->javascript_path($source);
                $contents[] = $this->content_tag("script", "", array_merge(array("type" => "text/javascript", "src" => $source), $options));
            }
            return implode("\n", $contents)."\n";
        }
    }
    
    /**
     * Returns path to a stylesheet asset. Example:
     *
     *  stylesheet_path("style") # => /stylesheets/style.css
     */
    function stylesheet_path($source) {
        return $this->compute_public_path($source, 'stylesheets', 'css'); #should be stylesheets
    }
    
    /**
     * Returns a css link tag per source given as argument. 
     *
     * Examples:
     *
     *  stylesheet_link_tag("style") # =>
     *   <link href="/stylesheets/style.css" media="screen" rel="Stylesheet" type="text/css" />
     *
     *  stylesheet_link_tag("style", array("media" => "all")) # =>
     *   <link href="/stylesheets/style.css" media="all" rel="Stylesheet" type="text/css" />
     *
     *  stylesheet_link_tag("random.styles", "/css/stylish") # =>
     *   <link href="/stylesheets/random.styles" media="screen" rel="Stylesheet" type="text/css" />
     *   <link href="/css/stylish.css" media="screen" rel="Stylesheet" type="text/css" />
     */
    function stylesheet_link_tag() {
        if(func_num_args() > 0) {
            $sources = func_get_args();     
            $options = (is_array(end($sources)) ? array_pop($sources) : array());
            $contents = array();
            foreach($sources as $source) {
                $source = $this->stylesheet_path($source);
                $contents[] = $this->tag("link", array_merge(array("rel" => "Stylesheet", "type" => "text/css", "media" => "screen", "href" => $source), $options));
            }
            return implode("\n", $contents) . "\n";
        }
    }
    
    /**
     * Returns path to an image asset. Example:
     *
     * The src can be supplied as a...
     * * full path, like "/my_images/image.gif"
     * * file name, like "rss.gif", that gets expanded to "/images/rss.gif"
     * * file name without extension, like "logo", that gets expanded to "/images/logo.png"
     */
    function image_path($source) {
        return $this->compute_public_path($source, 'images', 'png'); #should be images
    }
    
    /**
     * Returns an image tag converting the $options instead html options on the tag, but with these special cases:
     *
     * * <tt>:alt</tt> - If no alt text is given, the file name part of the src is used (capitalized and without the extension)
     * * <tt>:size</tt> - Supplied as "XxY", so "30x45" becomes width="30" and height="45"
     *
     * The src can be supplied as a...
     * * full path, like "/my_images/image.gif"
     * * file name, like "rss.gif", that gets expanded to "/images/rss.gif"
     * * file name without extension, like "logo", that gets expanded to "/images/logo.png"
     */
    function image_tag($source, $options = array()) {
        $options['src'] = $this->image_path($source);
        $options['alt'] = $options['alt'] ? $options['alt'] : Inflector::capitalize(reset($file_array = explode('.', basename($options['src']))));
        
        if(isset($options['size'])) {
            $size = explode('x', $options["size"]);         
            $options['width'] = reset($size);
            $options['height'] = end($size);
            unset($options['size']);
        }
        
        return $this->tag("img", $options);
    }
    
    /**
     * Returns a link tag that browsers and news readers can use to auto-detect a RSS or ATOM feed for this page. The $type can
     * either be <tt>:rss</tt> (default) or <tt>:atom</tt> and the $options follow the url_for() style of declaring a link target.
     *
     * Examples:
     *  auto_discovery_link_tag # =>
     *   <link rel="alternate" type="application/rss+xml" title="RSS" href="http://www.curenthost.com/controller/action" />
     *  auto_discovery_link_tag(:atom) # =>
     *   <link rel="alternate" type="application/atom+xml" title="ATOM" href="http://www.curenthost.com/controller/action" />
     *  auto_discovery_link_tag(:rss, {:action => "feed"}) # =>
     *   <link rel="alternate" type="application/rss+xml" title="RSS" href="http://www.curenthost.com/controller/feed" />
     *  auto_discovery_link_tag(:rss, {:action => "feed"}, {:title => "My RSS"}) # =>
     *   <link rel="alternate" type="application/rss+xml" title="My RSS" href="http://www.curenthost.com/controller/feed" />
     */
    function auto_discovery_link_tag($type = 'rss', $options = array(), $tag_options = array()) {
        return $this->tag(
          "link", array(
          "rel" => ($tag_options['rel'] ? $tag_options['rel'] : "alternate"),
          "type" => ($tag_options['type'] ? $tag_options['type'] : "application/{$type}+xml"),
          "title" => ($tag_options['title'] ? $tag_options['title'] : strtoupper($type)),
          "href" => url_for(array_merge($options, array('only_path' => false))))
        );
    }    
}


/**
  *  Avialble functions for use in views
  *  auto_discovery_link_tag($type = 'rss', $options = array(), $tag_options = array())
  */
function auto_discovery_link_tag() {
    $asset_helper = new AssetTagHelper();
    $args = func_get_args();
    return call_user_func_array(array($asset_helper, 'auto_discovery_link_tag'), $args);
}

/**
  *  image_tag($source, $options = array())
  */
function image_tag() {
    $asset_helper = new AssetTagHelper();
    $args = func_get_args();
    return call_user_func_array(array($asset_helper, 'image_tag'), $args);
}

/**
  *  stylesheet_link_tag($sources)
  */
function stylesheet_link_tag() {
    $asset_helper = new AssetTagHelper();
    $args = func_get_args();
    return call_user_func_array(array($asset_helper, 'stylesheet_link_tag'), $args);
}

/**
  *  javascript_include_tag($sources)
  */
function javascript_include_tag() {
    $asset_helper = new AssetTagHelper();
    $args = func_get_args();
    return call_user_func_array(array($asset_helper, 'javascript_include_tag'), $args);
}

?>