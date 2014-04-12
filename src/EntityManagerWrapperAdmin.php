<?php
use Mouf\MoufManager;
use Mouf\MoufUtils;

MoufUtils::registerMainMenu('dbMainMenu', 'DB', null, 'mainMenu', 70);
MoufUtils::registerMenuItem('dbDoctrineAdminSubMenu', 'Doctrine', null, 'dbMainMenu', 80);
MoufUtils::registerChooseInstanceMenuItem('dbDoctrineGenereateDAOAdminSubMenu', 'Generate schema and DAOs', 'entityManagerInstall/', "Mouf\\Doctrine\\ORM\\EntityManager", 'dbDoctrineAdminSubMenu', 10);

// Controller declaration
MoufManager::getMoufManager()->declareComponent('entityManagerInstall', 'Mouf\\Doctrine\\ORM\\Admin\\Controllers\\EntityManagerController', true);
MoufManager::getMoufManager()->bindComponents('entityManagerInstall', 'template', 'moufTemplate');
MoufManager::getMoufManager()->bindComponents('entityManagerInstall', 'contentBlock', 'block.content');
?>