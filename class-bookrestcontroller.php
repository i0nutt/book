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
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'required'          => true,
						'validate_callback' => array( $this, 'validate_int' ),
					),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/book',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'get_user_can_edit' ),
					'args'                => array(
						'post_id' => array(
							'required'          => true,
							'validate_callback' => array( $this, 'validate_int' ),
						),
						'title'   => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => array( $this, 'validate_request_param_name' ),
						),
						'author'  => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => array( $this, 'validate_request_param_name' ),
						),
						'genre'   => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => array( $this, 'validate_request_param_name' ),
						),
						'summary' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => array( $this, 'validate_request_param_text' ),
						),
					),
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
				'permission_callback' => array( $this, 'get_user_can_edit' ),
				'args'                => array(
					'id'      => array(
						'required'          => true,
						'validate_callback' => array( $this, 'validate_int' ),
					),
					'post_id' => array(
						'required'          => true,
						'validate_callback' => array( $this, 'validate_int' ),
					),
					'title'   => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => array( $this, 'validate_request_param_name' ),
					),
					'author'  => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => array( $this, 'validate_request_param_name' ),
					),
					'genre'   => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => array( $this, 'validate_request_param_name' ),
					),
					'summary' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => array( $this, 'validate_request_param_text' ),
					),
				),
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
				'permission_callback' => array( $this, 'get_user_can_edit' ),
				'args'                => array(
					'id'      => array(
						'required'          => true,
						'validate_callback' => array( $this, 'validate_int' ),
					),
					'post_id' => array(
						'required'          => true,
						'validate_callback' => array( $this, 'validate_int' ),
					),
				),
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
		$data   = get_post_meta( (int) $request['id'], self::META_KEY, true );
		$output = [];
		foreach ( $data as $key => $book ) {
			$output[ $key ] = $this->escape_html($book);
		}
		$response->set_data($output);
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
		$response = new WP_REST_Response();
		$next_id  = 1;
		$data     = get_post_meta( $request->get_param('post_id'), self::META_KEY, true );
		foreach ( $data as $key => $book ) {
			$next_id = max( $key, $next_id );
		}
		$model = array(
			'id'      => $next_id + 1,
			'title'   => $request->get_param('title'),
			'author'  => $request->get_param('author'),
			'genre'   => $request->get_param('genre'),
			'summary' => $request->get_param('summary'),
		);
		if ( $data === false ) {
			$data = array();
		}
		$data[ $model['id'] ] = $model;
		if ( ! update_post_meta( $request->get_param('post_id'), self::META_KEY, $data ) ) {
			$response->set_status(400);
		}
		$response->set_data($this->escape_html($model));
		return $response;
	}
	/**
	 * Updates only the specified fields of a book from the serialized array
	 *
	 * @param WP_REST_Request $request MUST have at least two keys, 'id' and 'post_id'
	 *
	 * @return WP_REST_Response Whether the operation was successful or not as JSON
	 */
	public function update_item( $request ) {
		$response = new WP_REST_Response();
		$id       = (int) $request->get_param('id');
		$post_id  = (int) $request->get_param('post_id');
		$data     = get_post_meta( $post_id, self::META_KEY, true );
		// inner model is used for cases where request doesn't have all fields
		$inner_model = $data[ $id ];
		$model       = array(
			'id'      => $id,
			'title'   => $request->get_param( 'title' ) !== null ? $request->get_param('title') : $inner_model['title'],
			'author'  => $request->get_param( 'author' ) !== null ? $request->get_param('author') : $inner_model['author'],
			'genre'   => $request->get_param( 'genre' ) !== null ? $request->get_param('genre') : $inner_model['genre'],
			'summary' => $request->get_param( 'summary' ) !== null ? $request->get_param('summary') : $inner_model['summary'],
		);
		//now add model into $data, serialize data then update the post meta
		$data[ $id ] = $model;
		if ( ! update_post_meta( $post_id, self::META_KEY, $data ) ) {
			$response->set_status(400);
		}
		$response->set_data($this->escape_html($model));
		return $response;
	}
	/**
	 * Deletes one item from the serialized array
	 *
	 * @param WP_REST_Request $request  MUST have two keys 'id' and 'post_id'
	 *
	 * @return WP_REST_Response Whether the operation was successful as JSON
	 */
	public function delete_item( $request ) {
		$response = new WP_REST_Response();
		$data     = get_post_meta( (int) $request->get_param('post_id'), self::META_KEY, true );
		unset( $data[ (int) $request['id'] ] );
		if ( ! update_post_meta( (int) $request->get_param('post_id'), self::META_KEY, $data ) ) {
			$response->set_status(400);
		}
		return $response;
	}
	/** Getter for user_can_edit property
	 * @return bool Whether the user can edit
	 */
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
	public function validate_int( $value ) {
		return isset( $value ) && is_numeric( $value ) && ( (int) $value !== 0 );
	}
	/** Checks whether a request parameter is a valid name
	 * @param string $param  The value to be validated
	 *
	 * @return bool Whether $param is a valid name
	 */
	public function validate_request_param_name( $param ) {
		$name_reg = '#^[a-zA-Z\s\'.]+$#';
		return $this->validate_param($param, $name_reg);
	}
	/** Checks whether a request parameter is a valid text
	 * @param string $param  The value to be validated
	 *
	 * @return bool Whether $param is a valid name
	 */
	public function validate_request_param_text( $param ) {
		$text_reg = '#^[a-zA-Z\d\s\'.,]+$#';
		return $this->validate_param($param, $text_reg);
	}
	/** Checks whether a set param is valid
	 * @param string $param The value to be validated
	 * @param string $regex The regex against which $param will be validated
	 *
	 * @return bool whether $param is valid against the regex given
	 */
	public function validate_param( $param, $regex ) {
		$valid = true;
		if ( isset( $param ) && preg_match( $regex, $param ) === 0 ) {
			$valid = false;
		}
		return $valid;
	}
	/** Returns the item html escaped
	 * @param array $item Book model to be escaped
	 *
	 * @return array The escaped book model
	 */
	private function escape_html( $item ) {
		$item['title']   = esc_html( $item['title'] );
		$item['author']  = esc_html( $item['author'] );
		$item['genre']   = esc_html( $item['genre'] );
		$item['summary'] = esc_html( $item['summary'] );
		return array(
			'id'      => $item['id'],
			'title'   => $item['title'],
			'author'  => $item['author'],
			'genre'   => $item['genre'],
			'summary' => $item['summary'],
		);
	}
}
