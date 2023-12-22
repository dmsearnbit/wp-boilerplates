<?php
/*
 * Plugin Name:       Rise Hotel Booking
 * Plugin URI:        https://www.risehotelbooking.com/
 * Description:       Easy to use Hotel Booking System by hoteliers, for hoteliers!
 * Version:           1.1.1
 * Requires at least: 5.2
 * Requires PHP:      5.6
 * Author:            Emre Danisan
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       rise-hotel-booking
 * Domain Path:       /languages
 *
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/* exist if directly accessed */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// define variable for path to this plugin file.
define( 'RISE_LOCATION', dirname( __FILE__ ) );
define( 'RISE_LOCATION_URL', plugins_url( '', __FILE__ ) );

const RISE_PLUGIN_WEBSITE = 'https://www.risehotelbooking.com';

// define cdn urls
const BOOTSTRAP_CSS       = RISE_LOCATION_URL . '/Assets/Dependencies/bootstrap/bootstrap.min.css';
const BOOTSTRAP_JS        = RISE_LOCATION_URL . '/Assets/Dependencies/bootstrap/bootstrap.bundle.min.js';
const MOMENT_JS           = RISE_LOCATION_URL . '/Assets/Dependencies/moment/moment.min.js';
const DATERANGEPICKER_JS  = RISE_LOCATION_URL . '/Assets/Dependencies/daterangepicker/daterangepicker.min.js';
const DATERANGEPICKER_CSS = RISE_LOCATION_URL . '/Assets/Dependencies/daterangepicker/daterangepicker.css';
const FULLCALENDAR_JS     = RISE_LOCATION_URL . '/Assets/Dependencies/fullcalendar/main.js';
const FULLCALENDAR_CSS    = RISE_LOCATION_URL . '/Assets/Dependencies/fullcalendar/main.css';
const SELECT2_JS          = RISE_LOCATION_URL . '/Assets/Dependencies/select2/select2.min.js';
const SELECT2_CSS         = RISE_LOCATION_URL . '/Assets/Dependencies/select2/select2.min.css';
const STRIPE_CHECKOUT_JS  = RISE_LOCATION_URL . '/Assets/Dependencies/stripe/checkout.js';
const DATATABLES_JS       = RISE_LOCATION_URL . '/Assets/Dependencies/datatables/jquery.dataTables.min.js';
const DATATABLES_CSS      = RISE_LOCATION_URL . '/Assets/Dependencies/datatables/jquery.dataTables.min.css';

// include controllers
include_once( "Controller/RoomController.php" );
include_once( "Controller/BookingController.php" );
include_once( "Controller/Shortcode/RoomsShortcode.php" );
include_once( "Controller/Shortcode/RoomSearchShortcode.php" );
include_once( "Controller/Shortcode/RoomSearchResultsShortcode.php" );
include_once( "Controller/Shortcode/RoomCheckoutShortcode.php" );
// include_once( "Controller/RoomTypeController.php" );
include_once( "Controller/PricingPlansController.php" );
include_once( "Controller/SettingsController.php" );
include_once( "Controller/CouponController.php" );
include_once( "Controller/CloseRoomsController.php" );
include_once( "Controller/ActivityLogController.php" );
include_once( "Controller/CustomRatesController.php" );


