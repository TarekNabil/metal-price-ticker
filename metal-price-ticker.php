<?php
/**
 * Plugin Name: Metal Price Ticker
 * Description: Plugin and Elementor add-on to display Metal prices ticker.
 * Version: 1.0.0
 * Author: Tarek Nabil
 * Author URI: https://tareknabil.net
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: metal-price-ticker
 */

// Enqueue CSS and JS
add_action('wp_enqueue_scripts', 'mpt_enqueue_assets');
function mpt_enqueue_assets() {
    wp_enqueue_style('mpt-styles', plugin_dir_url(__FILE__) . 'assets/css/style.css');
    wp_enqueue_script('mpt-scripts', plugin_dir_url(__FILE__) . 'assets/js/metal-price-ticker.js', array('jquery'), rand(), true);
    wp_localize_script('mpt-scripts', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'ajax_nonce' => wp_create_nonce('custom_nonce'),
        'interval' => 10, // seconds
        'images_path' => plugin_dir_url(__FILE__) . 'assets/images/',
    ));
    
}


// Add settings page
add_action('admin_menu', 'mpt_add_settings_page');
function mpt_add_settings_page() {
    add_options_page(
        'Metal Price Ticker Settings', // Page title
        'Metal Price Ticker',          // Menu title
        'manage_options',             // Capability
        'mpt-settings',               // Menu slug
        'mpt_render_settings_page'    // Callback function
    );
}

// Render settings page
function mpt_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Metal Price Ticker Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('mpt_settings_group');
            do_settings_sections('mpt-settings');
            submit_button();
            
            ?>
        </form>
        <!-- display metal prices  -->
        <div id="mpt-metal-prices"></div>
        <?php
        // Display the metal prices
        $metal_prices = mpt_get_all_metal_prices();
        if(is_array($metal_prices)) {
            echo ' Metal Prices Example : ' . $metal_prices['XAU']['name'] . ' Ask: ' . $metal_prices['XAU']['ask'] . ' Bid: ' . $metal_prices['XAU']['bid'] . ' Bid Time: ' . $metal_prices['XAU']['bid_time'];
        }
        
        ?>

        
    </div>
    <?php
}

// Register settings
add_action('admin_init', 'mpt_register_settings');
function mpt_register_settings() {
    register_setting('mpt_settings_group', 'mpt_fastmarkets_user');
    register_setting('mpt_settings_group', 'mpt_fastmarkets_password');

    add_settings_section(
        'mpt_Fastmarkets_settings_section',       // ID
        'Fastmarkets API Settings',   // Title
        null,                         // Callback
        'mpt-settings'                // Page
    );

    add_settings_field(
        'mpt_fastmarkets_user',       // ID
        'API User',                   // Title
        'mpt_fastmarkets_user_callback', // Callback
        'mpt-settings',               // Page
        'mpt_Fastmarkets_settings_section'        // Section
    );

    add_settings_field(
        'mpt_fastmarkets_password',   // ID
        'API Password',               // Title
        'mpt_fastmarkets_password_callback', // Callback
        'mpt-settings',               // Page
        'mpt_Fastmarkets_settings_section'        // Section
    );
    // add setting field for update interval and set default value to be 10
    register_setting('mpt_settings_group', 'mpt_update_interval', array(
        
        'default' => 10,
    ));
    // add setting section for updatae interval

    add_settings_section(
        'mpt_update_interval_section',       // ID
        'Update Interval',   // Title
        null,                         // Callback
        'mpt-settings'                // Page
    );
    


    add_settings_field(
        'mpt_update_interval',       // ID
        'Update Interval',                   // Title
        'mpt_update_interval_callback', // Callback
        'mpt-settings',               // Page
        'mpt_update_interval_section'        // Section
    );
}

// Callback functions for settings fields
function mpt_fastmarkets_user_callback() {
    $user = get_option('mpt_fastmarkets_user');
    echo '<input type="text" name="mpt_fastmarkets_user" value="' . esc_attr($user) . '" />';
}

function mpt_fastmarkets_password_callback() {
    $password = get_option('mpt_fastmarkets_password');
    echo '<input type="password" name="mpt_fastmarkets_password" value="' . esc_attr($password) . '" />';
}
// callback function for update interval and set default value to be 10

