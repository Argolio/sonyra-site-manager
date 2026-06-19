# SONYRA Auth Cookie Contract

## Назначение

Auth cookie transport — отдельный auth transport layer между internal auth session и будущим доступом к manager.

Cookie transport нужен для:

- безопасного хранения auth session token в браузере;
- передачи session token между запросами;
- дальнейшей проверки сессии через Auth Sessions service;
- будущего входа в Пульт сайта после успешного OTP.

Cookie transport не является:

- manager access guard;
- UI;
- REST endpoint;
- WordPress login;
- механизмом открытия `/manager`.

Cookie transport не должен сам открывать `/manager`. Manager access будет отдельным этапом после auth gate/access guard.

## Cookie name

Будущее имя cookie:

`sonyra_site_manager_auth`

Правила:

- имя cookie техническое;
- не использовать разные имена без необходимости;
- не использовать WordPress auth cookie как основной transport Пульта сайта;
- не подменять WordPress login.

## Cookie parameters

Будущая cookie должна ставиться с параметрами:

- `HttpOnly: true`;
- `Secure: true`, если сайт работает по HTTPS;
- `SameSite: Lax` по умолчанию;
- `Path: /`;
- `Expires` / `Max-Age`: 24 часа по умолчанию;
- `Domain`: не задавать без явной необходимости.

Обязательные правила:

- HttpOnly обязателен;
- Secure обязателен на HTTPS;
- SameSite=Lax — базовый безопасный режим;
- SameSite=None запрещён без отдельного этапа и HTTPS;
- срок жизни cookie должен совпадать или быть меньше срока жизни auth session;
- cookie не должна жить дольше записи в `sonyra_auth_sessions`.

## Token rules

Raw session token:

- может существовать только во время установки cookie;
- не пишется в БД;
- не пишется в audit log;
- не пишется в status;
- не пишется в options;
- не пишется в HTML;
- не передаётся в JS;
- не хранится в localStorage;
- не хранится в sessionStorage;
- не логируется.

В БД уже хранится только:

`session_token_hash`

Проверка cookie:

- прочитать raw token из cookie только в cookie/access layer;
- захешировать через Auth Sessions service;
- найти session по `session_token_hash`;
- проверить `status = active`;
- проверить `expires_at`;
- проверить `revoked_at`;
- обновить `last_seen_at`;
- вернуть safe auth context без raw token.

Cookie transport не должен:

- хранить token в localStorage;
- хранить token в sessionStorage;
- передавать token в JS;
- писать token в HTML;
- писать token в REST response после установки cookie;
- логировать token;
- сохранять raw token в options;
- сохранять raw token в audit context;
- выдавать доступ без проверки access guard;
- не выдавать manager access.

## Logout and revoke

Будущий logout должен:

- отозвать session через Auth Sessions service;
- поставить cookie с истёкшим сроком;
- не удалять запись из БД;
- записать audit event `auth.cookie_cleared` или `auth.logout_completed` без raw token.

Будущий revoke должен:

- пометить session revoked;
- не удалять запись;
- не логировать token.

## CSRF and request protection

Cookie transport сам по себе не решает CSRF.

Обязательные будущие правила:

- для state-changing REST actions после входа нужен nonce/CSRF layer;
- cookie authentication не должна позволять выполнять изменения без nonce;
- GET-запросы не должны менять данные;
- REST endpoints входа и действий должны иметь отдельные permission callbacks;
- access guard и REST permissions будут отдельными этапами.

## REST auth связь

- Cookie может быть установлена REST verify-code endpoint после успешного Auth Login.
- Cookie не должна быть доступна JavaScript.
- REST response после установки cookie не возвращает token.
- REST response после установки cookie не возвращает `session_token`.
- REST verify-code не выдаёт manager access сам по себе.
- REST session endpoint должен возвращать safe auth state без raw token.

## Login screen связь

- Cookie is set after verify-code success.
- Login frontend must not receive raw `session_token`.
- Cookie is HttpOnly and not visible to JS.
- Password is not required for cookie issue on first release.
- Frontend не хранит token в localStorage/sessionStorage.

## Future Cookie Transport class

Будущий класс:

`Sonyra_Site_Manager_Auth_Cookie`

Будущие методы:

- `get_cookie_name()`;
- `get_cookie_ttl()`;
- `get_cookie_options()`;
- `set_auth_cookie($session_token, $expires_at)`;
- `clear_auth_cookie()`;
- `get_cookie_token()`;
- `validate_cookie()`;
- `get_cookie_status()`.

На этапе 2.AUTH.9 этот класс не создаётся.

## Audit events

Будущие audit events:

- `auth.cookie_set`;
- `auth.cookie_validated`;
- `auth.cookie_invalid`;
- `auth.cookie_cleared`;
- `auth.logout_completed`.

Audit context не должен содержать:

- raw session token;
- `session_token_hash`;
- raw email;
- raw code;
- OTP;
- token;
- secret;
- nonce;
- cookie;
- authorization;
- api_key;
- raw IP;
- raw user agent.

## Future stages

- Этап 2.AUTH.9 — Auth cookie transport contract.
- Этап 2.AUTH.10 — Auth cookie transport implementation без REST/routes/UI.
- Этап 2.AUTH.11 — Internal auth guard foundation без manager route.
- Этап 2.AUTH.12 — REST auth endpoints contract.
- Этап 2.AUTH.13 — REST auth controller implementation без UI и без `/manager` route.
- Этап 2.AUTH.14 — REST auth endpoint internal smoke helpers без UI.
- Этап 3.0 — Login route/screen contract.
- Этап 3.0.1 — REST request-code identifier extension contract, если потребуется до подключения UI.
- Этап 3.1 — Login route foundation без visual UI.
- Этап 3.2 — First visual login shell CSS/HTML без подключения REST.
- Этап 3.3 — Browser visual QA login shell.
- Этап 3.4 — Extend REST request-code to identifier, если ещё не сделано.
- Этап 3.5 — Connect login shell to REST auth endpoints.
- Этап 3.6 — Login shell functional QA.
- Этап 3.7 — Manager access gate через OTP session.
- Этап 3.8 — First protected `/manager` shell placeholder.
