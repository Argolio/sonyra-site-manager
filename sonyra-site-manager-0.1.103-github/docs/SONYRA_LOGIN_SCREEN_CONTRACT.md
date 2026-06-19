# SONYRA Login Screen Contract

## Назначение

Login screen — первый видимый пользовательский экран продукта “Пульт сайта”.

Login screen не является `wp-login`, wp-admin page, manager dashboard, shortcode-based page, WordPress Page, публичным renderer или access gate.

Login screen должен работать через REST auth endpoints, использовать email OTP, принимать один identifier: электронная почта или логин, быть полностью на русском языке и быть визуально premium SaaS.

Login screen не должен раскрывать, существует ли электронная почта или логин, показывать debug-данные, возвращать или показывать token/code/hash.

## Пароль на первом релизе не используется

Основной вход первого релиза:

электронная почта или логин → одноразовый код → подтверждение входа → будущий переход в Пульт сайта через access gate.

Правила:

- пароль не используется;
- пароль не запрашивается;
- пароль не отображается;
- пароль не является обязательным фактором;
- WordPress password не используется как основной вход в Пульт сайта;
- `wp-login` не является основным пользовательским входом;
- пароль может быть рассмотрен позже как дополнительный фактор отдельным этапом;
- сейчас пароль запрещено добавлять в login UI.

Passwordless first release — каноническое решение для первого визуального входа.

## Электронная почта или логин

В пользовательском UI поле называется “Электронная почта или логин”.

Технически это `identifier`.

Правила:

- identifier может быть email;
- identifier может быть login;
- UI не должен писать “identifier”, “username”, “login” как технический термин;
- в видимом русском UI использовать только “Электронная почта или логин”;
- обработка email/login должна быть нейтральной;
- нельзя раскрывать, найден пользователь или нет;
- нельзя раскрывать, что именно введено неправильно;
- нельзя показывать “пользователь не найден”;
- нельзя показывать “email не найден”;
- нельзя показывать “логин не найден”.

Текущий REST auth contract уже имеет request-code input `email`.

Будущая миграция:

- будущий основной input для request-code: `identifier`;
- `email` может быть сохранён как backward-compatible alias;
- REST implementation расширять только отдельным будущим этапом;
- на этапе 3.0 код REST не менять.

## Будущие routes

Будущий login route:

`/manager/login`

Будущий manager route:

`/manager`

Правила:

- `/manager/login` — экран входа;
- `/manager` — будущий закрытый Пульт сайта;
- `/manager/login` пока не создавать;
- `/manager` пока не создавать;
- route implementation будет отдельным этапом;
- route visual shell будет отдельным этапом;
- access gate будет отдельным этапом.

## Будущий flow

Шаг 1: пользователь открывает `/manager/login`.

Шаг 2: пользователь видит первый экран:

- логотип / брендовая зона;
- заголовок “Вход в Пульт сайта”;
- пояснение;
- одно поле “Электронная почта или логин”;
- кнопка “Получить код”;
- служебная подсказка о коде.

Шаг 3: пользователь вводит электронную почту или логин и нажимает “Получить код”.

Шаг 4: frontend вызывает будущий REST request-code:

`POST /wp-json/sonyra-site-manager/v1/auth/request-code`

Target input: `identifier`.

Backward-compatible input: `email`.

Шаг 5: пользователь видит нейтральное сообщение:

“Если доступ разрешён, код будет отправлен на указанную почту.”

Даже если введён логин, текст сообщения остаётся нейтральным и не раскрывает существование логина/email.

Шаг 6: первый экран ввода электронной почты/логина заменяется экраном ввода кода.

Поле “Электронная почта или логин” исчезает как основное поле.

После успешного request-code поле “Электронная почта или логин” исчезает из основного ввода.

Вместо него появляется:

- заголовок “Введите код из письма”;
- 6 ячеек кода;
- кнопка “Войти”;
- действие “Отправить код ещё раз”;
- действие “Изменить почту или логин”.