function mpt_update_interval_callback() {

    $interval = get_option('mpt_update_interval', 10);
    echo '<input type="number" name="mpt_update_interval" value="' . esc_attr($interval) . '" />';
}

// Register Elementor widget
add_action('elementor/widgets/widgets_registered', 'mpt_register_elementor_widget');
function mpt_register_elementor_widget() {
    require_once plugin_dir_path(__FILE__) . 'widgets/class-mpt-metal-price-ticker-widget.php';
    \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \MPT_Metal_Price_Ticker_Widget());
}

// return the metal prices
function mpt_get_all_metal_prices() {

    //get prices from options if exists
    $mpt_metal_prices = get_option('mpt_metal_prices');
    
    if (!$mpt_metal_prices) {
        //get prices from fastmarkets
        error_log('fetch');
        $mpt_metal_prices = mpt_fetch_metal_prices();
    }
    //check last update
    $mpt_last_update = get_option('mpt_last_update');
    if (time() - $mpt_last_update > 10) {//TODO: change to setting option
        error_log('update');
        $mpt_metal_prices = mpt_fetch_metal_prices();
    }
    
    return $mpt_metal_prices;
    
}
function mpt_fetch_metal_prices(){
    // return $mpt_metal_prices;
    $user = get_option('mpt_fastmarkets_user');
    $password = get_option('mpt_fastmarkets_password');
    $url = "https://pro.fastmarkets.com/feeds/?usr=$user&pwd=$password";
    $response = wp_remote_get($url);
    // error_log(print_r($response, true));
    if (is_wp_error($response)) {
        echo 'Failed to fetch data.';
        return;
    }

    $data = wp_remote_retrieve_body($response);

    // Parse the XML response
    $xml = simplexml_load_string($data);
    
    if ($xml === false) {
        // error_log($data);
        echo 'Failed to parse XML.';
        return;
    }

    $metals = array('XAU', 'XAG', 'XPT', 'XPD');
    $prices = array();
    // Map $xml to $new_array
    foreach ($metals as $metal) {
        if (isset($xml->$metal)) {
            $prices[$metal] = array(
                'name' => (string) $xml->$metal['name'],
                'ask' => (string) $xml->$metal->ask,
                'bid' => (string) $xml->$metal->bid,
                'bid_time' => (string) $xml->$metal->bid_time,
            );
        }
    }
    update_option('mpt_metal_prices', $prices);
    update_option('mpt_last_update', time());    
    $mpt_metal_prices = $prices;
    return $prices;
}



function mpt_metal_price_updater_ajax_handler() {
    
    check_ajax_referer('custom_nonce', 'security'); // Security check

    // Process the request
    $data = mpt_fetch_metal_prices();

    // Send the data array as JSON;
    wp_send_json($data);

    wp_die(); // Required to properly terminate AJAX requests in WordPress
}
add_action('wp_ajax_mpt_metal_price_updater_action', 'mpt_metal_price_updater_ajax_handler'); // For logged-in users
add_action('wp_ajax_nopriv_mpt_metal_price_updater_action', 'mpt_metal_price_updater_ajax_handler'); // For non-logged-in users


// Add shortcode to make a placeholder for the metal price
add_shortcode('mpt_metal_price', 'mpt_metal_price_shortcode');
function mpt_metal_price_shortcode($atts) {
    $atts = shortcode_atts(array(
        'metal' => 'XAU',
        'request' => 'ask',
        'karats' => '24',
        'currency' => 'USD',
    ), $atts);
    return '
        <div class="mpt-metal-content mpt-metal-'.$atts['metal'].' mpt-request-'.$atts['request'].'"  mpt-metal="'.$atts['metal'].'" mpt-request = "'.$atts['request'].'" mpt-currency = "'.$atts['currency'].'" mpt-karats = "'.$atts['karats'].'" >
            <svg width="800px" height="800px" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
            <path d="M2 8a1 1 0 011-1h10a1 1 0 110 2H3a1 1 0 01-1-1z" fill="#000000"/>
            </svg>
            <span class="amount">    
                ...
            </span>
            <span class="currency"></span>
        </div>
    ';
}
