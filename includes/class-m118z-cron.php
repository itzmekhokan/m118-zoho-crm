<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * M118_Zoho_Cron class.
 */
class M118_Zoho_Cron {

    /**
     * Constructor.
     */
    public function __construct() {
        add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );
        self::create_cron_jobs();
        add_action( 'm118_daily_do_sync_lenders_zoho_wp', array( $this, 'do_sync_lenders_zoho_wp' ) );
        add_action( 'm118_manual_do_sync_lenders_zoho_wp', array( $this, 'do_sync_lenders_zoho_wp' ) );
        add_action( 'm118_daily_do_sync_faqs_zoho_wp', array( $this, 'do_sync_faqs_zoho_wp' ) );
        add_action( 'm118_manual_do_sync_faqs_zoho_wp', array( $this, 'do_sync_faqs_zoho_wp' ) );
        add_action( 'm118_daily_do_sync_fees_zoho_wp', array( $this, 'do_sync_fees_zoho_wp' ) );
        add_action( 'm118_manual_do_sync_fees_zoho_wp', array( $this, 'do_sync_fees_zoho_wp' ) );
        add_action( 'm118_2days_do_clear_log_file', array( $this, 'do_clear_log_file' ) );
        add_action( 'm118_manual_do_sync_reset_all_zoho_wp', array( $this, 'do_sync_reset_all_zoho_wp' ) );
    }

    /**
	 * Create cron jobs
	 */
	private static function create_cron_jobs() {
	
		// Schedule daily Zoho CRM lenders sync.
		if ( ! wp_next_scheduled( 'm118_daily_do_sync_lenders_zoho_wp' ) ) {
			wp_schedule_event( time(), 'daily', 'm118_daily_do_sync_lenders_zoho_wp' );
		}

        // Schedule daily Zoho CRM FAQ sync.
		if ( ! wp_next_scheduled( 'm118_daily_do_sync_faqs_zoho_wp' ) ) {
			wp_schedule_event( time(), 'daily', 'm118_daily_do_sync_faqs_zoho_wp' );
		}

        if ( ! wp_next_scheduled( 'm118_2days_do_clear_log_file' ) ) {
			wp_schedule_event( time(), '2days', 'm118_2days_do_clear_log_file' );
		}

	}

    /**
	 * Add cron schedules.
	 *
	 * @param array $schedules List of WP scheduled cron jobs.
	 *
	 * @return array
	 */
    public function cron_schedules( $schedules ) {
        $schedules['2days'] = array(
			'interval' => 2 * 24 * 60 * 60,
			'display'  => __( 'Every 2 days', 'mz-zoho-crm' ),
		);
		return $schedules;
    }

    public function do_sync_lenders_zoho_wp(){
        $data = M118_Zoho_API::get_modules( 'Vendors' );
        
        if( isset( $data['data'] ) && $data['data'] ) {
            doLogM118Zoho( 'Logged @' .date("Y-m-d H:i:s").' - M118_Zoho_Cron > do_sync_lenders_zoho_wp started with data count -'. count($data['data']) );
            M118_Zoho_Data::syncZohoToWP( $data['data'] );
            doLogM118Zoho( 'Logged @' .date("Y-m-d H:i:s").' - M118_Zoho_Cron > do_sync_lenders_zoho_wp completed' );
        } else {
            doLogM118Zoho( 'Logged @' .date("Y-m-d H:i:s").' - M118_Zoho_Cron > do_sync_lenders_zoho_wp return no data' );
        }
    }

    public function do_sync_faqs_zoho_wp(){
        $data = M118_Zoho_API::get_modules( 'FAQs' );
        
        if( isset( $data['data'] ) && $data['data'] ) {
            doLogM118Zoho( 'Logged @' .date("Y-m-d H:i:s").' - M118_Zoho_Cron > do_sync_faqs_zoho_wp started with data count -'. count($data['data']) );
            M118_Zoho_Data::syncZohoFAQsToWP( $data['data'] );
            doLogM118Zoho( 'Logged @' .date("Y-m-d H:i:s").' - M118_Zoho_Cron > do_sync_faqs_zoho_wp completed' );
        } else {
            doLogM118Zoho( 'Logged @' .date("Y-m-d H:i:s").' - M118_Zoho_Cron > do_sync_faqs_zoho_wp return no data' );
        }
    }

    public function do_sync_fees_zoho_wp(){
        $data = M118_Zoho_API::get_modules( 'Fees' );
        
        if( isset( $data['data'] ) && $data['data'] ) {
            doLogM118Zoho( 'Logged @' .date("Y-m-d H:i:s").' - M118_Zoho_Cron > do_sync_fees_zoho_wp started with data count -'. count($data['data']) );
            M118_Zoho_Data::syncZohoFeesToWP( $data['data'] );
            doLogM118Zoho( 'Logged @' .date("Y-m-d H:i:s").' - M118_Zoho_Cron > do_sync_fees_zoho_wp completed' );
        } else {
            doLogM118Zoho( 'Logged @' .date("Y-m-d H:i:s").' - M118_Zoho_Cron > do_sync_fees_zoho_wp return no data' );
        }
    }

    public function do_clear_log_file() {
        $file = Mortgage118_Zoho()->plugin_path() . '/log/m118-zoho.log';
        if (file_exists($file)) {
            // Open the file to get existing content
            $current = file_get_contents($file);
            if ($current) {
                $current = ''; // clean up existing logs data
            }
            // Write the contents back to the file
            file_put_contents($file, $current);
        }
    }

    public function do_sync_reset_all_zoho_wp() {
        global $wpdb;
        // Delete all post type `lenders`, `faqs`, `fees`
        foreach ( array( 'lenders', 'faqs', 'fees' ) as $post_type ) {
            $sql = $wpdb->prepare("
                DELETE p,pm FROM $wpdb->posts p LEFT JOIN $wpdb->postmeta pm ON ( p.ID = pm.post_id ) WHERE p.post_type = %s", 
                $post_type
            );
            $result = $wpdb->query( $sql );
            doLogM118Zoho( 'Logged @' .date("Y-m-d H:i:s").' - M118_Zoho_Cron > do_sync_reset_all_zoho_wp query Delete posts done for type -'. $post_type );
        }
        
        foreach ( array( 'lender_product' ) as $taxonomy ) {
            // Delete Terms
            $sql = $wpdb->prepare( "DELETE t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy IN ('%s')", $taxonomy );
            $wpdb->query( $sql );
            // Delete Taxonomy
            $wpdb->delete( $wpdb->term_taxonomy, array( 'taxonomy' => $taxonomy ), array( '%s' ) );
            // Delete Termmeta
            $wpdb->query( "DELETE FROM $wpdb->termmeta WHERE term_id NOT IN (SELECT term_id FROM $wpdb->terms)" );

            doLogM118Zoho( 'Logged @' .date("Y-m-d H:i:s").' - M118_Zoho_Cron > do_sync_reset_all_zoho_wp query Delete terms done for taxo -'. $taxonomy );
        }

        // if all done do Sync from Zoho to WP
        $this->do_sync_lenders_zoho_wp();
        $this->do_sync_faqs_zoho_wp();
        $this->do_sync_fees_zoho_wp();
    }

}

return new M118_Zoho_Cron();