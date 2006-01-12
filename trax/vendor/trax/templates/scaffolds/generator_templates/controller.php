< ?

class <?= $controller_class_name ?>Controller extends ApplicationController {

<? foreach($non_scaffolded_actions as $action): ?>
    function <?= $action ?>() {
    }
   
<? endforeach; ?>
    function index() {
    	$<?=$singluar_model_name?> = new <?=$model_class_name?>();
    	$this-><?=$plural_model_name?> = $<?=$singluar_model_name?>->find_all();
    	$this->content_columns = $<?=$singluar_model_name?>->content_columns;
    }
    
    function show() {
    	$<?=$singluar_model_name?> = new <?=$model_class_name?>();
    	$this-><?=$singluar_model_name?> = $<?=$singluar_model_name?>->find($_REQUEST['id']);
    }
    
    function add() {
        $this-><?=$singluar_model_name?> = new <?=$model_class_name?>($_REQUEST['<?=$singluar_model_name?>']);
        if($_POST) {
            if($this-><?=$singluar_model_name?>->save($_POST['<?=$singluar_model_name?>'])) {
            	Session::flash('notice', "<?=$human_model_name?> was successfully created.");
                $this->redirect_to = url_for(array(":action" => "index"));
            } else {
            	Session::flash('error', "Error adding <?=$singluar_model_name?> to the database.");
            }
        }
    }
    
    function edit() {
        $<?=$singluar_model_name?> = new <?=$model_class_name?>();
        $this-><?=$singluar_model_name?> = $<?=$singluar_model_name?>->find($_REQUEST['id']);	
        if($_POST) {
            if($this-><?=$singluar_model_name?>->save($_POST['<?=$singluar_model_name?>'])) {
            	Session::flash('notice', "<?=$human_model_name?> was successfully updated.");
                $this->redirect_to = url_for(array(":action" => "show", ":id" => $this-><?=$singluar_model_name?>));
            } else {
            	Session::flash('error', "Error saving <?=$singluar_model_name?> to the database.");
            }
        }
    }
    
    function delete() {
        if($_REQUEST['id'] > 0) {
            $<?=$singluar_model_name?> = new <?=$model_class_name?>();
            $<?=$singluar_model_name?> = $<?=$singluar_model_name?>->find($_REQUEST['id']);
            if($<?=$singluar_model_name?>->delete()) {
            	Session::flash('notice', "<?=$human_model_name?> was successfully deleted.");
            } else {
            	Session::flash('error', "Error deleting <?=$singluar_model_name?> from the database.");
            }
        }
        $this->redirect_to = url_for(array(":action" => "index"));
    }
}

? >