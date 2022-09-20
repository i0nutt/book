<?php

class BookRestController extends WP_REST_Controller {

	public function __construct() {
		$this->namespace = '/bookAPI/v1';
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/books/(?P<id>\d+)',
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_items' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/book/(?P<id>\d+)',
			array(
				'methods'  => 'PUT',
				'callback' => array( $this, 'create_item' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/book/(?P<id>\d+)&(?P<post_id>\d+)',
			array(
				'methods'  => 'DELETE',
				'callback' => array( $this, 'delete_item' ),
			)
		);
	}

	/**
	 * @throws JsonException
	 */
	public function get_items( $request ) {
		if ( ! isset( $request['id'] ) ) {
			return json_encode( array( 'success' => false ), JSON_THROW_ON_ERROR );
		}
		$data = get_post_meta( $request['id'], 'myBookApp', true );
		if ( $data !== false ) {
			$data = unserialize( $data, array( 'allowed_classes' => true ) );
		}
		return json_encode( $data, JSON_THROW_ON_ERROR );
	}

	/**
	 * @throws JsonException
	 */
	public function create_item( $request ) {
		if ( isset( $request['post_id'] ) ) {
			$post_id = $request['post_id'];
		} else {
			return json_encode( array( 'success' => false ), JSON_THROW_ON_ERROR );
		}
		if ( ! (
		isset( $request['title'], $request['author'], $request['genre'], $request['summary'], $request['id'] )
		) ) {
			return json_encode( array( 'success' => false ), JSON_THROW_ON_ERROR );
		}
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
			return json_encode( array( 'success' => false ), JSON_THROW_ON_ERROR );
		}

		return json_encode( array( 'success' => true ), JSON_THROW_ON_ERROR );
	}
	//

	/**
	 * @throws JsonException
	 */
	public function delete_item( $request ) {
		if ( ! isset( $request['id'] ) ) {
			return json_encode( array( 'success' => false ), JSON_THROW_ON_ERROR );
		}

		$data = unserialize( get_post_meta( $request['post_id'], 'myBookApp', true ), array( 'allowed_classes' => true ) );
		unset( $data[ (int) $request['id'] ] );
		$data = serialize( $data );
		var_dump($data);
		if ( ! update_post_meta( $request['post_id'], 'myBookApp', $data ) ) {
			return json_encode( array( 'success' => false ), JSON_THROW_ON_ERROR );
		}

		return json_encode( array( 'success' => true ), JSON_THROW_ON_ERROR );
	}

}
