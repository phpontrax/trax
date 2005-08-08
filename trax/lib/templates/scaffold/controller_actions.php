	function index() {
		$[model_name] = new [model_class]();
		$this->[model_name_plural] = $[model_name]->find_all();
	}
	
	function show() {
		$[model_name] = new [model_class]();
		$this->[model_name] = $[model_name]->find($_REQUEST['id']);		
	}

	function add() {
		if($_POST) {
    		$[model_name] = new [model_class]();
    		if($[model_name]->save($_REQUEST['[model_name]'])) {
      			$_SESSION['flash']['notice'] = "[model_name_human] was successfully created.";
      			$this->index();
      			$this->render_action = "index";
    		} else {
      			$_SESSION['flash']['error'] = "Error adding [model_name_human] to the database.";
    		}			
		}
	}
	
	function edit() {
		$[model_name] = new [model_class]();
		$this->[model_name] = $[model_name]->find($_REQUEST['id']);		
	
		if($_POST) {
    		if($this->[model_name]->save($_REQUEST['id'])) {
      			$_SESSION['flash']['notice'] = "[model_name_human] was successfully updated.";
      			$this->show();
      			$this->render_action = "show";
    		} else {
      			$_SESSION['flash']['error'] = "Error saving [model_name_human] to the database.";
    		}			
		}
	}
	
	function delete() {
		$[model_name] = new [model_class]();
		$[model_name] = $[model_name]->find($_REQUEST['id']);	
		if($[model_name]->delete()) {
      		$_SESSION['flash']['notice'] = "[model_name_human] was successfully deleted.";
      	} else {
      		$_SESSION['flash']['error'] = "Error deleting [model_name_human] from the database.";
    	}
		$this->index();
      	$this->render_action = "index";
	}
}

?>