<?php 
use Mouf\Doctrine\ORM\Admin\Controllers\EntityManagerController;
/* @var $this EntityManagerController */
?>

<h1>Configure your Entity Manager</h1>
<?php if (!$this->autoloadDetected) { ?>
<div class="alert">Warning! Could not detect the autoload section of your composer.json file.
Unless you are developing your own autoload system, you should configure <strong>composer.json</strong> to <a href="http://getcomposer.org/doc/01-basic-usage.md#autoloading" target="_blank">define a source directory and a root namespace using PSR-0</a>.</div>
<?php }else{
?>	
<div class="success">
	PSR-<?php echo $this->psrMode?> mode detected
</div>
<br/>
<?php 
} ?>

<form action="generate_schema" class="form-horizontal">
	<input type="hidden" id="selfedit" name="selfedit" value="<?php echo plainstring_to_htmlprotected($this->selfedit) ?>" />
	<input type="hidden" id="instanceName" name="instanceName" value="<?php echo plainstring_to_htmlprotected($this->instanceName); ?>" />
	<input type="hidden" id="psrMode" name="psrMode" value="<?php echo plainstring_to_htmlprotected($this->psrMode); ?>" />
	<input type="hidden" id="installMode" name="installMode" value="<?php echo plainstring_to_htmlprotected($this->installMode); ?>" />
	
	<div class="control-group">
		<label for="sourceDirectory" class="control-label">Source Directory:</label>
		<div class="controls">
			<input type="text" id="sourceDirectory" name="sourceDirectory" value="<?php echo plainstring_to_htmlprotected($this->sourceDirectory) ?>" />
			<span class="help-block">The path to the classes</span>
		</div>
	</div>
	<div>
		<label for="entitiesNamespace" class="control-label">Entities Namespace:</label>
		<div class="controls">
			<input type="text" id="entitiesNamespace" name="entitiesNamespace" value="<?php echo plainstring_to_htmlprotected($this->entitiesNamespace) ?>" />
			<span class="help-block">The path where the Proxies should be generated</span>
		</div>
	</div>
	<div>
		<label for="proxyDir" class="control-label">Proxy Namespace:</label>
		<div class="controls">
			<input type="text" id="proxyNamespace" name="proxyNamespace" value="<?php echo plainstring_to_htmlprotected($this->proxyNamespace) ?>" />
			<span class="help-block">The namespace for the Proxies</span>
		</div>
	</div>
	<div>
		<label for="proxyDir" class="control-label">DAO Namespace:</label>
		<div class="controls">
			<input type="text" id="daoNamespace" name="daoNamespace" value="<?php echo plainstring_to_htmlprotected($this->daoNamespace) ?>" />
			<span class="help-block">The namespace for the DAOs</span>
		</div>
	</div>
	<div class="control-group">
		<div class="controls">
			<button name="action" value="install" type="submit" class="btn btn-danger">Generate DAOs &gt;</button>
		</div>
	</div>
</form>
