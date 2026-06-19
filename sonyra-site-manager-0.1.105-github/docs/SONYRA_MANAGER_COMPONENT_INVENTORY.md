# SONYRA Manager Component Inventory

## Назначение

- это официальный каталог разрешённых UI-компонентов ПУЛЬТА САЙТА;
- перед любым UI-патчем Codex обязан прочитать этот документ и `docs/SONYRA_MANAGER_SECTION_UX_STANDARD.md`;
- если нужный компонент не найден в inventory, Codex не имеет права придумывать похожий локальный компонент;
- раздел `Страницы` является master reference для component anatomy, DOM, classes, helpers и placement.

## STANDARD-FIRST / NO IMITATION

- разрешено только переиспользование существующего shared helper, DOM, classes и selectors;
- запрещены `похоже`, `аналогично`, `в том же стиле`, `почти такое же`, локальные копии и альтернативная разметка;
- если shared helper отсутствует, сначала выносится эталонный компонент, потом к нему подключаются target usages;
- любой новый UI-компонент разрешён только после явного user approval и добавления в inventory.

## Mandatory Process

1. Read UX standard.
2. Read component inventory.
3. Identify required components.
4. Map each target component to an inventory item.
5. Reuse the same helper/classes/selectors.
6. Provide Component Reuse Proof.
7. Provide Required Markup Proof.
8. STOP if any target component cannot be mapped.

## Shared UI Library Rule

- canonical shared manager UI library file: `assets/js/sonyra-manager-ui.js`;
- reusable manager anatomy must be exported from this file before any new screen consumes it;
- release `0.1.79` shared helpers are: `renderButton(config)`, `renderIconTile(config)`, `renderHelpTrigger(config)`, `bindHelpTooltipLayer(root, config)`, `renderMetaChip(config)`, `renderToolbar(config)`, `renderContextCard(config)`;
- shared library is accepted only after the full enforcement chain is proven: file → enqueue → load order → helper exists on `window` → helper called by renderer → helper DOM reaches browser → CSS matches reference → behavior matches reference;
- if `Страницы` remains the visual master but target code duplicates the same DOM locally, the patch is not accepted;
- any future shared modal helper must preserve the exact `Страницы` DOM, placement and spacing contract.

## No Silent Fallback Rule

- if a component is marked shared in this inventory, target sections must not render a local lookalike fallback;
- missing shared helper is a failure condition, not permission to rebuild the same component locally;
- allowed recovery is a controlled failure/report path, not a surrogate DOM implementation.

## 1. Page Header

- **Source reference screen:** `Страницы`
- **Source file/helper:** `includes/class-sonyra-site-manager-manager-renderer.php`
- **Required DOM/classes:** wrapper `.sonyra-manager-page-header`; icon tile; eyebrow; title; description; actions; badges/meta row
- **Required CSS selectors:** `.sonyra-manager-page-header`, `.sonyra-manager-page-header-copy`, `.sonyra-manager-page-header-actions`
- **DOM structure:** page header wrapper → left icon zone + content zone + right actions zone → badges/meta row under text block
- **Placement:** icon tile слева, title/subtitle по центру слева-направо, actions справа, meta под description
- **Spacing:** compact shell header spacing from `Страницы`; no second hero
- **Forbidden local variants:** duplicate hero in body, custom header anatomy, custom action placement
- **Current usages:** global manager sections
- **Future usage rule:** every new section reuses the same header shell contract

## 2. Context Card

- **Source reference screen:** `Страницы → Секции`, `Виджеты страницы`, `Поп-апы страницы`
- **Source file/helper:** `assets/js/sonyra-manager.js#createLayerContext`, shared primitive `assets/js/sonyra-manager-ui.js#renderContextCard`
- **Required DOM/classes:** context wrapper with label row, title/subline block, actions group
- **Required CSS selectors:** `.sonyra-pages-workspace-context`, `.sonyra-pages-workspace-context-copy`, `.sonyra-pages-workspace-context-actions`
- **DOM structure:** wrapper → left content column (label row + title/subline) + right action zone
- **Placement:** context label and help icon above title; actions on the right; no duplicate body title
- **Spacing:** standard card padding; gap between content and actions; margin above toolbar/list by Pages standard
- **Forbidden local variants:** custom tool hero, plain text back action, moved action cluster
- **Current usages:** sections/widgets/popups workspace and design tool context
- **Future usage rule:** all workspace modes reuse this anatomy

