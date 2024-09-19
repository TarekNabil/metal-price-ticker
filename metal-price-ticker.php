<?php
/**
 * Plugin Name: Metal Price Ticker
 * Description: Plugin and Elementor add-on to display Metal prices ticker.
 * Version: 1.0.1
 * Author: Tarek Nabil
 * Author URI: https://tareknabil.net
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: metal-price-ticker
 */
// add plugin version to be used in enqueued assets
define('MPT_VERSION', '1.0.1');

// Enqueue CSS and JS
add_action('wp_enqueue_scripts', 'mpt_enqueue_assets');
function mpt_enqueue_assets() {
    // Enqueue styles
    wp_enqueue_style('mpt-styles', plugin_dir_url(__FILE__) . 'assets/css/style.css');
    $script_path = plugin_dir_path(__FILE__) . 'assets/js/metal-price-ticker.js';
    $script_url = plugin_dir_url(__FILE__) . 'assets/js/metal-price-ticker.js';
    $script_version = filemtime($script_path);

    wp_enqueue_script('mpt-scripts', $script_url, array('jquery'), $script_version, true );
    wp_localize_script('mpt-scripts', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'ajax_nonce' => wp_create_nonce('custom_nonce'),
        'interval' => get_option('mpt_update_interval', 10),
        'karats_rates' => get_option('mpt_karats_rates'),
        'unit_rates' => get_option('mpt_unit_rates'),
        'fees' => get_option('mpt_fees'),
    ));
    // enqueue the script for the metal price calculator
    $calc_script_path = plugin_dir_path(__FILE__) . 'assets/js/metal-price-calculator.js';
    $calc_script_url = plugin_dir_url(__FILE__) . 'assets/js/metal-price-calculator.js';
    $calc_script_version = filemtime($calc_script_path);
    
    wp_enqueue_script('metal-price-calculator', $calc_script_url, array('jquery'), $calc_script_version, true);
    // Send some data to JS
    wp_localize_script('metal-price-calculator', 'metal_price_calc', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('metal_price_calc_nonce'),
        'goldPrices' => mpt_get_all_metal_prices(),
        'unit_rates' => get_option('mpt_unit_rates'),
        'fees' => get_option('mpt_fees'),
        'conversionRates' => array(
            'AED' => 3.674,
            'SAR' => 3.75,
            'USD' => 1,
            'QAR' => 3.64,
        )
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
    // add settings section for conversion rates for karats, units and fees
    add_settings_section(
        'mpt_conversion_rates_section',       // ID
        'Conversion Rates',   // Title
        null,                         // Callback
        'mpt-settings'                // Page
    );
    
    // add setting field for karats rates
    register_setting('mpt_settings_group', 'mpt_karats_rates', array(
        
        'default' => array(
            24 => 1,
            22 => 0.9167,// XX
            21 => 0.880,// 0.880
            18 => 0.75,
            14 => 0.5833,//XX
            10 => 0.4167,// XX
        ),
    ));
    add_settings_field(
        'mpt_karats_rates',       // ID
        'Karats Rates',                   // Title
        'mpt_karats_rates_callback', // Callback
        'mpt-settings',               // Page
        'mpt_conversion_rates_section'        // Section
    );
    // add setting field for unit rates
    register_setting('mpt_settings_group', 'mpt_unit_rates', array(
        
        'default' => array(
            'ounce' => 1,
            'gram' => 0.0311034,
            'kilogram' => 0.0311034 / 1000,
        ),
    ));
    add_settings_field(
        'mpt_unit_rates',       // ID
        'Unit Rates',                   // Title
        'mpt_unit_rates_callback', // Callback
        'mpt-settings',               // Page
        'mpt_conversion_rates_section'        // Section
    );
    // add setting field for fees
    register_setting('mpt_settings_group', 'mpt_fees', array(
        
        'default' => array(
            'custom_fees' => 0.00,
            'main_fees' => 0.00,
            'fixed' => 0.00,
            'no_fees' => 0.00,
        ),
    ));
    add_settings_field(
        'mpt_fees',       // ID
        'Fees',                   // Title
        'mpt_fees_callback', // Callback
        'mpt-settings',               // Page
        'mpt_conversion_rates_section'        // Section
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
function mpt_karats_rates_callback() {
    $karats_rates = get_option('mpt_karats_rates');
    ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="mpt_karats_rates_24">24 Karats Rate:</label></th>
            <td><input type="number" id="mpt_karats_rates_24" name="mpt_karats_rates[24]" value="<?php echo esc_attr($karats_rates[24]); ?>" maxlength="10" style="margin-left: 10px;" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="mpt_karats_rates_22">22 Karats Rate:</label></th>
            <td><input type="number" id="mpt_karats_rates_22" name="mpt_karats_rates[22]" value="<?php echo esc_attr($karats_rates[22]); ?>" maxlength="10" style="margin-left: 10px;" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="mpt_karats_rates_21">21 Karats Rate:</label></th>
            <td><input type="number" id="mpt_karats_rates_21" name="mpt_karats_rates[21]" value="<?php echo esc_attr($karats_rates[21]); ?>" maxlength="10" style="margin-left: 10px;" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="mpt_karats_rates_18">18 Karats Rate:</label></th>
            <td><input type="number" id="mpt_karats_rates_18" name="mpt_karats_rates[18]" value="<?php echo esc_attr($karats_rates[18]); ?>" maxlength="10" style="margin-left: 10px;" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="mpt_karats_rates_14">14 Karats Rate:</label></th>
            <td><input type="number" id="mpt_karats_rates_14" name="mpt_karats_rates[14]" value="<?php echo esc_attr($karats_rates[14]); ?>" maxlength="10" style="margin-left: 10px;" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="mpt_karats_rates_10">10 Karats Rate:</label></th>
            <td><input type="number" id="mpt_karats_rates_10" name="mpt_karats_rates[10]" value="<?php echo esc_attr($karats_rates[10]); ?>" maxlength="10" style="margin-left: 10px;" /></td>
        </tr>
    </table>
    <?php
}
function mpt_unit_rates_callback() {
    $unit_rates = get_option('mpt_unit_rates');
    ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="mpt_unit_rates_ounce">Ounce Rate:</label></th>
            <td><input type="number" id="mpt_unit_rates_ounce" name="mpt_unit_rates[ounce]" value="<?php echo esc_attr($unit_rates['ounce']); ?>" step="0.1" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="mpt_unit_rates_gram">Gram Rate:</label></th>
            <td><input type="number" id="mpt_unit_rates_gram" name="mpt_unit_rates[gram]" value="<?php echo esc_attr($unit_rates['gram']); ?>" step="0.0000001" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="mpt_unit_rates_kilogram">Kilogram Rate:</label></th>
            <td><input type="number" id="mpt_unit_rates_kilogram" name="mpt_unit_rates[kilogram]" value="<?php echo esc_attr($unit_rates['kilogram']); ?>" step="0.0000000001" /></td>
        </tr>
    </table>
    <?php
}
function mpt_fees_callback() {
    $fees = get_option('mpt_fees');
    ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="mpt_fees_custom_fees">Custom Fees:</label></th>
            <td><input type="number" id="mpt_fees_custom_fees" name="mpt_fees[custom_fees]" value="<?php echo esc_attr($fees['custom_fees']); ?>" step="0.01" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="mpt_fees_main_fees">Main Fees:</label></th>
            <td><input type="number" id="mpt_fees_main_fees" name="mpt_fees[main_fees]" value="<?php echo esc_attr($fees['main_fees']); ?>" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="mpt_fees_fixed">Fixed Fees:</label></th>
            <td><input type="number" id="mpt_fees_fixed" name="mpt_fees[fixed]" value="<?php echo esc_attr($fees['fixed']); ?>" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="mpt_fees_no_fees">No Fees:</label></th>
            <td><input type="number" id="mpt_fees_no_fees" name="mpt_fees[no_fees]" value="<?php echo esc_attr($fees['no_fees']); ?>" /></td>
        </tr>
    </table>
    <?php
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
                'currency' => 'USD',
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
        'unit' => 'ounce',
        'karats' => '24',
        'currency' => 'USD',
    ), $atts);
    $metal = $atts['metal'];
    $request = $atts['request'];
    $currency = $atts['currency'];
    $karats = $atts['karats'];
    $unit = $atts['unit'];

    $metal_prices = mpt_get_all_metal_prices();
    $value = '...';
    // if $metal_prices is not empty and request is ask or bid then set the value to the corresponding value
    if (is_array($metal_prices) && ($request == 'ask' || $request == 'bid')) {
        // get the value of the metal and request
        $value = $metal_prices[$metal][$request];
        // add fees to the value
        $value = mpt_add_fees($value, 'custom_fees');
        // convert the value to the unit
        $unit_rates = get_option('mpt_unit_rates');

        $value = $value * $unit_rates[$unit];
        // convert the value to the currency
        $value = mpt_currency_converter($value, $currency);

        // if metal is gold and karats is not 24 then calculate the value based on the karats
        if ($metal == 'XAU') {
            $karats_rates = get_option('mpt_karats_rates');
            $value = $value * $karats_rates[$karats];
        }
        // limit to 2 decimal float using
        $value = number_format($value, 2);
        
    }
    
    
    $html =  '
        <div class= "mpt-metal-content metal-price-up mpt-metal-'.$atts['metal'].' mpt-request-'.$atts['request'].'"  mpt-metal="'.$atts['metal'].'" mpt-request = "'.$atts['request'].'" mpt-currency = "'.$atts['currency'].'" mpt-karats = "'.$atts['karats'].'"mpt-unit = "'.$atts['unit'].'">
            <svg fill="#00ff00" version="1.1" id="arrow-up-src" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512.001 512.001" xml:space="preserve">
                <g>
                    <g>
                        <path d="M505.749,304.918L271.083,70.251c-8.341-8.341-21.824-8.341-30.165,0L6.251,304.918C2.24,308.907,0,314.326,0,320.001
                            v106.667c0,8.619,5.184,16.427,13.163,19.712c7.979,3.307,17.152,1.472,23.253-4.629L256,222.166L475.584,441.75
                            c4.075,4.075,9.536,6.251,15.083,6.251c2.752,0,5.525-0.512,8.171-1.621c7.979-3.285,13.163-11.093,13.163-19.712V320.001
                            C512,314.326,509.76,308.907,505.749,304.918z"/>
                    </g>
                </g>
            </svg>
            <span class="amount">    
                '.$value.'
            </span>
            <span class="currency">' . esc_html(__($currency, 'metal-price-ticker')) . '</span>
        </div>
    ';
    return $html;
}


