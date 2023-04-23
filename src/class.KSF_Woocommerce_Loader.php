<?php
/**
 * Woocommerce plugin Loader base class
 *
 * @package KSF_Woocommerce
 * @since   22.11.20
 */

defined( 'ABSPATH' ) || exit;


/** From Woo Square
require_once plugin_dir_path( __FILE__ ) . 'vendor/woocommerce/action-scheduler/action-scheduler.php';

if ( ! defined( 'WC_SQUARE_PLUGIN_VERSION' ) ) {
	define( 'WC_SQUARE_PLUGIN_VERSION', '3.3.0' ); // WRCS: DEFINED_VERSION.
}

if ( ! defined( 'WC_SQUARE_PLUGIN_URL' ) ) {
	define( 'WC_SQUARE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'WC_SQUARE_PLUGIN_PATH' ) ) {
	define( 'WC_SQUARE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}
**/

require_once( 'vendor/ksfraser/wordpress/class.KSF_Wordpress_Loader.php' );


/**
 * Main KSF_Wordpress_Loader Class.
 *
 * Took the WooCommerce v3.6 class, copied, modified.
 * I am thinking that pretty well every plugin for WordPress is
 * going to have a set of common actions to setup the plugin.  It
 * is what you do afterwards that will change.
 *
 * Also compared against Woocommerce_Square_Loader
 *
 * @class KSF_Books
 */
class KSF_Woocommerce_Loader extends KSF_Wordpress_Loader {

	/**
	 * KSF_Woocommerce version.
	 *
	 * @var string
	 */
	public $version = '22.11.20';

/*** Inherited
	protected static $_instance = null;
	public $session = null;
	public $eventloop = null;
	public $integrations = null;
	public $structured_data = null;
	public $deprecated_hook_handlers = array();
***/

	/**
	 * Constructs the class.
	 *
	 * @since 2.0.0
	 */
	protected function __construct() {
		$this->plugin_name =  'Square for WooCommerce';
		parent::__construct();
/***Inherited
		add_action( 'admin_init', array( $this, 'add_plugin_notices' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ), 15 );
***/

		// if the environment check fails, don't initialize the plugin.
		if ( $this->is_environment_compatible() ) {
			//add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );	//Inherited
			add_action( 'woocommerce_blocks_payment_method_type_registration', array( $this, 'register_payment_method_block_integrations' ), 5, 1 );
			add_action( 'before_woocommerce_init', array( $this, 'declare_hpos_compatibility' ) );
		}
	}

/*** Inherited
	public function __clone() {
	public function __wakeup() {
	public function init_plugin() {
		throw new Exception( "This function " . __FUNCTION__ . " must be overwridden!!" );
	}
***/

	/**
	 * Loads the base framework classes.
	 *
	 * @since 2.0.0
	 */
	protected function load_framework() {
		throw new Exception( "This function " . __FUNCTION__ . " must be overwridden!!" );
/*
		require_once plugin_dir_path( __FILE__ ) . 'includes/Framework/Plugin.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/Framework/PaymentGateway/Payment_Gateway_Plugin.php';
*/
	}

	/**
	 * Adds notices for out-of-date WordPress and/or WooCommerce versions.
	 *
	 * @since 2.0.0
	 */
	public function add_plugin_notices() {

		parent::add_plugin_notices();
		if ( ! $this->is_wc_compatible() ) {

			$this->add_admin_notice(
				'update_woocommerce',
				'error',
				sprintf(
					'%1$s requires WooCommerce version %2$s or higher. Please %3$supdate WooCommerce%4$s to the latest version, or %5$sdownload the minimum required version &raquo;%6$s',
					'<strong>' . self::$plugin_name . '</strong>',
					self::MINIMUM_WC_VERSION,
					'<a href="' . esc_url( admin_url( 'update-core.php' ) ) . '">',
					'</a>',
					'<a href="' . esc_url( 'https://downloads.wordpress.org/plugin/woocommerce.' . self::MINIMUM_WC_VERSION . '.zip' ) . '">',
					'</a>'
				)
			);
		}
	}
        /**
         * Determines if the required plugins are compatible.
         *
         * @since 2.0.0
         *
         * @return bool
         */
        protected function plugins_compatible() {

                return parent::plugins_compatible() && $this->is_wc_compatible();
        }


/*** Inherited
	protected function is_wp_compatible() {
***/

