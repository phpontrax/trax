<refentry id="{@id}" revision="$Id$">
 <refnamediv>
  <refname>InputFilter</refname>
  <refpurpose>Protect Against Malicious SQL and HTML</refpurpose>
 </refnamediv>
 <refsynopsisdiv>
  <author>
   Walt Haas
   <authorblurb>
    {@link mailto:haas@xmission.com haas@xmission.com}
   </authorblurb>
  </author>
 </refsynopsisdiv>
 {@toc}
 <refsect1 id="{@id intro}">
  <title>Introduction</title>
  <para>{@link InputFilter} is a
  {@link http://en.wikipedia.org/wiki/Singleton_pattern singleton}
  class (although not enforced by the constructor) with three public
  methods that are useful in protecting  a web site from potential
  security threats from user input.</para>
  <unorderedlist>
   <listitem>{@link InputFilter::safeSQL()} protects SQL from the
    user.</listitem> 
   <listitem>{@link InputFilter::process()} protects HTML tags and
    attributes from the user.</listitem>
   <listitem>{@link InputFilter::process_all()} applies
    {@link process()} to all possible sources of user input</listitem>
  </unorderedlist>
 </refsect1>
 <refsect1 id="{@id safesql}">
  <title>safeSQL(): Protect SQL</title>

  <para>Web site security may be threatened by
  {@link http://en.wikipedia.org/wiki/SQL_injection SQL injection}
  if a user is allowed to input a query that is not properly screened.
  SQL statements are delimited by punctuation characters.  In
  particular, the beginning and end of the information being stored or
  searched for are delimited by quotes.  If a user is permitted to
  include unprotected quotes in their search, there is a danger that
  a malicious user might take advantage of this to inject unauthorized
  commands into the database.</para>

  <para>To protect against this attack, user information is examined
  for quotes and other characters that might be used in an attack, and
  every such character is <important>escaped</important> by prefixing
  the character with a backslash ('\').  The backslash tells the
  database to treat the following character as data, not a
  command.</para>

  <para>{@link InputFilter::safeSQL()} may be called as a static
  method to screen character strings for threatening characters and
  apply the protective backslashes.  An open MySQL connection resource
  is needed to establish the appropriate character set.  For
  example:</para> 

  <example>
$rs = mysql_connect('hostname', 'username', 'password');
$unsafe = "search term'; drop database employees;";
$protected = InputFilter::safeSQL($unsafe,$rs);
// $protected contains "search term\'; drop database employees;"
  </example>
 </refsect1>
 <refsect1 id="{@id process}">
  <title>process(): Protect Against HTML Tags and Attributes</title>

  <para>{@link InputFilter::process()} eliminates potentially
  dangerous HTML tags and attributes from its input.  There are
  internal lists of
  {@link InputFilter::$tagBlacklist blacklisted tags} and
  {@link InputFilter::$attrBlacklist blacklisted attributes} than can
  optionally be removed from the input.  The constructor also accepts
  lists of forbidden tags and attributes and allows the listed names
  to be removed, or alternatively to be the only names
  accepted.</para>

  <para>To use this method, you must construct an object of the
  InputFilter class, with optional behavior specified in the
  constructor call. The options are stored as static attributes of the
  constructed object, so any reference to an object of the class will
  use the attributes in the most recent object.  Therefore it makes
  code more readable to use static calls. For example:</para>

  <example>
@new InputFilter();
$output_string = InputFilter::process($input_string);
  </example>

  <para>The default constructor, as above, rejects all tags and
  attributes, returning only the text between tags.  You can construct
  an object which rejects only the blacklisted tags and attributes as
  follows:</para>

  <example>
@new InputFilter(array(),array(),1,1,1);
  </example>

  <para>You would probably be more secure if you listed what you know
  to be safe, instead of trying to think of everything that might
  be a threat:</para>
  <example>
@new InputFilter(array('div','span','strong','em'),
                 array('id','class'),0,0,0);
  </example>
 </refsect1>
 <refsect1 id="{@id process_all}">
  <title>process_all(): Protect Against HTML in Request Variables</title>
  <para>{@link InputFilter::process()} eliminates potentially
  dangerous HTML tags and attributes from the predefined globals
  {@link http://www.php.net/reserved.variables#reserved.variables.post $_POST},
  {@link http://www.php.net/reserved.variables#reserved.variables.get
  $_GET}
  and
 {@link http://www.php.net/reserved.variables#reserved.variables.request $_REQUEST}.
  Call the method statically, as InputFilter::process_all() with the same
  arguments as used by {@link InputFilter::__construct() the constructor}.
  A new object will be constructed with these options and then
  InputFilter::process() will be called on each of $_GET, $_POST and
  $_REQUEST.  The options in the call to process_all() are stored as
  static attributes of the new object, so they will be used on any calls to
  {@link InputFilter::process()} until another object is
  constructed.</para>
 </refsect1>
<!--
Local variables:
mode: xml
c-basic-offset: 1
indent-tabs-mode: nil
End:
-->
</refentry>
