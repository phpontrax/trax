<refentry id="{@id}" revision="$Id$" revision="$Id$">
 <refnamediv>
  <refname>Inflector</refname>
  <refpurpose>Convert names between classes and database tables</refpurpose>
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
  <para>There is a naming convention that relates the name of a
   database table to the name of the {@tutorial PHPonTrax/ActiveRecord.cls}
   subclass
   that represents that table.  The {@link Inflector} class provides a
   group of class methods that  support conversion between table and
   subclass names.  Inflector is never instantiated.</para>
 </refsect1>
 <refsect1 id="{@id naming}">
  <title>Class - Table Naming Convention</title>
  <para>There is one subclass of ActiveRecord for each table in the
   database.  The name of the subclass is a singular word or phrase in
   CamelCase notation, and 
   the name of the table is the English plural of the same word or
   phrase in lower_case_underscore notation.  The table name describes
   the overall table contents, while the subclass name describes a
   single row in the table.  For example, a table 
   named <important>order_details</important> would correspond to a
   subclass named <important>OrderDetail</important>, while
   subclass <important>Person</important> would match table
   <important>people</important>.</para>
  <para>There is also a naming convention for foreign keys.  A foreign
   key which selects a row in a table by (only) the "id" column is the
   singular lower_case_underscore name of that table with suffix
   "_id".  For example, a foreign key to the <important>people</important>
   table's "id" column would conventionally be stored in a column
   named "person_id". 
  </para>
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