	/**
	 * Determines if the WooCommerce compatible.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	protected function is_wc_compatible() {

		if ( ! self::MINIMUM_WC_VERSION ) {
			return true;
		}

		return defined( 'WC_VERSION' ) && version_compare( WC_VERSION, self::MINIMUM_WC_VERSION, '>=' );
	}

/*** Inherited
	protected function deactivate_plugin() {
	public function add_admin_notice( $slug, $class, $message ) {
	public function admin_notices() {
***/


	/**
	 * Determines if the server environment is compatible with this plugin.
	 *
	 * Override this method to add checks for more than just the PHP version.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function is_environment_compatible() {
		parent::is_environment_compatible();
		if ( ! $is_opcache_config_valid ) {
			$error_message .= sprintf(
				// translators: link to documentation
				esc_html__( '&bull;&nbsp;<strong>Invalid OPcache config: </strong><a href="%s" target="_blank">Please ensure the <code>save_comments</code> PHP option is enabled.</a> You may need to contact your hosting provider to change caching options.', $this->plugin_tag ),
				'https://woocommerce.com/document/' . $this->plugin_tag . '/#section-43'
			);
		}
		if ( ! empty( $error_message ) ) {
			$this->add_admin_notice(
				'bad_environment',
				'error',
				$error_message
			);
		}

		return $is_php_valid && $is_opcache_config_valid;
	}

	/**
	 * Declares support for HPOS.
	 */
	public function declare_hpos_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}

/*** Inherited
	protected function is_opcache_save_message_enabled() {
	protected function is_php_version_valid() {
	public static function instance() {
	public function __get( $key ) {
	public function add_shortcode( $code, $fcn )
	public function on_plugins_loaded() {
	private function define( $name, $value ) {
	private function is_request( $type ) {
	public function css_admin()
	public function plugin_row_meta()
	public function load_script_style()
	public function load_admin_script_style()
	public function load_plugin()
	public function ksf_books_menu() {
	public function log_errors() {
	private function define_constants() {
	public function includes() {
	public function options()
	private function theme_support_includes() {
	public function frontend_includes() {
	public function include_template_functions() {
	public function init() {
	public function load_plugin_textdomain() {
	public function setup_environment() {
	private function add_thumbnail_support() {
	public function add_image_sizes() {
	public function plugin_url() {
	public function plugin_path() {
	public function template_path() {
	public function ajax_url() {
	public function api_request_url( $request, $ssl = null ) {
	private function load_webhooks() {
	public function initialize_cart() {
	public function initialize_session() {
	public function wpdb_table_fix() {
	public function activated_plugin( $filename ) {
	public function deactivated_plugin( $filename ) {
	public function eventloop()
***/


	/**
	 * Register the Square Credit Card checkout block integration class
	 *
	 * @since 2.5.0
	 *  /
	public function register_payment_method_block_integrations( $payment_method_registry ) {
		if ( class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			$payment_method_registry->register( new WooCommerce\Square\Gateway\Blocks_Handler() );
		}
	}
	*/

	/**
	 * Hook into actions and filters.
	 *
	 * @since 1.2.0
	 */
	private function init_hooks() {
		parent::init_hooks();
/*
		register_activation_hook( KSF_Books_PLUGIN_FILE, array( 'KSF_Books_Install', 'install' ) );
		register_deactivation_hook( KSF_Books_PLUGIN_FILE, array( 'KSF_Books_Install', 'uninstall' ) );
		register_shutdown_function( array( $this, 'log_errors' ) );

		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ), -1 );
		add_action( 'after_setup_theme', array( $this, 'setup_environment' ) );
		add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 11 );
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'init', array( 'KSF_Books_Shortcodes', 'init' ) );
		add_action( 'init', array( 'KSF_Books_Emails', 'init_transactional_emails' ) );
		add_action( 'init', array( $this, 'add_image_sizes' ) );
		add_action( 'switch_blog', array( $this, 'wpdb_table_fix' ), 0 );
		add_action( 'activated_plugin', array( $this, 'activated_plugin' ) );
		add_action( 'deactivated_plugin', array( $this, 'deactivated_plugin' ) );
		add_action( 'activated_plugin', array( 'KSF_Books_Admin', 'activated_plugin' ) );
		add_action( 'deactivated_plugin', array( 'KSF_Books_Admin', 'deactivated_plugin' ) );

		add_action("init", array( $this, array( 'KSF_Books_Post_Series', "custom_post_type" ), 2);
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array($this, 'load_plugin'), 11 );
         	add_action( 'admin_enqueue_scripts', array($this, 'load_admin_script_style'));
         	add_action( 'wp_enqueue_scripts',  array($this, 'load_script_style'));
         	add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
         	add_filter( 'admin_footer', array( $this, 'css_admin' ), 10, 2 );
*/

	}

}
