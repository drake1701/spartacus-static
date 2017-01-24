/**
 * @author     Dennis Rogers <dennis@drogers.net>
 * @address    www.drogers.net
 * @date       1/19/16
 */

jQuery(window).load(heightsFix).resize(heightsFix);

function heightsFix() {
    jQuery('#main').height('');
    jQuery('#sidebar').height('');
    if(jQuery(document).width() > 976) {
        if(jQuery('#sidebar').height() < jQuery('#main').height()) {
            jQuery('#sidebar').height(jQuery('#main').height() - 8);
        } else {
            jQuery('#main').height(jQuery('#sidebar').height() + 8);
        }
    }
}

var ajaxing = false;
var ajaxContent;

jQuery(document).ready(function(){
    
    jQuery('#main').on('click', '#show_more', function(){
        if(ajaxing) return;
        if(typeof curPage != 'number') return;
        console.log('show more');

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
        
    })
		
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