## 3. Panel Header

- **Source reference screen:** `Секции`, `Блоки секции`
- **Source file/helper:** `assets/js/sonyra-manager.js`
- **Required DOM/classes:** title row with heading, help icon, optional right action
- **Required CSS selectors:** `.sonyra-pages-panel-header`, `.sonyra-pages-panel-title`, `.sonyra-help-tooltip-wrap`
- **DOM structure:** panel header wrapper → left title/help zone + right action zone
- **Placement:** title left, help icon adjacent, action right
- **Forbidden local variants:** detached help icon, second heading row, random wrapper nesting

## 4. Help Icon / Tooltip

- **Source reference screen:** `Секции`, `Блоки секции`
- **Source file/helper:** `assets/js/sonyra-manager.js#createHelpTooltip`, `assets/js/sonyra-manager-ui.js#renderHelpTrigger`, `assets/js/sonyra-manager-ui.js#bindHelpTooltipLayer`, shared help layer in manager renderer
- **Required DOM/classes:** `.sonyra-help-tooltip-wrap` → `.sonyra-help-tooltip-trigger`; popover in shared help layer
- **Required CSS selectors:** `.sonyra-help-tooltip-trigger`, `.sonyra-help-popover`, `[data-manager-help-layer]`
- **DOM structure:** trigger wrapper only in local component; tooltip body rendered into shared layer
- **Placement:** help icon lives in label/title row; tooltip floats above cards/toolbars
- **Forbidden local variants:** custom help button, inline paragraph instead of tooltip, local tooltip layer
- **Acceptance fields:** shared helper/classes are not enough; visual match required; functional match required; valid help key required; event binding required; global help layer required; hover/focus/click test required; tooltip must appear above panels; no visual-only imitation allowed

## 5. Back Button

- **Source reference screen:** `К списку страниц`
- **Source file/helper:** `assets/js/sonyra-manager.js#createButton`, shared primitive `assets/js/sonyra-manager-ui.js#renderButton`
- **Required DOM/classes:** button `.sonyra-manager-pages-secondary` with left arrow icon
- **Required CSS selectors:** `.sonyra-manager-pages-secondary`
- **DOM structure:** one button → icon span left + text span right
- **Placement:** left side of context card actions
- **Forbidden local variants:** text-only back action, moved icon, custom padding

## 6. Primary Button

- **Source reference screen:** `Страницы`, shared modals
- **Source file/helper:** `assets/js/sonyra-manager.js#createButton`, shared primitive `assets/js/sonyra-manager-ui.js#renderButton`
- **Required DOM/classes:** `.sonyra-manager-pages-primary`
- **Required CSS selectors:** `.sonyra-manager-pages-primary`
- **Placement:** right-side primary action or footer action
- **Forbidden local variants:** custom per-section primary button style

## 7. Secondary Button

- **Source reference screen:** `Страницы`, shared modals
- **Source file/helper:** `assets/js/sonyra-manager.js#createButton`, shared primitive `assets/js/sonyra-manager-ui.js#renderButton`
- **Required DOM/classes:** `.sonyra-manager-pages-secondary`
- **Required CSS selectors:** `.sonyra-manager-pages-secondary`
- **Placement:** secondary/back/cancel action before primary/danger action
- **Forbidden local variants:** custom neutral button markup

## 8. Danger Button

