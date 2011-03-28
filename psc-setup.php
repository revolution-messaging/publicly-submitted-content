<?php


/*
 * Activates WP plugin
 *
 * @global object $wpdb
 * @global object $psc
 * @returns bool
 */
function psc_activate() {
	global $wpdb, $psc;
	
	/* Updgrade Portion */
	// $installed_ver = get_option( "psc_db_version" );
	// 
	// if( $installed_ver != $psc->database_version ) {
	// 	// do DB updates here
	// 	update_option("psc_db_version", $psc->database_version );
	// }
	
	$charset_collate = '';
	if($wpdb->has_cap( 'collation')) {
		if(!empty( $wpdb->charset))
			$charset_collate = " DEFAULT CHARACTER SET $wpdb->charset";
		if(!empty( $wpdb->collate))
			$charset_collate .= " COLLATE $wpdb->collate";
	}
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
	if($wpdb->get_var('SHOW TABLES LIKE "'.$psc->forms.'"') != $psc->forms) {
		dbDelta('CREATE TABLE '.$psc->forms.'(
			id int unsigned auto_increment not null,
			name varchar(30) not null,
			slug varchar(30) not null,
			thanks_url varchar(255) not null,
			captcha tinyint(1) default 0,
			data text,
			default_category bigint(20) unsigned,
			default_status varchar(20) default "pending",
			PRIMARY KEY  (id))');
		// default_template varchar(20) null,
	}
	
	/* check with array_key_exists and then make "fields" a serialized array with lots of subsettings:
	 * 		label
	 * 		id/name <-- same diff
	 * 		type
	 * 		value (if set, validate against this for possible values for the input. also, if type==select, radio, or check, output based on this.)
	 */
	
	$cat_array = array('cat_ID' => 0,
		'cat_name' => 'Publicly Submitted Content',
		'category_description' => '',
		'category_nicename' => 'publicly_submitted_content' ,
		'category_parent' => ''
	);
	
	wp_insert_category($cat_array);
	add_option('psc_recaptch_public_key', '', '', 'yes');
	add_option('psc_recaptch_private_key', '', '', 'yes');
	add_option('psc_plugin_version', $psc->version, '', 'no');
	add_option('psc_category_slug', $psc->category_slug, '', 'no');
	add_option('psc_db_version', $psc->database_version, '', 'no');
	
	return true;
}

/*
 * Deactivates WP plugin
 *
 * @global object $wpdb
 * @global object $psc
 * @returns bool
 */
function psc_deactivate() {
	global $wpdb, $psc;
	return true;
}

/*
 * Uninstalls WP plugin
 *
 * @global object $wpdb
 * @global object $psc
 * @returns bool
 */
function psc_uninstall() {
	global $wpdb, $psc;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
	if($wpdb->get_var('SHOW TABLES LIKE "'.$psc->forms.'"') == $psc->forms) {
		dbDelta('DROP TABLE '.$psc->forms);
		// default_template varchar(20) null,
	}
	
	delete_option('psc_recaptch_key');
	delete_option('psc_recaptch_auth');
	delete_option('psc_plugin_version');
	delete_option('psc_category_slug');
	delete_option('psc_db_version');
	
	// publicly_submitted_content
	// wp_delete_category($id);
	return true;
}

?>