# SONYRA SVG Icon Library

## Назначение

SONYRA SVG Icon Library — внутренняя библиотека SVG-иконок для SONYRA Site Manager.

Библиотека нужна, чтобы Codex, manager UI, будущие модули и будущий клиентский icon picker использовали стабильные `icon_key`, а не вручную нарисованные SVG, не сырой SVG-код и не внешние icon-пакеты на runtime.

## Текущий объём

В версии `0.1.56` был создан core pack из `119` outline SVG.

В версии `0.1.59` библиотека расширена до curated extended pack из `1000` outline SVG.

Расширенный pack намеренно не содержит все `6100+` иконок upstream Tabler, потому что полный upstream-набор включает много брендовых, узкоспециализированных, дублирующихся и редко применимых иконок. Для runtime-плагина важнее чистый полезный набор под интерфейс, страницы, услуги, формы, карточки, магазин, оплату, события и будущие модули.

## Источник

Единственный источник SVG для библиотеки:

- Tabler Icons.

Используется локальный package source `@tabler/icons` и только `icons/outline/*.svg`.

В `0.1.59` source package:

- `@tabler/icons` `3.44.0`;
- license: MIT;
- локальная копия лицензии: `assets/icons/tabler/LICENSE.txt`.

Запрещено:

- CDN;
- remote SVG;
- icon font;
- bitmap;
- emoji;
- filled/solid pack;
- brand/trademark icons;
- ручное рисование новых SVG Codex-агентом;
- исправление SVG path вручную “на глаз”.

Плагин должен работать автономно без внешней сети на runtime.

## Хранение

SVG-файлы хранятся локально:

- `assets/icons/tabler/outline/`

Лицензия источника хранится локально:

- `assets/icons/tabler/LICENSE.txt`

Manifest библиотеки:

- `assets/icons/sonyra-icons-manifest.json`

Пользовательские настройки, будущие модули и будущий picker должны хранить `icon_key`, а не raw SVG.

## Manifest

Каждая иконка описывается metadata:

- `key`
- `source`
- `source_name`
- `file`
- `category`
- `title_ru`
- `tags_ru`
- `style`
- `viewBox`
- `stroke`
- `fill`
- `license`
- `use_cases_ru`

Правила:

- `key` — lowercase kebab-case, только `a-z`, `0-9`, дефис;
- `source` — `tabler`;
- `style` — `outline`;
- `viewBox` — `0 0 24 24`;
- `stroke` — `currentColor`;
- `fill` — `none`;
- `license` — `MIT / Tabler Icons`;
- `category`, `title_ru`, `tags_ru`, `use_cases_ru` — на русском языке;
- `file` — только относительный путь вида `tabler/outline/name.svg`.

Metadata manifest не выводится в UI в `0.1.59`; это foundation для будущего picker/search.

## Категории

Основные категории manifest:

1. Интерфейс
2. Навигация
3. Действия
4. Сайт
5. Страницы
6. Дизайн
7. Текст
8. Медиа
9. Формы
10. Файлы
11. Пользователи
12. Контакты
13. Сообщения
14. Время
15. География
16. Услуги
17. Магазин
18. Финансы
19. Аналитика
20. Безопасность
21. Устройства
22. Интеграции
23. Системные
24. Образование
25. Медицина
26. Спорт
27. Еда
28. Транспорт
29. Дом
30. События

## Стандарт выбора `icon_key`

Codex в будущих задачах обязан выбирать `icon_key` из `assets/icons/sonyra-icons-manifest.json`.

Нельзя добавлять interface icons как hardcoded inline SVG в PHP renderer, JS templates, HTML fragments или CSS `content`.

Новые interface icons добавляются только через цепочку:

1. официальный Tabler outline SVG в `assets/icons/tabler/outline/`;
2. metadata в `assets/icons/sonyra-icons-manifest.json`;
3. вывод через `Sonyra_Site_Manager_Icon_Renderer` по `icon_key`;
4. проверка sanitizer/security.

Контекстные ориентиры:

- email/login/request code: `mail`, `key`, `shield-check`;
- OTP code: `key`, `lock`, `shield-lock`, `shield-check`;
- quick-login: `key`, `lock`, `device-mobile`;
- trusted device: `device-desktop`, `device-mobile`, `shield-lock`;
- success: `circle-check`;
- error: `alert-circle`;
- warning: `alert-triangle`;
- info/help: `info-circle`;
- close: `x`;
- back/next: `arrow-left`, `arrow-right`, `chevron-left`, `chevron-right`;
- dashboard: `layout-dashboard`;
- site: `app-window`, `world-www`;
- pages: `file-text`;
- design: `brush`;
- modules: `components`;
- security: `shield-check`;
- settings: `settings`;
- logout: `logout`;
- upload/download: `upload`, `download`, `file-upload`, `file-download`;
- external link: `external-link`;
- copy/edit/delete: `copy`, `edit`, `trash`;
- calendar/date/time: `calendar`, `clock`;
- contact: `mail`, `phone`, `map-pin`;
- analytics: `chart-line`, `chart-bar`, `gauge`;
- payments: `credit-card`, `receipt`, `wallet`.

Если нужный смысл отсутствует, нужно выбрать ближайший официальный `icon_key` из manifest. Если подходящего ключа нет, добавить новую иконку через безопасную цепочку выше. Рисовать SVG вручную запрещено.

## Что не заменяется библиотекой

Логотип ПУЛЬТ САЙТА, favicon, PNG brand assets, декоративные фоновые элементы и SONYRA brand visuals не заменяются icon library.

Raw custom SVG upload не входит в текущий этап и допускается только будущим отдельным security/sanitizer contract.

