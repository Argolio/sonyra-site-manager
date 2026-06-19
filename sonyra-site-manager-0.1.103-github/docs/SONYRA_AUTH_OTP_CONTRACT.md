# SONYRA Auth/OTP Contract

## Назначение

Этот документ фиксирует контракт входа и OTP для “Пульт сайта” / SONYRA Site Manager перед созданием manager UI.

На этапе 2.AUTH.0 Auth/OTP только документируется. PHP-код, таблицы, REST endpoints, routes, manager UI, wp-admin menu, version bump и zip не создаются.

## Архитектурное решение

Вход в Пульт сайта делается через отдельную входную группу/модуль.

- Видимое название в интерфейсе: “Вход и доступ”.
- Архитектурное название: SONYRA Auth.
- Первый основной канал входа: email OTP.
- Вход не является частью монолитного ядра.
- Ядро может дать только foundation: database, status, audit, permissions, module contract.
- UI входа будет создан отдельным этапом.
- REST endpoints входа будут созданы отдельным этапом.
- Routes входа будут созданы отдельным этапом.
- Manager UI нельзя делать до принятия Auth/OTP contract.

Вход через обычную WordPress-админку не должен становиться основным пользовательским UX входа в Пульт сайта.

## Первый канал входа

Первый канал входа:

- email OTP.

Пользователь вводит:

- email.

Система:

- не раскрывает, существует ли пользователь;
- не пишет “пользователь не найден”;
- не пишет “такой email не зарегистрирован”;
- возвращает нейтральное сообщение будущего UI: “Если доступ разрешён, код будет отправлен на указанную почту.”

Все видимые сообщения входа должны быть только на русском языке.

В пользовательском UI термин OTP не используется. Нужно писать:

- “код входа”;
- “одноразовый код”;
- “код из письма”.

## Источники пользователей

### WordPress users

WordPress administrator может быть:

- owner;
- главным администратором Пульта сайта;
- recovery-доверенным пользователем.

Но основной UX входа в Пульт сайта всё равно должен идти через OTP.

WordPress administrator не должен автоматически обходить весь Auth/OTP flow в пользовательском UX.

Recovery-доступ должен быть отдельным будущим сценарием, а не скрытым обходом.

### Internal SONYRA identities

Внутренние пользователи Пульта сайта должны храниться отдельно от WordPress users.

Они могут иметь:

- email;
- роль;
- статус;
- связь с WordPress user_id, если есть;
- будущие внешние provider identities.

## Роли Пульта сайта

Минимальные роли:

- Главный администратор;
- Администратор;
- Редактор;
- Участник;
- Наблюдатель.

Правила:

- Главный администратор может назначать роли.
- Администратор может назначать роли только в разрешённых пределах.
- Пользователь без активной роли не должен входить в Пульт сайта.
- WordPress administrator не должен автоматически обходить Auth/OTP flow в пользовательском UX.
- Recovery-доступ должен быть отдельным будущим сценарием.

## OTP policy

Минимальная политика email OTP:

- код: 6 цифр;
- срок жизни: 5 минут;
- код одноразовый;
- код хранится только в hash;
- открытый код в БД не хранить;
- после успешного входа код помечается использованным;
- повторная отправка не чаще 1 раза в 60 секунд;
- максимум 3 отправки за 15 минут;
- максимум 5 попыток ввода на один challenge;
- после 5 неправильных попыток lockout на 15 минут;
- повторные lockout могут увеличиваться: 30 минут, 60 минут;
- блокировка должна учитывать email, IP и device fingerprint foundation;
- сообщение об ошибке не должно помогать перебирать email;
- OTP нельзя логировать в audit log;
- OTP нельзя отправлять обратно в REST response;
- OTP нельзя хранить в cookies, localStorage или sessionStorage.

## Provider contract

Архитектура должна поддерживать общий provider contract:

- Auth Provider;
- OTP Channel;
- Identity Provider.

Первый provider:

- email.

Будущие providers:

- VK ID / VK;
- MAX;
- Яндекс ID;
- TOTP-приложение;
- Passkey/WebAuthn.

Email OTP не должен быть захардкожен как единственный вечный способ входа.

Архитектура должна позволить добавить внешние providers без переписывания ядра.

## Future Auth/OTP schema extension

Эти таблицы описаны только как будущая DB extension. На этапе 2.AUTH.0 они не создаются.

Будущие логические таблицы:

- `sonyra_auth_identities`;
- `sonyra_auth_otp_challenges`;
- `sonyra_auth_sessions`;
- `sonyra_auth_attempts`;
- `sonyra_auth_trusted_devices`;
- `sonyra_auth_providers`.

Физические имена должны использовать `$wpdb->prefix`:

- `{$wpdb->prefix}sonyra_auth_identities`;
- `{$wpdb->prefix}sonyra_auth_otp_challenges`;
- `{$wpdb->prefix}sonyra_auth_sessions`;
- `{$wpdb->prefix}sonyra_auth_attempts`;
- `{$wpdb->prefix}sonyra_auth_trusted_devices`;
- `{$wpdb->prefix}sonyra_auth_providers`.

Назначение:

- `sonyra_auth_identities` — внутренние личности пользователей Пульта сайта, email, связь с WordPress user_id, статус, роль, provider links.
- `sonyra_auth_otp_challenges` — OTP challenge, email hash, code hash, expires_at, attempts_count, used_at, status.
- `sonyra_auth_sessions` — сессии входа в Пульт сайта, session token hash, user identity, expires_at, revoked_at.
- `sonyra_auth_attempts` — журнал попыток, rate limit, lockout, IP hash, user agent hash.
- `sonyra_auth_trusted_devices` — будущий “запомнить устройство”, device token hash, expires_at, revoked_at.
- `sonyra_auth_providers` — email, VK, MAX, Яндекс, TOTP, Passkey/WebAuthn.

Эти таблицы не входят в DB version 0.1.0. Они должны появиться отдельной миграцией DB version 0.1.1 на этапе 2.AUTH.1.

## Security rules

- Raw OTP code never stored.
- OTP хранится только как hash.
- No OTP in logs.
- No OTP in audit context.
- No OTP in REST responses.
- No user enumeration.
- Rate limit required.
- Lockout required.
- Replay protection required.
- Challenge expires.
- Challenge one-time use.
- Session token stored as hash.
- Device token stored as hash.
- IP should be stored as hash where possible.
- User agent should be normalized/hashed where possible.
- All auth actions must write audit events without secrets.

Разрешённые audit events будущих этапов:

- `auth.otp_requested`;
- `auth.otp_request_limited`;
- `auth.otp_verified`;
- `auth.otp_failed`;
- `auth.otp_locked`;
- `auth.session_created`;
- `auth.session_revoked`;
- `auth.access_denied`;
- `auth.provider_linked`;
- `auth.provider_failed`.

Все audit context должны маскировать:

- code;
- otp;
- token;
- secret;
- password;
- nonce;
- cookie;
- authorization;
- api_key.

## Будущий UX входа

Будущий экран входа должен быть только на русском языке.

Минимальный flow:

Шаг 1:

- заголовок: “Вход в Пульт сайта”;
- поле: “Email”;
- кнопка: “Получить код”;
- текст безопасности: “Если доступ разрешён, код будет отправлен на указанную почту.”

Шаг 2:

- заголовок: “Введите код”;
- поле: “Код из письма”;
- кнопка: “Войти”;
- кнопка/ссылка: “Отправить код ещё раз”;
- состояние ожидания повторной отправки;
- состояние блокировки;
- состояние истечения срока кода.

Запрещены видимые английские строки:

- Login;
- Sign in;
- Send code;
- Verify;
- OTP;
- Error;
- Success;
- Loading;
- Try again.

Термин OTP допустим только в технической документации и коде.

## Cookie transport связь

- После successful OTP verify and Auth Login service cookie transport может получить one-time internal session token.
- Cookie transport ставит безопасную cookie для будущих запросов Пульта сайта.
- Cookie transport не хранит raw token в БД, options, audit, status, HTML или JS.
- Cookie transport не использует localStorage/sessionStorage.
- Доступ в manager всё равно решается отдельным access guard.
- Cookie transport не является REST endpoint, UI или WordPress login.

## REST auth связь

- REST request-code вызывает OTP Request service.
- REST verify-code вызывает Auth Login service.
- REST verify-code на success передаёт one-time session token в Auth Cookie service.
- REST verify-code не возвращает raw session token в JSON.
- REST verify-code не возвращает raw OTP code в JSON.
- REST auth не раскрывает, существует ли email.
- REST auth не выдаёт manager access сам по себе.

## Login screen связь

- Login screen uses identifier request and verify-code.
- Identifier может быть email или login.
- Request-code response остаётся нейтральным.
- После request-code показывается code entry screen.
- Email/login enumeration запрещена.
- Raw OTP exposure запрещён.
- Password is not part of first release OTP flow.

## План этапов

- Этап 2.AUTH.0 — Auth/OTP contract.
- Этап 2.AUTH.1 — Auth database schema extension, DB version 0.1.1.
- Этап 2.AUTH.2 — Auth module registry foundation.
- Этап 2.AUTH.3 — Email OTP service без UI.
- Этап 2.AUTH.4 — Email delivery service foundation.
- Этап 2.AUTH.5 — Internal OTP request orchestration.
- Этап 2.AUTH.6 — Internal OTP verify orchestration.
- Этап 2.AUTH.7 — Auth session foundation.
- Этап 2.AUTH.8 — Auth login/session issue после OTP verify.
- Этап 2.AUTH.9 — Auth cookie transport contract.
- Этап 2.AUTH.10 — Auth cookie transport implementation без REST/routes/UI.
- Этап 2.AUTH.11 — Internal auth access guard foundation без manager route.
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

Manager UI и `/manager` access должны строиться после Auth foundation, а не до него.

## STOP-условия

Немедленно остановиться, если будущий Auth/OTP этап требует:

- сделать wp-login основным входом в Пульт сайта;
- ослабить email OTP как первый канал;
- хранить OTP открытым текстом;
- логировать OTP;
- раскрывать, существует ли email;
- создавать manager UI до Auth foundation;
- создавать `/manager` как открытую страницу без auth gate;
- добавлять английские видимые UI-строки;
- смешивать Auth DB, REST, routes и manager UI в одном этапе.