- **Source reference screen:** delete modal in `Страницы`
- **Source file/helper:** `window.openManagerDestructiveModal`
- **Required DOM/classes:** `.sonyra-manager-pages-primary.sonyra-manager-pages-primary-danger`
- **Required CSS selectors:** `.sonyra-manager-pages-primary-danger`
- **Placement:** rightmost footer button in destructive modal
- **Forbidden local variants:** local danger button class, local color delete button style, swapped footer order

## 9. Toolbar / Tabs / Filter Chips

- **Source reference screen:** pages toolbar/filter group
- **Source file/helper:** `assets/js/sonyra-manager.js`, shared primitive `assets/js/sonyra-manager-ui.js#renderToolbar`
- **Required DOM/classes:** toolbar panel, search/filter/sort row, chips/tabs row
- **Required CSS selectors:** `.sonyra-pages-toolbar-panel`, `.sonyra-pages-filter-chip`
- **Placement:** toolbar below context card; chips stay inside padded toolbar
- **Forbidden local variants:** loose text counters, detached tabs, custom toolbar shell

## 10. Meta Chips / Counters

- **Source reference screen:** pages header badges, design tool counters
- **Source file/helper:** `assets/js/sonyra-manager.js`, shared primitive `assets/js/sonyra-manager-ui.js#renderMetaChip`
- **Required DOM/classes:** compact chip row using existing badge/chip selectors
- **Placement:** under header copy or inside toolbar meta zone
- **Forbidden local variants:** plain text meta line, fake metrics

## 11. Cards

- **Source reference screen:** section/block/widget/popup cards
- **Source file/helper:** `assets/js/sonyra-manager.js`, `assets/js/sonyra-color-controller.js`
- **Required DOM/classes:** card wrapper; drag/action zone; icon tile; content; meta; actions
- **Required CSS selectors:** `.sonyra-pages-card`, `.sonyra-pages-card-actions`
- **Placement:** icon/content center-left, actions right
- **Forbidden local variants:** floating icon tile, action buttons inside title row if reference card does not do this

## 12. SVG Icon Tile

- **Source reference screen:** accepted icon tiles in `Страницы`, destructive modal danger tile
- **Source file/helper:** `clonePagesIcon()`, `assets/js/sonyra-manager-ui.js#renderIconTile`, shared modal helper
- **Required CSS selectors:** `.sonyra-manager-pages-modal-icon`
- **Placement:** icon tile always in left header zone for destructive modal
- **Spacing/sizes:** 56px destructive tile; 28px icon inside; same border/background/radius everywhere
- **Forbidden local variants:** local SVG tile size, local border radius, swapped placement
- **Icon tile form rule:** icon meaning/key may vary per context, but tile form, sizing, border, radius and alignment may not vary.

## 13. Notice / Status Banner

- **Source reference screen:** pages notices and global notice center
- **Source file/helper:** `assets/js/sonyra-manager.js`
- **Required DOM/classes:** notice center, icon, copy, dismiss
- **Placement:** above content list/workspace
- **Behavior:** success notices auto-dismiss after `5000ms`; close button remains; warning/error do not disappear quickly
- **Forbidden local variants:** decorative badge as notice replacement

## 14. Empty State

- **Source reference screen:** pages empty states
- **Source file/helper:** `assets/js/sonyra-manager.js#createWorkspaceEmptyState`
- **Required DOM/classes:** empty state wrapper + title + description + real action
- **Placement:** inside workspace body
- **Forbidden local variants:** fake CTA, separate pseudo-card component

## 15. Standard Modal

- **Source reference screen:** page settings/add/history modal
- **Source file/helper:** `assets/js/sonyra-manager.js#renderPageModal`
- **Required DOM/classes:** overlay, modal, header, icon tile, copy, close, body, footer
- **Required CSS selectors:** `.sonyra-manager-modal-overlay`, `.sonyra-manager-modal`, `.sonyra-manager-modal__header`, `.sonyra-manager-modal__body`, `.sonyra-manager-modal__footer`
- **Placement:** icon left, copy center-left, close top-right, footer actions right
- **Forbidden local variants:** custom modal shell per section

## 16. Destructive Modal

