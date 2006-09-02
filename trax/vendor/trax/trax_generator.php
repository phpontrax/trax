<?php
/**
 *  File containing the TraxGenerator class
 *
 *  (PHP 5)
 *
 *  @package PHPonTrax
 *  @version $Id$
 *  @copyright (c) 2005 John Peterson
 *
 *  Permission is hereby granted, free of charge, to any person obtaining
 *  a copy of this software and associated documentation files (the
 *  "Software"), to deal in the Software without restriction, including
 *  without limitation the rights to use, copy, modify, merge, publish,
 *  distribute, sublicense, and/or sell copies of the Software, and to
 *  permit persons to whom the Software is furnished to do so, subject to
 *  the following conditions:
 *
 *  The above copyright notice and this permission notice shall be
 *  included in all copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 *  EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 *  MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 *  NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 *  LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 *  OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 *  WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 *  @package PHPonTrax
 */

/**
 *  Generate application files in the Trax work area
 *
 *  Implements the commands of {@link generate.php script/generate.php}
 *  <p>Legal commands:</p>
 *  <ul>
 *    <li>{@link generate_controller() controller}</li>
 *    <li>{@link generate_model() model}</li>
 *    <li>{@link generate_scaffold() scaffold}</li>
 *  </ul>
 */
class TraxGenerator {

    /**
     *  Filesystem path to the app/views directory in the Trax work area
     *  @var string
     */
    private $view_path;

    /**
     *  Filesystem path to the app/controllers directory in the Trax work area
     *  @var string
     */
    private $controller_path;

    /**
     *  Filesystem path to the app/helpers directory in the Trax work area
     *  @var string
     */
    private $helper_path;

    /**
     *  Filesystem path to the app/model directory in the Trax work area
     *  @var string
     */
    private $model_path;

    /**
     *  Generated subdirectories in the Trax work area
     *
     *  When a controller is generated with a name that includes '/',
     *  $extra_path is set to the implied subdirectories.
     *  @var string
     */
    private $extra_path;

    /**
     *  Platform-dependent command to make a directory
     *  @var string
     */
    private $mkdir_cmd;

    /**
     *  Filesystem path to the templates/controller.php file
     *  @var string
     */
    private $controller_template_file;

    /**
     *  Filesystem path to the templates/helper.php file
     *  @var string
     */
    private $helper_template_file;

    /**
     *  Filesystem path to the templates/view.phtml file
     *  @var string
     */
    private $view_template_file;

    /**
     *  Filesystem path to the templates/model.php file
     *  @var string
     */
    private $model_template_file;

    /**
     *  Filesystem path to templates/scaffolds/generator_templates directory
     *  @var string
     */
    private $scaffold_template_path;

    /**
     *  Filesystem path to the app/views/layouts/ directory in the
     *  Trax work area
     *  @var string
     */
    private $layouts_path;

    /**
     *  @todo Document this variable
     *
     *  Value is set by {@link generate_controller()} and used by
     *  {@link generate_scaffold()}
     *  @var string
     */
    private $layout_filename;

    /**
     *  CamelCase name of the controller class
     *  @var string
     */
    private $controller_class;

    /**
     *  Constructor for the TraxGenerator object
     *
     *  Compute and store filesystem paths to the various
     *  subdirectories of the Trax work area and the template files
     *  used to generate application files
     *
     *  @uses controller_path
     *  @uses controller_template_file
     *  @uses helper_path
     *  @uses helper_template_file
     *  @uses layouts_path
     *  @uses model_path
     *  @uses model_template_file
     *  @uses scaffold_template_path
     *  @uses view_path
     *  @uses view_template_file
     */
    function __construct() {
        $this->view_path = Trax::$views_path;
        $this->controller_path = Trax::$controllers_path;
        $this->helper_path = Trax::$helpers_path;
        $this->model_path = Trax::$models_path;
        $this->layouts_path = Trax::$layouts_path;
        $this->controller_template_file =
               TRAX_LIB_ROOT . "/templates/controller.php";
        $this->helper_template_file =
               TRAX_LIB_ROOT . "/templates/helper.php";
        $this->view_template_file =
               TRAX_LIB_ROOT . "/templates/view.phtml";
        $this->model_template_file =
               TRAX_LIB_ROOT . "/templates/model.php";
        $this->scaffold_template_path =
               TRAX_LIB_ROOT . "/templates/scaffolds/generator_templates";
        $this->mailer_view_template_file =
               TRAX_LIB_ROOT . "/templates/mailer_view.phtml";
        $this->mailer_model_template_file =
               TRAX_LIB_ROOT . "/templates/mailer_model.php";

        if (substr(PHP_OS, 0, 3) == 'WIN') {
            $this->mkdir_cmd = "mkdir";
        } else {
            $this->mkdir_cmd = "mkdir -p";
        }
    }

