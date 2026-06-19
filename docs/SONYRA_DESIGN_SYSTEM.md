# SONYRA Design System

## Назначение

Этот документ фиксирует контракт будущей дизайн-системы SONYRA Site Manager.

Интерфейс должен выглядеть как готовый premium SaaS-продукт, а не как техническая админка.

UX-эталоном для manager sections является раздел `Страницы`, а формальный section/workspace standard закреплён в `docs/SONYRA_MANAGER_SECTION_UX_STANDARD.md`.

## Общие правила SONYRA

Во всех будущих design-этапах используются только канонические написания:

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

## Design foundation

Дизайн строится через:

- tokens;
- components;
- layout;
- states.

Случайные цвета в отдельных экранах запрещены.

Случайные размеры, тени, радиусы и отступы запрещены.

Будущие файлы design foundation:

- assets/design/sonyra-tokens.css
- assets/design/sonyra-components.css
- assets/design/sonyra-layout.css
- assets/design/sonyra-states.css
- assets/design/sonyra-manager.css

## Component states

Все компоненты должны иметь состояния:

- обычное;
- наведение;
- фокус;
- загрузка;
- успех;
- ошибка;
- отключено;
- пустое состояние.

## Color Library premium rule

- Color Library is a visual system editor, not a developer form;
- compact swatches are the primary color interaction model;
- gradients and patterns must present live previews before save;
- image-based patterns must use WordPress media storage and safe attachment metadata.

## Button Icon Semantics Standard

- `Создать` and `Добавить` actions use `plus`
- `Настроить`, `Редактировать` and `Параметры` actions use `settings`
- `Вернуться` uses `arrow-left`
- semantic icon mismatch is a UI regression and must be fixed before archive

## Standard-First / No Imitation

- дизайн-система SONYRA Site Manager работает только через standard-first reuse;
- reference inventory закреплён в `docs/SONYRA_MANAGER_COMPONENT_INVENTORY.md`;
- если компонент уже существует, допускается только reuse того же helper, classes, selectors и DOM anatomy;
- локальные per-section компоненты запрещены;
- shared SVG/icon tile rule обязателен;
- shared buttons rule обязателен;
- shared manager UI library `assets/js/sonyra-manager-ui.js` обязателен для reuse DOM anatomy в manager JS;
- shared modal rule обязателен;
- destructive modal допускается только через один shared helper;
- custom destructive modal markup, custom danger button и custom danger icon tile запрещены.

## Обязательные компоненты

Design system должен описывать и поддерживать:

- кнопки;
- карточки;
- панели;
- поля ввода;
- переключатели;
- бейджи;
- модальные окна;
- выезжающие панели;
- уведомления;
- боковое меню;
- верхняя панель;
- пустые состояния;
- состояния загрузки;
- состояния ошибки;
- состояния успеха;
- прогресс;
- карточки дашборда;
- карточки модулей;
- панель кода вставки;
- управление логотипом;
- управление градиентами.

Видимые названия компонентов в пользовательском UI должны быть по-русски. Технические имена классов и файлов могут быть на английском.

## Layout contract

Layout должен обеспечивать:

- предсказуемую manager-навигацию;
- читаемые рабочие области;
- responsive-поведение для desktop, tablet и mobile;
- отсутствие визуального хаоса при пустых, загрузочных и ошибочных состояниях;
- совместимость с будущими module screens.

## Section-module boundary

Section modules живут внутри одного manager shell и не подменяют shared layout contracts.

Manager shell dispatches sections, а section-module отвечает только за свой view, JS scope, CSS scope и service methods.

## Page Elements Library Pattern

Для раздела `Страницы` библиотека page elements подключается как section-module contract, а не как отдельный shell.

Stage 1 pattern:

- `Добавить секцию` открывает modal library;
- modal `Добавить секцию` работает только в single-select режиме;
- modal показывает только реально добавляемые core sections;
- выбор одной section добавляет ровно одну section instance и сразу закрывает modal;
- повторный click во время добавления блокируется до ответа backend;
- type label, icon и description берутся из Page Elements Library Contract;
- section instance сохраняется в текущий page store;
- action-кнопки section card обязаны быть рабочими;
- удаление section всегда идёт через destructive confirmation modal;
- preview/public output использует сохранённые данные без fake header/footer.

