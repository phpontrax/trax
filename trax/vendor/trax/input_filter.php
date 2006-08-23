<?php
/**
 *  File containing the InputFilter class
 *
 *  (PHP 5)
 *
 *  @package PHPonTrax
 *  @version $Id$
 *  @author Daniel Morris
 *  contributors: Gianpaolo Racca, Ghislain Picard, Marco Wandschneider,
 *                Chris Tobin and Andrew Eddie.
 *  @copyright Daniel Morris <dan@rootcube.com>
 *  @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

/**
 *  Filter user input to remove potential security threats
 *
 *  InputFilter has three public methods that are useful in protecting
 *  a web site from potential security threats from user input.
 *  <ul>
 *    <li>{@link safeSQL()} protects SQL from the user.</li>
 *    <li>{@link process()} protects HTML tags and attributes from the
 *      user.</li>
 *    <li>{@link process_all()} applies {@link process()} to all
 *      possible sources of user input</li>
 *  </ul>
 *  For usage instructions see
 *  {@tutorial PHPonTrax/InputFilter.cls the class tutorial}.
 *  @todo Check FIXMEs
 */
class InputFilter {
    
    /**
     *  User-provided list of tags to either accept or reject
     *
     *  Whether the tags in this list are accepted or rejected is
     *  determined by the value of {@link $tagsMethod}.
     *  @var string[]
     */
	protected static $tagsArray = array();	// default = empty array
    
    /**
     *  User-provided list of attributes to either accept or reject
     *
     *  Whether the attributes in this list are accepted or rejected is
     *  determined by the value of {@link $attrMethod}.
     *  @var string[]
     */
	protected static $attrArray = array();	// default = empty array
    
    /**
     *  How to apply user-provided tags list
     *
     *  Which method to use when applying the list of tags provided by
     *  the user and stored in {@link $tagsArray}.
     *  @var boolean Tested by {@link filterTags()} to see whether the
     *               user-provide list of tags in {@link $tagsArray}
     *               describes those tags which are forbidden, or
     *               those tags which are permitted.  Default false.
     *  <ul>
     *    <li>true =>  Remove  those tags which are in
     *                 {@link $tagsArray}.</li> 
     *    <li>false => Allow only those tags which are listed in
     *                 {@link $tagsArray}.</li> 
     *  </ul>
     */
	protected static $tagsMethod = true;
    
    /**
     *  How to apply user-provided attribute list
     *
     *  Which method to use when applying the list of attributes
     *  provided by the user and stored in {@link $attrArray}.
     *  @var boolean Tested by {@link filterAttr()} to see whether the
     *               user-provide list of tags in {@link $attrArray}
     *               describes those tags which are forbidden, or
     *               those tags which are permitted.  Default false.
     *  <ul>
     *    <li>true =>  Remove  those tags which are in
     *                 {@link $attrArray}.</li> 
     *    <li>false => Allow only those tags which are listed in
     *                 {@link $attrArray}.</li> 
     *  </ul>
     */
	protected static $attrMethod = true;

    
    /**
     *  Whether to remove blacklisted tags and attributes
     *
     *  @var boolean Tested by {@link filterAttr()} and
     *               {@link filterTags()} to see whether to remove
     *               blacklisted tags and attributes.  Default true.
     *  <ul>
     *    <li>true => Remove tags in {@link $tagBlacklist} and
     *                attributes in {@link $attrBlacklist}, in
     *                addition to all other potentially suspect tags
     *                and attributes.</li>
     *    <li>false => Remove potentially suspect tags and attributes
     *      without consulting{@link $tagBlacklist} or
     *      {@link $attrBlacklist}.</li> 
     *  </ul>
     */
	protected static $xssAuto = true;

    /**
     *  Fields to ignore that you want html and other banned stuff in.
     *
     *  @var array
     */	
	protected static $exception_fields = array();
    
    /**
     *  List of tags to be removed
     *
     *  If {@link $xssAuto} is true, remove the tags in this list.
     *  @var string[]
     */
	protected static $tagBlacklist =
        array('applet', 'body', 'bgsound', 'base', 'basefont', 'embed',
              'frame', 'frameset', 'head', 'html', 'id', 'iframe',
              'ilayer', 'layer', 'link', 'meta', 'name', 'object',
              'script', 'style', 'title', 'xml');
    
