<?php

/**
 * Shows form used to submit story
 */
function psc_show_form( $atts ){
	global $wpdb, $psc;
	
	$psc_error = false;
	
	extract( shortcode_atts( array(
			'id' => 1,
	), $atts ) );
	
	// $uri = explode('/', $_SERVER['REQUEST_URI']);
	
	// There's gotta be a better way of posting a form on the front-end.
	// $new_uri = 'http://'.$_SERVER['SERVER_NAME'].'/'.$uri[1].'/wp-content/plugins/public-submission/public-submission.php';
	
	// $image_uri = 'http://'.$_SERVER['SERVER_NAME'].'/'.$uri[1].'/';
	
	// Get the Form from the DB
	$res = $wpdb->get_results('SELECT * FROM '.$psc->forms.' WHERE id="'.$id.'"');
	$fields = unserialize($res[0]->data);
	
	if(count($res) == 1) {
		$any_errors = false;
		if(wp_verify_nonce($_POST['psc_add'], 'psc_nonce_field') && $_POST['psc_form_id']==$id)
		{
			$content = '';
			$meta = array();
			foreach($fields as $key => $field) {
				if($field['required']=='yes' && (!isset($_POST[$field['slug']]) || empty($_POST[$field['slug']]))) {
					$fields[$key]['error'] = true;
					$any_errors = true;
				} else if(isset($_POST[$field['slug']]) && !empty($_POST[$field['slug']])) {
					// if maps_as content
					if(isset($field['maps_as']) && $field['maps_as']=='content') {
						// add to content var
						$content = $_POST[$field['slug']];
					} else if($field['type'] == 'file') {
						$attachment = array(
							'data' => $_POST[$field['slug']],
							'field' => $field
						);
					} else {
						// add to meta save array
						$meta[$field['slug']] = $_POST[$field['slug']];
					}
				}
			}
			if(!$any_errors) {
				// save post
				$post_id = psc_create_post($content='', $post_category=1, $status='pending', $post_type='post');
				
				// save attachment
				if(isset($attachment)) {
					psc_save_attachment($attachment, $post_id);
				}
				
				// save meta
				foreach($meta as $key => $value) {
					add_post_meta($post_id, $key, $value, true);
				}
				
				// thanks page, else render the form again with errors
			}
		} else if(!empty($_POST) && !wp_verify_nonce($_POST['psc_add'], 'psc_nonce_field')) {
			$psc_error = true;
		} else if(!empty($_POST) && $_POST['psc_form_id']!=$id) {
			$psc_error = true;
		}
		
		$file = false;
		$form_fields = '';
		// Loop through and generate the elements
		foreach($fields as $field) {
			switch($field['type']) {
				case 'text':
					$form_fields .= '
						<div class="input">
							<label for="'.$field['slug'].'">'.$field['label'].'</label>
							<input type="text" name="'.$field['slug'].'" id="'.$field['slug'].'" />
						</div>
					';
					break;
				case 'file':
					$file = true;
					$form_fields .= '
						<div class="file">
							<label for="'.$field['slug'].'">'.$field['label'].'</label>
							<input type="file" name="'.$field['slug'].'" id="'.$field['slug'].'" />
						</div>
					';
					break;
				case 'hidden':
					$form_fields .= '<input type="hidden" name="'.$field['slug'].'" id="'.$field['slug'].'" value="'.$field['value'].'" />';
					break;
				case 'password':
					$form_fields .= '
					<div class="password">
						<label for="'.$field['slug'].'">'.$field['label'].'</label>
						<input type="password" name="'.$field['slug'].'" id="'.$field['slug'].'" />
					</div>
					';
					break;
				case 'textarea':
					$form_fields .= '
					<div class="textarea">
						<label for="'.$field['slug'].'">'.$field['label'].'</label>
						<textarea name="'.$field['slug'].'" id="'.$field['slug'].'"></textarea>
					</div>
					';
					break;
				case 'radio':
					$form_fields .= '
					<div id="psc_'.$field['slug'].'" class="radiogroup">'."\r\n";
						$i = 0;
						foreach($field['options'] as $key => $val) {
							$i++;
							$form_fields .= '<div class="radio"><input type="radio" name="'.$field['slug'].'" id="'.$i.'_'.$field['slug'].'" class="'.$field['slug'].'"> <label for="'.$i.'_'.$field['slug'].'">'.$field['label'].'</label></div>'."\r\n";
						}
					$form_fields .= '
					</div>
					';
					break;
				case 'select':
					$form_fields .= '
					<div class="select">
						<label for="'.$field['slug'].'">'.$field['label'].'</label>
						<select name="'.$field['slug'].'" id="'.$field['slug'].'">'."\r\n";
						foreach($field['options'] as $key => $val) {
							$form_fields .= '<option value="'.$key.'">'.$val.'</option>'."\r\n";
						}
					$form_fields .= '
						</select>
					</div>
					';
					break;
				case 'multiselect':
					$form_fields .= '
					<div class="multiselect">
						<label for="'.$field['slug'].'">'.$field['label'].'</label>
						<select multiple name="'.$field['slug'].'" id="'.$field['slug'].'">'."\r\n";
						foreach($field['options'] as $key => $val) {
							$form_fields .= '<option value="'.$key.'">'.$val.'</option>'."\r\n";
						}
					$form_fields .= '
						</select>
					</div>
					';
					break;
			}
		}
		?>
		<?php if($psc_error) { echo '<div id="psc_alert">Sorry, but your submission could not be saved.</div>'; } ?>
		<form <?php if($file) { echo 'enctype="multipart/form-data" '; } ?>name="psc_user_news" class="psc_user_submission" id="psc_form_<?php echo $res[0]->slug ?>" action="<?php echo $form_uri ?>" method="post">
			<?php
			echo '<input type="hidden" name="psc_form_id" id="psc_form_id" value="'.$id.'" />';
		
			if(function_exists('wp_nonce_field')) {
				wp_nonce_field('psc_nonce_field', 'psc_add');
			}
			
			echo $form_fields;
			
			// Captcha ?
			?>
			<div class="submit">
				<input type="submit" value="Submit" />
			</div>
		</form>
		<?php
	} else if(count($res)==0) {
		echo 'Sorry, but there isn\'t a form with that ID.';
	} else {
		echo 'Sorry, but we got more than 1 form back.';
	}
}


