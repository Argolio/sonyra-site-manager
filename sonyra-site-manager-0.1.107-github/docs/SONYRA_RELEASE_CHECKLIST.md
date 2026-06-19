# SONYRA Release Checklist

## Pre-release checklist

- [ ] Этап release явно разрешён.
- [ ] Все файлы этапа определены.
- [ ] Нет незавершённых STOP-условий.
- [ ] Все обязательные проверки выполнены.
- [ ] Финальный отчёт подготовлен.
- [ ] Видимое название плагина: Пульт сайта.
- [ ] Издатель: SONYRA STUDIO.
- [ ] Правообладатель/лицензиар/продавец в документах: ИП Катаев С.А. / IPKTFSA.
- [ ] Stable plugin slug: `sonyra-site-manager`.
- [ ] Корень архива внутри zip: `sonyra-site-manager/`.
- [ ] Главный файл внутри zip: `sonyra-site-manager/sonyra-site-manager.php`.
- [ ] Версионный корень вида `sonyra-site-manager-X.X.X/` запрещён.
- [ ] Техническое имя архива: sonyra-site-manager-X.X.X.zip.
- [ ] Color Controller screen opens in `Дизайн`.
- [ ] Option `sonyra_site_manager_color_controller` exists.
- [ ] Default Color Controller data loads.
- [ ] Color Controller tabs switch without errors.
- [ ] Create/edit/delete works for colors.
- [ ] Create/edit/delete works for gradients.
- [ ] Create/edit/delete works for patterns.
- [ ] Colors use compact swatch cards instead of large preview banners.
- [ ] Gradient modal uses swatches plus live preview and direction controls.
- [ ] Pattern modal shows grouped visual gallery with differentiated previews.
- [ ] Image pattern stores WordPress attachment ID and not external URL/base64.
- [ ] Delete opens standard destructive modal before removal.
- [ ] Landing card description explains library scope only.
- [ ] Landing card counters use aligned meta chips.
- [ ] HEX/RGB validation methods exist.
- [ ] No hardcoded visible Russian strings were added to JS.
- [ ] No raw CSS input or output is accepted by Color Controller.
- [ ] No raw i18n key is visible in browser UI, including `manager.pages.modal.delete_line_one`.
- [ ] Color Library item cards have visible background, border, radius and surface shadow.
- [ ] Edit and delete actions live inside each Color Library item card.
- [ ] Color Library cards use one horizontal anatomy: fixed preview zone, flexible content zone, fixed actions zone.
- [ ] Color card swatch keeps a fixed compact square size.
- [ ] Gradient and pattern previews keep fixed compact rectangle sizes.
- [ ] Preview width does not change because of title length or meta chip length.
- [ ] Item title clamps to two lines and wraps only inside the content column.
- [ ] Item title does not reserve a visible empty second line when one line is enough.
- [ ] Item title shows full value through native hover `title`.
- [ ] Actions remain inside the card and do not move because of title or chips.
- [ ] Grid keeps stable `4/3/3` desktop columns and explicit medium fallbacks before mobile.
- [ ] Desktop Colors grid uses 4 columns on wide desktop.
- [ ] Desktop Gradients grid uses 3 columns on wide desktop.
- [ ] Desktop Patterns grid uses 3 columns on wide desktop.
- [ ] Medium Color Library grids step down to `3/2/2` and then `2/2/2` as width decreases.
- [ ] Mobile Color Library card grid uses 1 column.
- [ ] Pattern meta chips stack vertically without widening the card.
- [ ] Pattern meta chips keep full readable text when two lines are enough.
- [ ] Pattern meta chips use ellipsis only when two lines are still insufficient.
- [ ] Duplicate color name inside `colors` scope is blocked on create and edit.
- [ ] Duplicate gradient name inside `gradients` scope is blocked on create and edit.
- [ ] Duplicate pattern name inside `patterns` scope is blocked on create and edit.
- [ ] Same visible name across `colors` / `gradients` / `patterns` remains allowed.
- [ ] Duplicate validation exists on both frontend and backend.
- [ ] Pattern gallery is grouped into accordion sections, not one long sheet.
- [ ] Selected pattern group stays open after tile selection and rerender.
- [ ] Pattern tile selection does not reset modal scroll to the top.
- [ ] Success notice auto-dismisses after `5000ms`.
- [ ] Error notice does not disappear too quickly.
- [ ] Save/edit does not clear Color Library collections.
- [ ] Reload keeps saved colors, gradients and patterns visible.
- [ ] Empty or missing Color Library core collections are safely recovered from defaults.
- [ ] Recovery never overwrites existing non-empty user collections.
- [ ] Color Library counters match actual normalized collections after recovery and after save.
- [ ] Landing card action `Настроить` uses `settings`, not `plus`.
- [ ] Create actions keep `plus` icon semantics.
- [ ] Color Library landing render does not depend on icon templates existing only inside the hidden `Страницы` workspace.
- [ ] Shared UI library enforcement chain is proven: file → enqueue → load order → helper exists → helper called → DOM visible → CSS match → behavior match.
- [ ] No silent local fallback is used for shared help trigger, shared icon tile, shared buttons, shared modal shell or shared chips.
- [ ] Separate `sonyra-color-controller.js` and `sonyra-color-controller.css` files exist.
- [ ] Предыдущие архивы сохранены.
- [ ] Deploy не выполнялся.

