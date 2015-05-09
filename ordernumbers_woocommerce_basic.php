<?php
/**
 * This is the actual ordernumber plugin class for WooCommerce.
 * Copyright (C) 2015 Reinhold Kainhofer, Open Tools
 * Author: Open Tools, Reinhold Kainhofer
 * Author URI: http://open-tools.net
 * License: GPL2+
*/
if ( ! defined( 'ABSPATH' ) ) { 
	exit; // Exit if accessed directly
}
require_once( plugin_dir_path( __FILE__ ) . '/ordernumber_helper_woocommerce.php');

class OpenToolsOrdernumbersBasic {
	public $ordernumber_meta = "_oton_number_";
	public $ordernumber_new_placeholder = "[New Order]";
	public $plugin_basename = '';
	
	protected $helper = null;
	protected $settings = array();
	protected $is_advanced = false;
	
	/**
	 * Construct the plugin object
	 */
	public function __construct($basename)
	{
		$this->helper = OrdernumberHelperWooCommerce::getHelper();
		$this->plugin_basename = $basename;
		$this->initializeBasicSettings();
		$this->initializeSettings();
		$this->initializeHooks();
	}
	
	/**
	 * Set up the functionality of the basic version of the plugin
	 */
	protected function initializeBasicSettings() {
		$this->helper->registerCallback('setupDateTimeReplacements',	array($this, 'setupDateTimeReplacements'));
		
		add_filter( 'plugin_row_meta', 									array( &$this, 'basic_ordernumber_plugin_row_meta' ), 30, 2 );
		add_filter( 'woocommerce_admin_field_opentools_ordernumbers_upgrade', array( &$this, 'admin_field_opentools_ordernumbers_upgrade') );
		$this->helper->setFlag('extract-counter-settings', false);
	}

	/**
	 * Install all neccessary filters and actions for this plugin
	 */
	protected function initializeHooks() {
		$helper = OrdernumberHelperWooCommerce::getHelper();
		// CONFIGURATION SCREENS
		add_filter( 'woocommerce_get_sections_checkout',				array( &$this, 'add_admin_section'));
		// The checkout settings page assumes all subpages are payment gateways, so we have to override this and manually pass our settings:
		add_action( 'woocommerce_settings_checkout',					array( &$this, 'settings_output' ) );
		add_action( 'woocommerce_settings_save_checkout',				array( &$this, 'settings_save' ) );
		add_action( 'woocommerce_admin_field_ordernumber_counters',		array( &$this, 'admin_field_counters' ) );
		// Add links to WordPress plugins page
		add_filter( 'plugin_action_links_'.$this->plugin_basename,		array( &$this, 'ordernumber_add_settings_link' ) );
		add_filter( 'plugin_row_meta', 									array( &$this, 'ordernumber_plugin_row_meta' ), 10, 2 );
		
		add_action( 'woocommerce_order_actions',						array( &$this, 'add_order_action_new_number' ) );
		add_action( 'woocommerce_order_action_assign_new_ordernumber',	array( &$this, 'order_action_assign_new_ordernumber' ) );

		add_action( 'admin_print_styles-woocommerce_page_wc-settings',	array( &$helper, 'print_admin_styles'));
		add_action( 'admin_print_styles-woocommerce_page_wc-settings',	array( &$this, 'print_admin_styles'));
		add_action( 'admin_print_scripts-woocommerce_page_wc-settings',	array( &$this, 'print_admin_scripts'));
		
		// AJAX counter modifications
		add_action( 'wp_ajax_setCounter',		array( &$this, 'counter_set_callback') );
		add_action( 'wp_ajax_addCounter',		array( &$this, 'counter_add_callback') );
		add_action( 'wp_ajax_deleteCounter',	array( &$this, 'counter_delete_callback') );

		// Add the ordernumber post meta to the search in the backend
		add_filter( 'woocommerce_shop_order_search_fields',		array( &$this, 'order_search_fields'));
		// Sort the order list in the backend by order number rather than ID, make sure this is called LAST so we modify the defaults passed as arguments
		add_filter( 'manage_edit-shop_order_sortable_columns',	array( &$this, 'modify_order_column_sortkey' ), 9999 );

		// When a new order is created, we immediately assign the order number:
		add_action( 'wp_insert_post',							array( &$this, 'check_assignNumber'), 10, 3);
		// The filter to actually return the order number for the given order
		add_filter ('woocommerce_order_number',					array( &$this, 'get_ordernumber'), 10, 2/*<= Also get the order object! */);
		
	}
	