Пользовательский picker/search не входит в `0.1.59`; это отдельный будущий UI-этап.

REST API для icon library не входит в `0.1.59`.

## Renderer

`Sonyra_Site_Manager_Icon_Renderer` принимает `icon_key` и возвращает безопасный inline SVG.

Renderer:

- читает только whitelisted SVG через registry;
- не принимает raw SVG;
- не принимает внешние URL;
- не читает произвольные пути;
- удаляет опасные SVG-теги и event handlers;
- нормализует `class`;
- сохраняет `currentColor`;
- отдаёт decorative SVG с `aria-hidden="true"` и `focusable="false"`;
- отдаёт смысловой SVG с `role="img"` и `aria-label`, если это явно нужно.

## Registry

`Sonyra_Site_Manager_Icon_Registry` отвечает за:

- загрузку manifest;
- request-cache manifest;
- проверку `icon_key`;
- возврат metadata;
- возврат SVG path только внутри `assets/icons/tabler/outline/`;
- защиту от path traversal;
- безопасный fallback `circle-dot`.

Разрешённый формат `icon_key`:

- lowercase kebab-case;
- только `a-z`, `0-9`, дефис.

Запрещены:

- `../`;
- slash;
- backslash;
- null byte;
- URL;
- protocol;
- query string;
- произвольный путь.

## Audit hardcoded SVG

Перед заменой существующего SVG нужно разделить найденное на группы:

- A — interface icons, которые безопасно перевести на renderer сейчас;
- B — brand/logo/favicon/decorative assets, которые нельзя трогать;
- C — сложные или чувствительные SVG, которые могут сломать экран и должны остаться до Stage 2;
- D — уже переведённые icons через `Sonyra_Site_Manager_Icon_Renderer`.

Заменять можно только группу A, если замена:

- не меняет visual layout;
- не меняет размеры;
- не ломает CSS;
- не меняет тексты;
- не ухудшает accessibility;
- не затрагивает auth/session/quick-login logic.

Если есть риск visual regression, SVG остаётся до Stage 2 и фиксируется в отчёте.

## Stage 2 standardization result

В версии `0.1.60` безопасные PHP-rendered interface icons входной группы переведены на `Sonyra_Site_Manager_Icon_Renderer`.

Заменённая группа A:

- heading/status/help/close icons в `includes/class-sonyra-site-manager-login-renderer.php`;
- прежние legacy names сохранены как внутренний compatibility mapping;
- output class names остались прежними, поэтому размеры, цвет и alignment продолжают управляться существующим `assets/css/sonyra-login.css`.

Оставлено на Stage 3:

- dynamic status icon factory внутри inline JS login renderer, потому что замена требует отдельного шаблонного JS/PHP-моста и может затронуть runtime status flow;
- brand/logo/favicon/decorative SVG assets, потому что они не являются interface icons.

После Stage 2 новые interface icons по-прежнему запрещено добавлять как hardcoded inline SVG. Используется только manifest `icon_key` и renderer.

## Stage 3 runtime JS standardization result

В версии `0.1.61` runtime status icons входной группы переведены с ручного JS SVG factory на скрытые PHP-rendered templates через `Sonyra_Site_Manager_Icon_Renderer`.

Закрытая группа A:

- `createStatusIcon()` в `includes/class-sonyra-site-manager-login-renderer.php` больше не создаёт `svg`/`path` через `document.createElementNS`;
- JS не хранит hardcoded `path d`;
- JS клонирует только заранее отрендеренные `template` для whitelisted states `neutral`, `success`, `warning`, `error`;
- templates содержат только минимальный набор `icon_key`: `info-circle`, `circle-check`, `clock-hour-3`, `alert-circle`;
- `warning` сохранён как timer-style icon через `clock-hour-3`, потому что прежний runtime SVG визуально показывал clock/cooldown state.

Оставлено вне замены:

- brand/logo/favicon/decorative assets не являются interface icons;
- manager/sidebar/footer/dashboard icons уже выводятся через renderer;
- новых JS SVG factories после Stage 3 не добавлено.

Для будущего JS разрешён только один из безопасных способов: server-prepared safe icon map или hidden templates, сформированные через renderer и строгий whitelist.

Запрещено для будущего JS:

- `innerHTML` с raw SVG;
- ручное создание SVG/path и hardcoded `path d`;
- external/CDN SVG;
- принятие `icon_key` или raw SVG из пользовательского ввода/REST response без whitelist;
- загрузка всех SVG библиотеки в browser payload.

## Manager page header icon standard

Начиная с версии `0.1.64`, page header protected `/manager` использует `icon_key` из section metadata.

Правила:

- page header icon выводится только через `Sonyra_Site_Manager_Icon_Renderer`;
- JS route switching не создаёт SVG вручную и не хранит hardcoded `path d`;
- для переключения route допускаются только hidden templates, сформированные PHP renderer из whitelisted section metadata;
- `logout` остаётся action icon и не является page section icon;
- новые page section icons выбираются из `assets/icons/sonyra-icons-manifest.json`;
- SVG manifest, icon pack, registry и renderer не меняются внутри обычного page header patch.

## Добавление новой иконки

Чтобы добавить новую иконку:

1. Взять официальный Tabler outline SVG.
2. Проверить, что это не brand/trademark и не filled variant.
3. Положить SVG в `assets/icons/tabler/outline/`.
4. Добавить metadata в `assets/icons/sonyra-icons-manifest.json`.
5. Проверить, что `key` unique и kebab-case.
6. Проверить, что файл существует.
7. Проверить sanitizer/security audit.
8. Проверить, что архив не содержит `node_modules`, полный upstream dump или временные package cache.
