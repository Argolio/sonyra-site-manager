# SONYRA Brand Assets

## Назначение

Этот документ фиксирует контракт будущей системы бренда и логотипа SONYRA Site Manager.

Логотип — системный объект, а не случайный img.

## Общие правила SONYRA

Во всех будущих brand-этапах используются только канонические написания:

- SONYRA
- SONYRA Site Platform
- SONYRA Site Manager
- SONYRA STUDIO

Неканонические варианты названия SONYRA нельзя использовать как рабочие названия.

Весь видимый пользовательский интерфейс будущего продукта должен быть только на русском языке.

Английские пользовательские UI-строки и англицизмы запрещены.

Исключения допустимы только для технически неизбежных терминов: API, REST, HTML, iframe, URL, JSON, CSS, JS, PHP, WordPress, shortcode, nonce, slug.

Пользовательские строки будущего интерфейса должны идти через i18n.

Будущий основной i18n-файл:

- assets/i18n/i18n-ru.js

Будущий helper:

- t('key')

Никаких “красивых, но мёртвых” кнопок. Каждый интерактивный элемент должен иметь рабочую цепочку:

DOM/root selector -> selector элемента -> handler -> loading state -> request/action -> success/error render -> UI update.

Deploy по умолчанию запрещён. Будущие zip-релизы собираются только в ./dist/. Предыдущие zip-релизы нельзя удалять.

## Брендовая модель продукта

“Пульт сайта” — русское коммерческое название плагина.

SONYRA Site Platform — платформа.

SONYRA STUDIO — издатель и бренд-производитель.

ИП Катаев С.А. / IPKTFSA — правообладатель, лицензиар и продавец.

Эти сущности нельзя смешивать.

В пользовательском интерфейсе название плагина нужно показывать по-русски.

В технических ключах и файлах сохраняется sonyra-site-manager.

## Brand Assets model

Brand Service и Logo Service будут работать через Brand Assets model.

Логотип должен использоваться в:

- manager branding;
- public header;
- public footer;
- body sections;
- login/access screen;
- embed/iframe shell;
- future modules;
- future personal cabinet.

## Варианты brand assets

Система должна поддерживать варианты:

- основной логотип;
- тёмный логотип;
- светлый логотип;
- компактный логотип;
- логотип футера;
- favicon;
- app icon;
- OG image.

## Logo settings

Пользователь должен иметь возможность настраивать:

- ширину;
- высоту;
- max-width;
- max-height;
- desktop размеры;
- tablet размеры;
- mobile размеры.

## Data model для brand asset

Будущий brand asset должен иметь data model:

- id
- type
- title
- attachment_id
- url
- width
- height
- alt
- usage_scope
- desktop_settings
- tablet_settings
- mobile_settings
- created_at
- updated_at

## UI и i18n

Alt/title, если они видимы пользователю, должны быть на русском языке и выводиться через i18n.

Английские пользовательские UI-строки в настройках бренда запрещены.

Технические поля data model могут быть на английском, если они не являются видимыми UI-строками.

## Storage и media rules

Настройки логотипа должны сохраняться безопасно.

Загрузка или выбор медиа проходит через безопасный слой.

Нельзя показывать сырой WordPress Media UI как основной UX будущего менеджера.

WordPress Media Library можно использовать как storage.

## Security

Brand Assets model должна учитывать:

- capability checks;
- nonce/session check для сохранения;
- sanitize input;
- validate input;
- escape output;
- audit log для важных изменений;
- запрет небезопасных URL.

## STOP-условия

Немедленно остановиться, если brand-этап требует случайного img вместо системного logo object, обхода Brand Service, обхода Logo Service, обхода i18n, английских видимых UI-строк или небезопасного сохранения медиа.