## Version consistency

- [ ] plugin header проверен.
- [ ] SONYRA_SITE_MANAGER_VERSION проверен.
- [ ] asset version проверен.
- [ ] REST bootstrap проверен.
- [ ] manager footer/about проверен.
- [ ] diagnostics проверен.
- [ ] version log проверен.
- [ ] release checklist проверен.
- [ ] mismatch отсутствует.
- [ ] При mismatch релиз остановлен.

## File structure

- [ ] Созданы только разрешённые файлы.
- [ ] Не создан лишний scaffold.
- [ ] Не добавлены лишние директории.
- [ ] Нет случайных временных файлов.
- [ ] UTF-8 без BOM подтверждён.

## No old NDD code

- [ ] Старый NDD-код не использован.
- [ ] Навигатор добрых дел не использован как источник архитектуры.
- [ ] ndd-site-manager не использован как источник архитектуры.
- [ ] manager-app.js не использован как основа.
- [ ] manager-v2 не использован как основа.

## No forbidden working names

- [ ] Используются только SONYRA, SONYRA Site Platform, SONYRA Site Manager, SONYRA STUDIO.
- [ ] Запрещённые варианты написания не используются как рабочие названия.
- [ ] Техническое имя sonyra-site-manager используется последовательно.
- [ ] Главный файл sonyra-site-manager.php указан последовательно.

## UI and i18n audit

- [ ] Видимый пользовательский интерфейс только на русском языке.
- [ ] Английские видимые UI-слова отсутствуют.
- [ ] Английские fallback-строки отсутствуют.
- [ ] `includes/i18n/ru.php` содержит активный `ru_RU` dictionary.
- [ ] `currentLocale` и `fallbackLocale` присутствуют в JS payload там, где payload используется.
- [ ] Новые i18n key names не содержат кириллицу.
- [ ] Help Library использует `i18n_title_key` и `i18n_body_key`.
- [ ] Page Elements Library использует `label_key` и `description_key`.
- [ ] Fake language switcher отсутствует.
- [ ] UI остаётся русским.
- [ ] Русские UI-строки не написаны напрямую в PHP.
- [ ] Русские UI-строки не написаны напрямую в JS.
- [ ] Русские UI-строки не написаны напрямую в HTML templates.
- [ ] Every UI component maps to `docs/SONYRA_MANAGER_COMPONENT_INVENTORY.md`.
- [ ] No new component without approval.
- [ ] No imitation components.
- [ ] Final report includes Component Reuse Proof table.
- [ ] Final report includes Required Markup Proof table.
- [ ] Final report includes shared UI library proof: source helper, target helper, DOM parity, placement parity.
- [ ] Open every new help icon and confirm tooltip is fully visible above neighboring panels/cards/toolbars.
- [ ] Tooltip in global help layer explicitly uses manager typography and does not fall back to browser serif fonts.
- [ ] Target components use the same helper, classes and selectors as source.
- [ ] Exactly one shared destructive modal helper exists.
- [ ] All delete actions call the shared destructive modal helper.
- [ ] No local destructive modal markup remains.
- [ ] Delete button uses the shared danger style.
- [ ] Warning slot uses the shared slot.
- [ ] Danger SVG tile is identical everywhere.
- [ ] Overlay, modal, header, body, footer and button order are identical everywhere.
- [ ] Patch fails if similar but separate modal is created.
- [ ] Patch fails if local danger button style is added.
- [ ] Patch fails if local SVG danger tile is added.
- [ ] Patch fails if target component differs from inventory without approval.
- [ ] Destructive modal typography matches manager standard.
- [ ] Destructive modal `font-family` is not browser fallback.
- [ ] Destructive modal title, body and buttons match standard typography.
- [ ] Help icons open tooltip by hover, focus and click.
- [ ] Help trigger size matches reference.
- [ ] Tooltip layer is visible above panels.
- [ ] Every help trigger has a valid help key.
- [ ] Component Reuse Proof includes visual and functional proof.

## Security audit

- [ ] Manager закрыт от гостей.
- [ ] Manager закрыт от пользователей без прав.
- [ ] Capability checks проверены.
- [ ] Nonce/session checks проверены.
- [ ] sanitize input проверен.
- [ ] validate input проверен.
- [ ] escape output проверен.
- [ ] audit log проверен.
- [ ] denied access events проверены.
- [ ] Auto-lock после 15 минут бездействия переводит в экран входа без reload wait.
- [ ] Auto-lock учитывает mousemove, mousedown, keydown, touchstart, scroll, focus и input.
- [ ] После auto-lock показывается notice `Сессия заблокирована`.
- [ ] Quick login по 4 цифрам auto-submit срабатывает на 4-й цифре и на paste/autofill.
- [ ] Email OTP по 6 цифрам auto-submit срабатывает на 6-й цифре и на paste/autofill.
- [ ] Кнопки `Войти` остаются visible fallback и ручной submit продолжает работать.

