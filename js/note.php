<?php
/*
Plugin Name: Note Taker
Plugin URI:  http://oyindesigns.com
Description: Takes Notes on a page and saves tehm with ajax
Version:     0.0.1
Author:      Oyin Abatan
Author URI:  http://oyindesigns.com
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Calls Notes if Template Pages
function nt_course_note_call() {

	// only show the registration form to non-logged-in members
	if(!is_user_logged_in()) {

		// check to make sure user registration is enabled
		//$registration_enabled = get_option('users_can_register');

		/* only show the registration form if allowed
		if($registration_enabled) {
			$output = pippin_registration_form_fields();
		} else {
			$output = __('User registration is not enabled');
		}*/

	}
  $output = nt_course_note_entry_field();
  return $output;
}

add_action('wp_footer', 'nt_course_note_call');

//Registers Course Notes as custom post type
function nt_register_course_note_create_type() {
	$post_labels = array(
		'name' 			    => 'Course Notes',
		'singular_name' 	=> 'Course Notes',
		'add_new' 		    => 'Add New',
		'add_new_item'  	=> 'Add New Note',
		'edit'		        => 'Edit',
		'edit_item'	        => 'Edit Course Note',
		'new_item'	        => 'New Course Note',
		'view' 			    => 'View Course Note',
		'view_item' 		=> 'View Course Note',
		'search_term'   	=> 'Search Notes',
		'parent' 		    => 'Parent Course Note',
		'not_found' 		=> 'No Notes Found',
		'not_found_in_trash' 	=> 'No Notes in Trash'
	);

	register_post_type( 'coursenote', array(
		'labels' => $post_labels,
		'public' => true,
		'has_archive' => true,
		'supports' => array( 'title', 'editor', 'thumbnail','page-attributes' ),
		'taxonomies' => array( 'post_tag', 'category' ),
		'exclude_from_search' => false,
		'capability_type' => 'post',
		'rewrite' => array( 'slug' => 'Course Notes' ),
	)
	 );

}
add_action( 'init', 'nt_register_course_note_create_type' );


//Adds Course Note taxonomies
function nt_regsiter_taxonomy() {
	$labels = array(
		'name'              => 'Course Note Categories',
		'singular_name'     => 'Course Note Category',
		'search_items'      => 'Search Course Notes Categories',
		'all_items'         => 'All Course Note Categories',
		'edit_item'         => 'Edit Course Note Category',
		'update_item'       => 'Update Course Note Category',
		'add_new_item'      => 'Add New Course Note Category',
		'new_item_name'     => 'New Course Note Category',
		'menu_name'         => 'Course Note Categories'
	);
	// register taxonomy
	register_taxonomy( 'coursenotecat', 'coursenote', array(
		'hierarchical' => true,
		'labels' => $labels,
		'query_var' => true,
		'show_admin_column' => true
	) );
}

add_action('init', 'nt_regsiter_taxonomy');

//Retreives Post Id from Meta Key
function get_post_id_by_meta_key_and_value($key, $value) {
		global $wpdb;
		$meta = $wpdb->get_results("SELECT * FROM `".$wpdb->postmeta."` WHERE meta_key='".$wpdb->escape($key)."' AND meta_value='".$wpdb->escape($value)."'");
		if (is_array($meta) && !empty($meta) && isset($meta[0])) {
			$meta = $meta[0];
		}
		if (is_object($meta)) {
			return $meta->post_id;
		}
		else {
			return false;
		}
	}

