<?php

namespace Mouf\Doctrine\ORM;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\Common\EventManager;
use Doctrine\ORM\ORMException;
use Mouf\Composer\ClassNameMapper;
use Mouf\Validator\MoufValidatorInterface;
use Mouf\Validator\MoufValidatorResult;
use Mouf\MoufManager;

/**
 * This is a very simple wrapper around Doctrine's EntityManager that exposes its contructor as "public".
 * This allows calling the constructor directly using Mouf.
 *
 * @author David Négrier <david@mouf-php.com>
 * @ExtendedAction {"name":"Generate DAOs", "url":"entityManagerInstall/", "default":false}
 * @ExtendedAction {"name":"Update DB schema", "url":"entityManagerInstall/generate_schema", "default":false}
 */
class EntityManager extends \Doctrine\ORM\EntityManager implements MoufValidatorInterface,MoufEntityManagerInterface
{
    private $entitiesNamespace;
    private $proxyNamespace;
    private $daoNamespace;

    /**
     * Creates a new EntityManager that operates on the given database connection
     * and uses the given Configuration and EventManager implementations.
     *
     * @param \Doctrine\DBAL\Connection     $conn
     * @param \Doctrine\ORM\Configuration   $config
     * @param \Doctrine\Common\EventManager $eventManager
     */
    public function __construct(Connection $conn, Configuration $config, EventManager $eventManager)
    {
        // Those security checks are usually performed in EntityManager::create
        if (! $config->getMetadataDriverImpl()) {
            throw ORMException::missingMappingDriverImpl();
        }
        if ($eventManager !== null && $conn->getEventManager() !== $eventManager) {
            throw ORMException::mismatchedEventManager();
        }

        parent::__construct($conn, $config, $eventManager);
    }

    public function updateSchema()
    {
        $metadata = $this->getMetadataFactory()->getAllMetadata();
        if (! empty($metadata)) {
            $tool = new SchemaTool($this);
            $fileName = ROOT_PATH.'dump.sql';
            $sqls = $tool->getCreateSchemaSql($metadata);
            $dump = '';
            foreach ($sqls as $sql) {
                $dump .= $sql.";\n";
            }
            file_put_contents($fileName, $dump);

            $tool->updateSchema($metadata);
        }

        return $fileName;
    }

    public function getSchemaUpdateSQL()
    {
        $doctrineCache = $this->getConfiguration()->getMetadataCacheImpl();
        if ($doctrineCache instanceof CacheProvider) {
            $doctrineCache->deleteAll();
        } else {
            $doctrineCache->delete("*");
        }
        
        $metadata = $this->getMetadataFactory()->getAllMetadata();
        $sql = array();
        if (! empty($metadata)) {
            $tool = new SchemaTool($this);
            $sql = $tool->getUpdateSchemaSql($metadata);
        }
        return $sql;
    }

    public function generateDAOs()
    {
        //Get Bean / Table list
        $metadata = $this->getMetadataFactory()->getAllMetadata();

        $daos = array();
        foreach ($metadata as $data) {
            // we should check that we generate DAOs only for the root package (not the other entities of other packages)
            $refClass = new \ReflectionClass($data->name);
            $vendorDir = realpath(__DIR__.'/../../../');
            $classFile = $refClass->getFileName();
            if (strpos($classFile, $vendorDir) === 0) {
                continue;
            }

            list($fullClassName, $className) = $this->generateDAO($data);
            $daos[$fullClassName] = $className;
        }

        return  $daos;
    }

