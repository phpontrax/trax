< ?php

class <?php echo $controller_class_name ?>Controller extends ApplicationController {

<?php foreach($non_scaffolded_actions as $action): ?>
    function <?php echo $action ?>() {
    }
   
<?php endforeach; ?>
    function index() {
    	$<?php echo $singular_model_name ?> = new <?php echo $model_class_name ?>();
    	$this-><?php echo $plural_model_name ?> = $<?php echo $singular_model_name ?>->find_all();
    	$this->content_columns = $<?php echo $singular_model_name ?>->content_columns;
    }
    
    function show() {
    	$<?php echo $singular_model_name ?> = new <?php echo $model_class_name ?>();
    	$this-><?php echo $singular_model_name ?> = $<?php echo $singular_model_name ?>->find($_REQUEST['id']);
    }
    
    function add() {
      $this-><?php echo $singular_model_name ?> = new <?php echo $model_class_name ?>(array_key_exists('<?php echo $singular_model_name ?>',$_REQUEST) ? $_REQUEST['<?php echo $singular_model_name ?>'] : null );
        if($_POST) {
            if($this-><?php echo $singular_model_name ?>->save($_POST['<?php echo $singular_model_name ?>'])) {
            	Session::flash('notice', "<?php echo $human_model_name ?> was successfully created.");
                $this->redirect_to = url_for(array(":action" => "index"));
            } else {
            	Session::flash('error', "Error adding <?php echo $singular_model_name ?> to the database.");
            }
        }
    }
    
    function edit() {
        $<?php echo $singular_model_name ?> = new <?php echo $model_class_name ?>();
        $this-><?php echo $singular_model_name ?> = $<?php echo $singular_model_name ?>->find($_REQUEST['id']);	
        if($_POST) {
            if($this-><?php echo $singular_model_name ?>->save($_POST['<?php echo $singular_model_name ?>'])) {
            	Session::flash('notice', "<?php echo $human_model_name ?> was successfully updated.");
                $this->redirect_to = url_for(array(":action" => "show", ":id" => $this-><?php echo $singular_model_name ?>));
            } else {
            	Session::flash('error', "Error saving <?php echo $singular_model_name ?> to the database.");
            }
        }
    }
    
    function delete() {
        if($_REQUEST['id'] > 0) {
            $<?php echo $singular_model_name ?> = new <?php echo $model_class_name ?>();
            $<?php echo $singular_model_name ?> = $<?php echo $singular_model_name ?>->find($_REQUEST['id']);
            if($<?php echo $singular_model_name ?>->delete()) {
            	Session::flash('notice', "<?php echo $human_model_name ?> was successfully deleted.");
            } else {
            	Session::flash('error', "Error deleting <?php echo $singular_model_name ?> from the database.");
            }
        }
        $this->redirect_to = url_for(array(":action" => "index"));
    }
}

? >