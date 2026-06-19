# SONYRA Base Widgets Library

## 1. Purpose

Widgets — это отдельный page-level слой:

- `page -> sections -> blocks`;
- `page -> widgets`;
- future `page -> popups`.

## 0.1.75 follow-up

- widgets remain a separate page-level layer.
- page-level workspace header for widgets now uses the page-layer pattern:
  - uppercase context label in the context card
  - page title on the second line
  - actions in the same context card
  - no duplicated workspace title/button below

Виджеты не являются секциями и не являются блоками.

## 2. Integration model

Поддерживаются три сценария:

- `core` — встроенные безопасные виджеты ПУЛЬТА САЙТА;
- `external_embed` — safe storage/fallback для внешнего embed-кода без raw execution;
- `module_native` — будущие виджеты от локальных WordPress-модулей.

Серьёзные интеграции вроде booking, Renovatio, CRM и payments должны приходить как native modules, а не как случайный embed в core.

## 3. Widget definition shape

- `type`
- `label`
- `description`
- `label_key`
- `description_key`
- `icon`
- `category`
- `source`
- `module_key`
- `integration_mode`
- `enabled`
- `fields`
- `defaults`

## 4. Core widget types

- `call`
- `email`
- `message`
- `messenger`
- `back_to_top`
- `social_button`
- `quick_request`
- `route_map`
- `callback`
- `floating_button`
- `external_widget`

## 5. Storage model

- widgets хранятся в `page.widgets`;
- порядок определяется порядком массива;
- unknown/external widgets сохраняются и не удаляются молча;
- existing disabled module widgets сохраняются в данных страницы.

## 6. Add Widget modal

- modal `Добавить виджет` работает в single-select режиме;
- pending guard блокирует double-click duplicates;
- после успешного add modal закрывается;
- новый widget становится active.

## 7. Widget settings modal

- modal `Параметры виджета` редактирует `name`, `enabled`, `position` и минимальные type-specific fields;
- `type` показывается readonly;
- реальные внешние интеграции в `0.1.74` не реализуются.

## 8. Public renderer

- widgets рендерятся как page-level floating layer после main content;
- raw external embed code не исполняется;
- `quick_request` и `callback` не делают fake submit;
- enabled widget с пустым `phone` / `email` / `url` рендерится как safe text-only placeholder, а не пропадает молча;
- preview/public renderer работает безопасно и не падает на unknown widget types.

## 9. Future modules

- modules могут регистрировать widget definitions через `sonyra_site_manager_widget_definitions`;
- disabled module definitions исчезают из add modal;
- уже сохранённые widgets остаются в storage model.

## 10. Widget card badges

- status badge показывает только краткий статус: `Включён` или `Выключен`;
- position badge показывает положение отдельным neutral badge;
- badges не являются action controls;
- widget enable/disable остаётся внутри modal `Параметры виджета`.
