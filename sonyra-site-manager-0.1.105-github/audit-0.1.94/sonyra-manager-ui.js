(function () {
	'use strict';

	if (window.SonyraManagerUI) {
		return;
	}

	function createNode(tag, className, text) {
		var node = document.createElement(tag);

		if (className) {
			node.className = className;
		}

		if (text !== undefined) {
			node.textContent = text;
		}

		return node;
	}

	function failSharedUi(message) {
		var error = new Error('[SonyraManagerUI] ' + message);

		if (typeof console !== 'undefined' && console && typeof console.error === 'function') {
			console.error(error.message);
		}

		throw error;
	}

	function cloneIcon(key) {
		var iconKey = String(key || '');
		var template = document.querySelector('[data-pages-icon-template="' + iconKey + '"]');

		if (!template || !template.firstElementChild) {
			failSharedUi('Missing shared icon template for "' + iconKey + '".');
		}

		return template.firstElementChild.cloneNode(true);
	}

	function resolveIconNode(settings) {
		if (typeof settings.renderIcon === 'function' && settings.iconKey) {
			return settings.renderIcon(settings.iconKey);
		}

		if (settings.iconNode && settings.iconNode.nodeType) {
			return settings.iconNode;
		}

		if (settings.iconKey) {
			return cloneIcon(settings.iconKey);
		}

		return null;
	}

	function applyAttributes(node, attributes) {
		Object.keys(attributes || {}).forEach(function (key) {
			if (attributes[key] === undefined || attributes[key] === null || attributes[key] === false) {
				return;
			}

			node.setAttribute(key, String(attributes[key]));
		});
	}

	function renderButton(config) {
		var settings = config || {};
		var button = createNode('button', settings.className || '');
		var iconWrap;
		var iconNode = resolveIconNode(settings);

		button.type = 'button';

		if (iconNode) {
			if (settings.iconWrapClass === false) {
				button.appendChild(iconNode);
			} else {
				iconWrap = createNode('span', settings.iconWrapClass || 'sonyra-manager-pages-button-icon-wrap');
				iconWrap.appendChild(iconNode);
				button.appendChild(iconWrap);
			}
		}

		if (settings.text) {
			button.appendChild(document.createTextNode(settings.text));
		}

		applyAttributes(button, settings.attributes || {});

		if (settings.disabled === true) {
			button.disabled = true;
		}

		return button;
	}

	function renderIconTile(config) {
		var settings = config || {};
		var tile = createNode(settings.tagName || 'span', settings.className || '');
		var iconNode = resolveIconNode(settings);

		if (iconNode) {
			tile.appendChild(iconNode);
		}

		applyAttributes(tile, settings.attributes || {});
		return tile;
	}

	function renderHelpTrigger(config) {
		var settings = config || {};
		var helpKey = String(settings.helpKey || '');
		var triggerId = settings.triggerId || ('help-trigger-' + helpKey.replace(/[^a-z0-9_-]/gi, '-'));
		var wrap = createNode('div', settings.wrapClassName || 'sonyra-help-tooltip-wrap');
		var trigger = renderButton({
			className: settings.triggerClassName || 'sonyra-help-tooltip-trigger',
			iconKey: settings.iconKey || 'help-circle',
			renderIcon: settings.renderIcon,
			attributes: {
				id: triggerId,
				'aria-label': settings.ariaLabel || '',
				'aria-expanded': settings.expanded === true ? 'true' : 'false',
				'data-sonyra-help-key': helpKey,
				'data-sonyra-help-trigger': triggerId
			}
		});

		if (settings.triggerAttributeName) {
			trigger.setAttribute(settings.triggerAttributeName, settings.triggerAttributeValue || helpKey);
		}

		wrap.appendChild(trigger);
		return wrap;
	}

	function renderMetaChip(config) {
		var settings = config || {};
		return createNode(settings.tagName || 'span', settings.className || 'sonyra-manager-meta-chip', settings.text || '');
	}

	function renderToolbar(config) {
		var settings = config || {};
		var toolbar = createNode(settings.tagName || 'div', settings.className || '');

		(settings.children || []).forEach(function (child) {
			if (child && child.nodeType) {
				toolbar.appendChild(child);
			}
		});

		applyAttributes(toolbar, settings.attributes || {});
		return toolbar;
	}

	function renderStandardModal(config) {
		var settings = config || {};
		var overlay = createNode('div', settings.overlayClassName || 'sonyra-manager-pages-modal-overlay sonyra-manager-modal-overlay');
		var panel = createNode('div', settings.panelClassName || 'sonyra-manager-pages-modal sonyra-manager-modal');
		var header = createNode('header', settings.headerClassName || 'sonyra-manager-pages-modal-header sonyra-manager-modal__header');
		var icon = renderIconTile({
			tagName: 'div',
			className: settings.iconClassName || 'sonyra-manager-pages-modal-icon',
			iconKey: settings.iconKey || '',
			renderIcon: settings.renderIcon,
			iconNode: settings.iconNode || null
		});
		var copy = createNode('div', settings.copyClassName || 'sonyra-manager-pages-modal-copy');
		var title = createNode(settings.titleTagName || 'h3', settings.titleClassName || 'sonyra-manager-pages-modal-title', settings.titleText || '');
		var description = createNode('p', settings.descriptionClassName || 'sonyra-manager-pages-modal-description', settings.descriptionText || '');
		var closeButton = renderButton({
			className: settings.closeClassName || 'sonyra-manager-pages-modal-close',
			iconKey: settings.closeIconKey || 'x',
			renderIcon: settings.renderCloseIcon,
			iconNode: settings.closeIconNode || null,
			attributes: settings.closeAttributes || {}
		});
		var body = createNode('div', settings.bodyClassName || 'sonyra-manager-pages-modal-body sonyra-manager-modal__body');
		var footer = createNode('footer', settings.footerClassName || 'sonyra-manager-pages-modal-footer sonyra-manager-modal__footer');

		applyAttributes(overlay, settings.overlayAttributes || {});
		applyAttributes(panel, settings.panelAttributes || {});

		if (settings.titleId) {
			title.id = settings.titleId;
			panel.setAttribute('aria-labelledby', settings.titleId);
		}

		if (settings.descriptionText === '' || settings.descriptionHidden === true) {
			description.hidden = true;
		}

		copy.appendChild(title);
		copy.appendChild(description);
		header.appendChild(icon);
		header.appendChild(copy);
		header.appendChild(closeButton);
		panel.appendChild(header);
		panel.appendChild(body);
		panel.appendChild(footer);
		overlay.appendChild(panel);

		return {
			overlay: overlay,
			panel: panel,
			header: header,
			icon: icon,
			copy: copy,
			title: title,
			description: description,
			closeButton: closeButton,
			body: body,
			footer: footer
		};
	}

	function renderContextCard(config) {
		var settings = config || {};
		var wrapper = createNode('div', settings.className || '');
		var copy = createNode('div', settings.copyClassName || '');
		var titleWrap = createNode('div', settings.titleWrapClassName || '');
		var actions = createNode('div', settings.actionsClassName || '');

		if (settings.labelNode) {
			titleWrap.appendChild(settings.labelNode);
		}

		if (settings.helpNode) {
			titleWrap.appendChild(settings.helpNode);
		}

		if (titleWrap.childNodes.length) {
			copy.appendChild(titleWrap);
		}

		if (settings.titleNode) {
			copy.appendChild(settings.titleNode);
		}

		if (settings.subtitleNode) {
			copy.appendChild(settings.subtitleNode);
		}

		wrapper.appendChild(copy);
		wrapper.appendChild(actions);

		if (settings.actionsAttributeName) {
			actions.setAttribute(settings.actionsAttributeName, settings.actionsAttributeValue || 'true');
		}

		(settings.actions || []).forEach(function (actionNode) {
			if (actionNode && actionNode.nodeType) {
				actions.appendChild(actionNode);
			}
		});

		return wrapper;
	}

	function bindHelpTooltipLayer(root, config) {
		var settings = config || {};
		var triggerSelector = settings.triggerSelector || '[data-sonyra-help-key]';
		var popoverSelector = settings.popoverSelector || '[data-color-help-popover]';

		if (!root) {
			return;
		}

		root.addEventListener('mouseenter', function (event) {
			if (event.target.closest(triggerSelector) && typeof settings.onOpen === 'function') {
				settings.onOpen();
			}
		}, true);

		root.addEventListener('focusin', function (event) {
			if (event.target.closest(triggerSelector) && typeof settings.onOpen === 'function') {
				settings.onOpen();
			}
		});

		root.addEventListener('mouseleave', function (event) {
			var helpWrap = event.target.closest('.sonyra-help-tooltip-wrap');
			var relatedPopover = event.relatedTarget && event.relatedTarget.closest ? event.relatedTarget.closest(popoverSelector) : null;

			if (!helpWrap || helpWrap.contains(event.relatedTarget) || relatedPopover) {
				return;
			}

			if (typeof settings.onClose === 'function') {
				settings.onClose();
			}
		}, true);

		root.addEventListener('focusout', function (event) {
			var helpWrap = event.target.closest('.sonyra-help-tooltip-wrap');
			var nextTarget = event.relatedTarget;
			var relatedPopover = nextTarget && nextTarget.closest ? nextTarget.closest(popoverSelector) : null;

			if (!helpWrap) {
				return;
			}

			if (nextTarget && (helpWrap.contains(nextTarget) || relatedPopover)) {
				return;
			}

			if (typeof settings.onClose === 'function') {
				settings.onClose();
			}
		});
	}

	window.SonyraManagerUI = {
		createNode: createNode,
		cloneIcon: cloneIcon,
		failSharedUi: failSharedUi,
		renderButton: renderButton,
		renderIconTile: renderIconTile,
		renderHelpTrigger: renderHelpTrigger,
		bindHelpTooltipLayer: bindHelpTooltipLayer,
		renderMetaChip: renderMetaChip,
		renderToolbar: renderToolbar,
		renderContextCard: renderContextCard,
		renderStandardModal: renderStandardModal
	};
}());