    /**
     *  List of attributes to be removed
     *
     *  If {@link $xssAuto} is true, remove the attributes in this list.
     *  @var string[]
     */
	protected static $attrBlacklist =
        array('action', 'background', 'codebase', 'dynsrc', 'lowsrc'); 
		
	/** 
     *  Initializer for InputFilter class.
     *
     *  @param string[] $tagsArray  User-provided list of tags to
     *                              either accept or reject.  Default: none
     *  @param string[] $attrArray  User-provided list of attributes to
     *                              either accept or reject.  Default: none
     *  @param boolean $tagsMethod How to apply the list of tags in $tagsArray:
     *  <ul>
     *    <li>true =>  Remove  those tags which are listed in
     *                 $tagsArray.</li>  
     *    <li>false => Allow only those tags which are listed in
     *                 $tagsArray.</li>  
     *  </ul>
     *  Default: false
     *  @param boolean $attrMethod How to apply the list of attributess in $attrArray:
     *  <ul>
     *    <li>true =>  Remove  those attributes which are listed in
     *                 $attrArray.</li>  
     *    <li>false => Allow only those attributes which are listed in
     *                 $attrArray.</li>  
     *  </ul>
     *  Default: false
     *  @param boolean $xssAuto Behavior of {@link filterTags()}:
     *  <ul>
     *    <li>true => Remove tags in {@link $tagBlacklist} and
     *                attributes in {@link $attrBlacklist}, in
     *                addition to all other potentially suspect tags
     *                and attributes.</li>
     *    <li>false => Remove potentially suspect tags and attributes
     *      without consulting{@link $tagBlacklist} or
     *      {@link $attrBlacklist}.</li> 
     *  </ul>
     *  Default: true
     *  @uses $attrArray
     *  @uses $attrMethod
     *  @uses $tagsArray
     *  @uses $tagsMethod
     */
	public function init($tagsArray = array(), $attrArray = array(),
                                $tagsMethod = true, $attrMethod = true,
                                $xssAuto = true) { 
                                    
		// make sure user defined arrays are in lowercase
		for ($i = 0; $i < count($tagsArray); $i++) $tagsArray[$i] = strtolower($tagsArray[$i]);
		for ($i = 0; $i < count($attrArray); $i++) $attrArray[$i] = strtolower($attrArray[$i]);
		// assign to member vars
		self::$tagsArray = (array) $tagsArray;
		self::$attrArray = (array) $attrArray;
		self::$tagsMethod = $tagsMethod;
		self::$attrMethod = $attrMethod;
		self::$xssAuto = $xssAuto;
	}

    /**
     *  Adds a field to exclude from filtering
     *
     */	
	public function add_field_exception($field) {
	    if($field) {
	        self::$exception_fields[] = $field;   
	    }
	}

    /**
     *  Clears all previous field exceptions
     *
     */		
	public function clear_field_exceptions() {
	    self::$exception_fields = array();        
	} 

    /**
     *  Remove forbidden tags and attributes from user input
     *
     *  Construct an InputFilter object.  Then apply the
     *  {@link process()} method to each of the user input arrays
     *  {@link http://www.php.net/reserved.variables#reserved.variables.post $_POST},
     *  {@link http://www.php.net/reserved.variables#reserved.variables.get $_GET} and
     *  {@link http://www.php.net/reserved.variables#reserved.variables.request $_REQUEST}.
     *  <b>FIXME:</b> isn't it partly redundant to do this to $_REQUEST?
     *  Shouldn't we do it to $_COOKIE instead?
     *  @param string[] $tagsArray  User-provided list of tags to
     *                              either accept or reject.  Default: none
     *  @param string[] $attrArray  User-provided list of attributes to
     *                              either accept or reject.  Default: none
     *  @param boolean $tagsMethod How to apply the list of tags in $tagsArray:
     *  <ul>
     *    <li>true =>  Remove  those tags which are listed in
     *                 $tagsArray.</li>  
     *    <li>false => Allow only those tags which are listed in
     *                 $tagsArray.</li>  
     *  </ul>
     *  Default: false
     *  @param boolean $attrMethod How to apply the list of attributess in $attrArray:
     *  <ul>
     *    <li>true =>  Remove  those attributes which are listed in
     *                 $attrArray.</li>  
     *    <li>false => Allow only those attributes which are listed in
     *                 $attrArray.</li>  
     *  </ul>
     *  Default: false
     *  @param boolean $xssAuto Behavior of {@link filterTags()}:
     *  <ul>
     *    <li>true => Remove tags in {@link $tagBlacklist} and
     *                attributes in {@link $attrBlacklist}, in
     *                addition to all other potentially suspect tags
     *                and attributes.</li>
     *    <li>false => Remove potentially suspect tags and attributes
     *      without consulting{@link $tagBlacklist} or
     *      {@link $attrBlacklist}.</li> 
     *  </ul>
     *  Default: true
     *  @author John Peterson
     *  @uses __construct()
     *  @uses process()
     *  @todo Check out FIXMEs
     */
    public function process_all($tagsArray = array(), $attrArray = array(),
                                $tagsMethod = true, $attrMethod = true,
                                $xssAuto = true) {
        self::init($tagsArray, $attrArray, $tagsMethod,
                          $attrMethod, $xssAuto);
        if(count($_POST)) {
            $_POST = self::process($_POST);
        }
        if(count($_GET)) {
            $_GET = self::process($_GET);
        }
        if(count($_REQUEST)) {
            $_REQUEST = self::process($_REQUEST);
        }
    }
	
