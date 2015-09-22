<?php

namespace Mouf\Doctrine\ORM\Event;


/**
 * An interface the will be used in dao
 *
 *@author  Xavier HUBERTY <x.huberty@thecodingmachine.com>
 */
interface SaveListenerInterface
{
    /**
     * This function will be call before the save operation (flush)
     * @param mixed $entity
     *
     * @throws \Exception
     */
    function preSave($entity);

    /**
     * This function will be call before the save operation (flush)
     * @param mixed $entity
     *
     * @throws \Exception
     */
    function postSave($entity);
}
