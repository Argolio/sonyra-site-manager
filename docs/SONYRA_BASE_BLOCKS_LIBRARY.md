# SONYRA Base Blocks Library

## 1. Purpose

Base Blocks Library — это block layer внутри выбранной секции:

- `page -> sections -> blocks`;
- блоки живут в `section.blocks`;
- библиотека блоков используется только в правой колонке `Блоки секции`.

## 2. Block definition shape

- `type`
- `label`
- `description`
- `label_key`
- `description_key`
- `icon`
- `category`
- `source`
- `module_key`
- `enabled`
- `fields`
- `defaults`

## 3. Core block types

- `accent` — Акцент
- `heading` — Заголовок
- `text` — Текст
- `button` — Кнопка
- `image` — Изображение
- `video` — Видео
- `card` — Карточка
- `icon_text` — Иконка и текст
- `list` — Список
- `metric` — Показатель
- `step` — Шаг
- `testimonial` — Отзыв
- `person_profile` — Профиль человека
- `logo` — Логотип
- `contact` — Контакт
- `map` — Карта
- `form` — Форма
- `faq_item` — Вопрос-ответ
- `link` — Ссылка
- `file` — Файл
- `divider` — Разделитель
- `social_link` — Социальная ссылка
- `price` — Цена
- `tag` — Метка
- `embed` — Встроенный код

## 4. Storage model

- storage model не меняется;
- страницы остаются в option `sonyra_site_manager_pages`;
- blocks order следует порядку `section.blocks`;
- unknown/external blocks сохраняются и не удаляются молча.

## 5. Add Block modal

- modal `Добавить блок` работает как single-select flow;
- один click по card создаёт ровно один block;
- pending guard защищает от double-click duplicates;
- после успешного add modal закрывается;
- новый block становится active.

## 6. Block settings modal

- modal `Параметры блока` редактирует `name` и минимальные type-specific fields;
- type показывается только readonly;
- advanced styles, conditions и icon picker в `0.1.73` не входят.

## 7. Public renderer

- public/preview renderer рендерит blocks безопасно;
- text escaping обязателен;
- raw embed code не исполняется;
- form block не отправляет данные и не показывает fake success.

## 8. Future modules

- future modules могут регистрировать block definitions через filter `sonyra_site_manager_block_definitions`;
- disabled module definitions исчезают из add modal;
- уже сохранённые block instances остаются в data model.