	/** 
     *  Remove forbidden tags and attributes from array of strings
     *
     *  Accept a string or array of strings.  For each string in the
     *  source, remove the forbidden tags and attributes from the string.
     *  @param mixed $source - input string/array-of-string to be 'cleaned'
     *  @return mixed 'cleaned' version of input parameter
     *  @uses decode()
     *  @uses remove()
     */
	public function process($source, $extra_key = null) {
		// clean all elements in this array
		if(is_array($source)) {
			foreach($source as $key => $value) {
			    //error_log("key:".$extra_key.$key);
			    if(in_array($extra_key.$key, self::$exception_fields)) { $source[$key] = $value; continue; }
                // for arrays in arrays
                if (is_array($value)) $source[$key] = self::process($value, $key.":");
            	// filter element for XSS and other 'bad' code etc.
				if (is_string($value)) $source[$key] = self::remove(self::decode($value));
            }
			return $source;
		// clean this string
		} elseif(is_string($source)) {
			// filter source for XSS and other 'bad' code etc.
			return self::remove(self::decode($source));
		// return parameter as given
		} else {
		    return $source;	
	    }
	}

	/** 
     *  Remove forbidden tags and attributes from a string iteratively
     *
     *  Call {@link filterTags()} repeatedly until no change in the
     *  input is produced.
     *  @param string $source Input string to be 'cleaned'
     *  @return string 'cleaned' version of $source
     *  @uses filterTags()
     */
	protected function remove($source) {
		// provides nested-tag protection
		while($source != self::filterTags($source)) {
			$source = self::filterTags($source);
		}
		return $source;
	}	
	
