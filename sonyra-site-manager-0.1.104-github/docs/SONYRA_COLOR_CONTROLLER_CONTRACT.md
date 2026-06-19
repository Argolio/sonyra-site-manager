# SONYRA Color Controller Contract

## 1. Purpose

Color Controller is the system design layer of SONYRA Site Manager. It stores colors, gradients, patterns and the reusable site library that will later connect to sections, blocks, widgets, popups, header, footer and global text colors.

Visible UX for this system must follow `docs/SONYRA_MANAGER_SECTION_UX_STANDARD.md`.

## 2. Store

- option name: `sonyra_site_manager_color_controller`
- autoload: `false`
- current store version: `0.1.0`
- DB migration is not used in releases `0.1.76` and `0.1.77`

Root shape:

- `version`
- `colors[]`
- `gradients[]`
- `patterns[]`
- `presets[]`
- `library_meta`

## 3. Colors

- each color stores `value_hex` and `value_rgb`
- HEX is normalized to uppercase `#RRGGBB`
- RGB is stored as a numeric array `[r, g, b]`
- roles use technical keys such as `brand.primary` and `text.secondary`
- invalid colors are rejected and not saved

## 4. Gradients

- only `linear` gradients are allowed in `0.1.77`
- each gradient stores `angle` and `stops[]`
- manager CRUD in `0.1.77` saves a safe two-stop gradient form and persists normalized stops
- each stop stores valid `color_hex`
- each stop position is limited to `0–100`
- raw CSS input is not accepted

## 5. Patterns

- patterns use an allowlist of `pattern_type`
- arbitrary CSS patterns are not accepted
- pattern colors are stored as normalized HEX values
- settings are limited to safe scalar values

Allowed pattern types:

- `dots`
- `grid`
- `diagonal_lines`
- `soft_glow`
- `soft_blobs`
- `diagonal_wave`
- `accent_orbit`
- `deep_background`
- `color_splash`
- `subtle_grid`
- `radial_aura`

## 6. Library

- the library stores saved colors, gradients and patterns
- the manager UI shows the default core library immediately after first load
- future releases may add user-created presets and richer reuse workflows

## 7. Tokens

- future integrations should reference design tokens instead of raw display values when possible
- token references should still keep safe fallback values for stable rendering

## 8. Integration Roadmap

Future connection points:

- sections
- blocks
- widgets
- popups
- header
- footer
- global text colors
- public CSS variables

## 9. Security

- no raw CSS injection
- no arbitrary CSS persistence
- no `url()`, `var()`, `expression()` or `javascript:` user input
- no public style application in release `0.1.77`
- validators normalize or reject unsafe input before saving

## 10. Release Scope

Release `0.1.76` includes:

- Color Controller architecture
- dedicated store and option
- `Дизайн` landing section with a real tool card for `Цветовая библиотека`
- dedicated internal tool screen in `Дизайн`
- default core library
- validation methods for colors, gradients and patterns

Release `0.1.76` does not include:

- full color CRUD
- gradient builder UI
- pattern builder UI
- picker integration into sections, blocks, widgets or popups
- public style application

Release `0.1.77` adds:

- working CRUD for colors in `Дизайн → Цветовая библиотека`
- working CRUD for gradients in `Дизайн → Цветовая библиотека`
- working CRUD for patterns in `Дизайн → Цветовая библиотека`
- REST create/update/delete endpoints for library entities
- standard destructive modal for delete actions
- toolbar create actions tied to the active tab
- landing card copy clarified as library-only scope
- landing counters rendered as meta chips aligned to the tool card content
- all delete actions use the global shared destructive modal helper

Release `0.1.77` still does not include:

- applying library items to sections, blocks, widgets or popups
- public CSS variables
- header/footer integration
- global text color integration
- presets CRUD
- advanced color picker workflows

Release `0.1.81` adds:

- compact swatch-based color editor;
- visual gradient editor with live preview and direction chips;
- grouped pattern registry with visual gallery tiles;
- image pattern storage through WordPress attachment IDs;
- media upload and media-library selection endpoints for image patterns;
- hidden auto-generated system keys for colors, gradients and patterns.

Release `0.1.82` hotfix adds:

- visible card surface for color, gradient and pattern items;
- action zone fixed inside each library card;
- REST response contract returns both `data` and `library` plus `updated_at`;
- frontend `applyResponse` cannot wipe collections when response shape is incomplete.

Release `0.1.83` hotfix adds:

- bootstrap collection normalization accepts safe object-like collections and restores item visibility;
- landing card counters align with the content column instead of the card edge;
- landing action `Настроить` uses `settings` icon semantics.

Release `0.1.84` hotfix adds:

- stored option recovery restores default colors, gradients and patterns only for empty or missing core collections;
- recovery preserves existing non-empty collections and does not overwrite user-created items in other collections;
- summary is derived from the same normalized collections that are returned to REST and frontend state;
- successful save and reload cannot keep stale `6/1/1` counters against empty visible lists.

Release `0.1.85` hotfix adds:

- the `Дизайн` body mount no longer depends on shared icon templates living only inside the hidden `Страницы` subtree;
- shared icon templates required by Color Library render are available at shell level before the first landing render;
- Color Library landing render no longer fails blank when the shared `settings` action icon cannot be resolved from the `pages` workspace DOM.

Release `0.1.86` hotfix adds:

- internal Color Library render normalizes browser locale tags before formatting `updated_at` values;
- payload locale values such as `ru_RU` no longer throw inside `Date.toLocaleString()` during internal tab/card render;
- internal `Цвета / Градиенты / Паттерны` screen no longer fails blank on the first item-card render because of invalid locale formatting.

Release `0.1.87` polish adds:

- color, gradient and pattern items now share one horizontal card anatomy with fixed preview zone, flexible content zone and fixed actions zone;
- gradient and pattern previews use compact fixed rectangles and no longer stretch with long titles or meta chips;
- Color Library item titles clamp to two lines and remain inside the content column without pushing actions out of the card;
- actions remain attached to the same card surface for all three entity types.

Release `0.1.88` polish adds:

- item titles now clamp to exactly two lines with ellipsis-safe geometry and full title access through the native `title` attribute;
- desktop card grids render three items per row for colors, gradients and patterns when the standard desktop width is available;
- tablet card grids render two items per row and mobile grids render one item per row;
- pattern meta chips stack vertically and truncate safely without changing card width or action placement.

Release `0.1.89` polish adds:

- colors render four cards per row on wide desktop while gradients and patterns stay at three cards per row;
- medium layouts step down explicitly to `3/2/2` and then `2/2/2` before the mobile single-column fallback;
- item titles no longer reserve a visible empty second line and only expand to a second line when content requires it;
- pattern meta chips keep a vertical stack, prefer full readable text, and only clamp with ellipsis when two lines are genuinely insufficient.

Release `0.1.90` hotfix adds:

- unique name validation inside each Color Library collection on both frontend and backend;
- pattern gallery groups now render as accordion sections inside the pattern modal;
- pattern selection preserves modal scroll position and accordion state during rerender;
- Color Library success notices now auto-dismiss after `5000ms` while error notices remain persistent.

Release `0.1.91` rebuild adds:

- schema-driven generator architecture for patterns;
- dedicated type chooser before pattern creation;
- separate premium editors for graphic generators and image-based patterns;
- consolidated generator list without duplicate preset categories;
- richer image pattern editor with placement, color-light, texture and effects controls.

## 11. UX Positioning Standard

- `Дизайн` should evolve as a section landing, not as a permanent full-screen Color Controller surface;
- the internal tool screen should use the tool context card pattern from `SONYRA_MANAGER_SECTION_UX_STANDARD.md`;
- the visible product name of the tool should be `Цветовая библиотека`;
- the technical name `Color Controller` may remain in architecture, option keys, REST layer and internal code;
- the internal screen keeps only `Цвета`, `Градиенты` and `Паттерны` tabs;
- the internal screen does not render a duplicate hero/header inside the body;
- counters belong to the tool card and the compact tabs/meta panel, not to the big `Дизайн` page header;
- future Color Library UI work must align with the `Страницы`-derived section UX standard after CRUD as well;
- the Color Library tool context card must reuse the `Страницы` context/panel standard one-to-one;
- Color Library shared UI anatomy must consume `assets/js/sonyra-manager-ui.js` helpers instead of local lookalike helpers;
- the visible label `ЦВЕТОВАЯ БИБЛИОТЕКА` remains a muted service label, not a violet accent;
- the help icon must reuse the shared help trigger component and Help Library behavior;
- the back action `Вернуться в раздел Дизайн` must use the shared arrow-back button pattern;
- the tabs/meta row must use the same padded toolbar logic as `Страницы`.
- the Color Library toolbar counters must use meta-chip style, not dot-separated loose text;
- the back action must remain vertically centered inside the context card;
- footer spacing is a global manager shell responsibility, not a Color Library-only hack;
- Color Library delete actions must use the global shared destructive modal;
- Color Library cannot define local destructive modal markup;
- color, gradient and pattern delete actions only pass config to the shared helper;
- modal visuals for color, gradient and pattern deletion must be identical to section/block deletion modal;
- future delete actions inside Color Library also use the same helper;
- help icon рядом с `ЦВЕТОВАЯ БИБЛИОТЕКА` must reuse the same shared help trigger pattern as `Страницы → Секции / Блоки секции`;
- help tooltip for Color Library must open by hover, focus and click through the global help layer;
- Color Library must never expose raw translation keys in modal copy, counters, badges or help UI;
- future product decision may still split card layouts: colors may become a compact grid later, while gradients and patterns may keep large preview cards;
- release `0.1.76` hotfix does not change card sizes and does not introduce CRUD.

