<?php
/*
    Plugin Name: Publicly Submitted Content
    Plugin URI: http://code.walkerhamilton.com/publicly_submitted_content
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
$GLOBALS['psc'] = (object) array(
	'forms' => $GLOBALS['wpdb']->prefix . 'psc_forms',
	'database_version' => '1.0',
	'category_slug' => 'publicly_submitted_content',
	'version' => '1.0');

/* My Logging Function */
function logit($var, $export=true) {
	if($export) {
		error_log(var_export($var, true));
	} else {
		error_log($var);
	}
}

include_once(ABSPATH.'wp-content/plugins/publicly-submitted-content/psc-setup.php');
include_once(ABSPATH.'wp-content/plugins/publicly-submitted-content/psc-manage.php');
include_once(ABSPATH.'wp-content/plugins/publicly-submitted-content/psc-functions.php');
include_once(ABSPATH.'wp-content/plugins/publicly-submitted-content/psc-frontend.php');

if (!function_exists('add_action')){
	require_once("../../../wp-config.php");
}

add_shortcode('psc_show_form','psc_show_form');
add_action('admin_menu', 'psc_plugin_menu');
// add_action('wp_head', 'psc_head');
// add_action('admin_head', 'psc_head');
// add_action('init', 'init_method');
register_activation_hook(__FILE__, 'psc_activate');
register_deactivation_hook(__FILE__, 'psc_deactivate');
register_uninstall_hook(__FILE__, 'psc_uninstall');

// if(isset($_POST['action'])) {
// 	$func = 'psc_'.$_POST['action'].'_story';
// 	
// 	if(function_exists($func)){
// 		call_user_func($func, $_POST['id']);
// 	} else {
// 		switch($_SERVER['REQUEST_METHOD']){
// 			case isset($_POST['psc_save']):
// 				psc_update_content($_POST);
// 				break;
// 			case isset($_POST['psc_publish']):
// 				psc_publish_content($_POST['psc_story_id']);
// 				break;
// 			case isset($_POST['psc_add']):
// 				psc_add_content($_POST);
// 				break;
// 		}
// 	}
// }


// class PubliclySubmittedContent {
// 	function __construct() {
// 		// wp_enqueue_script('jquery');
// 	}


?>