- **Source reference screen:** delete section/block in `Страницы`
- **Source file/helper:** `assets/js/sonyra-manager.js#openManagerDestructiveModal`
- **Required DOM/classes:** overlay `.sonyra-manager-pages-modal-overlay.sonyra-manager-modal-overlay` → modal `.sonyra-manager-pages-modal.sonyra-manager-modal.sonyra-manager-modal--destructive` → header/body/footer
- **Required CSS selectors:** `.sonyra-manager-pages-modal-icon`, `.sonyra-manager-pages-modal-title`, `.sonyra-manager-pages-modal-danger-text`, `.sonyra-manager-modal__danger-note`, `.sonyra-manager-pages-primary-danger`
- **Required DOM structure:** overlay → modal container → header → icon tile left → title/subtitle column → close button right → body → optional warning slot → footer → cancel button → danger confirm button
- **Placement:** icon tile always left; title/subtitle always middle column; close always top-right; warning slot below body copy; cancel left of danger confirm in footer
- **Spacing/sizes:** header `22px 24px 16px`; body `22px 24px`; footer `16px 24px 22px`; icon-title gap `14px`; footer gap `10px`; icon tile `56px`; destructive icon `28px`
- **Allowed config variables:** `entityType`, `entityName`, `title`, `subtitle`, `bodyText`, `warningText`, `confirmLabel`, `cancelLabel`, `closeLabel`, `loadingLabel`, `onConfirm`
- **Forbidden local variants:** local destructive modal markup, custom danger tile, custom footer order, extra wrappers, moved action zone
- **Current usages:** section delete, block delete, page delete, widget delete, popup delete, color delete, gradient delete, pattern delete
- **Future usage rule:** every delete action must call `openManagerDestructiveModal()` and pass config only
- **Acceptance fields:** shared helper is not enough; typography must match reference; `font-family` token required; title, subtitle, body and buttons must inherit manager font; no browser fallback allowed; behavior must match reference; visual regression check is required after moving modal to a global root

## 17. Footer

- **Source reference screen:** manager shell footer
- **Source file/helper:** manager renderer
- **Required DOM/classes:** footer wrapper + product/version/legal items
- **Placement:** global shell footer below content
- **Forbidden local variants:** local per-section footer spacers or alternate footer blocks

## 18. Visual Swatch Field

- **Source reference screen:** `Дизайн → Цветовая библиотека` release `0.1.81`
- **Source file/helper:** `assets/js/sonyra-color-controller.js` inside shared standard modal shell
- **Required DOM/classes:** `.sonyra-color-controller__swatch-control` → `.sonyra-color-controller__swatch-button` + `.sonyra-color-controller__hex-input` + hidden native color input
- **Required CSS selectors:** `.sonyra-color-controller__swatch-control`, `.sonyra-color-controller__swatch`, `.sonyra-color-controller__native-picker`
- **DOM structure:** field label from shared modal form → swatch button left → HEX input right → native picker hidden but connected
- **Placement:** lives only inside the shared standard modal body
- **Forbidden local variants:** large color banner, separate RGB inputs, visible system-key row

## 19. Pattern Gallery Tile

- **Source reference screen:** `Дизайн → Цветовая библиотека` release `0.1.81`
- **Source file/helper:** `assets/js/sonyra-color-controller.js`
- **Required DOM/classes:** `.sonyra-color-controller__pattern-gallery-grid` → `.sonyra-color-controller__pattern-tile` → preview + copy
- **Required CSS selectors:** `.sonyra-color-controller__pattern-tile`, `.sonyra-color-controller__pattern-preview`, `.sonyra-color-controller__pattern-tile-copy`
- **DOM structure:** grouped gallery section → tile button → preview rectangle → title + description
- **Placement:** only inside the shared standard modal body for pattern create/edit
- **Forbidden local variants:** plain select with raw types, empty preview tile, visually identical tiles

## 20. Color Library Entity Card

