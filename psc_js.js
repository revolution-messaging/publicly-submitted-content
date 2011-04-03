/**
 * Transform text into a URL slug: spaces turned into dashes, remove non alnum
 * @param string text
 */
function slugify(text) {
	text = text.replace(/[^-a-zA-Z0-9,&\s]+/ig, '');
	text = text.replace(/-/gi, "_");
	text = text.replace(/\s/gi, "_").toLowerCase();
	return text;
}

function sortIt() {
	jQuery('#normal-sortables').sortable();
}

jQuery(document).ready(function() {
	jQuery('#captcha_option').change(function() {
		if(jQuery('#captcha_option').attr('checked')==true) {
			if(jQuery('#api_key').attr('value')=='' || jQuery('#private_key').attr('value')=='') {
				jQuery('#psc_catcha_info').show();
			}
		} else {
			jQuery('#psc_catcha_info').hide();
		}
	});
	
	sortIt();
	
	jQuery('.handlediv').live('click', function() {
		if(jQuery(this).parent().children('.inside').css('display')=='block') {
			jQuery(this).parent().children('.inside').css('display', 'none');
			jQuery(this).parent().attr('class', 'postbox fieldItem closed');
		} else {
			jQuery(this).parent().children('.inside').css('display', 'block');
			jQuery(this).parent().attr('class', 'postbox fieldItem');
		}
	});
	
	jQuery('.fieldtype').live('change', function() {
		if(jQuery(this).attr('value')=='select' || jQuery(this).attr('value')=='multiselect' || jQuery(this).attr('value')=='radio' || jQuery(this).attr('value')=='checkbox') {
			jQuery(this).parent().parent().children('.options').css('display', 'block');
		} else {
			jQuery(this).parent().parent().children('.options').css('display', 'none');
		}
	});
	
	jQuery('#title').live('keyup', function() {
		if(jQuery(this).attr('value')=='') {
			var slug = '';
		} else {
			var slug = slugify(jQuery(this).attr('value'));
		}
		jQuery('#edit-slug-box').children('span').text(slug);
		jQuery('#edit-slug-box').children('input').attr('value', slug);
	});
	
	jQuery('.fieldlabel').live('keyup', function() {
		if(jQuery(this).attr('value')=='') {
			var handlename = '[Label]';
			var slug = '';
		} else {
			var handlename = jQuery(this).attr('value');
			var slug = slugify(jQuery(this).attr('value'));
		}
		jQuery(this).parent().parent().parent().children('.hndle').text(handlename);
		jQuery(this).parent().parent().find('.slugify').attr('value', slug);
	});
	
	jQuery('a#add_form_field').attr('href', 'javascript:void(0);');
	jQuery('a#add_form_field').click(function() {
		var count_items = 0;
		count_items = jQuery('#psckeycount').attr('value').valueOf();
		jQuery('#normal-sortables').append('<div id="field'+count_items+'item" class="postbox fieldItem"><div class="handlediv" title="Click to toggle"><br></div><h3 class="hndle"><span>[Label]</span></h3><div class="inside" style="display:block;"><a href="#" class="deleteFieldItem">Delete Field</a><div class="text"><label for="field'+count_items+'label">Label</label><input type="text" name="data['+count_items+'][label]" class="fieldlabel" id="field'+count_items+'label" value="" /></div><div class="text"><label for="field'+count_items+'slug">Slug/ID/Name</label><input type="text" name="data['+count_items+'][slug]" class="slugify" id="field'+count_items+'slug" value="" /></div><div class="select"><label for="field'+count_items+'type">Type</label><select name="data['+count_items+'][type]" class="fieldtype" id="field'+count_items+'type"><option value="text" selected="selected">Text</option><option value="textarea">Textarea</option><option value="hidden">Hidden</option><option value="select">Select</option><option value="multiselect">Multiselect</option><option value="radio">Radio</option><option value="checkbox">Checkbox</option><option value="file">Image</option></select></div><div class="text options" style="display:none;"><label for="field'+count_items+'options">Options <span class="small">(comma separated list of options for select, multiselect, checkbox, and radio types)</span></label><input type="text" name="data['+count_items+'][options]" id="field'+count_items+'options" value="" /></div><div class="checkbox"><input type="checkbox" name="data['+count_items+'][required]" id="field'+count_items+'required" /><label for="field'+count_items+'required">Required</label></div><div class="checkbox"><input type="checkbox" name="data['+count_items+'][maps_as]" id="field'+count_items+'maps_as" /><label for="field'+count_items+'maps_as">Use this as the "post content"</label></div></div></div>');
		sortIt();
		jQuery('#psckeycount').attr('value', count_items++);
	});
	
	jQuery('a.deleteFieldItem').attr('href', 'javascript:void(0);');
	jQuery('a.deleteFieldItem').live('click', function() {
		jQuery(this).parent().parent().slideUp(400, function() { jQuery(this).remove(); });
	});
});