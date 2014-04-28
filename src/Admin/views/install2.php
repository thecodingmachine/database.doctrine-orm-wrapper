<?php 
use Mouf\Doctrine\ORM\Admin\Controllers\EntityManagerController;
/* @var $this EntityManagerController */
?>

<h1>Launch Schema generation</h1>
<form action="install" class="form-horizontal">
	<input type="hidden" id="selfedit" name="selfedit" value="<?php echo plainstring_to_htmlprotected($this->selfedit) ?>" />
	<input type="hidden" id="instanceName" name="instanceName" value="<?php echo plainstring_to_htmlprotected($this->instanceName); ?>" />
	<input type="hidden" id="installMode" name="installMode" value="<?php echo plainstring_to_htmlprotected($this->installMode); ?>" />
	
	<div class="control-group">
		<p>
		Below is the list of SQL commands that will be executed, do you confirm ?
		</p>
		<?php 
		if (empty($this->sql)){
		?>
		<div class="info">
			There is no requests to be executed
		</div>
		<?php
		}
		?>
		<ul>
		<?php 
		foreach ($this->sql as $request){
		?>
			<li><?php echo $request.';'; ?></li>
		<?php
		}
		?>
		</ul>
	</div>
	
	<div class="control-group">
		<button name="action" value="install" type="submit" class="btn btn-danger">Generate</button>
	</div>
</form>