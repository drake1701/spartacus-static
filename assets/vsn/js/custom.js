/**
 * @author     Spartacus <spartacuswallpaper@gmail.com>
 * @address    www.spartacuswallpaper.com
 * @date       1/19/16
 */

var resizing = false;

jQuery(window).load(heightsFix).resize(function(){
    if(resizing == false ) {
        resizing = true;
        setTimeout(heightsFix, 100);
        resizing = false;
    }
});

function getWidths() {
    var sizes = [];
    var images = jQuery('img');
    images.each(function() {
        var image = $(this);
        if(sizes[image.width()] == undefined) {
            sizes[image.width()] = 1;
        } else {
            sizes[image.width()] = sizes[image.width()] + 1;
        }
    });
    console.log(sizes);
}

function heightsFix() {
    var width = jQuery(document).width();

    if(jQuery('.entry-images .image').length){
        var desktop = jQuery('.entry-images .desktop');
        var imagesD = desktop.find('.image');
        var mobile = jQuery('.entry-images .mobile');
        var imagesM = mobile.find('.image');
        if(width > 767) {
            if(imagesD.length > 3) {
                if(imagesD.length == 4) {
                    setHeights(desktop.find('.image:lt(2)'), desktop.width());
                    setHeights(desktop.find('.image:gt(1)'), desktop.width());
                } else {
                    setHeights(desktop.find('.image:lt(3)'), desktop.width());
                    setHeights(desktop.find('.image:gt(2)'), desktop.width());
                }
            } else if (imagesD.length == 1) {
                setHeights(imagesD, desktop.width() / 2);
            } else {
                setHeights(imagesD, desktop.width());
            }
            var boxwidth = ((1/6) * imagesM.length * mobile.width()) - (imagesM.length * 1.5);
            setHeights(imagesM, boxwidth);

        } else {
            jQuery('.entry-images .image, .entry-images .image span').css({'height': '', 'width': ''});
        }

    }
}

function setHeights(images, width) {
    var inverseTotal = 0;
    images.each(function(){
        inverseTotal += 1/jQuery(this).data('ratio');
    });
    var first = images.first();
    var factor = 1/inverseTotal;
    var widthfactor = factor * (1 / first.data('ratio'));
    var targetwidth = widthfactor * width;
    var height = targetwidth * first.data('ratio');
    images.css('height', parseInt(height));
    images.each(function(){
        var width = jQuery(this).find('img').width();
        jQuery(this).width(width);
        jQuery(this).find('span').width(width);
    });
}

var ajaxing = false;
var ajaxContent;

jQuery(document).ready(function(){

    var lazyConfig = {
        effect : "fadeIn",
        skip_invisible : true,
        placeholder: '/images/blank.png'
    };

    jQuery('#main img.lazy').lazyload(lazyConfig);
    jQuery('#sidebar img.lazy').lazyload(lazyConfig);

    jQuery('#main').on('click', '#show_more', function(){
        if(ajaxing) return;
        if(typeof curPage != 'number') return;

        curPage += 1;
        var newUrl = jQuery('.pager .current').prev().find('a').prop('href');

        //jQuery('nav').append('<img class="loader" src="../images/ajax-loader.gif" />');

        var loader = setInterval(function() {
            jQuery('.btn-lg').animate({backgroundPositionY: '44px'}, 300, function () {
                jQuery(this).removeAttr('style');
            })
        }, 300);

        ajaxing = true;
        jQuery('.btn-more').prop('disabled', 1);

        jQuery.ajax({
            url: newUrl,
            success: function(response) {
                processAjaxData(response, newUrl);
                clearInterval(loader);
            },
            error: function() {
                ajaxing = false;
            }
        });
    });

    setTimeout(function () {
        if (typeof window.Goog_AdSense_getAdAdapterInstance !== 'undefined'
            || typeof CHITIKA_ADS !== 'undefined'
        ) {
            jQuery('.ad').css('background-image', 'none');
        }
    }, 500);

    jQuery('.entry-images img').load(heightsFix);

});

function processAjaxData(response, urlPath){

    ajaxContent = jQuery(response);

    ajaxContent.find('.ad:not(.bottom), .content-head, .content-title, .btn-home').remove();

    jQuery('nav, .ad.bottom').remove();
    jQuery('#main').append(ajaxContent.find('#main').html());

    jQuery('img').load(heightsFix);

    window.history.pushState({"html":response.html,"pageTitle":document.title},"", urlPath);
    ajaxing = false;
}