## REST audit

- [ ] Все REST endpoints имеют permissions callbacks.
- [ ] Private endpoints закрыты.
- [ ] Public endpoints имеют явный контракт.
- [ ] Guest access разрешён только там, где это публичный контракт.
- [ ] Ошибки REST не раскрывают секреты.
- [ ] Видимые REST-сообщения на русском языке.

## Route audit

- [ ] Manager routes проверены.
- [ ] Public routes проверены.
- [ ] Embed routes проверены.
- [ ] iframe routes проверены.
- [ ] Internal diagnostics routes проверены.
- [ ] WordPress Pages не используются как основа публичных страниц.

## DB migration audit for future

- [ ] Миграции имеют версию.
- [ ] Миграции обратимы или безопасно повторяемы.
- [ ] Ошибки миграций фиксируются.
- [ ] Release останавливается при неуспешной миграции.

## Database pre-release checks for future

- [ ] Migration plan присутствует.
- [ ] Backup/rollback note присутствует.
- [ ] db version изменён намеренно.
- [ ] Migration idempotent.
- [ ] Destructive migration отсутствует.
- [ ] Version log обновлён.
- [ ] Audit event записан.

## Manager UI audit for future

- [ ] Manager shell проверен.
- [ ] Section-specific changes остаются внутри section boundary.
- [ ] Manager shell не загрязнён section business logic.
- [ ] No global CSS leaks from section styles.
- [ ] No unscoped JS leaks from section runtime.
- [ ] Section service methods задокументированы.
- [ ] No new plugin root or slug.
- [ ] No duplicate section header/hero.
- [ ] Page header follows `SONYRA_MANAGER_SECTION_UX_STANDARD.md`.
- [ ] Actions размещены в существующем page header.
- [ ] No duplicate body header under the page header.
- [ ] Context card используется там, где section имеет внутренний workspace mode.
- [ ] Context card не дублирует title/action в body.
- [ ] Section badges размещены в header meta/chips row только на основе реальных section data.
- [ ] Item badges размещены рядом с элементом, который они описывают.
- [ ] Badges are states or metadata, not action wording.
- [ ] Counters use correct Russian pluralization.
- [ ] Alerts/errors/success используют Notice Center, inline alert или field-level validation, а не decorative badges.
- [ ] No fake badges/metrics/progress/status.
- [ ] No fake controls in empty or future states.
- [ ] Help icons are small, Help Library-backed and viewport-safe.
- [ ] Context label color совпадает с эталонным standard selector.
- [ ] Help icon совпадает с standard help component из `Страницы`.
- [ ] Back action содержит left arrow icon.
- [ ] Toolbar counters are chips, not loose text.
- [ ] Toolbar counters use standard padding and do not touch edge.
- [ ] Back action vertically centered.
- [ ] Tabs/meta row использует standard internal padding.
- [ ] Counters не касаются right edge panel.
- [ ] Color Library create action lives in toolbar/context flow, not as fake body duplicate.
- [ ] Color Library CRUD modals reuse shared modal shell.
- [ ] Color Library delete flow uses destructive confirmation copy with item name.
- [ ] Arbitrary spacing отсутствует.
- [ ] Footer has correct spacing globally.
- [ ] Footer checked on short and long content pages.
- [ ] DOM/CSS match table приложена к финальному отчёту.
- [ ] SVG icon alignment следует header standard: icon tile выровнен по kicker + title.
- [ ] Header remains compact and does not become a hero.
- [ ] No second hero inside body.
- [ ] Рабочие UI-цепочки доказаны.
- [ ] DOM/root selector проверен.
- [ ] loading state проверен.
- [ ] success render проверен.
- [ ] error render проверен.
- [ ] Recovery/copy debug проверен, если нужен.

## Pages table release checks

