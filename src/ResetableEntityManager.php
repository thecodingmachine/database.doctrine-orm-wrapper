<?php
namespace Mouf\Doctrine\ORM;

use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\Common\EventManager;
use Doctrine\ORM\ORMException;
use Mouf\Validator\MoufValidatorInterface;
use Mouf\Validator\MoufValidatorResult;

/**
 * This is a very simple wrapper around Doctrine's EntityManager that exposes its contructor as "public".
 * This allows calling the constructor directly using Mouf.
 *
 * @author Xavier HUBERTY <x.huberty@gmail.com>
 */
class ResetableEntityManager extends DoctrineEntityManager
{
    private $conn;
    private $config;
    private $eventManager;
    private $entityManager;
    private $entityManagerClassName;

    const ENTITY_MANAGER_CLOSED = "The EntityManager is closed.";

    /**
     * @param Connection $conn
     * @param Configuration $config
     * @param EventManager $eventManager
     * @param EntityManager $entityManager
     * @throws ORMException
     */
    public function __construct(Connection $conn, Configuration $config, EventManager $eventManager, $entityManagerClassName = 'Doctrine\\ORM\\EntityManager')
    {
        $this->conn = $conn;
        $this->config = $config;
        $this->eventManager = $eventManager;
        $this->entityManagerClassName = $entityManagerClassName;
        $this->resetEntityManager();
    }