Шаг 7: frontend вызывает:

`POST /wp-json/sonyra-site-manager/v1/auth/verify-code`

Шаг 8: если код подтверждён:

- показывается сообщение “Вход подтверждён.”;
- cookie ставится REST endpoint’ом;
- frontend не получает `session_token`;
- manager access ещё проверяется отдельным access gate.

Шаг 9: будущий access gate решает переход в `/manager`.

До реализации access gate безопасное сообщение:

“Переход к Пульту сайта будет доступен после проверки доступа.”

## Смена полей на экране входа

Правила:

- initial state показывает только поле “Электронная почта или логин”;
- password field отсутствует;
- после request-code success initial field исчезает или сворачивается в безопасную summary-строку;
- code state показывает только поле “Код из письма”;
- code state не должен одновременно показывать активное поле электронной почты/логина как основное поле;
- пользователь может нажать “Изменить почту или логин” и вернуться к initial state;
- пользователь может нажать “Отправить код ещё раз” без раскрытия доступа;
- после verify success показывается “Вход подтверждён.”;
- после verify success будущий route может перейти в `/manager` только через access gate.

## Будущие состояния экрана

Обязательные состояния:

- `initial_identifier`;
- `request_loading`;
- `request_success`;
- `request_error`;
- `code_entry`;
- `resend_loading`;
- `resend_success`;
- `verify_loading`;
- `verify_success`;
- `verify_error`;
- `session_checking`;
- `access_waiting`;
- `logged_out`;
- `blocked_safe`;
- `network_error`;
- `server_error`;
- `expired_code_safe`;
- `too_many_attempts_safe`.

Error states не должны раскрывать существование электронной почты или логина, правильный ли identifier, сколько попыток осталось, точную причину блокировки, token/code/hash/debug.

## Видимые русские UI-строки

Разрешённые строки:

- “Пульт сайта”
- “Вход в Пульт сайта”
- “Введите электронную почту или логин, чтобы получить код доступа.”
- “Электронная почта или логин”
- “name@example.com или login”
- “Получить код”
- “Если доступ разрешён, код будет отправлен на указанную почту.”
- “Введите код из письма.”
- “Код из письма”
- “Войти”
- “Отправить код ещё раз”
- “Изменить почту или логин”
- “Вход подтверждён.”
- “Вход не подтверждён. Проверьте код и попробуйте ещё раз.”
- “Вход не выполнен.”
- “Вход выполнен.”
- “Доступ не разрешён.”
- “Вы вышли из Пульта сайта.”
- “Не удалось выполнить запрос. Проверьте соединение и попробуйте ещё раз.”
- “Не удалось обработать запрос. Попробуйте ещё раз.”
- “Переход к Пульту сайта будет доступен после проверки доступа.”
- “SONYRA STUDIO”

Forbidden visible strings:

- Login
- Sign in
- Continue
- Submit
- Email
- Username
- Password
- OTP
- Code
- Token
- Invalid code
- User not found
- Email not found
- Username not found
- Unauthorized
- Forbidden
- Error
- Success
- Loading
- Dashboard
- Manager
- Any visible English user-facing message.

Технические термины REST, API, URL, JSON, CSS, JS, PHP, WordPress допускаются только в документации и технических логах, но не в пользовательском login UI, если можно обойтись русским текстом.

## Визуальная структура будущего login shell

Root: `sonyra-login-root`

Outer shell: `sonyra-login-shell`

Brand area:

- logo/brand mark placeholder;
- text “Пульт сайта”;
- optional small “SONYRA STUDIO”.

Main panel:

- card/panel centered;
- title;
- description;
- form area;
- state message;
- action buttons.

Identifier form:

- label “Электронная почта или логин”;
- input;
- primary button “Получить код”;
- hint.

Code form:

- label “Код из письма”;
- 6 code cells or one accessible input styled as 6 cells;
- primary button “Войти”;
- secondary action “Отправить код ещё раз”;
- secondary action “Изменить почту или логин”.