## 12. Color Library Item Card Anatomy

- every Color Library item card uses one horizontal anatomy: `preview zone` → `content zone` → `actions zone`;
- preview zone has fixed dimensions and never changes width or height because of title length, chips or updated date;
- content zone uses `min-width: 0` and holds title, meta chips and updated date;
- title clamps to a maximum of two lines and wraps only inside the content zone;
- actions zone stays fixed on the right and always lives inside the same visible card surface;
- color preview uses a compact square swatch;
- gradient and pattern previews use compact fixed rectangles, not full-width banners;
- cards keep the manager card surface with visible background, border, radius and shadow;
- detached actions, transparent cards and preview growth caused by text are regressions.

## 13. Color Library Card Stability Standard

- title uses a maximum of two visible lines;
- overflow after the second line is truncated with ellipsis-safe clamp behavior;
- title does not reserve a visible empty second line when one line is enough;
- full title remains available through the native hover `title` attribute;
- colors desktop grid uses `repeat(4, minmax(0, 1fr))`;
- gradients and patterns desktop grid use `repeat(3, minmax(0, 1fr))`;
- medium grid steps down to `3/2/2` and then `2/2/2` depending on available width;
- mobile grid uses `1fr`;
- preview zone remains fixed for every entity type;
- actions remain fixed inside the card at the top-right edge;
- pattern meta chips stack vertically;
- pattern meta chips prefer readable full text and truncate with ellipsis only when two lines are still insufficient;
- full pattern meta text remains available through the native hover `title` attribute.

## 14. Unique Name Per Scope Standard

- duplicate names are forbidden inside one local collection scope;
- `colors`, `gradients` and `patterns` each have their own independent scope;
- same visible name across different collections is allowed;
- duplicate validation is mandatory on both frontend and backend;
- duplicate error copy uses i18n field error plus human helper hint.

## 15. Pattern Gallery Accordion Standard

- pattern groups render as collapsible accordion sections inside the shared modal body;
- selected pattern group stays open by default for create and edit flows;
- collapse/expand state survives tile selection and preview/settings rerender inside the same modal session;
- pattern selection must not jump the modal body back to the top;
- no modal-over-modal chooser is allowed for pattern picking.

Release `0.1.91` supersedes this flow for new pattern creation and editing: the primary architecture is now `type chooser → generator editor` instead of the accordion preset gallery.

## 16. Success Notice Auto-dismiss Standard

- Color Library success notices auto-dismiss after `5000ms`;
- close button remains available for manual dismiss;
- error notices do not auto-dismiss quickly;
- new notices replace the previous Color Library notice state instead of stacking хаотично.

## 17. Pattern Generator Architecture `0.1.91`

- patterns are generators, not a catalog of near-duplicate presets;
- one generator must cover its whole mechanic through settings instead of splitting into `микроточки`, `мягкая сетка`, `диагональные линии` and similar clones;
- pattern create flow is two-step: `Создать паттерн` → editor kind choice → dedicated editor;
- modal-over-modal is forbidden;
- graphic generators in `0.1.91`: `Точки`, `Сетка`, `Линии`, `Круги и орбиты`, `Геометрическая мозаика`, `Мягкие пятна`, `Световой луч`, `Волны`, `Мазки краски`, `Кляксы`, `Мрамор`, `Шум`, `Декоративные элементы`;
- category `SVG-символы` is forbidden; decorative SVG forms live inside `Декоративные элементы`;
- labels `Мрамор / прожилки` and `Шум / зерно` are forbidden; visible names stay `Мрамор` and `Шум`.

