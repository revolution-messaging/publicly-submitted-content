<?php
// session_start();
/*
    Plugin Name: Publicly Submitted Content
    Plugin URI: http://code.walkerhamilton.com/public_submission
    Description: Allows public submission and admin/editor moderation of information/posts
    Version: 1.0
    Author: Walker Hamilton
    Author URI: http://walkerhamilton.com

    Copyright 2011 Revolution Messaging LLC (email: info@revolutionmessaging.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
if (!function_exists('add_action')){
    require_once("../../../wp-config.php");
}

add_shortcode('psc_news_form','psc_show_form');
add_action('admin_menu', 'psc_plugin_menu');
add_action('wp_head', 'psc_head');
add_action('admin_head', 'psc_head');
add_action('init', 'init_method');
register_activation_hook(__FILE__,'psc_install_news');

$func = 'psc_'.$_POST['action'].'_story';

if(function_exists($func)){
	call_user_func($func, $_POST['id']);
} else {
	switch($_SERVER['REQUEST_METHOD']){
		case isset($_POST['psc_save']):
			psc_update_content($_POST);
			break;
		case isset($_POST['psc_publish']):
			psc_publish_content($_POST['psc_story_id']);
			break;
		case isset($_POST['psc_captcha_code']):
			psc_add_content($_POST);
			break;
	}
}

function init_method(){
	wp_enqueue_script('jquery');
}

function psc_plugin_menu(){
	add_menu_page('Community Submitted News', 'Community Submitted News', 8, __FILE__, 'psc_read_news');
}

/**
 * Gets story that was submitted by using the id of the story
 *
 * @global wpdb object $wpdb
 * @param string $id
 * @return array
 */
function psc_get_story($id){
	global $wpdb;
	$tablename = $wpdb->prefix.'psc_submission';
	$query = 'SELECT * FROM '.$tablename.' WHERE id = '.$id;
	
	return $wpdb->get_results($query);
}
/**
 * Displays story in editor for it to be edited
 *
 * @param string $id - ID of story
 * 
 */
function psc_view_story($id){
	$story = psc_get_story($id);
	$uri = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'].'?'.$_GET['page'];
	if(isset($_POST['psc_publish'])) {
		echo '<div id="message" class="updated fade"><p>This story has been published. To edit further go to the Edit Posts page or return to the <a href="'.$_SERVER["HTTP_REFERER"].'">View Stories</a> page to publish more stories</p></div>';
	} else if(isset($_POST['psc_save'])) {
		echo '<div id="message" class="updated fade"><p>This story has been updated</p></div>';
	}
?>
	<div id='poststuff'>
		<div id="psc_submission_content" class='wrap'>
			<h2>Edit Submission</h2>
			<form id='psc_story' name='csn' action='' method='post'>
				<input type='hidden' name='psc_story_id' value='<?php echo $story[0]->id ?>' />
				<?
				add_filter('user_can_richedit', create_function ('$a', 'return false;') , 50);	// Disable visual editor
				the_editor(stripslashes($story[0]->story), 'content', '', false, 5);
				add_filter('user_can_richedit', create_function ('$a', 'return true;') , 50);	// Enable visual editor
				?>
				<div id='psc_submission_details' class='postbox stuffbox'>
					<h3>Submission details</h3>
					<?php
					// Output story here
					
					?>
				</div>
			</form>
		</div>
	</div>
<?
}

/**
 * Includes the javascript  (let's not actually make this JS, right now)
 */
/* function psc_head() {
?>
 		<script type="text/javascript" src="<?php echo get_option('siteurl').'/wp-content/plugins/community-submitted-news/psc_js.js' ?>"> </script>
<?
}
*/

/**
 * Gets and displays stories from database and allows admin to publish or edit stories
 *
 * @global object $wpdb
 */
