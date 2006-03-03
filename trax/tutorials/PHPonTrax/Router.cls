<refentry id="{@id}" revision="$Id$">
 <refnamediv>
  <refname>Router</refname>
  <refpurpose>Convert a URL to an action</refpurpose>
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

  <para>The {@link Router Router class} owns the information and
   algorithms that convert URLs to actions.  An object of this class
   holds a {@link Router::$routes route table} containing routes to be
   matched to URLs.</para>

  <para>In normal operation, a Router object is created by 
   {@link ActionController::load_router()} which reads routes from the
   configuration file {@link routes.php config/routes.php}.</para>

 </refsect1>
 <refsect1 id="{@id table}">
  <title>Structure of the Route Table</title>

  <para>The route table is an ordered list of routes.  A route consists
   of a path and a (possibly null) parameter array. The structure of
   the table is:</para>

  <example>
$routes[0]['path']    First route path
          ['params']  First route parameters
       [1]['path']    Second route path
          ['params']  Second route parameters
...
  </example>

  <para>A route path consists of character strings separated by
   forward slash ('/') characters.  The route path must not begin
   or end with '/', and the character strings in the path must
   not contain '/'.  There are two kinds of character strings in
   route paths:</para>

  <unorderedlist>
   <listitem>Placeholder strings start with a colon (':') followed by
    one or more alphameric characters chosen from "a..z0..9_-".  A
    placeholder string matches any alphameric string in the same
    position of a URL.  The actual contents of the placeholder string
    following ':' are irrelevant.</listitem> 
   <listitem>Pattern strings start with any character except ':' or
    '/' followed by any character except '/'.  The contents of
    the pattern string are a
    {@link http://www.php.net/manual/en/ref.pcre.php Perl regular expression}.
   </listitem>
  </unorderedlist>

  <para>The order of routes in the table is significant.  When a URL
   is to be matched, the route table is searched in order from first
   entry to last.  The first route entry that matches is
   returned.</para>

  <para>{@link Router::find_route()} guarantees that there will always be
   at least one route in the table by inserting
   {@link Router::$default_route_path} if the table is empty when
   find_route() is called.</para>
 </refsect1>

 <refsect1 id="{@id build}">
  <title>Constructing the Route Table</title>

  <para>To construct a route table, first create a new Router object,
   then  make multiple calls to {@link Router::connect()} to add route
   entries to the object's table.  The second argument to connect(), if
   specified, must be an array, but the array can hold anything (or
   nothing).</para>

  <para>There is no way to change or delete an existing route table
   entry.</para>
  <para>Either assure that any path you search for will return some
  route, or prepare to deal with a null return from
  {@link Router::find_route()} </para>

  <para><important>CAUTION:</important> Do not call
   {@link Router::find_route()} before calling
   {@link Router::connect()}, unless you want the default route to be
   the first entry in the route table.</para>

 </refsect1>
 <refsect1 id="{@id search}">
  <title>Searching the Route Table</title>

  <para>To look up a URL in the route table, call
   {@link Router::find_route()} with the URL as argument.  A copy of
   the first matching route table entry, if any, will be returned.  If
   none matches, null will be returned.</para>

  <para>A URL consisting of a null string '' matches a route
   whose path is a null string.</para>

  <para>A copy of the last route table entry returned by find_route()
   is saved in {@link Router::$selected_route}.  It can be fetched by
   a call to {@link Router::get_selected_route()}.  The next call to
   find_route() overwrites the previous value in
   $selected_route.</para>

 </refsect1>
<!--
Local variables:
mode: xml
c-basic-offset: 1
indent-tabs-mode: nil
End:
-->
</refentry>
