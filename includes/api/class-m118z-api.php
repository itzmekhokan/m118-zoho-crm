<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * M118_Zoho_API class.
 *
 * Communicates with Zoho API.
 */
class M118_Zoho_API {

	/**
	 * Zoho API Constants
	 */
    const API_DOMAIN = 'https://www.zohoapis.com';
    const ACCOUNT_DOMAIN = 'https://accounts.zoho.com';
    const SCOPES    = 'ZohoCRM.users.ALL,ZohoCRM.org.ALL,ZohoCRM.modules.ALL,ZohoCRM.bulk.ALL,ZohoCRM.notifications.READ,ZohoCRM.notifications.CREATE,ZohoCRM.notifications.UPDATE,ZohoCRM.notifications.DELETE,ZohoCRM.coql.READ,ZohoCRM.modules.ALL,ZohoCRM.settings.ALL,ZohoCRM.settings.modules.ALL,ZohoCRM.settings.fields.ALL,ZohoCRM.settings.layouts.ALL,ZohoCRM.org.ALL,ZohoCRM.org.READ,ZohoCRM.notifications.ALL,ZohoCRM.notifications.CREATE,ZohoCRM.notifications.WRITE,ZohoCRM.notifications.UPDATE,ZohoCRM.notifications.DELETE,ZohoCRM.bulk.READ,ZohoCRM.modules.notes.ALL,ZohoCRM.modules.notes.READ,ZohoCRM.modules.notes.WRITE,ZohoCRM.modules.notes.CREATE,ZohoCRM.modules.notes.DELETE,ZohoCRM.functions.execute.READ,ZohoCRM.functions.execute.CREATE,ZohoCRM.bulk.ALL,ZohoCRM.files.CREATE,ZohoCRM.coql.READ,ZohoCRM.chat.slashcommand.READ,ZohoCRM.send_mail.accounts.Create,ZohoCRM.send_mail.contacts.Create,ZohoCRM.send_mail.custom.Create,ZohoCRM.modules.leads.ALL,ZohoCRM.modules.accounts.ALL,ZohoCRM.modules.contacts.ALL,ZohoCRM.modules.deals.ALL,ZohoCRM.modules.custom.ALL,ZohoCRM.mass_update.leads.UPDATE,ZohoCRM.mass_update.accounts.UPDATE,ZohoCRM.mass_update.contacts.UPDATE,ZohoCRM.mass_update.deals.UPDATE,ZohoCRM.mass_update.campaigns.UPDATE,ZohoCRM.mass_update.activities.UPDATE,ZohoCRM.mass_update.solutions.UPDATE,ZohoCRM.mass_update.products.UPDATE,ZohoCRM.mass_update.vendors.UPDATE,ZohoCRM.mass_update.pricebooks.UPDATE,ZohoCRM.mass_update.quotes.UPDATE,ZohoCRM.mass_update.salesorders.UPDATE,ZohoCRM.mass_update.purchaseorders.UPDATE,ZohoCRM.mass_update.invoices.UPDATE,ZohoCRM.mass_update.custom.UPDATE,ZohoCRM.Files.READ,ZohoCRM.modules.attachments.all,ZohoCRM.send_mail.all.CREATE,ZohoCRM.signals.ALL';

	/**
	 * Zoho account domain.
	 *
	 * @var string
	 */
	private static $account_domain = self::ACCOUNT_DOMAIN;

    /**
	 * Zoho token data.
	 *
	 * @var string
	 */
	private static $token_data = null;

    /**
	 * Zoho token data.
	 *
	 * @var string
	 */
	private static $authorize_data = null;

