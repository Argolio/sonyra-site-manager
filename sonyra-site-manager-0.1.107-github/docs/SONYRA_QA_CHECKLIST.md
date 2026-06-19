# SONYRA QA Checklist

## Базовая структура файлов

- [ ] Созданы только файлы, разрешённые текущим этапом.
- [ ] Не созданы лишние директории.
- [ ] Не создан scaffold плагина до отдельного разрешённого этапа.
- [ ] Главный файл sonyra-site-manager.php не создан до отдельного разрешённого этапа.
- [ ] Файлы сохранены в UTF-8 без BOM.

## Имена и legacy-запреты

- [ ] Используются только канонические названия SONYRA, SONYRA Site Platform, SONYRA Site Manager, SONYRA STUDIO.
- [ ] Неправильные варианты названия не используются как рабочие.
- [ ] Старый NDD-код не используется.
- [ ] Архитектура Навигатор добрых дел не переносится.
- [ ] legacy manager-app.js не используется.
- [ ] legacy manager-v2 не используется.
- [ ] ndd-site-manager не используется как источник архитектуры.

## Версии

- [ ] Версия определена единообразно.
- [ ] plugin header синхронизирован.
- [ ] SONYRA_SITE_MANAGER_VERSION синхронизирован.
- [ ] asset version синхронизирован.
- [ ] REST bootstrap синхронизирован.
- [ ] manager footer/about синхронизирован.
- [ ] diagnostics синхронизирован.
- [ ] version log синхронизирован.
- [ ] release checklist синхронизирован.
- [ ] При mismatch срабатывает STOP.

## i18n и русский интерфейс

- [ ] Все пользовательские UI-строки идут через assets/i18n/i18n-ru.js.
- [ ] Все пользовательские UI-строки выводятся через t('key').
- [ ] Видимый пользовательский интерфейс только на русском языке.
- [ ] Английские видимые UI-слова отсутствуют.
- [ ] Английские fallback-строки отсутствуют.
- [ ] Русские UI-строки не написаны напрямую в PHP.
- [ ] Русские UI-строки не написаны напрямую в JS.
- [ ] Русские UI-строки не написаны напрямую в HTML templates.
- [ ] Допустимые технические термины используются только при необходимости.

## Будущие syntax-проверки

- [ ] PHP syntax проверен для изменённых PHP-файлов.
- [ ] JS syntax проверен для изменённых JS-файлов.
- [ ] CSS sanity проверен для изменённых CSS-файлов.
- [ ] Нет синтаксических ошибок в templates.

## REST security

- [ ] Каждый приватный REST endpoint имеет nonce/session check.
- [ ] Каждый приватный REST endpoint имеет capability check.
- [ ] Input проходит sanitize.
- [ ] Input проходит validate.
- [ ] Output проходит escape.
- [ ] Важные действия пишутся в audit log.
- [ ] Гостевой доступ запрещён, если route не публичный по контракту.
- [ ] Denied access events фиксируются.

## Routes

- [ ] Manager routes отделены от публичных routes.
- [ ] Public routes имеют явный контракт.
- [ ] Private routes закрыты capability checks.
- [ ] REST routes имеют явные permissions callbacks.
- [ ] Нельзя использовать WordPress Pages как основу публичных страниц.

## Manager shell

- [ ] Manager закрыт от гостей.
- [ ] Manager закрыт от пользователей без прав.
- [ ] Manager имеет собственный shell.
- [ ] Manager не построен вокруг одного большого manager-app.js.
- [ ] Manager UI не содержит английских видимых UI-слов.

## Public renderer

- [ ] Публичный рендер не основан на WordPress Pages.
- [ ] Публичный рендер использует собственную модель страниц.
- [ ] Output escaping проверен.
- [ ] Видимый пользовательский интерфейс только на русском языке.

## Embeds, iframe и shortcode export

- [ ] Shortcode используется только как export/embed-слой.
- [ ] iframe используется только как export/embed-слой.
- [ ] HTML-вставки используются только как export/embed-слой.
- [ ] Embed token foundation проверен.
- [ ] iframe security foundation проверен.
- [ ] allowed domains foundation проверен.

## Рабочие UI-цепочки

- [ ] Для интерактивного элемента найден DOM/root selector.
- [ ] Для интерактивного элемента найден selector элемента.
- [ ] Подключён JS handler или delegated handler.
- [ ] preventDefault используется, если нужно.
- [ ] loading state реализован.
- [ ] request/action выполняется.
- [ ] nonce/capability/security проверены, если нужно.
- [ ] success render реализован.
- [ ] error render реализован.
- [ ] recovery/copy debug реализован, если нужно.
- [ ] Финальное обновление интерфейса выполнено.

## Audit log и diagnostics

- [ ] Важные действия пишутся в audit log.
- [ ] Ошибки доступа фиксируются.
- [ ] Diagnostics не раскрывает секреты.
- [ ] Diagnostics показывает версии.
- [ ] Diagnostics видим пользователю только на русском языке.

## Database QA for future

- [ ] db version option проверен.
- [ ] Таблицы существуют только на явно разрешённом database implementation этапе.
- [ ] Повторная активация безопасна.
- [ ] Destructive migration отсутствует.
- [ ] `$wpdb->prefix` проверен.
- [ ] charset/collate проверены.
- [ ] Индексы проверены.
- [ ] Migration log проверен.
- [ ] Audit log проверен.
- [ ] REST/UI не смешаны с DB stage.