	/** 
     *  Remove forbidden tags and attributes from a string
     *
     *  Inspect the input for tags "<tagname ...>" and check the tag
     *  name against a list of forbidden tag names.  Delete all tags
     *  with forbidden names.  If {@link $xssAuto} is true, delete all
     *  tags in {@link $tagBlacklist}.  If there is a user-defined tag
     *  list in {@link $tagsArray}, process according to the value of
     *  {@link $tagsMethod}.
     *
     *  If the tag name is OK, then call {@link filterAttr()} to check
     *  all attributes of the tag and delete forbidden attributes. 
     *  @param string $source Input string to be 'cleaned'
     *  @return string Cleaned version of input parameter
     *  @uses filterAttr()
     *  @uses $tagBlacklist
     *  @uses $tagsArray
     *  @uses $tagsMethod
     *  @uses $xssAuto
     */
	protected function filterTags($source) {
		// filter pass setup
		$preTag = null;
		$postTag = $source;
		// find initial tag's position
		$tagOpen_start = strpos($source, '<');
		// interate through string until no tags left
		while($tagOpen_start !== false) {
			// process tag interatively
			$preTag .= substr($postTag, 0, $tagOpen_start);
			$postTag = substr($postTag, $tagOpen_start);
			$fromTagOpen = substr($postTag, 1);
			// end of tag
			$tagOpen_end = strpos($fromTagOpen, '>');
			if ($tagOpen_end === false) break;
			// next start of tag (for nested tag assessment)
			$tagOpen_nested = strpos($fromTagOpen, '<');
			if (($tagOpen_nested !== false) && ($tagOpen_nested < $tagOpen_end)) {
				$preTag .= substr($postTag, 0, ($tagOpen_nested+1));
				$postTag = substr($postTag, ($tagOpen_nested+1));
				$tagOpen_start = strpos($postTag, '<');
				continue;
			} 
			$tagOpen_nested = (strpos($fromTagOpen, '<') + $tagOpen_start + 1);
			$currentTag = substr($fromTagOpen, 0, $tagOpen_end);
			$tagLength = strlen($currentTag);
			if (!$tagOpen_end) {
				$preTag .= $postTag;
				$tagOpen_start = strpos($postTag, '<');			
			}
			// iterate through tag finding attribute pairs - setup
			$tagLeft = $currentTag;
			$attrSet = array();
			$currentSpace = strpos($tagLeft, ' ');
			// is end tag
			if (substr($currentTag, 0, 1) == "/") {
				$isCloseTag = true;
				list($tagName) = explode(' ', $currentTag);
				$tagName = substr($tagName, 1);
			// is start tag
			} else {
				$isCloseTag = false;
				list($tagName) = explode(' ', $currentTag);
			}		
			// excludes all "non-regular" tagnames OR no tagname OR remove if xssauto is on and tag is blacklisted
			if ((!preg_match("/^[a-z][a-z0-9]*$/i",$tagName)) || (!$tagName) || ((in_array(strtolower($tagName), self::$tagBlacklist)) && (self::$xssAuto))) {
				$postTag = substr($postTag, ($tagLength + 2));
				$tagOpen_start = strpos($postTag, '<');
				// don't append this tag
				continue;
			}
			// this while is needed to support attribute values with spaces in!
			while ($currentSpace !== false) {
				$fromSpace = substr($tagLeft, ($currentSpace+1));
				$nextSpace = strpos($fromSpace, ' ');
				$openQuotes = strpos($fromSpace, '"');
				$closeQuotes = strpos(substr($fromSpace, ($openQuotes+1)), '"') + $openQuotes + 1;
				// another equals exists
				if (strpos($fromSpace, '=') !== false) {
					// opening and closing quotes exists
					if (($openQuotes !== false) && (strpos(substr($fromSpace, ($openQuotes+1)), '"') !== false))
						$attr = substr($fromSpace, 0, ($closeQuotes+1));
					// one or neither exist
					else $attr = substr($fromSpace, 0, $nextSpace);
				// no more equals exist
				} else $attr = substr($fromSpace, 0, $nextSpace);
				// last attr pair
				if (!$attr) $attr = $fromSpace;
				// add to attribute pairs array
				$attrSet[] = $attr;
				// next inc
				$tagLeft = substr($fromSpace, strlen($attr));
				$currentSpace = strpos($tagLeft, ' ');
			}
			// appears in array specified by user
			$tagFound = in_array(strtolower($tagName), self::$tagsArray);
			// remove this tag on condition
			if ((!$tagFound && self::$tagsMethod) || ($tagFound && !self::$tagsMethod)) {
				// reconstruct tag with allowed attributes
				if (!$isCloseTag) {
					$attrSet = self::filterAttr($attrSet);
					$preTag .= '<' . $tagName;
					for ($i = 0; $i < count($attrSet); $i++)
						$preTag .= ' ' . $attrSet[$i];
					// reformat single tags to XHTML
					if (strpos($fromTagOpen, "</" . $tagName)) $preTag .= '>';
					else $preTag .= ' />';
				// just the tagname
			    } else $preTag .= '</' . $tagName . '>';
			}
			// find next tag's start
			$postTag = substr($postTag, ($tagLength + 2));
			$tagOpen_start = strpos($postTag, '<');			
		}
		// append any code after end of tags
		$preTag .= $postTag;
		return $preTag;
	}

