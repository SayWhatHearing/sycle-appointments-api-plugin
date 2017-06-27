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
 * Text Domain: sycleapi
 * Domain Path: /languages/
 *
 * @package Sycle_Appointments
 * @category Core
 * @author Matty
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

register_deactivation_hook( __FILE__, array( 'Sycle_Appointments', 'de_activation_functions' ) );
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

	private static $_instance = null;

	public $token;

	public $version;

	public $plugin_url;

	public $plugin_path;

	public $admin;

	public $settings;

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
		add_shortcode('sycleclinicslist', array( $this, 'shortcode_sycleclinicslist' ) );


		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, '_scripts_styles_loader' ) );

		// Ajax for loading clinics
		add_action('wp_ajax_sycle_get_clinics_list', array(&$this, 'ajax_do_sycle_get_clinics_list'));
		add_action('wp_ajax_nopriv_sycle_get_clinics_list', array(&$this, 'ajax_do_sycle_get_clinics_list'));


		// Ajax for returning nearest clinics
		add_action('wp_ajax_sycle_get_search_results', array(&$this, 'ajax_do_sycle_get_search_results'));
		add_action('wp_ajax_nopriv_sycle_get_search_results', array(&$this, 'ajax_do_sycle_get_search_results'));


		// Ajax for logging lookups and searches
		add_action('wp_ajax_sycle_log_lookup', array(&$this, 'ajax_do_sycle_log_lookup'));
		add_action('wp_ajax_nopriv_sycle_log_lookup', array(&$this, 'ajax_do_sycle_log_lookup'));
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




	function ajax_do_sycle_log_lookup() {
		error_log('ajax_do_sycle_log_lookup()');
		echo "1";
		die();
	}


	function return_search_clinics_results($searchdata) {
		if (!$searchdata) return;
		do_action('sycle_send_request', 'return_search_clinics_results', $searchdata);
//add_action('sycle_send_request', 'do_sycle_send_request', 10, 2);

		$connectstring = json_encode($searchdata);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->get_api_url('clinics'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $connectstring);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		$headers = array();
		$headers[] = "Content-Type: application/x-www-form-urlencoded";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			$this->log('Error '.curl_error($ch));
		}
		curl_close ($ch);
		return $result;
	}




	// Returns ajax with a search
	function ajax_do_sycle_get_search_results() {
	// TODO validate token
		$request = array();

		//$addressfield = sanitize_text_field( $_POST['addressfield'] );
// todo merge addressfield

		//error_log('ajax_do_sycle_get_search_results() '.print_r($_POST,true));

		// Setting defaults
		$proximity = array(); // todo look for and parse any locale data
		$proximity['zip'] = '34239'; // todo debug required
		$proximity['miles'] = '100'; // todo debug 	1-1000 required

		if (is_array($_POST['addressfield'])) {
			foreach ($_POST['addressfield'] as $adrfield) {
				if ($adrfield['types'][0]=='street_number') {
					$proximity['street1'] = $adrfield['short_name'];
				}
				if ($adrfield['types'][0]=='route') {
					$proximity['street1'] .= ' '.$adrfield['short_name'];
				}
				if ($adrfield['types'][0]=='postal_code') {
					$proximity['zip'] = $adrfield['short_name'];
				}
				if ($adrfield['types'][0]=='administrative_area_level_2') {
					$proximity['city'] = $adrfield['short_name'];
				}
				if ($adrfield['types'][0]=='administrative_area_level_1') {
					$proximity['state'] = $adrfield['short_name'];
				}
			}
		}
		error_log(print_r($proximity,true));
		error_log(print_r($_POST,true));
		error_log(print_r(json_decode($_POST['addressfield']),true));



		$request['token'] = $this->get_token();
//$request['start_date'] = date('Y-m-d');
		$request['proximity'] = $proximity;

		$result = $this->return_search_clinics_results($request);

		$clinics_list = json_decode($result);
//error_log('clinics_list : '.print_r($clinics_list,true));

		$output = array();
		if (is_array($clinics_list->clinic_details)) {
			foreach ($clinics_list->clinic_details as $clinic) {
				$output['clinic_details'][] = $this->return_clinic_markup($clinic);
			}
		}
		echo json_encode($output);

		die();
	}