/**
 * Saves user submitted form to database
 *
 * @global object $wpdb
 * @param array $psc
 */
function psc_create_post($content='', $post_category=1, $status='pending', $post_type='post') {
	global $wpdb, $psc;
	
	$hash = gen_uuid();
	
	$post = array(
		'post_author' => 1, // Admin //The user ID number of the author.
		'post_category' => $post_category, // Get ID os publicly_submitted_content //Add some categories.
		'post_content' => $content, // if a field in the form maps_as 'content' //The full text of the post.
		'post_name' => $hash, // The name (slug) for your post
		'post_parent' => 0, //Sets the parent of the new post.
		'post_status' => $status, //Set the status of the new post. 
		'post_title' => $hash, //The title of your post.
		'post_type' => $post_type //You may want to insert a regular post, page, link, a menu item or some custom post type
	);
	$wp_error = false;
	$post_id = wp_insert_post($post, $wp_error);
	
	if($wp_error) {
		return false;
	} else {
		return $post_id;
	}
}

function gen_uuid() {
	return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
		// 32 bits for "time_low"
		mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
		
		// 16 bits for "time_mid"
		mt_rand( 0, 0xffff ),
		
		// 16 bits for "time_hi_and_version",
		// four most significant bits holds version number 4
		mt_rand( 0, 0x0fff ) | 0x4000,
		
		// 16 bits, 8 bits for "clk_seq_hi_res",
		// 8 bits for "clk_seq_low",
		// two most significant bits holds zero and one for variant DCE1.1
		mt_rand( 0, 0x3fff ) | 0x8000,
		
		// 48 bits for "node"
		mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
	);
}

function psc_save_attachment($info, $post_id) {
	// make sure it's png, jpg, or gif
	
	$wp_filetype = wp_check_filetype(basename($filename), null );
	$attachment = array(
		'post_mime_type' => $wp_filetype['type'],
		'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
		'post_content' => '',
		'post_status' => 'inherit'
	);
	$attach_id = wp_insert_attachment( $attachment, $filename, $post_id );
	// you must first include the image.php file
	// for the function wp_generate_attachment_metadata() to work
	require_once(ABSPATH . "wp-admin" . '/includes/image.php');
	$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
	wp_update_attachment_metadata( $attach_id,  $attach_data );
	// modify the the $psc array's options
}

?>