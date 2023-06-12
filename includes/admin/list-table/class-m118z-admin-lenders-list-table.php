<?php
/**
 * Lenders Table List
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if(!class_exists('WP_Posts_List_Table')){
    require_once ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php';
}

/**
 * Lenders table list class.
 */
class M118_Admin_Lenders_Table_List extends WP_Posts_List_Table {

	/**
	 * Initialize the API key table list.
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Return title column.
	 *
	 * @param  array $key Key data.
	 * @return string
	 */
	public function column_title( $post ) {
		
		$output = '<strong>';
		$output .= '<a href="" class="row-title">';
		$output .= esc_html( $post->post_title );
		$output .= '</a>';
		$output .= '</strong>';

		return $output;
	}

	
}
