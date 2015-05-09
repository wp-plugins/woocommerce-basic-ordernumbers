=== WooCommerce Basic Ordernumbers ===
Contributors: opentools
Tags: WooCommerce, Order numbers, orders
Requires at least: 4.0
Tested up to: 4.1.1
Stable tag: trunk
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl.html

Customize order numbers for WooCommerce. The order numbers can contain arbitrary text, a running counter and the current year.


== Description ==
The most flexible and complete solution for your WooCommerce webshop to customize your order numbers!

By default, WooCommerce uses the WordPress post ID of the order, which result in gaps between the order numbers. With this plugin you can configure the order numbers to have consecutive counters. Furthermore, the order number can contain the year, and the counter can be configured to reset each year.

The number format is a simple string, where # indicates the counter and [year] or [year2] indicate the year. 
To get order numbers like "WC-2015-1", "WC-2015-2", etc., simply set the format to "WC-[year]-#".

The plugin comes in two flavors:

*   This **free basic version**, which provides **sequential numbers** and allows the **year in the order number**
*   The **paid advanced version**, with lots of **additional features**:
	* Counter formatting: initial value, counter increments, number padding
	* Lots of variables to be used in the formats
		- date/time: year, month, day, hour, etc.
		- address: customer country, zip, name, etc.
		- order-specific: Number of articles, products, order total etc.
		- product categories, shipping method
	* Custom variable definitions (with conditions on available variables)
	* Multiple concurrent counters (e.g. numbering per country, per day, per ZIP, ...)
	* Different order numbers for free orders (e.g. "FREE-01" for free orders)
	* Different number format for e.g. certain IP addresses (for testing)
	* Different number format depending on products, product categories, shipping classes
	* Customize invoice numbers (only for the "WooCommerce PDF Invoices and Package Slips" plugin)

For the full documentation of both the basic and the advanced ordernumbers plugin for WooCommerce, see:
http://open-tools.net/documentation/advanced-order-numbers-for-woocommerce.html



== Installation ==

1. To install the plugin, either:
	1. use WordPress' plugin manager to find it in the WordPress plugin directory and directly install it from the WP plugin manager, or
	1. use WordPress' plugin manager to upload the plugin's zip file.
1. After installation, activate the plugin through the 'Plugins' menu in WordPress
1. Enable the plugin's functionality in the WooCommerce settings (tab "Checkout" -> "Order numbers")



== Frequently Asked Questions ==

= How can I create nice order numbers for existing orders? =

This plugin is intended for future orders. You can, however, create order numbers for existing orders in the order view in the WordPress admin section. In the top right "Order Actions" box select "Assign a new order number" and click "Save Order". Notice, however, that this will create an order number as if the order was created at that very moment.

= What about invoice numbers? =

The Advanced Ordernumbers for WooCommerce plugin supports some invoicing plugins. This functionality is not available in the free version, though.


== Screenshots ==

1. 

== Changelog ==

= 1.0 =
* Initial release

== Upgrade Notice ==

To install the Advanced Ordernumbers for WooCommerce package, proceed as described in the Installation section.
No upgrades yet. 
