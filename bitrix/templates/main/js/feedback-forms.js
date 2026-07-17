/**
 * Клиентская валидация попапов:
 * #oneclick, #specialist, #order-product
 */
(function ($) {
	'use strict';

	var FORM_SEL = '#oneclick form, #specialist form, #order-product form';

	function clearErrors($form) {
		$form.find('.is-invalid').removeClass('is-invalid');
		$form.find('.polimer-field-error').remove();
		$form.find('.polimer-feedback-errors').remove();
	}

	function markInvalid($target, message) {
		var $wrap = $target.closest('.line, .rule, .mf-captcha');
		if (!$wrap.length)
			$wrap = $target.parent();

		$wrap.addClass('is-invalid');
		if (message && !$wrap.find('.polimer-field-error').length)
			$wrap.append($('<span class="polimer-field-error"></span>').text(message));
	}

	function fieldLabel($input) {
		var $label = $input.closest('.line').find('.label').first();
		if ($label.length)
			return $.trim($label.text());
		return $input.attr('placeholder') || 'поле';
	}

	function validateForm($form) {
		clearErrors($form);
		var ok = true;

		$form.find('input[type="text"], input[type="email"], input[type="tel"], select').each(function () {
			var $input = $(this);
			if ($input.is('[type=hidden]') || $input.closest('.rule').length)
				return;

			var required = $input.is('[required]') || $input.prop('required');
			var val = $.trim($input.val() || '');

			if (required && val === '') {
				markInvalid($input, 'Заполните: ' + fieldLabel($input));
				ok = false;
				return;
			}

			if ($input.is('[type=email], [name=EMAIL]') && val !== '') {
				if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
					markInvalid($input, 'Укажите корректный e-mail');
					ok = false;
				}
			}

			if ($input.is('[name=FIO], .name') && val !== '') {
				if (!/[А-Яа-яЁё]/.test(val)) {
					markInvalid($input, 'ФИО — только кириллица');
					ok = false;
				}
			}
		});

		var $rule = $form.find('.rule input[type="checkbox"]').first();
		if ($rule.length && !$rule.is(':checked')) {
			markInvalid($rule, 'Отметьте согласие на обработку персональных данных');
			ok = false;
		}

		if ($form.find('.g-recaptcha').length) {
			var token = '';
			var $resp = $form.find('[name="g-recaptcha-response"]');
			if ($resp.length)
				token = $.trim($resp.val() || '');
			if (!token && typeof grecaptcha !== 'undefined' && grecaptcha.getResponse)
				token = $.trim(grecaptcha.getResponse() || '');

			if (!token) {
				markInvalid($form.find('.mf-captcha').first(), 'Подтвердите, что вы не робот');
				ok = false;
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

	$(document).on('change input', FORM_SEL + ' input, ' + FORM_SEL + ' select', function () {
		var $el = $(this);
		var $wrap = $el.closest('.line, .rule, .mf-captcha');
		$wrap.removeClass('is-invalid');
		$wrap.find('.polimer-field-error').remove();
	});
})(jQuery);