- [ ] Главный экран `Страницы` использует только колонки `Страница / В меню / Обновлена / Действия`.
- [ ] Колонки `Статус / Главная / Порядок` отсутствуют.
- [ ] Статус, `Главная` и `В меню` показаны compact badges внутри колонки `Страница`.
- [ ] Колонка `В меню` показывает `header/footer` icon + `№` позиции либо только `—`.
- [ ] Toolbar содержит поиск с icon `search` и placeholder `Найти страницу…`.
- [ ] Filters соответствуют `Все / Опубликованы / Черновики / Скрытые / В меню`.
- [ ] Sorting соответствует `Обновлены недавно / Сначала новые / А–Я / Я–А / По меню`.
- [ ] Dropdown `⋯` использует фиксированный порядок действий.
- [ ] Pages actions dropdown opens.
- [ ] `Предпросмотр`, `История изменений`, `Дублировать` активны и кликабельны.
- [ ] `SEO-настройки` остаётся disabled и не кликается.
- [ ] `Удалить` открывает destructive confirmation modal и не удаляет сразу.
- [ ] Toolbar separate from table.
- [ ] Updated header/body aligned.
- [ ] updated_at changes after save.
- [ ] visible updated date uses Intl, not toISOString slice.
- [ ] Toolbar filter icon present.
- [ ] Toolbar sort icon present and word `Сортировка` is not visible.
- [ ] Filter/sort groups have spacing.
- [ ] Accepted icons parameters/sections/open unchanged.
- [ ] Changed icons only for preview/copy link/make home/SEO/history/duplicate.
- [ ] Dynamic action shows `Скрыть` for published and `Опубликовать` for not published.
- [ ] No raw SVG.
- [ ] Dropdown lower row accessible.
- [ ] Dropdown internal scroll works.
- [ ] Publish label visible.
- [ ] No fake empty flash on reload.
- [ ] No dashboard-to-pages jump on reload.
- [ ] Success notice has icon.
- [ ] Notice icon color matches notice text via `currentColor`.
- [ ] Success notice auto-dismisses and has close button.
- [ ] Search focus remains stable while typing in `Найти страницу…`.
- [ ] Header badges under description are visible.
- [ ] Duplicate action creates a new draft page with unique slug.
- [ ] Home slug is preserved when home page changes.
- [ ] History modal opens and shows events or empty state.
- [ ] Modal header/body/footer are visually separated.
- [ ] Modal body scrolls inside modal and modal does not touch viewport bounds.
- [ ] Preview route `/sonyra-preview/{slug}/` works and is protected.
- [ ] Preview route returns `noindex,nofollow`.
- [ ] Open action uses real public route.
- [ ] Время `Обновлена` форматируется через browser/device timezone и JS `Intl`.

## Public renderer audit for future

- [ ] Собственная модель страниц проверена.
- [ ] Публичный рендер проверен.
- [ ] Output escaping проверен.
- [ ] Видимый пользовательский интерфейс только на русском языке.
- [ ] Enabled widgets с пустым action-field не пропадают молча из public HTML.
- [ ] Для пустого `phone` / `email` / `url` рендерится safe text-only placeholder без fake action.

## Section library release checks

- [ ] В left column есть heading `Секции`.
- [ ] Рядом с `Секции` есть help tooltip.
- [ ] Рядом с `Блоки секции` есть help tooltip.
- [ ] Tooltip texts подробные и читаемые.
- [ ] `Акцентная секция` не использует icon `app-window`.
- [ ] Delete section modal включает section name.
- [ ] Delete section modal показывает red warning icon.
- [ ] Delete section modal предупреждает про blocks внутри section.
- [ ] Delete section modal визуально воспринимается как dangerous action.
- [ ] Help popover не уходит за viewport.
- [ ] Каждый новый help icon открыт вручную.
- [ ] Tooltip виден полностью.
- [ ] Tooltip выше соседних плашек.
- [ ] Tooltip не обрезан overflow.
- [ ] Tooltip не уходит под toolbar/cards.
- [ ] Tooltip остаётся viewport-safe.
- [ ] Help texts приходят из Help Library entries.
- [ ] Hardcoded tooltip text в JS/PHP/CSS отсутствует.
- [ ] Drag handle визуально отличается от смысловой icon tile.
- [ ] В `Настройки` есть reserved card `База знаний ПУЛЬТА САЙТА`.
- [ ] Все core sections selectable через modal `Добавить секцию`.
- [ ] Modal `Добавить секцию` показывает только рабочие core sections.
- [ ] Settings action секции открывает modal `Параметры секции`.
- [ ] Delete action секции открывает destructive modal `Удалить секцию?`.
- [ ] Modal `Добавить секцию` закрывается сразу после выбора одной section.
- [ ] Double-click по section card не создаёт дубли.
- [ ] Desktop workspace секций использует пропорцию около `40/60`.
- [ ] Текст section card не ломается вертикально.
- [ ] В left panel всегда видна кнопка `Добавить секцию`.

## Base Blocks Library release checks

- [ ] Modal `Добавить блок` открывается из правой колонки.
- [ ] Modal `Добавить блок` работает в single-select режиме.
- [ ] Double-click по block card не создаёт дубли.
- [ ] Новый block становится active после add.
- [ ] Modal `Параметры блока` открывается и сохраняет данные.
- [ ] Delete block идёт только через destructive modal.
- [ ] Drag handle blocks рабочий и reorder сохраняется.
- [ ] Public/preview рендер blocks безопасен.
- [ ] raw embed code не исполняется.
- [ ] form block не делает fake submit.
- [ ] `sonyra_site_manager_section_definitions` существует.
- [ ] Unknown type сохраняется и не удаляется молча.
- [ ] Unavailable module instance не ломает editor.
- [ ] Unavailable module instance не ломает public/preview output.
- [ ] No raw SVG inside section library UI.

## Embed/iframe audit for future

- [ ] Shortcode export используется только как export/embed-слой.
- [ ] iframe используется только как export/embed-слой.
- [ ] Embed token foundation проверен.
- [ ] iframe security foundation проверен.
- [ ] allowed domains foundation проверен.

