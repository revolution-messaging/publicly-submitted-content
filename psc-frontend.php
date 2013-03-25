<?php

/**
 * Shows form used to submit story
 */
function psc_show_form( $atts, $form=true ){
	global $wpdb, $psc, $recaptcha_error;
	
	$psc_error = false;
	
	extract( shortcode_atts( array(
			'id' => null,
			'slug' => null
	), $atts ) );
	
	// Get the Form from the DB
	if($id) {
		$res = $wpdb->get_results('SELECT * FROM '.$psc->forms.' WHERE id="'.$id.'"');
	} else if($slug) {
		$res = $wpdb->get_results('SELECT * FROM '.$psc->forms.' WHERE slug="'.mysql_escape_string($slug).'" LIMIT 0, 1');
	} else {
		echo '<p style="background:pink;border:1px solid red;color:red;padding:5px 10px;">Error, you must provide a slug or id to "psc_show_forms".</p>';
	}
	
	$fields = unserialize($res[0]->data);
	
	$resp = (object) array('is_valid' => true);
	
	if($res[0]->captcha===1) {
		require_once(dirname(__FILE__).'/recaptcha/recaptchalib.php');
		$GLOBALS['recaptcha_error'] = null;
		$GLOBALS['recaptcha_resp'] = null;
		$resp = recaptcha_check_answer(get_option('psc_recaptch_private_key'),
										$_SERVER["REMOTE_ADDR"],
										$_POST["recaptcha_challenge_field"],
										$_POST["recaptcha_response_field"]);
	}
	
	if(count($res) == 1) {
		$any_errors = false;
		if((!isset($_POST['psc_add']) && isset($_GET['psc_priv'])) || (wp_verify_nonce($_POST['psc_add'], 'psc_nonce_field') && $_POST['psc_form_id']==$id))
		{
			if(isset($_POST['wh_priv'])) unset($_POST['wh_priv']);
			$content = '';
			$meta = array();
			foreach($fields as $key => $field) {
				if($field['required']=='true' && (!isset($_POST[$field['slug']]) || empty($_POST[$field['slug']])) && $field['type']!='file') {
					$fields[$key]['error'] = true;
					$any_errors = true;
				} else if($field['type'] == 'file' && isset($_FILES[$field['slug']])) {
					if($_FILES[$field['slug']]['error']==4 && $field['required']=='true') {
						$fields[$key]['error'] = true;
						$any_errors = true;
					} else if($_FILES[$field['slug']]['error']!=0 && $field['required']=='true') {
						$fields[$key]['error'] = true;
						$any_errors = true;
					} else if($_FILES[$field['slug']]['error']==0) {
						$attachment = array(
							'field' => $field
						);
					}
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
			
			if(!$resp->is_valid) {
				$any_errors = true;
				$recaptcha_error = $resp->error;
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
						
						if(!empty($res[0]->thanks_url)) {
							// thanks page, else render the form again with errors
							header('Location: '.get_bloginfo('siteurl').$res[0]->thanks_url);
							exit();
						} else {
							echo '<div id="submissionThankYou">Thanks for your submission! After a short period of moderation, it should appear.</div>';
							$psc_success = true;
						}
					}
				}
			}
		} else if(!empty($_POST) && isset($_POST['psc_add']) && !wp_verify_nonce($_POST['psc_add'], 'psc_nonce_field')) {
			$psc_error = true;
		} else if(!empty($_POST) && !$resp->is_valid) {
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
					if($field['required']=='true') { $form_fields .= ' <span class="required">*</span>'; }
					$form_fields .= '</label>
							<input type="text" name="'.$field['slug'].'" id="'.$field['slug'].'" value="';
					if(isset($_POST[$field['slug']])) {
						$form_fields .= $_POST[$field['slug']];
					}
					$form_fields .= '" />
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
						if(isset($field['error']) && $field['error']===true && $field['required']=='true') {
							$form_fields .= '<div class="errMsg">You must provide a '.$field['label'].'.</div>';
						} else if(isset($field['error']) && $field['error']===true) {
							$form_fields .= '<div class="errMsg">There was a problem uploading your file.</div>';
						}
						
						$form_fields .= '
								<label for="'.$field['slug'].'">'.$field['label'];
						if($field['required']=='true') { $form_fields .= ' <span class="required">*</span>'; }
						$form_fields .= '</label>
							<input type="file" name="'.$field['slug'].'" id="'.$field['slug'].'" />
						</div>
					';
					break;
				case 'hidden':
					$form_fields .= '<input type="hidden" name="'.$field['slug'].'" id="'.$field['slug'].'" value="';
					if(isset($_POST[$field['slug']])) {
						$form_fields .= $_POST[$field['slug']];
					} else if(isset($field['value'])) {
						$form_fields .= $field['value'];
					}
					$form_fields .= '" />';
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
						if($field['required']=='true') { $form_fields .= ' <span class="required">*</span>'; }
						$form_fields .= '</label>
					<input type="password" name="'.$field['slug'].'" id="'.$field['slug'].'" value="';
					if(isset($_POST[$field['slug']])) {
						$form_fields .= $_POST[$field['slug']];
					}
					$form_fields .= '"/>
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
					if($field['required']=='true') { $form_fields .= ' <span class="required">*</span>'; }
					$form_fields .= '</label>
					<textarea name="'.$field['slug'].'" id="'.$field['slug'].'">';
					if(isset($_POST[$field['slug']])) {
						$form_fields .= $_POST[$field['slug']];
					}
					$form_fields .= '</textarea>
					</div>
					';
					break;
				case 'checkbox':
					if(isset($field['options']) && !empty($field['options'])) {
						$form_fields .= '<fieldset id="psc_'.$field['slug'].'" class="checkboxgroup';
						if(isset($field['error']) && $field['error']===true) {
							$form_fields .= ' error">
							<div class="errMsg">You must check at least one.</div>';
						} else {
							$form_fields .= '">';
						}
						$form_fields .= '<legend>'.$field['label'];
						if($field['required']=='true') $form_fields .= '<span class="required">*</span>';
						$form_fields .= '</legend>';
						$i = 0;
						foreach($field['options'] as $val) {
							$i++;
							$form_fields .= '<div class="checkboxoption"><input type="checkbox" value="'.$val.'" name="'.$field['slug'].'['.$i.']['.$val.']" id="option_'.$i.'_'.$field['slug'].'" class="'.$field['slug'].'" /> <label for="option_'.$i.'_'.$field['slug'].'">'.$val.'</label></div>'."\r\n";
						}
						$form_fields .= '</fieldset>';
					} else {
						$form_fields .= '<div id="psc_'.$field['slug'].'" class="checkbox">';
						$form_fields .= '<div class="checkboxoption"><input type="checkbox" name="'.$field['slug'].'" id="'.$field['slug'].'" class="'.$field['slug'].'" /> <label for="'.$field['slug'].'">'.$field['label'].'</label></div>'."\r\n";
						$form_fields .= '</div>';
					}
					break;
				case 'radio':
					$form_fields .= '
					<fieldset id="psc_'.$field['slug'].'" class="radiogroup';
					if(isset($field['error']) && $field['error']===true) {
						$form_fields .= ' error';
					}
					$form_fields .= '"><legend>'.$field['label'];
					if($field['required']=='true') { $form_fields .= '<span class="required">*</span>'; }
					$form_fields .= '</legend>';
					if(isset($field['error']) && $field['error']===true) {
						$form_fields .= '<div class="errMsg">You must select an option.</div>';
					}
					$i = 0;
					foreach($field['options'] as $val) {
						$i++;
						$form_fields .= '<div class="radio"><input type="radio" value="'.$val.'" name="'.$field['slug'].'" id="'.$i.'_'.$field['slug'].'" class="'.$field['slug'].'"> <label for="'.$i.'_'.$field['slug'].'">'.$val.'</label></div>'."\r\n";
					}
					$form_fields .= '</fieldset>';
					break;
				case 'select':
					$form_fields .= '
					<div class="select';
						if(isset($field['error']) && $field['error']===true) {
							$form_fields .= ' error';
						}
						$form_fields .= '">';
						if(isset($field['error']) && $field['error']===true) {
							$form_fields .= '<div class="errMsg">You must select an option.</div>';
						}
						$form_fields .= '
								<label for="'.$field['slug'].'">'.$field['label'];
						if($field['required']=='true') { $form_fields .= ' <span class="required">*</span>'; }
						$form_fields .= '</label>
						<select name="'.$field['slug'].'" id="'.$field['slug'].'">'."\r\n".'
							<option>Select an option...</option>
						';
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
						if($field['required']=='true') { $form_fields .= ' <span class="required">*</span>'; }
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
		
		if($psc_error) { echo '<div id="psc_alert">Sorry, but your submission could not be saved.</div>'; }
		if($form_fields && $form===true) {
		?>
		<form <?php if($file) { echo 'enctype="multipart/form-data" '; } ?>name="psc_user_news" class="psc_user_submission" id="psc_form_<?php echo $res[0]->slug ?>" action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
			<?php
			echo '<input type="hidden" name="psc_form_id" id="psc_form_id" value="'.$id.'" />';
		
			if(function_exists('wp_nonce_field')) {
				wp_nonce_field('psc_nonce_field', 'psc_add');
			}
			
			echo $form_fields;
			
			// Captcha ?
			if($res[0]->captcha==="1") {
				require_once(dirname(__FILE__).'/recaptcha/recaptchalib.php');
				echo recaptcha_get_html(get_option('psc_recaptch_public_key'), $recaptcha_error);
			}
			?>
			<div class="submit">
				<input type="submit" value="Submit" />
			</div>
		</form>
		<?php
		}
	} else if(count($res)==0) {
		echo 'Sorry, but we couldn\'t find a form with ';
		if($slug) { echo 'slug: '.$slug; }
		else if($id) { echo 'id: '.$id; }
		echo '.';
	} else {
		echo 'Sorry, but we got more than 1 form back.';
	}
// 	if($psc_error)
// 		return false;
// 	else if(isset($psc_success))
// 		return 'moderation';
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

function psc_show_value($string) {
	// figure out if we're in the loop
	 
	// get the psc_form_id
	// get the form
	foreach($fields as $field) {
		// if it maps to content, return content raw (no HTML)
		// otherwise, output custom field
	}
}