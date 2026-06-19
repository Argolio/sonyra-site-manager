# SONYRA Language Pack Contract

## 1. Purpose

SONYRA Site Manager поддерживает будущие language packs без перевода продукта в релизе `0.1.73`.

## 2. Current release

- active UI language: `ru_RU`;
- fallback language: `ru_RU`;
- language switcher в `0.1.73` отсутствует;
- дополнительные переводы в `0.1.73` не подключаются.

## 3. Language pack file model

Будущая структура может использовать:

- `includes/i18n/ru.php`
- `includes/i18n/en.php`
- `includes/i18n/es.php`
- `includes/i18n/fr.php`
- `includes/i18n/de.php`
- `includes/i18n/zh.php`
- `includes/i18n/ja.php`

или future external language packs, если такой contract будет утверждён позже.

## 4. Key naming rules

- только English technical keys;
- без кириллицы в key names;
- без visible phrase как key;
- stable namespaces без привязки к текущему русскому тексту.

## 5. Fallback rules

- missing translation fallback-ится к `ru_RU`;
- incomplete language pack не должен ломать UI;
- current visible UI остаётся русским, пока другой locale не станет реально поддержанным runtime.

## 6. JS payload

- `currentLocale`
- `fallbackLocale`
- `dictionary`

## 7. Help Library

- help entries используют `i18n_title_key` и `i18n_body_key`;
- переводы help entries должны подключаться через language pack, а не через hardcoded text в JS.

## 8. Page Elements Library

- definitions sections/blocks/widgets/popups используют `label_key` и `description_key`;
- page-level popup UI strings for 0.1.75 are delivered only through `includes/i18n/ru.php`;
- `type` остаётся technical stable key;
- русские type keys запрещены.
- Base Blocks Library использует `manager.page_elements.blocks.*` для labels/descriptions.

## 9. Modules

External modules должны регистрировать i18n-compatible labels, descriptions и help texts и не должны внедрять raw visible Russian/English в core UI.

Color Controller labels, counters, tab names and help text должны идти через i18n keys из `includes/i18n/ru.php`.
Future preset names могут приходить из core default data в PHP, но не из hardcoded visible strings в JS.

## 10. L10N future

Нужно учитывать:

- date/time format;
- currency;
- legal/cookie text;
- RTL languages later;
- long German/French labels;
- compact Chinese/Japanese labels.

## 11. Release checklist

- no hardcoded visible strings;
- no Cyrillic key names;
- language pack fallback safe;
- current UI remains Russian.

## 12. Widgets compatibility

- widget labels/descriptions используют `manager.page_elements.widgets.*`;
- widget help texts используют `manager.help.pages.widgets.*`;
- widget UI использует `manager.pages.widgets.*`;
- visible widget strings не должны хардкодиться вне language pack.
