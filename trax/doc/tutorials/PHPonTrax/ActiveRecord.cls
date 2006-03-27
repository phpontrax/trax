<refentry id="{@id}" revision="$Id$">
 <refnamediv>
  <refname>ActiveRecord</refname>
  <refpurpose>Base class for the active record design pattern</refpurpose>
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
 <refsect1 id="{@id active_record_intro}">
  <title>Introduction</title>
   <para>Heart of Active Record design pattern.  Encapsulates all
   access to data in corresponding table.  Enforces domain rules.
   Never instantiated directly.  Subclass for each table.</para>
   <para>Each subclass of this class is associated with a database table
   in the Model section of the Model-View-Controller architecture.
   By convention, the name of each subclass is the CamelCase singular
   form of the table name, which is in the lower_case_underscore
   plural notation.  For example, 
   a table named "order_details" would be associated with a subclass
   of ActiveRecord named "OrderDetail", and a table named "people"
   would be associated with subclass "Person".  Naming convention
   encapsulated in {@tutorial PHPonTrax/Inflector.cls} class.</para>
   <para>Convention: primary key of each table is column named "id".</para>
   <para>For a discussion of the ActiveRecord design pattern, see
   "Patterns of Enterprise 
   Application Architecture" by Martin Fowler, pp. 160-164.</para>
   <para>When object constructed, the format of a row in the
   associated table is retrieved from 
   the database and stored in object variable $content_columns.
   The data values of the columns are stored as object variable with
   the same name as the column.  Therefore, it is not possible to have
   a column with the same name as an object variable
   </para>
 </refsect1>
 <refsect1 id="{@id basic_methods}">
  <title>Basic Methods</title>
  <refsect2 id="{@id create_methods}">
   <title>Create</title>
    <para>Before you can use the subclass of ActiveRecord associated
    with a table, you must install the table definition in the
    database.  For example:</para>
    <example>
drop table if exists person_names;
create table person_names
  (id         int auto_increment primary key,
   prefix     varchar(20) null,
   first_name varchar(40) null,
   mi         char(1)     null,
   last_name  varchar(40) null,
   suffix     varchar(20) null);
    </example>
    <para>With the table definition installed in the database, you can
    create an object that references that table.  The
    {@link ActiveRecord::__construct() constructor} for
    PersonName connects to the database and reads the definition for
    table person_names to find the names of its columns, which then
    become the names of the related attributes of the object.</para>
    <example>
class PersonName extends ActiveRecord {
};
$p = new PersonName;    
$p->first_name = "Lo";
$p->last_name  = "Phat";
$p->suffix     = "MD";
$result = $p->save();
    </example>
    <para>$result will be true if the row was saved successfully, false
    otherwise.  Slightly more compact ways to say the same thing:</para>
    <example>
$p = new PersonName;    
$result = $p->save(array("first_name" => "Lo","last_name" => "Phat",
                     "suffix" => "MD"));
    </example>
    <para>or assign attribute values in the constructor:</para>
    <example>
$p = new PersonName(array("first_name" => "Lo","last_name" => "Phat",
                     "suffix" => "MD"));    
$result = $p->save();
    </example>
    <para>You can define the class so that it requires certain
    validation tests in order
    to be saved.  Here is a definition that will generate an error on
    save() if either "first_name" or "last_name" is left blank.
    When you call save(), the object is inspected for method
    names like 'validate_*' where '*' is the name of a column.
    All such methods are executed, and the results are tested.
    The return must be an array with true or false in the first
    element, and an error message in the second if the first is
    false.  If any of the 'validate_*' methods returns false, the
    save fails:</para>
    <example>
class PersonName extends ActiveRecord {
    //  Function to validate first name not blank
    function validate_first_name() {
        if ($this->first_name == '') {
            return array(false, "first name is blank");
        } else {
            return array(true);
        }
    }
    //  Function to validate last name not blank
    function validate_last_name() {
        if ($this->last_name == '') {
            return array(false, "last name is blank");
        } else {
            return array(true);
        }
    }
};
$result = $p->save(array("suffix" => "MD"));
if ($result == false) {
   //  Errors are listed in array $p->errors
   echo "save failed:\n";
   foreach ($p->errors as $key => $value) {
       echo "  field $key: $value\n";
   }
}
    </example>
    <para>This will output:</para>
    <example>
save failed:
  field first_name: first name is blank
  field last_name: last name is blank
    </example>
    <para>If you misspell an attribute, the misspelled name will
    become an attribute of the object, but it will not be saved
    because it is not the name of a column.  This will not result in
    an error message (unless that attribute fails validation):  For
    example:</para>
    <example>
