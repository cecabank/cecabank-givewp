<?php

/**
 * GiveWp Cecabank Gateway Activation
 *
 * @package     Cecabank for GiveWP
 * @since       1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugins row action links
 *
 * @since 0.0.1
 *
 * @param array $actions An array of plugin action links.
 *
 * @return array An array of updated action links.
 */
function give_cecabank_plugin_action_links($actions)
{
    $new_actions = array(
        'settings' => sprintf(
            '<a href="%1$s">%2$s</a>', admin_url('edit.php?post_type=give_forms&page=give-settings&tab=gateways&section=cecabank'), esc_html__('Settings', 'give_cecabank')
        ),
    );
    return array_merge($new_actions, $actions);
}
add_filter('plugin_action_links_' . GIVE_PLUGIN_BASENAME, 'give_cecabank_plugin_action_links');

/**
 * This function will display field to opt for refund in Cecabank.
 *
 * @param int $donation_id Donation ID.
 *
 * @since 0.0.1
 *
 * @return void
 */
function give_cecabank_opt_refund( $donation_id ) {

	$processed_gateway = Give()->payment_meta->get_meta( $donation_id, '_give_payment_gateway', true );

	// Bail out, if the donation is not processed with Cecabank payment gateway.
	if ( ! in_array( $processed_gateway, ['cecabank'], true ) ) {
		return;
	}
    $payment = get_post($donation_id);
    if ($payment->post_status !== 'publish') {
        return;
    }

	?>
    
	<div id="give-cecabank-opt-refund-wrap" class="give-cecabank-opt-refund give-admin-box-inside">
		<p>
			<input type="checkbox" id="give-cecabank-opt-refund" name="give_cecabank_opt_refund" value="1"/>
			<label for="give-cecabank-opt-refund">
				<?php esc_html_e( '¿Devolver con Cecabank?', 'give_cecabank' ); ?>
			</label>
		</p>
	</div>

	<?php
}

add_action( 'give_view_donation_details_totals_after', 'give_cecabank_opt_refund', 11, 1 );

/**
* Process refund in Cecabank.
*
* @since  0.0.1
* @access public
*
* @param int    $donation_id Donation ID.
* @param string $new_status  New Donation Status.
* @param string $old_status  Old Donation Status.
*
* @return void
*/
function give_cecabank_process_refund( $donation_id, $new_status, $old_status ) {

   $cecabank_opt_refund_value = ! empty( $_POST['give_cecabank_opt_refund'] ) ? give_clean( $_POST['give_cecabank_opt_refund'] ) : '';
   $can_process_refund      = ! empty( $cecabank_opt_refund_value ) ? $cecabank_opt_refund_value : false;

   // Only move forward if refund requested.
   if ( ! $can_process_refund ) {
       return;
   }

   // Verify statuses.
   $should_process_refund = 'publish' !== $old_status ? false : true;

   if ( false === $should_process_refund ) {
       return;
   }

   if ( 'refunded' !== $new_status ) {
       return;
   }

   $reference = give_get_payment_transaction_id( $donation_id );

   // Bail if no reference was found.
   if ( empty( $reference ) ) {
       return;
   }

   // Get Form ID.
   $form_id = give_get_payment_form_id( $donation_id );
   $payment = get_post($donation_id);
   die($donation_id);

   $gateway = Give_Cecabank_Gateway::get_instance();
   if ($gateway->refund($payment->ID, $reference, $form_id, floatval(Give()->payment_meta->get_meta( $donation_id, '_give_payment_total', true)))) {
        give_insert_payment_note(
            $donation_id,
            esc_html__( 'Devuelta', 'give_cecabank' )
        );
   } else {
       // Refund issue occurred.
       $log_message  = __( 'Ha fallado la devolución.', 'give_cecabank' );
       // Log it with DB.
       give_record_gateway_error( __( 'Cecabank Error', 'give_cecabank' ), $log_message );
   }

   do_action( 'give_cecabank_donation_refunded', $donation_id );

}

add_action( 'give_update_payment_status', 'give_cecabank_process_refund', 400, 3 );