Stage 2 pattern:

- right column `Блоки секции` показывает реальные block cards;
- block card использует отдельный drag handle и отдельный icon tile;
- Add Block modal использует card grid с single-select behavior;
- block settings modal использует минимальный field set без advanced styles.

## Block Card Pattern

- drag handle отделён от смысловой icon tile;
- card показывает block name, type label и actions;
- active state заметен, но не шумный.

## Add Block Modal Pattern

- modal `Добавить блок` использует сетку карточек;
- одна карточка = один block type;
- длинные описания не склеиваются и не ломают сетку.

## Block Drag Handle Pattern

- handle использует существующий drag standard;
- `aria-label` для blocks: `Перетащить блок`;
- handle не должен мимикрировать под block icon.

## Visual acceptance rule

Любой UI/design-этап не принят, если Codex не дал визуальный отчёт:

- DOM-структура;
- CSS-слои;
- responsive-поведение;
- состояния элементов;
- i18n-источник видимых строк;
- отсутствие английских UI-слов;
- рабочая цепочка controls.

## Login shell design pattern

Login shell должен использовать premium SaaS pattern:

- centered auth card;
- brand area;
- clean SaaS background;
- identifier input “Электронная почта или логин”;
- code input state;
- primary action button;
- secondary text actions;
- accessible inputs;
- status messages;
- mobile responsive pattern;
- no raw technical styling;
- no global selectors;
- no password field on first release.

## Manager page header standard

Начиная с версии `0.1.64`, protected `/manager` использует единый premium SaaS page header:

- section metadata является единым источником для nav label, page title, kicker, description, `icon_key`, actions и осмысленных badge/chips;
- page header состоит из icon tile, text stack, optional meta/chips row и optional actions-zone;
- page header kicker не повторяет brand name “ПУЛЬТ САЙТА”;
- page title компактнее hero/display-заголовков и не делит CSS selector с hero/placeholder card titles;
- description берётся из i18n section metadata и не дублируется в JS;
- icon tile использует только `Sonyra_Site_Manager_Icon_Renderer` и `icon_key` из SONYRA Icon Library;
- background `/manager` следует approved login background pattern;
- visible UI strings остаются только через i18n.

Раздел `Страницы` является master UX reference для page header, workspace context card, panel titles, cards, badges и help icons.

### Page header exists rule

Во всех разделах ПУЛЬТА САЙТА уже есть общий page header. Рабочая зона раздела должна начинаться сразу после этого header.

Запрещено:

- создавать второй header внутри рабочей области раздела;
- создавать второй hero-блок ради заголовка, статусов или кнопок;
- дублировать название раздела внутри контента крупным заголовком;
- делать повторные заголовочные прослойки вроде “Публичный сайт / Страницы”;
- переносить заголовок раздела из общего page header в рабочую область.

Разрешено:

- менять существующий page header;
- менять надзаголовок, основной заголовок и описание;
- менять `icon_key`;
- добавлять actions справа, если это реальные действия раздела.

### Header typography and SVG alignment

Общий header для всех разделов использует компактный premium SaaS pattern:

- SVG icon tile слева;
- текстовая группа справа от иконки;
- надзаголовок маленький, фиолетовый, разряженный, uppercase;
- основной заголовок примерно в 3 раза крупнее надзаголовка;
- основной заголовок не должен быть чрезмерно жирным, огромным или с раздутым `line-height`;
- описание идёт ниже заголовка спокойным размером;
- header остаётся компактным, без hero-space.

SVG alignment contract:

- SVG/icon tile выравнивается по зоне “надзаголовок + основной заголовок”;
- верх icon tile примерно на уровне верхней границы надзаголовка;
- низ icon tile примерно на уровне нижней границы основного заголовка;
- описание не влияет на вертикальное положение SVG;
- запрещено центрировать SVG по полной высоте header вместе с описанием.

### Page Header Typography Fixed Scale

Все page headers ПУЛЬТА САЙТА используют фиксированную числовую шкалу. Подбирать значения визуально запрещено.

Desktop, `1024px+`:

- container: `padding: 22px 24px`, `border-radius: 24px`, `min-height: auto`;
- icon tile: `56px` × `56px`, `border-radius: 18px`, `flex: 0 0 56px`;
- icon SVG: `24px` × `24px`;
- icon-to-text gap: `16px`;
- text-to-actions gap: `20px`;
- eyebrow: `13px` / `16px`, `font-weight: 700`, `letter-spacing: 0.16em`, `text-transform: uppercase`, `margin-bottom: 6px`;
- title: `34px` / `37px`, `font-weight: 680`, `letter-spacing: -0.035em`, `max-width: 760px`, `text-transform: none`;
- title hard limits: max `36px`, max `font-weight 700`;
- description: `16px` / `24px`, `font-weight: 500`, `margin-top: 10px`, `max-width: 860px`;
- actions: `display: flex`, `align-items: center`, `justify-content: flex-end`, `gap: 10px`, `flex-wrap: nowrap`;
- badges/chips: `12px`, `height: 28px`, `line-height: 28px`, `font-weight: 650`, `padding: 0 10px`, `border-radius: 999px`.

Tablet, `768px–1023px`:

- container: `padding: 20px`;
- icon tile: `52px` × `52px`;
- icon SVG: `22px` × `22px`;
- eyebrow: `12px` / `15px`;
- title: `30px` / `34px`, `font-weight: 660`, `max-width: 680px`;
- description: `15px` / `23px`.

Mobile, `<768px`:

- container: `padding: 18px`;
- icon tile: `48px` × `48px`;
- icon SVG: `21px` × `21px`;
- eyebrow: `11px` / `14px`, `letter-spacing: 0.14em`;
- title: `26px` / `30px`, `font-weight: 650`, `letter-spacing: -0.03em`;
- description: `14px` / `21px`, `margin-top: 8px`;
- actions: `margin-top: 14px`, `flex-wrap: wrap`.

Запрещено:

- no title 40px+;
- no title font-weight 800/900;
- no uppercase main title;
- no duplicate header/hero;
- no icon centering by full header height;
- no fake badges.

Release audit anchors: Page Header Typography Fixed Scale, `34px`, `37px`, font-weight 680, no title 40px, no uppercase main title, icon tile 56, badges 12px, height 28px.

## Manager Section UX Standard

Начиная с релизного патча `0.1.76 UX Standard Patch`, все будущие manager sections и внутренние workspace-экраны должны опираться на `docs/SONYRA_MANAGER_SECTION_UX_STANDARD.md`.

Краткий summary стандарта:

- `Страницы` — master UX reference, а не просто один из разделов;
- новый section не должен придумывать свой layout, если аналогичный паттерн уже есть в `Страницы`;
- page header остаётся единым top-level header manager shell;
- внутренние рабочие режимы используют context card вместо второго hero/header;
- cards используют единый card anatomy: drag handle, icon tile, title, type label, badges, actions;
- badges показывают state/meta, но не action wording;
- small help icons рядом с headings/context labels/panel titles идут через Help Library.

Для раздела `Дизайн` в текущем `0.1.76` это означает:

- общий page header показывает только section-level смысл, без частных color counters;
- вход в `Дизайн` открывает landing section, а не сразу fullscreen tool;
- `Цветовая библиотека` открывается отдельным tool screen;
- tool screen использует context card и compact tabs/meta panel;
- toolbar counters внутри tool screen должны быть meta chips, а не loose text;
- back action в context card должен быть vertically centered;
- manager footer spacing относится к global shell и не чинится section-local hacks;
- дополнительная library-tab внутри `Цветовая библиотека` не показывается.

## Pages Table Pattern v1

Главный экран раздела `Страницы` использует полноширинный premium SaaS table pattern без второго hero/header внутри рабочей области.

Обязательный контракт:

- колонки строго: `Страница / В меню / Обновлена / Действия`;
- отдельные колонки `Статус`, `Главная`, `Порядок` запрещены;
- статус, `Главная`, `В меню` показываются compact badges внутри колонки `Страница`;
- колонка `В меню` показывает `header/footer` menu icon + `№` позиции, а при отсутствии меню показывает только `—`;
- действия строки доступны только через dropdown `⋯`;
- порядок dropdown фиксирован: `Параметры`, `Секции`, `Открыть`, `Предпросмотр`, `Копировать ссылку`, `Сделать главной`, `Скрыть`, `SEO-настройки`, `История изменений`, `Дублировать`, `divider`, `Удалить`;
- future dropdown actions можно показывать disabled как roadmap reminders;
- disabled future actions обязаны быть pale, `aria-disabled`, без click handler и без fake result;
- `Удалить` всегда нижний, красный, после divider и открывает destructive confirmation modal;
- toolbar обязан содержать поиск с magnifier icon и placeholder `Найти страницу…`;
- toolbar is a separate premium panel above table card;
- toolbar is not inside table header;
- filter chips use premium segmented/chip group;
- sort control uses product style, not raw browser look;
- filters строго: `Все / Опубликованы / Черновики / Скрытые / В меню`;
- sorting строго: `Обновлены недавно / Сначала новые / А–Я / Я–А / По меню`;
- table header and row must share one `grid-template-columns` variable;
- updated column date/time must align under header;
- actions dropdown must open on `⋯`;
- click outside/Escape close dropdown;
- one dropdown open at a time;
- `updated_at` is backend-controlled with `gmdate('c')`;
- `updated_at` в таблице отображается по browser/device timezone через JS `Intl.DateTimeFormat`;
- server timezone, IP timezone и VPN timezone запрещены для видимого времени таблицы.

## Pages Toolbar & Dropdown Polish Standard v1

- filter group has static muted filter SVG icon before chips;
- sort label is icon-only, no visible word `Сортировка`;
- sort icon is static muted SVG before sort control;
- toolbar groups use clear horizontal separation;
- filters remain segmented/chips, not raw buttons;
- icons are not buttons and have no background/border;
- accepted icons `Параметры`, `Секции`, `Открыть` must not be changed in future polish patches unless explicitly requested;
- only requested dropdown icons may be changed;
- `Сделать главной` uses home icon, not check;
- `Копировать ссылку` uses link/copy-link icon, not page/file icon;
- `Предпросмотр` must not use the same eye icon as publication actions;
- `Скрыть` uses eye-off;
- `Опубликовать` uses eye;
- `SEO-настройки` uses world-search/search icon, not settings gear;
- `История изменений` uses history/clock/timeline icon, not page/file icon;
- `Дублировать` uses copy/duplicate icon, not page/file icon;
- `Скрыть` must not be renamed to `Снять с публикации`;
- future disabled items remain visible but inactive.

## Dropdown Placement Standard

- actions dropdown auto-places down/up based on viewport space;
- when full height does not fit, dropdown uses constrained `max-height` and internal scroll;
- wheel/scroll/touch inside dropdown does not close it;
- click outside closes only when target is outside trigger and menu;
- `Escape` closes dropdown;
- only one dropdown is open at a time.

## Dynamic Publish Action Standard

- only labels `Скрыть` and `Опубликовать` are allowed;
- `Скрыть` uses `eye-off`;
- `Опубликовать` uses `eye`;
- empty label is forbidden;
- `Снять с публикации` is forbidden.

## Loading / Hydration State Standard

- loading state is separate from empty state;
- empty state is shown only after confirmed loaded empty data;
- fake empty flash on reload is forbidden;
- temporary render of another section as fallback is forbidden;
- current section must be restored before visible render.

## Notice / Status Banner Standard

- every notice/status/success/warning/error/info/loading banner has SVG icon before text;
- notice icon uses `currentColor`;
- icons are rendered only from SONYRA Icon Library;
- success auto-dismisses after `5000ms`;
- info auto-dismisses after `6000ms`;
- warning and error remain closable without fast auto-dismiss;
- close button is placed on the right;
- raw SVG, emoji, and CDN icons are forbidden.

## Section Header Badges Standard

- badges live under description inside the existing page header;
- Color Controller is the system design layer for colors, gradients, patterns and future reusable design tokens;
- raw CSS from users is not accepted for design configuration;
- future header, footer and global text styling should connect through Color Controller tokens instead of ad-hoc values;
- badges show only real live section summary;
- badges are not actions;
- badges are not separate cards;
- fake metrics are forbidden.

## Search Focus Stability Standard

- filtering must not recreate the focused search input;
- search focus and cursor position must remain during typing;
- table/list rows may rerender, but toolbar search DOM node must stay stable.

## Pages Home Slug Standard

- `is_home` affects public URL display only;
- `is_home` must not destroy stored slug;
- previous home page returns to its stored slug automatically;
- automatic rewrite of stored slug to `home` is forbidden.