function psc_read_news(){
	if(isset($_GET['action'])) {
		switch($_GET['action']) {
			case 'psc_view':
				psc_view_story($_GET['id']);
				break;
		}
	} else {
		global $wpdb;
		$category = get_category_by_slug(get_option('psc_category_slug'));        
		$tablename = $wpdb->prefix.'psc_submission';
		$query = 'SELECT * FROM '.$tablename.' WHERE approve = 0';
		$news = $wpdb->get_results($query);
		$page = $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

		echo '<div id="psc_msg" class="updated fade"></div>';
		echo '<table class="widefat post fixed" id = "csn">';
		echo '<thead>';
		echo '<tr><th class="name">Name</th><th class="title">Title</th><th class="story">Story</th><th class="action">View Story</th><th class="action">Publish Story</th><th class="action">Remove Story</th></tr><tbody>';
		foreach($news as $story){
			echo '<tr class="row">';
			echo '<td>'.$story->name.'</td>';
			echo '<td>'.$story->title.'</td>';
			echo '<td>'.$story->story.'</td>';
			echo '<td><a href="http://'.$page.'&action=psc_view&id='.$story->id.'">View</a></td>';
			echo '<td><a class="publish" id="'.$story->id.'" href="http://'.$page.'&action=psc_publish&id='.$story->id.'">Publish</a></td>';
			echo '<td><a class="delete" id="'.$story->id.'" href="http://'.$page.'&action=psc_remove&id='.$story->id.'">Remove</a></td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
	}
}

/**
 * Saves user submitted form to database
 *
 * @global object $wpdb
 * @param array $psc
 */
function psc_add_content($psc) {
	global $wpdb;
	// re:Captcha here
	// include_once ABSPATH.'wp-content/plugins/community-submitted-news/securimage/securimage.php';
	if(!function_exists('check_admin_referer')){
		require_once(ABSPATH."wp-includes/pluggable.php");
	}
	$tablename = $wpdb->prefix.'psc_submission';
	// upload the image here
	// modify the the $psc array's options
	$params = array(
		'data' => serialize($psc),
		'approve' => '0'
	);
	if(empty($_POST) || !wp_verify_nonce($_POST['name_of_nonce_field'],'name_of_my_action')) {
		print 'Sorry, there was a security error with your submission.';
		exit;
	} else {
		$wpdb->insert($tablename, $params);
}

/**
 * Updates a user submitted story
 *
 * @global object $wpdb
 * @param array $csn
 */
function psc_update_news($csn) {
	global $wpdb;
	
	$tablename = $wpdb->prefix.'psc_submission';
	$params = array(
		'name' => $csn['psc_user_name'],
		'story' => $csn['content'],
	);
	$where = array('id' => $csn['psc_story_id']);
	$wpdb->update($tablename, $params, $where);
}

/**
 * Installs plugin
 *
 * @global object $wpdb
 */
function psc_install_news(){
	global $wpdb;
	$tablename = $wpdb->prefix.'psc_submission';
	$pref_tablename = $wpdb->prefix.'psc_forms';
	$category_table = $wpdb->prefix.'terms';
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		$sql = 'CREATE TABLE '.$tablename.'(
				id int unsigned auto_increment not null,
				form_id int unsigned not null,
				data text,
				approve int(1) unsigned,
				PRIMARY KEY (id)
			)';
		
		$sql = 'CREATE TABLE '.$pref_tablename.'(
				id int unsigned auto_increment not null,
				name varchar(100),
				slug varchar(100),
				fields text,
				submit_value varchar(50),
				PRIMARY KEY (id)
			)';
		
		/* check with array_key_exists and then make "fields" a serialized array with lots of subsettings:
		 * 		label
		 * 		id/name <-- same diff
		 * 		type
		 * 		value (if set, validate against this for possible values for the input. also, if type==select, radio, or check, output based on this.)
		 */
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
		$cat_array = array('cat_ID' => 0,
			'cat_name' => 'Publicly Submitted Content',
			'category_description' => '',
			'category_nicename' => 'publicly_submitted_content' ,
			'category_parent' => ''
		);
		
		wp_insert_category($cat_array);
		add_option('psc_plugin_version', '1.0');
		add_option('psc_category_slug', 'publicly_submitted_content');
	}
}

/**
 * Shows form used to submit story
 */
