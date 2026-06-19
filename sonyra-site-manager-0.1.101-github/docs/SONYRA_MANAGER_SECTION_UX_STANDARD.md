# SONYRA Manager Section UX Standard

## 1. Назначение стандарта

- раздел `Страницы` является базовым эталоном оформления ПУЛЬТА САЙТА;
- все будущие разделы, подразделы и рабочие экраны должны следовать этому стандарту;
- Codex не должен придумывать новый layout, если нет явного разрешения на отдельный redesign;
- любые безопасные отклонения сначала сверяются с текущим UX-паттерном раздела `Страницы`.

## 2. Master UX Reference

Эталонным источником паттернов считаются:

- главный экран `Страницы`;
- workspace `Секции`;
- workspace `Виджеты`;
- workspace `Поп-апы`;
- связанные modal, badge, tooltip и notice patterns внутри раздела `Страницы`.

Этот документ не заменяет текущий UI `Страницы`, а фиксирует уже принятый стандарт.

## 2.1 Shared UI Library Standard

- visual master reference remains `Страницы`, but reusable manager anatomy must be exported through `assets/js/sonyra-manager-ui.js`;
- release `0.1.79` shared helper set is: `renderButton(config)`, `renderIconTile(config)`, `renderHelpTrigger(config)`, `bindHelpTooltipLayer(root, config)`, `renderMetaChip(config)`, `renderToolbar(config)`, `renderContextCard(config)`;
- new manager screens must consume these shared helpers instead of rebuilding the same DOM locally;
- shared library is accepted only when browser-facing proof confirms: file → enqueue → load order → helper exists → helper called → DOM output visible → CSS match → behavior match;
- if a patch claims standard reuse but keeps separate lookalike markup/helpers, it fails zero-regression acceptance.

## 2.2 No Silent Fallback Rule

- if a shared helper is missing, target screen must not silently render a local surrogate component;
- controlled failure/reporting is allowed; visual imitation is not;
- fallback to technical keys, browser defaults or approximate DOM is a patch rejection.

## 3. Page Header Standard

- каждый раздел использует один большой верхний page header manager shell;
- eyebrow = название раздела;
- main title = смысл раздела и рабочая задача раздела;
- description = короткое спокойное объяснение раздела;
- actions размещаются справа в зоне actions существующего header;
- meta badges/chips показываются только если это реальные данные;
- второй hero/header внутри body запрещён.

## 4. Page Header Typography

Стандарт заголовка закрепляется по текущему эталону `Страницы`:

- eyebrow uppercase, фиолетовый, с letter-spacing;
- main title остаётся компактным manager title, а не display-hero;
- description использует muted спокойный стиль;
- title не должен превращаться в обычный случайный sentence-case paragraph;
- внутри body нельзя создавать произвольные огромные заголовки, дублирующие header.

## 5. Context Card Standard

Context card используется для внутренних рабочих режимов:

- располагается сразу под большим page header;
- показывает текущий контекст пользователя;
- слева содержит uppercase label и основной context title/subline;
- справа содержит релевантные actions;
- применяется в `Секции`, `Виджеты`, `Поп-апы`;
- будущие рабочие режимы должны использовать его вместо случайных hero-блоков.

## 6. Context Card Variants

### Variant A — Entity Context

Для режимов, где редактируется структура конкретной сущности.

Пример:

- `ТЕКУЩАЯ СТРАНИЦА`
- `Тестовая страница`
- `[К списку страниц]`

### Variant B — Page-Level Layer Context

Для page-level слоёв одной страницы.

Примеры:

- `ВИДЖЕТЫ СТРАНИЦЫ [?]`
- `Тестовая страница`
- `[К списку страниц] [Добавить виджет]`

- `ПОП-АПЫ СТРАНИЦЫ [?]`
- `Тестовая страница`
- `[К списку страниц] [Добавить поп-ап]`

### Variant C — Tool Context

Для внутренних инструментов раздела.

Пример будущего паттерна:

- `ЦВЕТОВАЯ БИБЛИОТЕКА [?]`
- `Цвета, градиенты и паттерны сайта`
- `[Вернуться в раздел Дизайн]`

## 7. Section Landing Cards Standard

Для разделов, где внутри будет несколько инструментов:

- раздел не должен сразу разворачивать один инструмент на весь экран, если архитектурно это tool section;
- внутри landing section должны быть cards/tools;
- инструмент открывается через явное action вроде `Настроить`;
- карточка инструмента показывает title, description и понятное action;
- паттерн обязателен для будущих design/tools sections, включая будущую цветовую библиотеку.

