<?php

namespace Mouf\Doctrine\ORM\Admin\Controllers;

use Mouf\Composer\ClassNameMapper;
use Mouf\Console\ConsoleUtils;
use Mouf\Html\Widgets\MessageService\Service\UserMessageInterface;
use Mouf\MoufUtils;
use Mouf\InstanceProxy;
use Mouf\Actions\InstallUtils;
use Mouf\MoufManager;
use Mouf\Mvc\Splash\Controllers\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;
use Mouf\Database\Patcher\DatabasePatchInstaller;

/**
 * The controller managing the install process.
 * It will query the database details.
 */
class EntityManagerController extends Controller
{
    public $selfedit;

    /**
     * The active MoufManager to be edited/viewed.
     *
     * @var MoufManager
     */
    public $moufManager;

    /**
     * The template used by the main page for mouf.
     *
     * @Property
     * @Compulsory
     *
     * @var TemplateInterface
     */
    public $template;

    /**
     * The content block the template will be writting into.
     *
     * @Property
     * @Compulsory
     *
     * @var HtmlBlock
     */
    public $contentBlock;

    protected $entitiesNamespace;
    protected $proxyNamespace;
    protected $daoNamespace;
    protected $psrMode;
    protected $instanceName;
    protected $patchable;
    protected $nbAwaitingPatches;
    protected $generatePatch = true;

    protected $errors = array();

    /**
     * Displays the first install screen.
     *
     * @Action
     * @Logged
     *
     * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only)
     */
    public function defaultAction($name = null, $selfedit = 'false', $installMode = null)
    {
        $this->selfedit = $selfedit;
        $this->installMode = $installMode;
        $name = $name ? $name : 'entityManager';

        $this->instanceName = $name;

        if ($selfedit == 'true') {
            $this->moufManager = MoufManager::getMoufManager();
        } else {
            $this->moufManager = MoufManager::getMoufManagerHiddenInstance();
        }

        $classNameMapper = ClassNameMapper::createFromComposerFile(__DIR__.'/../../../../../../composer.json');
        $managedNamespaces = $classNameMapper->getManagedNamespaces();

        $this->autoloadDetected = true;
        if ($this->moufManager->instanceExists($name)) {
            $instance = $this->moufManager->getInstanceDescriptor($name);
            $this->entitiesNamespace = $instance->getSetterProperty('setEntitiesNamespace')->getValue();
            $this->proxyNamespace = $instance->getSetterProperty('setProxyNamespace')->getValue();
            $this->daoNamespace = $instance->getSetterProperty('setDaoNamespace')->getValue();
        } else {
            if ($managedNamespaces) {
                $rootNamespace = $classNameMapper->getManagedNamespaces()[0];
                $this->entitiesNamespace = $rootNamespace."Model\\Entities";
                $this->proxyNamespace = $rootNamespace."Model\\Proxies";
                $this->daoNamespace = $rootNamespace."Model\\DAOs";
            } else {
                $this->autoloadDetected = false;
                $this->entitiesPath = 'src/path_to_entities';
                $this->proxyDir = 'src/path_to_proxies';
                $this->proxyNamespace = "YOUR_APP_NAMESPACE\\PATH\\TO\\PROXIES";
            }
        }

        $this->contentBlock->addFile(__DIR__.'/../views/install.php', $this);
        $this->template->toHtml();
    }

