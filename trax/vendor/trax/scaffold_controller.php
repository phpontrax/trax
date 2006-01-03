<?
# $Id $
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

class ScaffoldController extends ActionController {

    function __construct($model_name) {
        $model_name = strtolower($model_name);
        $this->model_name = Inflector::camelize($model_name);
        $this->model_object_name = Inflector::singularize($model_name);
        $this->model_class = Inflector::classify($model_name);
        $this->model_name_plural = Inflector::humanize(Inflector::pluralize($model_name));
        $this->model_name_human = Inflector::humanize($model_name);
        if(!class_exists($this->model_class, true)) {
            $this->raise("Trying to use scaffolding on a non-existing Model ".$model_name, "Unknown Model", "404");
        }
    }

	function index() {
	    $model_class = $this->model_class;
		$model = new $model_class();
		$this->content_columns = $model->content_columns;
		$this->models = $model->find_all();
	}

	function show() {
	    $model_class = $this->model_class;
		$model = new $model_class();
		$this->{$this->model_object_name} = $model->find($_REQUEST['id']);
	}

	function add() {
	    $model_class = $this->model_class;
	    $this->{$this->model_object_name} = new $model_class($_REQUEST[$this->model_object_name]);
		if($_POST) {
    		if($this->{$this->model_object_name}->save($_POST[$this->model_object_name])) {
      			Session::flash('notice', $this->model_name_human." was successfully created.");
                $this->redirect_to = url_for(array(":action" => "index"));
    		} else {
      			Session::flash('error', "Error adding ".$this->model_name_human." to the database.");
    		}
		}
	}
	
	function edit() {
		$model_class = $this->model_class;
		$model = new $model_class();
		$this->{$this->model_object_name} = $model->find($_REQUEST['id']);	
		if($_POST) {
    		if($this->{$this->model_object_name}->save($_POST[$this->model_object_name])) {
      			Session::flash('notice', $this->model_name_human." was successfully updated.");
                $this->redirect_to = url_for(array(":action" => "show", ":id" => $this->{$this->model_object_name}));
    		} else {
      			Session::flash('error', "Error saving ".$this->model_name_human." to the database.");
    		}
		}
	}
	
	function delete() {
		if($_REQUEST['id'] > 0) {
    		$model_class = $this->model_class;
    		$model = new $model_class();
    		$model = $model->find($_REQUEST['id']);
    		if($model->delete()) {
          		Session::flash('notice', $this->model_name_human." was successfully deleted.");
          	} else {
          		Session::flash('error', "Error deleting ".$this->model_name_human." from the database.");
        	}
		}
        $this->redirect_to = url_for(array(":action" => "index"));
	}

}

?>