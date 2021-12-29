<?php

/**
 * 
 * 1. Init payment: process_payment function
 *    - create_payment function
 *    - get_cecabank function
 *    - redirect to cecabank_payment_pg page
 * 
 * 2. Verify payment: return_listener function
 *    - verify all the payment & order data
 *    - publish_payment function
 *    - redirect
 * 
 */


if (!defined('ABSPATH')) {
    exit;
}

$autoloader_param = __DIR__ . '/../lib/Cecabank/Client.php';
try {
    require_once $autoloader_param;
} catch (\Exception $e) {
    throw new \Exception('Error en el plugin de Cecabank al cargar la librería.');
}

class Give_Cecabank_Gateway
{
    private static $instance;

    private function __construct()
    {
        add_action('init', array($this, 'return_listener'));
        add_action('give_gateway_cecabank', array($this, 'process_payment'));
        add_action('give_cecabank_cc_form', array($this, 'give_cecabank_cc_form'));
        add_filter('give_enabled_payment_gateways', array($this, 'give_filter_cecabank_gateway'), 10, 2);
        add_filter('give_payment_confirm_cecabank', array($this, 'give_cecabank_success_page_content'));
        add_filter('give_payment_page', array($this, 'give_cecabank_payment_page_content'));
    }

    public static function get_instance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function give_filter_cecabank_gateway($gateway_list, $form_id)
    {
        if ((false === strpos($_SERVER['REQUEST_URI'], '/wp-admin/post-new.php?post_type=give_forms'))
            && $form_id
            && !give_is_setting_enabled(give_get_meta($form_id, 'cecabank_customize_cecabank_donations', true, 'enabled'), array('enabled'))
        ) {
            unset($gateway_list['cecabank']);
        }
        return $gateway_list;
    }

