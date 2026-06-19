# SONYRA Site Manager Rulebook

## Назначение проекта

SONYRA Site Manager — первый базовый WordPress-плагин платформы SONYRA Site Platform.

Плагин создаётся как новый продукт. Он не наследует старый код, старую архитектуру, старые менеджеры, NDD-код, manager-app.js, manager-v2 или любые legacy-фрагменты из других проектов.

## Канонические названия

Допустимые названия:

- SONYRA
- SONYRA Site Platform
- SONYRA Site Manager
- SONYRA STUDIO

Техническое имя плагина:

- sonyra-site-manager

Главный файл будущего плагина:

- sonyra-site-manager.php

Запрещены любые неканонические варианты написания бренда.

Такие варианты нельзя использовать как рабочее название проекта, интерфейса, бренда, пакета, файла или документа.

## Коммерческое название и юридико-брендовая модель

Видимое название плагина:

- Пульт сайта

Платформа:

- SONYRA Site Platform

Издатель / бренд-производитель:

- SONYRA STUDIO

Правообладатель / лицензиар / продавец:

- ИП Катаев С.А. / IPKTFSA

Техническое имя:

- sonyra-site-manager

Главный файл:

- sonyra-site-manager.php

Text Domain:

- sonyra-site-manager

Видимое название плагина должно быть на русском языке.

Платформу и издателя нужно писать только канонически: SONYRA Site Platform и SONYRA STUDIO.

Неканонические варианты названия SONYRA нельзя использовать как рабочие названия.

## Архитектурный принцип

SONYRA Site Manager — самостоятельный WordPress-плагин с собственным manager-интерфейсом, собственными маршрутами, собственной моделью страниц и собственным публичным рендером.

WordPress используется как платформа, storage, авторизация и API-окружение. Архитектура плагина должна быть модульной, проверяемой и расширяемой маленькими безопасными этапами.

## Архитектурные запреты

Запрещено:

- использовать WordPress Pages как основу публичных страниц;
- использовать шорткоды как основу архитектуры страниц;
- строить всё вокруг одного большого manager-app.js;
- делать монолитный фронтенд;
- создавать технические заглушки вместо рабочих цепочек;
- делать большие смешанные патчи;
- переносить старый NDD-код;
- использовать legacy manager-v2 как основу;
- использовать manager-app.js как основу;
- смешивать независимые задачи в одном этапе.

Разрешено:

- использовать WordPress как платформу, storage, авторизацию и API-окружение;
- использовать шорткоды только как export/embed-слой;
- использовать iframe и HTML-вставки только как export/embed-слой;
- использовать модульную архитектуру;
- делать маленькие безопасные этапы с отдельной проверкой результата.

## Русский пользовательский интерфейс

Весь видимый пользовательский интерфейс должен быть только на русском языке.

Это правило распространяется на manager-интерфейс, публичные страницы, embed-интерфейс, iframe-интерфейс, кнопки, меню, вкладки, карточки, заголовки, описания, подсказки, ошибки, пустые состояния, загрузочные состояния, успешные состояния, предупреждения, модальные окна, drawer/panel-интерфейсы, toast-уведомления, экраны доступа и видимые пользователю экраны диагностики.

Запрещено оставлять в видимой части интерфейса английские слова, англицизмы, английские fallback-строки и строки вида loading, error, success, save, cancel, submit, settings, dashboard, pages, preview, publish, undefined, coming soon, TODO.

Исключения допустимы только для технически неизбежных терминов и общепринятых сокращений: API, REST, HTML, iframe, URL, JSON, CSS, JS, PHP, WordPress, shortcode, nonce, slug. Эти термины нельзя использовать как ленивую замену русскому интерфейсу.

## i18n

Все пользовательские UI-строки должны идти только через i18n.

Базовый будущий файл:

- assets/i18n/i18n-ru.js

Базовый будущий helper:

- t('key')

Запрещено:

- писать русские UI-строки напрямую в PHP;
- писать русские UI-строки напрямую в JS;
- писать русские UI-строки напрямую в HTML templates;
- оставлять английские fallback-строки в пользовательском интерфейсе.

Разрешено:

- технические ключи на английском;
- имена файлов на английском;
- имена функций, классов и методов на английском;
- технические логи на английском, если они не видны обычному пользователю.

## Рабочие UI-цепочки

Никакая кнопка, форма, переключатель, вкладка, карточка действия, модальное окно или dropdown не считается готовыми, если не доказана полная рабочая цепочка:

DOM/root selector -> selector элемента -> JS handler или delegated handler -> preventDefault, если нужно -> loading state -> request/action -> nonce/capability/security, если нужно -> success render -> error render -> recovery/copy debug, если нужно -> финальное обновление интерфейса.

Красивый, но нерабочий UI запрещён.

## Версии

Версия должна быть синхронизирована во всех будущих местах:

- plugin header;
- SONYRA_SITE_MANAGER_VERSION;
- asset version;
- REST bootstrap;
- manager footer/about;
- diagnostics;
- version log;
- release checklist.

