# SONYRA Notice Center Contract

Этот документ фиксирует foundation единого Manager Notice Center в SONYRA Site Manager начиная с версии `0.1.67`.

## Назначение

Manager Notice Center — единая зона важных сообщений внутри protected `/manager`.

Размещение:

- сразу под page header;
- выше dashboard и контента любого manager-раздела;
- внутри manager shell;
- без отдельного пункта меню.

Notice Center предназначен для:

- ошибок;
- предупреждений;
- системных уведомлений;
- сообщений о лицензии, связи и обновлениях;
- будущих промо-баннеров расширений из repository;
- будущего обращения в систему сопровождения.

В версии `0.1.67` Notice Center не делает external API calls, REST endpoints, DB writes, telemetry, support send, marketplace runtime, install/update runtime, billing runtime или Owner Console runtime.

## Notice Object Schema

Базовый notice object:

- `id` — стабильный идентификатор notice;
- `source` — `core`, `extension`, `repository`, `support`, `system`;
- `type` — `error`, `warning`, `info`, `promo`, `success`;
- `severity` — `critical`, `high`, `medium`, `low`;
- `title` — ключ i18n или безопасный текст расширения;
- `description` — ключ i18n или безопасный текст расширения;
- `icon_key` — ключ из SONYRA Icon Library;
- `primary_action` — основное действие;
- `secondary_action` — вторичное действие;
- `dismissible` — можно ли закрыть notice в UI;
- `route` — manager route, если notice относится к разделу;
- `extension_slug` — slug расширения, если notice пришёл от расширения;
- `error_code` — технический код без секретов;
- `created_at` — будущая дата создания;
- `expires_at` — будущая дата окончания показа;
- `support_allowed` — разрешён ли будущий сценарий поддержки;
- `support_payload` — будущий минимальный технический контекст.

Extension output должен проходить normalization и escaping. Raw HTML в `title`, `description` и action labels запрещён.

## Priority Sorting

Notice Center сортирует сообщения по приоритету:

1. critical error;
2. high error;
3. warning;
4. license, connection или update system notice;
5. info;
6. promo;
7. success.

Критичные ошибки всегда выше promo. Promo не перекрывает ошибки и не помещается в slider/carousel.

Если notices несколько, они выводятся вертикальным stack. Auto-rotation, carousel и скрытие критичных ошибок запрещены.

## Actions

Action object:

- `type` — `route`, `reload`, `retry`, `open_modal`, `external_later`, `support_later`, `dismiss`;
- `label` — ключ i18n или безопасный текст;
- `route` — manager route для `route`;
- `payload` — будущий безопасный контекст;
- `disabled` — действие недоступно;
- `future_only` — действие документировано, но не активно в текущей версии.

В `0.1.67` реализованы только безопасные UI-действия:

- reload page;
- route switching через существующий manager route handler;
- dismiss для допустимых info/promo/success notices без persistence;
- close hidden support modal foundation.

Не реализованы:

- отправка обращения в поддержку;
- external API calls;
- repository fetch;
- marketplace install;
- license refresh;
- telemetry.

## Current Logout Error

Ошибка logout переведена в core notice:

- `id`: `manager_logout_error`;
- `source`: `core`;
- `type`: `error`;
- `severity`: `high`;
- `icon_key`: `alert-circle`;
- title: “Не удалось выйти”;
- description: “Обновите страницу и попробуйте ещё раз”;
- primary action: reload page.

Logout backend, REST endpoint, auth/session logic и access guard не меняются.

## Support Request Foundation

Будущий support flow:

1. Notice error может иметь `support_allowed: true`.
2. Пользователь открывает “Сообщить о проблеме”.
3. Modal показывает title, code и безопасный context.
4. Пользователь может добавить optional comment.
5. После будущего подключения системы сопровождения событие отправляется в Owner Console.

В `0.1.67` разрешён только hidden modal foundation. Реальной отправки нет, fake success нет.

Будущий support payload может содержать только технический минимум:

- `site_id`;
- domain;
- SONYRA Site Manager version;
- WordPress version;
- PHP version;
- `error_code`;
- `notice_id`;
- current route;
- time;
- health summary;
- optional user comment.

Запрещено отправлять:

- контент сайта;
- заявки клиентов сайта;
- персональные данные клиентов сайта;
- тексты страниц;
- содержимое форм;
- полный журнал согласий;
- raw tokens;
- auth sessions;
- cookie пользователей.

## Promo And Repository Banner Foundation

Будущий repository или extension promo notice:

- `source`: `repository` или `extension`;
- `type`: `promo`;
- `severity`: `low`;
- `extension_slug`;
- `title`;
- `description`;
- `icon_key`;
- primary action: открыть раздел “Модули” или карточку модуля;
- `dismissible`: true;
- `expires_at`: optional.

В `0.1.67` нет runtime-запросов к `updates.dobromap.ru` и нет fake promo в UI.

Витрина расширений относится к разделу “Модули”. Notice Center может только показать будущий приоритетный баннер, не устанавливая и не покупая расширения самостоятельно.

## Extension Slots

Core предоставляет filters:

- `sonyra_site_manager_notices`;
- `sonyra_site_manager_notice_types`;
- `sonyra_site_manager_notice_actions`.

Rules:

- invalid notice игнорируется;
- unsupported source/type/severity/action получает safe fallback или игнорируется;
- raw HTML запрещён;
- output всегда escaping на render;
- `icon_key` должен быть из SONYRA Icon Library, иначе renderer использует fallback;
- extension не может прятать critical core errors в promo UI;
- extension не может создавать external call внутри core notice renderer.

## Relationship With Dashboard

Dashboard не дублирует системные ошибки. Dashboard может использовать Notice Center для важных warnings, license/update notices и extension alerts.

Notice Center находится выше dashboard content и остаётся shell-level элементом, доступным всем разделам.

## Relationship With Owner Console

Owner Console — будущая центральная система сопровождения, мониторинга, лицензий и обновлений.

Notice Center может стать точкой входа для будущих support events, но в `0.1.67` не передаёт данные наружу.

## UI Rules

Notice Center должен выглядеть как premium SaaS system-level component:

- compact cards;
- soft type colors;
- icon tile;
- title and description;
- actions справа или ниже на mobile;
- vertical stack;
- no horizontal scroll;
- no slider;
- no auto-dismiss for errors;
- visible strings через i18n;
- no hardcoded SVG.

## Security And Privacy

В `0.1.67` запрещены:

- external calls;
- REST endpoints;
- DB writes;
- support sending;
- telemetry;
- personal data collection;
- raw HTML from extensions;
- fake promo banners;
- fake analytics;
- auth/logout backend changes.
