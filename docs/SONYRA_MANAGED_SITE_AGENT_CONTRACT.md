# SONYRA Managed Site Agent Contract

## Назначение

Этот документ фиксирует контракт будущего режима “Управляемый сайт” для плагина “Пульт сайта” / SONYRA Site Manager.

SONYRA Site Manager устанавливается на конкретный сайт и остаётся локальным Пультом сайта. В будущем этот плагин может получить роль SONYRA Managed Site Agent — управляемой стороны, которая безопасно подключается к отдельному центральному пульту владельца платформы.

Центральный пульт управления сайтами НЕ встраивается в этот плагин.

Будущий центральный продукт называется “Пульт управления сайтами” / SONYRA Owner Fleet Control и должен быть отдельным будущим плагином или модулем на управляющем сайте владельца платформы.

Первый релиз Пульта сайта не должен зависеть от готовности центрального пульта. Текущий плагин должен быть совместим с будущим удалённым управлением через безопасный Remote Control API, но на этом этапе API только документируется.

## Две стороны архитектуры

### Локальная сторона

Локальная сторона:

- Пульт сайта / SONYRA Site Manager на конкретном сайте.
- Будущая роль: Управляемый сайт / Managed Site Agent.

Будущий управляемый агент должен уметь:

- иметь `site_id` и `site_uuid`;
- подтверждать связь с центральным пультом;
- отдавать безопасный статус сайта;
- отдавать безопасную диагностику;
- отдавать статус лицензии и доступа;
- отдавать список статусов модулей;
- принимать только подписанные allowlist-команды;
- возвращать безопасный результат команды;
- писать локальный audit log.

### Центральная сторона

Центральная сторона:

- отдельный будущий плагин или модуль;
- видимое название: Пульт управления сайтами;
- техническое рабочее название: SONYRA Owner Fleet Control;
- место установки: управляющий сайт владельца платформы.

Будущий центральный пульт будет:

- хранить список подключённых сайтов;
- показывать статусы сайтов;
- показывать биллинг доступов владельца платформы;
- показывать лицензии;
- показывать диагностику;
- отправлять разрешённые команды;
- хранить историю действий;
- давать владельцу платформы управлять доступом.

Центральный пульт не создаётся внутри этого плагина.

## Future Remote Control API

Будущий namespace для управляемого сайта:

`sonyra-site-manager/v1`

Будущая группа endpoints:

- `POST /agent/connect`;
- `GET /agent/status`;
- `GET /agent/diagnostics`;
- `GET /agent/modules`;
- `POST /agent/license`;
- `POST /agent/command`;
- `GET /agent/command-result`.

На этапе 3.PLATFORM.0 endpoints только документируются. PHP-код не создаётся, REST controller не создаётся, `register_rest_route` не вызывается.

### POST /agent/connect

Назначение: безопасно связать этот сайт с центральным пультом владельца.

Будущий input:

- `site_url`;
- `site_label`;
- `public_key` или signed challenge;
- `connection_token`.

Будущий output:

- `success`;
- `site_uuid`;
- `connection_status`;
- `message`.

Запрещено возвращать:

- secrets;
- raw tokens;
- приватные ключи;
- персональные данные;
- данные клиентов сайта.

### GET /agent/status

Назначение: отдать безопасный технический статус сайта.

Будущий output:

- `site_uuid`;
- `plugin_version`;
- `db_version`;
- `seed_version`;
- `wordpress_version`;
- `php_version`;
- `license_status`;
- `access_status`;
- `last_seen_at`;
- `health_status`;
- `module_summary`.

### GET /agent/diagnostics

Назначение: отдать безопасную диагностику.

Будущий output:

- `database_health`;
- `rest_health`;
- `cron_health`;
- `mail_health`;
- `update_health`;
- `license_health`;
- `module_health`;
- `sanitized_error_summary`.

Запрещено возвращать:

- raw logs with secrets;
- stack traces with secrets;
- персональные данные;
- продажи клиента;
- содержимое заказов, форм или сообщений.

### GET /agent/modules

Назначение: отдать безопасный список модулей и их состояние.

Будущий output:

- `module_key`;
- `module_status`;
- `enabled`;
- `restricted`;
- `version`;
- `last_error_summary`.

### POST /agent/license

Назначение: получить или обновить безопасный статус лицензии и доступа.

Будущий behavior:

