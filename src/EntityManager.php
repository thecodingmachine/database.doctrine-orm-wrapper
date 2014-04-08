<?php
namespace Mouf\Doctrine\ORM;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\Common\EventManager;
use Doctrine\ORM\ORMException;

/**
 * This is a very simple wrapper around Doctrine's EntityManager that exposes its contructor as "public".
 * This allows calling the constructor directly using Mouf.
 * 
 * @author David Négrier <david@mouf-php.com>
 */
class EntityManager extends \Doctrine\ORM\EntityManager
{
    
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
}