class RISE {
	public function __construct() {
		if ( ! session_id() ) {
			session_start();
		}
		$RoomController             = new RoomController();
		$RoomsShortcode             = new RoomsShortcode();
		$RoomSearchShortcode        = new RoomSearchShortcode();
		$RoomSearchResultsShortcode = new RoomSearchResultsShortcode();
		$RoomCheckoutShortcode      = new RoomCheckoutShortcode();
		// $RoomTypeController         = new RoomTypeController();
		$PricingPlansController = new PricingPlansController();
		$CloseRoomsController   = new CloseRoomsController();
		$SettingsController     = new SettingsController();
		$BookingController      = new BookingController();
		$CouponController       = new CouponController();
		$ActivityLogController  = new ActivityLogController();
		$CustomRatesController  = new CustomRatesController();

		// add scripts and styles for frontend
		add_action( 'wp_enqueue_scripts', array( $this, 'frontendScriptsStyles' ) );

		// add scripts and styles for admin
		add_action( 'admin_enqueue_scripts', array( $this, 'adminScriptsStyles' ) );

		// create table on plugin activation
		register_activation_hook( __FILE__, array( $this, 'createTables' ) );

		// set default values on plugin activation
		register_activation_hook( __FILE__, array( $SettingsController, 'setDefaultValues' ) );

		// add default pricing rates on plugin activation
		register_activation_hook( __FILE__, array( $CustomRatesController, 'setDefaultPresets' ) );

		// check if using HTTPS on activation
		register_activation_hook( __FILE__, array( $this, 'checkSSL' ) );

		// display warnings
		add_action( 'admin_notices', array( $this, 'showWarnings' ) );

		// create tables on plugin upgrade
		add_action( 'upgrader_process_complete', array( $this, 'createTables' ) );

		// register api routes for pricing plans api
		add_action( 'rest_api_init', array( $PricingPlansController, 'registerRoutes' ) );

		// register api routes for booking api
		add_action( 'rest_api_init', array( $BookingController, 'registerRoutes' ) );

		// register api routes for room checkout api
		add_action( 'rest_api_init', array( $RoomCheckoutShortcode, 'registerRoutes' ) );

		// register api routes for close rooms api
		add_action( 'rest_api_init', array( $CloseRoomsController, 'registerRoutes' ) );

		// register api routes for activity log api
		add_action( 'rest_api_init', array( $ActivityLogController, 'registerRoutes' ) );

		// register api routes for custom rates api
		add_action( 'rest_api_init', array( $CustomRatesController, 'registerRoutes' ) );

		// register api routes for room api
		add_action( 'rest_api_init', array( $RoomController, 'registerRoutes' ) );

		// add footer
		add_action( 'wp_footer', array( $this, 'footer' ) );

		// set headers
		add_action( 'send_headers', array( $this, 'setSameSite' ) );
	}

	/**
	 * <p><b>create tables on plugin activation</b></p>
	 */
	public function createTables() {
		global $wpdb;

		$postsTable            = $wpdb->prefix . 'posts';
		$plansTable            = $wpdb->prefix . 'rise_pricing_plans';
		$bookingDetailsTable   = $wpdb->prefix . 'rise_booking_details';
		$closedRoomsTable      = $wpdb->prefix . 'rise_closed_rooms';
		$activityLogTable      = $wpdb->prefix . 'rise_activity_log';
		$pricingPlansMetaTable = $wpdb->prefix . 'rise_pricing_plans_meta';

		// get the charset and collation this db currently uses, so we can use it in our table as well
		$charset_collate = $wpdb->get_charset_collate();

		// sql query to create and initialize our tables
		$initializeTablesSQL = file_get_contents( RISE_LOCATION . '/Repository/InitializeTables.sql' );

        // insert table names, charset and other info into the sql query
        $initializeTablesSQL = str_replace( '$postsTable$', $postsTable, $initializeTablesSQL );
        $initializeTablesSQL = str_replace( '$plansTable', $plansTable, $initializeTablesSQL );
        $initializeTablesSQL = str_replace( '$bookingDetailsTable', $bookingDetailsTable, $initializeTablesSQL );
        $initializeTablesSQL = str_replace( '$closedRoomsTable', $closedRoomsTable, $initializeTablesSQL );
        $initializeTablesSQL = str_replace( '$activityLogTable', $activityLogTable, $initializeTablesSQL );
        $initializeTablesSQL = str_replace( '$pricingPlansMetaTable', $pricingPlansMetaTable, $initializeTablesSQL );
        $initializeTablesSQL = str_replace( '$charset_collate', $charset_collate, $initializeTablesSQL );

		// creating a PDO DB object because neither wpdb nor dbDelta is able to call stored procedures
		$host = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET;
		$pdoDB = new PDO($host, DB_USER, DB_PASSWORD);
		$pdoDB->exec( $initializeTablesSQL );
        unset($pdoDB);
        unset($host);
	}


