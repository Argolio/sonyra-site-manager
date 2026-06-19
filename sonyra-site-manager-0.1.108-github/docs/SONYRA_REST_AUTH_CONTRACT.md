# SONYRA REST Auth Contract

## Назначение

REST auth endpoints — транспортный API-слой между будущим login UI и внутренними auth-сервисами Пульта сайта.

REST auth endpoints используют внутренние сервисы:

- `Sonyra_Site_Manager_OTP_Request`;
- `Sonyra_Site_Manager_OTP_Verify`;
- `Sonyra_Site_Manager_Auth_Login`;
- `Sonyra_Site_Manager_Auth_Sessions`;
- `Sonyra_Site_Manager_Auth_Cookie`;
- `Sonyra_Site_Manager_Auth_Access_Guard`.

REST auth endpoints не являются:

- manager UI;
- `/manager` route;
- wp-admin menu;
- заменой access guard;
- WordPress login;
- механизмом выдачи manager access.

REST auth endpoints не должны:

- раскрывать существование email;
- возвращать raw session token в JSON;
- возвращать raw OTP code;
- возвращать `email_hash`;
- открывать `/manager`;
- создавать UI;
- делать redirect;
- выдавать manager access сами по себе.

Все видимые response messages должны быть на русском языке. Английские пользовательские сообщения запрещены.

## Namespace

Будущий REST namespace:

`sonyra-site-manager/v1`

Запрещено:

- использовать `ndd` namespace;
- использовать `manager-v2` namespace;
- использовать generic namespace без `sonyra-site-manager`;
- создавать альтернативные namespace без отдельного этапа.

## Endpoints

Будущие endpoints:

- `POST /wp-json/sonyra-site-manager/v1/auth/request-code`;
- `POST /wp-json/sonyra-site-manager/v1/auth/verify-code`;
- `POST /wp-json/sonyra-site-manager/v1/auth/logout`;
- `GET /wp-json/sonyra-site-manager/v1/auth/session`.

На этапе 2.AUTH.12 endpoints только документируются. Код не создаётся.

## POST /auth/request-code

Назначение:

Запросить email OTP code.

Input:

- `email`: string, required.

Validation:

- `email` required;
- email normalize via Auth Identities / OTP Request service;
- invalid format returns neutral safe response;
- do not reveal whether identity exists;
- rate limits handled by OTP Request service.

Permission callback:

- public endpoint allowed;
- must not require WordPress login;
- must not reveal identities;
- must not create WordPress user;
- must not use wp-login as main auth.

Internal service:

- `Sonyra_Site_Manager_OTP_Request::request_login_code()`.

Success/failure public response must be neutral:

```json
{
  "success": true,
  "message": "Если доступ разрешён, код будет отправлен на указанную почту.",
  "next_step": "Введите код из письма."
}
```

Forbidden response fields:

- `exists`;
- `user_exists`;
- `identity_exists`;
- raw email;
- `email_hash`;
- OTP code;
- challenge internal data;
- attempts detail;
- `locked_until`;
- provider secret;
- debug data.

Future identifier extension:

- REST auth endpoints are consumed by future `/manager/login` screen.
- Future request-code target input is `identifier`.
- `email` remains backward-compatible alias until implementation stage.
- Перед подключением login shell к REST request-code должен принимать электронную почту или логин без enumeration.
- Response messages must match login screen contract.
- No manager access from REST auth alone.
- No `session_token` in JSON.
- No email/login enumeration.

## POST /auth/verify-code

Назначение:

Проверить OTP code и установить auth cookie.

Input:

- `challenge_uuid`: string, required;
- `code`: string, required.

Validation:

- `challenge_uuid` required;
- `code` required;
- code numeric/string 6 digits;
- no raw code logging;
- failed response does not reveal internal reason.

Internal services:

- `Sonyra_Site_Manager_Auth_Login::complete_login_with_otp()`;
- `Sonyra_Site_Manager_Auth_Cookie::set_auth_cookie()`.

Success behavior:

- verify OTP;
- create session;
- set HttpOnly cookie;
- do not return `session_token` in JSON;
- не возвращать session_token в JSON;
- do not grant manager access;
- do not redirect.

Success response:

```json
{
  "success": true,
  "authenticated": true,
  "access_granted": false,
  "message": "Вход подтверждён.",
  "next_step": "Переход к Пульту сайта будет доступен после проверки доступа."
}
```

Failure response:

```json
{
  "success": true,
  "authenticated": false,
  "access_granted": false,
  "message": "Вход не подтверждён. Проверьте код и попробуйте ещё раз."
}
```

Forbidden response fields:

- `session_token`;
- `session_token_hash`;
- raw code;
- `code_hash`;
- raw email;
- `email_hash`;
- internal reason;
- attempts detail;
- `locked_until`;
- cookie value;
- authorization;
- debug data.