    /**
     *  Parse command line and carry out the command
     *
     *  Command line arguments, if any are in $_SERVER['argv']
     *  @uses controller_help()
     *  @uses generate_controller()
     *  @uses generate_model()
     *  @uses generate_scaffold()
     *  @uses generator_help()
     *  @uses model_help()
     *  @uses scaffold_help()
     */
    function run() {

        //  If command line arguments exist, parse them
        if (array_key_exists('argv', $_SERVER)) {
            if (array_key_exists(1, $_SERVER['argv'])) {
                $command = strtolower($_SERVER["argv"][1]);
            }
            if (array_key_exists(2, $_SERVER['argv'])) {
                $command_name = $_SERVER["argv"][2];
            }
        }

        //  Execute command or output a diagnostic
        if(empty($command)) {
            $this->generator_help();
        } else {
            switch($command) {

                //  Process "controller" command
                case "controller":
                    if(empty($command_name)) {
                        $this->controller_help();
                    } else {
                        $views = array();
                        if(array_key_exists(3, $_SERVER['argv'])
                           && ($_SERVER["argv"][3] != "")) {
                            for($i=3;$i < count($_SERVER["argv"]);$i++) {
                               $views[] = strtolower($_SERVER["argv"][$i]);
                            }
                        }
                        $this->generate_controller($command_name, $views);
                    }
                    break;

                //  Process "model" command
                //  $command_name is the name of the model
                case "model":
                    if(empty($command_name)) {
                        $this->model_help();
                    } else {
                        $this->generate_model($command_name);
                    }
                    break;

                //  Process "scaffold" command
                //  $command_name has the name of the model
                //  $_SERVER['argv'][3] has the name of the controller
                case "mailer":
                    if(empty($command_name)) {
                        $this->mailer_help();
                    } else {
                        $views = array();
                        if(array_key_exists(3, $_SERVER['argv'])
                           && ($_SERVER["argv"][3] != "")) {
                            for($i=3;$i < count($_SERVER["argv"]);$i++) {
                               $views[] = strtolower($_SERVER["argv"][$i]);
                            }
                        }
                        $this->generate_mailer($command_name, $views);
                    }
                    break;                    
                case "scaffold":

                    //  Model name is required
                    if( empty($command_name) ) {
                        echo "Error: name of model omitted\n";
                        $this->scaffold_help();
                        break;
                    }

                    //  Controller name is optional
                    if (array_key_exists(3, $_SERVER["argv"])) {
                        $controller_name = $_SERVER["argv"][3];
                    } else {
                        $controller_name = null;
                    }
                        
                    //  Views are optional following controller name
                    $views = array();
                    if (array_key_exists(4, $_SERVER["argv"])
                        && ($_SERVER["argv"][4] != "")) {
                        for($i=4;$i < count($_SERVER["argv"]);$i++) {
                            $views[] = strtolower($_SERVER["argv"][$i]);
                        }
                    }
                    $this->generate_scaffold($command_name,
                                             $controller_name, $views);
                    break;                    

            default:
                $this->generator_help();
            }                            // switch($command)
        }
        return;
    }