## 18. Image Pattern Editor Contract

- `Изображение как паттерн` is a dedicated editor, not a branch inside the graphic generator picker;
- persistence stores only WordPress attachment metadata, never external URL or base64 payload in the option;
- image editor must expose placement, scale, rotation, brightness, contrast, saturation, monochrome transition, color channels, blur, noise and finish effects;
- upload/library replacement stays inside the same modal shell and must not open a nested manager modal;
- save/reload contract remains the same as other Color Library entities.

## 19. Compact Controls Rule

- pattern editor controls are grouped into readable clusters instead of one long technical form;
- related numeric controls may sit in compact pairs;
- sliders always show current value in `%`, `px`, `°` or discrete scale;
- checkboxes are forbidden; switches/toggles are required;
- visual generator selection must use cards/tiles with preview, label and short description.

## 20. No Paint UI Rule

- the Color Library pattern editor is a curated generator console, not a freeform paint surface;
- no raw CSS textarea, no arbitrary SVG code field, no giant unstructured control sheet;
- first-level UI must stay product-oriented and understandable for a non-designer user.

## 21. Pattern Editor Layout Polish `0.1.92`

- release `0.1.92` keeps the `0.1.91` generator architecture and only polishes titles, layout and controls;
- desktop editor uses a fixed left result panel and independently scrollable right controls;
- mobile editor uses a fixed top result panel and independently scrollable lower controls;
- preview stays horizontal for both graphic and image-based pattern editors;
- pattern name lives in the result panel under preview;
- `Назад к выбору типа` lives under preview and remains available only in create flow;
- generator picker becomes a compact collapsible block and no longer pushes settings into a long sheet;
- generator mini-previews must keep one size, one radius and centered content;
- slider fill must match the thumb position on initial render and during input;
- binary settings use switch controls with active/inactive state, not text chips and not checkboxes.

## 22. Create/Edit Title Standard

- modal and screen titles use noun forms: `Создание …`, `Редактирование …`;
- action buttons use verbs: `Создать`, `Сохранить`, `Отменить`;
- Color Library in `0.1.92` must show `Создание цвета`, `Редактирование цвета`, `Создание градиента`, `Редактирование градиента`;
- pattern flow titles are mode-specific: `Создание паттерна`, `Создание графического паттерна`, `Редактирование графического паттерна`, `Создание паттерна из изображения`, `Редактирование паттерна из изображения`.

## 23. Pattern Editor Functional UX Repair `0.1.93`

- release `0.1.93` repairs pattern-editor regressions without changing Color Library CRUD architecture;
- modal header must always show title and subtitle through shared i18n strings;
- result panel must behave like a premium card and stay visible while controls scroll;
- collapsible groups must preserve `aria-expanded`, open state and scroll position during control changes;
- visible controls are allowed only when they affect preview and save payload;
- binary controls use switch only and must not render standalone `Включено` / `Выключено`;
- dot-generator controls must affect preview for density, size, spacing, layer count, layer offset, random offset, opacity and edge style.

## 24. Pattern Editor Full Rebuild `0.1.94`

- release `0.1.94` replaces the patchwork Pattern Editor grouping with one schema-normalized UI contract;
- visible UI groups are built through `getPatternEditorSchema(patternType, definition)`;
- raw PHP `control.group` is not the final visible grouping contract in `0.1.94`;
- patchwork remaps like `getPatternGroupKey()` and blind group-order tables are forbidden as the main grouping mechanism;
- PHP registry remains the storage/default/sanitization contract;
- JS schema normalizer is the visible source of truth for Pattern Editor group layout;
- every visible control must satisfy one chain: control → draft state → preview → payload → saved reload;
- controls that do not update preview must not stay visible in the editor;
- dots editor uses only `Точки`, `Слои и цвета`, `Эффекты`;
- dots `density` is removed from visible UI and no longer acts as a visible product control;
- result panel uses one finished result card instead of a technical preview column;
- result card contains pattern type, name field, preview and back action in one shared surface;
- controls panel uses one accordion primitive for generator and all groups;
- range controls use one helper and one CSS fill token `--sonyra-range-fill`;
- switch rows use one helper with semantic switch attributes;
- ordinary control changes must not rebuild the full modal shell;
- generator change may rebuild only the controls panel while result card and header stay stable;
- image pattern editor stays inside the same shell contract even when its control set is smaller than the graphic editor.
