<?php
/**
 * KSF_Books setup
 *
 * @package KSF_Books
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/***
* To Use:
*	KSF_Wordpress_Loader::instance();
*	but use the inheriting class's name
*/


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


/**************************
* CHeck out skyverge/wc-plugin-framework for API, Admin, etc...
*/


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
 * @class KSF_Wordpress_Loader
 */
class KSF_Wordpress_Loader {

	/** minimum PHP version required by this plugin */
	protected $MINIMUM_PHP_VERSION = '5.2.0';

	/** minimum WordPress version required by this plugin */
	protected $MINIMUM_WP_VERSION = '4.6';

	/** minimum WooCommerce version required by this plugin */
	protected $MINIMUM_WC_VERSION = '3.0';

	/**
	 * SkyVerge plugin framework version used by this plugin
	 * Constant is left as it is for legacy purposes.
	 **/
	protected $FRAMEWORK_VERSION = '5.4.0';

	/** the plugin name, for displaying notices */
	protected $plugin_name;
	protected $plugin_tag;
	protected $plugin_dir;

	/** @var array the admin notices to add */
	protected $notices = array();

	/** @var array list of variables for __get to return
	protected $inaccessible_arr;


	/**
	 * KSF_Books version.
	 *
	 * @var string
	 */
	public $version = '22.12.01';

	/**
	 * The single instance of the class.
	 *
	 * @var single instance of this class
	 * @since 1.2.0
	 */
	protected static $_instance = null;

	/**
	 * Session instance.
	 *
	 * @var KSF_Books_Session|KSF_Books_Session_Handler
	 */
	public $session = null;
	public $eventloop = null;

	protected $onPluginLoadedFcn;

	/**
	 * Integrations instance.
	 *
	 * @var KSF_Books_Integrations
	 */
	public $integrations = null;

	/**
	 * Structured data instance.
	 *
	 * @var KSF_Books_Structured_Data
	 */
	public $structured_data = null;

	/**
	 * Array of deprecated hook handlers.
	 *
	 * @var array of KSF_Books_Deprecated_Hooks
	 */
	public $deprecated_hook_handlers = array();