    /**
     *  Implement "generate controller" command
     *
     *  <p>Example:<br><samp>php script/generate.php controller</samp>
     *  <i>SomeName</i><br>
     *  will generate:</p>
     *  <ul>
     *    <li>a file
     *  <samp>app/controllers/</samp><i>some_name</i><samp>_controller.php</samp><br>
     *  containing the class definition<br>
     *  <samp>class</samp> <i>SomeName</i><samp>Controller extends
     *  ApplicationController {}</samp></li>
     *     <li>a file
     *  <samp>app/helpers/</samp><i>some_name</i><samp>_helper.php</samp></li>
     *     <li>a directory
     *  <samp>app/views/</samp><i>some_name</i></li>
     *  </ul>
     *
     *  <p>Optionally, one or more views can be appended to the command:<br>
     *  <samp>php script/generate.php controller</samp>
     *  <i>SomeName view1 view2</i><br>
     *  which will additionally generate files:<br>
     *  <samp>app/views/</samp><i>some_name/view1</i><samp>.phtml</samp><br>
     *  <samp>app/views/</samp><i>some_name/view2</i><samp>.phtml</samp></p>
     *
     *  @param string $name Name in CamelCase of the controller to generate.
     *                      The value may include '/' which will cause
     *                      creation of subdirectories indicated to
     *                      hold the controller and view files.
     *  @param string $views  Optional list of views to generate
     *  @param boolean $scaffolding
     *  @uses Inflector::underscore()
     *  @uses $controller_class   Set during call
     *  @uses $controller_path    Must be set before call.
     *  @uses create_controller()
     *  @uses create_helper()
     *  @uses create_view()
     *  @uses $extra_path         Set during call
     *  @uses $helper_path        Must be set before call.
     *  @uses $layouts_path       Must be set before call.
     *  @uses $layout_filename    Set during call
     *  @uses $view_path          Must be set before call.
     */
    function generate_controller($name, $views = "", $scaffolding = false) {

        # Set the View and Controller extra path info
        if(stristr($name, "/")) {
            $this->extra_path = substr($name,0,strrpos($name, "/"));
            $name = Inflector::underscore(substr($name,strrpos($name, "/")+1));
            $this->view_path .= "/$this->extra_path/$name";           
            $this->layouts_path .= "/$this->extra_path";
            $this->controller_path .= "/$this->extra_path";
            $this->helper_path .= "/$this->extra_path";
        } else {
            $name = Inflector::underscore($name);
            $this->view_path .= "/$name";
        }
        $this->layout_filename = $name;
        $this->controller_class = Inflector::camelize($name);

        # Create the extra folders for View / Controller
        if(file_exists($this->view_path)) {
            echo "exists $this->view_path\n";
        } else{
            $this->exec("$this->mkdir_cmd $this->view_path");
            echo "create $this->view_path\n";
        }

        if(file_exists($this->controller_path)) {
            echo "exists $this->controller_path\n";
        } else {
            $this->exec("$this->mkdir_cmd $this->controller_path");
            echo "create $this->controller_path\n";
        }

        if(file_exists($this->helper_path)) {
            echo "exists $this->helper_path\n";
        } else {
            $this->exec("$this->mkdir_cmd $this->helper_path");
            echo "create $this->helper_path\n";
        }

        # Create the actual controller/helper files
        if(!$scaffolding) {
            $this->create_controller($name, $views);
        } 
        $this->create_helper($name);

        if($this->extra_path) {
            $name = $this->extra_path."/".$name;
        }

        # Create view files if any
        if(is_array($views)) {
            foreach($views as $view) {
                $this->create_view($view,$name);
            }
        } elseif(!empty($views)) {
            $this->create_view($views,$name);
        }
    }