## 8. Workspace Body Standard

- body начинается после context card;
- body не дублирует заголовок из context card;
- body не дублирует primary action, если этот action уже стоит в context card;
- внутри body размещаются list/grid/empty state/editor;
- запрещены лишние hero/cards, повторяющие тот же смысл.

## 9. Panel Standard

Для двухколоночных и составных рабочих областей:

- panel title обязателен;
- help icon ставится рядом с panel title;
- action, если нужен, размещается справа от title row;
- контент панели начинается после понятного заголовочного ряда;
- spacing должен визуально отделять heading от content;
- glued elements и слипшиеся control rows запрещены.

## 10. Cards Standard

Для section/block/widget/popup cards:

- drag handle размещается отдельно слева;
- icon tile отделён от drag handle;
- card показывает title;
- card показывает type label;
- card показывает meta badges;
- actions размещаются справа;
- active state должен быть заметен, но не шумен;
- вертикальный слом текста недопустим;
- icon tile не должен “плавать” отдельно от content;
- `settings/delete` должны иметь приоритет над card select;
- drag handle должен быть визуально отличим от смысловой иконки.

## 11. Badge Standard

- statuses являются состояниями, а не действиями;
- корректные статусы: `Включён`, `Выключен`;
- некорректный badge-текст: `Включить виджет`;
- enabled state использует success-soft presentation;
- disabled state использует danger или muted-danger soft presentation;
- position, trigger и type metadata используют neutral/violet-soft presentation;
- badge должен выглядеть как badge, а не как plain text;
- счётчики обязаны использовать корректную русскую форму.

## 11.1 Form vs Content Rule

- shared component form is fixed by the `Страницы` reference and the shared library helpers;
- only content may vary between usages: title, subtitle, badge text, entity name, help key, counters;
- changing wording is allowed only through i18n and only without changing shared DOM anatomy;
- changing anatomy, placement, spacing or selector contracts to fit new content is forbidden.

## 12. Help Icon / Tooltip Standard

- используется маленькая question icon, а не большая secondary button;
- иконка ставится рядом с heading, context label или panel title;
- help content приходит через Help Library key;
- открытие работает по hover, focus и click;
- `Escape` и click outside закрывают popover;
- popover должен оставаться viewport-safe;
- длинные объяснения живут в tooltip, а не дублируются видимым абзацем рядом.

## 13. Button Placement Standard

- primary action размещается в page header или context card;
- в body не должно быть дубля той же primary action;
- тексты desktop buttons должны оставаться в одну строку;
- `К списку страниц` или `Вернуться…` ставятся левее primary add action;
- glued buttons и случайные action clusters запрещены.

## 14. Modal Standard

- add modal, settings modal и destructive modal используют общий contract;
- modal состоит из header, body и footer;
- close button обязателен;
- destructive modal должен содержать item name;
- удаление без подтверждения запрещено;
- destructive style должен читаться визуально сразу.

## 15. Empty State Standard

- empty state должен быть спокойным и честным;
- fake buttons запрещены;
- слова вроде `заглушка` или `будущий этап` в видимом UI запрещены;
- если action реально работает, кнопку можно показать;
- если action не работает, показывать фейковое действие нельзя.

## 16. Russian Text / I18N Standard

- все видимые строки идут через `includes/i18n/ru.php`;
- English UI запрещён;
- i18n keys не содержат кириллицу;
- hardcoded RU в JS запрещён;
- сухие безличные заглушки нежелательны;
- по возможности избегается сиротский перенос одного слова;
- счётчики используют корректную русскую плюрализацию.

## 17. Future Section Requirements

Каждый будущий Codex prompt для section UI должен включать:

- использовать `SONYRA_MANAGER_SECTION_UX_STANDARD.md`;
- перед изменением провести аудит текущего section/workspace;
- не создавать второй hero/header;
- использовать context card pattern там, где есть внутренний рабочий режим;
- подключать help icons через Help Library;
- соблюдать badge standard;
- соблюдать button placement standard;
- отдавать финальный visual/UX report.

## 18. Color Library Future Pattern

- `Дизайн` должен развиваться как landing section, а не как один fullscreen tool по умолчанию;
- будущий экран цветовой библиотеки должен открываться как tool screen внутри раздела `Дизайн`;
- visible name инструмента должен быть `Цветовая библиотека`;
- technical naming `Color Controller` может сохраняться в архитектуре, store и service layer;
- tool screen цветовой библиотеки обязан использовать Variant C tool context card.