function psc_show_form($form_id){
	// $uri = explode('/', $_SERVER['REQUEST_URI']);
	
	// There's gotta be a better way of posting a form on the front-end.
	// $new_uri = 'http://'.$_SERVER['SERVER_NAME'].'/'.$uri[1].'/wp-content/plugins/public-submission/public-submission.php';
	
	// $image_uri = 'http://'.$_SERVER['SERVER_NAME'].'/'.$uri[1].'/';
	
	// Get the Form from the DB
	
	// Loop through and generate the elements
	
	?>
	<div id="psc_alert"></div>
	<form name="psc_user_news" class="psc_user_submission" id="psc_form_<?php $form['slug'] ?>" action="<?php echo $form_uri ?>" method="post">
		<?php
		if(function_exists('wp_nonce_field')){
			wp_nonce_field('psc_add', 'psc_nonce_field');
		}
		foreach($fields as $field) {
			switch($field['type']) {
				case 'text':
					echo '
						<div class="input">
							<label for="psc_'.$field['name'].'">'.$field['label'].'</label>
							<input type="text" name="psc_'.$field['name'].'" id="psc_'.$field['name'].'" />
						</div>
					';
					break;
				case 'hidden':
					echo '<input type="hidden" name="psc_'.$field['name'].'" id="psc_'.$field['name'].'" value="psc_'.$field['value'].'" />';
					break;
				case 'password':
					echo '
					<div class="password">
						<label for="psc_'.$field['name'].'">'.$field['label'].'</label>
						<input type="password" name="psc_'.$field['name'].'" id="psc_'.$field['name'].'" />
					</div>
					';
					break;
				case 'textarea':
					echo '
					<div class="textarea">
						<label for="psc_'.$field['name'].'">'.$field['label'].'</label>
						<textarea name="psc_'.$field['name'].'" id="psc_'.$field['name'].'"></textarea>
					</div>
					';
					break;
				case 'radio':
					echo '
					<div id="psc_'.$field['name'].'" class="radiogroup">'."\r\n";
						$i = 0;
						foreach($field['options'] as $key => $val) {
							$i++;
							echo '<div class="radio"><input type="radio" name="psc_'.$field['name'].'" id="psc_'.$i.'_'.$field['name'].'" class="psc_'.$field['name'].'"> <label for="psc_'.$i.'_'.$field['name'].'">'.$field['label'].'</label></div>'."\r\n";
						}
					echo '
					</div>
					';
					break;
				case 'select':
					echo '
					<div class="select">
						<label for="psc_'.$field['name'].'">'.$field['label'].'</label>
						<select name="psc_'.$field['name'].'" id="psc_'.$field['name'].'">'."\r\n";
						foreach($field['options'] as $key => $val) {
							echo '<option value="'.$key.'">'.$val.'</option>'."\r\n";
						}
					echo '
						</select>
					</div>
					';
					break;
				case 'multiselect':
					echo '
					<div class="multiselect">
						<label for="psc_'.$field['name'].'">'.$field['label'].'</label>
						<select multiple name="psc_'.$field['name'].'" id="psc_'.$field['name'].'">'."\r\n";
						foreach($field['options'] as $key => $val) {
							echo '<option value="'.$key.'">'.$val.'</option>'."\r\n";
						}
					echo '
						</select>
					</div>
					';
					break;
			}
		}
		// Captcha ?
		?>
		<div class="submit">
			<input type="submit" value="Submit" />
		</div>
	</form>
	<?php
}

/**
 * Publishes user submitted story to wordpress posts table
 *
 * @global object $wpdb
 * @global object $wp_rewrite
 * @param string $id
 */
function psc_publish_story($id){
	require (ABSPATH . WPINC . '/pluggable.php');
	global $wpdb, $wp_rewrite;
	$category = get_category_by_slug(get_option('psc_category_slug'));
	$tablename = $wpdb->prefix.'posts';
	$story = psc_get_story($id);
	$wp_rewrite->feeds = array('no');
	$params = array(
		"post_author" => $current_user->ID,
		"post_content" => $story[0]->story,
		"post_title" => $story[0]->title,
		"post_category" => array($category->term_id),
		"post_status" => 'publish',
		"post_type" => 'post'
	);
	
	if(wp_insert_post($params)){
		$wpdb->update($wpdb->prefix.'psc_submission', array('approve' => 1), array('id' => $id));
		//psc_email_submitter($id);
		if($_GET['action'] != 'psc_view'){
			echo 'The story has been published. You may now edit it from Edit Posts page';
		}
	} else {
		echo 'Something has not gone well. Please try again';
	}
}

/**
 * Deletes story that was not yet published
 *
 * @param string $id
 */
function psc_delete_story($id){
	global $wpdb;
	$wpdb->query($wpdb->prepare('DELETE FROM `'.$wpdb->prefix.'psc_submission` WHERE id = %d', $id));
	
	echo 'The submission was deleted';
}

/**
 * Emails person who submitted story to tell their submission has been published
 *
 * @param string $id
 * @return boolean
 */
function psc_email_submitter($id, $action='approve'){
	$story = psc_get_story($id);
	$story_data = unserialize($story[0]->data);
	if(isset($story_data->email)) {
		$blogname = get_option('blogname');
		$message = 'Hello '.$name;
		if($action == 'approve'){
			$message = <<<MSG
The story you submitted to $blogname has been approved and published.

$blogname
MSG;
			$to = $story[0]->email;
			$subject = 'News story published at ';
			wp_mail($to, $subject, $message);
		}
	}
}
?>