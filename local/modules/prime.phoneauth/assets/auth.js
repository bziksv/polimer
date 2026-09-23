/**
 * prime.phoneauth — login by phone / confirm by inbound call
 */
(function () {
	var cfg = window.PRIME_PHONEAUTH;
	if (!cfg) return;
	var phoneOn = cfg.enabled !== false;

	function postForm(url, data) {
		var body = [];
		data.sessid = data.sessid || cfg.sessid || '';
		Object.keys(data).forEach(function (k) {
			body.push(encodeURIComponent(k) + '=' + encodeURIComponent(data[k]));
		});
		return fetch(url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
				'X-Requested-With': 'XMLHttpRequest'
			},
			body: body.join('&')
		}).then(function (r) { return r.json(); });
	}

	function phoneDigits(value) {
		var d = String(value || '').replace(/\D/g, '');
		if (d.length === 11 && (d.charAt(0) === '7' || d.charAt(0) === '8')) {
			return d.slice(1);
		}
		return d.length === 10 ? d : d;
	}

	var defaultAuthRedirect = '/personal/orders-list.php';

	function unwrapAuthBackurl(url) {
		if (!url || url.charAt(0) !== '/') {
			return defaultAuthRedirect;
		}
		var q = url.indexOf('?');
		if (q === -1) {
			if (url === '/personal/' || url === '/personal') {
				return defaultAuthRedirect;
			}
			return url;
		}
		var path = url.slice(0, q);
		var params = new URLSearchParams(url.slice(q + 1));
		var inner = params.get('backurl');
		if (inner && inner.charAt(0) === '/' && (path === '/login/' || path === '/login' || path === '/auth/' || path === '/auth')) {
			return unwrapAuthBackurl(inner);
		}
		if (path === '/personal/' || path === '/personal') {
			return defaultAuthRedirect;
		}
		return url;
	}

	function resolveAuthRedirect(serverRedirect) {
		if (serverRedirect && serverRedirect.charAt(0) === '/' && serverRedirect !== '/personal/' && serverRedirect !== '/personal') {
			return unwrapAuthBackurl(serverRedirect);
		}
		var inp = document.querySelector('input[name="backurl"]');
		if (inp && inp.value && inp.value.charAt(0) === '/') {
			return unwrapAuthBackurl(inp.value);
		}
		var m = location.search.match(/(?:^|[?&])backurl=([^&]+)/);
		if (m) {
			var u = decodeURIComponent(m[1]);
			if (u.charAt(0) === '/') {
				return unwrapAuthBackurl(u);
			}
		}
		return defaultAuthRedirect;
	}

	function authRedirectTarget(data) {
		return resolveAuthRedirect(data && data.redirect ? data.redirect : '');
	}

	function switchToLogin() {
		var auth = document.querySelector('.personal_enter .auth');
		if (!auth) return;
		var tab = auth.querySelector('.prime-phoneauth-tabs [data-tab="password"]');
		if (tab) tab.click();
		var loginInp = auth.querySelector('input[name="USER_LOGIN"]');
		if (loginInp) {
			loginInp.focus();
			if (loginInp.scrollIntoView) {
				loginInp.scrollIntoView({ block: 'center', behavior: 'smooth' });
			}
		}
	}

	function waitMarkup() {
		return '<p data-role="message"></p>'
			+ '<p>Звоните с номера <strong data-role="from-phone"></strong></p>'
			+ '<p class="prime-phoneauth-call-line"><span class="prime-phoneauth-call-line__label">Звоните на телефон:</span><a class="prime-phoneauth-number" data-role="call-number"></a></p>'
			+ '<div class="prime-phoneauth-error" data-role="wait-error" style="display:none"></div>'
			+ '<button type="button" class="prime-phoneauth-test" data-role="test">Я позвонил (тест)</button>'
			+ '<button type="button" class="prime-phoneauth-back" data-role="back">Отмена</button>';
	}

	function fillWaitBox(root, data) {
		var msg = root.querySelector('[data-role="message"]');
		var num = root.querySelector('[data-role="call-number"]');
		var from = root.querySelector('[data-role="from-phone"]');
		var testBtn = root.querySelector('[data-role="test"]');
		if (msg) msg.textContent = data.message || '';
		if (from) from.textContent = data.phone || '';
		if (num) {
			var n = data.callNumber || cfg.callNumber || '';
			num.textContent = n || 'номер для звонка не настроен';
			if (n) num.href = 'tel:+' + String(n).replace(/\D/g, '');
		}
		if (testBtn) {
			testBtn.disabled = false;
			testBtn.style.display = data.testConfirm ? '' : 'none';
		}
		root.setAttribute('data-token', data.token || '');
	}

	function showDuplicate(message, accounts, title, opts) {
		opts = opts || {};
		var old = document.querySelector('.prime-phoneauth-modal');
		if (old && old.parentNode) {
			old.parentNode.removeChild(old);
		}
		var wrap = document.createElement('div');
		wrap.className = 'prime-phoneauth-modal';
		var overlayClose = opts.lock ? '0' : '1';
		var claimLabel = opts.claimLabel || (cfg.authorized
			? 'Подтвердить звонком'
			: 'Всё равно создать новый — подтвердить звонком');
		var chooseActions;
		if (opts.startClaim) {
			chooseActions = '<div class="prime-phoneauth-modal__actions">'
				+ '<button type="button" class="prime-phoneauth-modal__btn" data-claim="1">' + claimLabel + '</button>'
				+ (cfg.authorized
					? '<button type="button" class="prime-phoneauth-modal__btn is-ghost" data-close="1">Понятно</button>'
					: '<button type="button" class="prime-phoneauth-modal__btn is-ghost" data-login="1">Войти в существующий аккаунт</button>')
				+ '</div>';
		} else {
			chooseActions = '<div class="prime-phoneauth-modal__actions">'
				+ '<button type="button" class="prime-phoneauth-modal__btn" data-close="1">Понятно</button>'
				+ (!cfg.authorized
					? '<button type="button" class="prime-phoneauth-modal__btn is-ghost" data-login="1">Войти в существующий аккаунт</button>'
					: '')
				+ '</div>';
		}
		wrap.innerHTML = '<div class="prime-phoneauth-modal__overlay" data-close="' + overlayClose + '"></div>'
			+ '<div class="prime-phoneauth-modal__box">'
			+ '<div class="prime-phoneauth-modal__title"></div>'
			+ '<div class="prime-phoneauth-modal__choose" data-role="choose">'
			+ '<p class="prime-phoneauth-modal__text"></p>'
			+ '<div class="prime-phoneauth-modal__accounts-label" data-role="accounts-label" style="display:none">Ящики, где уже есть этот номер:</div>'
			+ '<ul class="prime-phoneauth-modal__accounts"></ul>'
			+ '<p class="prime-phoneauth-modal__hint" data-role="hint" style="display:none"></p>'
			+ chooseActions
			+ '</div>'
			+ '<div class="prime-phoneauth-modal__wait" data-role="wait" style="display:none">' + waitMarkup() + '</div>'
			+ '</div>';
		var titleEl = wrap.querySelector('.prime-phoneauth-modal__title');
		var choose = wrap.querySelector('[data-role="choose"]');
		var waitBox = wrap.querySelector('[data-role="wait"]');
		var waitErr = waitBox.querySelector('[data-role="wait-error"]');
		titleEl.textContent = title || 'Несколько аккаунтов';
		wrap.querySelector('.prime-phoneauth-modal__text').textContent = message || cfg.duplicateMessage;
		var list = wrap.querySelector('.prime-phoneauth-modal__accounts');
		var accountsLabel = wrap.querySelector('[data-role="accounts-label"]');
		var hintEl = wrap.querySelector('[data-role="hint"]');
		(accounts || []).forEach(function (email) {
			if (!email) return;
			var li = document.createElement('li');
			li.textContent = email;
			list.appendChild(li);
		});
		if (!list.children.length) {
			list.style.display = 'none';
			if (accountsLabel) accountsLabel.style.display = 'none';
		} else if (accountsLabel) {
			accountsLabel.style.display = '';
		}
		if (hintEl) {
			var hintText = opts.hint || cfg.duplicateHint || '';
			if (hintText) {
				hintEl.textContent = hintText;
				hintEl.style.display = '';
			}
		}
		document.body.appendChild(wrap);

		var pollTimer = null;
		function stopPoll() {
			if (pollTimer) {
				clearInterval(pollTimer);
				pollTimer = null;
			}
		}
		function setWaitError(text) {
			if (!waitErr) return;
			waitErr.textContent = text || '';
			waitErr.style.display = text ? '' : 'none';
		}
		function closeModal() {
			stopPoll();
			if (wrap.parentNode) wrap.parentNode.removeChild(wrap);
		}
		function showChoose() {
			stopPoll();
			setWaitError('');
			waitBox.style.display = 'none';
			choose.style.display = '';
			titleEl.textContent = title || 'Несколько аккаунтов';
		}
		function onConfirmed(token) {
			closeModal();
			if (opts.onConfirmed) opts.onConfirmed(token);
		}
		function startPoll(token) {
			stopPoll();
			if (!token) return;
			pollTimer = setInterval(function () {
				fetch(cfg.statusUrl + '&token=' + encodeURIComponent(token), { credentials: 'same-origin' })
					.then(function (r) { return r.json(); })
					.then(function (data) {
						if (data && data.status === 'confirmed') {
							onConfirmed(token);
						} else if (data && (data.status === 'expired' || data.status === 'cancelled' || data.status === 'missing')) {
							setWaitError(data.error || 'Время истекло. Попробуйте ещё раз.');
							stopPoll();
						}
					})
					.catch(function () {});
			}, 2000);
		}
		function showWaitView(data) {
			choose.style.display = 'none';
			waitBox.style.display = '';
			titleEl.textContent = 'Подтвердите номер';
			setWaitError('');
			fillWaitBox(waitBox, data);
			startPoll(data.token);
		}
		function beginClaim() {
			if (!opts.startClaim) return;
			choose.style.display = 'none';
			waitBox.style.display = '';
			titleEl.textContent = 'Подтвердите номер';
			setWaitError('');
			var msg = waitBox.querySelector('[data-role="message"]');
			if (msg) msg.textContent = 'Запрашиваем номер для звонка…';
			var from = waitBox.querySelector('[data-role="from-phone"]');
			if (from) from.textContent = '';
			var num = waitBox.querySelector('[data-role="call-number"]');
			if (num) {
				num.textContent = '';
				num.removeAttribute('href');
			}
			var testBtn = waitBox.querySelector('[data-role="test"]');
			if (testBtn) testBtn.style.display = 'none';
			opts.startClaim().then(function (data) {
				if (!data || !data.ok) {
					showChoose();
					wrap.querySelector('.prime-phoneauth-modal__text').textContent =
						(data && (data.error || data.message)) || message || cfg.duplicateMessage;
					return;
				}
				showWaitView(data);
			}).catch(function () {
				showChoose();
				wrap.querySelector('.prime-phoneauth-modal__text').textContent = 'Ошибка сети. Попробуйте ещё раз.';
			});
		}

		wrap.addEventListener('click', function (e) {
			var t = e.target;
			if (!t || !t.getAttribute) return;
			if (t.getAttribute('data-claim') === '1') {
				beginClaim();
				return;
			}
			if (t.getAttribute('data-login') === '1') {
				closeModal();
				if (opts.onLogin) opts.onLogin();
				else switchToLogin();
				return;
			}
			if (t.getAttribute('data-close') === '1' && !opts.lock) {
				closeModal();
			}
		});
		waitBox.querySelector('[data-role="back"]').addEventListener('click', showChoose);
		waitBox.querySelector('[data-role="test"]').addEventListener('click', function () {
			var token = waitBox.getAttribute('data-token') || '';
			var testBtn = waitBox.querySelector('[data-role="test"]');
			testBtn.disabled = true;
			postForm(cfg.testUrl, { token: token }).then(function (data) {
				if (data && data.status === 'confirmed') {
					onConfirmed(token);
					return;
				}
				testBtn.disabled = false;
				setWaitError((data && data.error) || 'Не подтвердилось');
			}).catch(function () {
				testBtn.disabled = false;
				setWaitError('Ошибка сети');
			});
		});

		if (opts.autoClaim) {
			beginClaim();
		}
	}

	function initTabs(root) {
		var tabs = root.querySelectorAll('.prime-phoneauth-tabs button');
		if (!tabs.length) return;
		tabs.forEach(function (btn) {
			btn.addEventListener('click', function () {
				var name = btn.getAttribute('data-tab');
				tabs.forEach(function (b) { b.classList.toggle('is-active', b === btn); });
				root.querySelectorAll('.prime-phoneauth-panel').forEach(function (p) {
					p.classList.toggle('is-active', p.getAttribute('data-panel') === name);
				});
			});
		});
	}

	function bindPhoneForm(root) {
		var form = root.querySelector('.prime-phoneauth-phone-form');
		var wait = root.querySelector('.prime-phoneauth-wait');
		var err = root.querySelector('.prime-phoneauth-error');
		if (!form) return;

		var pollTimer = null;
		function stopPoll() {
			if (pollTimer) {
				clearInterval(pollTimer);
				pollTimer = null;
			}
		}

		function setError(text) {
			if (!err) return;
			err.textContent = text || '';
			err.style.display = text ? '' : 'none';
		}

		function showWait(data) {
			form.style.display = 'none';
			if (!wait) return;
			wait.style.display = '';
			var msg = wait.querySelector('[data-role="message"]');
			var num = wait.querySelector('[data-role="call-number"]');
			var from = wait.querySelector('[data-role="from-phone"]');
			var testBtn = wait.querySelector('[data-role="test"]');
			if (msg) msg.textContent = data.message || '';
			if (num) {
				var n = data.callNumber || cfg.callNumber || '';
				num.textContent = n || 'номер для звонка не настроен';
				if (n) num.href = 'tel:+' + String(n).replace(/\D/g, '');
			}
			if (from) from.textContent = data.phone || '';
			if (testBtn) testBtn.style.display = data.testConfirm ? '' : 'none';
			wait.setAttribute('data-token', data.token || '');
			startPoll(data.token);
		}

		function resetWait() {
			stopPoll();
			if (wait) wait.style.display = 'none';
			form.style.display = '';
			setError('');
		}

		function startPoll(token) {
			stopPoll();
			if (!token) return;
			pollTimer = setInterval(function () {
				fetch(cfg.statusUrl + '&token=' + encodeURIComponent(token), { credentials: 'same-origin' })
					.then(function (r) { return r.json(); })
					.then(function (data) {
						if (data && data.status === 'confirmed') {
							stopPoll();
							window.location.href = authRedirectTarget(data);
						} else if (data && (data.status === 'expired' || data.status === 'cancelled' || data.status === 'missing')) {
							stopPoll();
							resetWait();
							setError(data.error || 'Время истекло');
						}
					})
					.catch(function () {});
			}, 2000);
		}

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			setError('');
			var phoneInp = form.querySelector('input[name="PHONE"]');
			var btn = form.querySelector('button[type="submit"], input[type="submit"]');
			if (btn) btn.disabled = true;
			postForm(cfg.startUrl, {
				phone: phoneInp ? phoneInp.value : '',
				backurl: resolveAuthRedirect('')
			})
				.then(function (data) {
					if (btn) btn.disabled = false;
					if (!data || !data.ok) {
						if (data && data.status === 'duplicate') {
							showDuplicate(data.error, data.accounts);
							return;
						}
						setError((data && data.error) || 'Не удалось начать вход');
						return;
					}
					showWait(data);
				})
				.catch(function () {
					if (btn) btn.disabled = false;
					setError('Ошибка сети');
				});
		});

		if (wait) {
			var back = wait.querySelector('[data-role="back"]');
			if (back) back.addEventListener('click', resetWait);
			var testBtn = wait.querySelector('[data-role="test"]');
			if (testBtn) {
				testBtn.addEventListener('click', function () {
					var token = wait.getAttribute('data-token') || '';
					testBtn.disabled = true;
					postForm(cfg.testUrl, { token: token })
						.then(function (data) {
							if (data && data.status === 'confirmed') {
								window.location.href = authRedirectTarget(data);
								return;
							}
							testBtn.disabled = false;
							setError((data && data.error) || 'Не подтвердилось');
						})
						.catch(function () { testBtn.disabled = false; });
				});
			}
		}
	}

	function loggedInClaimOpts(getPhone) {
		return {
			startClaim: function () {
				var phone = typeof getPhone === 'function' ? getPhone() : (getPhone || cfg.phone || '');
				return postForm(cfg.startUrl, { phone: phone, verify: 'Y', claim: 'Y' });
			},
			onConfirmed: function () { window.location.reload(); },
			claimLabel: 'Подтвердить звонком'
		};
	}

	function initProfile() {
		if (!cfg.authorized) return;
		var phoneInput = document.querySelector('#personal-contacts input[name="PERSONAL_PHONE"]')
			|| document.querySelector('.pd__block input[name="PERSONAL_PHONE"]');
		if (!phoneInput) return;
		var line = phoneInput.closest('.line');
		var container = phoneInput.closest('#personal-contacts') || line && line.parentNode;
		if (!line || !container || container.querySelector('.prime-phoneauth-status')) return;

		var status = document.createElement('div');
		status.className = 'prime-phoneauth-status';
		if (cfg.confirmed) {
			status.className += ' is-ok';
			status.textContent = 'Номер подтверждён — можно входить по телефону';
			container.appendChild(status);
			return;
		}
		status.className += ' is-wait';
		status.innerHTML = '<span class="prime-phoneauth-status__text">' + (cfg.duplicate
			? 'Этот номер указан ещё в других аккаунтах.'
			: 'Номер не подтверждён') + '</span>'
			+ '<button type="button" class="prime-phoneauth-prompt__btn is-compact" data-role="verify">Подтвердить звонком</button>';
		container.appendChild(status);

		var waitBox = document.createElement('div');
		waitBox.className = 'prime-phoneauth-wait';
		waitBox.style.display = 'none';
		waitBox.innerHTML = waitMarkup();
		container.appendChild(waitBox);

		var pollTimer = null;
		function stopPoll() {
			if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
		}
		function startPoll(token) {
			stopPoll();
			pollTimer = setInterval(function () {
				fetch(cfg.statusUrl + '&token=' + encodeURIComponent(token), { credentials: 'same-origin' })
					.then(function (r) { return r.json(); })
					.then(function (data) {
						if (data && data.status === 'confirmed') {
							stopPoll();
							window.location.reload();
						}
					})
					.catch(function () {});
			}, 2000);
		}
		function showProfileWait(data) {
			waitBox.style.display = '';
			fillWaitBox(waitBox, data);
			startPoll(data.token);
		}

		status.querySelector('[data-role="verify"]').addEventListener('click', function () {
			var payload = { phone: phoneInput.value, verify: 'Y' };
			if (cfg.duplicate) payload.claim = 'Y';
			postForm(cfg.startUrl, payload)
				.then(function (data) {
					if (!data || !data.ok) {
						if (data && data.status === 'duplicate') {
							showDuplicate(cfg.duplicateMessage, data.accounts || cfg.duplicateAccounts, null, loggedInClaimOpts(function () {
								return phoneInput.value;
							}));
							return;
						}
						alert((data && data.error) || 'Не удалось начать подтверждение');
						return;
					}
					showProfileWait(data);
				});
		});
		waitBox.querySelector('[data-role="back"]').addEventListener('click', function () {
			stopPoll();
			waitBox.style.display = 'none';
		});
		waitBox.querySelector('[data-role="test"]').addEventListener('click', function () {
			var token = waitBox.getAttribute('data-token') || '';
			postForm(cfg.testUrl, { token: token }).then(function (data) {
				if (data && data.status === 'confirmed') {
					window.location.reload();
				}
			});
		});

		if (cfg.duplicate) {
			showDuplicate(cfg.duplicateMessage, cfg.duplicateAccounts, null, loggedInClaimOpts(function () {
				return phoneInput.value;
			}));
		}
	}

	function phoneReady(value) {
		var d = String(value || '').replace(/\D/g, '');
		if (d.length === 11 && (d.charAt(0) === '7' || d.charAt(0) === '8')) return true;
		return d.length === 10;
	}

	function findRegisterPhoneContext() {
		if (cfg.authorized) return null;

		var legacyForm = document.querySelector('.personal_enter .reg form[name="regform"]');
		if (legacyForm) {
			var legacyPhone = legacyForm.querySelector('input[name="REGISTER[PERSONAL_PHONE]"]');
			if (legacyPhone) {
				var legacyLine = legacyPhone.closest('.line');
				if (legacyLine && !legacyLine.parentNode.querySelector('.prime-phoneauth-reg')) {
					return { form: legacyForm, phoneInput: legacyPhone, anchor: legacyLine, mode: 'legacy' };
				}
			}
		}

		var bxForm = document.querySelector('.bx-authform form[name="bform"]');
		if (bxForm) {
			var bxPhone = bxForm.querySelector('input[name="USER_PERSONAL_PHONE"]');
			if (bxPhone) {
				var group = bxPhone.closest('.bx-authform-formgroup-container');
				var root = bxForm.closest('.bx-authform') || bxForm;
				if (group && !root.querySelector('.prime-phoneauth-reg')) {
					return { form: bxForm, phoneInput: bxPhone, anchor: group, mode: 'bx' };
				}
			}
		}

		return null;
	}

	function phoneDuplicateActive(data) {
		if (!data || !data.ok) return false;
		if ((data.accounts || []).length) return true;
		return data.status === 'taken' || data.status === 'exists';
	}

	var REG_TOKEN_STORAGE_KEY = 'prime_phoneauth_reg';

	function readRegTokenStorage(phone) {
		try {
			var raw = sessionStorage.getItem(REG_TOKEN_STORAGE_KEY);
			if (!raw) return '';
			var saved = JSON.parse(raw);
			if (!saved || !saved.token || !saved.phone) return '';
			if (saved.phone !== phoneDigits(phone)) return '';
			return String(saved.token);
		} catch (e) {
			return '';
		}
	}

	function writeRegTokenStorage(phone, token) {
		if (!token) return;
		try {
			sessionStorage.setItem(REG_TOKEN_STORAGE_KEY, JSON.stringify({
				phone: phoneDigits(phone),
				token: String(token)
			}));
		} catch (e) {}
	}

	function clearRegTokenStorage() {
		try {
			sessionStorage.removeItem(REG_TOKEN_STORAGE_KEY);
		} catch (e) {}
	}

	function initRegister() {
		var ctx = findRegisterPhoneContext();
		if (!ctx) return;
		var form = ctx.form;
		var phoneInput = ctx.phoneInput;
		var lookupOnly = cfg.lookupOnly === true || !phoneOn || cfg.callAuth !== true;

		var tokenInp = form.querySelector('input[name="prime_phoneauth_token"]');
		if (!tokenInp) {
			tokenInp = document.createElement('input');
			tokenInp.type = 'hidden';
			tokenInp.name = 'prime_phoneauth_token';
			form.appendChild(tokenInp);
		}

		var box = document.createElement('div');
		box.className = 'prime-phoneauth-reg' + (ctx.mode === 'bx' ? ' prime-phoneauth-reg--bx' : '');
		box.innerHTML = '<div class="prime-phoneauth-reg__status" data-role="status"></div>'
			+ '<ul class="prime-phoneauth-reg__accounts" data-role="accounts"></ul>'
			+ '<p class="prime-phoneauth-reg__links" data-role="links" style="display:none">'
			+ '<a href="/auth/">Войти</a> · <a href="/auth/?forgot_password=yes">Забыли пароль?</a>'
			+ '</p>'
			+ '<button type="button" class="prime-phoneauth-back" data-role="verify" style="display:none">Подтвердить звонком</button>'
			+ '<div class="prime-phoneauth-wait" data-role="wait" style="display:none">'
			+ '<p data-role="message"></p>'
			+ '<p>Звоните с номера <strong data-role="from-phone"></strong></p>'
			+ '<p class="prime-phoneauth-call-line"><span class="prime-phoneauth-call-line__label">Звоните на телефон:</span><a class="prime-phoneauth-number" data-role="call-number"></a></p>'
			+ '<button type="button" class="prime-phoneauth-test" data-role="test">Я позвонил (тест)</button>'
			+ '<button type="button" class="prime-phoneauth-back" data-role="back">Отмена</button>'
			+ '</div>';
		ctx.anchor.parentNode.insertBefore(box, ctx.anchor.nextSibling);

		var statusEl = box.querySelector('[data-role="status"]');
		var accountsEl = box.querySelector('[data-role="accounts"]');
		var linksEl = box.querySelector('[data-role="links"]');
		var verifyBtn = box.querySelector('[data-role="verify"]');
		var wait = box.querySelector('[data-role="wait"]');
		var lastLookupPhone = '';
		var lastModalPhone = '';
		var lastPhoneDigits = phoneDigits(phoneInput.value);
		var claimMode = false;
		var lastLookupData = null;
		var pollTimer = null;
		if (lookupOnly) {
			verifyBtn.style.display = 'none';
		}

		function syncRegBoxVisibility() {
			var hasContent = (statusEl.textContent || '').trim() !== ''
				|| accountsEl.children.length > 0
				|| wait.style.display !== 'none'
				|| (!lookupOnly && verifyBtn.style.display !== 'none');
			if (linksEl && linksEl.style.display !== 'none') {
				hasContent = true;
			}
			box.classList.toggle('is-visible', hasContent);
			box.style.display = hasContent ? '' : 'none';
		}

		function clearRegDuplicateNotice() {
			var live = box.querySelector('.prime-alerts-live-notice[data-kind="duplicate"]');
			if (live && live.parentNode) live.parentNode.removeChild(live);
			statusEl.style.display = '';
			accountsEl.style.display = '';
		}

		function openRegDuplicatePopup(data, phone) {
			clearRegDuplicateNotice();
			statusEl.textContent = '';
			statusEl.className = 'prime-phoneauth-reg__status';
			setAccounts([]);
			if (linksEl) linksEl.style.display = 'none';
			verifyBtn.style.display = 'none';
			syncRegBoxVisibility();
			if (lastModalPhone === phone && document.querySelector('.prime-phoneauth-modal')) {
				return;
			}
			lastModalPhone = phone;
			openClaimModal(false);
		}
		setAccounts([]);
		syncRegBoxVisibility();

		function stopPoll() {
			if (pollTimer) {
				clearInterval(pollTimer);
				pollTimer = null;
			}
		}

		function setAccounts(list) {
			accountsEl.innerHTML = '';
			(list || []).forEach(function (email) {
				if (!email) return;
				var li = document.createElement('li');
				li.textContent = email;
				accountsEl.appendChild(li);
			});
			accountsEl.style.display = accountsEl.children.length ? '' : 'none';
			syncRegBoxVisibility();
		}

		function resetConfirm() {
			stopPoll();
			tokenInp.value = '';
			clearRegTokenStorage();
			wait.style.display = 'none';
			verifyBtn.disabled = false;
		}

		function markConfirmed() {
			stopPoll();
			wait.style.display = 'none';
			verifyBtn.style.display = 'none';
			statusEl.className = 'prime-phoneauth-reg__status is-ok';
			statusEl.textContent = 'Номер подтверждён. Завершите регистрацию.';
			if (tokenInp.value && phoneReady(phoneInput.value)) {
				writeRegTokenStorage(phoneInput.value, tokenInp.value);
				lastLookupPhone = phoneInput.value;
			}
			syncRegBoxVisibility();
		}

		function restoreConfirmedToken() {
			var token = String(tokenInp.value || '').trim();
			if (!token) {
				token = readRegTokenStorage(phoneInput.value);
				if (token) tokenInp.value = token;
			}
			if (!token || !phoneReady(phoneInput.value)) {
				return Promise.resolve(false);
			}
			return fetch(cfg.statusUrl + '&token=' + encodeURIComponent(token), { credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (data) {
					if (data && data.status === 'confirmed') {
						tokenInp.value = token;
						markConfirmed();
						return true;
					}
					tokenInp.value = '';
					clearRegTokenStorage();
					return false;
				})
				.catch(function () { return false; });
		}

		function startPoll(token) {
			stopPoll();
			if (!token) return;
			pollTimer = setInterval(function () {
				fetch(cfg.statusUrl + '&token=' + encodeURIComponent(token), { credentials: 'same-origin' })
					.then(function (r) { return r.json(); })
					.then(function (data) {
						if (data && data.status === 'confirmed') {
							tokenInp.value = token;
							markConfirmed();
						} else if (data && (data.status === 'expired' || data.status === 'cancelled' || data.status === 'missing')) {
							resetConfirm();
							statusEl.className = 'prime-phoneauth-reg__status is-bad';
							statusEl.textContent = data.error || 'Время истекло. Запросите подтверждение ещё раз.';
							verifyBtn.style.display = '';
						}
					})
					.catch(function () {});
			}, 2000);
		}

		function showWait(data) {
			verifyBtn.style.display = 'none';
			wait.style.display = '';
			fillWaitBox(wait, data);
			startPoll(data.token);
		}

		function openClaimModal(autoClaim) {
			if (!lastLookupData) return;
			var data = lastLookupData;
			var title = data.status === 'taken' || ((data.accounts || []).length === 1)
				? 'Номер уже используется'
				: 'Несколько аккаунтов';
			showDuplicate(data.message || data.error, data.accounts, title, {
				lock: true,
				autoClaim: !!autoClaim,
				startClaim: function () {
					return postForm(cfg.startUrl, { phone: phoneInput.value, register: 'Y', claim: 'Y' });
				},
				onConfirmed: function (token) {
					tokenInp.value = token;
					markConfirmed();
					setAccounts([]);
				},
				onLogin: function () {
					switchToLogin();
					verifyBtn.style.display = '';
				}
			});
		}

		function applyLookup(data, phone) {
			if (!data || !data.ok) {
				clearRegDuplicateNotice();
				statusEl.className = 'prime-phoneauth-reg__status';
				statusEl.textContent = '';
				setAccounts([]);
				if (linksEl) linksEl.style.display = 'none';
				verifyBtn.style.display = 'none';
				claimMode = false;
				lastLookupData = null;
				syncRegBoxVisibility();
				return;
			}
			lastLookupData = data;
			if (ctx.mode === 'bx' && phoneDuplicateActive(data)) {
				openRegDuplicatePopup(data, phone);
				return;
			}
			clearRegDuplicateNotice();
			if (lookupOnly) {
				if (phoneDuplicateActive(data)) {
					statusEl.className = 'prime-phoneauth-reg__status is-bad';
					statusEl.textContent = data.message || cfg.duplicateMessage || 'Номер уже используется в другом аккаунте.';
					setAccounts(data.accounts || []);
					if (linksEl) linksEl.style.display = '';
				} else {
					statusEl.className = 'prime-phoneauth-reg__status';
					statusEl.textContent = data.status === 'free' ? '' : (data.message || '');
					setAccounts([]);
					if (linksEl) linksEl.style.display = 'none';
				}
				verifyBtn.style.display = 'none';
				syncRegBoxVisibility();
				return;
			}
			claimMode = !!data.canClaim || (data.accounts || []).length > 0;
			statusEl.className = 'prime-phoneauth-reg__status' + (data.status === 'taken' ? ' is-bad' : '');
			statusEl.textContent = data.message || '';
			setAccounts(claimMode ? [] : data.accounts);
			if (tokenInp.value || wait.style.display !== 'none' || document.querySelector('.prime-phoneauth-modal')) {
				verifyBtn.style.display = 'none';
				return;
			}
			verifyBtn.style.display = data.canConfirm ? '' : 'none';
			if ((data.accounts || []).length && lastModalPhone !== phone) {
				lastModalPhone = phone;
				openClaimModal(false);
			}
		}

		function lookupNow() {
			var phone = phoneInput.value;
			if (!phoneReady(phone)) {
				resetConfirm();
				clearRegDuplicateNotice();
				statusEl.textContent = '';
				statusEl.className = 'prime-phoneauth-reg__status';
				setAccounts([]);
				if (linksEl) linksEl.style.display = 'none';
				verifyBtn.style.display = 'none';
				lastLookupPhone = '';
				claimMode = false;
				lastLookupData = null;
				syncRegBoxVisibility();
				return;
			}
			if (document.querySelector('.prime-phoneauth-modal')) return;
			if (phone === lastLookupPhone && (tokenInp.value || wait.style.display !== 'none')) return;
			lastLookupPhone = phone;
			postForm(cfg.lookupUrl, { phone: phone }).then(function (data) {
				if (phoneInput.value !== phone) return;
				applyLookup(data, phone);
			});
		}

		phoneInput.addEventListener('blur', lookupNow);
		phoneInput.addEventListener('input', function () {
			clearTimeout(phoneInput._primeLookupTimer);
			phoneInput._primeLookupTimer = setTimeout(lookupNow, 500);
		});
		phoneInput.addEventListener('change', function () {
			var now = phoneDigits(phoneInput.value);
			if (now === lastPhoneDigits) return;
			lastPhoneDigits = now;
			resetConfirm();
			statusEl.className = 'prime-phoneauth-reg__status';
			statusEl.textContent = '';
			setAccounts([]);
			if (linksEl) linksEl.style.display = 'none';
			verifyBtn.style.display = 'none';
			lastLookupPhone = '';
			lastModalPhone = '';
			lookupNow();
		});

		verifyBtn.addEventListener('click', function () {
			if (claimMode) {
				openClaimModal(true);
				return;
			}
			verifyBtn.disabled = true;
			postForm(cfg.startUrl, { phone: phoneInput.value, register: 'Y' })
				.then(function (data) {
					verifyBtn.disabled = false;
					if (!data || !data.ok) {
						if (data && data.status === 'duplicate') {
							lastLookupData = data;
							claimMode = true;
							openClaimModal(false);
						}
						statusEl.className = 'prime-phoneauth-reg__status is-bad';
						statusEl.textContent = (data && (data.error || data.message)) || 'Не удалось начать подтверждение';
						return;
					}
					showWait(data);
				})
				.catch(function () {
					verifyBtn.disabled = false;
					statusEl.className = 'prime-phoneauth-reg__status is-bad';
					statusEl.textContent = 'Ошибка сети';
				});
		});

		wait.querySelector('[data-role="back"]').addEventListener('click', function () {
			resetConfirm();
			verifyBtn.style.display = '';
		});
		wait.querySelector('[data-role="test"]').addEventListener('click', function () {
			var token = wait.getAttribute('data-token') || '';
			var testBtn = wait.querySelector('[data-role="test"]');
			testBtn.disabled = true;
			postForm(cfg.testUrl, { token: token }).then(function (data) {
				testBtn.disabled = false;
				if (data && data.status === 'confirmed') {
					tokenInp.value = token;
					markConfirmed();
					return;
				}
				statusEl.className = 'prime-phoneauth-reg__status is-bad';
				statusEl.textContent = (data && data.error) || 'Не подтвердилось';
			}).catch(function () { testBtn.disabled = false; });
		});

		restoreConfirmedToken().then(function (restored) {
			if (!restored && phoneReady(phoneInput.value)) {
				lookupNow();
			}
		});

		form.addEventListener('submit', function (e) {
			if (tokenInp.value) return;
			if (!phoneReady(phoneInput.value)) return;
			if (lastLookupData && phoneDuplicateActive(lastLookupData)) {
				e.preventDefault();
				e.stopPropagation();
				applyLookup(lastLookupData, phoneInput.value);
				try { phoneInput.focus(); } catch (err) {}
				return;
			}
			if (!lastLookupData || lastLookupPhone !== phoneInput.value) {
				e.preventDefault();
				e.stopPropagation();
				postForm(cfg.lookupUrl, { phone: phoneInput.value }).then(function (data) {
					lastLookupPhone = phoneInput.value;
					applyLookup(data, phoneInput.value);
					if (!phoneDuplicateActive(data)) {
						if (typeof form.requestSubmit === 'function') {
							form.requestSubmit();
						} else {
							var btn = form.querySelector('input[type="submit"], button[type="submit"]');
							if (btn) btn.click();
						}
					} else {
						try { phoneInput.focus(); } catch (err2) {}
					}
				});
			}
		}, true);
	}

	function mountPhoneConfirm(root) {
		if (!root || root.getAttribute('data-bound') === '1') return;
		root.setAttribute('data-bound', '1');
		if (cfg.confirmed) {
			root.innerHTML = '<div class="prime-phoneauth-status is-ok">Номер подтверждён</div>';
			return;
		}

		var compact = root.classList.contains('is-inline');
		var needSpecify = !phoneReady(cfg.phone);
		var label = needSpecify ? 'Указать' : (compact ? 'Подтвердить' : 'Подтвердить звонком');

		root.innerHTML = '<div class="prime-phoneauth-error" data-role="error" style="display:none"></div>'
			+ (cfg.duplicate ? '<ul class="prime-phoneauth-reg__accounts" data-role="accounts"></ul>' : '')
			+ (needSpecify
				? '<input type="tel" class="prime-phoneauth-specify" data-role="phone" inputmode="tel" autocomplete="tel" placeholder="+7-___-___-__-__">'
				: '')
			+ '<button type="button" class="prime-phoneauth-prompt__btn' + (compact ? ' is-compact' : '') + '" data-role="verify">' + label + '</button>'
			+ '<div class="prime-phoneauth-wait" data-role="wait" style="display:none">' + waitMarkup() + '</div>';

		var err = root.querySelector('[data-role="error"]');
		var accountsEl = root.querySelector('[data-role="accounts"]');
		var verifyBtn = root.querySelector('[data-role="verify"]');
		var wait = root.querySelector('[data-role="wait"]');
		var phoneInp = root.querySelector('[data-role="phone"]');
		if (phoneInp) {
			if (window.PolimerRuPhone && typeof window.PolimerRuPhone.initRuPhoneFields === 'function') {
				window.PolimerRuPhone.initRuPhoneFields(phoneInp.parentNode || root);
			} else if (window.jQuery && jQuery.fn && jQuery.fn.inputmask) {
				jQuery(phoneInp).inputmask('+7-999-999-99-99', { showMaskOnHover: false, placeholder: '_' });
			} else if (window.jQuery && jQuery.fn && jQuery.fn.mask) {
				jQuery(phoneInp).mask('+7-999-999-99-99', { placeholder: '_', autoclear: false });
			}
		}
		var host = root.closest('.prime-alerts-profile-modal, .prime-alerts-profile-banner');
		var note = host ? host.querySelector('[data-prime-phone-note="1"]') : null;
		if (note) {
			if (err) note.appendChild(err);
			if (accountsEl) note.appendChild(accountsEl);
		}
		if (accountsEl) {
			(cfg.duplicateAccounts || []).forEach(function (email) {
				if (!email) return;
				var li = document.createElement('li');
				li.textContent = email;
				accountsEl.appendChild(li);
			});
			accountsEl.style.display = accountsEl.children.length ? '' : 'none';
			if (err && cfg.duplicateMessage) {
				err.textContent = cfg.duplicateMessage;
				err.style.display = '';
			}
		}

		var pollTimer = null;
		function stopPoll() {
			if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
		}
		function setError(text) {
			if (!err) return;
			err.textContent = text || '';
			err.style.display = text ? '' : 'none';
		}
		function currentPhone() {
			if (phoneInp) return phoneInp.value || '';
			return cfg.phone || '';
		}
		function startPoll(token) {
			stopPoll();
			if (!token) return;
			pollTimer = setInterval(function () {
				fetch(cfg.statusUrl + '&token=' + encodeURIComponent(token), { credentials: 'same-origin' })
					.then(function (r) { return r.json(); })
					.then(function (data) {
						if (data && data.status === 'confirmed') {
							stopPoll();
							window.location.reload();
						} else if (data && (data.status === 'expired' || data.status === 'cancelled' || data.status === 'missing')) {
							stopPoll();
							wait.style.display = 'none';
							verifyBtn.style.display = '';
							if (phoneInp) phoneInp.style.display = '';
							setError(data.error || 'Время истекло');
						}
					})
					.catch(function () {});
			}, 2000);
		}

		verifyBtn.addEventListener('click', function () {
			var phoneVal = currentPhone();
			if (!phoneReady(phoneVal)) {
				setError('Укажите корректный номер телефона РФ.');
				if (phoneInp) phoneInp.focus();
				return;
			}
			verifyBtn.disabled = true;
			setError('');
			postForm(cfg.startUrl, { phone: phoneVal, verify: 'Y', claim: cfg.duplicate ? 'Y' : '' })
				.then(function (data) {
					verifyBtn.disabled = false;
					if (!data || !data.ok) {
						if (data && data.status === 'duplicate') {
							showDuplicate(cfg.duplicateMessage, data.accounts || cfg.duplicateAccounts, null, loggedInClaimOpts(currentPhone));
						}
						setError((data && (data.error || data.message)) || 'Не удалось начать подтверждение');
						return;
					}
					var profileInp = document.querySelector('#personal-contacts input[name="PERSONAL_PHONE"]');
					if (profileInp) {
						profileInp.value = data.phone || phoneVal;
					}
					verifyBtn.style.display = 'none';
					if (phoneInp) phoneInp.style.display = 'none';
					wait.style.display = '';
					wait.querySelector('[data-role="message"]').textContent = data.message || '';
					wait.querySelector('[data-role="from-phone"]').textContent = data.phone || phoneVal || cfg.phone || '';
					var num = wait.querySelector('[data-role="call-number"]');
					var n = data.callNumber || cfg.callNumber || '';
					num.textContent = n || 'номер для звонка не настроен';
					if (n) num.href = 'tel:+' + String(n).replace(/\D/g, '');
					wait.querySelector('[data-role="test"]').style.display = data.testConfirm ? '' : 'none';
					wait.setAttribute('data-token', data.token || '');
					startPoll(data.token);
				})
				.catch(function () {
					verifyBtn.disabled = false;
					setError('Ошибка сети');
				});
		});
		wait.querySelector('[data-role="back"]').addEventListener('click', function () {
			stopPoll();
			wait.style.display = 'none';
			verifyBtn.style.display = '';
			if (phoneInp) phoneInp.style.display = '';
		});
		wait.querySelector('[data-role="test"]').addEventListener('click', function () {
			var token = wait.getAttribute('data-token') || '';
			var testBtn = wait.querySelector('[data-role="test"]');
			testBtn.disabled = true;
			postForm(cfg.testUrl, { token: token }).then(function (data) {
				if (data && data.status === 'confirmed') {
					window.location.reload();
					return;
				}
				testBtn.disabled = false;
				setError((data && data.error) || 'Не подтвердилось');
			}).catch(function () { testBtn.disabled = false; });
		});
	}

	function initPhonePrompt() {
		if (!cfg.authorized || cfg.confirmed) return;
		var slot = document.querySelector('[data-prime-phone-confirm="1"]');
		if (slot) {
			mountPhoneConfirm(slot);
			return;
		}
		if (!cfg.standalonePrompt) return;
		if (document.querySelector('.prime-alerts-profile-modal, .prime-phoneauth-prompt-modal')) return;

		var wrap = document.createElement('div');
		wrap.className = 'prime-phoneauth-prompt-modal';
		wrap.setAttribute('role', 'dialog');
		wrap.innerHTML = '<div class="prime-phoneauth-prompt-modal__overlay"></div>'
			+ '<div class="prime-phoneauth-prompt-modal__box">'
			+ '<button type="button" class="prime-phoneauth-prompt-modal__close" data-close="1" aria-label="Закрыть">&times;</button>'
			+ '<div class="prime-phoneauth-prompt-modal__title">Подтвердите телефон</div>'
			+ '<p class="prime-phoneauth-prompt-modal__text">Подтвердите номер звонком — после этого можно будет входить в кабинет по телефону, без пароля.</p>'
			+ '<div class="prime-phoneauth-prompt-modal__phone">' + (cfg.phone || '') + '</div>'
			+ '<div data-prime-phone-confirm="1"></div>'
			+ '<button type="button" class="prime-phoneauth-prompt-modal__snooze" data-snooze="1">Отложить на 2 недели</button>'
			+ '</div>';
		document.body.appendChild(wrap);
		document.body.classList.add('prime-phoneauth-prompt-open');
		mountPhoneConfirm(wrap.querySelector('[data-prime-phone-confirm="1"]'));

		function closeModal() {
			wrap.parentNode && wrap.parentNode.removeChild(wrap);
			document.body.classList.remove('prime-phoneauth-prompt-open');
		}
		wrap.addEventListener('click', function (e) {
			if (e.target && e.target.getAttribute('data-close') === '1') {
				closeModal();
			}
			if (e.target && e.target.getAttribute('data-snooze') === '1') {
				e.preventDefault();
				e.target.disabled = true;
				postForm(cfg.snoozeUrl, {}).then(closeModal).catch(closeModal);
			}
		});
		document.addEventListener('keydown', function onKey(e) {
			if (e.key === 'Escape') {
				document.removeEventListener('keydown', onKey);
				closeModal();
			}
		});
	}

	function initAuthSwitchLinks() {
		document.querySelectorAll('.bx-authform-link-container a[href]').forEach(function (a) {
			if (a.getAttribute('data-prime-auth-switch') === '1') {
				return;
			}
			var href = a.getAttribute('href') || '';
			if (!/(^|[?&])(login|register)=yes/.test(href)) {
				return;
			}
			a.setAttribute('data-prime-auth-switch', '1');
			a.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				if (typeof CloseWaitWindow === 'function') {
					CloseWaitWindow();
				}
				window.location.assign(href);
			});
		});
	}

	function isDuplicateErrorText(text) {
		return /номер уже есть в других|номер уже привязан|уже используется в друг/i.test(text || '');
	}

	function stripOrderRegPrefix(text) {
		return String(text || '').replace(/^Ошибка регистрации нового пользователя:\s*/i, '').trim();
	}

	function getOrderPhone() {
		var form = document.getElementById('bx-soa-order-form');
		if (!form) return '';
		var candidates = form.querySelectorAll(
			'input[type="tel"], input[autocomplete="tel"], input[name*="PHONE"], input[name*="Phone"], input[name*="phone"]'
		);
		for (var i = 0; i < candidates.length; i++) {
			var v = (candidates[i].value || '').trim();
			if (phoneReady(v)) return v;
		}
		var all = form.querySelectorAll('input[type="text"], input:not([type])');
		for (var j = 0; j < all.length; j++) {
			var val = (all[j].value || '').trim();
			if (/^\+?7[\d\-\s()]{10,}$/.test(val) && phoneReady(val)) return val;
		}
		return '';
	}

	function injectOrderToken(token) {
		var form = document.getElementById('bx-soa-order-form');
		if (!form || !token) return;
		var inp = form.querySelector('input[name="prime_phoneauth_token"]');
		if (!inp) {
			inp = document.createElement('input');
			inp.type = 'hidden';
			inp.name = 'prime_phoneauth_token';
			form.appendChild(inp);
		}
		inp.value = token;
	}

	function retryOrderSave() {
		try {
			if (window.BX && BX.Sale && BX.Sale.OrderAjaxComponent) {
				var comp = BX.Sale.OrderAjaxComponent;
				if (typeof comp.clickOrderSaveButton === 'function') {
					comp.clickOrderSaveButton();
					return;
				}
				if (typeof comp.sendRequest === 'function') {
					if (typeof comp.allowOrderSave === 'function') {
						comp.allowOrderSave();
					}
					comp.sendRequest('saveOrderAjax');
					return;
				}
			}
		} catch (e) {}
		var btn = document.querySelector('#bx-soa-orderSave a, #bx-soa-orderSave .btn, [data-save-button]');
		if (btn) btn.click();
	}

	function hideOrderDuplicateBanner(el) {
		if (!el) return;
		el.classList.add('prime-phoneauth-dup-hide');
		el.style.setProperty('display', 'none', 'important');
		el.setAttribute('aria-hidden', 'true');
	}

	function hideAllOrderDuplicateBanners(scope) {
		var root = scope && scope.querySelectorAll ? scope : document;
		root.querySelectorAll('.alert.alert-danger, .bx-soa-alert').forEach(function (el) {
			if (isDuplicateErrorText(el.textContent || '')) {
				hideOrderDuplicateBanner(el);
			}
		});
	}

	var orderDupOpening = false;
	var orderDupArmed = true;

	function htmlToPlain(html) {
		var d = document.createElement('div');
		d.innerHTML = String(html || '');
		return (d.textContent || d.innerText || '').replace(/\s+/g, ' ').trim();
	}

	function fillModalAccounts(wrap, accounts) {
		if (!wrap) return;
		var list = wrap.querySelector('.prime-phoneauth-modal__accounts');
		var accountsLabel = wrap.querySelector('[data-role="accounts-label"]');
		if (!list) return;
		list.innerHTML = '';
		(accounts || []).forEach(function (email) {
			if (!email) return;
			var li = document.createElement('li');
			li.textContent = email;
			list.appendChild(li);
		});
		if (!list.children.length) {
			list.style.display = 'none';
			if (accountsLabel) accountsLabel.style.display = 'none';
		} else {
			list.style.display = '';
			if (accountsLabel) accountsLabel.style.display = '';
		}
	}

	function openOrderDuplicateModal(message, accounts, phone) {
		if (document.querySelector('.prime-phoneauth-modal')) {
			return document.querySelector('.prime-phoneauth-modal');
		}
		var title = (accounts || []).length === 1 ? 'Номер уже используется' : 'Несколько аккаунтов';
		var canClaim = phoneOn && cfg.callAuth === true && phoneReady(phone);

		showDuplicate(message || cfg.duplicateMessage, accounts, title, {
			lock: false,
			hint: cfg.duplicateHint,
			claimLabel: 'Подтвердить звонком и продолжить заказ',
			startClaim: canClaim ? function () {
				return postForm(cfg.startUrl, { phone: phone, register: 'Y', claim: 'Y' });
			} : null,
			onConfirmed: function (token) {
				injectOrderToken(token);
				orderDupArmed = true;
				setTimeout(retryOrderSave, 200);
			},
			onLogin: function () {
				// Наша форма входа (/login/), не кривой блок sale.order.ajax
				var back = '/personal/order/make/';
				try {
					if (window.location.pathname && window.location.pathname.indexOf('/personal/order/') === 0) {
						back = window.location.pathname + (window.location.search || '');
					}
				} catch (e) {}
				window.location.assign('/login/?login=yes&backurl=' + encodeURIComponent(back));
			}
		});
		return document.querySelector('.prime-phoneauth-modal');
	}

	function processOrderDuplicateError(rawText) {
		var text = stripOrderRegPrefix(htmlToPlain(rawText));
		if (!isDuplicateErrorText(text)) return false;

		hideAllOrderDuplicateBanners(document);

		if (!orderDupArmed || document.querySelector('.prime-phoneauth-modal') || orderDupOpening) {
			return true;
		}

		orderDupArmed = false;
		orderDupOpening = true;
		var phone = getOrderPhone();

		// Модалку сразу — не ждём lookup (иначе кажется, что «ничего нет»)
		var modal = openOrderDuplicateModal(text || cfg.duplicateMessage, [], phone);
		orderDupOpening = false;

		if (phoneReady(phone) && cfg.lookupUrl) {
			postForm(cfg.lookupUrl, { phone: phone }).then(function (data) {
				if (!data) return;
				var wrap = document.querySelector('.prime-phoneauth-modal');
				if (!wrap) return;
				var msg = data.message || data.error;
				if (msg) {
					var textEl = wrap.querySelector('.prime-phoneauth-modal__text');
					if (textEl) textEl.textContent = msg;
				}
				fillModalAccounts(wrap, data.accounts || []);
				var titleEl = wrap.querySelector('.prime-phoneauth-modal__title');
				if (titleEl && (data.accounts || []).length === 1) {
					titleEl.textContent = 'Номер уже используется';
				}
			}).catch(function () {});
		}

		return true;
	}

	function scanOrderDuplicateAlerts(root) {
		if (!document.getElementById('bx-soa-order-form')) return;
		var scope = root && root.querySelectorAll ? root : document;
		var found = null;
		scope.querySelectorAll('.alert.alert-danger, .bx-soa-alert, .bx-soa-section .alert').forEach(function (el) {
			var text = (el.textContent || '').replace(/\s+/g, ' ').trim();
			if (!isDuplicateErrorText(text)) return;
			hideOrderDuplicateBanner(el);
			if (!found) found = text;
		});
		if (found) {
			processOrderDuplicateError(found);
		}
	}

	function patchOrderAjaxComponent() {
		function tryPatch() {
			var Comp = window.BX && BX.Sale && BX.Sale.OrderAjaxComponent;
			if (!Comp || Comp.__primePhoneDupPatched) {
				return !!Comp;
			}
			if (typeof Comp.showError !== 'function') {
				return false;
			}
			Comp.__primePhoneDupPatched = true;
			var origShowError = Comp.showError;
			Comp.showError = function (node, msg, border) {
				var raw = (window.BX && BX.type && BX.type.isArray(msg)) ? msg.join('<br>') : String(msg || '');
				var text = htmlToPlain(raw);
				if (isDuplicateErrorText(text)) {
					this.hasErrorSection = this.hasErrorSection || {};
					if (node && node.id) {
						this.hasErrorSection[node.id] = true;
					}
					processOrderDuplicateError(text);
					hideAllOrderDuplicateBanners(document);
					return;
				}
				return origShowError.apply(this, arguments);
			};
			return true;
		}

		if (tryPatch()) return;
		var n = 0;
		var timer = setInterval(function () {
			if (tryPatch() || ++n > 60) {
				clearInterval(timer);
			}
		}, 200);
	}

	function initOrderDuplicateModal() {
		if (!document.getElementById('bx-soa-order-form')) return;

		patchOrderAjaxComponent();
		scanOrderDuplicateAlerts(document);

		document.addEventListener('click', function (e) {
			var t = e.target;
			if (!t) return;
			var btn = t.closest ? t.closest('[data-save-button], #bx-soa-orderSave a, #bx-soa-orderSave .btn') : null;
			if (!btn) return;
			orderDupArmed = true;
			orderDupOpening = false;
		}, true);

		if (window.MutationObserver) {
			var obs = new MutationObserver(function () {
				scanOrderDuplicateAlerts(document);
			});
			var orderRoot = document.getElementById('bx-soa-order') || document.body;
			obs.observe(orderRoot, {
				childList: true,
				subtree: true,
				characterData: true,
				attributes: true,
				attributeFilter: ['style', 'class']
			});
		}

		if (window.BX && BX.addCustomEvent) {
			BX.addCustomEvent('onAjaxSuccess', function () {
				patchOrderAjaxComponent();
				setTimeout(function () { scanOrderDuplicateAlerts(document); }, 30);
				setTimeout(function () { scanOrderDuplicateAlerts(document); }, 250);
			});
		}

		setInterval(function () {
			if (!document.getElementById('bx-soa-order-form')) return;
			hideAllOrderDuplicateBanners(document);
			patchOrderAjaxComponent();
		}, 500);
	}

	function onReady() {
		// Сначала заказ: initRegister на этой странице не нужен и не должен ломать модалку
		try { initOrderDuplicateModal(); } catch (e) {}
		try {
			document.querySelectorAll('.personal_enter .auth, .bx-authform .auth').forEach(function (root) {
				initTabs(root);
				if (phoneOn) {
					bindPhoneForm(root);
				}
			});
		} catch (e) {}
		try { initAuthSwitchLinks(); } catch (e) {}
		try { initRegister(); } catch (e) {}
		if (phoneOn) {
			try { initProfile(); } catch (e) {}
			setTimeout(function () {
				try { initPhonePrompt(); } catch (e) {}
			}, 0);
		}
	}

	window.primePhoneauthMountProfile = function (root) {
		var scope = root && root.querySelector ? root : document;
		var slot = scope.querySelector('[data-prime-phone-confirm="1"]');
		if (slot) {
			mountPhoneConfirm(slot);
		}
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', onReady);
	} else {
		onReady();
	}

	if (window.BX && BX.addCustomEvent) {
		BX.addCustomEvent('onAjaxSuccess', function () {
			initAuthSwitchLinks();
			initRegister();
		});
	}
	setInterval(function () { initRegister(); }, 1500);

	window.primePhoneauthInitRegister = initRegister;
})();
