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
     *  <b>FIXME:</b> static declaration must be after visibility declaration
     *  @var string[]
     */
	static protected $tagsArray = array();	// default = empty array
    
    /**
     *  User-provided list of attributes to either accept or reject
     *
     *  Whether the attributes in this list are accepted or rejected is
     *  determined by the value of {@link $attrMethod}.
     *  <b>FIXME:</b> static declaration must be after visibility declaration
     *  @var string[]
     */
	static protected $attrArray = array();	// default = empty array
    
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
     *  <b>FIXME:</b> static declaration must be after visibility declaration
     */
	static protected $tagsMethod = 0;	// default = 0
    
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
     *  <b>FIXME:</b> static declaration must be after visibility declaration
     */
	static protected $attrMethod = 0;	// default = 0

    
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
     *  <b>FIXME:</b> static declaration must be after visibility declaration
     */
	static protected $xssAuto = 1;     // default = 1
    
    /**
     *  List of tags to be removed
     *
     *  If {@link $xssAuto} is true, remove the tags in this list.
     *  @var string[]
     *  <b>FIXME:</b> static declaration must be after visibility declaration
     */
	static protected $tagBlacklist =
        array('applet', 'body', 'bgsound', 'base', 'basefont', 'embed',
              'frame', 'frameset', 'head', 'html', 'id', 'iframe',
              'ilayer', 'layer', 'link', 'meta', 'name', 'object',
              'script', 'style', 'title', 'xml');
    
    /**
     *  List of attributes to be removed
     *
     *  If {@link $xssAuto} is true, remove the attributes in this list.
     *  @var string[]
     *  <b>FIXME:</b> static declaration must be after visibility declaration
     */
	static protected $attrBlacklist =
        array('action', 'background', 'codebase', 'dynsrc', 'lowsrc'); 
		
	/** 
     *  Constructor for InputFilter class.
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
	public function __construct($tagsArray = array(), $attrArray = array(),
                                $tagsMethod = 0, $attrMethod = 0,
                                $xssAuto = 1) { 
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
                                $tagsMethod = 0, $attrMethod = 0,
                                $xssAuto = 1) {
        self::__construct($tagsArray, $attrArray, $tagsMethod,
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
	public function process($source) {
		// clean all elements in this array
		if (is_array($source)) {
			foreach($source as $key => $value) {
                // for arrays in arrays
                if (is_array($value)) $source[$key] = self::process($value);
            	// filter element for XSS and other 'bad' code etc.
				if (is_string($value)) $source[$key] = self::remove(self::decode($value));
            }
			return $source;
		// clean this string
		} else if (is_string($source)) {
			// filter source for XSS and other 'bad' code etc.
			return self::remove(self::decode($source));
		// return parameter as given
		} else return $source;	
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
        //  FIXME: what do we use $loopCounter for?
		$loopCounter=0;
		// provides nested-tag protection
		while($source != self::filterTags($source)) {
			$source = self::filterTags($source);
			$loopCounter++;
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
		$preTag = NULL;
		$postTag = $source;
		// find initial tag's position
		$tagOpen_start = strpos($source, '<');
		// interate through string until no tags left
		while($tagOpen_start !== FALSE) {
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
				$isCloseTag = TRUE;
				list($tagName) = explode(' ', $currentTag);
				$tagName = substr($tagName, 1);
			// is start tag
			} else {
				$isCloseTag = FALSE;
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
			while ($currentSpace !== FALSE) {
				$fromSpace = substr($tagLeft, ($currentSpace+1));
				$nextSpace = strpos($fromSpace, ' ');
				$openQuotes = strpos($fromSpace, '"');
				$closeQuotes = strpos(substr($fromSpace, ($openQuotes+1)), '"') + $openQuotes + 1;
				// another equals exists
				if (strpos($fromSpace, '=') !== FALSE) {
					// opening and closing quotes exists
					if (($openQuotes !== FALSE) && (strpos(substr($fromSpace, ($openQuotes+1)), '"') !== FALSE))
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
			if ($attrSubSet[1]) {
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
			if (	((strpos(strtolower($attrSubSet[1]), 'expression') !== false) &&	(strtolower($attrSubSet[0]) == 'style')) ||
					(strpos(strtolower($attrSubSet[1]), 'javascript:') !== false) ||
					(strpos(strtolower($attrSubSet[1]), 'behaviour:') !== false) ||
					(strpos(strtolower($attrSubSet[1]), 'vbscript:') !== false) ||
					(strpos(strtolower($attrSubSet[1]), 'mocha:') !== false) ||
					(strpos(strtolower($attrSubSet[1]), 'livescript:') !== false) 
			) continue;

			// if matches user defined array
			$attrFound = in_array(strtolower($attrSubSet[0]), self::$attrArray);
			// keep this attr on condition
			if ((!$attrFound && self::$attrMethod) || ($attrFound && !self::$attrMethod)) {
				// attr has value
				if ($attrSubSet[1]) $newSet[] = $attrSubSet[0] . '="' . $attrSubSet[1] . '"';
				// attr has decimal zero as value
				else if ($attrSubSet[1] == "0") $newSet[] = $attrSubSet[0] . '="0"';
				// reformat single attributes to XHTML
				else $newSet[] = $attrSubSet[0] . '="' . $attrSubSet[0] . '"';
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

	/** 
     *  Remove HTML entities and magic quotes, insert SQL special
     *  character escapes
     *
     *  If the input is a string or an array of strings, then each
     *  string is edited to convert any HTML entities to the
     *  corresponding character and remove slashes inserted by
     *  {@link http://www.php.net/manual/en/security.magicquotes.php magic quotes},
     *  then the result has SQL special characters
     *  escaped.
     *  @param mixed $source Input to be 'cleaned'
     *  @param resource $connection  An open MySQL connection
     *  @return mixed $source with HTML entities and GPC magic quotes
     *                removed from, and SQL special character escapes
     *                inserted in, the string or array of strings.
     *  @uses decode()
     *  @uses quoteSmart()
     */
	public function safeSQL($source, &$connection) {
		// clean all elements in this array
		if (is_array($source)) {
			foreach($source as $key => $value)
				// filter element for SQL injection
				if (is_string($value)) $source[$key] = self::quoteSmart(self::decode($value), $connection);
			return $source;
		// clean this string
		} else if (is_string($source)) {
			// filter source for SQL injection
			if (is_string($source)) return self::quoteSmart(self::decode($source), $connection);
		// return parameter as given
		} else return $source;	
	}

	/** 
     *  Remove GPC magic quotes from input string & escape SQL special
     *  characters
     *
     *  The input is a string that came from a GET or POST HTTP
     *  operation, or a cookie.  If GPC magic quotes are currently in
     *  effect, the resulting slashes are stripped.  Then any SQL
     *  special characters in the string are escaped, taking into
     *  account the character set in use on $connection.
     *  @author Chris Tobin, Daniel Morris
     *  @param string $source Input string to be converted
     *  @param resource $connection An open MySQL connection
     *  @return string Input string with any GPC magic quotes stripped
     *                 and SQL special characters escaped
     *  @uses escapeString()
     *  @uses get_magic_quotes_gpc()
     *  @uses stripslashes()
     */
	protected function quoteSmart($source, &$connection) {
		// strip slashes
		if (get_magic_quotes_gpc()) $source = stripslashes($source);
		// quote both numeric and text
		$source = self::escapeString($source, $connection);
		return $source;
	}
	
	/** 
     *  Escape SQL special characters in string
     *
     *  Escape SQL special characters in the input string, taking into
     *  account the character set of the connection.
     *
     *  <b>FIXME:</b> since we require PHP 5 can't we remove the use
     *  of mysql_esacape_string()?
     *
     *  <b>FIXME:</b>Shouldn't we pass the connection to
     *  mysql_real_escape_string()? 
     *
     *  <b>FIXME:</b>Is this really RDBMS independent?
     *  @todo Check FIXMEs
     *  @author Chris Tobin, Daniel Morris
     *  @param string $string  String to be protected
     *  @param resource $connection - An open MySQL connection
     *  @return string Value of $string with characters special in
     *                 SQL escaped by '\'s
     *  @uses mysql_escape_string()
     *  @uses mysql_real_escape_string()
     *  @uses phpversion()
     *  @uses version_compare()
     */	
	protected function escapeString($string, &$connection) {
		// depreciated function
		if (version_compare(phpversion(),"4.3.0", "<"))
            return mysql_escape_string($string);
		// current function
		else
            return mysql_real_escape_string($string);
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