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
 </refsect1>
 <refsect1 id="{@id filters}">
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
    performing a redirect or render.  This is especially useful for
    filters like authentication where you're not interested in
    allowing the action to be  performed if the proper credentials are
    not in order.</para>

   <refsect2 id="{@id filter_inherit}">
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
     intended action are never called.</para>
    </refsect2>

    <refsect2 id="{@id filter_types}">
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
    </refsect2>

    <refsect2 id="{@id filter_skip}">
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
    </refsect2>

    <refsect2 id="{@id filter_conditions}">
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
  </refsect2>
 </refsect1>
<!--
Local variables:
mode: xml
c-basic-offset: 1
indent-tabs-mode: nil
End:
-->
</refentry>
