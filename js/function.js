
function replaceBasket(url, $el)
{
    $.ajax({
        url: url,
        type: 'get',
        success: function (data) {
            $el.replaceWith(data);
        }
    })
}

function addToBasket2(idel, quantity,el) {
    let $href = "/ajax/add.php?id=" + idel;

    $.ajax({
        url: $href + '&quantity=' + quantity,
        type: 'get',
        success: function (data) {
            if (data === "Товар успешно добавлен в корзину") {
                replaceBasket('/ajax/basket.php', $('.header__cart'));
                replaceBasket('/ajax/basket-mobile.php', $('.hmobile__cart'));
                alertify.success(data);
            } else {
                alertify.error(data);
            }
        }
    });
}

function setCupon(){
    var numCupon = $('#coupon').val();
    if(numCupon){
        $.ajax({
            type: "GET",
            url: "/ajax/set_cupon.php?cupon="+numCupon,
            success: function(msg){
                if(msg)
                {
                    console.log(msg);
                    UpdateBigBasket();
                    alertify.success("Купон активирован!");
                }
                else
                {
                    alertify.error("Купон не найден");
                }
            }
        });
    }else{
        alertify.error('Введите номер купона!');
        return false;
    }

}

function inputQuntly(max,count,id){
    if(count < 1){
        $('.quantity#'+id+' input').val(1);
        alertify.error("Запрашиваемое кол-во. На складе нет");
        return false;
    }
    if(count > max){
        $('.quantity#'+id+' input').val(max);
        alertify.error("Запрашиваемое кол-во превышает остаток. На складе: " + max);
        return false;
    }else{
        var data="id="+id+"&quant="+count;
        ChangeCount(data);
    }
}


function basketPlus(max,count,id, ratio){
    var increm = parseInt(count) + parseInt(ratio);

    if(increm > max){
        $('.quantity#'+id+' input').val(max - ratio);
        alertify.error("Запрашиваемое кол-во превышает остаток. На складе: " + max);
        return false;
    }else{
        var data="id="+id+"&quant="+increm;
        ChangeCount(data);
    }
}

function basketMinus(max,count,id,ratio){
    var increm = parseInt(count) - parseInt(ratio);

    if(increm < 1){
        $('.quantity#'+id+' input').val(ratio);
        alertify.error("Запрашиваемое кол-во. На складе нет");
        return false;
    }else{
        var data="id="+id+"&quant="+increm;
        ChangeCount(data);
    }
}

function ChangeCount(data)
{
    $.ajax({
        type: "GET",
        url: "/ajax/change_count.php",
        data:data,
        success: function(msg){
            if(msg!="error")
            {
                 UpdateBigBasket();
            }
            else
            {
                alertify.error("");
            }
        }
    });
}
function UpdateBigBasket(){
    $.ajax({
        type: "GET",
        url: "/ajax/big_basket.php",
        data:"",
        success: function(msg){
            if(msg!="error")
            {
                $(".page_content").html(msg);
            }
            else
            {
                alertify.error("Произошла ошибка. Попробуйте повторить запрос позже");
            }
        }
    });
}

function deleteBasket(){
    $.ajax({
        type: "GET",
        url: "/ajax/delete_all_basket.php",
        data:"",
        success: function(msg){
            if(msg!="error")
            {
                UpdateBigBasket();
            }
            else
            {
                alertify.error("Произошла ошибка. Попробуйте повторить запрос позже");
            }
        }
    });
    return false;
}

$(function(){

    $('.catalog-sections-text').readmore({
        speed: 75,
        maxHeight: 100,
        moreLink: '<a href="#" style="border-bottom:snow;padding: 0 10px;">Подробнее...</a>',
        lessLink: '<a href="#" style="border-bottom:snow;padding: 0 10px;">Скрыть</a>'
    });
    $('.col-txt > .catalog-sections-text-hidden').readmore({
        speed: 75,
        maxHeight: 130,
        moreLink: '<a href="#" style="border-bottom:snow">Подробнее...</a>',
        lessLink: '<a href="#" style="border-bottom:snow">Скрыть</a>'
    });

    $('.category__show').off('click').on('click.polimerCategories', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var $hidden = $btn.closest('.tabitem').find('.toggle_product_no');
        if (!$hidden.length) {
            return false;
        }
        var expanding = !$hidden.first().hasClass('is-visible');
        $hidden.toggleClass('is-visible', expanding);
        $btn.text(expanding ? 'Скрыть' : 'Показать ещё категории');
        return false;
    });

    $('.show_brand').click(function(){
        var than = $(this);
        than.closest('.brand-list').find('.brand-hidden').slideToggle({
            start : function () {
                if ($(this).is(':visible'))
                    $(this).css('display','inline-block');
            }
        });
        return false;
    });


	  $('.ym-goal-subscribe-price').submit(function(e) {
        var $form = $(this);
        $.ajax({
          type: $form.attr('method'),
          url: $form.attr('action'),
          data: $form.serialize()
        }).done(function(data) {
          alertify.success("Подписка оформлена!");
		  $form[0].reset();
        }).fail(function() {
          alertify.error("Произошла ошибка. Попробуйте повторить запрос позже");
        });
        //отмена действия по умолчанию для кнопки submit
        e.preventDefault();
      });

});

