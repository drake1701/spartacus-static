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

function heightsFix() {
    var width = jQuery(document).width();
    jQuery('#main').css('height', '');
    jQuery('#sidebar').css('height', '');
    if(width > 991) {
        if(parseInt(jQuery('#sidebar').css('height')) < parseInt(jQuery('#main').css('height'))) {
            jQuery('#sidebar').css('height', jQuery('#main').css('height'));
        } else {
            jQuery('#main').css('height', jQuery('#sidebar').css('height'));
        }
    }

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
            } else {
                setHeights(imagesD, desktop.width());
            }
            var width = ((1/6) * imagesM.length * mobile.width()) - (imagesM.length * 1.5);
            setHeights(imagesM, width);

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

    if(jQuery(window).width() > 991) {
        jQuery('#sidebar').insertBefore(jQuery('#main'));
    } else {
        jQuery('#sidebar').insertAfter(jQuery('#main'));
    }

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

