Writing packages that provide entities
======================================

If you are looking to write a package that provides a set of entities, you must make sure that Doctrine finds your entities inside your package.

To do so, you must write a [Mouf install script for your package](http://mouf-php.com/packages/mouf/mouf/doc/install_process.md).

In this install process, you can use the `DoctrineInstallUtils` class to register your package entities:

```php
$doctrineInstallUtils = new DoctrineInstallUtils($moufManager);

// Register classic Doctrine entities (with annotations):
// First parameter is the namespace of the entities
// Second parameter is the directory of the entities
$doctrineInstallUtils->registerAnnotationBasedEntities('My\Namespace', 'src/my/directory');

// Register YAML mapping files for Doctrine entities:
// First parameter is the namespace of the entities
// Second parameter is the directory of the mapping files
$doctrineInstallUtils->registerYamlBasedEntities('My\Namespace', 'src/my/directory');

// Register XML mapping files for Doctrine entities:
// First parameter is the namespace of the entities
// Second parameter is the directory of the mapping files
$doctrineInstallUtils->registerXmlBasedEntities('My\Namespace', 'src/my/directory');
```
