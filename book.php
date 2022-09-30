<?php
/**
 * @wordpress-plugin
 * Plugin Name:       Book List
 * Description:       A plugin to manage a book list/post.
 * Version:           0.1
 * Author:            Ionut .T
 */


//hooks executed by my plugin
add_action( 'wp_enqueue_scripts', 'book_enqueue_scripts' );
add_action( 'init', 'rest_api_init' );
add_action( 'rest_api_init', 'book_register_rest_routes' );
add_shortcode( 'book', 'book_load_short_code' );

/**
 * Registers the scripts from the book plugin
 * @return void
 */
function book_enqueue_scripts() {
	wp_enqueue_script(
		'book-view-app',
		plugins_url( 'views/app.js', __FILE__ ),
		array( 'book-collection', 'book-view-view', 'backbone' ),
		0.1,
		true
	);
	wp_enqueue_script( 'book-model', plugins_url( 'models/book.js', __FILE__ ), array( 'backbone' ), 0.1, true );
	wp_localize_script(
		'book-model',
		'Book_Info',
		array( 'post_id' => get_the_ID() )
	);

	wp_enqueue_script(
		'book-collection',
		plugins_url( 'collections/list.js', __FILE__ ),
		array(
			'book-model',
			'backbone',
		),
		0.1,
		true
	);

	include( __DIR__ . '/class-bookservice.php' );
	$data = BookService::get_items( get_the_ID() );

	wp_localize_script(
		'book-collection',
		'Book_Info',
		array(
			'post_id' => get_the_ID(),
			'data'    => $data,
		)
	);

	if ( current_user_can( 'edit_posts' ) ) {
		wp_enqueue_script( 'book-view-view', plugins_url( 'views/book.js', __FILE__ ), array(), 0.1, true );
	} else {
		wp_enqueue_script( 'book-view-view', plugins_url( 'views/book-no-edit.js', __FILE__ ), array(), 0.1, true );
	}
	wp_enqueue_script( 'book', plugins_url( 'book.js', __FILE__ ), array(), 0.1, true );
}

/**
 * Creates a rest controller instance and registers the routes
 * @return void
 */
function book_register_rest_routes() {
	include( __DIR__ . '/class-bookrestcontroller.php' );
	$controller = new BookRESTController();
	$controller->register_routes();
}

/**
 * Return a shortcode depending on the user rights<br>
 * The shortcode is either an empty table or empty table and create form
 * @return false|string The proper HTML depending on the user rights as string
 */
function book_load_short_code() {
	$file = 'shortcode.html';
	if ( current_user_can( 'edit_posts' ) ) {
		$file = 'shortcode_editor.html';
	}
	ob_start();
	include __DIR__ . '/utils/' . $file;
	return ob_get_clean();
}
