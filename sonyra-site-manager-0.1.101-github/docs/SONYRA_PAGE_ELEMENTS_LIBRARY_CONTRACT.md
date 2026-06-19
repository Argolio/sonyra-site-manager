# SONYRA Page Elements Library Contract

## 1. Core concept

Page Elements Library объединяет:

- sections;
- blocks;
- widgets;
- popups.

## 0.1.75

- `Sonyra_Site_Manager_Page_Elements_Library` now exposes popup registry methods:
  - `get_core_popup_definitions()`
  - `get_popup_definitions()`
  - `get_popup_definition( $type )`
  - `normalize_popup_definition( array $definition )`
- modules/extensions can register popups through `sonyra_site_manager_popup_definitions`.
- popup definitions follow the same `label_key` / `description_key` contract as blocks and widgets.

Stage 1 реализует sections registry.
Stage 2 реализует Base Blocks Library внутри выбранных sections.

Следующие этапы:

- Stage 2 — blocks;
- Stage 3 — widgets;
- Stage 4 — popups.

## 2. Section definition shape

Section definition использует поля:

- `type`;
- `label`;
- `description`;
- `label_key`;
- `description_key`;
- `icon_key`;
- `category`;
- `provider`;
- `module_key`;
- `status`;
- `default_section`;
- `default_blocks`;
- `fields`.

Правила:

- `type` обязателен и хранится как stable key;
- `type` не использует русские слова;
- `label` обязателен и идёт только через i18n;
- `description` обязателен и идёт только через i18n;
- `label_key` и `description_key` должны быть i18n-compatible technical keys;
- `icon_key` обязателен и берётся только из SONYRA Icon Library;
- `category` обязателен;
- `provider` по умолчанию `core`;
- `status` по умолчанию `implemented`;
- `default_section` обязателен;
- `default_blocks` опционален;
- `fields` опционален.

## 3. Filter connector

Базовый connector для секций:

- `sonyra_site_manager_section_definitions`

Базовый connector для блоков:

- `sonyra_site_manager_block_definitions`

Core передаёт в filter свои base definitions.

Внешний модуль может добавить свои definitions через этот filter.

После filter definitions должны пройти нормализацию и валидацию.

## 4. External module behavior

Пока модуль включён:

- его definitions появляются в library modal;
- editor может создавать новые instances этого module type.

Если модуль выключен:

- definitions исчезают из modal добавления;
- уже созданные instances не удаляются;
- editor показывает instance как unavailable;
- public renderer не падает;
- save не удаляет instance молча.

Внешние modules должны:

- использовать `label_key` и `description_key`;
- хранить `type` как technical stable key;
- не использовать русские type keys;
- не встраивать raw visible Russian/English прямо в core UI.
- future visual bindings from sections, blocks, widgets and popups to shared design values should resolve through Color Controller tokens instead of raw ad-hoc color fields.

## 5. Block definition shape

Block definition использует поля:

- `type`;
- `label`;
- `description`;
- `label_key`;
- `description_key`;
- `icon`;
- `category`;
- `source`;
- `module_key`;
- `enabled`;
- `fields`;
- `defaults`.

Для blocks:

- `label_key` и `description_key` обязательны;
- `type` остаётся stable technical key;
- disabled definitions не показываются в add modal;
- existing unknown/external blocks не удаляются при save.

## 6. Naming standard

Для visible labels используются только универсальные названия.

Запрещены:

- Первый экран;
- Главный блок;
- Основной блок;
- Ведущий блок.

Разрешённый pattern:

- Акцентная секция;
- Текстовая секция;
- Преимущества;
- Контакты;
- Акцент.

Stored backward-compatible keys вроде `hero` сохраняются и не переименовываются.

## 7. Cross-section integration

- `Страницы` владеют page body sections;
- `Сайт` может использовать menu/page data, но не владеет body sections;
- `Дизайн` позже даёт style defaults;
- `Модули` контролируют подключённые external modules;
- `Безопасность` принимает события и согласия модулей при необходимости;
- `Настройки` владеют только system-level settings;
- `Главное` получает summary, а не editor internals.

## 8. Add section modal contract

- modal `Добавить секцию` работает только как single-select flow;
- выбор section создаёт ровно одну новую section instance;
- после успешного add/save modal закрывается сразу;
- multi-select режим для sections в текущем продукте запрещён;
- повторный click во время add/save блокируется;
- fake success до ответа backend запрещён.

## 9. Add block modal contract

- modal `Добавить блок` работает только как single-select flow;
- один click по block card создаёт ровно один новый block;
- повторный click во время add/save блокируется;
- после успешного add modal закрывается;
- новый block становится active;
- unavailable module blocks в modal не показываются.

## 10. Section card actions contract

- settings action section card обязан открывать modal `Параметры секции`;
- save в modal секции меняет только section name;
- delete action section card обязан открывать destructive confirmation modal;
- delete section без confirmation modal запрещён.

## 11. Section library icon contract

- `Акцентная секция` для type `hero` использует icon `sparkles`;
- `app-window` не используется для `Акцентная секция`, потому что конфликтует с site/page meaning;
- icon change не меняет type key, label, storage или default blocks.

## 8. Widget definition shape

Widget definition использует поля:

- `type`;
- `label`;
- `description`;
- `label_key`;
- `description_key`;
- `icon`;
- `category`;
- `source`;
- `module_key`;
- `integration_mode`;
- `enabled`;
- `fields`;
- `defaults`.

Для widgets:

- используется filter `sonyra_site_manager_widget_definitions`;
- `label_key` и `description_key` обязательны;
- `integration_mode` допускает только `core`, `external_embed`, `module_native`;
- disabled definitions не показываются в add modal;
- existing unknown/external widgets не удаляются при save.
