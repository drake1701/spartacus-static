<?php
require_once __DIR__ . '/../bootstrap.php';
header('content-type: text/javascript');
?>
jQuery(function() {
    jQuery( ".calendar" ).sortable({
        stop: function() {
            jQuery('#calendar').submit();
        },
        items:'.item'
    });
    jQuery( ".calendar" ).disableSelection();
    jQuery('.entry img').click(function(){
        jQuery(this).parent().toggleClass('ui-selected');
        var check = jQuery(this).parent().find('input[type=checkbox]');
        check.prop('checked', check.prop('checked') ? '' : 'checked');
    });
    var availableTags = [
        <?php
        $tags = \Paperroll\Helper\Registry::get('entityManager')->getRepository(\Paperroll\Model\Tag::class)->findAll();
        foreach($tags as $tag) {
            echo '"'.$tag->getSlug().'", ';
        }
        ?>
    ];
    function split( val ) {
        return val.split( /,\s*/ );
    }
    function extractLast( term ) {
        return split( term ).pop();
    }

    $( ".tags" )
    // don't navigate away from the field on tab when selecting an item
        .bind( "keydown", function( event ) {
            if ( event.keyCode === $.ui.keyCode.TAB &&
                $( this ).autocomplete( "instance" ).menu.active ) {
                event.preventDefault();
            }
        })
        .autocomplete({
            minLength: 0,
            source: function( request, response ) {
// delegate back to autocomplete, but extract the last term
                response( $.ui.autocomplete.filter(
                    availableTags, extractLast( request.term ) ) );
            },
            focus: function() {
// prevent value inserted on focus
                return false;
            },
            select: function( event, ui ) {
                var terms = split( this.value );
// remove the current input
                terms.pop();
// add the selected item
                terms.push( ui.item.value );
// add placeholder to get the comma-and-space at the end
                terms.push( "" );
                this.value = terms.join( ", " );
                return false;
            },
            appendTo: '#tags-element'
        });
});
jQuery(window).load(function(){
    var height = 0;
    jQuery('.calendar > div').each(function(){
        if(jQuery(this).height() > height) {
            height = jQuery(this).height();
        }
    });
    jQuery('.calendar > div').height('25px');
    jQuery('.calendar > div:gt(6)').height(height);
    jQuery('.calendar > div:nth-child(7n+1)').css({
        clear:'left',
        borderLeft: '1px solid #A4B070'
    });
});