    function generate_mailer($name, $views = "") {
        if(stristr($name, "_")) {
            $model_file = $this->model_path."/".strtolower($name).".php";
        } else {
            $model_file = $this->model_path."/".Inflector::underscore($name).".php";
        }

        $model_class = Inflector::camelize($name);

        if(!file_exists($model_file)) {
            if(file_exists($this->mailer_model_template_file)) {
                $template = file_get_contents($this->mailer_model_template_file);
                $template = str_replace('[class_name]',$model_class,$template);
                # Add view methods
                if (!empty($views)) {
                    # There are some views, add a method for each
                    if(is_array($views)) {
                        # Multiple views in an array
                        foreach($views as $view) {
                            $method  = "\tfunction $view() {\n";
                            $method .= "\t\t\$this->subject    = '".$model_class."->".$view."';\n";
                            $method .= "\t\t\$this->recipients = '';\n";
                            $method .= "\t\t\$this->from       = '';\n";
                            $method .= "\t\t\$this->headers    = array();\n";
                            $method .= "\t\t\$this->body       = array();\n";
                            $method .= "\t}";
                            $class_methods[] = $method;
                        }
                        $class_methods = implode("\n\n",$class_methods);
                    } else {
                        $class_methods  = "\tfunction $views() {\n";
                        $class_methods .= "\t\t\$this->subject    = '".$model_class."->".$views."';\n";
                        $class_methods .= "\t\t\$this->recipients = '';\n";
                        $class_methods .= "\t\t\$this->from       = '';\n";
                        $class_methods .= "\t\t\$this->headers    = array();\n";
                        $class_methods .= "\t\t\$this->body       = array();\n";
                        $class_methods .= "\t}";                        
                    }
                    $template = str_replace('[class_methods]', $class_methods, $template);
                } else {
                    # No view methods to add, so remove unneeded template
                    $template = str_replace('[class_methods]', '', $template);
                }
                # Write the mailer model to disk
                if(!file_put_contents($model_file, $template)) {
                    echo "error creating mailer model file: $model_file\n";
                } else {
                    echo "create $model_file\n";
                }
            } else {
                echo "error mailer model template file doesn't exist: $this->mailer_model_template_file\n";
            }
        } else {
            echo "exists $model_file\n";
        }        
        
        # Now create the view files
        $name = Inflector::underscore($name);
        $this->view_path .= "/$name"; 
        $this->view_template_file = $this->mailer_view_template_file;
        $this->controller_class = $model_class;

        # Create the extra folders for View / Controller
        if(file_exists($this->view_path)) {
            echo "exists $this->view_path\n";
        } else{
            $this->exec("$this->mkdir_cmd $this->view_path");
            echo "create $this->view_path\n";
        }

        # Create view files if any
        if(is_array($views)) {
            foreach($views as $view) {
                $this->create_view($view, $name);
            }
        } elseif(!empty($views)) {
            $this->create_view($views, $name);
        }
             
        
    }

    /**
     *  Implement the "generate model" command
     *
     *  <p>Example:<br><samp>php script/generate.php model</samp>
     *  <i>SomeName</i><br>
     *  will generate a file
     *  <samp>app/models/</samp><i>some_name</i><samp>.php</samp><br>
     *  containing the class definition<br>
     *  <samp>class</samp> <i>SomeName</i> <samp>extends
     *  ActiveRecord {}</samp>
     *  @param string $name Name of the model.  May be in either
     *                under_score or CamelCase.  If no '_' exists in
     *                $name it is treated as CamelCase.
     *  @uses Inflector::underscore()
     *  @uses model_path           Must be set before call.
     *                             Not changed during call.
     *  @uses model_template_file  Must be set before call.
     *                             Not changed during call.
     */
    function generate_model($name) {

        if(stristr($name, "_")) {
            $model_file = $this->model_path."/".strtolower($name).".php";
        } else {
            $model_file = $this->model_path."/".Inflector::underscore($name).".php";
        }

        $model_class = Inflector::camelize($name);

        if(!file_exists($model_file)) {
            if(file_exists($this->model_template_file)) {
                $template = file_get_contents($this->model_template_file);
                $template = str_replace('[class_name]',$model_class,$template);
                if(!file_put_contents($model_file,$template)) {
                    echo "error creating model file: $model_file\n";
                } else {
                    echo "create $model_file\n";
                    return true;
                }
            } else {
                echo "error model template file doesn't exist: $this->model_template_file\n";
            }
        } else {
            echo "exists $model_file\n";
            return true;
        }
        return false;
    }
    
