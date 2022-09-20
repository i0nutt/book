<?php
/**
 * Plugin Name: Book
 * Author: Ionut T.
 */

//hooks executed by my plugin
add_action( 'wp_enqueue_scripts', 'prefix_register_scripts' );
add_action( 'wp_enqueue_scripts', 'prefix_enqueue_scripts' );
add_action( 'init', 'rest_api_init' );
add_action( 'rest_api_init', 'register_rest_routes' );
add_shortcode( 'book', 'load_short_code' );
add_action( 'template_redirect', 'add_id' );

//registers scripts
function prefix_register_scripts() {
	wp_register_script( 'backbone-localstorage', 'C:\wamp64\www\wp-local\wp-includes\js\backbone.js', array( 'backbone' ) );
	wp_register_script(
		'book-view-app',
		plugins_url( 'views/app.js', __FILE__ ),
		array( 'book-collection', 'book-view-view' ),
		0.1,
		true
	);
	wp_register_script( 'book-model', plugins_url( 'models/book.js', __FILE__ ), array(), 0.1, true );
	wp_register_script( 'book-collection', plugins_url( 'collections/list.js', __FILE__ ), array( 'book-model' ), 0.1, true );
	wp_register_script( 'book-view-view', plugins_url( 'views/book.js', __FILE__ ), array(), 0.1, true );
	wp_register_script( 'book', plugins_url( 'book.js', __FILE__ ), array(), 0.1, true );
}

function prefix_enqueue_scripts() {
	wp_enqueue_script( 'backbone-localstorage' );
	wp_enqueue_script( 'book-view-app' );
	wp_enqueue_script( 'book-model' );
	wp_enqueue_script( 'book-collection' );
	wp_enqueue_script( 'book-view-view' );
	wp_enqueue_script( 'book' );
}

//creates a rest controller instance and registers the routes
function register_rest_routes() {
	include( __DIR__ . '/class-bookrestcontroller.php' );
	$controller = new BookRESTController();
	$controller->register_routes();
}

// a shortcode for adding an empty table into the page and a create item form
// which will only be visible if you have the user has the rights to edit content
function load_short_code() {
	if ( is_user_logged_in() ) {
		$user = wp_get_current_user();
		if ( ! isset($user) ) {
			return '';
		}
		$roles = $user->roles;
		if ( ! ( in_array( 'administrator', $roles ) || in_array( 'editor', $roles ) || in_array( 'author', $roles ) ) ) {
			ob_start();
			include __DIR__ . '/utils/shortcode.html';
			return ob_get_clean();
		}
	}
	ob_start();
	include __DIR__ . '/utils/shortcode_editor.html';
	return ob_get_clean();
}

//adds the post id to a hidden input field in order to access it from backbone
function add_id() {
	$page_id = get_the_ID();
	echo '<input type="hidden" id="get_page_id" value="' . $page_id . '">';
}