## Global Modal Standard

- modal uses overlay/header/body/footer structure;
- header and footer use distinct surfaces from body;
- body scrolls inside modal;
- modal must not touch viewport top/bottom;
- standard applies to every modal in every section.

## Preview/Open Standard

- `Открыть` uses real public URL on domain slug;
- `Предпросмотр` uses protected technical route `/sonyra-preview/{slug}/`;
- preview opens as full page, not modal or embedded container;
- preview uses `noindex,nofollow` and `X-Robots-Tag: noindex, nofollow`;
- before Site section config, public/preview renderer uses clean content shell without fake header/footer.

### Header actions rule

Actions — это реальные быстрые действия раздела.

Примеры:

- Открыть сайт;
- Создать страницу;
- Сохранить;
- Добавить;
- Проверить;
- Обновить;
- Экспортировать.

Размещение:

- desktop: справа в существующем page header, в одну строку;
- mobile: аккуратный перенос или стек без поломки header.

Запрещено:

- добавлять fake actions;
- делать кнопку, если действие не реализовано;
- делать кнопку “Сохранить”, если save-flow не работает;
- делать кнопку “Открыть”, если нечего реально открыть;
- создавать второй header ради кнопок.

### Badge placement standard

Бейджи не являются actions. Бейджи нельзя бездумно размещать справа рядом с кнопками и нельзя использовать как декоративные fake-status.

#### Глобальные бейджи состояния раздела

Используются только если основаны на реальных данных всего раздела.

Примеры для “Страниц”:

- 3 опубликованы;
- 1 черновик;
- Главная выбрана;
- Есть скрытые страницы.

Где размещать:

- компактная meta/chips-строка внутри существующего page header;
- под описанием или рядом с описанием;
- не справа вместо actions;
- не отдельным hero-блоком;
- не декоративной плашкой.

#### Бейджи конкретного элемента

Если бейдж относится к конкретной странице, модулю, пользователю, документу или записи, он должен быть рядом с этим элементом.

Примеры для строки страницы:

- Опубликована;
- Черновик;
- Скрыта;
- Главная;
- В меню.

Где размещать:

- в строке списка;
- в карточке элемента;
- в редакторе рядом с названием элемента;
- рядом с соответствующим полем.

Не размещать такие бейджи в общем page header, если они не относятся ко всему разделу.

#### Предупреждения, ошибки, успех

Это не бейджи.

Использовать:

- Notice Center;
- inline alert;
- field-level validation.

Примеры:

- slug занят;
- страница не опубликована;
- сохранить не удалось;
- главная страница не выбрана.

Запрещено превращать ошибки/предупреждения в маленькие декоративные бейджи в header.

#### Статусы контролов

Если статус относится к конкретному контролу, он должен быть рядом с этим контролом.

Примеры:

- email подтверждён;
- ключ подключён;
- синхронизация включена;
- модуль требует настройки.

Не выносить такие статусы в header без причины.

### Fake badge prohibition

Запрещено:

- fake metrics;
- fake progress;
- fake status;
- бейджи “для красоты”;
- бейджи без реальных данных;
- бейджи, которые дублируют заголовок;
- бейджи, которые обещают будущую функцию;
- “готово”, “активно”, “фундамент”, “будущий этап”, если за этим нет реальной рабочей логики.

### Examples

Правильно:

```text
СТРАНИЦЫ
СОЗДАНИЕ, НАСТРОЙКА И ПУБЛИКАЦИЯ
Создавайте страницы, настраивайте адреса, собирайте секции и управляйте пунктами меню

[3 опубликованы] [1 черновик] [Главная выбрана]

Справа:
[Открыть сайт] [Создать страницу]
```

Неправильно:

- второй блок “Публичный сайт / Страницы”;
- повторный крупный заголовок “Страницы”;
- бейджи справа вместо кнопок;
- fake badge “Будущий этап”;
- fake badge “Основа”;
- бейдж “Меню готово”, если меню не реализовано;
- кнопка “Открыть сайт”, если сайт не открывается.

## Manager sidebar standard freeze

Начиная с версии `0.1.63`, sidebar invariant rail считается принятым стандартом.

Правила для будущих изменений:

- новые page sections добавляются через section metadata, а не ручным дублированием nav/view/header массивов;
- `icon_key` для nav и page header выбирается только из SONYRA Icon Library;
- nav item height, icon slot, SVG size, font-weight и gap не меняются без отдельного visual audit;
- collapsed и expanded nav grid model не должны различаться по геометрии icon slot;
- icon slot и SVG не должны иметь state-dependent geometry;
- “Выйти” остаётся последним action item, слегка отделённым, без pin-to-bottom;
- sidebar остаётся fixed/sticky rail, а main content сохраняет независимый scroll;
- hardcoded SVG в sidebar запрещены;
- `transition: all` для nav/icon/sidebar geometry запрещён.

## Extension UI standard

Будущие расширения обязаны использовать дизайн-систему базы.

Правила:

- расширения не приносят хаотичные стили;
- расширения используют cards, buttons, badges, toggles, page header и layout tokens базового manager UI;
- UI витрины расширений в “Модули” должен использовать те же компоненты;
- новые основные пункты меню не добавляются без отдельного архитектурного решения;
- extension UI должен соблюдать i18n, русский интерфейс, accessibility и security rules;
- расширение не может подменять sidebar, page header или global shell.

## STOP-условия

Немедленно остановиться, если design-этап требует случайных CSS-решений вне design system, английских видимых UI-строк, обхода i18n, обхода security rules или создания декоративных controls без рабочей цепочки.

## Pages Sections Workspace Pattern

- режим секций для `Страницы` открывается как отдельный workspace без повторного page header;
- в workspace показываются только compact context row, левая колонка секций и правая колонка блоков;
- left column title — `Секции`;
- right column title — `Блоки секции`;
- оба heading могут показывать help tooltip рядом с title;
- tooltip объясняет концепцию без перегрузки карточек;
- левая колонка секций использует desktop-пропорцию около `40%`;
- правая колонка блоков использует desktop-пропорцию около `60%`;
- параметры страницы, slug, статус, SEO, home и menu не показываются в workspace;
- редактирование секции и блока выполняется только через shared modal standard;
- удаление секции и блока выполняется только через destructive confirmation modal;
- reorder секций и блоков выполняется только drag handle, без arrow up/down;
- collapsed sidebar state должен применяться до first paint без expanded flicker.

## Section Card Standard

- drag handle слева;
- type icon из SONYRA Icon Library;
- title как основной текст карточки;
- muted type label второй строкой;
- settings action справа;
- danger delete action справа;
- settings/delete actions обязаны открывать рабочие modal flows;
- active section выделяется мягким violet border/background;
- grid карточки: `34px 34px minmax(0, 1fr) auto`;
- title и type label не должны ломаться вертикально по буквам.

## Help Tooltip Standard

- info icon ставится рядом с heading;
- hover/focus открывает tooltip;
- click/tap открывает tooltip на mobile;
- `Escape` и click outside закрывают tooltip;
- trigger не выглядит как тяжёлая button;
- visible text идёт только через i18n;
- tooltip может содержать 2–4 предложения, если нужно объяснение;
- все tooltips должны получать title/body из Help Library entries;
- help popover обязан учитывать viewport и не уезжать за экран.

## Drag Handle Standard

- drag handle визуально отличается от смысловой icon tile;
- handle использует grip/dots-grip visual, а не section type icon;
- cursor `grab` / `grabbing` обязателен;
- drag behavior сохраняется только через handle;
- future block drag handles следуют тому же standard.

## Future Localization Layout

- UI strings должны переносить более длинные localized labels без поломки layout;
- ширины и отступы нельзя жёстко строить только под русский текст;
- compact controls должны выдерживать более длинные German/French labels;
- headings, buttons и chips должны оставаться читаемыми и для коротких Chinese/Japanese labels.

## Destructive Modal Standard v2

- destructive modal использует class `sonyra-manager-modal--destructive`;
- warning icon всегда red и визуально сильнее обычной modal icon;
- title обязан включать item name;
- body обязан включать item name;
- для section deletion обязательно предупреждение про nested blocks;
- exclamation marks обязательны;
- danger action button красная;
- слабая generic modal для irreversible delete запрещена.

## Section Library Icon Standard

- `Акцентная секция` использует icon `sparkles`;
- `app-window` нельзя использовать для `Акцентная секция`, потому что он конфликтует с site/page meaning.

## Block Card Standard

