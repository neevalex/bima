<?php
/**
 * Gateway Functions
 *
 * @package     Restrict Content Pro
 * @subpackage  Gateways/Functions
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Load additional gateway include files
 *
 * @uses rcp_get_payment_gateways()
 *
 * @access private
 * @since  2.1
 * @return void
*/
function rcp_load_gateway_files() {
	foreach( rcp_get_payment_gateways() as $key => $gateway ) {
		if( file_exists( RCP_PLUGIN_DIR . 'includes/gateways/' . $key . '/functions.php' ) ) {
			require_once RCP_PLUGIN_DIR . 'includes/gateways/' . $key . '/functions.php';
		}
	}
}

/**
 * Get all available payment gateways
 *
 * @access      private
 * @return      array
*/
function rcp_get_payment_gateways() {
	$gateways = new RCP_Payment_Gateways;
	return $gateways->available_gateways;
}

/**
 * Get information about a payment gateway by its slug.
 *
 * For example, if you have the slug `paypal_express` and want to return the admin label of
 * `PayPal Express`, you'd use this function like so:
 *
 * rcp_get_payment_gateway_details( 'paypal_express', 'admin_label' )
 *
 * @param string $slug Gateway slug to get details for.
 * @param string $key  Specific key to retrieve. Leave blank for array of all details, including:
 *                     `label`, `admin_label`, `class`
 *
 * @since 3.0.4
 * @return array|string
 */
function rcp_get_payment_gateway_details( $slug, $key = '' ) {

	$gateways = rcp_get_payment_gateways();
	$details  = array();

	if ( isset( $gateways[ $slug ] ) ) {
		$details = $gateways[ $slug ];
	}

	if ( ! empty( $key ) && isset( $details[ $key ] ) ) {
		return $details[ $key ];
	} elseif ( ! empty( $key ) ) {
		return '';
	}

	return $details;

}

/**
 * Return list of active gateways
 *
 * @access      private
 * @return      array
*/
function rcp_get_enabled_payment_gateways() {

	$gateways = new RCP_Payment_Gateways;

	foreach( $gateways->enabled_gateways  as $key => $gateway ) {

		if( is_array( $gateway ) ) {

			$gateways->enabled_gateways[ $key ] = $gateway['label'];

		}

	}

	return $gateways->enabled_gateways;
}

/**
 * Determine if a gateway is enabled
 *
 * @param string $id ID of the gateway to check.
 *
 * @access public
 * @return bool
 */
function rcp_is_gateway_enabled( $id = '' ) {
	$gateways = new RCP_Payment_Gateways;
	return $gateways->is_gateway_enabled( $id );
}

/**
 * Send payment / subscription data to gateway
 *
 * @param string $gateway           ID of the gateway.
 * @param array  $subscription_data Subscription data.
 *
 * @access      private
 * @return      void
 */
function rcp_send_to_gateway( $gateway, $subscription_data ) {

	if( has_action( 'rcp_gateway_' . $gateway ) ) {

		do_action( 'rcp_gateway_' . $gateway, $subscription_data );

	} else {

		$gateways = new RCP_Payment_Gateways;
		$gateway  = $gateways->get_gateway( $gateway );
		$gateway  = new $gateway['class']( $subscription_data );

		$gateway->process_signup();

	}

}

/**
 * Determines if a gateway supports recurring payments
 *
 * @param string $gateway ID of the gateway to check.
 * @param string $item    Feature to check support for.
 *
 * @access  public
 * @since   2.1
 * @return  bool
 */
function rcp_gateway_supports( $gateway = 'paypal', $item = 'recurring' ) {

	$ret      = true;
	$gateways = new RCP_Payment_Gateways;
	$gateway  = $gateways->get_gateway( $gateway );

	if( is_array( $gateway ) && isset( $gateway['class'] ) ) {

		$gateway = new $gateway['class'];
		$ret     = $gateway->supports( sanitize_text_field( $item ) );

	}

	return $ret;

}

