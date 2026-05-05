(function() {
	function removeMessage(el) {
		el.closest('.adm-informer-item').remove();
	}

	function decrementCounter() {
		const counter = document.getElementById('adm-header-notif-counter');
		const count = parseInt(counter.textContent.trim());

		counter.textContent = Math.max(0, count - 1);
	}

	function submitRead(data) {
		BX.ajax({
			'method': 'POST',
			'dataType': 'json',
			'url': '/bitrix/tools/avito.export/chat/ajax.php',
			'async': true,
			'data': data
		});
	}

	function markInformer() {
		const informer = document.getElementById('adm-header-notif-block');

		if (informer === null) {
			waitInformer();
		} else {
			informer.classList.add('has-avito-messages');
		}
	}

	function unmarkInformer() {
		const avitoMessages = document.querySelectorAll('.adm-informer-item-avito-white');
		const informer = document.getElementById('adm-header-notif-block');

		if (avitoMessages.length > 0) { return; }

		informer.classList.remove('has-avito-messages');
	}

	markInformer();

	function waitInformer() {
		setTimeout(markInformer, 1);
	}

	window.avitoHideInformerMessage = function (el, data) {
		removeMessage(el);
		decrementCounter();
		submitRead(data);
		unmarkInformer();
	}
})();