<refentry id="{@id}" revision="$Id$" revision="$Id$">
 <refnamediv>
  <refname>Inflector</refname>
  <refpurpose>Implement the Trax naming convention</refpurpose>
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
  <para>This class provides static methods to implement the
   {@tutorial PHPonTrax/naming.pkg Trax naming convention}.
   Inflector is never instantiated.</para>
 </refsect1>
 <refsect1 id="{@id examples}">
  <title>Conversion Examples</title>
  <refsect2 id="{@id example_tableize}">
   <title>Find Table Name For This Class</title>
   <example>
class OrderDetail extends ActiveRecord {
    function __construct() {
        $table_name = Inflector::tableize(get_class());
        //  $table_name is now "order_details"
    }
}
   </example>
  </refsect2>
  <refsect2 id="{@id example_classify}">
   <title>Find Class Name For A Table</title>
   <example>
$class_name = Inflector::classify("people");
//  $class_name is now "Person"
   </example>
  </refsect2>
  <refsect2 id="{@id example_humanize}">
   <title>Generate User Friendly Description Of A Table</title>
   <example>
$readable_name = Inflector::humanize("monthly_reports");
//  $readable_name is now "Monthly Reports"
   </example>
  </refsect2>
  <refsect2 id="{@id example_classify}">
   <title>Find Foreign Key For A Class</title>
   <example>
$key_name = Inflector::foreign_key("PartNumber");
//  $key_name is now "part_number_id"
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
