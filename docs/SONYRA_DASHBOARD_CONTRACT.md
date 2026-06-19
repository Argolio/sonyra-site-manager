# SONYRA Dashboard Contract

Этот документ фиксирует foundation раздела “Главное” в SONYRA Site Manager начиная с версии `0.1.66`.

## Назначение

“Главное” — базовый dashboard конкретного клиентского сайта внутри protected `/manager`.

Раздел состоит из двух сценариев:

- setup dashboard для первичной подготовки сайта;
- working dashboard foundation для будущей рабочей сводки после настройки.

В версии `0.1.66` не реализуются реальные analytics, external API, REST endpoints, DB writes, marketplace UI, installer, billing runtime, Owner Console или public rendering.

Начиная с версии `0.1.67`, shell-level Notice Center располагается выше dashboard content и принимает важные ошибки, предупреждения, license/update notices и будущие extension alerts. Dashboard не должен дублировать системные ошибки внутри своих карточек, если они относятся к общему manager-состоянию.

Начиная с версии `0.1.68`, раздел “Сайт” имеет собственный foundation screen на route `setup`. Dashboard может направлять пользователя в этот раздел, но не вычисляет готовность сайта автоматически и не ставит `ready` без фактического сигнала.

## Setup Dashboard

Setup mode работает всегда:

- без расширений;
- с будущими расширениями;
- без DB migrations;
- без автоматического перехода в working mode.

Core setup steps:

- site — “Сайт”, route `setup`, required;
- pages — “Страницы”, route `pages`, required;
- design — “Дизайн”, route `design`, required;
- documents — “Документы”, route `setup`, required;
- cookie — “Cookie”, route `setup`, required;
- form — “Форма”, route `pages`, optional;
- security — “Безопасность”, route `security`, required;
- publication — “Публикация”, route `setup`, required.

Каждый setup step имеет:

- `key`;
- `title`;
- `description`;
- `section_route`;
- `icon_key`;
- `status`;
- `required`;
- `action_label`;
- `action_route`.

## Statuses

Разрешённые foundation statuses:

- `not_checked`;
- `needs_setup`;
- `ready`;
- `optional`.

Без фактического сигнала core не должен ставить `ready`. Default foundation state использует честные статусы `needs_setup`, `not_checked` и `optional`.

## Working Dashboard Foundation

Working dashboard foundation не показывает fake metrics.

Разрешённые карточки без чисел:

- Сайт;
- Документы и cookie;
- Формы;
- Безопасность;
- Модули;
- Обновления и связь.

Данные появляются только после настройки сайта, проверок и подключения расширений.

В `0.1.68` состояние “Сайт” остаётся foundation-состоянием: публичные данные, документы, cookie, шапка, подвал, служебные страницы и SEO показываются как зоны настройки без DB writes и без публикации.

Запрещены fake values:

- fake visits;
- fake sales;
- fake applications;
- fake SEO scores;
- fake “site perfect” states;
- любые числа без источника.

## Extension Slots

Будущие расширения могут добавлять данные через filters:

- `sonyra_site_manager_dashboard_setup_steps`;
- `sonyra_site_manager_dashboard_status_cards`;
- `sonyra_site_manager_dashboard_alerts`;
- `sonyra_site_manager_dashboard_metrics`;
- `sonyra_site_manager_dashboard_activity`;
- `sonyra_site_manager_dashboard_mode`;
- `sonyra_site_manager_dashboard_completion`.

Filters принимают текущий массив и context. Core должен продолжать работать без расширений. Если расширение возвращает неподходящий тип, core сохраняет безопасное значение.

Расширения могут добавлять:

- setup steps;
- dashboard cards;
- alerts;
- metrics placeholders;
- activity items;
- completion signals.

Расширения не могут:

- добавлять fake metrics;
- обходить i18n;
- обходить security rules;
- менять core DB без отдельного контракта;
- ломать семь базовых разделов;
- подменять sidebar, page header или dashboard layout.

## Completion Modal Contract

Completion modal — foundation для будущего сообщения “САЙТ ГОТОВ К РАБОТЕ”.

В версии `0.1.66`:

- modal markup может существовать hidden;
- modal не показывается автоматически;
- completion false/unknown оставляет modal hidden;
- реальное открытие требует будущего completion signal;
- extension setup steps должны учитываться в будущей completion logic.

## Privacy And Owner Console Boundary

“Главное” показывает dashboard конкретного сайта. Owner Console — отдельная будущая система сопровождения платформы.

Dashboard `0.1.66`:

- не отправляет данные наружу;
- не обращается к `updates.dobromap.ru`;
- не отправляет telemetry;
- не собирает персональные данные;
- не хранит analytics;
- не делает DB writes.

Центральная система сопровождения в будущем получает только технический и лицензионный минимум по отдельному contract.

## UI Rules

Dashboard UI должен использовать premium SaaS pattern текущего manager:

- cards;
- compact badges;
- section panels;
- icon_key из SONYRA Icon Library;
- visible strings через i18n;
- без hardcoded SVG;
- без английских UI-строк;
- без fake analytics.

Notice Center над dashboard не является частью analytics area и не должен показывать fake promo или fake metrics без реального источника.
