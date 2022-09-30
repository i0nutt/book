<?php

/**
 * Makes operations on books
 */
class BookService {
	const META_KEY = 'myBookApp';

	/**
	 * Gets list of books from the post_meta
	 * @param int $post_id The id of the post holding the data
	 *
	 * @return array The list of books
	 */
	public static function get_items( $post_id ) {
		$data   = get_post_meta( $post_id, self::META_KEY, true );
		$output = [];

		foreach ( $data as $key => $book ) {
			$output[ $key ] = array_map( 'esc_html', $book );
		}
		return $output;
	}
}
