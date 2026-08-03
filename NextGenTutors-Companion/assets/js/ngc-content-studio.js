/**
 * Content Studio — AI Agent toggle (enhance / restore) + TinyMCE sync.
 * Enhancement state is isolated per form (WeakMap) so multiple toggles
 * on one page cannot leak originals / flags across forms.
 */
(function () {
	'use strict';

	var cfg = window.NGC_CONTENT_STUDIO || {};
	/** @type {WeakMap<HTMLFormElement, {originals: object|null, enhanced: boolean}>} */
	var formState = typeof WeakMap !== 'undefined' ? new WeakMap() : null;
	/**
	 * Fallback when WeakMap is unavailable (legacy browsers).
	 * Entries hold strong form refs — must prune detached nodes or they leak.
	 * @type {Array<{form: HTMLFormElement, state: {originals: object|null, enhanced: boolean}}>}
	 */
	var formStateLegacy = [];

	function formIsInDocument(form) {
		if (!form) {
			return false;
		}
		if (typeof form.isConnected === 'boolean') {
			return form.isConnected;
		}
		return !!(document.documentElement && document.documentElement.contains(form));
	}

	/** Drop legacy entries whose forms were removed from the DOM. */
	function pruneLegacyFormState() {
		if (!formStateLegacy.length) {
			return;
		}
		var kept = [];
		for (var i = 0; i < formStateLegacy.length; i++) {
			if (formIsInDocument(formStateLegacy[i].form)) {
				kept.push(formStateLegacy[i]);
			}
		}
		formStateLegacy = kept;
	}

	function getState(form) {
		if (formState) {
			var s = formState.get(form);
			if (!s) {
				s = { originals: null, enhanced: false };
				formState.set(form, s);
			}
			return s;
		}
		pruneLegacyFormState();
		for (var i = 0; i < formStateLegacy.length; i++) {
			if (formStateLegacy[i].form === form) {
				return formStateLegacy[i].state;
			}
		}
		var legacy = { originals: null, enhanced: false };
		formStateLegacy.push({ form: form, state: legacy });
		return legacy;
	}

	function $(sel, root) {
		return (root || document).querySelector(sel);
	}

	function $all(sel, root) {
		return Array.prototype.slice.call((root || document).querySelectorAll(sel));
	}

	function editorContent(id) {
		if (window.tinymce && tinymce.get(id)) {
			return tinymce.get(id).getContent();
		}
		var el = document.getElementById(id);
		return el ? el.value : '';
	}

	function setEditorContent(id, html) {
		if (window.tinymce && tinymce.get(id)) {
			tinymce.get(id).setContent(html || '');
			return;
		}
		var el = document.getElementById(id);
		if (el) {
			el.value = html || '';
		}
	}

	function fieldValue(name, form) {
		var el = form.querySelector('[name="' + name + '"]');
		if (!el) {
			return '';
		}
		if (el.id && (el.classList.contains('wp-editor-area') || el.id.indexOf('ngc_content_') === 0)) {
			return editorContent(el.id);
		}
		return el.value || '';
	}

	function setFieldValue(name, value, form) {
		var el = form.querySelector('[name="' + name + '"]');
		if (!el) {
			return;
		}
		if (el.id && (window.tinymce && tinymce.get(el.id))) {
			setEditorContent(el.id, value);
			return;
		}
		el.value = value || '';
	}

	function syncEditors(form) {
		if (!window.tinymce) {
			return;
		}
		$all('textarea.wp-editor-area', form).forEach(function (ta) {
			var ed = tinymce.get(ta.id);
			if (ed) {
				ed.save();
			}
		});
	}

	function setStatus(form, msg, kind) {
		var status = form.querySelector('.ngc-cs-status');
		if (!status) {
			return;
		}
		status.textContent = msg || '';
		status.classList.remove('is-error', 'is-ok');
		if (kind) {
			status.classList.add(kind);
		}
	}

	function setToggleState(btn, on, form) {
		var state = getState(form);
		btn.classList.toggle('is-on', !!on);
		btn.setAttribute('aria-pressed', on ? 'true' : 'false');
		var bar = btn.closest('.ngc-cs-ai-bar');
		if (bar) {
			var label = bar.querySelector('.ngc-cs-ai-state');
			if (label) {
				label.textContent = on ? (cfg.i18n && cfg.i18n.on) || 'On — AI enhancing copy' : (cfg.i18n && cfg.i18n.off) || 'Off';
				label.classList.toggle('is-on', !!on);
			}
		}
		var hidden = form.querySelector('input[name="ai_enhanced"]');
		if (hidden) {
			hidden.value = on ? '1' : '0';
		}
		var orig = form.querySelector('input[name="ai_original_json"]');
		if (orig) {
			orig.value = state.originals ? JSON.stringify(state.originals) : '';
		}
	}

	function collect(form) {
		return {
			audience: fieldValue('audience', form),
			text: fieldValue('text', form),
			alt_text: fieldValue('alt_text', form),
		};
	}

	function applyFields(form, data) {
		if (!data) {
			return;
		}
		setFieldValue('audience', data.audience || '', form);
		setFieldValue('text', data.text || '', form);
		setFieldValue('alt_text', data.alt_text || '', form);
	}

	function enhance(form, btn) {
		var state = getState(form);
		var payload = collect(form);
		var plainText = (payload.text || '').replace(/<[^>]+>/g, ' ').trim();
		if (!payload.audience.trim() && !plainText && !(payload.alt_text || '').trim()) {
			setStatus(form, (cfg.i18n && cfg.i18n.empty) || 'Enter audience, post text, or alt text first.', 'is-error');
			setToggleState(btn, false, form);
			state.enhanced = false;
			return;
		}

		// Always snapshot the current form values as the restore baseline for this
		// enhance pass — otherwise a second enhance keeps stale pre-edit originals.
		state.originals = {
			audience: payload.audience || '',
			text: payload.text || '',
			alt_text: payload.alt_text || '',
		};

		btn.classList.add('is-busy');
		setStatus(form, (cfg.i18n && cfg.i18n.working) || 'AI Agent enhancing…', '');

		var body = new URLSearchParams();
		body.set('action', 'ngc_content_ai_enhance');
		body.set('nonce', cfg.nonce || '');
		body.set('audience', state.originals.audience);
		body.set('text', state.originals.text);
		body.set('alt_text', state.originals.alt_text);

		fetch(cfg.ajaxUrl || (window.ajaxurl || ''), {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString(),
		})
			.then(function (r) {
				return r.json();
			})
			.then(function (json) {
				btn.classList.remove('is-busy');
				if (!json || !json.success || !json.data) {
					var err =
						(json && json.data && json.data.message) ||
						(cfg.i18n && cfg.i18n.fail) ||
						'AI enhancement failed.';
					setStatus(form, err, 'is-error');
					setToggleState(btn, false, form);
					state.enhanced = false;
					state.originals = null;
					return;
				}
				applyFields(form, json.data);
				state.enhanced = true;
				setToggleState(btn, true, form);
				setStatus(form, (cfg.i18n && cfg.i18n.ok) || 'Enhanced (lawful, non-discriminatory).', 'is-ok');
			})
			.catch(function () {
				btn.classList.remove('is-busy');
				setStatus(form, (cfg.i18n && cfg.i18n.fail) || 'AI enhancement failed.', 'is-error');
				setToggleState(btn, false, form);
				state.enhanced = false;
				state.originals = null;
			});
	}

	function restore(form, btn) {
		var state = getState(form);
		if (state.originals) {
			applyFields(form, state.originals);
		}
		state.originals = null;
		state.enhanced = false;
		setToggleState(btn, false, form);
		setStatus(form, (cfg.i18n && cfg.i18n.restored) || 'Restored previous text.', 'is-ok');
	}

	function bindForm(form) {
		var btn = form.querySelector('.ngc-cs-ai-toggle');
		if (!btn) {
			return;
		}
		getState(form);

		btn.addEventListener('click', function (e) {
			e.preventDefault();
			if (btn.classList.contains('is-busy')) {
				return;
			}
			var state = getState(form);
			if (state.enhanced || btn.classList.contains('is-on')) {
				restore(form, btn);
			} else {
				enhance(form, btn);
			}
		});

		form.addEventListener('submit', function () {
			syncEditors(form);
			var state = getState(form);
			if (state.enhanced && state.originals) {
				var orig = form.querySelector('input[name="ai_original_json"]');
				if (orig) {
					orig.value = JSON.stringify(state.originals);
				}
				var flag = form.querySelector('input[name="ai_enhanced"]');
				if (flag) {
					flag.value = '1';
				}
			}
		});
	}

	function bindEditToggles() {
		$all('[data-ngc-cs-edit]').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				var id = btn.getAttribute('data-ngc-cs-edit');
				var row = document.getElementById('ngc-cs-edit-' + id);
				if (!row) {
					return;
				}
				if (btn.getAttribute('type') === 'submit') {
					row.classList.remove('is-hidden');
					return;
				}
				e.preventDefault();
				row.classList.toggle('is-hidden');
			});
		});
	}

	function bindDeletes() {
		$all('form.ngc-cs-delete-form').forEach(function (form) {
			form.addEventListener('submit', function (e) {
				var msg = (cfg.i18n && cfg.i18n.confirmDelete) || 'Delete this post permanently?';
				if (!window.confirm(msg)) {
					e.preventDefault();
				}
			});
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		if (window.wp && wp.editor && typeof wp.editor.initialize === 'function') {
			$all('textarea.ngc-wysiwyg').forEach(function (ta, idx) {
				if (!ta.id) {
					ta.id = 'ngc-cs-wysiwyg-' + idx;
				}
				if (ta.classList.contains('wp-editor-area')) {
					return;
				}
				try {
					wp.editor.initialize(ta.id, {
						tinymce: {
							wpautop: true,
							plugins: 'lists link paste',
							toolbar1: 'bold italic bullist numlist link unlink',
							toolbar2: '',
							height: 140,
						},
						quicktags: true,
						mediaButtons: false,
					});
				} catch (err) {
					/* keep textarea */
				}
			});
		}
		$all('form.ngc-cs-draft-form, form.ngc-cs-update-form').forEach(bindForm);
		bindEditToggles();
		bindDeletes();
	});
})();
