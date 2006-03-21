<refentry id="{@id}" revision="$Id$">
 <refnamediv>
  <refname>ActionController</refname>
  <refpurpose></refpurpose>
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
  <para>The {@link ActionController} base class does the
  following:</para>
  <orderedlist>
    <listitem>Accepts a URL as input</listitem>
    <listitem>Translates the URL into a controller and action</listitem>
    <listitem>Creates the indicated controller object (which is a subclass
      of ActionController) and calls its action method</listitem>
    <listitem>Redirects to another URL or renders the output of the
    action method</listitem> 
  </orderedlist>
 </refsect1>
 <refsect1 is="{@id url}">
  <title>URL Processing</title>

  <para>
   When Apache receives an HTTP request addressed to a Trax
   application,
{@link http://httpd.apache.org/docs/2.0/mod/mod_rewrite.html Apache mod_rewrite}
   is invoked and rewrites the request to invoke Trax file
   <literal>dispatch.php</literal>.  At this time the URL which was
   input to the rewrite rules is in
{@link http://www.php.net/manual/en/reserved.variables.php#reserved.variables.server $_SERVER}['REDIRECT_URL'].  
   <literal>dispatch.php</literal> creates a new {@link Dispatcher}
   object and calls its 
   {@link Dispatcher::dispatch() dispatch()} method. dispatch()
   restores the state of the session identified by a cookie in the
   request, or creates a session if none exists.  Then it creates a
   new ActionController object and calls its
   {@link ActionController::process_route() <literal>process_route()</literal>} 
   method. 
  </para>

  <para>
   The word "route" is used in Trax to describe a rule which
   translates some URL into a particular controller object and method.
   When <literal>process_route()</literal> receives control, it calls
   {@link ActionController::recognize_route() recognize_route()} to
   parse the URL into controller, action and id
   components. recognize_route() calls
   {@link ActionController::load_router() load_router()} to load the
   "routing table", which is a list of one or more
   <literal>$router->connect()</literal> calls, from
   {@link routes.php <literal>config/routes.php</literal>}.  
   This list of calls define the rules for translating a URL into a
   controller and action.
  </para>

  <para>
   The translation rules work as follows:  Starting with the first
   rule in <literal>routes.php</literal>, each rule is tested against
   the URL to see 
   whether the rule matches.  If a rule does not match, then the next
   rule in the table is tested in turn, until a rule matches or the
   table is exhausted.  If no matching rule is found,
   recognize_route() tests the last route in the table to see whether
   it is the default route <literal>:controller/:action/:id</literal>
   .  If the last route is the default route, then
   <literal>recognize_route()</literal>
   returns it as a match, even if it does not in fact match.  But if
   there is no matching route and the last route in the table is not
   the default route, then recognize_route() returns 'failure' which is
   equivalent to HTTP code '404&nbsp;Not&nbsp;found'.
  </para>

  <para>
   Each entry in the route table contains two parts:
   <orderedlist>
    <listitem>
     A <important>path</important>, which is a character string to
     test against the URL.
    </listitem>
    <listitem>
     <important>Parameters</important>, which are not tested against
     the URL and aren't involved unless the
     <important>path</important> part of the entry matches the URL.
     <important>Parameters</important> are optional (and frequently
     omitted).
    </listitem>
   </orderedlist>
   A <important>path</important> is a series of substrings separated
   by '/' (forward slash) characters.  Each of these substrings can
   contain any character except '/'.  The <important>path</important>
   does not begin or end with '/'.  A substring may not be the null
   (no characters) string, but it is legal for the entire
   path to be the null string. Each substring is one of the following:
   <itemizedlist>
    <listitem><literal>:controller</literal></listitem>
    <listitem><literal>:action</literal></listitem>
    <listitem><literal>:id</literal></listitem>
    <listitem>A
     {@link http://www.php.net/manual/en/ref.pcre.php Perl regular expression}
     that does not begin with ':' (colon)</listitem>
   </itemizedlist>
   The following are legal <important>path</important> values:
   <itemizedlist>
    <listitem><literal>:controller/:action/:id</literal>
     This is the default <important>path</important>. It matches URLs
     like <literal>word1/word2/word3</literal></listitem>
    <listitem><literal>catalog/product/:action/:id</literal>
     Remember that <literal>catalog</literal> is a Perl regular
     expression that matches <literal>catalog</literal>, and
     <literal>product</literal> is a Perl regular expression that
     matches <literal>product</literal>, so this
     <important>path</important> matches URLs like 
     <literal>catalog/product/word1/word2</literal></listitem>
    <listitem><literal>''</literal> matches '' (the empty string as a 
     <important>path</important> value matches the empty string as a
     URL).</listitem>
    <listitem><literal>member/name=.*</literal> matches URLs like
     <literal>member/name=</literal> or
     <literal>member/name=Tom.Jones</literal> or
     <literal>member/name=Smith,J/since=1987/type=full</literal> etc.
    </listitem>
   </itemizedlist>
   <literal>:controller</literal>, <literal>:action</literal> and
   <literal>:id</literal> may each appear at most once in a 
   <important>path</important>.
  </para>

  <para>
   After the URL has been matched to a <important>path</important>,
   the next step is to extract the name of the controller and action
   to be invoked on this URL.  These must be valid names in the PHP
   language consisting only of lower-case alphameric characters and
   '_' (underscore), because the controller name will translate
   directly into a file name and a class name, and the action name
   will be used as the name of a method in that class.  The controller
   and action names come from the route that matches the URL.
  </para>

  <para>
   There are two places that a route can specify a controller or
   action name: as part of the <important>path</important>, or in the 
   <important>parameters</important>.  The
   <important>parameters</important> are the optional second part of a
   route.  The value of <important>parameters</important> is an array
   with key values that may be <literal>:controller</literal> or
   <literal>:action</literal>.  The following are legal
   <important>parameters</important> values:
   <itemizedlist>
    <listitem><literal>array(':controller' =>
     'new_product')</literal></listitem> 
    <listitem><literal>array(':action' => 'enter')</literal></listitem>
    <listitem><literal>array(':controller' => 'membership', ':action
     => 'new')</literal></listitem> 
   </itemizedlist>    
  </para>

  <para>
   When a URL matches a route, the controller name is extracted as
   follows:  First, if the <important>parameters</important> array
   exists and has an element whose key is
   <literal>:controller</literal>, then the value of that element is
   used as the controller name.  If no <literal>:controller</literal>
   is specified by the <important>parameters</important>, then the
   <important>path</important> is tested for a substring whose value
   is <literal>:controller</literal>.  If found, then the part of the
   URL which matched that substring is used as the controller value.
   A controller value must be specified by either the
   <important>parameters</important> or the
   <important>path</important>.  The action name is extracted by the
   same process, substituting <literal>:action</literal> for 
   <literal>:controller</literal>.  If the
   <important>path</important> has a substring <literal>:id</literal>,
   then the part of the URL which matched that substring is forced to
   lower case and the result assigned to
   <literal>$_REQUEST['id']</literal>.
  </para>

  <para>
   If <literal>routes.php</literal> contains the following:
   <example>
router->connect('',array(':controller' => 'home'));
router->connect('product\?.*',
                array(':controller' => 'catalog', ':action' => 'find'));
router->connect(':controller/:action/:id');
   </example>
   Then URLs will match routes as follows:
   <itemizedlist>    
    <listitem>URL <literal>''</literal> (no characters) will select
     controller <literal>home</literal>, action not specified.
    </listitem>
    <listitem>URL <literal>product?item=4317</literal> will select
     controller <literal>catalog</literal>, action
     <literal>find</literal>
    </listitem>
    <listitem>URL <literal>cart/add/4317</literal> will select
     controller <literal>cart</literal>, action <literal>add</literal>
    </listitem>
   </itemizedlist>    
  </para>

 </refsect1>
 <refsect1 is="{@id action}">
  <title>Action Call</title>
  <para>When the names of the controller and action have been
   successfully determined from the URL, the associated filesystem
   paths are constructed and relevant files are loaded.
   First file <literal>app/controllers/application.php</literal> is
   loaded if it exists. This file contains the definition of the
   {@link ApplicationController} class, which extends
   <literal>ActionController</literal>.
   <literal>ApplicationController</literal> contains properties and
   methods used by all the controller classes, which should extend
   <literal>ApplicationController</literal> .
   Then the controller name is used to find the file and class
   containing the selected controller.  By Trax naming conventions, 
   if the controller name is
   <arg choice="tute-comment">controller&nbsp;name</arg>
   then the controller file name is
   <arg choice="tute-comment">controller_name</arg><literal>_controller.php</literal>
   and the controller class name is
   <arg choice="tute-comment">ControllerName</arg> .  So for a
   "catalog&nbsp;item" controller, the controller file name is
   <literal>catalog_item_controller</literal> and the controller class
   name is <literal>CatalogItem</literal>.
   The controller file is loaded and a new object of the controller
   class is created.
  </para>

  <para>
   Next any needed helper files are loaded.  Helper files contain PHP
   code which helps prepare the output of an action method for
   viewing. If file
   <literal>application_helper.php</literal> exists, it is loaded.
   <literal>application_helper.php</literal> contains
   helpers that apply to every controller in the application.
   Then the controller-specific helper file
   <arg choice="tute-comment">controller_name</arg><literal>_helper.php</literal>
   is loaded if it exists.  Finally any extra helper files, as
   specified by calls to {@link ActionController::add_helper()}, are
   loaded.
  </para>

  <para>
   When controller and helper files have been loaded, the before
   filters are executed (<important>FIXME:</important> We should check
   return but don't).  Next the controller object is tested for the
   presence of a method with the name of the action as determined from
   the URL. If such a method exists, it is called; if 
   no such method exists, then the controller object is tested
   for the presence of a method named <literal>index()</literal>.
   If such a method exists it is called, otherwise the request fails
   with 404&nbsp;Unknown&nbsp;action. If an action method was found
   and called, the after filters are executed.
  </para>

  <refsect2 id="{@id helpers}">
   <title>Helper Loading</title>
   <para>Helpers are classes that provide view logic.  They exist to
    hold view logic that would otherwise need to be added to a
    template or controller.  Helper services that are applicable to
    the entire application go into
    <literal>application_helper.php</literal>, while
    controller-specific helper functions go into a helper file named
    after the controller, as 
    <arg choice="tute-comment">controller_name</arg><literal>_helper.php</literal> 
    . Helper classes are written as subclasses of class {@link Helpers},
    which has a number of methods widely used by helper
    subclasses.  You can add a helper to an
    <literal>ActionController</literal> object by calling its
    {@link ActionController::add_helper() add_helper()} 
    method, passing the name of the helper as an argument.
   </para>

   <para>
    A number of predefined helper classes are distributed with Trax:
    <itemizedlist>
     <listitem>{@link ActiveRecordHelper}</listitem>
     <listitem>{@link AssetTagHelper}</listitem>
     <listitem>{@link DateHelper}</listitem>
     <listitem>{@link FormHelper}</listitem>
     <listitem>{@link FormTagHelper}</listitem>
     <listitem>{@link JavaScriptHelper}</listitem>
     <listitem>{@link UrlHelper}</listitem>
    </itemizedlist>
    These classes are <important>not</important> automatically loaded,
    you have to load them explicitly.
   </para>
   <para></para>
  </refsect2>

  <refsect2 id="{@id filters}">
   <title>Filters</title>

   <para>Filters enable controllers to run shared pre and post
    processing code for its actions. These filters can be used to do
    authentication, caching, or auditing before the intended action is
    performed. Or to do localization or output compression after the
    action has been performed.</para>

   <para>Filters have access to the request, response, and all the
    instance variables set by other filters in the chain or by the
    action (in the case of after filters). Additionally, it's possible
    for a pre-processing <samp>before_filter</samp> to halt the processing
    before the intended action is processed by returning false or
    performing a redirect or render. (FIXME: we don't implement this)
    This is especially useful for
    filters like authentication where you're not interested in
    allowing the action to be  performed if the proper credentials are
    not in order.</para>

   <refsect3 id="{@id filter_inherit}">
    <title>Filter inheritance</title>

    <para>Controller inheritance hierarchies share filters downwards, but
     subclasses can also add new filters without affecting the
     superclass. For example:</para>

    <example>
class BankController extends ActionController
{
    $this->before_filter = audit();

    private function audit() {
        // record the action and parameters in an audit log
    }
}

class VaultController extends BankController
{
    $this->before_filter = verify_credentials();

    private function verify_credentials() {
        // make sure the user is allowed into the vault
    }
}
    </example>

    <para>Now any actions performed on the BankController will have the
     audit method called before. On the VaultController, first the
     audit method is called, then the verify_credentials method. If the
     audit method returns false, then verify_credentials and the
     intended action are never called.  <important>FIXME:
     This is currently broken.</important></para>
    </refsect3>

    <refsect3 id="{@id filter_types}">
     <title>Filter types</title>

     <para>A filter can take one of three forms: method reference
      (symbol), external class, or inline method (proc). The first is the
      most common and works by referencing a protected or private method
      somewhere in the inheritance hierarchy of the controller by use of
      a symbol. In the bank example above, both BankController and
      VaultController use this form.</para>

     <para>Using an external class makes for more easily reused generic
      filters, such as output compression. External filter classes are
      implemented by having a static +filter+ method on any class and
      then passing this class to the filter method. Example:</para>
 
     <example>
class OutputCompressionFilter
{
    static functionfilter(controller) {
        controller.response.body = compress(controller.response.body)
    }
}

class NewspaperController extends ActionController
{
    $this->after_filter = OutputCompressionFilter;
}
     </example>

     <para>The filter method is passed the controller instance and is
      hence granted access to all aspects of the controller and can
      manipulate them as it sees fit.</para>

     <para>The inline method (using a proc) can be used to quickly do
      something small that doesn't require a lot of explanation.  Or
      just as a quick test. It works like this:</para>
 
     <example>
class WeblogController extends ActionController
{
    before_filter { |controller| false if controller.params["stop_action"] }
}
     </example>

     <para>As you can see, the block expects to be passed the controller
      after it has assigned the request to the internal variables.  This
      means that the block has access to both the request and response
      objects complete with convenience methods for params, session,
      template, and assigns. Note: The inline method doesn't strictly
      have to be a block; any object that responds to call and returns 1
      or -1 on arity will do (such as a Proc or an Method object).</para>
    </refsect3>

    <refsect3 id="{@id filter_skip}">
     <title>Filter chain skipping</title>

     <para>Some times its convenient to specify a filter chain in a
      superclass that'll hold true for the majority of the subclasses,
      but not necessarily all of them. The subclasses that behave in
      exception can then specify which filters they would like to be
      relieved of. Examples</para> 

     <example>
class ApplicationController extends ActionController
{
    $this->before_filter = authenticate();
}

class WeblogController extends ApplicationController
{
    // will run the authenticate() filter
}
     </example>
    </refsect3>

    <refsect3 id="{@id filter_conditions}">
     <title>Filter conditions</title>

     <para>Filters can be limited to run for only specific
      actions. This can be expressed either by listing the actions to
      exclude or the actions to include when executing the
      filter. Available conditions are +:only+ or +:except+, both of
      which accept an arbitrary number of method references. For
      example:</para> 

     <example>
class Journal extends ActionController
{
    // only require authentication if the current action is edit or delete
    before_filter :authorize, :only => [ :edit, :delete ]
    
    private function authorize() {
        // redirect to login unless authenticated
    }
}
     </example>
 
     <para>When setting conditions on inline method (proc) filters the
      condition must come first and be placed in parentheses.</para>
 
    <example>
class UserPreferences extends ActionController
{
    before_filter(:except => :new) { ? some proc ... }
    ...
}
    </example>
   </refsect3>
  </refsect2>
 </refsect1>
 <refsect1 id="{@id render}">
  <title>Redirect Browser or Render Output</title>

  <para>After the controller object's action method has returned to
   <literal>ActionController::process_route()</literal> and the after
   filters have been executed, the controller object is examined for
   a property named <literal>redirect_to</literal>.  If this
   property exists and has a value, it means that the action method
   has decided to redirect the user's browser to a different URL.  The
   value of the <literal>redirect_to</literal> property is passed to
   {@link ActionController::redirect_to() <literal>redirect_to()</literal>}
   which outputs a header redirecting the browser, then calls
   {@link http://www.php.net/manual/en/function.exit.php exit} .</para>

  <para>
   If the action didn't redirect the browser, it should have provided
   output to send to the browser.  This is in the form of explicit
   output produced by calls to
   {@link http://www.php.net/manual/en/function.echo.php echo},
   {@link http://www.php.net/manual/en/function.print.php print} or
   {@link http://www.php.net/manual/en/function.printf.php printf} ,
   plus any properties of the controller object that are referenced in
   the layout. <literal>ActionController::process_route()</literal>
   collects all output produced by the controller's action method
   in the output buffer, for presentation within a layout.
  </para>

  <para>
   If the controller object has a property
   <literal>render_text</literal> which contains a string, then this
   string is sent directly to the browser and all output and view
   files are ignored.
  </para>

  <para>
   If <literal>render_text</literal> is undefined or empty, then the
   saved output of the controller's action method is to be rendered.
   A <important>view file</important> determined by the action is
   found and included.  The view file for an action is
   <literal>app/views/</literal><arg choice="tute-comment">controller_name/action_name</arg><literal>.phtml</literal> .
   This file contains HTML, which goes to the output buffer after the
   action method's output.  The output buffer is now assigned to
   $content_for_layout.  Finally the layout file is loaded.  The
   view file and layout file both contain HTML with
   {@link http://www.php.net/manual/en/language.basic-syntax.php embedded PHP}
   expressions to present action method output to the user.
  </para>
 </refsect1>
<!--
Local variables:
mode: xml
c-basic-offset: 1
indent-tabs-mode: nil
End:
-->
</refentry>
