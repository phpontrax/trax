<?php
# $Id$
#
# Copyright (c) 2005 John Peterson
#
# Permission is hereby granted, free of charge, to any person obtaining
# a copy of this software and associated documentation files (the
# "Software"), to deal in the Software without restriction, including
# without limitation the rights to use, copy, modify, merge, publish,
# distribute, sublicense, and/or sell copies of the Software, and to
# permit persons to whom the Software is furnished to do so, subject to
# the following conditions:
#
# The above copyright notice and this permission notice shall be
# included in all copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
# EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
# MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
# NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
# LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
# OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
# WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.


class TraxGenerator {

    private
        $view_path,
        $controller_path,
        $helper_path,
        $model_path,
        $mkdir_cmd,
        $controller_template_file,
        $helper_template_file,
        $view_template_file,
        $model_template_file,
        $scaffold_template_path,
        $layout_path,
        $layout_filename;
    public
        $view_file_extention = TRAX_VIEWS_EXTENTION;

    function __construct() {
        $this->view_path = TRAX_ROOT . $GLOBALS['TRAX_INCLUDES']['views'];
        $this->controller_path = TRAX_ROOT . $GLOBALS['TRAX_INCLUDES']['controllers'];
        $this->helper_path = TRAX_ROOT . $GLOBALS['TRAX_INCLUDES']['helpers'];
        $this->model_path = TRAX_ROOT . $GLOBALS['TRAX_INCLUDES']['models'];
        $this->layouts_path = TRAX_ROOT . $GLOBALS['TRAX_INCLUDES']['layouts'];
        $this->controller_template_file = TRAX_LIB_ROOT . "/templates/controller.php";
        $this->helper_template_file = TRAX_LIB_ROOT . "/templates/helper.php";
        $this->view_template_file = TRAX_LIB_ROOT . "/templates/view.".$this->view_file_extention;
        $this->model_template_file = TRAX_LIB_ROOT . "/templates/model.php";
        $this->scaffold_template_path = TRAX_LIB_ROOT . "/templates/scaffolds/generator_templates";

        if (substr(PHP_OS, 0, 3) == 'WIN') {
            $this->mkdir_cmd = "mkdir";
        } else {
            $this->mkdir_cmd = "mkdir -p";
        }

    }

    function run() {
        $command = strtolower($_SERVER["argv"][1]);
        $command_name = $_SERVER["argv"][2];

        if(empty($command)) {
            $this->generator_help();
        } else {
            switch($command) {
                case "controller":
                    if(empty($command_name)) {
                        $this->controller_help();
                    } else {
                        if($_SERVER["argv"][3] != "") {
                            for($i=3;$i < count($_SERVER["argv"]);$i++) {
                                $views[] = strtolower($_SERVER["argv"][$i]);
                            }
                        }
                        $this->generate_controller($command_name, $views);
                    }
                    break;
                case "model":
                    if(empty($command_name)) {
                        $this->model_help();
                    } else {
                        $this->generate_model($command_name);
                    }
                    break;
                case "scaffold":
                    if(empty($command_name)) {
                        $this->scaffold_help();
                    } else {
                        $controller_name = $_SERVER["argv"][3];
                        if($_SERVER["argv"][4] != "") {
                            for($i=4;$i < count($_SERVER["argv"]);$i++) {
                                $views[] = strtolower($_SERVER["argv"][$i]);
                            }
                        }                        
                        $this->generate_scaffold($command_name, $controller_name, $views);
                    }
                    break;                    
            }
        }
        exit;
    }

