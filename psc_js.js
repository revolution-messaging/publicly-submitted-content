/**
 * Transform text into a URL slug: spaces turned into dashes, remove non alnum
 * @param string text
 */
function slugify(text) {
	text = text.replace(/[^-a-zA-Z0-9,&\s]+/ig, '');
	text = text.replace(/-/gi, "_");
	text = text.replace(/\s/gi, "-");
	return text;
}

jQuery(document).ready(function() {
	jQuery('#captcha_option').change(function() {
		if(jQuery('#captcha_option').attr('checked')==true) {
			if(jQuery('#api_key').attr('value')=='false' || jQuery('#auth_id').attr('value')=='false')
			jQuery('#psc_catcha_info').show();
		} else {
			jQuery('#psc_catcha_info').hide();
		}
	});
});