=== Metal Price Ticker ===
Contributors: TarekNabil
Donate link: https://tareknabil.net
Tags: metal price, ticker, elementor, wordpress plugin
Requires at least: 5.0
Tested up to: 6.6.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Metal Price Ticker is a wordpress plugin and Elementor add-on to display metal prices ticker.

== Description ==

Metal Price Ticker is a WordPress plugin that allows you to display real-time metal prices on your website. It includes an Elementor widget for easy integration into your Elementor-powered pages.

== Installation ==

1. Upload the `metal-price-ticker` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to 'Settings' -> 'Metal Price Ticker' to configure the plugin.

== Frequently Asked Questions ==

= What is the new calculator shortcode? =

The new calculator shortcode allows your clients to calculate metal prices directly on your website. You can use the shortcode [mpt_metal_price_calculator] to add a calculator to any page or post. This feature provides an interactive way for users to determine the value of metals based on the current prices and their specified quantities.

= How do I add the ticker to my page? =

You can add the ticker to your page using the Elementor widget provided by the plugin.
You can also use the shortcode anywhere at your website.
Example:
[mpt_metal_price metal="XAU" request="ask" currency="SAR" karats="24"  unit="gram"]

= Can I customize the update interval? =

Yes, you can customize the update interval from the plugin settings page.

== Screenshots ==

1. Screenshot of the settings page.
2. Screenshot of the ticker in action.

== Changelog ==


= 1.0.1 =
* Added QAR currency support.
* Added units Gram, Kilogram and ounce to ticker shortcode.
* Added custom fees functionality.
* Added calculator shortcode.
* Added conversion rates and fees to plugin settings for customization.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==
 
= 1.0.0 =
* Initial release.

== shortcode usage ==

[ mpt_metal_price
    metal   = "XAU" // supports: ('XAU', 'XAG', 'XPT', 'XPD')
    request = "ask" // supports: ('ask', 'bid', 'name', 'bid_time')
    currency= "USD" // supports: ('USD', 'AED', 'SAR') // Not used if you are requesting 'name' or 'bid_time'
    karats  = "24"  // supports: ('24', '22', '18', '14', '10') // only works for Gold
    unit    = "ounce" // supports: ('ounce', 'gram', 'kilogram')
]
== License ==

This plugin is licensed under the GPLv2 or later. For more information, see http://www.gnu.org/licenses/gpl-2.0.html.