## Release readiness

- [ ] Release checklist заполнен.
- [ ] Security audit пройден.
- [ ] REST audit пройден.
- [ ] Route audit пройден.
- [ ] DB migration audit выполнен для будущих этапов.
- [ ] Manager UI audit выполнен для будущих этапов.
- [ ] Public renderer audit выполнен для будущих этапов.
- [ ] Embed/iframe audit выполнен для будущих этапов.
- [ ] Архив релиза создаётся только на отдельном release-этапе.

## Future release package checks

- [ ] Архив находится строго в ./dist/.
- [ ] Имя архива соответствует sonyra-site-manager-X.X.X.zip.
- [ ] Предыдущие архивы сохранены.
- [ ] Deploy не выполнялся.
- [ ] Версия архива совпадает с plugin header.
- [ ] Версия архива совпадает с SONYRA_SITE_MANAGER_VERSION.
- [ ] Пользователь после ручной установки zip через WordPress проверяет, что плагин активируется.
- [ ] Пользователь после ручной установки zip через WordPress проверяет, что сайт не падает.
- [ ] Пользователь проверяет, что manager route открывается, если он уже реализован.
- [ ] Пользователь проверяет, что видимый интерфейс на русском.
- [ ] Пользователь проверяет, что нет английских пользовательских UI-строк.
- [ ] Пользователь проверяет, что нет старого NDD-кода.
- [ ] Пользователь проверяет, что можно откатиться на предыдущий zip.

## Private updater QA

- [ ] Update server unavailable не ломает сайт.
- [ ] Invalid manifest не ломает сайт.
- [ ] Wrong slug = no update.
- [ ] Lower/same version = no update.
- [ ] Higher version = update available.
- [ ] Checksum mismatch = STOP.
- [ ] Package domain mismatch = STOP.
- [ ] Visible update UI только на русском языке.

## Auth/OTP QA for future

- [ ] Неизвестный email не раскрывается.
- [ ] Известный email не раскрывается.
- [ ] Neutral response работает.
- [ ] OTP expires.
- [ ] OTP one-time.
- [ ] Wrong OTP increments attempts.
- [ ] 5 wrong attempts lock challenge.
- [ ] Resend cooldown works.
- [ ] Resend limit works.
- [ ] Lockout works.
- [ ] Code not stored plain.
- [ ] Code not logged.
- [ ] Session token not stored plain.
- [ ] Visible UI only Russian.
- [ ] No English fallback strings.
- [ ] Видимый UI входа только на русском языке.

## Auth cookie QA

- [ ] Cookie set only after successful auth login.
- [ ] HttpOnly present.
- [ ] Secure present on HTTPS.
- [ ] SameSite present.
- [ ] Token not visible to JS.
- [ ] Token not in HTML.
- [ ] Token not in audit log.
- [ ] Token not in status.
- [ ] Invalid token rejected.
- [ ] Expired session rejected.
- [ ] Revoked session rejected.
- [ ] Logout clears cookie.
- [ ] Logout revokes session.
- [ ] No manager access without access guard.

## REST auth QA

- [ ] `request-code` returns neutral message.
- [ ] `request-code` does not reveal identity existence.
- [ ] `verify-code` success sets HttpOnly cookie.
- [ ] `verify-code` does not return `session_token`.
- [ ] `verify-code` failure does not reveal reason.
- [ ] `logout` clears cookie.
- [ ] `logout` revokes session.
- [ ] `session` returns safe state only.
- [ ] No raw email, `email_hash`, token or code in response.
- [ ] All response messages Russian.
- [ ] No `/manager` access from REST auth alone.

## Login screen QA

- [ ] `/manager/login` opens.
- [ ] `/manager` not opened without access gate.
- [ ] Visible UI only Russian.
- [ ] No English user-facing strings.
- [ ] No password field in first release login UI.
- [ ] Identifier field visible as “Электронная почта или логин”.
- [ ] After request-code identifier field changes to code screen.
- [ ] Code input accessible.
- [ ] Request-code neutral response.
- [ ] Verify-code no `session_token` in frontend response.
- [ ] Cookie is HttpOnly.
- [ ] Frontend localStorage/sessionStorage do not store auth token.
- [ ] Loading/success/error states render.
- [ ] Mobile layout works.
- [ ] No WordPress admin style visible.
- [ ] No shortcode/page dependency.

## Managed Site Agent QA

- [ ] No central owner UI in this plugin.
- [ ] Managed site API requires signature.
- [ ] Unsigned command rejected.
- [ ] Replay command rejected.
- [ ] Unknown command rejected.
- [ ] Dangerous command rejected.
- [ ] Allowed command audited.
- [ ] Telemetry excludes customer sales.
- [ ] Telemetry excludes personal data.
- [ ] Secrets masked.
- [ ] License/access status changes logged.
- [ ] Manual restriction can be applied safely.
- [ ] Manual restore can be applied safely.
- [ ] No arbitrary PHP execution.
- [ ] No arbitrary SQL execution.
- [ ] Central Owner Control Plugin remains separate.
