# SONYRA Gradient System

## Назначение

Этот документ фиксирует контракт будущей системы градиентов SONYRA Site Manager.

Градиенты являются системными preset/token-объектами, а не случайными CSS-фрагментами.

## Общие правила SONYRA

Во всех будущих gradient-этапах используются только канонические написания:

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

## Gradient foundation

Запрещены случайные linear-gradient/radial-gradient внутри отдельных экранов без preset/token.

Градиенты должны использоваться через design tokens или gradient presets.

Будущий файл:

- assets/design/sonyra-gradients.css

Градиенты должны поддерживать:

- публичный рендер страниц;
- manager UI;
- карточки;
- hero-секции;
- кнопки;
- акцентные зоны.

## Типы градиентов

Система должна поддерживать типы:

- solid;
- linear;
- radial;
- multi-stop;
- layered;
- blob/spot;
- section accent;
- hero gradient;
- card gradient;
- button gradient.

## Data model для gradient preset

Каждый gradient preset должен иметь data model:

- id
- title
- description
- type
- tokens
- css_variables
- stops
- angle
- opacity
- usage_scope
- light_mode
- dark_mode
- accessibility_notes

## UI и i18n

Каждый preset должен иметь понятное русское название в UI через i18n.

Видимые названия preset на английском запрещены.

Технические id, tokens и css_variables могут быть на английском, если они не являются пользовательскими UI-строками.

## Security и public renderer

Public renderer не получает сырой небезопасный CSS от пользователя.

Пользовательские значения должны проходить sanitize, validate и ограничение разрешённой модели preset.

## Проверки

Каждый gradient-этап должен подтвердить:

- нет случайных градиентов вне системы;
- каждый preset имеет понятное русское название в UI через i18n;
- нет английских видимых названий preset;
- contrast/readability проверяются;
- public renderer не получает сырой небезопасный CSS от пользователя.

## STOP-условия

Немедленно остановиться, если требуется добавить случайный gradient вне preset/token, вывести английское видимое название preset, обойти i18n или передать небезопасный CSS в public renderer.