- принять подписанный license/access update;
- проверить подпись;
- обновить локальный `access_status`;
- записать audit event;
- вернуть safe result.

### POST /agent/command

Назначение: принять подписанную owner-only команду из центрального пульта.

Будущий behavior:

- проверить подпись;
- проверить timestamp;
- проверить replay protection;
- проверить `command_id`;
- проверить allowlist;
- выполнить только разрешённую команду;
- записать audit log;
- вернуть safe command result.

### GET /agent/command-result

Назначение: отдать безопасный результат ранее выполненной команды.

## Allowlist remote commands

Remote commands должны быть только allowlist.

Разрешённые будущие команды:

- `request_diagnostics` — запросить диагностику;
- `request_status` — запросить статус;
- `check_updates` — проверить обновления;
- `check_license` — проверить лицензию;
- `set_access_active` — включить доступ;
- `set_access_limited` — включить ограниченный режим;
- `set_access_suspended` — приостановить доступ;
- `disable_module` — отключить конкретный разрешённый модуль;
- `enable_module` — включить конкретный разрешённый модуль;
- `show_owner_notice` — показать техническое уведомление владельцу сайта;
- `clear_plugin_cache` — очистить технический кэш плагина;
- `request_safe_report` — запросить безопасный отчёт о состоянии.

Запрещённые команды:

- выполнить произвольный PHP-код;
- выполнить произвольное выполнение SQL;
- прочитать произвольную таблицу;
- скачать базу данных;
- скачать файлы сайта;
- изменить контент сайта клиента;
- изменить заказы клиента;
- изменить клиентов клиента;
- изменить платежи клиента;
- читать персональные данные без отдельного разрешённого сценария;
- создавать скрытого администратора;
- менять пароль администратора;
- отключать плагины вне allowlist;
- выполнять shell-команды.

## Remote Control API security

Будущий Remote Control API должен требовать:

- подпись запроса;
- `site_uuid`;
- `command_id`;
- timestamp;
- nonce/replay protection;
- срок действия команды;
- allowlist command type;
- audit log на локальном сайте;
- audit log на центральной стороне;
- безопасный response без secrets.

Запрещено:

- команды без подписи;
- команды без срока действия;
- команды без `command_id`;
- команды без audit log;
- произвольный payload, который исполняется как код;
- произвольный SQL;
- произвольное чтение файлов;
- произвольное чтение таблиц.

## Managed site identity

Каждый подключённый сайт в будущем должен иметь:

- `site_id`;
- `site_uuid`;
- public site label;
- `site_url`;
- `connection_status`;
- `license_id` или license key id;
- signing key model;
- `created_at`;
- `last_seen_at`;
- `revoked_at`;
- owner notes;
- status.

Правила identity:

- секреты не хранить в открытом виде;
- секреты не выводить в UI;
- секреты не писать в audit log;
- публичные идентификаторы можно показывать;
- приватные ключи и raw secrets нельзя показывать.

## Telemetry boundaries

Разрешённые данные telemetry:

- `site_uuid`;
- `site_url`;
- `site_name`;
- `plugin_version`;
- `db_version`;
- `seed_version`;
- WordPress version;
- PHP version;
- active theme name;
- `license_status`;
- `billing_status`;
- `billing_plan`;
- `access_status`;
- `last_seen_at`;
- heartbeat status;
- module statuses;
- update status;
- cron status;
- mail status;
- REST health status;
- error counters;
- sanitized error summaries;
- diagnostics result;
- owner command status;
- audit event counters.

Запрещённые данные telemetry:

- продажи клиента;
- заказы клиента;
- платежи клиента;
- корзины клиента;
- персональные данные клиентов сайта;
- медицинские данные;
- пароли;
- cookie пользователей сайта;
- raw access tokens;
- raw auth sessions;
- содержимое личных сообщений;
- содержимое форм;
- полная база данных сайта;
- произвольные таблицы сайта;
- файлы сайта без явного разрешённого сценария;
- коммерческие тайны клиента.

Этот слой отслеживает только техническое состояние сайта и коммерческий доступ к платформе SONYRA, а не бизнес клиента.

## License, access and billing status boundaries

Будущий API может принимать и отдавать только статус доступа или лицензии к платформе SONYRA:

- `active`;
- `trial`;
- `grace`;
- `payment_required`;
- `limited`;
- `suspended`;
- `disabled_by_owner`;
- `cancelled`;
- `error`;
- `unknown`.

