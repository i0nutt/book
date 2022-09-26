<?php

/**
 * Book controller for managing interactions with REST API book items
 */
class BookRestController extends WP_REST_Controller {
	const META_KEY = 'myBookApp';
	private $user_can_edit;
	/**
	 * Initializes the class with a namespace and whether the user can edit as user_can_edit
	 */
	public function __construct() {
		$this->namespace     = 'bookAPI/v1';
		$this->user_can_edit = current_user_can( 'edit_posts' );
	}
	/**
	 * Registers the CRUD routes for GET , POST, PUT/PATCH and DELETE
	 * @return void
	 */
	public function register_routes() {
		//get all items
		register_rest_route(
			$this->namespace,
			//notice, /books/post_id is the route, books plural, the ones after this will be at singular
			'/books/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => function () {
					return true;
				},
			)
		);
		//since the data is kept in a serialized array, put method used on create
		register_rest_route(
			$this->namespace,
			'/book/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'get_user_can_edit' ),
					'args',
				),
			)
		);
		//patch for edit, not all fields must be sent
		register_rest_route(
			$this->namespace,
			'/book/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => function () {
					return $this->user_can_edit;
				},
			)
		);
		//delete item
		register_rest_route(
			$this->namespace,
			//use id and post_id as request parameters since both are needed in the current format
			'/book/(?P<id>\d+)&(?P<post_id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => function () {
					return $this->user_can_edit;
				},
			)
		);
	}
	/**
	 * Retrieves all items from the post meta in JSON format<br>
	 *
	 * @param array $request MUST have the key 'id'
	 *
	 * @return WP_REST_Response The data in format JSON or FALSE if bad request
	 */
	public function get_items( $request ) {
		$response = new WP_REST_Response();
		if ( ! $this->validate_int( $request['id'] ) ) {
			$response->set_status(400);
			return $response;
		}
		$data    = get_post_meta( (int) $request['id'], self::META_KEY, true );
		$escaped = [];
		foreach ( $data as $key => $book ) {
			$book['title']   = esc_html( $book['title'] );
			$book['author']  = esc_html( $book['author'] );
			$book['genre']   = esc_html( $book['genre'] );
			$book['summary'] = esc_html( $book['summary'] );
			//
			$escaped[ $book['id'] ] = array(
				'id'      => $key,
				'title'   => $book['title'],
				'author'  => $book['author'],
				'genre'   => $book['genre'],
				'summary' => $book['summary'],
			);
		}
		$response->set_data($escaped);
		return $response;
	}
	/**
	 * Adds an item into the serialized array<br>
	 * If the item with the specified id already exists it will be updated
	 * @param WP_REST_Request $request MUST have the following keys : 'post_id', 'title', 'author' 'genre' and 'summary'
	 *                       for the operation to be successful
	 * @return WP_REST_Response Whether the operation was successful in JSON format
	 */
	public function create_item( $request ) {
		$request->sanitize_params();
		$response = new WP_REST_Response();
		if ( $this->validate_int( $request['post_id'] ) ) {
			$post_id = (int) $request['post_id'];
		} else {
			$response->set_status(400);
			return $response;
		}
		if ( ! ( isset( $request['title'], $request['author'], $request['genre'], $request['summary'] ) ) || ! $this->validate_request_params( $request ) ) {
			$response->set_status( 400 );
			return $response;
		}
		$next_id = 1;
		$data    = get_post_meta( $post_id, self::META_KEY, true );
		foreach ( $data as $key => $book ) {
			$next_id = max( $key, $next_id );
		}
		$model = array(
			'id'      => $next_id + 1,
			'title'   => $request['title'],
			'author'  => $request['author'],
			'genre'   => $request['genre'],
			'summary' => $request['summary'],
		);
		if ( $data === false ) {
			$data = array();
		}
		$data[ $model['id'] ] = $model;
		if ( ! update_post_meta( $post_id, self::META_KEY, $data ) ) {
			$response->set_status(400);
		}
		$response->set_data($model);
		return $response;
	}
	/**
	 * Updates only the specified fields of a book from the serialized array<br>
	 *
	 * @param WP_REST_Request $request MUST have at least two keys, 'id' and 'post_id'
	 *
	 * @return WP_REST_Response Whether the operation was successful or not as JSON
	 */
	public function update_item( $request ) {
		$request->sanitize_params();
		$response = new WP_REST_Response();
		if ( ! $this->validate_int($request['id']) || ! $this->validate_int($request['post_id']) || ! $this->validate_request_params($request) ) {
			$response->set_status(400);
			return $response;
		}
		$id      = (int) $request['id'];
		$post_id = (int) $request['post_id'];
		$data    = get_post_meta( $post_id, self::META_KEY, true );
		// inner model is used for cases where request doesn't have all fields
		$inner_model = $data[ $id ];
		$model       = array(
			'id'      => $id,
			'title'   => isset( $request['title'] ) ? $request['title'] : $inner_model['title'],
			'author'  => isset( $request['author'] ) ? $request['author'] : $inner_model['author'],
			'genre'   => isset( $request['genre'] ) ? $request['genre'] : $inner_model['genre'],
			'summary' => isset( $request['summary'] ) ? $request['summary'] : $inner_model['summary'],
		);
		//now add model into $data, serialize data then update the post meta
		$data[ $id ] = $model;
		if ( ! update_post_meta( $post_id, self::META_KEY, $data ) ) {
			$response->set_status(400);
		}
		return $response;
	}
	/**
	 * Deletes one item from the serialized array<br>
	 *
	 * @param WP_REST_Request $request  MUST have two keys 'id' and 'post_id'
	 *
	 * @return WP_REST_Response Whether the operation was successful as JSON
	 */
	public function delete_item( $request ) {
		$response = new WP_REST_Response();
		if ( ! $this->validate_int($request['id']) || ! $this->validate_int($request['post_id']) ) {
			$response->set_status(400);
			return $response;
		}
		$data = get_post_meta( (int) $request['post_id'], self::META_KEY, true );
		unset( $data[ (int) $request['id'] ] );
		if ( ! update_post_meta( (int) $request['post_id'], self::META_KEY, $data ) ) {
			$response->set_status(400);
		}
		return $response;
	}
	public function get_user_can_edit() {
		return $this->user_can_edit;
	}
	/**
	 * Check if the given parameter is a valid integer
	 *
	 * @param mixed $value the value to be checked
	 *
	 * @return bool Whether the input is a valid integer
	 */
	private function validate_int( $value ) {
		return isset( $value ) && is_numeric( $value ) && ( (int) $value !== 0 );
	}
	private function validate_request_params( $request ) {
		$name_reg = '#^[a-zA-Z\s\'.]+$#';
		$text_reg = '#^[a-zA-Z\d\s\'.,]+$#';
		$valid    = true;
		if ( isset( $request['title'] ) && preg_match( $name_reg, $request['title'] ) === 0 ) {
			$valid = false;
		}
		if ( isset( $request['author'] ) && preg_match( $name_reg, $request['author'] ) === 0 ) {
			$valid = false;
		}
		if ( isset( $request['genre'] ) && preg_match( $name_reg, $request['genre'] ) === 0 ) {
			$valid = false;
		}
		if ( isset( $request['summary'] ) && preg_match( $text_reg, $request['summary'] ) === 0 ) {
			$valid = false;
		}
		return $valid;
	}
}
