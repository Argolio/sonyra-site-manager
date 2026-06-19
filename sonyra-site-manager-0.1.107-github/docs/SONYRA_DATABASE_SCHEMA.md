# SONYRA Database Schema Contract

## Назначение

Этот документ фиксирует будущий database schema contract для SONYRA Site Manager / “Пульт сайта” внутри SONYRA Site Platform.

На этапе 1.4 таблицы НЕ создаются. Документ только описывает контракт. Реальное создание таблиц должно быть отдельным этапом после принятия этого контракта.

Этот контракт не является migration runner, не меняет версию плагина и не создаёт database foundation.

## Общие принципы

- Логические имена таблиц описаны без WordPress-префикса.
- Физические имена таблиц в WordPress должны использовать `$wpdb->prefix`, например `{$wpdb->prefix}sonyra_sites`.
- Каждая таблица должна иметь `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`.
- Для дат используются `created_at DATETIME` и `updated_at DATETIME`, где это применимо.
- `status VARCHAR(50)` используется там, где сущности имеют жизненный цикл.
- JSON-данные хранятся только в `LONGTEXT` через `wp_json_encode` и `wp_json_decode`.
- MySQL JSON type не используется ради совместимости WordPress-хостингов.
- JSON нельзя использовать как свалку для всего подряд.
- Секреты нельзя хранить в открытом виде.
- Лишние персональные данные нельзя хранить.
- Медицинские и чувствительные данные нельзя хранить.
- API keys, tokens, passwords, cookies и nonces нельзя хранить в таблицах или plain options.

## Будущие таблицы первого слоя

### sonyra_sites

Назначение: базовая сущность сайта/проекта внутри платформы.

Минимальные поля:

- `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
- `slug VARCHAR(190)`
- `title VARCHAR(255)`
- `status VARCHAR(50)`
- `owner_user_id BIGINT UNSIGNED`
- `settings LONGTEXT`
- `created_at DATETIME`
- `updated_at DATETIME`

Индексы:

- `slug`
- `status`
- `owner_user_id`
- `created_at`
- `updated_at`

### sonyra_pages

Назначение: страницы, созданные через Пульт сайта, не WordPress Pages.

Минимальные поля:

- `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
- `site_id BIGINT UNSIGNED`
- `slug VARCHAR(190)`
- `title VARCHAR(255)`
- `status VARCHAR(50)`
- `template_key VARCHAR(100)`
- `published_revision_id BIGINT UNSIGNED`
- `created_by BIGINT UNSIGNED`
- `updated_by BIGINT UNSIGNED`
- `created_at DATETIME`
- `updated_at DATETIME`

Индексы:

- `site_id`
- `slug`
- `status`
- `created_by`
- `updated_at`

### sonyra_page_revisions

Назначение: версии страниц.

Минимальные поля:

- `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
- `page_id BIGINT UNSIGNED`
- `revision_number BIGINT UNSIGNED`
- `status VARCHAR(50)`
- `snapshot LONGTEXT`
- `created_by BIGINT UNSIGNED`
- `created_at DATETIME`

Индексы:

- `page_id`
- `status`
- `created_by`
- `created_at`

### sonyra_page_blocks

Назначение: блоки страниц и их настройки.

Минимальные поля:

- `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
- `page_id BIGINT UNSIGNED`
- `revision_id BIGINT UNSIGNED`
- `block_type VARCHAR(100)`
- `sort_order INT UNSIGNED`
- `status VARCHAR(50)`
- `settings LONGTEXT`
- `created_at DATETIME`
- `updated_at DATETIME`

Индексы:

- `page_id`
- `revision_id`
- `block_type`
- `status`
- `sort_order`

### sonyra_global_settings

Назначение: глобальные настройки платформы.

Минимальные поля:

- `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
- `setting_key VARCHAR(190)`
- `setting_value LONGTEXT`
- `autoload_flag TINYINT UNSIGNED`
- `updated_by BIGINT UNSIGNED`
- `created_at DATETIME`
- `updated_at DATETIME`

Индексы:

- `setting_key`
- `updated_by`
- `updated_at`

### sonyra_brand_assets

Назначение: брендовые медиа-объекты.

Минимальные поля:

- `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
- `site_id BIGINT UNSIGNED`
- `asset_type VARCHAR(100)`
- `attachment_id BIGINT UNSIGNED`
- `title VARCHAR(255)`
- `status VARCHAR(50)`
- `metadata LONGTEXT`
- `created_by BIGINT UNSIGNED`
- `created_at DATETIME`
- `updated_at DATETIME`

