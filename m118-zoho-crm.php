<?php
/**
 * Plugin Name: Mortgage118 Zoho CRM
 * Description: A toolkit to integrate Zoho CRM API for Mortgage118.
 * Version: 1.0.0
 * Author: itzmekhokan
 * Text Domain: mz-zoho-crm
 * Domain Path: /languages/
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'MZ_PLUGIN_FILE' ) ) {
	define( 'MZ_PLUGIN_FILE', __FILE__ );
}

// Include the main Mortgage_Zoho class.
if ( ! class_exists( 'Mortgage118_Zoho', false ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-mortgage118-zoho.php';
}

/**
 * Returns the main instance of Mortgage118_Zoho.
 */
function Mortgage118_Zoho() { 
	return Mortgage118_Zoho::instance();
}

// Global 
$GLOBALS['m118_zoho'] = Mortgage118_Zoho();