## Классический ручной релиз

Перед сборкой:

- [ ] Проверки этапа пройдены.
- [ ] Нет STOP-срабатываний.
- [ ] Нет изменений вне разрешённых файлов.
- [ ] Версия синхронизирована.

Сборка:

- [ ] Архив создаётся только в ./dist/.
- [ ] Имя архива соответствует sonyra-site-manager-X.X.X.zip.
- [ ] Предыдущие архивы не удалены.
- [ ] Deploy не выполнялся.

После сборки:

- [ ] Наличие архива проверено.
- [ ] Размер архива проверен.
- [ ] Список файлов внутри архива проверен.
- [ ] Лишние системные файлы отсутствуют.
- [ ] .DS_Store отсутствует.
- [ ] node_modules отсутствует.
- [ ] Временные файлы отсутствуют.
- [ ] Старый NDD-код отсутствует.

Ручная проверка пользователем:

- [ ] Пользователь устанавливает zip через WordPress.
- [ ] Пользователь активирует плагин.
- [ ] Пользователь проверяет сайт.
- [ ] Пользователь проверяет экран плагина.
- [ ] При ошибке пользователь откатывается на предыдущий zip.

## Archive/package checklist for future

- [ ] Архив создаётся только на release-этапе.
- [ ] Архив не содержит временные файлы.
- [ ] Архив не содержит dev-only артефакты.
- [ ] Архив не содержит секреты.
- [ ] Архив соответствует version consistency.

## Private updater release checks

- [ ] Update endpoint проверен только на явно разрешённом updater-этапе.
- [ ] Manifest JSON валиден.
- [ ] Package URL принадлежит разрешённому домену.
- [ ] Package checksum / sha256 совпадает.
- [ ] Signature проверяется, когда signed manifest включён.
- [ ] License status проверяется, когда license/update integration включена.
- [ ] Fallback to manual zip через ./dist/ сохраняется.
- [ ] Update UI не создаётся отдельным пунктом меню WordPress.

## Extension architecture release checks

- [ ] Manifest расширения содержит compatibility metadata.
- [ ] Manifest расширения содержит license/update contract fields.
- [ ] Package URL принадлежит разрешённому repository domain.
- [ ] Checksum/signature validation предусмотрены для будущего install/update runtime.
- [ ] Расширение не ломает семь базовых разделов.
- [ ] Расширение не добавляет основные пункты меню без отдельного архитектурного решения.
- [ ] Расширение не подменяет sidebar, page header или дизайн-систему базы.
- [ ] Клиентский сайт не отправляет контент или персональные данные в центральную систему.
- [ ] Формулировки подключения к системе сопровождения не создают неверную семантику управления сайтом поставщиком.
- [ ] Zip не содержит `node_modules`, temp files, OS junk, hidden service dirs или full upstream dumps.

## Auth release checks for future

- [ ] No plain OTP storage.
- [ ] No user enumeration.
- [ ] Rate limits проверены.
- [ ] Lockout проверен.
- [ ] Session hash проверен.
- [ ] Audit events без секретов проверены.
- [ ] Russian visible UI проверен.
- [ ] wp-login не является main product UX.

## Auth cookie release checks for future

- [ ] Auth cookie содержит HttpOnly.
- [ ] Auth cookie содержит Secure on HTTPS.
- [ ] Auth cookie содержит SameSite.
- [ ] Auth token не хранится в localStorage/sessionStorage.
- [ ] Auth token не пишется в logs/audit/status.
- [ ] Logout clears cookie.
- [ ] Session revoke works.
- [ ] Access guard checked separately.

## REST auth release checks for future

- [ ] REST auth namespace correct: `sonyra-site-manager/v1`.
- [ ] `request-code` neutral response.
- [ ] `verify-code` no token in JSON.
- [ ] HttpOnly cookie set.
- [ ] `logout` clears cookie.
- [ ] `session` safe response.
- [ ] No English visible messages.
- [ ] No manager access from REST auth alone.

## Login screen release checks for future

- [ ] Login route not `wp-login`.
- [ ] Login UI Russian only.
- [ ] No password field in first release login.
- [ ] Identifier field says “Электронная почта или логин”.
- [ ] After request-code screen switches to code entry.
- [ ] No token in frontend.
- [ ] No localStorage/sessionStorage auth token.
- [ ] No email/login enumeration.
- [ ] Visual QA completed.
- [ ] Mobile QA completed.
- [ ] `/manager` protected separately.

## Final release report format

Финальный release-отчёт должен содержать:

- статус release;
- версию;
- список файлов и артефактов;
- результаты version consistency;
- результаты security audit;
- результаты REST audit;
- результаты route audit;
- результаты UI/i18n audit;
- результаты package checklist;
- STOP-условия и срабатывания;
- подтверждение, что старый NDD-код, manager-app.js и manager-v2 не использованы.

## Managed Site Agent release checks

Будущие release checks для PLATFORM/AGENT-этапов:

- [ ] No central owner UI inside managed site plugin.
- [ ] Managed site API signed.
- [ ] Unsigned commands rejected.
- [ ] Replay commands rejected.
- [ ] Unknown commands rejected.
- [ ] No arbitrary remote code execution.
- [ ] No arbitrary SQL execution.
- [ ] Telemetry excludes customer sales and personal data.
- [ ] Secrets masked.
- [ ] Command audit exists.
- [ ] Billing/access restriction safe.
- [ ] Manual restore safe.
- [ ] License/access status changes audited.
- [ ] Central Owner Control Plugin is separate.

## Pages sections workspace release checks

- [ ] В режиме `Секции` не показываются параметры страницы.
- [ ] Workspace имеет two-column layout: sections left, blocks right.
- [ ] Section card text не ломается вертикально.
- [ ] Block card text не ломается вертикально.
- [ ] Delete section confirmation работает через destructive modal.
- [ ] Delete block confirmation работает через destructive modal.
- [ ] Drag handles reorder sections и сохраняют порядок.
- [ ] Drag handles reorder blocks и сохраняют порядок.
- [ ] Sidebar collapsed reload проходит без flicker.

## Widgets release checks

- [ ] Действие `Виджеты` открывает page widgets workspace.
- [ ] Modal `Добавить виджет` открывается.
- [ ] Modal `Добавить виджет` работает в single-select режиме.
- [ ] Double-click не создаёт duplicates.
- [ ] Modal `Параметры виджета` сохраняет изменения.
- [ ] Widget delete использует destructive modal.
- [ ] Widget drag reorder сохраняет порядок.
- [ ] Public/preview widgets render безопасен.
- [ ] `external_widget` не исполняет raw embed.
- [ ] `quick_request` и `callback` не имитируют submit.
- [ ] Hardcoded visible RU outside `ru.php` отсутствует.
- [ ] Карточка включённого виджета показывает badge `Включён`.
- [ ] Badge `Включён` использует green success-soft style.
- [ ] Карточка выключенного виджета показывает badge `Выключен`.
- [ ] Badge `Выключен` использует red danger-soft style.
- [ ] Position badge выглядит как badge, а не как plain text.
- [ ] Отдельный toggle рядом с settings/delete не добавлен.

## Manager Section UX Standard checks

- [ ] `docs/SONYRA_MANAGER_SECTION_UX_STANDARD.md` exists.
- [ ] Документ явно называет `Страницы` master UX reference.
- [ ] Document covers Page Header Standard.
- [ ] Document covers Context Card Standard.
- [ ] Document covers Section Landing Cards Standard.
- [ ] Document covers Workspace Body Standard.
- [ ] Document covers Panel Standard.
- [ ] Document covers Cards Standard.
- [ ] Document covers Badge Standard.
- [ ] Document covers Help Icon / Tooltip Standard.
- [ ] Document covers Button Placement Standard.
- [ ] Document covers Modal Standard.
- [ ] Document covers Empty State Standard.
- [ ] Document covers Russian Text / I18N Standard.
- [ ] Document covers future Color Library tool-screen pattern.

## Design / Color Library UX hotfix checks

- [ ] `Дизайн` page header shows `ДИЗАЙН`.
- [ ] `Дизайн` page header title shows `ОБЩИЙ СТИЛЬ И ОФОРМЛЕНИЕ`.
- [ ] `Дизайн` page header description matches the current section copy.
- [ ] No meta badges appear in the big `Дизайн` header.
- [ ] `Дизайн` opens as a landing section with tool cards.
- [ ] Landing card `Цветовая библиотека` is visible.
- [ ] Landing card shows only real counters for colors, gradients and patterns.
- [ ] Landing card primary action is `Настроить`.
- [ ] Internal tool screen uses a context card, not a repeated hero/header.
- [ ] Context card label is `ЦВЕТОВАЯ БИБЛИОТЕКА`.
- [ ] Context card action is `Вернуться в раздел Дизайн`.
- [ ] Small help icon opens the Help Library tooltip.
- [ ] Tooltip у `ЦВЕТОВАЯ БИБЛИОТЕКА` не уходит под toolbar `Цвета / Градиенты / Паттерны`.
- [ ] No duplicate visible description paragraph sits under the internal context title.
- [ ] Internal tabs are only `Цвета`, `Градиенты`, `Паттерны`.
- [ ] No visible extra internal library tab.
- [ ] Internal counters use correct Russian pluralization.
- [ ] No fake CRUD controls appear on the landing card or the internal tool screen.

## Pattern Generator Architecture checks `0.1.91`

- [ ] `Создать паттерн` сначала открывает выбор типа редактора.
- [ ] Выбор типа содержит только `Графический паттерн` и `Изображение как паттерн`.
- [ ] При выборе типа не открывается modal поверх modal.
- [ ] Graphic editor shows left preview and right grouped controls on desktop.
- [ ] Mobile layout stacks preview above controls.
- [ ] Visible generator list contains only approved generators and no duplicate preset names.
- [ ] Category `SVG-символы` отсутствует.
- [ ] Visible names `Мрамор / прожилки` и `Шум / зерно` отсутствуют.
- [ ] Compact sliders show current values.
- [ ] Switches are used instead of checkboxes.
- [ ] Image pattern editor exposes upload/library, placement, color-light and texture/effects groups.
- [ ] Image pattern editor is richer than upload + opacity.
- [ ] Visible RU strings in JS отсутствуют.