// hover-слайдер превью карточки товара: скраб мышью на десктопе, свайп на мобильном
$(function () {
    var ZONE = '.products_roll .pic.has-slider';
    var SWIPE_STEP = 30; // px на один кадр при свайпе

    function setFrame($pic, index) {
        var $slides = $pic.find('.pic-slide');
        var max = $slides.length - 1;
        if (index < 0) index = 0;
        if (index > max) index = max;
        $slides.removeClass('active').eq(index).addClass('active');
        $pic.find('.pic-dot').removeClass('active').eq(index).addClass('active');
        var activeSlide = $slides.get(index);
        if (activeSlide) {
            polimerNormalizeCatalogPhoto(activeSlide);
        }
    }

    // десктоп: переключение кадров движением курсора
    $(document).on('mousemove', ZONE, function (e) {
        var $pic = $(this);
        var rect = this.getBoundingClientRect();
        var count = $pic.find('.pic-slide').length;
        if (!count || !rect.width) return;
        var index = Math.floor((e.clientX - rect.left) / rect.width * count);
        setFrame($pic, index);
    });
    $(document).on('mouseleave', ZONE, function () {
        setFrame($(this), 0); // возврат к первому кадру
    });

    // мобильный: переключение свайпом
    var touchPic = null, touchStartX = 0, touchStartIndex = 0;
    $(document).on('touchstart', ZONE, function (e) {
        touchPic = $(this);
        touchStartX = e.originalEvent.touches[0].clientX;
        touchStartIndex = touchPic.find('.pic-slide.active').index();
    });
    $(document).on('touchmove', ZONE, function (e) {
        if (!touchPic) return;
        var dx = e.originalEvent.touches[0].clientX - touchStartX;
        if (Math.abs(dx) > SWIPE_STEP) {
            var steps = Math.floor(Math.abs(dx) / SWIPE_STEP) * (dx < 0 ? 1 : -1);
            setFrame(touchPic, touchStartIndex + steps);
            e.preventDefault(); // блокируем переход по ссылке и скролл при горизонтальном свайпе
        }
    });
    $(document).on('touchend touchcancel', ZONE, function () {
        touchPic = null;
    });
});

function polimerCatalogPhotoScaleClass(nw, nh, cw, ch) {
    var fitScale = Math.min(cw / nw, ch / nh);
    var widthFill = (nw * fitScale) / cw;
    var heightFill = (nh * fitScale) / ch;

    // Напольные / крупные: почти вся высота рамки (Lemax) — уменьшаем
    if (heightFill >= 0.88 && widthFill >= 0.5) {
        return 'polimer-photo-scale-compact';
    }

    // Узкий настенный котёл на всю высоту
    if (heightFill >= 0.88 && widthFill < 0.5) {
        return 'polimer-photo-scale-tall';
    }

    // Широкий низкий или мелкий в рамке (ZOTA) — увеличиваем
    if (heightFill <= 0.7 || (widthFill >= 0.78 && heightFill <= 0.75)) {
        return 'polimer-photo-scale-tall';
    }

    if (widthFill < 0.72 && heightFill < 0.72) {
        return 'polimer-photo-scale-tall';
    }

    return 'polimer-photo-scale-mid';
}

function polimerNormalizeCatalogPhoto(img) {
    if (!img || img.tagName !== 'IMG') {
        return;
    }

    if (img.src && img.src.indexOf('no_photo') !== -1) {
        return;
    }

    var pic = img.closest('.pic');
    if (!pic) {
        return;
    }

    var apply = function () {
        var nw = img.naturalWidth;
        var nh = img.naturalHeight;
        if (!nw || !nh) {
            return;
        }

        var cw = pic.clientWidth;
        var ch = pic.clientHeight;
        if (!cw || !ch) {
            return;
        }

        img.classList.remove(
            'polimer-photo-fit',
            'polimer-photo-contain',
            'polimer-photo-cover',
            'polimer-photo-scale-tall',
            'polimer-photo-scale-mid',
            'polimer-photo-scale-wide',
            'polimer-photo-scale-compact'
        );
        img.classList.add(polimerCatalogPhotoScaleClass(nw, nh, cw, ch));
        img.style.removeProperty('width');
        img.style.removeProperty('height');
        img.style.removeProperty('transform');
        img.style.removeProperty('object-fit');
        img.style.removeProperty('object-position');
    };

    if (img.complete && img.naturalWidth) {
        apply();
    } else {
        img.addEventListener('load', apply, { once: true });
    }
}

