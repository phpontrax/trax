<?
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

    private $view_path, $controller_path, $helper_path, $model_path, $mkdir_cmd;
    private $controller_template_file, $helper_template_file, $view_template_file, $model_template_file;
    public $view_file_extention = "phtml";

    function __construct() {
        $this->view_path = TRAX_ROOT . $GLOBALS['TRAX_INCLUDES']['views'];
        $this->controller_path = TRAX_ROOT . $GLOBALS['TRAX_INCLUDES']['controllers'];
        $this->helper_path = TRAX_ROOT . $GLOBALS['TRAX_INCLUDES']['helpers'];
        $this->model_path = TRAX_ROOT . $GLOBALS['TRAX_INCLUDES']['models'];
        $this->controller_template_file = TRAX_ROOT . "lib/templates/controller.php";
        $this->helper_template_file = TRAX_ROOT . "lib/templates/helper.php";
        $this->view_template_file = TRAX_ROOT . "lib/templates/view.".$this->view_file_extention;
        $this->model_template_file = TRAX_ROOT . "lib/templates/model.php";

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
                        $this->generate_controller($command_name,$views);
                    }
                    break;
                case "model":
                    if(empty($command_name)) {
                        $this->model_help();
                    } else {
                        $this->generate_model($command_name);
                    }
                    break;
            }
        }
        exit;
    }

    function generate_controller($name, $views="") {

        // Set the View and Controller extra path info
        if(stristr($name, "/")) {
            $extraPath = substr($name,0,strrpos($name, "/"));
            $name = Inflector::underscore(substr($name,strrpos($name, "/")+1));
            $this->view_path .= "/$extraPath/$name";
            $this->controller_path .= "/$extraPath";
            $this->helper_path .= "/$extraPath";
        } else {
            $name = Inflector::underscore($name);
            $this->view_path .= "/$name";
        }

        $this->controller_class = Inflector::camelize($name);

        // Create the extra folders for View / Controller
        if(!file_exists($this->view_path)) {
            $this->exec("$this->mkdir_cmd $this->view_path");
        }

        if(!file_exists($this->controller_path)) {
            $this->exec("$this->mkdir_cmd $this->controller_path");
        }

        if(!file_exists($this->helper_path)) {
            $this->exec("$this->mkdir_cmd $this->helper_path");
        }

        // Create the actual controller/helper files
        $this->create_controller($name,$views);
        $this->create_helper($name);

        if($extraPath) {
            $name = $extraPath."/".$name;
        }

        // Create view files if any
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
                }
            } else {
                echo "error model template file doesn't exist: $this->model_template_file\n";
            }
        } else {
            echo "exists $model_file\n";
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

    function create_view($view,$controller) {
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
        exec(str_replace("/","\\",$cmd));                    
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
        echo "\t\tViews:      app/views/credit_card/debit.phtml [...]\n\n";
        echo "Folders Example:\n";
        echo "\t./script/generate.php controller 'admin/credit_card' suspend late_fee\n\n";
        echo "\tCredit card admin controller with URLs /admin/credit_card/suspend.\n";
        echo "\t\tController: app/controllers/admin/credit_card_controller.php\n";
        echo "\t\tViews:      app/views/admin/credit_card/suspend.phtml [...]\n\n";
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