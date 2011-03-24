<?php

/**
 * Gets story that was submitted by using the id of the story
 *
 * @global wpdb object $wpdb
 * @param string $id
 * @return array
 */
function psc_get_story($id) {
	global $wpdb, $psc;
	$query = 'SELECT * FROM '.$psc->data.' WHERE id = '.$id;

	return $wpdb->get_results($query);
}

/**
 * Includes the javascript  (let's not actually make this JS, right now)
 */
function psc_head() {
	// echo '<script type="text/javascript" src="'.get_option('siteurl').'/wp-content/plugins/community-submitted-news/psc_js.js"></script>';
}


// function psc_save_cleanup($data) {
// 	do_action('psc_data_alter', $data);
	// remove stuff that's going in the table in its own column
	// clean up the data here
// }

?>