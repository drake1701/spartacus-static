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
