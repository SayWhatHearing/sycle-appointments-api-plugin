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
		add_shortcode('syclebooking', array( $this, 'shortcode_syclebooking' ) );
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
		$request['proximity'] = $proximity;

		$result = $this->return_search_clinics_results($request);

		$clinics_list = json_decode($result);

		$output = array();
		if (is_array($clinics_list->clinic_details)) {
			foreach ($clinics_list->clinic_details as $clinic) {
				$output['clinic_details'][] = $this->return_clinic_markup($clinic,$request['token'] );
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
			$output['clinic_details'][] = $this->return_clinic_markup($clinic,$token);
		}
	}
	echo json_encode($output);
	die();
}

// Returns individual location in marked up format
// locdetails - location object with details
// parsedtoken - If set, reuses the token for future api requests
function return_clinic_markup($locdetails,$parsedtoken) {
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
	$output .= '<input type="hidden" name="sycle_clinic_id" value="'.$locdetails->clinic->clinic_id.'">';

	$output .= '<select class="sycle_apttype" name="sycle_apttype">';
	foreach ($locdetails->appointment_types as $appointment_type) {
		if (!$firsttypename) {
			$firsttypename = $appointment_type->name;
		}
		if (!$firsttypelength) {
			$firsttypelength = $appointment_type->length;
		}

		$output .= '<option value="'.esc_attr($appointment_type->appt_type_id).'" data-name="'.esc_attr($appointment_type->name).'"data-type="'.esc_attr($appointment_type->appt_type_id).'" data-length="'.esc_attr($appointment_type->length).'">'.esc_attr($appointment_type->name).'</option>';
	}
	$output .= '</select>';

	// Hidden fields and parsed data
	if ($parsedtoken) {
		$output .= '<input type="hidden" name="sycle_token" value="'.$parsedtoken.'">';
	}
	if ($firsttypename) {
		$output .= '<input type="hidden" name="sycle_aptname" value="'.$firsttypename.'" class="sycle_aptname" >';
	}
	if ($firsttypelength) {
		$output .= '<input type="hidden" name="sycle_aptlength" value="'.$firsttypelength.'" class="sycle_aptlength">';
	}

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

// FUNCTION RETURNS the booking form
function shortcode_syclebooking($atts = []) {
	$output = '<div class="sycleapi">';
	error_log('post '.print_r($_POST,true));

	/*
[27-Jun-2017 20:56:36 UTC] post Array

    [sycle_clinic_id] => 2803-9506
    [sycle_apttype] => 2803-1
    [sycle_token] => c668dd7fdf0d96ed0db92aa09a7b83
    [sycle_aptname] => 2803-1
    [sycle_aptlength] => 90
    [submit] => Book Time

	*/




    if (isset($_POST['sycle_clinic_id'])) {
    	$sycle_clinic_id = sanitize_text_field($_POST['sycle_clinic_id']);
    }

    if (isset($_POST['sycle_apttype'])) {
    	$sycle_apttype = sanitize_text_field($_POST['sycle_apttype']);
    }

    if (isset($_POST['sycle_aptname'])) {
    	$sycle_aptname = sanitize_text_field($_POST['sycle_aptname']);
    }

    if (isset($_POST['sycle_aptlength'])) {
    	$sycle_aptlength = sanitize_text_field($_POST['sycle_aptlength']);
    }

    if (isset($_POST['sycle_token'])) {
    	$sycle_token = sanitize_text_field($_POST['sycle_token']);
    }






    $atts = array_change_key_case((array)$atts, CASE_LOWER);
// TODO - options "showmeta", ...
	// Lets see if the shortcode has the id paramater
    if (isset($atts['id'])) {
    	$sycle_clinic_id = sanitize_text_field($atts['id']);
    }

	// If not, lets see if it is parsed via POST
    if ( (!isset($sycle_clinic_id)) && (isset($_POST['sycle_clinic_id'])) ) {
    	$sycle_clinic_id = sanitize_text_field($_POST['sycle_clinic_id']);
    }

	// Final chance, looking up via post meta.
    if (!isset($sycle_clinic_id)) {
    	global $post;
    	$meta = get_post_meta($post->ID, 'sycle_clinic_id', true);
    	if ($meta) $sycle_clinic_id = $meta;
    }

	// Output errors for admins.
    if (!isset($sycle_clinic_id)) {

    	$current_user = wp_get_current_user();

		// Error for admins - perhaps add something for regular visitors?
    	if (user_can( $current_user, 'administrator' )) {
    		$output .= '<div class="sycleerror">'.__('[syclebooking] Shortcode needs id="" paramater.','sycleapi').'</div><!-- .sycleerror -->';
    	}
    	else {
			// Here we can add to $output for errors for non-admins
    	}
    	return $output;
    }

    if (isset($sycle_clinic_id)) {
	//	$output .= '<div id="datepicker">test</div>';

    	$output .= '<h3>'.__('Book an appointment','sycleapi').'</h3>';
    	$output .= 'At ...todo</br>';

    	$output .= '<form action="" class="sycle-booking sycle-clinic-'.esc_attr($sycle_clinic_id).'" method="POST" enctype="multipart/form-data" >';

    	if (isset($sycle_token)) {
    		$output .= '<input type="hidden" name="sycle_booking_token" value="'.esc_attr($sycle_token).'">';
    	}

    	if (isset($sycle_clinic_id)) {
    		$output .= '<input type="hidden" name="sycle_clinic_id" value="'.esc_attr($sycle_clinic_id).'">';
    	}

    	if (isset($sycle_apttype)) {
    		$output .= '<input type="hidden" name="sycle_apttype" value="'.esc_attr($sycle_apttype).'">';
    	}

    	if (isset($sycle_aptname)) {
    		$output .= '<input type="hidden" name="sycle_aptname" value="'.esc_attr($sycle_aptname).'">';
    	}

    	if (isset($sycle_aptlength)) {
    		$output .= '<input type="hidden" name="sycle_aptlength" value="'.esc_attr($sycle_aptlength).'">';
    	}


/*
    [sycle_clinic_id] => 2803-9506
    [sycle_apttype] => 2803-1

    [sycle_aptname] => 2803-1
    [sycle_aptlength] => 90
*/

// todo - look up what apt. types available for this clinic via API -
    $output .= '<fieldset>
    <label for="sycle_booking_date">Choose date</label>
    <input type="text" name="sycle_booking_date" class="sycle_booking_date" required/>
    </fieldset>
    <fieldset>
			### this will be loaded with available times from the clinic ###
    <label for="sycle_booking_time">Time</label>
    <input type="text" name="sycle_booking_time" class="sycle_booking_time" required/>

    </fieldset>
    <fieldset>
    <label for="sycle_customer_title">Your Title</label>
    <select class="required" name="sycle_customer_title" class="sycle_customer_title">
    <option value="" selected="selected">- Select -</option>
    <option value="Mr">Mr</option>
    <option value="Mrs">Mrs</option>
    <option value="Miss">Miss</option>
    <option value="Ms">Ms</option>
    <option value="Dr">Dr</option>
    </select>
    </fieldset>
    <fieldset>
    <label for="sycle_customer_name">Your Name</label>
    <input type="text" name="sycle_customer_name" class="sycle_customer_name" required/>
    </fieldset>
    <fieldset>
    <label for="sycle_customer_email">Your Email</label>
    <input type="text" name="sycle_customer_email" class="sycle_customer_email email" required/>
    </fieldset>

    <fieldset>
    <button type="submit" name="sycle-submit" class="sycle-booking-submit">Send Query</button>
    </fieldset>'.wp_nonce_field( 'submit_contact_form' , 'nonce_field_for_submit_contact_form');
    $output .= '<input type="text" class="datepicker" name="sycle_datepicker" value=""/>';
    $output .= '</form>';
  }

  $output .= '</div><!-- .sycleapi -->';
  return $output;
}


	/**
	 * Load the localisation file.
	 * @access  public
	 * @since   1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'sycleapi', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
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

	// This is the minified js version, dev is in /assets/js/
	wp_register_script('sycle', $this->plugin_url . 'js/sycle-min.js', array('jquery','jquery-validate-script','jquery-ui-datepicker'),false,true);
	wp_enqueue_style('sycle', $this->plugin_url . 'css/sycle.css', array(), false);
	wp_localize_script(
		'sycle',
		'sycle_ajax_object',
		$localizeparams
	);
	wp_enqueue_script('sycle');

	// TODO - Check the shortcode is used before loading bloat .js
	wp_enqueue_script('jquery-ui-datepicker');
	// TODO - Check the shortcode is used before loading bloat .js
	wp_register_script( 'jquery-validate-script',  $this->plugin_url . 'assets/js/jquery-validation/dist/jquery.validate.min.js' ,array( 'jquery'));

	wp_enqueue_style( 'jquery-ui' );

	// TODO - we can do this better - include style in plugin css perhaps?
	// Also: https://github.com/stuttter/wp-datepicker-styling/blob/master/datepicker.css
	wp_enqueue_style('e2b-admin-ui-css','//ajax.googleapis.com/ajax/libs/jqueryui/1.9.0/themes/base/jquery-ui.css',false,"1.9.0",false);


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