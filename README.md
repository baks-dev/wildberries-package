# BaksDev Package Wildberries

[![Version](https://img.shields.io/badge/version-7.2.15-blue)](https://github.com/baks-dev/wildberries-package/releases)
![php 8.4+](https://img.shields.io/badge/php-min%208.4-red.svg)

Модуль упаковки заказов

## Установка

``` bash
$ composer require baks-dev/wildberries-package
```

## Дополнительно

Установка конфигурации и файловых ресурсов:

``` bash
$ php bin/console baks:assets:install
```

Изменения в схеме базы данных с помощью миграции

``` bash
$ php bin/console doctrine:migrations:diff
$ php bin/console doctrine:migrations:migrate
```

## Тестирование

``` bash
$ php bin/phpunit --group=wildberries-package
```

## Журнал изменений ![Changelog](https://img.shields.io/badge/changelog-yellow)

О том, что изменилось за последнее время, обратитесь к [CHANGELOG](CHANGELOG.md) за дополнительной информацией.

## Лицензия ![License](https://img.shields.io/badge/MIT-green)

The MIT License (MIT). Обратитесь к [Файлу лицензии](LICENSE.md) за дополнительной информацией.

