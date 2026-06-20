(function () {
	'use strict';

	var STORAGE_KEY = 'sonyra_manager_sidebar_state';
	var MOBILE_QUERY = '(max-width: 780px)';
	var shell = document.querySelector('[data-manager-shell]');

	if (!shell) {
		return;
	}

	var toggleButton = shell.querySelector('[data-manager-sidebar-toggle]');
	var logoButton = shell.querySelector('[data-manager-logo-toggle]');
	var sidebar = shell.querySelector('.sonyra-manager-sidebar');
	var backdrop = shell.querySelector('[data-manager-backdrop]');
	var routeButtons = Array.prototype.slice.call(shell.querySelectorAll('[data-manager-route]'));
	var views = Array.prototype.slice.call(shell.querySelectorAll('[data-manager-view]'));
	var pageKicker = shell.querySelector('[data-manager-kicker]');
	var topbarTitle = shell.querySelector('[data-manager-title]');
	var pageDescription = shell.querySelector('[data-manager-description]');
	var pageHeaderIcon = shell.querySelector('[data-manager-header-icon]');
	var pageBadges = shell.querySelector('[data-manager-badges]');
	var headerActionGroups = Array.prototype.slice.call(shell.querySelectorAll('[data-manager-header-actions]'));
	var activeRoute = 'dashboard';
	var logoutButton = shell.querySelector('[data-manager-logout]');
	var logoutError = shell.querySelector('[data-manager-logout-error]');
	var noticeCenter = shell.querySelector('[data-manager-notice-center]');
	var supportModal = shell.querySelector('[data-manager-notice-support-modal]');
	var mediaQuery = window.matchMedia ? window.matchMedia(MOBILE_QUERY) : null;
	var sidebarMotionTimer = 0;
	var noticeTimers = {};
	var autoLockTimer = 0;
	var sessionRedirecting = false;
	var lastActivityAt = Date.now();
	var lastSessionCheckAt = 0;
	var managerDestructiveModalState = null;
	var managerDestructiveModalRoot = null;

	function isMobile() {
		return mediaQuery ? mediaQuery.matches : window.innerWidth <= 780;
	}

	function getStoredState() {
		try {
			var value = window.localStorage.getItem(STORAGE_KEY);
			return value === 'collapsed' || value === 'expanded' ? value : '';
		} catch (error) {
			return '';
		}
	}

	function storeState(state) {
		try {
			window.localStorage.setItem(STORAGE_KEY, state);
		} catch (error) {}
	}

	function clearSidebarMotionTimer() {
		if (sidebarMotionTimer) {
			window.clearTimeout(sidebarMotionTimer);
			sidebarMotionTimer = 0;
		}
	}

	function finishSidebarMotion() {
		clearSidebarMotionTimer();
		shell.removeAttribute('data-sidebar-motion');
	}

	function beginSidebarMotion() {
		clearSidebarMotionTimer();
		shell.setAttribute('data-sidebar-motion', 'active');
		sidebarMotionTimer = window.setTimeout(finishSidebarMotion, 340);
	}

	function setSidebarState(state, shouldStore, shouldAnimate) {
		var nextState = state === 'collapsed' ? 'collapsed' : 'expanded';
		var currentState = shell.getAttribute('data-sidebar-state') || '';
		var shouldRunMotion = shouldAnimate === true && currentState && currentState !== nextState;
		var expanded = nextState === 'expanded';

		if (shouldRunMotion) {
			beginSidebarMotion();
		}

		document.documentElement.classList.toggle('sonyra-sidebar-collapsed', nextState === 'collapsed');
		shell.setAttribute('data-sidebar-state', nextState);
		shell.classList.toggle('sonyra-manager-mobile-open', expanded && isMobile());

		if (toggleButton) {
			toggleButton.setAttribute('aria-expanded', expanded ? 'true' : 'false');
			toggleButton.setAttribute('aria-label', expanded ? (toggleButton.dataset.labelExpanded || '') : (toggleButton.dataset.labelCollapsed || ''));
		}

		if (logoButton) {
			logoButton.setAttribute('aria-expanded', expanded ? 'true' : 'false');
			logoButton.setAttribute('aria-label', expanded ? (logoButton.dataset.labelExpanded || '') : (logoButton.dataset.labelCollapsed || ''));
		}

		if (backdrop) {
			backdrop.hidden = !(expanded && isMobile());
		}

		if (shouldStore) {
			storeState(nextState);
		}
	}

	function handleSidebarTransitionEnd(event) {
		if (!shell.hasAttribute('data-sidebar-motion')) {
			return;
		}

		if (
			(event.target === shell && event.propertyName === 'grid-template-columns') ||
			(event.target === sidebar && event.propertyName === 'width')
		) {
			finishSidebarMotion();
		}
	}

	function getRouteFromHash() {
		var hash = window.location.hash || '';
		var route = hash.replace(/^#\/?/, '').replace(/^\//, '');
		return route || 'dashboard';
	}

	function normalizeRoute(route) {
		var found = views.some(function (view) {
			return view.getAttribute('data-manager-view') === route;
		});

		return found ? route : 'dashboard';
	}

	function findTemplate(selector, route) {
		return shell.querySelector(selector + '="' + route + '"]');
	}

	function clearNode(node) {
		while (node.firstChild) {
			node.removeChild(node.firstChild);
		}
	}

	function updateHeaderIcon(route) {
		var iconTemplate = findTemplate('[data-manager-icon-template', route);

		if (pageHeaderIcon && iconTemplate && iconTemplate.firstElementChild) {
			clearNode(pageHeaderIcon);
			pageHeaderIcon.appendChild(iconTemplate.firstElementChild.cloneNode(true));
		}
	}

	function updateHeaderBadges(route) {
		var badgeTemplate = findTemplate('[data-manager-badge-template', route);

		if (pageBadges && badgeTemplate && badgeTemplate.firstElementChild) {
			pageBadges.parentNode.replaceChild(badgeTemplate.firstElementChild.cloneNode(true), pageBadges);
			pageBadges = shell.querySelector('[data-manager-badges]');
		}
	}

	function updateHeaderActions(route) {
		headerActionGroups.forEach(function (group) {
			group.hidden = group.getAttribute('data-manager-header-actions') !== route;
		});
	}

	function setRoute(route, shouldUpdateHash) {
		var nextRoute = normalizeRoute(route);
		var activeButton = null;
		activeRoute = nextRoute;

		views.forEach(function (view) {
			view.hidden = view.getAttribute('data-manager-view') !== nextRoute;
		});

		routeButtons.forEach(function (button) {
			var isActive = button.getAttribute('data-manager-route') === nextRoute;
			button.classList.toggle('sonyra-manager-nav-item-active', isActive);

			if (isActive) {
				button.setAttribute('aria-current', 'page');
				activeButton = button;
			} else {
				button.removeAttribute('aria-current');
			}
		});

		if (activeButton) {
			if (pageKicker) {
				pageKicker.textContent = activeButton.dataset.kicker || '';
			}

			if (topbarTitle) {
				topbarTitle.textContent = activeButton.dataset.title || activeButton.textContent || '';
			}

			if (pageDescription) {
				pageDescription.textContent = activeButton.dataset.description || '';
			}

			updateHeaderIcon(nextRoute);
			updateHeaderBadges(nextRoute);
			updateHeaderActions(nextRoute);
		}

		if (shouldUpdateHash && window.location.hash !== '#/' + nextRoute) {
			window.location.hash = '#/' + nextRoute;
		}

		if (isMobile()) {
			setSidebarState('collapsed', false, true);
		}

		if (SonyraPagesSection && typeof SonyraPagesSection.handleRouteChange === 'function') {
			SonyraPagesSection.handleRouteChange(nextRoute);
		}

		if (window.SonyraColorController && typeof window.SonyraColorController.handleRouteChange === 'function') {
			window.SonyraColorController.handleRouteChange(nextRoute);
		}

		shell.removeAttribute('data-manager-booting');
	}

	function showLogoutError() {
		if (noticeCenter) {
			noticeCenter.hidden = false;
		}

		if (logoutError) {
			logoutError.hidden = false;
		}
	}

	function closeSupportModal() {
		if (supportModal) {
			supportModal.hidden = true;
		}
	}

	function openSupportModal() {
		if (supportModal) {
			supportModal.hidden = false;
			var closeButton = supportModal.querySelector('[data-manager-notice-modal-close]');

			if (closeButton) {
				closeButton.focus();
			}
		}
	}

	function clearNoticeTimer(targetId) {
		if (!targetId || !noticeTimers[targetId]) {
			return;
		}

		window.clearTimeout(noticeTimers[targetId]);
		delete noticeTimers[targetId];
	}

	function getNoticeAutoDismissDelay(type) {
		if (type === 'success') {
			return 4000;
		}

		if (type === 'info') {
			return 6000;
		}

		return 0;
	}

	function dismissNotice(targetId) {
		if (!targetId) {
			return;
		}

		clearNoticeTimer(targetId);
		var notice = shell.querySelector('[data-manager-notice-id="' + targetId + '"]');

		if (notice) {
			notice.classList.add('is-dismissing');
			window.setTimeout(function () {
				notice.hidden = true;
				notice.classList.remove('is-dismissing');

				if (noticeCenter && !noticeCenter.querySelector('.sonyra-manager-notice-card:not([hidden])')) {
					noticeCenter.hidden = true;
				}
			}, 180);
			return;
		}

		if (noticeCenter && !noticeCenter.querySelector('.sonyra-manager-notice-card:not([hidden])')) {
			noticeCenter.hidden = true;
		}
	}

	function scheduleNoticeAutoDismiss(notice) {
		if (!notice) {
			return;
		}

		var targetId = notice.getAttribute('data-manager-notice-id') || '';
		var delay = getNoticeAutoDismissDelay(notice.getAttribute('data-manager-notice-type') || '');

		clearNoticeTimer(targetId);

		if (!targetId || delay <= 0) {
			return;
		}

		noticeTimers[targetId] = window.setTimeout(function () {
			dismissNotice(targetId);
		}, delay);
	}

	function initRenderedNotices() {
		if (!noticeCenter) {
			return;
		}

		Array.prototype.slice.call(noticeCenter.querySelectorAll('[data-manager-notice-id]')).forEach(function (notice) {
			scheduleNoticeAutoDismiss(notice);
		});
	}

	function handleNoticeAction(event) {
		var actionButton = event.target.closest('[data-manager-notice-action], [data-manager-notice-modal-close]');

		if (!actionButton || !shell.contains(actionButton) && (!supportModal || !supportModal.contains(actionButton))) {
			return;
		}

		if (actionButton.disabled) {
			return;
		}

		if (actionButton.hasAttribute('data-manager-notice-modal-close')) {
			event.preventDefault();
			closeSupportModal();
			return;
		}

		var action = actionButton.getAttribute('data-manager-notice-action') || '';

		if (action === 'reload') {
			event.preventDefault();
			window.location.reload();
			return;
		}

		if (action === 'dismiss') {
			event.preventDefault();
			dismissNotice(actionButton.getAttribute('data-manager-notice-target') || '');
			return;
		}

		if (action === 'open_modal') {
			event.preventDefault();
			openSupportModal();
		}
	}

	function getIdleTimeoutMs() {
		var timeoutSeconds = parseInt(shell.getAttribute('data-idle-timeout-seconds') || '', 10);

		if (!Number.isFinite(timeoutSeconds) || timeoutSeconds <= 0) {
			timeoutSeconds = 900;
		}

		return timeoutSeconds * 1000;
	}

	function buildLoginRedirectUrl(reason) {
		var loginUrl = shell.getAttribute('data-login-url') || '/manager/login';
		var fallbackUrl = '/manager/login';
		var normalizedReason = reason === 'session_locked' ? 'session_locked' : '';
		var parsedUrl;

		try {
			parsedUrl = new URL(loginUrl, window.location.origin);

			if (parsedUrl.origin !== window.location.origin) {
				parsedUrl = new URL(fallbackUrl, window.location.origin);
			}
		} catch (error) {
			parsedUrl = new URL(fallbackUrl, window.location.origin);
		}

		if (normalizedReason) {
			parsedUrl.searchParams.set('sonyra_login_notice', normalizedReason);
		}

		return parsedUrl.href;
	}

	function clearAutoLockTimer() {
		if (!autoLockTimer) {
			return;
		}

		window.clearTimeout(autoLockTimer);
		autoLockTimer = 0;
	}

	function scheduleAutoLockTimer() {
		clearAutoLockTimer();

		if (sessionRedirecting) {
			return;
		}

		autoLockTimer = window.setTimeout(function () {
			redirectToLogin('session_locked');
		}, getIdleTimeoutMs());
	}

	function recordActivity(forceUpdate) {
		var now = Date.now();

		if (forceUpdate !== true && now - lastActivityAt < 1000) {
			return;
		}

		lastActivityAt = now;
		scheduleAutoLockTimer();
	}

	function verifySessionState(forceCheck) {
		var sessionUrl = shell.getAttribute('data-session-url') || '';
		var now = Date.now();

		if (!sessionUrl || sessionRedirecting) {
			return;
		}

		if (forceCheck !== true && now - lastSessionCheckAt < 5000) {
			return;
		}

		lastSessionCheckAt = now;

		fetch(sessionUrl, {
			method: 'GET',
			credentials: 'same-origin',
			headers: {
				'X-WP-Nonce': shell.getAttribute('data-nonce') || ''
			}
		}).then(function (response) {
			return response.json().catch(function () {
				return {};
			}).then(function (data) {
				return {
					ok: response.ok,
					data: data || {}
				};
			});
		}).then(function (result) {
			if (!result.ok || !result.data || result.data.authenticated !== true || result.data.access_granted !== true) {
				redirectToLogin('session_locked');
			}
		}).catch(function () {});
	}

	function redirectToLogin(reason) {
		if (sessionRedirecting) {
			return;
		}

		sessionRedirecting = true;
		clearAutoLockTimer();
		window.location.assign(buildLoginRedirectUrl(reason));
	}

	function ensureManagerDestructiveModalRoot() {
		if (managerDestructiveModalRoot && document.body.contains(managerDestructiveModalRoot)) {
			return managerDestructiveModalRoot;
		}

		managerDestructiveModalRoot = document.createElement('div');
		managerDestructiveModalRoot.setAttribute('data-manager-destructive-modal-root', 'true');
		document.body.appendChild(managerDestructiveModalRoot);
		return managerDestructiveModalRoot;
	}

	function hasAnyManagerModalOpen() {
		return !!document.querySelector('[data-pages-modal-overlay], [data-color-modal-overlay], [data-manager-destructive-modal-overlay]');
	}

	function syncManagerModalBodyState() {
		document.body.classList.toggle('sonyra-manager-modal-open', hasAnyManagerModalOpen());
	}

	function closeManagerDestructiveModal(options) {
		var shouldRestoreFocus = !options || options.restoreFocus !== false;
		var modalState = managerDestructiveModalState;

		managerDestructiveModalState = null;

		if (managerDestructiveModalRoot) {
			clearNode(managerDestructiveModalRoot);
		}

		syncManagerModalBodyState();

		if (modalState && typeof modalState.onClose === 'function') {
			modalState.onClose();
		}

		if (shouldRestoreFocus && modalState && modalState.trigger && typeof modalState.trigger.focus === 'function') {
			modalState.trigger.focus();
		}
	}

	function renderManagerDestructiveModal() {
		var rootNode = ensureManagerDestructiveModalRoot();
		var overlay;
		var panel;
		var header;
		var icon;
		var copy;
		var title;
		var description;
		var closeButton;
		var body;
		var footer;
		var cancelButton;
		var confirmButton;
		var errorText = '';

		clearNode(rootNode);

		if (!managerDestructiveModalState) {
			syncManagerModalBodyState();
			return;
		}

		document.body.classList.add('sonyra-manager-modal-open');

		overlay = createTextNode('div', 'sonyra-manager-pages-modal-overlay sonyra-manager-modal-overlay');
		panel = createTextNode('div', 'sonyra-manager-pages-modal sonyra-manager-modal sonyra-manager-modal--destructive');
		header = createTextNode('header', 'sonyra-manager-pages-modal-header sonyra-manager-modal__header');
		icon = createTextNode('div', 'sonyra-manager-pages-modal-icon');
		copy = createTextNode('div', 'sonyra-manager-pages-modal-copy');
		title = createTextNode('h3', 'sonyra-manager-pages-modal-title', managerDestructiveModalState.title || '');
		description = createTextNode('p', 'sonyra-manager-pages-modal-description', managerDestructiveModalState.subtitle || '');
		closeButton = createButton('sonyra-manager-pages-modal-close', '', 'x');
		body = createTextNode('div', 'sonyra-manager-pages-modal-body sonyra-manager-modal__body');
		footer = createTextNode('footer', 'sonyra-manager-pages-modal-footer sonyra-manager-modal__footer');
		cancelButton = createButton('sonyra-manager-pages-secondary', managerDestructiveModalState.cancelLabel || '');
		confirmButton = createButton(
			'sonyra-manager-pages-primary sonyra-manager-pages-primary-danger',
			managerDestructiveModalState.loading ? (managerDestructiveModalState.loadingLabel || managerDestructiveModalState.confirmLabel || '') : (managerDestructiveModalState.confirmLabel || '')
		);

		overlay.setAttribute('data-manager-destructive-modal-overlay', 'true');
		panel.setAttribute('role', 'dialog');
		panel.setAttribute('aria-modal', 'true');
		panel.setAttribute('aria-labelledby', 'sonyra-manager-destructive-modal-title');
		title.id = 'sonyra-manager-destructive-modal-title';
		closeButton.setAttribute('aria-label', managerDestructiveModalState.closeLabel || '');
		closeButton.setAttribute('data-manager-destructive-close', 'true');
		cancelButton.setAttribute('data-manager-destructive-cancel', 'true');
		confirmButton.setAttribute('data-manager-destructive-confirm', 'true');
		confirmButton.disabled = managerDestructiveModalState.loading === true;
		cancelButton.disabled = managerDestructiveModalState.loading === true;
		closeButton.disabled = managerDestructiveModalState.loading === true;

		icon.appendChild(clonePagesIcon('alert-triangle'));
		copy.appendChild(title);
		copy.appendChild(description);
		header.appendChild(icon);
		header.appendChild(copy);
		header.appendChild(closeButton);

		if (managerDestructiveModalState.bodyText) {
			body.appendChild(createTextNode('p', 'sonyra-manager-pages-modal-danger-text', managerDestructiveModalState.bodyText));
		}

		if (managerDestructiveModalState.warningText) {
			body.appendChild(createTextNode('p', 'sonyra-manager-modal__danger-note', managerDestructiveModalState.warningText));
		}

		if (managerDestructiveModalState.errorText) {
			errorText = String(managerDestructiveModalState.errorText);
			body.appendChild(createTextNode('p', 'sonyra-manager-modal__danger-note sonyra-manager-modal__danger-note-error', errorText));
		}

		footer.appendChild(cancelButton);
		footer.appendChild(confirmButton);
		panel.appendChild(header);
		panel.appendChild(body);
		panel.appendChild(footer);
		overlay.appendChild(panel);
		rootNode.appendChild(overlay);
	}

	function openManagerDestructiveModal(config) {
		var modalConfig = config && typeof config === 'object' ? config : {};

		managerDestructiveModalState = {
			entityType: modalConfig.entityType || '',
			entityName: modalConfig.entityName || '',
			title: modalConfig.title || '',
			subtitle: modalConfig.subtitle || '',
			bodyText: modalConfig.bodyText || '',
			warningText: modalConfig.warningText || '',
			confirmLabel: modalConfig.confirmLabel || '',
			cancelLabel: modalConfig.cancelLabel || '',
			closeLabel: modalConfig.closeLabel || '',
			loadingLabel: modalConfig.loadingLabel || '',
			errorText: '',
			errorFallbackText: modalConfig.errorFallbackText || '',
			loading: false,
			trigger: modalConfig.trigger || null,
			onConfirm: typeof modalConfig.onConfirm === 'function' ? modalConfig.onConfirm : null,
			onClose: typeof modalConfig.onClose === 'function' ? modalConfig.onClose : null
		};

		renderManagerDestructiveModal();
	}

	function handleManagerDestructiveModalClick(event) {
		var closeAction = event.target.closest('[data-manager-destructive-close], [data-manager-destructive-cancel]');
		var confirmAction = event.target.closest('[data-manager-destructive-confirm]');
		var overlay = event.target.closest('[data-manager-destructive-modal-overlay]');
		var result;

		if (!managerDestructiveModalState) {
			return;
		}

		if (overlay && event.target === overlay && managerDestructiveModalState.loading !== true) {
			closeManagerDestructiveModal();
			return;
		}

		if (closeAction) {
			event.preventDefault();

			if (managerDestructiveModalState.loading === true) {
				return;
			}

			closeManagerDestructiveModal();
			return;
		}

		if (!confirmAction || managerDestructiveModalState.loading === true) {
			return;
		}

		event.preventDefault();

		if (typeof managerDestructiveModalState.onConfirm !== 'function') {
			closeManagerDestructiveModal();
			return;
		}

		managerDestructiveModalState.loading = true;
		managerDestructiveModalState.errorText = '';
		renderManagerDestructiveModal();

		try {
			result = managerDestructiveModalState.onConfirm();
		} catch (error) {
			managerDestructiveModalState.loading = false;
			managerDestructiveModalState.errorText = error && error.message ? String(error.message) : managerDestructiveModalState.errorFallbackText || '';
			renderManagerDestructiveModal();
			return;
		}

		Promise.resolve(result).then(function () {
			closeManagerDestructiveModal({ restoreFocus: false });
		}).catch(function (error) {
			if (!managerDestructiveModalState) {
				return;
			}

			managerDestructiveModalState.loading = false;
			managerDestructiveModalState.errorText = error && error.message ? String(error.message) : managerDestructiveModalState.errorFallbackText || '';
			renderManagerDestructiveModal();
		});
	}

	window.openManagerDestructiveModal = openManagerDestructiveModal;
	window.closeManagerDestructiveModal = closeManagerDestructiveModal;
	window.isManagerDestructiveModalOpen = function () {
		return managerDestructiveModalState !== null;
	};
	if (window.SonyraManagerUI) {
		window.SonyraManagerUI.openManagerDestructiveModal = openManagerDestructiveModal;
	}

	document.addEventListener('click', handleManagerDestructiveModalClick);

	function runLogout() {
		var logoutUrl = shell.getAttribute('data-logout-url') || '';

		if (!logoutUrl || logoutUrl.indexOf('://') === -1 && logoutUrl.charAt(0) !== '/') {
			showLogoutError();
			return;
		}

		if (logoutButton) {
			logoutButton.disabled = true;
		}

		fetch(logoutUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': shell.getAttribute('data-nonce') || ''
			},
			body: '{}'
		}).then(function (response) {
			if (!response.ok) {
				throw new Error('logout_failed');
			}

			return response.json().catch(function () {
				return {};
			});
		}).then(function () {
			redirectToLogin();
		}).catch(function () {
			showLogoutError();

			if (logoutButton) {
				logoutButton.disabled = false;
			}
		});
	}

	var SonyraPagesSection;
	var pagesRoot = shell.querySelector('[data-manager-pages-root]');
	var pagesState = {
		items: [],
		pagesStatus: 'loading',
		activeId: '',
		activeSectionId: '',
		activeBlockId: '',
		activeWidgetId: '',
		activePopupId: '',
		saving: false,
		loaded: false,
		mode: 'table',
		searchQuery: '',
		statusFilter: 'all',
		sort: 'recent',
		openDropdownId: '',
		openAddMenu: '',
		pageModalOpen: false,
		sectionLibraryModalOpen: false,
		blockLibraryModalOpen: false,
		widgetLibraryModalOpen: false,
		popupLibraryModalOpen: false,
		sectionModalOpen: false,
		blockModalOpen: false,
		widgetModalOpen: false,
		popupModalOpen: false,
		historyModalOpen: false,
		deleteModalOpen: false,
		modalDraft: null,
		sectionDraft: null,
		blockDraft: null,
		widgetDraft: null,
		popupDraft: null,
		historyTargetId: '',
		deleteTargetType: '',
		deleteTargetId: '',
		deleteTargetParentId: '',
		openHelpTooltip: '',
		openHelpTooltipPinned: false,
		openHelpTooltipTrigger: '',
		addingSection: false,
		addingBlock: false,
		addingWidget: false,
		addingPopup: false,
		pendingSectionType: '',
		pendingBlockType: '',
		pendingWidgetType: '',
		pendingPopupType: '',
		dragSectionId: '',
		dragBlockId: '',
		dragWidgetId: '',
		dragPopupId: '',
		lastTrigger: null,
		pendingPageId: '',
		pendingAction: ''
	};
	var pagesI18n = {};
	var currentLocale = 'ru_RU';
	var fallbackLocale = 'ru_RU';
	var pagesMessages = {
		destructiveModalSubtitle: ''
	};
	var helpEntries = {};
	var sectionDefinitions = [];
	var blockDefinitions = [];
	var widgetDefinitions = [];
	var popupDefinitions = [];
	var pagesMain = shell.querySelector('.sonyra-manager-main');
	var browserTimeZone = '';

	function initPagesI18n() {
		var node = document.getElementById('sonyra-manager-pages-i18n');

		if (!node) {
			return;
		}

		try {
			var payload = JSON.parse(node.textContent || '{}') || {};
			if (payload && typeof payload === 'object' && payload.dictionary) {
				currentLocale = String(payload.currentLocale || 'ru_RU');
				fallbackLocale = String(payload.fallbackLocale || currentLocale || 'ru_RU');
				pagesI18n = payload.dictionary && typeof payload.dictionary === 'object' ? payload.dictionary : {};
				pagesMessages = payload.messages && typeof payload.messages === 'object' ? payload.messages : { destructiveModalSubtitle: '' };
			} else {
				pagesI18n = payload && typeof payload === 'object' ? payload : {};
				pagesMessages = { destructiveModalSubtitle: '' };
			}
		} catch (error) {
			pagesI18n = {};
			currentLocale = 'ru_RU';
			fallbackLocale = 'ru_RU';
			pagesMessages = { destructiveModalSubtitle: '' };
		}
	}

	function t(key) {
		return pagesI18n[key] || '';
	}

	function getDestructiveModalSubtitleText() {
		return String(pagesMessages && pagesMessages.destructiveModalSubtitle ? pagesMessages.destructiveModalSubtitle : '');
	}

	function initSectionDefinitions() {
		var node = document.getElementById('sonyra-manager-pages-sections-library');

		if (!node) {
			return;
		}

		try {
			sectionDefinitions = (JSON.parse(node.textContent || '[]') || []).map(normalizeSectionDefinition).filter(function (definition) {
				return Boolean(definition.type);
			});
		} catch (error) {
			sectionDefinitions = [];
		}
	}

	function initBlockDefinitions() {
		var node = document.getElementById('sonyra-manager-pages-blocks-library');

		if (!node) {
			return;
		}

		try {
			blockDefinitions = (JSON.parse(node.textContent || '[]') || []).map(normalizeBlockDefinition).filter(function (definition) {
				return Boolean(definition.type);
			});
		} catch (error) {
			blockDefinitions = [];
		}
	}

	function initWidgetDefinitions() {
		var node = document.getElementById('sonyra-manager-pages-widgets-library');

		if (!node) {
			return;
		}

		try {
			widgetDefinitions = (JSON.parse(node.textContent || '[]') || []).map(normalizeWidgetDefinition).filter(function (definition) {
				return Boolean(definition.type);
			});
		} catch (error) {
			widgetDefinitions = [];
		}
	}

	function initPopupDefinitions() {
		var node = document.getElementById('sonyra-manager-pages-popups-library');

		if (!node) {
			return;
		}

		try {
			popupDefinitions = (JSON.parse(node.textContent || '[]') || []).map(normalizePopupDefinition).filter(function (definition) {
				return Boolean(definition.type);
			});
		} catch (error) {
			popupDefinitions = [];
		}
	}

	function initHelpEntries() {
		var node = document.getElementById('sonyra-manager-help-entries');

		if (!node) {
			return;
		}

		try {
			(JSON.parse(node.textContent || '[]') || []).forEach(function (entry) {
				if (entry && entry.key) {
					helpEntries[String(entry.key)] = entry;
				}
			});
		} catch (error) {
			helpEntries = {};
		}
	}

	function getHelpEntry(key) {
		return helpEntries[String(key || '')] || null;
	}

	function normalizeSectionDefinition(definition) {
		var normalized = definition && typeof definition === 'object' ? definition : {};

		if (!normalized.type || !normalized.label || !normalized.description || !normalized.icon_key || !normalized.category) {
			return {};
		}

		return {
			type: normalized.type || '',
			label: normalized.label || '',
			description: normalized.description || '',
			label_key: normalized.label_key || '',
			description_key: normalized.description_key || '',
			icon_key: normalized.icon_key || 'circle-dot',
			category: normalized.category || 'content',
			provider: normalized.provider || 'core',
			module_key: normalized.module_key || '',
			status: normalized.status || 'implemented',
			default_section: normalized.default_section && typeof normalized.default_section === 'object' ? normalized.default_section : {},
			default_blocks: Array.isArray(normalized.default_blocks) ? normalized.default_blocks : [],
			fields: Array.isArray(normalized.fields) ? normalized.fields : [],
			default_block_name_key: normalized.default_block_name_key || ''
		};
	}

	function normalizeBlockDefinition(definition) {
		var normalized = definition && typeof definition === 'object' ? definition : {};

		if (!normalized.type || !normalized.label || !normalized.description || !normalized.label_key || !normalized.description_key) {
			return {};
		}

		return {
			type: normalized.type || '',
			label: normalized.label || '',
			description: normalized.description || '',
			label_key: normalized.label_key || '',
			description_key: normalized.description_key || '',
			icon: normalized.icon || normalized.icon_key || 'circle-dot',
			icon_key: normalized.icon_key || normalized.icon || 'circle-dot',
			category: normalized.category || 'content',
			source: normalized.source || 'core',
			module_key: normalized.module_key || '',
			enabled: normalized.enabled !== false,
			fields: Array.isArray(normalized.fields) ? normalized.fields : [],
			defaults: normalized.defaults && typeof normalized.defaults === 'object' ? normalized.defaults : {}
		};
	}

	function normalizeWidgetDefinition(definition) {
		var normalized = definition && typeof definition === 'object' ? definition : {};

		if (!normalized.type || !normalized.label || !normalized.description || !normalized.label_key || !normalized.description_key) {
			return {};
		}

		return {
			type: normalized.type || '',
			label: normalized.label || '',
			description: normalized.description || '',
			label_key: normalized.label_key || '',
			description_key: normalized.description_key || '',
			icon: normalized.icon || normalized.icon_key || 'circle-dot',
			icon_key: normalized.icon_key || normalized.icon || 'circle-dot',
			category: normalized.category || 'layout',
			source: normalized.source || 'core',
			module_key: normalized.module_key || '',
			integration_mode: normalized.integration_mode || 'core',
			enabled: normalized.enabled !== false,
			fields: Array.isArray(normalized.fields) ? normalized.fields : [],
			defaults: normalized.defaults && typeof normalized.defaults === 'object' ? normalized.defaults : {}
		};
	}

	function normalizePopupDefinition(definition) {
		var normalized = definition && typeof definition === 'object' ? definition : {};

		if (!normalized.type || !normalized.label || !normalized.description || !normalized.label_key || !normalized.description_key) {
			return {};
		}

		return {
			type: normalized.type || '',
			label: normalized.label || '',
			description: normalized.description || '',
			label_key: normalized.label_key || '',
			description_key: normalized.description_key || '',
			icon: normalized.icon || normalized.icon_key || 'circle-dot',
			icon_key: normalized.icon_key || normalized.icon || 'circle-dot',
			category: normalized.category || 'content',
			source: normalized.source || 'core',
			module_key: normalized.module_key || '',
			integration_mode: normalized.integration_mode || 'core',
			enabled: normalized.enabled !== false,
			fields: Array.isArray(normalized.fields) ? normalized.fields : [],
			defaults: normalized.defaults && typeof normalized.defaults === 'object' ? normalized.defaults : {}
		};
	}

	function getSectionDefinitions() {
		return sectionDefinitions.slice();
	}

	function getSectionDefinition(type) {
		return getSectionDefinitions().find(function (definition) {
			return definition.type === type;
		}) || null;
	}

	function getSectionCategoryLabel(category) {
		return t('manager.page_elements.categories.' + String(category || 'content')) || t('manager.pages.library.category.' + String(category || 'content')) || '';
	}

	function getBlockDefinitions() {
		return blockDefinitions.filter(function (definition) {
			return definition.enabled !== false;
		}).slice();
	}

	function getWidgetDefinitions() {
		return widgetDefinitions.filter(function (definition) {
			return definition.enabled !== false;
		}).slice();
	}

	function getWidgetDefinition(type) {
		return getWidgetDefinitions().find(function (definition) {
			return definition.type === type;
		}) || null;
	}

	function getPopupDefinitions() {
		return popupDefinitions.filter(function (definition) {
			return definition.enabled !== false;
		}).slice();
	}

	function getPopupDefinition(type) {
		return getPopupDefinitions().find(function (definition) {
			return definition.type === type;
		}) || null;
	}

	function getBlockDefinition(type) {
		return getBlockDefinitions().find(function (definition) {
			return definition.type === type;
		}) || null;
	}

	function initPagesLocale() {
		try {
			browserTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
		} catch (error) {
			browserTimeZone = '';
		}
	}

	function createId(prefix) {
		return (prefix || 'id') + '-' + String(Date.now()) + '-' + String(Math.floor(Math.random() * 100000));
	}

	function normalizePage(page) {
		var normalized = page && typeof page === 'object' ? page : {};

		return {
			id: normalized.id || createId('page'),
			title: normalized.title || '',
			slug: normalized.slug || '',
			status: normalized.status || 'draft',
			is_home: normalized.is_home === true,
			show_in_menu: normalized.show_in_menu === true,
			menu_title: normalized.menu_title || '',
			menu_order: Number(normalized.menu_order || 10),
			seo_title: normalized.seo_title || '',
			seo_description: normalized.seo_description || '',
			sections: ensureUniqueTitles(Array.isArray(normalized.sections) ? normalized.sections.map(normalizeSection) : [], function (section) {
				return section.name || getDefaultSectionTitle(section.type);
			}),
			widgets: ensureUniqueTitles(Array.isArray(normalized.widgets) ? normalized.widgets.map(normalizeWidget) : [], function (widget) {
				return widget.name || getDefaultWidgetTitle(widget.type);
			}),
			popups: ensureUniqueTitles(Array.isArray(normalized.popups) ? normalized.popups.map(normalizePopup) : [], function (popup) {
				return popup.name || getDefaultPopupTitle(popup.type);
			}),
			history: Array.isArray(normalized.history) ? normalized.history.map(normalizeHistoryEvent) : [],
			created_at: normalized.created_at || '',
			updated_at: normalized.updated_at || '',
			public_url: normalized.public_url || '',
			preview_url: normalized.preview_url || '',
			can_open: normalized.can_open === true
		};
	}

	function normalizeHistoryEvent(event) {
		var normalized = event && typeof event === 'object' ? event : {};

		return {
			at: normalized.at || new Date().toISOString(),
			action: normalized.action || '',
			label: normalized.label || '',
			details: normalized.details || ''
		};
	}

	function normalizeSection(section) {
		var normalized = section && typeof section === 'object' ? section : {};
		var definition = getSectionDefinition(normalized.type || '');
		var type = normalized.type || 'text';
		var blocks = Array.isArray(normalized.blocks) ? normalized.blocks.map(function (block) {
			return normalizeBlock(block, type);
		}) : [];
		var defaultSection = definition && definition.default_section && typeof definition.default_section === 'object' ? definition.default_section : {};

		if (!blocks.length && hasLegacySectionContent(normalized)) {
			blocks.push(normalizeBlock({
				type: getDefaultBlockType(type),
				heading: normalized.title || '',
				kicker: normalized.kicker || '',
				text: normalized.text || '',
				description: normalized.text || '',
				button_label: normalized.button_label || '',
				button_url: normalized.button_url || '',
				email: normalized.email || '',
				phone: normalized.phone || '',
				address: normalized.address || ''
			}, type));
		}

		if (!blocks.length && Array.isArray(defaultSection.blocks) && defaultSection.blocks.length) {
			blocks = defaultSection.blocks.map(function (block) {
				return normalizeBlock(block, type);
			});
		}

		return {
			id: normalized.id || createId('section'),
			type: type,
			name: normalized.name || '',
			blocks: ensureUniqueTitles(blocks, function (block) {
				return block.name || getDefaultBlockTitle(block.type);
			}),
			provider: normalized.provider || (defaultSection.provider || 'core'),
			module_key: normalized.module_key || defaultSection.module_key || '',
			unavailable: normalized.unavailable === true || !definition
		};
	}

	function normalizeBlock(block, fallbackType) {
		var normalized = block && typeof block === 'object' ? block : {};
		var originalType = normalized.type || getDefaultBlockType(fallbackType);
		var definition = getBlockDefinition(originalType);
		var nextBlock = {
			id: normalized.id || createId('block'),
			type: originalType,
			name: normalized.name || '',
			kicker: normalized.kicker || '',
			heading: normalized.heading || normalized.title || '',
			text: normalized.text || '',
			description: normalized.description || (originalType === 'contacts' ? (normalized.text || '') : ''),
			button_label: normalized.button_label || '',
			button_url: normalized.button_url || '',
			email: normalized.email || '',
			phone: normalized.phone || '',
			address: normalized.address || '',
			image_url: normalized.image_url || '',
			image_alt: normalized.image_alt || '',
			caption: normalized.caption || '',
			video_url: normalized.video_url || '',
			icon_key: normalized.icon_key || '',
			list_text: normalized.list_text || '',
			value: normalized.value || '',
			metric_label: normalized.metric_label || '',
			step_number: normalized.step_number || '',
			quote: normalized.quote || '',
			author: normalized.author || '',
			role: normalized.role || '',
			name_person: normalized.name_person || '',
			link_url: normalized.link_url || '',
			map_url: normalized.map_url || '',
			question: normalized.question || '',
			answer: normalized.answer || '',
			link_label: normalized.link_label || '',
			file_url: normalized.file_url || '',
			file_label: normalized.file_label || '',
			platform: normalized.platform || '',
			embed_code: normalized.embed_code || '',
			tag_label: normalized.tag_label || '',
			unavailable: normalized.unavailable === true || !definition
		};

		Object.keys(normalized).forEach(function (key) {
			if (!(key in nextBlock)) {
				nextBlock[key] = normalized[key];
			}
		});

		if (definition && definition.defaults) {
			Object.keys(definition.defaults).forEach(function (key) {
				if (nextBlock[key] === undefined || nextBlock[key] === null || nextBlock[key] === '') {
					nextBlock[key] = definition.defaults[key];
				}
			});
		}

		return nextBlock;
	}

	function normalizeWidget(widget) {
		var normalized = widget && typeof widget === 'object' ? widget : {};
		var definition = getWidgetDefinition(normalized.type || '');
		var nextWidget = {
			id: normalized.id || createId('widget'),
			type: normalized.type || '',
			name: normalized.name || '',
			enabled: normalized.enabled !== false,
			position: getWidgetPosition(normalized.position || 'bottom_right'),
			created_at: normalized.created_at || new Date().toISOString(),
			updated_at: normalized.updated_at || new Date().toISOString(),
			label: normalized.label || '',
			url: normalized.url || '',
			phone: normalized.phone || '',
			email: normalized.email || '',
			message: normalized.message || '',
			platform: normalized.platform || '',
			address: normalized.address || '',
			embed_code: normalized.embed_code || '',
			description: normalized.description || '',
			unavailable: normalized.unavailable === true || !definition
		};

		Object.keys(normalized).forEach(function (key) {
			if (!(key in nextWidget)) {
				nextWidget[key] = normalized[key];
			}
		});

		if (definition && definition.defaults) {
			Object.keys(definition.defaults).forEach(function (key) {
				if (nextWidget[key] === undefined || nextWidget[key] === null || nextWidget[key] === '') {
					nextWidget[key] = definition.defaults[key];
				}
			});
		}

		nextWidget.position = getWidgetPosition(nextWidget.position);
		return nextWidget;
	}

	function normalizePopup(popup) {
		var normalized = popup && typeof popup === 'object' ? popup : {};
		var definition = getPopupDefinition(normalized.type || '');
		var nextPopup = {
			id: normalized.id || createId('popup'),
			type: normalized.type || '',
			name: normalized.name || '',
			enabled: normalized.enabled !== false,
			trigger: getPopupTrigger(normalized.trigger || 'manual'),
			created_at: normalized.created_at || new Date().toISOString(),
			updated_at: normalized.updated_at || new Date().toISOString(),
			heading: normalized.heading || '',
			text: normalized.text || '',
			button_label: normalized.button_label || '',
			button_url: normalized.button_url || '',
			email: normalized.email || '',
			phone: normalized.phone || '',
			address: normalized.address || '',
			video_url: normalized.video_url || '',
			embed_code: normalized.embed_code || '',
			description: normalized.description || '',
			unavailable: normalized.unavailable === true || !definition
		};

		Object.keys(normalized).forEach(function (key) {
			if (!(key in nextPopup)) {
				nextPopup[key] = normalized[key];
			}
		});

		if (definition && definition.defaults) {
			Object.keys(definition.defaults).forEach(function (key) {
				if (nextPopup[key] === undefined || nextPopup[key] === null || nextPopup[key] === '') {
					nextPopup[key] = definition.defaults[key];
				}
			});
		}

		nextPopup.trigger = getPopupTrigger(nextPopup.trigger);
		return nextPopup;
	}

	function hasLegacySectionContent(section) {
		return ['kicker', 'title', 'text', 'button_label', 'button_url', 'email', 'phone', 'address'].some(function (key) {
			return Boolean(section && section[key]);
		});
	}

	function ensureUniqueTitles(items, getBaseTitle) {
		var used = [];

		return items.map(function (item) {
			var nextItem = item;
			var currentTitle = item.name || '';
			var baseTitle = typeof getBaseTitle === 'function' ? getBaseTitle(item) : currentTitle;
			nextItem.name = buildUniqueTitle(currentTitle || baseTitle, used);
			used.push(nextItem.name);
			return nextItem;
		});
	}

	function buildUniqueTitle(baseTitle, usedTitles) {
		var base = String(baseTitle || '').trim();
		var suffix = 1;
		var candidate = base;

		if (!base) {
			return '';
		}

		while (usedTitles.indexOf(candidate) >= 0) {
			candidate = base + ' ' + String(suffix);
			suffix += 1;
		}

		return candidate;
	}

	function getTypeConfig(type) {
		var baseConfig = {
			hero: {
				sectionTitle: t('manager.pages.section_types.hero'),
				blockTitleKey: 'manager.pages.block_types.hero',
				sectionLabel: t('manager.pages.sections.hero'),
				blockLabelKey: 'manager.pages.block_types.hero',
				sectionIcon: 'sparkles',
				blockIcon: 'app-window'
			},
			text: {
				sectionTitle: t('manager.pages.section_types.text'),
				blockTitleKey: 'manager.pages.block_types.text',
				sectionLabel: t('manager.pages.sections.text'),
				blockLabelKey: 'manager.pages.sections.block_text',
				sectionIcon: 'file-text',
				blockIcon: 'align-left'
			},
			contacts: {
				sectionTitle: t('manager.pages.section_types.contacts'),
				blockTitleKey: 'manager.pages.block_types.contacts',
				sectionLabel: t('manager.pages.sections.contacts'),
				blockLabelKey: 'manager.pages.sections.block_contacts',
				sectionIcon: 'address-book',
				blockIcon: 'mail'
			}
		}[type || 'text'] || {
			sectionTitle: t('manager.pages.sections.module_unavailable'),
			blockTitleKey: 'manager.pages.block_types.text',
			sectionLabel: t('manager.pages.sections.module_unavailable'),
			blockLabelKey: 'manager.pages.sections.block_text',
			sectionIcon: 'file-text',
			blockIcon: 'align-left'
		};
		var sectionDefinition = getSectionDefinition(type);

		if (sectionDefinition) {
			baseConfig.sectionTitle = sectionDefinition.label;
			baseConfig.sectionLabel = sectionDefinition.label;
			baseConfig.sectionIcon = sectionDefinition.icon_key;
			baseConfig.sectionDescription = sectionDefinition.description;
		}

		return baseConfig;
	}

	function getDefaultSectionTitle(type) {
		return getTypeConfig(type).sectionTitle || '';
	}

	function getDefaultBlockTitle(type) {
		var definition = getBlockDefinition(type);

		if (definition) {
			return definition.label || '';
		}

		return t('manager.pages.blocks.unavailable_type');
	}

	function getDefaultWidgetTitle(type) {
		var definition = getWidgetDefinition(type);

		if (definition) {
			return definition.label || '';
		}

		return t('manager.pages.widgets.unavailable_type');
	}

	function getDefaultPopupTitle(type) {
		var definition = getPopupDefinition(type);

		if (definition) {
			return definition.label || '';
		}

		return t('manager.pages.popups.unavailable_type');
	}

	function getSectionTypeLabel(type) {
		return getTypeConfig(type).sectionLabel || '';
	}

	function getBlockTypeLabel(type) {
		var definition = getBlockDefinition(type);

		if (definition) {
			return definition.label || '';
		}

		return t('manager.pages.blocks.unavailable_type');
	}

	function getBlockIcon(type) {
		var definition = getBlockDefinition(type);

		return definition ? (definition.icon_key || definition.icon || 'circle-dot') : 'circle-dot';
	}

	function getWidgetTypeLabel(type) {
		var definition = getWidgetDefinition(type);

		if (definition) {
			return definition.label || '';
		}

		return t('manager.pages.widgets.unavailable_type');
	}

	function getWidgetIcon(type) {
		var definition = getWidgetDefinition(type);

		return definition ? (definition.icon_key || definition.icon || 'circle-dot') : 'circle-dot';
	}

	function getPopupTypeLabel(type) {
		var definition = getPopupDefinition(type);

		if (definition) {
			return definition.label || '';
		}

		return t('manager.pages.popups.unavailable_type');
	}

	function getPopupIcon(type) {
		var definition = getPopupDefinition(type);

		return definition ? (definition.icon_key || definition.icon || 'circle-dot') : 'circle-dot';
	}

	function getWidgetPosition(value) {
		return ['bottom_right', 'bottom_left', 'top_right', 'top_left'].indexOf(String(value || '')) >= 0 ? String(value || '') : 'bottom_right';
	}

	function getWidgetPositionLabel(value) {
		return t('manager.pages.widgets.position_' + getWidgetPosition(value)) || t('manager.pages.widgets.position_bottom_right');
	}

	function getPopupTrigger(value) {
		return ['manual', 'page_load', 'delay', 'scroll', 'exit_intent', 'first_visit'].indexOf(String(value || '')) >= 0 ? String(value || '') : 'manual';
	}

	function getPopupTriggerLabel(value) {
		return t('manager.pages.popups.trigger_' + getPopupTrigger(value)) || t('manager.pages.popups.trigger_manual');
	}

	function getWidgetFieldSchema(type) {
		var definition = getWidgetDefinition(type);
		var fields = definition && Array.isArray(definition.fields) ? definition.fields : [];
		var typeMap = {
			url: 'url',
			email: 'email',
			embed_code: 'textarea',
			description: 'textarea',
			message: 'textarea'
		};
		var labelMap = {
			label: 'manager.pages.widgets.label_label',
			url: 'manager.pages.widgets.url_label',
			phone: 'manager.pages.widgets.phone_label',
			email: 'manager.pages.widgets.email_label',
			message: 'manager.pages.widgets.message_label',
			platform: 'manager.pages.widgets.platform_label',
			address: 'manager.pages.widgets.address_label',
			description: 'manager.pages.widgets.description_label',
			embed_code: 'manager.pages.widgets.embed_code_label'
		};

		return fields.map(function (fieldKey) {
			return {
				key: fieldKey,
				label: t(labelMap[fieldKey] || ''),
				type: typeMap[fieldKey] || 'text'
			};
		});
	}

	function getPopupFieldSchema(type) {
		var definition = getPopupDefinition(type);
		var fields = definition && Array.isArray(definition.fields) ? definition.fields : [];
		var typeMap = {
			button_url: 'url',
			email: 'email',
			video_url: 'url',
			embed_code: 'textarea',
			description: 'textarea',
			text: 'textarea'
		};
		var labelMap = {
			heading: 'manager.pages.popups.heading_label',
			text: 'manager.pages.popups.text_label',
			button_label: 'manager.pages.popups.button_label_label',
			button_url: 'manager.pages.popups.button_url_label',
			email: 'manager.pages.popups.email_label',
			phone: 'manager.pages.popups.phone_label',
			address: 'manager.pages.popups.address_label',
			video_url: 'manager.pages.popups.video_url_label',
			embed_code: 'manager.pages.popups.embed_code_label',
			description: 'manager.pages.popups.description_label'
		};

		return fields.map(function (fieldKey) {
			return {
				key: fieldKey,
				label: t(labelMap[fieldKey] || ''),
				type: typeMap[fieldKey] || 'text'
			};
		});
	}

	function createWidgetFromDefinition(definition, usedTitles) {
		var defaults = definition && definition.defaults && typeof definition.defaults === 'object' ? definition.defaults : {};
		var nextWidget = normalizeWidget(defaults);
		nextWidget.id = createId('widget');
		nextWidget.type = definition ? definition.type : nextWidget.type;
		nextWidget.name = buildUniqueTitle(nextWidget.name || getDefaultWidgetTitle(nextWidget.type), usedTitles || []);
		nextWidget.unavailable = false;
		nextWidget.updated_at = new Date().toISOString();
		return nextWidget;
	}

	function createPopupFromDefinition(definition, usedTitles) {
		var defaults = definition && definition.defaults && typeof definition.defaults === 'object' ? definition.defaults : {};
		var nextPopup = normalizePopup(defaults);
		nextPopup.id = createId('popup');
		nextPopup.type = definition ? definition.type : nextPopup.type;
		nextPopup.name = buildUniqueTitle(nextPopup.name || getDefaultPopupTitle(nextPopup.type), usedTitles || []);
		nextPopup.unavailable = false;
		nextPopup.updated_at = new Date().toISOString();
		return nextPopup;
	}

	function getBlockFieldLabel(fieldKey) {
		return t('manager.pages.blocks.field.' + String(fieldKey || '')) || '';
	}

	function getBlockFieldSchema(type) {
		var definition = getBlockDefinition(type);
		var fields = definition && Array.isArray(definition.fields) ? definition.fields : [];
		var textareaFields = {
			text: true,
			description: true,
			list_text: true,
			quote: true,
			answer: true,
			embed_code: true
		};
		var urlFields = {
			button_url: true,
			image_url: true,
			video_url: true,
			link_url: true,
			map_url: true,
			file_url: true
		};
		var emailFields = {
			email: true
		};

		return fields.map(function (fieldKey) {
			var inputType = 'text';

			if (textareaFields[fieldKey]) {
				inputType = 'textarea';
			} else if (urlFields[fieldKey]) {
				inputType = 'url';
			} else if (emailFields[fieldKey]) {
				inputType = 'email';
			}

			return {
				key: fieldKey,
				label: getBlockFieldLabel(fieldKey),
				type: inputType
			};
		});
	}

	function createBlockFromDefinition(definition, usedTitles) {
		var defaults = definition && definition.defaults && typeof definition.defaults === 'object' ? definition.defaults : {};
		var nextBlock = normalizeBlock(defaults, definition ? definition.type : 'text');
		nextBlock.id = createId('block');
		nextBlock.type = definition ? definition.type : nextBlock.type;
		nextBlock.name = buildUniqueTitle(nextBlock.name || getDefaultBlockTitle(nextBlock.type), usedTitles || []);
		nextBlock.unavailable = false;
		return nextBlock;
	}

	function replacePlaceholder(template, value) {
		return String(template || '').replace('%s', String(value || ''));
	}

	function getDeleteTargetName(type, targetId, parentId) {
		var page;
		var section;
		var block;
		var widget;

		if (type === 'section') {
			section = getSectionById(getActivePage(), targetId);
			return section ? (section.name || getSectionTypeLabel(section.type)) : '';
		}

		if (type === 'block') {
			section = getSectionById(getActivePage(), parentId);

			if (!section) {
				return '';
			}

			block = (section.blocks || []).find(function (item) {
				return item.id === targetId;
			}) || null;

			return block ? (block.name || getBlockTypeLabel(block.type)) : '';
		}

		if (type === 'widget') {
			page = getActivePage();
			widget = page && Array.isArray(page.widgets) ? page.widgets.find(function (item) {
				return item.id === targetId;
			}) : null;

			return widget ? (widget.name || getWidgetTypeLabel(widget.type)) : '';
		}

		page = findPage(targetId);
		return page ? (page.title || page.slug || '') : '';
	}

	function getDefaultBlockType(sectionType) {
		var definition = getSectionDefinition(sectionType);
		var defaults = definition && definition.default_blocks && definition.default_blocks[0] ? definition.default_blocks[0] : null;

		if (defaults && defaults.type) {
			return defaults.type;
		}

		return ['hero', 'text', 'contacts'].indexOf(sectionType) >= 0 ? sectionType : 'text';
	}

	function findPage(id) {
		return pagesState.items.find(function (item) {
			return item.id === id;
		}) || null;
	}

	function getActivePage() {
		return findPage(pagesState.activeId);
	}

	function getActiveSection(page) {
		var currentPage = page || getActivePage();

		if (!currentPage) {
			return null;
		}

		if (!pagesState.activeSectionId && currentPage.sections[0]) {
			pagesState.activeSectionId = currentPage.sections[0].id;
		}

		return currentPage.sections.find(function (section) {
			return section.id === pagesState.activeSectionId;
		}) || currentPage.sections[0] || null;
	}

	function getActiveBlock(section) {
		var currentSection = section || getActiveSection();

		if (!currentSection) {
			return null;
		}

		if (!pagesState.activeBlockId && currentSection.blocks[0]) {
			pagesState.activeBlockId = currentSection.blocks[0].id;
		}

		return currentSection.blocks.find(function (block) {
			return block.id === pagesState.activeBlockId;
		}) || currentSection.blocks[0] || null;
	}

	function getActiveWidget(page) {
		var currentPage = page || getActivePage();

		if (!currentPage) {
			return null;
		}

		if (!pagesState.activeWidgetId && currentPage.widgets[0]) {
			pagesState.activeWidgetId = currentPage.widgets[0].id;
		}

		return currentPage.widgets.find(function (widget) {
			return widget.id === pagesState.activeWidgetId;
		}) || currentPage.widgets[0] || null;
	}

	function getActivePopup(page) {
		var currentPage = page || getActivePage();

		if (!currentPage) {
			return null;
		}

		if (!pagesState.activePopupId && currentPage.popups[0]) {
			pagesState.activePopupId = currentPage.popups[0].id;
		}

		return currentPage.popups.find(function (popup) {
			return popup.id === pagesState.activePopupId;
		}) || currentPage.popups[0] || null;
	}

	function getSectionById(page, sectionId) {
		var currentPage = page || getActivePage();

		if (!currentPage) {
			return null;
		}

		return currentPage.sections.find(function (section) {
			return section.id === sectionId;
		}) || null;
	}

	function isPagesTableMobile() {
		return window.innerWidth <= 767;
	}

	function clonePagesIcon(key) {
		var template = pagesRoot ? pagesRoot.querySelector('[data-pages-icon-template="' + key + '"]') : null;

		if (!template || !template.firstElementChild) {
			return document.createTextNode('');
		}

		return template.firstElementChild.cloneNode(true);
	}

	function getManagerUi() {
		if (!window.SonyraManagerUI) {
			throw new Error('[SonyraManagerUI] Shared manager UI library is not loaded.');
		}

		return window.SonyraManagerUI;
	}

	function setPagesMessage(type, message) {
		var messageNode = pagesRoot ? pagesRoot.querySelector('[data-pages-message]') : null;
		var iconKey = 'info-circle';
		var icon;
		var text;
		var closeButton;

		if (!messageNode) {
			return;
		}

		if (!message) {
			clearNoticeTimer('pages-message');
			messageNode.hidden = true;
			messageNode.textContent = '';
			messageNode.removeAttribute('data-pages-message-type');
			messageNode.removeAttribute('data-manager-notice-type');
			messageNode.classList.remove('is-dismissing');
			return;
		}

		if (type === 'success') {
			iconKey = 'circle-check';
		} else if (type === 'warning') {
			iconKey = 'alert-triangle';
		} else if (type === 'error') {
			iconKey = 'alert-circle';
		} else if (type === 'loading') {
			iconKey = 'progress';
		}

		clearNode(messageNode);
		messageNode.hidden = false;
		messageNode.setAttribute('data-pages-message-type', type || 'info');
		messageNode.setAttribute('data-manager-notice-type', type || 'info');

		icon = createTextNode('span', 'sonyra-manager-notice__icon');
		icon.appendChild(clonePagesIcon(iconKey));
		text = createTextNode('span', 'sonyra-manager-notice__text', message);
		messageNode.appendChild(icon);
		messageNode.appendChild(text);

		if (type !== 'loading') {
			closeButton = document.createElement('button');
			closeButton.type = 'button';
			closeButton.className = 'sonyra-manager-notice__close';
			closeButton.setAttribute('data-pages-message-close', 'true');
			closeButton.setAttribute('aria-label', t('manager.notice_close_label'));
			closeButton.appendChild(clonePagesIcon('x'));
			messageNode.appendChild(closeButton);
		}

		clearNoticeTimer('pages-message');

		if (type === 'success' || type === 'info') {
			noticeTimers['pages-message'] = window.setTimeout(function () {
				messageNode.classList.add('is-dismissing');
				window.setTimeout(function () {
					setPagesMessage('', '');
				}, 180);
			}, type === 'success' ? 4000 : 6000);
		}
	}

	function getPagesUrl(page) {
		if (!page) {
			return '';
		}

		if (page.public_url) {
			return page.public_url;
		}

		var siteUrl = shell.getAttribute('data-site-url') || '/';

		if (page.is_home) {
			return siteUrl;
		}

		return siteUrl.replace(/\/?$/, '/') + (page.slug || '').replace(/^\/|\/$/g, '') + '/';
	}

	function getPagePreviewUrl(page) {
		if (!page) {
			return '';
		}

		if (page.preview_url) {
			return page.preview_url;
		}

		var siteUrl = shell.getAttribute('data-site-url') || '/';
		var normalizedBase = siteUrl.replace(/\/?$/, '/');
		var slug = (page.slug || '').replace(/^\/|\/$/g, '');

		if (slug) {
			return normalizedBase + 'sonyra-preview/' + slug + '/';
		}

		return normalizedBase + 'sonyra-preview/id/' + (page.id || '') + '/';
	}

	function formatCountLabel(key, count) {
		return t(key).replace('%d', String(count));
	}

	function getPagesSummary() {
		return pagesState.items.reduce(function (summary, page) {
			summary.total += 1;
			if (page.status === 'published') {
				summary.published += 1;
			} else if (page.status === 'hidden') {
				summary.hidden += 1;
			} else {
				summary.draft += 1;
			}

			if (page.is_home) {
				summary.hasHome = true;
			}

			return summary;
		}, {
			total: 0,
			published: 0,
			draft: 0,
			hidden: 0,
			hasHome: false
		});
	}

	function createHeaderMetaChip(label, tone) {
		return createTextNode('span', 'sonyra-manager-page-badge sonyra-manager-page-header__meta-chip sonyra-manager-page-badge-' + (tone || 'neutral'), label);
	}

	function getHistoryEventLabel(action) {
		var key = 'manager.pages.history.event.' + action;
		var value = t(key);
		return value || '';
	}

	function addPageHistoryEvent(page, action, details) {
		if (!page) {
			return page;
		}

		page.history = Array.isArray(page.history) ? page.history.slice() : [];
		page.history.unshift({
			at: new Date().toISOString(),
			action: action,
			label: getHistoryEventLabel(action),
			details: details || ''
		});
		page.history = page.history.slice(0, 50);
		return page;
	}

	function clearPendingPageAction() {
		pagesState.pendingPageId = '';
		pagesState.pendingAction = '';
	}

	function setPendingPageAction(pageId, action, messageKey) {
		pagesState.pendingPageId = pageId || '';
		pagesState.pendingAction = action || '';
		if (messageKey) {
			setPagesMessage('loading', t(messageKey));
		}
		renderPagesGridContentOnly();
	}

	function isPendingPageAction(pageId, action) {
		return pagesState.saving && pagesState.pendingPageId === pageId && (!action || pagesState.pendingAction === action);
	}

	function buildDuplicateSlug(baseSlug) {
		var normalizedBase = String(baseSlug || 'home').replace(/^\/|\/$/g, '') || 'home';
		var candidate = normalizedBase + '-copy';
		var suffix = 2;
		var used = {};

		pagesState.items.forEach(function (page) {
			if (page.slug) {
				used[page.slug] = true;
			}
		});

		while (used[candidate]) {
			candidate = normalizedBase + '-copy-' + String(suffix);
			suffix += 1;
		}

		return candidate;
	}

	function renderPagesToolbar() {
		var openSite = shell.querySelector('[data-pages-open-site]');
		var firstOpenable = pagesState.items.find(function (page) {
			return page.can_open && page.is_home;
		}) || pagesState.items.find(function (page) {
			return page.can_open;
		});

		if (openSite) {
			openSite.disabled = !firstOpenable;
			openSite.title = firstOpenable ? '' : t('manager.pages.open_site_disabled');
		}
	}

	function renderPagesHeaderBadges() {
		if (!pageBadges) {
			return;
		}

		clearNode(pageBadges);
		pageBadges.classList.add('sonyra-manager-page-header__meta');

		var summary = getPagesSummary();
		pageBadges.appendChild(createHeaderMetaChip(formatCountLabel('manager.pages.badges.total', summary.total), 'neutral'));
		pageBadges.appendChild(createHeaderMetaChip(formatCountLabel('manager.pages.badges.published_total', summary.published), 'ready'));
		pageBadges.appendChild(createHeaderMetaChip(formatCountLabel('manager.pages.badges.draft_total', summary.draft), 'warning'));
		pageBadges.appendChild(createHeaderMetaChip(formatCountLabel('manager.pages.badges.hidden_total', summary.hidden), 'danger'));

		if (summary.hasHome) {
			pageBadges.appendChild(createHeaderMetaChip(t('manager.pages.badges.home_ready'), 'neutral'));
		}

		pageBadges.hidden = false;
	}

	function renderPagesEditor() {
		if (!pagesRoot) {
			return;
		}

		var workspaceRoot = pagesRoot.querySelector('[data-pages-workspace]');
		var workspace = pagesRoot.querySelector('[data-pages-sections-mode]');
		var helpRoot = getGlobalHelpLayer();
		var page = getActivePage();

		if (!workspaceRoot || !workspace) {
			return;
		}

		workspace.hidden = pagesState.mode !== 'sections' && pagesState.mode !== 'widgets' && pagesState.mode !== 'popups';
		clearNode(workspaceRoot);
		if (helpRoot) {
			clearNode(helpRoot);
		}

		if (!page || (pagesState.mode !== 'sections' && pagesState.mode !== 'widgets' && pagesState.mode !== 'popups')) {
			pagesState.openHelpTooltip = '';
			pagesState.openHelpTooltipPinned = false;
			pagesState.openHelpTooltipTrigger = '';
			return;
		}

		if (pagesState.mode === 'widgets') {
			var activeWidget = getActiveWidget(page);
			if (activeWidget) {
				pagesState.activeWidgetId = activeWidget.id;
			}
			workspaceRoot.appendChild(buildWidgetsWorkspace(page, activeWidget));
		} else if (pagesState.mode === 'popups') {
			var activePopup = getActivePopup(page);
			if (activePopup) {
				pagesState.activePopupId = activePopup.id;
			}
			workspaceRoot.appendChild(buildPopupsWorkspace(page, activePopup));
		} else {
			var activeSection = getActiveSection(page);

			if (activeSection) {
				pagesState.activeSectionId = activeSection.id;
			}

			workspaceRoot.appendChild(buildSectionsWorkspace(page, activeSection));
		}
		renderHelpPopover();
	}

	function renderHelpPopover() {
		var popoverRoot = getGlobalHelpLayer();
		var trigger;
		var triggerRect;
		var popover;
		var entry;
		var preferredWidth = 360;
		var gap = 10;
		var viewportPadding = 14;
		var viewportWidth = window.innerWidth || 0;
		var viewportHeight = window.innerHeight || 0;
		var left = 0;
		var top = 0;
		var placement = 'right';
		var clampedWidth;
		var popoverHeight;

		if (!popoverRoot) {
			return;
		}

		clearHelpPopoverScope('pages');

		if (!pagesState.openHelpTooltip || (pagesState.mode !== 'sections' && pagesState.mode !== 'widgets' && pagesState.mode !== 'popups')) {
			return;
		}

		entry = getHelpEntry(pagesState.openHelpTooltip);
		trigger = pagesRoot.querySelector('[data-sonyra-help-trigger="' + pagesState.openHelpTooltipTrigger + '"]');

		if (!entry || !trigger) {
			return;
		}

		popover = createTextNode('article', 'sonyra-help-popover');
		popover.setAttribute('data-sonyra-help-scope', 'pages');
		popover.setAttribute('data-pages-help-popover', pagesState.openHelpTooltip);
		popover.setAttribute('role', 'tooltip');
		popover.appendChild(createTextNode('strong', 'sonyra-help-popover__title', entry.title || ''));
		popover.appendChild(createTextNode('p', 'sonyra-help-popover__body', entry.body || ''));
		popoverRoot.appendChild(popover);

		triggerRect = trigger.getBoundingClientRect();
		clampedWidth = Math.min(preferredWidth, Math.max(220, viewportWidth - (viewportPadding * 2)));
		popover.style.maxWidth = String(clampedWidth) + 'px';

		if (triggerRect.right + gap + clampedWidth <= viewportWidth - viewportPadding) {
			left = triggerRect.right + gap;
			placement = 'right';
		} else if (triggerRect.left - gap - clampedWidth >= viewportPadding) {
			left = triggerRect.left - gap - clampedWidth;
			placement = 'left';
		} else {
			left = Math.max(viewportPadding, Math.min(triggerRect.left, viewportWidth - clampedWidth - viewportPadding));
			placement = 'bottom';
		}

		popoverHeight = Math.ceil(popover.getBoundingClientRect().height || popover.offsetHeight || 0);

		if (placement === 'right' || placement === 'left') {
			if (triggerRect.top < viewportHeight * 0.45 && triggerRect.bottom + gap + popoverHeight <= viewportHeight - viewportPadding) {
				top = triggerRect.bottom + gap;
			} else if (triggerRect.top - gap - popoverHeight >= viewportPadding) {
				top = triggerRect.top - popoverHeight - gap;
			} else {
				top = Math.max(viewportPadding, Math.min(triggerRect.top, viewportHeight - popoverHeight - viewportPadding));
			}
		} else {
			if (triggerRect.bottom + gap + popoverHeight <= viewportHeight - viewportPadding) {
				top = triggerRect.bottom + gap;
				placement = 'bottom';
			} else {
				top = triggerRect.top - popoverHeight - gap;
				placement = 'top';
			}

			top = Math.max(viewportPadding, Math.min(top, viewportHeight - popoverHeight - viewportPadding));
		}

		left = Math.max(viewportPadding, Math.min(left, viewportWidth - clampedWidth - viewportPadding));

		popover.style.left = String(left) + 'px';
		popover.style.top = String(top) + 'px';
		popover.setAttribute('data-popover-placement', placement);
	}

	function getGlobalHelpLayer() {
		return document.querySelector('[data-manager-help-layer]') || (pagesRoot ? pagesRoot.querySelector('[data-pages-help-popover-root]') : null);
	}

	function clearHelpPopoverScope(scope) {
		var layer = getGlobalHelpLayer();
		var selectors;

		if (!layer) {
			return;
		}

		selectors = scope ? ('[data-sonyra-help-scope="' + scope + '"]') : '[data-sonyra-help-scope]';
		Array.prototype.slice.call(layer.querySelectorAll(selectors)).forEach(function (node) {
			if (node && node.parentNode) {
				node.parentNode.removeChild(node);
			}
		});
	}

	function buildSectionsWorkspace(page, activeSection) {
		var workspace = createTextNode('div', 'sonyra-pages-sections-workspace');
		var context = createTextNode('div', 'sonyra-pages-sections-context sonyra-manager-pages-table-card');
		var contextCopy = createTextNode('div', 'sonyra-pages-sections-context-copy');
		var contextLabel = createTextNode('span', 'sonyra-pages-sections-context-label', t('manager.pages.sections.current_page'));
		var contextTitle = createTextNode('strong', 'sonyra-pages-sections-context-title', page.title || '/');
		var backButton = createButton('sonyra-manager-pages-secondary', t('manager.pages.actions.back_to_table'), 'arrow-left');
		var grid = createTextNode('div', 'sonyra-pages-sections-grid');

		backButton.setAttribute('data-pages-back-to-table', 'true');
		contextCopy.appendChild(contextLabel);
		contextCopy.appendChild(contextTitle);
		context.appendChild(contextCopy);
		context.appendChild(backButton);
		workspace.appendChild(context);
		grid.appendChild(buildSectionsListPanel(page, activeSection));
		grid.appendChild(buildBlocksPanel(page, activeSection));
		workspace.appendChild(grid);

		return workspace;
	}

	function buildWidgetsWorkspace(page, activeWidget) {
		var workspace = createTextNode('div', 'sonyra-pages-sections-workspace sonyra-pages-widgets-workspace');
		var context = createLayerContext(t('manager.pages.widgets.context_title'), 'pages.widgets.panel', 'manager.help.aria.pages.widgets.panel', page.title || '/');
		var backButton = createButton('sonyra-manager-pages-secondary', t('manager.pages.actions.back_to_table'), 'arrow-left');
		var addButton = createButton('sonyra-manager-pages-primary', t('manager.pages.widgets.add_button'));
		var panel = createTextNode('section', 'sonyra-pages-widgets-panel sonyra-manager-pages-table-card sonyra-pages-layer-panel');
		var list = createTextNode('div', 'sonyra-pages-widgets-list');

		backButton.setAttribute('data-pages-back-to-table', 'true');
		addButton.setAttribute('data-pages-open-widget-library', 'true');
		context.querySelector('[data-pages-layer-actions]').appendChild(backButton);
		context.querySelector('[data-pages-layer-actions]').appendChild(addButton);
		workspace.appendChild(context);

		if (!page.widgets.length) {
			panel.appendChild(createWorkspaceEmptyState(
				t('manager.pages.widgets.empty.title'),
				t('manager.pages.widgets.empty.description'),
				t('manager.pages.widgets.add_button'),
				'widget'
			));
			workspace.appendChild(panel);
			return workspace;
		}

		page.widgets.forEach(function (widget) {
			list.appendChild(createWidgetCard(widget, activeWidget && activeWidget.id === widget.id));
		});
		panel.appendChild(list);
		workspace.appendChild(panel);

		return workspace;
	}

	function buildPopupsWorkspace(page, activePopup) {
		var workspace = createTextNode('div', 'sonyra-pages-sections-workspace sonyra-pages-popups-workspace');
		var context = createLayerContext(t('manager.pages.popups.context_title'), 'pages.popups.panel', 'manager.help.aria.pages.popups.panel', page.title || '/');
		var backButton = createButton('sonyra-manager-pages-secondary', t('manager.pages.actions.back_to_table'), 'arrow-left');
		var addButton = createButton('sonyra-manager-pages-primary', t('manager.pages.popups.add_button'));
		var panel = createTextNode('section', 'sonyra-pages-popups-panel sonyra-manager-pages-table-card sonyra-pages-layer-panel');
		var list = createTextNode('div', 'sonyra-pages-popups-list');

		backButton.setAttribute('data-pages-back-to-table', 'true');
		addButton.setAttribute('data-pages-open-popup-library', 'true');
		context.querySelector('[data-pages-layer-actions]').appendChild(backButton);
		context.querySelector('[data-pages-layer-actions]').appendChild(addButton);
		workspace.appendChild(context);

		if (!page.popups.length) {
			panel.appendChild(createWorkspaceEmptyState(
				t('manager.pages.popups.empty.title'),
				t('manager.pages.popups.empty.description'),
				t('manager.pages.popups.add_button'),
				'popup'
			));
			workspace.appendChild(panel);
			return workspace;
		}

		page.popups.forEach(function (popup) {
			list.appendChild(createPopupCard(popup, activePopup && activePopup.id === popup.id));
		});
		panel.appendChild(list);
		workspace.appendChild(panel);

		return workspace;
	}

	function createLayerContext(labelText, helpKey, ariaLabelKey, titleText) {
		return getManagerUi().renderContextCard({
			className: 'sonyra-pages-sections-context sonyra-pages-layer-context sonyra-manager-pages-table-card',
			copyClassName: 'sonyra-pages-sections-context-copy sonyra-pages-layer-context-copy',
			titleWrapClassName: 'sonyra-pages-panel-title-wrap',
			actionsClassName: 'sonyra-pages-layer-context-actions',
			labelNode: createTextNode('span', 'sonyra-pages-sections-context-label', labelText),
			helpNode: createHelpTooltip(helpKey, ariaLabelKey),
			titleNode: createTextNode('strong', 'sonyra-pages-sections-context-title', titleText),
			actionsAttributeName: 'data-pages-layer-actions'
		});
	}

	function buildSectionsListPanel(page, activeSection) {
		var panel = createTextNode('aside', 'sonyra-pages-sections-list-panel sonyra-manager-pages-table-card');
		var head = createPanelHead(t('manager.pages.sections.panel_title'), t('manager.pages.sections.description'), 'pages.sections.panel', 'manager.help.aria.pages.sections.panel');
		var addButton = createButton('sonyra-manager-pages-primary', t('manager.pages.actions.add_section'));
		var list = createTextNode('div', 'sonyra-pages-sections-list');

		addButton.setAttribute('data-pages-open-section-library', 'true');
		head.appendChild(addButton);
		panel.appendChild(head);

		if (!page.sections.length) {
			panel.appendChild(createWorkspaceEmptyState(
				t('manager.pages.sections.empty_title'),
				t('manager.pages.sections.empty_description'),
				t('manager.pages.actions.add_section'),
				'section'
			));
			return panel;
		}

		page.sections.forEach(function (section) {
			list.appendChild(createSectionCard(section, activeSection && activeSection.id === section.id));
		});
		panel.appendChild(list);

		return panel;
	}

	function buildBlocksPanel(page, activeSection) {
		var panel = createTextNode('section', 'sonyra-pages-blocks-panel sonyra-manager-pages-table-card');
		var head = createPanelHead(t('manager.pages.sections.blocks_title'), '', 'pages.sections.blocks', 'manager.help.aria.pages.sections.blocks');
		var addButton = createButton('sonyra-manager-pages-primary', t('manager.pages.actions.add_block'));
		var list = createTextNode('div', 'sonyra-pages-blocks-list');
		var activeBlock = getActiveBlock(activeSection);

		addButton.setAttribute('data-pages-open-block-library', 'true');
		addButton.disabled = !activeSection;
		head.appendChild(addButton);
		panel.appendChild(head);

		if (!activeSection) {
			panel.appendChild(createWorkspaceHintState(t('manager.pages.sections.choose_section')));
			return panel;
		}

		if (!activeSection.blocks.length) {
			panel.appendChild(createWorkspaceEmptyState(
				t('manager.pages.blocks.empty.title'),
				t('manager.pages.blocks.empty.description'),
				t('manager.pages.blocks.add_button'),
				'block'
			));
			return panel;
		}

		activeSection.blocks.forEach(function (block) {
			list.appendChild(createBlockCard(activeSection, block, activeBlock && activeBlock.id === block.id));
		});
		panel.appendChild(list);

		return panel;
	}

	function createPanelHead(titleText, descriptionText, helpKey, ariaLabelKey) {
		var head = createTextNode('div', 'sonyra-pages-workspace-head sonyra-pages-panel-head');
		var copy = createTextNode('div', 'sonyra-pages-workspace-head-copy');
		var titleWrap = createTextNode('div', 'sonyra-pages-panel-title-wrap');
		titleWrap.appendChild(createTextNode('h3', 'sonyra-pages-workspace-head-title', titleText));
		if (helpKey) {
			titleWrap.appendChild(createHelpTooltip(helpKey, ariaLabelKey));
		}
		copy.appendChild(titleWrap);
		if (descriptionText) {
			copy.appendChild(createTextNode('p', 'sonyra-pages-workspace-head-description', descriptionText));
		}
		head.appendChild(copy);
		return head;
	}

	function createHelpTooltip(helpKey, ariaLabelKey) {
		var triggerId = 'help-trigger-' + String(helpKey || '').replace(/[^a-z0-9_-]/gi, '-');
		var isOpen = pagesState.openHelpTooltip === helpKey;

		return getManagerUi().renderHelpTrigger({
			helpKey: helpKey,
			triggerId: triggerId,
			ariaLabel: t(ariaLabelKey || 'manager.pages.actions.help_tooltip'),
			expanded: isOpen,
			triggerAttributeName: 'data-pages-help-tooltip-trigger',
			renderIcon: clonePagesIcon
		});
	}

	function buildAddMenuWrap(button, target) {
		var wrap = createTextNode('div', 'sonyra-pages-workspace-menu-wrap');
		var menu = createTextNode('div', 'sonyra-pages-action-menu sonyra-pages-inline-action-menu');
		var configList = target === 'section' ? ['hero', 'text', 'contacts'] : ['hero', 'text', 'contacts'];
		var isOpen = pagesState.openAddMenu === target;

		wrap.appendChild(button);
		menu.hidden = !isOpen;
		menu.setAttribute('data-pages-add-menu', target);

		configList.forEach(function (type) {
			var option = createTextNode('button', 'sonyra-pages-action-item');
			var icon = createTextNode('span', 'sonyra-manager-pages-dropdown-icon');
			var labelKey = target === 'section' ? getTypeConfig(type).sectionLabelKey : getTypeConfig(type).blockLabelKey;
			option.type = 'button';
			option.setAttribute('data-pages-add-' + target, type);
			icon.appendChild(clonePagesIcon(target === 'section' ? getTypeConfig(type).sectionIcon : getTypeConfig(type).blockIcon));
			option.appendChild(icon);
			option.appendChild(createTextNode('span', '', t(labelKey)));
			menu.appendChild(option);
		});

		wrap.appendChild(menu);

		return wrap;
	}

	function createWorkspaceEmptyState(title, description, buttonLabel, target) {
		var empty = createTextNode('div', 'sonyra-manager-pages-empty-state sonyra-pages-workspace-empty-state');
		var button = createButton('sonyra-manager-pages-primary', buttonLabel);

		if (target === 'section') {
			button.setAttribute('data-pages-open-section-library', 'true');
		} else if (target === 'widget') {
			button.setAttribute('data-pages-open-widget-library', 'true');
		} else if (target === 'popup') {
			button.setAttribute('data-pages-open-popup-library', 'true');
		} else {
			button.setAttribute('data-pages-open-block-library', 'true');
		}
		empty.appendChild(createTextNode('strong', '', title));
		empty.appendChild(createTextNode('p', '', description));
		empty.appendChild(button);
		return empty;
	}

	function createWorkspaceHintState(title) {
		var empty = createTextNode('div', 'sonyra-manager-pages-empty-state sonyra-pages-workspace-empty-state');
		empty.appendChild(createTextNode('strong', '', title));
		return empty;
	}

	function createSectionCard(section, isActive) {
		var card = createTextNode('article', 'sonyra-section-card');
		var handle = createDragHandle('section', section.id);
		var icon = createCardIcon(getTypeConfig(section.type).sectionIcon);
		var text = createCardText(section.name || getDefaultSectionTitle(section.type), section.unavailable ? t('manager.pages.sections.module_unavailable') : getSectionTypeLabel(section.type));
		var actions = createCardActions('section', section.id);

		card.setAttribute('data-pages-section-card', section.id);
		card.setAttribute('data-pages-section-select', section.id);
		card.dataset.sectionId = section.id;
		card.classList.toggle('is-active', isActive === true);
		card.appendChild(handle);
		card.appendChild(icon);
		card.appendChild(text);
		card.appendChild(actions);
		return card;
	}

	function createBlockCard(section, block, isActive) {
		var card = createTextNode('article', 'sonyra-block-card');
		var handle = createDragHandle('block', block.id, section.id);
		var icon = createCardIcon(getBlockIcon(block.type));
		var text = createCardText(block.name || getDefaultBlockTitle(block.type), getBlockTypeLabel(block.type));
		var actions = createCardActions('block', block.id, section.id);

		card.setAttribute('data-pages-block-card', block.id);
		card.setAttribute('data-pages-block-select', block.id);
		card.dataset.blockId = block.id;
		card.dataset.sectionId = section.id;
		card.classList.toggle('is-active', isActive === true);
		card.appendChild(handle);
		card.appendChild(icon);
		card.appendChild(text);
		card.appendChild(actions);
		return card;
	}

	function createWidgetCard(widget, isActive) {
		var card = createTextNode('article', 'sonyra-widget-card sonyra-block-card');
		var handle = createDragHandle('widget', widget.id);
		var icon = createCardIcon(getWidgetIcon(widget.type));
		var text = createWidgetCardText(widget);
		var actions = createCardActions('widget', widget.id);

		card.setAttribute('data-pages-widget-card', widget.id);
		card.setAttribute('data-pages-widget-select', widget.id);
		card.dataset.widgetId = widget.id;
		card.classList.toggle('is-active', isActive === true);
		card.appendChild(handle);
		card.appendChild(icon);
		card.appendChild(text);
		card.appendChild(actions);
		return card;
	}

	function createPopupCard(popup, isActive) {
		var card = createTextNode('article', 'sonyra-popup-card sonyra-widget-card sonyra-block-card');
		var handle = createDragHandle('popup', popup.id);
		var icon = createCardIcon(getPopupIcon(popup.type));
		var text = createPopupCardText(popup);
		var actions = createCardActions('popup', popup.id);

		card.setAttribute('data-pages-popup-card', popup.id);
		card.setAttribute('data-pages-popup-select', popup.id);
		card.dataset.popupId = popup.id;
		card.classList.toggle('is-active', isActive === true);
		card.appendChild(handle);
		card.appendChild(icon);
		card.appendChild(text);
		card.appendChild(actions);
		return card;
	}

	function createDragHandle(kind, itemId, sectionId) {
		var handle = createTextNode('button', 'sonyra-pages-drag-handle sonyra-drag-handle');
		handle.type = 'button';
		handle.draggable = true;
		handle.setAttribute('aria-label', kind === 'section' ? t('manager.pages.actions.drag_section') : (kind === 'widget' ? t('manager.pages.widgets.drag_label') : (kind === 'popup' ? t('manager.pages.popups.drag_label') : t('manager.pages.blocks.drag_label'))));
		handle.setAttribute('data-pages-drag-handle', kind);
		handle.setAttribute('data-pages-drag-id', itemId);
		if (sectionId) {
			handle.setAttribute('data-pages-drag-section-id', sectionId);
		}
		handle.appendChild(clonePagesIcon('dots-grip'));
		return handle;
	}

	function createCardIcon(iconKey) {
		return getManagerUi().renderIconTile({
			className: 'sonyra-pages-card-icon',
			iconKey: iconKey,
			renderIcon: clonePagesIcon
		});
	}

	function createCardText(titleText, typeText) {
		var text = createTextNode('div', 'sonyra-pages-card-copy sonyra-section-card__content');
		text.appendChild(createTextNode('div', 'sonyra-pages-card-title sonyra-section-card__title', titleText));
		text.appendChild(createTextNode('div', 'sonyra-pages-card-type sonyra-section-card__type', typeText));
		return text;
	}

	function createWidgetCardText(widget) {
		var text = createTextNode('div', 'sonyra-pages-card-copy sonyra-section-card__content');
		var meta = createTextNode('div', 'sonyra-pages-widget-card-meta');
		var state = createTextNode('span', 'sonyra-pages-widget-card-badge' + (widget.enabled ? ' is-enabled' : ' is-disabled'), widget.enabled ? t('manager.pages.widgets.status_enabled') : t('manager.pages.widgets.status_disabled'));

		text.appendChild(createTextNode('div', 'sonyra-pages-card-title sonyra-section-card__title', widget.name || getDefaultWidgetTitle(widget.type)));
		text.appendChild(createTextNode('div', 'sonyra-pages-card-type sonyra-section-card__type', getWidgetTypeLabel(widget.type)));
		meta.appendChild(state);
		meta.appendChild(createTextNode('span', 'sonyra-pages-widget-card-badge sonyra-pages-widget-card-position', getWidgetPositionLabel(widget.position)));
		text.appendChild(meta);
		return text;
	}

	function createPopupCardText(popup) {
		var text = createTextNode('div', 'sonyra-pages-card-copy sonyra-section-card__content');
		var meta = createTextNode('div', 'sonyra-pages-widget-card-meta sonyra-pages-popup-card-meta');
		var state = createTextNode('span', 'sonyra-pages-widget-card-badge' + (popup.enabled ? ' is-enabled' : ' is-disabled'), popup.enabled ? t('manager.pages.popups.status_enabled') : t('manager.pages.popups.status_disabled'));

		text.appendChild(createTextNode('div', 'sonyra-pages-card-title sonyra-section-card__title', popup.name || getDefaultPopupTitle(popup.type)));
		text.appendChild(createTextNode('div', 'sonyra-pages-card-type sonyra-section-card__type', getPopupTypeLabel(popup.type)));
		meta.appendChild(state);
		meta.appendChild(createTextNode('span', 'sonyra-pages-widget-card-badge sonyra-pages-widget-card-position', getPopupTriggerLabel(popup.trigger)));
		text.appendChild(meta);
		return text;
	}

	function createCardActions(kind, itemId, sectionId) {
		var actions = createTextNode('div', 'sonyra-pages-card-actions sonyra-section-card__actions');
		var settingsButton = createButton('sonyra-pages-card-action', '', 'settings');
		var deleteButton = createButton('sonyra-pages-card-action sonyra-pages-card-action-danger', '', 'trash');
		var settingsLabel = kind === 'section' ? t('manager.pages.sections.section_modal_title') : (kind === 'widget' ? t('manager.pages.widgets.settings.title') : (kind === 'popup' ? t('manager.pages.popups.settings.title') : t('manager.pages.blocks.settings.title')));
		var deleteLabel = kind === 'section' ? t('manager.pages.sections.delete_section_title') : (kind === 'widget' ? t('manager.pages.widgets.delete.title') : (kind === 'popup' ? t('manager.pages.popups.delete.title') : t('manager.pages.sections.delete_block_title')));

		settingsButton.setAttribute('data-pages-' + kind + '-settings', itemId);
		deleteButton.setAttribute('data-pages-' + kind + '-delete', itemId);
		settingsButton.setAttribute('aria-label', settingsLabel);
		deleteButton.setAttribute('aria-label', deleteLabel);
		if (sectionId) {
			settingsButton.setAttribute('data-pages-section-id', sectionId);
			deleteButton.setAttribute('data-pages-section-id', sectionId);
		}
		actions.appendChild(settingsButton);
		actions.appendChild(deleteButton);
		return actions;
	}

	function createTextNode(tag, className, text) {
		var node = document.createElement(tag);

		if (className) {
			node.className = className;
		}

		if (text !== undefined) {
			node.textContent = text;
		}

		return node;
	}

	function createButton(className, text, iconKey) {
		return getManagerUi().renderButton({
			className: className,
			text: text,
			iconKey: iconKey,
			renderIcon: clonePagesIcon
		});
	}

	function formatPageUpdatedAt(value) {
		if (!value) {
			return {
				dateText: '—',
				timeText: ''
			};
		}

		var date = new Date(value);

		if (Number.isNaN(date.getTime())) {
			return {
				dateText: '—',
				timeText: ''
			};
		}

		return {
			dateText: new Intl.DateTimeFormat('ru-RU', {
				day: '2-digit',
				month: '2-digit',
				year: 'numeric'
			}).format(date),
			timeText: new Intl.DateTimeFormat('ru-RU', {
				hour: '2-digit',
				minute: '2-digit'
			}).format(date)
		};
	}

	function filterPagesForTable() {
		var query = String(pagesState.searchQuery || '').trim().toLowerCase();

		return pagesState.items.filter(function (page) {
			var matchesFilter = true;

			if (pagesState.statusFilter === 'published') {
				matchesFilter = page.status === 'published';
			} else if (pagesState.statusFilter === 'draft') {
				matchesFilter = page.status === 'draft';
			} else if (pagesState.statusFilter === 'hidden') {
				matchesFilter = page.status === 'hidden';
			} else if (pagesState.statusFilter === 'menu') {
				matchesFilter = page.show_in_menu === true;
			}

			if (!matchesFilter) {
				return false;
			}

			if (!query) {
				return true;
			}

			var haystack = [
				page.title || '',
				page.slug || '',
				page.menu_title || ''
			].join(' ').toLowerCase();

			return haystack.indexOf(query) !== -1;
		});
	}

	function sortPagesForTable(items) {
		return items.slice().sort(function (left, right) {
			if (pagesState.sort === 'created') {
				return new Date(right.created_at || 0).getTime() - new Date(left.created_at || 0).getTime();
			}

			if (pagesState.sort === 'az') {
				return String(left.title || '').localeCompare(String(right.title || ''), 'ru');
			}

			if (pagesState.sort === 'za') {
				return String(right.title || '').localeCompare(String(left.title || ''), 'ru');
			}

			if (pagesState.sort === 'menu') {
				if (left.show_in_menu !== right.show_in_menu) {
					return left.show_in_menu ? -1 : 1;
				}

				if (Number(left.menu_order || 0) !== Number(right.menu_order || 0)) {
					return Number(left.menu_order || 0) - Number(right.menu_order || 0);
				}

				return String(left.title || '').localeCompare(String(right.title || ''), 'ru');
			}

			return new Date(right.updated_at || right.created_at || 0).getTime() - new Date(left.updated_at || left.created_at || 0).getTime();
		});
	}

	function getPreparedPagesForTable() {
		return sortPagesForTable(filterPagesForTable());
	}

	function createPageBadge(text, tone) {
		return createTextNode('span', 'sonyra-manager-pages-page-badge sonyra-manager-pages-page-badge-' + tone, text);
	}

	function renderMenuCell(page) {
		var cell = createTextNode('div', 'sonyra-manager-pages-menu-cell');

		if (!page.show_in_menu) {
			cell.appendChild(createTextNode('span', 'sonyra-manager-pages-menu-empty', t('manager.pages.table.not_in_menu')));
			return cell;
		}

		var icon = createTextNode('span', 'sonyra-manager-pages-menu-icon');
		icon.appendChild(clonePagesIcon('menu-header'));
		cell.appendChild(icon);
		cell.appendChild(createTextNode('span', 'sonyra-manager-pages-menu-text', '№' + String(page.menu_order || 0)));
		return cell;
	}

	function renderUpdatedCell(page) {
		var formatted = formatPageUpdatedAt(page.updated_at || page.created_at || '');
		var cell = createTextNode('div', 'sonyra-pages-updated sonyra-pages-table-cell sonyra-pages-table-cell--updated');

		cell.appendChild(createTextNode('span', 'sonyra-pages-updated-date', formatted.dateText));

		if (formatted.timeText) {
			cell.appendChild(createTextNode('span', 'sonyra-pages-updated-time', formatted.timeText));
		}

		return cell;
	}

	function createDropdownItem(definition, page) {
		var item = createTextNode('button', 'sonyra-pages-action-item');
		var isDisabled = definition.future === true || definition.disabled(page) === true || isPendingPageAction(page.id);

		item.type = 'button';
		item.setAttribute('data-sonyra-page-action', definition.action);
		item.setAttribute('data-sonyra-page-id', page.id);
		item.appendChild(createTextNode('span', 'sonyra-manager-pages-dropdown-icon'));
		item.firstChild.appendChild(clonePagesIcon(definition.icon));
		item.appendChild(createTextNode('span', '', definition.label));

		if (definition.danger) {
			item.classList.add('is-danger');
		}

		if (isDisabled) {
			item.classList.add('is-disabled');
			item.setAttribute('aria-disabled', 'true');
			item.setAttribute('tabindex', '-1');
			item.disabled = true;
		}

		return item;
	}

	function getPagePublishAction(page) {
		if (page.status === 'published') {
			return {
				action: 'hide',
				label: t('manager.pages.actions.hide'),
				icon: 'eye-off',
				disabled: function () { return false; }
			};
		}

		return {
			action: 'publish',
			label: t('manager.pages.actions.publish'),
			icon: 'eye',
			disabled: function () { return false; }
		};
	}

	function getDropdownItems(page) {
		var publishAction = getPagePublishAction(page);

		return [
			{ action: 'settings', label: t('manager.pages.actions.parameters'), icon: 'settings', disabled: function () { return false; } },
			{ action: 'sections', label: t('manager.pages.actions.sections'), icon: 'section', disabled: function () { return false; } },
			{ action: 'widgets', label: t('manager.pages.widgets.action'), icon: 'circle-dot', disabled: function () { return false; } },
			{ action: 'popups', label: t('manager.pages.popups.action'), icon: 'info-circle', disabled: function () { return false; } },
			{ action: 'open', label: t('manager.pages.actions.open'), icon: 'external-link', disabled: function (current) { return !current.can_open; } },
			{ action: 'preview', label: t('manager.pages.actions.preview'), icon: 'browser-preview', disabled: function () { return false; } },
			{ action: 'copy', label: t('manager.pages.actions.copy_link'), icon: 'link', disabled: function () { return false; } },
			{ action: 'make-home', label: t('manager.pages.actions.make_home'), icon: 'home', disabled: function (current) { return current.is_home === true; } },
			publishAction,
			{ action: 'seo', label: t('manager.pages.actions.seo'), icon: 'world-search', future: true, disabled: function () { return true; } },
			{ action: 'history', label: t('manager.pages.actions.history'), icon: 'history', disabled: function () { return false; } },
			{ action: 'duplicate', label: t('manager.pages.actions.duplicate'), icon: 'copy', disabled: function () { return false; } },
			{ action: 'delete', label: t('manager.pages.actions.delete'), icon: 'trash', disabled: function () { return false; }, danger: true }
		];
	}

	function renderActionsCell(page) {
		var wrapper = createTextNode('div', 'sonyra-pages-action-cell sonyra-pages-table-cell sonyra-pages-table-cell--actions');
		var trigger = createButton('sonyra-pages-action-trigger', t('manager.pages.table.actions'));
		var dropdown = createTextNode('div', 'sonyra-pages-action-menu');
		var isOpen = pagesState.openDropdownId === page.id;
		var isPending = isPendingPageAction(page.id);

		trigger.setAttribute('aria-label', t('manager.pages.actions.page_actions'));
		trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
		trigger.setAttribute('data-sonyra-page-actions-trigger', page.id);
		trigger.disabled = isPending;

		if (isPending) {
			trigger.classList.add('is-pending');
			clearNode(trigger);
			trigger.appendChild(createTextNode('span', 'sonyra-manager-pages-dropdown-icon'));
			trigger.firstChild.appendChild(clonePagesIcon('progress'));
		}

		wrapper.appendChild(trigger);

		dropdown.setAttribute('data-sonyra-page-actions-menu', page.id);
		dropdown.hidden = !isOpen;
		getDropdownItems(page).forEach(function (definition, index) {
			if (index === 10) {
				dropdown.appendChild(createTextNode('div', 'sonyra-manager-pages-dropdown-divider'));
			}

			dropdown.appendChild(createDropdownItem(definition, page));
		});
		wrapper.appendChild(dropdown);

		return wrapper;
	}

	function renderPageCell(page) {
		var cell = createTextNode('div', 'sonyra-manager-pages-page-cell');
		var slugText = page.is_home ? '/' : '/' + page.slug + '/';
		var title = createTextNode('div', 'page-title', page.title || page.menu_title || slugText);
		var url = createTextNode('div', 'page-url', page.is_home ? '/' : '/' + page.slug + '/');
		var badges = createTextNode('div', 'page-badges');

		badges.appendChild(createPageBadge(t('manager.pages.status.' + page.status), page.status));

		if (page.is_home) {
			badges.appendChild(createPageBadge(t('manager.pages.badges.home_selected'), 'home'));
		}

		if (page.show_in_menu) {
			badges.appendChild(createPageBadge(t('manager.pages.status.menu_yes'), 'menu'));
		}

		cell.appendChild(title);
		cell.appendChild(url);
		cell.appendChild(badges);
		return cell;
	}

	function buildPagesGridContent() {
		var pages = getPreparedPagesForTable();
		var card = createTextNode('div', 'sonyra-manager-pages-table-card sonyra-pages-table-card');
		var listWrap;

		card.setAttribute('data-pages-grid-card', 'true');

		if (pagesState.pagesStatus === 'loading') {
			card.appendChild(renderPagesLoadingState());
			return card;
		}

		if (pagesState.pagesStatus === 'error') {
			card.appendChild(renderPagesErrorState());
			return card;
		}

		if (pagesState.pagesStatus === 'loaded' && !pagesState.items.length) {
			card.appendChild(renderEmptyState('empty'));
			return card;
		}

		if (!pages.length) {
			card.appendChild(renderEmptyState('filtered'));
			return card;
		}

		if (isPagesTableMobile()) {
			listWrap = createTextNode('div', 'sonyra-manager-pages-cards');
			pages.forEach(function (page) {
				var pageCard = createTextNode('article', 'sonyra-manager-pages-card');
				var menuRow = createTextNode('div', 'sonyra-manager-pages-card-row');
				var updatedRow = createTextNode('div', 'sonyra-manager-pages-card-row');
				var actionsRow = createTextNode('div', 'sonyra-manager-pages-card-actions');
				menuRow.appendChild(createTextNode('span', 'sonyra-manager-pages-card-label', t('manager.pages.table.column_menu')));
				menuRow.appendChild(renderMenuCell(page));
				updatedRow.appendChild(createTextNode('span', 'sonyra-manager-pages-card-label', t('manager.pages.table.column_updated')));
				updatedRow.appendChild(renderUpdatedCell(page));
				actionsRow.appendChild(renderActionsCell(page));
				pageCard.appendChild(renderPageCell(page));
				pageCard.appendChild(menuRow);
				pageCard.appendChild(updatedRow);
				pageCard.appendChild(actionsRow);
				if (isPendingPageAction(page.id)) {
					pageCard.classList.add('is-pending');
				}
				listWrap.appendChild(pageCard);
			});
		} else {
			listWrap = createTextNode('div', 'sonyra-pages-table');
			var header = createTextNode('div', 'sonyra-pages-table-head');
			header.appendChild(createTextNode('div', 'sonyra-pages-table-heading sonyra-pages-table-heading--page', t('manager.pages.table.column_page')));
			header.appendChild(createTextNode('div', 'sonyra-pages-table-heading sonyra-pages-table-heading--menu', t('manager.pages.table.column_menu')));
			header.appendChild(createTextNode('div', 'sonyra-pages-table-heading sonyra-pages-table-heading--updated', t('manager.pages.table.column_updated')));
			header.appendChild(createTextNode('div', 'sonyra-pages-table-heading sonyra-pages-table-heading--actions', t('manager.pages.table.column_actions')));
			listWrap.appendChild(header);
			var body = createTextNode('div', 'sonyra-pages-table-body');
			pages.forEach(function (page) {
				var row = createTextNode('div', 'sonyra-pages-table-row');
				var pageCell = renderPageCell(page);
				var menuCell = renderMenuCell(page);

				pageCell.classList.add('sonyra-pages-table-cell', 'sonyra-pages-table-cell--page');
				menuCell.classList.add('sonyra-pages-table-cell', 'sonyra-pages-table-cell--menu');
				row.appendChild(pageCell);
				row.appendChild(menuCell);
				row.appendChild(renderUpdatedCell(page));
				row.appendChild(renderActionsCell(page));
				if (isPendingPageAction(page.id)) {
					row.classList.add('is-pending');
				}
				body.appendChild(row);
			});
			listWrap.appendChild(body);
		}

		card.appendChild(listWrap);
		return card;
	}

	function renderPagesGridContentOnly() {
		var screen = pagesRoot ? pagesRoot.querySelector('[data-pages-table-screen]') : null;

		if (!screen || pagesState.mode === 'sections' || pagesState.mode === 'widgets' || pagesState.mode === 'popups') {
			return;
		}

		var nextCard = buildPagesGridContent();
		var currentCard = screen.querySelector('[data-pages-grid-card]');

		if (!currentCard) {
			renderPagesGrid();
			return;
		}

		currentCard.parentNode.replaceChild(nextCard, currentCard);
	}

	function renderPagesGrid() {
		var screen = pagesRoot.querySelector('[data-pages-table-screen]');
		var list = createTextNode('div', 'sonyra-pages-list');
		var toolbar = createTextNode('div', 'sonyra-pages-toolbar-panel');
		var searchWrap = createTextNode('label', 'sonyra-pages-search');
		var searchIcon = createTextNode('span', 'sonyra-pages-search-icon');
		var searchInput = document.createElement('input');
		var filtersWrap = createTextNode('div', 'sonyra-pages-toolbar-controls');
		var filterWrap = createTextNode('div', 'sonyra-pages-filter-wrap');
		var filterIcon = createTextNode('span', 'sonyra-pages-toolbar-static-icon');
		var filters = createTextNode('div', 'sonyra-pages-filter-group');
		var sortWrap = createTextNode('label', 'sonyra-pages-sort sonyra-pages-sort-control');
		var sortLabel = createTextNode('span', 'sonyra-pages-toolbar-static-icon');
		var sortSelect = document.createElement('select');

		if (!screen) {
			return;
		}

		clearNode(screen);
		screen.hidden = pagesState.mode === 'sections' || pagesState.mode === 'widgets' || pagesState.mode === 'popups';

		if (pagesState.mode === 'sections' || pagesState.mode === 'widgets' || pagesState.mode === 'popups') {
			return;
		}

		searchIcon.appendChild(clonePagesIcon('search'));
		searchWrap.appendChild(searchIcon);
		searchInput.type = 'search';
		searchInput.className = 'sonyra-pages-search-input';
		searchInput.value = pagesState.searchQuery;
		searchInput.placeholder = t('manager.pages.table.search_prompt');
		searchInput.setAttribute('data-pages-search', 'true');
		searchWrap.appendChild(searchInput);
		toolbar.appendChild(searchWrap);
		filterIcon.setAttribute('aria-hidden', 'true');
		filterIcon.appendChild(clonePagesIcon('filter'));

		[
			{ value: 'all', label: t('manager.pages.table.filter_all') },
			{ value: 'published', label: t('manager.pages.table.filter_published') },
			{ value: 'draft', label: t('manager.pages.table.filter_draft') },
			{ value: 'hidden', label: t('manager.pages.table.filter_hidden') },
			{ value: 'menu', label: t('manager.pages.table.filter_menu') }
		].forEach(function (filter) {
			var chip = createButton('sonyra-pages-filter-chip', filter.label);
			chip.setAttribute('data-pages-filter', filter.value);
			chip.setAttribute('aria-pressed', pagesState.statusFilter === filter.value ? 'true' : 'false');
			chip.classList.toggle('is-active', pagesState.statusFilter === filter.value);
			filters.appendChild(chip);
		});

		filterWrap.appendChild(filterIcon);
		filterWrap.appendChild(filters);
		sortLabel.setAttribute('aria-hidden', 'true');
		sortLabel.appendChild(clonePagesIcon('arrows-sort'));
		sortWrap.appendChild(sortLabel);
		sortSelect.className = 'sonyra-pages-sort-select';
		sortSelect.setAttribute('data-pages-sort', 'true');
		[
			{ value: 'recent', label: t('manager.pages.table.sort_recent') },
			{ value: 'created', label: t('manager.pages.table.sort_created') },
			{ value: 'az', label: t('manager.pages.table.sort_az') },
			{ value: 'za', label: t('manager.pages.table.sort_za') },
			{ value: 'menu', label: t('manager.pages.table.sort_menu') }
		].forEach(function (optionDef) {
			var option = document.createElement('option');
			option.value = optionDef.value;
			option.textContent = optionDef.label;
			sortSelect.appendChild(option);
		});
		sortSelect.value = pagesState.sort;
		sortWrap.appendChild(sortSelect);
		filtersWrap.appendChild(filterWrap);
		filtersWrap.appendChild(sortWrap);
		toolbar.appendChild(filtersWrap);
		list.appendChild(toolbar);

		list.appendChild(buildPagesGridContent());
		screen.appendChild(list);
	}

	function renderEmptyState(type) {
		var empty = createTextNode('div', 'sonyra-manager-pages-empty-state');
		var title;
		var description;
		var button;

		if (type === 'filtered') {
			title = t('manager.pages.empty_reset_title');
			description = t('manager.pages.empty_reset_description');
			button = createButton('sonyra-manager-pages-secondary', t('manager.pages.empty_reset_action'));
			button.setAttribute('data-pages-reset-filters', 'true');
		} else {
			title = t('manager.pages.empty_title');
			description = t('manager.pages.empty_description');
			button = createButton('sonyra-manager-pages-primary', t('manager.pages.actions.create'));
			button.setAttribute('data-pages-create', 'true');
		}

		empty.appendChild(createTextNode('strong', '', title));
		empty.appendChild(createTextNode('p', '', description));
		empty.appendChild(button);
		return empty;
	}

	function renderPagesLoadingState() {
		var loading = createTextNode('div', 'sonyra-manager-pages-empty-state sonyra-manager-pages-loading-state');
		var icon = createTextNode('span', 'sonyra-manager-notice__icon');

		icon.appendChild(clonePagesIcon('progress'));
		loading.appendChild(icon);
		loading.appendChild(createTextNode('strong', '', t('manager.pages.loading')));
		return loading;
	}

	function renderPagesErrorState() {
		var error = createTextNode('div', 'sonyra-manager-pages-empty-state');
		error.appendChild(createTextNode('strong', '', t('manager.pages.errors.load_failed')));
		return error;
	}

	function renderPagesTableScreen() {
		renderPagesToolbar();
		renderPagesHeaderBadges();
		renderPagesGrid();
		renderPagesEditor();
		renderPageModal();
	}

	function renderPages() {
		renderPagesTableScreen();
	}

	function fetchPagesCollection() {
		var pagesUrl = shell.getAttribute('data-pages-url') || '';

		if (!pagesRoot || !pagesUrl) {
			return Promise.reject(new Error('pages_unavailable'));
		}

		return fetch(pagesUrl, {
			method: 'GET',
			credentials: 'same-origin',
			headers: {
				'X-Sonyra-Pages-Nonce': shell.getAttribute('data-pages-nonce') || ''
			}
		}).then(function (response) {
			if (!response.ok) {
				throw new Error('load_failed');
			}

			return response.json();
		});
	}

	function loadPages() {
		pagesState.pagesStatus = 'loading';
		renderPages();

		fetchPagesCollection().then(function (data) {
			pagesState.items = Array.isArray(data.items) ? data.items.map(normalizePage) : [];
			pagesState.activeId = pagesState.items[0] ? pagesState.items[0].id : '';
			pagesState.loaded = true;
			pagesState.pagesStatus = 'loaded';
			renderPages();
		}).catch(function () {
			pagesState.loaded = true;
			pagesState.pagesStatus = 'error';
			setPagesMessage('error', t('manager.pages.errors.load_failed'));
			renderPages();
		});
	}

	function createPage() {
		pagesState.modalDraft = normalizePage({
			id: createId('page'),
			title: '',
			slug: '',
			status: 'draft',
			menu_order: (pagesState.items.length + 1) * 10,
			sections: []
		});
		pagesState.pageModalOpen = true;
		pagesState.historyModalOpen = false;
		pagesState.lastTrigger = document.activeElement;
		renderPageModal();
	}

	function renderPageModal() {
		if (!pagesRoot) {
			return;
		}

		var modalRoot = pagesRoot.querySelector('[data-pages-modal-root]');

		if (!modalRoot) {
			return;
		}

		clearNode(modalRoot);

		if (!pagesState.pageModalOpen && !pagesState.sectionLibraryModalOpen && !pagesState.blockLibraryModalOpen && !pagesState.widgetLibraryModalOpen && !pagesState.popupLibraryModalOpen && !pagesState.sectionModalOpen && !pagesState.blockModalOpen && !pagesState.widgetModalOpen && !pagesState.popupModalOpen && !pagesState.historyModalOpen) {
			if (!(window.isManagerDestructiveModalOpen && window.isManagerDestructiveModalOpen())) {
				document.body.classList.remove('sonyra-manager-modal-open');
			}
			return;
		}

		document.body.classList.add('sonyra-manager-modal-open');

		var overlay = createTextNode('div', 'sonyra-manager-pages-modal-overlay sonyra-manager-modal-overlay');
		var panel = createTextNode('div', 'sonyra-manager-pages-modal sonyra-manager-modal');
		var header = createTextNode('header', 'sonyra-manager-pages-modal-header sonyra-manager-modal__header');
		var icon = createTextNode('div', 'sonyra-manager-pages-modal-icon');
		var copy = createTextNode('div', 'sonyra-manager-pages-modal-copy');
		var title = createTextNode('h3', 'sonyra-manager-pages-modal-title');
		var description = createTextNode('p', 'sonyra-manager-pages-modal-description');
		var closeButton = createButton('sonyra-manager-pages-modal-close', '', 'x');
		var body = createTextNode('div', 'sonyra-manager-pages-modal-body sonyra-manager-modal__body');
		var footer = createTextNode('footer', 'sonyra-manager-pages-modal-footer sonyra-manager-modal__footer');

		overlay.setAttribute('data-pages-modal-overlay', 'true');
		panel.setAttribute('role', 'dialog');
		panel.setAttribute('aria-modal', 'true');
		panel.setAttribute('aria-labelledby', 'sonyra-pages-modal-title');
		closeButton.setAttribute('aria-label', t('manager.pages.modal.close'));
		closeButton.setAttribute('data-pages-close-modal', 'true');

		if (pagesState.historyModalOpen) {
			var historyPage = findPage(pagesState.historyTargetId);
			var historyItems = historyPage && Array.isArray(historyPage.history) ? historyPage.history : [];

			icon.appendChild(clonePagesIcon('history'));
			title.id = 'sonyra-pages-modal-title';
			title.textContent = t('manager.pages.history.title');
			description.textContent = t('manager.pages.history.description');
			copy.appendChild(title);
			copy.appendChild(description);

			if (!historyItems.length) {
				body.appendChild(createTextNode('strong', '', t('manager.pages.history.empty_title')));
				body.appendChild(createTextNode('p', 'sonyra-manager-pages-modal-description', t('manager.pages.history.empty_description')));
			} else {
				var historyList = createTextNode('div', 'sonyra-manager-pages-history-list');
				historyItems.forEach(function (eventItem) {
					var formatted = formatPageUpdatedAt(eventItem.at || '');
					var row = createTextNode('article', 'sonyra-manager-pages-history-item');
					row.appendChild(createTextNode('strong', 'sonyra-manager-pages-history-item-title', eventItem.label || ''));
					row.appendChild(createTextNode('time', 'sonyra-manager-pages-history-item-time', formatted.dateText + (formatted.timeText ? ' · ' + formatted.timeText : '')));
					if (eventItem.details) {
						row.appendChild(createTextNode('p', 'sonyra-manager-pages-history-item-details', eventItem.details));
					}
					historyList.appendChild(row);
				});
				body.appendChild(historyList);
			}

				footer.appendChild(createButton('sonyra-manager-pages-secondary', t('manager.pages.modal.close')));
				footer.lastChild.setAttribute('data-pages-close-modal', 'true');
		} else if (pagesState.sectionLibraryModalOpen) {
			icon.appendChild(clonePagesIcon('section'));
			title.id = 'sonyra-pages-modal-title';
			title.textContent = t('manager.pages.sections.add_modal_title');
			description.textContent = t('manager.pages.sections.add_modal_description');
			copy.appendChild(title);
			copy.appendChild(description);
			body.appendChild(buildSectionLibraryModalBody());
			footer.appendChild(createButton('sonyra-manager-pages-secondary', t('manager.pages.actions.cancel')));
			footer.lastChild.setAttribute('data-pages-close-modal', 'true');
		} else if (pagesState.blockLibraryModalOpen) {
			icon.appendChild(clonePagesIcon('components'));
			title.id = 'sonyra-pages-modal-title';
			title.textContent = t('manager.pages.blocks.add_modal.title');
			description.textContent = t('manager.pages.blocks.add_modal.description');
			copy.appendChild(title);
			copy.appendChild(description);
			body.appendChild(buildBlockLibraryModalBody());
			footer.appendChild(createButton('sonyra-manager-pages-secondary', t('manager.pages.actions.cancel')));
			footer.lastChild.setAttribute('data-pages-close-modal', 'true');
		} else if (pagesState.widgetLibraryModalOpen) {
			icon.appendChild(clonePagesIcon('circle-dot'));
			title.id = 'sonyra-pages-modal-title';
			title.textContent = t('manager.pages.widgets.add_modal.title');
			description.textContent = t('manager.pages.widgets.add_modal.description');
			copy.appendChild(title);
			copy.appendChild(description);
			body.appendChild(buildWidgetLibraryModalBody());
			footer.appendChild(createButton('sonyra-manager-pages-secondary', t('manager.pages.actions.cancel')));
			footer.lastChild.setAttribute('data-pages-close-modal', 'true');
		} else if (pagesState.popupLibraryModalOpen) {
			icon.appendChild(clonePagesIcon('info-circle'));
			title.id = 'sonyra-pages-modal-title';
			title.textContent = t('manager.pages.popups.add_modal.title');
			description.textContent = t('manager.pages.popups.add_modal.description');
			copy.appendChild(title);
			copy.appendChild(description);
			body.appendChild(buildPopupLibraryModalBody());
			footer.appendChild(createButton('sonyra-manager-pages-secondary', t('manager.pages.actions.cancel')));
			footer.lastChild.setAttribute('data-pages-close-modal', 'true');
		} else if (pagesState.sectionModalOpen && pagesState.sectionDraft) {
			icon.appendChild(clonePagesIcon('settings'));
			title.id = 'sonyra-pages-modal-title';
			title.textContent = t('manager.pages.sections.section_modal_title');
			description.textContent = t('manager.pages.sections.section_modal_description');
			copy.appendChild(title);
			copy.appendChild(description);
			body.appendChild(createNamedModalField('section', 'name', t('manager.pages.sections.section_name'), pagesState.sectionDraft.name || '', 'text'));
			body.appendChild(createReadonlyModalField(t('manager.pages.sections.section_type'), getSectionTypeLabel(pagesState.sectionDraft.type), t('manager.pages.sections.type_readonly')));
			footer.appendChild(createButton('sonyra-manager-pages-secondary', t('manager.pages.actions.cancel')));
			footer.lastChild.setAttribute('data-pages-close-modal', 'true');
			footer.appendChild(createButton('sonyra-manager-pages-primary', pagesState.saving ? t('manager.pages.actions.saving') : t('manager.pages.actions.save')));
			footer.lastChild.setAttribute('data-pages-save-section-modal', 'true');
			footer.lastChild.disabled = pagesState.saving;
		} else if (pagesState.blockModalOpen && pagesState.blockDraft) {
			icon.appendChild(clonePagesIcon('settings'));
			title.id = 'sonyra-pages-modal-title';
			title.textContent = t('manager.pages.blocks.settings.title');
			description.textContent = t('manager.pages.blocks.settings.description');
			copy.appendChild(title);
			copy.appendChild(description);
			body.appendChild(createNamedModalField('block', 'name', t('manager.pages.blocks.name_label'), pagesState.blockDraft.name || '', 'text'));
			body.appendChild(createReadonlyModalField(t('manager.pages.blocks.type_label'), getBlockTypeLabel(pagesState.blockDraft.type), t('manager.pages.sections.type_readonly')));
			getBlockFieldSchema(pagesState.blockDraft.type).forEach(function (field) {
				body.appendChild(createNamedModalField('block', field.key, field.label, pagesState.blockDraft[field.key] || '', field.type));
			});
			footer.appendChild(createButton('sonyra-manager-pages-secondary', t('manager.pages.actions.cancel')));
			footer.lastChild.setAttribute('data-pages-close-modal', 'true');
			footer.appendChild(createButton('sonyra-manager-pages-primary', pagesState.saving ? t('manager.pages.actions.saving') : t('manager.pages.actions.save')));
			footer.lastChild.setAttribute('data-pages-save-block-modal', 'true');
			footer.lastChild.disabled = pagesState.saving;
		} else if (pagesState.widgetModalOpen && pagesState.widgetDraft) {
			icon.appendChild(clonePagesIcon('settings'));
			title.id = 'sonyra-pages-modal-title';
			title.textContent = t('manager.pages.widgets.settings.title');
			description.textContent = t('manager.pages.widgets.settings.description');
			copy.appendChild(title);
			copy.appendChild(description);
			body.appendChild(createNamedModalField('widget', 'name', t('manager.pages.widgets.name_label'), pagesState.widgetDraft.name || '', 'text'));
			body.appendChild(createReadonlyModalField(t('manager.pages.widgets.type_label'), getWidgetTypeLabel(pagesState.widgetDraft.type), t('manager.pages.sections.type_readonly')));
			body.appendChild(createNamedSwitchField('widget', 'enabled', t('manager.pages.widgets.enabled_label'), pagesState.widgetDraft.enabled !== false));
			body.appendChild(createSelectModalField('widget', 'position', t('manager.pages.widgets.position_label'), pagesState.widgetDraft.position || 'bottom_right', [
				{ value: 'bottom_right', label: t('manager.pages.widgets.position_bottom_right') },
				{ value: 'bottom_left', label: t('manager.pages.widgets.position_bottom_left') },
				{ value: 'top_right', label: t('manager.pages.widgets.position_top_right') },
				{ value: 'top_left', label: t('manager.pages.widgets.position_top_left') }
			]));
			getWidgetFieldSchema(pagesState.widgetDraft.type).forEach(function (field) {
				body.appendChild(createNamedModalField('widget', field.key, field.label, pagesState.widgetDraft[field.key] || '', field.type));
			});
			footer.appendChild(createButton('sonyra-manager-pages-secondary', t('manager.pages.actions.cancel')));
			footer.lastChild.setAttribute('data-pages-close-modal', 'true');
			footer.appendChild(createButton('sonyra-manager-pages-primary', pagesState.saving ? t('manager.pages.actions.saving') : t('manager.pages.actions.save')));
			footer.lastChild.setAttribute('data-pages-save-widget-modal', 'true');
			footer.lastChild.disabled = pagesState.saving;
		} else if (pagesState.popupModalOpen && pagesState.popupDraft) {
			icon.appendChild(clonePagesIcon(getPopupIcon(pagesState.popupDraft.type)));
			title.id = 'sonyra-pages-modal-title';
			title.textContent = t('manager.pages.popups.settings.title');
			description.textContent = t('manager.pages.popups.settings.description');
			copy.appendChild(title);
			copy.appendChild(description);
			body.appendChild(createNamedModalField('popup', 'name', t('manager.pages.popups.name_label'), pagesState.popupDraft.name || '', 'text'));
			body.appendChild(createReadonlyModalField(t('manager.pages.popups.type_label'), getPopupTypeLabel(pagesState.popupDraft.type), t('manager.pages.sections.type_readonly')));
			body.appendChild(createNamedSwitchField('popup', 'enabled', t('manager.pages.popups.enabled_label'), pagesState.popupDraft.enabled !== false));
			body.appendChild(createSelectModalField('popup', 'trigger', t('manager.pages.popups.trigger_label'), pagesState.popupDraft.trigger || 'manual', [
				{ value: 'manual', label: t('manager.pages.popups.trigger_manual') },
				{ value: 'page_load', label: t('manager.pages.popups.trigger_page_load') },
				{ value: 'delay', label: t('manager.pages.popups.trigger_delay') },
				{ value: 'scroll', label: t('manager.pages.popups.trigger_scroll') },
				{ value: 'exit_intent', label: t('manager.pages.popups.trigger_exit_intent') },
				{ value: 'first_visit', label: t('manager.pages.popups.trigger_first_visit') }
			]));
			getPopupFieldSchema(pagesState.popupDraft.type).forEach(function (field) {
				body.appendChild(createNamedModalField('popup', field.key, field.label, pagesState.popupDraft[field.key] || '', field.type));
			});
			footer.appendChild(createButton('sonyra-manager-pages-secondary', t('manager.pages.actions.cancel')));
			footer.lastChild.setAttribute('data-pages-close-modal', 'true');
			footer.appendChild(createButton('sonyra-manager-pages-primary', pagesState.saving ? t('manager.pages.actions.saving') : t('manager.pages.actions.save')));
			footer.lastChild.setAttribute('data-pages-save-popup-modal', 'true');
			footer.lastChild.disabled = pagesState.saving;
		} else if (pagesState.modalDraft) {
			icon.appendChild(clonePagesIcon('settings'));
			title.id = 'sonyra-pages-modal-title';
			title.textContent = t('manager.pages.modal.page_title');
			description.textContent = t('manager.pages.modal.page_description');
			copy.appendChild(title);
			copy.appendChild(description);
			body.appendChild(createPageModalField('title', t('manager.pages.fields.title'), pagesState.modalDraft.title, 'text'));
			body.appendChild(createPageModalField('slug', t('manager.pages.fields.slug'), pagesState.modalDraft.slug, 'text'));
			body.appendChild(createPageModalField('status', t('manager.pages.fields.status'), pagesState.modalDraft.status, 'select'));
			body.appendChild(createPageModalField('menu_order', t('manager.pages.fields.menu_order'), String(pagesState.modalDraft.menu_order || 0), 'number'));
			body.appendChild(createPageSwitch('is_home', t('manager.pages.fields.is_home'), pagesState.modalDraft.is_home));
			body.appendChild(createPageSwitch('show_in_menu', t('manager.pages.fields.show_in_menu'), pagesState.modalDraft.show_in_menu, pagesState.modalDraft.status !== 'published'));
			body.appendChild(createPageModalField('menu_title', t('manager.pages.fields.menu_title'), pagesState.modalDraft.menu_title, 'text'));
			body.appendChild(createPageModalField('seo_title', t('manager.pages.fields.seo_title'), pagesState.modalDraft.seo_title, 'text'));
			body.appendChild(createPageModalField('seo_description', t('manager.pages.fields.seo_description'), pagesState.modalDraft.seo_description, 'textarea'));
			footer.appendChild(createButton('sonyra-manager-pages-secondary', t('manager.pages.actions.cancel')));
			footer.lastChild.setAttribute('data-pages-close-modal', 'true');
			footer.appendChild(createButton('sonyra-manager-pages-primary', pagesState.saving ? t('manager.pages.actions.saving') : t('manager.pages.actions.save')));
			footer.lastChild.setAttribute('data-pages-save-modal', 'true');
			footer.lastChild.disabled = pagesState.saving;
		}

		header.appendChild(icon);
		header.appendChild(copy);
		header.appendChild(closeButton);
		panel.appendChild(header);
		panel.appendChild(body);
		panel.appendChild(footer);
		overlay.appendChild(panel);
		modalRoot.appendChild(overlay);
	}

	function buildSectionLibraryModalBody() {
		var grid = createTextNode('div', 'sonyra-pages-section-library-grid');

		getSectionDefinitions().forEach(function (definition) {
			grid.appendChild(createSectionLibraryCard(definition));
		});

		return grid;
	}

	function buildBlockLibraryModalBody() {
		var grid = createTextNode('div', 'sonyra-pages-section-library-grid sonyra-pages-block-library-grid');

		getBlockDefinitions().forEach(function (definition) {
			grid.appendChild(createBlockLibraryCard(definition));
		});

		return grid;
	}

	function buildWidgetLibraryModalBody() {
		var grid = createTextNode('div', 'sonyra-pages-section-library-grid sonyra-pages-widget-library-grid');

		getWidgetDefinitions().forEach(function (definition) {
			grid.appendChild(createWidgetLibraryCard(definition));
		});

		return grid;
	}

	function buildPopupLibraryModalBody() {
		var grid = createTextNode('div', 'sonyra-pages-section-library-grid sonyra-pages-widget-library-grid sonyra-pages-popup-library-grid');

		getPopupDefinitions().forEach(function (definition) {
			grid.appendChild(createPopupLibraryCard(definition));
		});

		return grid;
	}

	function createSectionLibraryCard(definition) {
		var card = createTextNode('button', 'sonyra-pages-section-library-card');
		var icon = createTextNode('span', 'sonyra-pages-section-library-icon');
		var copy = createTextNode('span', 'sonyra-pages-section-library-copy');
		var category = getSectionCategoryLabel(definition.category);
		var isPending = pagesState.addingSection && pagesState.pendingSectionType === definition.type;

		card.type = 'button';
		card.setAttribute('data-pages-library-section', definition.type);
		card.disabled = pagesState.addingSection || pagesState.saving;
		card.setAttribute('aria-disabled', card.disabled ? 'true' : 'false');
		card.classList.toggle('is-pending', isPending);
		icon.appendChild(clonePagesIcon(definition.icon_key));
		copy.appendChild(createTextNode('strong', 'sonyra-pages-section-library-title', definition.label));
		copy.appendChild(createTextNode('span', 'sonyra-pages-section-library-description', definition.description));

		if (category) {
			copy.appendChild(createTextNode('span', 'sonyra-pages-section-library-category', category));
		}

		card.appendChild(icon);
		card.appendChild(copy);
		return card;
	}

	function createBlockLibraryCard(definition) {
		var card = createTextNode('button', 'sonyra-pages-section-library-card sonyra-pages-block-library-card');
		var icon = createTextNode('span', 'sonyra-pages-section-library-icon');
		var copy = createTextNode('span', 'sonyra-pages-section-library-copy');
		var category = getSectionCategoryLabel(definition.category);
		var isPending = pagesState.addingBlock && pagesState.pendingBlockType === definition.type;

		card.type = 'button';
		card.setAttribute('data-pages-library-block', definition.type);
		card.disabled = pagesState.addingBlock || pagesState.saving;
		card.setAttribute('aria-disabled', card.disabled ? 'true' : 'false');
		card.classList.toggle('is-pending', isPending);
		icon.appendChild(clonePagesIcon(definition.icon_key || definition.icon));
		copy.appendChild(createTextNode('strong', 'sonyra-pages-section-library-title', definition.label));
		copy.appendChild(createTextNode('span', 'sonyra-pages-section-library-description', definition.description));

		if (category) {
			copy.appendChild(createTextNode('span', 'sonyra-pages-section-library-category', category));
		}

		card.appendChild(icon);
		card.appendChild(copy);
		return card;
	}

	function createWidgetLibraryCard(definition) {
		var card = createTextNode('button', 'sonyra-pages-section-library-card sonyra-pages-widget-library-card');
		var icon = createTextNode('span', 'sonyra-pages-section-library-icon');
		var copy = createTextNode('span', 'sonyra-pages-section-library-copy');
		var category = getSectionCategoryLabel(definition.category);
		var isPending = pagesState.addingWidget && pagesState.pendingWidgetType === definition.type;

		card.type = 'button';
		card.setAttribute('data-pages-library-widget', definition.type);
		card.disabled = pagesState.addingWidget || pagesState.saving;
		card.setAttribute('aria-disabled', card.disabled ? 'true' : 'false');
		card.classList.toggle('is-pending', isPending);
		icon.appendChild(clonePagesIcon(definition.icon_key || definition.icon));
		copy.appendChild(createTextNode('strong', 'sonyra-pages-section-library-title', definition.label));
		copy.appendChild(createTextNode('span', 'sonyra-pages-section-library-description', definition.description));

		if (category) {
			copy.appendChild(createTextNode('span', 'sonyra-pages-section-library-category', category));
		}

		card.appendChild(icon);
		card.appendChild(copy);
		return card;
	}

	function createPopupLibraryCard(definition) {
		var card = createTextNode('button', 'sonyra-pages-section-library-card sonyra-pages-widget-library-card sonyra-pages-popup-library-card');
		var icon = createTextNode('span', 'sonyra-pages-section-library-icon');
		var copy = createTextNode('span', 'sonyra-pages-section-library-copy');
		var category = getSectionCategoryLabel(definition.category);
		var isPending = pagesState.addingPopup && pagesState.pendingPopupType === definition.type;

		card.type = 'button';
		card.setAttribute('data-pages-library-popup', definition.type);
		card.disabled = pagesState.addingPopup || pagesState.saving;
		card.setAttribute('aria-disabled', card.disabled ? 'true' : 'false');
		card.classList.toggle('is-pending', isPending);
		icon.appendChild(clonePagesIcon(definition.icon_key || definition.icon));
		copy.appendChild(createTextNode('strong', 'sonyra-pages-section-library-title', definition.label));
		copy.appendChild(createTextNode('span', 'sonyra-pages-section-library-description', definition.description));

		if (category) {
			copy.appendChild(createTextNode('span', 'sonyra-pages-section-library-category', category));
		}

		card.appendChild(icon);
		card.appendChild(copy);
		return card;
	}

	function createNamedModalField(scope, key, labelText, value, type) {
		var field = createTextNode('label', 'sonyra-manager-pages-field');
		var title = createTextNode('span', '', labelText);
		var control = type === 'textarea' ? document.createElement('textarea') : document.createElement('input');
		if (type === 'textarea') {
			control.rows = 4;
		} else {
			control.type = type || 'text';
		}
		control.value = value || '';
		control.setAttribute('data-pages-' + scope + '-modal-field', key);
		field.appendChild(title);
		field.appendChild(control);
		return field;
	}

	function createReadonlyModalField(labelText, valueText, hintText) {
		var field = createTextNode('div', 'sonyra-manager-pages-field sonyra-pages-modal-readonly-field');
		field.appendChild(createTextNode('span', '', labelText));
		field.appendChild(createTextNode('div', 'sonyra-pages-modal-readonly-value', valueText));
		if (hintText) {
			field.appendChild(createTextNode('p', 'sonyra-manager-pages-modal-description', hintText));
		}
		return field;
	}

	function createSelectModalField(scope, key, labelText, value, options) {
		var field = createTextNode('label', 'sonyra-manager-pages-field');
		var title = createTextNode('span', '', labelText);
		var control = document.createElement('select');

		(options || []).forEach(function (option) {
			var node = document.createElement('option');
			node.value = option.value;
			node.textContent = option.label;
			control.appendChild(node);
		});

		control.value = value || '';
		control.setAttribute('data-pages-' + scope + '-modal-field', key);
		field.appendChild(title);
		field.appendChild(control);
		return field;
	}

	function createNamedSwitchField(scope, key, labelText, active) {
		var field = createTextNode('div', 'sonyra-manager-pages-field sonyra-pages-modal-switch-field');
		field.appendChild(createTextNode('span', '', labelText));
		field.appendChild(createScopeSwitch(scope, key, active));
		return field;
	}

	function createPageModalField(key, labelText, value, type) {
		var field = createTextNode('label', 'sonyra-manager-pages-field');
		var title = createTextNode('span', '', labelText);
		var control;

		if (type === 'textarea') {
			control = document.createElement('textarea');
			control.rows = 3;
		} else if (type === 'select') {
			control = document.createElement('select');
			[
				{ value: 'draft', label: t('manager.pages.status.draft') },
				{ value: 'published', label: t('manager.pages.status.published') },
				{ value: 'hidden', label: t('manager.pages.status.hidden') }
			].forEach(function (optionData) {
				var option = document.createElement('option');
				option.value = optionData.value;
				option.textContent = optionData.label;
				control.appendChild(option);
			});
		} else {
			control = document.createElement('input');
			control.type = type;
		}

		control.value = value || '';
		control.setAttribute('data-pages-modal-field', key);
		field.appendChild(title);
		field.appendChild(control);
		return field;
	}

	function createPageSwitch(key, labelText, active, disabled) {
		var button = createButton('sonyra-manager-pages-switch', labelText);
		button.setAttribute('data-pages-modal-switch', key);
		button.setAttribute('aria-pressed', active ? 'true' : 'false');
		button.classList.toggle('sonyra-manager-pages-switch-active', active === true);
		button.disabled = disabled === true;
		button.insertBefore(createTextNode('span', 'sonyra-manager-pages-switch-control'), button.firstChild);
		return button;
	}

	function createScopeSwitch(scope, key, active) {
		var button = createButton('sonyra-manager-pages-switch', '');
		button.setAttribute('data-pages-' + scope + '-modal-switch', key);
		button.setAttribute('aria-pressed', active ? 'true' : 'false');
		button.classList.toggle('sonyra-manager-pages-switch-active', active === true);
		button.insertBefore(createTextNode('span', 'sonyra-manager-pages-switch-control'), button.firstChild);
		return button;
	}

	function updateModalDraftField(key, value) {
		if (!pagesState.modalDraft) {
			return;
		}

		if (key === 'menu_order') {
			pagesState.modalDraft[key] = Math.max(0, Math.min(999, Number(value || 0)));
		} else {
			pagesState.modalDraft[key] = value;
		}

		if (key === 'status' && value !== 'published') {
			pagesState.modalDraft.show_in_menu = false;
		}

		if (key === 'status') {
			renderPageModal();
		}
	}

	function updateSectionDraftField(key, value) {
		if (!pagesState.sectionDraft) {
			return;
		}

		pagesState.sectionDraft[key] = value;
	}

	function updateBlockDraftField(key, value) {
		if (!pagesState.blockDraft) {
			return;
		}

		pagesState.blockDraft[key] = value;
	}

	function updateWidgetDraftField(key, value) {
		if (!pagesState.widgetDraft) {
			return;
		}

		pagesState.widgetDraft[key] = key === 'position' ? getWidgetPosition(value) : value;
	}

	function updatePopupDraftField(key, value) {
		if (!pagesState.popupDraft) {
			return;
		}

		pagesState.popupDraft[key] = key === 'trigger' ? getPopupTrigger(value) : value;
	}

	function toggleModalDraftSwitch(key) {
		if (!pagesState.modalDraft) {
			return;
		}

		if (key === 'show_in_menu' && pagesState.modalDraft.status !== 'published') {
			return;
		}

		pagesState.modalDraft[key] = !pagesState.modalDraft[key];
		renderPageModal();
	}

	function closePageOverlay() {
		pagesState.pageModalOpen = false;
		pagesState.sectionLibraryModalOpen = false;
		pagesState.blockLibraryModalOpen = false;
		pagesState.widgetLibraryModalOpen = false;
		pagesState.popupLibraryModalOpen = false;
		pagesState.sectionModalOpen = false;
		pagesState.blockModalOpen = false;
		pagesState.widgetModalOpen = false;
		pagesState.popupModalOpen = false;
		pagesState.historyModalOpen = false;
		pagesState.deleteModalOpen = false;
		pagesState.modalDraft = null;
		pagesState.sectionDraft = null;
		pagesState.blockDraft = null;
		pagesState.widgetDraft = null;
		pagesState.popupDraft = null;
		pagesState.historyTargetId = '';
		pagesState.deleteTargetType = '';
		pagesState.deleteTargetId = '';
		pagesState.deleteTargetParentId = '';
		pagesState.addingSection = false;
		pagesState.pendingSectionType = '';
		pagesState.addingBlock = false;
		pagesState.pendingBlockType = '';
		pagesState.addingWidget = false;
		pagesState.pendingWidgetType = '';
		pagesState.addingPopup = false;
		pagesState.pendingPopupType = '';
		renderPageModal();

		if (pagesState.lastTrigger && typeof pagesState.lastTrigger.focus === 'function') {
			pagesState.lastTrigger.focus();
		}
	}

	function openSectionLibrary(trigger) {
		pagesState.sectionLibraryModalOpen = true;
		pagesState.blockLibraryModalOpen = false;
		pagesState.pageModalOpen = false;
		pagesState.sectionModalOpen = false;
		pagesState.blockModalOpen = false;
		pagesState.historyModalOpen = false;
		pagesState.deleteModalOpen = false;
		pagesState.lastTrigger = trigger || document.activeElement;
		renderPageModal();
	}

	function openWidgetLibrary(trigger) {
		pagesState.widgetLibraryModalOpen = true;
		pagesState.sectionLibraryModalOpen = false;
		pagesState.blockLibraryModalOpen = false;
		pagesState.sectionModalOpen = false;
		pagesState.blockModalOpen = false;
		pagesState.widgetModalOpen = false;
		pagesState.pageModalOpen = false;
		pagesState.lastTrigger = trigger || document.activeElement;
		renderPageModal();
	}

	function openPopupLibrary(trigger) {
		pagesState.popupLibraryModalOpen = true;
		pagesState.widgetLibraryModalOpen = false;
		pagesState.sectionLibraryModalOpen = false;
		pagesState.blockLibraryModalOpen = false;
		pagesState.sectionModalOpen = false;
		pagesState.blockModalOpen = false;
		pagesState.widgetModalOpen = false;
		pagesState.popupModalOpen = false;
		pagesState.pageModalOpen = false;
		pagesState.lastTrigger = trigger || document.activeElement;
		renderPageModal();
	}

	function savePagesCollection(items, onSuccess, onError) {
		var pagesUrl = shell.getAttribute('data-pages-url') || '';
		var previousItems = pagesState.items.slice();

		if (!pagesRoot || !pagesUrl || pagesState.saving) {
			return Promise.resolve();
		}

		pagesState.saving = true;
		pagesState.items = items.map(normalizePage);
		renderPageModal();
		renderPagesGridContentOnly();
		renderPagesHeaderBadges();

		return fetch(pagesUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-Sonyra-Pages-Nonce': shell.getAttribute('data-pages-nonce') || ''
			},
			body: JSON.stringify({ items: items })
		}).then(function (response) {
			return response.json().catch(function () {
				return {};
			}).then(function (data) {
				if (!response.ok) {
					throw new Error(data.message || t('manager.pages.errors.save_failed'));
				}

				return data;
			});
		}).then(function (data) {
			pagesState.items = Array.isArray(data.items) ? data.items.map(normalizePage) : pagesState.items;

			if (typeof onSuccess === 'function') {
				onSuccess(data);
			}

			renderPages();
			return data;
		}).catch(function (error) {
			pagesState.items = previousItems.map(normalizePage);
			if (typeof onError === 'function') {
				onError(error);
			}
			setPagesMessage('error', error.message || t('manager.pages.errors.save_failed'));
			renderPages();
			throw error;
		}).finally(function () {
			pagesState.saving = false;
			clearPendingPageAction();
			renderPageModal();
			renderPagesGridContentOnly();
		});
	}

	function savePageModal() {
		if (!pagesState.modalDraft) {
			return;
		}

		var items = pagesState.items.map(normalizePage);
		var index = items.findIndex(function (page) {
			return page.id === pagesState.modalDraft.id;
		});
		var currentPage = index >= 0 ? items[index] : null;
		var nextPage = normalizePage({
			id: pagesState.modalDraft.id,
			title: pagesState.modalDraft.title,
			slug: pagesState.modalDraft.slug,
			status: pagesState.modalDraft.status,
			is_home: pagesState.modalDraft.is_home,
			show_in_menu: pagesState.modalDraft.show_in_menu,
			menu_title: pagesState.modalDraft.menu_title,
			menu_order: pagesState.modalDraft.menu_order,
			seo_title: pagesState.modalDraft.seo_title,
			seo_description: pagesState.modalDraft.seo_description,
			sections: index >= 0 ? items[index].sections : [],
			history: currentPage && Array.isArray(currentPage.history) ? currentPage.history : []
		});
		var successMessageKey = index >= 0 ? 'manager.pages.success.saved' : 'manager.pages.success.created';

		if (nextPage.is_home) {
			items = items.map(function (page) {
				page.is_home = false;
				return page;
			});
		}

		if (index >= 0 && currentPage && currentPage.slug !== nextPage.slug) {
			addPageHistoryEvent(nextPage, 'slug_changed');
		}

		addPageHistoryEvent(nextPage, index >= 0 ? 'updated' : 'created');

		if (index >= 0) {
			items[index] = nextPage;
		} else {
			items.push(nextPage);
		}

		setPagesMessage('loading', t(index >= 0 ? 'manager.pages.pending.saving' : 'manager.pages.pending.creating'));
		savePagesCollection(items, function () {
			pagesState.activeId = nextPage.id;
			setPagesMessage('success', t(successMessageKey));
			closePageOverlay();
		});
	}

	function deletePage(pageId) {
		var items = pagesState.items.filter(function (page) {
			return page.id !== pageId;
		});

		return savePagesCollection(items, function () {
			if (pagesState.activeId === pageId) {
				pagesState.activeId = items[0] ? items[0].id : '';
			}

			closePageOverlay();
		});
	}

	function copyPageUrl(pageId) {
		var page = findPage(pageId);

		if (!page) {
			return;
		}

		if (!navigator.clipboard || typeof navigator.clipboard.writeText !== 'function') {
			setPagesMessage('error', t('manager.pages.table.link_copy_error'));
			return;
		}

		navigator.clipboard.writeText(getPagesUrl(page)).then(function () {
			setPagesMessage('success', t('manager.pages.table.link_copied'));
		}).catch(function () {
			setPagesMessage('error', t('manager.pages.table.link_copy_error'));
		});
	}

	function makePageHome(pageId) {
		var items = pagesState.items.map(normalizePage).map(function (page) {
			page.is_home = page.id === pageId;
			return page;
		});

		items.forEach(function (page) {
			if (page.id === pageId) {
				addPageHistoryEvent(page, 'home');
			}
		});

		setPendingPageAction(pageId, 'make-home', 'manager.pages.pending.home');
		savePagesCollection(items, function () {
			pagesState.openDropdownId = '';
			setPagesMessage('success', t('manager.pages.success.home_updated'));
		});
	}

	function hidePage(pageId) {
		var items = pagesState.items.map(normalizePage).map(function (page) {
			if (page.id === pageId) {
				page.status = 'hidden';
				page.show_in_menu = false;
				addPageHistoryEvent(page, 'hidden');
			}

			return page;
		});

		setPendingPageAction(pageId, 'hide', 'manager.pages.pending.hiding');
		savePagesCollection(items, function () {
			pagesState.openDropdownId = '';
			setPagesMessage('success', t('manager.pages.success.hidden'));
		});
	}

	function publishPage(pageId) {
		var items = pagesState.items.map(normalizePage).map(function (page) {
			if (page.id === pageId) {
				page.status = 'published';
				addPageHistoryEvent(page, 'published');
			}

			return page;
		});

		setPendingPageAction(pageId, 'publish', 'manager.pages.pending.publishing');
		savePagesCollection(items, function () {
			pagesState.openDropdownId = '';
			setPagesMessage('success', t('manager.pages.success.published'));
		});
	}

	function duplicatePage(pageId) {
		var source = findPage(pageId);

		if (!source) {
			return;
		}

		var duplicatedPage = normalizePage({
			id: createId('page'),
			title: (source.title || '') + ' ' + t('manager.pages.duplicate.suffix'),
			slug: buildDuplicateSlug(source.slug || 'home'),
			status: 'draft',
			is_home: false,
			show_in_menu: false,
			menu_title: source.menu_title || '',
			menu_order: Number(source.menu_order || 0) + 10,
			seo_title: source.seo_title || '',
			seo_description: source.seo_description || '',
			sections: Array.isArray(source.sections) ? source.sections.map(normalizeSection) : [],
			history: [],
			created_at: new Date().toISOString(),
			updated_at: new Date().toISOString()
		});

		addPageHistoryEvent(duplicatedPage, 'duplicated');
		setPendingPageAction(pageId, 'duplicate', 'manager.pages.pending.duplicating');
		savePagesCollection(pagesState.items.map(normalizePage).concat([duplicatedPage]), function () {
			pagesState.activeId = duplicatedPage.id;
			pagesState.openDropdownId = '';
			setPagesMessage('success', t('manager.pages.success.duplicated'));
		});
	}

	function openPageActionsDropdown(pageId, trigger) {
		if (!pageId) {
			return;
		}

		pagesState.openDropdownId = pageId;
		pagesState.lastTrigger = trigger || document.activeElement;
		renderPages();
		window.requestAnimationFrame(function () {
			applyPageActionsDropdownPlacement(pageId);
		});
	}

	function togglePageActionsDropdown(pageId, trigger) {
		if (pagesState.openDropdownId === pageId) {
			closePageActionsDropdown();
			return;
		}

		openPageActionsDropdown(pageId, trigger);
	}

	function closePageActionsDropdown() {
		if (!pagesState.openDropdownId) {
			return;
		}

		pagesState.openDropdownId = '';
		renderPages();
	}

	function applyPageActionsDropdownPlacement(pageId) {
		var dropdown = pagesRoot ? pagesRoot.querySelector('[data-sonyra-page-actions-menu="' + pageId + '"]') : null;
		var trigger = pagesRoot ? pagesRoot.querySelector('[data-sonyra-page-actions-trigger="' + pageId + '"]') : null;
		var triggerRect;
		var viewportHeight;
		var minimumGap = 12;
		var preferredHeight;
		var spaceBelow;
		var spaceAbove;
		var placement = 'down';
		var maxHeight;

		if (!dropdown || !trigger || dropdown.hidden) {
			return;
		}

		dropdown.classList.remove('is-open-up', 'is-open-down');
		dropdown.style.maxHeight = '';

		triggerRect = trigger.getBoundingClientRect();
		viewportHeight = window.innerHeight;
		preferredHeight = Math.ceil(dropdown.scrollHeight || dropdown.getBoundingClientRect().height || 0);
		spaceBelow = viewportHeight - triggerRect.bottom - minimumGap;
		spaceAbove = triggerRect.top - minimumGap;

		if (spaceBelow >= preferredHeight) {
			placement = 'down';
			maxHeight = Math.min(preferredHeight, spaceBelow);
		} else if (spaceAbove >= preferredHeight) {
			placement = 'up';
			maxHeight = Math.min(preferredHeight, spaceAbove);
		} else if (spaceAbove > spaceBelow) {
			placement = 'up';
			maxHeight = Math.max(180, spaceAbove);
		} else {
			placement = 'down';
			maxHeight = Math.max(180, spaceBelow);
		}

		dropdown.classList.add(placement === 'up' ? 'is-open-up' : 'is-open-down');
		dropdown.style.maxHeight = String(Math.max(180, Math.floor(maxHeight))) + 'px';
		dropdown.style.overflowY = 'auto';
	}

	function updateActivePageField(key, value) {
		var page = getActivePage();

		if (!page) {
			return;
		}

		if (key === 'menu_order') {
			page[key] = Math.max(0, Math.min(999, Number(value || 0)));
		} else {
			page[key] = value;
		}

		if (key === 'status' && page.status !== 'published') {
			page.show_in_menu = false;
		}

		if (key === 'status') {
			renderPages();
		}
	}

	function toggleActivePageSetting(key) {
		var page = getActivePage();

		if (!page) {
			return;
		}

		if (key === 'show_in_menu' && page.status !== 'published') {
			return;
		}

		page[key] = !page[key];

		if (key === 'is_home' && page.is_home) {
			pagesState.items.forEach(function (item) {
				if (item.id !== page.id) {
					item.is_home = false;
				}
			});
		}

		renderPages();
	}

	function addSection(type) {
		var page = getActivePage();
		var definition = getSectionDefinition(type);
		var nextType = definition ? definition.type : 'text';
		var defaults = definition && definition.default_section ? definition.default_section : { type: nextType, name: getDefaultSectionTitle(nextType), blocks: [] };
		var nextSectionId = createId('section');
		var items;

		if (!page) {
			return;
		}

		if (pagesState.addingSection || pagesState.saving) {
			return;
		}

		items = pagesState.items.map(normalizePage).map(function (currentPage) {
			var usedTitles;
			if (currentPage.id !== pagesState.activeId) {
				return currentPage;
			}

			usedTitles = currentPage.sections.map(function (section) {
				return section.name || '';
			});
			currentPage.sections.push(normalizeSection({
				id: nextSectionId,
				type: nextType,
				name: buildUniqueTitle(defaults.name || getDefaultSectionTitle(nextType), usedTitles),
				blocks: Array.isArray(defaults.blocks) ? defaults.blocks : [],
				provider: defaults.provider || (definition ? definition.provider : 'core'),
				module_key: defaults.module_key || (definition ? definition.module_key : '')
			}));
			addPageHistoryEvent(currentPage, 'updated');
			return currentPage;
		});

		pagesState.addingSection = true;
		pagesState.pendingSectionType = nextType;
		pagesState.activeSectionId = nextSectionId;
		pagesState.openAddMenu = '';
		renderPageModal();
		setPagesMessage('loading', t('manager.pages.pending.saving'));
		savePagesCollection(items, function () {
			pagesState.addingSection = false;
			pagesState.pendingSectionType = '';
			setPagesMessage('success', t('manager.pages.success.section_added'));
			closePageOverlay();
		}, function () {
			pagesState.addingSection = false;
			pagesState.pendingSectionType = '';
			renderPageModal();
		});
	}

	function addBlock(type) {
		var page = getActivePage();
		var section = getActiveSection(page);
		var definition = getBlockDefinition(type);
		var nextBlock = null;
		var items;

		if (!page || !section || !definition) {
			return;
		}

		if (pagesState.addingBlock || pagesState.saving) {
			return;
		}

		items = pagesState.items.map(normalizePage).map(function (currentPage) {
			if (currentPage.id !== pagesState.activeId) {
				return currentPage;
			}

			currentPage.sections = currentPage.sections.map(function (currentSection) {
				var usedTitles;
				if (currentSection.id !== section.id) {
					return currentSection;
				}

				usedTitles = currentSection.blocks.map(function (block) {
					return block.name || '';
				});
				nextBlock = createBlockFromDefinition(definition, usedTitles);
				currentSection.blocks.push(nextBlock);
				return currentSection;
			});
			addPageHistoryEvent(currentPage, 'updated');
			return currentPage;
		});

		pagesState.addingBlock = true;
		pagesState.pendingBlockType = definition.type;
		pagesState.activeBlockId = nextBlock ? nextBlock.id : '';
		setPagesMessage('loading', t('manager.pages.pending.saving'));
		savePagesCollection(items, function () {
			pagesState.addingBlock = false;
			pagesState.pendingBlockType = '';
			pagesState.blockLibraryModalOpen = false;
			setPagesMessage('success', t('manager.pages.blocks.added_notice'));
		}, function () {
			pagesState.addingBlock = false;
			pagesState.pendingBlockType = '';
			pagesState.blockLibraryModalOpen = true;
			renderPageModal();
		});
	}

	function addWidget(type) {
		var page = getActivePage();
		var definition = getWidgetDefinition(type);
		var nextWidget = null;
		var items;

		if (!page || !definition) {
			return;
		}

		if (pagesState.addingWidget || pagesState.saving) {
			return;
		}

		items = pagesState.items.map(normalizePage).map(function (currentPage) {
			var usedTitles;

			if (currentPage.id !== pagesState.activeId) {
				return currentPage;
			}

			usedTitles = currentPage.widgets.map(function (widget) {
				return widget.name || '';
			});
			nextWidget = createWidgetFromDefinition(definition, usedTitles);
			currentPage.widgets.push(nextWidget);
			addPageHistoryEvent(currentPage, 'updated');
			return currentPage;
		});

		pagesState.addingWidget = true;
		pagesState.pendingWidgetType = definition.type;
		pagesState.activeWidgetId = nextWidget ? nextWidget.id : '';
		setPagesMessage('loading', t('manager.pages.pending.saving'));
		savePagesCollection(items, function () {
			pagesState.addingWidget = false;
			pagesState.pendingWidgetType = '';
			pagesState.widgetLibraryModalOpen = false;
			setPagesMessage('success', t('manager.pages.widgets.added_notice'));
		}, function () {
			pagesState.addingWidget = false;
			pagesState.pendingWidgetType = '';
			pagesState.widgetLibraryModalOpen = true;
			renderPageModal();
		});
	}

	function addPopup(type) {
		var page = getActivePage();
		var definition = getPopupDefinition(type);
		var nextPopup = null;
		var items;

		if (!page || !definition) {
			return;
		}

		if (pagesState.addingPopup || pagesState.saving) {
			return;
		}

		items = pagesState.items.map(normalizePage).map(function (currentPage) {
			var usedTitles;

			if (currentPage.id !== pagesState.activeId) {
				return currentPage;
			}

			usedTitles = currentPage.popups.map(function (popup) {
				return popup.name || '';
			});
			nextPopup = createPopupFromDefinition(definition, usedTitles);
			currentPage.popups.push(nextPopup);
			addPageHistoryEvent(currentPage, 'updated');
			return currentPage;
		});

		pagesState.addingPopup = true;
		pagesState.pendingPopupType = definition.type;
		pagesState.activePopupId = nextPopup ? nextPopup.id : '';
		setPagesMessage('loading', t('manager.pages.pending.saving'));
		savePagesCollection(items, function () {
			pagesState.addingPopup = false;
			pagesState.pendingPopupType = '';
			pagesState.popupLibraryModalOpen = false;
			setPagesMessage('success', t('manager.pages.popups.added_notice'));
		}, function () {
			pagesState.addingPopup = false;
			pagesState.pendingPopupType = '';
			pagesState.popupLibraryModalOpen = true;
			renderPageModal();
		});
	}

	function openBlockLibrary(trigger) {
		if (!getActiveSection()) {
			return;
		}

		pagesState.blockLibraryModalOpen = true;
		pagesState.sectionLibraryModalOpen = false;
		pagesState.sectionModalOpen = false;
		pagesState.blockModalOpen = false;
		pagesState.pageModalOpen = false;
		pagesState.lastTrigger = trigger || document.activeElement;
		renderPageModal();
	}

	function openWidgetSettings(widgetId, trigger) {
		var page = getActivePage();
		var widget;

		if (!page) {
			return;
		}

		widget = page.widgets.find(function (item) {
			return item.id === widgetId;
		}) || null;

		if (!widget) {
			return;
		}

		pagesState.widgetDraft = normalizeWidget(widget);
		pagesState.activeWidgetId = widgetId;
		pagesState.widgetModalOpen = true;
		pagesState.widgetLibraryModalOpen = false;
		pagesState.sectionModalOpen = false;
		pagesState.blockModalOpen = false;
		pagesState.pageModalOpen = false;
		pagesState.lastTrigger = trigger || document.activeElement;
		renderPageModal();
	}

	function openPopupSettings(popupId, trigger) {
		var page = getActivePage();
		var popup;

		if (!page) {
			return;
		}

		popup = page.popups.find(function (item) {
			return item.id === popupId;
		}) || null;

		if (!popup) {
			return;
		}

		pagesState.popupDraft = normalizePopup(popup);
		pagesState.activePopupId = popupId;
		pagesState.popupModalOpen = true;
		pagesState.popupLibraryModalOpen = false;
		pagesState.widgetModalOpen = false;
		pagesState.sectionModalOpen = false;
		pagesState.blockModalOpen = false;
		pagesState.pageModalOpen = false;
		pagesState.lastTrigger = trigger || document.activeElement;
		renderPageModal();
	}

	function openSectionSettings(sectionId, trigger) {
		var section = getSectionById(getActivePage(), sectionId);

		if (!section) {
			return;
		}

		pagesState.sectionDraft = normalizeSection(section);
		pagesState.sectionModalOpen = true;
		pagesState.blockModalOpen = false;
		pagesState.pageModalOpen = false;
		pagesState.lastTrigger = trigger || document.activeElement;
		renderPageModal();
	}

	function openBlockSettings(sectionId, blockId, trigger) {
		var section = getSectionById(getActivePage(), sectionId);
		var block;

		if (!section) {
			return;
		}

		block = section.blocks.find(function (item) {
			return item.id === blockId;
		}) || null;

		if (!block) {
			return;
		}

		pagesState.blockDraft = normalizeBlock(block, section.type);
		pagesState.blockDraft.sectionId = sectionId;
		pagesState.activeBlockId = blockId;
		pagesState.blockModalOpen = true;
		pagesState.blockLibraryModalOpen = false;
		pagesState.sectionModalOpen = false;
		pagesState.pageModalOpen = false;
		pagesState.lastTrigger = trigger || document.activeElement;
		renderPageModal();
	}

	function saveSectionModal() {
		var items;

		if (!pagesState.sectionDraft) {
			return;
		}

		items = pagesState.items.map(normalizePage).map(function (page) {
			if (page.id !== pagesState.activeId) {
				return page;
			}

			page.sections = ensureUniqueTitles(page.sections.map(function (section) {
				if (section.id === pagesState.sectionDraft.id) {
					section.name = pagesState.sectionDraft.name || getDefaultSectionTitle(section.type);
				}

				return section;
			}), function (section) {
				return section.name || getDefaultSectionTitle(section.type);
			});
			addPageHistoryEvent(page, 'updated');
			return page;
		});

		setPagesMessage('loading', t('manager.pages.pending.saving'));
		savePagesCollection(items, function () {
			setPagesMessage('success', t('manager.pages.success.saved'));
			closePageOverlay();
		});
	}

	function saveBlockModal() {
		var items;

		if (!pagesState.blockDraft || !pagesState.blockDraft.sectionId) {
			return;
		}

		items = pagesState.items.map(normalizePage).map(function (page) {
			if (page.id !== pagesState.activeId) {
				return page;
			}

			page.sections = page.sections.map(function (section) {
				if (section.id !== pagesState.blockDraft.sectionId) {
					return section;
				}

				section.blocks = ensureUniqueTitles(section.blocks.map(function (block) {
					if (block.id === pagesState.blockDraft.id) {
						Object.keys(pagesState.blockDraft).forEach(function (key) {
							if (key === 'sectionId') {
								return;
							}

							block[key] = pagesState.blockDraft[key];
						});
						block.name = pagesState.blockDraft.name || getDefaultBlockTitle(block.type);
					}

					return block;
				}), function (block) {
					return block.name || getDefaultBlockTitle(block.type);
				});
				return section;
			});
			addPageHistoryEvent(page, 'updated');
			return page;
		});

		setPagesMessage('loading', t('manager.pages.pending.saving'));
		savePagesCollection(items, function () {
			setPagesMessage('success', t('manager.pages.blocks.saved_notice'));
			closePageOverlay();
		});
	}

	function saveWidgetModal() {
		var items;

		if (!pagesState.widgetDraft) {
			return;
		}

		items = pagesState.items.map(normalizePage).map(function (page) {
			if (page.id !== pagesState.activeId) {
				return page;
			}

			page.widgets = ensureUniqueTitles(page.widgets.map(function (widget) {
				if (widget.id === pagesState.widgetDraft.id) {
					Object.keys(pagesState.widgetDraft).forEach(function (key) {
						widget[key] = pagesState.widgetDraft[key];
					});
					widget.name = pagesState.widgetDraft.name || getDefaultWidgetTitle(widget.type);
					widget.position = getWidgetPosition(pagesState.widgetDraft.position);
					widget.updated_at = new Date().toISOString();
				}

				return widget;
			}), function (widget) {
				return widget.name || getDefaultWidgetTitle(widget.type);
			});
			addPageHistoryEvent(page, 'updated');
			return page;
		});

		setPagesMessage('loading', t('manager.pages.pending.saving'));
		savePagesCollection(items, function () {
			setPagesMessage('success', t('manager.pages.widgets.saved_notice'));
			closePageOverlay();
		});
	}

	function savePopupModal() {
		var items;

		if (!pagesState.popupDraft) {
			return;
		}

		items = pagesState.items.map(normalizePage).map(function (page) {
			if (page.id !== pagesState.activeId) {
				return page;
			}

			page.popups = ensureUniqueTitles(page.popups.map(function (popup) {
				if (popup.id === pagesState.popupDraft.id) {
					Object.keys(pagesState.popupDraft).forEach(function (key) {
						popup[key] = pagesState.popupDraft[key];
					});
					popup.name = pagesState.popupDraft.name || getDefaultPopupTitle(popup.type);
					popup.trigger = getPopupTrigger(pagesState.popupDraft.trigger);
					popup.updated_at = new Date().toISOString();
				}

				return popup;
			}), function (popup) {
				return popup.name || getDefaultPopupTitle(popup.type);
			});
			addPageHistoryEvent(page, 'updated');
			return page;
		});

		setPagesMessage('loading', t('manager.pages.pending.saving'));
		savePagesCollection(items, function () {
			setPagesMessage('success', t('manager.pages.popups.saved_notice'));
			closePageOverlay();
		});
	}

	function openDeleteModal(type, targetId, parentId, trigger) {
		var deleteTargetName = getDeleteTargetName(type, targetId, parentId || '');

		pagesState.deleteTargetType = type;
		pagesState.deleteTargetId = targetId;
		pagesState.deleteTargetParentId = parentId || '';
		pagesState.deleteModalOpen = true;
		pagesState.pageModalOpen = false;
		pagesState.sectionLibraryModalOpen = false;
		pagesState.blockLibraryModalOpen = false;
		pagesState.popupLibraryModalOpen = false;
		pagesState.sectionModalOpen = false;
		pagesState.blockModalOpen = false;
		pagesState.popupModalOpen = false;
		pagesState.lastTrigger = trigger || document.activeElement;
		renderPageModal();

		openManagerDestructiveModal({
			entityType: type,
			entityName: deleteTargetName,
			title: getDeleteModalTitle(type, deleteTargetName),
			subtitle: getDestructiveModalSubtitleText(),
			bodyText: getDeleteModalBody(type, deleteTargetName),
			warningText: type === 'section' ? t('manager.pages.sections.delete_section_blocks_warning') : '',
			confirmLabel: t('manager.pages.actions.delete'),
			cancelLabel: t('manager.pages.actions.cancel'),
			closeLabel: t('manager.pages.modal.close'),
			loadingLabel: t('manager.pages.modal.deleting'),
			errorFallbackText: t('manager.pages.errors.save_failed'),
			trigger: pagesState.lastTrigger,
			onClose: function () {
				closePageOverlay();
			},
			onConfirm: getDeleteConfirmAction(type, targetId, parentId || '')
		});
	}

	function deleteSection(sectionId) {
		var items = pagesState.items.map(normalizePage).map(function (page) {
			if (page.id !== pagesState.activeId) {
				return page;
			}

			page.sections = page.sections.filter(function (section) {
				return section.id !== sectionId;
			});
			addPageHistoryEvent(page, 'updated');
			return page;
		});

		setPagesMessage('loading', t('manager.pages.pending.saving'));
		return savePagesCollection(items, function () {
			var activePage = getActivePage();
			var nextSection = activePage && activePage.sections[0] ? activePage.sections[0] : null;
			pagesState.activeSectionId = nextSection ? nextSection.id : '';
			setPagesMessage('success', t('manager.pages.success.saved'));
			closePageOverlay();
		});
	}

	function deleteBlock(sectionId, blockId) {
		var items = pagesState.items.map(normalizePage).map(function (page) {
			if (page.id !== pagesState.activeId) {
				return page;
			}

			page.sections = page.sections.map(function (section) {
				if (section.id === sectionId) {
					section.blocks = section.blocks.filter(function (block) {
						return block.id !== blockId;
					});
				}

				return section;
			});
			addPageHistoryEvent(page, 'updated');
			return page;
		});

		setPagesMessage('loading', t('manager.pages.pending.saving'));
		return savePagesCollection(items, function () {
			var activeSection = getSectionById(getActivePage(), sectionId);
			pagesState.activeBlockId = activeSection && activeSection.blocks[0] ? activeSection.blocks[0].id : '';
			setPagesMessage('success', t('manager.pages.blocks.deleted_notice'));
			closePageOverlay();
		});
	}

	function deleteWidget(widgetId) {
		var items = pagesState.items.map(normalizePage).map(function (page) {
			if (page.id !== pagesState.activeId) {
				return page;
			}

			page.widgets = page.widgets.filter(function (widget) {
				return widget.id !== widgetId;
			});
			addPageHistoryEvent(page, 'updated');
			return page;
		});

		setPagesMessage('loading', t('manager.pages.pending.saving'));
		return savePagesCollection(items, function () {
			var activePage = getActivePage();
			pagesState.activeWidgetId = activePage && activePage.widgets[0] ? activePage.widgets[0].id : '';
			setPagesMessage('success', t('manager.pages.widgets.deleted_notice'));
			closePageOverlay();
		});
	}

	function deletePopup(popupId) {
		var items = pagesState.items.map(normalizePage).map(function (page) {
			if (page.id !== pagesState.activeId) {
				return page;
			}

			page.popups = page.popups.filter(function (popup) {
				return popup.id !== popupId;
			});
			addPageHistoryEvent(page, 'updated');
			return page;
		});

		setPagesMessage('loading', t('manager.pages.pending.saving'));
		return savePagesCollection(items, function () {
			var activePage = getActivePage();
			pagesState.activePopupId = activePage && activePage.popups[0] ? activePage.popups[0].id : '';
			setPagesMessage('success', t('manager.pages.popups.deleted_notice'));
			closePageOverlay();
		});
	}

	function getDeleteModalTitle(type, targetName) {
		if (type === 'section') {
			return replacePlaceholder(t('manager.pages.sections.delete_section_title_named'), targetName);
		}

		if (type === 'block') {
			return replacePlaceholder(t('manager.pages.blocks.delete.title'), targetName);
		}

		if (type === 'widget') {
			return replacePlaceholder(t('manager.pages.widgets.delete.title'), targetName);
		}

		if (type === 'popup') {
			return replacePlaceholder(t('manager.pages.popups.delete.title'), targetName);
		}

		return replacePlaceholder(t('manager.pages.modal.delete_page_title_named'), targetName);
	}

	function getDeleteModalBody(type, targetName) {
		if (type === 'section') {
			return replacePlaceholder(t('manager.pages.sections.delete_section_line_two_named'), targetName);
		}

		if (type === 'block') {
			return replacePlaceholder(t('manager.pages.blocks.delete.body'), targetName);
		}

		if (type === 'widget') {
			return replacePlaceholder(t('manager.pages.widgets.delete.body'), targetName);
		}

		if (type === 'popup') {
			return replacePlaceholder(t('manager.pages.popups.delete.body'), targetName);
		}

		return replacePlaceholder(t('manager.pages.modal.delete_page_line_two_named'), targetName);
	}

	function getDeleteConfirmAction(type, targetId, parentId) {
		if (type === 'section') {
			return function () {
				return deleteSection(targetId);
			};
		}

		if (type === 'block') {
			return function () {
				return deleteBlock(parentId, targetId);
			};
		}

		if (type === 'widget') {
			return function () {
				return deleteWidget(targetId);
			};
		}

		if (type === 'popup') {
			return function () {
				return deletePopup(targetId);
			};
		}

		return function () {
			return deletePage(targetId);
		};
	}

	function reorderSections(sourceId, targetId) {
		reorderItems(function (page) {
			var nextSections = page.sections.slice();
			var fromIndex = nextSections.findIndex(function (section) { return section.id === sourceId; });
			var toIndex = nextSections.findIndex(function (section) { return section.id === targetId; });
			var moved;

			if (fromIndex < 0 || toIndex < 0 || fromIndex === toIndex) {
				return null;
			}

			moved = nextSections.splice(fromIndex, 1)[0];
			nextSections.splice(toIndex, 0, moved);
			page.sections = nextSections;
			return page;
		});
	}

	function reorderBlocks(sectionId, sourceId, targetId) {
		reorderItems(function (page) {
			var updated = false;

			page.sections = page.sections.map(function (section) {
				var nextBlocks;
				var fromIndex;
				var toIndex;
				var moved;

				if (section.id !== sectionId) {
					return section;
				}

				nextBlocks = section.blocks.slice();
				fromIndex = nextBlocks.findIndex(function (block) { return block.id === sourceId; });
				toIndex = nextBlocks.findIndex(function (block) { return block.id === targetId; });

				if (fromIndex < 0 || toIndex < 0 || fromIndex === toIndex) {
					return section;
				}

				moved = nextBlocks.splice(fromIndex, 1)[0];
				nextBlocks.splice(toIndex, 0, moved);
				section.blocks = nextBlocks;
				updated = true;
				return section;
			});

			return updated ? page : null;
		});
	}

	function reorderWidgets(sourceId, targetId) {
		reorderItems(function (page) {
			var nextWidgets = page.widgets.slice();
			var fromIndex = nextWidgets.findIndex(function (widget) { return widget.id === sourceId; });
			var toIndex = nextWidgets.findIndex(function (widget) { return widget.id === targetId; });
			var moved;

			if (fromIndex < 0 || toIndex < 0 || fromIndex === toIndex) {
				return null;
			}

			moved = nextWidgets.splice(fromIndex, 1)[0];
			nextWidgets.splice(toIndex, 0, moved);
			page.widgets = nextWidgets;
			return page;
		});
	}

	function reorderPopups(sourceId, targetId) {
		reorderItems(function (page) {
			var nextPopups = page.popups.slice();
			var fromIndex = nextPopups.findIndex(function (popup) { return popup.id === sourceId; });
			var toIndex = nextPopups.findIndex(function (popup) { return popup.id === targetId; });
			var moved;

			if (fromIndex < 0 || toIndex < 0 || fromIndex === toIndex) {
				return null;
			}

			moved = nextPopups.splice(fromIndex, 1)[0];
			nextPopups.splice(toIndex, 0, moved);
			page.popups = nextPopups;
			return page;
		});
	}

	function reorderItems(mutator) {
		var items = pagesState.items.map(normalizePage);
		var changed = false;
		var nextItems = items.map(function (page) {
			var nextPage = mutator(page);

			if (nextPage) {
				addPageHistoryEvent(nextPage, 'updated');
				changed = true;
				return nextPage;
			}

			return page;
		});

		if (!changed) {
			return;
		}

		pagesState.openAddMenu = '';
		savePagesCollection(nextItems, function () {
			setPagesMessage('success', t('manager.pages.success.saved'));
		});
	}

	function savePages() {
		var pagesUrl = shell.getAttribute('data-pages-url') || '';
		var saveButton = pagesRoot ? pagesRoot.querySelector('[data-pages-save]') : null;
		var activePage = getActivePage();

		if (!pagesRoot || !pagesUrl || pagesState.saving) {
			return;
		}

		if (activePage) {
			addPageHistoryEvent(activePage, 'updated');
		}

		pagesState.saving = true;
		setPagesMessage('loading', t('manager.pages.pending.saving'));

		if (saveButton) {
			saveButton.disabled = true;
			saveButton.querySelector('span:last-child').textContent = t('manager.pages.actions.saving');
		}

		fetch(pagesUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-Sonyra-Pages-Nonce': shell.getAttribute('data-pages-nonce') || ''
			},
			body: JSON.stringify({ items: pagesState.items })
		}).then(function (response) {
			return response.json().catch(function () {
				return {};
			}).then(function (data) {
				if (!response.ok) {
					throw new Error(data.message || t('manager.pages.errors.save_failed'));
				}

				return data;
			});
		}).then(function (data) {
			pagesState.items = Array.isArray(data.items) ? data.items.map(normalizePage) : pagesState.items;
			setPagesMessage('success', data.message || t('manager.pages.success.saved'));
			renderPages();
		}).catch(function (error) {
			setPagesMessage('error', error.message || t('manager.pages.errors.save_failed'));
			renderPages();
		}).finally(function () {
			pagesState.saving = false;

			if (saveButton) {
				saveButton.disabled = false;
				saveButton.querySelector('span:last-child').textContent = t('manager.pages.actions.save');
			}
		});
	}

	function openPage(page) {
		if (!page || !page.can_open) {
			setPagesMessage('error', t('manager.pages.errors.page_not_openable'));
			return;
		}

		window.open(getPagesUrl(page), '_blank', 'noopener');
	}

	function openPreviewPage(page) {
		if (!page) {
			return;
		}

		window.open(getPagePreviewUrl(page), '_blank', 'noopener');
	}

	function handlePagesClick(event) {
		var pagesAction = event.target.closest('[data-pages-create], [data-pages-open-site]');

		if (!pagesRoot || (!pagesRoot.contains(event.target) && !pagesAction)) {
			closePageActionsDropdown();
			return;
		}

		var createButton = event.target.closest('[data-pages-create]');
		var backButton = event.target.closest('[data-pages-back-to-table]');
		var resetFiltersButton = event.target.closest('[data-pages-reset-filters]');
		var filterChip = event.target.closest('[data-pages-filter]');
		var dropdownToggle = event.target.closest('[data-sonyra-page-actions-trigger]');
		var dropdownAction = event.target.closest('[data-sonyra-page-action]');
		var dropdownMenu = event.target.closest('[data-sonyra-page-actions-menu]');
		var pagesMessageClose = event.target.closest('[data-pages-message-close]');
		var closeModalButton = event.target.closest('[data-pages-close-modal]');
		var saveButton = event.target.closest('[data-pages-save]');
		var saveModalButton = event.target.closest('[data-pages-save-modal]');
		var editButton = event.target.closest('[data-pages-edit]');
		var openButton = event.target.closest('[data-pages-open]');
		var openCurrent = event.target.closest('[data-pages-open-current]');
		var openSite = event.target.closest('[data-pages-open-site]');
		var openSectionLibraryButton = event.target.closest('[data-pages-open-section-library]');
		var openBlockLibraryButton = event.target.closest('[data-pages-open-block-library]');
		var openWidgetLibraryButton = event.target.closest('[data-pages-open-widget-library]');
		var openPopupLibraryButton = event.target.closest('[data-pages-open-popup-library]');
		var switchButton = event.target.closest('[data-pages-switch]');
		var modalSwitchButton = event.target.closest('[data-pages-modal-switch]');
		var widgetModalSwitchButton = event.target.closest('[data-pages-widget-modal-switch]');
		var popupModalSwitchButton = event.target.closest('[data-pages-popup-modal-switch]');
		var addMenuTrigger = event.target.closest('[data-pages-add-menu-trigger]');
		var addSectionButton = event.target.closest('[data-pages-add-section]');
		var addBlockButton = event.target.closest('[data-pages-add-block]');
		var sectionLibraryCard = event.target.closest('[data-pages-library-section]');
		var blockLibraryCard = event.target.closest('[data-pages-library-block]');
		var widgetLibraryCard = event.target.closest('[data-pages-library-widget]');
		var popupLibraryCard = event.target.closest('[data-pages-library-popup]');
		var sectionSelectButton = event.target.closest('[data-pages-section-select]');
		var blockSelectButton = event.target.closest('[data-pages-block-select]');
		var widgetSelectButton = event.target.closest('[data-pages-widget-select]');
		var popupSelectButton = event.target.closest('[data-pages-popup-select]');
		var sectionSettingsButton = event.target.closest('[data-pages-section-settings]');
		var sectionDeleteButton = event.target.closest('[data-pages-section-delete]');
		var blockSettingsButton = event.target.closest('[data-pages-block-settings]');
		var blockDeleteButton = event.target.closest('[data-pages-block-delete]');
		var widgetSettingsButton = event.target.closest('[data-pages-widget-settings]');
		var widgetDeleteButton = event.target.closest('[data-pages-widget-delete]');
		var popupSettingsButton = event.target.closest('[data-pages-popup-settings]');
		var popupDeleteButton = event.target.closest('[data-pages-popup-delete]');
		var saveSectionModalButton = event.target.closest('[data-pages-save-section-modal]');
		var saveBlockModalButton = event.target.closest('[data-pages-save-block-modal]');
		var saveWidgetModalButton = event.target.closest('[data-pages-save-widget-modal]');
		var savePopupModalButton = event.target.closest('[data-pages-save-popup-modal]');

		if (pagesMessageClose) {
			event.preventDefault();
			setPagesMessage('', '');
			return;
		}

		if (dropdownToggle) {
			event.preventDefault();
			event.stopPropagation();
			togglePageActionsDropdown(dropdownToggle.getAttribute('data-sonyra-page-actions-trigger') || '', dropdownToggle);
			return;
		}

		if (dropdownAction) {
			event.preventDefault();
			event.stopPropagation();

			if (dropdownAction.disabled || dropdownAction.classList.contains('is-disabled') || dropdownAction.getAttribute('aria-disabled') === 'true') {
				return;
			}

			handleDropdownAction(dropdownAction);
			return;
		}

		if (!dropdownMenu) {
			closePageActionsDropdown();
		}

		if (createButton) {
			event.preventDefault();
			createPage();
			} else if (backButton) {
				event.preventDefault();
				pagesState.mode = 'table';
				pagesState.openAddMenu = '';
				renderPages();
		} else if (resetFiltersButton) {
			event.preventDefault();
			pagesState.searchQuery = '';
			pagesState.statusFilter = 'all';
			pagesState.sort = 'recent';
			renderPages();
		} else if (filterChip) {
			event.preventDefault();
			pagesState.statusFilter = filterChip.getAttribute('data-pages-filter') || 'all';
			renderPages();
		} else if (closeModalButton) {
			event.preventDefault();
			closePageOverlay();
		} else if (saveModalButton) {
			event.preventDefault();
			savePageModal();
		} else if (saveButton) {
			event.preventDefault();
			savePages();
		} else if (editButton) {
			event.preventDefault();
			pagesState.activeId = editButton.getAttribute('data-pages-edit') || '';
			renderPages();
		} else if (openButton) {
			event.preventDefault();
			openPage(findPage(openButton.getAttribute('data-pages-open') || ''));
		} else if (openCurrent) {
			event.preventDefault();
			openPage(getActivePage());
		} else if (openSite) {
			event.preventDefault();
			openPage(pagesState.items.find(function (page) {
				return page.can_open && page.is_home;
			}) || pagesState.items.find(function (page) {
				return page.can_open;
			}));
		} else if (openSectionLibraryButton) {
			event.preventDefault();
			openSectionLibrary(openSectionLibraryButton);
		} else if (openBlockLibraryButton) {
			event.preventDefault();
			openBlockLibrary(openBlockLibraryButton);
		} else if (openWidgetLibraryButton) {
			event.preventDefault();
			openWidgetLibrary(openWidgetLibraryButton);
		} else if (openPopupLibraryButton) {
			event.preventDefault();
			openPopupLibrary(openPopupLibraryButton);
		} else if (switchButton) {
			event.preventDefault();
			toggleActivePageSetting(switchButton.getAttribute('data-pages-switch') || '');
		} else if (modalSwitchButton) {
			event.preventDefault();
			toggleModalDraftSwitch(modalSwitchButton.getAttribute('data-pages-modal-switch') || '');
		} else if (widgetModalSwitchButton) {
			event.preventDefault();
			if (pagesState.widgetDraft) {
				pagesState.widgetDraft[widgetModalSwitchButton.getAttribute('data-pages-widget-modal-switch') || 'enabled'] = !(pagesState.widgetDraft[widgetModalSwitchButton.getAttribute('data-pages-widget-modal-switch') || 'enabled'] !== false);
				renderPageModal();
			}
		} else if (popupModalSwitchButton) {
			event.preventDefault();
			if (pagesState.popupDraft) {
				pagesState.popupDraft[popupModalSwitchButton.getAttribute('data-pages-popup-modal-switch') || 'enabled'] = !(pagesState.popupDraft[popupModalSwitchButton.getAttribute('data-pages-popup-modal-switch') || 'enabled'] !== false);
				renderPageModal();
			}
		} else if (addMenuTrigger) {
			event.preventDefault();
			pagesState.openAddMenu = pagesState.openAddMenu === (addMenuTrigger.getAttribute('data-pages-add-menu-trigger') || '') ? '' : (addMenuTrigger.getAttribute('data-pages-add-menu-trigger') || '');
			renderPagesEditor();
		} else if (sectionLibraryCard) {
			event.preventDefault();
			if (sectionLibraryCard.disabled || sectionLibraryCard.getAttribute('aria-disabled') === 'true') {
				return;
			}
			addSection(sectionLibraryCard.getAttribute('data-pages-library-section') || 'text');
		} else if (blockLibraryCard) {
			event.preventDefault();
			if (blockLibraryCard.disabled || blockLibraryCard.getAttribute('aria-disabled') === 'true') {
				return;
			}
			addBlock(blockLibraryCard.getAttribute('data-pages-library-block') || 'text');
		} else if (widgetLibraryCard) {
			event.preventDefault();
			if (widgetLibraryCard.disabled || widgetLibraryCard.getAttribute('aria-disabled') === 'true') {
				return;
			}
			addWidget(widgetLibraryCard.getAttribute('data-pages-library-widget') || '');
		} else if (popupLibraryCard) {
			event.preventDefault();
			if (popupLibraryCard.disabled || popupLibraryCard.getAttribute('aria-disabled') === 'true') {
				return;
			}
			addPopup(popupLibraryCard.getAttribute('data-pages-library-popup') || '');
		} else if (widgetSettingsButton) {
			event.preventDefault();
			event.stopPropagation();
			openWidgetSettings(widgetSettingsButton.getAttribute('data-pages-widget-settings') || '', widgetSettingsButton);
		} else if (widgetDeleteButton) {
			event.preventDefault();
			event.stopPropagation();
			openDeleteModal('widget', widgetDeleteButton.getAttribute('data-pages-widget-delete') || '', '', widgetDeleteButton);
		} else if (popupSettingsButton) {
			event.preventDefault();
			event.stopPropagation();
			openPopupSettings(popupSettingsButton.getAttribute('data-pages-popup-settings') || '', popupSettingsButton);
		} else if (popupDeleteButton) {
			event.preventDefault();
			event.stopPropagation();
			openDeleteModal('popup', popupDeleteButton.getAttribute('data-pages-popup-delete') || '', '', popupDeleteButton);
		} else if (blockSettingsButton) {
			event.preventDefault();
			event.stopPropagation();
			openBlockSettings(blockSettingsButton.getAttribute('data-pages-section-id') || '', blockSettingsButton.getAttribute('data-pages-block-settings') || '', blockSettingsButton);
		} else if (blockDeleteButton) {
			event.preventDefault();
			event.stopPropagation();
			openDeleteModal('block', blockDeleteButton.getAttribute('data-pages-block-delete') || '', blockDeleteButton.getAttribute('data-pages-section-id') || '', blockDeleteButton);
		} else if (sectionSettingsButton) {
			event.preventDefault();
			event.stopPropagation();
			openSectionSettings(sectionSettingsButton.getAttribute('data-pages-section-settings') || '', sectionSettingsButton);
		} else if (sectionDeleteButton) {
			event.preventDefault();
			event.stopPropagation();
			openDeleteModal('section', sectionDeleteButton.getAttribute('data-pages-section-delete') || '', '', sectionDeleteButton);
		} else if (addSectionButton) {
			event.preventDefault();
			addSection(addSectionButton.getAttribute('data-pages-add-section') || 'text');
		} else if (addBlockButton) {
			event.preventDefault();
			addBlock(addBlockButton.getAttribute('data-pages-add-block') || 'text');
		} else if (event.target.closest('[data-pages-help-tooltip-trigger]')) {
			var helpTrigger = event.target.closest('[data-pages-help-tooltip-trigger]');
			event.preventDefault();
			if (pagesState.openHelpTooltip === (helpTrigger.getAttribute('data-pages-help-tooltip-trigger') || '') && pagesState.openHelpTooltipPinned) {
				closeHelpTooltip();
			} else {
				openHelpTooltip(
					helpTrigger.getAttribute('data-pages-help-tooltip-trigger') || '',
					helpTrigger.getAttribute('data-sonyra-help-trigger') || '',
					true
				);
			}
		} else if (sectionSelectButton) {
			if (event.target.closest('[data-pages-drag-handle], .sonyra-pages-card-actions')) {
				return;
			}
			event.preventDefault();
			pagesState.activeSectionId = sectionSelectButton.getAttribute('data-pages-section-select') || '';
			pagesState.activeBlockId = '';
			renderPagesEditor();
		} else if (blockSelectButton) {
			if (event.target.closest('[data-pages-drag-handle], .sonyra-pages-card-actions')) {
				return;
			}
			event.preventDefault();
			pagesState.activeBlockId = blockSelectButton.getAttribute('data-pages-block-select') || '';
			renderPagesEditor();
		} else if (widgetSelectButton) {
			if (event.target.closest('[data-pages-drag-handle], .sonyra-pages-card-actions')) {
				return;
			}
			event.preventDefault();
			pagesState.activeWidgetId = widgetSelectButton.getAttribute('data-pages-widget-select') || '';
			renderPagesEditor();
		} else if (popupSelectButton) {
			if (event.target.closest('[data-pages-drag-handle], .sonyra-pages-card-actions')) {
				return;
			}
			event.preventDefault();
			pagesState.activePopupId = popupSelectButton.getAttribute('data-pages-popup-select') || '';
			renderPagesEditor();
		} else if (saveSectionModalButton) {
			event.preventDefault();
			saveSectionModal();
		} else if (saveBlockModalButton) {
			event.preventDefault();
			saveBlockModal();
		} else if (saveWidgetModalButton) {
			event.preventDefault();
			saveWidgetModal();
		} else if (savePopupModalButton) {
			event.preventDefault();
			savePopupModal();
		}
	}

	function handleDropdownAction(button) {
		if (button.classList.contains('is-disabled') || button.disabled || button.getAttribute('aria-disabled') === 'true') {
			return;
		}

		var action = button.getAttribute('data-sonyra-page-action') || '';
		var pageId = button.getAttribute('data-sonyra-page-id') || '';
		var page = findPage(pageId);

		if (!page) {
			return;
		}

		if (action === 'settings') {
			pagesState.openDropdownId = '';
			pagesState.modalDraft = normalizePage(page);
			pagesState.pageModalOpen = true;
			pagesState.lastTrigger = button;
			renderPages();
			return;
		}

			if (action === 'sections') {
				pagesState.openDropdownId = '';
				pagesState.activeId = pageId;
				pagesState.activeSectionId = page.sections[0] ? page.sections[0].id : '';
				pagesState.mode = 'sections';
				renderPages();
				return;
		}

		if (action === 'widgets') {
			pagesState.openDropdownId = '';
			pagesState.activeId = pageId;
			pagesState.activeWidgetId = page.widgets && page.widgets[0] ? page.widgets[0].id : '';
			pagesState.mode = 'widgets';
			renderPages();
			return;
		}

		if (action === 'popups') {
			pagesState.openDropdownId = '';
			pagesState.activeId = pageId;
			pagesState.activePopupId = page.popups && page.popups[0] ? page.popups[0].id : '';
			pagesState.mode = 'popups';
			renderPages();
			return;
		}

		if (action === 'open') {
			closePageActionsDropdown();
			openPage(page);
			return;
		}

		if (action === 'preview') {
			closePageActionsDropdown();
			openPreviewPage(page);
			return;
		}

		if (action === 'copy') {
			closePageActionsDropdown();
			copyPageUrl(pageId);
			renderPages();
			return;
		}

		if (action === 'make-home') {
			closePageActionsDropdown();
			makePageHome(pageId);
			return;
		}

		if (action === 'history') {
			pagesState.openDropdownId = '';
			pagesState.historyTargetId = pageId;
			pagesState.historyModalOpen = true;
			pagesState.lastTrigger = button;
			renderPageModal();
			renderPagesGridContentOnly();
			return;
		}

		if (action === 'duplicate') {
			closePageActionsDropdown();
			duplicatePage(pageId);
			return;
		}

		if (action === 'hide') {
			closePageActionsDropdown();
			hidePage(pageId);
			return;
		}

		if (action === 'publish') {
			closePageActionsDropdown();
			publishPage(pageId);
			return;
		}

		if (action === 'delete') {
			pagesState.openDropdownId = '';
			openDeleteModal('page', pageId, '', button);
			renderPages();
		}
	}

	function handlePagesInput(event) {
		if (!pagesRoot || !pagesRoot.contains(event.target)) {
			return;
		}

		var searchField = event.target.closest('[data-pages-search]');
		var modalField = event.target.closest('[data-pages-modal-field]');
		var sectionModalField = event.target.closest('[data-pages-section-modal-field]');
		var blockModalField = event.target.closest('[data-pages-block-modal-field]');
		var widgetModalField = event.target.closest('[data-pages-widget-modal-field]');
		var popupModalField = event.target.closest('[data-pages-popup-modal-field]');

		if (searchField) {
			pagesState.searchQuery = searchField.value || '';
			renderPagesGridContentOnly();
		} else if (modalField) {
			updateModalDraftField(modalField.getAttribute('data-pages-modal-field') || '', modalField.value);
		} else if (sectionModalField) {
			updateSectionDraftField(sectionModalField.getAttribute('data-pages-section-modal-field') || '', sectionModalField.value);
		} else if (blockModalField) {
			updateBlockDraftField(blockModalField.getAttribute('data-pages-block-modal-field') || '', blockModalField.value);
		} else if (widgetModalField) {
			updateWidgetDraftField(widgetModalField.getAttribute('data-pages-widget-modal-field') || '', widgetModalField.value);
		} else if (popupModalField) {
			updatePopupDraftField(popupModalField.getAttribute('data-pages-popup-modal-field') || '', popupModalField.value);
		}
	}

	function handlePagesChange(event) {
		var filterChip = event.target.closest('[data-pages-filter]');
		var sortControl = event.target.closest('[data-pages-sort]');
		var widgetModalField = event.target.closest('[data-pages-widget-modal-field]');
		var popupModalField = event.target.closest('[data-pages-popup-modal-field]');

		if (filterChip) {
			pagesState.statusFilter = filterChip.getAttribute('data-pages-filter') || 'all';
			renderPages();
		} else if (sortControl) {
			pagesState.sort = sortControl.value || 'recent';
			renderPages();
		} else if (widgetModalField) {
			updateWidgetDraftField(widgetModalField.getAttribute('data-pages-widget-modal-field') || '', widgetModalField.value);
		} else if (popupModalField) {
			updatePopupDraftField(popupModalField.getAttribute('data-pages-popup-modal-field') || '', popupModalField.value);
		}
	}

	function handlePagesDragStart(event) {
		var handle = event.target.closest('[data-pages-drag-handle]');

		if (!handle) {
			return;
		}

		if (handle.getAttribute('data-pages-drag-handle') === 'section') {
			pagesState.dragSectionId = handle.getAttribute('data-pages-drag-id') || '';
			pagesState.dragBlockId = '';
			pagesState.dragWidgetId = '';
			pagesState.dragPopupId = '';
		} else if (handle.getAttribute('data-pages-drag-handle') === 'widget') {
			pagesState.dragWidgetId = handle.getAttribute('data-pages-drag-id') || '';
			pagesState.dragSectionId = '';
			pagesState.dragBlockId = '';
			pagesState.dragPopupId = '';
		} else if (handle.getAttribute('data-pages-drag-handle') === 'popup') {
			pagesState.dragPopupId = handle.getAttribute('data-pages-drag-id') || '';
			pagesState.dragSectionId = '';
			pagesState.dragBlockId = '';
			pagesState.dragWidgetId = '';
		} else {
			pagesState.dragBlockId = handle.getAttribute('data-pages-drag-id') || '';
			pagesState.dragSectionId = handle.getAttribute('data-pages-drag-section-id') || '';
			pagesState.dragWidgetId = '';
			pagesState.dragPopupId = '';
		}

		if (event.dataTransfer) {
			event.dataTransfer.effectAllowed = 'move';
			event.dataTransfer.setData('text/plain', handle.getAttribute('data-pages-drag-id') || '');
		}
	}

	function handlePagesDragOver(event) {
		var sectionCard = event.target.closest('[data-pages-section-card]');
		var blockCard = event.target.closest('[data-pages-block-card]');
		var widgetCard = event.target.closest('[data-pages-widget-card]');
		var popupCard = event.target.closest('[data-pages-popup-card]');

		if ((pagesState.dragSectionId && sectionCard) || (pagesState.dragBlockId && blockCard) || (pagesState.dragWidgetId && widgetCard) || (pagesState.dragPopupId && popupCard)) {
			event.preventDefault();
		}
	}

	function handlePagesDrop(event) {
		var sectionCard = event.target.closest('[data-pages-section-card]');
		var blockCard = event.target.closest('[data-pages-block-card]');
		var widgetCard = event.target.closest('[data-pages-widget-card]');
		var popupCard = event.target.closest('[data-pages-popup-card]');
		var targetId;
		var targetSectionId;

		if (pagesState.dragSectionId && sectionCard) {
			event.preventDefault();
			targetId = sectionCard.getAttribute('data-pages-section-card') || '';
			if (targetId && targetId !== pagesState.dragSectionId) {
				reorderSections(pagesState.dragSectionId, targetId);
			}
		}

		if (pagesState.dragBlockId && blockCard) {
			event.preventDefault();
			targetId = blockCard.getAttribute('data-pages-block-card') || '';
			targetSectionId = blockCard.dataset.sectionId || '';
			if (targetId && targetSectionId && targetId !== pagesState.dragBlockId) {
				reorderBlocks(targetSectionId, pagesState.dragBlockId, targetId);
			}
		}

		if (pagesState.dragWidgetId && widgetCard) {
			event.preventDefault();
			targetId = widgetCard.getAttribute('data-pages-widget-card') || '';
			if (targetId && targetId !== pagesState.dragWidgetId) {
				reorderWidgets(pagesState.dragWidgetId, targetId);
			}
		}

		if (pagesState.dragPopupId && popupCard) {
			event.preventDefault();
			targetId = popupCard.getAttribute('data-pages-popup-card') || '';
			if (targetId && targetId !== pagesState.dragPopupId) {
				reorderPopups(pagesState.dragPopupId, targetId);
			}
		}

		pagesState.dragSectionId = '';
		pagesState.dragBlockId = '';
		pagesState.dragWidgetId = '';
		pagesState.dragPopupId = '';
	}

	function handlePagesDragEnd() {
		pagesState.dragSectionId = '';
		pagesState.dragBlockId = '';
		pagesState.dragWidgetId = '';
		pagesState.dragPopupId = '';
	}

	function openHelpTooltip(tooltipId, triggerId, pinned) {
		if (!tooltipId) {
			return;
		}

		pagesState.openHelpTooltip = tooltipId;
		pagesState.openHelpTooltipPinned = pinned === true;
		pagesState.openHelpTooltipTrigger = triggerId || '';
		renderPagesEditor();
	}

	function closeHelpTooltip() {
		if (!pagesState.openHelpTooltip) {
			return;
		}

		pagesState.openHelpTooltip = '';
		pagesState.openHelpTooltipPinned = false;
		pagesState.openHelpTooltipTrigger = '';
		renderPagesEditor();
	}

	SonyraPagesSection = {
		init: function () {
			initPagesI18n();
			initHelpEntries();
			initSectionDefinitions();
			initBlockDefinitions();
			initWidgetDefinitions();
			initPopupDefinitions();
			initPagesLocale();

			if (!pagesRoot) {
				return;
			}

				shell.addEventListener('click', handlePagesClick);
				pagesRoot.addEventListener('input', handlePagesInput);
				pagesRoot.addEventListener('change', handlePagesChange);
				pagesRoot.addEventListener('dragstart', handlePagesDragStart);
				pagesRoot.addEventListener('dragover', handlePagesDragOver);
				pagesRoot.addEventListener('drop', handlePagesDrop);
				pagesRoot.addEventListener('dragend', handlePagesDragEnd);
				pagesRoot.addEventListener('click', function (event) {
					var overlay = event.target.closest('[data-pages-modal-overlay]');

				if (overlay && event.target === overlay) {
					closePageOverlay();
				}
			});
			pagesRoot.addEventListener('wheel', function (event) {
				if (event.target.closest('[data-sonyra-page-actions-menu]')) {
					event.stopPropagation();
				}
			}, { passive: true });
			pagesRoot.addEventListener('touchmove', function (event) {
				if (event.target.closest('[data-sonyra-page-actions-menu]')) {
					event.stopPropagation();
				}
			}, { passive: true });
			document.addEventListener('click', function (event) {
				if (event.target.closest('[data-sonyra-page-actions-trigger]') || event.target.closest('[data-sonyra-page-actions-menu]')) {
					return;
				}

				if (!pagesRoot.contains(event.target) && !event.target.closest('[data-pages-open-site]')) {
					closePageActionsDropdown();
					if (pagesState.openAddMenu) {
						pagesState.openAddMenu = '';
						if (pagesState.mode === 'sections') {
							renderPagesEditor();
						}
					}
					return;
				}

				if (pagesRoot.contains(event.target)) {
					closePageActionsDropdown();
				}

				if (!event.target.closest('[data-pages-add-menu-trigger]') && !event.target.closest('[data-pages-add-menu]')) {
					pagesState.openAddMenu = '';
					if (pagesState.mode === 'sections') {
						renderPagesEditor();
					}
				}

				if (!event.target.closest('[data-pages-help-tooltip-trigger]') && !event.target.closest('[data-pages-help-popover]')) {
					closeHelpTooltip();
				}
			});
			pagesRoot.addEventListener('mouseover', function (event) {
				var trigger = event.target.closest('[data-pages-help-tooltip-trigger]');

				if (!trigger) {
					return;
				}

				var tooltipId = trigger.getAttribute('data-pages-help-tooltip-trigger') || '';

				if (pagesState.openHelpTooltip === tooltipId && !pagesState.openHelpTooltipPinned) {
					return;
				}

				openHelpTooltip(tooltipId, trigger.getAttribute('data-sonyra-help-trigger') || '', false);
			});
			pagesRoot.addEventListener('mouseout', function (event) {
				var tooltipWrap = event.target.closest('.sonyra-help-tooltip-wrap');
				var relatedPopover = event.relatedTarget && event.relatedTarget.closest ? event.relatedTarget.closest('[data-pages-help-popover]') : null;

				if (!tooltipWrap || tooltipWrap.contains(event.relatedTarget) || relatedPopover) {
					return;
				}

				if (!pagesState.openHelpTooltipPinned) {
					closeHelpTooltip();
				}
			});
			pagesRoot.addEventListener('focusin', function (event) {
				var trigger = event.target.closest('[data-pages-help-tooltip-trigger]');

				if (!trigger) {
					return;
				}

				var tooltipId = trigger.getAttribute('data-pages-help-tooltip-trigger') || '';

				if (pagesState.openHelpTooltip === tooltipId && pagesState.openHelpTooltipPinned) {
					return;
				}

				openHelpTooltip(tooltipId, trigger.getAttribute('data-sonyra-help-trigger') || '', true);
			});
			pagesRoot.addEventListener('focusout', function (event) {
				var tooltipWrap = event.target.closest('.sonyra-help-tooltip-wrap');
				var relatedPopover = event.relatedTarget && event.relatedTarget.closest ? event.relatedTarget.closest('[data-pages-help-popover]') : null;

				if (!tooltipWrap || tooltipWrap.contains(event.relatedTarget) || relatedPopover) {
					return;
				}

				if (!pagesState.openHelpTooltipPinned) {
					closeHelpTooltip();
				}
			});
			pagesRoot.addEventListener('mouseout', function (event) {
				var popover = event.target.closest('[data-pages-help-popover]');

				if (!popover || popover.contains(event.relatedTarget)) {
					return;
				}

				if (!pagesState.openHelpTooltipPinned) {
					closeHelpTooltip();
				}
			});
					document.addEventListener('keydown', function (event) {
						if (event.key === 'Escape') {
							if (pagesState.openHelpTooltip) {
								closeHelpTooltip();
								return;
							}
							if (window.isManagerDestructiveModalOpen && window.isManagerDestructiveModalOpen()) {
								closeManagerDestructiveModal();
								return;
							}
							if (pagesState.deleteModalOpen || pagesState.pageModalOpen || pagesState.sectionLibraryModalOpen || pagesState.blockLibraryModalOpen || pagesState.widgetLibraryModalOpen || pagesState.popupLibraryModalOpen || pagesState.sectionModalOpen || pagesState.blockModalOpen || pagesState.widgetModalOpen || pagesState.popupModalOpen || pagesState.historyModalOpen) {
								closePageOverlay();
								return;
							}

						pagesState.openAddMenu = '';
						closePageActionsDropdown();
					}
				});
			window.addEventListener('resize', function () {
				closePageActionsDropdown();
				if (pagesState.openHelpTooltip) {
					renderHelpPopover();
				}
			});

			if (pagesMain) {
				pagesMain.addEventListener('scroll', function () {
					closePageActionsDropdown();
					if (pagesState.openHelpTooltip) {
						renderHelpPopover();
					}
				});
			}
			},
				handleRouteChange: function (route) {
					if (route !== 'pages') {
						closeHelpTooltip();
						clearHelpPopoverScope('pages');
						return;
					}

					if (pagesRoot && !pagesState.loaded) {
						loadPages();
					} else if (pagesRoot) {
						renderPagesToolbar();
						renderPagesHeaderBadges();
					}
				},
		load: loadPages,
		render: renderPages
	};

	SonyraPagesSection.init();

	if (window.SonyraColorController && typeof window.SonyraColorController.init === 'function') {
		window.SonyraColorController.init();
	}

	routeButtons.forEach(function (button) {
		button.addEventListener('click', function () {
			setRoute(button.getAttribute('data-manager-route') || 'dashboard', true);
		});
	});

	if (toggleButton) {
		toggleButton.addEventListener('click', function () {
			setSidebarState(shell.getAttribute('data-sidebar-state') === 'expanded' ? 'collapsed' : 'expanded', true, true);
		});
	}

	if (logoButton) {
		logoButton.addEventListener('click', function () {
			setSidebarState(shell.getAttribute('data-sidebar-state') === 'expanded' ? 'collapsed' : 'expanded', true, true);
		});
	}

	if (backdrop) {
		backdrop.addEventListener('click', function () {
			setSidebarState('collapsed', false, true);
		});
	}

	if (logoutButton) {
		logoutButton.addEventListener('click', runLogout);
	}

	['mousedown', 'keydown', 'touchstart', 'focusin', 'input'].forEach(function (eventName) {
		document.addEventListener(eventName, function () {
			recordActivity(true);
		}, true);
	});

	['mousemove', 'scroll'].forEach(function (eventName) {
		document.addEventListener(eventName, function () {
			recordActivity(false);
		}, true);
	});

	window.addEventListener('focus', function () {
		recordActivity(true);
		verifySessionState(true);
	});

	document.addEventListener('visibilitychange', function () {
		if (document.visibilityState === 'visible') {
			recordActivity(true);
			verifySessionState(true);
		}
	});

	document.addEventListener('click', handleNoticeAction);
	initRenderedNotices();

	if (supportModal) {
		supportModal.addEventListener('click', function (event) {
			if (event.target === supportModal) {
				closeSupportModal();
			}
		});
	}

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape' && supportModal && !supportModal.hidden) {
			closeSupportModal();
			return;
		}

		if (event.key === 'Escape' && isMobile() && shell.getAttribute('data-sidebar-state') === 'expanded') {
			setSidebarState('collapsed', false, true);
		}
	});

	window.addEventListener('hashchange', function () {
		setRoute(getRouteFromHash(), false);
	});

	if (mediaQuery && mediaQuery.addEventListener) {
		mediaQuery.addEventListener('change', function () {
			setSidebarState(isMobile() ? 'collapsed' : (getStoredState() || 'expanded'), false);
		});
	}

	shell.addEventListener('transitionend', handleSidebarTransitionEnd);

	if (sidebar) {
		sidebar.addEventListener('transitionend', handleSidebarTransitionEnd);
	}

	scheduleAutoLockTimer();
	setSidebarState(getStoredState() || (isMobile() ? 'collapsed' : 'expanded'), false);
	setRoute(getRouteFromHash(), false);
}());