### Manual browser checklist

- [ ] Открыть `Дизайн → Цветовая библиотека → Паттерны`.
- [ ] Нажать `Создать паттерн`.
- [ ] Проверить выбор `Графический паттерн` / `Изображение как паттерн`.
- [ ] Выбрать `Графический паттерн`.
- [ ] Проверить генераторы `Точки`, `Сетка`, `Линии`, `Круги и орбиты`, `Геометрическая мозаика`, `Мягкие пятна`, `Световой луч`, `Волны`, `Мазки краски`, `Кляксы`, `Мрамор`, `Шум`, `Декоративные элементы`.
- [ ] Убедиться, что в списке нет `Микроточки`, `Мягкая сетка`, `Диагональные линии`, `SVG-символы`.
- [ ] Изменить несколько контролов и проверить live preview.
- [ ] Вернуться к выбору типа через `Назад к выбору типа`.
- [ ] Открыть `Изображение как паттерн`.
- [ ] Проверить upload/library selection, placement, color-light, texture and effects groups.

## Pattern Editor Layout Polish checks `0.1.92`

- [ ] Color Library modal titles use noun forms for create/edit flows.
- [ ] Color Library buttons keep verb forms and were not converted to nouns.
- [ ] `Создание цвета` is shown in create color modal.
- [ ] `Редактирование цвета` is shown in edit color modal.
- [ ] `Создание градиента` is shown in create gradient modal.
- [ ] `Редактирование градиента` is shown in edit gradient modal.
- [ ] `Создание графического паттерна` is shown after choosing the graphic editor.
- [ ] `Редактирование графического паттерна` is shown for graphic pattern edit.
- [ ] `Создание паттерна из изображения` is shown after choosing the image editor.
- [ ] `Редактирование паттерна из изображения` is shown for image pattern edit.
- [ ] Desktop pattern editor keeps a fixed left result panel and scrollable right controls.
- [ ] Mobile pattern editor keeps a fixed top result panel and scrollable lower controls.
- [ ] Preview stays horizontal in both graphic and image editors.
- [ ] Pattern name and `Назад к выбору типа` live under preview.
- [ ] Generator block can collapse and expand.
- [ ] Generator mini-previews are aligned and share one size.
- [ ] Slider fill reaches the thumb and matches the displayed value.
- [ ] Binary settings use switches and no checkbox UI appears.

### Manual browser checklist `0.1.92`

- [ ] Установить или обновить `dist/sonyra-site-manager-0.1.92.zip`.
- [ ] Подтвердить, что WordPress обновляет существующий плагин, а не создаёт второй.
- [ ] Открыть `/manager`.
- [ ] Открыть `Дизайн → Цветовая библиотека`.
- [ ] Открыть `Цвета` → create modal и проверить `Создание цвета`.
- [ ] Открыть `Цвета` → edit modal и проверить `Редактирование цвета`.
- [ ] Открыть `Градиенты` → create modal и проверить `Создание градиента`.
- [ ] Открыть `Градиенты` → edit modal и проверить `Редактирование градиента`.
- [ ] Открыть `Паттерны` → `Создать паттерн`.
- [ ] Выбрать `Графический паттерн` и проверить `Создание графического паттерна`.
- [ ] Проверить fixed result panel слева и scrollable controls справа.
- [ ] Проверить, что preview горизонтальный и остаётся видимым при скролле controls.
- [ ] Проверить, что под preview стоят название и `Назад к выбору типа`.
- [ ] Проверить, что блок `Генератор` можно свернуть и развернуть.
- [ ] Проверить alignment mini-preview карточек генераторов.
- [ ] Проверить, что fill slider совпадает с thumb и что value виден.
- [ ] Проверить, что бинарные опции используют switch, а checkbox отсутствует.
- [ ] Сохранить паттерн и открыть его редактирование.
- [ ] Проверить `Редактирование графического паттерна`.
- [ ] Открыть создание image pattern и проверить `Создание паттерна из изображения`.
- [ ] Открыть редактирование image pattern и проверить `Редактирование паттерна из изображения`.
- [ ] Проверить mobile width: fixed top result panel, lower controls scroll, preview остаётся видимым.
- [ ] Проверить, что `Сохранить` и `Отменить` не перекрываются.
- [ ] Проверить create/edit/save/reload и отсутствие console fatal errors.

## Pattern Editor Functional UX Repair checks `0.1.93`

- [ ] Pattern modal header shows title and subtitle in create and edit flows.
- [ ] Left result panel looks like a card and preview stays horizontal.
- [ ] Accordion groups collapse and expand without losing state.
- [ ] Dot-generator controls affect preview and save payload.
- [ ] Control changes do not jump scroll to the top.
- [ ] Switches show no standalone `Включено` / `Выключено`.
- [ ] Slider fill matches thumb on initial render and during input.

