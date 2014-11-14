# Users

Библиотека управления пользователями.

Пароли хешируются стандартными методами PHP (5.5+) http://php.net/manual/ru/book.password.php

## Использование

**Авторизация**

 - *При нескольких не успешных попытках авторизации за период (в соответствии настройкам) аккаунт будет заблокирован.*
 - *При успешной авторизации логируются данные пользователя.*
```
#!php
<?php

$manager = new CS\Users\Manager($db);
try {
    $userData = $manager->login($siteId, $email, $password);
} catch (CS\User\UserNotFoundException $e) {
    // пользователь не найден
} catch (CS\User\InvalidPasswordException $e) {
    // неверный пароль
} catch (CS\User\UserLockedException $e) {
    // учетная запись пользователя заблокирована
}
```
**Регистрация**
```
#!php
<?php

$manager = new CS\Users\Manager($db);
$userRecord = $manager->getUser();
$userRecord->setSiteId($siteId)
    ->setLogin($email)
    ->setPassword($manager->getPasswordHash($password))
    ->save();

$id = $userRecord->getId();
```

**Авторизация по ID (для администраторов)**
```
#!php
<?php

$manager = new CS\Users\Manager($db);
try {
    $userData = $manager->loginById($siteId, $id);
} catch (CS\User\UserNotFoundException $e) {
    // пользователь не найден
}
```

**Другое**
```
#!php
<?php

$manager = new CS\Users\Manager($db);

$manager->isUser($siteId, $email); // проверяем на существования на сайте пользователя с таким названием

$manager->getPasswordHash($password); // получаем хеш пароля

$manager->verifyPassword($hash, $password); // проверяем пароль

// получение объекта CS\Models\UserRecord (cs-models)
$manager->getUser();
$manager->getUser($id);
```