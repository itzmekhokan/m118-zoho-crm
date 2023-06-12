<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * M118_Zoho_Admin_Settings class.
 */
class M118_Zoho_Admin_Settings {

	/**
	 * settings options.
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->options = get_option( 'm118_zoho_settings_options' );
        add_action( 'admin_init', array( $this, 'settings_page_init' ) ); 
        // For testing purpose only
        if( isset( $_GET['check_auth_data'] ) && absint( $_GET['check_auth_data'] ) == 1 ) {
            echo '<pre>';
            print_r( get_option( 'm118_zoho_authorized_data' ) );
            echo '</pre>';
        }
	}

	/**
     * Register and add settings
     */
    public function settings_page_init() {   
        // Do zoho authorization if code available
        if( isset( $_GET['code'] ) && $_GET['code'] ) 
            $this->do_authorization_request();

        register_setting(
            'm118_zoho_settings_option_group', // Option group
            'm118_zoho_settings_options', // Option name
            array( $this, 'sanitize_settings' ) // Sanitize
        );

        add_settings_section(
            'm118_zoho_general_wp_settings', // ID
            __( 'General Settings', 'mz-zoho-crm' ), // Title
            array( $this, 'm118_zoho_general_wp_settings_section_info' ), // Callback
            'm118-zoho-settings-admin' // Page
        );  

        add_settings_field(
            'google_api_key', // ID
            __( 'Google API key', 'mz-zoho-crm' ), // Title 
            array( $this, 'google_api_key_field_callback' ), // Callback
            'm118-zoho-settings-admin', // Page
            'm118_zoho_general_wp_settings' // Section           
        ); 

        add_settings_field(
            'default_zoho2wp_user', // ID
            __( 'Default Zoho to WP user', 'mz-zoho-crm' ), // Title 
            array( $this, 'default_zoho2wp_user_field_callback' ), // Callback
            'm118-zoho-settings-admin', // Page
            'm118_zoho_general_wp_settings' // Section           
        ); 


        add_settings_section(
            'm118_zoho_general_settings', // ID
            __( 'Configuration Zoho API', 'mz-zoho-crm' ), // Title
            array( $this, 'm118_zoho_general_settings_section_info' ), // Callback
            'm118-zoho-settings-admin' // Page
        );  

        add_settings_field(
            'zoho_client_id', // ID
            __( 'Client ID', 'mz-zoho-crm' ), // Title 
            array( $this, 'zoho_client_id_field_callback' ), // Callback
            'm118-zoho-settings-admin', // Page
            'm118_zoho_general_settings' // Section           
        ); 
        
        add_settings_field(
            'zoho_client_secret', // ID
            __( 'Client Secret', 'mz-zoho-crm' ), // Title 
            array( $this, 'zoho_client_secret_field_callback' ), // Callback
            'm118-zoho-settings-admin', // Page
            'm118_zoho_general_settings' // Section           
        ); 
        
        add_settings_field(
            'zoho_redirect_uri', // ID
            __( 'Redirect URL', 'mz-zoho-crm' ), // Title 
            array( $this, 'zoho_redirect_uri_field_callback' ), // Callback
            'm118-zoho-settings-admin', // Page
            'm118_zoho_general_settings' // Section           
        ); 

        add_settings_field(
            'zoho_authorize_request', // ID
            __( 'Authenticate Zoho CRM', 'mz-zoho-crm' ), // Title 
            array( $this, 'zoho_authorize_request_field_callback' ), // Callback
            'm118-zoho-settings-admin', // Page
            'm118_zoho_general_settings' // Section           
        ); 

        add_settings_field(
            'zoho_sync_lenders_cron', // ID
            __( 'Sync from Zoho CRM to WP', 'mz-zoho-crm' ), // Title 
            array( $this, 'zoho_sync_lenders_cron_field_callback' ), // Callback
            'm118-zoho-settings-admin', // Page
            'm118_zoho_general_settings' // Section           
        );
        
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize_settings( $input ) {
        $type = 'updated';
        $message = __( 'All settings saved.', 'mz-zoho-crm' );

        $new_input = array();

        if( isset( $input['zoho_client_id'] ) )
            $new_input['zoho_client_id'] = $input['zoho_client_id'];
        if( isset( $input['zoho_client_secret'] ) )
            $new_input['zoho_client_secret'] = $input['zoho_client_secret'];

        if( isset( $input['google_api_key'] ) )
            $new_input['google_api_key'] = $input['google_api_key'];    

        if( isset( $input['default_zoho2wp_user'] ) )
            $new_input['default_zoho2wp_user'] = $input['default_zoho2wp_user'];     

        if( isset( $input['do_sync_zoho_lenders'] ) ) {
            wp_schedule_single_event( time() + 10, 'm118_manual_do_sync_lenders_zoho_wp' ); // trigger in 10 sec.
            $message = __( 'Background syncing process of lenders from Zoho CRM to WP has started. It may takes some times to completed the process.', 'mz-zoho-crm' );
        }

        if( isset( $input['do_sync_zoho_faqs'] ) ) {
            wp_schedule_single_event( time() + 10, 'm118_manual_do_sync_faqs_zoho_wp' ); // trigger in 10 sec.
            $message = __( 'Background syncing process of FAQs from Zoho CRM to WP has started. It may takes some times to completed the process.', 'mz-zoho-crm' );
        }

        if( isset( $input['do_sync_zoho_fees'] ) ) {
            wp_schedule_single_event( time() + 10, 'm118_manual_do_sync_fees_zoho_wp' ); // trigger in 10 sec.
            $message = __( 'Background syncing process of Fees from Zoho CRM to WP has started. It may takes some times to completed the process.', 'mz-zoho-crm' );
        }

        if( isset( $input['do_sync_zoho_reset_all'] ) ) {
            wp_schedule_single_event( time() + 10, 'm118_manual_do_sync_reset_all_zoho_wp' ); // trigger in 10 sec.
            $message = __( 'Background reset process of all lenders posts, faqs and modules related data by deleting from WP backend has started. After it, process of re sync from Zoho to WP will started. So, It may takes some times to completed the process.', 'mz-zoho-crm' );
        }
        
        if ( $message ) :
            add_settings_error(
                'm118_zoho_settings_options',
                esc_attr( 'settings_updated' ),
                $message,
                $type
            );
        endif;
        
        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function m118_zoho_general_wp_settings_section_info() {
        print __( 'Configure below Zoho to WP sync related info', 'mz-zoho-crm' );
    }

    /** 
     * Get settings fields
     */
    public function google_api_key_field_callback() {
        $google_api_key = isset( $this->options['google_api_key'] ) ? $this->options['google_api_key'] : '';
        printf(
            "<input name='m118_zoho_settings_options[google_api_key]' type='text' id='google_api_key' class='regular-text' value='%s' />",
            $google_api_key
        );
    }

    /** 
     * Get settings fields
     */
    public function default_zoho2wp_user_field_callback() {
        $default_zoho2wp_user = isset( $this->options['default_zoho2wp_user'] ) ? $this->options['default_zoho2wp_user'] : 0;
        wp_dropdown_users( array( 
            'name' => 'm118_zoho_settings_options[default_zoho2wp_user]',
            'selected' => $default_zoho2wp_user,
        ) );
        printf(
            "<p class='description'>%s</p>",
            __( 'Default used to set post author for Lenders when sync from Zoho to WP. If Zoho API data Owner > email is present with wp user then default setting overridden.', 'mz-zoho-crm' )
        );
    }

    /** 
     * Print the Section text
     */
    public function m118_zoho_general_settings_section_info() {
        print __( 'Configure below <a href="https://accounts.zoho.com/developerconsole" target="_blank">Zoho client ID and Secret</a> by adding client <b>`Server-based Application`</b>', 'mz-zoho-crm' );
    }

    /** 
     * Get settings fields
     */
    public function zoho_client_id_field_callback() {
        $zoho_client_id = isset( $this->options['zoho_client_id'] ) ? $this->options['zoho_client_id'] : '';
        printf(
            "<input name='m118_zoho_settings_options[zoho_client_id]' type='text' id='zoho_client_id' class='regular-text' value='%s' />",
            $zoho_client_id
        );
    }

    /** 
     * Get settings fields
     */
    public function zoho_client_secret_field_callback() {
        $zoho_client_secret = isset( $this->options['zoho_client_secret'] ) ? $this->options['zoho_client_secret'] : '';
        printf(
            "<input name='m118_zoho_settings_options[zoho_client_secret]' type='text' id='zoho_client_secret' class='regular-text' value='%s' />",
            $zoho_client_secret
        );
    }

    /** 
     * Get settings fields
     */
    public function zoho_redirect_uri_field_callback() {
        printf(
            "<label class='zoho_client_secret'><strong>%s</strong></label><p class='description'>%s</p>",
            admin_url( 'admin.php?page=m118-zoho-settings' ),
            __( 'Add this URL in your Zoho API console Server-based Client App Redirect URL for authorization process.', 'mz-zoho-crm' )
        );
    }

    /** 
     * Get settings fields
     */
    public function zoho_authorize_request_field_callback() {
        $scope = M118_Zoho_API::SCOPES;
        $redirect_uri = admin_url( 'admin.php?page=m118-zoho-settings' );
        $client_id = isset( $this->options['zoho_client_id'] ) ? $this->options['zoho_client_id'] : '';
        $zoho_account_url = 'https://accounts.zoho.com';

        $request_url = $zoho_account_url.'/oauth/v2/auth?scope='.$scope.'&client_id='.$client_id.'&response_type=code&access_type=offline&redirect_uri='.$redirect_uri;
        
        if( isset( $_GET['code'] ) && $_GET['code'] ) :
            printf(
                "<p class='notice notice-success'><label class='m118-zoho-authorized-msg'>%s</label></p>",
                __( 'Authorization in progress!', 'mz-zoho-crm' )
            );
        endif;

        $tokenData = get_m118_zoho_authorized_data( 'token_data' );
        if( $tokenData ) :
            printf(
                "<p class='notice notice-success'><label class='m118-zoho-authorized-msg'>%s</label></p>",
                __( 'Authorization Token created at', 'mz-zoho-crm' ) . ' '. date('Y-m-d H:i:s', get_m118_zoho_authorized_data( 'token_created' ))
            );
        endif;

        if( $client_id ) :
            printf(
                "<a href='%s' class='m118-zoho-admin-button button button-secondary' >%s</a>",
                $request_url,
                __( 'Do Authenticate', 'mz-zoho-crm' )
            );
        endif;
    }

    /** 
     * Get settings fields
     */
    public function zoho_sync_lenders_cron_field_callback() {
        $other_attributes = array( 'id' => 'm118-zoho-cron-sync-btn-lenders' );
        submit_button( __( 'Sync Lenders', 'mz-zoho-crm' ), 'm118-zoho-cron-sync-btn', 'm118_zoho_settings_options[do_sync_zoho_lenders]', false, $other_attributes );
        $other_attributes = array( 'id' => 'm118-zoho-cron-sync-btn-faqs' );
        submit_button( __( 'Sync FAQs', 'mz-zoho-crm' ), 'm118-zoho-cron-sync-btn', 'm118_zoho_settings_options[do_sync_zoho_faqs]', false, $other_attributes );
        $other_attributes = array( 'id' => 'm118-zoho-cron-sync-btn-fees' );
        submit_button( __( 'Sync Fees', 'mz-zoho-crm' ), 'm118-zoho-cron-sync-btn', 'm118_zoho_settings_options[do_sync_zoho_fees]', false, $other_attributes );
        $other_attributes = array( 'id' => 'm118-zoho-cron-sync-btn-reset-all' );
        submit_button( __( 'Reset all from Zoho', 'mz-zoho-crm' ), 'm118-zoho-cron-sync-btn-reset-all', 'm118_zoho_settings_options[do_sync_zoho_reset_all]', false, $other_attributes );

    }

    public function do_authorization_request() {
        $authorized_data = array();
        if( isset( $_GET['code'] ) && $_GET['code'] ) {
            $this->options['code'] = $_GET['code'];
            $zoho_account_url = 'https://accounts.zoho.com';
            if( isset( $_GET['accounts-server'] ) && $_GET['accounts-server'] ){
                $zoho_account_url = $_GET['accounts-server'];
            }
            // do call oauth token 
            $body = array(
                'grant_type'    => 'authorization_code',
                'client_id'     => isset( $this->options['zoho_client_id'] ) ? $this->options['zoho_client_id'] : '',
                'client_secret' => isset( $this->options['zoho_client_secret'] ) ? $this->options['zoho_client_secret'] : '',
                'redirect_uri'  => admin_url( 'admin.php?page=m118-zoho-settings' ),
                'code'          => $_GET['code'],
            );
            $url = $zoho_account_url.'/oauth/v2/token';
            $response = wp_remote_post( $url, array(
                'method'    => 'POST',
                'body'      => $body,
            ) );
            $response = wp_remote_retrieve_body( $response );
            
            set_transient( 'm118_zoho_token_data', $response, HOUR_IN_SECONDS );
            $authorized_data['token_data'] = $response;
            $authorized_data['token_created'] = strtotime( date("Y-m-d H:i:s") );
            // set refresh token in authorized_data to handle token override
            $token_data = json_decode($response, true);
            if( isset( $token_data['refresh_token'] ) ) {
                $authorized_data['refresh_token'] = $token_data['refresh_token'];
            }
        }
        if( isset( $_GET['location'] ) && $_GET['location'] ) {
            $authorized_data['location'] = $_GET['location'];
        }
        if( isset( $_GET['accounts-server'] ) && $_GET['accounts-server'] ) {
            $authorized_data['accounts-server'] = $_GET['accounts-server'];
        }
        // update authorized data
        if( $authorized_data )
            update_option( 'm118_zoho_authorized_data', $authorized_data );

        // Log the task
        doLogM118Zoho( 'Logged - M118_Zoho_Admin_Settings > do_authorization_request @'.date("Y-m-d H:i:s") );
    }

}
return new M118_Zoho_Admin_Settings();