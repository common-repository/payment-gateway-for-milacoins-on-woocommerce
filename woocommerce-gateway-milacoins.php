<?php
/*
 * Plugin Name: Payment Gateway through MilaCoins on WooCommerce
 * Description: Start paying with crypto for goods and services without risk. With MilaCoins automated crypto payment processor, you can increase your sales and offer crypto payments to millions of crypto users worldwide. All payments are settled to the wallet of your choice (fiat or crypto). No matter what crypto coin your customer chooses to pay in, you will receive your converted funds to your preferred wallet set in the currency of your choice.
 * Author: OnePix
 * Author URI: https://onepix.net/
 * Version: 1.0.2
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

class Milacoins
{
    private static $instance;
    public static $plugin_url;
    public static $images_url;
    public static $gateway_id;
    public static $plugin_path;
    public static $version;

    private function __construct()
    {
        self::$gateway_id  = 'milacoins';
        self::$plugin_url  = plugin_dir_url(__FILE__);
        self::$images_url  = plugin_dir_url(__FILE__).'assets/img/';
        self::$plugin_path = plugin_dir_path(__FILE__);
        self::$version = time();
        // Check if WooCommerce is active
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        if(is_plugin_active_for_network('woocommerce/woocommerce.php') or is_plugin_active('woocommerce/woocommerce.php')) {
            add_action('plugins_loaded', [$this, 'pluginsLoaded']);
            add_filter('woocommerce_payment_gateways', [$this, 'woocommercePaymentGateways']);
            add_action('wp_enqueue_scripts', [$this, 'frontend_enqueue_scripts'], 5);
            add_action('admin_enqueue_scripts', [$this, 'admin_style']);
            add_action('woocommerce_review_order_before_submit', [$this, 'add_button']);
        }
    }

    public function admin_style()
    {
        if (isset($_GET['page']) && $_GET['page'] === 'wc-settings' &&
            isset($_GET['tab']) && $_GET['tab'] === 'checkout' &&
            isset($_GET['section']) && $_GET['section'] === 'milacoins') {
            wp_enqueue_script('tailwind', Milacoins::$plugin_url.'assets/js/tailwind-min-3.0.24.js');
            wp_enqueue_style('admin-milacoins', Milacoins::$plugin_url . 'assets/css/admin-milacoins.css');
        }
    }

    public function add_button()
    {
        wp_nonce_field('woocommerce-process_checkout', 'woocommerce-process-checkout-nonce');
        if (!empty($_POST['payment_method']) && sanitize_text_field($_POST['payment_method']) == 'milacoins') {
            echo '<style>
                #place_order{
                    display: none;
                }
            </style>';
            $display = 'flex';
        } else {
            $display = 'none';
        }

        $settings = get_option('woocommerce_milacoins_settings');
        if ($settings['button_style'] == 'roundedFull') {
            $radius = 'border-radius: 100px;';
        } elseif ($settings['button_style'] == 'sharp') {
            $radius = 'border-radius: 0;';
        } else {
            $radius = 'border-radius: 5px;';
        }

        if ($settings['button_color'] == 'white') {
            $color = 'background-image: linear-gradient(to right bottom, rgb(255, 255, 255) 0%, rgb(255, 255, 255) 100%); color: rgb(0, 0, 0); border: 1.5px solid rgb(0, 0, 0);';
            $img   = 'logo.svg';
        } elseif ($settings['button_color'] == 'secondary') {
            $color = 'background-image: linear-gradient(to right bottom, rgb(255, 0, 66) 0%, rgb(255, 0, 66) 100%); color: rgb(255, 255, 255); border: none;';
            $img   = 'btnLogo.svg';
        } elseif ($settings['button_color'] == 'black') {
            $color = 'background-image: linear-gradient(to right bottom, rgb(0, 0, 0) 0%, rgb(0, 0, 0) 100%); color: rgb(255, 255, 255); border: none;';
            $img   = 'btnLogo.svg';
        } else { //mila
            $color = 'background-image: linear-gradient(to right bottom, rgb(251, 114, 0) 0%, rgb(222, 37, 47) 35%, rgb(132, 3, 163) 85%, rgb(171, 32, 253) 100%); color: rgb(255, 255, 255); border: none;';
            $img   = 'btnLogo.svg';
        }


        echo '<div id="milacoins-widget-con">
                <div style="display: ' . $display . '; flex-direction: column; width: 100%; min-width: 250px; max-width: 750px;">
                    <button class="custom" type="submit" style="' . $color . ' background-size: 100%; ' . $radius . ' height: 45px; min-height: 25px; max-height: 55px; font-family: hpRegFont; font-weight: bold; font-size: 0.75rem; letter-spacing: 1px; cursor: pointer; display: flex; flex-direction: row; align-items: center; justify-content: center;">
                        <span>Pay Crypto With</span>
                        <img src="' . Milacoins::$images_url.$img . '" alt=" " style="margin-right: 5px; margin-left: 10px; height: 35px; width: auto;">
                    </button>
                    <div class="supportBox">
                        <div class="supportBox">
                            <div class="supportText">Support</div>
                            <img class="iconCrypto" src="'.Milacoins::$images_url.'icons.svg" alt="icons">
                        </div>
                        <div class="supportBox"><div class="supportText" style="width: min width 100px;">Provided by :</div>
                        <img class="logoSmall" alt="logoMila" src="'.Milacoins::$images_url.'logo.svg">
                    </div>
                </div>
            </div>';
    }

    public function frontend_enqueue_scripts()
    {
        if (is_checkout()) {
            $settings = get_option('woocommerce_milacoins_settings');
            wp_enqueue_script('milacoins-cdn', Milacoins::$plugin_url . 'assets/js/mila-coins-widget-min_v2.js', [], Milacoins::$version, true);
            wp_enqueue_script('milacoins-script', Milacoins::$plugin_url . 'assets/js/milacoins-script.js', ['jquery', 'milacoins-cdn'], Milacoins::$version, true);
            wp_localize_script('milacoins-script', 'JsmilacoinsData', [
                'key'         => $settings['widget_key'],
                'mode'        => !empty($settings['mode']) ? $settings['mode'] : 'sandbox',
                'currency'    => get_woocommerce_currency(),
                'wallet'      => $settings['wallet_target'],
                'checkoutUrl' => WC_AJAX::get_endpoint('checkout'),
                'total'       => WC()->cart->cart_contents_total,
                'cart'        => WC()->cart->get_cart_hash(),
                'color'       => $settings['button_color'],
                'style'       => $settings['button_style'],
            ]);
        }
    }

    public function pluginsLoaded()
    {
        require_once 'includes/class-wc-milacoins-gateway.php';
    }

    public function woocommercePaymentGateways($gateways)
    {
        $gateways[] = 'WC_Milacoins';
        return $gateways;
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

Milacoins::getInstance();