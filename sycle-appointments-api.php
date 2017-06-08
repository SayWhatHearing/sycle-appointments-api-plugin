<?php
/**
 * Plugin Name: Sycle Appointments API
 * Plugin URI: https://saywhathearing.com
 * Description: Integration to Sycle Appoinments API by SayWhatHearing
 * Version: 1.0.0
 * Author: lkoudal, mrodriguez
 * Author URI: https://saywhathearing.com
 * Requires at least: 4.23.0
 * Tested up to: 4.7.5
 *
 * Text Domain: sycle-appointments
 * Domain Path: /languages/
 *
 * @package Sycle_Appointments
 * @category Core
 * @author Matty
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

register_activation_hook( __FILE__, array( 'Sycle_Appointments', 'activation_functions' ) );
/**
 * Returns the main instance of Sycle_Appointments to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Sycle_Appointments
 */
function Sycle_Appointments() {
	return Sycle_Appointments::instance();
} // End Sycle_Appointments()
add_action( 'plugins_loaded', 'Sycle_Appointments' );
/**
 * Main Sycle_Appointments Class
 *
 * @class Sycle_Appointments
 * @version	1.0.0
 * @since 1.0.0
 * @package	Sycle_Appointments
 * @author Matty
 */
final class Sycle_Appointments {
	/**
	 * Sycle_Appointments The single instance of Sycle_Appointments.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;
	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $token;
	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $version;
	/**
	 * The plugin directory URL.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $plugin_url;
	/**
	 * The plugin directory path.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $plugin_path;
	// Admin - Start
	/**
	 * The admin object.
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $admin;
	/**
	 * The settings object.
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings;
	// Admin - End
	// Post Types - Start
	/**
	 * The post types we're registering.
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $post_types = array();
	// Post Types - End
	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 */
	public function __construct () {
		$this->token 			= 'sycle-appointments';
		$this->plugin_url 		= plugin_dir_url( __FILE__ );
		$this->plugin_path 		= plugin_dir_path( __FILE__ );
		$this->version 			= '1.0.0';

		require_once( 'classes/class-sycle-appointments-settings.php' );
		$this->settings = Sycle_Appointments_Settings::instance();

		if ( is_admin() ) {
			require_once( 'classes/class-sycle-appointments-admin.php' );
			$this->admin = Sycle_Appointments_Admin::instance();
		}
		add_shortcode('sycle', array( $this, 'shortcode_sycle' ) );
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
	} // End __construct()

	/**
	 * Main Sycle_Appointments Instance
	 *
	 * Ensures only one instance of Sycle_Appointments is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Sycle_Appointments()
	 * @return Main Sycle_Appointments instance
	 */
	public static function instance () {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
			return self::$_instance;
	} // End instance()



	function shortcode_sycle( ) {

// todo -

		/*
	shortcode

	- if no param - get list of vendors and return results.


	transient short term?



		*/
/*
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "https://amg.sycle.net/api/vendor/token/");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"username\":\"amg_apt_api_test\", \"password\":\"Sycl3!\"}");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");


$headers = array();
$headers[] = "Content-Type: application/x-www-form-urlencoded";
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close ($ch);
*/




$arg_data = array(
		'username' => 'amg_apt_api_test',
		'password' => 'Sycl3!'
	);
$data = json_encode( $arg_data );


$headers = array();
//$headers[] = "Content-Type: application/x-www-form-urlencoded";
$headers = array('Content-Type' => 'application/json');
$get_token_args = array(
	'method' => 'GET',
	'headers' => $headers,
	'body' => array(
		'username' => 'amg_apt_api_test',
		'password' => 'Sycl3!'
	)
);
error_log(print_r($get_token_args,true));

$response = wp_remote_post( 'https://amg.sycle.net/api/vendor/token/', $get_token_args );
$body = wp_remote_retrieve_body($response);
error_log(print_r($body,true));

$this->log('Response '.print_r($body,true));

return print_r($body,true);
//echo $token;

	//	return get_search_form(false); // true= echoes
}



	/**
	 * Load the localisation file.
	 * @access  public
	 * @since   1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'sycle-appointments', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	} // End load_plugin_textdomain()


	/**
	 * Logs actions to database
	 * @access  public
	 * @since   1.0.0
	 */
	public function log($text, $prio = 0) {

		global $wpdb;
		$table_name_log = $wpdb->prefix . 'sycle_log';
		$time           = date('Y-m-d H:i:s ', time());
		$text           = esc_sql($text);

	// TODO - PREPARE
		$daquery        = "INSERT INTO `$table_name_log` (logtime,prio,log) VALUES ('$time','$prio','" . esc_sql($text) . "');";
		$result         = $wpdb->query($daquery);
		$total          = (int) $wpdb->get_var("SELECT COUNT(*) FROM `$table_name_log`;");
	if ($total > 1000) { // Set a little higher, no need to run it all the time
		$targettime = $wpdb->get_var("SELECT `logtime` from `$table_name_log` order by `logtime` DESC limit 500,1;");
		$query      = "DELETE from `$table_name_log`  where `logtime` < '$targettime';";
		$success    = $wpdb->query($query);
	}
}


public static function activation_functions () {
	if ( ! current_user_can( 'activate_plugins' ) )
		return;
	$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
	check_admin_referer( "activate-plugin_{$plugin}" );
        # Uncomment the following line to see the function in action
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();
	$table_name_log = $wpdb->prefix . 'sycle_log';

	$sql = "CREATE TABLE $table_name_log (
	logtime timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	prio tinyint(1) NOT NULL,
	log varchar(2048) NOT NULL,
	KEY logtime (logtime)
) $charset_collate;";

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
dbDelta( $sql );
} // End install()




	/**
	 * Cloning is forbidden.
	 * @access public
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	} // End __clone()
	/**
	 * Unserializing instances of this class is forbidden.
	 * @access public
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	} // End __wakeup()
	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 */



	/**
	 * Log the plugin version number.
	 * @access  private
	 * @since   1.0.0
	 */
	function _log_version_number () {
		update_option( $this->token . '-version', $this->version );
	} // End _log_version_number()
} // End Class