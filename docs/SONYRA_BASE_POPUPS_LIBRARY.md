# SONYRA Base Popups Library

## Release

- Introduced in `0.1.75`.

## Model

- page popups are stored in `page.popups[]`.
- popups are a separate page-level layer.
- popups are not sections.
- popups are not blocks.
- popups are not widgets.

## Core registry

- `info`
- `request_form`
- `callback`
- `offer`
- `subscription`
- `notice`
- `confirmation`
- `document_text`
- `video_popup`
- `contacts`
- `external_popup`

## Contracts

- popup definitions come from `Sonyra_Site_Manager_Page_Elements_Library`.
- extensions can register popup definitions through `sonyra_site_manager_popup_definitions`.
- popup labels/descriptions use `label_key` and `description_key`.
- unknown and external popup instances must be preserved on save.

## Manager UI

- page actions include `Поп-апы`.
- popups use the page-level context header pattern.
- popup cards support add, select, edit, delete, and drag reorder.

## Public safety

- disabled popups do not render.
- unknown popups do not fatal.
- `external_popup` is rendered only as escaped safe content.
- raw popup embed code is not executed on public or preview routes.

## 0.1.75 hotfix

- public/preview popup output uses overlay modal markup, not normal in-flow content cards.
- `manual` stays hidden by default.
- `page_load` opens shortly after load.
- `delay` opens after a safe default timeout.
- `scroll` opens once after scroll threshold.
- `exit_intent` is desktop-only minimal runtime.
- `first_visit` is treated as `page_load` in `0.1.75`.