## POST /auth/logout

Назначение:

Выйти из Пульта сайта.

Input:

- no required public input.

Internal service:

- `Sonyra_Site_Manager_Auth_Cookie::revoke_cookie_session()`.

Behavior:

- read auth cookie through Auth Cookie service;
- revoke session if token exists;
- clear cookie;
- do not reveal token;
- do not fail loudly if no cookie exists;
- return safe Russian response.

Response:

```json
{
  "success": true,
  "message": "Вы вышли из Пульта сайта."
}
```

Security:

- state-changing endpoint;
- must use nonce/CSRF protection when called from authenticated UI;
- must allow safe idempotent logout behavior;
- must not expose token.

## GET /auth/session

Назначение:

Проверить текущую auth session для будущего UI.

Internal services:

- `Sonyra_Site_Manager_Auth_Cookie::validate_cookie()`;
- `Sonyra_Site_Manager_Auth_Access_Guard::can_access_manager()`.

Behavior:

- read cookie via Auth Cookie service;
- validate session;
- compute internal guard decision;
- do not redirect;
- do not create route access;
- do not create manager UI;
- return safe user/session state.

Unauthenticated response:

```json
{
  "success": true,
  "authenticated": false,
  "access_granted": false,
  "message": "Вход не выполнен."
}
```

Authenticated response:

```json
{
  "success": true,
  "authenticated": true,
  "access_granted": false,
  "message": "Вход выполнен.",
  "role": "viewer"
}
```

Important:

- `access_granted` remains false until future `/manager` access gate stage;
- role can be returned only as safe role key or Russian role title if already defined;
- do not return `email_hash`;
- do not return raw email;
- do not return token;
- "Доступ не разрешён." is the safe access-not-granted message.

## Permission callbacks

`request-code`:

- public;
- no WordPress login required;
- anti-enumeration required;
- rate-limit handled by internal service.

`verify-code`:

- public;
- no WordPress login required;
- must not return token;
- sets HttpOnly cookie on success.

`logout`:

- requires CSRF/nonce if called from logged UI;
- must be idempotent;
- must not reveal auth details.

`session`:

- public safe read;
- must not reveal sensitive identity details;
- must return only safe auth state.

## CSRF / nonce rules

- `request-code` and `verify-code` are public auth endpoints, but must include anti-abuse/rate-limit logic through internal services.
- `logout` is state-changing and must have nonce/CSRF protection in UI flow.
- Future manager REST actions must require nonce/CSRF and access guard.
- GET endpoints must not mutate state.
- REST nonce must not be used as replacement for Auth session.
- Auth cookie must not be exposed to JavaScript.
- Nonces must not be logged.

## Response language rules

Allowed visible response messages:

- "Если доступ разрешён, код будет отправлен на указанную почту."
- "Введите код из письма."
- "Вход подтверждён."
- "Вход не подтверждён. Проверьте код и попробуйте ещё раз."
- "Вы вышли из Пульта сайта."
- "Вход не выполнен."
- "Вход выполнен."
- "Доступ не разрешён."

Forbidden visible response messages:

- "Login successful";
- "Invalid code";
- "User not found";
- "Unauthorized";
- "Forbidden";
- "Token expired";
- any English visible UI/response message.

## Audit events

Будущие REST audit events:

- `auth.rest_request_code`;
- `auth.rest_verify_code`;
- `auth.rest_logout`;
- `auth.rest_session_checked`;
- `auth.rest_denied`;
- `auth.rest_error`.

Audit context не должен содержать:

- raw session token;
- `session_token_hash`;
- raw email;
- `email_hash`;
- raw code;
- `code_hash`;
- OTP;
- token;
- secret;
- nonce;
- cookie;
- authorization;
- api_key;
- raw IP;
- raw user agent.

## Future REST controller class

Будущий класс:

`Sonyra_Site_Manager_REST_Auth_Controller`

Будущие методы:

- `register_routes()`;
- `permission_public()`;
- `permission_logout()`;
- `request_code()`;
- `verify_code()`;
- `logout()`;
- `session()`;
- `build_response()`;
- `sanitize_email_input()`;
- `sanitize_code_input()`;
- `sanitize_challenge_uuid()`.

На этапе 2.AUTH.12 этот класс не создаётся.

## Implementation stage sequence

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

## STOP-условия

Немедленно остановиться, если REST auth этап требует:

- раскрыть существование email;
- вернуть `session_token` в JSON;
- вернуть raw OTP code;
- вернуть `email_hash`;
- сделать WordPress login основным входом;
- выдать manager access из REST auth endpoint;
- создать `/manager` route;
- создать manager UI;
- создать wp-admin menu;
- добавить английские видимые response messages.
