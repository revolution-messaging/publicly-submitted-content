jQuery(document).ready(function(){
    jQuery('#psc_form').submit(function(e){
        e.preventDefault();
        jQuery.post(
            'wp-content/plugins/public-submission/public-submission.php',
            {
                _wpnonce: jQuery('#_wpnonce').val(),
                _wp_http_referer: jQuery("input[name='_wp_http_referer']").val(),
                psc_user_name: jQuery("#psc_user_name").val(),
                psc_user_email: jQuery("#psc_user_email").val(),
                psc_user_title: jQuery("#psc_user_title").val(),
                psc_user_story: jQuery("#psc_user_story").val(),
                psc_captcha_code: jQuery("#psc_captcha_code").val()
            },
            function(e){
                if(e.length != 0){
                    jQuery('#psc_user_submission').before(e);
                }
                else{
                    jQuery('#psc_form').html('<p>Thank your for your submission. Click <a href="'+window.location.href+'" id="psc_reload_form">here</a> to sumbit another story</p>');
                    jQuery('#psc_msg').show();
                }
            }
        );
    });
    jQuery('#psc_form').ajaxError(function(e, xhr, settings, exception){
        alert(xhr);
    });

    jQuery('a#psc_reload_form').click(function(event){
        event.preventDefault();
        alert(';stiuf');
        jQuery('#psc_form').text('[psc_news_form /]')
    });

    jQuery('#csn a').click(function(event){
        if(this.className == 'publish' || this.className == 'delete'){
            event.preventDefault();
            jQuery.post(
                '../wp-content/plugins/public-submission/public-submission.php',
                {
                    action: this.className,
                    id: this.id
                },
                function(e){
                    jQuery('#psc_msg').html(e);
                    jQuery('#psc_msg').show();
                    var row = jQuery(event.target).parents('tr');
                    row.each(function(){
                        jQuery(this).fadeOut('slow', function(e){
                            jQuery(this).remove();
                        });
                    });
                    jQuery(row).fadeOut('slow', function(e){
                        jQuery(row).remove();
                    });
                    setTimeout(function(e){
                        jQuery('#psc_msg').fadeOut('slow');
                    }, 5000);
                }
            );
        }
    });
});