Это не продажи клиента. Это состояние доступа к платформе SONYRA.

## Heartbeat contract

Heartbeat может быть реализован позже как отдельный этап.

Будущие варианты:

- локальный агент сам отправляет heartbeat в центральный пульт;
- центральный пульт запрашивает status/diagnostics у локального агента;
- гибридная модель.

Правила будущего heartbeat:

- heartbeat должен быть подписан;
- heartbeat не должен содержать персональные данные;
- heartbeat не должен содержать продажи клиента;
- heartbeat не должен содержать secrets;
- heartbeat должен содержать `site_uuid`, `plugin_version`, health summary, timestamp.

## Future local storage plan

Potential future managed site agent local tables/options:

- `sonyra_managed_site_connection`;
- `sonyra_managed_site_commands`;
- `sonyra_managed_site_command_logs`;
- `sonyra_managed_site_heartbeats`;
- `sonyra_managed_site_diagnostics_cache`.

Это только план. На этапе 3.PLATFORM.0 DB version не меняется, SQL не пишется, таблицы не создаются, migration runner не используется.

## Future separate owner plugin storage plan

Potential future central owner plugin tables, but NOT in this plugin:

- `sonyra_owner_connected_sites`;
- `sonyra_owner_site_heartbeats`;
- `sonyra_owner_site_diagnostics`;
- `sonyra_owner_site_commands`;
- `sonyra_owner_site_billing_status`;
- `sonyra_owner_site_license_events`.

Это только план для отдельного будущего продукта. Эти таблицы не относятся к текущему плагину и не создаются внутри SONYRA Site Manager.

## Future central modules

Будущий центральный пульт может иметь модули:

- Подключённые сайты;
- Доступы и лицензии;
- Биллинг платформы;
- Диагностика сайтов;
- Удалённые команды;
- Обновления;
- События безопасности;
- Заметки владельца;
- История сайта.

Эти модули не создаются в этом плагине.

## Юридико-этическое ограничение

Managed Site Agent предназначен для технического и коммерческого управления доступом к платформе SONYRA.

Он не предназначен для скрытого контроля бизнеса клиента.

Он не должен использоваться для просмотра продаж клиента, клиентов клиента, платежей клиента или персональных данных клиентов сайта.

Любые будущие расширения, затрагивающие персональные или коммерческие данные клиента, требуют отдельного юридического, технического и UX-контракта.

## Future implementation stages

- 3.PLATFORM.0 — Managed Site Agent / Remote Control API contract.
- 3.PLATFORM.1 — Managed site identity contract.
- 3.PLATFORM.2 — Remote Control API security contract.
- 3.PLATFORM.3 — Agent status endpoint implementation.
- 3.PLATFORM.4 — Agent diagnostics endpoint implementation.
- 3.PLATFORM.5 — Agent license/access endpoint implementation.
- 3.PLATFORM.6 — Signed command receiver implementation.
- 3.PLATFORM.7 — Heartbeat sender implementation.
- 3.PLATFORM.8 — Central Owner Control Plugin architecture contract.
- 4.OWNER.1 — Separate Owner Control Plugin scaffold.
- 4.OWNER.2 — Connected sites list.
- 4.OWNER.3 — Site diagnostics dashboard.
- 4.OWNER.4 — Billing/license access dashboard.
- 4.OWNER.5 — Remote command sender.

Эти этапы не реализуются сейчас.

## STOP-условия

Нужно немедленно остановиться, если текущий этап требует:

- создать PHP-код;
- создать route;
- создать REST endpoints;
- изменить REST endpoints;
- создать UI;
- создать центральный Пульт управления сайтами;
- создать owner console;
- создать JS/CSS;
- изменить plugin version, DB version или Seed version;
- собрать zip;
- создать database tables;
- написать SQL;
- добавить heartbeat code;
- добавить command receiver code;
- добавить billing enforcement code;
- отправить данные на внешний сервер;
- читать продажи клиента;
- собирать персональные данные клиента;
- выполнить удалённые команды;
- добавить произвольное удалённое выполнение PHP;
- добавить произвольное выполнение SQL;
- встроить Owner Control Plugin внутрь SONYRA Site Manager;
- смешать Managed Site Agent с login route или visual login shell;
- изменить бренд, название или правообладателя.