Status area:

- neutral message;
- success message;
- error message;
- loading state.

Footer:

- “SONYRA STUDIO”;
- no legal overload in first shell.

## Визуальный стиль

Login shell должен быть premium SaaS:

- спокойный светлый фон;
- аккуратная карточка входа;
- мягкие градиенты;
- нормальные spacing;
- крупный понятный заголовок;
- удобные поля;
- понятные кнопки;
- focus states;
- loading states;
- success/error states;
- mobile-first responsive;
- без сырого технического вида;
- без WordPress admin style;
- без старого NDD style;
- без случайной мешанины цветов;
- без английских UI-слов.

## Accessibility

Будущий login screen должен иметь:

- label для identifier;
- `autocomplete="username"`;
- `inputmode="email"`;
- доступный ввод кода;
- `aria-live` для status messages;
- focus ring;
- keyboard navigation;
- button disabled/loading states;
- normal contrast;
- no clickable divs вместо buttons;
- no hidden text traps.

Так как поле может быть email или login, `autocomplete="username"` предпочтительнее, чем только email, а `inputmode="email"` допустим для удобства email.

## REST integration

Будущий login shell должен использовать:

- `POST /wp-json/sonyra-site-manager/v1/auth/request-code`;
- `POST /wp-json/sonyra-site-manager/v1/auth/verify-code`;
- `GET /wp-json/sonyra-site-manager/v1/auth/session`;
- `POST /wp-json/sonyra-site-manager/v1/auth/logout`.

Правила:

- request-code не раскрывает existence электронной почты или логина;
- целевой input request-code: `identifier`;
- текущий email input может оставаться backward-compatible alias до отдельного этапа;
- verify-code не возвращает `session_token`;
- cookie ставится сервером;
- session endpoint возвращает safe auth state;
- frontend не хранит token;
- frontend не использует localStorage/sessionStorage для auth;
- frontend не пишет token в HTML;
- frontend не показывает debug data.

## Будущее расширение REST auth request-code до identifier

Текущий REST auth controller был создан с email input.

Будущий login screen требует identifier input.

Перед подключением login shell к REST нужно отдельным этапом расширить request-code:

- принимать `identifier`;
- определять, email это или login;
- нормализовать email через существующие helpers;
- нормализовать login безопасно;
- искать identity нейтрально;
- не раскрывать, найдено или нет;
- сохранить поддержку email как alias;
- не менять публичный нейтральный response.

На этапе 3.0 код REST не менять.

## Будущие implementation-файлы

Будущие файлы этапа 3.1/3.2 могут быть:

- `includes/class-sonyra-site-manager-login-route.php`;
- `includes/class-sonyra-site-manager-login-renderer.php`;
- `assets/css/sonyra-login.css`;
- `assets/js/sonyra-login.js`;
- `templates/login-shell.php`.

Сейчас эти файлы не создавать.

Когда будет implementation:

- CSS/JS должны быть scoped only to login screen;
- no global CSS;
- no raw technical UI;
- no wp-admin UI dependency;
- all visible strings via i18n or centralized PHP data structure;
- no English visible strings;
- no password field.

## No shortcode architecture

Login route не должен быть шорткодом.

Шорткоды в SONYRA Site Platform — это будущий export/embed слой, а не основа продукта.

## No WordPress Pages architecture

Login route не должен требовать создания WordPress Page.

Пульт сайта не должен зависеть от страницы WordPress как основной архитектуры входа.

## No wp-login main auth

Пульт сайта не должен использовать `wp-login` как основной пользовательский вход.

WordPress administrator/owner может быть recovery/trusted foundation, но основной UX входа в Пульт сайта — отдельный route `/manager/login` через email/login OTP.

## Future implementation stages

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

Немедленно остановиться, если login screen этап требует `wp-login` как основной вход, WordPress Page или shortcode как основу route, password field, обязательный пароль, route до implementation-этапа, английские visible UI strings, token/code/hash во frontend или раскрытие существования email/login.
