<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * M118_Zoho_Admin class.
 */
class M118_Zoho_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'includes' ) );
		// Add menus.
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 10 );
		add_filter( 'post_row_actions', array( $this, 'm118_post_row_actions' ), 99, 2 );
		add_filter( 'bulk_actions-edit-lenders', array( $this, 'm118_lenders_bulk_actions' ), 99 );
		add_filter( 'handle_bulk_actions-edit-lenders', array( $this, 'm118_lenders_bulk_action_handler' ), 99, 3 );
		add_action( 'admin_notices', array( $this, 'm118_lenders_bulk_action_notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'do_meta_boxes', array( $this, 'm118_remove_metaboxes' ) );
		add_action( 'admin_print_styles', array( $this, 'm118_lenders_head' ) );

		//add_filter( 'views_edit-lenders', array( $this, 'm118_lenders_list_table' ) );    
		//add_filter( 'manage_lenders_posts_columns', array( $this, 'check_lenders_columns' ) );

	}

	/**
	 * Includes files.
	 */
	public function includes() {
		include_once MZ_ABSPATH . 'includes/admin/settings/class-m118z-admin-settings.php';
		include_once MZ_ABSPATH . 'includes/admin/list-table/class-m118z-admin-lenders-list-table.php';

		// Delete transient
		if( isset( $_GET['m118_del_trans'] ) && $_GET['m118_del_trans'] ) {
			$key = trim( $_GET['m118_del_trans'] );
			M118_Zoho_Data::delete_prefix_transients( $key );
		}

		// Test
		if( isset( $_GET['m118_api'] ) && $_GET['m118_api'] ) {
			$data = M118_Zoho_API::get_modules( 'Fees' );
			print_r($data);
		}
	}

	/**
	 * Add menu items.
	 */
	public function admin_menu() {

		add_menu_page( 
            __( 'Mortgage118 Zoho', 'mz-zoho-crm' ), 
            __( 'Mortgage118 Zoho', 'mz-zoho-crm' ), 
            'manage_options', 
            'm118-zoho-settings', 
            array( $this, 'm118_zoho_settings_handler' ), 
            'dashicons-book', 
            50 
        );
	}

	/**
	 * Add row actions.
	 */
	public function m118_post_row_actions( $actions, $post ) {
		if( $post->post_type != 'lenders' ) return $actions;
		// remove unwanted actions
		if( isset( $actions['duplicate_post'] ) ) unset( $actions['duplicate_post'] );
		if( isset( $actions['eae_duplicate'] ) ) unset( $actions['eae_duplicate'] );
		if( isset( $actions['edit'] ) ) unset( $actions['edit'] );
		if( isset( $actions['inline hide-if-no-js'] ) ) unset( $actions['inline hide-if-no-js'] );
		if( isset( $actions['trash'] ) ) unset( $actions['trash'] );

		return array_merge( array( 
			'id' => sprintf( __( 'ID: %d', 'mz-zoho-crm' ), $post->ID ),
			'record_id' => sprintf( __( 'Record ID: %d', 'mz-zoho-crm' ), M118_Zoho_Data::get_record_id( $post->ID ) )
		), $actions );
	}

	/**
	 * Add bulk actions.
	 */
	public function m118_lenders_bulk_actions( $actions ) {
		if( isset( $actions['edit'] ) ) unset( $actions['edit'] );
		if( isset( $actions['trash'] ) ) unset( $actions['trash'] );
		$actions['sync-zoho-records'] = __('Sync Zoho Records', 'mz-zoho-crm');
		return $actions;
	}

	/**
	 * Handle bulk actions.
	 */
	public function m118_lenders_bulk_action_handler( $redirect, $doaction, $object_ids ) {
		// let's remove query args first
		$redirect = remove_query_arg( array( 'm118_sync_zoho_records_done', 'm118_sync_post_zoho_records_done' ), $redirect );

		// do something for "Sync Zoho Records" bulk action
		if ( $doaction == 'sync-zoho-records' ) {

			foreach ( $object_ids as $post_id ) {
				$record_id = M118_Zoho_Data::get_record_id( $post_id );
				if( $record_id ) {
					$data = M118_Zoho_API::get_modules( 'Vendors', $record_id );
					if( isset( $data['data'] ) && $data['data'] ) { 
						M118_Zoho_Data::sync_post_from_zoho( $data['data'] );
					}
				}
			}

			// delete old transients
			M118_Zoho_Data::delete_prefix_transients( 'm118_get_alphabetically_lenders' );

			// do not forget to add query args to URL because we will show notices later
			$redirect = add_query_arg(
				'm118_sync_zoho_records_done', // just a parameter for URL (we will use $_GET['misha_make_draft_done'] )
				count( $object_ids ), // parameter value - how much posts have been affected
			$redirect );
		}

		return $redirect;
	}

	/**
	 * Notice against bulk actions.
	 */
	public function m118_lenders_bulk_action_notices( $actions ) {

		if( ! empty( $_REQUEST['m118_sync_zoho_records_done'] ) ) {

			// depending on ho much posts were changed, make the message different
			printf( '<div id="message" class="updated notice is-dismissible"><p>' .
				_n( 'Zoho records of %s lenders has been updated.',
				'Zoho records of %s lenders has been updated.',
				intval( $_REQUEST['m118_sync_zoho_records_done'] )
			) . '</p></div>', intval( $_REQUEST['m118_sync_zoho_records_done'] ) );
	
		}
	}

	/**
	 * Remove unwanted metaboxes
	 */
	public function m118_remove_metaboxes(){
		remove_meta_box( 'submitdiv', 'lenders', 'side' ); // Remove publish meta boxe
		remove_meta_box( 'wtr_custom', 'lenders', 'side' ); // Remove wtr_custom meta boxe
	}

	/**
	 * Define lenders table.
	 */
	public function m118_lenders_list_table( $columns ) {
		global $wp_list_table;
		$lenderslisttable = new M118_Admin_Lenders_Table_List();
		$wp_list_table = $lenderslisttable ; 
	}

	/**
	 * Enqueue scripts & styles.
	 */
	public function admin_enqueue_scripts() {

		$screen       = get_current_screen();
		$screen_id    = $screen ? $screen->id : '';
		$suffix = ( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ) ? '' : '.min';

		wp_register_style( 'm118_zoho_admin_settings', Mortgage118_Zoho()->plugin_url() . '/assets/css/admin-settings.css', array(), MZ_VERSION );
		wp_enqueue_script( 'm118_zoho_admin_settings', Mortgage118_Zoho()->plugin_url() . '/assets/js/admin-settings.js', array( 'jquery' ), MZ_VERSION, true );
		wp_enqueue_style( 'm118_zoho_admin_settings' );
	}

	/**
	 * Print in head.
	 */
	public function m118_lenders_head() {
		global $typenow;
    	if( 'lenders' == $typenow ) :
		?>
		<style>
			#postimagediv #set-post-thumbnail-desc,
			#postimagediv #remove-post-thumbnail {
				display: none;
			}
		</style>
		<?php
		endif;
		if( isset( $_GET['page'] ) && $_GET['page'] === 'm118-zoho-settings' ) :
		?>
		<style>
			.wp-core-ui .button.m118-zoho-cron-sync-btn {
				margin-right: 10px;
			}
		</style>
		<?php
		endif;
	}

	/**
	 * Output of backend Settings page.
	 */
	public function m118_zoho_settings_handler() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'Mortgage118 Zoho', 'mz-zoho-crm' ); ?></h1>
			<?php settings_errors( 'm118_zoho_settings_options' ); ?>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'm118_zoho_settings_option_group' );
                do_settings_sections( 'm118-zoho-settings-admin' );
                submit_button();
            ?>
            </form>
            <?php do_action( 'm118_zoho_settings_options_footer' )?>
        </div>
        <?php
	}

	public function check_lenders_columns( $columns ) {
		unset($columns['taxonomy-property_type']);
		unset($columns['taxonomy-lender_product']);
		return $columns;
	}
}

return new M118_Zoho_Admin();