    private function create_payment($purchase_data)
    {
        $form_id = intval($purchase_data['post_data']['give-form-id']);
        $price_id = isset($purchase_data['post_data']['give-price-id']) ? $purchase_data['post_data']['give-price-id'] : '';

        // Collect payment data.
        $insert_payment_data = array(
            'price' => $purchase_data['price'],
            'give_form_title' => $purchase_data['post_data']['give-form-title'],
            'give_form_id' => $form_id,
            'give_price_id' => $price_id,
            'date' => $purchase_data['date'],
            'user_email' => $purchase_data['user_email'],
            'purchase_key' => $purchase_data['purchase_key'],
            'currency' => give_get_currency($form_id, $purchase_data),
            'user_info' => $purchase_data['user_info'],
            'status' => 'pending',
            'gateway' => 'cecabank',
        );

        /**
         * Filter the payment params.
         *
         * @since 0.0.1
         *
         * @param array $insert_payment_data
         */
        $insert_payment_data = apply_filters('give_create_payment', $insert_payment_data);

        // Record the pending payment.
        return give_insert_payment($insert_payment_data);
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
                'versionMod' => 'G-0.0.1'
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
                'versionMod' => 'G-0.0.1'
            );
        }
    }

    private function get_cecabank($purchase_data)
    {
        //ob_start();
        $form_id = intval($purchase_data['post_data']['give-form-id']);

        $custom_donation = give_get_meta($form_id, 'cecabank_customize_cecabank_donations', true, 'enabled');
        $status = give_is_setting_enabled($custom_donation, 'enabled');

        if ($status) {
            return $this->get_client_config($form_id);
        }
        return $this->get_client_config(null);
    }

    public function process_regular_payment( $cecabank_client, $purchase_data, $payment_id ) {

        $user_age = 'JUST_CHANGED';
        $user_info_age = 'JUST_CHANGED';
        $registered = '';
        $txn_activity_today = '0';
        $txn_activity_year = '0';
        $txn_purchase_6 = '0';
        $ship_name_indicator = 'IDENTICAL';

        $user = $purchase_data['user_info'];
        $user_id = $user['id'];

        $name = $user['first_name'].' '.$user['last_name'];
        $email = $purchase_data['user_email'];
        $ip = '';
        $city = $user['address']['city'];
        $country = $user['address']['country'];
        $line1 = $user['address']['line1'];
        $line2 = $user['address']['line2'];
        $postal_code = $user['address']['zip'];
        $state = $user['address']['state'];
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
        $cancel = give_get_failed_transaction_uri('?payment-id=' . $payment_id . '&error_message=error&is_recurring=false');
        // $cancel = str_replace("_wpnonce", "_wponce", $failedUrl);

        // Create transaction
        $cecabank_client->setFormHiddens(array(
            'Num_operacion' => $payment_id,
            'Descripcion' => __('Pago del pedido ', 'wc-gateway-cecabank').$payment_id,
            'Importe' => $purchase_data['price'],
            'URL_OK' => $return,
            'URL_NOK' => $cancel,
            'TipoMoneda' => $cecabank_client->getCurrencyCode(give_get_currency(intval($purchase_data['post_data']['give-form-id']), $purchase_data)),
            'datos_acs_20' => base64_encode( str_replace( '[]', '{}', json_encode( $acs ) ) )
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

    public function process_payment($purchase_data)
    {
        $get_vars = give_clean($_GET);

        // Validate nonce.
        give_validate_nonce($purchase_data['gateway_nonce'], 'give-gateway');

        $config = $this-> get_client_config(null);

        $cecabank_client = new Cecabank\Client($config);

        $payment_id = $this->create_payment($purchase_data);

        // Check payment.
        if (empty($payment_id)) {
            // Record the error.
            give_record_gateway_error(__('Payment Error', 'give_cecabank'), sprintf( /* translators: %s: payment data */
                __('Fallo en la creación del pago. Información: %s', 'give_cecabank'),
                json_encode($purchase_data)
            ), $payment_id);
            // Problems? Send back.
            give_send_back_to_checkout();
        }

        $result = $this->process_regular_payment( $cecabank_client, $purchase_data, $payment_id );

        exit;
    }

    public function refund($payment_id, $reference, $form_id, $amount) {
        $config = $this-> get_client_config(null);

        $cecabank_client = new Cecabank\Client($config);

        $refund_data = array(
            'Num_operacion' => $payment_id,
            'Referencia' => $reference,
            'TipoMoneda' => $cecabank_client->getCurrencyCode(give_get_currency(intval($form_id))),
            'Importe' => $amount
        );

        return $cecabank_client->refund($refund_data);
    }

    public function give_cecabank_cc_form($form_id)
    {
        // ob_start();
        $post_cecabank_customize_option = give_get_meta($form_id, 'cecabank_customize_cecabank_donations', true, 'enabled');

        // Output Address fields if global option is on and user hasn't elected to customize this form's offline donation options
        if (
            (give_is_setting_enabled($post_cecabank_customize_option, 'enabled'))
        ) {
            give_default_cc_address_fields($form_id);
            return true;
        }

        return false;
        // echo ob_get_clean();
    }

    private function publish_payment($payment_id, $transaction_id)
    {
        if ('publish' !== get_post_status($payment_id)) {
            give_set_payment_transaction_id($payment_id, $transaction_id);
            give_update_payment_status($payment_id, 'publish');
            give_insert_payment_note($payment_id, "Payment ID: {$payment_id}, Transaction ID: {$transaction_id}");
        }
    }

    public function return_listener()
    {
        if (empty($_POST['Num_operacion'])) {
            return;
        }
        $config = $this-> get_client_config(null);

        $cecabank_client = new Cecabank\Client($config);
        $is_recurring = false;

        try {
            $cecabank_client->checkTransaction($_POST);
        } catch (\Exception $e) {
            give_record_gateway_error(__('Cecabank Error', 'give'), json_encode($_POST), $payment_id);
            give_set_payment_transaction_id($payment_id, $_POST['Referencia']);
            give_update_payment_status($payment_id, 'failed');
            give_insert_payment_note($payment_id, __('Transaction ID: ' . $_POST['Referencia'] . ', error', 'give'));
            $failedUrl = give_get_failed_transaction_uri('?payment-id=' . $payment_id . '&error_message=error&is_recurring=' . $is_recurring);
            $failedUrl = str_replace("_wpnonce", "_wponce", $failedUrl);
            wp_redirect($failedUrl);
            exit;
        }

        $payment_id = $_POST['Num_operacion'];
        $transaction_id = $_POST['Referencia'];
        $payment_amount = give_donation_amount($payment_id);

        $this->publish_payment($payment_id, $transaction_id);

        die($cecabank_client->successCode());
    }

    public function give_cecabank_success_page_content($content)
    {
        if (!isset($_GET['payment-id']) && !give_get_purchase_session()) {
            return $content;
        }

        $payment_id = isset($_GET['payment-id']) ? absint($_GET['payment-id']) : false;

        if (!$payment_id) {
            $session    = give_get_purchase_session();
            $payment_id = give_get_donation_id_by_key($session['purchase_key']);
        }

        $payment = get_post($payment_id);
        if ($payment && 'pending' === $payment->post_status) {

            // Payment is still pending so show processing indicator to fix the race condition.
            ob_start();

            give_get_template_part('payment', 'processing');

            $content = ob_get_clean();
        }

        return $content;
    }
}
Give_Cecabank_Gateway::get_instance();
