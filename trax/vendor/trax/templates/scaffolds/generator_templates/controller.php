< ?php

class <?php echo $controller_class_name ?>Controller extends ApplicationController {

<?php foreach($non_scaffolded_actions as $action): ?>
    function <?php echo $action ?>() {
    }
   
<?php endforeach; ?>
    function index() {
    	$<?php echo $singluar_model_name ?> = new <?php echo $model_class_name ?>();
    	$this-><?php echo $plural_model_name ?> = $<?php echo $singluar_model_name ?>->find_all();
    	$this->content_columns = $<?php echo $singluar_model_name ?>->content_columns;
    }
    
    function show() {
    	$<?php echo $singluar_model_name ?> = new <?php echo $model_class_name ?>();
    	$this-><?php echo $singluar_model_name ?> = $<?php echo $singluar_model_name ?>->find($_REQUEST['id']);
    }
    
    function add() {
        $this-><?php echo $singluar_model_name ?> = new <?php echo $model_class_name ?>($_REQUEST['<?php echo $singluar_model_name ?>']);
        if($_POST) {
            if($this-><?php echo $singluar_model_name ?>->save($_POST['<?php echo $singluar_model_name ?>'])) {
            	Session::flash('notice', "<?php echo $human_model_name ?> was successfully created.");
                $this->redirect_to = url_for(array(":action" => "index"));
            } else {
            	Session::flash('error', "Error adding <?php echo $singluar_model_name ?> to the database.");
            }
        }
    }
    
    function edit() {
        $<?php echo $singluar_model_name ?> = new <?php echo $model_class_name ?>();
        $this-><?php echo $singluar_model_name ?> = $<?php echo $singluar_model_name ?>->find($_REQUEST['id']);	
        if($_POST) {
            if($this-><?php echo $singluar_model_name ?>->save($_POST['<?php echo $singluar_model_name ?>'])) {
            	Session::flash('notice', "<?php echo $human_model_name ?> was successfully updated.");
                $this->redirect_to = url_for(array(":action" => "show", ":id" => $this-><?php echo $singluar_model_name ?>));
            } else {
            	Session::flash('error', "Error saving <?php echo $singluar_model_name ?> to the database.");
            }
        }
    }
    
    function delete() {
        if($_REQUEST['id'] > 0) {
            $<?php echo $singluar_model_name ?> = new <?php echo $model_class_name ?>();
            $<?php echo $singluar_model_name ?> = $<?php echo $singluar_model_name ?>->find($_REQUEST['id']);
            if($<?php echo $singluar_model_name ?>->delete()) {
            	Session::flash('notice', "<?php echo $human_model_name ?> was successfully deleted.");
            } else {
            	Session::flash('error', "Error deleting <?php echo $singluar_model_name ?> from the database.");
            }
        }
        $this->redirect_to = url_for(array(":action" => "index"));
    }
}

? >