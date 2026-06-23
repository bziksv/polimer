
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

    $('.category__show').click(function(){
       var than = $(this);
        than.parent().find('.toggle_product_no').slideToggle();
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