/**
 * Load webhook processor for all gateways
 *
 * @access      public
 * @since       2.1
 * @return      void
*/
function rcp_process_gateway_webooks() {

	if ( ! apply_filters( 'rcp_process_gateway_webhooks', ! empty( $_GET['listener'] ) ) ) {
		return;
	}

	$gateways = new RCP_Payment_Gateways;

	foreach( $gateways->available_gateways  as $key => $gateway ) {

		if( is_array( $gateway ) && isset( $gateway['class'] ) ) {

			$gateway = new $gateway['class'];
			$gateway->process_webhooks();

		}

	}

}
add_action( 'init', 'rcp_process_gateway_webooks', -99999 );

/**
 * Process gateway confirmaions
 *
 * @access      public
 * @since       2.1
 * @return      void
*/
function rcp_process_gateway_confirmations() {

	global $rcp_options;

	if( empty( $rcp_options['registration_page'] ) ) {
		return;
	}

	if( empty( $_GET['rcp-confirm'] ) ) {
		return;
	}

	if( ! rcp_is_registration_page() ) {
		return;
	}

	$gateways = new RCP_Payment_Gateways;
	$gateway  = sanitize_text_field( $_GET['rcp-confirm'] );

	if( ! $gateways->is_gateway_enabled( $gateway ) ) {
		return;
	}

	$gateway = $gateways->get_gateway( $gateway );

	if( is_array( $gateway ) && isset( $gateway['class'] ) ) {

		$gateway = new $gateway['class'];
		$gateway->process_confirmation();

	}

}
add_action( 'template_redirect', 'rcp_process_gateway_confirmations', -99999 );

/**
 * Load gateway scripts on registration page
 *
 * @access      public
 * @since       2.1
 * @return      void
*/
function rcp_load_gateway_scripts() {

	global $rcp_options;

	$load_scripts = rcp_is_registration_page() || defined( 'RCP_LOAD_SCRIPTS_GLOBALLY' );
	$gateways     = new RCP_Payment_Gateways;

	/*
	 * Unless the option is disabled, Stripe.js is loaded on all pages for advanced fraud functionality.
	 */
	$global_scripts = empty( $rcp_options['disable_sitewide_scripts'] ) ? array( 'stripe', 'stripe_checkout' ) : array();

	foreach( $gateways->enabled_gateways  as $key => $gateway ) {

		if( is_array( $gateway ) && isset( $gateway['class'] ) && ( $load_scripts || in_array( $key, $global_scripts ) ) ) {

			$gateway = new $gateway['class'];
			$gateway->scripts();

		}

	}

}
add_action( 'wp_enqueue_scripts', 'rcp_load_gateway_scripts', 100 );

/**
 * Process an update card form request
 *
 * @uses rcp_member_can_update_billing_card()
 *
 * @access      private
 * @since       2.1
 * @return      void
 */
