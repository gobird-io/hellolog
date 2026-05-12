# helloLOG

WordPress activity audit log plugin that ships events to an external
PostgreSQL + TimescaleDB backend at
[`api.gobird.io/v1/wordpress-activity-audit-log`](https://api.gobird.io/v1/wordpress-activity-audit-log).

The plugin keeps **only a small outgoing queue** in your WordPress DB — every
event is buffered for a few seconds and then pushed to the backend, where
storage, search, retention, and cross-site aggregation happen.

## Status

Scaffold (0.1.0). Sensors, transport layer, settings UI, and the Activity Log
admin page land in subsequent commits.

## Layout

```
hellolog.php       Plugin header + bootstrap
uninstall.php                   WP uninstall entry point
composer.json                   PSR-4 autoload + WPCS + PHPUnit deps
phpcs.xml.dist                  WordPress Coding Standards ruleset
readme.txt                      WordPress.org readme
CHANGELOG.md                    Keep a Changelog
includes/                       PSR-4 root, namespace HelloLog
  Plugin.php                    Singleton bootstrap
  Activator.php                 Queue table DDL + default options
  Deactivator.php               Cancels Action Scheduler jobs
  Uninstall.php                 Drops table + options
languages/                      Translations (.pot, .po, .mo)
```

## Local development

```sh
composer install
composer phpcs
composer test
```

## Coding standards

Inherits the LW Plugins house rules:
- PSR-4 autoloading, PascalCase filenames
- Max **200 lines** per class, **30 lines** per method
- `declare(strict_types=1);` at the top of every file
- WordPress Coding Standards (`composer phpcs` before every commit)
- PHP 8.0+ type declarations on parameters and return types
