<refentry id="{@id}" revision="$Id$">
 <refnamediv>
  <refname>Dispatcher</refname>
  <refpurpose>Restore session state, then process URL</refpurpose>
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
  <para>{@link Dispatcher} is a
  {@link http://en.wikipedia.org/wiki/Singleton_pattern singleton}
  class (although not enforced by the constructor) with one method.  When
  {@link http://httpd.apache.org/ Apache} receives an HTTP request,
  {@link http://httpd.apache.org/docs/2.0/mod/mod_rewrite.html Apache mod_rewrite}
  converts the URL to an invocation of dispatch.php with the URL as argument.
  dispatch.php instantiates a Dispatcher and calls its
  {@link Dispatcher::dispatch() dispatch()}
  method, which restores session state and creates an
  {@tutorial PHPonTrax/ActionController.cls} object to process the URL.</para>
 </refsect1>
</refentry>