$p = new PersonName(array("first_name" => "Lo","last_name" => "Phat",
                     "sufix" => "MD"));    
$result = $p->save();
    </example>
    <para>This will not fail, but the attribute $p->sufix will not be
    saved. Instead, column 'suffix' will be set to '' (empty string).
    </para>
  </refsect2>
  <refsect2 id="{@id read_methods}">
   <title>Read</title>
   <para>The find*() methods search the table associated with this
   class for rows that match some argument and return a new object of
   this class for each matching row.  In the simplest case, you know
   the value of the id column of the row you want:</para>
   <example>
$p = new PersonName();
$rs = $p->find("42");
//  $rs is a new object representing the row with id 42,
//  or false if it wasn't found.  $p object remains empty.
   </example>
   <para>You can ask for multiple id values with one find:</para>
   <example>
$rs = $p->find(array("17","23","65"));
//  $rs is an array containing zero or more new objects representing the
//  matching rows.  The keys of the array are the id values that matched.
   </example>
   <para>You can search the table more generally with the find_all()
   method, which allows you to provide an SQL clause to describe the
   match rule.  For example:</para>
   <example>
$rs = $p->find_all("last_name='Dover'");
//  $rs is an array containing zero or more new objects representing the
//  matching rows.  The keys of the array are the id values that matched.
   </example>
   <para>The argument you provide must be a valid SQL WHERE clause
   and it must contain an '='.</para>
   <para>If you omit the argument or provide an argument of null, you
   get all rows in the table.</para> 
   <para>You can also use a second argument to control the order of
   the results.  This argument must be a valid SQL ORDER BY clause.
   For example:</para>
   <example>
$rs = $p->find_all("last_name='Dover'","last_name,first_name");
   </example>
   <para>The find_first() method returns only the first matching row.
   Is there a doctor in the house?</para>
   <example>
$rs = $p->find_first("prefix='Dr.'");
//  $rs is a new object representing the first row with prefix 'Dr.',
//  or false if none was found
   </example>
   <para>An error, such as incorrect SQL, causes an
   {@tutorial PHPonTrax/ActiveRecordError.cls}
   exception to be thrown.  Note that
   it is not an error for no matching rows to be found.</para> 
  </refsect2>
  <refsect2 id="{@id update_methods}">
   <title>Update</title>
    <para>@todo Update tutorial</para>
  </refsect2>
  <refsect2 id="{@id delete_methods}">
   <title>Delete</title>
    <para>@todo Delete tutorial</para>
  </refsect2>
  <refsect2 id="{@id agg_methods}">
   <title>SQL Aggregate Functions</title>
    <para>SQL defines aggregate functions AVG(), COUNT(), MAX(), MIN()
     and SUM().  Not all DBMS's implement all of them.  Implemented by
     methods avg_all(), count_all(), max_all(), min_all() and sum_all().
     Parameters: [0]: If present, expression to apply
            the aggregate function to.  Otherwise, '*' will be used.
            <b>NOTE:</b>SQL uses '*' only for the COUNT() function,
            where it means "including rows with NULL in this column".
          [1]: argument to WHERE clause
          [2]: joins??? @todo. Joins parameter  Method call is calculated from list
          in $aggregrations</para>

   <refsect3 id="{@id agg_method_avg}">
    <title>avg_all()</title>
    <para>@todo Document avg_all()</para>
   </refsect3>
   <refsect3 id="{@id agg_method_count}">
    <title>count_all()</title>
    <para>@todo Document count_all()</para>
    <para> </para>
   </refsect3>
   <refsect3 id="{@id agg_method_max}">
    <title>max_all()</title>
    <para>@todo Document max_all()</para>
    <para> </para>
   </refsect3>
   <refsect3 id="{@id agg_method_min}">
    <title>min_all()</title>
    <para>@todo Document min_all()</para>
    <para> </para>
   </refsect3>
   <refsect3 id="{@id agg_method_sum}">
    <title>sum_all()</title>
    <para>@todo Document sum_all()</para>
    <para> </para>
   </refsect3>
  </refsect2>
 </refsect1>
 <refsect1 id="{@id associations}">
  <title>Associations</title>
    <para>@todo Document associations</para>
    <para>belongs_to</para>
    <para>has_one</para>
    <para>has_many</para>
    <para>has_and_belongs_to_many</para>
 </refsect1>
<!--
Local variables:
mode: xml
c-basic-offset: 1
indent-tabs-mode: nil
End:
-->
</refentry>
