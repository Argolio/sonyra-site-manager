# SONYRA Versioning Rules

## Единый источник версии

SONYRA Site Manager должен иметь единый источник версии, определённый на будущем этапе реализации.

До появления кода версия фиксируется только как требование. Нельзя создавать техническую константу, header или runtime-файл раньше разрешённого этапа.

## Где версия должна отображаться

После появления кода версия должна быть синхронизирована в следующих местах:

- plugin header;
- SONYRA_SITE_MANAGER_VERSION;
- asset version;
- REST bootstrap;
- manager footer/about;
- diagnostics;
- version log;
- release checklist.

## Где версия должна проверяться

Версия должна проверяться:

- перед каждым release-этапом;
- при сборке asset version;
- при регистрации REST bootstrap;
- в diagnostics;
- в финальном отчёте Codex;
- в release checklist.

## Проверка plugin header и бренда

Перед каждым будущим релизом plugin header должен содержать:

- Plugin Name: Пульт сайта
- Author: SONYRA STUDIO
- Text Domain: sonyra-site-manager

Версия zip должна совпадать с plugin header и SONYRA_SITE_MANAGER_VERSION.

Mismatch названия или версии = STOP.

## Правило синхронизации

Значения version в plugin header, SONYRA_SITE_MANAGER_VERSION, asset version, REST bootstrap, manager footer/about, diagnostics, version log и release checklist должны совпадать.

Если найден mismatch — STOP.

Запрещено:

- выпускать релиз при mismatch;
- менять версию только в одном месте;
- скрывать mismatch в отчёте;
- продолжать следующий этап без устранения mismatch.

## Версия zip-релиза

Имя zip-релиза должно включать точную версию.

Формат имени:

- sonyra-site-manager-X.X.X.zip

Версия в имени zip должна совпадать с plugin header.

Версия в имени zip должна совпадать с SONYRA_SITE_MANAGER_VERSION.

Версия в имени zip должна совпадать с asset version, REST bootstrap, diagnostics и manager UI, когда эти части будут реализованы.

Mismatch версии = STOP.

Нельзя собирать release zip при mismatch.

## Версия update manifest

Версия update manifest должна совпадать с версией zip-пакета, plugin header и SONYRA_SITE_MANAGER_VERSION.

Если latest_version, package version, plugin header или SONYRA_SITE_MANAGER_VERSION не совпадают — STOP.

Update manifest не может объявлять обновление для другого slug.

Slug update manifest должен совпадать с sonyra-site-manager.

## Формат отчёта по версии

Каждый этап, который касается версии, должен включать:

- текущую версию;
- источник версии;
- список мест, где версия проверена;
- результат сравнения;
- наличие или отсутствие mismatch;
- вывод: release allowed или STOP.

## Видимый интерфейс версии

Если версия видна пользователю в manager footer/about или diagnostics, подписи должны быть на русском языке и выводиться через assets/i18n/i18n-ru.js с helper t('key').

## Managed Site Agent versioning

Managed Site Agent implementation stages must bump plugin version only when PHP/code/zip changes occur.

Documentation-only managed site stages must not bump plugin version and must not build zip.

Separate Owner Control Plugin has its own future versioning and must not be mixed into SONYRA Site Manager releases.

Если этап только документирует Remote Control API, managed site identity, security contract, telemetry boundaries или Owner Control Plugin architecture, версия SONYRA Site Manager остаётся неизменной.
