<?php

function psc_plugin_menu() {
	add_menu_page('Pub Sub Content', 'Pub Sub Content', 8, __FILE__, 'psc_manage_forms');
}

/*
 * Manage the PSC Forms
 *
 * @global object $wpdb
 * @global object $psc
 * @returns bool
 */
function psc_manage_forms() {
	global $wpdb, $psc;
	
	return true;
}

/*
 * Save a PSC Form
 *
 * @global object $wpdb
 * @global object $psc
 * @returns bool
 */
function psc_save_form() {
	// $form = array(
	// 	array(
	// 		'label' => 'Name',
	// 		'slug' => 'psc_name',
	// 		'type' => 'text',
	// 		'required' => 'true'
	// 	),
	// 	array(
	// 		'label' => 'Email Address',
	// 		'slug' => 'psc_email_address',
	// 		'type' => 'text',
	// 		'required' => 'true'
	// 		),
	// 	array(
	// 		'label' => 'Mobile Number',
	// 		'slug' => 'psc_mobile',
	// 		'type' => 'text',
	// 		'required' => 'true'
	// 		),
	// 	array(
	// 		'label' => 'Story',
	// 		'slug' => 'psc_story',
	// 		'maps_as' => 'content',
	// 		'type' => 'textarea',
	// 		'required' => 'true'
	// 	),
	// 	array(
	// 		'label' => 'Location',
	// 		'slug' => 'psc_location',
	// 		'type' => 'text',
	// 		'required' => 'true'
	// 	),
	// 	array(
	// 		'label' => 'Latitude',
	// 		'slug' => 'psc_latitude',
	// 		'type' => 'hidden',
	// 		'required' => 'false'
	// 	),
	// 	array(
	// 		'label' => 'Longitude',
	// 		'slug' => 'psc_longitude',
	// 		'type' => 'hidden',
	// 		'required' => 'true'
	// 	),
	// 	array(
	// 		'label' => 'Picture',
	// 		'slug' => 'psc_picture',
	// 		'type' => 'file',
	// 		'required' => 'false'
	// 	)
	// );
}

/*
 * View/edit a PSC Form
 *
 * @global object $wpdb
 * @global object $psc
 * @returns bool
 */
function psc_edit_form() {
	
}


?>