    /**
     *  Implement the "generate scaffold" command
     *
     *  @param string $model_name
     *  @param string $controller_name
     *  @param string $views
     *  @uses generate_controller()
     *  @uses generate_model()
     *  @uses Inflector::classify()
     *  @uses Inflector::humanize()
     *  @uses Inflector::pluralize()
     *  @uses Inflector::singularize()
     *  @uses Inflector::underscore()
     *  @uses $layout_filename          Set as output from
     *                                  generate_controller().
     *                                  Not changed afterward.
     *  @uses fix_php_brackets()
     */
    function generate_scaffold($model_name, $controller_name, $views="") {
        //echo 'generate_scaffold("'.$model_name.'", "'
        //          .$controller_name.'", "'.$views.'")'."\n";
        if(!$model_exists = $this->generate_model($model_name)) {
            echo "Error - Can't create Model: $model_name.\n";    
            return;
        }

        Trax::$current_controller_object =& $this;
        $model_class_name = Inflector::classify($model_name);
        $singular_model_name = Inflector::singularize($model_name);
        $plural_model_name = Inflector::pluralize($model_name);  
        $human_model_name = Inflector::humanize($model_name);      

        try {
            $this->{$singular_model_name} = new $model_class_name();
        } catch (ActiveRecordError $e) {
            echo "Can't create model.\n";
            echo $e->getMessage()."\n";
            echo "for database '"
                . ActiveRecord::$database_settings[TRAX_ENV]['database']
                . "' on host '"
                . ActiveRecord::$database_settings[TRAX_ENV]['hostspec']
                . "' as user '"
                . ActiveRecord::$database_settings[TRAX_ENV]['username']
                . "'\nDid you configure file "
                . Trax::$config_path
                . "/database.ini correctly?\n";
            die();
        }
        if(empty($controller_name)) {
            $controller_name = Inflector::underscore($model_name);   
        } else {
            $controller_name = Inflector::underscore($controller_name);    
        }
        Trax::$current_controller_name = $controller_name;
        $controller_file = "$this->controller_path/" . $controller_name."_controller.php";
        Trax::$current_controller_path = $controller_file;
        $non_scaffolded_actions = array();
        $illegal_views = array("index","add","edit","show");      
        if(is_array($views)) {
            foreach($views as $view) {
                if(!in_array($view, $illegal_views)) {
                    $non_scaffolded_actions[] = $view;  
                }           
            }
        }         
        $this->generate_controller($controller_name,
                                   $non_scaffolded_actions, true); 
        if(stristr($controller_name, "/")) {
            $controller_class_name =
                Inflector::classify(substr($controller_name,
                                           strrpos($controller_name, "/")+1));
            $human_controller_name =
                Inflector::humanize(substr($controller_name,
                                           strrpos($controller_name, "/")+1));
        } else {
            $controller_class_name = Inflector::classify($controller_name);
            $human_controller_name = Inflector::humanize($controller_name);
        }             
     
        # Generate the controller
        ob_start();    
        include("$this->scaffold_template_path/controller.php");
        $controller_contents = $this->fix_php_brackets(ob_get_contents());
        ob_end_clean();
        if(!file_exists($controller_file)) {
            if(!file_put_contents($controller_file, $controller_contents)) {
                echo "error creating controller class file: $controller_file\n";
            } else {
                echo "create $controller_file\n";
            }        
        } else {
            echo "exists $controller_file\n";        
        } 
                
        # Generate the index.phtml view
        $view_file = "$this->view_path/index.".Trax::$views_extension;
        ob_start();    
        include("$this->scaffold_template_path/view_index.phtml");
        $index_contents = $this->fix_php_brackets(ob_get_contents());
        ob_end_clean();
        if(!file_exists($view_file)) {
            if(!file_put_contents($view_file, $index_contents)) {
                echo "error creating view file: $view_file\n";
            } else {
                echo "create $view_file\n";
            }
        } else {
            echo "exists $view_file\n";        
        } 
               
        # Generate the add.phtml view
        $view_file = "$this->view_path/add.".Trax::$views_extension;
        ob_start();    
        include("$this->scaffold_template_path/view_add.phtml");
        $add_contents = $this->fix_php_brackets(ob_get_contents());
        ob_end_clean();       
        if(!file_exists($view_file)) {
            if(!file_put_contents($view_file, $add_contents)) {
                echo "error creating view file: $view_file\n";
            } else {
                echo "create $view_file\n";
            }
        } else {
            echo "exists $view_file\n";        
        } 
        
        # Generate the edit.phtml view
        $view_file = "$this->view_path/edit.".Trax::$views_extension;
        ob_start();    
        include("$this->scaffold_template_path/view_edit.phtml");
        $edit_contents = $this->fix_php_brackets(ob_get_contents());
        ob_end_clean(); 
        if(!file_exists($view_file)) {
            if(!file_put_contents($view_file, $edit_contents)) {
                echo "error creating view file: $view_file\n";
            } else {
                echo "create $view_file\n";
            }
        } else {
            echo "exists $view_file\n";        
        } 
        
        # Generate the show.phtml view
        $view_file = "$this->view_path/show.".Trax::$views_extension;
        ob_start();    
        include("$this->scaffold_template_path/view_show.phtml");
        $show_contents = $this->fix_php_brackets(ob_get_contents());
        ob_end_clean();
        if(!file_exists($view_file)) {
            if(!file_put_contents($view_file, $show_contents)) {
                echo "error creating view file: $view_file\n";
            } else {
                echo "create $view_file\n";
            }
        } else {
            echo "exists $view_file\n";        
        } 
               
        # Generate the partial containing the form elments from the database
        $view_file = "$this->view_path/_form.".Trax::$views_extension;
        ob_start();    
        require "$this->scaffold_template_path/form_scaffolding.phtml";
        $_form_contents = $this->fix_php_brackets(ob_get_contents());
        ob_end_clean();  
        if(!file_exists($view_file)) {
            if(!file_put_contents($view_file, $_form_contents)) {
                echo "error creating view file: $view_file\n";
            } else {
                echo "create $view_file\n";
            }
        } else {
            echo "exists $view_file\n";        
        } 
        
        # Generate the layout for the scaffolding
        $layout_file = $this->layouts_path."/".$this->layout_filename.".".Trax::$views_extension;
        if(!file_exists($this->layouts_path)) {
            mkdir($this->layouts_path);        
        }
        ob_start();    
        include("$this->scaffold_template_path/layout.phtml");
        $layout_contents = $this->fix_php_brackets(ob_get_contents());
        ob_end_clean();  
        if(!file_exists($layout_file)) {
            if(!file_put_contents($layout_file, $layout_contents)) {
                echo "error creating layout file: $layout_file\n";
            } else {
                echo "create $layout_file\n";
            }
        } else {
            echo "exists $layout_file\n";        
        }                   
    }    

