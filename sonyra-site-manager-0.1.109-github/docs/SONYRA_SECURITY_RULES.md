# SONYRA Security Rules

## Manager access rules

Manager SONYRA Site Manager должен быть закрыт от гостей и пользователей без прав.

Доступ к manager-интерфейсу разрешается только авторизованным пользователям с подходящей capability.

Отказ доступа должен быть обработан безопасно, без раскрытия секретов, и видимый пользовательский интерфейс отказа должен быть только на русском языке.

## Capability foundation

Будущая модель прав должна учитывать роли:

- owner;
- admin;
- editor;
- moderator;
- viewer.

Каждое действие manager, REST endpoint, diagnostics, export/embed-настройка и изменение публичного рендера должны иметь явную capability foundation.

## REST nonce rules

Все будущие приватные REST endpoints должны иметь:

- nonce/session check;
- capability check;
- sanitize input;
- validate input;
- escape output;
- audit log для важных действий;
- запрет доступа гостям, если endpoint не является публичным по контракту.

REST permissions callbacks обязательны для каждого endpoint.

## Sanitize, validate, escape

Правило обработки данных:

- sanitize input перед использованием;
- validate input перед сохранением или действием;
- escape output перед выводом;
- не доверять данным из REST, query, post body, meta, options, embed params или iframe params.

## Database security

Правила будущего database foundation:

- sanitize перед insert/update;
- raw SQL выполнять через prepare там, где есть данные пользователя или динамические значения;
- escape делать только на output;
- секрет нельзя хранить в plain options или tables;
- лишние персональные данные нельзя хранить;
- открытые tokens, cookies, passwords, nonces и API keys запрещены;
- прямые user-controlled SQL fragments запрещены.

## Audit log

Audit log обязателен для важных действий:

- изменение настроек;
- изменение страниц;
- публикация;
- удаление;
- изменение маршрутов;
- изменение embed/iframe-настроек;
- изменение прав доступа;
- security-denied события;
- release-sensitive действия.

Audit log не должен хранить секреты, nonce, пароли, токены в открытом виде или приватные payload без необходимости.

## Denied access events

Отказы доступа должны фиксироваться как security events, если это важно для диагностики или защиты.

Denied access events должны различать:

- guest access;
- authenticated user without capability;
- invalid nonce;
- expired session;
- forbidden route;
- invalid embed token;
- disallowed domain.

## Public/private route distinction

Каждый route должен иметь явный статус:

- public по контракту;
- private для manager/API;
- embed-only;
- iframe-only;
- internal diagnostics.

Гостевой доступ запрещён ко всем private routes.

## Embed token foundation

Будущие embed-сценарии должны иметь token foundation:

- токен не должен раскрывать секреты;
- токен должен иметь понятный scope;
- token validation обязательна для приватных или ограниченных embed-сценариев;
- invalid token должен давать безопасный отказ;
- видимый отказ должен быть на русском языке.

## iframe security foundation

Будущие iframe-сценарии должны учитывать:

- allowed domains foundation;
- frame headers;
- sandbox policy, если применимо;
- origin validation;
- безопасный обмен сообщениями;
- запрет раскрытия приватных данных;
- русские видимые сообщения об ошибках.

## Allowed domains foundation

Для embed и iframe должен быть предусмотрен список разрешённых доменов.

Если домен не разрешён, доступ должен быть отклонён, событие должно быть зафиксировано, а видимое сообщение должно быть на русском языке.

## Rate limit foundation for future OTP

Будущие OTP, login, recovery или sensitive endpoints должны иметь rate limit foundation.

Rate limit должен учитывать:

- пользователя;
- IP;
- route;
- действие;
- временное окно;
- audit log для превышений.

## Private updater security

Будущий private updater должен иметь package checksum verification.

Signed manifest plan обязателен до внедрения автоматического обновления.

Разрешённый update domain должен быть явно задан.

Unsafe package URL запрещён.

Update install без validation запрещён.

Если update server unavailable, плагин должен завершать проверку gracefully и без fatal error.

Недоступность https://updates.dobromap.ru/public/index.php?action=check не должна ломать сайт.

## Auth/OTP security