- drag handle слева;
- block type icon из SONYRA Icon Library;
- block title как основной текст;
- muted type label второй строкой;
- settings action справа;
- danger delete action справа;
- text contract совпадает с section card;
- mobile layout не создаёт horizontal overflow.

## Section/Block Naming Standard

- default title берётся из type label;
- если base title свободен, он используется без suffix;
- suffix добавляется только при реальном конфликте текущих title;
- suffix считается в рамках текущей page для sections;
- suffix считается в рамках текущей section для blocks;
- renamed title освобождает base name снова.

## Sidebar Initial State Standard

- sidebar collapsed state читается из persisted storage максимально рано;
- collapsed geometry применяется до first visible paint;
- reload не должен показывать intermediate expanded state;
- shell layout не должен “разъезжаться” при reload свернутого sidebar.

## Widget Card Pattern

- widget card использует отдельный drag handle;
- icon tile отделён от drag handle;
- card показывает name, type, enabled state и position;
- actions settings/delete не должны конфликтовать с select/drag.

## Pattern Generator Standard `0.1.91`

- patten editor in `Цветовая библиотека` is a premium generator console, not a preset dump;
- one visual mechanic = one generator with adjustable controls;
- no duplicate preset categories such as `микроточки`, `мягкая сетка`, `диагональные линии` when the result can be produced by settings;
- image-based pattern editing is a dedicated editor with the same premium shell and grouped controls;
- compact control groups are preferred over giant full-width sliders and flat technical forms.

## No Paint UI Rule

- no freeform paint canvas;
- no raw CSS/SVG authoring surface in first-level product UI;
- no checkbox-heavy settings sheet;
- live preview stays curated and generator-based.

## Pattern Editor Layout Standard `0.1.92`

- pattern editors use a premium SaaS two-region layout instead of one technical form;
- desktop: fixed left result panel, scrollable right controls;
- mobile: fixed top result panel, scrollable lower controls;
- preview stays horizontal and behaves like a result card, not like a long canvas;
- generator picker stays compact and collapsible;
- mini-previews stay visually aligned and share one geometry.

## Create/Edit Title Standard

- titles use noun forms such as `Создание цвета`, `Редактирование градиента`, `Создание графического паттерна`;
- buttons use verbs such as `Создать`, `Сохранить`, `Отменить`;
- mixing these roles is forbidden in future SONYRA manager flows.

## Range Fill Standard

- range fill must visually match the thumb position on every slider;
- value labels remain visible and human-readable in `%`, `px` or `°`;
- a detached fill track is a visual regression.

## Pattern Editor Functional UX Repair `0.1.93`

- pattern modal header always shows explicit title and subtitle;
- left result panel uses a card surface and remains visible while controls scroll;
- slider input updates preview without resetting control-panel scroll;
- switch stays compact and does not duplicate state with extra text;
- only meaningful groups are shown for a given generator.

## Pattern Editor Full Rebuild `0.1.94`

- Pattern Editor is a schema-driven premium editor, not a patched collection of local remaps;
- one editor shell contains one result card and one controls panel;
- result card is the canonical place for pattern type, entity name, preview and back action;
- controls panel uses one accordion primitive for generator and all groups;
- one range helper and one switch-row helper define the live-control surface;
- visible controls exist only when they change the live preview and persisted payload;
- ordinary control updates do not rebuild the full modal shell;
- generator change may rebuild only the controls panel and must not recreate the full modal anatomy;
- dots generator uses `Точки`, `Слои и цвета`, `Эффекты` and must not regress to generic patchwork labels.

## Add Widget Modal Pattern

- modal показывает grid карточек виджетов;
- card имеет icon, label, description и category;
- один click создаёт ровно один widget instance.

## Widget Drag Handle Pattern

- drag handle имеет отдельный hit-area;
- курсор использует `grab`/`grabbing`;
- reorder должен реально менять storage order.

## Public Floating Widget Pattern

- public widgets рендерятся как fixed floating layer;
- spacing остаётся безопасным для mobile;
- widget layer не исполняет raw embed code.
## 0.1.75

- page-level layers `Виджеты` and `Поп-апы` use a shared context-header pattern.
- status/meta in page-level cards should remain badge-based, not plain text.
- public popups use overlay modal presentation and must not consume layout space in normal page flow.
