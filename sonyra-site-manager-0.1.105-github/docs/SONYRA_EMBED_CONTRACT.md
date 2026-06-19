# SONYRA Embed Contract

## Назначение

Этот документ фиксирует контракт будущего export/embed-слоя SONYRA Site Manager.

Shortcode, iframe и HTML-код — это слой вставки/export, а не основа архитектуры страниц.

Публичные страницы SONYRA Site Manager не должны строиться на WordPress Pages.

## Общие правила SONYRA

Во всех будущих embed-этапах используются только канонические написания:

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

## Export/embed architecture

Shortcode не основа архитектуры страниц. Он допустим только как export/embed-слой.

iframe не основа архитектуры страниц. Он допустим только как export/embed-слой.

HTML-код не основа архитектуры страниц. Он допустим только как export/embed-слой.

Embed routes должны быть отдельными.

Iframe renderer должен иметь отдельный shell.

Embed token foundation обязателен.

Allowed domains foundation обязателен.

Copy buttons должны быть рабочими, а не декоративными.

UI экрана вставок должен быть полностью на русском языке.

## Будущие сервисы

Будущий export/embed-слой должен предусматривать:

- Embed Service
- Shortcode Service
- Iframe Renderer
- Embed Token Service
- Embed Routes
- Embed Permissions

## Будущий экран /manager/embeds

Экран /manager/embeds должен показывать:

- объект;
- shortcode;
- iframe-код;
- HTML-код;
- кнопку копирования;
- предпросмотр;
- статус;
- предупреждение безопасности.

Видимые подписи должны быть по-русски:

- “Код вставки”
- “Скопировать”
- “Предпросмотр”
- “Статус”
- “Предупреждение безопасности”

Не использовать видимые английские подписи:

- Copy
- Preview
- Status
- Embed
- Shortcode
- iframe code
- HTML code

Технические термины shortcode, iframe, HTML допустимы, если они необходимы для точности, но интерфейсная фраза должна быть русской. Правильно: “Скопировать iframe-код”. Неправильно: “Copy iframe code”.

## Security contract

Embed routes должны иметь явный public/private/embed-only/iframe-only контракт.

Embed Token Service должен проверять token scope, срок действия и доступ.

allowed domains должны проверяться до показа iframe или embed-ответа.

Embed Permissions должны учитывать capability checks для manager-действий.

Все важные действия должны попадать в Audit Log.

## UI-chain contract

Кнопка копирования не считается готовой без цепочки:

DOM/root selector -> selector элемента -> handler -> loading state -> request/action или copy action -> success/error render -> UI update.

Ошибки копирования должны иметь русское видимое сообщение через i18n.

## STOP-условия

Немедленно остановиться, если embed-этап требует использовать WordPress Pages как основу публичных страниц, shortcode как основу архитектуры, старый NDD-код, manager-app.js, manager-v2, английские видимые UI-строки, обход i18n, обход allowed domains или небезопасный embed token.
