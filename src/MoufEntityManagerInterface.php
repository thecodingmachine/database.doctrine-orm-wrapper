<?php

namespace Mouf\Doctrine\ORM;


/**

 * @author Xavier HUBERTY <x.huberty@gmail.com>
 */
interface MoufEntityManagerInterface
{

    public function updateSchema();

    public function getSchemaUpdateSQL();

    public function generateDAOs();

}