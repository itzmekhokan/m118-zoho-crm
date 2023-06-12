<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * M118_Zoho_Install class.
 */
class M118_Zoho_Install {
    /**
	 * Hook in tabs.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_types_taxonomy' ), 5 );
    }
    
    /**
	 * Install M118_Zoho_Install.
	 */
	public static function install() {
		if ( ! is_blog_installed() ) {
			return;
		}
    }

	/**
	 * Register core post types.
	 */
	public static function register_post_types_taxonomy() {
		if ( ! is_blog_installed() 
		|| post_type_exists( 'lenders' ) 
		|| post_type_exists( 'faqs' ) 
		|| post_type_exists( 'fees' ) 
		) {
			return;
		}

		$labels = array(
			'name'                  => _x( 'Lenders', 'Post type general name', 'mz-zoho-crm' ),
			'singular_name'         => _x( 'Lender', 'Post type singular name', 'mz-zoho-crm' ),
			'menu_name'             => _x( 'Lenders', 'Admin Menu text', 'mz-zoho-crm' ),
			'name_admin_bar'        => _x( 'Lender', 'Add New on Toolbar', 'mz-zoho-crm' ),
			'add_new'               => __( 'Add New Lender', 'mz-zoho-crm' ),
			'add_new_item'          => __( 'Add New Lender', 'mz-zoho-crm' ),
			'new_item'              => __( 'New Lender', 'mz-zoho-crm' ),
			'edit_item'             => __( 'Edit Lender', 'mz-zoho-crm' ),
			'view_item'             => __( 'View Lender', 'mz-zoho-crm' ),
			'all_items'             => __( 'All Lenders', 'mz-zoho-crm' ),
			'search_items'          => __( 'Search Lenders', 'mz-zoho-crm' ),
			'not_found'             => __( 'No Lenders found.', 'mz-zoho-crm' ),
			'not_found_in_trash'    => __( 'No Lenders found in Trash.', 'mz-zoho-crm' ),
		);     
		$args = array(
			'labels'             => $labels,
			'description'        => __( 'Lenders', 'mz-zoho-crm' ),
			'public'             => false,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'lenders' ),
			'capability_type'    => 'post',
			'capabilities' => array(
				'create_posts' 	 => false, // Removes support for the "Add New" function ( use 'do_not_allow' instead of false for multisite set ups )
			),
			'map_meta_cap' 		 => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 56,
			'menu_icon' 		 => 'dashicons-admin-multisite',
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail' ),
			'show_in_rest'       => true
		);
		  
		register_post_type( 'lenders', $args );

		// Add property type taxonomy
		$labels = array(
			'name'              => _x( 'Property Type', 'taxonomy general name', 'mz-zoho-crm' ),
			'singular_name'     => _x( 'Property Type', 'taxonomy singular name', 'mz-zoho-crm' ),
			'search_items'      => __( 'Search Property Type', 'mz-zoho-crm' ),
			'all_items'         => __( 'All Property Types', 'mz-zoho-crm' ),
			'parent_item'       => __( 'Parent Property Type', 'mz-zoho-crm' ),
			'parent_item_colon' => __( 'Parent Property Type:', 'mz-zoho-crm' ),
			'edit_item'         => __( 'Edit Property Type', 'mz-zoho-crm' ),
			'update_item'       => __( 'Update Property Type', 'mz-zoho-crm' ),
			'add_new_item'      => __( 'Add New Property Type', 'mz-zoho-crm' ),
			'new_item_name'     => __( 'New Property Type', 'mz-zoho-crm' ),
			'menu_name'         => __( 'Property Type', 'mz-zoho-crm' ),
		);
	 
		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => false,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'property_type' ),
		);
	 
		register_taxonomy( 'property_type', array( 'lenders' ), $args );

		// insert default property types
		$property_types = array(
			'resi'			=> 'Residential',
			'first-time-buyer'	=> 'First Time Buyer',
			'btl'				=> 'Buy to let',
			'development-finance'			=> 'Development Finance',
			'land'	=> 'Land',
			'commercial'				=> 'Commercial',
			'hmo'			=> 'HMO',
			'bridging-finance'	=> 'Bridging Finance',
			'holiday-let'		=> 'Holiday Let'
		);

		foreach ( $property_types as $key => $label) {
			$term = term_exists( $label, 'property_type' );
			if( isset( $term['term_id'] ) ) continue;
			wp_insert_term(
				$label,
				'property_type', 
				array(
					'slug' => $key,
				)
			);
		}

		// Add lender product taxonomy
		$labels = array(
			'name'              => _x( 'Lender Product', 'taxonomy general name', 'mz-zoho-crm' ),
			'singular_name'     => _x( 'Lender Product', 'taxonomy singular name', 'mz-zoho-crm' ),
			'search_items'      => __( 'Search Lender Product', 'mz-zoho-crm' ),
			'all_items'         => __( 'All Lender Product', 'mz-zoho-crm' ),
			'parent_item'       => __( 'Parent Lender Product', 'mz-zoho-crm' ),
			'parent_item_colon' => __( 'Parent Lender Product:', 'mz-zoho-crm' ),
			'edit_item'         => __( 'Edit Lender Product', 'mz-zoho-crm' ),
			'update_item'       => __( 'Update Lender Product', 'mz-zoho-crm' ),
			'add_new_item'      => __( 'Add New Lender Product', 'mz-zoho-crm' ),
			'new_item_name'     => __( 'New Lender Product', 'mz-zoho-crm' ),
			'menu_name'         => __( 'Lender Product', 'mz-zoho-crm' ),
		);
	 
		$args = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => false,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'lender_product' ),
		);
	 
		register_taxonomy( 'lender_product', array( 'lenders' ), $args );

		// Register FAQs post types ( Non UI )
		$args = array(
			'labels'             => array(),
			'description'        => __( 'Zoho FAQs data', 'mz-zoho-crm' ),
			'public'             => false,
			'publicly_queryable' => true,
			'show_ui'            => false,
			'show_in_menu'       => false,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'faqs' ),
			'capability_type'    => 'post',
			'capabilities' => array(
				'create_posts' 	 => false, // Removes support for the "Add New" function ( use 'do_not_allow' instead of false for multisite set ups )
			),
			'map_meta_cap' 		 => false,
			'has_archive'        => false,
			'hierarchical'       => false,
			'show_in_rest'       => true
		);
		  
		register_post_type( 'faqs', $args );

		// Register Fees post types ( Non UI )
		$args = array(
			'labels'             => array(),
			'description'        => __( 'Zoho Fees data', 'mz-zoho-crm' ),
			'public'             => false,
			'publicly_queryable' => true,
			'show_ui'            => false,
			'show_in_menu'       => false,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'fees' ),
			'capability_type'    => 'post',
			'capabilities' => array(
				'create_posts' 	 => false, // Removes support for the "Add New" function ( use 'do_not_allow' instead of false for multisite set ups )
			),
			'map_meta_cap' 		 => false,
			'has_archive'        => false,
			'hierarchical'       => false,
			'show_in_rest'       => true
		);
		  
		register_post_type( 'fees', $args );
	}
   
}
M118_Zoho_Install::init();