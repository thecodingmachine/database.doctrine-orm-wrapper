<?php

namespace Mouf\Doctrine\ORM;


use Doctrine\ORM\ORMException;
use Mouf\Validator\MoufValidatorInterface;
use Mouf\Validator\MoufValidatorResult;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\Common\EventManager;


/**
 * This is a very simple wrapper around Doctrine's EntityManager that exposes its contructor as "public".
 * This allows calling the constructor directly using Mouf.
 *
 * @author Xavier HUBERTY <x.huberty@gmail.com>
 * @ExtendedAction {"name":"Generate DAOs", "url":"entityManagerInstall/", "default":false}
 * @ExtendedAction {"name":"Update DB schema", "url":"entityManagerInstall/generate_schema", "default":false}
 */
class MoufResetableEntityManager extends ResetableEntityManager
{
    /**
     * Creates a new EntityManager that operates on the given database connection
     * and uses the given Configuration and EventManager implementations.
     *
     * @param \Doctrine\DBAL\Connection     $conn
     * @param \Doctrine\ORM\Configuration   $config
     * @param \Doctrine\Common\EventManager $eventManager
     */
    public function __construct(Connection $conn, Configuration $config, EventManager $eventManager, $entityManagerClassName = 'Mouf\\Doctrine\\ORM\\EntityManager')
    {
        parent::__construct($conn, $config, $eventManager,$entityManagerClassName);
    }

    public function updateSchema()
    {
        try{
            return parent::getEntityManager()->updateSchema();
        } catch(ORMException $e) {
            if (!parent::getEntityManager()->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    public function getSchemaUpdateSQL()
    {
        try{
            return parent::getEntityManager()->getSchemaUpdateSQL();
        } catch(ORMException $e) {
            if (!parent::getEntityManager()->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    public function generateDAOs()
    {
        try{
            return parent::getEntityManager()->generateDAOs();
        } catch(ORMException $e) {
            if (!parent::getEntityManager()->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }


    public function setEntitiesNamespace($entitiesNamespace)
    {
        try{
            return parent::getEntityManager()->setEntitiesNamespace($entitiesNamespace);
        } catch(ORMException $e) {
            if (!parent::getEntityManager()->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }
    public function setProxyNamespace($proxyNamespace)
    {
        try{
            return parent::getEntityManager()->setProxyNamespace($proxyNamespace);
        } catch(ORMException $e) {
            if (!parent::getEntityManager()->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }
    public function setDaoNamespace($daoNamespace)
    {
        try{
            return parent::getEntityManager()->setDaoNamespace($daoNamespace);
        } catch(ORMException $e) {
            if (!parent::getEntityManager()->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see \Mouf\Validator\MoufValidatorInterface::validateInstance()
     */
    public function validateInstance()
    {
        try{
            return parent::getEntityManager()->validateInstance();
        } catch(ORMException $e) {
            if (!parent::getEntityManager()->isOpen()) {
                $this->resetEntityManager();
            }
            throw $e;
        }
    }
}
