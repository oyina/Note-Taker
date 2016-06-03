<?php
/*
Plugin Name: Note Taker
Plugin URI:  http://oyindesigns.com
Description: Takes Notes on a page and saves them with ajax
Version:     0.0.1
Author:      Oyin Abatan
Author URI:  http://oyindesigns.com
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Calls Notes if Template Pages
function nt_course_note_call() {

	// only show logged-in members on learn dash pages
	$post_type = get_post_type();
	$types = array( 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-assignment');

	if (is_user_logged_in()){

		if( in_array( $post_type, $types ) ) {
		  return nt_course_note_entry_field();
		} else {
			echo "<h1>THIS ISNT LEARN DASH</h1>";
		}

	}
	else {
	    echo '<h1>NOT LOGGED</h1>';
	}

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
$current_post_type = get_post_type();

//Checks if note exists and changes title and body variables accordingly
$args = array(
	'post_type'  => 'coursenote',
	'post_status' => array('draft'),
	'meta_query' => array(
		//'relation' => 'AND',
		array(
			'key'     => 'nt-note-current-lessson-id',
			'value'   => $current_lesson_id,
			'compare' => '=',
		)
	),
	 'author' => $current_user
);

$the_query = new WP_Query( $args );

if ($the_query->have_posts()){
 while ( $the_query->have_posts() ) : $the_query->the_post();

 $title = get_the_title();
 $body = get_the_content();

 endwhile;
 wp_reset_postdata();
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
					<input type="hidden" name="nt-note-current-post-type" id="nt-note-current-post-type" value="<?php echo $current_post_type; ?>">
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

//Genereate full title for course, lessson, topic
function nt_generate_course_title($course_type,$active_id){
	if( $course_type == 'sfwd-courses' ) {

		$title = get_the_title($active_id);

	}

	if( $course_type == 'sfwd-lessons' ) {

		$course_id 		= 	get_post_meta( $active_id , 'course_id' , true );
		$course_title 	= 	get_the_title( $course_id );
		$lesson_title = get_the_title( $active_id );

		$title = $course_title.': '.$lesson_title;

	}

	if( $course_type == 'sfwd-topic' ) {

		$course_id 		= 	get_post_meta( $active_id , 'course_id' , true );
		$course_title 	= 	get_the_title( $course_id );

		$lesson_id		=	get_post_meta( $active_id , 'lesson_id' , true );
		$lesson_title	=	get_the_title( $lesson_id );

		$topic_title =  get_the_title( $active_id);

		$title = $course_title.': '.$lesson_title.': '.$topic_title;
	}

	return $title;
}

//AJAX - Submits Note and Saves extra fields to Postmeta
function process_course_note() {
	if ( ! empty( $_POST[ 'submission' ] ) ) {
		wp_send_json_error( 'Honeypot Check Failed' );
	}
	if ( ! check_ajax_referer( 'nt-course-note-nonce', 'security' ) ) {
		wp_send_json_error( 'Security Check failed' );
	}
	$course_title = nt_generate_course_title($_POST[ 'data' ][ 'currentPostType' ] ,$_POST[ 'data' ][ 'currentLessonId' ]);
	$notes_data = array(
		'post_title' => $course_title.' - '.
			/*sanitize_text_field( $_POST[ 'data' ][ 'userId' ] ),
			sanitize_text_field( $_POST[ 'data' ][ 'currentLessonId' ] ),*/

			sanitize_text_field( $_POST[ 'data' ][ 'title' ] ),
		'post_status' => 'draft',
		'post_type' => 'coursenote',
		'post_content' => wp_kses_post( $_POST[ 'data' ][ 'body' ] )
	);



//If note id already exists update exisiting note else insert new note
$note_Id_update = get_post_id_by_meta_key_and_value('nt-note-current-lessson-id', $_POST[ 'data' ][ 'currentLessonId' ]);
$post_author = get_post_field( 'post_author', $note_Id_update );

	if($note_Id_update && ($post_author == $_POST[ 'data' ][ 'userId' ])){

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


// create shortcode to list all notes
function nt_mass_listing_shortcode( $atts ) {
    ob_start();
		$current_user = get_current_user_id();


		//Admin and editor users can view all notes
		if(current_user_can('edit_posts')) {
			$args = array(
					'post_type' => 'coursenote',
					'posts_per_page' => -1,
					'post_status' => array('draft'),
					'order' => 'ASC',
					'orderby' => 'title',

			);
		}
		//Viewer can only see their notes
		else {
			$args = array(
					'post_type' => 'coursenote',
					'posts_per_page' => -1,
					'post_status' => array('draft'),
					'order' => 'ASC',
					'orderby' => 'title',
					'author__in' => $current_user
			);
		}



    $query = new WP_Query($args);
    if ( $query->have_posts() ) { ?>
        <ul class="notes-listing">
            <?php while ( $query->have_posts() ) : $query->the_post(); ?>
            <li id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </li>
            <?php endwhile;
            wp_reset_postdata(); ?>
        </ul>
    <?php $nt_mass_list = ob_get_clean();
    return $nt_mass_list;
    }
}
add_shortcode( 'list_all_notes', 'nt_mass_listing_shortcode' );
