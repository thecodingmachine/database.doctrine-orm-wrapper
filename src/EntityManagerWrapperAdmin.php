<?php
use Mouf\MoufManager;
// Controller declaration
MoufManager::getMoufManager()->declareComponent('entityManagerInstall', 'Mouf\\Doctrine\\ORM\\Controllers\\EntityManagerController', true);
MoufManager::getMoufManager()->bindComponents('entityManagerInstall', 'template', 'moufTemplate');
MoufManager::getMoufManager()->bindComponents('entityManagerInstall', 'contentBlock', 'block.content');
?>