// Returns endpoint url for API - appends endpoint if added
	function get_api_url($endpoint = '') {
		$thesettings = Sycle_Appointments()->settings->get_settings();
		$sycle_subdomain = $thesettings['sycle_subdomain'];
	if (!$sycle_subdomain) $sycle_subdomain = 'amg'; // default
	$finalurl = trailingslashit('https://'.$sycle_subdomain.'.sycle.net/api/vendor/'.$endpoint);
	return $finalurl;
}

	// Gets a token from the Sycle API
function get_token() {
	$thesettings = Sycle_Appointments()->settings->get_settings();
	$connectstring= '{"username":"'.esc_attr($thesettings['sycle_username']).'", "password":"'.esc_attr($thesettings['sycle_pw']).'"}';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $this->get_api_url('token'));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $connectstring);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
	$headers = array();
	$headers[] = "Content-Type: application/x-www-form-urlencoded";
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	$result = curl_exec($ch);
	$response = json_decode($result);
	if (curl_errno($ch)) {
		// Error happened, add to log.
		$this->log('Error '.curl_error($ch));
	}
	curl_close ($ch);
	return sanitize_text_field($response->token);
}



	// Returns ajax requests with clinics data
function ajax_do_sycle_get_clinics_list() {
	$token = $this->get_token();
	$clinics_list = $this->return_clinics_list($token);
	$clinics_list = json_decode($clinics_list);
	$output = array();
	if (is_array($clinics_list->clinic_details)) {
		foreach ($clinics_list->clinic_details as $clinic) {
			$output['clinic_details'][] = $this->return_clinic_markup($clinic);
		}
	}
	echo json_encode($output);
	die();
}

// Returns individual location in marked up format
function return_clinic_markup($locdetails) {
	global $wpdb;

	$output = '';
	$output .= '<div itemscope itemtype="http://schema.org/LocalBusiness">
	<div itemprop="name"><strong>'.$locdetails->clinic->clinic_name.'</strong></div>';

	if ( (isset($locdetails->clinic->phone1)) && ($locdetails->clinic->phone1<>'')) {
		$output .= '<a href="tel:'.$locdetails->clinic->phone1.'" target="_blank" class="telephone" itemprop="telephone">'.$locdetails->clinic->phone1.'</a>';
	}

	$output .= '<div itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">
	<span itemprop="streetAddress">'.$locdetails->clinic->address->street1.'</span>
	<span itemprop="addressLocality">'.$locdetails->clinic->address->city.'</span>,
	<span itemprop="addressRegion">'.$locdetails->clinic->address->state.'</span>
	<span itemprop="postalCode">'.$locdetails->clinic->address->zip.'</span>
	<span itemprop="addressCountry">'.$locdetails->clinic->address->country.'</span><br>
	</div>
	';

	$actionurl = '';

	$locID = $wpdb->get_var("SELECT post_id FROM `".$wpdb->postmeta."` WHERE meta_key='sycle_clinic_id' AND meta_value='".$locdetails->clinic->clinic_id."'");

	if ($locID) {
		$actionurl = get_post_permalink( $locID );
	}

	$output .= '<form method="post" action="'.$actionurl.'">';
	$output .= '<input type="hidden" name="clinic_id" value="'.$locdetails->clinic->clinic_id.'">';
	$output .= '<input type="hidden" name="sycle_token" value="'.$locdetails->clinic->clinic_id.'">';


	$output .= '<select class="apttype">';
	foreach ($locdetails->appointment_types as $appointment_type) {
		$output .= '<option value="'.esc_attr($appointment_type->name).'" data-name="'.esc_attr($appointment_type->name).'"data-type="'.esc_attr($appointment_type->appt_type_id).'" data-length="'.esc_attr($appointment_type->length).'">'.esc_attr($appointment_type->name).'</option>';
	}

	$output .= '</select>';
	$output .= '<input type="submit" name="submit" id="submit" class="button" value="'.__('Book Time','sycleapi').'">';

	$output .='</form>';

	$output .= '</div><!-- LocalBusiness -->';

	return $output;
}