    /**
     * Displays the "schema generation screen".
     *
     * @Action
     * @Logged
     *
     * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only)
     */
    public function do_generate_daos($entitiesNamespace, $proxyNamespace, $daoNamespace, $instanceName, $selfedit, $installMode = null)
    {
        $this->instanceName = $instanceName;
        $this->selfedit = $selfedit;
        $this->installMode = $installMode;

        if ($selfedit == 'true') {
            $this->moufManager = MoufManager::getMoufManager();
        } else {
            $this->moufManager = MoufManager::getMoufManagerHiddenInstance();
        }

        $dbalConnection = $this->moufManager->getInstanceDescriptor('dbalConnection');
        $eventManager = $dbalConnection->getProperty('eventManager')->getValue();

        if (!$this->moufManager->instanceExists($instanceName)) {
            $em = $this->moufManager->createInstance("Mouf\\Doctrine\\ORM\\MoufResetableEntityManager");
            $em->setName($instanceName);

            $quoteStrategy = $this->moufManager->createInstance("Mouf\\Doctrine\\ORM\\Mapping\\EscapingQuoteStrategy");
            $quoteStrategy->setName('escapingQuoteStrategy');

            $namingStrategy = $this->moufManager->createInstance("Doctrine\\ORM\\Mapping\\UnderscoreNamingStrategy");
            $namingStrategy->setName('underscoreNamingStrategy');

            $doctrineApc = $this->moufManager->getInstanceDescriptor('defaultDoctrineCache');

            $config = $this->moufManager->createInstance("Doctrine\\ORM\\Configuration");
            $config->setName('doctrineConfiguration');

            $configQuoteProperty = $config->getSetterProperty('setQuoteStrategy');
            $configQuoteProperty->setValue($quoteStrategy);

            $configNamingProperty = $config->getSetterProperty('setNamingStrategy');
            $configNamingProperty->setValue($namingStrategy);

            $configQueryCacheProperty = $config->getSetterProperty('setQueryCacheImpl');
            $configQueryCacheProperty->setValue($doctrineApc);

            $configMetadataCacheProperty = $config->getSetterProperty('setMetadataCacheImpl');
            $configMetadataCacheProperty->setValue($doctrineApc);

            $configResultCacheProperty = $config->getSetterProperty('setResultCacheImpl');
            $configResultCacheProperty->setValue($doctrineApc);
        } else {
            $em = $this->moufManager->getInstanceDescriptor($instanceName);
            $config = $em->getProperty('config')->getValue();

            $configQuoteProperty = $config->getSetterProperty('setQuoteStrategy');
            if (!$configQuoteProperty->isValueSet()) {
                $quoteStrategy = $this->moufManager->createInstance("Mouf\\Doctrine\\ORM\\Mapping\\EscapingQuoteStrategy");
                $quoteStrategy->setName('escapingQuoteStrategy');
                $configQuoteProperty->setValue($quoteStrategy);
            }

            $configNamingProperty = $config->getSetterProperty('setNamingStrategy');
            if(!$configNamingProperty) {
                $namingStrategy = $this->moufManager->createInstance("Mouf\\Doctrine\\ORM\\Mapping\\UnderscoreNamingStrategy");
                $namingStrategy->setName('underscoreNamingStrategy');
                $configNamingProperty->setValue($namingStrategy);
            }

            $configMetadataCacheProperty = $config->getSetterProperty('setMetadataCacheImpl');
            $configQueryCacheProperty = $config->getSetterProperty('setQueryCacheImpl');
            $configResultCacheProperty = $config->getSetterProperty('setResultCacheImpl');
            if(!$configMetadataCacheProperty || !$configQueryCacheProperty || !$configResultCacheProperty){
                $doctrineApc = $this->moufManager->getInstanceDescriptor('defaultDoctrineCache');

                if(!$configQueryCacheProperty){
                    $configQueryCacheProperty->setValue($doctrineApc);
                }

                if(!$configMetadataCacheProperty){
                    $configMetadataCacheProperty->setValue($doctrineApc);
                }

                if(!$configResultCacheProperty){
                    $configResultCacheProperty->setValue($doctrineApc);
                }
            }
        }

        $classNameMapper = ClassNameMapper::createFromComposerFile(__DIR__.'/../../../../../../composer.json');

        $entitiesNamespace = rtrim($entitiesNamespace, '\\');
        $proxyNamespace = rtrim($proxyNamespace, '\\');
        $daoNamespace = rtrim($daoNamespace, '\\');

        // Let's locate the path by locating a fake class in the namespace.
        $entitiesPath = substr($classNameMapper->getPossibleFileNames($entitiesNamespace.'\\ZZZ')[0], 0, -7);
        $proxyPath = substr($classNameMapper->getPossibleFileNames($proxyNamespace.'\\ZZZ')[0], 0, -7);
        $daoPath = substr($classNameMapper->getPossibleFileNames($daoNamespace.'\\ZZZ')[0], 0, -7);

        $fileSystem = new Filesystem();
        $oldMask = umask(0);
        // Note: for some reason, the mode of mkdir is not accounted for. We need to call chmod on it
        // Not perfect: only the last dir takes the mode, not the intermediate directories.
        $fileSystem->mkdir(array(ROOT_PATH.'../../../'.$entitiesPath, ROOT_PATH.'../../../'.$proxyPath, ROOT_PATH.'../../../'.$daoPath), 0775);
        try {
            $fileSystem->chmod(array(ROOT_PATH.'../../../'.$entitiesPath, ROOT_PATH.'../../../'.$proxyPath, ROOT_PATH.'../../../'.$daoPath), 0775);
        } catch (IOException $e) {
            // Do nothing because the change mode can send an error if the folder is associated to another user (in the same group)
        }
        umask($oldMask);

        $defaultMappingDriver = InstallUtils::getOrCreateInstance('defaultMappingDriver', null, $this->moufManager);
        $defaultMappingDriver->setCode('return new Doctrine\\ORM\\Mapping\\Driver\\AnnotationDriver($container->get(\'annotationReader\'), [ROOT_PATH . "'.$entitiesPath.'"]);');

        if (!$this->moufManager->has('mappingDriverChain')) {
            $mappingDriverChain = $this->moufManager->createInstance("Mouf\\Doctrine\\ORM\\Mapping\\Driver\\MappingDriverChain");
            $mappingDriverChain->setName('mappingDriverChain');
            $mappingDriverChain->getProperty('defaultDriver')->setValue($defaultMappingDriver);
        } else {
            $mappingDriverChain = $this->moufManager->getInstanceDescriptor('mappingDriverChain');
        }

        $config->getProperty('metadataDriverImpl')->setValue($mappingDriverChain);
        $config->getProperty('proxyDir')->setOrigin('php')->setValue('return ROOT_PATH."'.addslashes($proxyPath).'";');
        $config->getProperty('proxyNamespace')->setValue($proxyNamespace);
        // Proxy classes are generated in development mode only.
        $config->getProperty('autoGenerateProxyClasses')->setOrigin('config')->setValue('DEBUG');
        // Ignore table "patches" because it is managed by our patch system.
        $config->getProperty('filterSchemaAssetsExpression')->setValue('/^(?!patches$).*/');

        // On the dbalConnection, we register a mapping type "enum"=>"string"
        $em->getProperty('entityManagerClassName')->setValue("Mouf\\Doctrine\\ORM\\EntityManager");
        $em->getProperty('conn')->setOrigin('php')->setValue('$dbalConnection = $container->get("dbalConnection");
$dbalConnection->getDatabasePlatform()->registerDoctrineTypeMapping("enum", "string");
return $dbalConnection;');
        $em->getProperty('config')->setValue($config);
        $em->getProperty('eventManager')->setValue($eventManager);

        $em->getSetterProperty('setEntitiesNamespace')->setValue($entitiesNamespace);
        $em->getSetterProperty('setProxyNamespace')->setValue($proxyNamespace);
        $em->getSetterProperty('setDaoNamespace')->setValue($daoNamespace);

        //Update connection to get the same configuration instance
        $dbalConnection->getProperty('config')->setValue($config);

        // Now, let's write the commands
        $consoleUtils = new ConsoleUtils($this->moufManager);

        // Let's configure the console
        if (!$this->moufManager->instanceExists("ormConnectionHelper")){
            $ormConnectionHelper = InstallUtils::getOrCreateInstance('ormConnectionHelper', 'Doctrine\\ORM\\Tools\\Console\\Helper\\EntityManagerHelper', $this->moufManager);
            $ormConnectionHelper->getConstructorArgumentProperty("em")->setValue($em);
            $consoleUtils->registerHelper($ormConnectionHelper, 'em');
        }

        $commands = [
            '\\Doctrine\\ORM\\Tools\\Console\\Command\\ClearCache\\MetadataCommand',
            '\\Doctrine\\ORM\\Tools\\Console\\Command\\ClearCache\\ResultCommand',
            '\\Doctrine\\ORM\\Tools\\Console\\Command\\ClearCache\\QueryCommand',
            '\\Doctrine\\ORM\\Tools\\Console\\Command\\SchemaTool\\CreateCommand',
            '\\Doctrine\\ORM\\Tools\\Console\\Command\\SchemaTool\\UpdateCommand',
            '\\Doctrine\\ORM\\Tools\\Console\\Command\\SchemaTool\\DropCommand',
            '\\Doctrine\\ORM\\Tools\\Console\\Command\\EnsureProductionSettingsCommand',
            '\\Doctrine\\ORM\\Tools\\Console\\Command\\ConvertDoctrine1SchemaCommand',
            '\\Doctrine\\ORM\\Tools\\Console\\Command\\GenerateRepositoriesCommand',
            '\\Doctrine\\ORM\\Tools\\Console\\Command\\GenerateEntitiesCommand',
            '\\Doctrine\\ORM\\Tools\\Console\\Command\\GenerateProxiesCommand',
            '\\Doctrine\\ORM\\Tools\\Console\\Command\\ConvertMappingCommand',
            '\\Doctrine\\ORM\\Tools\\Console\\Command\\RunDqlCommand',
            '\\Doctrine\\ORM\\Tools\\Console\\Command\\ValidateSchemaCommand',
            '\\Doctrine\\ORM\\Tools\\Console\\Command\\InfoCommand',
            '\\Doctrine\\ORM\\Tools\\Console\\Command\\MappingDescribeCommand',
        ];

        foreach ($commands as $command) {
            $consoleUtils->registerCommand($this->moufManager->createInstance($command));
        }

        $this->moufManager->rewriteMouf();

        //Todo: If we generate Dao's we might create all the instances
        $proxy = new InstanceProxy($instanceName);
        $daoData = $proxy->generateDAOs();

        header('Location: generate_schema?name='.urlencode($instanceName).'&selfedit='.urlencode($selfedit).'&installMode='.urlencode($installMode));
    }

    /**
     * Displays the "schema generation screen".
     *
     * @Action
     * @Logged
     *
     * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only)
     */
    public function generate_schema($name, $selfedit = 'false', $installMode = null)
    {
        $this->instanceName = $name;
        $this->selfedit = $selfedit;
        $this->installMode = $installMode;
        $this->patchable = class_exists("Mouf\\Database\\Patcher\\DatabasePatchInstaller");
        if ($this->patchable) {
            // Let's check if there are awaiting patches. If so, let's display a warning.
            $patchService = new InstanceProxy('patchService');
            /* @var $patchService PatchService */
            $this->nbAwaitingPatches = $patchService->getNbAwaitingPatchs();
        }

        if ($selfedit == 'true') {
            $this->moufManager = MoufManager::getMoufManager();
        } else {
            $this->moufManager = MoufManager::getMoufManagerHiddenInstance();
        }

        $constants = $this->moufManager->getConfigManager()->getDefinedConstants();
        $this->debugMode = $constants['DEBUG'];

        $proxy = new InstanceProxy($name);
        $this->sql = $proxy->getSchemaUpdateSQL();
        if (empty($this->sql) && $installMode == 1) {
            InstallUtils::continueInstall($selfedit == 'true');

            return;
        }
        if($this->moufManager->issetVariable("doctrine_".$name."_generatePatch")) {

            $this->generatePatch = $this->moufManager->getVariable("doctrine_" . $name . "_generatePatch");
        }
        $this->contentBlock->addFile(__DIR__.'/../views/install2.php', $this);
        $this->template->toHtml();
    }
    /**
     * Displays the "schema generation screen".
     *
     * @Action
     * @Logged
     *
     * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only)
     */
    public function install($instanceName, $selfedit, $installMode = null, $generateDaos = null, $generatePatch = false)
    {
        if ($selfedit == 'true') {
            $this->moufManager = MoufManager::getMoufManager();
        } else {
            $this->moufManager = MoufManager::getMoufManagerHiddenInstance();
        }

        $proxy = new InstanceProxy($instanceName);

        $this->moufManager->setVariable("doctrine_".$instanceName."_generatePatch", $generatePatch);

        $fileName = $proxy->updateSchema();
        if ($generatePatch) {
            DatabasePatchInstaller::generatePatch($this->moufManager,'Doctrine patch to match DB schema with defined entities.', $instanceName, $selfedit);
        }
        if ($generateDaos) {
            $daoData = $proxy->generateDAOs();

            $em = $this->moufManager->getInstanceDescriptor($instanceName);

            foreach ($daoData as $fullClassName => $className) {
                if (!$this->moufManager->instanceExists(lcfirst($className))) {
                    $daoInstance = $this->moufManager->createInstance($fullClassName);
                    $daoInstance->setName(lcfirst($className));
                } else {
                    $daoInstance = $this->moufManager->getInstanceDescriptor(lcfirst($className));
                }
                $daoInstance->getProperty('entityManager')->setValue($em);
            }
        }

        $this->moufManager->rewriteMouf();

        if ($installMode) {
            InstallUtils::continueInstall($selfedit == 'true');
        } else {
            set_user_message("Schema and DAOs have been successfully updated.<br>
			<b>A backup dump has been generated in $fileName</b>", UserMessageInterface::SUCCESS);
            header('Location:'.ROOT_URL."ajaxinstance/?name=$instanceName&selfedit=".$selfedit);
        }
    }
}