	/**
	 * Setup all options and the corresponding settings UI to configure this plugin, using the WP settings API
	 */
	protected function initializeSettings() {
		// TODO: Call some virtual function for the upgrade NAG text...
		$this->settings = array_merge(
			$this->initializeSettingsGeneral(),
			$this->initializeSettingsOrderNumbers(),
			$this->initializeSettingsOther()
		);
	}

	protected function initializeSettingsGeneral() {
		// TODO: Add some kind of NAG screen to advertize the premium version
		$settings = array(
			array(
				'name' 		=> $this->helper->__( 'Upgrade to the ADVANCED VERSION of the OpenTools Ordernumber plugin'),
				'desc'		=> $this->helper->__( 'This basic version has limited functionality...'),
				'desc_tip'	=> true,
				'id' 		=> 'opentools_ordernumbers_upgrade',
				'type' 		=> 'opentools_ordernumbers_upgrade',
				'link'		=> 'http://open-tools.net/woocommerce/advanced-ordernumbers-for-woocommerce.html',
			),
		);
		return $settings;
	}
	protected function initializeSettingsOrderNumbers() {
		$settings = array(
			array(
				'name' 		=> $this->helper->__( 'Configure Order Numbers'),
				'desc'		=> $this->helper->__( 'Configure the format and the counters of the order numbers in WooCommerce. For help, check out the plugin\'s <a href="http://open-tools.net/documentation/advanced-order-numbers-for-woocommerce.html">documentation at OpenTools</a>.'),
				'type' 		=> 'title',
				'id' 		=> 'ordernumber_options'
			),

			array(
				'name' 		=> $this->helper->__( 'Customize Order Numbers'),
				'desc' 		=> $this->helper->__( 'Check to use custom order numbers rather than the default wordpress post ID.'),
				'id' 		=> 'customize_ordernumber',
				'type' 		=> 'checkbox',
				'default'	=> 'no'
			),
			array(
				'title'		=> $this->helper->__( 'Order number format'),
				'desc' 		=> $this->getNumberFormatSettingsLabel(),
				'desc_tip'	=> true,
				'id' 		=> 'ordernumber_format',
				'default'	=> '#',
				'type' 		=> 'text',
				'css'		=> 'width: 100%',
			),
			array(
				'title'		=> $this->helper->__( 'Use global counter'),
				'desc' 		=> $this->helper->__( 'A global counter never resets. Non-global counters run within each number format and reset whenever any variable changes.'),
				'id' 		=> 'ordernumber_global',
				'type' 		=> 'checkbox',
				'default'	=> 'no',
			),
			array(
				'name' 		=> $this->helper->__( 'All order number counters'),
				'desc'		=> $this->helper->__( 'View and modify the current counter values. The counter value is the value used for the previous number. All changes are immediately applied!'),
				'desc_tip'	=> true,
				'id' 		=> 'ordernumber_counters',
				'type' 		=> 'ordernumber_counters',
				'nrtype' 	=> 'ordernumber',
			),
			array( 'type' => 'sectionend', 'id' => 'ordernumber_options' )
		);
		add_option ('customize_ordernumber', 'no');
		add_option ('ordernumber_format',    "#");
		add_option ('ordernumber_global',    'no');
		return $settings;
	}
	/**
	 * Return the tooltip for the number format settings textinput (the two plugin versions have different features!)
	 */
	protected function getNumberFormatSettingsLabel() {
		return $this->helper->__( 'The format for the order numbers: You can choose any text string, where the counter is indicated by # and variables by [...]. In the free version of the plugin, only the [year] and [year2] variables are available. For example, a format "WC-[year2]-#" will create order numbers "WC-15-01", "WC-15-02", "WC-15-03", ...');
	}
	