    /**
	 * Constructor.
	 */
	public function __construct() {
        $authorizeData = get_m118_zoho_authorized_data();
        self::$authorize_data = $authorizeData;
        self::$token_data = json_decode($authorizeData['token_data'], true);

        add_filter( 'https_local_ssl_verify', '__return_true' );
        add_action( 'http_api_curl', array( $this, 'set_custom_curl_timeout' ), 9999, 1 );
        add_filter( 'http_request_timeout', array( $this, 'set_custom_http_request_timeout' ), 9999 );
        add_filter( 'http_request_args', array( $this, 'set_custom_http_request_args' ), 9999, 1 );
        // Check zoho token validity and auto refresh token if needed
        self::check_token();
        
    }

    public function set_custom_curl_timeout( $handle ){
        curl_setopt( $handle, CURLOPT_CONNECTTIMEOUT, 30 ); // 30 seconds. Too much for production, only for testing.
        curl_setopt( $handle, CURLOPT_TIMEOUT, 30 ); // 30 seconds. Too much for production, only for testing.
    }

    public function set_custom_http_request_timeout( $timeout_value ) {
        return 30; // 30 seconds. Too much for production, only for testing.
    }

    public function set_custom_http_request_args( $r ){
        $r['timeout'] = 30; // 30 seconds. Too much for production, only for testing.
        return $r;
    }

	/**
	 * Set zoho account domain.
	 *
	 * @param string $key
	 */
	public static function set_account_domain( $domain ) {
		self::$account_domain = esc_url( $domain );
	}

    /**
	 * get authorize data.
	 *
	 * @return array 
	 */
	public static function get_authorize_data() {
		return self::$authorize_data;
	}

    /**
	 * get token data.
	 *
	 * @return array 
	 */
	public static function get_token_data() {
		return self::$token_data;
	}

	/**
	 * Get zoho account domain.
	 *
	 * @return string
	 */
	public static function get_account_domain() {
		if ( ! self::$authorize_data ) {
			return self::$account_domain;
		} else {
            $domain = self::$authorize_data['accounts-server'];
            self::$account_domain = esc_url( $domain );
        }
		return self::$account_domain;
	}

    /**
	 * Get zoho api-domain.
	 *
	 * @return string
	 */
	public static function get_api_domain() {
        $authorize_data = self::get_authorize_data();
        $domain = '';
		if ( ! isset( $authorize_data['location'] ) ) {
			switch ($authorize_data['location']) {
                case 'eu':
                    $domain = 'https://www.zohoapis.eu';
                    break;

                case 'au':
                    $domain = 'https://www.zohoapis.com.au';
                    break;

                case 'in':
                    $domain = 'https://www.zohoapis.in';
                    break;

                case 'cn':
                    $domain = 'https://www.zohoapis.com.cn';
                    break;

                case 'jp':
                    $domain = 'https://www.zohoapis.jp';
                    break;
                
                default:
                    $domain = 'https://www.zohoapis.com';
                    break;
            }
		} else {
            $domain = self::API_DOMAIN;
        }
        // do override with com ( currently )
        $domain = self::API_DOMAIN;

		return $domain;
	}
    