	/** 
     *  Internal method to strip a tag of certain attributes
     *
     *  Remove potentially dangerous attributes from a set of
     *  "attr=value" strings.  Attributes considered dangerous are:
     *  <ul>
     *    <li>Any attribute name containing any non-alphabetic
     *      character</li> 
     *    <li>Any attribute name beginning "on..."</li>
     *    <li>If {@link $xssAuto} is true, any attribute name in
     *      {@link $attrBlacklist}</li>
     *    <li>Any attribute with a value containing the strings
     *      'javascript:', 'behaviour:', 'vbscript:', 'mocha:',
     *      'livescript:'</li> 
     *    <li>Any attribute whose name contains 'style' and whose
     *      value contains 'expression'.</li>
     *    <li>If there is a user-provided list of attributes in
     *      {@link $attrArray}, process according to the value of
     *      {@link $attrMethod}.</li>
     *  </ul>
     *  @param string[] $attrSet Array of strings "attr=value" parsed
     *                           from a tag.
     *  @return string[] Input with potentially dangerous attributes
     *                   removed
     *  @uses $attrArray
     *  @uses $attrBlacklist
     *  @uses $attrMethod
     *  @uses $xssAuto
     */
	protected function filterAttr($attrSet) {	
		$newSet = array();
		// process attributes
		for ($i = 0; $i <count($attrSet); $i++) {
			// skip blank spaces in tag
			if (!$attrSet[$i]) continue;
			// split into attr name and value
			$attrSubSet = explode('=', trim($attrSet[$i]));
			list($attrSubSet[0]) = explode(' ', $attrSubSet[0]);
			// removes all "non-regular" attr names AND also attr blacklisted
			if ((!eregi("^[a-z]*$",$attrSubSet[0])) || ((self::$xssAuto) && ((in_array(strtolower($attrSubSet[0]), self::$attrBlacklist)) || (substr($attrSubSet[0], 0, 2) == 'on'))))
				continue;
			// xss attr value filtering
			if ($attrSubSet[1] || is_numeric($attrSubSet[1])) {
				// strips unicode, hex, etc
				$attrSubSet[1] = str_replace('&#', '', $attrSubSet[1]);
				// strip normal newline within attr value
				$attrSubSet[1] = preg_replace('/\s+/', '', $attrSubSet[1]);
				// strip double quotes
				$attrSubSet[1] = str_replace('"', '', $attrSubSet[1]);
				// [requested feature] convert single quotes from either side to doubles (Single quotes shouldn't be used to pad attr value)
				if ((substr($attrSubSet[1], 0, 1) == "'") && (substr($attrSubSet[1], (strlen($attrSubSet[1]) - 1), 1) == "'"))
					$attrSubSet[1] = substr($attrSubSet[1], 1, (strlen($attrSubSet[1]) - 2));
				// strip slashes
				$attrSubSet[1] = stripslashes($attrSubSet[1]);
			}
			// auto strip attr's with "javascript:
			if (((strpos(strtolower($attrSubSet[1]), 'expression') !== false) && 
			    (strtolower($attrSubSet[0]) == 'style')) ||
				(strpos(strtolower($attrSubSet[1]), 'javascript:') !== false) ||
				(strpos(strtolower($attrSubSet[1]), 'behaviour:') !== false) ||
				(strpos(strtolower($attrSubSet[1]), 'vbscript:') !== false) ||
				(strpos(strtolower($attrSubSet[1]), 'mocha:') !== false) ||
				(strpos(strtolower($attrSubSet[1]), 'livescript:') !== false) 
			) { continue; }

			// if matches user defined array
			$attrFound = in_array(strtolower($attrSubSet[0]), self::$attrArray);
			//error_log("attrFound:".($attrFound ? "Yes" : "No"));
			// keep this attr on condition
			if ((!$attrFound && self::$attrMethod) || ($attrFound && !self::$attrMethod)) {
			    //error_log($attrSubSet[0]."=".$attrSubSet[1]);
				// attr has value
				if($attrSubSet[1]) {
				    $newSet[] = $attrSubSet[0] . '="' . $attrSubSet[1] . '"';
				// attr has decimal zero as value
			    } elseif ($attrSubSet[1] == "0") { 
			        $newSet[] = $attrSubSet[0] . '="0"';
				// reformat single attributes to XHTML
			    } else {
			        $newSet[] = $attrSubSet[0] . '="' . $attrSubSet[0] . '"';
			    }
			}	
		}
		return $newSet;
	}
	
	/** 
     *  Convert HTML entities to characters
     *
     *  Convert input string containing HTML entities to the
     *  corresponding character (&amp; => &).  ISO 8859-1 character
     *  set is assumed.
     *  @param string $source Character string containing HTML entities
     *  @return string Input string, with entities converted to characters
     *  @uses chr()
     *  @uses html_entity_decode()
     *  @uses preg_replace()
     */
	protected function decode($source) {
		// url decode
		$source = html_entity_decode($source, ENT_QUOTES, "ISO-8859-1");
		// convert decimal &#DDD; to character DDD
		$source = preg_replace('/&#(\d+);/me',"chr(\\1)", $source);
		// convert hex &#xXXX; to character XXX
		$source = preg_replace('/&#x([a-f0-9]+);/mei',"chr(0x\\1)", $source);
		return $source;
	}
}

// -- set Emacs parameters --
// Local variables:
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>