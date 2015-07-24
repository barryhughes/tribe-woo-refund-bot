<?php
/**
 * Plugin Name: WooCommerce Refund Bot – Tribe Integration
 * Plugin URI:  http://tri.be
 * Description: Send WooCommerce refund data to a Slack channel via Slack's "incoming webhook" feature.
 * Author:      George Gecewicz
 * Author URI:  http://tri.be
 * Version:     1.0
 * License:     GPL v2.0 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

add_action( 'woocommerce_order_status_changed', function( $order_id, $old_status, $new_status ) {

	if ( 0 == $order_id || 'refunded' !== $new_status ) return;

	$refunds_obj = get_children([
		'post_parent' => $order_id,
		'post_type'   => 'shop_order_refund',
		'numberposts' => -1,
		'post_status' => 'any'
	], OBJECT );

	$refunds = array_values( $refunds_obj );

	$order_meta = get_post_meta( $order_id );

	$payload = [
		'username'    => 'Tribe Refunds',
		'icon_emoji'  => ':tec_logo:',
		'channel'     => '#refunds',
		'text'        => '',
		'attachments' => [
			[
				'fallback'   => '',
				'color'      => '#A46497',
				'pretext'    => '',
				'title'      => sprintf( '$%s refunded for order #%s',  get_post_meta( $refunds[0]->ID, '_refund_amount', true ), $order_id ),
				'title_link' => get_edit_post_link( $order_id ),
				'text'       => sprintf( '%s', $refunds[0]->post_excerpt ),
				'fields'     => [
					[
						'title' => 'Customer',
						'value' => sprintf( '%s %s', ucfirst( $order_meta['_billing_first_name'][0] ), ucfirst( $order_meta['_billing_last_name'][0] ) ),
						'short' => true
					],
					[
						'title' => 'Refunded By',
						'value' => ucfirst( get_the_author_meta( 'user_nicename', $refunds[0]->post_author ) ),
						'short' => true
					]
				]
			]
		]
	];

	$json_data = json_encode( $payload );

	$response = wp_remote_post( 'https://hooks.slack.com/services/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', [
		'method'      => 'POST',
		'timeout'     => 45,
		'redirection' => 5,
		'httpversion' => '1.0',
		'blocking'    => true,
		'headers'     => [],
		'cookies'     => [],
		'body'        => $json_data
	]);

}, 10, 3 );