/**
 * prime.alerts — live notice under email fields
 * Default: registration + checkout (SOA), not 1-click / lead forms
 *   (1-click uses technical_boc — no account from form email).
 * Config.noticeEverywhere=true — any email field except we still wait for complete address.
 */
(function () {
	var cfg = window.PRIME_ALERTS;
	if (!cfg) return;

	function placeProfileBanner() {
		var node = document.querySelector('.prime-alerts-profile-modal');
		if (!node) {
			var html = cfg.profileBannerHtml;
			if (!html) {
				return;
			}
			var wrap = document.createElement('div');
			wrap.innerHTML = html;
			node = wrap.firstElementChild;
			if (!node) {
				return;
			}
		}

		if (node.parentNode !== document.body) {
			document.body.appendChild(node);
		}
		document.body.classList.add('prime-alerts-profile-open');
		if (typeof window.primePhoneauthMountProfile === 'function') {
			window.primePhoneauthMountProfile(node);
		}

		function postSnooze(mode) {
			var url = cfg.snoozeUrl || '/local/modules/prime.alerts/ajax/snooze.php';
			var body = 'sessid=' + encodeURIComponent(cfg.sessid || (window.BX && BX.bitrix_sessid && BX.bitrix_sessid()) || '');
			if (mode) {
				body += '&mode=' + encodeURIComponent(mode);
			}
			return fetch(url, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
					'X-Requested-With': 'XMLHttpRequest'
				},
				body: body
			}).catch(function () {});
		}

		function closeModal() {
			node.parentNode && node.parentNode.removeChild(node);
			document.body.classList.remove('prime-alerts-profile-open');
		}

		function snoozeModal() {
			var btn = node.querySelector('[data-prime-alerts-snooze="1"]');
			if (btn) {
				btn.disabled = true;
			}
			postSnooze().then(function () {
				closeModal();
			});
		}

		if (cfg.justRegistered && typeof ym === 'function') {
			try {
				if (!sessionStorage.getItem('primeRegGoal')) {
					sessionStorage.setItem('primeRegGoal', '1');
					ym(29264840, 'reachGoal', 'Registracija031024143836', {}, function () {});
				}
			} catch (e) {
				ym(29264840, 'reachGoal', 'Registracija031024143836', {}, function () {});
			}
		}

		node.addEventListener('click', function (e) {
			var t = e.target;
			if (t && t.getAttribute && t.getAttribute('data-prime-alerts-snooze') === '1') {
				e.preventDefault();
				snoozeModal();
				return;
			}
			if (t && t.getAttribute && t.getAttribute('data-prime-alerts-close') === '1') {
				e.preventDefault();
				closeModal();
			}
		});
		document.addEventListener('keydown', function onKey(e) {
			if (e.key === 'Escape') {
				document.removeEventListener('keydown', onKey);
				closeModal();
			}
		});
	}

	function openPersonalContacts() {
		if (window.location.hash !== '#personal-contacts') {
			return;
		}
		var block = document.getElementById('personal-contacts');
		if (!block) {
			return;
		}
		block.classList.add('active');
		window.setTimeout(function () {
			block.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}, 50);
	}

	function onReady() {
		placeProfileBanner();
		openPersonalContacts();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', onReady);
	} else {
		onReady();
	}

	var duplicateTimers = typeof WeakMap !== 'undefined' ? new WeakMap() : null;
	var duplicateCache = typeof Map !== 'undefined' ? new Map() : null;
	var duplicateState = typeof WeakMap !== 'undefined' ? new WeakMap() : null;
	var DUPLICATE_DEBOUNCE_MS = 450;

	var providers = cfg.providers || [];
	var everywhere = cfg.noticeEverywhere === true;
	var timers = typeof WeakMap !== 'undefined' ? new WeakMap() : null;
	var DEBOUNCE_MS = 650;

	function domainOf(email) {
		email = String(email || '').trim().toLowerCase();
		var at = email.lastIndexOf('@');
		return at > 0 ? email.slice(at + 1) : '';
	}

	/** Enough of an address to judge zone (local@domain.tld), not mid-typing. */
	function looksComplete(email) {
		email = String(email || '').trim();
		if (!email) return false;
		return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/i.test(email);
	}

	function isAllowed(email) {
		email = String(email || '').trim().toLowerCase();
		if (!email) return true;
		var domain = domainOf(email);
		if (!domain) return true;
		if (/\.(ru|su)$/.test(domain)) return true;
		if (/\.by$/i.test(domain)) return false;
		for (var i = 0; i < providers.length; i++) {
			var p = providers[i];
			if (domain === p || domain.slice(-(p.length + 1)) === '.' + p) return true;
		}
		return false;
	}

	function fieldWrap(inp) {
		return inp.closest(
			'.bx-authform-formgroup-container, .line, .sale-profile-detail-form-group, .bx-soa-customer-field, .form-group, .soa-property-container, .field, .row, .form-input, tr, label'
		) || inp.parentNode;
	}

	function noticeAnchor(inp) {
		var bxGroup = inp.closest('.bx-authform-formgroup-container');
		if (bxGroup) return bxGroup;
		var wrap = fieldWrap(inp);
		if (!wrap) return inp.parentNode;
		if (wrap.classList && wrap.classList.contains('form-input')) {
			return wrap.parentNode || wrap;
		}
		return wrap;
	}

	function isLeadForm(inp, form, idText) {
		// «Заказать товар» = altop:forms (under_order), не регистрация и не SOA
		if (form && /^under_order_/i.test(form.id || '')) return true;
		if (/under_order|altop\/forms\/script\.php/i.test(idText || '')) return true;
		if (form && /altop\/forms\/script\.php/i.test(form.action || '')) return true;
		return false;
	}

	function isBuyOneClick(inp, form, idText) {
		if (isLeadForm(inp, form, idText)) return true;
		if (inp && inp.closest('[id*="boc_"], [id*="BOC"]')) {
			var pop = inp.closest('[id*="boc_"], form[id*="boc_"]');
			if (pop) return true;
		}
		if (form && /^boc_/i.test(form.id || '')) return true;
		if (form && /boc/i.test(form.id || '')) return true;
		if (/buy\.one\.click|\/boc_|1cb|one.?click/i.test(idText || '')) return true;
		if (form && /buy\.one\.click/i.test(form.action || '')) return true;
		return false;
	}

	function formBlob(inp) {
		var form = inp.form || inp.closest('form');
		if (!form) {
			var popup = inp.closest('.popup-window, .bx-modal, .modal, [id*="boc_"], [id*="BOC"]');
			return {
				form: null,
				text: ((popup && popup.id) || '') + ' ' + ((popup && popup.className) || '')
			};
		}
		var parts = [
			form.id || '',
			form.name || '',
			form.action || '',
			form.className || '',
			form.getAttribute('data-action') || ''
		];
		var actionInput = form.querySelector('input[name="ACTION"], input[name="action"], input[name="register"], input[name="REGISTER"], input[name="Register"]');
		if (actionInput) {
			parts.push(actionInput.name || '', actionInput.value || '');
		}
		var typeInput = form.querySelector('input[name="TYPE"]');
		if (typeInput) {
			parts.push('TYPE', typeInput.value || '');
		}
		if (form.querySelector('input[name="AUTH_FORM"]')) {
			parts.push('AUTH_FORM');
		}
		return { form: form, text: parts.join(' ') };
	}

	/**
	 * @returns {'signup'|'checkout'|null}
	 */
	function contextFor(inp) {
		if (!inp) return null;

		var formEarly = inp.form || inp.closest('form');
		if (formEarly && formEarly.name === 'bform'
			&& (inp.name || '').toUpperCase() === 'USER_EMAIL') {
			return 'signup';
		}

		var blob = formBlob(inp);
		var form = blob.form;
		var id = blob.text;

		// «Заказать товар» / 1 клик — учётка с формы не создаётся
		if (isBuyOneClick(inp, form, id)) {
			return everywhere ? 'signup' : null;
		}

		if (inp.closest('#bx-soa-order, #bx-soa-properties, #bx-soa-main-notifications')) {
			return 'checkout';
		}
		if (document.getElementById('bx-soa-order') && inp.closest('form[name="ORDER_FORM"], #bx-soa-order')) {
			return 'checkout';
		}

		// Не матчить «under_order» по подстроке ORDER
		if (/\bORDER_FORM\b|bx-soa|sale\.order\.ajax|\/order\/|checkout/i.test(id)) {
			return 'checkout';
		}
		if (form && form.querySelector && form.querySelector('input[name="ORDER_PROP_EMAIL"], input[name^="ORDER_PROP_"]')) {
			if (inp.name && /^ORDER_PROP_/i.test(inp.name)) {
				return 'checkout';
			}
		}
		// Профиль покупателя в ЛК
		if (/profile|profiles/i.test(location.pathname) && inp.name && /^ORDER_PROP_/i.test(inp.name || '')) {
			return 'checkout';
		}

		if (/regist|signup|AUTH_FORM|bx-auth|personal\/register|USER_REGISTER|main\.profile|private/i.test(id)) {
			return 'signup';
		}
		if (form) {
			if (form.querySelector('input[name="TYPE"][value="REGISTRATION"]')
				&& form.querySelector('input[name="USER_EMAIL"], input[name="REGISTER[EMAIL]"]')
				&& form.querySelector('input[name="USER_PASSWORD"], input[name="REGISTER[PASSWORD]"], input[type="password"]')) {
				return 'signup';
			}
			if (form.querySelector('input[name="REGISTER[LOGIN]"], input[name="REGISTER[EMAIL]"], input[name="UF_EMAIL"]')) {
				return 'signup';
			}
			if (form.querySelector('input[name="register_submit_button"], input[name="register"], button[name="register"]')) {
				return 'signup';
			}
			// Личный кабинет / персональные данные / профиль заказа
			if (form.querySelector('input[name="EMAIL"]') && (
				/personal|profile|private/i.test(location.pathname)
				|| form.querySelector('input[name="save"], button[name="save"], input[name="apply"]')
			)) {
				if (!isBuyOneClick(inp, form, id) && !isLeadForm(inp, form, id)) {
					return 'signup';
				}
			}
			var emailName = (inp.name || '').toLowerCase();
			if ((emailName === 'email' || emailName === 'user_email' || emailName === 'register[email]')
				&& form.querySelector('input[name="PASSWORD"], input[name="REGISTER[PASSWORD]"], input[type="password"]')
				&& form.querySelector('input[name="LOGIN"], input[name="REGISTER[LOGIN]"], input[name="USER_LOGIN"]')) {
				return 'signup';
			}
		}

		if (everywhere) {
			return 'signup';
		}
		return null;
	}

	function noticeHtml(ctx) {
		return ctx === 'checkout' ? (cfg.noticeCheckout || '') : (cfg.noticeSignup || '');
	}

	function ensureBox(inp, create) {
		var anchor = noticeAnchor(inp);
		if (!anchor || !anchor.parentNode) return null;
		var sibling = anchor.nextElementSibling;
		if (sibling && sibling.classList && sibling.classList.contains('prime-alerts-live-notice')) {
			return sibling;
		}
		if (!create) return null;
		var box = document.createElement('div');
		box.className = 'prime-alerts-live-notice';
		box.setAttribute('aria-live', 'polite');
		box.style.display = 'none';
		if (anchor.nextSibling) {
			anchor.parentNode.insertBefore(box, anchor.nextSibling);
		} else {
			anchor.parentNode.appendChild(box);
		}
		return box;
	}

	function removeEmptyNoticeBox(inp) {
		hideLiveNotice(inp);
	}

	function hideLiveNotice(inp, kind) {
		var anchor = noticeAnchor(inp);
		if (!anchor || !anchor.parentNode) return;
		var box = anchor.nextElementSibling;
		if (!box || !box.classList || !box.classList.contains('prime-alerts-live-notice')) {
			return;
		}
		if (kind && box.getAttribute('data-kind') !== kind) {
			return;
		}
		hideBox(box);
		box.removeAttribute('data-kind');
		box.removeAttribute('data-filled');
		box.removeAttribute('data-ctx');
		box.removeAttribute('data-dup-filled');
		box.innerHTML = '';
		if (box.parentNode) {
			box.parentNode.removeChild(box);
		}
	}

	function isEmailInput(inp) {
		if (!inp || inp.tagName !== 'INPUT') return false;
		var type = (inp.type || '').toLowerCase();
		if (type === 'hidden' || type === 'password' || type === 'checkbox' || type === 'radio' || type === 'file' || type === 'submit' || type === 'button') {
			return false;
		}
		var name = (inp.name || '').toLowerCase();
		var auto = (inp.getAttribute('autocomplete') || '').toLowerCase();
		var id = (inp.id || '').toLowerCase();
		if (type === 'email') return true;
		if (auto === 'email') return true;
		// vrn-ehk: видимое поле логина = e-mail, REGISTER[EMAIL] скрытый
		if (name === 'register[login]' || name === 'register[email]') return true;
		if (name === 'user_email' || name === 'email' || name.indexOf('email') >= 0) return true;
		if (id.indexOf('email') >= 0) return true;
		var wrap = fieldWrap(inp);
		var label = wrap ? wrap.textContent : '';
		if (/e-?mail/i.test(label) && (type === 'text' || type === '' || !inp.type)) return true;
		return false;
	}

	function policyEnabledFor(ctx) {
		if (ctx === 'checkout') return cfg.policyOrder !== false;
		if (ctx === 'signup') return cfg.policyRegister !== false;
		return false;
	}

	function duplicateStateFor(inp) {
		if (!duplicateState) return { exists: false, checking: false };
		return duplicateState.get(inp) || { exists: false, checking: false };
	}

	function setDuplicateState(inp, state) {
		if (duplicateState) duplicateState.set(inp, state);
	}

	function showDuplicateNotice(box) {
		if (!box.getAttribute('data-dup-filled')) {
			box.innerHTML = cfg.emailExistsNotice || '';
			box.setAttribute('data-dup-filled', '1');
			box.setAttribute('data-kind', 'duplicate');
		}
		box.style.display = 'block';
		box.classList.add('is-visible');
	}

	function getExcludeUserId(inp) {
		var id = parseInt(cfg.currentUserId, 10);
		if (id > 0) return id;
		var form = inp && (inp.form || inp.closest('form'));
		if (form) {
			var idInp = form.querySelector('input[name="ID"]');
			if (idInp && idInp.value) {
				id = parseInt(idInp.value, 10);
				if (id > 0) return id;
			}
		}
		return 0;
	}

	function isOwnProfileEmail(email, inp) {
		email = String(email || '').trim().toLowerCase();
		if (!email) return false;
		var own = String(cfg.profileEmail || '').trim().toLowerCase();
		if (own !== '' && email === own) return true;
		return false;
	}

	function duplicateCacheKey(email, inp) {
		return String(email || '').trim().toLowerCase() + '|' + getExcludeUserId(inp);
	}

	function checkEmailDuplicate(email, inp) {
		var url = cfg.checkEmailUrl || '/local/modules/prime.alerts/ajax/check_email.php';
		var excludeUserId = inp ? getExcludeUserId(inp) : (parseInt(cfg.currentUserId, 10) || 0);
		var body = 'sessid=' + encodeURIComponent(cfg.sessid || (window.BX && BX.bitrix_sessid && BX.bitrix_sessid()) || '')
			+ '&email=' + encodeURIComponent(email);
		if (excludeUserId > 0) {
			body += '&exclude_user_id=' + encodeURIComponent(String(excludeUserId));
		}
		return fetch(url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
				'X-Requested-With': 'XMLHttpRequest'
			},
			body: body
		}).then(function (r) { return r.json(); }).catch(function () { return { ok: false }; });
	}

	function scheduleDuplicateCheck(inp) {
		if (cfg.checkEmailDuplicate === false || contextFor(inp) !== 'signup') {
			return;
		}
		var email = String(inp.value || '').trim();
		if (!looksComplete(email)) {
			setDuplicateState(inp, { exists: false, checking: false });
			return;
		}
		if (isOwnProfileEmail(email, inp)) {
			setDuplicateState(inp, { exists: false, checking: false });
			return;
		}
		var cacheKey = duplicateCacheKey(email, inp);
		if (duplicateCache && duplicateCache.has(cacheKey)) {
			setDuplicateState(inp, { exists: duplicateCache.get(cacheKey), checking: false });
			return;
		}
		setDuplicateState(inp, { exists: false, checking: true });
		if (!duplicateTimers) {
			checkEmailDuplicate(email, inp).then(function (data) {
				var exists = !!(data && data.ok && data.exists);
				if (duplicateCache) duplicateCache.set(cacheKey, exists);
				setDuplicateState(inp, { exists: exists, checking: false });
				if (String(inp.value || '').trim() === email) refreshInput(inp);
			}).catch(function () {
				setDuplicateState(inp, { exists: false, checking: false });
				if (String(inp.value || '').trim() === email) refreshInput(inp);
			});
			return;
		}
		var prev = duplicateTimers.get(inp);
		if (prev) clearTimeout(prev);
		duplicateTimers.set(inp, setTimeout(function () {
			duplicateTimers.delete(inp);
			checkEmailDuplicate(email, inp).then(function (data) {
				var exists = !!(data && data.ok && data.exists);
				if (duplicateCache) duplicateCache.set(cacheKey, exists);
				setDuplicateState(inp, { exists: exists, checking: false });
				if (String(inp.value || '').trim() === email) refreshInput(inp);
			}).catch(function () {
				setDuplicateState(inp, { exists: false, checking: false });
				if (String(inp.value || '').trim() === email) refreshInput(inp);
			});
		}, DUPLICATE_DEBOUNCE_MS));
	}

	function showNotice(inp, ctx, box) {
		if (!box.getAttribute('data-filled')) {
			box.innerHTML = noticeHtml(ctx);
			box.setAttribute('data-filled', '1');
			box.setAttribute('data-ctx', ctx);
		} else if (box.getAttribute('data-ctx') !== ctx) {
			box.innerHTML = noticeHtml(ctx);
			box.setAttribute('data-ctx', ctx);
		}
		box.style.display = 'block';
		box.classList.add('is-visible');
	}

	function hideBox(box) {
		if (!box) return;
		box.style.display = 'none';
		box.classList.remove('is-visible');
	}

	/**
	 * @param {HTMLInputElement} inp
	 * @param {{force?: boolean}} opts force=true on blur — show if looks complete
	 */
	function refreshInput(inp, opts) {
		opts = opts || {};
		if (!isEmailInput(inp)) return;
		var ctx = contextFor(inp);

		var email = String(inp.value || '').trim();
		if (!email || !looksComplete(email)) {
			setDuplicateState(inp, { exists: false, checking: false });
			removeEmptyNoticeBox(inp);
			return;
		}

		if (ctx === 'signup' && cfg.checkEmailDuplicate !== false) {
			scheduleDuplicateCheck(inp);
			var dup = duplicateStateFor(inp);
			if (dup.exists) {
				showDuplicateNotice(ensureBox(inp, true));
				return;
			}
			if (dup.checking) {
				removeEmptyNoticeBox(inp);
				return;
			}
		}

		if (cfg.enabled === false || !ctx || !policyEnabledFor(ctx)) {
			removeEmptyNoticeBox(inp);
			return;
		}

		var box = ensureBox(inp, true);
		if (!box) return;

		if (!isAllowed(email)) {
			box.removeAttribute('data-dup-filled');
			box.removeAttribute('data-kind');
			showNotice(inp, ctx, box);
			box.setAttribute('data-kind', 'policy');
			box.classList.add('is-visible');
		} else {
			hideLiveNotice(inp, 'policy');
		}
	}

	function scheduleRefresh(inp) {
		if (!timers) {
			refreshInput(inp);
			return;
		}
		var prev = timers.get(inp);
		if (prev) clearTimeout(prev);
		// hide immediately if incomplete so notice doesn't linger mid-edit
		var email = String(inp.value || '').trim();
		if (!looksComplete(email)) {
			removeEmptyNoticeBox(inp);
		}
		timers.set(inp, setTimeout(function () {
			timers.delete(inp);
			refreshInput(inp);
		}, DEBOUNCE_MS));
	}

	function scan(root) {
		root = root || document;
		initBxRegistrationEmail();
		var nodes = root.querySelectorAll('input[type="email"], input[type="text"], input:not([type])');
		for (var i = 0; i < nodes.length; i++) {
			if (isEmailInput(nodes[i])) refreshInput(nodes[i]);
		}
	}

	function bind() {
		document.addEventListener('input', function (e) {
			if (e.target && e.target.tagName === 'INPUT') scheduleRefresh(e.target);
		}, true);
		document.addEventListener('change', function (e) {
			if (e.target && e.target.tagName === 'INPUT') refreshInput(e.target);
		}, true);
		document.addEventListener('blur', function (e) {
			if (e.target && e.target.tagName === 'INPUT') refreshInput(e.target, { force: true });
		}, true);
		document.addEventListener('submit', function (e) {
			var form = e.target;
			if (!form || form.tagName !== 'FORM') return;
			var inputs = form.querySelectorAll('input[name="USER_EMAIL"], input[name="REGISTER[EMAIL]"], input[name="EMAIL"]');
			for (var i = 0; i < inputs.length; i++) {
				var emailInput = inputs[i];
				if (!isEmailInput(emailInput) || contextFor(emailInput) !== 'signup') continue;
				var email = String(emailInput.value || '').trim();
				if (!looksComplete(email) || cfg.checkEmailDuplicate === false) continue;
				if (form.getAttribute('data-prime-alerts-email-ok') === email) {
					form.removeAttribute('data-prime-alerts-email-ok');
					continue;
				}
				if (isOwnProfileEmail(email, emailInput)) continue;
				var cacheKey = duplicateCacheKey(email, emailInput);
				var dup = duplicateStateFor(emailInput);
				if (dup.exists) {
					e.preventDefault();
					e.stopPropagation();
					refreshInput(emailInput, { force: true });
					try { emailInput.focus(); } catch (err) {}
					return;
				}
				if (dup.checking || !(duplicateCache && duplicateCache.has(cacheKey))) {
					e.preventDefault();
					e.stopPropagation();
					checkEmailDuplicate(email, emailInput).then(function (data) {
						var exists = !!(data && data.ok && data.exists);
						if (duplicateCache) duplicateCache.set(cacheKey, exists);
						setDuplicateState(emailInput, { exists: exists, checking: false });
						refreshInput(emailInput, { force: true });
						if (!exists) {
							form.setAttribute('data-prime-alerts-email-ok', email);
							if (typeof form.requestSubmit === 'function') {
								form.requestSubmit();
							} else {
								var btn = form.querySelector('input[type="submit"], button[type="submit"]');
								if (btn) btn.click();
							}
						} else {
							try { emailInput.focus(); } catch (err2) {}
						}
					});
					return;
				}
			}
		}, true);
		scan();
		initBxRegistrationEmail();
	}

	function initBxRegistrationEmail() {
		var inp = document.querySelector('.bx-authform form[name="bform"] input[name="USER_EMAIL"]');
		if (!inp || inp.getAttribute('data-prime-email-bound') === '1') return;
		inp.setAttribute('data-prime-email-bound', '1');
		var lastVal = '';
		function runCheck() {
			if (looksComplete(inp.value)) refreshInput(inp, { force: true });
		}
		inp.addEventListener('input', runCheck);
		inp.addEventListener('change', runCheck);
		inp.addEventListener('blur', runCheck);
		inp.addEventListener('keyup', runCheck);
		// autofill часто не шлёт input/blur — опрашиваем значение
		var poll = setInterval(function () {
			var v = String(inp.value || '');
			if (v !== lastVal) {
				lastVal = v;
				runCheck();
			}
		}, 400);
		setTimeout(function () { clearInterval(poll); }, 12000);
		runCheck();
		setTimeout(runCheck, 100);
		setTimeout(runCheck, 600);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bind);
	} else {
		bind();
	}

	if (window.BX && BX.addCustomEvent) {
		BX.addCustomEvent('onAjaxSuccess', function () { scan(); });
		BX.addCustomEvent('onFrameDataReceived', function () { scan(); });
	}
	setInterval(function () { scan(); }, 1500);

	window.primeAlertsCheckRegistrationEmail = function (inp) {
		if (!inp) {
			inp = document.querySelector('.bx-authform form[name="bform"] input[name="USER_EMAIL"]');
		}
		if (inp) refreshInput(inp, { force: true });
	};
})();
