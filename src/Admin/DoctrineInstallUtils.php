<?php
namespace Mouf\Doctrine\ORM\Admin;

use Mouf\Actions\InstallUtils;
use Mouf\MoufInstanceDescriptor;
use Mouf\MoufManager;

class DoctrineInstallUtils {

    private $moufManager;

    public function __construct(MoufManager $moufManager)
    {
        $this->moufManager = $moufManager;
    }

    /**
     * Registers a set of annotation-based entities.
     *
     * @param string $namespace
     * @param string $directory Directory where the annotations are stored, relative to ROOT_PATH.
     */
    public function registerAnnotationBasedEntities($namespace, $directory) {
        $mappingDriver = InstallUtils::getOrCreateInstance('mappingDriver.'.$namespace, null, $this->moufManager);
        $mappingDriver->setCode('return new Doctrine\\ORM\\Mapping\\Driver\\AnnotationDriver($container->get(\'annotationReader\'), [ROOT_PATH . "'.$directory.'"]);');

        $this->addDriverToChain($namespace, $mappingDriver);
    }

    /**
     * Registers a set of XML-based entities using Symfony's simplified XML driver:
     * http://doctrine-orm.readthedocs.org/en/latest/reference/xml-mapping.html
     *
     * Note: you can use a global.orm.xml file to define all files.
     *
     * @param string $namespace
     * @param string $directory Directory where the annotations are stored, relative to ROOT_PATH.
     */
    public function registerXmlBasedEntities($namespace, $directory) {
        $mappingDriver = InstallUtils::getOrCreateInstance('mappingDriver.'.$namespace, null, $this->moufManager);

        $code = '
        $namespaces = array(
            ROOT_PATH.'.var_export($directory, true).' => '.var_export($namespace, true).'
        );
        $driver = new \Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver($namespaces);
        $driver->setGlobalBasename("global"); // global.orm.xml
        return $driver;
        ';

        $mappingDriver->setCode($code);

        $this->addDriverToChain($namespace, $mappingDriver);
    }

    /**
     * Registers a set of YAML-based entities using Symfony's simplified YAML driver:
     * http://doctrine-orm.readthedocs.org/en/latest/reference/xml-mapping.html
     *
     * Note: you can use a global.orm.xml file to define all files.
     *
     * @param string $namespace
     * @param string $directory Directory where the annotations are stored, relative to ROOT_PATH.
     */
    public function registerYamlBasedEntities($namespace, $directory) {
        $mappingDriver = InstallUtils::getOrCreateInstance('mappingDriver.'.$namespace, null, $this->moufManager);

        $code = '
        $namespaces = array(
            ROOT_PATH.'.var_export($directory, true).' => '.var_export($namespace, true).'
        );
        $driver = new \Doctrine\ORM\Mapping\Driver\SimplifiedYamlDriver($namespaces);
        $driver->setGlobalBasename("global"); // global.orm.xml
        return $driver;
        ';

        $mappingDriver->setCode($code);

        $this->addDriverToChain($namespace, $mappingDriver);
    }

    private function addDriverToChain($namespace, MoufInstanceDescriptor $driverDescriptor) {
        $defaultMappingDriver = $this->moufManager->getInstanceDescriptor('mappingDriverChain');
        $drivers = $defaultMappingDriver->getSetterProperty('setDrivers')->getValue();
        $drivers[$namespace] = $driverDescriptor;
        $defaultMappingDriver->getSetterProperty('setDrivers')->setValue($drivers);
    }
}