    /**
     *  Create a controller file with optional view methods
     *
     *  @param string $controller Name of the controller
     *  @param string[] $views    Name(s) of view(s), if any
     *  @uses controller_class    Must be set before call.
     *                            Not changed during call.
     *  @uses controller_path     Must be set before call.
     *                            Not changed during call.
     *  @uses controller_template_file Must be set before call.
     *                            Not changed during call.
     *  @todo Should return succeed/fail indication
     */
    function create_controller($controller,$views="") {

        $controller_file = "$this->controller_path/"
            . $controller . "_controller.php";

        if(!file_exists($controller_file)) {
            if(file_exists($this->controller_template_file)) {
                $template = file_get_contents($this->controller_template_file);
                $template = str_replace('[class_name]',
                                        $this->controller_class,$template);
                //  Add view methods
                if (!empty($views)) {

                    //  There are some views, add a method for each
                    if(is_array($views)) {

                        //  Multiple views in an array
                        foreach($views as $view) {
                            $class_methods[] = "\tfunction $view() {\n\t}";
                        }
                        $class_methods = implode("\n\n",$class_methods);
                    } else {
                        $class_methods = "\tfunction $views() {\n\t}\n\n";
                    }
                    $template = str_replace('[class_methods]',
                                            $class_methods,$template);
                } else {

                    //  No view methods to add, so remove unneeded template
                    $template = str_replace('[class_methods]', '',$template);
                }

                if(!file_put_contents($controller_file,$template)) {
                    echo "error creating controller class file: "
                        . $controller_file . "\n";
                } else {
                    echo "create $controller_file\n";
                }

            } else {
                echo "error controller template file doesn't exist: "
                    . $this->controller_template_file . "\n";
            }
        } else {
            echo "exists $controller_file\n";
        }
    }