function rcp_process_update_card_form_post() {

	if( ! is_user_logged_in() ) {
		return;
	}

	if( is_admin() ) {
		return;
	}

	if ( ! isset( $_POST['rcp_update_card_nonce'] ) || ! wp_verify_nonce( $_POST['rcp_update_card_nonce'], 'rcp-update-card-nonce' ) ) {
		return;
	}

	$membership_id = isset( $_POST['rcp_membership_id'] ) ? absint( $_POST['rcp_membership_id'] ) : false;

	if ( empty( $membership_id ) ) {
		$customer   = rcp_get_customer_by_user_id(); // current customer
		$membership = ! empty( $customer ) ? rcp_get_customer_single_membership( $customer->get_id() ) : false;
	} else {
		$membership = rcp_get_membership( $membership_id );
	}

	if ( ! is_object( $membership ) || 0 == $membership->get_id() ) {
		wp_die( __( 'Invalid membership.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 500 ) );
	}

	// Bail if this user isn't actually the customer associated with this membership.
	if ( $membership->get_customer()->get_user_id() != get_current_user_id() ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if( ! $membership->can_update_billing_card() ) {
		wp_die( __( 'Your account does not support updating your billing card', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( has_action( 'rcp_update_billing_card' ) ) {
		$member = new RCP_Member( get_current_user_id() );

		if ( $member ) {
			/**
			 * @deprecated 3.0 Use `rcp_update_membership_billing_card` instead.
			 */
			do_action( 'rcp_update_billing_card', $member->ID, $member );
		}
	}

	/**
	 * Processes the billing card update. Individual gateways hook into here.
	 *
	 * @param RCP_Membership $membership
	 *
	 * @since 3.0
	 */
	do_action( 'rcp_update_membership_billing_card', $membership );

}
add_action( 'init', 'rcp_process_update_card_form_post' );

/**
 * Retrieve the full HTML link for the transaction ID on the merchant site
 *
 * @param object  $payment Payment object
 *
 * @access public
 * @since  2.6
 * @return string HTML link, or just the transaction ID.
 */
function rcp_get_merchant_transaction_id_link( $payment ) {

	global $rcp_options;

	$url  = '';
	$link = $payment->transaction_id;
	$test = rcp_is_sandbox();

	if( ! empty( $payment->transaction_id ) ) {

		$gateway = strtolower( $payment->gateway );
		$type    = strtolower( $payment->payment_type );

		if ( empty( $gateway ) && ! empty( $type ) ) {

			switch ( $type ) {

				case 'web_accept' :
				case 'paypal express one time' :
				case 'recurring_payment' :
				case 'subscr_payment' :
				case 'recurring_payment_profile_created' :
					$gateway = 'paypal';
					break;

				case 'credit card' :
				case 'credit card one time' :
					if ( false !== strpos( $payment->transaction_id, 'ch_' ) ) {
						$gateway = 'stripe';
					} elseif( false !== strpos( $payment->transaction_id, 'anet_' ) ) {
						$gateway = 'authorizenet';
					} elseif ( is_numeric( $payment->transaction_id ) ) {
						$gateway = 'twocheckout';
					}
					break;

				case 'braintree credit card one time' :
				case 'braintree credit card initial payment' :
				case 'braintree credit card' :
					$gateway = 'braintree';
					break;

			}

		}

		switch( $gateway ) {

			// PayPal
			case 'paypal' :
			case 'paypal_express' :
			case 'paypal_pro' :

				$mode = $test ? 'sandbox.' : '';
				$url  = 'https://www.' . $mode . 'paypal.com/webscr?cmd=_history-details-from-hub&id=' . $payment->transaction_id;

				break;

			// 2Checkout
			case 'twocheckout' :

				$mode = $test ? 'sandbox.' : '';
				$url  = 'https://' . $mode . '2checkout.com/sandbox/sales/detail?sale_id=' . $payment->transaction_id;

				break;

			// Stripe
			case 'stripe' :
			case 'stripe_checkout' :

				$mode = $test ? 'test/' : '';
				$dir  = false !== strpos( $payment->transaction_id, 'sub_' ) ? 'subscriptions/' : 'payments/';
				$url  = 'https://dashboard.stripe.com/' . $mode . $dir . $payment->transaction_id;

				break;

			// Braintree
			case 'braintree' :

				$mode        = $test ? 'sandbox.' : '';
				$merchant_id = $test ? $rcp_options['braintree_sandbox_merchantId'] : $rcp_options['braintree_live_merchantId'];

				$url         = 'https://' . $mode . 'braintreegateway.com/merchants/' . $merchant_id . '/transactions/' . $payment->transaction_id;

				break;
		}

		if( ! empty( $url ) ) {

			$link = '<a href="' . esc_url( $url ) . '" class="rcp-payment-txn-id-link" target="_blank">' . $payment->transaction_id . '</a>';

		}

	}

	return apply_filters( 'rcp_merchant_transaction_id_link', $link, $payment );

}

/**
 * Returns the name of the gateway, given the class object
 *
 * @param RCP_Payment_Gateway $gateway Gateway object.
 *
 * @since 2.9
 * @return string
 */
function rcp_get_gateway_name_from_object( $gateway ) {

	$gateway_classes = wp_list_pluck( rcp_get_payment_gateways(), 'class' );
	$gateway_name    = array_search( get_class( $gateway ), $gateway_classes );

	return ucwords( $gateway_name );

}

/**
 * Log cancellation via webhook
 *
 * @param RCP_Member          $member  Member object.
 * @param RCP_Payment_Gateway $gateway Gateway object.
 *
 * @since 2.9
 * @return void
 */
function rcp_log_webhook_cancel( $member, $gateway ) {
	rcp_log( sprintf( 'Membership cancelled via %s webhook for member ID #%d.', rcp_get_gateway_name_from_object( $gateway ), $member->ID ) );
}
add_action( 'rcp_webhook_cancel', 'rcp_log_webhook_cancel', 10, 2 );

/**
 * Log new recurring payment profile created via webhook. This is when the
 * subscription is initially created, it does not include renewals.
 *
 * @param RCP_Member          $member  Member object.
 * @param RCP_Payment_Gateway $gateway Gateway object.
 *
 * @since 2.9
 * @return void
 */
function rcp_log_webhook_recurring_payment_profile_created( $member, $gateway ) {
	rcp_log( sprintf( 'New recurring payment profile created for member #%d in gateway %s.', $member->ID, rcp_get_gateway_name_from_object( $gateway ) ) );
}
add_action( 'rcp_webhook_recurring_payment_profile_created', 'rcp_log_webhook_recurring_payment_profile_created', 10, 2 );

/**
 * Log error when duplicate payment is detected.
 *
 * @param string              $payment_txn_id Payment transaction ID.
 * @param RCP_Member          $member         Member object.
 * @param RCP_Payment_Gateway $gateway        Gateway object.
 *
 * @since 2.9
 * @return void
 */
function rcp_log_duplicate_ipn_payment( $payment_txn_id, $member, $gateway ) {
	rcp_log( sprintf( 'A duplicate payment was detected for user #%d. Check to make sure both payments weren\'t recorded. Transaction ID: %s', $member->ID, $payment_txn_id ) );
}
add_action( 'rcp_ipn_duplicate_payment', 'rcp_log_duplicate_ipn_payment', 10, 3 );

/**
 * Log payment inserted via gateway. This can run on renewals and/or one-time payments.
 *
 * @param RCP_Member          $member     Member object.
 * @param int                 $payment_id ID of the payment that was just inserted.
 * @param RCP_Payment_Gateway $gateway    Gateway object.
 *
 * @since 2.9
 * @return void
 */
function rcp_log_gateway_payment_processed( $member, $payment_id, $gateway ) {
	rcp_log( sprintf( 'Payment #%d completed for member #%d via %s gateway.', $payment_id, $member->ID, rcp_get_gateway_name_from_object( $gateway ) ) );
}
add_action( 'rcp_gateway_payment_processed', 'rcp_log_gateway_payment_processed', 10, 3 );

/**
 * Update the membership's "recurring_amount" when a renewal payment is processed.
 * This will correct any invalid recurring_amount values due to recurring discounts
 * or level price changes.
 *
 * @param RCP_Member          $member     Member object.
 * @param int                 $payment_id ID of the payment that was just inserted.
 * @param RCP_Payment_Gateway $gateway    Gateway object.
 *
 * @since 3.0.5
 * @return void
 */
function rcp_update_membership_recurring_amount_on_renewal( $member, $payment_id, $gateway ) {

	$payments   = new RCP_Payments();
	$payment    = $payments->get_payment( $payment_id );
	$membership = false;

	if ( empty( $payment ) || empty( $payment->amount ) ) {
		return;
	}

	if ( ! empty( $gateway->membership ) ) {
		$membership = $gateway->membership;
	} elseif ( ! empty( $payment->membership_id ) ) {
		$membership = rcp_get_membership( $payment->membership_id );
	}

	if ( ! is_a( $membership, 'RCP_Membership' ) ) {
		return;
	}

	if ( $payment->amount != $membership->get_recurring_amount() ) {
		$membership->update( array(
			'recurring_amount' => $payment->amount
		) );
	}

}
add_action( 'rcp_webhook_recurring_payment_processed', 'rcp_update_membership_recurring_amount_on_renewal', 10, 3 );

/**
 * Return the direct URL to manage a customer profile in the gateway.
 *
 * @param string $gateway     Gateway slug.
 * @param int    $customer_id ID of the customer profile in the payment gateway.
 *
 * @since 3.0.4
 * @return string
 */
function rcp_get_gateway_customer_id_url( $gateway, $customer_id ) {

	global $rcp_options;

	$url     = '';
	$sandbox = rcp_is_sandbox();

	if ( false !== strpos( $gateway, 'stripe' ) ) {

		/**
		 * Stripe, Stripe Checkout, Stripe Elements (TK).
		 */
		$base_url = $sandbox ? 'https://dashboard.stripe.com/test/' : 'https://dashboard.stripe.com/';
		$url      = $base_url . 'customers/' . urlencode( $customer_id );

	} elseif ( 'braintree' == $gateway ) {

		/**
		 * Braintree
		 */
		$subdomain = $sandbox ? 'sandbox.' : '';
		$merchant_id = '';

		if ( $sandbox && ! empty( $rcp_options['braintree_sandbox_merchantId'] ) ) {
			$merchant_id = $rcp_options['braintree_sandbox_merchantId'];
		} elseif ( ! $sandbox && ! empty( $rcp_options['braintree_live_merchantId'] ) ) {
			$merchant_id = $rcp_options['braintree_live_merchantId'];
		}

		if ( ! empty( $merchant_id ) ) {
			$url = sprintf( 'https://%sbraintreegateway.com/merchants/%s/customers/%s', $subdomain, urlencode( $merchant_id ), urlencode( $customer_id ) );
		}

	}

	/**
	 * Filters the customer profile URL.
	 *
	 * @param string $url         URL to manage the customer profile in the gateway.
	 * @param string $gateway     Payment gateway slug.
	 * @param string $customer_id ID of the customer in the gateway.
	 *
	 * @since 3.0.4
	 */
	return apply_filters( 'rcp_gateway_customer_id_url', $url, $gateway, $customer_id );

}

/**
 * Return the direct URL to manage a subscription in the gateway.
 *
 * @param string $gateway         Gateway slug.
 * @param int    $subscription_id ID of the subscription in the payment gateway.
 *
 * @since 3.0.4
 * @return string
 */
function rcp_get_gateway_subscription_id_url( $gateway, $subscription_id ) {

	global $rcp_options;

	$url     = '';
	$sandbox = rcp_is_sandbox();

	if ( false !== strpos( $gateway, 'stripe' ) ) {

		/**
		 * Stripe, Stripe Checkout, Stripe Elements (TK).
		 */
		$base_url = $sandbox ? 'https://dashboard.stripe.com/test/' : 'https://dashboard.stripe.com/';
		$url      = $base_url . 'subscriptions/' . urlencode( $subscription_id );

	} elseif( false !== strpos( $gateway, 'paypal' ) ) {

		/**
		 * PayPal Standard, PayPal Express, PayPal Pro
		 */
		$base_url = $sandbox ? 'https://www.sandbox.paypal.com' : 'https://www.paypal.com';
		$url      = $base_url . '/cgi-bin/webscr?cmd=_profile-recurring-payments&encrypted_profile_id=' . urlencode( $subscription_id );

	} elseif ( 'twocheckout' == $gateway ) {

		/**
		 * 2Checkout
		 */
		if ( $sandbox ) {
			$base_url = 'https://sandbox.2checkout.com/sandbox/sales/detail';
		} else {
			$base_url = 'https://2checkout.com/sales/detail';
		}

		$twocheckout_id = str_replace( '2co_', '', $subscription_id );
		$url            = add_query_arg( 'sale_id', urlencode( $twocheckout_id ), $base_url );

	} elseif ( 'braintree' == $gateway ) {

		/**
		 * Braintree
		 */
		$subdomain = $sandbox ? 'sandbox.' : '';
		$merchant_id = '';

		if ( $sandbox && ! empty( $rcp_options['braintree_sandbox_merchantId'] ) ) {
			$merchant_id = $rcp_options['braintree_sandbox_merchantId'];
		} elseif ( ! $sandbox && ! empty( $rcp_options['braintree_live_merchantId'] ) ) {
			$merchant_id = $rcp_options['braintree_live_merchantId'];
		}

		if ( ! empty( $merchant_id ) ) {
			$url = sprintf( 'https://%sbraintreegateway.com/merchants/%s/subscriptions/%s', $subdomain, urlencode( $merchant_id ), urlencode( $subscription_id ) );
		}

	}

	/**
	 * Filters the subscription profile URL.
	 *
	 * @param string $url             URL to manage the subscription in the gateway.
	 * @param string $gateway         Payment gateway slug.
	 * @param string $subscription_id ID of the subscription in the gateway.
	 *
	 * @since 3.0.4
	 */
	return apply_filters( 'rcp_gateway_subscription_id_url', $url, $gateway, $subscription_id );

}

/**
 * Get payment gateway slug from gateway customer/subscription IDs.
 *
 * @param array $args                    {
 *
 * @type string $gateway_customer_id     Gateway customer ID.
 * @type string $gateway_subscription_id Gateway subscription ID.
 *                    }
 *
 * @since 3.1
 * @return string|false Gateway slug on success, false if cannot be parsed.
 */
function rcp_get_gateway_slug_from_gateway_ids( $args ) {

	$customer_id      = ! empty( $args['gateway_customer_id'] ) ? $args['gateway_customer_id'] : '';
	$subscription_id  = ! empty( $args['gateway_subscription_id'] ) ? $args['gateway_subscription_id'] : '';
	$enabled_gateways = rcp_get_enabled_payment_gateways();

	// Check for Stripe.
	if ( false !== strpos( $customer_id, 'cus_' ) || false !== strpos( $subscription_id, 'sub_' ) ) {
		if ( array_key_exists( 'stripe', $enabled_gateways ) ) {
			return 'stripe';
		} elseif ( array_key_exists( 'stripe_checkout', $enabled_gateways ) ) {
			return 'stripe_checkout';
		}

		return 'stripe';
	}

	// Check for 2Checkout.
	if ( false !== strpos( $subscription_id, '2co_' ) ) {
		return 'twocheckout';
	}

	// Check for Authorize.net.
	if ( false !== strpos( $subscription_id, 'anet_' ) ) {
		return 'authorizenet';
	}

	// Check for Braintree.
	if ( false !== strpos( $customer_id, 'bt_' ) ) {
		return 'braintree';
	}

	// Check for PayPal.
	if ( false !== strpos( $subscription_id, 'I-' ) ) {
		// Determine which PayPal gateway is activated.
		if ( array_key_exists( 'paypal', $enabled_gateways ) ) {
			return 'paypal';
		} elseif ( array_key_exists( 'paypal_express', $enabled_gateways ) ) {
			return 'paypal_express';
		} elseif ( array_key_exists( 'paypal_express', $enabled_gateways ) ) {
			return 'paypal_pro';
		}

		return 'paypal';
	}

	return false;

}