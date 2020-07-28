<?php
namespace Mouf\Doctrine\ORM\Mapping\Driver;

use Doctrine\Persistence\Mapping\Driver\MappingDriver;

/**
 * Extension of MappingDriverChain to play more easily with Mouf instances.
 */
class MappingDriverChain extends \Doctrine\Persistence\Mapping\Driver\MappingDriverChain {

    /**
     * @param array<string,MappingDriver> $drivers The key is the namespace, the value the driver to use.
     */
    public function setDrivers(array $drivers) {
        foreach ($drivers as $namespace => $driver) {
            /* @var $driver MappingDriver */
            $this->addDriver($driver, $namespace);
        }
    }
}
