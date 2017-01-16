#Generated DAO classes

DAO instances will provide you with a shortcut to your Entities' `RepositoryManagers`. If you are familiar with Doctrine ORM, you may know that this code :

```php
$user = $entityManager->find('PATH\TO\ENTITIES\User', 7);
```

Or that one :

```php
$user = $entityManager->getRepository('PATH\TO\ENTITIES\User')->find(7);
```

... will retrieve the User with id 7

Repositories also provide magic __call implementation that can retrive a User entity by it's fields, like `$entityManager->getRepository('PATH\TO\ENTITIES\User')->findByLogin('fooBar')` if your `User` class declares a `login` field.

Problem is : as this is a __call implementation, your IDE wont provide you with autocompletion.

Therefore, the database.doctrine-orm-wrapper package will autogenerate DAO classes that will make it easier :

```php
$user = $userDao->find(7);//Find user with ID '7'
$user = $userDao->findByLogin('fooBar');//Find user with login 'fooBar', with autocomplete :)
```

**Note:** In fact, 2 classes are generated for each Entity:

 * the `BaseDao` class is generated each time the process is run
 * the `Dao` class is generated only once. It extends the `BaseDao`, and will remain unmodifies after being created as you may code your own requests there.  

As Mouf provides other ORM packages, the DAOInterface is implemented by the Generated DAOs, this will simply add some functions for Object retrieval : `getById`, `create`, `save`, and `getList` methods.
