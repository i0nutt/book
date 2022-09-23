<?php

class BookRestController extends WP_REST_Controller {
	private $meta_key;
	private $cap;
	public function __construct() {
		$this->namespace = '/bookAPI/v1';
		$this->meta_key  = 'myBookApp';
		$this->cap       = current_user_can( 'edit_posts' );
	}
	/**
	 * Registers the CRUD routes for GET , PUT, PATCH and DELETE
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
					'methods'             => 'PUT',
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => function () {
						return $this->cap;
					},
				),
			)
		);
		//patch for edit, not all fields must be sent
		register_rest_route(
			$this->namespace,
			'/book/(?P<id>\d+)',
			array(
				'methods'             => 'PATCH',
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => function () {
					return $this->cap;
				},
			)
		);
		//delete item
		register_rest_route(
			$this->namespace,
			//use id and post_id as request parameters since both are needed in the current format
			'/book/(?P<id>\d+)&(?P<post_id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => function () {
					return $this->cap;
				},
			)
		);
	}
	/**
	 * Retrieves all items from the post meta in JSON format<br>
	 *
	 * @param array $request MUST have the key 'id'
	 *
	 * @return false|string The data in format JSON or FALSE if bad request
	 */
	public function get_items( $request ) {
		if ( ! $this->validate_int( $request['id'] ) ) {
			http_response_code(400);
			return json_encode( array( 'success' => false ) );
		}
		$data = get_post_meta( (int) $request['id'], $this->meta_key, true );
		if ( $data !== false ) {
			$data = unserialize( $data, array( 'allowed_classes' => true ) );
		}
		return json_encode( $data );
	}
	/**
	 * Adds an item into the serialized array<br>
	 * If the item with the specified id already exists it will be updated
	 * @param array $request MUST have the following keys : 'id', 'post_id', 'title', 'author' 'genre' and 'summary'
	 *                       for the operation to be successful
	 * @return false|string Whether the operation was successful in JSON format
	 */
	public function create_item( $request ) {
		$request->sanitize_params();
		if ( $this->validate_int( $request['id'] ) || $this->validate_int( $request['post_id'] ) ) {
			$post_id = (int) $request['post_id'];
		} else {
			http_response_code(400);
			return null;
		}
		if ( ! (
		isset( $request['title'], $request['author'], $request['genre'], $request['summary'] )
		) ) {
			http_response_code(400);
			return null;
		}
		if ( ! $this->validate_request_params( $request ) ) {
			http_response_code(400);
			return null;
		}
		$model = array(
			'id'      => (int) $request['id'],
			'title'   => $request['title'],
			'author'  => $request['author'],
			'genre'   => $request['genre'],
			'summary' => $request['summary'],
		);
		$data  = unserialize( get_post_meta( $post_id, $this->meta_key, true ), array( 'allowed_classes' => true ) );
		if ( $data === false ) {
			$data = array();
		}
		$data[ $model['id'] ] = $model;
		$data                 = serialize( $data );
		if ( ! update_post_meta( $post_id, $this->meta_key, $data ) ) {
			http_response_code(400);
			return null;
		}
		return json_encode( array( 'success' => true ) );
	}
	/**
	 * Updates only the specified fields of a book from the serialized array<br>
	 *
	 * @param array $request MUST have at least two keys, 'id' and 'post_id'
	 *
	 * @return false|string Whether the operation was successful or not as JSON
	 */
	public function update_item( $request ) {
		$request->sanitize_params();
		//var_dump($request['author']);die; working on allowing names like O'hara
		if ( ! $this->validate_int($request['id']) || ! $this->validate_int($request['post_id']) ) {
			http_response_code(400);
			return null;
		}
		if ( ! $this->validate_request_params($request) ) {
			http_response_code(400);
			return null;
		}
		$id      = (int) $request['id'];
		$post_id = (int) $request['post_id'];
		$data    = unserialize( get_post_meta( $post_id, $this->meta_key, true ), array( 'allowed_classes' => true ) );
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
		$data        = serialize( $data );
		if ( ! update_post_meta( $post_id, $this->meta_key, $data ) ) {
			return json_encode( array( 'success' => false ) );
		}
		return json_encode( array( 'success' => true ) );
	}
	/**
	 * Deletes one item from the serialized array<br>
	 *
	 * @param array $request  MUST have two keys 'id' and 'post_id'
	 *
	 * @return false|string Whether the operation was successful as JSON
	 */
	public function delete_item( $request ) {
		if ( ! $this->validate_int($request['id']) || ! $this->validate_int($request['post_id']) ) {
			return json_encode( array( 'success' => false ) );
		}
		$data = unserialize( get_post_meta( (int) $request['post_id'], $this->meta_key, true ), array( 'allowed_classes' => true ) );
		unset( $data[ (int) $request['id'] ] );
		$data = serialize( $data );
		if ( ! update_post_meta( (int) $request['post_id'], $this->meta_key, $data ) ) {
			return json_encode( array( 'success' => false ) );
		}
		return json_encode( array( 'success' => true ) );
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
		if ( isset( $request['title'] ) && preg_match( '#^[a-zA-Z\s]+$#', $request['title'] ) === 0 ) {
			return false;
		}
		if ( isset( $request['author'] ) && preg_match( '#^[a-zA-Z\s]+$#', $request['author'] ) === 0 ) {
			return false;
		}
		if ( isset( $request['genre'] ) && preg_match( '#^[a-zA-Z\s]+$#', $request['genre'] ) === 0 ) {
			return false;
		}
		if ( isset( $request['summary'] ) && preg_match( '#^[a-zA-Z\s]+$#', $request['summary'] ) === 0 ) {
			return false;
		}
		return true;
	}
}
