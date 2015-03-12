<?php
namespace Mouf\Doctrine\ORM;

use Doctrine\ORM\Mapping\ClassMetadata;

use Doctrine\ORM\Tools\SchemaTool;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\Common\EventManager;
use Doctrine\ORM\ORMException;
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
class EntityManager extends \Doctrine\ORM\EntityManager implements MoufValidatorInterface
{

	private $sourceDirectory;
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
		if ( ! $config->getMetadataDriverImpl()) {
			throw ORMException::missingMappingDriverImpl();
		}
		if ($eventManager !== null && $conn->getEventManager() !== $eventManager) {
			throw ORMException::mismatchedEventManager();
		}
		 
		parent::__construct($conn, $config, $eventManager);
	}

	public function updateSchema(){
		$metadata = $this->getMetadataFactory()->getAllMetadata();
		if ( ! empty($metadata)) {
			$tool = new SchemaTool($this);
			$fileName = ROOT_PATH . "dump.sql";
			$sqls = $tool->getCreateSchemaSql($metadata);
			$dump = "";
			foreach ($sqls as $sql){
				$dump .= $sql . ";\n";
			}
			file_put_contents($fileName, $dump);
			
			$tool->updateSchema($metadata);
		}
		return $fileName;
	}
	
	public function getSchemaUpdateSQL(){
		$metadata = $this->getMetadataFactory()->getAllMetadata();
		$sql = array();
		if ( ! empty($metadata)) {
			$tool = new SchemaTool($this);
			$sql = $tool->getUpdateSchemaSql($metadata);
		}
		return $sql;
	}

	public function generateDAOs(){
		//Get Bean / Table list
		$metadata = $this->getMetadataFactory()->getAllMetadata();
		 
		//Get Path where to generate dao files
		$daoPath = ROOT_PATH . $this->sourceDirectory . str_replace("\\", "/", $this->daoNamespace);
		if (!is_dir($daoPath)){
			$oldUmask = umask();
			umask(0);
			$dirCreate = mkdir($daoPath, 0775, true);
			umask($oldUmask);
		}
		
		$daos = array();
		foreach ($metadata as $data){
			list($fullClassName, $className) = $this->generateDAO($data, $daoPath);
			$daos[$fullClassName] = $className;
		}
		
		return  $daos;
	}

	private function generateDAO($data, $daoPath){
		/* @var $data ClassMetaData */
		$entityClass = $data->name;
		$entityName = str_replace($this->entitiesNamespace . "\\", "", $data->name);
		$tableName = $data->table['name'];
		$daoClassName =  $entityName. "Dao";
		$daoBaseClassName =  $entityName. "BaseDao";
		
		//generate magic _call functions : findOne & find By field
		$magicCallsStr = "";
		foreach($data->fieldNames as $fieldName){
			if (array_search($fieldName, $data->identifier) === false){
				$field = \Doctrine\Common\Util\Inflector::classify($fieldName);
				$magicCallsStr .= "
	/**
	 * Wrapper around the magic __call implementations of the findBy[Field] function to get autocompletion
	 * @param mixed \$fieldValue the value of the filtered field
	 * @param array|null \$orderBy the value of the filtered field
	 * @param int|null \$limit the max elements to be returned
	 * @param int|null \$offset the index of the first element to retrieve
	 * @return ".$entityName."[]
	 */
	public function findBy$field(\$fieldValue, \$orderBy = null, \$limit = null, \$offset = null) {
		return \$this->__call('findBy$field', array(\$fieldValue, \$orderBy, \$limit, \$offset));
	}

	/**
	 * Wrapper around the magic __call implementations of the findByOne[Field] function to get autocompletion
	 * @param mixed \$fieldValue the value of the filtered field
	 * @param array|null \$orderBy the value of the filtered field
	 * @return $entityName
	 */
	public function findOneBy$field(\$fieldValue, \$orderBy = null) {
		return \$this->__call('findOneBy$field', array(\$fieldValue, \$orderBy));
	}

	/**
	 * Finds only one entity by $field.
     * Throw an exception if more than one entity was found.
	 * @param mixed \$fieldValue the value of the filtered field
	 * @return $entityName
	 */
	public function findUniqueBy$field(\$fieldValue) {
		return \$this->findUniqueBy(array('$fieldName' => \$fieldValue));
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
use Mouf\\Doctrine\\ORM\\EntityManager;
use Doctrine\\ORM\\EntityRepository;
use Doctrine\\ORM\\NonUniqueResultException;
use $entityClass;

/**
* The $daoBaseClassName class will maintain the persistance of $entityName class into the $tableName table.
*
*/
class $daoBaseClassName extends EntityRepository implements DAOInterface {

	/**
	 * @param EntityManager \$entityManager
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
		$fileName = $daoBaseClassName . ".php";
		file_put_contents($daoPath . "/" . $fileName, $str);
		@chmod($daoPath . "/" . $fileName, 0664);
		
		$str = "<?php
namespace $this->daoNamespace;

use Mouf\\Database\\DAOInterface;
use Mouf\\Doctrine\\ORM\\EntityManager;
use Doctrine\\ORM\\EntityRepository;

/**
* The $daoClassName class will maintain the persistance of $entityName class into the $tableName table.
*/
class $daoClassName extends $daoBaseClassName {
	
	/*** PUT YOUR SPECIFIC QUERIES HERE !! ***/

}";
		$fileName = $daoClassName . ".php";
		if (!file_exists($daoPath . "/" . $fileName)){
			file_put_contents($daoPath . "/" . $fileName, $str);
			chmod($daoPath . "/" . $fileName, 0664);
		}
		
		return array($this->daoNamespace . "\\" . $daoClassName , $daoClassName);
	}

	public function setSourceDirectory($sourceDirectory){
		$this->sourceDirectory = $sourceDirectory;
	}
	public function setEntitiesNamespace($entitiesNamespace){
		$this->entitiesNamespace = $entitiesNamespace;
	}
	public function setProxyNamespace($proxyNamespace){
		$this->proxyNamespace = $proxyNamespace;
	}
	public function setDaoNamespace($daoNamespace){
		$this->daoNamespace = $daoNamespace;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \Mouf\Validator\MoufValidatorInterface::validateInstance()
	 */
	public function validateInstance() {
		$instanceName = MoufManager::getMoufManager()->findInstanceName($this);
		
		$sql = $this->getSchemaUpdateSQL();
		// Let's validate that the schema and the entities do match
		if ( ! empty($sql)) {
			return new MoufValidatorResult(MoufValidatorResult::ERROR, "<b>Doctrine ORM:</b> Your database schema does not match the Doctrine entities in your code. <a href='".ROOT_URL."vendor/mouf/mouf/entityManagerInstall/generate_schema?name=".$instanceName."&selfedit=false' class='btn btn-danger'><i class='icon icon-white icon-wrench'></i> Fix database schema to match entities</a>");	
		}
		
		return new MoufValidatorResult(MoufValidatorResult::SUCCESS, "<b>Doctrine ORM:</b> Your database schema matches your entities.");
	}
}