- **Source reference screen:** `Дизайн → Цветовая библиотека` release `0.1.82`
- **Source reference component:** `Страницы` card/action visual language
- **Source file/helper:** `assets/js/sonyra-color-controller.js` + shared `.sonyra-pages-card-action`
- **Required DOM/classes:** `.sonyra-color-controller__entity-card` → `.sonyra-color-controller__entity-row` → `.sonyra-color-controller__entity-preview` + `.sonyra-color-controller__entity-content` + `.sonyra-pages-card-actions`
- **Required CSS selectors:** `.sonyra-color-controller__entity-card`, `.sonyra-color-controller__entity-row`, `.sonyra-color-controller__entity-preview`, `.sonyra-color-controller__entity-content`, `.sonyra-color-controller__entity-actions`, `.sonyra-pages-card-actions`
- **DOM structure:** card surface wrapper → horizontal grid row → fixed preview zone → flexible content zone → fixed action zone inside the same card
- **Placement:** color, gradient and pattern lists only
- **Placement details:** preview stays left, content stays center, actions stay top-right inside the card; title/meta/updated live only in content zone
- **Spacing/sizes:** preview width is fixed per entity type; content uses `min-width: 0`; title clamps to two lines with native `title` attribute for full text and does not reserve a visible empty second line; actions never move because of long title or chips; colors desktop grid = 4 columns, gradients/patterns desktop grid = 3 columns, medium grid = `3/2/2` then `2/2/2`, mobile = 1
- **Forbidden local variants:** transparent block without surface, detached actions column, preview without card shell, preview width driven by text, actions outside card

## 20A. Pattern Gallery Accordion

- **Source reference screen:** `Дизайн → Цветовая библиотека` release `0.1.90`
- **Source file/helper:** `assets/js/sonyra-color-controller.js`
- **Status:** superseded by the generator editor flow in release `0.1.91`
- **Required DOM/classes:** `.sonyra-color-controller__pattern-group` → `.sonyra-color-controller__pattern-group-toggle` → `.sonyra-color-controller__pattern-group-panel` → `.sonyra-color-controller__pattern-gallery-grid`
- **Required CSS selectors:** `.sonyra-color-controller__pattern-group-toggle`, `.sonyra-color-controller__pattern-group-panel`, `.sonyra-color-controller__pattern-gallery-grid`
- **DOM structure:** accordion section wrapper → header button → collapsible panel → pattern tiles grid
- **Placement:** only inside the shared standard modal body for pattern create/edit
- **Behavior:** selected group stays open; collapse/expand state survives tile selection and settings rerender; tile selection must not reset modal scroll
- **Forbidden local variants:** one long uncollapsed gallery, modal-over-modal chooser, scroll reset on tile select

## 21. Button Icon Semantics

- **Source reference screen:** manager sections using shared `renderButton`
- **Source file/helper:** `assets/js/sonyra-manager-ui.js#renderButton`
- **Required rule:** `Создать/Добавить` uses `plus`; `Настроить/Редактировать/Параметры` uses `settings`; `Вернуться` uses `arrow-left`; destructive icon actions use `trash`
- **Placement:** applies to landing cards, context cards, toolbars and inline item actions
- **Forbidden local variants:** plus icon for configure/settings action, semantic icon swaps without standard update

## 22. Pattern Editor Type Chooser

- **Source reference screen:** `Дизайн → Цветовая библиотека` release `0.1.91`
- **Source file/helper:** `assets/js/sonyra-color-controller.js` inside shared standard modal shell
- **Required DOM/classes:** `.sonyra-color-controller__pattern-type-chooser` → `.sonyra-color-controller__pattern-type-card`
- **DOM structure:** shared modal body → two large choice cards → title + short description inside each card
- **Placement:** first step only for `Создать паттерн`; modal closes into the selected editor flow, not into a second stacked modal
- **Forbidden local variants:** modal-over-modal chooser, plain radio list, raw technical labels

## 23. Pattern Generator Editor