function return_clinics_list($token) {
	if (!$token) return;
	$connectstring= '{"token":"'.esc_attr($token).'"}';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $this->get_api_url('clinics'));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $connectstring);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
	$headers = array();
	$headers[] = "Content-Type: application/x-www-form-urlencoded";
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	$result = curl_exec($ch);
	if (curl_errno($ch)) {
		$this->log('Error '.curl_error($ch));
	}
	curl_close ($ch);
	return $result;
}




// Shortcode [sycleclinicslist] output
function shortcode_sycleclinicslist() {
	// Content is generated via AJAX call. An ajax call is performed that returns the list of clinics and injects in to the UL .clinicslist
	$token = $this->get_token();
	$output = '<div class="sycleapi sycleclinicslist"><input class="sycletoken" type="hidden" value="'.esc_attr($token).'"><ul class="clinicslist"></ul></div><!-- .sycleclinicslist -->';
	return $output;
}


function shortcode_sycle() {
	$formtemplate = '<div class="sycleapi"><div class="syclelookupresults"><ul class="clinicslist"></ul></div><!-- .syclelookupresults -->
	<form class="syclefindcloseclinic"><div id="locationField">
	<input id="sycletoken" value="'.Sycle_Appointments()->get_token().'" type="hidden">
	<input id="sycleautocomplete" placeholder="'.__('Enter your address or ZIP code','sycleapi').'" type="text" class="sycleautocomplete"></input>
	</div></form></div><!-- .sycleapi -->';
	return $formtemplate;
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
		$thesettings = Sycle_Appointments()->settings->get_settings();
		if (!$thesettings['logging']) return; // Don't log if turned off
		global $wpdb;
		$table_name_log = $wpdb->prefix . 'sycle_log';
		$time           = date('Y-m-d H:i:s ', time());
		$text           = esc_sql($text);
		$wpdb->insert(
			$table_name_log,
			array(
				'logtime'  => current_time( 'mysql' ),
				'prio'  => $prio,
				'log' => esc_sql($text)
			),
			array(
				'%s',
				'%d',
				'%s'
			)
		);

		$total          = (int) $wpdb->get_var("SELECT COUNT(*) FROM `$table_name_log`;");
		if ($total > 1000) { // Set a little higher, no need to run it all the time
			$targettime = $wpdb->get_var("SELECT `logtime` from `$table_name_log` order by `logtime` DESC limit 500,1;");
			$query      = "DELETE from `$table_name_log`  where `logtime` < '$targettime';";
			$success    = $wpdb->query($query);
		}
	}




/**
 * Load CSS styles & JavaScript scripts
 */
function _scripts_styles_loader() {

	$thesettings = Sycle_Appointments()->settings->get_settings();

	// todo - genbrug token?
	$localizeparams = array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'sycle_nonce' => wp_create_nonce( 'sycle_nonce_val' )
	);
		// todo - load via sycle.js instead
	if ($thesettings['places_api']) {
		$localizeparams[] = $thesettings['places_api'];
		wp_enqueue_script('gplaces', 'https://maps.googleapis.com/maps/api/js?key='.esc_attr($thesettings['places_api']).'&libraries=places', array('jquery','sycle'),false,true);
	}

	wp_register_script('sycle', $this->plugin_url . 'js/sycle-min.js', array('jquery'),false,true);
	wp_enqueue_style('sycle', $this->plugin_url . 'css/sycle.css', array(), false);
	wp_localize_script(
		'sycle',
		'sycle_ajax_object',
		$localizeparams
	);
	wp_enqueue_script('sycle');
}


public static function de_activation_functions () {
	if ( ! current_user_can( 'activate_plugins' ) )
		return;
	$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
	check_admin_referer( "deactivate-plugin_{$plugin}" );
	$thesettings = Sycle_Appointments()->settings->get_settings();

	// If option in settings set to remove data.
	if ($thesettings['cleanondeactivate']) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'sb2_log';
		$sql = "DROP TABLE IF EXISTS $table_name";
		$wpdb->query($sql);
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
Sycle_Appointments()->log(__('Plugin Activated','sycleapi'));
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