Если версия не совпадает — STOP.

## Release workflow через dist/

Основной способ передачи результата пользователю — zip-релиз.

Будущие архивы SONYRA Site Manager должны собираться строго в ./dist/.

Имя будущего архива должно иметь формат:

- sonyra-site-manager-X.X.X.zip

Предыдущие архивы в ./dist/ нельзя удалять. Каждый zip является точкой отката.

Пользователь устанавливает zip вручную через WordPress и после установки проверяет работу руками.

Если ручная проверка плохая, рабочий путь отката — установка предыдущего zip-релиза из ./dist/.

Автоматический deploy запрещён как основной путь доставки, пока пользователь явно не разрешит обратное. Deploy нельзя выполнять внутри обычных Codex-этапов.

Git полезен, но не является обязательным условием workflow на старте. Zip-релизы и синхронизированные версии обязательны.

## Private updater

updates.dobromap.ru — будущий update-сервер для приватных обновлений.

Будущий endpoint:

- https://updates.dobromap.ru/public/index.php?action=check

Updater внедряется отдельной безопасной линией.

Ручной zip через ./dist/ остаётся базовым безопасным способом установки и отката на ранних этапах.

Автоматическое обновление нельзя внедрять без checksum verification, signed manifest plan и license/update integration plan.

Update UI не должен создаваться отдельным пунктом меню WordPress.

Update UI должен быть только на русском языке.

## Database schema contract

Database schema сначала документируется в `docs/SONYRA_DATABASE_SCHEMA.md`.

Таблицы создаются только отдельным явно разрешённым database implementation этапом.

Destructive migrations запрещены без отдельного явного destructive этапа.

Запрещено смешивать DB creation с manager UI, REST endpoints, routes, admin pages или wp-admin menu.

## Auth/OTP как обязательный входной слой

Основной UX входа в Пульт сайта должен идти через входную группу “Вход и доступ”.

Первый канал входа — email OTP.

WordPress administrator является trusted/recovery source, но не основным UX входа в Пульт сайта.

Manager UI нельзя считать готовым без Auth/OTP foundation.

Нельзя делать `/manager` как открытую страницу без auth gate.

Нельзя делать основной вход через обычный wp-login.

Все видимые строки входа должны быть только на русском языке.

## Auth cookie transport

Auth cookie transport будет отдельным слоем после OTP session issue.

Cookie transport не заменяет access guard и не должен сам выдавать manager access.

Cookie transport не должен использовать localStorage или sessionStorage.

Cookie transport не должен передавать auth token в JS, HTML, audit, status или logs.

Auth cookie должна быть HttpOnly.

Auth cookie должна быть Secure на HTTPS.

Auth cookie должна использовать SameSite=Lax по умолчанию.

Auth cookie не должна выдавать manager access без отдельного access guard.

## REST auth endpoints

REST auth endpoints — будущий транспортный API-слой для login UI, а не manager UI и не `/manager` route.

Канонический namespace:

- `sonyra-site-manager/v1`

Будущие endpoints:

- `POST /wp-json/sonyra-site-manager/v1/auth/request-code`;
- `POST /wp-json/sonyra-site-manager/v1/auth/verify-code`;
- `POST /wp-json/sonyra-site-manager/v1/auth/logout`;
- `GET /wp-json/sonyra-site-manager/v1/auth/session`.

REST auth layer не создаёт manager UI, не создаёт `/manager` route, не создаёт wp-admin menu и не заменяет access guard.

REST auth responses должны быть только на русском языке.

REST auth layer не должен возвращать raw token, raw code, raw email, `email_hash`, `session_token` или debug data.

REST auth layer не должен выдавать manager access сам по себе.

## Login screen and route

Вход в Пульт сайта должен идти через будущий route `/manager/login`.

Login screen не является `wp-login`, wp-admin page, shortcode, WordPress Page, `/manager` или manager shell.

Первый релиз login screen — passwordless.

Правила:

- пароль не используется;
- одно поле называется “Электронная почта или логин”;
- identifier может быть email или login;
- после отправки identifier показывается экран кода;
- все UI-строки только на русском;
- token/code/email/login leakage запрещены;
- `wp-login` не используется как основной вход;
- `/manager` открывается только через будущий access gate;
- visual QA обязателен перед подключением сложной логики.

## Manager UI standard

Protected `/manager` должен использовать единый section metadata source для nav, page header и route switching.

Правила:

