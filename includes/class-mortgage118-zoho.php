<?php

defined( 'ABSPATH' ) || exit;

/**
 * Main Mortgage118_Zoho Class.
 *
 * @class Mortgage118_Zoho
 */
final class Mortgage118_Zoho {
	/**
	 * Mortgage118_Zoho version.
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * The single instance of the class.
	 *
	 * @var Mortgage118_Zoho
	 */
	protected static $_instance = null;

	/**
	 * Main Mortgage118_Zoho Instance.
	 *
	 * Ensures only one instance of Mortgage118_Zoho is loaded or can be loaded.
	 *
	 * @return Mortgage118_Zoho - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'mz-zoho-crm' ), '1.0.0' );
	}

	/**
	 * Mortgage118_Zoho Constructor.
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Define Mortgage118_Zoho Constants.
	 */
	private function define_constants() {
		if ( ! defined( 'MZ_ABSPATH' ) ) define( 'MZ_ABSPATH', dirname( MZ_PLUGIN_FILE ) . '/' );
		if ( ! defined( 'MZ_VERSION' ) ) define( 'MZ_VERSION', $this->version );
	}

	/**
	 * Hook into actions and filters.
	 */
	private function init_hooks() {
		register_activation_hook( MZ_PLUGIN_FILE, array( 'M118_Zoho_Install', 'install' ) );
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {

		include_once MZ_ABSPATH . 'includes/m118z-core-functions.php';
		include_once MZ_ABSPATH . 'includes/m118z-lenders-functions.php';

		include_once MZ_ABSPATH . 'includes/class-m118z-install.php';
		include_once MZ_ABSPATH . 'includes/api/class-m118z-api.php';
		include_once MZ_ABSPATH . 'includes/class-m118z-data.php';
		include_once MZ_ABSPATH . 'includes/class-m118z-cron.php';
		
		if ( is_admin() ) {
			include_once MZ_ABSPATH . 'includes/admin/class-m118z-admin.php';
		}
	}

	/**
	 * Init Initialises.
	 */
	public function init() {
		// Set up localisation.
		$this->load_plugin_textdomain();
	}


	/**
	 * Load Localisation files.
	 */
	public function load_plugin_textdomain() {
		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
		} else {
			// @todo Remove when start supporting WP 5.0 or later.
			$locale = is_admin() ? get_user_locale() : get_locale();
		}

		$locale = apply_filters( 'plugin_locale', $locale, 'mz-zoho-crm' );

		unload_textdomain( 'mz-zoho-crm' );
		load_textdomain( 'mz-zoho-crm', WP_LANG_DIR . '/mortgage118-zoho/mz-zoho-crm-' . $locale . '.mo' );
		load_plugin_textdomain( 'mz-zoho-crm', false, plugin_basename( dirname( MZ_PLUGIN_FILE ) ) . '/languages' );
	}


	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', MZ_PLUGIN_FILE ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( MZ_PLUGIN_FILE ) );
	}
}