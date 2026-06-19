(function () {
	'use strict';

	var root = document.querySelector('[data-color-controller-root]');
	var app = root ? root.querySelector('[data-color-controller-app]') : null;
	var payloadNode = document.getElementById('sonyra-manager-color-controller-payload');
	var helpEntriesNode = document.getElementById('sonyra-manager-help-entries');

	if (!root || !app || !payloadNode) {
		return;
	}

	var payload = parseJson(payloadNode.textContent || '{}') || {};
	var helpEntries = parseJson(helpEntriesNode ? (helpEntriesNode.textContent || '[]') : '[]') || [];
	var helpMap = {};
	var patternRegistry = normalizePatternRegistry(payload.patternRegistry || []);

	helpEntries.forEach(function (entry) {
		if (entry && entry.key) {
			helpMap[String(entry.key)] = entry;
		}
	});

	var state = {
		routeActive: false,
		view: 'landing',
		activeTab: 'colors',
		data: normalizeData(payload.data || {}),
		summary: normalizeSummary(payload.summary || {}),
		apiUrl: String(payload.apiUrl || ''),
		mediaUploadUrl: String(payload.mediaUploadUrl || ''),
		mediaLibraryUrl: String(payload.mediaLibraryUrl || ''),
		nonce: String(payload.nonce || ''),
		helpKey: String(payload.helpKey || ''),
		message: null,
		modal: null,
		helpOpen: false
	};
	var noticeTimer = 0;

	function parseJson(source) {
		try {
			return JSON.parse(source);
		} catch (error) {
			return null;
		}
	}

	function t(key, fallback) {
		var dictionary = payload && payload.dictionary ? payload.dictionary : {};
		return dictionary[key] || fallback || '';
	}

	function getManagerUi() {
		if (!window.SonyraManagerUI) {
			throw new Error('[SonyraManagerUI] Shared manager UI library is not loaded.');
		}

		return window.SonyraManagerUI;
	}

	function normalizePatternRegistry(entries) {
		var map = {};

		(entries || []).forEach(function (entry) {
			if (!entry || !entry.key) {
				return;
			}

			map[String(entry.key)] = {
				key: String(entry.key),
				group: String(entry.group || 'decorative'),
				groupLabel: String(entry.group_label || ''),
				editorKind: String(entry.editor_kind || (String(entry.group || '') === 'image' ? 'image' : 'graphic')),
				label: String(entry.label || ''),
				description: String(entry.description || ''),
				previewStyle: String(entry.preview_style || entry.key),
				colorSlots: Array.isArray(entry.color_slots) ? entry.color_slots.slice() : [],
				controls: Array.isArray(entry.controls) ? entry.controls.slice() : [],
				defaultColors: entry.default_colors && typeof entry.default_colors === 'object' ? entry.default_colors : {},
				defaultSettings: entry.default_settings && typeof entry.default_settings === 'object' ? entry.default_settings : {},
				requiresMedia: entry.requires_media === true
			};
		});

		return map;
	}

	function normalizeCollection(items) {
		if (Array.isArray(items)) {
			return items.slice();
		}

		if (items && typeof items === 'object') {
			return Object.keys(items).map(function (key) {
				return items[key];
			}).filter(function (item) {
				return !!item;
			});
		}

		return [];
	}

	function normalizeData(data) {
		return {
			colors: normalizeCollection(data.colors),
			gradients: normalizeCollection(data.gradients),
			patterns: normalizeCollection(data.patterns),
			presets: normalizeCollection(data.presets),
			library_meta: data.library_meta && typeof data.library_meta === 'object' ? data.library_meta : {}
		};
	}

	function normalizeSummary(summary) {
		return {
			colors: Number(summary.colors || 0),
			gradients: Number(summary.gradients || 0),
			patterns: Number(summary.patterns || 0),
			presets: Number(summary.presets || 0)
		};
	}

	function createNode(tag, className, text) {
		var node = document.createElement(tag);
		if (className) {
			node.className = className;
		}
		if (typeof text === 'string') {
			node.textContent = text;
		}
		return node;
	}

	function createButton(className, text, iconName) {
		return getManagerUi().renderButton({
			className: className,
			text: text,
			iconKey: normalizeSharedIconKey(iconName)
		});
	}

	function normalizeSharedIconKey(iconName) {
		if (iconName === 'arrowLeft') {
			return 'arrow-left';
		}

		if (iconName === 'close') {
			return 'x';
		}

		return iconName;
	}

	function clearNode(node) {
		while (node.firstChild) {
			node.removeChild(node.firstChild);
		}
	}

	function normalizeHex(value) {
		var next = String(value || '').trim().toUpperCase();
		if (next && next.charAt(0) !== '#') {
			next = '#' + next;
		}
		return /^#[0-9A-F]{6}$/.test(next) ? next : '';
	}

	function getBrowserLocale(locale) {
		var normalized = String(locale || '').trim().replace(/_/g, '-');
		return normalized || 'ru-RU';
	}

	function formatUpdated(value) {
		if (!value) {
			return '—';
		}
		var date = new Date(value);
		if (isNaN(date.getTime())) {
			return '—';
		}
		return date.toLocaleString(getBrowserLocale(payload.currentLocale), {
			day: '2-digit',
			month: '2-digit',
			year: 'numeric',
			hour: '2-digit',
			minute: '2-digit'
		});
	}

	function formatCount(value, oneKey, fewKey, manyKey) {
		var count = Math.max(0, Number(value || 0));
		var mod10 = count % 10;
		var mod100 = count % 100;
		var noun = t(manyKey);

		if (mod10 === 1 && mod100 !== 11) {
			noun = t(oneKey);
		} else if (mod10 >= 2 && mod10 <= 4 && !(mod100 >= 12 && mod100 <= 14)) {
			noun = t(fewKey);
		}

		return String(count) + ' ' + noun;
	}

	function getCollectionKey(entityType) {
		if (entityType === 'color') {
			return 'colors';
		}
		if (entityType === 'gradient') {
			return 'gradients';
		}
		return 'patterns';
	}

	function getCurrentEntityType() {
		if (state.activeTab === 'colors') {
			return 'color';
		}
		if (state.activeTab === 'gradients') {
			return 'gradient';
		}
		return 'pattern';
	}

	function getEntityCollection() {
		return state.data[getCollectionKey(getCurrentEntityType())] || [];
	}

	function getCollectionForEntityType(entityType) {
		return state.data[getCollectionKey(entityType)] || [];
	}

	function normalizeScopeName(value) {
		return String(value || '').replace(/\s+/g, ' ').trim().toLocaleLowerCase(getBrowserLocale(payload && payload.currentLocale ? payload.currentLocale : 'ru_RU'));
	}

	function isDuplicateNameInCollection(entityType, candidateName, currentItemId) {
		var normalizedCandidate = normalizeScopeName(candidateName);
		var currentId = String(currentItemId || '');

		if (!normalizedCandidate) {
			return false;
		}

		return getCollectionForEntityType(entityType).some(function (item) {
			if (!item || typeof item !== 'object') {
				return false;
			}

			if (currentId && String(item.id || '') === currentId) {
				return false;
			}

			return normalizeScopeName(item.name || '') === normalizedCandidate;
		});
	}

	function clearNoticeTimer() {
		if (noticeTimer) {
			window.clearTimeout(noticeTimer);
			noticeTimer = 0;
		}
	}

	function setMessage(type, text) {
		var nextText = String(text || '').trim();

		clearNoticeTimer();
		state.message = nextText ? { type: String(type || 'info'), text: nextText } : null;

		if (state.message && state.message.type === 'success') {
			noticeTimer = window.setTimeout(function () {
				if (state.message && state.message.type === 'success' && state.message.text === nextText) {
					state.message = null;
					noticeTimer = 0;
					render();
				}
			}, 5000);
		}
	}

	function getEntityCreateKey(entityType) {
		if (entityType === 'color') {
			return 'manager.design.colors.create_color';
		}
		if (entityType === 'gradient') {
			return 'manager.design.colors.create_gradient';
		}
		return 'manager.design.colors.create_pattern';
	}

	function getEntityEditKey(entityType) {
		if (entityType === 'color') {
			return 'manager.design.colors.edit_color';
		}
		if (entityType === 'gradient') {
			return 'manager.design.colors.edit_gradient';
		}
		return 'manager.design.colors.edit_pattern';
	}

	function getEntityTitleCreateKey(entityType) {
		if (entityType === 'color') {
			return 'manager.design.colors.title_create_color';
		}
		if (entityType === 'gradient') {
			return 'manager.design.colors.title_create_gradient';
		}
		return 'manager.design.colors.title_create_pattern';
	}

	function getEntityTitleEditKey(entityType) {
		if (entityType === 'color') {
			return 'manager.design.colors.title_edit_color';
		}
		if (entityType === 'gradient') {
			return 'manager.design.colors.title_edit_gradient';
		}
		return 'manager.design.colors.title_edit_pattern';
	}

	function getEntityDeleteTitleKey(entityType) {
		if (entityType === 'color') {
			return 'manager.design.colors.delete_color_title';
		}
		if (entityType === 'gradient') {
			return 'manager.design.colors.delete_gradient_title';
		}
		return 'manager.design.colors.delete_pattern_title';
	}

	function getEntityDeleteBodyKey(entityType) {
		if (entityType === 'color') {
			return 'manager.design.colors.delete_color_body';
		}
		if (entityType === 'gradient') {
			return 'manager.design.colors.delete_gradient_body';
		}
		return 'manager.design.colors.delete_pattern_body';
	}

	function getModalDescriptionKey(entityType, mode) {
		if (entityType === 'color') {
			return mode === 'edit' ? 'manager.design.colors.modal_color_edit_description' : 'manager.design.colors.modal_color_create_description';
		}
		if (entityType === 'gradient') {
			return mode === 'edit' ? 'manager.design.colors.modal_gradient_edit_description' : 'manager.design.colors.modal_gradient_create_description';
		}
		return mode === 'edit' ? 'manager.design.colors.modal_pattern_edit_description' : 'manager.design.colors.modal_pattern_create_description';
	}

	function getPatternDefinition(patternType) {
		return patternRegistry[String(patternType || '')] || null;
	}

	function getDefaultPatternType() {
		return 'dots';
	}

	function getPatternEditorSchema(patternType, definition) {
		var currentDefinition = definition || getPatternDefinition(patternType) || getPatternDefinition(getDefaultPatternType());
		var currentType = currentDefinition && currentDefinition.key ? currentDefinition.key : getDefaultPatternType();
		var currentEditorKind = currentDefinition && currentDefinition.editorKind ? currentDefinition.editorKind : 'graphic';
		var schemaMap = {
			dots: {
				descriptionKey: 'manager.design.colors.pattern_desc_dots',
				groups: [
					{
						key: 'dots_points',
						titleKey: 'manager.design.colors.group_points',
						defaultOpen: true,
						controlKeys: ['dot_size', 'spacing']
					},
					{
						key: 'dots_layers',
						titleKey: 'manager.design.colors.group_layers_and_colors',
						defaultOpen: true,
						controlKeys: ['layer_count', 'layer_offset'],
						colorSlots: [
							'background',
							'layer_1',
							{ key: 'layer_2', visible_when: { setting: 'layer_count', 'min': 2 } },
							{ key: 'layer_3', visible_when: { setting: 'layer_count', 'min': 3 } }
						]
					},
					{
						key: 'dots_effects',
						titleKey: 'manager.design.colors.group_effects',
						defaultOpen: false,
						controlKeys: ['opacity', 'random_offset', 'edge_style']
					}
				]
			},
			grid: {
				groups: [
					{ key: 'grid_geometry', titleKey: 'manager.design.colors.group_geometry', defaultOpen: true, controlKeys: ['cell_size', 'line_thickness'] },
					{ key: 'grid_colors', titleKey: 'manager.design.colors.group_layers_and_colors', defaultOpen: true, colorSlots: ['background', 'line_color'] },
					{ key: 'grid_effects', titleKey: 'manager.design.colors.group_effects', defaultOpen: false, controlKeys: ['opacity'] }
				]
			},
			lines: {
				groups: [
					{ key: 'lines_geometry', titleKey: 'manager.design.colors.group_geometry', defaultOpen: true, controlKeys: ['angle', 'line_thickness', 'line_spacing'] },
					{
						key: 'lines_layers',
						titleKey: 'manager.design.colors.group_layers_and_colors',
						defaultOpen: true,
						controlKeys: ['second_layer', 'layer_offset'],
						colorSlots: [
							'background',
							'line_color',
							{ key: 'second_color', visible_when: { setting: 'second_layer', equals: true } }
						]
					},
					{ key: 'lines_effects', titleKey: 'manager.design.colors.group_effects', defaultOpen: false, controlKeys: ['opacity'] }
				]
			},
			circles_orbits: {
				groups: [
					{ key: 'circles_geometry', titleKey: 'manager.design.colors.group_geometry', defaultOpen: true, controlKeys: ['circle_size'] },
					{ key: 'circles_colors', titleKey: 'manager.design.colors.group_layers_and_colors', defaultOpen: true, colorSlots: ['background', 'ring_color', 'orbit_color'] },
					{ key: 'circles_effects', titleKey: 'manager.design.colors.group_effects', defaultOpen: false, controlKeys: ['opacity'] }
				]
			},
			geometric_mosaic: {
				groups: [
					{ key: 'mosaic_geometry', titleKey: 'manager.design.colors.group_geometry', defaultOpen: true, controlKeys: ['shape_size'] },
					{ key: 'mosaic_colors', titleKey: 'manager.design.colors.group_layers_and_colors', defaultOpen: true, colorSlots: ['background', 'color_1', 'color_2', 'color_3'] },
					{ key: 'mosaic_effects', titleKey: 'manager.design.colors.group_effects', defaultOpen: false, controlKeys: ['opacity'] }
				]
			},
			soft_blobs: {
				groups: [
					{ key: 'blobs_colors', titleKey: 'manager.design.colors.group_layers_and_colors', defaultOpen: true, colorSlots: ['background', 'blob_1', 'blob_2', 'blob_3'] },
					{ key: 'blobs_effects', titleKey: 'manager.design.colors.group_effects', defaultOpen: false, controlKeys: ['blur', 'opacity'] }
				]
			},
			light_beam: {
				groups: [
					{ key: 'beam_geometry', titleKey: 'manager.design.colors.group_geometry', defaultOpen: true, controlKeys: ['angle'] },
					{ key: 'beam_colors', titleKey: 'manager.design.colors.group_layers_and_colors', defaultOpen: true, colorSlots: ['background', 'beam_color'] },
					{ key: 'beam_effects', titleKey: 'manager.design.colors.group_effects', defaultOpen: false, controlKeys: ['opacity', 'softness'] }
				]
			},
			waves: {
				groups: [
					{ key: 'waves_geometry', titleKey: 'manager.design.colors.group_geometry', defaultOpen: true, controlKeys: ['amplitude', 'thickness', 'angle'] },
					{ key: 'waves_colors', titleKey: 'manager.design.colors.group_layers_and_colors', defaultOpen: true, colorSlots: ['background', 'wave_1', 'wave_2'] },
					{ key: 'waves_effects', titleKey: 'manager.design.colors.group_effects', defaultOpen: false, controlKeys: ['opacity'] }
				]
			},
			paint_strokes: {
				groups: [
					{ key: 'strokes_geometry', titleKey: 'manager.design.colors.group_geometry', defaultOpen: true, controlKeys: ['angle'] },
					{ key: 'strokes_colors', titleKey: 'manager.design.colors.group_layers_and_colors', defaultOpen: true, colorSlots: ['background', 'stroke_1', 'stroke_2'] },
					{ key: 'strokes_effects', titleKey: 'manager.design.colors.group_effects', defaultOpen: false, controlKeys: ['opacity', 'roughness'] }
				]
			},
			ink_blots: {
				groups: [
					{ key: 'blots_colors', titleKey: 'manager.design.colors.group_layers_and_colors', defaultOpen: true, colorSlots: ['background', 'blot_1', 'blot_2'] },
					{ key: 'blots_effects', titleKey: 'manager.design.colors.group_effects', defaultOpen: false, controlKeys: ['opacity'] }
				]
			},
			marble: {
				groups: [
					{ key: 'marble_geometry', titleKey: 'manager.design.colors.group_geometry', defaultOpen: true, controlKeys: ['curve'] },
					{ key: 'marble_colors', titleKey: 'manager.design.colors.group_layers_and_colors', defaultOpen: true, colorSlots: ['base_color', 'line_color', 'accent_color'] },
					{ key: 'marble_effects', titleKey: 'manager.design.colors.group_effects', defaultOpen: false, controlKeys: ['opacity', 'softness'] }
				]
			},
			noise: {
				groups: [
					{ key: 'noise_geometry', titleKey: 'manager.design.colors.group_geometry', defaultOpen: true, controlKeys: ['particle_size'] },
					{ key: 'noise_colors', titleKey: 'manager.design.colors.group_layers_and_colors', defaultOpen: true, colorSlots: ['background', 'noise_color'] },
					{ key: 'noise_effects', titleKey: 'manager.design.colors.group_effects', defaultOpen: false, controlKeys: ['opacity'] }
				]
			},
			decorative_elements: {
				groups: [
					{ key: 'decor_geometry', titleKey: 'manager.design.colors.group_geometry', defaultOpen: true, controlKeys: ['spacing', 'row_offset'] },
					{ key: 'decor_colors', titleKey: 'manager.design.colors.group_layers_and_colors', defaultOpen: true, colorSlots: ['background', 'element_color', 'layer_2_color'] },
					{ key: 'decor_effects', titleKey: 'manager.design.colors.group_effects', defaultOpen: false, controlKeys: ['opacity'] }
				]
			},
			image_pattern: {
				groups: [
					{ key: 'image_media', titleKey: 'manager.design.colors.group_image', defaultOpen: true, media: true },
					{ key: 'image_placement', titleKey: 'manager.design.colors.group_placement', defaultOpen: true, controlKeys: ['image_mode', 'scale', 'position'] },
					{ key: 'image_light', titleKey: 'manager.design.colors.group_light', defaultOpen: true, colorSlots: ['overlay_color', 'background'], controlKeys: ['dimness'] },
					{ key: 'image_texture', titleKey: 'manager.design.colors.group_texture', defaultOpen: false, controlKeys: ['blur'] }
				]
			}
		};
		var currentSchema = schemaMap[currentType] || null;

		if (!currentSchema) {
			return {
				patternType: currentType,
				editorKind: currentEditorKind,
				title: currentDefinition && currentDefinition.label ? currentDefinition.label : '',
				description: currentDefinition && currentDefinition.description ? currentDefinition.description : '',
				groups: []
			};
		}

		return {
			patternType: currentType,
			editorKind: currentEditorKind,
			title: currentDefinition && currentDefinition.label ? currentDefinition.label : '',
			description: currentDefinition && currentDefinition.description ? currentDefinition.description : '',
			descriptionKey: currentSchema.descriptionKey || '',
			groups: currentSchema.groups.map(function (group) {
				return {
					key: group.key,
					titleKey: group.titleKey,
					defaultOpen: group.defaultOpen !== false,
					controlKeys: Array.isArray(group.controlKeys) ? group.controlKeys.slice() : [],
					colorSlots: Array.isArray(group.colorSlots) ? group.colorSlots.slice() : [],
					media: group.media === true
				};
			})
		};
	}

	function createPatternGroupState(editorKind, patternType) {
		var stateMap = {};
		var definition = getPatternDefinition(patternType || '') || getPatternDefinition(getDefaultPatternType());
		var schema = getPatternEditorSchema(patternType || '', definition);

		if ((editorKind || schema.editorKind || 'graphic') === 'graphic') {
			stateMap.__generator = true;
		}

		(schema.groups || []).forEach(function (group) {
			stateMap[group.key] = group.defaultOpen !== false;
		});
		return stateMap;
	}

	function resolveModalText(key) {
		var value = t(key);

		if (typeof value === 'string') {
			value = value.trim();
		}

		if (value && value !== key) {
			return value;
		}

		if (window && window.console && typeof window.console.error === 'function') {
			window.console.error('SONYRA i18n text missing:', key);
		}

		return '';
	}

	function getSonyraSourceText(key, fallback) {
		var value = t(key);
		if (typeof value === 'string') {
			value = value.trim();
		}
		if (value && value !== key) {
			return value;
		}
		if (typeof fallback === 'string' && fallback.trim()) {
			return fallback.trim();
		}
		if (window && window.console && typeof window.console.error === 'function') {
			window.console.error('SONYRA i18n text missing:', key);
		}
		return '';
	}

	function shouldUseApprovedGraphicPatternLayout() {
	  return !!(
	    state.modal &&
	    state.modal.type === 'pattern' &&
	    state.modal.patternSourceSelected === true &&
	    state.modal.draft &&
	    state.modal.draft.editor_kind !== 'image' &&
	    state.modal.draft.pattern_type !== 'image_pattern'
	  );
	}

	function ensureApprovedGraphicPatternStyle() {
	  var styleId = 'sonyra-approved-graphic-pattern-style';

	  if (document.getElementById(styleId)) {
	    return;
	  }

	  var style = document.createElement('style');
	  style.id = styleId;
	  style.textContent = [
	    '.sonyra-color-controller__modal-panel-pattern{width:min(1060px,calc(100vw - 42px))!important;max-width:min(1060px,calc(100vw - 42px))!important;max-height:min(830px,calc(100vh - 46px))!important;overflow:hidden!important;box-shadow:none!important;}',
	    '.sonyra-color-controller__modal-body-pattern{padding:0 52px!important;overflow:hidden!important;min-height:0!important;box-sizing:border-box!important;}',
	    '.sonyra-approved-graphic-pattern-editor{height:min(632px,calc(100vh - 248px))!important;min-height:0!important;display:flex!important;flex-direction:column!important;gap:12px!important;padding-top:10px!important;overflow:hidden!important;box-sizing:border-box!important;}',
	    '.sonyra-approved-graphic-pattern-top{flex:0 0 auto!important;width:100%!important;min-height:130px!important;display:grid!important;grid-template-columns:minmax(370px,45%) minmax(0,1fr)!important;gap:20px!important;align-items:stretch!important;padding:12px!important;border:1px solid rgba(204,214,242,.95)!important;border-radius:22px!important;background:#fbfcff!important;box-shadow:none!important;box-sizing:border-box!important;}',
	    '.sonyra-approved-graphic-pattern-preview-box{position:relative!important;min-height:108px!important;border-radius:18px!important;overflow:hidden!important;background:#fff!important;border:1px solid rgba(215,223,246,.95)!important;box-shadow:none!important;}',
	    '.sonyra-approved-graphic-pattern-preview-box .sonyra-color-controller__pattern-preview{width:100%!important;height:100%!important;min-height:108px!important;margin:0!important;border:0!important;border-radius:18px!important;box-shadow:none!important;overflow:hidden!important;}',
	    '.sonyra-approved-graphic-pattern-badge{position:absolute!important;z-index:5!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;height:24px!important;padding:0 9px!important;border-radius:999px!important;border:1px solid rgba(205,215,241,.95)!important;background:rgba(255,255,255,.92)!important;box-shadow:none!important;white-space:nowrap!important;}',
	    '.sonyra-approved-graphic-pattern-preview-badge{top:10px!important;left:10px!important;color:#5d687b!important;font-size:10px!important;line-height:1!important;font-weight:850!important;letter-spacing:.08em!important;text-transform:uppercase!important;}',
	    '.sonyra-approved-graphic-pattern-type-badge{right:10px!important;bottom:10px!important;color:#5148f2!important;font-size:12px!important;line-height:1!important;font-weight:850!important;}',
	    '.sonyra-approved-graphic-pattern-meta{min-width:0!important;display:flex!important;flex-direction:column!important;justify-content:center!important;gap:8px!important;}',
	    '.sonyra-approved-graphic-pattern-title{margin:0!important;color:#2d374b!important;font-size:14px!important;line-height:1.2!important;font-weight:850!important;letter-spacing:.04em!important;text-transform:uppercase!important;}',
	    '.sonyra-approved-graphic-pattern-label{margin:0!important;color:#66728a!important;font-size:12px!important;line-height:1.2!important;font-weight:750!important;}',
	    '.sonyra-approved-graphic-pattern-name{width:100%!important;height:42px!important;min-height:42px!important;border-radius:14px!important;border:1px solid rgba(190,202,235,.98)!important;background:#fff!important;box-shadow:none!important;outline:none!important;color:#1d2638!important;font-size:14px!important;padding:0 14px!important;box-sizing:border-box!important;}',
	    '.sonyra-approved-graphic-pattern-name:focus{border-color:rgba(82,77,242,.68)!important;box-shadow:0 0 0 3px rgba(82,77,242,.09)!important;}',
	    '.sonyra-approved-graphic-pattern-hint{margin:0!important;color:#69758d!important;font-size:13px!important;line-height:1.4!important;}',
	    '.sonyra-approved-graphic-pattern-scroll{flex:1 1 auto!important;min-height:0!important;overflow-y:auto!important;overflow-x:hidden!important;display:flex!important;flex-direction:column!important;gap:12px!important;padding:0 4px 18px 0!important;scrollbar-width:thin!important;box-sizing:border-box!important;}',
	    '.sonyra-approved-graphic-pattern-section{flex:0 0 auto!important;border:1px solid rgba(215,224,246,.98)!important;border-radius:20px!important;background:#fff!important;box-shadow:none!important;overflow:hidden!important;box-sizing:border-box!important;}',
	    '.sonyra-approved-graphic-pattern-section>summary{list-style:none!important;min-height:54px!important;padding:0 18px!important;display:flex!important;align-items:center!important;justify-content:space-between!important;gap:14px!important;cursor:pointer!important;user-select:none!important;color:#273148!important;font-size:13px!important;line-height:1.1!important;font-weight:850!important;letter-spacing:.04em!important;text-transform:uppercase!important;box-sizing:border-box!important;}',
	    '.sonyra-approved-graphic-pattern-section>summary::-webkit-details-marker{display:none!important;}',
	    '.sonyra-approved-graphic-pattern-chevron{width:30px!important;height:30px!important;flex:0 0 30px!important;border-radius:999px!important;border:1px solid rgba(203,213,241,.95)!important;background:#fff!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;color:#59657a!important;box-shadow:none!important;}',
	    '.sonyra-approved-graphic-pattern-chevron svg{width:16px!important;height:16px!important;display:block!important;transition:transform .18s ease!important;}',
	    '.sonyra-approved-graphic-pattern-section[open] .sonyra-approved-graphic-pattern-chevron svg{transform:rotate(180deg)!important;}',
	    '.sonyra-approved-graphic-pattern-inner{padding:0 18px 18px!important;box-sizing:border-box!important;}',
	    '.sonyra-approved-graphic-pattern-types{display:grid!important;grid-template-columns:repeat(6,minmax(0,1fr))!important;gap:10px!important;}',
	    '.sonyra-approved-graphic-pattern-type{appearance:none!important;min-height:84px!important;border-radius:16px!important;border:1px solid rgba(216,224,246,.98)!important;background:#fff!important;box-shadow:none!important;padding:11px!important;text-align:left!important;cursor:pointer!important;box-sizing:border-box!important;color:#273148!important;display:flex!important;flex-direction:column!important;justify-content:space-between!important;}',
	    '.sonyra-approved-graphic-pattern-type.is-active{border-color:rgba(149,112,255,.68)!important;background:rgba(248,246,255,.98)!important;}',
	    '.sonyra-approved-graphic-pattern-type strong{display:block!important;min-height:18px!important;margin:0!important;font-size:13px!important;line-height:1.2!important;font-weight:850!important;}',
	    '.sonyra-approved-graphic-pattern-type span{display:block!important;min-height:34px!important;margin-top:8px!important;color:#6b768d!important;font-size:12px!important;line-height:1.25!important;}',
	    '.sonyra-approved-graphic-pattern-grid-2{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:14px!important;}',
	    '.sonyra-approved-graphic-pattern-field{min-width:0!important;border:1px solid rgba(216,224,246,.98)!important;border-radius:16px!important;background:#fff!important;padding:12px!important;box-sizing:border-box!important;box-shadow:none!important;}',
	    '.sonyra-approved-graphic-pattern-field-head{display:flex!important;align-items:baseline!important;justify-content:space-between!important;gap:12px!important;margin-bottom:8px!important;}',
	    '.sonyra-approved-graphic-pattern-field-title{color:#66728a!important;font-size:12px!important;line-height:1.2!important;font-weight:750!important;}',
	    '.sonyra-approved-graphic-pattern-field-value{color:#4b5870!important;font-size:12px!important;line-height:1.2!important;font-weight:850!important;white-space:nowrap!important;}',
	    '.sonyra-approved-graphic-pattern-range{-webkit-appearance:none!important;appearance:none!important;width:100%!important;height:22px!important;margin:0!important;border:0!important;border-radius:999px!important;background:var(--sonyra-range-bg,linear-gradient(90deg,#5148f2 0%,#5148f2 50%,#dfe6ef 50%,#dfe6ef 100%))!important;background-size:100% 8px!important;background-position:center!important;background-repeat:no-repeat!important;outline:none!important;box-shadow:none!important;}',
	    '.sonyra-approved-graphic-pattern-range::-webkit-slider-runnable-track{height:8px!important;border-radius:999px!important;background:transparent!important;border:0!important;box-shadow:none!important;}',
	    '.sonyra-approved-graphic-pattern-range::-webkit-slider-thumb{-webkit-appearance:none!important;appearance:none!important;width:18px!important;height:18px!important;margin-top:-5px!important;border-radius:999px!important;border:3px solid #fff!important;background:#5148f2!important;box-shadow:0 0 0 1px rgba(81,72,242,.75)!important;cursor:pointer!important;}',
	    '.sonyra-approved-graphic-pattern-colors{display:flex!important;flex-wrap:wrap!important;gap:12px!important;align-items:flex-start!important;justify-content:flex-start!important;width:100%!important;margin-bottom:14px!important;}',
	    '.sonyra-approved-graphic-pattern-color{flex:0 0 auto!important;width:218px!important;max-width:218px!important;min-height:76px!important;display:grid!important;grid-template-columns:54px 132px!important;gap:12px!important;align-items:center!important;border:1px solid rgba(216,224,246,.98)!important;border-radius:16px!important;background:#fff!important;padding:11px!important;box-sizing:border-box!important;box-shadow:none!important;}',
	    '.sonyra-approved-graphic-pattern-color-button{appearance:none!important;width:54px!important;height:54px!important;border-radius:14px!important;border:1px solid rgba(210,218,242,.95)!important;box-shadow:none!important;cursor:pointer!important;padding:0!important;background:var(--sonyra-color,#9A76F8)!important;}',
	    '.sonyra-approved-graphic-pattern-color-copy{min-width:0!important;height:54px!important;display:grid!important;grid-template-rows:1fr 1fr!important;gap:4px!important;align-items:stretch!important;}',
	    '.sonyra-approved-graphic-pattern-color-name{align-self:start!important;color:#66728a!important;font-size:12px!important;line-height:1.2!important;font-weight:750!important;white-space:nowrap!important;overflow:hidden!important;text-overflow:ellipsis!important;}',
	    '.sonyra-approved-graphic-pattern-color-input{align-self:end!important;width:100%!important;height:28px!important;min-height:28px!important;border-radius:10px!important;border:1px solid rgba(206,216,242,.98)!important;background:#fbfcff!important;color:#273148!important;font-size:13px!important;line-height:1!important;font-weight:850!important;letter-spacing:.02em!important;padding:0 9px!important;box-sizing:border-box!important;box-shadow:none!important;outline:none!important;}',
	    '.sonyra-approved-graphic-pattern-color-input:focus{border-color:rgba(82,77,242,.68)!important;box-shadow:0 0 0 3px rgba(82,77,242,.08)!important;}',
	    '.sonyra-approved-graphic-pattern-hidden-color{position:fixed!important;left:-9999px!important;top:-9999px!important;width:1px!important;height:1px!important;opacity:0!important;pointer-events:none!important;}',
	    '.sonyra-approved-graphic-pattern-layers{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:14px!important;width:100%!important;}',
	    '.sonyra-approved-graphic-pattern-effects{display:grid!important;grid-template-columns:repeat(3,minmax(0,1fr))!important;gap:14px!important;align-items:stretch!important;}',
	    '.sonyra-approved-graphic-pattern-toggle,.sonyra-approved-graphic-pattern-edge{min-height:66px!important;border:1px solid rgba(216,224,246,.98)!important;border-radius:16px!important;background:#fff!important;padding:12px!important;box-sizing:border-box!important;box-shadow:none!important;display:flex!important;align-items:center!important;justify-content:space-between!important;gap:12px!important;color:#4c5870!important;font-size:13px!important;line-height:1.25!important;font-weight:750!important;}',
	    '.sonyra-approved-graphic-pattern-switch{width:48px!important;height:28px!important;border-radius:999px!important;background:#dfe6ef!important;position:relative!important;flex:0 0 auto!important;box-shadow:none!important;}',
	    '.sonyra-approved-graphic-pattern-switch::after{content:""!important;position:absolute!important;left:4px!important;top:4px!important;width:20px!important;height:20px!important;border-radius:999px!important;background:#fff!important;box-shadow:none!important;}',
	    '.sonyra-approved-graphic-pattern-segmented{display:inline-flex!important;padding:3px!important;border-radius:999px!important;background:#eef2fa!important;border:1px solid rgba(216,224,246,.98)!important;gap:3px!important;flex:0 0 auto!important;}',
	    '.sonyra-approved-graphic-pattern-segmented button{appearance:none!important;border:0!important;background:transparent!important;height:30px!important;padding:0 12px!important;border-radius:999px!important;color:#4c5870!important;font-size:12px!important;font-weight:800!important;box-shadow:none!important;cursor:pointer!important;}',
	    '.sonyra-approved-graphic-pattern-segmented button.is-active{background:#fff!important;color:#5148f2!important;}',
	    '.sonyra-approved-graphic-pattern-footer-back{order:-10!important;margin-right:auto!important;min-height:44px!important;border-radius:15px!important;border:1px solid rgba(200,210,242,.95)!important;background:#fff!important;color:#5148f2!important;box-shadow:none!important;font-weight:850!important;padding:0 16px!important;}',
	    '@media (max-width:1040px){.sonyra-approved-graphic-pattern-types{grid-template-columns:repeat(3,minmax(0,1fr))!important;}.sonyra-approved-graphic-pattern-effects{grid-template-columns:1fr!important;}}',
	    '@media (max-width:760px){.sonyra-color-controller__modal-body-pattern{padding:0 20px!important;}.sonyra-approved-graphic-pattern-top,.sonyra-approved-graphic-pattern-grid-2,.sonyra-approved-graphic-pattern-layers{grid-template-columns:1fr!important;}.sonyra-approved-graphic-pattern-types{grid-template-columns:1fr!important;}.sonyra-approved-graphic-pattern-colors{display:grid!important;grid-template-columns:1fr!important;}.sonyra-approved-graphic-pattern-color{width:100%!important;max-width:none!important;grid-template-columns:54px minmax(0,1fr)!important;}}'
	  ].join('\n');

	  document.head.appendChild(style);
	}

	function createApprovedGraphicPatternNode(tag, className, textValue) {
	  var node = document.createElement(tag);

	  if (className) {
	    node.className = className;
	  }

	  if (typeof textValue === 'string') {
	    node.textContent = textValue;
	  }

	  return node;
	}

	function getApprovedGraphicPatternText(key) {
	  return t(key);
	}

	function getApprovedGraphicPatternDraft() {
	  return state.modal && state.modal.draft ? state.modal.draft : {};
	}

	function setApprovedGraphicPatternDraftValue(key, value) {
	  var draft = getApprovedGraphicPatternDraft();

	  draft[key] = value;

	  if (state.modal) {
	    state.modal.draft = draft;
	  }
	}

	function setApprovedGraphicPatternPreviewBackground(previewBox) {
	  var draft = getApprovedGraphicPatternDraft();

	  if (!previewBox) {
	    return;
	  }

	  var preview = previewBox.querySelector('.sonyra-approved-graphic-pattern-live-preview');

	  if (!preview) {
	    return;
	  }

	  var colorOne = normalizeApprovedGraphicPatternHex(draft.color_one || '#9A76F8') || '#9A76F8';
	  var colorTwo = normalizeApprovedGraphicPatternHex(draft.color_two || '#FF93C8') || '#FF93C8';
	  var size = Number(draft.dot_size || 12);
	  var spacing = Number(draft.dot_spacing || 28);
	  var opacity = Number(draft.opacity || 86) / 100;

	  preview.style.opacity = String(Math.max(0, Math.min(1, opacity)));
	  preview.style.background =
	    'radial-gradient(circle at ' + spacing + 'px ' + spacing + 'px, ' + colorOne + ' 0 ' + Math.max(2, size / 2) + 'px, transparent ' + Math.max(3, size / 2 + 1) + 'px),' +
	    'radial-gradient(circle at ' + Math.round(spacing * 1.55) + 'px ' + Math.round(spacing * 1.2) + 'px, ' + colorTwo + ' 0 ' + Math.max(2, size / 2) + 'px, transparent ' + Math.max(3, size / 2 + 1) + 'px),' +
	    '#ffffff';
	  preview.style.backgroundSize = spacing * 2 + 'px ' + spacing * 2 + 'px';
	}

	function createApprovedGraphicPatternChevron() {
	  var span = createApprovedGraphicPatternNode('span', 'sonyra-approved-graphic-pattern-chevron');
	  span.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 10l5 5 5-5" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';

	  return span;
	}

	function createApprovedGraphicPatternSection(titleKey, open, children) {
	  var details = createApprovedGraphicPatternNode('details', 'sonyra-approved-graphic-pattern-section');

	  if (open) {
	    details.open = true;
	    details.setAttribute('open', 'open');
	  } else {
	    details.open = false;
	    details.removeAttribute('open');
	  }

	  var summary = createApprovedGraphicPatternNode('summary');
	  var label = createApprovedGraphicPatternNode('span', '', getApprovedGraphicPatternText(titleKey));
	  var inner = createApprovedGraphicPatternNode('div', 'sonyra-approved-graphic-pattern-inner');

	  summary.appendChild(label);
	  summary.appendChild(createApprovedGraphicPatternChevron());

	  (children || []).forEach(function (child) {
	    inner.appendChild(child);
	  });

	  details.appendChild(summary);
	  details.appendChild(inner);

	  return details;
	}

	function updateApprovedGraphicPatternRange(range, valueNode) {
	  var min = Number(range.min || 0);
	  var max = Number(range.max || 100);
	  var value = Number(range.value || 0);
	  var percent = max > min ? ((value - min) / (max - min)) * 100 : 0;
	  var safe = Math.max(0, Math.min(100, percent));

	  range.style.setProperty(
	    '--sonyra-range-bg',
	    'linear-gradient(90deg, #5148f2 0%, #5148f2 ' + safe + '%, #dfe6ef ' + safe + '%, #dfe6ef 100%)'
	  );

	  valueNode.textContent = String(Math.round(value)) + String(range.dataset.suffix || '');
	}

	function createApprovedGraphicPatternRange(labelKey, min, max, value, suffix, draftKey, onUpdate) {
	  var draft = getApprovedGraphicPatternDraft();
	  var currentValue = draftKey && typeof draft[draftKey] !== 'undefined' ? draft[draftKey] : value;
	  var wrap = createApprovedGraphicPatternNode('div', 'sonyra-approved-graphic-pattern-field');
	  var head = createApprovedGraphicPatternNode('div', 'sonyra-approved-graphic-pattern-field-head');
	  var title = createApprovedGraphicPatternNode('span', 'sonyra-approved-graphic-pattern-field-title', getApprovedGraphicPatternText(labelKey));
	  var valueNode = createApprovedGraphicPatternNode('span', 'sonyra-approved-graphic-pattern-field-value');
	  var range = document.createElement('input');

	  range.type = 'range';
	  range.className = 'sonyra-approved-graphic-pattern-range';
	  range.min = String(min);
	  range.max = String(max);
	  range.value = String(currentValue);
	  range.dataset.suffix = String(suffix || '');

	  head.appendChild(title);
	  head.appendChild(valueNode);
	  wrap.appendChild(head);
	  wrap.appendChild(range);

	  updateApprovedGraphicPatternRange(range, valueNode);

	  range.addEventListener('input', function () {
	    updateApprovedGraphicPatternRange(range, valueNode);

	    if (draftKey) {
	      setApprovedGraphicPatternDraftValue(draftKey, Number(range.value));
	    }

	    if (typeof onUpdate === 'function') {
	      onUpdate();
	    }
	  });

	  return wrap;
	}

	function normalizeApprovedGraphicPatternHex(value) {
	  var hex = String(value || '').trim().toUpperCase();

	  if (hex && hex.charAt(0) !== '#') {
	    hex = '#' + hex;
	  }

	  hex = hex.replace(/[^#0-9A-F]/g, '').slice(0, 7);

	  return /^#[0-9A-F]{6}$/.test(hex) ? hex : '';
	}

	function createApprovedGraphicPatternColorControl(index, initialColor, draftKey, onUpdate) {
	  var draft = getApprovedGraphicPatternDraft();
	  var color = normalizeApprovedGraphicPatternHex(draftKey && draft[draftKey] ? draft[draftKey] : initialColor) || '#9A76F8';
	  var tile = createApprovedGraphicPatternNode('div', 'sonyra-approved-graphic-pattern-color');
	  var button = document.createElement('button');
	  var picker = document.createElement('input');
	  var copy = createApprovedGraphicPatternNode('div', 'sonyra-approved-graphic-pattern-color-copy');
	  var name = createApprovedGraphicPatternNode(
	    'div',
	    'sonyra-approved-graphic-pattern-color-name',
	    getApprovedGraphicPatternText('manager.design.colors.pattern_color_point') + ' ' + String(index)
	  );
	  var input = document.createElement('input');

	  if (draftKey) {
	    setApprovedGraphicPatternDraftValue(draftKey, color);
	  }

	  button.type = 'button';
	  button.className = 'sonyra-approved-graphic-pattern-color-button';
	  button.style.setProperty('--sonyra-color', color);

	  picker.type = 'color';
	  picker.value = color;
	  picker.className = 'sonyra-approved-graphic-pattern-hidden-color';

	  input.className = 'sonyra-approved-graphic-pattern-color-input';
	  input.value = color;

	  function applyColor(nextColor) {
	    var normalized = normalizeApprovedGraphicPatternHex(nextColor);

	    if (!normalized) {
	      return;
	    }

	    button.style.setProperty('--sonyra-color', normalized);
	    picker.value = normalized;
	    input.value = normalized;

	    if (draftKey) {
	      setApprovedGraphicPatternDraftValue(draftKey, normalized);
	    }

	    if (typeof onUpdate === 'function') {
	      onUpdate();
	    }
	  }

	  button.addEventListener('click', function () {
	    picker.click();
	  });

	  picker.addEventListener('input', function () {
	    applyColor(picker.value);
	  });

	  input.addEventListener('input', function () {
	    var normalized = normalizeApprovedGraphicPatternHex(input.value);

	    if (normalized) {
	      button.style.setProperty('--sonyra-color', normalized);
	      picker.value = normalized;

	      if (draftKey) {
	        setApprovedGraphicPatternDraftValue(draftKey, normalized);
	      }

	      if (typeof onUpdate === 'function') {
	        onUpdate();
	      }
	    }
	  });

	  input.addEventListener('blur', function () {
	    applyColor(input.value);
	  });

	  copy.appendChild(name);
	  copy.appendChild(input);
	  tile.appendChild(button);
	  tile.appendChild(copy);
	  tile.appendChild(picker);

	  return tile;
	}

	function createApprovedGraphicPatternTypeButton(typeKey, titleKey, hintKey, badgeNode, previewBox) {
	  var draft = getApprovedGraphicPatternDraft();
	  var button = document.createElement('button');
	  var title = createApprovedGraphicPatternNode('strong', '', getApprovedGraphicPatternText(titleKey));
	  var hint = createApprovedGraphicPatternNode('span', '', getApprovedGraphicPatternText(hintKey));
	  var activeType = draft.approved_pattern_type || 'dots';

	  button.type = 'button';
	  button.className = 'sonyra-approved-graphic-pattern-type';
	  button.dataset.approvedPatternType = typeKey;

	  if (typeKey === activeType) {
	    button.classList.add('is-active');
	  }

	  button.appendChild(title);
	  button.appendChild(hint);

	  button.addEventListener('click', function () {
	    var siblings = button.parentNode ? Array.from(button.parentNode.querySelectorAll('.sonyra-approved-graphic-pattern-type')) : [];

	    siblings.forEach(function (node) {
	      node.classList.remove('is-active');
	    });

	    button.classList.add('is-active');

	    setApprovedGraphicPatternDraftValue('approved_pattern_type', typeKey);

	    if (badgeNode) {
	      badgeNode.textContent = title.textContent;
	    }

	    setApprovedGraphicPatternPreviewBackground(previewBox);
	  });

	  return button;
	}

	function renderApprovedGraphicPatternEditor() {
	  var draft = getApprovedGraphicPatternDraft();
	  var editor = createApprovedGraphicPatternNode('div', 'sonyra-approved-graphic-pattern-editor');
	  var top = createApprovedGraphicPatternNode('div', 'sonyra-approved-graphic-pattern-top');
	  var previewBox = createApprovedGraphicPatternNode('div', 'sonyra-approved-graphic-pattern-preview-box');
	  var preview = createApprovedGraphicPatternNode('div', 'sonyra-color-controller__pattern-preview sonyra-approved-graphic-pattern-live-preview');
	  var previewBadge = createApprovedGraphicPatternNode(
	    'span',
	    'sonyra-approved-graphic-pattern-badge sonyra-approved-graphic-pattern-preview-badge',
	    getApprovedGraphicPatternText('manager.design.colors.pattern_editor_preview_badge')
	  );
	  var typeBadge = createApprovedGraphicPatternNode(
	    'span',
	    'sonyra-approved-graphic-pattern-badge sonyra-approved-graphic-pattern-type-badge',
	    getApprovedGraphicPatternText('manager.design.colors.pattern_type_dots')
	  );
	  var meta = createApprovedGraphicPatternNode('div', 'sonyra-approved-graphic-pattern-meta');
	  var metaTitle = createApprovedGraphicPatternNode(
	    'p',
	    'sonyra-approved-graphic-pattern-title',
	    getApprovedGraphicPatternText('manager.design.colors.pattern_editor_settings_title')
	  );
	  var nameLabel = createApprovedGraphicPatternNode(
	    'p',
	    'sonyra-approved-graphic-pattern-label',
	    getApprovedGraphicPatternText('manager.design.colors.pattern_editor_name_label')
	  );
	  var nameInput = document.createElement('input');
	  var hint = createApprovedGraphicPatternNode(
	    'p',
	    'sonyra-approved-graphic-pattern-hint',
	    getApprovedGraphicPatternText('manager.design.colors.pattern_editor_hint')
	  );
	  var scroll = createApprovedGraphicPatternNode('div', 'sonyra-approved-graphic-pattern-scroll');
	  var types = createApprovedGraphicPatternNode('div', 'sonyra-approved-graphic-pattern-types');
	  var dotSettings = createApprovedGraphicPatternNode('div', 'sonyra-approved-graphic-pattern-grid-2');
	  var colorsWrap = createApprovedGraphicPatternNode('div', 'sonyra-approved-graphic-pattern-colors-wrap');
	  var colors = createApprovedGraphicPatternNode('div', 'sonyra-approved-graphic-pattern-colors');
	  var layers = createApprovedGraphicPatternNode('div', 'sonyra-approved-graphic-pattern-layers');
	  var effects = createApprovedGraphicPatternNode('div', 'sonyra-approved-graphic-pattern-effects');
	  var random = createApprovedGraphicPatternNode('div', 'sonyra-approved-graphic-pattern-toggle');
	  var randomLabel = createApprovedGraphicPatternNode('span', '', getApprovedGraphicPatternText('manager.design.colors.pattern_random_offset'));
	  var randomSwitch = createApprovedGraphicPatternNode('span', 'sonyra-approved-graphic-pattern-switch');
	  var edge = createApprovedGraphicPatternNode('div', 'sonyra-approved-graphic-pattern-edge');
	  var edgeLabel = createApprovedGraphicPatternNode('span', '', getApprovedGraphicPatternText('manager.design.colors.pattern_edge'));
	  var segmented = createApprovedGraphicPatternNode('span', 'sonyra-approved-graphic-pattern-segmented');

	  ensureApprovedGraphicPatternStyle();

	  if (!draft.approved_pattern_type) {
	    setApprovedGraphicPatternDraftValue('approved_pattern_type', 'dots');
	  }

	  if (typeof draft.dot_size === 'undefined') {
	    setApprovedGraphicPatternDraftValue('dot_size', 12);
	  }

	  if (typeof draft.dot_spacing === 'undefined') {
	    setApprovedGraphicPatternDraftValue('dot_spacing', 28);
	  }

	  if (typeof draft.layers_count === 'undefined') {
	    setApprovedGraphicPatternDraftValue('layers_count', 2);
	  }

	  if (typeof draft.layer_offset === 'undefined') {
	    setApprovedGraphicPatternDraftValue('layer_offset', 14);
	  }

	  if (typeof draft.opacity === 'undefined') {
	    setApprovedGraphicPatternDraftValue('opacity', 86);
	  }

	  nameInput.className = 'sonyra-approved-graphic-pattern-name';
	  nameInput.value = String(draft.name || '');
	  nameInput.placeholder = getApprovedGraphicPatternText('manager.design.colors.pattern_name_placeholder');
	  nameInput.addEventListener('input', function () {
	    setApprovedGraphicPatternDraftValue('name', nameInput.value);
	  });

	  previewBox.appendChild(preview);
	  previewBox.appendChild(previewBadge);
	  previewBox.appendChild(typeBadge);

	  meta.appendChild(metaTitle);
	  meta.appendChild(nameLabel);
	  meta.appendChild(nameInput);
	  meta.appendChild(hint);

	  top.appendChild(previewBox);
	  top.appendChild(meta);

	  [
	    ['dots', 'manager.design.colors.pattern_type_dots', 'manager.design.colors.pattern_type_dots_hint'],
	    ['grid', 'manager.design.colors.pattern_type_grid', 'manager.design.colors.pattern_type_grid_hint'],
	    ['waves', 'manager.design.colors.pattern_type_waves', 'manager.design.colors.pattern_type_waves_hint'],
	    ['lines', 'manager.design.colors.pattern_type_lines', 'manager.design.colors.pattern_type_lines_hint'],
	    ['paint', 'manager.design.colors.pattern_type_paint', 'manager.design.colors.pattern_type_paint_hint'],
	    ['shapes', 'manager.design.colors.pattern_type_shapes', 'manager.design.colors.pattern_type_shapes_hint']
	  ].forEach(function (item) {
	    types.appendChild(createApprovedGraphicPatternTypeButton(item[0], item[1], item[2], typeBadge, previewBox));
	  });

	  dotSettings.appendChild(createApprovedGraphicPatternRange('manager.design.colors.pattern_dot_size', 4, 40, draft.dot_size || 12, 'px', 'dot_size', function () {
	    setApprovedGraphicPatternPreviewBackground(previewBox);
	  }));
	  dotSettings.appendChild(createApprovedGraphicPatternRange('manager.design.colors.pattern_dot_spacing', 8, 80, draft.dot_spacing || 28, 'px', 'dot_spacing', function () {
	    setApprovedGraphicPatternPreviewBackground(previewBox);
	  }));

	  colors.appendChild(createApprovedGraphicPatternColorControl(1, draft.color_one || '#9A76F8', 'color_one', function () {
	    setApprovedGraphicPatternPreviewBackground(previewBox);
	  }));
	  colors.appendChild(createApprovedGraphicPatternColorControl(2, draft.color_two || '#FF93C8', 'color_two', function () {
	    setApprovedGraphicPatternPreviewBackground(previewBox);
	  }));

	  layers.appendChild(createApprovedGraphicPatternRange('manager.design.colors.pattern_layers_count', 1, 5, draft.layers_count || 2, '', 'layers_count', function () {
	    setApprovedGraphicPatternPreviewBackground(previewBox);
	  }));
	  layers.appendChild(createApprovedGraphicPatternRange('manager.design.colors.pattern_layer_offset', 0, 40, draft.layer_offset || 14, 'px', 'layer_offset', function () {
	    setApprovedGraphicPatternPreviewBackground(previewBox);
	  }));

	  colorsWrap.appendChild(colors);
	  colorsWrap.appendChild(layers);

	  effects.appendChild(createApprovedGraphicPatternRange('manager.design.colors.pattern_opacity', 0, 100, draft.opacity || 86, '%', 'opacity', function () {
	    setApprovedGraphicPatternPreviewBackground(previewBox);
	  }));

	  random.appendChild(randomLabel);
	  random.appendChild(randomSwitch);

	  segmented.innerHTML = '<button type="button" class="is-active">' + getApprovedGraphicPatternText('manager.design.colors.pattern_edge_soft') + '</button><button type="button">' + getApprovedGraphicPatternText('manager.design.colors.pattern_edge_sharp') + '</button>';

	  edge.appendChild(edgeLabel);
	  edge.appendChild(segmented);

	  effects.appendChild(random);
	  effects.appendChild(edge);

	  scroll.appendChild(createApprovedGraphicPatternSection('manager.design.colors.pattern_editor_type_section', true, [types]));
	  scroll.appendChild(createApprovedGraphicPatternSection('manager.design.colors.pattern_editor_dot_settings_section', false, [dotSettings]));
	  scroll.appendChild(createApprovedGraphicPatternSection('manager.design.colors.pattern_editor_layers_colors_section', false, [colorsWrap]));
	  scroll.appendChild(createApprovedGraphicPatternSection('manager.design.colors.pattern_editor_effects_section', false, [effects]));

	  editor.appendChild(top);
	  editor.appendChild(scroll);

	  setApprovedGraphicPatternPreviewBackground(previewBox);

	  return editor;
	}

	function moveApprovedGraphicPatternBackButton(modalFooter) {
	  var existingBack = document.querySelector('.sonyra-approved-graphic-pattern-footer-back');

	  if (!modalFooter) {
	    return;
	  }

	  if (!existingBack) {
	    existingBack = document.createElement('button');
	    existingBack.type = 'button';
	    existingBack.className = 'sonyra-approved-graphic-pattern-footer-back sonyra-color-controller__pattern-back-button';
	  }

	  existingBack.textContent = getApprovedGraphicPatternText('manager.design.colors.pattern_back_to_type');

	  existingBack.addEventListener('click', function () {
	    if (!state.modal) {
	      return;
	    }

	    state.modal.patternSourceSelected = false;
	    state.modal.patternType = '';
	    rerenderActiveModal();
	  });

	  modalFooter.insertBefore(existingBack, modalFooter.firstChild);
	}

	function getColorControllerModalCopy(modal) {
		var type = modal && modal.entityType ? String(modal.entityType) : (modal && modal.type ? String(modal.type) : '');
		var mode = modal && modal.mode ? String(modal.mode) : 'create';
		var draftPatternType = modal && modal.draft && modal.draft.pattern_type ? String(modal.draft.pattern_type) : '';
		var patternType = modal && modal.patternType ? String(modal.patternType) : draftPatternType;
		var isEdit = mode === 'edit';
		var isImagePattern = patternType === 'image_pattern';
		var titleKey = '';
		var descriptionKey = '';

		if (type === 'pattern' && !isEdit && modal && modal.patternSourceSelected !== true) {
			titleKey = 'manager.design.colors.pattern_source_modal_title';
			descriptionKey = 'manager.design.colors.pattern_source_modal_description';

			return {
				titleKey: titleKey,
				descriptionKey: descriptionKey,
				titleText: resolveModalText(titleKey),
				descriptionText: resolveModalText(descriptionKey)
			};
		}

		if (type === 'color') {
			titleKey = isEdit ? 'manager.design.colors.title_edit_color' : 'manager.design.colors.title_create_color';
			descriptionKey = isEdit ? 'manager.design.colors.modal_color_edit_description' : 'manager.design.colors.modal_color_create_description';

			return {
				titleKey: titleKey,
				descriptionKey: descriptionKey,
				titleText: resolveModalText(titleKey),
				descriptionText: resolveModalText(descriptionKey)
			};
		}

		if (type === 'gradient') {
			titleKey = isEdit ? 'manager.design.colors.title_edit_gradient' : 'manager.design.colors.title_create_gradient';
			descriptionKey = isEdit ? 'manager.design.colors.modal_gradient_edit_description' : 'manager.design.colors.modal_gradient_create_description';

			return {
				titleKey: titleKey,
				descriptionKey: descriptionKey,
				titleText: resolveModalText(titleKey),
				descriptionText: resolveModalText(descriptionKey)
			};
		}

		if (type === 'pattern') {
			titleKey = isImagePattern
				? (isEdit ? 'manager.design.colors.title_edit_pattern_image' : 'manager.design.colors.title_create_pattern_image')
				: (isEdit ? 'manager.design.colors.title_edit_pattern_graphic' : 'manager.design.colors.title_create_pattern_graphic');

			descriptionKey = isImagePattern
				? 'manager.design.colors.modal_pattern_image_description'
				: 'manager.design.colors.modal_pattern_graphic_description';

			return {
				titleKey: titleKey,
				descriptionKey: descriptionKey,
				titleText: resolveModalText(titleKey),
				descriptionText: resolveModalText(descriptionKey)
			};
		}

		return {
			titleKey: '',
			descriptionKey: '',
			titleText: '',
			descriptionText: ''
		};
	}

	function getColorValue(item, key, fallbackKey) {
		var colors = item && item.colors ? item.colors : {};
		return String(colors[key] || colors[fallbackKey] || '');
	}

	function getGradientStopColor(item, index) {
		var stops = Array.isArray(item && item.stops) ? item.stops : [];
		var stop = stops[index] || stops[stops.length - 1] || null;
		return stop && stop.color_hex ? String(stop.color_hex) : '';
	}

	function getPatternSetting(item, key, fallback) {
		var settings = item && item.settings ? item.settings : {};
		return settings[key] !== undefined ? settings[key] : fallback;
	}

	function hasCollectionShape(data) {
		return !!(data && typeof data === 'object' && data.colors !== undefined && data.gradients !== undefined && data.patterns !== undefined);
	}

	function buildSummaryFromData(data) {
		var normalized = normalizeData(data || {});
		return normalizeSummary({
			colors: normalized.colors.length,
			gradients: normalized.gradients.length,
			patterns: normalized.patterns.length,
			presets: normalized.presets.length
		});
	}

	function applyResponse(response) {
		var nextData = hasCollectionShape(response && response.data) ? response.data : (hasCollectionShape(response && response.library) ? response.library : null);

		if (nextData) {
			state.data = normalizeData(nextData);
			state.summary = buildSummaryFromData(state.data);
		}

		if (!nextData && response && response.summary && typeof response.summary === 'object') {
			state.summary = normalizeSummary(response.summary);
		}

		setMessage('success', response && response.message ? String(response.message) : '');
	}

	function getDestructiveModalSubtitleText() {
		return String(payload && payload.messages && payload.messages.destructiveModalSubtitle ? payload.messages.destructiveModalSubtitle : t('manager.design.colors.delete_modal_description'));
	}

	function buildSummaryChips() {
		var wrap = createNode('div', 'sonyra-manager-meta-chip-group');
		[
			formatCount(state.summary.colors, 'manager.design.colors.count_color_one', 'manager.design.colors.count_color_few', 'manager.design.colors.count_color_many'),
			formatCount(state.summary.gradients, 'manager.design.colors.count_gradient_one', 'manager.design.colors.count_gradient_few', 'manager.design.colors.count_gradient_many'),
			formatCount(state.summary.patterns, 'manager.design.colors.count_pattern_one', 'manager.design.colors.count_pattern_few', 'manager.design.colors.count_pattern_many')
		].forEach(function (label) {
			wrap.appendChild(getManagerUi().renderMetaChip({
				className: 'sonyra-manager-meta-chip',
				text: label
			}));
		});
		return wrap;
	}

	function buildHelpTooltip() {
		var triggerId = 'help-trigger-' + String(state.helpKey || 'design-colors-controller').replace(/[^a-z0-9_-]/gi, '-');

		return getManagerUi().renderHelpTrigger({
			helpKey: state.helpKey || 'design.colors.controller',
			triggerId: triggerId,
			ariaLabel: t('manager.pages.actions.help_tooltip'),
			expanded: state.helpOpen,
			triggerAttributeName: 'data-color-help-toggle'
		});
	}

	function buildLandingCard() {
		var card = createNode('article', 'sonyra-color-controller__tool-card sonyra-color-controller__landing');
		var head = createNode('div', 'sonyra-color-controller__tool-card-head');
		var icon = createNode('div', 'sonyra-color-controller__tool-card-icon');
		var copy = createNode('div', 'sonyra-color-controller__tool-card-copy');
		var title = createNode('h2', 'sonyra-color-controller__tool-card-title', t('manager.design.tools.color_library.title'));
		var description = createNode('p', 'sonyra-color-controller__tool-card-description', t('manager.design.tools.color_library.description'));
		var footerSpacer = createNode('div', 'sonyra-color-controller__tool-card-footer-spacer');
		var footer = createNode('div', 'sonyra-color-controller__tool-card-footer');
		var action = createButton('sonyra-manager-pages-primary', t('manager.design.tools.color_library.action'), 'settings');

		action.setAttribute('data-color-open-library', 'true');
		head.appendChild(icon);
		copy.appendChild(title);
		copy.appendChild(description);
		head.appendChild(copy);
		footer.appendChild(footerSpacer);
		footer.appendChild(buildSummaryChips());
		footer.appendChild(action);
		card.appendChild(head);
		card.appendChild(footer);

		return card;
	}

	function buildContext() {
		var back = createButton('sonyra-manager-pages-secondary', t('manager.design.colors.back_to_design'), 'arrowLeft');

		back.setAttribute('data-color-back', 'true');

		return getManagerUi().renderContextCard({
			className: 'sonyra-pages-sections-context sonyra-pages-layer-context sonyra-manager-pages-table-card sonyra-color-controller__context',
			copyClassName: 'sonyra-pages-sections-context-copy sonyra-pages-layer-context-copy',
			titleWrapClassName: 'sonyra-pages-panel-title-wrap',
			actionsClassName: 'sonyra-pages-layer-context-actions sonyra-color-controller__context-actions',
			labelNode: createNode('span', 'sonyra-pages-sections-context-label', t('manager.design.colors.context_title')),
			helpNode: buildHelpTooltip(),
			titleNode: createNode('strong', 'sonyra-pages-sections-context-title', t('manager.design.colors.context_subtitle')),
			actions: [back]
		});
	}

	function buildToolbar() {
		var toolbar = createNode('div', 'sonyra-pages-toolbar-panel');
		var filtersWrap = createNode('div', 'sonyra-pages-toolbar-controls');
		var filterGroup = createNode('div', 'sonyra-pages-filter-group sonyra-color-controller__tabs');
		var actions = createNode('div', 'sonyra-color-controller__toolbar-actions');
		var createAction = createButton('sonyra-manager-pages-primary', t(getEntityCreateKey(getCurrentEntityType())), 'plus');

		[
			{ key: 'colors', label: t('manager.design.colors.tab_colors') },
			{ key: 'gradients', label: t('manager.design.colors.tab_gradients') },
			{ key: 'patterns', label: t('manager.design.colors.tab_patterns') }
		].forEach(function (tab) {
			var chip = createButton('sonyra-pages-filter-chip', tab.label);
			chip.setAttribute('data-color-tab', tab.key);
			chip.setAttribute('aria-pressed', state.activeTab === tab.key ? 'true' : 'false');
			chip.classList.toggle('is-active', state.activeTab === tab.key);
			filterGroup.appendChild(chip);
		});

		createAction.setAttribute('data-color-create', getCurrentEntityType());
		filtersWrap.appendChild(filterGroup);
		actions.appendChild(buildSummaryChips());
		actions.appendChild(createAction);
		toolbar.appendChild(filtersWrap);
		toolbar.appendChild(actions);

		return toolbar;
	}

	function buildMessage() {
		if (!state.message || !state.message.text) {
			return null;
		}

		var note = createNode('div', 'sonyra-color-controller__notice sonyra-color-controller__notice-' + state.message.type);
		var close = createButton('sonyra-color-controller__notice-close', '', 'close');

		note.appendChild(createNode('span', 'sonyra-color-controller__notice-text', state.message.text));
		close.setAttribute('aria-label', t('manager.pages.modal.close'));
		close.setAttribute('data-color-dismiss-message', 'true');
		note.appendChild(close);

		return note;
	}

	function renderColorSwatch(hex, large) {
		var swatch = createNode('span', 'sonyra-color-controller__swatch' + (large ? ' is-large' : ''));
		swatch.style.background = normalizeHex(hex) || '#FFFFFF';
		return swatch;
	}

	function renderGradientPreview(item, large) {
		var preview = createNode('div', 'sonyra-color-controller__gradient-preview' + (large ? ' is-large' : ''));
		var stops = Array.isArray(item && item.stops) ? item.stops : [
			{ color_hex: getGradientStopColor(item, 0) || '#7C3AED', position: 0 },
			{ color_hex: getGradientStopColor(item, 1) || '#EC4899', position: 100 }
		];
		var angle = Number(item && item.angle !== undefined ? item.angle : 135);
		var parts = stops.map(function (stop) {
			return (stop.color_hex || '#FFFFFF') + ' ' + String(Number(stop.position || 0)) + '%';
		});

		preview.style.background = 'linear-gradient(' + angle + 'deg, ' + parts.join(', ') + ')';
		return preview;
	}

	function getPatternRenderState(item, fallbackDefinition) {
		var definition = fallbackDefinition || getPatternDefinition(item.pattern_type) || getPatternDefinition(getDefaultPatternType());
		var settings = item && item.settings ? item.settings : {};
		var colors = item && item.colors ? item.colors : {};
		var media = item && item.media ? item.media : {};

		return {
			definition: definition,
			patternType: item.pattern_type || getDefaultPatternType(),
			colors: colors,
			background: normalizeHex(colors.background || colors.base_color) || '#FFFFFF',
			primary: normalizeHex(colors.layer_1 || colors.line_color || colors.ring_color || colors.color_1 || colors.blob_1 || colors.beam_color || colors.wave_1 || colors.stroke_1 || colors.blot_1 || colors.noise_color || colors.element_color || colors.line_color || colors.primary) || '#7C3AED',
			accent: normalizeHex(colors.layer_2 || colors.second_color || colors.orbit_color || colors.color_2 || colors.blob_2 || colors.wave_2 || colors.stroke_2 || colors.blot_2 || colors.accent_color || colors.layer_2_color || colors.overlay_color || colors.accent) || '#EC4899',
			overlay: normalizeHex(colors.overlay_color || colors.overlay) || '#0F172A',
			settings: settings,
			opacity: Number(settings.opacity !== undefined ? settings.opacity : 72),
			angle: Number(settings.angle !== undefined ? settings.angle : 135),
			imageMode: String(settings.image_mode || 'cover'),
			dimness: Number(settings.dimness !== undefined ? settings.dimness : 0),
			blur: Number(settings.blur !== undefined ? settings.blur : 0),
			media: media
		};
	}

	function hexToRgba(hex, alpha) {
		var normalized = normalizeHex(hex);
		if (!normalized) {
			return 'rgba(124,58,237,' + String(alpha || 0.25) + ')';
		}
		return 'rgba('
			+ parseInt(normalized.slice(1, 3), 16) + ','
			+ parseInt(normalized.slice(3, 5), 16) + ','
			+ parseInt(normalized.slice(5, 7), 16) + ','
			+ String(alpha || 0.25) + ')';
	}

	function renderPatternPreview(item, large, definitionOverride) {
		var preview = createNode('div', 'sonyra-color-controller__pattern-preview' + (large ? ' is-large' : ''));
		var renderState = getPatternRenderState(item, definitionOverride);
		var style = renderState.definition ? renderState.definition.previewStyle : renderState.patternType;
		var settings = renderState.settings || {};
		var alpha = Math.max(0.08, Math.min(1, renderState.opacity / 100));
		var overlay;
		var texture;
		var noise;
		var rings;

		preview.style.backgroundColor = renderState.background;

		if (style === 'image_pattern') {
			var scale = Math.max(40, Number(settings.scale || 100));
			var position = String(settings.position || 'center');
			var backgroundSize = scale + '%';

			if (renderState.imageMode === 'stretch') {
				backgroundSize = '100% 100%';
			} else if (renderState.imageMode === 'contain') {
				backgroundSize = 'contain';
			} else if (renderState.imageMode === 'cover') {
				backgroundSize = 'cover';
			}

			preview.style.backgroundImage = renderState.media && renderState.media.url ? 'url("' + String(renderState.media.url).replace(/"/g, '%22') + '")' : '';
			preview.style.backgroundSize = renderState.imageMode === 'repeat' ? backgroundSize : backgroundSize;
			preview.style.backgroundRepeat = renderState.imageMode === 'repeat' ? 'repeat' : 'no-repeat';
			preview.style.backgroundPosition = position.replace('-', ' ');
			preview.style.filter = renderState.blur ? 'blur(' + String(renderState.blur) + 'px)' : '';
			overlay = createNode('span', 'sonyra-color-controller__pattern-overlay');
			overlay.style.background = 'linear-gradient(0deg, ' + hexToRgba(renderState.overlay, Math.min(0.82, Number(settings.dimness || 0) / 100)) + ', ' + hexToRgba(renderState.overlay, Math.min(0.82, Number(settings.dimness || 0) / 100)) + ')';
			preview.appendChild(overlay);
			return preview;
		}

		if (style === 'dots') {
			var dotSize = Math.max(2, Number(settings.dot_size || 12));
			var spacing = Math.max(8, Number(settings.spacing || 28));
			var layerCount = Math.max(1, Math.min(3, Number(settings.layer_count || 2)));
			var layerOffset = Math.max(0, Number(settings.layer_offset || 14));
			var randomOffset = settings.random_offset === true || settings.random_offset === 'true' || Number(settings.random_offset || 0) === 1;
			var edgeStyle = String(settings.edge_style || 'soft');
			var layerTwo = normalizeHex(renderState.colors.layer_2 || '') || renderState.accent;
			var layerThree = normalizeHex(renderState.colors.layer_3 || '') || renderState.overlay;
			var dotCore = edgeStyle === 'crisp' ? Math.max(1, dotSize / 2) : Math.max(1, dotSize / 2.6);
			var dotFade = edgeStyle === 'crisp' ? 0.7 : Math.max(2, dotSize / 2.4);
			var backgrounds = [];
			var positions = [];
			var layerAlphas = [alpha, alpha * 0.84, alpha * 0.68];
			var offsets = randomOffset
				? [
					'0 0',
					String(layerOffset + 6) + 'px ' + String(Math.max(4, layerOffset * 0.72)) + 'px',
					String(Math.max(3, layerOffset * 0.38)) + 'px ' + String(layerOffset + 11) + 'px'
				]
				: [
					'0 0',
					String(layerOffset) + 'px ' + String(layerOffset) + 'px',
					String(layerOffset * 1.45) + 'px ' + String(layerOffset * 0.6) + 'px'
				];
			var colors = [renderState.primary, layerTwo, layerThree];
			var index;

			for (index = 0; index < layerCount; index++) {
				backgrounds.push(
					'radial-gradient('
						+ hexToRgba(colors[index], layerAlphas[index]) + ' 0 ' + String(dotCore) + 'px, '
						+ hexToRgba(colors[index], edgeStyle === 'crisp' ? layerAlphas[index] : layerAlphas[index] * 0.5) + ' ' + String(dotCore) + 'px ' + String(dotCore + dotFade) + 'px, '
						+ 'transparent ' + String(dotCore + dotFade) + 'px)'
				);
				positions.push(offsets[index] || offsets[offsets.length - 1]);
			}

			preview.style.backgroundImage = backgrounds.join(',');
			preview.style.backgroundPosition = positions.join(',');
			preview.style.backgroundSize = Array(layerCount).fill(spacing + 'px ' + spacing + 'px').join(',');
			return preview;
		}

		if (style === 'grid') {
			var cellSize = Math.max(12, Number(settings.cell_size || 28));
			var thickness = Math.max(1, Number(settings.line_thickness || 2));
			preview.style.backgroundImage =
				'linear-gradient(' + hexToRgba(renderState.primary, alpha) + ' ' + thickness + 'px, transparent ' + thickness + 'px),'
				+ 'linear-gradient(90deg, ' + hexToRgba(renderState.primary, alpha) + ' ' + thickness + 'px, transparent ' + thickness + 'px)';
			preview.style.backgroundSize = cellSize + 'px ' + cellSize + 'px';
			return preview;
		}

		if (style === 'lines') {
			var lineThickness = Math.max(1, Number(settings.line_thickness || 4));
			var lineSpacing = Math.max(8, Number(settings.line_spacing || 22));
			var lineAngle = String(Number(settings.angle || 135)) + 'deg';
			var lineLayerOffset = Math.max(0, Number(settings.layer_offset || 10));
			var lineSecondLayer = settings.second_layer === true || settings.second_layer === 'true' || Number(settings.second_layer || 0) === 1;
			var lineBackgrounds = [
				'repeating-linear-gradient(' + lineAngle + ', ' + hexToRgba(renderState.primary, alpha) + ' 0 ' + String(lineThickness) + 'px, transparent ' + String(lineThickness) + 'px ' + String(lineSpacing) + 'px)'
			];
			var linePositions = ['0 0'];

			if (lineSecondLayer) {
				lineBackgrounds.push('repeating-linear-gradient(' + lineAngle + ', ' + hexToRgba(renderState.accent, alpha * 0.72) + ' 0 ' + String(Math.max(1, lineThickness - 1)) + 'px, transparent ' + String(Math.max(1, lineThickness - 1)) + 'px ' + String(lineSpacing) + 'px)');
				linePositions.push(String(lineLayerOffset) + 'px ' + String(lineLayerOffset) + 'px');
			}

			preview.style.backgroundImage = lineBackgrounds.join(',');
			preview.style.backgroundPosition = linePositions.join(',');
			return preview;
		}

		if (style === 'circles_orbits') {
			rings = createNode('span', 'sonyra-color-controller__pattern-rings');
			rings.style.background =
				'radial-gradient(circle at 32% 42%, transparent 0 ' + String(Math.max(8, Number(settings.circle_size || 42) / 3)) + 'px, ' + hexToRgba(renderState.primary, alpha) + ' ' + String(Math.max(8, Number(settings.circle_size || 42) / 3)) + 'px ' + String(Math.max(10, Number(settings.circle_size || 42) / 3 + 2)) + 'px, transparent ' + String(Math.max(10, Number(settings.circle_size || 42) / 3 + 2)) + 'px 100%),'
				+ 'radial-gradient(circle at 70% 66%, transparent 0 ' + String(Math.max(7, Number(settings.circle_size || 42) / 4)) + 'px, ' + hexToRgba(renderState.accent, alpha * 0.82) + ' ' + String(Math.max(7, Number(settings.circle_size || 42) / 4)) + 'px ' + String(Math.max(9, Number(settings.circle_size || 42) / 4 + 2)) + 'px, transparent ' + String(Math.max(9, Number(settings.circle_size || 42) / 4 + 2)) + 'px 100%)';
			preview.appendChild(rings);
			return preview;
		}

		if (style === 'geometric_mosaic') {
			preview.style.backgroundImage =
				'linear-gradient(45deg, ' + hexToRgba(normalizeHex(renderState.colors.color_1 || '') || renderState.primary, alpha) + ' 25%, transparent 25%),'
				+ 'linear-gradient(-45deg, ' + hexToRgba(normalizeHex(renderState.colors.color_2 || '') || renderState.accent, alpha) + ' 25%, transparent 25%),'
				+ 'linear-gradient(45deg, transparent 75%, ' + hexToRgba(normalizeHex(renderState.colors.color_3 || '') || renderState.primary, alpha * 0.78) + ' 75%)';
			preview.style.backgroundSize = String(Math.max(16, Number(settings.shape_size || 28))) + 'px ' + String(Math.max(16, Number(settings.shape_size || 28))) + 'px';
			return preview;
		}

		if (style === 'soft_blobs') {
			preview.style.background =
				'radial-gradient(circle at 18% 24%, ' + hexToRgba(normalizeHex(renderState.colors.blob_1 || '') || renderState.primary, alpha) + ', transparent 38%),'
				+ 'radial-gradient(circle at 76% 28%, ' + hexToRgba(normalizeHex(renderState.colors.blob_2 || '') || renderState.accent, alpha * 0.92) + ', transparent 34%),'
				+ 'radial-gradient(circle at 54% 80%, ' + hexToRgba(normalizeHex(renderState.colors.blob_3 || '') || renderState.primary, alpha * 0.64) + ', transparent 36%)';
			preview.style.filter = 'blur(' + String(Math.max(0, Number(settings.blur || 24)) / 4) + 'px)';
			return preview;
		}

		if (style === 'light_beam') {
			overlay = createNode('span', 'sonyra-color-controller__pattern-overlay');
			overlay.style.background = 'linear-gradient(' + String(Number(settings.angle || 135)) + 'deg, transparent 0 18%, ' + hexToRgba(renderState.primary, alpha) + ' 36%, transparent 72%)';
			overlay.style.filter = 'blur(' + String(Math.max(6, Number(settings.softness || 4) * 3)) + 'px)';
			preview.appendChild(overlay);
			return preview;
		}

		if (style === 'waves') {
			texture = createNode('span', 'sonyra-color-controller__pattern-wave');
			texture.style.background = 'repeating-radial-gradient(circle at 0 50%, transparent 0 ' + String(Math.max(6, Number(settings.amplitude || 32) / 2)) + 'px, ' + hexToRgba(renderState.primary, alpha) + ' ' + String(Math.max(6, Number(settings.amplitude || 32) / 2)) + 'px ' + String(Math.max(10, Number(settings.amplitude || 32))) + 'px, transparent ' + String(Math.max(10, Number(settings.amplitude || 32))) + 'px ' + String(Math.max(18, Number(settings.amplitude || 32) + Number(settings.thickness || 10))) + 'px)';
			texture.style.transform = 'rotate(' + String(Number(settings.angle || 0)) + 'deg)';
			preview.appendChild(texture);
			return preview;
		}

		if (style === 'paint_strokes') {
			texture = createNode('span', 'sonyra-color-controller__pattern-texture');
			texture.style.background = 'linear-gradient(' + String(Number(settings.angle || 45)) + 'deg, transparent 0 12%, ' + hexToRgba(normalizeHex(renderState.colors.stroke_1 || '') || renderState.primary, alpha) + ' 12% 30%, transparent 30% 44%, ' + hexToRgba(normalizeHex(renderState.colors.stroke_2 || '') || renderState.accent, alpha * 0.9) + ' 44% 62%, transparent 62% 100%)';
			texture.style.filter = 'blur(' + String(Math.max(0, Number(settings.roughness || 3) * 2)) + 'px)';
			preview.appendChild(texture);
			return preview;
		}

		if (style === 'ink_blots') {
			texture = createNode('span', 'sonyra-color-controller__pattern-overlay');
			texture.style.background =
				'radial-gradient(circle at 28% 34%, ' + hexToRgba(normalizeHex(renderState.colors.blot_1 || '') || renderState.primary, alpha) + ' 0 12%, transparent 24%),'
				+ 'radial-gradient(circle at 70% 62%, ' + hexToRgba(normalizeHex(renderState.colors.blot_2 || '') || renderState.accent, alpha * 0.88) + ' 0 14%, transparent 28%)';
			preview.appendChild(texture);
			return preview;
		}

		if (style === 'marble') {
			texture = createNode('span', 'sonyra-color-controller__pattern-texture');
			texture.style.background = 'repeating-linear-gradient(' + String(Number(settings.curve || 52)) + 'deg, transparent 0 10px, ' + hexToRgba(normalizeHex(renderState.colors.line_color || '') || renderState.primary, alpha) + ' 10px 12px, transparent 12px 22px, ' + hexToRgba(normalizeHex(renderState.colors.accent_color || '') || renderState.accent, alpha * 0.72) + ' 22px 24px)';
			texture.style.filter = 'blur(' + String(Math.max(1, Number(settings.softness || 3) * 1.5)) + 'px)';
			preview.appendChild(texture);
			return preview;
		}

		if (style === 'noise') {
			noise = createNode('span', 'sonyra-color-controller__pattern-noise');
			noise.style.backgroundImage = 'radial-gradient(' + hexToRgba(renderState.primary, alpha * 0.48) + ' 0.8px, transparent 1px)';
			noise.style.backgroundSize = String(Math.max(4, Number(settings.particle_size || 6))) + 'px ' + String(Math.max(4, Number(settings.particle_size || 6))) + 'px';
			preview.appendChild(noise);
			return preview;
		}

		if (style === 'decorative_elements') {
			preview.style.backgroundImage = 'radial-gradient(' + hexToRgba(renderState.primary, alpha) + ' 3px, transparent 3px), radial-gradient(' + hexToRgba(renderState.accent, alpha * 0.72) + ' 2px, transparent 2px)';
			preview.style.backgroundSize = String(Math.max(18, Number(settings.spacing || 30))) + 'px ' + String(Math.max(18, Number(settings.spacing || 30))) + 'px';
			preview.style.backgroundPosition = '0 0, ' + String(Math.max(6, Number(settings.row_offset || 14))) + 'px ' + String(Math.max(6, Number(settings.row_offset || 14))) + 'px';
			return preview;
		}

		return preview;
	}

	function buildCardActions(entityType, itemId) {
		var actions = createNode('div', 'sonyra-pages-card-actions sonyra-color-controller__entity-actions');
		var edit = createButton('sonyra-pages-card-action', '', 'settings');
		var remove = createButton('sonyra-pages-card-action sonyra-pages-card-action-danger', '', 'trash');

		edit.setAttribute('aria-label', t('manager.design.colors.edit_action'));
		edit.setAttribute('title', t('manager.design.colors.edit_action'));
		edit.setAttribute('data-color-edit', entityType);
		edit.setAttribute('data-color-id', String(itemId || ''));
		edit.setAttribute('data-color-entity', entityType);

		remove.setAttribute('aria-label', t('manager.pages.actions.delete'));
		remove.setAttribute('title', t('manager.pages.actions.delete'));
		remove.setAttribute('data-color-delete', itemId);
		remove.setAttribute('data-color-entity', entityType);

		actions.appendChild(edit);
		actions.appendChild(remove);
		return actions;
	}

	function buildEntityCard(config) {
		var card = createNode('article', 'sonyra-pages-card sonyra-color-controller__entity-card ' + String(config.cardClassName || ''));
		var body = createNode('div', 'sonyra-color-controller__entity-row');
		var preview = createNode('div', 'sonyra-color-controller__entity-preview sonyra-color-controller__entity-preview-' + String(config.previewType || 'color'));
		var content = createNode('div', 'sonyra-color-controller__entity-content sonyra-color-controller__entity-copy');
		var meta = createNode('div', 'sonyra-color-controller__entity-meta ' + String(config.metaClassName || ''));
		var updated = createNode('span', 'sonyra-color-controller__entity-updated', formatUpdated(config.updatedAt));
		var actions = buildCardActions(config.entityType, config.itemId);
		var title = createNode('strong', 'sonyra-color-controller__entity-title', config.title || '—');

		title.setAttribute('title', config.title || '—');
		preview.appendChild(config.previewNode);
		(config.metaNodes || []).forEach(function (node) {
			if (node) {
				meta.appendChild(node);
			}
		});
		content.appendChild(title);
		content.appendChild(meta);
		content.appendChild(updated);
		body.appendChild(preview);
		body.appendChild(content);
		body.appendChild(actions);
		card.appendChild(body);

		return card;
	}

	function buildColorCard(item) {
		var meta = createNode('div', 'sonyra-color-controller__entity-meta');
		var type = getManagerUi().renderMetaChip({ className: 'sonyra-manager-meta-chip sonyra-color-controller__type-chip', text: t('manager.design.colors.color_card_label') });
		var hex = getManagerUi().renderMetaChip({ className: 'sonyra-manager-meta-chip sonyra-color-controller__value-chip', text: item.value_hex || '—' });
		type.setAttribute('title', t('manager.design.colors.color_card_label'));
		hex.setAttribute('title', item.value_hex || '—');
		meta.appendChild(type);
		meta.appendChild(hex);

		return buildEntityCard({
			cardClassName: 'sonyra-color-controller__entity-card-color',
			entityType: 'color',
			itemId: item.id,
			previewType: 'color',
			previewNode: renderColorSwatch(item.value_hex, true),
			title: item.name || '—',
			metaNodes: Array.prototype.slice.call(meta.childNodes),
			updatedAt: item.updated_at
		});
	}

	function buildGradientCard(item) {
		var type = getManagerUi().renderMetaChip({ className: 'sonyra-manager-meta-chip sonyra-color-controller__type-chip', text: t('manager.design.colors.gradient_card_label') });
		var angle = getManagerUi().renderMetaChip({ className: 'sonyra-manager-meta-chip sonyra-color-controller__value-chip', text: String(Number(item.angle || 135)) + '°' });
		type.setAttribute('title', t('manager.design.colors.gradient_card_label'));
		angle.setAttribute('title', String(Number(item.angle || 135)) + '°');

		return buildEntityCard({
			cardClassName: 'sonyra-color-controller__entity-card-gradient',
			entityType: 'gradient',
			itemId: item.id,
			previewType: 'gradient',
			previewNode: renderGradientPreview(item, true),
			title: item.name || '—',
			metaNodes: [type, angle],
			updatedAt: item.updated_at
		});
	}

	function buildPatternCard(item) {
		var definition = getPatternDefinition(item.pattern_type);
		var type = getManagerUi().renderMetaChip({ className: 'sonyra-manager-meta-chip sonyra-color-controller__type-chip', text: definition ? definition.label : String(item.pattern_type || '') });
		var group = getManagerUi().renderMetaChip({ className: 'sonyra-manager-meta-chip sonyra-color-controller__value-chip', text: definition ? definition.groupLabel : '' });
		type.setAttribute('title', definition ? definition.label : String(item.pattern_type || ''));
		if (definition && definition.groupLabel) {
			group.setAttribute('title', definition.groupLabel);
		}

		return buildEntityCard({
			cardClassName: 'sonyra-color-controller__entity-card-pattern',
			entityType: 'pattern',
			itemId: item.id,
			previewType: 'pattern',
			previewNode: renderPatternPreview(item, true),
			title: item.name || '—',
			metaClassName: 'sonyra-color-controller__entity-meta-stack',
			metaNodes: definition && definition.groupLabel ? [type, group] : [type],
			updatedAt: item.updated_at
		});
	}

	function buildEmptyState() {
		var empty = createNode('div', 'sonyra-color-controller__empty');
		empty.appendChild(createNode('strong', '', t('manager.design.colors.empty_title')));
		empty.appendChild(createNode('p', '', t('manager.design.colors.empty_description')));
		return empty;
	}

	function buildGrid() {
		var grid = createNode('div', 'sonyra-color-controller__grid sonyra-color-controller__grid-' + state.activeTab);
		var collection = getEntityCollection();
		var entityType = getCurrentEntityType();

		if (!collection.length) {
			grid.appendChild(buildEmptyState());
			return grid;
		}

		collection.forEach(function (item) {
			if (entityType === 'color') {
				grid.appendChild(buildColorCard(item));
			} else if (entityType === 'gradient') {
				grid.appendChild(buildGradientCard(item));
			} else {
				grid.appendChild(buildPatternCard(item));
			}
		});

		return grid;
	}

	function buildWorkspace() {
		var workspace = createNode('div', 'sonyra-color-controller__workspace');
		var message = buildMessage();

		workspace.appendChild(buildContext());
		workspace.appendChild(buildToolbar());
		if (message) {
			workspace.appendChild(message);
		}
		workspace.appendChild(buildGrid());

		return workspace;
	}

	function getItemById(entityType, itemId) {
		var collection = state.data[getCollectionKey(entityType)] || [];
		return collection.find(function (item) {
			return String(item.id || '') === String(itemId || '');
		}) || null;
	}

	function createColorDraft(item) {
		return {
			name: item ? String(item.name || '') : '',
			value_hex: normalizeHex(item && item.value_hex ? item.value_hex : '') || '#7C3AED'
		};
	}

	function createGradientDraft(item) {
		return {
			name: item ? String(item.name || '') : '',
			first_color_hex: normalizeHex(getGradientStopColor(item, 0)) || '#7C3AED',
			second_color_hex: normalizeHex(getGradientStopColor(item, 1)) || '#EC4899',
			angle: String(Number(item && item.angle !== undefined ? item.angle : 135))
		};
	}

	function cloneObject(value) {
		return value && typeof value === 'object' ? JSON.parse(JSON.stringify(value)) : {};
	}

	function getPatternDefinitionsByEditorKind(editorKind) {
		return Object.keys(patternRegistry).map(function (key) {
			return patternRegistry[key];
		}).filter(function (definition) {
			return definition && definition.editorKind === editorKind;
		});
	}

	function getDefaultPatternTypeForEditor(editorKind) {
		var definitions = getPatternDefinitionsByEditorKind(editorKind || 'graphic');
		return definitions.length ? definitions[0].key : getDefaultPatternType();
	}

	function createPatternMediaDraft(media) {
		var current = media && typeof media === 'object' ? media : {};

		return {
			attachment_id: current.attachment_id ? String(current.attachment_id) : '',
			url: current.url ? String(current.url) : '',
			thumb_url: current.thumb_url ? String(current.thumb_url) : '',
			name: current.name ? String(current.name) : '',
			mime_type: current.mime_type ? String(current.mime_type) : '',
			width: current.width ? String(current.width) : '',
			height: current.height ? String(current.height) : '',
			browserOpen: false,
			browserLoading: false,
			browserLoaded: false,
			browserError: '',
			libraryItems: []
		};
	}

	function mergePatternColors(definition, currentColors) {
		var colors = cloneObject(definition && definition.defaultColors ? definition.defaultColors : {});

		Object.keys(currentColors || {}).forEach(function (key) {
			var normalized = normalizeHex(currentColors[key]);
			if (normalized) {
				colors[key] = normalized;
			}
		});

		return colors;
	}

	function mergePatternSettings(definition, currentSettings) {
		var settings = cloneObject(definition && definition.defaultSettings ? definition.defaultSettings : {});

		Object.keys(currentSettings || {}).forEach(function (key) {
			settings[key] = currentSettings[key];
		});

		return settings;
	}

	function createPatternDraft(item) {
		var patternType = item && item.pattern_type ? String(item.pattern_type) : getDefaultPatternTypeForEditor('graphic');
		var definition = getPatternDefinition(patternType) || getPatternDefinition(getDefaultPatternType());
		var editorKind = definition && definition.editorKind ? definition.editorKind : 'graphic';

		return {
			name: item ? String(item.name || '') : '',
			editor_kind: editorKind,
			pattern_type: patternType,
			colors: mergePatternColors(definition, item && item.colors ? item.colors : {}),
			settings: mergePatternSettings(definition, item && item.settings ? item.settings : {}),
			media: createPatternMediaDraft(item && item.media ? item.media : {})
		};
	}

	function createDraft(entityType, item) {
		if (entityType === 'color') {
			return createColorDraft(item);
		}
		if (entityType === 'gradient') {
			return createGradientDraft(item);
		}
		return createPatternDraft(item);
	}

	function openColorLibraryFormModal(entityType, mode, itemId) {
		var type = String(entityType || getCurrentEntityType());
		var normalizedMode = mode === 'edit' ? 'edit' : 'create';
		var id = String(itemId || '');
		var item = normalizedMode === 'edit' && id ? getItemById(type, id) : null;
		var draft;

		if (normalizedMode === 'edit' && !item) {
			return;
		}

		draft = createDraft(type, item);
		if (type === 'pattern' && normalizedMode === 'create') {
			draft.editor_kind = '';
			draft.pattern_type = '';
			draft.colors = {};
			draft.settings = {};
		}

		state.modal = {
			kind: 'form',
			type: type,
			entityType: type,
			itemId: item ? String(item.id || '') : '',
			mode: normalizedMode,
			draft: draft,
			patternType: type === 'pattern' ? String(draft.pattern_type || '') : '',
			patternSourceSelected: type === 'pattern' ? normalizedMode === 'edit' : true,
			showTypePicker: false,
			editorKind: type === 'pattern' ? String(draft.editor_kind || 'graphic') : '',
			generatorExpanded: type === 'pattern' ? true : false,
			groupOpen: type === 'pattern' && draft.editor_kind && draft.pattern_type ? createPatternGroupState(String(draft.editor_kind || 'graphic'), String(draft.pattern_type || '')) : {},
			errors: {},
			errorHelpers: {},
			loading: false,
			scrollTop: 0
		};

		state.helpOpen = false;
		setMessage('', '');
		render();
	}

	function openFormModal(entityType, itemId) {
		openColorLibraryFormModal(entityType, itemId ? 'edit' : 'create', itemId || '');
	}

	function replaceNamePlaceholder(text, name) {
		return String(text || '').replace(/%s/g, name || '—');
	}

	function openDeleteModal(entityType, itemId, trigger) {
		var item = getItemById(entityType, itemId);
		if (!item || typeof window.openManagerDestructiveModal !== 'function') {
			return;
		}

		state.message = null;

		window.openManagerDestructiveModal({
			entityType: entityType,
			entityName: String(item.name || ''),
			title: replaceNamePlaceholder(t(getEntityDeleteTitleKey(entityType)), String(item.name || '')),
			subtitle: getDestructiveModalSubtitleText(),
			bodyText: replaceNamePlaceholder(t(getEntityDeleteBodyKey(entityType)), String(item.name || '')),
			warningText: '',
			confirmLabel: t('manager.pages.actions.delete'),
			cancelLabel: t('manager.pages.actions.cancel'),
			closeLabel: t('manager.pages.modal.close'),
			loadingLabel: t('manager.pages.modal.deleting'),
			errorFallbackText: t('manager.design.colors.save_failed'),
			trigger: trigger || document.activeElement,
			onConfirm: function () {
				return requestJson('DELETE', buildRequestUrl(entityType, itemId)).then(function (response) {
					applyResponse(response);
					render();
				});
			}
		});
	}

	function closeModal() {
		state.modal = null;
		render();
	}

	function buildRequestUrl(entityType, itemId) {
		var base = state.apiUrl.replace(/\/$/, '');
		var path = '/' + getCollectionKey(entityType);

		if (itemId) {
			path += '/' + encodeURIComponent(itemId);
		}

		return base + path;
	}

	function requestJson(method, url, body) {
		return fetch(url, {
			method: method,
			headers: {
				'Content-Type': 'application/json',
				'X-Sonyra-Color-Controller-Nonce': state.nonce
			},
			body: body ? JSON.stringify(body) : undefined,
			credentials: 'same-origin'
		}).then(function (response) {
			return response.json().catch(function () {
				return {};
			}).then(function (json) {
				if (!response.ok) {
					var error = new Error(json.message || t('manager.design.colors.save_failed'));
					error.payload = json;
					throw error;
				}
				return json;
			});
		});
	}

	function requestUpload(url, formData) {
		return fetch(url, {
			method: 'POST',
			headers: {
				'X-Sonyra-Color-Controller-Nonce': state.nonce
			},
			body: formData,
			credentials: 'same-origin'
		}).then(function (response) {
			return response.json().catch(function () {
				return {};
			}).then(function (json) {
				if (!response.ok) {
					var error = new Error(json.message || t('manager.design.colors.media_upload_failed'));
					error.payload = json;
					throw error;
				}
				return json;
			});
		});
	}

	function buildField(labelText, control, errorText, helperText, wide) {
		var field = createNode('label', 'sonyra-manager-pages-field' + (wide ? ' sonyra-manager-pages-field-wide' : ''));
		if (labelText) {
			field.appendChild(createNode('span', '', labelText));
		}
		field.appendChild(control);
		if (helperText) {
			field.appendChild(createNode('small', 'sonyra-color-controller__field-helper', helperText));
		}
		if (errorText) {
			field.appendChild(createNode('small', 'sonyra-color-controller__field-error', errorText));
		}
		return field;
	}

	function getModalFieldHelperText(modal, field, fallback) {
		if (modal && modal.errorHelpers && modal.errorHelpers[field]) {
			return modal.errorHelpers[field];
		}

		return fallback || '';
	}

	function buildTextInput(fieldKey, value) {
		var input = document.createElement('input');
		input.type = 'text';
		input.value = value || '';
		input.setAttribute('data-color-modal-field', fieldKey);
		return input;
	}

	function buildSwatchControl(fieldKey, value, labelKey) {
		var wrap = createNode('div', 'sonyra-color-controller__swatch-control');
		var button = createNode('button', 'sonyra-color-controller__swatch-button');
		var colorInput = document.createElement('input');
		var hexInput = buildTextInput(fieldKey, value || '');

		button.type = 'button';
		button.setAttribute('data-color-swatch-button', fieldKey);
		button.setAttribute('aria-label', t(labelKey));
		button.appendChild(renderColorSwatch(value || '#FFFFFF', false));

		colorInput.type = 'color';
		colorInput.value = normalizeHex(value || '#7C3AED') || '#7C3AED';
		colorInput.setAttribute('data-color-picker-input', fieldKey);
		colorInput.className = 'sonyra-color-controller__native-picker';

		hexInput.classList.add('sonyra-color-controller__hex-input');
		wrap.appendChild(button);
		wrap.appendChild(hexInput);
		wrap.appendChild(colorInput);

		return wrap;
	}

	function buildDirectionControl(value) {
		var wrap = createNode('div', 'sonyra-color-controller__direction-control');
		['0', '45', '90', '135', '180'].forEach(function (angle) {
			var chip = createButton('sonyra-pages-filter-chip', t('manager.design.colors.direction_' + angle));
			chip.setAttribute('data-color-angle', angle);
			chip.setAttribute('aria-pressed', String(value || '135') === angle ? 'true' : 'false');
			chip.classList.toggle('is-active', String(value || '135') === angle);
			wrap.appendChild(chip);
		});
		return wrap;
	}

	function buildEnumChips(fieldKey, currentValue, options) {
		var wrap = createNode('div', 'sonyra-color-controller__option-chips');
		options.forEach(function (option) {
			var chip = createButton('sonyra-pages-filter-chip', option.label);
			chip.setAttribute('data-color-option-field', fieldKey);
			chip.setAttribute('data-color-option-value', option.value);
			chip.setAttribute('aria-pressed', String(currentValue || '') === option.value ? 'true' : 'false');
			chip.classList.toggle('is-active', String(currentValue || '') === option.value);
			wrap.appendChild(chip);
		});
		return wrap;
	}

	function buildColorEditorBody(modal) {
		var draft = modal.draft;
		var body = createNode('div', 'sonyra-color-controller__modal-form');
		var row = createNode('div', 'sonyra-color-controller__editor-preview-row');
		var live = createNode('div', 'sonyra-color-controller__editor-color-preview');

		live.style.background = normalizeHex(draft.value_hex) || '#7C3AED';
		row.appendChild(live);
		row.appendChild(createNode('span', 'sonyra-color-controller__editor-preview-copy', t('manager.design.colors.color_picker_hint')));

		body.appendChild(buildField(t('manager.design.colors.field_name'), buildTextInput('name', draft.name || ''), modal.errors.name, getModalFieldHelperText(modal, 'name', ''), true));
		body.appendChild(buildField(t('manager.design.colors.field_color'), buildSwatchControl('value_hex', draft.value_hex || '#7C3AED', 'manager.design.colors.field_color'), modal.errors.value_hex, t('manager.design.colors.color_hex_hint'), true));
		body.appendChild(row);
		return body;
	}

	function buildGradientEditorBody(modal) {
		var draft = modal.draft;
		var body = createNode('div', 'sonyra-color-controller__modal-form');
		var preview = renderGradientPreview({
			angle: Number(draft.angle || 135),
			stops: [
				{ color_hex: normalizeHex(draft.first_color_hex) || '#7C3AED', position: 0 },
				{ color_hex: normalizeHex(draft.second_color_hex) || '#EC4899', position: 100 }
			]
		}, true);

		body.appendChild(buildField(t('manager.design.colors.field_name'), buildTextInput('name', draft.name || ''), modal.errors.name, getModalFieldHelperText(modal, 'name', ''), true));
		body.appendChild(buildField(t('manager.design.colors.gradient_preview_label'), preview, '', '', true));
		body.appendChild(buildField(t('manager.design.colors.field_first_color'), buildSwatchControl('first_color_hex', draft.first_color_hex || '#7C3AED', 'manager.design.colors.field_first_color'), modal.errors.stops || modal.errors.first_color_hex, '', true));
		body.appendChild(buildField(t('manager.design.colors.field_second_color'), buildSwatchControl('second_color_hex', draft.second_color_hex || '#EC4899', 'manager.design.colors.field_second_color'), modal.errors.stops || modal.errors.second_color_hex, '', true));
		body.appendChild(buildField(t('manager.design.colors.gradient_direction_label'), buildDirectionControl(draft.angle || '135'), modal.errors.angle, '', true));
		return body;
	}

	function getControlGroupLabel(groupKey) {
		return t(String(groupKey || 'manager.design.colors.group_composition'));
	}

	function findPatternControlDefinition(definition, controlKey) {
		return (definition && Array.isArray(definition.controls) ? definition.controls : []).find(function (control) {
			return String(control && control.key ? control.key : '') === String(controlKey || '');
		}) || null;
	}

	function findPatternColorSlotDefinition(definition, slotKey) {
		return (definition && Array.isArray(definition.colorSlots) ? definition.colorSlots : []).find(function (slot) {
			return String(slot && slot.key ? slot.key : '') === String(slotKey || '');
		}) || null;
	}

	function getPatternDraftValue(path, fallback) {
		var current = state.modal && state.modal.draft ? state.modal.draft : null;
		var parts = String(path || '').split('.');

		parts.forEach(function (part) {
			if (!current || typeof current !== 'object') {
				current = undefined;
				return;
			}
			current = current[part];
		});

		return current !== undefined ? current : fallback;
	}

	function setPatternDraftValue(path, value) {
		var current = state.modal && state.modal.draft ? state.modal.draft : null;
		var parts = String(path || '').split('.');

		if (!current || !parts.length) {
			return;
		}

		while (parts.length > 1) {
			var segment = parts.shift();
			if (!current[segment] || typeof current[segment] !== 'object') {
				current[segment] = {};
			}
			current = current[segment];
		}

		current[parts[0]] = value;
	}

	function normalizePatternFieldValue(control, value) {
		var type = String(control && control.type ? control.type : 'slider');

		if (type === 'switch') {
			return value === true || value === 'true' || value === 1 || value === '1';
		}

		if (type === 'slider' || type === 'stepper' || type === 'angle') {
			return Number(value || 0);
		}

		return String(value || '');
	}

	function getPatternValueText(options) {
		var control = options || {};
		var type = String(control.type || 'slider');
		var unit = String(control.unit || '');
		var value = control.value;

		if (type === 'angle') {
			return String(Number(value || 0)) + '°';
		}

		if (type === 'segmented' || type === 'position') {
			return t('manager.design.colors.option_' + String(value || ''), String(value || ''));
		}

		if (type === 'switch') {
			return value ? t('manager.pages.widgets.status_enabled') : t('manager.pages.widgets.status_disabled');
		}

		return String(value !== undefined ? value : '') + unit;
	}

	function isVisibleByRule(rule, draft) {
		if (!rule || typeof rule !== 'object') {
			return true;
		}

		var currentValue = draft && draft.settings ? draft.settings[rule.setting] : undefined;

		if (rule.equals !== undefined) {
			return currentValue === rule.equals;
		}

		if (rule.min !== undefined) {
			return Number(currentValue || 0) >= Number(rule.min);
		}

		return true;
	}

	function applyRangeFill(input) {
		if (!input || input.type !== 'range') {
			return;
		}

		var min = Number(input.min || 0);
		var max = Number(input.max || 100);
		var value = Number(input.value || min);
		var percent = max <= min ? 0 : ((value - min) / (max - min)) * 100;

		input.style.setProperty('--sonyra-range-fill', String(Math.max(0, Math.min(100, percent))) + '%');
	}

	function buildPatternRangeControl(config) {
		var settings = config || {};
		var wrap = createNode('div', 'sonyra-color-controller__pattern-range');
		var row = createNode('div', 'sonyra-color-controller__pattern-range-row');
		var input = document.createElement('input');
		var valueNode = createNode('span', 'sonyra-color-controller__pattern-range-value', getPatternValueText(settings));

		input.type = 'range';
		input.min = String(settings.min !== null && settings.min !== undefined ? settings.min : 0);
		input.max = String(settings.max !== null && settings.max !== undefined ? settings.max : 100);
		input.step = String(settings.step !== null && settings.step !== undefined ? settings.step : 1);
		input.value = String(settings.value !== undefined ? settings.value : settings.min || 0);
		input.setAttribute('data-color-modal-field', settings.path || '');
		input.setAttribute('aria-valuenow', String(input.value));
		input.setAttribute('data-color-control-key', String(settings.controlKey || ''));
		valueNode.setAttribute('data-color-range-value', settings.path || '');
		applyRangeFill(input);

		row.appendChild(input);
		row.appendChild(valueNode);
		wrap.appendChild(row);

		return wrap;
	}

	function buildSegmentedOptions(path, control, value) {
		return buildEnumChips(path, String(value || ''), (control.options || []).map(function (optionValue) {
			return {
				value: optionValue,
				label: t('manager.design.colors.option_' + optionValue, optionValue)
			};
		}));
	}

	function buildPatternSwitchRow(config) {
		var settings = config || {};
		var row = createNode('div', 'sonyra-color-controller__pattern-switch-row');
		var labelWrap = createNode('div', 'sonyra-color-controller__pattern-switch-copy');
		var label = createNode('span', 'sonyra-color-controller__pattern-switch-label', settings.label || '');
		var button = createButton('sonyra-manager-pages-switch sonyra-color-controller__pattern-switch');
		var switchControl = createNode('span', 'sonyra-manager-pages-switch-control');
		var helper;

		if (settings.helpKey) {
			helper = createNode('small', 'sonyra-color-controller__pattern-switch-helper', t(settings.helpKey));
			labelWrap.appendChild(helper);
		}

		button.setAttribute('data-color-option-field', settings.path || '');
		button.setAttribute('data-color-option-value', settings.value ? 'false' : 'true');
		button.setAttribute('data-color-control-key', String(settings.controlKey || ''));
		button.setAttribute('role', 'switch');
		button.setAttribute('aria-checked', settings.value ? 'true' : 'false');
		button.setAttribute('aria-label', String(settings.label || ''));
		button.classList.toggle('sonyra-manager-pages-switch-active', !!settings.value);
		button.insertBefore(switchControl, button.firstChild);
		labelWrap.insertBefore(label, labelWrap.firstChild);
		row.appendChild(labelWrap);
		row.appendChild(button);
		return row;
	}

	function buildAngleControl(path, control, value) {
		var wrap = createNode('div', 'sonyra-color-controller__direction-control');
		['0', '45', '90', '135', '180'].forEach(function (angle) {
			var chip = createButton('sonyra-pages-filter-chip', angle + '°');
			chip.setAttribute('data-color-option-field', path);
			chip.setAttribute('data-color-option-value', angle);
			chip.setAttribute('aria-pressed', String(value || '0') === angle ? 'true' : 'false');
			chip.classList.toggle('is-active', String(value || '0') === angle);
			wrap.appendChild(chip);
		});
		return wrap;
	}

	function buildPositionControl(path, value) {
		var positions = [
			'top-left', 'top', 'top-right',
			'left', 'center', 'right',
			'bottom-left', 'bottom', 'bottom-right'
		];
		var grid = createNode('div', 'sonyra-color-controller__position-grid');

		positions.forEach(function (position) {
			var button = createNode('button', 'sonyra-color-controller__position-cell' + (position === value ? ' is-active' : ''), '');
			button.type = 'button';
			button.setAttribute('data-color-option-field', path);
			button.setAttribute('data-color-option-value', position);
			button.setAttribute('aria-label', t('manager.design.colors.option_' + position, position));
			grid.appendChild(button);
		});

		return grid;
	}

	function draftColorValue(draft, key, fallback) {
		return normalizeHex(draft && draft.colors && draft.colors[key] ? draft.colors[key] : '') || normalizeHex(fallback || '') || '#FFFFFF';
	}

	function resolvePatternColorSlot(definition, slotDescriptor) {
		var descriptor = typeof slotDescriptor === 'string' ? { key: slotDescriptor } : (slotDescriptor || {});
		var slot = findPatternColorSlotDefinition(definition, descriptor.key);

		if (!slot) {
			return null;
		}

		if (descriptor.visible_when) {
			slot = Object.assign({}, slot, { visible_when: descriptor.visible_when });
		}

		return slot;
	}

	function buildPatternColorField(modal, definition, slotDescriptor) {
		var slot = resolvePatternColorSlot(definition, slotDescriptor);
		var colorsError = modal.errors.colors || '';
		var fallbackColor;

		if (!slot || !isVisibleByRule(slot.visible_when, modal.draft)) {
			return null;
		}

		fallbackColor = definition && definition.defaultColors ? definition.defaultColors[slot.key] : '#FFFFFF';

		return buildField(
			slot.label,
			buildSwatchControl('colors.' + slot.key, draftColorValue(modal.draft, slot.key, fallbackColor), slot.labelKey || ''),
			colorsError,
			'',
			true
		);
	}

	function buildPatternControlField(modal, definition, controlKey) {
		var control = findPatternControlDefinition(definition, controlKey);
		var path;
		var value;
		var normalizedValue;
		var controlNode;

		if (!control || !isVisibleByRule(control.visible_when, modal.draft)) {
			return null;
		}

		path = 'settings.' + control.key;
		value = modal.draft.settings && modal.draft.settings[control.key] !== undefined ? modal.draft.settings[control.key] : definition.defaultSettings[control.key];
		normalizedValue = normalizePatternFieldValue(control, value);

		if (control.type === 'slider' || control.type === 'stepper') {
			controlNode = buildPatternRangeControl({
				path: path,
				label: control.label,
				min: control.min,
				max: control.max,
				step: control.step,
				value: normalizedValue,
				unit: control.unit || '',
				type: control.type,
				controlKey: control.key
			});
		} else if (control.type === 'switch') {
			controlNode = buildPatternSwitchRow({
				path: path,
				label: control.label,
				value: normalizedValue,
				controlKey: control.key
			});
			return buildField('', controlNode, modal.errors[control.key] || '', '', true);
		} else if (control.type === 'segmented') {
			controlNode = buildSegmentedOptions(path, control, normalizedValue);
		} else if (control.type === 'angle') {
			controlNode = buildAngleControl(path, control, normalizedValue);
		} else if (control.type === 'position') {
			controlNode = buildPositionControl(path, normalizedValue);
		} else {
			controlNode = buildPatternRangeControl({
				path: path,
				label: control.label,
				min: control.min,
				max: control.max,
				step: control.step,
				value: normalizedValue,
				unit: control.unit || '',
				type: control.type,
				controlKey: control.key
			});
		}

		return buildField(control.label, controlNode, modal.errors[control.key] || '', '', true);
	}

	function getPatternControlByKey(controlKey) {
		var definition = state.modal && state.modal.draft ? getPatternDefinition(state.modal.draft.pattern_type) : null;
		return findPatternControlDefinition(definition, controlKey);
	}

	function buildPatternGeneratorPicker(currentType, editorKind) {
		var grid = createNode('div', 'sonyra-color-controller__pattern-gallery-grid sonyra-color-controller__pattern-generator-grid');

		getPatternDefinitionsByEditorKind(editorKind || 'graphic').forEach(function (definition) {
			var button = createNode('button', 'sonyra-color-controller__pattern-tile' + (definition.key === currentType ? ' is-active' : ''));
			var preview = renderPatternPreview({
				pattern_type: definition.key,
				colors: definition.defaultColors,
				settings: definition.defaultSettings,
				media: {}
			}, false, definition);
			var copy = createNode('div', 'sonyra-color-controller__pattern-tile-copy');

			button.type = 'button';
			button.setAttribute('data-color-pattern-type', definition.key);
			button.appendChild(preview);
			copy.appendChild(createNode('strong', 'sonyra-color-controller__pattern-tile-title', definition.label));
			copy.appendChild(createNode('span', 'sonyra-color-controller__pattern-tile-description', definition.description));
			button.appendChild(copy);
			grid.appendChild(button);
		});

		return grid;
	}

	function buildPatternAccordionGroup(config) {
		var settings = config || {};
		var key = String(settings.key || '');
		var groupId = 'sonyra-pattern-group-' + key.replace(/[^a-z0-9_-]/gi, '-');
		var section = createNode('section', 'sonyra-color-controller__pattern-accordion sonyra-color-controller__pattern-accordion-' + String(settings.kind || 'group'));
		var button = createNode('button', 'sonyra-color-controller__pattern-accordion-toggle');
		var head = createNode('span', 'sonyra-color-controller__pattern-accordion-head');
		var title = createNode('span', 'sonyra-color-controller__pattern-accordion-title', settings.title || '');
		var summary = createNode('span', 'sonyra-color-controller__pattern-accordion-summary', settings.summary || '');
		var chevron = createNode('span', 'sonyra-color-controller__pattern-accordion-chevron', '');
		var body = createNode('div', 'sonyra-color-controller__pattern-accordion-body');
		var isOpen = settings.defaultOpen !== false;

		section.setAttribute('data-color-pattern-group-root', key);
		button.type = 'button';
		button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
		button.setAttribute('aria-controls', groupId);
		if (settings.kind === 'generator') {
			button.setAttribute('data-color-pattern-generator-toggle', 'true');
		} else {
			button.setAttribute('data-color-pattern-group-toggle', key);
		}
		summary.setAttribute('data-color-pattern-group-summary', key);
		if (settings.kind === 'generator') {
			summary.setAttribute('data-color-pattern-generator-summary', 'true');
		}
		if (!settings.summary) {
			summary.hidden = true;
		}
		head.appendChild(title);
		head.appendChild(summary);
		button.appendChild(head);
		button.appendChild(chevron);
		body.id = groupId;
		if (!isOpen) {
			body.hidden = true;
		}
		if (settings.body) {
			body.appendChild(settings.body);
		}
		section.appendChild(button);
		section.appendChild(body);

		return section;
	}

	function updatePatternAccordionState(groupKey, isOpen) {
		var rootNode = root.querySelector('[data-color-pattern-group-root="' + String(groupKey || '') + '"]');
		var button;
		var body;

		if (!rootNode) {
			return;
		}

		button = rootNode.querySelector('.sonyra-color-controller__pattern-accordion-toggle');
		body = rootNode.querySelector('.sonyra-color-controller__pattern-accordion-body');

		if (button) {
			button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
		}

		if (body) {
			body.hidden = !isOpen;
		}
	}

	function togglePatternGroup(groupKey) {
		if (!state.modal || state.modal.entityType !== 'pattern') {
			return;
		}

		state.modal.groupOpen = state.modal.groupOpen && typeof state.modal.groupOpen === 'object' ? state.modal.groupOpen : {};
		state.modal.groupOpen[groupKey] = !(state.modal.groupOpen[groupKey] !== false);
		updatePatternAccordionState(groupKey, state.modal.groupOpen[groupKey] !== false);
	}

	function togglePatternGeneratorPanel() {
		if (!state.modal || state.modal.entityType !== 'pattern') {
			return;
		}

		state.modal.generatorExpanded = !(state.modal.generatorExpanded !== false);
		state.modal.groupOpen = state.modal.groupOpen && typeof state.modal.groupOpen === 'object' ? state.modal.groupOpen : {};
		state.modal.groupOpen.__generator = state.modal.generatorExpanded !== false;
		updatePatternAccordionState('__generator', state.modal.generatorExpanded !== false);
	}

	function buildMediaLibrary(draft) {
		var block = createNode('div', 'sonyra-color-controller__media-library');

		if (draft.media.browserLoading) {
			block.appendChild(createNode('p', 'sonyra-color-controller__media-state', t('manager.design.colors.media_loading')));
			return block;
		}

		if (draft.media.browserError) {
			block.appendChild(createNode('p', 'sonyra-color-controller__media-state is-error', draft.media.browserError));
			return block;
		}

		if (!draft.media.libraryItems.length) {
			block.appendChild(createNode('p', 'sonyra-color-controller__media-state', t('manager.design.colors.media_library_empty')));
			return block;
		}

		draft.media.libraryItems.forEach(function (item) {
			var button = createNode('button', 'sonyra-color-controller__media-tile');
			var image = createNode('img', 'sonyra-color-controller__media-thumb');
			var copy = createNode('div', 'sonyra-color-controller__media-copy');

			button.type = 'button';
			button.setAttribute('data-color-media-select', String(item.attachment_id || ''));
			image.src = String(item.thumb_url || item.url || '');
			image.alt = '';
			copy.appendChild(createNode('strong', 'sonyra-color-controller__media-name', String(item.name || '')));
			copy.appendChild(createNode('span', 'sonyra-color-controller__media-meta', String(item.mime_type || '')));
			button.appendChild(image);
			button.appendChild(copy);
			block.appendChild(button);
		});

		return block;
	}

	function buildMediaControl(modal) {
		var draft = modal.draft;
		var wrap = createNode('div', 'sonyra-color-controller__media-block');
		var actions = createNode('div', 'sonyra-color-controller__media-actions');
		var upload = createButton('sonyra-manager-pages-secondary', draft.media.attachment_id ? t('manager.design.colors.media_replace') : t('manager.design.colors.media_upload'), 'photo');
		var select = createButton('sonyra-manager-pages-secondary', t('manager.design.colors.media_select'), 'file');
		var remove = createButton('sonyra-manager-pages-secondary', t('manager.design.colors.media_remove'), 'trash');
		var fileInput = document.createElement('input');

		upload.setAttribute('data-color-media-upload', 'true');
		select.setAttribute('data-color-media-open-library', 'true');
		remove.setAttribute('data-color-media-remove', 'true');

		fileInput.type = 'file';
		fileInput.accept = '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp';
		fileInput.hidden = true;
		fileInput.setAttribute('data-color-media-file', 'true');

		actions.appendChild(upload);
		actions.appendChild(select);
		if (draft.media.attachment_id) {
			actions.appendChild(remove);
		}

		if (draft.media.thumb_url || draft.media.url) {
			var preview = createNode('div', 'sonyra-color-controller__media-preview');
			var image = createNode('img', 'sonyra-color-controller__media-preview-image');
			var meta = createNode('div', 'sonyra-color-controller__media-preview-copy');

			image.src = String(draft.media.thumb_url || draft.media.url);
			image.alt = '';
			meta.appendChild(createNode('strong', 'sonyra-color-controller__media-name', draft.media.name || ''));
			meta.appendChild(createNode('span', 'sonyra-color-controller__media-meta', draft.media.mime_type || ''));
			preview.appendChild(image);
			preview.appendChild(meta);
			wrap.appendChild(preview);
		} else {
			wrap.appendChild(createNode('p', 'sonyra-color-controller__media-state', t('manager.design.colors.media_empty')));
		}

		wrap.appendChild(actions);
		wrap.appendChild(fileInput);

		if (draft.media.browserOpen) {
			wrap.appendChild(createNode('strong', 'sonyra-color-controller__media-library-title', t('manager.design.colors.media_library_title')));
			wrap.appendChild(buildMediaLibrary(draft));
		}

		return wrap;
	}

	function buildPatternPreviewFromDraft(draft) {
		return renderPatternPreview({
			pattern_type: draft.pattern_type,
			colors: draft.colors,
			settings: draft.settings,
			media: draft.media
		}, true, getPatternDefinition(draft.pattern_type));
	}


	function getPatternFlowStep(modal) {
		if (!modal || !modal.draft) {
			return 'source-choice';
		}

		if (!modal.draft.editor_kind) {
			return 'source-choice';
		}

		if (modal.draft.editor_kind === 'image') {
			return 'image-editor';
		}

		return 'graphic-editor';
	}

	function setPatternEditorKind(kind) {
		var definition;

		if (!state.modal || state.modal.entityType !== 'pattern' || !state.modal.draft) {
			return;
		}

		if (kind === 'image') {
			definition = getPatternDefinition('image_pattern');
			state.modal.draft.editor_kind = 'image';
			state.modal.draft.pattern_type = 'image_pattern';
		} else {
			state.modal.draft.editor_kind = 'graphic';
			state.modal.draft.pattern_type = state.modal.draft.pattern_type && state.modal.draft.pattern_type !== 'image_pattern'
				? state.modal.draft.pattern_type
				: getDefaultPatternType();
			definition = getPatternDefinition(state.modal.draft.pattern_type) || getPatternDefinition(getDefaultPatternType());
		}

		state.modal.patternType = state.modal.draft.pattern_type;
		state.modal.draft.colors = mergePatternColors(definition, state.modal.draft.colors || {});
		state.modal.draft.settings = mergePatternSettings(definition, state.modal.draft.settings || {});
		state.modal.groupOpen = createPatternGroupState(state.modal.draft.editor_kind, state.modal.draft.pattern_type);
		render();
	}

	function resetPatternEditorSource() {
		if (!state.modal || state.modal.entityType !== 'pattern' || !state.modal.draft) {
			return;
		}

		state.modal.draft.editor_kind = '';
		state.modal.draft.pattern_type = '';
		state.modal.patternType = '';
		state.modal.groupOpen = {};
		render();
	}

	function getPatternDefinitionsList() {
		return Object.keys(patternRegistry).map(function (key) {
			return patternRegistry[key];
		}).filter(function (definition) {
			return !!definition;
		});
	}

	function getPatternControlDefinition(definition, controlKey) {
		return findPatternControlDefinition(definition, controlKey);
	}

	function shouldShowPatternSlot(slot, draft) {
		return !!slot && isVisibleByRule(slot.visible_when, draft);
	}

	function shouldShowPatternSourceChoice(modal) {
		if (!modal || modal.type !== 'pattern') {
			return false;
		}
		if (modal.mode === 'edit') {
			return false;
		}
		return modal.patternSourceSelected !== true;
	}

	function buildPatternSourceChoice(modal) {
		var wrap = document.createElement('div');
		wrap.className = 'sonyra-color-controller__pattern-source-choice';
		wrap.setAttribute('data-pattern-source-choice-screen', 'true');
		var grid = document.createElement('div');
		grid.className = 'sonyra-color-controller__pattern-source-choice-grid';
		grid.appendChild(buildPatternSourceChoiceCard({
			kind: 'graphic',
			titleKey: 'manager.design.colors.pattern_source_graphic_title',
			descriptionKey: 'manager.design.colors.pattern_source_graphic_description'
		}));
		grid.appendChild(buildPatternSourceChoiceCard({
			kind: 'image',
			titleKey: 'manager.design.colors.pattern_source_image_title',
			descriptionKey: 'manager.design.colors.pattern_source_image_description'
		}));
		wrap.appendChild(grid);
		assertPatternSourceChoiceDom(wrap);
		return wrap;
	}

	function buildPatternSourceChoiceCard(config) {
		var button = document.createElement('button');
		button.type = 'button';
		button.className = 'sonyra-color-controller__pattern-source-card';
		button.setAttribute('data-pattern-source-choice', config.kind);
		var titleText = getSonyraSourceText(config.titleKey, config.titleFallback);
		var descriptionText = getSonyraSourceText(config.descriptionKey, config.descriptionFallback);
		button.setAttribute('aria-label', titleText);
		var icon = document.createElement('span');
		icon.className = 'sonyra-color-controller__pattern-source-card-icon';
		icon.setAttribute('aria-hidden', 'true');
		icon.innerHTML = config.kind === 'image'
			? '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none"><rect x="4.5" y="5.5" width="15" height="13" rx="3" stroke="rgba(255,255,255,.94)" stroke-width="2"></rect><circle cx="15.6" cy="9.1" r="1.45" fill="rgba(255,255,255,.94)"></circle><path d="M6.8 16.4l4.1-4.4 3 3.1 1.8-1.9 2.7 3.2" stroke="rgba(255,255,255,.94)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>'
			: '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none"><circle cx="12" cy="12" r="6.8" fill="rgba(255,255,255,.94)"></circle></svg>';
		var copy = document.createElement('span');
		copy.className = 'sonyra-color-controller__pattern-source-card-copy';
		var title = document.createElement('strong');
		title.className = 'sonyra-color-controller__pattern-source-card-title';
		title.textContent = titleText;
		var description = document.createElement('span');
		description.className = 'sonyra-color-controller__pattern-source-card-description';
		description.textContent = descriptionText;
		copy.appendChild(title);
		copy.appendChild(description);
		button.appendChild(icon);
		button.appendChild(copy);
		button.addEventListener('click', function () {
			selectPatternSourceChoice(config.kind);
		});
		return button;
	}

	function assertPatternSourceChoiceDom(root) {
		if (!root) {
			throw new Error('SONYRA pattern source choice missing root');
		}
		var cards = root.querySelectorAll('[data-pattern-source-choice]');
		var titles = root.querySelectorAll('.sonyra-color-controller__pattern-source-card-title');
		var descriptions = root.querySelectorAll('.sonyra-color-controller__pattern-source-card-description');
		var actions = root.querySelectorAll('.sonyra-color-controller__pattern-source-card' + '-action');
		var bodyTitles = root.querySelectorAll('.sonyra-color-controller__pattern-source-choice' + '-title');
		var bodyDescriptions = root.querySelectorAll('.sonyra-color-controller__pattern-source-choice' + '-description');
		var svgs = root.querySelectorAll('.sonyra-color-controller__pattern-source-card-icon svg');
		if (cards.length !== 2) {
			throw new Error('SONYRA pattern source choice must render exactly 2 cards');
		}
		if (titles.length !== 2 || descriptions.length !== 2) {
			throw new Error('SONYRA pattern source choice visible title/description nodes missing');
		}
		if (svgs.length !== 2) {
			throw new Error('SONYRA pattern source choice must render exactly 2 SVG icons');
		}
		if (actions.length !== 0) {
			throw new Error('SONYRA pattern source choice action nodes are forbidden');
		}
		if (bodyTitles.length !== 0 || bodyDescriptions.length !== 0) {
			throw new Error('SONYRA pattern source choice body title/description are forbidden');
		}
		Array.prototype.forEach.call(titles, function (node) {
			if (!node.textContent || !node.textContent.trim()) {
				throw new Error('SONYRA pattern source choice card title is empty');
			}
		});
		Array.prototype.forEach.call(descriptions, function (node) {
			if (!node.textContent || !node.textContent.trim()) {
				throw new Error('SONYRA pattern source choice card description is empty');
			}
		});
	}

	function selectPatternSourceChoice(kind) {
		if (!state.modal || state.modal.type !== 'pattern' || !state.modal.draft) {
			return;
		}

		state.modal.patternSourceSelected = true;

		if (kind === 'image') {
			state.modal.draft.editor_kind = 'image';
			state.modal.draft.pattern_type = 'image_pattern';
		} else {
			state.modal.draft.editor_kind = 'graphic';
			state.modal.draft.pattern_type = state.modal.draft.pattern_type && state.modal.draft.pattern_type !== 'image_pattern'
				? state.modal.draft.pattern_type
				: getDefaultPatternType();
		}

		state.modal.patternType = state.modal.draft.pattern_type;

		state.modal.groupOpen = createPatternGroupState(
			state.modal.draft.editor_kind,
			state.modal.draft.pattern_type
		);

		rerenderActiveModal();
	}

	function buildPatternGroupBody(modal, definition, group) {
		var body = createNode('div', 'sonyra-color-controller__pattern-group-fields');

		if (group.media) {
			body.appendChild(buildField(
				t('manager.design.colors.pattern_media_label'),
				buildMediaControl(modal),
				modal.errors.image_attachment_id || '',
				t('manager.design.colors.image_requirement_hint'),
				true
			));
		}

		(group.colorSlots || []).forEach(function (slotDescriptor) {
			var field = buildPatternColorField(modal, definition, slotDescriptor);
			if (field) {
				body.appendChild(field);
			}
		});

		(group.controlKeys || []).forEach(function (controlKey) {
			var field = buildPatternControlField(modal, definition, controlKey);
			if (field) {
				body.appendChild(field);
			}
		});

		return body.childNodes.length ? body : null;
	}


	function buildPatternNameField(modal) {
		var draft = modal && modal.draft ? modal.draft : {};
		var field = createNode('div', 'sonyra-color-controller__pattern-simple-field');
		var label = createNode('label', 'sonyra-color-controller__pattern-simple-label', t('manager.design.colors.pattern_name_label'));
		var input = createNode('input', 'sonyra-color-controller__pattern-simple-input');
		var inputId = 'sonyra-pattern-name-' + String(draft.id || 'new');
		label.setAttribute('for', inputId);
		input.id = inputId;
		input.type = 'text';
		input.value = draft.name || '';
		input.placeholder = t('manager.design.colors.pattern_name_placeholder');
		input.setAttribute('data-pattern-name-input', 'true');
		field.appendChild(label);
		field.appendChild(input);
		return field;
	}

	function buildPatternPreviewBlock(modal) {
		var draft = modal && modal.draft ? modal.draft : {};
		var block = createNode('div', 'sonyra-color-controller__pattern-simple-preview-block');
		var previewWrap = createNode('div', 'sonyra-color-controller__pattern-simple-preview-wrap');
		block.appendChild(createNode('div', 'sonyra-color-controller__pattern-simple-section-title', t('manager.design.colors.pattern_preview_label')));
		previewWrap.appendChild(buildPatternPreviewFromDraft({
			pattern_type: draft.pattern_type || getDefaultPatternType(),
			colors: draft.colors || {},
			settings: draft.settings || {},
			media: draft.media || {}
		}));
		block.appendChild(previewWrap);
		return block;
	}

	function buildPatternGraphicTypeSelect(modal) {
		var draft = modal && modal.draft ? modal.draft : {};
		var field = createNode('div', 'sonyra-color-controller__pattern-simple-field');
		var grid = createNode('div', 'sonyra-color-controller__pattern-simple-type-grid');
		field.appendChild(createNode('div', 'sonyra-color-controller__pattern-simple-label', t('manager.design.colors.pattern_graphic_type_label')));
		getPatternDefinitionsList().filter(function (definition) {
			return definition && definition.editorKind !== 'image' && definition.key !== 'image_pattern';
		}).forEach(function (definition) {
			var card = createNode('button', 'sonyra-color-controller__pattern-simple-type-card');
			card.type = 'button';
			card.setAttribute('data-pattern-type', definition.key);
			card.classList.toggle('is-active', draft.pattern_type === definition.key);
			card.appendChild(renderPatternPreview(createPatternDraft({
				pattern_type: definition.key,
				editor_kind: 'graphic',
				name: definition.label || ''
			}), false, definition));
			card.appendChild(createNode('strong', 'sonyra-color-controller__pattern-simple-type-title', definition.label || ''));
			card.appendChild(createNode('span', 'sonyra-color-controller__pattern-simple-type-description', definition.description || ''));
			grid.appendChild(card);
		});
		field.appendChild(grid);
		return field;
	}

	function buildPatternSimpleControl(control, modal) {
		var draft = modal && modal.draft ? modal.draft : {};
		var settings = draft.settings || {};
		var key = control.key;
		var field = createNode('div', 'sonyra-color-controller__pattern-simple-control');
		var labelText = control.label || t(control.labelKey || control.titleKey || '');
		var label = createNode('label', 'sonyra-color-controller__pattern-simple-label', labelText);
		var type = String(control.type || '');
		var currentValue;
		if (type === 'slider' || type === 'stepper' || type === 'range' || type === 'angle') {
			var input = createNode('input', 'sonyra-color-controller__pattern-simple-range');
			var value = settings[key] !== undefined ? settings[key] : (control.defaultValue !== undefined ? control.defaultValue : (control.min !== undefined ? control.min : 0));
			var valueNode = createNode('span', 'sonyra-color-controller__pattern-simple-value', String(value));
			input.type = 'range';
			input.min = control.min !== undefined ? String(control.min) : '0';
			input.max = control.max !== undefined ? String(control.max) : '100';
			input.step = control.step !== undefined ? String(control.step) : '1';
			input.value = String(value);
			input.setAttribute('data-pattern-setting', key);
			field.appendChild(label);
			field.appendChild(input);
			field.appendChild(valueNode);
			return field;
		}
		if (type === 'boolean' || type === 'switch' || type === 'toggle') {
			var button = createNode('button', 'sonyra-color-controller__pattern-simple-switch');
			button.type = 'button';
			button.setAttribute('role', 'switch');
			button.setAttribute('data-pattern-setting', key);
			button.setAttribute('aria-checked', settings[key] === true ? 'true' : 'false');
			button.appendChild(createNode('span', 'sonyra-color-controller__pattern-simple-switch-thumb'));
			field.appendChild(label);
			field.appendChild(button);
			return field;
		}
		if (type === 'position') {
			var positionSelect = createNode('select', 'sonyra-color-controller__pattern-simple-select');
			['top-left', 'top', 'top-right', 'left', 'center', 'right', 'bottom-left', 'bottom', 'bottom-right'].forEach(function (position) {
				var optionNode = createNode('option', '');
				optionNode.value = position;
				optionNode.textContent = t('manager.design.colors.option_' + position) || position;
				optionNode.selected = String(settings[key] !== undefined ? settings[key] : '') === String(position);
				positionSelect.appendChild(optionNode);
			});
			positionSelect.setAttribute('data-pattern-setting', key);
			field.appendChild(label);
			field.appendChild(positionSelect);
			return field;
		}
		if (type === 'segmented' || type === 'enum' || Array.isArray(control.options)) {
			var select = createNode('select', 'sonyra-color-controller__pattern-simple-select');
			currentValue = settings[key] !== undefined ? settings[key] : control.defaultValue;
			select.setAttribute('data-pattern-setting', key);
			(control.options || []).forEach(function (option) {
				var optionNode = createNode('option', '');
				var optionValue = typeof option === 'object' ? option.value : option;
				var optionLabelKey = option && typeof option === 'object' ? option.labelKey : '';
				var optionLabel = option && typeof option === 'object' ? option.label : option;
				optionNode.value = String(optionValue);
				optionNode.textContent = optionLabelKey ? (t(optionLabelKey) || String(optionLabel || optionValue)) : String(optionLabel || optionValue);
				optionNode.selected = String(currentValue) === String(optionValue);
				select.appendChild(optionNode);
			});
			field.appendChild(label);
			field.appendChild(select);
			return field;
		}
		return field;
	}

	function buildPatternSimpleColorSlot(slotKey, modal) {
		var draft = modal && modal.draft ? modal.draft : {};
		var colors = draft.colors || {};
		var definition = getPatternDefinition(draft.pattern_type) || getPatternDefinition(getDefaultPatternType());
		var slot = findPatternColorSlotDefinition(definition, slotKey);
		var field = createNode('div', 'sonyra-color-controller__pattern-simple-control');
		var label = createNode('label', 'sonyra-color-controller__pattern-simple-label', slot && slot.label ? slot.label : (t('manager.design.colors.color_slot_' + slotKey) || slotKey));
		var input = createNode('input', 'sonyra-color-controller__pattern-simple-color');
		var fallback = definition && definition.defaultColors ? definition.defaultColors[slotKey] : '';
		input.type = 'color';
		input.value = normalizeHex(colors[slotKey] || '') || normalizeHex(fallback || '') || '#7C3AED';
		input.setAttribute('data-pattern-color', slotKey);
		field.appendChild(label);
		field.appendChild(input);
		return field;
	}

	function buildPatternSimpleImageBlock(modal) {
		var block = createNode('div', 'sonyra-color-controller__pattern-simple-image-block');
		block.appendChild(createNode('strong', 'sonyra-color-controller__pattern-simple-group-title', t('manager.design.colors.pattern_image_block_title')));
		block.appendChild(createNode('p', 'sonyra-color-controller__pattern-simple-section-hint', t('manager.design.colors.pattern_image_block_hint')));
		if (state.mediaUploadUrl || state.mediaLibraryUrl) {
			block.appendChild(buildMediaControl(modal));
		} else {
			block.appendChild(createNode('div', 'sonyra-color-controller__pattern-upload-placeholder', t('manager.design.colors.pattern_upload_placeholder')));
		}
		return block;
	}

	function buildPatternSimpleSettings(modal) {
		var draft = modal && modal.draft ? modal.draft : {};
		var definition = getPatternDefinition(draft.pattern_type) || getPatternDefinition(getDefaultPatternType());
		var schema = getPatternEditorSchema(draft.pattern_type || getDefaultPatternType(), definition);
		var block = createNode('div', 'sonyra-color-controller__pattern-simple-settings');
		block.appendChild(createNode('div', 'sonyra-color-controller__pattern-simple-section-title', t('manager.design.colors.pattern_settings_label')));
		block.appendChild(createNode('p', 'sonyra-color-controller__pattern-simple-section-hint', t('manager.design.colors.pattern_basic_settings_hint')));
		(schema.groups || []).forEach(function (group) {
			var groupBlock = createNode('div', 'sonyra-color-controller__pattern-simple-group');
			groupBlock.appendChild(createNode('strong', 'sonyra-color-controller__pattern-simple-group-title', t(group.titleKey)));
			(group.controlKeys || []).forEach(function (key) {
				var control = getPatternControlDefinition(definition, key);
				if (control && isVisibleByRule(control.visible_when, draft)) {
					groupBlock.appendChild(buildPatternSimpleControl(control, modal));
				}
			});
			(group.colorSlots || []).forEach(function (slot) {
				if (typeof slot === 'string') {
					groupBlock.appendChild(buildPatternSimpleColorSlot(slot, modal));
				} else if (slot && shouldShowPatternSlot(slot, draft)) {
					groupBlock.appendChild(buildPatternSimpleColorSlot(slot.key, modal));
				}
			});
			if (group.media) {
				groupBlock.appendChild(buildPatternSimpleImageBlock(modal));
			}
			block.appendChild(groupBlock);
		});
		return block;
	}

	function getClientGraphicPatternDefinitions(currentType) {
		var curatedKeys = ['dots', 'grid', 'waves', 'lines', 'decorative_elements'];
		var seen = {};
		var definitions = [];

		curatedKeys.forEach(function (key) {
			var definition = getPatternDefinition(key);
			if (!definition || definition.editorKind === 'image' || definition.key === 'image_pattern') {
				return;
			}
			seen[definition.key] = true;
			definitions.push(definition);
		});

		if (currentType && !seen[currentType]) {
			var currentDefinition = getPatternDefinition(currentType);
			if (currentDefinition && currentDefinition.editorKind !== 'image' && currentDefinition.key !== 'image_pattern') {
				definitions.push(currentDefinition);
			}
		}

		return definitions;
	}

	function buildPatternEditorNameField(modal) {
		var draft = modal && modal.draft ? modal.draft : {};
		var wrap = createNode('div', 'sonyra-color-controller__pattern-name-field');
		var label = createNode('label', 'sonyra-color-controller__pattern-name-label', t('manager.design.colors.pattern_name_label'));
		var input = buildTextInput('name', draft.name || '');

		input.className = 'sonyra-color-controller__pattern-name-input';
		input.placeholder = t('manager.design.colors.pattern_name_placeholder');
		label.setAttribute('data-color-pattern-name-field', 'true');
		wrap.setAttribute('data-color-pattern-name-field', 'true');
		label.appendChild(input);
		wrap.appendChild(label);

		if (modal.errors && modal.errors.name) {
			wrap.appendChild(createNode('small', 'sonyra-color-controller__field-error', modal.errors.name));
		}
		if (getModalFieldHelperText(modal, 'name', '')) {
			wrap.appendChild(createNode('small', 'sonyra-color-controller__field-helper', getModalFieldHelperText(modal, 'name', '')));
		}

		return wrap;
	}

	function buildPatternImageEditor(modal) {
		var wrap = createNode('div', 'sonyra-color-controller__pattern-simple-editor');
		var back = createNode('button', 'sonyra-color-controller__pattern-simple-back');
		back.type = 'button';
		back.setAttribute('data-pattern-back-source', 'true');
		back.textContent = t('manager.design.colors.pattern_back_to_source');
		wrap.appendChild(back);
		wrap.appendChild(buildPatternNameField(modal));
		wrap.appendChild(buildPatternPreviewBlock(modal));
		wrap.appendChild(buildPatternSimpleImageBlock(modal));
		wrap.appendChild(buildPatternSimpleSettings(modal));
		return wrap;
	}

	function buildPatternTypePicker(modal) {
		var draft = modal && modal.draft ? modal.draft : {};
		var currentType = draft.pattern_type || getDefaultPatternType();
		var wrap = createNode('section', 'sonyra-color-controller__pattern-type-picker');
		var title = createNode('strong', 'sonyra-color-controller__pattern-type-picker-title', t('manager.design.colors.pattern_graphic_type_label'));
		var grid = createNode('div', 'sonyra-color-controller__pattern-type-picker-grid');

		getClientGraphicPatternDefinitions(currentType).forEach(function (definition) {
			var button = createNode('button', 'sonyra-color-controller__pattern-tile' + (definition.key === currentType ? ' is-active' : ''));
			var preview = renderPatternPreview({
				pattern_type: definition.key,
				colors: definition.defaultColors,
				settings: definition.defaultSettings,
				media: {}
			}, false, definition);
			var copy = createNode('div', 'sonyra-color-controller__pattern-tile-copy');

			button.type = 'button';
			button.setAttribute('data-pattern-type', definition.key);
			button.setAttribute('aria-pressed', definition.key === currentType ? 'true' : 'false');
			button.appendChild(preview);
			copy.appendChild(createNode('strong', 'sonyra-color-controller__pattern-tile-title', definition.label || definition.key));
			copy.appendChild(createNode('span', 'sonyra-color-controller__pattern-tile-description', definition.description || ''));
			button.appendChild(copy);
			grid.appendChild(button);
		});

		wrap.appendChild(title);
		wrap.appendChild(grid);
		return wrap;
	}

	function buildPatternResultCard(modal) {
		var draft = modal && modal.draft ? modal.draft : {};
		var definition = getPatternDefinition(draft.pattern_type) || getPatternDefinition(getDefaultPatternType());
		var card = createNode('section', 'sonyra-color-controller__pattern-result-card');
		var meta = createNode('div', 'sonyra-color-controller__pattern-result-meta');
		var top = createNode('div', 'sonyra-color-controller__pattern-result-top');
		var copy = createNode('div', 'sonyra-color-controller__pattern-result-copy');
		var previewWrap = createNode('div', 'sonyra-color-controller__pattern-result-preview');
		var preview = buildPatternPreviewFromDraft(draft);

		preview.setAttribute('data-color-pattern-preview-live', 'true');
		copy.appendChild(createNode('strong', 'sonyra-color-controller__pattern-result-title', t('manager.design.colors.pattern_preview_label')));
		copy.appendChild(createNode('p', 'sonyra-color-controller__pattern-controls-hint', t('manager.design.colors.pattern_live_preview_hint')));
		top.appendChild(copy);
		top.appendChild(createNode('span', 'sonyra-manager-meta-chip sonyra-color-controller__pattern-result-chip', definition && definition.label ? definition.label : ''));
		top.lastChild.setAttribute('data-color-pattern-result-type', 'true');
		previewWrap.appendChild(preview);
		meta.appendChild(top);
		meta.appendChild(previewWrap);
		card.appendChild(meta);
		return card;
	}

	function buildPatternControlsPanel(modal) {
		var draft = modal && modal.draft ? modal.draft : {};
		var definition = getPatternDefinition(draft.pattern_type) || getPatternDefinition(getDefaultPatternType());
		var schema = getPatternEditorSchema(draft.pattern_type || getDefaultPatternType(), definition);
		var panel = createNode('section', 'sonyra-color-controller__pattern-controls-panel');
		var head = createNode('div', 'sonyra-color-controller__pattern-controls-head');
		var back = createButton('sonyra-manager-pages-secondary sonyra-color-controller__pattern-back-button', t('manager.design.colors.pattern_back_to_choice'));
		var scroll = createNode('div', 'sonyra-color-controller__pattern-controls-scroll');

		back.setAttribute('data-pattern-back-source', 'true');
		head.appendChild(back);
		panel.appendChild(head);
		scroll.setAttribute('data-color-pattern-settings-scroll', 'true');
		scroll.appendChild(buildPatternEditorNameField(modal));
		scroll.appendChild(buildPatternTypePicker(modal));

		(schema.groups || []).forEach(function (group) {
			var body = buildPatternGroupBody(modal, definition, group);
			if (!body) {
				return;
			}

			scroll.appendChild(buildPatternAccordionGroup({
				key: group.key,
				kind: 'group',
				title: getControlGroupLabel(group.titleKey),
				summary: '',
				defaultOpen: !!(state.modal && state.modal.groupOpen && state.modal.groupOpen[group.key] !== false),
				body: body
			}));
		});

		panel.appendChild(scroll);
		return panel;
	}

	function buildPatternGraphicEditor(modal) {
		var wrap = createNode('div', 'sonyra-color-controller__pattern-editor');
		var left = createNode('div', 'sonyra-color-controller__pattern-left');
		var right = createNode('div', 'sonyra-color-controller__pattern-right');

		left.appendChild(buildPatternControlsPanel(modal));
		right.appendChild(buildPatternResultCard(modal));
		wrap.appendChild(left);
		wrap.appendChild(right);
		return wrap;
	}

	function buildPatternEditorLayout(modal) {
		if (shouldShowPatternSourceChoice(modal)) {
			return buildPatternSourceChoice(modal);
		}
		var step = getPatternFlowStep(modal);
		var wrap = createNode('div', 'sonyra-color-controller__pattern-simple');
		if (step === 'source-choice') {
			wrap.appendChild(buildPatternSourceChoice(modal));
			return wrap;
		}
		if (step === 'image-editor') {
			wrap.appendChild(buildPatternImageEditor(modal));
			return wrap;
		}
		wrap.appendChild(buildPatternGraphicEditor(modal));
		return wrap;
	}

	function buildPatternEditorBody(modal) {
		return buildPatternEditorLayout(modal);
	}

	function buildModalBody() {
		if (!state.modal) {
			return createNode('div');
		}
		if (state.modal.entityType === 'color') {
			return buildColorEditorBody(state.modal);
		}
		if (state.modal.entityType === 'gradient') {
			return buildGradientEditorBody(state.modal);
		}
		return buildPatternEditorBody(state.modal);
	}


	function assertColorControllerModalDom(modalElement, modal) {
		var titleNode;
		var descriptionNode;
		var patternSimple;

		if (!window || !window.SONYRA_MANAGER_DEBUG) {
			return;
		}

		if (!modalElement) {
			throw new Error('SONYRA modal DOM assertion failed: modalElement is missing');
		}

		titleNode = modalElement.querySelector('.sonyra-manager-modal__title');
		descriptionNode = modalElement.querySelector('.sonyra-manager-modal__description');

		if (!titleNode || !String(titleNode.textContent || '').trim()) {
			throw new Error('SONYRA modal DOM assertion failed: modal title is missing');
		}

		if (!descriptionNode || !String(descriptionNode.textContent || '').trim()) {
			throw new Error('SONYRA modal DOM assertion failed: modal description is missing');
		}

		if (!modal || modal.entityType !== 'pattern') {
			return;
		}

		patternSimple = modalElement.querySelector('.sonyra-color-controller__pattern-simple');
		if (!patternSimple) {
			throw new Error('SONYRA modal DOM assertion failed: simple pattern editor is missing');
		}

		if (getPatternFlowStep(modal) === 'source-choice') {
			if (!modalElement.querySelector('.sonyra-color-controller__pattern-source-title') || !modalElement.querySelectorAll('[data-pattern-source]').length) {
				throw new Error('SONYRA modal DOM assertion failed: source choice is incomplete');
			}
			return;
		}

		if (getPatternFlowStep(modal) === 'image-editor') {
			if (!modalElement.querySelector('.sonyra-color-controller__pattern-simple-editor')) {
				throw new Error('SONYRA modal DOM assertion failed: image editor body is missing');
			}
			if (!modalElement.querySelector('[data-pattern-back-source]')) {
				throw new Error('SONYRA modal DOM assertion failed: back to source button is missing');
			}
			return;
		}

		if (!modalElement.querySelector('.sonyra-color-controller__pattern-editor')) {
			throw new Error('SONYRA modal DOM assertion failed: graphic editor body is missing');
		}

		if (!modalElement.querySelector('.sonyra-color-controller__pattern-type-picker')) {
			throw new Error('SONYRA modal DOM assertion failed: type picker is missing');
		}

		if (!modalElement.querySelector('[data-color-pattern-name-field] [data-color-modal-field=\"name\"]')) {
			throw new Error('SONYRA modal DOM assertion failed: pattern name field is missing');
		}

		if (!modalElement.querySelector('[data-color-pattern-preview-live]')) {
			throw new Error('SONYRA modal DOM assertion failed: live preview is missing');
		}

		if (!modalElement.querySelector('.sonyra-color-controller__pattern-controls-panel')) {
			throw new Error('SONYRA modal DOM assertion failed: controls panel is missing');
		}
	}

	function renderModal() {
		if (!state.modal) {
			if (!(window.isManagerDestructiveModalOpen && window.isManagerDestructiveModalOpen())) {
				document.body.classList.remove('sonyra-manager-modal-open');
			}
			return null;
		}

		document.body.classList.add('sonyra-manager-modal-open');

		var modalCopy = getColorControllerModalCopy(state.modal);

		if (!modalCopy.titleText) {
			if (window && window.console && typeof window.console.error === 'function') {
				window.console.error('SONYRA Color Library modal titleText is empty:', modalCopy.titleKey || '');
			}
		}

		if (!modalCopy.descriptionText) {
			if (window && window.console && typeof window.console.error === 'function') {
				window.console.error('SONYRA Color Library modal descriptionText is empty:', modalCopy.descriptionKey || '');
			}
		}

		var chrome = getManagerUi().renderStandardModal({
			iconKey: 'settings',
			closeIconKey: 'x',
			titleId: 'sonyra-color-modal-title',
			titleText: modalCopy.titleText,
			descriptionText: modalCopy.descriptionText,
			panelAttributes: {
				role: 'dialog',
				'aria-modal': 'true'
			},
			closeAttributes: {
				'data-color-close-modal': 'true',
				'aria-label': t('manager.pages.modal.close')
			}
		});
		var overlay = chrome.overlay;
		var title = chrome.title;
		var description = chrome.description;
		var body = chrome.body;
		var footer = chrome.footer;
		var cancel = createButton('sonyra-manager-pages-secondary', t('manager.pages.actions.cancel'));
		var save = createButton('sonyra-manager-pages-primary', state.modal.loading ? t('manager.pages.actions.saving') : t('manager.pages.actions.save'));

		overlay.setAttribute('data-color-modal-overlay', 'true');
		body.setAttribute('data-color-modal-scroll', 'true');
		if (state.modal.entityType === 'pattern') {
			overlay.classList.add('sonyra-color-controller__modal-overlay');
			chrome.panel.classList.add('sonyra-color-controller__modal-panel', 'sonyra-color-controller__modal-panel-pattern');
			body.classList.add('sonyra-color-controller__modal-body', 'sonyra-color-controller__modal-body-pattern');
			footer.classList.add('sonyra-color-controller__modal-footer-pattern');
		} else if (state.modal.entityType === 'color' || state.modal.entityType === 'gradient') {
			overlay.classList.add('sonyra-color-controller__modal-overlay');
			chrome.panel.classList.add('sonyra-color-controller__modal-panel');
			body.classList.add('sonyra-color-controller__modal-body');
		}
		if (shouldUseApprovedGraphicPatternLayout()) {
			body.appendChild(renderApprovedGraphicPatternEditor());
		} else {
			body.appendChild(buildModalBody());
		}
		cancel.setAttribute('data-color-close-modal', 'true');
		footer.appendChild(cancel);
		save.setAttribute('data-color-save-modal', 'true');
		if (state.modal.loading || (state.modal.entityType === 'pattern' && getPatternFlowStep(state.modal) === 'source-choice')) {
			save.disabled = true;
		}
		footer.appendChild(save);
		if (shouldUseApprovedGraphicPatternLayout()) {
			moveApprovedGraphicPatternBackButton(footer);
		}
		assertColorControllerModalDom(overlay, state.modal);

		return overlay;
	}

	function rerenderActiveModal() {
		var currentOverlay;
		var nextOverlay;

		if (!app) {
			return;
		}

		captureModalViewState();
		currentOverlay = root.querySelector('[data-color-modal-overlay="true"]');
		nextOverlay = renderModal();

		if (currentOverlay && nextOverlay && currentOverlay.parentNode) {
			currentOverlay.parentNode.replaceChild(nextOverlay, currentOverlay);
			restoreModalViewState();
			return;
		}

		if (!currentOverlay && nextOverlay) {
			app.appendChild(nextOverlay);
			restoreModalViewState();
			return;
		}

		if (currentOverlay && currentOverlay.parentNode) {
			currentOverlay.parentNode.removeChild(currentOverlay);
		}
	}

	function captureModalViewState() {
		var scrollNode;
		var activeField;

		if (!state.modal) {
			return;
		}

		scrollNode = root.querySelector('[data-color-pattern-settings-scroll]') || root.querySelector('[data-color-modal-scroll]');
		if (scrollNode) {
			state.modal.scrollTop = scrollNode.scrollTop;
		}

		activeField = document.activeElement && document.activeElement.getAttribute ? document.activeElement.getAttribute('data-color-modal-field') : '';
		state.modal.activeField = activeField || '';
		if (document.activeElement && document.activeElement.selectionStart !== undefined) {
			state.modal.activeSelectionStart = document.activeElement.selectionStart;
			state.modal.activeSelectionEnd = document.activeElement.selectionEnd;
		}
	}

	function restoreModalViewState() {
		var scrollNode;
		var activeField;

		if (!state.modal) {
			return;
		}

		scrollNode = root.querySelector('[data-color-pattern-settings-scroll]') || root.querySelector('[data-color-modal-scroll]');
		if (scrollNode && typeof state.modal.scrollTop === 'number') {
			scrollNode.scrollTop = state.modal.scrollTop;
		}

		activeField = state.modal.activeField ? root.querySelector('[data-color-modal-field="' + String(state.modal.activeField) + '"]') : null;
		if (activeField && activeField !== document.activeElement && activeField.type !== 'range') {
			activeField.focus({ preventScroll: true });
			if (state.modal.activeSelectionStart !== undefined && activeField.selectionStart !== undefined) {
				activeField.setSelectionRange(state.modal.activeSelectionStart, state.modal.activeSelectionEnd);
			}
		}
	}

	function renderGlobalHelpPopover() {
		var layer = document.querySelector('[data-manager-help-layer]');
		var trigger;
		var triggerRect;
		var popover;
		var help = helpMap[state.helpKey] || null;
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

		if (!state.helpOpen || !layer || !help) {
			return;
		}

		trigger = root.querySelector('[data-sonyra-help-key="' + String(state.helpKey || 'design.colors.controller') + '"]');

		if (!trigger) {
			return;
		}

		popover = createNode('article', 'sonyra-help-popover sonyra-color-controller__help-popover');
		popover.setAttribute('data-sonyra-help-scope', 'design');
		popover.setAttribute('data-color-help-popover', 'true');
		popover.setAttribute('role', 'tooltip');
		popover.appendChild(createNode('strong', 'sonyra-help-popover__title sonyra-color-controller__help-title', help.title || ''));
		popover.appendChild(createNode('p', 'sonyra-help-popover__body sonyra-color-controller__help-body', help.body || ''));
		layer.appendChild(popover);

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

	function clearHelpPopoverScope(scope) {
		var layer = document.querySelector('[data-manager-help-layer]');
		var selector;

		if (!layer) {
			return;
		}

		selector = scope ? ('[data-sonyra-help-scope="' + scope + '"]') : '[data-sonyra-help-scope]';
		Array.prototype.slice.call(layer.querySelectorAll(selector)).forEach(function (node) {
			if (node && node.parentNode) {
				node.parentNode.removeChild(node);
			}
		});
	}

	function render() {
		captureModalViewState();
		clearHelpPopoverScope('design');
		clearNode(app);
		app.appendChild(state.view === 'landing' ? buildLandingCard() : buildWorkspace());
		var modal = renderModal();
		if (modal) {
			app.appendChild(modal);
			restoreModalViewState();
		}
		renderGlobalHelpPopover();
	}

	function refreshPatternChoiceState(fieldName) {
		var nodes = Array.prototype.slice.call(root.querySelectorAll('[data-color-option-field]'));
		var nextValue = getPatternDraftValue(fieldName, '');

		nodes.forEach(function (node) {
			if (String(node.getAttribute('data-color-option-field') || '') !== String(fieldName || '')) {
				return;
			}

			if (node.getAttribute('role') === 'switch') {
				var isChecked = String(node.getAttribute('data-color-option-value') || '') === 'false';
				node.setAttribute('aria-checked', isChecked ? 'true' : 'false');
				node.classList.toggle('sonyra-manager-pages-switch-active', isChecked);
				node.setAttribute('data-color-option-value', isChecked ? 'true' : 'false');
				return;
			}

			if (node.classList.contains('sonyra-color-controller__position-cell')) {
				node.classList.toggle('is-active', String(node.getAttribute('data-color-option-value') || '') === String(nextValue || ''));
				return;
			}

			node.setAttribute('aria-pressed', String(node.getAttribute('data-color-option-value') || '') === String(nextValue || '') ? 'true' : 'false');
			node.classList.toggle('is-active', String(node.getAttribute('data-color-option-value') || '') === String(nextValue || ''));
		});
	}

	function rebuildPatternControlsPanel() {
		if (!state.modal || state.modal.entityType !== 'pattern' || state.modal.kind !== 'form') {
			return;
		}

		render();
	}

	function patchPatternPreview(modal) {
		if (!modal || modal.entityType !== 'pattern' || modal.kind !== 'form') {
			return;
		}
	}

	function refreshPatternEditorRuntime(fieldName) {
		var previewNode;
		var nameInput;
		var nameValue;
		var typeNode;
		var generatorSummaryNode;
		var rangeValueNode;
		var control;
		var preview;
		var definition;
		var fieldValue;

		if (!state.modal || state.modal.entityType !== 'pattern' || state.modal.kind !== 'form') {
			return;
		}

		definition = getPatternDefinition(state.modal.draft.pattern_type) || getPatternDefinition(getDefaultPatternType());
		previewNode = root.querySelector('[data-color-pattern-preview-live]');
		if (previewNode && previewNode.parentNode) {
			preview = buildPatternPreviewFromDraft(state.modal.draft);
			preview.setAttribute('data-color-pattern-preview-live', 'true');
			previewNode.parentNode.replaceChild(preview, previewNode);
		}

		if (!fieldName || fieldName === 'name') {
			nameInput = root.querySelector('[data-color-pattern-name-field] [data-color-modal-field="name"]');
			nameValue = state.modal.draft.name || '';
			if (nameInput && nameInput.value !== nameValue) {
				nameInput.value = nameValue;
			}
		}

		typeNode = root.querySelector('[data-color-pattern-result-type]');
		if (typeNode) {
			typeNode.textContent = state.modal.draft.editor_kind === 'image'
				? t('manager.design.colors.pattern_editor_image_title')
				: (definition && definition.label ? definition.label : '');
		}

		generatorSummaryNode = root.querySelector('[data-color-pattern-generator-summary]');
		if (generatorSummaryNode) {
			generatorSummaryNode.textContent = definition && definition.label ? definition.label : '';
			generatorSummaryNode.hidden = !generatorSummaryNode.textContent;
		}

		if (fieldName && fieldName.indexOf('settings.') === 0) {
			control = getPatternControlByKey(fieldName.replace(/^settings\./, ''));
			rangeValueNode = root.querySelector('[data-color-range-value="' + fieldName + '"]');
			if (control && rangeValueNode) {
				fieldValue = getPatternDraftValue(fieldName, '');
				rangeValueNode.textContent = getPatternValueText({
					type: control.type,
					unit: control.unit || '',
					value: fieldValue
				});
			}
		}
	}

	function shouldRerenderPatternControls(fieldName) {
		return fieldName === 'settings.layer_count' || fieldName === 'settings.second_layer';
	}

	function updateDraftField(field, value) {
		var control;
		var fieldKey;

		if (!state.modal || state.modal.kind !== 'form') {
			return;
		}
		if (state.modal.entityType === 'pattern' && field.indexOf('.') !== -1) {
			control = field.indexOf('settings.') === 0 ? getPatternControlByKey(field.replace(/^settings\./, '')) : null;
			setPatternDraftValue(field, control ? normalizePatternFieldValue(control, value) : value);
		} else {
			state.modal.draft[field] = value;
		}
		fieldKey = field.replace(/^settings\./, '').replace(/^colors\./, '');
		if (state.modal.errors && state.modal.errors[field]) {
			delete state.modal.errors[field];
		}
		if (state.modal.errors && state.modal.errors[fieldKey]) {
			delete state.modal.errors[fieldKey];
		}
		if (field.indexOf('colors.') === 0 && state.modal.errors && state.modal.errors.colors) {
			delete state.modal.errors.colors;
		}
		if (state.modal.errorHelpers && state.modal.errorHelpers[field]) {
			delete state.modal.errorHelpers[field];
		}
		if (state.modal.errorHelpers && state.modal.errorHelpers[fieldKey]) {
			delete state.modal.errorHelpers[fieldKey];
		}
	}

	function updatePatternDraftSetting(key, value, options) {
		var field = String(key || '').indexOf('settings.') === 0 ? String(key) : ('settings.' + String(key || ''));

		updateDraftField(field, value);
		if (shouldRerenderPatternControls(field)) {
			rebuildPatternControlsPanel(options || {});
		}
		patchPatternPreview(state.modal);
	}

	function setDraftEnum(field, value) {
		if (!state.modal || !state.modal.draft) {
			return;
		}
		if (state.modal.entityType === 'pattern' && field.indexOf('.') !== -1) {
			var control = field.indexOf('settings.') === 0 ? getPatternControlByKey(field.replace(/^settings\./, '')) : { type: 'segmented' };
			setPatternDraftValue(field, normalizePatternFieldValue(control || { type: 'segmented' }, value));
			if (state.modal.errors && state.modal.errors[field]) {
				delete state.modal.errors[field];
			}
			if (state.modal.errors && state.modal.errors[field.replace(/^settings\./, '')]) {
				delete state.modal.errors[field.replace(/^settings\./, '')];
			}
			if (shouldRerenderPatternControls(field)) {
				rebuildPatternControlsPanel();
			} else {
				refreshPatternChoiceState(field);
			}
			patchPatternPreview(state.modal);
			refreshPatternEditorRuntime(field);
			return;
		} else {
			state.modal.draft[field] = value;
		}
		render();
	}

	function setPatternType(patternType) {
		if (!state.modal || state.modal.entityType !== 'pattern') {
			return;
		}

		var definition = getPatternDefinition(patternType) || getPatternDefinition(getDefaultPatternType());
		var draft = state.modal.draft;

		draft.pattern_type = patternType;
		state.modal.patternType = patternType;
		draft.editor_kind = definition.editorKind || 'graphic';
		draft.colors = mergePatternColors(definition, draft.colors || {});
		draft.settings = mergePatternSettings(definition, draft.settings || {});
		state.modal.groupOpen = createPatternGroupState(draft.editor_kind, draft.pattern_type);
		state.modal.generatorExpanded = state.modal.groupOpen.__generator !== false;
		render();
	}

	function applyDuplicateNameError() {
		if (!state.modal || !state.modal.draft) {
			return false;
		}

		if (!isDuplicateNameInCollection(state.modal.entityType, state.modal.draft.name || '', state.modal.itemId || '')) {
			return false;
		}

		state.modal.errors.name = t('manager.design.colors.validation_name_duplicate');
		state.modal.errorHelpers.name = t('manager.design.colors.validation_name_duplicate_hint');
		return true;
	}

	function validateModalBeforeSubmit() {
		if (!state.modal || state.modal.kind !== 'form') {
			return false;
		}

		state.modal.errors = state.modal.errors && typeof state.modal.errors === 'object' ? state.modal.errors : {};
		state.modal.errorHelpers = state.modal.errorHelpers && typeof state.modal.errorHelpers === 'object' ? state.modal.errorHelpers : {};
		delete state.modal.errors.name;
		delete state.modal.errorHelpers.name;

		return !applyDuplicateNameError();
	}

	function buildSubmitPayload() {
		var draft = state.modal.draft;

		if (state.modal.entityType === 'color') {
			return {
				name: String(draft.name || '').trim(),
				value_hex: normalizeHex(draft.value_hex || '')
			};
		}

		if (state.modal.entityType === 'gradient') {
			return {
				name: String(draft.name || '').trim(),
				first_color_hex: normalizeHex(draft.first_color_hex || ''),
				second_color_hex: normalizeHex(draft.second_color_hex || ''),
				angle: Number(draft.angle || 135)
			};
		}

		return {
			name: String(draft.name || '').trim(),
			pattern_type: String(draft.pattern_type || getDefaultPatternType()),
			colors: cloneObject(draft.colors || {}),
			settings: cloneObject(draft.settings || {}),
			media: {
				attachment_id: Number(draft.media && draft.media.attachment_id ? draft.media.attachment_id : 0),
				url: String(draft.media && draft.media.url ? draft.media.url : ''),
				thumb_url: String(draft.media && draft.media.thumb_url ? draft.media.thumb_url : ''),
				name: String(draft.media && draft.media.name ? draft.media.name : ''),
				mime_type: String(draft.media && draft.media.mime_type ? draft.media.mime_type : ''),
				width: Number(draft.media && draft.media.width ? draft.media.width : 0),
				height: Number(draft.media && draft.media.height ? draft.media.height : 0)
			}
		};
	}

	function serializePatternPayload(modal) {
		if (!modal || modal.entityType !== 'pattern') {
			return {};
		}

		return buildSubmitPayload();
	}

	function submitModal() {
		if (!state.modal || state.modal.kind !== 'form' || state.modal.loading) {
			return;
		}

		if (!validateModalBeforeSubmit()) {
			render();
			return;
		}

		state.modal.loading = true;
		render();

		requestJson(
			state.modal.mode === 'edit' ? 'PUT' : 'POST',
			buildRequestUrl(state.modal.entityType, state.modal.mode === 'edit' ? state.modal.itemId : ''),
			{ item: buildSubmitPayload() }
			).then(function (response) {
				applyResponse(response);
				state.modal = null;
				render();
			}).catch(function (error) {
				var payloadData = error && error.payload && error.payload.data ? error.payload.data : {};
				state.modal.loading = false;
				state.modal.errors = payloadData.errors && typeof payloadData.errors === 'object' ? payloadData.errors : {};
				state.modal.errorHelpers = payloadData.helper_texts && typeof payloadData.helper_texts === 'object' ? payloadData.helper_texts : {};
				setMessage('error', error.message || t('manager.design.colors.save_failed'));
				render();
			});
	}

	function openMediaLibrary() {
		if (!state.modal || state.modal.entityType !== 'pattern') {
			return;
		}

		var draft = state.modal.draft;
		draft.media.browserOpen = !draft.media.browserOpen;
		draft.media.browserError = '';

		if (!draft.media.browserOpen || draft.media.browserLoaded || draft.media.browserLoading || !state.mediaLibraryUrl) {
			render();
			return;
		}

		draft.media.browserLoading = true;
		render();

		requestJson('GET', state.mediaLibraryUrl).then(function (response) {
				draft.media.browserLoading = false;
				draft.media.browserLoaded = true;
				draft.media.libraryItems = Array.isArray(response.items) ? response.items : [];
				render();
			}).catch(function (error) {
				draft.media.browserLoading = false;
				draft.media.browserError = error.message || t('manager.design.colors.media_upload_failed');
				setMessage('error', draft.media.browserError);
				render();
			});
	}

	function assignMediaItem(item) {
		if (!state.modal || state.modal.entityType !== 'pattern' || !item) {
			return;
		}

		var draft = state.modal.draft;
		draft.media.attachment_id = String(item.attachment_id || '');
		draft.media.url = String(item.url || '');
		draft.media.thumb_url = String(item.thumb_url || item.url || '');
		draft.media.name = String(item.name || '');
		draft.media.mime_type = String(item.mime_type || '');
		draft.media.width = String(item.width || '');
		draft.media.height = String(item.height || '');
		draft.media.browserOpen = false;
		draft.media.browserError = '';
		render();
	}

	function removeAssignedMedia() {
		if (!state.modal || state.modal.entityType !== 'pattern') {
			return;
		}

		var draft = state.modal.draft;
		draft.media = createPatternMediaDraft({});
		render();
	}

	function uploadMediaFile(file) {
		if (!state.modal || state.modal.entityType !== 'pattern' || !file || !state.mediaUploadUrl) {
			return;
		}

		var formData = new FormData();

		formData.append('file', file);
		state.modal.loading = true;
		render();

			requestUpload(state.mediaUploadUrl, formData).then(function (response) {
				state.modal.loading = false;
				assignMediaItem(response.item || null);
				setMessage('success', response.message ? String(response.message) : '');
				render();
			}).catch(function (error) {
				state.modal.loading = false;
				setMessage('error', error.message || t('manager.design.colors.media_upload_failed'));
				render();
			});
	}

	function handleColorControllerClick(event) {
		var target = event.target;
		if (!target || !root) {
			return;
		}

		var openLibrary = target.closest('[data-color-open-library]');
		var back = target.closest('[data-color-back]');
		var tab = target.closest('[data-color-tab]');
		var create = target.closest('[data-color-create]');
		var edit = target.closest('[data-color-edit]');
		var remove = target.closest('[data-color-delete]');
		var close = target.closest('[data-color-close-modal]');
		var save = target.closest('[data-color-save-modal]');
		var dismiss = target.closest('[data-color-dismiss-message]');
		var helpToggle = target.closest('[data-color-help-toggle]');
		var overlay = target.closest('[data-color-modal-overlay]');
		var swatchButton = target.closest('[data-color-swatch-button]');
		var angleChip = target.closest('[data-color-angle]');
		var optionChip = target.closest('[data-color-option-field]');
		var patternType = target.closest('[data-color-pattern-type]');
		var mediaUpload = target.closest('[data-color-media-upload]');
		var mediaOpenLibrary = target.closest('[data-color-media-open-library]');
		var mediaSelect = target.closest('[data-color-media-select]');
		var mediaRemove = target.closest('[data-color-media-remove]');
		var patternEditorKind = target.closest('[data-color-pattern-editor-kind]');
		var patternBackToChoice = target.closest('[data-color-pattern-back-to-choice]');
		var patternSource = target.closest('[data-pattern-source]');
		var patternBackSource = target.closest('[data-pattern-back-source]');
		var patternSimpleType = target.closest('[data-pattern-type]');
		var patternSimpleSwitch = target.closest('[data-pattern-setting][role="switch"]');
		var fileInput;
		var mediaItem;

		if (openLibrary) {
			event.preventDefault();
			state.view = 'library';
			state.helpOpen = false;
			render();
			return;
		}

		if (back) {
			event.preventDefault();
			state.view = 'landing';
			state.helpOpen = false;
			state.modal = null;
			setMessage('', '');
			render();
			return;
		}

		if (tab) {
			event.preventDefault();
			state.activeTab = tab.getAttribute('data-color-tab') || 'colors';
			state.helpOpen = false;
			setMessage('', '');
			render();
			return;
		}

		if (create) {
			event.preventDefault();
			openColorLibraryFormModal(String(create.getAttribute('data-color-create') || getCurrentEntityType()), 'create', null);
			return;
		}

		if (edit) {
			event.preventDefault();
			openColorLibraryFormModal(String(edit.getAttribute('data-color-edit') || edit.getAttribute('data-color-entity') || getCurrentEntityType()), 'edit', String(edit.getAttribute('data-color-id') || ''));
			return;
		}

		if (remove) {
			event.preventDefault();
			openDeleteModal(remove.getAttribute('data-color-entity') || getCurrentEntityType(), remove.getAttribute('data-color-delete') || '', remove);
			return;
		}

		if (close) {
			event.preventDefault();
			closeModal();
			return;
		}

		if (save) {
			event.preventDefault();
			submitModal();
			return;
		}

		if (patternSource) {
			setPatternEditorKind(patternSource.getAttribute('data-pattern-source') || 'graphic');
			return;
		}

		if (patternBackSource) {
			resetPatternEditorSource();
			return;
		}

		if (patternSimpleType) {
			setPatternType(patternSimpleType.getAttribute('data-pattern-type') || getDefaultPatternType());
			return;
		}

		if (patternSimpleSwitch) {
			if (state.modal && state.modal.entityType === 'pattern' && state.modal.draft) {
				var switchKey = patternSimpleSwitch.getAttribute('data-pattern-setting') || '';
				state.modal.draft.settings[switchKey] = patternSimpleSwitch.getAttribute('aria-checked') !== 'true';
				render();
			}
			return;
		}

		if (patternEditorKind) {
			var selectedEditorKind = patternEditorKind.getAttribute('data-color-pattern-editor-kind') || 'graphic';
			state.modal = {
				kind: 'form',
				type: 'pattern',
				entityType: 'pattern',
				itemId: '',
				mode: 'create',
				draft: createPatternDraft({
					pattern_type: getDefaultPatternTypeForEditor(selectedEditorKind),
					colors: {},
					settings: {},
					media: {}
				}),
				patternType: getDefaultPatternTypeForEditor(selectedEditorKind),
				patternSourceSelected: true,
				showTypePicker: false,
				editorKind: selectedEditorKind,
				generatorExpanded: selectedEditorKind === 'graphic',
				groupOpen: createPatternGroupState(selectedEditorKind, getDefaultPatternTypeForEditor(selectedEditorKind)),
				errors: {},
				errorHelpers: {},
				loading: false,
				scrollTop: 0
			};
			state.modal.draft.editor_kind = state.modal.editorKind;
			render();
			return;
		}

		if (patternBackToChoice) {
			resetPatternEditorSource();
			return;
		}

		if (dismiss) {
			event.preventDefault();
			setMessage('', '');
			render();
			return;
		}

		var patternGroupToggle = event.target.closest('[data-color-pattern-group-toggle]');
		var patternGeneratorToggle = event.target.closest('[data-color-pattern-generator-toggle]');

		if (patternGroupToggle) {
			togglePatternGroup(patternGroupToggle.getAttribute('data-color-pattern-group-toggle') || '');
			return;
		}

		if (patternGeneratorToggle) {
			togglePatternGeneratorPanel();
			return;
		}

		if (helpToggle) {
			event.preventDefault();
			state.helpOpen = !state.helpOpen;
			render();
			return;
		}

		if (swatchButton) {
			fileInput = root.querySelector('[data-color-picker-input="' + swatchButton.getAttribute('data-color-swatch-button') + '"]');
			if (fileInput) {
				fileInput.click();
			}
			return;
		}

		if (angleChip) {
			setDraftEnum('angle', angleChip.getAttribute('data-color-angle') || '135');
			return;
		}

		if (optionChip) {
			setDraftEnum(optionChip.getAttribute('data-color-option-field') || '', optionChip.getAttribute('data-color-option-value') || '');
			return;
		}

		if (patternType) {
			setPatternType(patternType.getAttribute('data-color-pattern-type') || getDefaultPatternType());
			return;
		}

		if (mediaUpload) {
			fileInput = root.querySelector('[data-color-media-file="true"]');
			if (fileInput) {
				fileInput.click();
			}
			return;
		}

		if (mediaOpenLibrary) {
			openMediaLibrary();
			return;
		}

		if (mediaSelect) {
			mediaItem = (state.modal && state.modal.draft && state.modal.draft.media && Array.isArray(state.modal.draft.media.libraryItems) ? state.modal.draft.media.libraryItems : []).find(function (item) {
				return String(item.attachment_id || '') === String(mediaSelect.getAttribute('data-color-media-select') || '');
			}) || null;
			assignMediaItem(mediaItem);
			return;
		}

		if (mediaRemove) {
			removeAssignedMedia();
			return;
		}

		if (overlay && event.target === overlay) {
			closeModal();
		}
	}

	root.addEventListener('click', handleColorControllerClick);

	root.addEventListener('input', function (event) {
		var field = event.target.closest('[data-color-modal-field]');
		var patternNameInput = event.target.closest('[data-pattern-name-input]');
		var patternSettingInput = event.target.closest('[data-pattern-setting]');
		var fieldName;

		if (patternNameInput && state.modal && state.modal.entityType === 'pattern' && state.modal.draft) {
			state.modal.draft.name = patternNameInput.value;
			return;
		}

		if (patternSettingInput && state.modal && state.modal.entityType === 'pattern' && state.modal.draft) {
			var patternSettingKey = patternSettingInput.getAttribute('data-pattern-setting') || '';
			state.modal.draft.settings[patternSettingKey] = patternSettingInput.type === 'range'
				? Number(patternSettingInput.value || 0)
				: String(patternSettingInput.value || '');
			render();
			return;
		}

		if (field) {
			fieldName = field.getAttribute('data-color-modal-field') || '';
			if (field.type === 'range') {
				field.setAttribute('aria-valuenow', String(field.value || '0'));
				applyRangeFill(field);
			}
			updateDraftField(fieldName, field.value);
			if (state.modal && state.modal.entityType === 'pattern') {
				patchPatternPreview(state.modal);
				refreshPatternEditorRuntime(fieldName);
			} else if (
				fieldName === 'value_hex' ||
				fieldName === 'first_color_hex' ||
				fieldName === 'second_color_hex' ||
				fieldName === 'opacity' ||
				fieldName === 'dimness' ||
				fieldName === 'blur'
			) {
				render();
			}
		}
	});

	root.addEventListener('change', function (event) {
		var file = event.target.closest('[data-color-media-file]');
		var picker = event.target.closest('[data-color-picker-input]');
		var field = event.target.closest('[data-color-modal-field]');
		var patternSettingInput = event.target.closest('[data-pattern-setting]');
		var patternColor = event.target.closest('[data-pattern-color]');

		if (picker) {
			updateDraftField(picker.getAttribute('data-color-picker-input') || '', picker.value);
			if (state.modal && state.modal.entityType === 'pattern') {
				patchPatternPreview(state.modal);
				refreshPatternEditorRuntime(picker.getAttribute('data-color-picker-input') || '');
			} else {
				render();
			}
			return;
		}

		if (patternSettingInput && state.modal && state.modal.entityType === 'pattern' && state.modal.draft) {
			var patternSettingKey = patternSettingInput.getAttribute('data-pattern-setting') || '';
			state.modal.draft.settings[patternSettingKey] = String(patternSettingInput.value || '');
			render();
			return;
		}

		if (patternColor && state.modal && state.modal.entityType === 'pattern' && state.modal.draft) {
			var patternColorKey = patternColor.getAttribute('data-pattern-color') || '';
			state.modal.draft.colors[patternColorKey] = patternColor.value || '';
			render();
			return;
		}

		if (field && state.modal && state.modal.entityType === 'pattern') {
			if (shouldRerenderPatternControls(field.getAttribute('data-color-modal-field') || '')) {
				rebuildPatternControlsPanel();
			}
			return;
		}

		if (file && file.files && file.files[0]) {
			uploadMediaFile(file.files[0]);
		}
	});

	getManagerUi().bindHelpTooltipLayer(root, {
		triggerSelector: '[data-color-help-toggle]',
		popoverSelector: '[data-color-help-popover]',
		onOpen: function () {
			if (!state.helpOpen) {
				state.helpOpen = true;
				render();
			}
		},
		onClose: function () {
			if (state.helpOpen) {
				state.helpOpen = false;
				render();
			}
		}
	});

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape') {
			if (state.modal) {
				closeModal();
			} else if (state.helpOpen) {
				state.helpOpen = false;
				render();
			}
		}
	});

	document.addEventListener('click', function (event) {
		if (!root.contains(event.target) && !event.target.closest('[data-color-help-popover]') && state.helpOpen) {
			state.helpOpen = false;
			render();
		}
	});

	window.addEventListener('resize', function () {
		if (state.helpOpen) {
			render();
		}
	});

	window.SonyraColorController = {
		init: function () {
			render();
		},
		handleRouteChange: function (route) {
			state.routeActive = route === 'design';
			if (!state.routeActive) {
				state.helpOpen = false;
				state.modal = null;
			}
			render();
		}
	};
}());