- **Source reference screen:** `Дизайн → Цветовая библиотека` release `0.1.91`
- **Source file/helper:** `assets/js/sonyra-color-controller.js`
- **Required DOM/classes:** `.sonyra-color-controller__pattern-editor` → preview zone + settings zone; grouped panels `.sonyra-color-controller__pattern-editor-group`
- **DOM structure:** shared modal body → left preview card + right grouped controls → optional back action to type chooser
- **Placement:** desktop uses side-by-side preview/settings; mobile stacks preview above settings
- **Required subcomponents:** generator tile picker, compact sliders with value readout, swatch fields, segmented controls, switches, position grid
- **Forbidden local variants:** one long flat form, paint-like free canvas, checkbox rows, duplicate preset gallery instead of generators

## 24. Pattern Editor Result Panel

- **Source reference screen:** `Дизайн → Цветовая библиотека` release `0.1.92`
- **Source file/helper:** `assets/js/sonyra-color-controller.js`
- **Required DOM/classes:** `.sonyra-color-controller__pattern-editor-preview-zone` → `.sonyra-color-controller__pattern-editor-preview-card` → preview; `.sonyra-color-controller__pattern-editor-result-meta`; `.sonyra-color-controller__pattern-editor-back`
- **DOM structure:** editor shell → result panel column → horizontal preview card → name field/result meta → back action
- **Placement:** desktop left fixed panel; mobile top fixed panel
- **Spacing/sizes:** horizontal preview ratio; compact name/back stack; result panel never becomes a long empty column
- **Forbidden local variants:** vertical giant preview, scrolling-away result panel, name field buried in right controls

## 25. Pattern Editor Control Panel

- **Source reference screen:** `Дизайн → Цветовая библиотека` release `0.1.92`
- **Source file/helper:** `assets/js/sonyra-color-controller.js`
- **Required DOM/classes:** `.sonyra-color-controller__pattern-editor-settings`; `.sonyra-color-controller__pattern-editor-group`; `.sonyra-color-controller__pattern-editor-group-toggle`; `.sonyra-color-controller__pattern-editor-group-body`
- **DOM structure:** scrollable controls column → generator block → collapsible setting groups
- **Placement:** desktop right scroll region; mobile lower scroll region
- **Required behavior:** generator block can collapse; setting groups can collapse; scroll stays in control panel
- **Forbidden local variants:** one long sheet, non-collapsible huge generator wall, page-level scroll inside modal body

## 26. Pattern Editor Live Controls

- **Source reference screen:** `Дизайн → Цветовая библиотека` release `0.1.93`
- **Source file/helper:** `assets/js/sonyra-color-controller.js`
- **Required DOM/classes:** `[data-color-pattern-preview-live]`; `[data-color-pattern-settings-scroll]`; `[data-color-range-value]`; `.sonyra-color-controller__switch-chip`
- **DOM structure:** live preview card → result meta → independently scrollable controls area → collapsible groups → compact slider / switch controls
- **Required behavior:** slider input updates preview and value without scroll jump; switch has no extra on/off text; active group state survives rerender
- **Forbidden local variants:** full modal jump-to-top on input; decorative non-working controls; checkbox fallback

## 27. Pattern Editor Rebuilt Shell

- **Source reference screen:** `Дизайн → Цветовая библиотека` release `0.1.94`
- **Source file/helper:** `assets/js/sonyra-color-controller.js`
- **Required DOM/classes:** `.sonyra-color-controller__pattern-editor` → `.sonyra-color-controller__pattern-result-panel` + `.sonyra-color-controller__pattern-controls-panel`
- **DOM structure:** shared modal body → editor root grid → left result panel wrapper → finished result card → right controls wrapper → scrollable controls column
- **Placement:** result panel слева на desktop и сверху на mobile; controls panel справа на desktop и снизу на mobile
- **Forbidden local variants:** technical split-pane with border-right preview look, random extra shell wrappers, page-level modal-body scroll instead of control-panel scroll

## 28. Pattern Editor Result Card

