# SONYRA Site Section Contract

Этот документ фиксирует foundation раздела “Сайт” в SONYRA Site Manager начиная с версии `0.1.68`.

## Назначение

“Сайт” — первый рабочий экран публичной основы конкретного сайта внутри protected `/manager`.

Раздел показывает:

- публичные данные сайта;
- домен и технический адрес;
- состояние публикации;
- шапку и подвал;
- юридические документы;
- cookie и согласия;
- служебные страницы;
- базовое SEO и карточки ссылок;
- контрольный список готовности к публикации.

## Реализация 0.1.68

В версии `0.1.68` раздел `setup` больше не является placeholder. Он рендерит честный foundation UI без записи данных и без публикации.

Разрешено:

- показывать карточки будущих настроек;
- показывать честные статусы `needs_setup`, `not_checked`, `optional`, `future`;
- использовать route-кнопки для перехода в существующие разделы;
- использовать только `icon_key` из SONYRA Icon Library;
- выводить все видимые строки через `includes/i18n/ru.php`.

Запрещено:

- сохранять данные;
- показывать fake success;
- показывать fake metrics;
- создавать WordPress Pages;
- создавать REST save endpoint;
- менять DB schema или DB version;
- включать public rendering;
- публиковать сайт автоматически;
- добавлять checkbox UI;
- обращаться к внешним API;
- подключать runtime обновлений, лицензий, billing или Owner Console.

## Route Contract

Раздел сохраняет постоянный route:

- route: `setup`;
- nav label: “Сайт”;
- icon: `app-window`;
- page header берётся из единого manager section manifest.

Новый route, slug плагина или versioned plugin root не создаются.

## UI Contract

UI раздела использует premium SaaS pattern текущего manager:

- hero panel;
- status panel;
- compact cards;
- rounded badges;
- responsive grids;
- keyboard-safe route buttons;
- без активных полей ввода на этом этапе.

## Boundary

Раздел “Сайт” отвечает за тексты документов и cookie-настройки. Факты согласий, audit log и security runtime относятся к разделу “Безопасность”.

Dashboard может ссылаться на состояние foundation “Сайт”, но в `0.1.68` не вычисляет готовность автоматически и не показывает `ready` без фактического сигнала.
