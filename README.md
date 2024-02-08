# BaksDev Wildberries Package

[![Version](https://img.shields.io/badge/version-7.0.12-blue)](https://github.com/baks-dev/wildberries-package/releases)
![php 8.2+](https://img.shields.io/badge/php-min%208.1-red.svg)

Модуль упаковки заказов

## Установка

``` bash
$ composer require baks-dev/wildberries-package
```

## Дополнительно

Изменения в схеме базы данных с помощью миграции

``` bash
$ php bin/console doctrine:migrations:diff

$ php bin/console doctrine:migrations:migrate
```

Установка файловых ресурсов в публичную директорию (javascript, css, image ...):

``` bash
$ php bin/console baks:assets:install
```

Роли администратора с помощью Fixtures

``` bash
$ php bin/console doctrine:fixtures:load --append
```

Тесты

``` bash
$ php bin/phpunit --group=wildberries-package
```

## Журнал изменений ![Changelog](https://img.shields.io/badge/changelog-yellow)

О том, что изменилось за последнее время, обратитесь к [CHANGELOG](CHANGELOG.md) за дополнительной информацией.

## Лицензия ![License](https://img.shields.io/badge/MIT-green)

The MIT License (MIT). Обратитесь к [Файлу лицензии](LICENSE.md) за дополнительной информацией.