	/**
	 * Constructs the class.
	 *
	 * @since 2.0.0
	 */
	protected function __construct() {
		$this->inaccessible_arr = array( 'eventloop' );

		register_activation_hook( __FILE__, array( $this, 'activation_check' ) );	//from skyverge wc-plugin-framework

		add_action( 'admin_init', array( $this, 'add_plugin_notices' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ), 15 );

		// if the environment check fails, don't initialize the plugin.
		if ( $this->is_environment_compatible() ) {
			add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
		}
	}
	/**
	 * KSF_Books Constructor.
	 * /
	public function __construct() {
		throw new Exception( __LINE__ );
		$this->define_constants();
		$this->define_tables();
		$this->includes();
		$this->init_hooks();
		//$this->add_shortcode( 'ksf_book', 'ksf_book_fcn' );
			foreach( $this->shortcode_arr as $shortcode => $func )
			{
				$this->add_shortcode( $shortcode, $func )
			}
	}
	*/

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @since 1.2.0
	 * @static
	 * @see WC()
	 * @return Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		/**These are in the ksf_books constructor ****/
			$this->define_constants();
			$this->define_tables();
			$this->includes();
			$this->init_hooks();
			foreach( $this->shortcode_arr as $shortcode => $func )
			{
				$this->add_shortcode( $shortcode, $func );
			}
		/** ! These are in the ksf_books constructor ****/
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.2.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden. %s', get_class( $this ) ), $this->version );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.2.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class %s is forbidden.', get_class( $this ) ), $this->version );
	}

	/**
	 * Initializes the plugin.
	 *
	 * @since 2.0.0
	 */
	public function init_plugin() {

		throw new Exception( "This function " . __FUNCTION__ . " must be overwridden!!  See " . get_class( $this ) . " for instructions on HOW" );
/*

		if ( ! $this->plugins_compatible() ) {
			return;
		}

		$this->load_framework();

/** If the plugin is structured for PSR-4, do the following:

		// autoload plugin and vendor files
		$loader = require_once( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' );

		// register plugin namespace with autoloader
		$loader->addPsr4( 'SkyVerge\\WooCommerce\\Plugin_Name\\', __DIR__ . '/includes' ); // TODO: plugin namespace here

		// depending on how the plugin is structured, you may need to manually load the file that contains the initial plugin function
		// require_once( plugin_dir_path( __FILE__ ) . 'includes/Functions.php' ); // TODO: maybe load a file to call your initialization function

		/****************** /

		/** Otherwise, for plugins that use the traditional WordPress class-class-name.php structure, simply include the main plugin file:

		// load the main plugin class
		require_once( plugin_dir_path( __FILE__ ) . 'class-wc-framework-plugin.php' ); // TODO: main plugin class file

		******************* /

		// fire it up!
		wc_framework_plugin(); // TODO: call the main plugin method

*/
	}

	/**
	 * Loads the base framework classes.
	 *
	 * @since 2.0.0
	 */
	protected function load_framework() {
		throw new Exception( "This function " . __FUNCTION__ . " must be overwridden!!" );
/*


		if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\' . $this->get_framework_version_namespace() . '\\SV_WC_Plugin' ) ) {
			require_once( plugin_dir_path( __FILE__ ) . 'lib/skyverge/woocommerce/class-sv-wc-plugin.php' );
		}

		// TODO: remove this if not a payment gateway
		if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\' . $this->get_framework_version_namespace() . '\\SV_WC_Payment_Gateway_Plugin' ) ) {
			require_once( plugin_dir_path( __FILE__ ) . 'lib/skyverge/woocommerce/payment-gateway/class-sv-wc-payment-gateway-plugin.php' );
		}

 *** OR ***
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

		if ( ! $this->is_wp_compatible() ) {

			$this->add_admin_notice(
				'update_wordpress',
				'error',
				sprintf(
					'%s requires WordPress version %s or higher. Please %supdate WordPress &raquo;%s',
					'<strong>' . self::$this->plugin_name .
					'</strong>',
					self::$MINIMUM_WP_VERSION,
					'<a href="' . esc_url( admin_url( 'update-core.php' ) ) . '">',
					'</a>'
				)
			);
		}

	}

	/**
	 * Gets the framework version in namespace form.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function get_framework_version_namespace() {

		return 'v' . str_replace( '.', '_', $this->get_framework_version() );
	}


	/**
	 * Gets the framework version used by this plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function get_framework_version() {

		return self::FRAMEWORK_VERSION;
	}

	/**
	 * Checks the server environment and other factors and deactivates plugins as necessary.
	 *
	 * Based on http://wptavern.com/how-to-prevent-wordpress-plugins-from-activating-on-sites-with-incompatible-hosting-environments
	 *
	 * @since 1.0.0
	 */
	public function activation_check() {

		if ( ! $this->is_environment_compatible() ) {

			$this->deactivate_plugin();

			wp_die( self::PLUGIN_NAME . ' could not be activated. ' . $this->get_environment_message() );
		}
	}

	/**
	 * Checks the environment on loading WordPress, just in case the environment changes after activation.
	 *
	 * @since 1.0.0
	 */
	public function check_environment() {

		if ( ! $this->is_environment_compatible() && is_plugin_active( plugin_basename( __FILE__ ) ) ) {

			$this->deactivate_plugin();

			$this->add_admin_notice( 'bad_environment', 'error', self::PLUGIN_NAME . ' has been deactivated. ' . $this->get_environment_message() );
		}
	}

	/**
	 * Gets the message for display when the environment is incompatible with this plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function get_environment_message() {

		$message = sprintf( 'The minimum PHP version required for this plugin is %1$s. You are running %2$s.', self::MINIMUM_PHP_VERSION, PHP_VERSION );

		return $message;
	}


	/**
	 * Determines if the required plugins are compatible.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	protected function plugins_compatible() {

		return $this->is_wp_compatible(); 
	}

	/**
	 * Determines if the WordPress compatible.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	protected function is_wp_compatible() {

		if ( ! self::$MINIMUM_WP_VERSION ) {
			return true;
		}

		return version_compare( get_bloginfo( 'version' ), self::$MINIMUM_WP_VERSION, '>=' );
	}

	/**
	 * Deactivates the plugin.
	 *
	 * @since 2.0.0
	 */
	protected function deactivate_plugin() {

		deactivate_plugins( plugin_basename( __FILE__ ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['activate'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			unset( $_GET['activate'] );
		}
	}

	/**
	 * Adds an admin notice to be displayed.
	 *
	 * @since 2.0.0
	 *
	 * @param string $slug the slug for the notice
	 * @param string $class the css class for the notice
	 * @param string $message the notice message
	 */
	public function add_admin_notice( $slug, $class, $message ) {

		$this->notices[ $slug ] = array(
			'class'   => $class,
			'message' => $message,
		);
	}
	/**
	 * Displays any admin notices added with \WooCommerce_Square_Loader::add_admin_notice()
	 *
	 * @since 2.0.0
	 */
	public function admin_notices() {

		foreach ( (array) $this->notices as $notice_key => $notice ) {

			?>
			<div class="<?php echo esc_attr( $notice['class'] ); ?>">
				<p>
					<?php
						echo wp_kses(
							$notice['message'],
							array(
								'a'      => array(
									'href'   => array(),
									'target' => array(),
								),
								'code'   => array(),
								'strong' => array(),
								'br'     => array(),
							)
						);
					?>
				</p>
			</div>
			<?php
		}
	}


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
		$is_php_valid            = $this->is_php_version_valid();
		$is_opcache_config_valid = $this->is_opcache_save_message_enabled();
		$error_message           = '';

		if ( ! $is_php_valid || ! $is_opcache_config_valid ) {
			$error_message .= sprintf(
				// translators: plugin name
				esc_html__( '<strong>All features in %1$s have been disabled</strong> due to unsupported settings:<br>', $this->plugin_tag ),
				self::$this->plugin_name
			);
		}

		if ( ! $is_php_valid ) {
			$error_message .= sprintf(
				// translators: minimum PHP version, current PHP version
				esc_html__( '&bull;&nbsp;<strong>Invalid PHP version: </strong>The minimum PHP version required is %1$s. You are running %2$s.<br>', $this->plugin_tag ),
				self::$MINIMUM_PHP_VERSION,
				PHP_VERSION
			);
		}

		if ( ! $is_opcache_config_valid ) {
			$error_message .= sprintf(
				// translators: link to documentation
				esc_html__( '&bull;&nbsp;<strong>Invalid OPcache config: </strong><a href="%s" target="_blank">Please ensure the <code>save_comments</code> PHP option is enabled.</a> You may need to contact your hosting provider to change caching options.', $this->plugin_tag ),
				'https://woocommerce.com/document/woocommerce-square/#section-43'
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
	 * Returns true if opcache.save_comments is enabled.
	 *
	 * @since 3.0.2
	 *
	 * @return boolean
	 */
	protected function is_opcache_save_message_enabled() {
		$zend_optimizer_plus = extension_loaded( 'Zend Optimizer+' ) && '0' === ( ini_get( 'zend_optimizerplus.save_comments' ) || '0' === ini_get( 'opcache.save_comments' ) );
		$zend_opcache        = extension_loaded( 'Zend OPcache' ) && '0' === ini_get( 'opcache.save_comments' );

		return ! ( $zend_optimizer_plus || $zend_opcache );
	}

	/**
	 * Returns true if the PHP version of the environment
	 * meets the requirement.
	 *
	 * @since 3.0.2
	 *
	 * @return boolean
	 */
	protected function is_php_version_valid() {
		return version_compare( PHP_VERSION, self::$MINIMUM_PHP_VERSION, '>=' );
	}


	/**
	 * Auto-load in-accessible properties on demand.
	 *
	 * This function NEEDS to be overridden.
	 *
	 * @param mixed $key Key name.
	 * @return mixed
	 */
	public function __get( $key ) {
		//if ( in_array( $key, array( 'payment_gateways', 'shipping', 'mailer', 'checkout' ), true ) ) {
		if ( in_array( $key, $this->inaccessible_arr, true ) ) {
			return $this->$key();
		}
	}

	public function add_shortcode( $code, $fcn )
	{
		add_shortcode( $code, $fcn );
	}

	/* Wmen WP has loaded all plugins, trigger the `KSF_Books_loaded` hook.
	 *
	 * This ensures `KSF_Books_loaded` is called only after all other plugins
	 * are loaded, to avoid issues caused by plugin directory naming changing
	 * the load order. 
	 *
	 * @since 1.2.0
	 */
	public function on_plugins_loaded() {
		do_action( $this->onPluginLoadedFcn );
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 1.2.0
	 */
	private function init_hooks() {
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

		add_action("init", array( $this, array( 'KSF_Books_Post_Series', "custom_post_type" ), 2) );
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array($this, 'load_plugin'), 11 );
         	add_action( 'admin_enqueue_scripts', array($this, 'load_admin_script_style'));
         	add_action( 'wp_enqueue_scripts',  array($this, 'load_script_style'));
         	add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
         	add_filter( 'admin_footer', array( $this, 'css_admin' ), 10, 2 );

	}
	public function css_admin()
	{
			?>
         		<style type="text/css">
		            .costcf7oc_rating_div {
		               width: 10%;
		               vertical-align: middle;
		            }
         		</style>
         	<?php
	}
	public function plugin_row_meta()
	{
		if ( KSFBOOKS_BASE_NAME === $file ) {
             	$row_meta = array(
                 	'rating'    =>  '<a href="#" target="_blank"><img src="'.$this->plugin_dir .'/includes/images/star.png" class="costcf7oc_rating_div"></a>',
             	);
             	return array_merge( $links, $row_meta );
         	}
         	return (array) $links;
	}
	public function load_script_style()
	{
	   	wp_enqueue_script( 'KSFBOOKS-front-js', $this->plugin_dir  . '/includes/js/front.js', false, '2.0.0' );
        	wp_enqueue_script( 'jquery-ui' );
         	wp_enqueue_script( 'jquery-ui-slider' );
         	wp_enqueue_script( 'jquery-touch-punch' );
         	wp_enqueue_style( 'KSFBOOKS-front-jquery-ui-css', $this->plugin_dir  . '/includes/js/jquery-ui.css', false, '2.0.0' );
         	wp_enqueue_style( 'KSFBOOKS-front-css', $this->plugin_dir  . '/includes/css/front-style.css', false, '2.0.0' );
	}
	public function load_admin_script_style()
	{
		 wp_enqueue_style( 'KSFBOOKS-back-style', $this->plugin_dir  . '/includes/css/back_style.css', false, '1.0.0' );
            	wp_enqueue_script( 'KSFBOOKS-back-script', $this->plugin_dir  . '/includes/js/back_script.js', false, '1.0.0' );
	}
	public function load_plugin()
	{
		/* This plugin doesn't currently depend on CF7
         	if ( ! ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) ) {
            	add_action( 'admin_notices', array($this,'KSFBOOKS_install_error') );
         	}
		*/
		/* ** Do we need a similar check for PODS?
		*/
	}
	public function ksf_books_menu() {
		add_options_page( 'KSF Books Options', 'KSF Books', 'manage_options', 'ksf_books_identifier', array( $this, 'options' ) );
	}
	public function options()
	{
		/*
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		 */
		echo '<div class="wrap">';
		echo '<p>Here is where the form would go if I actually had options.</p>';
		echo '</div>';
	}


	/**
	 * Ensures fatal errors are logged so they can be picked up in the status report.
	 *
	 * @since 1.2.0
	 */
	public function log_errors() {
		$error = error_get_last();
		if ( in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ), true ) ) {
			$logger = KSF_Books_get_logger();
			$logger->critical(
				/* translators: 1: error message 2: file name and path 3: line number */
				sprintf( __( '%1$s in %2$s on line %3$s', get_class( $this ) ), $error['message'], $error['file'], $error['line'] ) . PHP_EOL,
				array(
					'source' => 'fatal-errors',
				)
			);
			do_action( 'KSF_Books_shutdown_error', $error );
		}
	}

	/**
	 * Define WC Constants.
	 */
	private function define_constants() {
		$upload_dir = wp_upload_dir( null, false );

		$this->define( 'KSF_Books_ABSPATH', dirname( KSF_Books_PLUGIN_FILE ) . '/' );
		$this->define( 'KSF_Books_PLUGIN_BASENAME', plugin_basename( KSF_Books_PLUGIN_FILE ) );
		$this->define( 'KSF_Books_VERSION', $this->version );
		//$this->define( 'WOOCOMMERCE_VERSION', $this->version );
		//$this->define( 'KSF_Books_ROUNDING_PRECISION', 6 );
		//$this->define( 'KSF_Books_DISCOUNT_ROUNDING_MODE', 2 );
		//$this->define( 'KSF_Books_TAX_ROUNDING_MODE', 'yes' === get_option( 'KSF_Books_prices_include_tax', 'no' ) ? 2 : 1 );
		//$this->define( 'KSF_Books_DELIMITER', '|' );
		$this->define( 'KSF_Books_LOG_DIR', $upload_dir['basedir'] . '/KSF_Books-logs/' );
		$this->define( 'KSF_Books_SESSION_CACHE_GROUP', 'KSF_Books_session_id' );
		$this->define( 'KSF_Books_TEMPLATE_DEBUG_MODE', false );
		//$this->define( 'KSF_Books_NOTICE_MIN_PHP_VERSION', '5.6.20' );
		//$this->define( 'KSF_Books_NOTICE_MIN_WP_VERSION', '4.9' );
	}

	/**
	 * Register custom tables within $wpdb object.
	 */
	private function define_tables() {
		global $wpdb;

		// List of tables without prefixes.
		$tables = array(
			//'payment_tokenmeta'      => 'KSF_Books_payment_tokenmeta',
			//'order_itemmeta'         => 'KSF_Books_order_itemmeta',
			//'KSF_Books_product_meta_lookup' => 'KSF_Books_product_meta_lookup',
		);

		foreach ( $tables as $name => $table ) {
			$wpdb->$name    = $wpdb->prefix . $table;
			$wpdb->tables[] = $table;
		}
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Returns true if the request is a non-legacy REST API request.
	 *
	 * Legacy REST requests should still run some extra code for backwards compatibility.
	 *
	 * @todo: replace this function once core WP function is available: https://core.trac.wordpress.org/ticket/42061.
	 *
	 * @return bool
	 */
	public function is_rest_api_request() {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$rest_prefix         = trailingslashit( rest_get_url_prefix() );
		$is_rest_api_request = ( false !== strpos( $_SERVER['REQUEST_URI'], $rest_prefix ) ); // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		return apply_filters( 'KSF_Books_is_rest_api_request', $is_rest_api_request );
	}

	/**
	 * What type of request is this?
	 *
	 * @param  string $type admin, ajax, cron or frontend.
	 * @return bool
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) && ! $this->is_rest_api_request();
		}
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		/**
		 * Class autoloader.
		 */
		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-autoloader.php';

		/**
		 * Interfaces.
		 */
//		include_once KSF_Books_ABSPATH . 'includes/interfaces/class-KSF_Books-abstract-order-data-store-interface.php';
//		include_once KSF_Books_ABSPATH . 'includes/interfaces/class-KSF_Books-coupon-data-store-interface.php';
//		include_once KSF_Books_ABSPATH . 'includes/interfaces/class-KSF_Books-customer-data-store-interface.php';
//		include_once KSF_Books_ABSPATH . 'includes/interfaces/class-KSF_Books-customer-download-data-store-interface.php';
//		include_once KSF_Books_ABSPATH . 'includes/interfaces/class-KSF_Books-customer-download-log-data-store-interface.php';
//		include_once KSF_Books_ABSPATH . 'includes/interfaces/class-KSF_Books-object-data-store-interface.php';
//		include_once KSF_Books_ABSPATH . 'includes/interfaces/class-KSF_Books-order-data-store-interface.php';
//		include_once KSF_Books_ABSPATH . 'includes/interfaces/class-KSF_Books-order-item-data-store-interface.php';
//		include_once KSF_Books_ABSPATH . 'includes/interfaces/class-KSF_Books-order-item-product-data-store-interface.php';
//		include_once KSF_Books_ABSPATH . 'includes/interfaces/class-KSF_Books-order-item-type-data-store-interface.php';
//		include_once KSF_Books_ABSPATH . 'includes/interfaces/class-KSF_Books-order-refund-data-store-interface.php';
//		include_once KSF_Books_ABSPATH . 'includes/interfaces/class-KSF_Books-payment-token-data-store-interface.php';
//		include_once KSF_Books_ABSPATH . 'includes/interfaces/class-KSF_Books-product-data-store-interface.php';
//		include_once KSF_Books_ABSPATH . 'includes/interfaces/class-KSF_Books-product-variable-data-store-interface.php';
//		include_once KSF_Books_ABSPATH . 'includes/interfaces/class-KSF_Books-shipping-zone-data-store-interface.php';
//		include_once KSF_Books_ABSPATH . 'includes/interfaces/class-KSF_Books-logger-interface.php';
//		include_once KSF_Books_ABSPATH . 'includes/interfaces/class-KSF_Books-log-handler-interface.php';
//		include_once KSF_Books_ABSPATH . 'includes/interfaces/class-KSF_Books-webhooks-data-store-interface.php';
//		include_once KSF_Books_ABSPATH . 'includes/interfaces/class-KSF_Books-queue-interface.php';

		/**
		 * Abstract classes.
		 */
//		include_once KSF_Books_ABSPATH . 'includes/abstracts/abstract-KSF_Books-data.php';
//		include_once KSF_Books_ABSPATH . 'includes/abstracts/abstract-KSF_Books-object-query.php';
//		include_once KSF_Books_ABSPATH . 'includes/abstracts/abstract-KSF_Books-payment-token.php';
//		include_once KSF_Books_ABSPATH . 'includes/abstracts/abstract-KSF_Books-product.php';
//		include_once KSF_Books_ABSPATH . 'includes/abstracts/abstract-KSF_Books-order.php';
//		include_once KSF_Books_ABSPATH . 'includes/abstracts/abstract-KSF_Books-settings-api.php';
//		include_once KSF_Books_ABSPATH . 'includes/abstracts/abstract-KSF_Books-shipping-method.php';
//		include_once KSF_Books_ABSPATH . 'includes/abstracts/abstract-KSF_Books-payment-gateway.php';
//		include_once KSF_Books_ABSPATH . 'includes/abstracts/abstract-KSF_Books-integration.php';
//		include_once KSF_Books_ABSPATH . 'includes/abstracts/abstract-KSF_Books-log-handler.php';
//		include_once KSF_Books_ABSPATH . 'includes/abstracts/abstract-KSF_Books-deprecated-hooks.php';
//		include_once KSF_Books_ABSPATH . 'includes/abstracts/abstract-KSF_Books-session.php';
//		include_once KSF_Books_ABSPATH . 'includes/abstracts/abstract-KSF_Books-privacy.php';

		/**
		 * Core classes.
		 */
//		include_once KSF_Books_ABSPATH . 'includes/KSF_Books-core-functions.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-datetime.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-post-types.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-install.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-geolocation.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-download-handler.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-comments.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-post-data.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-ajax.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-emails.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-data-exception.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-query.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-meta-data.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-order-factory.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-order-query.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-product-factory.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-product-query.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-payment-tokens.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-shipping-zone.php';
//		include_once KSF_Books_ABSPATH . 'includes/gateways/class-KSF_Books-payment-gateway-cc.php';
//		include_once KSF_Books_ABSPATH . 'includes/gateways/class-KSF_Books-payment-gateway-echeck.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-countries.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-integrations.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-cache-helper.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-https.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-deprecated-action-hooks.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-deprecated-filter-hooks.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-background-emailer.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-discounts.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-cart-totals.php';
//		include_once KSF_Books_ABSPATH . 'includes/customizer/class-KSF_Books-shop-customizer.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-regenerate-images.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-privacy.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-structured-data.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-shortcodes.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-logger.php';
//		include_once KSF_Books_ABSPATH . 'includes/queue/class-KSF_Books-action-queue.php';
//		include_once KSF_Books_ABSPATH . 'includes/queue/class-KSF_Books-queue.php';
//		include_once KSF_Books_ABSPATH . 'includes/admin/marketplace-suggestions/class-KSF_Books-marketplace-updater.php';

		/**
		 * Data stores - used to store and retrieve CRUD object data from the database.
		 */
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-data-store.php';
//		include_once KSF_Books_ABSPATH . 'includes/data-stores/class-KSF_Books-data-store-wp.php';
//		include_once KSF_Books_ABSPATH . 'includes/data-stores/class-KSF_Books-coupon-data-store-cpt.php';
//		include_once KSF_Books_ABSPATH . 'includes/data-stores/class-KSF_Books-product-data-store-cpt.php';
//		include_once KSF_Books_ABSPATH . 'includes/data-stores/class-KSF_Books-product-grouped-data-store-cpt.php';
//		include_once KSF_Books_ABSPATH . 'includes/data-stores/class-KSF_Books-product-variable-data-store-cpt.php';
//		include_once KSF_Books_ABSPATH . 'includes/data-stores/class-KSF_Books-product-variation-data-store-cpt.php';
//		include_once KSF_Books_ABSPATH . 'includes/data-stores/abstract-KSF_Books-order-item-type-data-store.php';
//		include_once KSF_Books_ABSPATH . 'includes/data-stores/class-KSF_Books-order-item-data-store.php';
//		include_once KSF_Books_ABSPATH . 'includes/data-stores/class-KSF_Books-order-item-coupon-data-store.php';
//		include_once KSF_Books_ABSPATH . 'includes/data-stores/class-KSF_Books-order-item-fee-data-store.php';
//		include_once KSF_Books_ABSPATH . 'includes/data-stores/class-KSF_Books-order-item-product-data-store.php';
//		include_once KSF_Books_ABSPATH . 'includes/data-stores/class-KSF_Books-order-item-shipping-data-store.php';
//		include_once KSF_Books_ABSPATH . 'includes/data-stores/class-KSF_Books-order-item-tax-data-store.php';
//		include_once KSF_Books_ABSPATH . 'includes/data-stores/class-KSF_Books-payment-token-data-store.php';
//		include_once KSF_Books_ABSPATH . 'includes/data-stores/class-KSF_Books-customer-data-store.php';
//		include_once KSF_Books_ABSPATH . 'includes/data-stores/class-KSF_Books-customer-data-store-session.php';
//		include_once KSF_Books_ABSPATH . 'includes/data-stores/class-KSF_Books-customer-download-data-store.php';
//		include_once KSF_Books_ABSPATH . 'includes/data-stores/class-KSF_Books-customer-download-log-data-store.php';
//		include_once KSF_Books_ABSPATH . 'includes/data-stores/class-KSF_Books-shipping-zone-data-store.php';
//		include_once KSF_Books_ABSPATH . 'includes/data-stores/abstract-KSF_Books-order-data-store-cpt.php';
//		include_once KSF_Books_ABSPATH . 'includes/data-stores/class-KSF_Books-order-data-store-cpt.php';
//		include_once KSF_Books_ABSPATH . 'includes/data-stores/class-KSF_Books-order-refund-data-store-cpt.php';
//		include_once KSF_Books_ABSPATH . 'includes/data-stores/class-KSF_Books-webhook-data-store.php';

		/**
		 * REST API.
		 */
		include_once KSF_Books_ABSPATH . 'includes/legacy/class-KSF_Books-legacy-api.php';
		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-api.php';
		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-auth.php';
		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-register-wp-admin-settings.php';

		/**
		 * Blocks.
		 */
//		if ( file_exists( KSF_Books_ABSPATH . 'includes/blocks/class-KSF_Books-block-library.php' ) ) {
//			include_once KSF_Books_ABSPATH . 'includes/blocks/class-KSF_Books-block-library.php';
//		}

		/**
		 * Libraries
		 */
//		include_once KSF_Books_ABSPATH . 'includes/libraries/action-scheduler/action-scheduler.php';
//
//		if ( defined( 'WP_CLI' ) && WP_CLI ) {
//			include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-cli.php';
//		}
//
//		if ( $this->is_request( 'admin' ) ) {
//			include_once KSF_Books_ABSPATH . 'includes/admin/class-KSF_Books-admin.php';
//		}
//
		if ( $this->is_request( 'frontend' ) ) {
			$this->frontend_includes();
		}

		if ( $this->is_request( 'cron' ) && 'yes' === get_option( 'KSF_Books_allow_tracking', 'no' ) ) {
			include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-tracker.php';
		}

		$this->theme_support_includes();
//		$this->query = new KSF_Books_Query();
		$this->api   = new KSF_Books_API();
	}

	/**
	 * Include classes for theme support.
	 *
	 * @since 3.3.0
	 */
	private function theme_support_includes() {
		if ( KSF_Books_is_active_theme( array( 'twentynineteen', 'twentyseventeen', 'twentysixteen', 'twentyfifteen', 'twentyfourteen', 'twentythirteen', 'twentyeleven', 'twentytwelve', 'twentyten' ) ) ) {
			switch ( get_template() ) {
				case 'twentyten':
					include_once KSF_Books_ABSPATH . 'includes/theme-support/class-KSF_Books-twenty-ten.php';
					break;
				case 'twentyeleven':
					include_once KSF_Books_ABSPATH . 'includes/theme-support/class-KSF_Books-twenty-eleven.php';
					break;
				case 'twentytwelve':
					include_once KSF_Books_ABSPATH . 'includes/theme-support/class-KSF_Books-twenty-twelve.php';
					break;
				case 'twentythirteen':
					include_once KSF_Books_ABSPATH . 'includes/theme-support/class-KSF_Books-twenty-thirteen.php';
					break;
				case 'twentyfourteen':
					include_once KSF_Books_ABSPATH . 'includes/theme-support/class-KSF_Books-twenty-fourteen.php';
					break;
				case 'twentyfifteen':
					include_once KSF_Books_ABSPATH . 'includes/theme-support/class-KSF_Books-twenty-fifteen.php';
					break;
				case 'twentysixteen':
					include_once KSF_Books_ABSPATH . 'includes/theme-support/class-KSF_Books-twenty-sixteen.php';
					break;
				case 'twentyseventeen':
					include_once KSF_Books_ABSPATH . 'includes/theme-support/class-KSF_Books-twenty-seventeen.php';
					break;
				case 'twentynineteen':
					include_once KSF_Books_ABSPATH . 'includes/theme-support/class-KSF_Books-twenty-nineteen.php';
					break;
			}
		}
	}

	/**
	 * Include required frontend files.
	 */
	public function frontend_includes() {
//		include_once KSF_Books_ABSPATH . 'includes/KSF_Books-cart-functions.php';
//		include_once KSF_Books_ABSPATH . 'includes/KSF_Books-notice-functions.php';
//		include_once KSF_Books_ABSPATH . 'includes/KSF_Books-template-hooks.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-template-loader.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-frontend-scripts.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-form-handler.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-cart.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-tax.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-shipping-zones.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-customer.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-embed.php';
//		include_once KSF_Books_ABSPATH . 'includes/class-KSF_Books-session-handler.php';
	}

	/**
	 * Function used to Init KSF_Books Template Functions - This makes them pluggable by plugins and themes.
	 */
	public function include_template_functions() {
		include_once KSF_Books_ABSPATH . 'includes/KSF_Books-template-functions.php';
	}

	/**
	 * Init KSF_Books when WordPress Initialises.
	 */
	public function init() {
		// Before init action.
		do_action( 'before_KSF_Books_init' );

		// Set up localisation.
		$this->load_plugin_textdomain();

		// Load class instances.
//		$this->product_factory                     = new KSF_Books_Product_Factory();
//		$this->order_factory                       = new KSF_Books_Order_Factory();
//		$this->countries                           = new KSF_Books_Countries();
//		$this->integrations                        = new KSF_Books_Integrations();
//		$this->structured_data                     = new KSF_Books_Structured_Data();
//		$this->deprecated_hook_handlers['actions'] = new KSF_Books_Deprecated_Action_Hooks();
//		$this->deprecated_hook_handlers['filters'] = new KSF_Books_Deprecated_Filter_Hooks();

		// Classes/actions loaded for the frontend and for ajax requests.
		if ( $this->is_request( 'frontend' ) ) {
			KSF_Books_load_cart();
		}

		$this->load_webhooks();

		// Init action.
		do_action( 'KSF_Books_init' );
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/KSF_Books/KSF_Books-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/KSF_Books-LOCALE.mo
	 */
	public function load_plugin_textdomain() {
		$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$locale = apply_filters( 'plugin_locale', $locale, get_class( $this ) );

		unload_textdomain( get_class( $this ) );
		load_textdomain( get_class( $this ), WP_LANG_DIR . '/KSF_Books/KSF_Books-' . $locale . '.mo' );
		load_plugin_textdomain( get_class( $this ), false, plugin_basename( dirname( KSF_Books_PLUGIN_FILE ) ) . '/i18n/languages' );
	}

	/**
	 * Ensure theme and server variable compatibility and setup image sizes.
	 */
	public function setup_environment() {
		/**
		 * KSF_Books_TEMPLATE_PATH constant.
		 *
		 * @deprecated 2.2 Use WC()->template_path() instead.
		 */
		$this->define( 'KSF_Books_TEMPLATE_PATH', $this->template_path() );

		$this->add_thumbnail_support();
	}

	/**
	 * Ensure post thumbnail support is turned on.
	 */
	private function add_thumbnail_support() {
		if ( ! current_theme_supports( 'post-thumbnails' ) ) {
			add_theme_support( 'post-thumbnails' );
		}
		add_post_type_support( 'product', 'thumbnail' );
	}

	/**
	 * WooCommerce handles its own image sizes in the themes.  See WOOCOMMERCE!
	 */
	public function add_image_sizes() {
	}

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', KSF_Books_PLUGIN_FILE ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( KSF_Books_PLUGIN_FILE ) );
	}

	/**
	 * Get the template path.
	 *
	 * @return string
	 */
	public function template_path() {
		return apply_filters( 'KSF_Books_template_path', 'KSF_Books/' );
	}

	/**
	 * Get Ajax URL.
	 *
	 * @return string
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}

	/**
	 * Return the WC API URL for a given request.
	 *
	 * @param string    $request Requested endpoint.
	 * @param bool|null $ssl     If should use SSL, null if should auto detect. Default: null.
	 * @return string
	 */
	public function api_request_url( $request, $ssl = null ) {
		if ( is_null( $ssl ) ) {
			$scheme = wp_parse_url( home_url(), PHP_URL_SCHEME );
		} elseif ( $ssl ) {
			$scheme = 'https';
		} else {
			$scheme = 'http';
		}

		if ( strstr( get_option( 'permalink_structure' ), '/index.php/' ) ) {
			$api_request_url = trailingslashit( home_url( '/index.php/KSF_Books-api/' . $request, $scheme ) );
		} elseif ( get_option( 'permalink_structure' ) ) {
			$api_request_url = trailingslashit( home_url( '/KSF_Books-api/' . $request, $scheme ) );
		} else {
			$api_request_url = add_query_arg( 'KSF_Books-api', $request, trailingslashit( home_url( '', $scheme ) ) );
		}

		return esc_url_raw( apply_filters( 'KSF_Books_api_request_url', $api_request_url, $request, $ssl ) );
	}

	/**
	 * Load & enqueue active webhooks.
	 *
	 * @since 2.2
	 */
	private function load_webhooks() {

		if ( ! is_blog_installed() ) {
			return;
		}

		/**
		 * Hook: KSF_Books_load_webhooks_limit.
		 *
		 * @since 3.6.0
		 * @param int $limit Used to limit how many webhooks are loaded. Default: no limit.
		 */
		$limit = apply_filters( 'KSF_Books_load_webhooks_limit', null );

		KSF_Books_load_webhooks( 'active', $limit );
	}

	/**
	 * Initialize the customer and cart objects and setup customer saving on shutdown.
	 *
	 * @since 3.6.4
	 * @return void
	 */
	public function initialize_cart() {
		// Cart needs customer info.
		if ( is_null( $this->customer ) || ! $this->customer instanceof KSF_Books_Customer ) {
			$this->customer = new KSF_Books_Customer( get_current_user_id(), true );
			// Customer should be saved during shutdown.
			add_action( 'shutdown', array( $this->customer, 'save' ), 10 );
		}
		if ( is_null( $this->cart ) || ! $this->cart instanceof KSF_Books_Cart ) {
			$this->cart = new KSF_Books_Cart();
		}
	}

	/**
	 * Initialize the session class.
	 *
	 * @since 3.6.4
	 * @return void
	 */
	public function initialize_session() {
		// Session class, handles session data for users - can be overwritten if custom handler is needed.
		$session_class = apply_filters( 'KSF_Books_session_handler', 'KSF_Books_Session_Handler' );
		if ( is_null( $this->session ) || ! $this->session instanceof $session_class ) {
			$this->session = new $session_class();
			$this->session->init();
		}
	}

	/**
	 * Set tablenames inside WPDB object.
	 */
	public function wpdb_table_fix() {
		$this->define_tables();
	}

	/**
	 * Ran when any plugin is activated.
	 *
	 * @since 3.6.0
	 * @param string $filename The filename of the activated plugin.
	 */
	public function activated_plugin( $filename ) {
		include_once dirname( __FILE__ ) . '/admin/helper/class-KSF_Books-helper.php';

		KSF_Books_Helper::activated_plugin( $filename );
	}

	/**
	 * Ran when any plugin is deactivated.
	 *
	 * @since 3.6.0
	 * @param string $filename The filename of the deactivated plugin.
	 */
	public function deactivated_plugin( $filename ) {
		include_once dirname( __FILE__ ) . '/admin/helper/class-KSF_Books-helper.php';

		KSF_Books_Helper::deactivated_plugin( $filename );
	}
	/**
	 * Load Eventloop
	 * 
	 * @return eventloop
	 */
	public function eventloop()
	{
		global $eventloop;
		if( ! isset( $eventloop ) OR null === $eventloop )
		{
			global $moduledir;
			$moduledir = dirname( __FILE__ ) . '/modules';
			//var_dump( __FILE__ ); var_dump( __LINE__ );  var_dump( $moduledir );  var_dump( $GLOBALS['moduledir'] );
			//$eventloop = new eventloop( $moduledir, $this );
               		require( 'vendor/ksfraser/ksf_common/eventloop.php' );
               		$this->eventloop = $eventloop = new eventloop( dirname( __FILE__ ) . '/modules', $this );
		}
		else
		{
			$this->eventloop = $eventloop;
		}
		echo "This MODULES homedir: " . dirname( __FILE__ );
		return $this->eventloop;
	}
}

