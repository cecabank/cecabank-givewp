<?php
/**
 * Cecabank for Give | Recurring Support
 *
 */

// Bailout, if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class Cecabank_Recurring extends Give_Recurring_Gateway {

	const QUERY_VAR = 'cecabank_givewp_return';
    const LISTENER_PASSPHRASE = 'cecabank_givewp_listener_passphrase';

	/**
	 * Setup gateway ID and possibly load API libraries.
	 *
	 * @access      public
	 * @return      void
	 */
	public function init() {
		$this->id = 'cecabank';

		// Complete recurring donation via backend response by cecabank.
		$this->offsite = true;
	}

	//session key
	public function cecabank_get_session_key() {
		return apply_filters( 'givececabank_get_session_key', uniqid() );
	}

	public function get_listener_url($payment_id)
	{
		$passphrase = get_option(self::LISTENER_PASSPHRASE, false);
		if (!$passphrase) {
			$passphrase = md5(site_url() . time());
			update_option(self::LISTENER_PASSPHRASE, $passphrase);
		}

		$arg = array(
			self::QUERY_VAR => $passphrase,
			'payment-id' => $payment_id,
		);
		return add_query_arg($arg, site_url('/'));
	}

    function get_client_config($form_id) {
        $lang = '1';
        $locale = get_locale();
        if ($locale) {
            $locale = substr($locale, 0, 2);
        }
        switch ($locale) {
            case 'en':
                $lang = '6';
                break;
            case 'fr':
                $lang = '7';
                break;
            case 'de':
                $lang = '8';
                break;
            case 'pt':
                $lang = '9';
                break;
            case 'it':
                $lang = '10';
                break;
            case 'ru':
                $lang = '14';
                break;
            case 'no':
                $lang = '15';
                break;
            case 'ca':
                $lang = '2';
                break;
            case 'eu':
                $lang = '3';
                break;
            case 'gl':
                $lang = '4';
                break;
            default:
                $lang = '1';
                break;
        }

        if ($form_id) {
            return array(
                'Environment' => give_get_meta($form_id, 'cecabank_environment', true),
                'MerchantID' => give_get_meta($form_id, 'cecabank_merchant', true),
                'AcquirerBIN' => give_get_meta($form_id, 'cecabank_acquirer', true),
                'TerminalID' => give_get_meta($form_id, 'cecabank_terminal', true),
                'ClaveCifrado' => give_get_meta($form_id, 'cecabank_secret_key', true),
                'Exponente' => '2',
                'Cifrado' => 'SHA2',
                'Idioma' => $lang,
                'Pago_soportado' => 'SSL',
                'versionMod' => 'G-0.0.2'
            );
        } else {
            return array(
                'Environment' => give_get_option('cecabank_environment'),
                'MerchantID' => give_get_option('cecabank_merchant'),
                'AcquirerBIN' => give_get_option('cecabank_acquirer'),
                'TerminalID' => give_get_option('cecabank_terminal'),
                'ClaveCifrado' => give_get_option('cecabank_secret_key'),
                'Exponente' => '2',
                'Cifrado' => 'SHA2',
                'Idioma' => $lang,
                'Pago_soportado' => 'SSL',
                'versionMod' => 'G-0.0.2'
            );
        }
    }

    public function process_regular_payment( $cecabank_client, $purchase_data, $payment_id, $currency, $data ) {

        $user_age = 'JUST_CHANGED';
        $user_info_age = 'JUST_CHANGED';
        $registered = '';
        $txn_activity_today = '0';
        $txn_activity_year = '0';
        $txn_purchase_6 = '0';
        $ship_name_indicator = 'IDENTICAL';

        $name = $purchase_data->first_name.' '.$purchase_data->last_name;
        $email = $purchase_data->email;
        $ip = '';
        $city = '';
        $country = '';
        $line1 = '';
        $line2 = '';
        $postal_code = '';
        $state = '';
        $phone = '';
        $ship_city = $city;
        $ship_country = $country;
        $ship_line1 = $line1;
        $ship_line2 = $line2;
        $ship_postal_code = $postal_code;
        $ship_state = $state;

        $ship_indicator = 'DIGITAL_GOODS';
        $delivery_time_frame = 'ELECTRONIC_DELIVERY';
        $delivery_email = $email;
        $reorder_items = 'FIRST_TIME_ORDERED';

        // ACS
        $acs = array();

        // Cardholder
        $cardholder = array();
        $add_cardholder = false;

        // Cardholder bill address
        $bill_address = array();
        $add_bill_address = false;
        if ($city) {
            $bill_address['CITY'] = $city;
            $add_bill_address = true;
        }                
        if ($country) {
            $bill_address['COUNTRY'] = $country;
            $add_bill_address = true;
        }
        if ($line1) {
            $bill_address['LINE1'] = $line1;
            $add_bill_address = true;
        }                
        if ($line2) {
            $bill_address['LINE2'] = $line2;
            $add_bill_address = true;
        }
        if ($postal_code) {
            $bill_address['POST_CODE'] = $postal_code;
            $add_bill_address = true;
        }                
        if ($state) {
            $bill_address['STATE'] = $state;
            $add_bill_address = true;
        }
        if ($add_bill_address) {
            $cardholder['BILL_ADDRESS'] = $bill_address;
            $add_cardholder = true;
        }

        // Cardholder name
        if ($name) {
            $cardholder['NAME'] = $name;
            $add_cardholder = true;
        }

        // Cardholder email
        if ($email) {
            $cardholder['EMAIL'] = $email;
            $add_cardholder = true;
        }

        if ($add_cardholder) {
            $acs['CARDHOLDER'] = $cardholder;
        }

        // Purchase
        $purchase = array();
        $add_purchase = true;

        // Purchase ship address
        $ship_address = array();
        $add_ship_address = false;
        if ($ship_city) {
            $ship_address['CITY'] = $ship_city;
            $add_ship_address = true;
        }                
        if ($ship_country) {
            $ship_address['COUNTRY'] = $ship_country;
            $add_ship_address = true;
        }
        if ($ship_line1) {
            $ship_address['LINE1'] = $ship_line1;
            $add_ship_address = true;
        }                
        if ($ship_line2) {
            $ship_address['LINE2'] = $ship_line2;
            $add_ship_address = true;
        }
        if ($ship_postal_code) {
            $ship_address['POST_CODE'] = $ship_postal_code;
            $add_ship_address = true;
        }                
        if ($ship_state) {
            $ship_address['STATE'] = $ship_state;
            $add_ship_address = true;
        }
        if ($add_ship_address) {
            $purchase['SHIP_ADDRESS'] = $ship_address;
            $add_purchase = true;
        }

        // Purchase mobile phone
        if ($phone) {
            $purchase['MOBILE_PHONE'] = array(
                'SUBSCRIBER' => $phone
            );
            $add_purchase = true;
        }

        if ($add_purchase) {
            $acs['PURCHASE'] = $purchase;
        }

        // Merchant risk
        $merchant_risk = array(
            'PRE_ORDER_PURCHASE_IND' => 'AVAILABLE'
        );
        if ($ship_indicator) {
            $merchant_risk['SHIP_INDICATOR'] = $ship_indicator;
        }
        if ($delivery_time_frame) {
            $merchant_risk['DELIVERY_TIMEFRAME'] = $delivery_time_frame;
        }
        if ($delivery_email) {
            $merchant_risk['DELIVERY_EMAIL_ADDRESS'] = $delivery_email;
        }
        if ($reorder_items) {
            $merchant_risk['REORDER_ITEMS_IND'] = $reorder_items;
        }
        $acs['MERCHANT_RISK_IND'] = $merchant_risk;

        // Account info
        $account_info = array(
            'SUSPICIOUS_ACC_ACTIVITY' => 'NO_SUSPICIOUS'
        );
        if ($user_age) {
            $account_info['CH_ACC_AGE_IND'] = $user_age;
            $account_info['PAYMENT_ACC_IND'] = $user_age;
        }
        if ($user_info_age) {
            $account_info['CH_ACC_CHANGE_IND'] = $user_info_age;
        }
        if ($registered) {
            $account_info['CH_ACC_CHANGE'] = $registered;
            $account_info['CH_ACC_DATE'] = $registered;
            $account_info['PAYMENT_ACC_AGE'] = $registered;
        }
        if ($txn_activity_today) {
            $account_info['TXN_ACTIVITY_DAY'] = $txn_activity_today;
        }
        if ($txn_activity_year) {
            $account_info['TXN_ACTIVITY_YEAR'] = $txn_activity_year;
        }
        if ($txn_purchase_6) {
            $account_info['NB_PURCHASE_ACCOUNT'] = $txn_purchase_6;
        }
        if ($ship_name_indicator) {
            $account_info['SHIP_NAME_INDICATOR'] = $ship_name_indicator;
        }
        $acs['ACCOUNT_INFO'] = $account_info;

        $return = add_query_arg(array(
            'payment-confirmation' => 'cecabank',
            'payment-id' => $payment_id,
        ), get_permalink(give_get_option('success_page')));
        $cancel = give_get_failed_transaction_uri('?payment-id=' . $payment_id . '&error_message=error&is_recurring=true');

        // Create transaction
        $cecabank_client->setFormHiddens(array(
            'Num_operacion' => $payment_id,
            'Descripcion' => __('Pago del pedido ', 'wc-gateway-cecabank').$payment_id,
            'Importe' => give_maybe_sanitize_amount( give_get_meta( $payment_id, '_give_payment_total', true ) ),
            'URL_OK' => $return,
            'URL_NOK' => $cancel,
            'TipoMoneda' => $cecabank_client->getCurrencyCode($currency),
            'datos_acs_20' => base64_encode( str_replace( '[]', '{}', json_encode( $acs ) ) ),
			'Tipo_operacion': 'D',
			'Datos_operaciones': $data
        ));

        $parameter = $cecabank_client->hidden;
        $parameter['action'] = $cecabank_client->getPath();
        $parameter['cecabank-payment-pg'] = true;
        $parameter['URL_OK'] = urlencode($parameter['URL_OK']);
        $parameter['URL_NOK'] = urlencode($parameter['URL_NOK']);

        //send to payment page as params
        $payment_page = site_url() . "/cecabank_payment_pg";

        $payment_url = add_query_arg($parameter, $payment_page);
        $payment_page = '<script type="text/javascript">
            window.onload = function(){
                window.parent.location = "' . $payment_url . '";
              }
            </script>';

        echo $payment_page;
    }

	/**
	 * Creates subscription payment profiles and sets the IDs so they can be stored.
	 *
	 * @access      public
	 */
	public function create_payment_profiles() {
		// Record Subscription.
		$this->record_signup();

		$redirect_to_url      = ! empty( $this->purchase_data['post_data']['give-current-url'] ) ? $this->purchase_data['post_data']['give-current-url'] : site_url();
		$session_key          = $this->cecabank_get_session_key();
		$donation_id          = ! empty( $this->payment_id ) ? intval( $this->payment_id ) : false;
		$subscription_details = give_recurring_get_subscription_by( 'payment', $donation_id );
		$subscription_id      = ! empty( $subscription_details->id ) ? intval( $subscription_details->id ) : false;

		// Update session key to database for reference.
		give_update_meta( $donation_id, 'give_cecabank_unique_session_key', $session_key );

		$form_id            = give_get_payment_form_id( $donation_id );
		$form_name          = give_get_meta( $donation_id, '_give_payment_form_title', true );
		$currency           = give_get_currency( $form_id );
		$donation_details   = give_get_payment_by( 'id', $donation_id );

		$config = $this-> get_client_config($form_id);
        $cecabank_client = new Cecabank\Client($config);
		
	    // Recurring donations.
        $first_payment_date   = date_i18n( 'Ymd', strtotime( $subscription_details->created ) );
        $ongoing_payments     = apply_filters( 'cecabank_update_ongoing_payments_count', 9999 );
        $number_of_payments   = $subscription_details->bill_times > 0 ? $subscription_details->bill_times : $ongoing_payments;
        $frequency            = $this->cecabank_recurring_get_frequency( $subscription_details->frequency, $subscription_details->period );
		$data = $first_payment_date.sprintf("%10d", $number_of_payments).sprintf("%02d", $frequency);

		$this->process_regular_payment($cecabank_client, $donation_details, $donation_id, $currency, $data);

		give_die();
	}

	/**
	 * Gets interval length and interval unit for Authorize.net based on Give subscription period.
	 *
	 * @param  string $period
	 * @param  int    $frequency
	 *
	 * @access public
	 *
	 * @return string
	 */
	public static function cecabank_recurring_get_frequency( $frequency, $period ) {

		$interval_count = $frequency;

		switch ( $period ) {

			case 'year':
				$interval_count = 12 * $frequency;
				break;

			case 'quarter':
				$interval_count = 3 * $frequency;
				break;
		}

		return $interval_count;
	}

}

new Cecabank_Recurring();