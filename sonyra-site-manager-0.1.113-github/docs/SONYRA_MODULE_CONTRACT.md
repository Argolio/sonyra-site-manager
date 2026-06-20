# SONYRA Module Contract

## Назначение

Этот документ фиксирует контракт будущей модульной системы SONYRA Site Manager.

SONYRA Site Manager должен развиваться как модульная система SONYRA Site Platform, а не как монолитный комбайн.

## Общие правила SONYRA

Во всех будущих модулях используются только канонические написания:

- SONYRA
- SONYRA Site Platform
- SONYRA Site Manager
- SONYRA STUDIO

Неканонические варианты названия SONYRA нельзя использовать как рабочие названия.

Весь видимый пользовательский интерфейс будущего продукта должен быть только на русском языке.

Английские пользовательские UI-строки и англицизмы запрещены.

Исключения допустимы только для технически неизбежных терминов: API, REST, HTML, iframe, URL, JSON, CSS, JS, PHP, WordPress, shortcode, nonce, slug.

Пользовательские строки будущего интерфейса должны идти через i18n.

Будущий основной i18n-файл:

- assets/i18n/i18n-ru.js

Будущий helper:

- t('key')

Никаких “красивых, но мёртвых” кнопок. Каждый интерактивный элемент должен иметь рабочую цепочку:

DOM/root selector -> selector элемента -> handler -> loading state -> request/action -> success/error render -> UI update.

Deploy по умолчанию запрещён. Будущие zip-релизы собираются только в ./dist/. Предыдущие zip-релизы нельзя удалять.

## Принцип модульной архитектуры

Ядро SONYRA Site Manager не должно становиться монолитным комбайном.

Будущие модули подключаются через Module Registry.

Личный кабинет не встраивается в ядро как основная логика. Он подключается как будущий модуль SONYRA Account или SONYRA Personal Cabinet.

Ядро может содержать только foundation для OTP, account routes и permissions.

## Ограничения модулей

Модуль не может:

- напрямую ломать ядро;
- самовольно создавать публичные маршруты без регистрации;
- добавлять видимые строки вне i18n;
- добавлять английские пользовательские UI-строки;
- подключать случайные стили, обходя design system;
- обходить Permission Service;
- обходить Audit Log для важных действий;
- использовать WordPress Pages как основу публичных страниц;
- использовать shortcode как основу архитектуры страниц;
- использовать старый NDD-код, manager-app.js или manager-v2 как основу.

## Extension Plugin Contract

Начиная с архитектурного lock `0.1.65`, типовой модуль SONYRA Site Platform рассматривается как отдельный WordPress-плагин-расширение.

Правила:

- расширение физически устанавливается на тот же WordPress-сайт, где установлен SONYRA Site Manager;
- код расширения не исполняется удалённо по API;
- удалённый API может отдавать catalog, manifest, license status, update metadata, package URL, checksum, signature и cloud-service endpoints;
- удалённый API не заменяет локальную установку кода;
- установка и обновление будущих расширений должны идти через штатную механику WordPress installer/upgrader;
- zip расширения должен проходить checksum/signature validation в будущей реализации.

Расширения могут регистрировать:

- dashboard cards, setup steps и working metrics в “Главное”;
- модули, состояние, настройки и placement controls в “Модули”;
- группы настроек в “Настройки”;
- типы страниц, секции и блоки в “Страницы”;
- visual presets в “Дизайн”;
- документы, cookie requirements и consent requirements в “Сайт”;
- audit events, consent log entries, health checks и notifications.

Расширения не могут:

- ломать семь базовых разделов;
- добавлять основные пункты меню без отдельного архитектурного решения;
- подменять sidebar или page header;
- подменять базовую дизайн-систему;
- приносить хаотичный UI;
- обходить i18n, Permission Service, Audit Log или security rules;
- хранить секреты в браузере;
- менять core DB напрямую без контракта;
- добавлять hardcoded SVG;
- добавлять английские UI-строки.

## Dashboard Extension Slots

Начиная с `0.1.66`, “Главное” поддерживает dashboard foundation slots для будущих расширений.

Разрешённые filters:

- `sonyra_site_manager_dashboard_setup_steps`;
- `sonyra_site_manager_dashboard_status_cards`;
- `sonyra_site_manager_dashboard_alerts`;
- `sonyra_site_manager_dashboard_metrics`;
- `sonyra_site_manager_dashboard_activity`;
- `sonyra_site_manager_dashboard_mode`;
- `sonyra_site_manager_dashboard_completion`.

Расширение должно возвращать массивы в documented structure и не добавлять fake metrics. Core продолжает работать без расширений.

## Notice Center Extension Slots

Начиная с `0.1.67`, расширения могут публиковать важные notices и promos в единый Manager Notice Center.

Разрешённые filters:

- `sonyra_site_manager_notices`;
- `sonyra_site_manager_notice_types`;
- `sonyra_site_manager_notice_actions`.

Расширение может добавить warning, info, promo или success notice, если output проходит normalization, escaping и не содержит raw HTML.

Расширение не может:

- скрывать critical core errors;
- показывать fake promo без реального источника;
- запускать установку, покупку или external support send через notice;
- обходить i18n и security rules;
- использовать Notice Center вместо раздела “Модули” для полноценной витрины.

## Module Registry

Module Registry должен быть единственной точкой регистрации будущих модулей.

Registry отвечает за:

- регистрацию manifest;
- проверку dependencies;
- регистрацию permissions;
- регистрацию manager routes;
- регистрацию REST endpoints;
- регистрацию page blocks;
- регистрацию i18n-ключей;
- регистрацию assets;
- регистрацию embeds и shortcodes;
- отключение модуля без поломки ядра.

## Manifest format

Будущий manifest модуля должен предусматривать поля:

- id
- name
- version
- description
- status
- dependencies
- permissions
- manager_menu_items
- manager_routes
- dashboard_widgets
- page_blocks
- rest_routes
- assets
- i18n
- settings_schema
- embeds
- shortcodes
- account_routes
- billing_features
- analytics_events

## Security contract

Каждый модуль должен использовать Permission Service для действий, routes, REST endpoints, manager UI и embed/export-сценариев.

Важные действия модуля должны создавать audit events через Audit Log.

Модуль не может регистрировать приватный REST endpoint без nonce/session check, capability check, sanitize input, validate input и escape output.

## SONYRA Auth module

SONYRA Auth является изолированным модулем/группой входа и доступа.

Видимое название будущего интерфейса: “Вход и доступ”.

Ядро даёт permissions, status, audit и database foundation.

Auth module может регистрировать auth routes, auth providers, auth sessions и auth permissions только в своих явно разрешённых этапах.

Auth module не должен смешиваться с page editor, public renderer, embeds или analytics.

Первый канал входа Auth module — email OTP.

Будущие providers: VK, MAX, Яндекс, TOTP и Passkey/WebAuthn.

## UI contract

Каждый UI-элемент модуля должен:

- иметь видимые строки только на русском языке;
- получать пользовательские строки через assets/i18n/i18n-ru.js и t('key');
- соблюдать design system;
- иметь доказанную рабочую цепочку;
- не содержать английские fallback-строки.

## Модульный отчёт

Каждый будущий этап добавления или изменения модуля должен включать отчёт:

- какие routes добавлены;
- какие permissions нужны;
- какие i18n keys добавлены;
- какие assets подключены;
- какие REST endpoints добавлены;
- какие audit events создаются;
- какие UI-цепочки доказаны;
- какие проверки выполнены;
- есть ли STOP-срабатывания.

## STOP-условия

Немедленно остановиться, если модуль требует обхода Module Registry, Permission Service, Audit Log, i18n, design system, security rules или правил русского UI.

## Managed Site Agent module line

Будущие Managed Site Agent modules inside this plugin:

- Connection;
- Status API;
- Diagnostics API;
- License/Access API;
- Command Receiver;
- Heartbeat Sender;
- Agent Audit.

Эти модули относятся только к управляемой стороне конкретного установленного сайта. Они не являются центральным пультом управления сайтами.

Separate Owner Control Plugin modules, not inside this plugin:

- Connected Sites;
- Billing Access;
- License Control;
- Diagnostics Center;
- Remote Commands;
- Update Control;
- Security Events.

Модули отдельного Owner Control Plugin нельзя создавать внутри SONYRA Site Manager. Они требуют отдельного будущего продукта, отдельного scaffold и отдельной линии release/versioning.