    /**
     *  Create a helper file for a controller
     *
     *  @param string $controller Name of the controller
     *  @uses controller_class      Must be set before call.
     *                              Not changed during call.
     *  @uses helper_path           Must be set before call.
     *                              Not changed during call.
     *  @uses helper_template_file  Must be set before call.
     *                              Not changed during call.
     *  @todo Should return succeed/fail indication
     */
    function create_helper($controller) {
        $helper_file = "$this->helper_path/".$controller."_helper.php";
        if(!file_exists($helper_file)) {
            if(file_exists($this->helper_template_file)) {
                $template = file_get_contents($this->helper_template_file);
                $template = str_replace('[class_name]',
                                        $this->controller_class,$template);
                if(!file_put_contents($helper_file,$template)) {
                    echo "error creating helper file: $helper_file\n";
                } else {
                    echo "create $helper_file\n";
                }

            } else {
                echo "error helper template file doesn't exist: "
                    . $this->helper_template_file . "\n";
            }
        } else {
            echo "exists $helper_file\n";
        }
    }

    /**
     *  Create a view file if it doesn't exist
     *
     *  Create a view file in the Trax work area if the required file
     *  does not yet exist.  Generate the view file contents by
     *  customizing the view template file with information about the
     *  controller and view names.
     *
     *  @param string $view           Name of the view
     *  @param string $controller     Name of the controller
     *  @uses controller_class        Must be set before call.
     *                                Not changed during call.
     *  @uses view_path               Must be set before call.
     *                                Not changed during call.
     *  @uses view_template_file      Must be set before call.
     *                                Not changed during call.
     *  @todo Should return succeed/fail indication
     */
    function create_view($view, $controller) {
        $view_file = "$this->view_path/".$view.".".Trax::$views_extension;
        if(!file_exists($view_file)) {
            if(file_exists($this->view_template_file)) {
                $template = file_get_contents($this->view_template_file);
                $template = str_replace('[class_name]',
                                        $this->controller_class,$template);
                $template = str_replace('[controller]',$controller,$template);
                $template = str_replace('[view]',$view,$template);
                if(!file_put_contents($view_file,$template)) {
                    echo "error creating view file: $view_file\n";
                } else {
                    echo "create $view_file\n";
                }
            } else {
                echo "error view template file doesn't exist: "
                    . $this->view_template_file . "\n";
            }
        } else {
            echo "exists $view_file\n";
        }
    }

    /**
     *  Execute an operating system command
     *
     *  @param string $cmd  Command to be executed
     *  @todo Replace with calls to filesystem methods
     */
    function exec($cmd) {
        if (substr(PHP_OS, 0, 3) == 'WIN') {
            exec(str_replace("/","\\",$cmd));
        } else {
            exec($cmd);
        }
    }
    
    /**
     *  Replace "< ?php ... ? >" with "<?php ... ?>"
     *
     *  @param string $string  String to be edited
     *  @return string Edited input string
     */
    function fix_php_brackets($string) {
        return str_replace("? >", "?>",
                           str_replace("< ?php", "<?php", $string));
    }

    /**
     *  Output console help message for "generate controller"
     */
    function controller_help() {
        echo "Usage: php generate.php controller ControllerName [view1 view2 ...]\n\n";
        echo "Description:\n";
        echo "\tThe controller generator creates functions for a new controller and\n";
        echo"\tits views.\n\n";
        echo "\tThe generator takes a controller name and a list of views as arguments.\n";
        echo "\tThe controller name may be given in CamelCase or under_score and should\n";
        echo "\tnot be suffixed with 'Controller'.  To create a controller within a\n";
        echo "\tmodule, specify the controller name as 'folder/controller'.\n";
        echo "\tThe generator creates a controller class in app/controllers with view\n";
        echo "\ttemplates in app/views/controller_name.\n\n";
        echo "Example:\n";
        echo "\tphp script/generate.php controller CreditCard open debit credit close\n\n";
        echo "\tCredit card controller with URLs like /credit_card/debit.\n";
        echo "\t\tController: app/controllers/credit_card_controller.php\n";
        echo "\t\tViews:      app/views/credit_card/debit.phtml [...]\n";
        echo "\t\tHelper:     app/helpers/credit_card_helper.php\n\n";
        echo "Module/Folders Example:\n";
        echo "\tphp script/generate.php controller 'admin/credit_card' suspend late_fee\n\n";
        echo "\tCredit card admin controller with URLs /admin/credit_card/suspend.\n";
        echo "\t\tController: app/controllers/admin/credit_card_controller.php\n";
        echo "\t\tViews:      app/views/admin/credit_card/suspend.phtml [...]\n";
        echo "\t\tHelper:     app/helpers/credit_card_helper.php\n\n";
    }