	/**
	 * Check zoho token
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public static function check_token() {
		$currentTime = strtotime(date("Y-m-d H:i:s"));
        $tokenCreatedTime = self::$authorize_data['token_created'];
        $tokenExpiryTime = $tokenCreatedTime + 3600; 
        $tokenCreatedModifiedTime = $tokenExpiryTime - 600; // minus 10 minutes
        //print_r(array(date("Y-m-d H:i:s", $tokenCreatedTime), date("Y-m-d H:i:s", $tokenExpiryTime),date("Y-m-d H:i:s", $tokenCreatedModifiedTime), date("Y-m-d H:i:s")));
        if( $currentTime > $tokenCreatedModifiedTime ) {
            self::refresh_token();  
            
        }
	}

    /**
	 * Generates zoho refresh token.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
    public static function refresh_token() {
        $refresh_token = self::$authorize_data['refresh_token'];
        
        $client_id = get_m118_zoho_settings( 'zoho_client_id' );
        $client_secret = get_m118_zoho_settings( 'zoho_client_secret' );
        $domain = trim( self::get_account_domain() );
        $url = $domain.'/oauth/v2/token?refresh_token='.$refresh_token.'&client_id='.$client_id.'&client_secret='.$client_secret.'&grant_type=refresh_token';

        $response = wp_remote_post( $url );
        $response = wp_remote_retrieve_body( $response );
    
        $response = json_decode( $response, true );
    
        $authorized_data = self::$authorize_data;
   
        if( isset( $response['access_token'] ) ) {
            set_transient( 'm118_zoho_token_data', json_encode($response), HOUR_IN_SECONDS );
            $authorized_data['initial_token_data'] = json_encode(self::$token_data);
            $authorized_data['token_data'] = json_encode($response);
            $authorized_data['token_created'] = strtotime( date("Y-m-d H:i:s") );

            update_option( 'm118_zoho_authorized_data', $authorized_data );

            // Log the task
            doLogM118Zoho( 'Logged - M118_Zoho_API > refresh_token @'.date("Y-m-d H:i:s") );

            return true;
        }
        return false;
    }

	/**
	 * Generates the headers to pass to API request.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public static function get_headers() {
		$token_data = self::get_token_data();

        if( !isset( $token_data['access_token'] ) ) return array();

        return array(
            'Authorization' => 'Zoho-oauthtoken ' . $token_data['access_token'],
        );
	}

    public static function get_modules( $module_name = '', $record_id = '' ) {
        $api = '/crm/v2/';
        
        $api = ( $module_name ) ? $api. $module_name : $api . 'settings/modules';
        if( $record_id ) {
            $api .= '/'.$record_id;
        }

        $data = self::retrieve( $api );
        
        return $data;
    }

	public static function get_related_records( $record_id = '', $module_name = '', $related = 'Products' ) {
		if( !$record_id || !$module_name ) return false;

        $api = '/crm/v2/';
        
        $api = $api. $module_name . '/' . $record_id . '/' . $related;

        $data = self::retrieve( $api );
        
        return $data;
    }

	public static function get_record_image( $record_id = '', $module_name = '', $type = 'photo' ) {
		if( !$record_id || !$module_name ) return false;

        $api = '/crm/v2/';
        
        $api = $api. $module_name . '/' . $record_id . '/' . $type;

        $data = self::retrieve( $api, true );
        
        return $data;
    }

	public static function get_record_attachments( $record_id = '', $module_name = '', $attachment_id = '', $type = 'Attachments' ) {
		if( !$record_id || !$module_name ) return false;

        $api = '/crm/v2/';
        
        $api = $api. $module_name . '/' . $record_id . '/' . $type;
		if( $attachment_id ) {
			$api = $api. '/'. $attachment_id;
			$data = self::retrieve( $api, true );
		} else {
			$data = self::retrieve( $api );
		}    
        
        return $data;
    }

	/**
	 * Retrieve API endpoint.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param string $api
	 */
	public static function retrieve( $api, $raw_body = false ) {
		doLogM118Zoho( 'Logged @' .date("Y-m-d H:i:s").' - M118_Zoho_API > retrieve $api - '.$api );

        $domain = trim( self::get_api_domain() );

        $url = $domain . $api;
        doLogM118Zoho( 'Logged @' .date("Y-m-d H:i:s").' - M118_Zoho_API > retrieve > url - '.$url );
		$response = wp_safe_remote_get(
			$url,
			[
				'method'  => 'GET',
				'headers' => self::get_headers(),
				'timeout' => 70,
			]
		);
        
		if ( is_wp_error( $response ) || empty( $response['body'] ) ) {
			doLogM118Zoho( 'Logged @' .date("Y-m-d H:i:s"). ' - M118_Zoho_API > retrieve $api Error Response: ' . print_r( $response, true ) );
		}

		if( $raw_body ) return $response['body'];

		return json_decode( $response['body'], true );
	}
	
}
return new M118_Zoho_API();