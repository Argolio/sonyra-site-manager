# SONYRA Update Contract

## Назначение

Этот документ фиксирует будущий private updater contract для плагина “Пульт сайта” / SONYRA Site Manager.

Сейчас updater не реализуется. Этот документ описывает только контракт, ограничения, будущие этапы и проверки.

## Базовые правила

Пульт сайта не обновляется через wordpress.org.

Будущий источник обновлений:

- https://updates.dobromap.ru/public/index.php?action=check

Обновления должны быть приватными и контролируемыми.

На ранних этапах основной способ установки остаётся ручной zip через ./dist/.

Private updater внедряется отдельной безопасной линией, а не смешивается со scaffold, audit log, routes, REST или manager UI.

Codex не должен писать updater code, подключаться к update endpoint, выполнять HTTP-запросы или менять update-механику вне явно объявленного updater-этапа.

## Security requirements

Нельзя внедрять updater без checksum verification.

Нельзя внедрять updater без проверки версии.

Нельзя внедрять updater без безопасной обработки ошибок.

Нельзя внедрять updater без защиты от подмены пакета.

Signed manifest должен быть запланирован.

License/update integration должен быть запланирован.

Update install без validation запрещён.

Package URL должен принадлежать только разрешённому update-домену.

При недоступности update-сервера сайт должен продолжать работать.

Ошибки update-сервера не должны вызывать fatal error.

## UI rules

Update UI в будущем должен быть в основном manager, не отдельным пунктом меню WordPress.

Видимые строки update UI должны быть только на русском языке.

Английские пользовательские UI-строки запрещены.

Технические термины API, REST, HTML, iframe, URL, JSON, CSS, JS, PHP, WordPress, shortcode, nonce, slug допустимы только когда они технически необходимы.

## Будущие updater-этапы

- Этап 1.UPDATER.0 — документация updater-контракта.
- Этап 1.UPDATER.1 — update metadata/status foundation.
- Этап 1.UPDATER.2 — private update check client.
- Этап 1.UPDATER.3 — package URL + checksum verification.
- Этап 1.UPDATER.4 — signed manifest.
- Этап 1.UPDATER.5 — license/update integration.
- Этап 1.UPDATER.6 — UI в “Главная” без отдельного меню.

## Repository And Marketplace Contract

`updates.dobromap.ru` — технический приватный репозиторий, сервер обновлений, лицензий и пакетов. Это не обязательная публичная витрина на первом этапе.

Витрина расширений в будущей реализации находится внутри SONYRA Site Manager в разделе “Модули”.

Сервер обновлений может отдавать:

- catalog;
- manifest;
- version;
- changelog;
- license status;
- package URL;
- checksum;
- signature;
- compatibility;
- dependencies;
- minimum SONYRA Site Manager version;
- minimum WordPress version;
- minimum PHP version.

Расширение скачивается как zip. Установка и обновление должны идти через штатную механику WordPress installer/upgrader.

Zip пакета не должен содержать `node_modules`, полный upstream dump, temp files, OS junk или скрытые служебные директории. В будущей реализации пакет должен проходить checksum/signature validation до установки.

В версии `0.1.65` install/update runtime, marketplace UI и billing runtime не реализуются.

Начиная с версии `0.1.67`, Manager Notice Center может принимать будущие repository notices, update notices и promo banners по отдельному безопасному контракту. В `0.1.67` runtime-запросы к `updates.dobromap.ru`, install/update actions и fake promo banners не реализуются.

Future repository notice должен быть безопасным notice object:

- `source`: `repository`;
- `type`: `info`, `warning` или `promo`;
- `severity`: `low`, `medium` или `high` по фактическому сигналу;
- `extension_slug`, если notice относится к расширению;
- action только в существующий manager route или documented future action.

Repository notice не должен запускать установку, покупку, license refresh или external request внутри Notice Center.

## Manifest model

Будущий manifest model должен содержать:

- product
- slug
- current_version
- latest_version
- minimum_wp_version
- minimum_php_version
- package_url
- package_sha256
- signature
- released_at
- changelog
- requires_license
- license_status
- tested_wp_version

## Будущие проверки

Будущий updater-этап должен проверять:

- endpoint доступен;
- JSON валиден;
- slug совпадает с sonyra-site-manager;
- версия новее текущей;
- package_url принадлежит разрешённому домену;
- package_sha256 совпадает;
- signature валидна, когда signed manifest будет включён;
- ошибки не ломают сайт;
- при недоступности update-сервера сайт продолжает работать.

## STOP-условия

Немедленно остановиться, если updater-этап требует:

- обход checksum verification;
- обход signed manifest plan;
- обход license/update integration plan;
- unsafe package URL;
- update install без validation;
- отдельный пункт меню WordPress для update UI;
- английские видимые пользовательские UI-строки;
- ослабление ручного zip workflow через ./dist/.