function polimerNormalizeCatalogPhotos(root) {
    var scope = root && root.querySelectorAll ? root : document;
    var selector = '.products_roll .pic img';

    if (root && root.matches && root.matches(selector)) {
        polimerNormalizeCatalogPhoto(root);
    }

    scope.querySelectorAll(selector).forEach(function (img) {
        polimerNormalizeCatalogPhoto(img);
    });
}

$(function () {
    polimerNormalizeCatalogPhotos();
    window.addEventListener('load', function () {
        polimerNormalizeCatalogPhotos();
    });
    setTimeout(function () {
        polimerNormalizeCatalogPhotos();
    }, 300);
    setTimeout(function () {
        polimerNormalizeCatalogPhotos();
    }, 1200);

    var resizeTimer = null;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            polimerNormalizeCatalogPhotos();
        }, 100);
    });

    if (typeof MutationObserver !== 'undefined') {
        var normalizeTimer = null;
        var observer = new MutationObserver(function () {
            clearTimeout(normalizeTimer);
            normalizeTimer = setTimeout(function () {
                polimerNormalizeCatalogPhotos();
            }, 50);
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }
});

function polimerCloseAdd2CartPopups($exceptItem) {
    $('.products_roll .pr_box .item.add2cart2').each(function () {
        if ($exceptItem && $exceptItem.length && this === $exceptItem[0]) {
            return;
        }

        var $item = $(this);
        $item.removeClass('add2cart2');
        $item.children('.hover').css({ transform: '', left: '', right: '', top: '' });
    });
}

function polimerCenterAdd2CartPopup($item) {
    var $hover = $item.children('.hover');
    if (!$hover.length) {
        return;
    }

    $hover.css({
        left: '50%',
        right: 'auto',
        transform: 'translateX(-50%)'
    });

    window.requestAnimationFrame(function () {
        var node = $hover[0];
        if (!node) {
            return;
        }

        var rect = node.getBoundingClientRect();
        var pad = 10;
        var shift = 0;

        if (rect.left < pad) {
            shift = pad - rect.left;
        } else if (rect.right > window.innerWidth - pad) {
            shift = (window.innerWidth - pad) - rect.right;
        }

        if (shift !== 0) {
            $hover.css('transform', 'translateX(calc(-50% + ' + shift + 'px))');
        }
    });
}

function polimerAdd2CartFromCard($add2cart, triggerEl) {
    var $item = $add2cart.closest('.item');
    var id = ($item.attr('id') || '').replace('product_', '');

    if (!id) {
        return;
    }

    var qty = $item.find('.quantity input[name="quantity"]').val() || 1;
    addToBasket2(id, qty, triggerEl || $add2cart[0]);
}

$(function () {
    var isDesktopCatalog = window.matchMedia('(min-width: 1020px)');

    if (!isDesktopCatalog.matches) {
        $(document).on('click', '.products_roll .pr_box .item .hover .inner .add2cart', function (e) {
            e.preventDefault();
            e.stopPropagation();
            polimerAdd2CartFromCard($(this), e.target);
            return false;
        });
        return;
    }

    $(document).on('click', function (e) {
        if ($(e.target).closest('.products_roll .pr_box .item .hover .inner .add2cart').length) {
            return;
        }
        if ($(e.target).closest('.products_roll .pr_box .item.add2cart2 .hover').length) {
            return;
        }

        polimerCloseAdd2CartPopups();
    });

    $(document).on('click', '.products_roll .pr_box .item .hover .inner .add2cart', function (e) {
        if ($(e.target).closest('.txt2').length) {
            e.preventDefault();
            e.stopPropagation();
            polimerAdd2CartFromCard($(this), e.target);
            return false;
        }

        var $item = $(this).closest('.item');

        if ($item.hasClass('add2cart2')) {
            e.preventDefault();
            return false;
        }

        e.preventDefault();
        e.stopPropagation();

        polimerCloseAdd2CartPopups();
        $item.addClass('add2cart2');

        window.requestAnimationFrame(function () {
            polimerCenterAdd2CartPopup($item);
        });
    });

    $(document).on('click', '.products_roll .pr_box .item .hover .inner .close', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $item = $(this).closest('.item');
        $item.removeClass('add2cart2');
        $item.children('.hover').css({ transform: '', left: '', right: '', top: '' });
    });
});

$(function () {
    $('.product_top .filter_top .header_f').off('click').on('click.polimerFilterTop', function () {
        var $block = $(this).closest('.filter_top');
        var $body = $block.find('.body_f').first();

        $block.toggleClass('is-collapsed');
        $body.stop(true, true).slideToggle(200);
        return false;
    });
});

