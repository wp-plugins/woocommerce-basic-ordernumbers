<?php
/**
 * Plugin Name: WooCommerce Basic Ordernumbers
 * Plugin URI: http://open-tools.net/woocommerce/advanced-ordernumbers-for-woocommerce.html
 * Description: Lets the user freely configure the order numbers in WooCommerce.
 * Version: 1.0
 * Author: Open Tools
 * Author URI: http://open-tools.net
 * Text Domain: woocommerce-advanced-ordernumbers
 * License: GPL2+
 WC requires at least: 2.2
WC tested up to: 2.3
*/

if ( ! defined( 'ABSPATH' ) ) { 
	exit; // Exit if accessed directly
}
/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	if (file_exists(plugin_dir_path( __FILE__ ) . '/ordernumbers_woocommerce_basic.php') && !class_exists("OpenToolsOrdernumbersBasic") ) {
		require_once( plugin_dir_path( __FILE__ ) . '/ordernumbers_woocommerce_basic.php');
	}

	function ordernumbers_print_basic_admin_notice() { 
		deactivate_plugins( plugin_basename( __FILE__ ) );
		?>
		<div class="error">
			<p><?php _e( 'The <b>OpenTools Advanced Ordernumbers</b> plugin is <b>installed</b> and activated, the <b>basic ordernumber plugin</b> with similar, but limited functionality will be <b>deactivated</b>.', 'opentools-advanced-ordernumbers' ); ?></p>
		</div>
		<?php
	}
	function ordernumbers_check_deactivate() {
		if (defined ('OPENTOOLS_ADVANCED_ORDERNUMBERS')) {
			add_action( 'admin_notices', 'ordernumbers_print_basic_admin_notice');
		}
	}
	add_action( 'plugins_loaded', 'ordernumbers_check_deactivate', 99 );
	
	// instantiate the plugin class
	if (class_exists("OpenToolsOrdernumbersBasic")) {
		$ordernumber_plugin = new OpenToolsOrdernumbersBasic(plugin_basename(__FILE__));
	}
 
}
