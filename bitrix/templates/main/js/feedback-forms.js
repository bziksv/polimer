/**
 * Клиентская валидация форм с согласием на обработку ПД.
 */
(function ($) {
	'use strict';

	var FORM_SEL = '.js-polimer-consent-form, .js-polimer-feedback-form, form.ym-goal-write-email, form.ym-goal-calc, #reviews form, #mailus form';

	function clearErrors($form) {
		$form.find('.is-invalid').removeClass('is-invalid');
		$form.find('.polimer-field-error').remove();
		$form.find('.polimer-feedback-errors').remove();
	}

	function markInvalid($target, message) {
		var $wrap = $target.closest('.line, .rule, .polimer-consent-rule, .mf-captcha, .agent, .inp');
		if (!$wrap.length) {
			$wrap = $target.parent();
		}

		$wrap.addClass('is-invalid');
		if (message && !$wrap.find('.polimer-field-error').length) {
			$wrap.append($('<span class="polimer-field-error"></span>').text(message));
		}
	}

	function fieldLabel($input) {
		var $label = $input.closest('.line').find('.label, > span').first();
		if ($label.length) {
			return $.trim($label.text());
		}
		return $input.attr('placeholder') || 'поле';
	}

	function validateConsent($form) {
		var $rule = $form.find('.polimer-consent-rule input[type="checkbox"]').first();
		if (!$rule.length) {
			$rule = $form.find('.rule input[type="checkbox"], .agent input[type="checkbox"], .inp input[type="checkbox"]')
				.not('[name="WORK_POSITION"]')
				.first();
		}
		if (!$rule.length) {
			return true;
		}
		if (!$rule.is(':checked')) {
			markInvalid($rule, 'Отметьте согласие на обработку персональных данных');
			return false;
		}
		return true;
	}

	function validateForm($form) {
		clearErrors($form);
		var ok = true;

		$form.find('input[type="text"], input[type="email"], input[type="tel"], select, textarea').each(function () {
			var $input = $(this);
			if ($input.is('[type=hidden]') || $input.closest('.polimer-consent-rule, .rule').length) {
				return;
			}

			var required = $input.is('[required]') || $input.prop('required');
			var val = $.trim($input.val() || '');

			if (required && val === '') {
				markInvalid($input, 'Заполните: ' + fieldLabel($input));
				ok = false;
				return;
			}

			if ($input.is('[type=email], [name=EMAIL], [name=email]') && val !== '') {
				if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
					markInvalid($input, 'Укажите корректный e-mail');
					ok = false;
				}
			}

			if ($input.is('.phone, .ru_phone_check, .phone_check, [name=PHONE], [name=phone], [autocomplete=tel]') && val !== '') {
				var phoneOk = window.PolimerRuPhone && window.PolimerRuPhone.isValidRuPhone
					? window.PolimerRuPhone.isValidRuPhone(val)
					: /^\+7-\d{3}-\d{3}-\d{2}-\d{2}$/.test(val);
				if (!phoneOk) {
					markInvalid($input, 'Укажите номер в формате +7-___-___-__-__');
					ok = false;
				}
			}

			if ($input.is('[name=FIO], .name, .fio') && val !== '') {
				if (!/[А-Яа-яЁё]/.test(val)) {
					markInvalid($input, 'ФИО — только кириллица');
					ok = false;
				}
			}
		});

		if (!validateConsent($form)) {
			ok = false;
		}

		if (!ok) {
			var $firstInvalid = $form.find('.is-invalid').first();
			if ($firstInvalid.length) {
				$('html, body').animate({ scrollTop: $firstInvalid.offset().top - 80 }, 200);
			}
		}

		return ok;
	}

	$(document).on('submit', FORM_SEL, function (e) {
		if (!validateForm($(this))) {
			e.preventDefault();
			e.stopImmediatePropagation();
			return false;
		}
	});

	$(document).on('change input', FORM_SEL + ' input, ' + FORM_SEL + ' select, ' + FORM_SEL + ' textarea', function () {
		var $el = $(this);
		var $wrap = $el.closest('.line, .rule, .polimer-consent-rule, .mf-captcha, .agent, .inp');
		$wrap.removeClass('is-invalid');
		$wrap.find('.polimer-field-error').remove();
	});

	function validateOrderConsent() {
		var $checkbox = $('#bx-soa-orderSave .polimer-consent-checkbox');
		if (!$checkbox.length) {
			return true;
		}
		if ($checkbox.is(':checked')) {
			$checkbox.closest('.polimer-consent-rule').removeClass('is-invalid').find('.polimer-field-error').remove();
			return true;
		}
		markInvalid($checkbox, 'Отметьте согласие на обработку персональных данных');
		return false;
	}

	document.addEventListener('click', function (e) {
		var btn = e.target && e.target.closest ? e.target.closest('#bx-soa-orderSave [data-save-button="true"]') : null;
		if (!btn) {
			return;
		}
		if (!validateOrderConsent()) {
			e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();
		}
	}, true);
})(jQuery);