	protected function initializeSettingsOther() {
		return array();
	}

	/**
	 * Add settings link to plugins page
	 */
	public function ordernumber_add_settings_link( $links ) {
		$link = '<a href="admin.php?page=wc-settings&tab=checkout&section=ordernumber">'. $this->helper->__( 'Settings' ) . '</a>';
		array_push( $links, $link );
		return $links;
	}
	public function ordernumber_plugin_row_meta( $links, $file ) {
		if ($file==$this->plugin_basename) {
			$links['docs'] = '<a href="' . esc_url(  'http://open-tools.net/documentation/advanced-order-numbers-for-woocommerce.html' ) . '" title="' . esc_attr( $this->helper->__( 'Plugin Documentation' ) ) . '">' . $this->helper->__( 'Plugin Documentation' ) . '</a>';
			$links['support'] = '<a href="' . esc_url( 'http://open-tools.net/support-forum/ordernumbers-for-woocommerce.html' ) . '" title="' . esc_attr( $this->helper->__( 'Support Forum' ) ) . '">' . $this->helper->__( 'Support Forum' ) . '</a>';
		}
		return (array)$links;
	}
	
	public function basic_ordernumber_plugin_row_meta( $links, $file ) {
		if ($file==$this->plugin_basename && !$this->is_advanced) {
			$links['advanced'] = '<a href="' . esc_url( 'http://open-tools.net/woocommerce/advanced-ordernumbers-for-woocommerce.html' ) . '" title="' . esc_attr( $this->helper->__('Purchase Advanced Version')) . '">' . $this->helper->__('Purchase Advanced Version') . '</a>';
		}
		return (array)$links;
	}
	
	/**
	 * Insert our own section in the checkout setting page. Rearrange the sections array to make sure our settings
	 * come second place, directly after the default page with the '' key and before all the payment gateways
	 */
	function add_admin_section($sections) {
		$newsections = array();
		foreach ($sections as $sec => $name ) {
			$newsections[$sec] = $name;
			if ($sec == '') {
				$newsections['ordernumber'] = $this->helper->__('Order Numbers');
			}
		}
		return $newsections;
	}
	
	public function settings_output() {
		global $current_section;
		if ($current_section == 'ordernumber') {
			$settings = $this->settings;
			WC_Admin_Settings::output_fields( $settings );
		}
	}

	public function settings_save() {
		global $current_section;
		if ($current_section == 'ordernumber') {
			$settings = $this->settings;
			WC_Admin_Settings::save_fields( $settings );
		}
	}
	
	/** 
	 * Print the JS for the counter values and counter variables tables to the page header in the WC backend admin setting page
	 */
	public function print_admin_scripts() {
		$this->helper->print_admin_scripts();
		wp_register_script( 'ordernumber-admin', plugins_url('assets/js/ordernumber-config.js', __FILE__), array('jquery'));
		wp_enqueue_script( 'ordernumber-admin');
	}
    public function print_admin_styles() {
		wp_register_style('ordernumber-wc-styles',  plugins_url('assets/css/ordernumber-config.css', __FILE__));
		wp_enqueue_style('ordernumber-wc-styles');
	}
	