- новые разделы менеджера нельзя добавлять через разрозненные массивы nav/view/header;
- во всех разделах уже есть общий page header; второй header/hero внутри рабочей области запрещён;
- page header должен иметь icon tile, kicker, title, description, optional meta/chips row и optional actions-zone;
- page header не должен использовать brand name “ПУЛЬТ САЙТА” как повторяющийся kicker;
- быстрые actions раздела размещаются справа в существующем page header, а не во втором header;
- actions должны быть реальными: нельзя добавлять кнопку без реализованного действия;
- бейджи не являются actions и размещаются по смыслу: section badges — в meta/chips row header только на реальных данных, item badges — рядом с соответствующим элементом;
- ошибки, warnings и success-сообщения идут через Notice Center, inline alert или field-level validation, а не через декоративные badges;
- fake badges, fake metrics, fake progress и fake status запрещены;
- SVG icon tile выравнивается по зоне “kicker + title”, description не должен опускать SVG;
- header остаётся компактным и не превращается в hero;
- sidebar invariant rail из версии `0.1.63` нельзя менять без отдельного visual audit;
- SVG icons в manager UI выводятся через SONYRA Icon Renderer и `icon_key` из manifest;
- подробный visual contract закреплён в `docs/SONYRA_DESIGN_SYSTEM.md`.

## Base Architecture And Extension Lock

Начиная с версии `0.1.65`, актуальный план SONYRA Site Manager фиксируется заново.

Старый план, где почти всё считалось готовым после sidebar/header polishing, больше не является актуальным. Текущий прогресс нужно считать примерно `67% > 33%`: foundation заметно продвинут, но базовый dashboard, разделы, расширения, marketplace contracts, licensing, health и billing boundaries ещё требуют отдельных этапов.

`0.1.65` является архитектурным lock для:

- базового управляющего WordPress-плагина;
- будущих расширений как отдельных WordPress-плагинов;
- семи постоянных manager sections;
- repository/marketplace contract;
- центральной системы сопровождения, мониторинга, лицензий и обновлений.

SONYRA Site Manager может работать самостоятельно как простой сайт-визитка или лендинг. Типовые сайты должны реализовываться расширениями, а не разрастанием core в вертикальный продукт.

Этап `0.1.66` — Главное Dashboard Foundation: setup dashboard, working dashboard foundation, dashboard extension slots и hidden completion modal foundation без fake analytics.

Этап `0.1.67` — Manager Notice Center Foundation: единая зона важных сообщений под page header, current logout error как notice, notice filters для будущих расширений, hidden support modal foundation и contract без external API, support send, DB writes и fake promo.

Этап `0.1.68` — Сайт Foundation Pack: первый реальный экран раздела “Сайт” на постоянном route `setup`, public foundation cards, документы, cookie, шапка, подвал, SEO и readiness checklist без DB writes, public rendering, REST save endpoint, публикации и fake states.

Следующий этап после `0.1.68`: уточняется отдельным prompt после ручной проверки раздела “Сайт”.

## Extension Architecture Rules

Расширения SONYRA Site Platform устанавливаются физически как обычные WordPress-плагины на тот же сайт, где установлен SONYRA Site Manager.

Правила:

- код расширений не исполняется удалённо по API;
- удалённый API может отдавать catalog, manifest, license status, updates, package URL, checksum, signature и compatibility metadata;
- витрина расширений находится внутри раздела “Модули”;
- `updates.dobromap.ru` является техническим приватным репозиторием, сервером обновлений, лицензий и пакетов;
- расширения добавляют blocks/cards/settings/events внутрь семи базовых разделов;
- расширения не ломают меню, sidebar, page header и дизайн-систему базы;
- центральная система сопровождения является отдельным будущим слоем для мониторинга, лицензий, обновлений и технического health.

## STOP-условия

Немедленно остановиться и не продолжать, если:

- требуется изменить что-то вне разрешённого набора файлов этапа;
- нужно создать код плагина до этапа scaffold;
- нужно создать scaffold вне утверждённого этапа;
- невозможно создать обязательные документы;
- обнаружена попытка использовать старый NDD-код;
- обнаружена попытка использовать manager-app.js или manager-v2 как основу;
- документы сохраняются не в UTF-8 без BOM;
- появились файлы, не входящие в разрешённый список;
- Codex не может подтвердить, что изменял только разрешённые файлы;
- Codex не может дать финальный отчёт;
- проверки этапа не прошли.

## Managed Site Agent / удалённое управление этим плагином

SONYRA Site Manager не содержит центральный пульт управления сайтами. Этот плагин устанавливается на конкретный сайт и в будущем может содержать только управляемую сторону: Управляемый сайт / Managed Site Agent.

Центральный Пульт управления сайтами будет отдельным будущим плагином или модулем на управляющем сайте владельца платформы. Его нельзя встраивать в SONYRA Site Manager, смешивать с login route, visual login shell или manager shell.

Будущие API-методы Managed Site Agent нужны только для безопасного подключения и управления этим установленным плагином через отдельный центральный пульт. Remote Control API должен быть реализован отдельными PLATFORM/AGENT-этапами и не должен появляться как побочный эффект auth, login, manager или billing-этапов.

Правила будущего удалённого управления:

- remote commands только allowlist;
- no arbitrary code execution;
- no arbitrary SQL execution;
- no customer sales data collection;
- no customer personal data collection;
- все команды должны быть подписаны;
- все команды должны иметь срок действия и replay protection;
- all commands audited локально и на центральной стороне;
- telemetry не должна содержать secrets, продажи клиента или персональные данные клиента.
