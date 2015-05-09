<?php
/**
 * Advanced Ordernumbers generic helper class for WooCommerce
 * Reinhold Kainhofer, Open Tools, office@open-tools.net
 * @copyright (C) 2012-2015 - Reinhold Kainhofer
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
**/

if ( !defined( 'ABSPATH' ) ) { 
	die( 'Direct Access to ' . basename( __FILE__ ) . ' is not allowed.' );
}
if (!class_exists( 'OrdernumberHelper' )) 
	require_once (dirname(__FILE__) . '/library/ordernumber_helper.php');

class OrdernumberHelperWooCommerce extends OrdernumberHelper {
	public static $ordernumber_counter_prefix = '_ordernumber_counter_';

	function __construct() {
		parent::__construct();
		load_plugin_textdomain('opentools-ordernumbers', false, basename( dirname( __FILE__ ) ) . '/languages' );
		// WC-specific Defaults for the HTML tables
		$this->_styles['counter-table-class']  = "widefat";
		$this->_styles['variable-table-class'] = "widefat wc_input_table sortable";
		
	}

	static function getHelper() {
		static $helper = null;
		if (!$helper) {
			$helper = new OrdernumberHelperWooCommerce();
		}
		return $helper;
    }
	
	/**
	 * HELPER FUNCTIONS, WooCommerce-specific
	 */
	public function __($string) {
		$string = $this->readableString($string);
		return __($string, 'opentools-advanced-ordernumbers');
	}
	function urlPath($type, $file) {
		return plugins_url('library/' . $type . '/' . $file, __FILE__);
    }
    
    public function print_admin_styles() {
		wp_register_style('ordernumber-styles',  $this->urlPath('css', 'ordernumber.css'));
		wp_enqueue_style('ordernumber-styles');
	}
	
	public function print_admin_scripts() {
		wp_register_script( 'ordernumber-script', $this->urlPath('js', 'ordernumber.js',  __FILE__), array('jquery') );
		wp_enqueue_script( 'ordernumber-script');
		
		// Handle the translations:
		$localizations = array( 'ajax_url' => admin_url( 'admin-ajax.php' ) );
		
		$localizations['ORDERNUMBER_JS_JSONERROR'] = $this->__("Error reading response from server:");
		$localizations['ORDERNUMBER_JS_NOT_AUTHORIZED'] = $this->__("You are not authorized to modify order number counters.");
		$localizations['ORDERNUMBER_JS_NEWCOUNTER'] = $this->__("Please enter the format/name of the new counter:");
		$localizations['ORDERNUMBER_JS_ADD_FAILED'] = $this->__("Failed adding counter {0}");
		$localizations['ORDERNUMBER_JS_INVALID_COUNTERVALUE'] = $this->__("You entered an invalid value for the counter.\n\n");
		
		$localizations['ORDERNUMBER_JS_EDITCOUNTER'] = $this->__("{0}Please enter the new value for the counter '{1}' (current value: {2}):");
		$localizations['ORDERNUMBER_JS_MODIFY_FAILED'] = $this->__("Failed modifying counter {0}");
		$localizations['ORDERNUMBER_JS_DELETECOUNTER'] = $this->__("Really delete counter '{0}' with value '{1}'?");
		$localizations['ORDERNUMBER_JS_DELETE_FAILED'] = $this->__("Failed deleting counter {0}");

		// in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
		wp_localize_script( 'ordernumber-script', 'ajax_ordernumber', $localizations );
	}




 	function getAllCounters($type) {
		$counters = array();
		$pfxlen = strlen(self::$ordernumber_counter_prefix );
		foreach (wp_load_alloptions() as $name => $value) {
			if (substr($name, 0, $pfxlen) == self::$ordernumber_counter_prefix) {
				$parts = explode('-', substr($name, $pfxlen), 2);
				if (sizeof($parts)==1) {
					array_unshift($parts, 'ordernumber');
				}
				if ($parts[0]==$type) {
					$counters[] = array(
						'type'  => $parts[0],
						'name'  => $parts[1],
						'value' => $value,
					);
				}
			}
		}
		return $counters;
	}

    function getCounter($type, $format, $default=0) {
		return get_option (self::$ordernumber_counter_prefix.$type.'-'.$format, $default);
	}
    
	function addCounter($type, $format, $value) {
		return $this->setCounter($type, $format, $value);
	}

	function setCounter($type, $format, $value) {
		return update_option(self::$ordernumber_counter_prefix.$type.'-'.$format, $value);
	}

	function deleteCounter($type, $format) {
		return delete_option(self::$ordernumber_counter_prefix.$type.'-'.$format);
	}


}