	/**
	 * <p><b>Check if the protocol being used is HTTPS, and set transient if it's not.</b></p>
	 */
	public function checkSSL() {
		if ( ! isset( $_SERVER['HTTPS'] ) || $_SERVER['HTTPS'] != 'on' ) {
			set_transient( 'rise-ssl-warning', true, 0 );
		}
	}


	/**
	 * <p><b>Display warnings depending on transients.</b></p>
	 */
	public function showWarnings() { // TODO: Maybe move this to a separate WarningController class?
		/* Check transient, if available display notice */
		if ( get_transient( 'rise-ssl-warning' ) ) {
			?>
            <div class="updated rise-message notice is-dismissible">
                <b><?php _e( 'RISE Hotel Booking Warning', 'rise-hotel-booking' ) ?></b>
                <p><?php _e( 'It is suggested to use a valid SSL certificate & redirect all the traffic through HTTPS for optimal performance and security.', 'rise-hotel-booking' ) ?></p>
            </div>
			<?php
			delete_transient( 'rise-ssl-warning' );
		}

		$postTypesToShowWarning = [
			'rise_room',
			'rise_booking',
			'rise_coupon',
			'rise_pricing_rate'
		];

		$pagesToShowWarning = [
			'rise_pricing_plans',
			'rise_close_rooms',
			'rise_settings',
			'rise_activity_log'
		];

		// check if any of the rooms have any warnings, if so, display them
		$currentPostType = get_post_type();
		$currentPage     = sanitize_text_field( @$_GET['page'] );
		if ( in_array( $currentPostType, $postTypesToShowWarning ) || in_array( $currentPage, $pagesToShowWarning ) ) {
			$warnings = RoomController::getRoomWarnings();
			if ( $warnings ) {
				echo '<div class="updated rise-message notice">';
				echo '<b>' . _e( 'RISE Hotel Booking Warning', 'rise-hotel-booking' ) . '</b>';
				foreach ( $warnings as $warning ) {
					echo '<p>' . wp_kses( $warning['message'], array( 'b' => array() ) ) . '</p>';
				}
				echo '</div>';
			}
		}
	}


	/**
	 * <p><b>add scripts and styles for admin</b></p>
	 */
	public function adminScriptsStyles() {
		wp_enqueue_style(
			'rise_admin_CSS',
			RISE_LOCATION_URL . '/View/AdminPanel/css/AdminPanelStylesheet.css',
			array(),
			1,
			'all'
		);

		wp_enqueue_script(
			'rise_admin_JS',
			RISE_LOCATION_URL . '/View/AdminPanel/js/AdminPanel.js',
			array(
				'jquery',
				'wp-i18n'
			),
			1,
			true
		);

		wp_localize_script( 'rise_admin_JS', 'rise_data', [
			'rest' => [
				'endpoints' => [
					'get_prices'                => esc_url_raw( rest_url( 'rise-hotel-booking/v1/get-prices' ) ),
					'get_price'                 => esc_url_raw( rest_url( 'rise-hotel-booking/v1/get-price' ) ),
					'check_availability'        => esc_url_raw( rest_url( 'rise-hotel-booking/v1/check-availability' ) ),
					'get_room_information'      => esc_url_raw( rest_url( 'rise-hotel-booking/v1/get-room-information' ) ),
					'get_closed_dates'          => esc_url_raw( rest_url( 'rise-hotel-booking/v1/get-closed-dates' ) ),
					'get_activity_log'          => esc_url_raw( rest_url( 'rise-hotel-booking/v1/get-activity-log' ) ),
					'get_rates_for_dates'       => esc_url_raw( rest_url( 'rise-hotel-booking/v1/get-rates-for-dates' ) ),
					'get_rate_name_by_id'       => esc_url_raw( rest_url( 'rise-hotel-booking/v1/get-rate-name-by-id' ) ),
					'get_room_meta_box_details' => esc_url_raw( rest_url( 'rise-hotel-booking/v1/get-room-meta-box-details' ) ),
				],
				'nonce'     => wp_create_nonce( 'wp_rest' ),
			],
		] );

		wp_enqueue_style( 'bootstrap4', BOOTSTRAP_CSS );
		wp_enqueue_style( 'daterangepicker_css', DATERANGEPICKER_CSS );
		wp_enqueue_script( 'bootstrap-bundle', BOOTSTRAP_JS );
		wp_enqueue_script( 'moment_js', MOMENT_JS );
		wp_enqueue_script( 'daterangepicker_js', DATERANGEPICKER_JS );
		wp_enqueue_style( 'fullcalendar_css', FULLCALENDAR_CSS );
		wp_enqueue_script( 'fullcalendar_js', FULLCALENDAR_JS );
		wp_enqueue_style( 'select2_css', SELECT2_CSS );
		wp_enqueue_script( 'select2_js', SELECT2_JS );
		wp_enqueue_style( 'datatables_css', DATATABLES_CSS );
		wp_enqueue_script( 'datatables_js', DATATABLES_JS );
	}


