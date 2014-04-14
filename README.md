Doctrine ORM wrapper classes for Mouf
=====================================

WARNING! IN DEVELOPMENT! NOT READY FOR PRODUCTION
=================================================

This package contains a single classe that makes Doctrine/ORM easily usable in Mouf.
It extends Doctrine's `EntityManager` class and makes it constructor public (and therefore instantiable using Mouf DI engine).

The other components in this package only implement:

 * install & edit interfaces to help the user defining the main properties of the `entityManager` instance. 
 * automated DB Schema generation
 * DAO generation that ill produce helpers for performing base queries
 
These steps are triggered during the install process, but also when lanching the configuration interface from the `entityManager` instance dedicated button :

![Configure the enttityManager](doc/images/configure-entityManager.png) 


> **Approach:** Doctrine allows multiple strategies to operate from DB Schema to it's Entities.
In this package, we made the choice to work from Model down to DB Schema : you just define your entities,
then use the `entityManager` and associated `schemaTool` in order to create / update your schema.

For more information, please read the next steps:

 * Doctrine documentation is very important as this package is just a wrapper : [http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/)
 * [Defining properties for the `entityManager` instance](doc/define-properties.md)
 * [Create / Update your DB schema](doc/schema.md)
 * [Generated DAO classes](doc/daos.md)