    private function generateDAO($data)
    {
        //Get Path where to generate dao files
        $classNameMapper = ClassNameMapper::createFromComposerFile(__DIR__.'/../../../../composer.json');

        /* @var $data ClassMetaData */
        $entityClass = $data->name;

        $entityName = basename(str_replace("\\", '/', $data->name));
        $tableName = $data->table['name'];
        $daoClassName =  $entityName.'Dao';
        $daoBaseClassName =  $entityName.'BaseDao';

        $daoPath = ROOT_PATH.'/'.$classNameMapper->getPossibleFileNames(rtrim($this->daoNamespace, '\\').'\\'.$daoClassName)[0];
        $daoBasePath = ROOT_PATH.'/'.$classNameMapper->getPossibleFileNames(rtrim($this->daoNamespace, '\\').'\\'.$daoBaseClassName)[0];

        $daoDir = dirname($daoPath);
        if (!is_dir($daoDir)) {
            $oldUmask = umask();
            umask(0);
            $dirCreate = mkdir($daoDir, 0775, true);
            umask($oldUmask);
        }

        //generate magic _call functions : findOne & find By field
        $magicCallsStr = '';
        $magicCallMethodAnnotation = '';
        foreach ($data->fieldNames as $fieldName) {
            if (array_search($fieldName, $data->identifier) === false) {
                $field = \Doctrine\Common\Util\Inflector::classify(str_replace('.', ' ', $fieldName));
                $magicCallMethodAnnotation .="
* @method $entityName findBy$field(\$fieldValue, \$orderBy = null, \$limit = null, \$offset = null)
* @method $entityName findOneBy$field(\$fieldValue, \$orderBy = null)
                ";

                $magicCallsStr .= "
	/**
	 * Finds only one entity by $field.
     * Throw an exception if more than one entity was found.
	 * @param mixed \$fieldValue the value of the filtered field
	 * @return $entityName
	 */
	public function findUniqueBy$field(\$fieldValue) {
		return \$this->findUniqueBy(array(".var_export($fieldName, true)." => \$fieldValue));
	}";
            }
        }

        $str = "<?php
/*
* This file has been automatically generated by Mouf/ORM.
* DO NOT edit this file, as it might be overwritten.
* If you need to perform changes, edit the $daoClassName class instead!
*/
namespace $this->daoNamespace;

use Mouf\\Database\\DAOInterface;
use Doctrine\\ORM\\EntityManagerInterface;
use Doctrine\\ORM\\EntityRepository;
use Doctrine\\ORM\\NonUniqueResultException;
use $entityClass;

/**
* The $daoBaseClassName class will maintain the persistance of $entityName class into the $tableName table.
$magicCallMethodAnnotation
*/
class $daoBaseClassName extends EntityRepository implements DAOInterface {

	/**
	 * @param EntityManagerInterface \$entityManager
	 */
	public function __construct(\$entityManager){
		parent::__construct(\$entityManager, \$entityManager->getClassMetadata('$entityClass'));
	}


	/**
	 * Get a new bean record
	 * * @return ".$entityName." the new bean object
	 */
	public function create(){
		return new $entityName();
	}

	/**
	 * Get a bean by it's Id
	 * @param mixed \$id
	 * @return ".$entityName." the bean object
	 */
	public function getById(\$id){
		return \$this->find(\$id);
	}

	/**
	 *
	 * Peforms saving on a bean object
	 * @param mixed bean object
	 */
	public function save(\$entity){
		\$this->getEntityManager()->persist(\$entity);
	}

	/**
	 *
	 * Peforms remove on a bean object
	 * @param $entityName \$entity the bean object
	 */
	public function remove($entityName \$entity){
		\$this->getEntityManager()->remove(\$entity);
	}

	/**
	 * Returns the lis of beans
	 * @return array[".$entityName."] array of bean objects
	 */
	public function getList(){
		return \$this->findAll();
	}

	/**
     * Finds only one entity. The criteria must contain all the elements needed to find a unique entity.
     * Throw an exception if more than one entity was found.
     *
     * @param array \$criteria
     *
     * @return ".$entityName." the bean object
     */
    public function findUniqueBy(array \$criteria)
    {
        \$result = \$this->findBy(\$criteria);

        if(count(\$result) == 1){
            return \$result[0];
        }elseif(count(\$result) > 1){
            throw new NonUniqueResultException('More than one $entityName was found');
        }else{
           return null;
        }
    }

	$magicCallsStr
}";
        file_put_contents($daoBasePath, $str);
        @chmod($daoBasePath, 0664);

        $str = "<?php
namespace $this->daoNamespace;

use Mouf\\Database\\DAOInterface;
use Doctrine\\ORM\\EntityManagerInterface;
use Doctrine\\ORM\\EntityRepository;

/**
* The $daoClassName class will maintain the persistance of $entityName class into the $tableName table.
*/
class $daoClassName extends $daoBaseClassName {

	/*** PUT YOUR SPECIFIC QUERIES HERE !! ***/

}";
        if (!file_exists($daoPath)) {
            file_put_contents($daoPath, $str);
            chmod($daoPath, 0664);
        }

        return array($this->daoNamespace."\\".$daoClassName , $daoClassName);
    }

    public function setEntitiesNamespace($entitiesNamespace)
    {
        $this->entitiesNamespace = $entitiesNamespace;
    }
    public function setProxyNamespace($proxyNamespace)
    {
        $this->proxyNamespace = $proxyNamespace;
    }
    public function setDaoNamespace($daoNamespace)
    {
        $this->daoNamespace = $daoNamespace;
    }

    /**
     * (non-PHPdoc).
     *
     * @see \Mouf\Validator\MoufValidatorInterface::validateInstance()
     */
    public function validateInstance()
    {
        $instanceName = MoufManager::getMoufManager()->findInstanceName($this);

        $sql = $this->getSchemaUpdateSQL();
        // Let's validate that the schema and the entities do match
        if (! empty($sql)) {
            return new MoufValidatorResult(MoufValidatorResult::ERROR, "<b>Doctrine ORM:</b> Your database schema does not match the Doctrine entities in your code. <a href='".ROOT_URL.'vendor/mouf/mouf/entityManagerInstall/generate_schema?name='.$instanceName."&selfedit=false' class='btn btn-danger'><i class='icon icon-white icon-wrench'></i> Fix database schema to match entities</a>");
        }

        return new MoufValidatorResult(MoufValidatorResult::SUCCESS, '<b>Doctrine ORM:</b> Your database schema matches your entities.');
    }
}
