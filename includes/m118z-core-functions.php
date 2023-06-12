<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function get_m118_zoho_settings( $key= '' ) {
	$settings = get_option( 'm118_zoho_settings_options' );
	if( $key ) {
		return isset( $settings[$key] ) ? $settings[$key] : '';
	}else{
		return $settings;
	}
}

function get_m118_zoho_authorized_data( $key= '', $subkey = '' ) {
	$settings = get_option( 'm118_zoho_authorized_data' );
	
	if( $key ) {
		$data = isset( $settings[$key] ) ? $settings[$key] : '';
		if( $key == 'token_data' ) {
			$data = json_decode( $data, true );
		}
		if( $subkey ) {
			return isset( $data[$subkey] ) ? $data[$subkey] : '';
		}
		return $data;
	}else{
		return $settings;
	}
}

if (!function_exists('doLogM118Zoho')) {
    /**
     * Write to log file
     */
    function doLogM118Zoho($str) {
        $file = Mortgage118_Zoho()->plugin_path() . '/log/m118-zoho.log';
        if (file_exists($file)) {
            //            $temphandle = @fopen($file, 'w+'); // @codingStandardsIgnoreLine.
            //            @fclose($temphandle); // @codingStandardsIgnoreLine.
            //            if (defined('FS_CHMOD_FILE')) {
            //                @chmod($file, FS_CHMOD_FILE); // @codingStandardsIgnoreLine.
            //            }
            // Open the file to get existing content
            $current = file_get_contents($file);
            if ($current) {
                // Append a new content to the file
                $current .= "$str" . "\r\n";
                $current .= "-------------------------------------\r\n";
            } else {
                $current = "$str" . "\r\n";
                $current .= "-------------------------------------\r\n";
            }
            // Write the contents back to the file
            file_put_contents($file, $current);
        }
    }
}