    /**
     *  Output console help message for "generate model"
     */
    function model_help() {
        echo "Usage: php generate.php model ModelName\n";
        echo "Description:\n";
        echo "\tThe model generator creates functions for a new model.\n";
        echo "\tThe generator takes a model name as its argument.  The model name\n";
        echo "\tmay be given in CamelCase or under_score and should not be suffixed\n";
        echo "\twith 'Model'. The generator creates a model class in app/models.\n";
        echo "Example:\n";
        echo "\tphp script/generate.php model Account\n";
        echo "\tThis will create an Account model:\n";
        echo "\t\tModel:      app/models/account.php\n\n";
    }

    /**
     *  Output console help message for "generate mailer"
     */
    function mailer_help() {
        echo "Usage: php script/generate.php mailer MailerName [view1 view2 ...]\n\n";
        echo "Description:\n";
        echo "\tThe mailer generator creates class methods for a new mailer and its views.\n\n";
        echo "\tThe generator takes a mailer name and a list of views as arguments.\n";
        echo "\tThe mailer name may be given in CamelCase or under_score.\n\n";
        echo "\tThe generator creates a mailer class in app/models with view templates\n";
        echo "\tin app/views/mailer_name.\n\n";
        echo "Example:\n";
        echo "\tphp script/generate.php mailer Notifications signup forgot_password invoice\n\n";
        echo "\tThis will create a Notifications mailer class:\n";
        echo "\t\tMailer:     app/models/notifications.php\n";
        echo "\t\tViews:      app/views/notifications/signup.phtml [...]\n\n";  
    }
    
    /**
     *  Output console help message for "generate scaffold"
     */
    function scaffold_help() {
        echo "Usage: php script/generate.php scaffold ModelName [ControllerName] [view1 view2 ...]\n\n";
        echo "Description:\n";
        echo "\tThe scaffold generator creates a controller to interact with a model.\n";
        echo "\tIf the model does not exist, it creates the model as well.  The\n";
        echo "\tgenerated code is equivalent to the ( public \$scaffold = \"model\"; )\n";
        echo "\tdeclaration, making it easy to migrate when you wish to customize\n";
        echo "\tyour controller and views.\n\n";
        echo "\tThe generator takes a model name, an optional controller name, and a\n";
        echo "\tlist of views as arguments.  Scaffolded actions and views are created\n";
        echo "\tautomatically.\n\n";
        echo "\tThe auto scaffolded actions and views are:\n";
        echo "\t\tindex, show, add, edit, delete\n\n";
        echo "\tIf a controller name is not given, the plural form of the model name\n";
        echo "\twill be used.  The model and controller names may be given in CamelCase\n";
        echo "\tor under_score and should not be suffixed with 'Model' or 'Controller'.\n\n";
        echo "Example:\n";
        echo "\tphp script/generate.php scaffold Account Bank debit credit\n\n";
        echo "\tThis will generate an Account model and BankController with a basic\n";
        echo "\tuser interface.  Now create the accounts table in your database and\n";
        echo "\t browse to http://localhost/bank/.  Voila, you're on Trax!\n\n";
        echo "Module/Folders Example:\n";
        echo "\tphp script/generate.php scaffold CreditCard 'admin/credit_card' suspend late_fee\n\n";
        echo "\tThis will generate a CreditCard model and CreditCardController\n";
        echo "\tcontroller in the admin module.\n";            
    }

    /**
     *  Output console help message for unrecognized command
     */
    function generator_help() {
        echo "Usage:\n";
        echo "Generate Controller:\n";
        echo "php script/generate.php controller controller_name [view1 view2 ...]\n";
        echo "for more controller info php script/generate.php controller\n\n";
        echo "Generate Model:\n";
        echo "php script/generate.php model ModelName\n";
        echo "for more model info php script/generate.php model\n\n";
        echo "Generate Mailer:\n";
        echo "php script/generate.php mailer MailerName [view1 view2 ...]\n";
        echo "for more mailer info php script/generate.php mailer\n\n";
        echo "Generate Scaffold:\n";
        echo "php script/generate.php scaffold ModelName [controller_name] [view1 view2 ...]\n";
        echo "for more scaffold info php script/generate.php scaffold\n\n";        
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
