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
				if($field['required']=='yes' && (!isset($_POST[$field['slug']]) || empty($_POST[$field['slug']])) && $field['type']!='file') {
					$fields[$key]['error'] = true;
					$any_errors = true;
				} else if($field['type'] == 'file') {
						$attachment = array(
							'field' => $field
						);
				} else if(isset($_POST[$field['slug']]) && !empty($_POST[$field['slug']])) {
					// if maps_as content
					if(isset($field['maps_as']) && $field['maps_as']=='content') {
						// add to content var
						$content = $_POST[$field['slug']];
					} else {
						// add to meta save array
						$meta[$field['slug']] = $_POST[$field['slug']];
					}
				} else {
					$meta[$field['slug']] = '';
				}
			}
			if(!$any_errors) {
				// save post
				$post_id = psc_create_post($content, $res[0]->default_category, $res[0]->default_status); // $res[0]->default_post_type (v1.1?)
				
				if($post_id===false) {
					$any_errors = true;
				} else {
					// save attachment
					if(isset($attachment)) {
						$image = insert_attachment($attachment['field']['slug'], $post_id);
						if($image===false) {
							wp_delete_post($post_id, true);
							$any_errors = true;
							foreach($fields as $key => $field) {
								if($field['type']=='file') {
									$fields[$key]['error'] = true;
								}
							}
						}
					}
					
					if($any_errors===false) {
						// save meta
						foreach($meta as $key => $value) {
							add_post_meta($post_id, $key, $value, true);
						}
						
						// clear $_POST
						$_POST = array();
						
						// thanks page, else render the form again with errors
						header('Location: '.get_bloginfo('siteurl').$res[0]->thanks_url);
						exit();
					}
				}
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
						<div class="input';
					if(isset($field['error']) && $field['error']===true) {
						$form_fields .= ' error';
					}
					$form_fields .= '">';
					if(isset($field['error']) && $field['error']===true) {
						$form_fields .= '<div class="errMsg">This field cannot be left blank.</div>';
					}
					$form_fields .= '
							<label for="'.$field['slug'].'">'.$field['label'];
					if($field['required']=='yes') { $form_fields .= ' <span class="required">*</span>'; }
					$form_fields .= '</label>
							<input type="text" name="'.$field['slug'].'" id="'.$field['slug'].'" />
						</div>
					';
					break;
				case 'file':
					$file = true;
					$form_fields .= '
						<div class="file';
						if(isset($field['error']) && $field['error']===true) {
							$form_fields .= ' error';
						}
						$form_fields .= '">';
						if(isset($field['error']) && $field['error']===true) {
							$form_fields .= '<div class="errMsg">This field cannot be left blank.</div>';
						}
						$form_fields .= '
								<label for="'.$field['slug'].'">'.$field['label'];
						if($field['required']=='yes') { $form_fields .= ' <span class="required">*</span>'; }
						$form_fields .= '</label>
							<input type="file" name="'.$field['slug'].'" id="'.$field['slug'].'" />
						</div>
					';
					break;
				case 'hidden':
					$form_fields .= '<input type="hidden" name="'.$field['slug'].'" id="'.$field['slug'].'" value="'.$field['value'].'" />';
					break;
				case 'password':
					$form_fields .= '
					<div class="password	';
						if(isset($field['error']) && $field['error']===true) {
							$form_fields .= ' error';
						}
						$form_fields .= '">';
						if(isset($field['error']) && $field['error']===true) {
							$form_fields .= '<div class="errMsg">This field cannot be left blank.</div>';
						}
						$form_fields .= '
								<label for="'.$field['slug'].'">'.$field['label'];
						if($field['required']=='yes') { $form_fields .= ' <span class="required">*</span>'; }
						$form_fields .= '</label>
					<input type="password" name="'.$field['slug'].'" id="'.$field['slug'].'" />
					</div>
					';
					break;
				case 'textarea':
					$form_fields .= '
					<div class="textarea';
					if(isset($field['error']) && $field['error']===true) {
						$form_fields .= ' error';
					}
					$form_fields .= '">';
					if(isset($field['error']) && $field['error']===true) {
						$form_fields .= '<div class="errMsg">This field cannot be left blank.</div>';
					}
					$form_fields .= '
							<label for="'.$field['slug'].'">'.$field['label'];
					if($field['required']=='yes') { $form_fields .= ' <span class="required">*</span>'; }
					$form_fields .= '</label>
					<textarea name="'.$field['slug'].'" id="'.$field['slug'].'"></textarea>
					</div>
					';
					break;
				case 'radio':
					$form_fields .= '
					<div id="psc_'.$field['slug'].'" class="radiogroup';
					if(isset($field['error']) && $field['error']===true) {
						$form_fields .= ' error';
					}
					$form_fields .= '">';
					if(isset($field['error']) && $field['error']===true) {
						$form_fields .= '<div class="errMsg">You must select an option.</div>';
					}
						$i = 0;
						foreach($field['options'] as $key => $val) {
							$i++;
							$form_fields .= '<div class="radio"><input type="radio" name="'.$field['slug'].'" id="'.$i.'_'.$field['slug'].'" class="'.$field['slug'].'"> <label for="'.$i.'_'.$field['slug'].'">'.$field['label'].'</label></div>'."\r\n";
						}
					if($field['required']=='yes') { $form_fields .= '<div class="required">require</div>'; }
					$form_fields .= '
					</div>
					';
					break;
				case 'select':
					$form_fields .= '
					<div class="select';
						if(isset($field['error']) && $field['error']===true) {
							$form_fields .= ' error';
						}
						$form_fields .= '">';
						if(isset($field['error']) && $field['error']===true) {
							$form_fields .= '<div class="errMsg">This field cannot be left blank.</div>';
						}
						$form_fields .= '
								<label for="'.$field['slug'].'">'.$field['label'];
						if($field['required']=='yes') { $form_fields .= ' <span class="required">*</span>'; }
						$form_fields .= '</label>
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
					<div class="multiselect	';
						if(isset($field['error']) && $field['error']===true) {
							$form_fields .= ' error';
						}
						$form_fields .= '">';
						if(isset($field['error']) && $field['error']===true) {
							$form_fields .= '<div class="errMsg">This field cannot be left blank.</div>';
						}
						$form_fields .= '
								<label for="'.$field['slug'].'">'.$field['label'];
						if($field['required']=='yes') { $form_fields .= ' <span class="required">*</span>'; }
						$form_fields .= '</label>
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
		<form <?php if($file) { echo 'enctype="multipart/form-data" '; } ?>name="psc_user_news" class="psc_user_submission" id="psc_form_<?php echo $res[0]->slug ?>" action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
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
		'post_category' => array($post_category), // Get ID os publicly_submitted_content //Add some categories.
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

function insert_attachment($file_handler, $post_id) {
	// check to make sure its a successful upload
	if ($_FILES[$file_handler]['error'] !== UPLOAD_ERR_OK) return false;
	
	// check that it's a jpeg, gif, or png image
	if(!in_array($_FILES[$file_handler]['type'], array('image/gif', 'image/jpeg', 'image/png'))) return false;
	
	require_once(ABSPATH . "wp-admin" . '/includes/image.php');
	require_once(ABSPATH . "wp-admin" . '/includes/file.php');
	require_once(ABSPATH . "wp-admin" . '/includes/media.php');
	
	$attach_id = media_handle_upload( $file_handler, $post_id );
	$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
	wp_update_attachment_metadata( $attach_id,  $attach_data );
	update_post_meta($post_id,'_thumbnail_id',$attach_id);
	
	return true;
}

?>