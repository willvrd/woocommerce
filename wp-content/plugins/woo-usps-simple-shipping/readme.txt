=== USPS Simple Shipping for Woocommerce ===
Contributors: SkyWorks85
Donate link:
Tags: USPS, USPS Shipping, Shipping rates, shipping method, shipping extension,calculator,shipping calculator, tracking, postage, Shipping, WooCommerce
Requires at least: 4.0
Tested up to: 4.6.1
Stable tag: 1.2.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The USPS Simple plugin calculates rates for domestic shipping dynamically using USPS API during cart/checkout.

== Description ==

The USPS Simple plugin adds a new delivery option to WooCommerce - US postal service. Each item is packed individually, then items' delivery prices are summed up. Regular-size items can be grouped by their weight. USPS API is used for the delivery price calculation. USPS Simple allows to calculate rates for domestic shipping only. WooCommerce currency must be set to US dollars and base country/region must be the USA.

= USPS Simple supports the following services: =
<ul>
<li>Priority Mail Express™</li>
<li>Priority Mail Express™, Hold for Pickup</li>
<li>Priority Mail Express™, Sunday/Holiday</li>
<li>Priority Mail®</li>
<li>Priority Mail®, Hold For Pickup</li>
<li>Priority Mail® Keys and IDs</li>
<li>Priority Mail® Regional Rate Box A</li>
<li>Priority Mail® Regional Rate Box A, Hold For Pickup</li>
<li>Priority Mail® Regional Rate Box B</li>
<li>Priority Mail® Regional Rate Box B, Hold For Pickup</li>
<li>First-Class Mail® Parcel</li>
<li>First-Class™ Postcard Stamped</li>
<li>First-Class™ Large Postcards</li>
<li>First-Class™ Keys and IDs</li>
<li>First-Class™ Package Service</li>
<li>First-Class™ Package Service, Hold For Pickup</li>
<li>First-Class Mail® Metered Letter</li>
<li>USPS Retail Ground™</li>
<li>Media Mail Parcel</li>
<li>Library Mail Parcel</li>
</ul>

== Installation ==

1. Upload the plugin folder to the '/wp-content/plugins/' directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Now you need configure the plugin: Enter your postcode and check option "Enable this shipping method". You can use default User ID or enter your.

== Screenshots ==

1. Configuration
2. Cart

== Changelog ==

= 1.2.6 =
* Compatible with woocommerce 2.6

= 1.2.5 =
* Fix First-Class Mail® Parcel price calculator.
* Added First-Class Mail® Large Envelope, Letter and Postcards.

= 1.2.4 =
* API Request updated

= 1.2.3 =
* Fix - Incorrect work of "Quote regular items by weight" with zero size items.

= 1.2.2 =
* Removed deprecated USPS services: 
  Priority Mail® Regional Rate Box C; 
  Priority Mail® Regional Rate Box C, Hold For Pickup;
* Added First-Class Mail® Metered Letter;  
* Rebranding of Standard Post as Retail Ground.

= 1.2.1 =
* Fix - warning message in cart.

= 1.2.0 =
* Added services:
  Priority Mail®, Hold For Pickup;
  Priority Mail® Regional Rate Box A, Hold For Pickup;
  Priority Mail® Regional Rate Box B, Hold For Pickup;
  Priority Mail® Regional Rate Box C, Hold For Pickup.

= 1.1.1 =
* Added mail class id

= 1.1.0 =
* Added grouping by weight.

= 1.0.1 =
* Fix - Standard Post™ really works.

= 1.0 =
* Supported services: Priority Mail Express®, Priority Mail®, First-Class Mail®, Standard Post™, Media Mail®, Library Mail