Индексы:

- `site_id`
- `asset_type`
- `attachment_id`
- `status`
- `created_at`

### sonyra_logo_settings

Назначение: настройки логотипов.

Минимальные поля:

- `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
- `site_id BIGINT UNSIGNED`
- `logo_context VARCHAR(100)`
- `asset_id BIGINT UNSIGNED`
- `status VARCHAR(50)`
- `responsive_settings LONGTEXT`
- `created_at DATETIME`
- `updated_at DATETIME`

Индексы:

- `site_id`
- `logo_context`
- `asset_id`
- `status`

### sonyra_design_tokens

Назначение: системные design tokens.

Минимальные поля:

- `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
- `site_id BIGINT UNSIGNED`
- `token_group VARCHAR(100)`
- `token_key VARCHAR(190)`
- `token_value LONGTEXT`
- `status VARCHAR(50)`
- `created_at DATETIME`
- `updated_at DATETIME`

Индексы:

- `site_id`
- `token_group`
- `token_key`
- `status`

### sonyra_gradient_presets

Назначение: пресеты градиентов.

Минимальные поля:

- `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
- `site_id BIGINT UNSIGNED`
- `preset_key VARCHAR(190)`
- `title VARCHAR(255)`
- `status VARCHAR(50)`
- `gradient_config LONGTEXT`
- `created_by BIGINT UNSIGNED`
- `created_at DATETIME`
- `updated_at DATETIME`

Индексы:

- `site_id`
- `preset_key`
- `status`
- `created_by`

### sonyra_header_configs

Назначение: настройки шапки.

Минимальные поля:

- `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
- `site_id BIGINT UNSIGNED`
- `config_key VARCHAR(190)`
- `status VARCHAR(50)`
- `settings LONGTEXT`
- `created_by BIGINT UNSIGNED`
- `created_at DATETIME`
- `updated_at DATETIME`

Индексы:

- `site_id`
- `config_key`
- `status`
- `updated_at`

### sonyra_footer_configs

Назначение: настройки подвала.

Минимальные поля:

- `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
- `site_id BIGINT UNSIGNED`
- `config_key VARCHAR(190)`
- `status VARCHAR(50)`
- `settings LONGTEXT`
- `created_by BIGINT UNSIGNED`
- `created_at DATETIME`
- `updated_at DATETIME`

Индексы:

- `site_id`
- `config_key`
- `status`
- `updated_at`

### sonyra_modules

Назначение: реестр модулей.

Минимальные поля:

- `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
- `module_id VARCHAR(190)`
- `module_version VARCHAR(50)`
- `status VARCHAR(50)`
- `manifest LONGTEXT`
- `enabled_by BIGINT UNSIGNED`
- `created_at DATETIME`
- `updated_at DATETIME`

Индексы:

- `module_id`
- `module_version`
- `status`
- `enabled_by`

### sonyra_embeds

Назначение: коды вставки, iframe/HTML/shortcode export records.

Минимальные поля:

- `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
- `site_id BIGINT UNSIGNED`
- `object_type VARCHAR(100)`
- `object_id BIGINT UNSIGNED`
- `embed_type VARCHAR(100)`
- `embed_token_hash VARCHAR(190)`
- `status VARCHAR(50)`
- `config LONGTEXT`
- `created_by BIGINT UNSIGNED`
- `created_at DATETIME`
- `updated_at DATETIME`

Индексы:

- `site_id`
- `object_type, object_id`
- `embed_type`
- `embed_token_hash`
- `status`
- `created_at`

### sonyra_audit_log

Назначение: будущий полноценный журнал событий.

Минимальные поля:

- `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
- `event_type VARCHAR(190)`
- `severity VARCHAR(50)`
- `object_type VARCHAR(100)`
- `object_id BIGINT UNSIGNED`
- `user_id BIGINT UNSIGNED`
- `message VARCHAR(255)`
- `context LONGTEXT`
- `created_at DATETIME`

Индексы:

- `event_type`
- `severity`
- `object_type, object_id`
- `user_id`
- `created_at`

### sonyra_locks

Назначение: блокировки редактирования.

Минимальные поля:

- `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
- `object_type VARCHAR(100)`
- `object_id BIGINT UNSIGNED`
- `user_id BIGINT UNSIGNED`
- `lock_token_hash VARCHAR(190)`
- `expires_at DATETIME`
- `created_at DATETIME`
- `updated_at DATETIME`

Индексы:

- `object_type, object_id`
- `user_id`
- `lock_token_hash`
- `expires_at`

### sonyra_user_permissions

Назначение: дополнительные правила доступа.

Минимальные поля:

- `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
- `user_id BIGINT UNSIGNED`
- `site_id BIGINT UNSIGNED`
- `permission_key VARCHAR(190)`
- `status VARCHAR(50)`
- `rules LONGTEXT`
- `created_by BIGINT UNSIGNED`
- `created_at DATETIME`
- `updated_at DATETIME`

Индексы:

- `user_id`
- `site_id`
- `permission_key`
- `status`

### sonyra_events

Назначение: сырые события аналитики.

Минимальные поля:

- `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
- `site_id BIGINT UNSIGNED`
- `page_id BIGINT UNSIGNED`
- `event_type VARCHAR(190)`
- `event_date DATE`
- `metadata LONGTEXT`
- `created_at DATETIME`

Индексы:

- `site_id`
- `page_id`
- `event_type`
- `event_date`
- `created_at`

### sonyra_analytics_daily

Назначение: дневная агрегация.

Минимальные поля:

- `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
- `site_id BIGINT UNSIGNED`
- `page_id BIGINT UNSIGNED`
- `date DATE`
- `metric_key VARCHAR(190)`
- `metric_value BIGINT UNSIGNED`
- `metadata LONGTEXT`
- `created_at DATETIME`
- `updated_at DATETIME`

Индексы:

- `site_id`
- `page_id`
- `date`
- `metric_key`
- `updated_at`

### sonyra_version_log

Назначение: история версий и миграций.

Минимальные поля:

