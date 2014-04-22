<?php
namespace Mouf\Doctrine\ORM\Admin\Controllers;

use Mouf\Html\Widgets\MessageService\Service\UserMessageInterface;

use Mouf\MoufUtils;

use Mouf\InstanceProxy;

use Mouf\Validator\InstancesClassValidator;

use Doctrine\ORM\Tools\SchemaTool;

use Mouf\Actions\InstallUtils;
use Mouf\MoufManager;
use Mouf\Mvc\Splash\Controllers\Controller;

/**
 * The controller managing the install process.
 * It will query the database details.
 *
 * @Component
 */
class EntityManagerController extends Controller  {
	
	public $selfedit;
	
	/**
	 * The active MoufManager to be edited/viewed
	 *
	 * @var MoufManager
	 */
	public $moufManager;
	
	/**
	 * The template used by the main page for mouf.
	 *
	 * @Property
	 * @Compulsory
	 * @var TemplateInterface
	 */
	public $template;
	
	/**
	 * The content block the template will be writting into.
	 *
	 * @Property
	 * @Compulsory
	 * @var HtmlBlock
	 */
	public $contentBlock;
	
	protected $sourceDirectory;
	protected $entitiesNamespace;
	protected $proxyNamespace;
	protected $daoNamespace;
	protected $psrMode;
	protected $instanceName;
	
	protected $errors = array();
	
	/**
	 * Displays the first install screen.
	 * 
	 * @Action
	 * @Logged
	 * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only) 
	 */
	public function defaultAction($name = null, $selfedit = "false", $installMode = null) {
		$this->selfedit = $selfedit;
		$this->installMode = $installMode;
		$name = $name ? $name : "entityManager";
		
		$this->instanceName = $name;
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		
		$autoloadNamespaces = MoufUtils::getAutoloadNamespaces2();
		$this->psrMode = $autoloadNamespaces['psr'];
		
		$this->autoloadDetected = true;
		if ($this->moufManager->instanceExists($name)){
			$instance = $this->moufManager->getInstanceDescriptor($name);
			$this->sourceDirectory = $instance->getProperty("sourceDirectory")->getValue();
			$this->entitiesNamespace = $instance->getProperty("entitiesNamespace")->getValue();
			$this->proxyNamespace = $instance->getProperty("proxyNamespace")->getValue();
			$this->daoNamespace = $instance->getProperty("daoNamespace")->getValue();
		}else{
			if ($autoloadNamespaces) {
				$rootNamespace = $autoloadNamespaces[0]['namespace'].'\\';
				$this->sourceDirectory = $autoloadNamespaces[0]['directory'];
				$this->entitiesNamespace = $rootNamespace."Model\\Entities";
				$this->proxyNamespace = $rootNamespace."Model\\Proxies";
				$this->daoNamespace = $rootNamespace."Model\\DAOs";
			} else {
				$this->autoloadDetected = false;
				$this->entitiesPath = "src/path_to_entities";
				$this->proxyDir = "src/path_to_proxies";
				$this->proxyNamespace = "YOUR_APP_NAMESPACE\\PATH\\TO\\PROXIES";
			}
		}
		
		$this->contentBlock->addFile(__DIR__."/../views/install.php", $this);
		$this->template->toHtml();
	}
	
	/**
	 * Displays the "schema generation screen"
	 *
	 * @Action
	 * @Logged
	 * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only)
	 */
	public function test($sourceDirectory, $entitiesNamespace, $proxyNamespace, $daoNamespace, $instanceName, $selfedit, $installMode = null) {
		$this->instanceName = $instanceName;
		$this->selfedit = $selfedit;
		$this->installMode = $installMode;
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		
		$dbalConnection = $this->moufManager->getInstanceDescriptor("dbalConnection");
		$eventManager = $dbalConnection->getProperty("eventManager")->getValue();
		
		if (!$this->moufManager->instanceExists($instanceName)){
			$em = $this->moufManager->createInstance("Mouf\\Doctrine\\ORM\\EntityManager");
			$em->setName($instanceName);
			$config = $this->moufManager->createInstance("Doctrine\\ORM\\Configuration");
			$config->setName("doctrineConfiguration");
		}else{
			$em = $this->moufManager->getInstanceDescriptor($instanceName);
			$config = $em->getProperty("config")->getValue();
		}

		$entitiesPath = $sourceDirectory . str_replace("\\", "/", $entitiesNamespace);
		$proxyPath = $sourceDirectory . str_replace("\\", "/", $proxyNamespace);
		$daoPath = $sourceDirectory . str_replace("\\", "/", $daoNamespace);
		
		$annotationDriver = InstallUtils::getOrCreateInstance('annotationDriver', null, $this->moufManager);
		$annotationDriver->setCode('return new Doctrine\\ORM\\Mapping\\Driver\\AnnotationDriver($container->get(\'annotationReader\'), [ROOT_PATH . "'. $entitiesPath.'"]);');
		
		$config->getProperty("metadataDriverImpl")->setValue($annotationDriver);
		$config->getProperty("proxyDir")->setValue($proxyPath);
		$config->getProperty("proxyNamespace")->setValue($proxyNamespace);
		
		$em->getProperty("conn")->setValue($dbalConnection);
		$em->getProperty("config")->setValue($config);
		$em->getProperty("eventManager")->setValue($eventManager);

		$em->getProperty("sourceDirectory")->setValue($sourceDirectory);
		$em->getProperty("entitiesNamespace")->setValue($entitiesNamespace);
		$em->getProperty("proxyNamespace")->setValue($proxyNamespace);
		$em->getProperty("daoNamespace")->setValue($daoNamespace);
		
		//Update connection to get the same configuration instance 
		$dbalConnection->getProperty("config")->setValue($config);
		
		$proxy = new InstanceProxy($instanceName);
		$this->sql = $proxy->getSchemaUpdateSQL();
		
		$this->moufManager->rewriteMouf();
		
		$this->contentBlock->addFile(__DIR__."/../views/install2.php", $this);
		$this->template->toHtml();
	}
	/**
	 * Displays the "schema generation screen"
	 *
	 * @Action
	 * @Logged
	 * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only)
	 */
	public function install($instanceName, $selfedit, $installMode = null) {
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		
		$proxy = new InstanceProxy($instanceName);
		$fileName = $proxy->updateSchema();
		$daoData = $proxy->generateDAOs();
		$em = $this->moufManager->getInstanceDescriptor($instanceName);
		
		
		foreach ($daoData as $fullClassName => $className) {
			if (!$this->moufManager->instanceExists(lcfirst($className))){
				$daoInstance = $this->moufManager->createInstance($fullClassName);
				$daoInstance->setName(lcfirst($className));
			}else{
				$daoInstance = $this->moufManager->getInstanceDescriptor(lcfirst($className));
			}
			$daoInstance->getProperty("entityManager")->setValue($em);
		}
		
		$this->moufManager->rewriteMouf();
		
		if ($installMode){
			InstallUtils::continueInstall($selfedit == "true");
		}else{
			set_user_message("Schema and DAOs have been successfully updated.<br>
			<b>A backup dump has been generated in $fileName</b>", UserMessageInterface::SUCCESS);
			header("Location:".ROOT_URL."ajaxinstance/?name=$instanceName&selfedit=".$selfedit);
		}
		
	}
	
}