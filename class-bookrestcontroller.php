<?php

class BookRestController extends WP_REST_Controller {
	private $meta_key;
	public function __construct() {
		$this->namespace = '/bookAPI/v1';
		$this->meta_key  = 'myBookApp';
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
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_items' ),
			)
		);
		//since the data is kept in a serialized array, put method used on create
		register_rest_route(
			$this->namespace,
			'/book/(?P<id>\d+)',
			array(
				'methods'  => 'PUT',
				'callback' => array( $this, 'create_item' ),
			)
		);
		//patch for edit, not all fields must be sent
		register_rest_route(
			$this->namespace,
			'/book/(?P<id>\d+)',
			array(
				'methods'  => 'PATCH',
				'callback' => array( $this, 'update_item' ),
			)
		);
		//delete item
		register_rest_route(
			$this->namespace,
			//use id and post_id as request parameters since both are needed in the current format
			'/book/(?P<id>\d+)&(?P<post_id>\d+)',
			array(
				'methods'  => 'DELETE',
				'callback' => array( $this, 'delete_item' ),
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
		if ( ! $this->validate_int($request['id']) || ! $this->validate_int($request['post_id']) ) {
			$post_id = (int) $request['post_id'];
		} else {
			return json_encode( array( 'success' => false ) );
		}
		if ( ! (
			isset( $request['title'], $request['author'], $request['genre'], $request['summary'] )
			) ) {
			return json_encode( array( 'success' => false ) );
		}
		$model = array(
			'id'      => (int) $request['id'],
			'title'   => sanitize_text_field( $request['title'] ),
			'author'  => sanitize_text_field( $request['author'] ),
			'genre'   => sanitize_text_field( $request['genre'] ),
			'summary' => sanitize_text_field( $request['summary'] ),
		);
		$data  = unserialize( get_post_meta( $post_id, $this->meta_key, true ), array( 'allowed_classes' => true ) );
		if ( $data === false ) {
			$data = array();
		}
		$data[ $model['id'] ] = $model;
		$data                 = serialize( $data );
		if ( ! update_post_meta( $post_id, $this->meta_key, $data ) ) {
			return json_encode( array( 'success' => false ) );
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
		if ( ! $this->validate_int($request['id']) || ! $this->validate_int($request['post_id']) ) {
			return json_encode( array( 'success' => false ) );
		}
		$id      = (int) $request['id'];
		$post_id = (int) $request['post_id'];
		$data    = unserialize( get_post_meta( $post_id, $this->meta_key, true ), array( 'allowed_classes' => true ) );
		// inner model is used for cases where request doesn't have all fields
		$inner_model = $data[ $id ];
		$model       = array(
			'id'      => $id,
			'title'   => isset( $request['title'] ) ? sanitize_text_field($request['title']) : $inner_model['title'],
			'author'  => isset( $request['author'] ) ? sanitize_text_field($request['author']) : $inner_model['author'],
			'genre'   => isset( $request['genre'] ) ? sanitize_text_field($request['genre']) : $inner_model['genre'],
			'summary' => isset( $request['summary'] ) ? sanitize_text_field($request['summary']) : $inner_model['summary'],
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
}