- `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
- `from_version VARCHAR(50)`
- `to_version VARCHAR(50)`
- `db_version VARCHAR(50)`
- `migration_id VARCHAR(190)`
- `status VARCHAR(50)`
- `message VARCHAR(255)`
- `context LONGTEXT`
- `created_at DATETIME`

Индексы:

- `from_version`
- `to_version`
- `db_version`
- `migration_id`
- `status`
- `created_at`

## Допустимые JSON/LONGTEXT-поля

Разрешённые области для JSON в `LONGTEXT`:

- block settings;
- design settings;
- gradient config;
- embed config;
- module manifest data;
- responsive logo settings;
- feature flags;
- analytics metadata без персональных данных.

Запрещено:

- хранить секреты, nonce, passwords, tokens, cookies, API keys;
- складывать произвольные payload без схемы;
- заменять JSON-полем нормальные связи таблиц;
- хранить лишние персональные данные;
- хранить медицинские или чувствительные данные.

## Общий набор будущих индексов

Будущие реализации должны учитывать индексы:

- `status`
- `slug`
- `page_id`
- `site_id`
- `module_id`
- `user_id`
- `event_type`
- `created_at`
- `updated_at`
- `date`
- `object_type, object_id`
- `embed_token_hash`, если будет использоваться.

## Migration rules

Будущие миграции должны:

- идти через отдельный migration runner;
- использовать dbDelta только в строго проверенном слое;
- иметь db version option;
- иметь version log;
- быть идемпотентными;
- не удалять данные без отдельного явного destructive этапа;
- не делать destructive migration в обычном релизе;
- не выполняться, если обнаружен mismatch версии;
- логировать результат в audit log и version log;
- иметь rollback plan хотя бы на уровне zip-отката;
- не зависеть от manager UI.

Будущая database implementation должна быть отдельным этапом и не должна смешиваться с REST/routes, manager UI, admin pages или wp-admin menu.

## Options

Уже существующие и планируемые options:

- `sonyra_site_manager_version`
- `sonyra_site_manager_last_known_version`
- `sonyra_site_manager_installed_at`
- `sonyra_site_manager_updated_at`
- `sonyra_site_manager_owner_user_id`
- `sonyra_site_manager_release_channel`
- `sonyra_site_manager_build_type`
- `sonyra_site_manager_product_name`
- `sonyra_site_manager_platform_name`
- `sonyra_site_manager_publisher`
- `sonyra_site_manager_licensor`
- `sonyra_site_manager_db_version`
- `sonyra_site_manager_audit_log`

Текущий option-based audit log является временным foundation.

Полноценная таблица `sonyra_audit_log` должна появиться позже отдельной миграцией.

Временный option audit log нельзя удалять автоматически при переходе на таблицу.

## Database security rules

- Данные нужно sanitize перед insert/update.
- Raw SQL должен использовать prepare там, где присутствуют данные пользователя или динамические значения.
- Escape выполняется только на output.
- Секрет нельзя хранить в plain options или tables.
- Персональные данные нельзя хранить без необходимости и явного назначения.
- Открытые tokens, cookies, passwords, nonces и API keys запрещены.
- Пользовательские SQL-фрагменты нельзя передавать напрямую.

## STOP-условия перед будущим созданием таблиц

Будущий database foundation должен остановиться, если:

- не принят `SONYRA_DATABASE_SCHEMA.md`;
- нет db version plan;
- нет migration runner plan;
- нет version log plan;
- нет audit log plan;
- есть риск destructive migration;
- таблицы создаются без `$wpdb->prefix`;
- используются raw SQL без prepare там, где есть данные пользователя;
- используются секреты в БД без необходимости;
- создаются WordPress Pages;
- создаются REST/routes/UI в том же этапе;
- смешиваются DB + manager UI + REST + routes;
- Codex пытается создать таблицы без отдельного этапа.

## Future Auth/OTP schema extension

Будущие Auth/OTP tables не входят в DB version 0.1.0.

Они должны появиться только отдельной миграцией DB version 0.1.1 на этапе 2.AUTH.1.

Нельзя добавлять эти таблицы без отдельного этапа 2.AUTH.1.

Будущие логические таблицы:

- `sonyra_auth_identities` — внутренние личности пользователей Пульта сайта, email, связь с WordPress user_id, статус, роль, provider links.
- `sonyra_auth_otp_challenges` — OTP challenge, email hash, code hash, expires_at, attempts_count, used_at, status.
- `sonyra_auth_sessions` — сессии входа в Пульт сайта, session token hash, user identity, expires_at, revoked_at.
- `sonyra_auth_attempts` — журнал попыток, rate limit, lockout, IP hash, user agent hash.
- `sonyra_auth_trusted_devices` — будущий “запомнить устройство”, device token hash, expires_at, revoked_at.
- `sonyra_auth_providers` — email, VK, MAX, Яндекс, TOTP, Passkey/WebAuthn.

Физические имена должны использовать `$wpdb->prefix`:

- `{$wpdb->prefix}sonyra_auth_identities`;
- `{$wpdb->prefix}sonyra_auth_otp_challenges`;
- `{$wpdb->prefix}sonyra_auth_sessions`;
- `{$wpdb->prefix}sonyra_auth_attempts`;
- `{$wpdb->prefix}sonyra_auth_trusted_devices`;
- `{$wpdb->prefix}sonyra_auth_providers`.

## Potential future managed site agent local tables/options

Этот раздел является только планом будущей PLATFORM/AGENT-линии. Он не меняет DB version, не создаёт SQL, не создаёт таблицы, не вызывает migration runner и не является migration contract текущего релиза.

Potential future managed site agent local tables/options:

- `sonyra_managed_site_connection` — будущая локальная связь сайта с центральным пультом, `site_uuid`, статус подключения, публичная метка, модель подписи.
- `sonyra_managed_site_commands` — будущие входящие signed command records, `command_id`, allowlist command type, статус, срок действия.
- `sonyra_managed_site_command_logs` — будущий безопасный журнал результатов команд без secrets и без персональных данных.
- `sonyra_managed_site_heartbeats` — будущий кэш heartbeat/status-событий без продаж клиента и без персональных данных.
- `sonyra_managed_site_diagnostics_cache` — будущий безопасный diagnostics cache с sanitized summaries.

Секреты не должны храниться в открытом виде. Raw tokens, private keys, raw auth sessions, cookie пользователей сайта, продажи клиента и персональные данные клиентов сайта запрещены.

## Potential future central owner plugin tables

Этот раздел описывает только будущий отдельный Owner Control Plugin. Эти таблицы НЕ относятся к текущему плагину, НЕ создаются внутри SONYRA Site Manager и НЕ должны влиять на DB version текущего плагина.

Potential future central owner plugin tables, but NOT in this plugin:

- `sonyra_owner_connected_sites`;
- `sonyra_owner_site_heartbeats`;
- `sonyra_owner_site_diagnostics`;
- `sonyra_owner_site_commands`;
- `sonyra_owner_site_billing_status`;
- `sonyra_owner_site_license_events`.

Это только план для отдельного будущего продукта. Центральный пульт управления сайтами не встраивается в SONYRA Site Manager.
