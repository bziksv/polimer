/**
 * Маска и валидация телефонов РФ: +7-___-___-__-__
 */
(function ($) {
	'use strict';
	if (!$ || !$.fn) {
		return;
	}

	var MASK = '+7-999-999-99-99';
	var PLACEHOLDER = '_';
	var PLACEHOLDER_TEXT = '+7-___-___-__-__';
	var SELECTOR = [
		'input.ru_phone_check',
		'input.phone_check',
		'input.phone',
		'input.phone_number',
		'input.prime-phoneauth-specify',
		'input[name="PERSONAL_PHONE"]',
		'input[name="USER_PERSONAL_PHONE"]',
		'input[name="REGISTER[PERSONAL_PHONE]"]',
		'input[name="PHONE"]',
		'input[name="phone"]',
		'input[autocomplete="tel"]',
		'input[type="tel"]'
	].join(', ');

	function normalizeRuPhone(phone) {
		var digits = String(phone || '').replace(/\D/g, '');
		if (digits.length === 11 && (digits.charAt(0) === '8' || digits.charAt(0) === '7')) {
			digits = digits.slice(1);
		}
		return digits.length === 10 ? digits : '';
	}

	function isValidRuPhone(phone) {
		var digits = normalizeRuPhone(phone);
		return digits !== '' && /^[3-9]\d{9}$/.test(digits);
	}

	function formatRuPhone(phone) {
		var digits = normalizeRuPhone(phone);
		if (!digits) {
			return '';
		}
		return '+7-' + digits.slice(0, 3) + '-' + digits.slice(3, 6) + '-' + digits.slice(6, 8) + '-' + digits.slice(8, 10);
	}

	function applyMask($input) {
		if (!$input || !$input.length || $input.data('ruPhoneMask')) {
			return;
		}
		if ($input.is('[type=hidden], [readonly], [disabled]')) {
			return;
		}

		var val = $.trim($input.val());
		if (val) {
			var formatted = formatRuPhone(val);
			if (formatted) {
				$input.val(formatted);
			}
		}

		if ($.fn.inputmask) {
			$input.inputmask(MASK, { showMaskOnHover: false, placeholder: PLACEHOLDER });
		} else if ($.fn.mask) {
			try {
				$input.unmask();
			} catch (e) {
				// ignore
			}
			$input.mask(MASK, { placeholder: PLACEHOLDER, autoclear: false });
		}

		$input.attr('placeholder', PLACEHOLDER_TEXT);
		if (!$input.attr('autocomplete')) {
			$input.attr('autocomplete', 'tel');
		}
		if (!$input.attr('inputmode')) {
			$input.attr('inputmode', 'tel');
		}
		if (!$input.hasClass('ru_phone_check')) {
			$input.addClass('ru_phone_check phone_check');
		}
		$input.data('ruPhoneMask', true);
	}

	function initRuPhoneFields(root) {
		$(root || document).find(SELECTOR).filter('input').each(function () {
			applyMask($(this));
		});
	}

	function validateField($input, showAlert) {
		var value = $.trim($input.val());
		if (!value) {
			if ($input.is('.req, [required]')) {
				$input.addClass('err is-invalid');
				if (showAlert && window.alertify) {
					alertify.error('Укажите номер телефона');
				}
				return false;
			}
			$input.removeClass('err is-invalid');
			return true;
		}
		if (!isValidRuPhone(value)) {
			$input.addClass('err is-invalid');
			if (showAlert && window.alertify) {
				alertify.error('Введите корректный номер телефона РФ');
			}
			return false;
		}
		$input.removeClass('err is-invalid');
		return true;
	}

	window.PolimerRuPhone = {
		MASK: MASK,
		normalizeRuPhone: normalizeRuPhone,
		isValidRuPhone: isValidRuPhone,
		formatRuPhone: formatRuPhone,
		initRuPhoneFields: initRuPhoneFields,
		validateField: validateField
	};

	$(function () {
		initRuPhoneFields();
	});

	if (typeof BX !== 'undefined' && BX.addCustomEvent) {
		BX.addCustomEvent('onAjaxSuccess', function () {
			initRuPhoneFields();
		});
		BX.addCustomEvent('onFrameDataReceived', function () {
			initRuPhoneFields();
		});
	}

	if (typeof MutationObserver !== 'undefined') {
		var timer;
		var observer = new MutationObserver(function () {
			clearTimeout(timer);
			timer = setTimeout(function () {
				initRuPhoneFields();
			}, 80);
		});
		$(function () {
			if (document.body) {
				observer.observe(document.body, { childList: true, subtree: true });
			}
		});
	}

	$(document).on('blur', 'input.ru_phone_check', function () {
		validateField($(this), false);
	});
})(window.jQuery);
