# SONYRA Help Library Standard

## 1. Назначение

- Help Library / База знаний ПУЛЬТА САЙТА хранит help entries для question/help icons;
- все question/help icons должны ссылаться на help entries;
- help entries централизуются в registry и не размазываются по JS/PHP/CSS.

## 2. Entry shape

- `key`
- `title`
- `body`
- `category`
- `section`
- `source`
- `module_key`
- `editable`
- `i18n_title_key`
- `i18n_body_key`

## 3. Tooltip/popover behavior

- hover, focus и click/tap открывают help popover;
- `Escape` закрывает popover;
- click outside закрывает popover;
- auto-placement обязателен;
- popover должен clamp-иться внутри viewport;
- mobile behavior остаётся viewport-safe и не уходит за экран.
- shared help trigger считается принятым только если совпадают DOM, CSS, typography, SVG/icon sizing, spacing и behavior с эталоном `Страницы`;
- visual match без working hover/focus/click behavior не принимается;
- valid `data-sonyra-help-key` и valid event binding обязательны;
- tooltip обязан открываться через global help layer, а не через локальную визуальную имитацию.

Question/help icon visual standard follows `docs/SONYRA_MANAGER_SECTION_UX_STANDARD.md`: a small help trigger sits рядом с section headers, context labels и panel titles, а не заменяет action button.

## 4. I18N

- видимый русский текст идёт только через `includes/i18n/ru.php`;
- hardcoded help text в JS/PHP/CSS запрещён;
- каждый help entry использует `i18n_title_key` и `i18n_body_key`;
- future language packs смогут переводить help entries без переписывания JS;
- missing translation должна fallback-нуться к `ru_RU`.
- help entry `design.colors.controller` закреплён за экраном Цветового контроллера в разделе `Дизайн`.
- тексты Color Controller help entry идут только через `includes/i18n/ru.php`.
- help triggers in manager screens must be rendered through `assets/js/sonyra-manager-ui.js#renderHelpTrigger(config)`;
- hover/focus close/open binding for manager help triggers must use `assets/js/sonyra-manager-ui.js#bindHelpTooltipLayer(root, config)` unless the existing global section code already owns the exact same pattern.
- silent per-screen tooltip trigger fallback is forbidden once a screen adopts the shared help trigger;
- tooltip title/body inside the global help layer must explicitly use manager typography selectors, not accidental browser inheritance.

## 5. Future editor

- `База знаний ПУЛЬТА САЙТА` показывается внутри `Настройки`;
- редактирование будет доступно только owner/admin;
- используется текущий manager auth/capability layer;
- отдельный login/password не используется;
- редактор не реализуется в `0.1.72`.

## 6. Module integration

- будущие modules могут регистрировать help entries через filter;
- если module отключён, его entries исчезают из registry;
- уже существующий UI должен безопасно fallback-нуться, если entry недоступен.

## 6.1 Manager section header usage

- help icons в section headers, context cards и panel titles должны оставаться маленькими и вторичными;
- help icon не должен дублировать длинный paragraph, если explanation уже есть в tooltip;
- section/workspace help triggers должны использовать общий help button pattern и Help Library key.

## 7. Base Blocks Library readiness

- existing question icons рядом с `Секции` и `Блоки секции` должны сохраняться;
- block UI не обязан получать массовые новые question icons в `0.1.73`;
- future complex block/settings screens должны ссылаться на Help Library entries, а не хранить help text локально.

## 8. Widgets panel help entry

Для слоя виджетов используется отдельная help entry:

- `pages.widgets.panel`;
- `i18n_title_key = manager.help.pages.widgets.panel.title`;
- `i18n_body_key = manager.help.pages.widgets.panel.body`.

Существующие question icons рядом с `Секции` и `Блоки секции` сохраняются.
## 0.1.75

- added core help entry `pages.popups.panel`.
- page-level help icons now cover both `pages.widgets.panel` and `pages.popups.panel`.