## 19. Component-Level Visual Details Standard

### Context label standard

- context labels являются service labels, а не primary accent;
- для context label запрещён произвольный фиолетовый accent, если это не active navigation state;
- используется тот же muted selector/pattern, что и в `Страницы`, прежде всего `.sonyra-pages-section .sonyra-pages-sections-context-label`;
- `font-size`, `font-weight`, `line-height`, `letter-spacing`, `color`, uppercase-подача и gap до title должны повторять эталонный selector, а не подбираться визуально;
- если в target-screen label стоит рядом с help icon, используется тот же label-row pattern и тот же gap, что у эталона `Страницы`.

## 20. Color Library Visual Editor Standard

- цвета в библиотеке показываются compact cards со swatch, названием и HEX;
- обычный цвет не использует большой preview banner;
- RGB не редактируется вручную в основном сценарии;
- системный ключ и служебные descriptions не показываются на первом уровне UI;
- gradient editor использует visual preview, два swatch control и direction chips;
- pattern editor использует grouped visual gallery, preview и только смысловые controls;
- image pattern хранится через WordPress attachment ID, а не через внешний URL или base64.

### Help icon standard

- help icon обязан переиспользовать тот же DOM/class pattern, что и `Секции` / `Блоки секции`;
- canonical pattern: `.sonyra-help-tooltip-wrap` + `.sonyra-help-tooltip-trigger` + `.sonyra-help-popover`;
- trigger остаётся маленьким round secondary control, а не accent button;
- `padding`, `border-radius`, `background`, `border`, `color`, `hover`, `focus`, `icon size`, `line-height` и vertical alignment берутся из эталонного selector;
- per-screen custom visuals для help icon запрещены;
- tooltip content приходит только через Help Library key.

### Help Tooltip Layering Standard

- help tooltip всегда отображается поверх соседних panels, cards, toolbars, tables и workspace content;
- tooltip не должен перекрываться контентными плашками, даже если открыт между двумя glass/panel blocks;
- tooltip использует стабильный shared z-index standard value, а не случайные per-screen числа;
- tooltip layer должен жить вне clipping/overflow проблемных parent containers, если layout этого требует;
- parent containers новых context cards, panels и landing cards не должны обрезать tooltip;
- при любом новом help icon Codex обязан проверить hover/click tooltip на перекрытие и viewport safety;
- если tooltip перекрывается соседним panel/card/toolbar, UI patch не принимается.

### Tooltip Typography Rule

- tooltip rendered into the global help layer must explicitly use manager typography;
- relying on accidental inheritance is forbidden;
- browser serif/Times fallback is a patch rejection even if layout is otherwise correct.

## Zero Regression Rule

- refactor-only library extraction must not visually or behaviorally change accepted manager UI;
- if shared helper migration changes spacing, icon placement, button order, tooltip behavior or modal anatomy, the patch is rejected;
- every shared helper extraction must be verified against the original `Страницы` reference before archive build.

### Back action standard

- любой action вида `Вернуться…` / `К списку…` обязан использовать стандартный back button pattern;
- canonical button pattern берётся с `К списку страниц`;
- left arrow icon обязателен и ставится перед текстом;
- размер icon, gap между icon и text, `min-height`, `padding`, `font-size`, `font-weight`, `border-radius`, `border`, `background`, normal/hover/focus state должны совпадать с эталоном;
- plain text-only back actions запрещены;
- custom button shape для back action запрещён.

### Toolbar Meta Counters Standard

- counters inside manager toolbars должны выглядеть как meta chips, а не как loose text line;
- chips используют neutral/soft presentation, а не active violet chip и не primary action styling;
- counters живут внутри padded toolbar area и не касаются right edge;
- gap между chips должен оставаться compact и стабильным;
- mobile wrap обязателен и не должен ломать tabs row;
- dot-separated loose text вида `6 цветов · 1 градиент · 1 паттерн` запрещён, если пользователь явно не одобрил именно такой format.

### Back Action Vertical Alignment Standard

- все back/navigation actions внутри context cards должны быть vertically centered;

## 20. STANDARD-FIRST / NO IMITATION RULE