//Prints Note field in front end and retieves exisintg note as placeholder
function nt_course_note_entry_field() {
	global $post;

//ID's
$current_user = get_current_user_id();
$current_lesson_id = $post->ID;
$note_id = get_post_id_by_meta_key_and_value('nt-note-current-lessson-id', $current_lesson_id);

//Checks if note exists and displays in note box for editing
if($note_id){
	$queried_post = get_post($note_id);
	$title = $queried_post->post_title;

	$body = $queried_post->post_content;
} else {
	$title = 'Note Title';

	$body = 'Enter Lesson Notes here';
}



?>

  <div id="nt_note_cont" class="note-container">
    <div class="note-header">
      <div class="note-header-title">
        <?php _e('Notes'); ?>
				<div id="apf-response"></div>
      </div>
      <div class="note-header-actions">

      </div>
    </div>
    <div class="note-body">
      <form id="nt-course-note" action="" method="post">
					<?php wp_nonce_field( basename(__FILE__), 'nt-course-note-nonce') ?>
					<input type="text" name="nt-note-title" id="nt-note-title" value="<?php echo $title; ?>" placeholder="" >
					<input type="hidden" name="nt-note-user-id" id="nt-note-user-id" value="<?php echo $current_user; ?>">
					<input type="hidden" name="nt-note-current-lesson-id" id="nt-note-current-lessson-id" value="<?php echo $current_lesson_id; ?>">

					<textarea rows="8"  name="nt-note-body" id="nt-note-body" class="" placeholder=""/><?php echo $body; ?></textarea>
					<input type="text" id="xyz" name="<?php echo apply_filters( 'honeypot_name', 'date-submitted') ?>" value="" style="display:none">
        <input type="submit" id="nt-note-submit" value="<?php _e('Save Notes'); ?>"/>
      </form>

    </div>

  </div>
   <?php

}



// Enqueue CSS
function nt_enqueue_css() {
  wp_enqueue_style( 'course-note-taker-css', plugins_url( '/css/note.css', __FILE__ ) );
}
add_action('init', 'nt_enqueue_css');

// Enqueue JS
function nt_enqueue_scripts() {
  wp_enqueue_script('jquery-ui-draggable');
  wp_enqueue_script( 'nt-notes', plugins_url( '/js/nt_notes.js', __FILE__ ) );
	wp_localize_script('nt-notes', 'nt_ajax_call', array(
		'adminAjax' => admin_url('admin-ajax.php'),
		'security' => wp_create_nonce( 'nt-course-note-nonce')
	));
}
add_action('wp_enqueue_scripts','nt_enqueue_scripts');

//AJAX - Submits Note and Saves extra fields to Postmeta
function process_course_note() {
	if ( ! empty( $_POST[ 'submission' ] ) ) {
		wp_send_json_error( 'Honeypot Check Failed' );
	}
	if ( ! check_ajax_referer( 'nt-course-note-nonce', 'security' ) ) {
		wp_send_json_error( 'Security Check failed' );
	}
	$notes_data = array(
		'post_title' => sprintf( '%s>%s>%s',
			sanitize_text_field( $_POST[ 'data' ][ 'userId' ] ),
			sanitize_text_field( $_POST[ 'data' ][ 'currentLessonId' ] ),

			sanitize_text_field( $_POST[ 'data' ][ 'title' ] )

		),
		'post_status' => 'draft',
		'post_type' => 'coursenote',
		'post_content' => wp_kses_post( $_POST[ 'data' ][ 'body' ] )
	);

//It note id already exists update exisiting note else insert new note
$note_Id_update = get_post_id_by_meta_key_and_value('nt-note-current-lessson-id', $_POST[ 'data' ][ 'currentLessonId' ]);
	if($note_Id_update){

		$post_id = wp_update_post( array(
			'ID'           => $note_Id_update,
			'post_content' => wp_kses_post( $_POST[ 'data' ][ 'body' ] )
		), true );

		wp_send_json_success( $post_id );
	} else {
		$post_id = wp_insert_post( $notes_data, true );
		if ( $post_id ) {

			update_post_meta( $post_id, 'nt-note-user-id',  $_POST[ 'data' ][ 'userId' ] );
			update_post_meta( $post_id, 'nt-note-current-lessson-id',  $_POST[ 'data' ][ 'currentLessonId' ] );


		}
		wp_send_json_success( $post_id );
	}

}
add_action( 'wp_ajax_process_course_note', 'process_course_note' );
add_action( 'wp_ajax_nopriv_process_course_note', 'process_course_note' );