    function generate_controller($name, $views="", $scaffolding = false) {

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
        if(!file_exists($this->view_path)) {
            $this->exec("$this->mkdir_cmd $this->view_path");
        }

        if(!file_exists($this->controller_path)) {
            $this->exec("$this->mkdir_cmd $this->controller_path");
        }

        if(!file_exists($this->helper_path)) {
            $this->exec("$this->mkdir_cmd $this->helper_path");
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
                    echo "created $model_file\n";
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
    
    function generate_scaffold($model_name, $controller_name, $views="") {
        if(!$model_exists = $this->generate_model($model_name)) {
            echo "Error - Can't create Model: $model_name.\n";    
            exit;
        }

        $GLOBALS['current_controller_object'] =& $this;
        $model_class_name = Inflector::classify($model_name);
        $singluar_model_name = Inflector::singularize($model_name);
        $plural_model_name = Inflector::pluralize($model_name);  
        $human_model_name = Inflector::humanize($model_name);      
        $this->{$singluar_model_name} = new $model_class_name();            
        if(!$controller_name) {
            $controller_name = Inflector::pluralize($model_name);   
        } else {
            $controller_name = Inflector::underscore($controller_name);    
        }
        $controller_file = "$this->controller_path/".$controller_name."_controller.php";
        $non_scaffolded_actions = array();
        $illegal_views = array("index","add","edit","show");      
        if(is_array($views)) {
            foreach($views as $view) {
                if(!in_array($view, $illegal_views)) {
                    $non_scaffolded_actions[] = $view;  
                }           
            }
        }         
        $this->generate_controller($controller_name, $non_scaffolded_actions, true); 
        if(stristr($controller_name, "/")) {
            $controller_class_name = Inflector::classify(substr($controller_name,strrpos($controller_name, "/")+1));
            $human_controller_name = Inflector::humanize(substr($controller_name,strrpos($controller_name, "/")+1));
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
                echo "created $controller_file\n";
            }        
        } else {
            echo "exists $controller_file\n";        
        } 
                
        # Generate the index.phtml view
        $view_file = "$this->view_path/index.".$this->view_file_extention;
        ob_start();    
        include("$this->scaffold_template_path/view_index.phtml");
        $index_contents = $this->fix_php_brackets(ob_get_contents());
        ob_end_clean();
        if(!file_exists($view_file)) {
            if(!file_put_contents($view_file, $index_contents)) {
                echo "error creating view file: $view_file\n";
            } else {
                echo "created $view_file\n";
            }
        } else {
            echo "exists $view_file\n";        
        } 
               
        # Generate the add.phtml view
        $view_file = "$this->view_path/add.".$this->view_file_extention;
        ob_start();    
        include("$this->scaffold_template_path/view_add.phtml");
        $add_contents = $this->fix_php_brackets(ob_get_contents());
        ob_end_clean();       
        if(!file_exists($view_file)) {
            if(!file_put_contents($view_file, $add_contents)) {
                echo "error creating view file: $view_file\n";
            } else {
                echo "created $view_file\n";
            }
        } else {
            echo "exists $view_file\n";        
        } 
        
        # Generate the edit.phtml view
        $view_file = "$this->view_path/edit.".$this->view_file_extention;
        ob_start();    
        include("$this->scaffold_template_path/view_edit.phtml");
        $edit_contents = $this->fix_php_brackets(ob_get_contents());
        ob_end_clean(); 
        if(!file_exists($view_file)) {
            if(!file_put_contents($view_file, $edit_contents)) {
                echo "error creating view file: $view_file\n";
            } else {
                echo "created $view_file\n";
            }
        } else {
            echo "exists $view_file\n";        
        } 
        
        # Generate the show.phtml view
        $view_file = "$this->view_path/show.".$this->view_file_extention;
        ob_start();    
        include("$this->scaffold_template_path/view_show.phtml");
        $show_contents = $this->fix_php_brackets(ob_get_contents());
        ob_end_clean();
        if(!file_exists($view_file)) {
            if(!file_put_contents($view_file, $show_contents)) {
                echo "error creating view file: $view_file\n";
            } else {
                echo "created $view_file\n";
            }
        } else {
            echo "exists $view_file\n";        
        } 
               
        # Generate the partial containing the form elments from the database
        $view_file = "$this->view_path/_form.".$this->view_file_extention;
        ob_start();    
        include("$this->scaffold_template_path/form_scaffolding.phtml");
        $_form_contents = $this->fix_php_brackets(ob_get_contents());
        ob_end_clean();  
        if(!file_exists($view_file)) {
            if(!file_put_contents($view_file, $_form_contents)) {
                echo "error creating view file: $view_file\n";
            } else {
                echo "created $view_file\n";
            }
        } else {
            echo "exists $view_file\n";        
        } 
        
        # Generate the layout for the scaffolding
        $layout_file = $this->layouts_path."/".$this->layout_filename.".".$this->view_file_extention;
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
                echo "created $layout_file\n";
            }
        } else {
            echo "exists $layout_file\n";        
        }                   
    }    

    function create_controller($controller,$views="") {

        $controller_file = "$this->controller_path/".$controller."_controller.php";

        if(!file_exists($controller_file)) {
            if(file_exists($this->controller_template_file)) {
                $template = file_get_contents($this->controller_template_file);
                $template = str_replace('[class_name]',$this->controller_class,$template);
                if(is_array($views)) {
                    foreach($views as $view) {
                        $classMethods[] = "\tfunction $view() {\n\t}";
                    }
                    $classMethods = implode("\n\n",$classMethods);
                }
                $template = str_replace('[class_methods]',$classMethods,$template);

                if(!file_put_contents($controller_file,$template)) {
                    echo "error creating controller class file: $controller_file\n";
                } else {
                    echo "created $controller_file\n";
                }

            } else {
                echo "error controller template file doesn't exist: $this->controller_template_file\n";
            }
        } else {
            echo "exists $controller_file\n";
        }
    }

    function create_helper($controller) {

        $helper_file = "$this->helper_path/".$controller."_helper.php";

        if(!file_exists($helper_file)) {
            if(file_exists($this->helper_template_file)) {
                $template = file_get_contents($this->helper_template_file);
                $template = str_replace('[class_name]',$this->controller_class,$template);
                if(!file_put_contents($helper_file,$template)) {
                    echo "error creating helper file: $helper_file\n";
                } else {
                    echo "created $helper_file\n";
                }

            } else {
                echo "error helper template file doesn't exist: $this->helper_template_file\n";
            }
        } else {
            echo "exists $helper_file\n";
        }
    }

    function create_view($view, $controller) {
        $view_file = "$this->view_path/".$view.".".$this->view_file_extention;
        if(!file_exists($view_file)) {
            if(file_exists($this->view_template_file)) {
                $template = file_get_contents($this->view_template_file);
                $template = str_replace('[class_name]',$this->controller_class,$template);
                $template = str_replace('[controller]',$controller,$template);
                $template = str_replace('[view]',$view,$template);
                if(!file_put_contents($view_file,$template)) {
                    echo "error creating view file: $view_file\n";
                } else {
                    echo "created $view_file\n";
                }
            } else {
                echo "error controller template file doesn't exist: $this->view_template_file\n";
            }
        } else {
            echo "exists $view_file\n";
        }
    }

    function exec($cmd) {
        if (substr(PHP_OS, 0, 3) == 'WIN') {
            exec(str_replace("/","\\",$cmd));
        } else {
            exec($cmd);
        }
    }
    
    function fix_php_brackets($string) {
        return str_replace("? >", "?>", str_replace("< ?php", "<?php", $string));            
    }

    function controller_help() {
        echo "Usage: ./generate.php controller ControllerName [view1 view2 ...]\n\n";
        echo "Description:\n";
        echo "\tThe controller generator creates functions for a new controller and its views.\n\n";
        echo "\tThe generator takes a controller name and a list of views as arguments.\n";
        echo "\tThe controller name may be given in CamelCase or under_score and should\n";
        echo "\tnot be suffixed with 'Controller'.  To create a controller within a\n";
        echo "\tmodule, specify the controller name as 'folder/controller'.\n";
        echo "\tThe generator creates a controller class in app/controllers with view\n";
        echo "\ttemplates in app/views/controller_name.\n\n";
        echo "Example:\n";
        echo "\t./script/generate.php controller CreditCard open debit credit close\n\n";
        echo "\tCredit card controller with URLs like /credit_card/debit.\n";
        echo "\t\tController: app/controllers/credit_card_controller.php\n";
        echo "\t\tViews:      app/views/credit_card/debit.phtml [...]\n";
        echo "\t\tHelper:     app/helpers/credit_card_helper.php\n\n";
        echo "Module/Folders Example:\n";
        echo "\t./script/generate.php controller 'admin/credit_card' suspend late_fee\n\n";
        echo "\tCredit card admin controller with URLs /admin/credit_card/suspend.\n";
        echo "\t\tController: app/controllers/admin/credit_card_controller.php\n";
        echo "\t\tViews:      app/views/admin/credit_card/suspend.phtml [...]\n";
        echo "\t\tHelper:     app/helpers/credit_card_helper.php\n\n";
    }

    function model_help() {
        echo "Usage: ./generate.php model ModelName\n";
        echo "Description:\n";
        echo "\tThe model generator creates functions for a new model.\n";
        echo "\tThe generator takes a model name as its argument.  The model name may be\n";
        echo "\tgiven in CamelCase or under_score and should not be suffixed with 'Model'.\n";
        echo "\tThe generator creates a model class in app/models.\n";
        echo "Example:\n";
        echo "\t./script/generate.php model Account\n";
        echo "\tThis will create an Account model:\n";
        echo "\t\tModel:      app/models/account.php\n\n";
    }
    
    function scaffold_help() {
        echo "Usage: ./generate scaffold ModelName [ControllerName] [view1 view2 ...]\n\n";
        echo "Description:\n";
        echo "\tThe scaffold generator creates a controller to interact with a model.\n";
        echo "\tIf the model does not exist, it creates the model as well.  The generated\n";
        echo "\tcode is equivalent to the ( public \$scaffold = \"model\"; ) declaration,\n";
        echo "\tmaking it easy to migrate when you wish to customize your controller and views.\n\n";
        echo "\tThe generator takes a model name, an optional controller name, and a\n";
        echo "\tlist of views as arguments.  Scaffolded actions and views are created\n";
        echo "\tautomatically.\n\n";
        echo "\tThe auto scaffolded actions and views are:\n";
        echo "\t\tindex, show, add, edit, delete\n\n";
        echo "\tIf a controller name is not given, the plural form of the model name\n";
        echo "\twill be used.  The model and controller names may be given in CamelCase\n";
        echo "\tor under_score and should not be suffixed with 'Model' or 'Controller'.\n\n";
        echo "Example:\n";
        echo "\t./generate scaffold Account Bank debit credit\n\n";
        echo "\tThis will generate an Account model and BankController with a basic user interface\n";
        echo "\tNow create the accounts table in your database and browse to http://localhost/bank/\n";
        echo "\tvoila, you're on Trax!\n\n";
        echo "Module/Folders Example:\n";
        echo "\t./generate scaffold CreditCard 'admin/credit_card' suspend late_fee\n\n";
        echo "\tThis will generate a CreditCard model and CreditCardController controller\n";
        echo "\tin the admin module.\n";            
    }

    function generator_help() {
        echo "Usage:\n";
        echo "Generate Controller:\n";
        echo "./generate.php controller controller_name [view1 view2 ..]\n";
        echo "for more controller info ./generate.php controller\n";
        echo "Generate Model:\n";
        echo "./generate.php model model_name\n";
        echo "for more model info ./generate.php model\n\n";
    }
}


?>