### Manual browser checklist `0.1.93`

- [ ] Установить или обновить `dist/sonyra-site-manager-0.1.93.zip`.
- [ ] Подтвердить, что WordPress обновляет существующий плагин, а не создаёт второй.
- [ ] Открыть `/manager` и проверить `Версия 0.1.93`.
- [ ] Открыть `Дизайн → Цветовая библиотека → Паттерны`.
- [ ] Создать графический паттерн и проверить видимый header title/subtitle.
- [ ] Проверить премиальную card-подачу left result panel.
- [ ] Проверить, что `Генератор`, `Основное`, `Цвета`, `Эффекты` сворачиваются и разворачиваются.
- [ ] Проверить, что для `Точки` нет бессмысленной группы `Форма`.
- [ ] Проверить, что `Плотность`, `Размер точки`, `Расстояние`, `Смещение слоя`, `Количество слоёв`, `Случайное смещение`, `Прозрачность`, `Край` влияют на preview.
- [ ] Проверить, что slider input не сбрасывает scroll наверх.
- [ ] Проверить, что switch не показывает отдельный текст состояния.
- [ ] Сохранить паттерн, перезагрузить экран и открыть редактирование.
- [ ] Проверить `Редактирование графического паттерна`.
- [ ] Создать image pattern и проверить тот же header/result-panel standard.
- [ ] Проверить отсутствие fatal console errors и raw keys.

## Pattern Editor Full Rebuild checks `0.1.94`

- [ ] Pattern Editor groups are built through one schema normalizer, not blind PHP group remaps.
- [ ] No visible control stays in UI if it does not change live preview.
- [ ] Dots editor shows exactly `Точки`, `Слои и цвета`, `Эффекты`.
- [ ] Dots editor does not show visible `Плотность`.
- [ ] Result panel is a finished card, not a technical left column.
- [ ] Pattern type, name and back action belong to the same result-card area.
- [ ] Generator and all settings groups use the same accordion primitive.
- [ ] Ordinary slider/switch/segmented/color updates do not rebuild the full modal shell.
- [ ] Generator change may rebuild controls panel only and does not reset modal header/result card.
- [ ] Range helper uses one fill token `--sonyra-range-fill`.
- [ ] Switch rows use semantic switch markup and no standalone visible state text.
- [ ] No hardcoded visible Russian strings were added to JS.

### Manual browser checklist `0.1.94`

- [ ] Установить или обновить `dist/sonyra-site-manager-0.1.94.zip`.
- [ ] Подтвердить, что WordPress обновляет существующий плагин, а не создаёт второй.
- [ ] Открыть `/manager` и проверить `Версия 0.1.94`.
- [ ] Открыть `Дизайн → Цветовая библиотека → Паттерны`.
- [ ] Открыть создание графического паттерна.
- [ ] Проверить, что слева показана finished result card, а не техническая колонка.
- [ ] Проверить, что type label, поле имени и `Назад к выбору типа` относятся к той же result card.
- [ ] Проверить, что `Генератор` и все группы используют один и тот же accordion pattern.
- [ ] Выбрать `Точки`.
- [ ] Проверить, что видны только `Точки`, `Слои и цвета`, `Эффекты`.
- [ ] Проверить, что `Плотность` не показывается.
- [ ] Проверить, что `Размер точки`, `Расстояние`, `Количество слоёв`, `Смещение слоя`, `Прозрачность`, `Случайное смещение`, `Край` влияют на preview.
- [ ] Изменить `Количество слоёв` и проверить, что меняются только зависимые color slots без полного пересоздания modal shell.
- [ ] Переключить генератор и проверить, что обновился только control panel, а header/result card не прыгают.
- [ ] Открыть image pattern editor и проверить тот же shell standard.
- [ ] Проверить, что обычные slider/switch/color updates не сбрасывают scroll наверх.
- [ ] Сохранить паттерн, перезагрузить экран и открыть редактирование.
- [ ] Проверить, что preview и control values сохранились.
- [ ] Проверить отсутствие fatal console errors и raw i18n keys.
## 0.1.75

- verify widgets workspace header uses the page-level layer pattern.
- verify popups action opens a dedicated page-level workspace.
- verify popup library contains 11 core popup types.
- verify public/preview popup rendering stays safe and does not execute raw embed code.
- manual browser checklist:
  - open page actions and click `Виджеты`
  - confirm context label is `ВИДЖЕТЫ СТРАНИЦЫ`
  - confirm add button is in the context card
  - open page actions and click `Поп-апы`
  - confirm context label is `ПОП-АПЫ СТРАНИЦЫ`
  - confirm 11 popup cards are visible in add-popup modal
  - add one popup, edit it, reorder it, delete it
  - confirm preview/public page stays stable and `external_popup` does not execute raw code
  - confirm popup is not rendered as a static section/card in page flow
  - confirm backdrop / dialog / Escape / click-outside close work