	/**
	 * <p><b>add scripts and styles for frontend</b></p>
	 */
	public function frontendScriptsStyles() {
		wp_enqueue_style(
			'rise_frontend_CSS',
			RISE_LOCATION_URL . '/View/FrontEnd/css/FrontEndStylesheet.css',
			array(),
			1,
			'all'
		);

		wp_enqueue_script(
			'rise_frontend_JS',
			RISE_LOCATION_URL . '/View/FrontEnd/js/FrontEnd.js',
			array(
				'jquery'
			),
			1,
			true
		);

		wp_localize_script( 'rise_frontend_JS', 'rise_data', [
			'rest' => [
				'endpoints' => [
					'get_customer_details_by_email' => esc_url_raw( rest_url( 'rise-hotel-booking/v1/get-customer-details-by-email' ) ),
					'delete_room_from_session'      => esc_url_raw( rest_url( 'rise-hotel-booking/v1/delete-room-from-session' ) ),
					'check_coupon_availability'     => esc_url_raw( rest_url( 'rise-hotel-booking/v1/check-coupon-availability' ) ),
					'remove_coupon'                 => esc_url_raw( rest_url( 'rise-hotel-booking/v1/remove-coupon' ) ),
				],
				'nonce'     => wp_create_nonce( 'wp_rest' ),
			],
		] );

		wp_enqueue_style( 'bootstrap4', BOOTSTRAP_CSS );
		wp_enqueue_script( 'bootstrap-bundle', BOOTSTRAP_JS );
		wp_enqueue_style( 'daterangepicker_css', DATERANGEPICKER_CSS );
		wp_enqueue_script( 'moment_js', MOMENT_JS );
		wp_enqueue_script( 'daterangepicker_js', DATERANGEPICKER_JS );
		wp_enqueue_script( 'stripe_checkout_js', STRIPE_CHECKOUT_JS );
		wp_enqueue_style( 'select2_css', SELECT2_CSS );
		wp_enqueue_script( 'select2_js', SELECT2_JS );

		wp_enqueue_style( 'dashicons' );
	}


	/**
	 * <p><b>Includes footer PHP file if footer text is enabled in settings.</b></p>
	 */
	public function footer() {
		include_once( RISE_LOCATION . '/View/FrontEnd/Footer.php' );
	}


	/**
	 * <p><b>Sets SameSite=None to avoid session problems while using 3rd party payment gateways.</b></p>
	 */
	public function setSameSite() {
		header( 'Set-Cookie: ' . session_name() . '=' . session_id() . '; SameSite=None; Secure', false );
	}
}

new RISE();