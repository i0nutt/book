<?php

class BookRestController extends WP_REST_Controller {

	public function __construct() {
		$this->namespace = '/bookAPI/v1';
	}
	//register all CRUD routes used
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
	//get all items
	public function get_items( $request ) {
		if ( ! isset( $request['id'] ) ) {
			return json_encode( array( 'success' => false ) );
		}
		$data = get_post_meta( $request['id'], 'myBookApp', true );
		if ( $data !== false ) {
			$data = unserialize( $data, array( 'allowed_classes' => true ) );
		}
		return json_encode( $data );
	}
	//create one item, insert it at 'id' index in an assoc array
	public function create_item( $request ) {
		//validations
		if ( isset( $request['post_id'] ) ) {
			$post_id = $request['post_id'];
		} else {
			return json_encode( array( 'success' => false ));
		}
		if ( ! (
		isset( $request['title'], $request['author'], $request['genre'], $request['summary'], $request['id'] )
		) ) {
			return json_encode( array( 'success' => false ) );
		}
		//model is assoc array
		$model = array(
			'id'      => $request['id'],
			'title'   => $request['title'],
			'author'  => $request['author'],
			'genre'   => $request['genre'],
			'summary' => $request['summary'],
		);
		$data  = unserialize( get_post_meta( $post_id, 'myBookApp', true ), array( 'allowed_classes' => true ) );
		if ( $data === false ) {
			$data = array();
		}
		$data[ $model['id'] ] = $model;
		$data                 = serialize( $data );
		if ( ! update_post_meta( $post_id, 'myBookApp', $data ) ) {
			return json_encode( array( 'success' => false ));
		}

		return json_encode( array( 'success' => true ) );
	}
	// update item given a few or all model parameters on the request, 'post_id' is mandatory
	public function update_item( $request ) {
		if ( ! isset( $request['id'] ) ) {
			return json_encode( array( 'success' => false ) );
		}
		$data = unserialize( get_post_meta( $request['post_id'], 'myBookApp', true ), array( 'allowed_classes' => true ) );
		// inner model is used for cases where only certain fields must be changed
		$inner_model = $data[ $request['id'] ];
		$model       = array(
			'id'      => $request['id'],
			'title'   => isset($request['title']) ? $request['title'] : $inner_model['title'],
			'author'  => isset($request['author']) ? $request['author'] : $inner_model['author'],
			'genre'   => isset($request['genre']) ? $request['genre'] : $inner_model['genre'],
			'summary' => isset($request['summary']) ? $request['summary'] : $inner_model['summary'],
		);
		//now add model into $data, serialize data then update the post meta
		$data[ $request['id'] ] = $model;
		$data                   = serialize( $data );
		if ( ! update_post_meta( $request['post_id'], 'myBookApp', $data ) ) {
			return json_encode( array( 'success' => false ));
		}
		return json_encode( array( 'success' => true ) );
	}
	//delete item from serialized array
	public function delete_item( $request ) {
		if ( ! isset( $request['id'] ) ) {
			return json_encode( array( 'success' => false ) );
		}
		$data = unserialize( get_post_meta( $request['post_id'], 'myBookApp', true ), array( 'allowed_classes' => true ) );
		unset( $data[ (int) $request['id'] ] );
		$data = serialize( $data );
		if ( ! update_post_meta( $request['post_id'], 'myBookApp', $data ) ) {
			return json_encode( array( 'success' => false ));
		}
		return json_encode( array( 'success' => true ) );
	}

}