- Codex не имеет права создавать похожие компоненты;
- Codex не имеет права создавать альтернативную DOM-разметку для существующего компонента;
- Codex не имеет права создавать локальные modal, card, button, help, toolbar, chip, badge, notice, footer и destructive-modal implementations;
- если компонент существует в `docs/SONYRA_MANAGER_COMPONENT_INVENTORY.md`, он должен быть переиспользован;
- если shared helper отсутствует, Codex обязан сначала вынести эталонный компонент в shared helper;
- любое `похоже`, `аналогично`, `в том же стиле`, но не тот же helper/classes/selectors — patch rejection;
- любой новый UI-компонент разрешён только после явного user approval и добавления в component inventory.

## 21. Mandatory UI Patch Process

1. Read UX standard.
2. Read component inventory.
3. Identify required components.
4. Map each target component to an existing inventory item.
5. Reuse the same shared helper, classes and selectors.
6. Provide Component Reuse Proof table.
7. Provide Required Markup Proof table.
8. STOP if any target component cannot be mapped.

## 22. Layout / Markup Placement Standard

Для каждого inventory component обязательно фиксируются:

- родительский wrapper;
- внутренние header/body/footer sections;
- left/right zones;
- icon zone;
- content zone;
- actions zone;
- warning slot;
- meta slot;
- help icon slot.

Также обязательно фиксируются:

- точный порядок элементов;
- что слева, справа, сверху и снизу;
- что идёт в одну строку, а что отдельной строкой;
- где action area;
- где help icon;
- где SVG icon tile;
- padding wrapper/header/body/footer;
- gap между icon и title;
- gap между title и subtitle;
- gap между body и warning slot;
- gap между footer buttons;
- min-height buttons;
- icon tile size;
- SVG icon size;
- border-radius, border, shadow, line-height, letter-spacing.

Запрещённые отклонения:

- нельзя менять порядок элементов;
- нельзя переносить action zone в другое место;
- нельзя менять icon tile placement;
- нельзя менять footer button order;
- нельзя создавать похожую DOM-структуру;
- нельзя добавлять отдельные wrappers, если их нет в эталоне;
- нельзя менять SVG tile size, background, border, radius;
- нельзя менять button placement;
- нельзя менять modal header/body/footer anatomy.

## 23. Raw I18N Key Stop Rule

- raw translation keys must never appear in visible UI, modal copy, badges, tooltips or notices;
- JS fallback cannot expose the key string to the user;
- if a new shared helper needs additional strings, the payload contract and `includes/i18n/ru.php` must be updated in the same patch;
- a raw key in browser output is a release stop.

## 23. Shared Component Regression Rule

- shared helper/classes сами по себе не считаются достаточным доказательством переиспользования;
- после refactor или выноса в shared root Codex обязан доказать, что typography, SVG/icon sizing, spacing, z-index и behavior совпадают с reference component;
- если компонент визуально похож, но behavior сломан — patch failed;
- если behavior работает, но typography отличается — patch failed;
- если help icon существует, но tooltip не открывается по hover, focus и click — patch failed;
- если destructive modal использует fallback/browser font — patch failed.
- action area должна держать `align-items: center`;
- icon и text внутри button должны быть aligned center без визуального смещения вверх/вниз;
- line-height и vertical padding не должны поднимать label или icon;
- target обязан визуально совпадать с pattern `К списку страниц`.

### Tabs/meta panel standard

- segmented tabs и meta counters живут внутри одной padded panel row;
- canonical container pattern берётся из `.sonyra-pages-toolbar-panel`;
- canonical tabs group pattern берётся из `.sonyra-pages-filter-group` и `.sonyra-pages-filter-chip`;
- первая tab не должна касаться левого edge panel;
- counters/meta row не должна касаться правого edge panel;
- desktop pattern: tabs слева, counters справа;
- mobile pattern: clean wrap с теми же стандартными gap, без схлопывания spacing;
- oversized stats cards запрещены, если inline counters уже достаточно.

### Manager Footer Standard

- footer является global manager shell element, а не section-specific block;
- footer должен иметь стабильный spacing и сверху, и снизу;
- footer не должен прилипать к viewport edge;
- footer не должен прилипать к последней content card/panel;
- short-content и long-content screens проверяются одинаково;
- per-section footer hacks запрещены.

### Spacing standard