Будущий Auth/OTP foundation должен соблюдать:

- OTP hash only;
- no OTP logs;
- no user enumeration;
- rate limits;
- lockouts;
- replay protection;
- session token hash;
- trusted device token hash;
- audit events without secrets.

Открытый OTP нельзя хранить в БД, options, cookies, localStorage, sessionStorage, audit log или REST responses.

Auth audit context должен маскировать code, otp, token, secret, password, nonce, cookie, authorization и api_key.

## Auth cookie security

- HttpOnly required.
- Secure on HTTPS required.
- SameSite=Lax default.
- SameSite=None запрещён без отдельного этапа и HTTPS.
- Auth token нельзя хранить в localStorage/sessionStorage.
- Auth token нельзя передавать в JS.
- Auth token нельзя писать в HTML.
- Auth token нельзя писать в logs.
- Auth token нельзя писать в audit.
- Auth token нельзя писать в status.
- Session validation должна идти через hash only и Auth Sessions service.
- Cookie lifetime должна быть меньше или равна session lifetime.
- Logout должен очищать cookie и revoke session.
- State-changing actions require nonce/CSRF layer.
- Cookie transport не является access guard и не выдаёт manager access сам по себе.

## REST auth security

- REST auth request-code должен использовать anti-enumeration.
- REST auth request-code должен возвращать neutral response без раскрытия identity.
- REST auth verify-code должен не возвращать session_token в JSON.
- REST auth verify-code не должен возвращать raw session token в JSON.
- REST auth verify-code не должен возвращать raw code в JSON.
- REST auth responses не должны возвращать `email_hash`.
- REST auth verify-code может ставить HttpOnly cookie только после успешного Auth Login.
- REST auth logout должен очищать cookie и revoke session.
- State-changing REST actions require nonce/CSRF layer.
- Access guard проверяется отдельно от REST auth.
- REST auth alone не выдаёт manager access.
- REST nonce не заменяет Auth session.
- Nonce, token, cookie, authorization, raw IP и raw user agent нельзя писать в audit context.

## Login screen security

- Login UI не раскрывает email/login existence.
- Первое поле — “Электронная почта или логин”.
- Пароль не используется на первом релизе.
- Frontend не получает `session_token`.
- Frontend не хранит token в localStorage/sessionStorage.
- Frontend не пишет token в HTML.
- Errors are neutral и не раскрывают identity.
- REST auth handles cookie.
- Manager access gate separate.
- `wp-login` не используется как main product auth.

## Managed Site Agent security

Будущий Managed Site Agent может открывать только контролируемый managed-site API локального плагина. Центральный owner plugin is separate и не создаётся внутри SONYRA Site Manager.

Правила безопасности будущего Remote Control API:

- signed communication обязательна;
- allowlist commands only;
- no arbitrary PHP execution;
- no arbitrary SQL execution;
- no customer sales data collection;
- no customer personal data collection;
- no secrets in telemetry;
- audit all commands;
- replay protection обязательна;
- revocation support обязателен;
- unsigned command rejected;
- unknown command rejected;
- dangerous command rejected.

Remote telemetry не должна включать продажи клиента, персональные данные клиентов сайта, raw tokens, raw auth sessions, cookie пользователей, содержимое форм, содержимое сообщений, заказы, платежи или произвольные таблицы.

Запрещено добавлять произвольное выполнение SQL, произвольное чтение файлов, произвольное чтение таблиц или payload, который исполняется как код.

## Extension And Central Support Security

Начиная с архитектурного lock `0.1.65`, расширения SONYRA считаются локальными WordPress-плагинами.

Правила:

- код расширений не исполняется удалённо по API;
- удалённый API не заменяет локальную установку кода;
- package zip должен проходить checksum/signature validation в будущей реализации;
- расширения не могут обходить Permission Service, Audit Log, i18n и security rules;
- расширения не могут хранить секреты в браузере;
- расширения не могут менять core DB напрямую без отдельного контракта;
- центральная система сопровождения получает только технический и лицензионный минимум;
- центральная система сопровождения не получает контент сайта, заявки клиентов, персональные данные клиентов или полный журнал согласий.
