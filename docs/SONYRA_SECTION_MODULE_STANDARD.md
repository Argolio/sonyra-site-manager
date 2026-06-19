# SONYRA Section Module Standard

## 1. Core rule

Каждый раздел ПУЛЬТА САЙТА — внутренний `section-module` внутри одного плагина `sonyra-site-manager`, а не отдельный WordPress-плагин.

## 2. Shell responsibility

Manager shell отвечает только за:

- layout;
- sidebar;
- page header;
- Notice Center;
- dispatch sections;
- shared assets;
- shared UI contracts;
- icon renderer;
- auth/access context.

## 3. Section responsibility

Section-module отвечает только за:

- собственную бизнес-логику;
- собственный view/render body;
- store/settings layer;
- REST layer;
- public route, если он нужен;
- JS scope;
- CSS scope;
- service methods;
- безопасную summary-интеграцию с другими разделами.

## 4. Allowed section files

Допустимый pattern:

- `includes/class-sonyra-site-manager-{section}-section.php`
- `includes/class-sonyra-site-manager-{section}-store.php`
- `includes/class-sonyra-site-manager-rest-{section}-controller.php`
- `includes/class-sonyra-site-manager-{section}-public-route.php`, если нужен public route

## 5. JS boundary

Каждый section должен иметь namespaced JS scope.

Общий manager JS не должен превращаться в хаотичный набор section-specific функций без boundary.

## 6. CSS boundary

Каждый section должен иметь root CSS scope.

Section-specific CSS не должен использовать глобальные selectors и не должен протекать в shell или соседние разделы.

## 7. Section service methods

Разделы взаимодействуют через service methods/contracts, а не через доступ к чужому UI.

Для `Pages` service layer:

- `get_pages()`
- `get_public_pages()`
- `get_menu_pages()`
- `get_home_page()`
- `get_summary()`

## 8. Dashboard summary rule

Будущий раздел `Главное` собирает summary из section modules и не лезет во внутренний UI section-модулей.

## 9. Cross-section integration rule

Раздел `Сайт` должен получать меню через Pages service methods, а не дублировать Pages logic.

## 10. Help entry integration

Будущие section/modules должны регистрировать help entries для крупных UI-блоков через общий Help Library contract, а не хранить help text локально в JS/PHP/CSS.

## 11. Forbidden

Запрещено:

- новый WordPress plugin per section;
- новый plugin root per section;
- cross-section direct UI access;
- global CSS leaks;
- unscoped JS leaks;
- переносить auth/sidebar/page header в section;
- менять shared shell ради section-specific нужд.

## 12. Pages sections workspace boundary

Для section-module `Pages` режим `Секции` остаётся частью того же module boundary и не создаёт отдельный shell.

Правила:

- workspace секций не дублирует page header;
- workspace секций не повторяет page settings form;
- left panel отвечает только за sections list и использует desktop-width около `40%`;
- right panel отвечает только за blocks list текущей section и использует desktop-width около `60%`;
- page settings остаются в page settings modal из таблицы страниц;
- modal standard для section/block reused, не reinvented;
- section card показывает title, muted type label и actions;
- settings/delete actions секции обязаны быть рабочими;
- delete section всегда идёт через destructive modal;
- reorder делается через local drag/drop implementation без внешних библиотек.

## 13. Page Elements Library Contract

Pages section-module использует отдельный `Page Elements Library Contract`.

Текущий Stage 1 scope:

- sections registry;
- add-section modal;
- connector filter для external definitions;
- safe unavailable behavior для instances отключённого модуля.

Stage 2 расширяет тот же contract для blocks без изменения shell boundary.

Дополнение для blocks:

- sections могут содержать `section.blocks`;
- right column `Блоки секции` остаётся частью `Pages` section-module;
- future modules могут регистрировать block definitions через Page Elements Library;
- shell boundary, routes и auth layer при этом не меняются.
