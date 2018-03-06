<?php
/*
Plugin Name: payments4G - 4Geeks Payments
Plugin URI: https://4geeks.io/payments
Description: 4Geeks Payments integration Woocommerce
Version: 2.0.18
*/

// Add payment method to woocommerce
add_action( 'plugins_loaded', 'gpayments_init', 0 );
function gpayments_init() {
    //if condition use to do nothin while WooCommerce is not installed
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
	include_once( 'public/gpayments-woocommerce-gateway.php' );
	// class add too WooCommerce
	add_filter( 'woocommerce_payment_gateways', 'add_gpayments_gateway' );
	function add_gpayments_gateway( $methods ) {
		$methods[] = 'WC_GPayments_Connection';
		return $methods;
	}
}

// Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'gpayments_gateway_action_links' );
function gpayments_gateway_action_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'wc-gpayments' ) . '</a>',
	);
	return array_merge( $plugin_links, $links );
}

// Load form styles
add_action('wp_enqueue_scripts', 'gpayments_scripts', 99 );
function gpayments_scripts() 
{
	if (function_exists('is_woocommerce') && is_checkout()) {
		wp_enqueue_style('style', plugin_dir_url( __FILE__ ) . 'public/css/style.min.css');
		wp_enqueue_style('load-fa', '//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css');
	}
}

?>