- каждый internal tool screen обязан копировать эталон `Страницы` для `context card padding`, `body panel padding`, `tabs panel padding`, `row gaps`, `gap between label and help icon`, `gap between context card and workspace body`, `gap between tabs panel and cards grid/list`;
- нельзя клеить controls/text к краям panel или viewport;
- нельзя использовать arbitrary “почти такие же” spacing values, если эталонный selector уже существует;
- при отсутствии централизованного utility нужно либо переиспользовать existing class, либо документировать extracted standard value до merge.

### Final visual report requirement

- для каждого будущего UI patch обязателен DOM/CSS match table;
- в таблице должны быть: reference component from `Страницы`, target component, DOM/classes, CSS selectors, declared/computed values;
- обязательно фиксируются: `color`, `font-size`, `font-weight`, `line-height`, `letter-spacing`, `padding`, `margin/gap`, `border-radius`, `border`, `background`, `min-height`, `icon size`, `vertical alignment`;
- если target заметно отличается от reference и difference заранее не одобрен пользователем, Codex должен остановиться до внесения такого отклонения.

### Unique Name Per Scope Standard

- нельзя создавать сущности с одинаковым названием внутри одного локального scope;
- scope определяется коллекцией или локальным контейнером данных;
- `colors`, `gradients` и `patterns` проверяются независимо друг от друга;
- одинаковое название в разных scope допустимо;
- duplicate validation обязательна и на frontend, и на backend;
- ошибка должна быть человекочитаемой и идти через i18n.

### Pattern Gallery Accordion Standard

- длинные галереи паттернов внутри modal body группируются в accordion-блоки;
- выбранная группа открыта по умолчанию;
- collapse/expand state стабилен в пределах одной открытой модалки;
- выбор tile не должен сбрасывать scroll position modal body;
- modal-over-modal для выбора паттерна запрещён.

### Success Notice Auto-dismiss Standard

- success notices auto-dismiss через `5000ms`;
- close button остаётся доступной;
- error и warning notices не исчезают быстро;
- все section notices должны использовать shared manager behavior, а не хаотичные локальные таймеры.

### Create/Edit Title Standard

- titles of modal and editor screens use noun forms: `Создание …`, `Редактирование …`;
- action buttons keep verb forms: `Создать`, `Сохранить`, `Отменить`, `Удалить`, `Вернуться`;
- mixing command wording into titles is forbidden;
- converting action buttons into noun forms is forbidden.

### Pattern Editor Result Panel Standard

- desktop pattern editor uses a fixed left result panel and independently scrollable right controls;
- mobile pattern editor uses a fixed top result panel and independently scrollable lower controls;
- result panel contains horizontal preview, entity name and back action;
- preview must remain visible while controls scroll;
- vertical oversized preview is forbidden;
- horizontal scroll is forbidden.

### Compact Controls Standard

- compact sliders show the current value next to the control;
- switch is used for binary functions only;
- segmented controls or chips are used for multi-option choices;
- checkbox UI is forbidden;
- generator picker and settings groups may collapse to preserve vertical space.

### Range Fill Standard

- slider fill must end exactly at the thumb position;
- fill percentage uses the normalized formula `(value - min) / (max - min) * 100`;
- initial render and every input/update must set the same fill value;
- mismatch between fill and thumb is a release stop.

### Pattern Editor Functional Repair Standard

- modal header must always keep visible title and subtitle in create and edit flows;
- control-panel scroll must stay stable during slider, switch and color input updates;
- accordion groups must preserve open state through rerender and input updates;
- visible pattern controls must update both preview and save payload;
- standalone `Включено` / `Выключено` text near a switch is forbidden.

### Pattern Editor Full Rebuild Standard `0.1.94`

- Pattern Editor must use one coherent shell: left/top result panel plus right/bottom controls panel;
- result panel must be a finished premium card, not a technical split column;
- pattern name, type and back action belong to the same result-card area as the preview;
- controls panel must use one accordion primitive for generator and all groups;
- dots editor uses only `Точки`, `Слои и цвета`, `Эффекты`;
- meaningless groups like generic `Основное` or `Форма` are forbidden for dots;
- every visible control must satisfy one product chain: control → state → preview → payload → reload;
- controls that do not affect preview must not be visible;
- ordinary slider/switch/segmented/color updates must not rebuild the full modal shell;
- generator switch may rebuild only the controls panel while result card and header remain stable;
- one range helper and one switch-row helper must be reused across the editor;
- decorative accordion, decorative switch and decorative controls are forbidden;
- if a visible control saves but does not affect preview, the patch fails;
- if a visible control changes preview but is missing from payload or saved reload, the patch fails.