    /**
     * @return mixed
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getConnection()
    {
        try{
            return $this->entityManager->getConnection();
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * Gets the metadata factory used to gather the metadata of classes.
     *
     * @return \Doctrine\ORM\Mapping\ClassMetadataFactory
     */
    public function getMetadataFactory()
    {
        try{
            return $this->entityManager->getMetadataFactory();
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getExpressionBuilder()
    {
        try{
            return $this->entityManager->getExpressionBuilder();
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function beginTransaction()
    {
        try{
            $this->entityManager->beginTransaction();
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getCache()
    {
        try{
            return $this->entityManager->getCache();
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function transactional($func)
    {
        try{
            $this->entityManager->transactional($func);
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function commit()
    {
        try{
            $this->entityManager->commit();
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function rollback()
    {
        try{
            $this->entityManager->rollback();
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * Returns the ORM metadata descriptor for a class.
     *
     * The class name must be the fully-qualified class name without a leading backslash
     * (as it is returned by get_class($obj)) or an aliased class name.
     *
     * Examples:
     * MyProject\Domain\User
     * sales:PriceRequest
     *
     * Internal note: Performance-sensitive method.
     *
     * @param string $className
     *
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    public function getClassMetadata($className)
    {
        try{
            return $this->entityManager->getClassMetadata($className);
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createQuery($dql = '')
    {
        try{
            return $this->entityManager->createQuery($dql);
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createNamedQuery($name)
    {
        try{
            return $this->entityManager->createNamedQuery($name);
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createNativeQuery($sql, ResultSetMapping $rsm)
    {
        try{
            return $this->entityManager->createNativeQuery($sql, $rsm);
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createNamedNativeQuery($name)
    {
        try{
            return $this->entityManager->createNamedNativeQuery($name);
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createQueryBuilder()
    {
        try{
            return $this->entityManager->createQueryBuilder();
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * Flushes all changes to objects that have been queued up to now to the database.
     * This effectively synchronizes the in-memory state of managed objects with the
     * database.
     *
     * If an entity is explicitly passed to this method only this entity and
     * the cascade-persist semantics + scheduled inserts/removals are synchronized.
     *
     * @param null|object|array $entity
     *
     * @return void
     *
     * @throws \Doctrine\ORM\OptimisticLockException If a version check on an entity that
     *         makes use of optimistic locking fails.
     */
    public function flush($entity = null)
    {
        try{
            return $this->entityManager->flush($entity);
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * Finds an Entity by its identifier.
     *
     * @param string       $entityName  The class name of the entity to find.
     * @param mixed        $id          The identity of the entity to find.
     * @param integer|null $lockMode    One of the \Doctrine\DBAL\LockMode::* constants
     *                                  or NULL if no specific lock mode should be used
     *                                  during the search.
     * @param integer|null $lockVersion The version of the entity to find when using
     *                                  optimistic locking.
     *
     * @return object|null The entity instance or NULL if the entity can not be found.
     *
     * @throws OptimisticLockException
     * @throws ORMInvalidArgumentException
     * @throws TransactionRequiredException
     * @throws ORMException
     */
    public function find($entityName, $id, $lockMode = null, $lockVersion = null)
    {
        try{
            return $this->entityManager->find($entityName, $id, $lockMode, $lockVersion);
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getReference($entityName, $id)
    {
        try{
            return $this->entityManager->getReference($entityName, $id);
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getPartialReference($entityName, $identifier)
    {
        try{
            return $this->entityManager->getPartialReference($entityName, $identifier);
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * Clears the EntityManager. All entities that are currently managed
     * by this EntityManager become detached.
     *
     * @param string|null $entityName if given, only entities of this type will get detached
     *
     * @return void
     */
    public function clear($entityName = null)
    {
        try{
            return $this->entityManager->clear($entityName);
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function close()
    {
        try{
            return $this->entityManager->close();
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * Tells the EntityManager to make an instance managed and persistent.
     *
     * The entity will be entered into the database at or before transaction
     * commit or as a result of the flush operation.
     *
     * NOTE: The persist operation always considers entities that are not yet known to
     * this EntityManager as NEW. Do not pass detached entities to the persist operation.
     *
     * @param object $entity The instance to make managed and persistent.
     *
     * @return void
     *
     * @throws ORMInvalidArgumentException
     */
    public function persist($entity)
    {
        try{
            return $this->entityManager->persist($entity);
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * Removes an entity instance.
     *
     * A removed entity will be removed from the database at or before transaction commit
     * or as a result of the flush operation.
     *
     * @param object $entity The entity instance to remove.
     *
     * @return void
     *
     * @throws ORMInvalidArgumentException
     */
    public function remove($entity)
    {
        try{
            return $this->entityManager->remove($entity);
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * Refreshes the persistent state of an entity from the database,
     * overriding any local changes that have not yet been persisted.
     *
     * @param object $entity The entity to refresh.
     *
     * @return void
     *
     * @throws ORMInvalidArgumentException
     */
    public function refresh($entity)
    {
        try{
            return $this->entityManager->refresh($entity);
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * Detaches an entity from the EntityManager, causing a managed entity to
     * become detached.  Unflushed changes made to the entity if any
     * (including removal of the entity), will not be synchronized to the database.
     * Entities which previously referenced the detached entity will continue to
     * reference it.
     *
     * @param object $entity The entity to detach.
     *
     * @return void
     *
     * @throws ORMInvalidArgumentException
     */
    public function detach($entity)
    {
        try{
            return $this->entityManager->detach($entity);
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * Merges the state of a detached entity into the persistence context
     * of this EntityManager and returns the managed copy of the entity.
     * The entity passed to merge will not become associated/managed with this EntityManager.
     *
     * @param object $entity The detached entity to merge into the persistence context.
     *
     * @return object The managed copy of the entity.
     *
     * @throws ORMInvalidArgumentException
     */
    public function merge($entity)
    {
        try{
            return $this->entityManager->merge($entity);
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     *
     * @todo Implementation need. This is necessary since $e2 = clone $e1; throws an E_FATAL when access anything on $e:
     * Fatal error: Maximum function nesting level of '100' reached, aborting!
     */
    public function copy($entity, $deep = false)
    {
        try{
            return $this->entityManager->copy($entity, $deep);
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function lock($entity, $lockMode, $lockVersion = null)
    {
        try{
            return $this->entityManager->lock($entity, $lockMode, $lockVersion);
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * Gets the repository for an entity class.
     *
     * @param string $entityName The name of the entity.
     *
     * @return \Doctrine\ORM\EntityRepository The repository class.
     */
    public function getRepository($entityName)
    {
        try{
            return $this->entityManager->getRepository($entityName);
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * Determines whether an entity instance is managed in this EntityManager.
     *
     * @param object $entity
     *
     * @return boolean TRUE if this EntityManager currently manages the given entity, FALSE otherwise.
     */
    public function contains($entity)
    {
        try{
            return $this->entityManager->contains($entity);
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getEventManager()
    {
        return $this->eventManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfiguration()
    {
        return $this->config;
    }


    /**
     * {@inheritDoc}
     */
    public function isOpen()
    {
        try{
            return $this->entityManager->isOpen();
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getUnitOfWork()
    {
        try{
            return $this->entityManager->getUnitOfWork();
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getHydrator($hydrationMode)
    {
        try{
            return $this->entityManager->getHydrator($hydrationMode);
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function newHydrator($hydrationMode)
    {
        try{
            return $this->entityManager->newHydrator($hydrationMode);
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getProxyFactory()
    {
        try{
            return $this->entityManager->getProxyFactory();
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function initializeObject($obj)
    {
        try{
            return $this->entityManager->initializeObject($obj);
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * Factory method to create EntityManager instances.
     *
     * @param mixed         $conn         An array with the connection parameters or an existing Connection instance.
     * @param Configuration $config       The Configuration instance to use.
     * @param EventManager  $eventManager The EventManager instance to use.
     *
     * @return EntityManager The created EntityManager.
     *
     * @throws \InvalidArgumentException
     * @throws ORMException
     */
    public static function create($conn, Configuration $config, EventManager $eventManager = null)
    {
        try{
            return self::getEntityManager()->create($conn, $config, $eventManager);
        } catch(ORMException $e) {
            if (!self::getEntityManager()->isOpen()) {
                self::resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getFilters()
    {
        try{
            return $this->entityManager->getFilters();
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isFiltersStateClean()
    {
        try{
            return $this->entityManager->isFiltersStateClean();
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function hasFilters()
    {
        try{
            return $this->entityManager->hasFilters();
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }


    protected function resetEntityManager() {
        $entityManagerClassName = $this->entityManagerClassName;
        $this->entityManager = new $entityManagerClassName($this->conn, $this->config, $this->eventManager);
    }

    public function __call($method, $args) {
        try{
            // TODO: check that protected methods are not exposed.
            return call_user_func_array(array($this->entityManager, $method), $args);
        } catch(ORMException $e) {
            if (!$this->entityManager->isOpen()) {
                // Let's reset the entityManager instance if it is closed.
                $this->resetEntityManager();
            }
            throw $e;
        }
    }
}