- **Source reference screen:** `Дизайн → Цветовая библиотека` release `0.1.94`
- **Source file/helper:** `assets/js/sonyra-color-controller.js`
- **Required DOM/classes:** `.sonyra-color-controller__pattern-result-card`; `.sonyra-color-controller__pattern-result-meta`; `.sonyra-color-controller__pattern-result-top`; `.sonyra-color-controller__pattern-result-copy`; `.sonyra-color-controller__pattern-result-actions`; `.sonyra-color-controller__pattern-result-preview`
- **DOM structure:** result panel → result card → top row → left copy zone (type chip + name field) + optional right action zone → preview wrapper → helper copy
- **Placement:** type/name belong to the same card surface as preview; back action sits in the same top zone and never drops into the controls column
- **Spacing/sizes:** one finished card surface, compact gap stack, horizontal preview ratio, no empty technical side column
- **Forbidden local variants:** separate preview box plus detached name field, detached back action, plain text type row, giant vertical preview

## 29. Pattern Editor Accordion Group

- **Source reference screen:** `Дизайн → Цветовая библиотека` release `0.1.94`
- **Source file/helper:** `assets/js/sonyra-color-controller.js#buildPatternAccordionGroup`
- **Required DOM/classes:** `.sonyra-color-controller__pattern-accordion`; `.sonyra-color-controller__pattern-accordion-toggle`; `.sonyra-color-controller__pattern-accordion-head`; `.sonyra-color-controller__pattern-accordion-title`; `.sonyra-color-controller__pattern-accordion-summary`; `.sonyra-color-controller__pattern-accordion-chevron`; `.sonyra-color-controller__pattern-accordion-body`
- **DOM structure:** group section → header button → title/summary stack + chevron → controlled body with `aria-controls`
- **Placement:** same accordion primitive is used for generator and all settings groups
- **Required behavior:** keyboard button semantics, `aria-expanded`, stable open state, no decorative fake accordion
- **Forbidden local variants:** separate generator toggler implementation, text-only collapse rows, non-semantic clickable divs

## 30. Pattern Editor Control Primitives

- **Source reference screen:** `Дизайн → Цветовая библиотека` release `0.1.94`
- **Source file/helper:** `assets/js/sonyra-color-controller.js#buildPatternRangeControl`, `assets/js/sonyra-color-controller.js#buildPatternSwitchRow`
- **Required DOM/classes:** `.sonyra-color-controller__pattern-range`; `.sonyra-color-controller__pattern-range-row`; `.sonyra-color-controller__pattern-range-value`; `.sonyra-color-controller__pattern-switch-row`; `.sonyra-color-controller__pattern-switch-copy`; `.sonyra-color-controller__pattern-switch-label`; `.sonyra-color-controller__pattern-switch`
- **DOM structure:** field wrapper → one control primitive instance → value/helper nodes inside the same control contract
- **Placement:** range value sits on the same row as the slider; switch label sits to the left, switch control to the right
- **Required behavior:** ordinary inputs update live preview without full modal rebuild; switch uses `role=\"switch\"` and `aria-checked`
- **Forbidden local variants:** duplicate slider helper, duplicate switch helper, visible standalone `Включено/Выключено`, full modal rerender on ordinary slider/switch input

## Required Markup Proof

For every UI patch Codex must provide:

`Component | Reference DOM from “Страницы” | Shared helper/selector | Target DOM | Placement match | Spacing match | Deviations`

For destructive modal the proof is mandatory for:

- section delete
- block delete
- color delete
- gradient delete
- pattern delete
- page delete
- widget delete
- popup delete

If placement or markup differs from the reference component, the patch fails and archive must not be built.

## Raw I18N Key Stop Rule

- no visible UI may render a raw translation key such as `manager.pages.modal.delete_line_one`;
- JS fallback may return a safe empty string or approved fallback copy, but never the source key;
- if a shared component needs a string, the payload contract must include that key explicitly;
- any screenshot, DOM audit or browser check that shows a raw key is a release stop.
