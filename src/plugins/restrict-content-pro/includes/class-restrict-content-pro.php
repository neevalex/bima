<?php
/**
 * Restrict Content Pro Base Class
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2018, Restrict Content Pro team
 * @license   GPL2+
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Restrict_Content_Pro' ) ) :


	/**
	 * Class Restrict_Content_Pro
	 *
	 * @since 3.0
	 */
	final class Restrict_Content_Pro {

		/**
		 * @var Restrict_Content_Pro The one true Restrict_Content_Pro
		 *
		 * @since 3.0
		 */
		private static $instance;

		/**
		 * RCP loader file.
		 *
		 * @since 3.0
		 * @var string
		 */
		private $file = '';

		/**
		 * @var \RCP\Database\Tables\Customers
		 */
		public $customers_table;

		/**
		 * @var \RCP\Database\Tables\Memberships
		 */
		public $memberships_table;

		/**
		 * @var \RCP\Database\Tables\Queue
		 */
		public $queue_table;

		/**
		 * Main Restrict_Content_Pro Instance.
		 *
		 * Insures that only one instance of Restrict_Content_Pro exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @since     3.0
		 *
		 * @static
		 * @staticvar array $instance
		 *
		 * @uses      Restrict_Content_Pro::setup_constants() Setup constants.
		 * @uses      Restrict_Content_Pro::setup_files() Setup required files.
		 * @see       restrict_content_pro()
		 *
		 * @param string $file Main plugin file path.
		 *
		 * @return Restrict_Content_Pro The one true Restrict_Content_Pro
		 */
		public static function instance( $file = '' ) {

			// Return if already instantiated
			if ( self::is_instantiated() ) {
				return self::$instance;
			}

			// Setup the singleton
			self::setup_instance( $file );

			// Bootstrap
			self::$instance->setup_constants();
			self::$instance->setup_globals();
			self::$instance->setup_files();
			self::$instance->setup_application();

			// Backwards compat globals
			self::$instance->backcompat_globals();

			// Return the instance
			return self::$instance;

		}

		/**
		 * Throw error on object clone.
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @since  3.0
		 * @return void
		 */
		public function __clone() {
			// Cloning instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'rcp' ), '3.0' );
		}

		/**
		 * Disable un-serializing of the class.
		 *
		 * @since  3.0
		 * @return void
		 */
		public function __wakeup() {
			// Unserializing instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'rcp' ), '3.0' );
		}

		/**
		 * Return whether the main loading class has been instantiated or not.
		 *
		 * @access private
		 * @since  3.0
		 * @return boolean True if instantiated. False if not.
		 */
		private static function is_instantiated() {

			// Return true if instance is correct class
			if ( ! empty( self::$instance ) && ( self::$instance instanceof Restrict_Content_Pro ) ) {
				return true;
			}

			// Return false if not instantiated correctly
			return false;
		}

		/**
		 * Setup the singleton instance
		 *
		 * @param string $file Path to main plugin file.
		 *
		 * @access private
		 * @since  3.0
		 */
		private static function setup_instance( $file = '' ) {
			self::$instance       = new Restrict_Content_Pro();
			self::$instance->file = $file;
		}

		/**
		 * Setup plugin constants.
		 *
		 * @access private
		 * @since  3.0
		 * @return void
		 */
		private function setup_constants() {

			if ( ! defined( 'RCP_PLUGIN_VERSION' ) ) {
				define( 'RCP_PLUGIN_VERSION', '3.1' );
			}

			if ( ! defined( 'RCP_PLUGIN_FILE' ) ) {
				define( 'RCP_PLUGIN_FILE', $this->file );
			}

			if ( ! defined( 'RCP_PLUGIN_DIR' ) ) {
				define( 'RCP_PLUGIN_DIR', plugin_dir_path( RCP_PLUGIN_FILE ) );
			}

			if ( ! defined( 'RCP_PLUGIN_URL' ) ) {
				define( 'RCP_PLUGIN_URL', plugin_dir_url( RCP_PLUGIN_FILE ) );
			}

			if ( ! defined( 'CAL_GREGORIAN' ) ) {
				define( 'CAL_GREGORIAN', 1 );
			}

		}

		/**
		 * Setup globals
		 *
		 * @access private
		 * @since  3.0.1
		 * @return void
		 */
		private function setup_globals() {
			$GLOBALS['rcp_options'] = get_option( 'rcp_settings', array() );
		}

		/**
		 * Include required files.
		 *
		 * @access private
		 * @since  3.0
		 * @return void
		 */
		private function setup_files() {
			$this->include_files();

			// Admin
			if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
				$this->include_admin();
			} else {
				$this->include_frontend();
			}
		}


		/**
		 * Setup the rest of the application
		 *
		 * @since 3.0
		 */
		private function setup_application() {

			self::$instance->customers_table   = new \RCP\Database\Tables\Customers();
			self::$instance->memberships_table = new \RCP\Database\Tables\Memberships();
			self::$instance->queue_table       = new \RCP\Database\Tables\Queue();

			new \RCP\Database\Tables\Membership_Meta();

		}

		/** Includes **************************************************************/

		/**
		 * Include global files
		 *
		 * @access public
		 * @since  3.0
		 */
		private function include_files() {

			// Database
			require_once RCP_PLUGIN_DIR . 'includes/database/engine/class-base.php';
			require_once RCP_PLUGIN_DIR . 'includes/database/engine/class-table.php';
			require_once RCP_PLUGIN_DIR . 'includes/database/engine/class-query.php';
			require_once RCP_PLUGIN_DIR . 'includes/database/engine/class-column.php';
			require_once RCP_PLUGIN_DIR . 'includes/database/engine/class-row.php';
			require_once RCP_PLUGIN_DIR . 'includes/database/engine/class-schema.php';

			// Tables
			require_once RCP_PLUGIN_DIR . 'includes/database/customers/class-customers-table.php';
			require_once RCP_PLUGIN_DIR . 'includes/database/memberships/class-memberships-table.php';
			require_once RCP_PLUGIN_DIR . 'includes/database/memberships/class-membership-meta-table.php';
			require_once RCP_PLUGIN_DIR . 'includes/database/queue/class-queue-table.php';

			// Queries
			require_once RCP_PLUGIN_DIR . 'includes/database/customers/class-customer-query.php';
			require_once RCP_PLUGIN_DIR . 'includes/database/memberships/class-membership-query.php';
			require_once RCP_PLUGIN_DIR . 'includes/database/queue/class-queue-query.php';

			// Schemas
			require_once RCP_PLUGIN_DIR . 'includes/database/customers/class-customers-schema.php';
			require_once RCP_PLUGIN_DIR . 'includes/database/memberships/class-memberships-schema.php';
			require_once RCP_PLUGIN_DIR . 'includes/database/queue/class-queue-schema.php';

			// @todo can this be improved?
			require( RCP_PLUGIN_DIR . 'includes/install.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/class-rcp-base-object.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/class-rcp-capabilities.php' );
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				require_once( RCP_PLUGIN_DIR . 'includes/class-rcp-cli.php' );
			}
			require_once( RCP_PLUGIN_DIR . 'includes/class-rcp-emails.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/class-rcp-integrations.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/class-rcp-levels.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/class-rcp-logging.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/class-rcp-member.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/class-rcp-payments.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/class-rcp-discounts.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/class-rcp-registration.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/class-rcp-reminders.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/scripts.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/ajax-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/cron-functions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/compat/class-base.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/compat/class-member.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/customers/class-rcp-customer.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/customers/customer-functions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/deprecated/functions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/discount-functions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/email-functions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/gateways/class-rcp-payment-gateway.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/gateways/class-rcp-payment-gateway-braintree.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/gateways/class-rcp-payment-gateway-manual.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/gateways/class-rcp-payment-gateway-paypal.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/gateways/class-rcp-payment-gateway-paypal-pro.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/gateways/class-rcp-payment-gateway-paypal-express.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/gateways/class-rcp-payment-gateway-stripe.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/gateways/class-rcp-payment-gateway-stripe-checkout.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/gateways/class-rcp-payment-gateway-2checkout.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/gateways/class-rcp-payment-gateways.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/gateways/gateway-functions.php' );
			rcp_load_gateway_files();
			require_once( RCP_PLUGIN_DIR . 'includes/invoice-functions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/levels/level-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/levels/meta.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/login-functions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/member-forms.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/member-functions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/memberships/class-rcp-membership.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/memberships/membership-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/memberships/membership-functions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/memberships/meta.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/payments/meta.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/misc-functions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/payments/payment-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/registration-functions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/subscription-functions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/error-tracking.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/shortcodes.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/template-functions.php' );

			require_once RCP_PLUGIN_DIR . 'includes/batch/interface-job-callback.php';
			require_once RCP_PLUGIN_DIR . 'includes/batch/abstract-job-callback.php';
			require_once RCP_PLUGIN_DIR . 'includes/batch/batch-functions.php';
			require_once RCP_PLUGIN_DIR . 'includes/batch/class-job.php';

			// @todo load this only when needed
			require_once RCP_PLUGIN_DIR . 'includes/batch/v3/class-migrate-memberships.php';

			// @todo remove
			if ( ! class_exists( 'WP_Logging' ) ) {
				require_once( RCP_PLUGIN_DIR . 'includes/deprecated/class-wp-logging.php' );
			}

		}

		/**
		 * Setup administration
		 *
		 * @since 3.0
		 */
		private function include_admin() {

			global $rcp_options;

			require_once( RCP_PLUGIN_DIR . 'includes/admin/upgrades.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/class-list-table.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/class-rcp-upgrades.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/admin-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/admin-pages.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/admin-notices.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/admin-ajax-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/class-rcp-add-on-updater.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/customers/customer-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/customers/customers-page.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/screen-options.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/members/member-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/members/members-page.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/memberships/membership-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/reminders/subscription-reminders.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/settings/settings.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/subscriptions/subscription-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/subscriptions/subscription-levels.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/discounts/discount-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/discounts/discount-codes.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/payments/payment-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/payments/payments-page.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/reports/reports-page.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/export.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/tools/tools-page.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/import/import-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/import/import-functions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/help/help-menus.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/metabox.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/add-ons.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/terms.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/admin/post-types/restrict-post-type.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/user-page-columns.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/export-functions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/deactivation.php' );
			require_once( RCP_PLUGIN_DIR . 'RCP_Plugin_Updater.php' );

			// batch processing
			require_once RCP_PLUGIN_DIR . 'includes/admin/batch/ajax-actions.php';

			// retrieve our license key from the DB
			$license_key = ! empty( $rcp_options['license_key'] ) ? trim( $rcp_options['license_key'] ) : false;

			if ( $license_key ) {
				// setup the updater
				$rcp_updater = new RCP_Plugin_Updater( 'https://restrictcontentpro.com', RCP_PLUGIN_FILE, array(
						'version' => RCP_PLUGIN_VERSION, // current version number
						'license' => $license_key, // license key (used get_option above to retrieve from DB)
						'item_id' => 479, // Download ID
						'author'  => 'Restrict Content Pro Team', // author of this plugin
						'beta'    => ! empty( $rcp_options['show_beta_updates'] )
					)
				);
			}

		}

		/**
		 * Setup front-end specific code
		 *
		 * @since 3.0
		 */
		private function include_frontend() {

			require_once( RCP_PLUGIN_DIR . 'includes/content-filters.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/captcha-functions.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/query-filters.php' );
			require_once( RCP_PLUGIN_DIR . 'includes/redirects.php' );

		}

		/**
		 * Backwards compatibility for old global values
		 *
		 * @since 3.0
		 */
		private function backcompat_globals() {

			global $wpdb, $rcp_payments_db, $rcp_levels_db, $rcp_discounts_db;

			// the plugin base directory
			global $rcp_base_dir; // not used any more, but just in case someone else is
			$rcp_base_dir = dirname( __FILE__ );

			global $rcp_db_name;
			$rcp_db_name = rcp_get_levels_db_name();

			global $rcp_db_version;
			$rcp_db_version = '1.6';

			global $rcp_discounts_db_name;
			$rcp_discounts_db_name = rcp_get_discounts_db_name();

			global $rcp_discounts_db_version;
			$rcp_discounts_db_version = '1.2';

			global $rcp_payments_db_name;
			$rcp_payments_db_name = rcp_get_payments_db_name();

			global $rcp_payments_db_version;
			$rcp_payments_db_version = '1.5';

			/* database table query globals */

			$rcp_payments_db       = new RCP_Payments;
			$rcp_levels_db         = new RCP_Levels;
			$rcp_discounts_db      = new RCP_Discounts;
			$wpdb->levelmeta       = $rcp_levels_db->meta_db_name;
			$wpdb->rcp_paymentmeta = $rcp_payments_db->meta_db_name;

			/* settings page globals */
			global $rcp_members_page;
			global $rcp_subscriptions_page;
			global $rcp_discounts_page;
			global $rcp_payments_page;
			global $rcp_settings_page;
			global $rcp_reports_page;
			global $rcp_export_page;
			global $rcp_help_page;

		}

	}

endif; // End if class_exists check.

/**
 * Returns the instance of Restrict_Content_Pro.
 *
 * The main function responsible for returning the one true Restrict_Content_Pro
 * instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $rcp = restrict_content_pro(); ?>
 *
 * @since 3.0
 * @return Restrict_Content_Pro The one true Restrict_Content_Pro instance.
 */
function restrict_content_pro() {
	return Restrict_Content_Pro::instance();
}