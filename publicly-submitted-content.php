<?php
/*
	Plugin Name: Publicly Submitted Content
	Plugin URI: http://code.walkerhamilton.com/publicly_submitted_content
	Description: Allows public submission and admin/editor moderation of information/posts
	Version: 1.0
	Author: Walker Hamilton
	Author URI: http://walkerhamilton.com

	Copyright 2011 Revolution Messaging LLC (email: info@revolutionmessaging.com)
*/
$GLOBALS['psc'] = (object) array(
	'forms' => $GLOBALS['wpdb']->prefix . 'psc_forms',
	'database_version' => '1.0',
	'category_slug' => 'publicly_submitted_content',
	'version' => '1.0'
);

/* My Logging Function */
function logit($var, $export=true) {
	if($export) {
		error_log(var_export($var, true));
	} else {
		error_log($var);
	}
}

include_once(dirname(__FILE__).'/psc-setup.php');
include_once(dirname(__FILE__).'/psc-functions.php');
include_once(dirname(__FILE__).'/psc-frontend.php');

if(strpos($_SERVER['REQUEST_URI'], 'publicly-submitted-content/admin')!==false) {
	include_once(dirname(__FILE__).'/psc-manage.php');
}

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

function psc_plugin_menu() {
	add_menu_page('Pub Sub Content', 'Pub Sub Content', 8, dirname(__FILE__).'/admin', 'psc_manage_forms');
}

// class PubliclySubmittedContent {
// 	function __construct() {
// 		// wp_enqueue_script('jquery');
// 	}


?>