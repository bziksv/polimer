function PolimerTitleSearch(arParams)
{
	var _this = this;

	this.arParams = {
		'AJAX_PAGE': arParams.AJAX_PAGE,
		'CONTAINER_ID': arParams.CONTAINER_ID,
		'INPUT_ID': arParams.INPUT_ID,
		'MIN_QUERY_LEN': parseInt(arParams.MIN_QUERY_LEN, 10)
	};

	if (arParams.MIN_QUERY_LEN <= 0)
		arParams.MIN_QUERY_LEN = 1;

	this.cache = [];
	this.cache_key = null;
	this.startText = '';
	this.running = false;
	this.runningCall = false;
	this.currentRow = -1;
	this.RESULT = null;
	this.CONTAINER = null;
	this.INPUT = null;
	this.OVERLAY = null;
	this.items = [];
	this.inputTimer = null;
	this.layoutTimer = null;
	this.mouseOverResult = false;
	this.lastPosition = null;
	this.activeSectionId = null;
	this.sectionFilters = {};

	this.getItems = function()
	{
		if (!_this.RESULT)
			return [];

		return Array.prototype.slice.call(_this.RESULT.querySelectorAll('.polimer-search-item')).filter(function(item) {
			return item.style.display !== 'none';
		});
	};

	this.getLayoutMetrics = function()
	{
		var headerBottom = document.querySelector('header .header__bottom');
		var pageContainer = document.querySelector('header .container') || document.querySelector('.container');
		var anchor = (headerBottom && headerBottom.classList.contains('fixed')) ? headerBottom : _this.CONTAINER;
		var anchorRect = anchor.getBoundingClientRect();
		var containerRect = pageContainer ? pageContainer.getBoundingClientRect() : anchorRect;
		var viewportWidth = document.documentElement.clientWidth || window.innerWidth;

		var left = containerRect.left;
		var width = containerRect.width;

		if (viewportWidth <= 1319)
		{
			left = 0;
			width = viewportWidth;
		}

		return {
			top: Math.round(anchorRect.bottom + 4),
			left: Math.round(left),
			width: Math.round(width)
		};
	};

	this.applyPosition = function(metrics)
	{
		if (!_this.lastPosition
			|| _this.lastPosition.top !== metrics.top
			|| _this.lastPosition.left !== metrics.left
			|| _this.lastPosition.width !== metrics.width)
		{
			_this.RESULT.style.position = 'fixed';
			_this.RESULT.style.top = metrics.top + 'px';
			_this.RESULT.style.left = metrics.left + 'px';
			_this.RESULT.style.width = metrics.width + 'px';
			_this.RESULT.style.maxWidth = metrics.width + 'px';
			_this.lastPosition = metrics;
		}
	};

	this.adjustResultNode = function()
	{
		if (!BX.type.isElementNode(_this.RESULT) || !BX.type.isElementNode(_this.CONTAINER))
			return;

		_this.applyPosition(_this.getLayoutMetrics());
		_this.syncOverlayPosition();
	};

	this.syncOverlayPosition = function()
	{
		if (!_this.OVERLAY || !_this.OVERLAY.classList.contains('is-visible'))
			return;

		var metrics = _this.getLayoutMetrics();
		_this.OVERLAY.style.top = metrics.top + 'px';
	};

	this.scheduleLayout = function()
	{
		if (_this.layoutTimer)
			return;

		_this.layoutTimer = requestAnimationFrame(function(){
			_this.layoutTimer = null;
			_this.adjustResultNode();
		});
	};

	this.toggleOverlay = function(show)
	{
		if (!_this.OVERLAY)
			return;

		if (show)
		{
			_this.syncOverlayPosition();
			BX.addClass(_this.OVERLAY, 'is-visible');
			BX.addClass(document.body, 'polimer-search-open');
			BX.addClass(document.documentElement, 'polimer-search-open');
		}
		else
		{
			BX.removeClass(_this.OVERLAY, 'is-visible');
			BX.removeClass(document.body, 'polimer-search-open');
			BX.removeClass(document.documentElement, 'polimer-search-open');
			_this.OVERLAY.style.top = '';
		}
	};

	this.ShowResult = function(result, restoreSectionId)
	{
		if (BX.type.isString(result))
			_this.RESULT.innerHTML = result;

		var hasContent = !!_this.RESULT.querySelector('.polimer-search-dropdown');
		_this.RESULT.style.display = hasContent ? 'block' : 'none';
		_this.toggleOverlay(hasContent);

		if (!hasContent)
		{
			_this.currentRow = -1;
			_this.items = [];
			_this.activeSectionId = null;
			return;
		}

		_this.RESULT.className = 'title-search-result title-search-result--polimer';
		_this.scheduleLayout();
		_this.currentRow = -1;
		_this.UnSelectAll();

		var sectionToApply = restoreSectionId || null;
		if (sectionToApply === null && _this.cache_key && _this.sectionFilters[_this.cache_key])
			sectionToApply = _this.sectionFilters[_this.cache_key];

		_this.applySectionFilter(sectionToApply, true);
	};

	this.escapeHtml = function(text)
	{
		return String(text || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	};

	this.getSectionFilterButtons = function()
	{
		if (!_this.RESULT)
			return [];

		return Array.prototype.slice.call(_this.RESULT.querySelectorAll('.polimer-search-dropdown__section-filter'));
	};

	this.applySectionFilter = function(sectionId, silent)
	{
		var dropdown = _this.RESULT.querySelector('.polimer-search-dropdown');
		if (!dropdown)
			return;

		sectionId = sectionId ? String(sectionId) : null;
		_this.activeSectionId = sectionId;

		if (_this.cache_key)
		{
			if (sectionId)
				_this.sectionFilters[_this.cache_key] = sectionId;
			else
				delete _this.sectionFilters[_this.cache_key];
		}

		var filterButtons = _this.getSectionFilterButtons();
		var activeButton = null;
		for (var i = 0; i < filterButtons.length; i++)
		{
			var button = filterButtons[i];
			var isActive = sectionId && button.getAttribute('data-section-id') === sectionId;
			if (isActive)
				BX.addClass(button, 'is-active');
			else
				BX.removeClass(button, 'is-active');
			if (isActive)
				activeButton = button;
		}

		var productItems = dropdown.querySelectorAll('.polimer-search-item--product');
		var visibleCount = 0;
		for (var j = 0; j < productItems.length; j++)
		{
			var item = productItems[j];
			var matches = !sectionId || item.getAttribute('data-section-id') === sectionId;
			item.style.display = matches ? '' : 'none';
			if (matches)
				visibleCount++;
		}

		var filterBar = dropdown.querySelector('.polimer-search-dropdown__filter-bar');
		var filterLabel = dropdown.querySelector('.polimer-search-dropdown__filter-chip-label');
		var emptyState = dropdown.querySelector('.polimer-search-dropdown__empty');
		var productsList = dropdown.querySelector('.polimer-search-dropdown__list--products');
		var productsCount = dropdown.querySelector('.polimer-search-dropdown__products-count');

		if (filterBar && filterLabel)
		{
			if (sectionId && activeButton)
			{
				filterBar.hidden = false;
				filterLabel.textContent = activeButton.getAttribute('data-section-name') || '';
			}
			else
			{
				filterBar.hidden = true;
				filterLabel.textContent = '';
			}
		}

		if (emptyState && productsList)
		{
			var showEmpty = sectionId && visibleCount === 0;
			emptyState.hidden = !showEmpty;
			productsList.style.display = showEmpty ? 'none' : '';
		}

		if (productsCount)
		{
			var total = parseInt(productsCount.getAttribute('data-total'), 10) || productItems.length;
			productsCount.textContent = sectionId ? visibleCount : total;
		}

		var footerLink = dropdown.querySelector('.polimer-search-dropdown__all');
		if (footerLink)
		{
			var allUrl = footerLink.getAttribute('data-url-all') || footerLink.getAttribute('href');
			var allLabel = footerLink.getAttribute('data-label-all') || 'Все результаты';
			var query = dropdown.getAttribute('data-query') || _this.INPUT.value || '';

			if (sectionId && activeButton)
			{
				var sectionUrl = activeButton.getAttribute('data-section-url') || allUrl;
				var sectionName = activeButton.getAttribute('data-section-name') || '';
				footerLink.href = sectionUrl;
				footerLink.innerHTML = 'Все в «' + _this.escapeHtml(sectionName) + '»'
					+ (query ? ' по запросу «' + _this.escapeHtml(query) + '»' : '');
			}
			else
			{
				footerLink.href = allUrl;
				footerLink.innerHTML = '<span class="polimer-search-dropdown__all-text">' + _this.escapeHtml(allLabel) + '</span>'
					+ (query ? ' по запросу «' + _this.escapeHtml(query) + '»' : '');
			}
		}

		if (!silent)
		{
			_this.currentRow = -1;
			_this.UnSelectAll();
			_this.scheduleLayout();
		}
	};

	this.toggleSectionFilter = function(sectionId)
	{
		sectionId = sectionId ? String(sectionId) : null;
		if (_this.activeSectionId === sectionId)
			_this.applySectionFilter(null);
		else
			_this.applySectionFilter(sectionId);
	};

	this.UnSelectAll = function()
	{
		var items = _this.getItems();
		for (var i = 0; i < items.length; i++)
			BX.removeClass(items[i], 'title-search-selected');
	};

	this.onKeyPress = function(keyCode)
	{
		_this.items = _this.getItems();
		if (!_this.items.length)
			return false;

		var cnt = _this.items.length;
		var i;

		switch (keyCode)
		{
			case 27:
				_this.RESULT.style.display = 'none';
				_this.toggleOverlay(false);
				_this.currentRow = -1;
				_this.UnSelectAll();
				return true;

			case 40:
				if (_this.RESULT.style.display === 'none')
					_this.RESULT.style.display = 'block';

				var first = 0;
				for (i = 0; i < cnt; i++)
				{
					if (_this.currentRow < i)
					{
						_this.currentRow = i;
						break;
					}
				}
				if (i === cnt)
					_this.currentRow = first;

				_this.UnSelectAll();
				BX.addClass(_this.items[_this.currentRow], 'title-search-selected');
				return true;

			case 38:
				if (_this.RESULT.style.display === 'none')
					_this.RESULT.style.display = 'block';

				var last = cnt - 1;
				for (i = cnt - 1; i >= 0; i--)
				{
					if (_this.currentRow > i)
					{
						_this.currentRow = i;
						break;
					}
				}
				if (i < 0)
					_this.currentRow = last;

				_this.UnSelectAll();
				BX.addClass(_this.items[_this.currentRow], 'title-search-selected');
				return true;

			case 13:
				if (_this.RESULT.style.display === 'block' && _this.currentRow >= 0)
				{
					var item = _this.items[_this.currentRow];
					var filterBtn = item.querySelector('.polimer-search-dropdown__section-filter');
					if (filterBtn)
					{
						_this.toggleSectionFilter(filterBtn.getAttribute('data-section-id'));
						return true;
					}

					var link = item.querySelector('.polimer-search-dropdown__product-link')
						|| item.querySelector('.polimer-search-dropdown__section-go')
						|| item.querySelector('a');
					if (link && link.href)
					{
						window.location = link.href;
						return true;
					}
				}
				return false;
		}

		return false;
	};

	this.onChange = function(callback)
	{
		if (_this.running)
		{
			_this.runningCall = true;
			return;
		}
		_this.running = true;

		if (_this.INPUT.value != _this.oldValue && _this.INPUT.value != _this.startText)
		{
			_this.oldValue = _this.INPUT.value;
			_this.activeSectionId = null;

			if (_this.INPUT.value.length >= _this.arParams.MIN_QUERY_LEN)
			{
				_this.cache_key = _this.arParams.INPUT_ID + '|' + _this.INPUT.value;

				if (_this.cache[_this.cache_key] == null)
				{
					BX.ajax.post(
						_this.arParams.AJAX_PAGE,
						{
							'ajax_call': 'y',
							'INPUT_ID': _this.arParams.INPUT_ID,
							'q': _this.INPUT.value,
							'l': _this.arParams.MIN_QUERY_LEN
						},
						function(result)
						{
							_this.cache[_this.cache_key] = result;
							_this.ShowResult(result);
							if (callback)
								callback();
							_this.running = false;
							if (_this.runningCall)
							{
								_this.runningCall = false;
								_this.onChange();
							}
						}
					);
					return;
				}

				_this.ShowResult(_this.cache[_this.cache_key]);
			}
			else
			{
				_this.RESULT.style.display = 'none';
				_this.toggleOverlay(false);
				_this.currentRow = -1;
				_this.UnSelectAll();
			}
		}

		if (callback)
			callback();
		_this.running = false;
	};

	this.onScroll = function()
	{
		if (BX.type.isElementNode(_this.RESULT)
			&& _this.RESULT.style.display !== 'none'
			&& _this.RESULT.innerHTML !== '')
		{
			_this.scheduleLayout();
		}
	};

	this.onFocusLost = function()
	{
		setTimeout(function(){
			if (_this.mouseOverResult || _this.RESULT.contains(document.activeElement))
				return;

			_this.RESULT.style.display = 'none';
			_this.toggleOverlay(false);
			_this.currentRow = -1;
			_this.UnSelectAll();
		}, 200);
	};

	this.onFocusGain = function()
	{
		if (_this.RESULT.innerHTML.length)
			_this.ShowResult();
	};

	this.onInput = function()
	{
		clearTimeout(_this.inputTimer);
		_this.inputTimer = setTimeout(function(){ _this.onChange(); }, 300);
	};

	this.onKeyDown = function(e)
	{
		if (!e)
			e = window.event;

		if (_this.RESULT.style.display === 'block' && _this.onKeyPress(e.keyCode))
			return BX.PreventDefault(e);
	};

	this._onContainerLayoutChange = function()
	{
		if (BX.type.isElementNode(_this.RESULT)
			&& _this.RESULT.style.display !== 'none'
			&& _this.RESULT.innerHTML !== '')
		{
			_this.scheduleLayout();
		}
	};

	this.openOrderPopup = function(button)
	{
		if (typeof jQuery === 'undefined')
			return;

		var popupId = button.getAttribute('data-id') || 'order-product';
		var productUrl = button.getAttribute('data-product-url') || window.location.pathname;
		var productName = button.getAttribute('data-product-name') || '';

		if (jQuery('#' + popupId).length)
		{
			jQuery('.popup#' + popupId).velocity('fadeIn', 'fast');
			return;
		}

		jQuery.get('/ajax/forms.php', {
			FORM_ID: popupId,
			URL: productUrl,
			TITLE: productName
		}).done(function(data) {
			jQuery('body').prepend(data);

			if (jQuery.fn.mask)
				jQuery('.phone').mask('+7 (999) 999-99-99');

			try
			{
				grecaptcha.render(jQuery('#' + popupId + ' .g-recaptcha').get(0), {
					sitekey: '6LfZ8kgUAAAAAJWtIx1_4_pUvd1Xk_VfdMhpqT4P'
				});
			}
			catch (error) {}

			jQuery('.popup#' + popupId).velocity('fadeIn', 'fast');
		});
	};

	this.Init = function()
	{
		this.CONTAINER = document.getElementById(this.arParams.CONTAINER_ID);
		if (!this.CONTAINER || !document.getElementById(this.arParams.INPUT_ID))
			return;

		BX.addCustomEvent(this.CONTAINER, 'OnNodeLayoutChange', this._onContainerLayoutChange);

		this.RESULT = document.body.appendChild(document.createElement('DIV'));
		this.RESULT.className = 'title-search-result title-search-result--polimer';

		this.OVERLAY = document.body.appendChild(document.createElement('DIV'));
		this.OVERLAY.className = 'polimer-search-overlay';
		BX.bind(this.OVERLAY, 'click', function(){
			_this.RESULT.style.display = 'none';
			_this.toggleOverlay(false);
			_this.currentRow = -1;
			_this.UnSelectAll();
		});

		this.INPUT = document.getElementById(this.arParams.INPUT_ID);
		this.startText = this.oldValue = this.INPUT.value;

		BX.bind(this.INPUT, 'focus', function(){ _this.onFocusGain(); });
		BX.bind(this.INPUT, 'blur', function(){ _this.onFocusLost(); });
		this.INPUT.onkeydown = this.onKeyDown;
		BX.bind(this.INPUT, 'input', function(){ _this.onInput(); });

		BX.bind(this.RESULT, 'mouseenter', function(){ _this.mouseOverResult = true; });
		BX.bind(this.RESULT, 'mouseleave', function(){ _this.mouseOverResult = false; });

		BX.bind(this.RESULT, 'mousedown', function(e){
			if (e.target.closest('.polimer-search-dropdown__actions')
				|| e.target.closest('.polimer-search-dropdown__section-go'))
				return;
			e.preventDefault();
		});

		BX.bind(this.RESULT, 'click', function(e){
			var filterBtn = e.target.closest('.polimer-search-dropdown__section-filter');
			if (filterBtn)
			{
				e.preventDefault();
				_this.toggleSectionFilter(filterBtn.getAttribute('data-section-id'));
				return;
			}

			var clearBtn = e.target.closest('.polimer-search-dropdown__filter-chip');
			if (clearBtn)
			{
				e.preventDefault();
				_this.applySectionFilter(null);
				return;
			}

			var orderBtn = e.target.closest('.polimer-search-dropdown__action--order');
			if (orderBtn)
			{
				e.preventDefault();
				e.stopPropagation();
				_this.openOrderPopup(orderBtn);
				return;
			}

			var compareBtn = e.target.closest('.polimer-search-dropdown__action--compare[data-product-id]');
			if (!compareBtn || compareBtn.tagName !== 'BUTTON')
				return;

			e.preventDefault();
			e.stopPropagation();

			var productId = compareBtn.getAttribute('data-product-id');
			if (!productId || typeof jQuery === 'undefined')
				return;

			jQuery.get('/catalog/compare/', { action: 'ADD_TO_COMPARE_LIST', id: productId }, function(){
				if (typeof alertify !== 'undefined')
					alertify.success('Товар успешно добавлен в сравнение.');

				var link = document.createElement('a');
				link.href = '/catalog/compare/';
				link.className = 'polimer-search-dropdown__action polimer-search-dropdown__action--compare is-active';
				link.title = 'Перейти в сравнение';
				link.innerHTML = '<i class="fa fa-bar-chart" aria-hidden="true"></i>';
				compareBtn.replaceWith(link);
			});
		});

		BX.bind(window, 'scroll', BX.throttle(this.onScroll, 150, this));
		BX.bind(window, 'resize', BX.throttle(this.onScroll, 150, this));
	};

	BX.ready(function(){ _this.Init(); });
}

BX.ready(function(){
	var container = document.querySelector('[data-polimer-search="Y"]');
	if (!container)
		return;

	new PolimerTitleSearch({
		'AJAX_PAGE': container.getAttribute('data-ajax-page'),
		'CONTAINER_ID': container.id,
		'INPUT_ID': container.getAttribute('data-input-id'),
		'MIN_QUERY_LEN': parseInt(container.getAttribute('data-min-query-len'), 10) || 2
	});
});
