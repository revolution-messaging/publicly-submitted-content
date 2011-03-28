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
	
	jQuery('#psc_form_add_field a').attr('href', 'javascript:void(0);');
	jQuery('#psc_form_add_field a').click(function() {
		var count_items = 0;
		count_items = jQuery('.fieldItem').length;
		console.log(count_items);
		jQuery('ol#form_edit_fields').append('<li id="field'+count_items+'item" class="fieldItem"><a href="#" class="deleteFieldItem">[delete]</a><div class="text"><label for="field'+count_items+'label">Label</label><input type="text" name="data['+count_items+'][label]" id="field'+count_items+'label" value="" /></div><div class="text"><label for="field'+count_items+'slug">Slug/ID/Name</label><input type="text" name="data['+count_items+'][slug]" id="field'+count_items+'slug" value="" /></div><div class="select"><label for="field'+count_items+'type">Type</label><select name="data['+count_items+'][type]" id="field'+count_items+'type"><option value="text" selected="selected">Text</option><option value="textarea">Textarea</option><option value="hidden">Hidden</option><option value="select">Select</option><option value="multiselect">Multiselect</option><option value="radio">Radio</option><option value="checkbox">Checkbox</option></select></div><div class="text options" style="display:none;"><label for="field'+count_items+'options">Options <span class="small">(comma separated list of options for select, multiselect, checkbox, and radio types)</span></label><input type="text" name="data['+count_items+'][options]" id="field'+count_items+'options" value="" /></div><div class="checkbox"><input type="checkbox" name="data['+count_items+'][required]" id="field'+count_items+'required" /><label for="field'+count_items+'required">Required</label></div><div class="checkbox"><input type="checkbox" name="data['+count_items+'][maps_as]" id="field'+count_items+'maps_as" /><label for="field'+count_items+'maps_as">Use this as the "post content"</label></div></li>');
	});
	
	jQuery('a.deleteFieldItem').attr('href', 'javascript:void(0);');
	jQuery('a.deleteFieldItem').click(function() {
		jQuery(this).parent().slideUp(400).delay(1000).detach();
	});
});