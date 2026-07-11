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
	this.activeSectionIds = [];
	this.sectionFilters = {};
	this.searchRequestId = 0;

	this.isResultPanelVisible = function()
	{
		return !!(_this.RESULT && !_this.RESULT.classList.contains('is-dismissed'));
	};

	this.dismissResults = function()
	{
		_this.searchRequestId++;
		clearTimeout(_this.inputTimer);
		_this.inputTimer = null;
		_this.running = false;
		_this.runningCall = false;
		_this.cache_key = null;
		_this.currentRow = -1;
		_this.activeSectionIds = [];

		if (_this.RESULT)
		{
			BX.addClass(_this.RESULT, 'is-dismissed');
			_this.RESULT.style.setProperty('display', 'none', 'important');
			_this.RESULT.style.height = '';
			_this.RESULT.style.maxHeight = '';
			BX.removeClass(_this.RESULT, 'title-search-result--fill');
			_this.UnSelectAll();
		}

		_this.toggleOverlay(false);
	};

	this.canShowResults = function()
	{
		return !!(_this.INPUT && _this.INPUT.value.length >= _this.arParams.MIN_QUERY_LEN);
	};

	this.normalizeSectionIds = function(sectionIds)
	{
		if (!sectionIds)
			return [];

		if (Array.isArray(sectionIds))
			return sectionIds.map(String).filter(Boolean);

		return [String(sectionIds)];
	};

	this.isMobileLayout = function()
	{
		return window.matchMedia('(max-width: 1319px)').matches;
	};

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
		var header = document.querySelector('header');
		var hmobile = document.querySelector('header .hmobile');
		var pageContainer = document.querySelector('header .container') || document.querySelector('.container');
		var anchor = _this.CONTAINER;
		var isMobileHeaderSearch = hmobile
			&& (hmobile.classList.contains('hmobile--search-open') || hmobile.classList.contains('hmobile--search-inline'))
			&& window.matchMedia('(max-width: 1019px)').matches;

		if (headerBottom && headerBottom.classList.contains('fixed'))
			anchor = headerBottom;
		else if (isMobileHeaderSearch && header)
			anchor = header;
		else if (hmobile && window.matchMedia('(max-width: 1019px)').matches)
			anchor = isMobileHeaderSearch ? header || hmobile : _this.CONTAINER;

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

		var topGap = 4;
		if (hmobile && hmobile.classList.contains('hmobile--search-inline') && window.matchMedia('(max-width: 1019px)').matches)
			topGap = 4;
		else if (hmobile && hmobile.classList.contains('hmobile--search-open') && window.matchMedia('(max-width: 1019px)').matches)
			topGap = 0;

		var top = Math.round(anchorRect.bottom + topGap);
		if (isMobileHeaderSearch && top < 8)
			top = Math.max(top, Math.round(Math.min(anchorRect.height, hmobile ? hmobile.getBoundingClientRect().height : anchorRect.height)));

		return {
			top: top,
			left: Math.round(left),
			width: Math.round(width)
		};
	};

	this.applyPosition = function(metrics)
	{
		var viewportHeight = window.innerHeight || document.documentElement.clientHeight;
		var shouldFill = _this.isMobileLayout()
			&& _this.RESULT.querySelector('.polimer-search-dropdown');
		var fillHeight = shouldFill ? Math.max(220, viewportHeight - metrics.top - 4) : null;

		if (_this.lastPosition
			&& _this.lastPosition.top === metrics.top
			&& _this.lastPosition.left === metrics.left
			&& _this.lastPosition.width === metrics.width
			&& _this.lastPosition.height === fillHeight)
			return;

		_this.RESULT.style.position = 'fixed';
		_this.RESULT.style.top = metrics.top + 'px';
		_this.RESULT.style.left = metrics.left + 'px';
		_this.RESULT.style.width = metrics.width + 'px';
		_this.RESULT.style.maxWidth = metrics.width + 'px';

		if (shouldFill)
		{
			_this.RESULT.style.height = fillHeight + 'px';
			_this.RESULT.style.maxHeight = fillHeight + 'px';
			BX.addClass(_this.RESULT, 'title-search-result--fill');
		}
		else
		{
			_this.RESULT.style.height = '';
			_this.RESULT.style.maxHeight = '';
			BX.removeClass(_this.RESULT, 'title-search-result--fill');
		}

		_this.lastPosition = {
			top: metrics.top,
			left: metrics.left,
			width: metrics.width,
			height: fillHeight
		};
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
			polimerLockSearchHeader();
			_this.syncOverlayPosition();
			BX.addClass(_this.OVERLAY, 'is-visible');
			BX.addClass(document.body, 'polimer-search-open');
			BX.addClass(document.documentElement, 'polimer-search-open');
			_this.scheduleLayout();
		}
		else
		{
			BX.removeClass(_this.OVERLAY, 'is-visible');
			BX.removeClass(document.body, 'polimer-search-open');
			BX.removeClass(document.documentElement, 'polimer-search-open');
			_this.OVERLAY.style.top = '';
			polimerUnlockSearchHeader();
		}
	};

	this.ShowResult = function(result, restoreSectionId)
	{
		if (BX.type.isString(result))
			_this.RESULT.innerHTML = result;

		var hasContent = _this.canShowResults() && !!_this.RESULT.querySelector('.polimer-search-dropdown');

		if (hasContent)
		{
			BX.removeClass(_this.RESULT, 'is-dismissed');
			_this.RESULT.style.removeProperty('display');
		}
		else
		{
			_this.dismissResults();
			_this.currentRow = -1;
			_this.items = [];
			_this.activeSectionIds = [];
			return;
		}

		_this.toggleOverlay(true);

		_this.RESULT.className = 'title-search-result title-search-result--polimer';
		_this.scheduleLayout();
		_this.currentRow = -1;
		_this.UnSelectAll();

		var sectionsToApply = _this.normalizeSectionIds(restoreSectionId);
		if (!sectionsToApply.length && _this.cache_key && _this.sectionFilters[_this.cache_key])
			sectionsToApply = _this.normalizeSectionIds(_this.sectionFilters[_this.cache_key]);

		_this.applySectionFilter(sectionsToApply, true);
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

	this.renderFilterChips = function(dropdown, activeButtons)
	{
		var filterBar = dropdown.querySelector('.polimer-search-dropdown__filter-bar');
		if (!filterBar)
			return;

		if (!activeButtons.length)
		{
			filterBar.hidden = true;
			filterBar.innerHTML = '';
			return;
		}

		var html = '<div class="polimer-search-dropdown__filter-chips">';
		for (var i = 0; i < activeButtons.length; i++)
		{
			var button = activeButtons[i];
			var sectionId = button.getAttribute('data-section-id') || '';
			var sectionName = button.getAttribute('data-section-name') || '';
			html += '<button type="button" class="polimer-search-dropdown__filter-chip" data-section-id="'
				+ _this.escapeHtml(sectionId) + '">'
				+ '<span class="polimer-search-dropdown__filter-chip-label">'
				+ _this.escapeHtml(sectionName) + '</span>'
				+ '<span class="polimer-search-dropdown__filter-clear" title="Убрать">&times;</span>'
				+ '</button>';
		}
		html += '<button type="button" class="polimer-search-dropdown__filter-reset">Сбросить все</button>';
		html += '</div>';
		filterBar.hidden = false;
		filterBar.innerHTML = html;
	};

	this.applySectionFilter = function(sectionIds, silent)
	{
		var dropdown = _this.RESULT.querySelector('.polimer-search-dropdown');
		if (!dropdown)
			return;

		sectionIds = _this.normalizeSectionIds(sectionIds);
		_this.activeSectionIds = sectionIds.slice();

		if (_this.cache_key)
		{
			if (sectionIds.length)
				_this.sectionFilters[_this.cache_key] = sectionIds.slice();
			else
				delete _this.sectionFilters[_this.cache_key];
		}

		var filterButtons = _this.getSectionFilterButtons();
		var activeButtons = [];
		for (var i = 0; i < filterButtons.length; i++)
		{
			var button = filterButtons[i];
			var buttonSectionId = button.getAttribute('data-section-id');
			var isActive = sectionIds.indexOf(buttonSectionId) !== -1;
			if (isActive)
			{
				BX.addClass(button, 'is-active');
				activeButtons.push(button);
			}
			else
			{
				BX.removeClass(button, 'is-active');
			}
		}

		var sectionItems = dropdown.querySelectorAll('.polimer-search-item--section');
		for (var s = 0; s < sectionItems.length; s++)
		{
			var sectionItem = sectionItems[s];
			var itemSectionId = sectionItem.getAttribute('data-section-id');
			if (sectionIds.indexOf(itemSectionId) !== -1)
				BX.addClass(sectionItem, 'is-filter-active');
			else
				BX.removeClass(sectionItem, 'is-filter-active');
		}

		var productItems = dropdown.querySelectorAll('.polimer-search-item--product');
		var visibleCount = 0;
		for (var j = 0; j < productItems.length; j++)
		{
			var item = productItems[j];
			var matches = !sectionIds.length || sectionIds.indexOf(item.getAttribute('data-section-id')) !== -1;
			item.style.display = matches ? '' : 'none';
			if (matches)
				visibleCount++;
		}

		_this.renderFilterChips(dropdown, activeButtons);

		var emptyState = dropdown.querySelector('.polimer-search-dropdown__empty');
		var productsList = dropdown.querySelector('.polimer-search-dropdown__list--products');
		var productsCount = dropdown.querySelector('.polimer-search-dropdown__products-count');

		if (emptyState && productsList)
		{
			var showEmpty = sectionIds.length && visibleCount === 0;
			emptyState.hidden = !showEmpty;
			productsList.style.display = showEmpty ? 'none' : '';
		}

		if (productsCount)
		{
			var total = parseInt(productsCount.getAttribute('data-total'), 10) || productItems.length;
			if (sectionIds.length)
				productsCount.textContent = String(visibleCount);
			else
				productsCount.textContent = total > productItems.length
					? (productItems.length + ' из ' + total)
					: String(total);
		}

		var footerLink = dropdown.querySelector('.polimer-search-dropdown__all');
		if (footerLink)
		{
			var allUrl = footerLink.getAttribute('data-url-all') || footerLink.getAttribute('href');
			var allLabel = footerLink.getAttribute('data-label-all') || 'Все результаты';
			var query = dropdown.getAttribute('data-query') || _this.INPUT.value || '';

			if (activeButtons.length === 1)
			{
				var singleButton = activeButtons[0];
				var sectionUrl = singleButton.getAttribute('data-section-url') || allUrl;
				var sectionName = singleButton.getAttribute('data-section-name') || '';
				footerLink.href = sectionUrl;
				footerLink.innerHTML = 'Все в «' + _this.escapeHtml(sectionName) + '»'
					+ (query ? ' по запросу «' + _this.escapeHtml(query) + '»' : '');
			}
			else if (activeButtons.length > 1)
			{
				footerLink.href = allUrl;
				footerLink.innerHTML = 'Показать все ' + visibleCount + ' товаров'
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
		sectionId = String(sectionId);
		var sectionIds = _this.activeSectionIds.slice();
		var index = sectionIds.indexOf(sectionId);

		if (index !== -1)
			sectionIds.splice(index, 1);
		else
			sectionIds.push(sectionId);

		_this.applySectionFilter(sectionIds);
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
				_this.dismissResults();
				return true;

			case 40:
				if (!_this.isResultPanelVisible())
					_this.ShowResult(_this.RESULT.innerHTML);

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
				if (!_this.isResultPanelVisible())
					_this.ShowResult(_this.RESULT.innerHTML);

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
				if (_this.isResultPanelVisible() && _this.currentRow >= 0)
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

		if (_this.INPUT.value != _this.oldValue)
		{
			_this.oldValue = _this.INPUT.value;
			_this.activeSectionIds = [];

			if (_this.INPUT.value.length >= _this.arParams.MIN_QUERY_LEN)
			{
				_this.cache_key = _this.arParams.INPUT_ID + '|' + _this.INPUT.value;
				var requestId = ++_this.searchRequestId;

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
							if (requestId !== _this.searchRequestId)
								return;

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
				_this.dismissResults();
			}
		}

		if (callback)
			callback();
		_this.running = false;
	};

	this.onScroll = function()
	{
		if (BX.type.isElementNode(_this.RESULT)
			&& _this.isResultPanelVisible()
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

			_this.dismissResults();
		}, 200);
	};

	this.onFocusGain = function()
	{
		if (_this.canShowResults() && _this.RESULT.innerHTML.length)
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

		if (_this.isResultPanelVisible() && _this.onKeyPress(e.keyCode))
			return BX.PreventDefault(e);
	};

	this._onContainerLayoutChange = function()
	{
		if (BX.type.isElementNode(_this.RESULT)
			&& _this.isResultPanelVisible()
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
			_this.dismissResults();

			if (_this.arParams.CONTAINER_ID === 'title-search-mobile' && window.matchMedia('(max-width: 1019px)').matches)
				polimerCloseActiveSearch();
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

			var chipBtn = e.target.closest('.polimer-search-dropdown__filter-chip');
			if (chipBtn)
			{
				e.preventDefault();
				var chipSectionId = chipBtn.getAttribute('data-section-id');
				if (chipSectionId)
				{
					var nextSectionIds = _this.activeSectionIds.filter(function(id) {
						return id !== chipSectionId;
					});
					_this.applySectionFilter(nextSectionIds);
				}
				return;
			}

			var resetBtn = e.target.closest('.polimer-search-dropdown__filter-reset');
			if (resetBtn)
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

function polimerInitTitleSearchContainers()
{
	if (!window._polimerTitleSearchInstances)
		window._polimerTitleSearchInstances = {};

	document.querySelectorAll('[data-polimer-search="Y"]').forEach(function(container) {
		if (!container.id || window._polimerTitleSearchInstances[container.id])
			return;

		window._polimerTitleSearchInstances[container.id] = new PolimerTitleSearch({
			'AJAX_PAGE': container.getAttribute('data-ajax-page'),
			'CONTAINER_ID': container.id,
			'INPUT_ID': container.getAttribute('data-input-id'),
			'MIN_QUERY_LEN': parseInt(container.getAttribute('data-min-query-len'), 10) || 2
		});
	});
}

function polimerIsTabletInlineSearch()
{
	return window.matchMedia('(min-width: 768px) and (max-width: 1019px)').matches;
}

function polimerIsMobileSearchIconMode()
{
	return window.matchMedia('(min-width: 380px) and (max-width: 767px)').matches;
}

function polimerSyncMobileSearchMode()
{
	var panel = document.getElementById('hmobile-search-panel');
	var hmobile = document.querySelector('header .hmobile');
	var trigger = document.querySelector('.hmobile__search');
	var triggerIcon = trigger ? trigger.querySelector('.header__fa-icon') : null;

	if (!panel || !hmobile)
		return;

	if (polimerIsTabletInlineSearch())
	{
		panel.classList.add('is-inline');
		panel.classList.add('is-open');
		BX.addClass(hmobile, 'hmobile--search-inline');
		BX.addClass(hmobile, 'hmobile--search-open');

		if (trigger)
		{
			trigger.setAttribute('aria-expanded', 'true');
			trigger.setAttribute('aria-hidden', 'true');
		}

		if (triggerIcon)
		{
			triggerIcon.classList.remove('fa-times');
			triggerIcon.classList.add('fa-search');
		}

		var container = document.getElementById('title-search-mobile');
		if (container && typeof BX.onCustomEvent === 'function')
			BX.onCustomEvent(container, 'OnNodeLayoutChange');

		return;
	}

	panel.classList.remove('is-inline');
	BX.removeClass(hmobile, 'hmobile--search-inline');

	if (trigger)
		trigger.removeAttribute('aria-hidden');

	if (!panel.classList.contains('is-open'))
	{
		BX.removeClass(hmobile, 'hmobile--search-open');

		if (trigger)
		{
			trigger.setAttribute('aria-expanded', 'false');
			trigger.setAttribute('aria-label', 'Поиск');
		}

		if (triggerIcon)
		{
			triggerIcon.classList.remove('fa-times');
			triggerIcon.classList.add('fa-search');
		}
	}
}

function polimerGetMobileSearchInstance()
{
	if (!window._polimerTitleSearchInstances)
		return null;

	return window._polimerTitleSearchInstances['title-search-mobile'] || null;
}

function polimerDismissTitleSearchInstance(instance)
{
	if (!instance)
		return;

	instance.dismissResults();

	if (instance.INPUT)
	{
		instance.oldValue = '';
		instance.INPUT.value = '';
	}

	if (instance.CONTAINER && typeof BX.onCustomEvent === 'function')
		BX.onCustomEvent(instance.CONTAINER, 'OnNodeLayoutChange');
}

function polimerHideMobileSearchResults()
{
	polimerDismissTitleSearchInstance(polimerGetMobileSearchInstance());
}

function polimerHideAllTitleSearchResults()
{
	if (!window._polimerTitleSearchInstances)
		return;

	Object.keys(window._polimerTitleSearchInstances).forEach(function(key) {
		polimerDismissTitleSearchInstance(window._polimerTitleSearchInstances[key]);
	});
}

function polimerEnsureMobileSearchCloseBtn()
{
	var form = document.querySelector('#hmobile-search-panel .search');

	if (!form || form.querySelector('.search__close'))
		return;

	var btn = document.createElement('button');
	btn.type = 'button';
	btn.className = 'search__close';
	btn.setAttribute('aria-label', 'Закрыть поиск');
	btn.innerHTML = '<i class="fa fa-times" aria-hidden="true"></i>';
	BX.bind(btn, 'click', function(e){
		e.preventDefault();
		e.stopPropagation();
		polimerCloseActiveSearch();
	});
	BX.bind(btn, 'mousedown', function(e){
		e.stopPropagation();
	});
	form.appendChild(btn);
}

function polimerSetMobileSearchHeaderLock(locked)
{
	var header = document.querySelector('header');

	if (!header)
		return;

	if (locked)
		header.classList.add('polimer-header--mobile-search-open');
	else
		header.classList.remove('polimer-header--mobile-search-open');
}

function polimerGetSearchHeaderHeight()
{
	var hmobile = document.querySelector('header .hmobile');

	return hmobile ? hmobile.offsetHeight : 0;
}

function polimerLockSearchHeader()
{
	if (!window.matchMedia('(max-width: 1019px)').matches)
		return;

	if (typeof window._polimerSearchHeaderLockCount !== 'number')
		window._polimerSearchHeaderLockCount = 0;

	if (window._polimerSearchHeaderLockCount === 0)
	{
		if (typeof window._polimerMobileSearchScrollY !== 'number')
			window._polimerMobileSearchScrollY = window.scrollY || window.pageYOffset || 0;

		var headerHeight = polimerGetSearchHeaderHeight();
		if (headerHeight > 0)
			document.body.style.paddingTop = headerHeight + 'px';

		polimerSetMobileSearchHeaderLock(true);
	}

	window._polimerSearchHeaderLockCount++;
}

function polimerUnlockSearchHeader(force)
{
	if (!window.matchMedia('(max-width: 1019px)').matches)
		return;

	if (typeof window._polimerSearchHeaderLockCount !== 'number' || window._polimerSearchHeaderLockCount <= 0)
		return;

	if (force)
		window._polimerSearchHeaderLockCount = 0;
	else
		window._polimerSearchHeaderLockCount--;

	if (window._polimerSearchHeaderLockCount > 0)
		return;

	polimerSetMobileSearchHeaderLock(false);
	document.body.style.paddingTop = '';

	if (polimerIsTabletInlineSearch() && typeof window._polimerMobileSearchScrollY === 'number')
	{
		var scrollY = window._polimerMobileSearchScrollY;
		window._polimerMobileSearchScrollY = null;
		window.scrollTo(0, scrollY);
	}
}

function polimerCloseActiveSearch()
{
	if (polimerIsTabletInlineSearch())
	{
		polimerHideAllTitleSearchResults();

		var instance = polimerGetMobileSearchInstance();
		var input = document.getElementById('title-search-input-mobile');

		if (instance && input)
		{
			instance.oldValue = '';
			input.value = '';
		}

		if (input)
		{
			try { input.blur({ preventScroll: true }); }
			catch (e) { input.blur(); }
		}

		return;
	}

	polimerCloseMobileSearchPanel();
}

function polimerCloseMobileSearchPanel()
{
	if (polimerIsTabletInlineSearch())
		return;

	var panel = document.getElementById('hmobile-search-panel');
	var trigger = document.querySelector('.hmobile__search');
	var hmobile = document.querySelector('header .hmobile');
	var input = document.getElementById('title-search-input-mobile');
	var triggerIcon = trigger ? trigger.querySelector('.header__fa-icon') : null;

	if (!panel)
		return;

	polimerHideMobileSearchResults();

	panel.classList.remove('is-open');

	if (hmobile)
		BX.removeClass(hmobile, 'hmobile--search-open');

	if (trigger)
	{
		trigger.setAttribute('aria-expanded', 'false');
		trigger.setAttribute('aria-label', 'Поиск');
	}

	if (triggerIcon)
	{
		triggerIcon.classList.remove('fa-times');
		triggerIcon.classList.add('fa-search');
	}

	if (input)
	{
		input.value = '';
		input.blur();
	}

	polimerUnlockSearchHeader(true);

	if (typeof window._polimerMobileSearchScrollY === 'number')
	{
		window.scrollTo(0, window._polimerMobileSearchScrollY);
		window._polimerMobileSearchScrollY = null;
	}

	var container = document.getElementById('title-search-mobile');
	if (container && typeof BX.onCustomEvent === 'function')
		BX.onCustomEvent(container, 'OnNodeLayoutChange');
}

function polimerOpenMobileSearchPanel()
{
	if (polimerIsTabletInlineSearch())
		return;

	var panel = document.getElementById('hmobile-search-panel');
	var trigger = document.querySelector('.hmobile__search');
	var hmobile = document.querySelector('header .hmobile');
	var input = document.getElementById('title-search-input-mobile');
	var menuTrigger = document.querySelector('header .menu__trigger');
	var triggerIcon = trigger ? trigger.querySelector('.header__fa-icon') : null;

	if (!panel)
		return;

	if (menuTrigger && menuTrigger.classList.contains('close'))
		menuTrigger.click();

	if (typeof window._polimerMobileSearchScrollY !== 'number')
		window._polimerMobileSearchScrollY = window.scrollY || window.pageYOffset || 0;

	window.scrollTo(0, 0);
	polimerLockSearchHeader();

	panel.classList.add('is-open');

	if (hmobile)
		BX.addClass(hmobile, 'hmobile--search-open');

	if (trigger)
	{
		trigger.setAttribute('aria-expanded', 'true');
		trigger.setAttribute('aria-label', 'Закрыть поиск');
	}

	if (triggerIcon)
	{
		triggerIcon.classList.remove('fa-search');
		triggerIcon.classList.add('fa-times');
	}

	if (input)
	{
		setTimeout(function(){
			if (input.focus)
			{
				try { input.focus({ preventScroll: true }); }
				catch (e) { input.focus(); }
			}

			var container = document.getElementById('title-search-mobile');
			if (container && typeof BX.onCustomEvent === 'function')
				BX.onCustomEvent(container, 'OnNodeLayoutChange');
		}, 0);
	}
}

function polimerBindMobileSearchPanel()
{
	if (window._polimerMobileSearchBound)
		return;

	window._polimerMobileSearchBound = true;

	polimerEnsureMobileSearchCloseBtn();

	var trigger = document.querySelector('.hmobile__search');

	if (trigger)
	{
		BX.bind(trigger, 'click', function(e){
			e.preventDefault();
			if (!polimerIsMobileSearchIconMode())
				return;

			var panel = document.getElementById('hmobile-search-panel');
			if (panel && panel.classList.contains('is-open'))
				polimerCloseMobileSearchPanel();
			else
				polimerOpenMobileSearchPanel();
		});
	}

	BX.bind(document, 'keydown', function(e){
		if (!e || e.keyCode !== 27)
			return;

		var hasOpenResults = document.body.classList.contains('polimer-search-open');
		var panel = document.getElementById('hmobile-search-panel');
		var panelOpen = panel && panel.classList.contains('is-open');

		if (polimerIsTabletInlineSearch())
		{
			if (hasOpenResults)
				polimerCloseActiveSearch();
			return;
		}

		if (panelOpen || hasOpenResults)
			polimerCloseActiveSearch();
	});

	BX.bind(window, 'resize', BX.throttle(function(){
		polimerSyncMobileSearchMode();

		if (!polimerIsMobileSearchIconMode() && !polimerIsTabletInlineSearch())
			polimerCloseMobileSearchPanel();
	}, 150));
}

BX.ready(function(){
	polimerInitTitleSearchContainers();
	polimerBindMobileSearchPanel();
	polimerSyncMobileSearchMode();
});