	/**
	 * Render the Counter Values modification table
	 */
	public function admin_field_counters ($settings) {
		// Description handling
		$field_description = WC_Admin_Settings::get_field_description( $settings );
		extract( $field_description );
		?>

		
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $settings['id'] ); ?>"><?php echo esc_html( $settings['title'] ); ?></label>
				<?php echo $tooltip_html; ?>
			</th>
		    <td class="forminp forminp-<?php echo sanitize_title( $settings['nrtype'] ) ?>">
				<?php 
					$counters = $this->helper->getAllCounters($settings['nrtype']);
					echo $this->helper->counter_modification_create_table($settings['nrtype'], $counters);
				?>
			</td>
		</tr> 
		<?php
	}
	

	/**
	 * Render the UPGRADE TO ADVANCED VERSION nag table in the settings page
	 */
	public function admin_field_opentools_ordernumbers_upgrade ($settings) {
		// Description handling
		$field_description = WC_Admin_Settings::get_field_description( $settings );
		extract( $field_description );
		?>

		
		<tr valign="top">
			<td colspan="2">
				<div id="opentools-ordernumber-upgrade" class="postbox">
					<h3><?php echo esc_html($settings['title']); ?></h3>
					<div class="contents">
						<div class="logoleft"><a href="<?php echo esc_html($settings['link']); ?>"><img src="<?php echo plugins_url('assets/images/advlogo100.png', __FILE__); ?>"></a></div>
					<p>Advanced features not included in the free plugin include:</p>
					<ul>
						<li><b>Counter formatting</b>: initial value, counter steps, counter padding</li>
						<li><b>Many variables</b>: month, day, time, address fields, order properties (amount, # of articles, shipping methods), product categories and tags, etc.</li>
						<li>Multiple <b>concurrent counters</b>: Separate counters per country, per product category, etc.</li>
						<li><b>Different order number formats</b> for orders with specific properties (e.g. numbers "FREE-#" for free orders)</li>
						<li><b>Flexible counter resets</b>: Counter "resets" when any variable changes</li>
						<li>Customize <b>invoice numbers</b> (only for the <a href="https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/">"WooCommerce PDF Invoices and Package Slips"</a> plugin)</li>
						<li>...</li>
					</ul>
					<p>More information and purchase: <a class="button-primary" href="<?php echo esc_html($settings['link']); ?>" target="_blank">Get Support and advanced features</a></p>
					</div>
				</div>
			</td>
		</tr>
		<?php
	}
	
	
	/** 
	 * Hook to add the order numer post meta field to the searchable field 
	 * (so the admin can search for the order number in the backend)
	 */
	public function order_search_fields($fields) {
		$fields[] = $this->ordernumber_meta;
		return $fields;
	}
	
	/**
	 * Sort the order list's "Order" column by our post meta rather than by ID
	 */
	public function modify_order_column_sortkey($columns) {
		$columns['order_title'] = $this->ordernumber_meta;
		return $columns;
	}
	
	/**
	 * Add the "create new order number" action to the edit order page in the Backend
	 */
	public function add_order_action_new_number($actions) {
		$actions['assign_new_ordernumber'] = $this->helper->__('Assign a new order number');
		return $actions;
	}
	/**
	 * Handle the "Assign a new order number" action from the edit order page in the backend
	 */
	public function order_action_assign_new_ordernumber( $order ) {
		$number = $this->generateNumber($order->id, $order, 'ordernumber');
	}
		
	/** 
	 * Handle new posts created in the frontend. This action will be called for all posts, 
	 * not only for orders, so we need to check explicitly. Also, this function will be called
	 * for order updates, so we need to check the update argument, too.
	 */
	public function check_assignNumber($post_id, $post, $update) {
		// Is the post really an order?
		// Order numbers are only assigned to orders on creation, not when updating!
		if ($post->post_type != 'shop_order') {
			return;
		} else {
			// Handle new admin-created orders, where the address is entered later on!
			// Assign an order number:
			$number = $this->assign_new_ordernumber($post_id, $post, $update);
		}
	}

	/**
	 * AJAX Counter handling (simple loading/storing counters), storing them as options
	 */
	
	public function counter_delete_callback() {
		$json = $this->helper->ajax_counter_delete($_POST['nrtype'], $_POST['counter']);
		wp_send_json($json);
	}

	public function counter_add_callback () {
		$json = $this->helper->ajax_counter_add($_POST['nrtype'], $_POST['counter'], isset($_POST['value'])?$_POST['value']:"0");
		wp_send_json($json);
	}
	
	public function counter_set_callback () {
		$json = $this->helper->ajax_counter_set($_POST['nrtype'], $_POST['counter'], $_POST['value']);
		wp_send_json($json);
	}
	
	
	
	/** ***********************************************************
	 * 
	 *  REPLACEMENT FUNCTIONS
	 *
	 **************************************************************/
	
	/* Restrict date variables to years */
	public function setupDateTimeReplacements (&$reps, $details, $nrtype) {
		$utime = microtime(true);
		$reps["[year]"] = date ("Y", $utime);
		$reps["[year2]"] = date ("y", $utime);
	}

	function generateNumber($orderid, $order, $type='ordernumber') {
		if (get_option('customize_'.$type, 'no')!='no') {
			$fmt     = get_option ($type.'_format',  "#");
			$ctrsettings = array(
				"${type}_format"  => '',
				"${type}_counter" => '',
				"${type}_global"  => get_option ($type.'_global',  'no'),
				"${type}_padding" => 1,
				"${type}_step"    => 1,
				"${type}_start"   => 1,
			);
			$customvars = get_option ('ordernumber_variables',   array());

			$number = $this->helper->createNumber ($fmt, $type, $order, $customvars, $ctrsettings);
			update_post_meta( $orderid, $this->ordernumber_meta.$type, $number );
			return $number;
		} else {
			return $orderid;
		}
	}
	
	/** 
	 * The hook to assign a customized order number (unless the order already has one assigned)
	 */
	function assign_new_ordernumber($orderid, $order, $update=true) {
		if ((!$update) /*&& ($order->post_status == 'auto-draft')*/) {
			// New order => assign placeholder, which will later be overwritten the real order number
			update_post_meta( $orderid, $this->ordernumber_meta.'ordernumber', $this->ordernumber_new_placeholder );
		}
		// If we do not have an order (yet), we cannot proceed. But we probably have created the 
		// ordernumber placeholder for that post, so this function has done its job and we can return
		if (!$order instanceof WC_Order) {
			return;
		}
		$number = get_post_meta( $orderid, $this->ordernumber_meta.'ordernumber', 'true');
		if ($number == $this->ordernumber_new_placeholder && $order->post_status != 'auto-draft') {
			$number = $this->generateNumber($orderid, $order, 'ordernumber');
			// Assign a new number
		}
		return $number;
	}

	function get_or_create_number($orderid, $order, $type = 'ordernumber') {
		$stored_number = get_post_meta( $orderid, $this->ordernumber_meta.$type, true);
		if (!empty($stored_number)) {
			return $stored_number;
		} else {
			return $this->generateNumber($orderid, $order, $type);
		}
	}
	
	/** 
	 * The generic function to retrieve a particular number
	 */
	function get_number($orderid, $order, $type = 'ordernumber') {
		$stored_number = get_post_meta( $orderid, $this->ordernumber_meta.$type, 'true');
		if ($stored_number == $this->ordernumber_new_placeholder) {
			// Check whether the order was now really created => create order number now
			return $this->assign_new_ordernumber($orderid, $order, $type);
		} elseif (!empty($stored_number)) {
			// Order number already exists => simply return it
			return $stored_number;
		} else {
			// No order number was created for this order, so simply use the orderid as default.
			return $orderid;
		}
	}
	
	/**
	 * Callback function for Woocommerce to retrieve the ordernumber for an order
	 * The hook to customize order numbers (requests the order number from the database; 
	 * creates a new ordernumber if no entry exists in the database)
	 */
	function get_ordernumber($orderid, $order) {
		return $this->get_number($orderid, $order, 'ordernumber');
	}

}