function mpt_currency_converter($amount, $currency){
    $currency_rates = array(
        'USD' => 1,
        'SAR' => 3.75,
        'AED' => 3.674,
        'QAR' => 3.64,
    );
    $rate = $currency_rates[$currency];
    return $amount * $rate;
}



function mpt_add_fees($amount, $fees_type){
    $fees = get_option('mpt_fees');
    $fees = $fees[$fees_type];
    return $amount + $fees;
    
}

// add shortcode to place a metal price calculator
add_shortcode('mpt_metal_price_calculator', 'mpt_metal_price_calculator_shortcode');
function mpt_metal_price_calculator_shortcode() {

    // Shortcode output
    ob_start();
    ?>
        <form id="calcForm" class="form-container">
        <div>
            <label for="lstSymbol">Metal Symbol:</label>
            <select id="lstSymbol" name="symbol" required onchange="calcForm()">
                <option value="XAU">Gold (XAU)</option>
                <option value="XAG">Silver (XAG)</option>
                <option value="XPT">Platinum (XPT)</option>
                <option value="XPD">Palladium (XPD)</option>
            </select>  
        </div>
        <div>
            <label for="txtWeight">Weight:</label>
            <input type="number" id="txtWeight" name="weight" step="0.01" required onchange="calcForm()">
        </div>
        <div>
            <label for="lstWeightUnit">Weight Unit:</label>
            <select id="lstWeightUnit" name="weightUnit" required onchange="calcForm()">
                <option value="gram">Grams</option>
                <option value="ounce">Ounces</option>
                <option value="kilogram">Kilograms</option>
            </select>
        </div>
        <div>
            <label for="lstCurrency">Currency:</label>
            <select id="lstCurrency" name="currency" required onchange="calcForm()">
                <option value="USD">USD</option>
                <option value="AED">AED</option>
                <option value="SAR">SAR</option>
            </select>
        </div>
        <div>
            <label for="txtAmount">Amount:</label>
            <input type="text" id="txtAmount" name="amount" readonly>
        </div>
    </form>
    <?php
    return ob_get_clean();
}