$(function () {
    var mobileHeaderMq = window.matchMedia('(max-width: 1019px)');

    function updateMobileHeaderScroll() {
        var $header = $('header');

        if (!mobileHeaderMq.matches || !$header.length) {
            $header.removeClass('is-scrolled');
            return;
        }

        $header.toggleClass('is-scrolled', $(window).scrollTop() > 4);
    }

    $(window).on('scroll.polimerMobileHeader resize.polimerMobileHeader', updateMobileHeaderScroll);
    updateMobileHeaderScroll();
});

function polimerInitViewedProductsSlider() {
    var $viewed = $('#mp__product__action.viewed-products-slider');

    if (!$viewed.length || typeof $.fn.slick !== 'function') {
        return;
    }

    if ($viewed.hasClass('slick-initialized')) {
        $viewed.slick('unslick');
    }

    $viewed.slick({
        slidesToShow: 4,
        slidesToScroll: 1,
        arrows: true,
        dots: true,
        infinite: false,
        swipe: true,
        draggable: true,
        touchMove: true,
        touchThreshold: 8,
        customPaging: function (slider, i) {
            return $('<button type="button" />').text(i + 1);
        },
        responsive: [
            {
                breakpoint: 1319,
                settings: {
                    slidesToShow: 3,
                    slidesToScroll: 1,
                    arrows: true,
                    dots: true
                }
            },
            {
                breakpoint: 1019,
                settings: {
                    slidesToShow: 2,
                    slidesToScroll: 1,
                    arrows: true,
                    dots: true
                }
            },
            {
                breakpoint: 660,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    centerMode: true,
                    centerPadding: '36px',
                    arrows: true,
                    dots: true,
                    infinite: false
                }
            }
        ]
    });
}

$(function () {
    polimerInitViewedProductsSlider();
});

function polimerEnsureMobileFilterLayer() {
    var $leftbar = $('.ct__leftbar');
    var $mask = $('.ct__mask');

    if (!$leftbar.length || $leftbar.data('polimer-portaled')) {
        return;
    }

    $leftbar.appendTo('body').data('polimer-portaled', 1);
    if ($mask.length) {
        $mask.appendTo('body').data('polimer-portaled', 1);
    }
}

function polimerDockFilterResultBar() {
    if (!window.matchMedia('(max-width: 659px)').matches) {
        return;
    }

    var modef = document.getElementById('modef');
    if (!modef || !$('.ct__leftbar').hasClass('active')) {
        return;
    }

    if (modef.parentElement !== document.body) {
        document.body.appendChild(modef);
    }
}

function polimerCloseMobileFilter() {
    $('.ct__leftbar').removeClass('active');
    $('.ct__content').removeClass('active');
    $('.products_roll .pr_header .filter').removeClass('change');
    $('.ct__mask').removeClass('active');
    $('body').removeClass('polimer-filter-open');
}

function polimerOpenMobileFilter() {
    polimerEnsureMobileFilterLayer();
    $('.ct__leftbar').addClass('active');
    $('.ct__content').addClass('active');
    $('.products_roll .pr_header .filter').addClass('change');
    $('.ct__mask').addClass('active');
    $('body').addClass('polimer-filter-open');
    polimerDockFilterResultBar();
}

function polimerToggleMobileFilter() {
    if ($('.ct__leftbar').hasClass('active')) {
        polimerCloseMobileFilter();
    } else {
        polimerOpenMobileFilter();
    }
}

$(function () {
    var mobileFilterMq = window.matchMedia('(max-width: 659px)');

    function isMobileFilterView() {
        return mobileFilterMq.matches;
    }

    $(document).on('click.polimerMobileFilter', '.products_roll .pr_header > a.filter', function (e) {
        if (!isMobileFilterView()) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();
        polimerToggleMobileFilter();
    });

    $(document).on('click.polimerMobileFilter', '.ct__mask.active', function (e) {
        if (!isMobileFilterView()) {
            return;
        }

        e.preventDefault();
        polimerCloseMobileFilter();
    });

    $(document).on('click.polimerMobileFilter', '.polimer-filter-close, .cat.filter.m-close .filter', function (e) {
        e.preventDefault();
        e.stopPropagation();
        polimerCloseMobileFilter();
    });

    $(window).on('resize.polimerMobileFilter', function () {
        if (!isMobileFilterView() && $('.ct__leftbar').hasClass('active')) {
            polimerCloseMobileFilter();
        }
    });

    $(document).on('change.polimerMobileFilter', '.ct__leftbar .smartfilter input', function () {
        setTimeout(polimerDockFilterResultBar, 0);
        setTimeout(polimerDockFilterResultBar, 150);
        setTimeout(polimerDockFilterResultBar, 500);
    });

    if (document.getElementById('modef')) {
        new MutationObserver(function () {
            polimerDockFilterResultBar();
        }).observe(document.body, { childList: true, subtree: true });
    }
});

