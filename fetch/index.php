<!DOCTYPE HTML>
<html>
    <head>
        <script src="//code.jquery.com/jquery-1.11.3.min.js"></script>
        <style type="text/css">
            body { 
                font: 14px 'Menlo'; 
                margin-bottom:20em;
            }
            #search { margin: 20px auto; text-align: center; }
            #search input { font-size:20px; }
            #results .item { 
                width:19%; 
                margin:0.5%; 
                float:left; 
                background-color: #ccc;                
                height: 200px;
                display: block;
                background-size: contain;
                background-repeat: no-repeat;
                background-position: 50% 50%;
                overflow: scroll;
                position: relative;
            }
            #results a { 
                color: #000; 
                text-decoration: none; 
            }
            #results .item a.name {
                display:none;
            }
            #results .item:hover .name {
                display: block;
                position: absolute;
                bottom: 3%;
                padding: 20px 8px;
                background: rgba(255,255,255,.8);
            }
            #results {
                border: 1px solid black;
                overflow: auto;
            }
            #results a.reject {
                min-height: auto;
                position: absolute;
                right: 0;
                background: #eee;
                padding: 14px;
                z-index: 999;
                cursor: pointer;
            }
            iframe {
                display:none;
            }
        </style>
        <script type="text/javascript">
        //<![CDATA[
            var m = 0;
        	jQuery(document).ready(function(){
                jQuery('#search').submit(function(e) {
                    e.preventDefault();
                    search(true);
                });
                jQuery('input[type=checkbox]').click(function(e) {
                    search();
                });
                jQuery('#results').on('click', 'a.reject', function(e){
                    e.preventDefault();
                    m = m + 1;
                    jQuery('#results').after('<div id="msg_'+ m +'" class="message"><img class="loader" src="/ajax-loader.gif" /></div>');
                    jQuery('#msg_'+ m).load(
                		'/reject.php',
                		'id=' + jQuery(e.target).parent().prop('id'),
                		function() {
                            jQuery(e.target).parent().fadeOut(100).remove();
                		}
                	)
                });
                jQuery('#results').on('click', '.item a.name', function(e){
                    e.preventDefault();
                    var url = jQuery(e.target).parent().prop('href');
                    var item = jQuery(e.target).parents('.item');
                    if(url) {
                        jQuery('#results').after('<iframe src="'+url+'" />');
                    }
                    jQuery.ajax('/reject.php?id=' + item.prop('id'));
                    item.html('').css('background-image', 'url(/ajax-loader.gif)');
                	item.fadeOut(900).remove();
                });
                jQuery('#purge').on('click', function(e){
                    e.preventDefault();
                    jQuery('a.reject').each(function(){
                        if(jQuery(this).parent().is(':visible')) {
                            jQuery(this).click();
                        }
                    });
                });
                jQuery(window).scroll(function() {
                    if(jQuery(window).scrollTop() + jQuery(window).height() > jQuery(document).height() - 10) {
                        getCaches();
                    }
                });
                search();
        	});
            function search() {
                var existingString = jQuery("#q").val();
                if (existingString.length < 3) return; //wasn't enter, not > 2 char
                jQuery('#results').html('<img class="loader" src="/ajax-loader.gif" />');
        		jQuery('#results').load(
            		'/dosearch.php',
            		jQuery('#search').serialize(),
            		function(){
                		getCaches();
            		}
            	)
            }
            function getCaches() {
                var count = 0;
        		jQuery('.item:not(:visible)').each(function(){
            		if(count++ > 14) return;
            		var item = jQuery(this);
            		var background = jQuery(this).data('image')
            		jQuery.ajax({
                		url: background,
                		type: 'HEAD',
                		error: function(){
                            item.show().css('background-image', 'url(/ajax-loader.gif)');
                    		jQuery.ajax({
                        		url: '/cachethumbs.php?id=' + item.prop('id'),
                        		success: function(){
                            		item.css('background-image', 'url('+background+')');
                        		},
                        		error: function(){
                            		item.fadeOut(100).remove();
                        		}
                    		});
                		},
                		success: function(){
                    		item.css('background-image', 'url('+background+')').show();
                		}
            		});
        		});                
            }
        //]]>
        </script>
    </head>
    <body>
        <form id="search">
            <input id="q" name="q" type="text" value="new" /><button type="submit"><span>Go</span></button>
            <input type="checkbox" name="b" /> Bad
            <input type="checkbox" name="r" /> Reject
            
        </form>
        <div id="results">
            
        </div>
        <h2 style="text-align:center;"><a href="#" id="purge">Purge</a> | <a href="#" onclick="getCaches();return false;">Load More</a></h2>
    </body>
</html>