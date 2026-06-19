# SONYRA i18n Rules

## 1. Current runtime

- текущий язык manager UI: `ru_RU`;
- fallback language: `ru_RU`;
- текущий источник русских строк: `includes/i18n/ru.php`;
- релиз `0.1.72` не включает language switcher;
- видимый UI остаётся русским.

## 2. Base rules

- все visible strings идут через i18n keys;
- русские тексты хранятся только как значения ключей;
- новые key names должны быть technical English names;
- key names должны быть lowercase и dot-separated;
- кириллица в key names запрещена;
- key names не должны повторять видимую фразу.

## 3. Recommended namespaces

- `manager.*`
- `manager.nav.*`
- `manager.header.*`
- `manager.pages.*`
- `manager.pages.table.*`
- `manager.pages.sections.*`
- `manager.pages.blocks.*`
- `manager.page_elements.sections.*`
- `manager.page_elements.blocks.*`
- `manager.page_elements.widgets.*`
- `manager.page_elements.popups.*`
- `manager.help.*`
- `manager.settings.*`
- `manager.notices.*`
- `manager.modals.*`
- `manager.actions.*`
- `manager.status.*`
- `manager.errors.*`

## 4. Runtime payload

- JS получает `currentLocale`;
- JS получает `fallbackLocale`;
- JS получает `dictionary`;
- для релиза `0.1.72` оба locale равны `ru_RU`;
- missing translations должны fallback-нуться к `ru_RU`.

## 5. Library compatibility

- Help Library использует `i18n_title_key` и `i18n_body_key`;
- Page Elements Library использует `label_key` и `description_key`;
- external modules должны регистрировать i18n-compatible labels, descriptions и help texts;
- raw visible Russian/English text не должен внедряться в core UI напрямую.

## 6. Audit rules

- hardcoded visible Russian вне `includes/i18n/ru.php` в changed UI-files запрещён;
- hardcoded visible fallback English запрещён;
- новые key names с кириллицей запрещены;
- incomplete future language pack не должен ломать UI;
- при missing translation runtime обязан безопасно fallback-нуться к `ru_RU`.
