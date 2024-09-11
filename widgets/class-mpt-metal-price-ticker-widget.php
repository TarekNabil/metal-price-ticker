<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class MPT_Metal_Price_Ticker_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'metal_price_ticker';
    }

    public function get_title() {
        return 'Metal Price Ticker';
    }

    public function get_icon() {
        return 'eicon-sync';
    }

    public function get_categories() {
        return ['general'];
    }
    protected function register_controls() {
        // Content Tab
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'mpt-metal-price-ticker'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'title',
            [
                'label' => __('Title', 'mpt-metal-price-ticker'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Metal Price Ticker', 'mpt-metal-price-ticker'),
                'placeholder' => __('Enter your title', 'mpt-metal-price-ticker'),
            ]
        );

        $this->end_controls_section();

        // Style Tab
        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Style', 'mpt-metal-price-ticker'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => __('Title Color', 'mpt-metal-price-ticker'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mpt-title' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => __('Typography', 'mpt-metal-price-ticker'),
                'selector' => '{{WRAPPER}} .mpt-title',
            ]
        );

        $this->end_controls_section();
    }
    // display item
    public function display_item($item_name, $item_ask) {
        ?>
        <div class="mpt-item">
            <span class="mpt-item-name"><?php echo $item_name; ?></span>
            <span class="mpt-item-ask"><?php echo $item_ask; ?></span>
        </div>
        <?php
        
    }
    protected function render() {


        $user = get_option('mpt_fastmarkets_user');
        $password = get_option('mpt_fastmarkets_password');
    
        if (!$user || !$password) {
            echo 'API credentials are not set.';
            return;
        }
    
        // Use the working URL with the user and password
        $url = "https://pro.fastmarkets.com/feeds/?usr=$user&pwd=$password";
    
        $response = wp_remote_get($url);
        error_log(print_r($response, true));
        if (is_wp_error($response)) {
            echo 'Failed to fetch data.';
            return;
        }
    
        $data = wp_remote_retrieve_body($response);
    
        // Parse the XML response
        $xml = simplexml_load_string($data);
        if ($xml === false) {
            error_log( $data);
            echo 'Failed to parse XML.';
            return;
        }
    
        // Convert XML to JSON for easier handling (optional)
        $json = json_encode($xml);
        $array = json_decode($json, true);
    
        if (empty($array)) {

            echo 'No data available.';
            return;
        }
        //display widget settings
        $settings = $this->get_settings_for_display();
        // Display the title if provided
        if($settings['title']) {
            ?>
            <div class="mpt-title" style="color: <?php echo $settings['title_color']; ?>; font-size: <?php echo $settings['title_typography']['size']; ?>px; font-family: <?php echo $settings['title_typography']['family']; ?>">
                <?php echo $settings['title']; ?>
            </div>
            <?php
        }
        

        // Display the data (customize as needed)
        echo '<div class="mpt-items-container">';
        // Display each item [@attributes]->[name] and [ask] nest to it
        foreach ($array as $item) {
            if (!isset($item['@attributes']['name']) || !isset($item['ask'])) {
                continue;
            }
            $this->display_item($item['@attributes']['name'], $item['ask']);
            
        }
        echo '</div>';
    }
}
