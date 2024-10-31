<?php


class WC_Milacoins extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'milacoins'; // ID платёжног шлюза
        $this->method_title = 'MilaCoins ';
        $this->method_description = 'Start accepting payments for goods or services in cryptocurrencies. With the MilaCoins plugin for WooCommerce, you can significantly increase your profits and offer crypto payments to more customers. Regardless of the cryptocurrency chosen by your customer at the checkout, you will receive the payment to your MilaCoins wallet set in the currency of your choice.'; // будет отображаться в админке
        $this->supports = array('products', 'refunds');
        $this->icon = Milacoins::$plugin_url . 'assets/img/logo.svg';
        $this->title = 'Pay crypto through MilaCoins';
        $this->key = $this->get_option('key');
        //$this->description = 'Pay via our secure payment gateway.';
        $this->logging = 'yes' === $this->get_option('logging');
        $this->order = NULL;
        $this->init_form_fields();
        $this->init_settings();
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action('woocommerce_before_checkout_form', array($this, 'wnd_checkout_code'), 10);
    }

    public function wnd_checkout_code(){
        if(isset($_GET['notice'])){
            echo '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
                <ul class="woocommerce-error" role="alert">
                    <li>
                        '.esc_html($_GET['notice']).'	
                    </li>
                </ul>
            </div>';
        }
    }

    public function init_form_fields()
    {
        $currency_code_options = [
            'BTC' => 'Bitcoin',
            'BCH' => 'Bitcoin Cash',
            'EUR' => 'Euro',
            'GBP' => 'Pound sterling',
            'ETH' => 'Ethereum',
            'USDT' => 'USDT',
            'USDC' => 'USDC',
            'USD' => 'United States dollar',
        ];

        foreach ( $currency_code_options as $code => $name ) {
            $currency_code_options[ $code ] = $name . ' (' . $code . ')';
        }
        $fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'label' => 'Enable MilaCoins Gateway',
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'widget_key' => array(
                'title' => 'Widget Key',
                'type' => 'text',
                'description' => 'To get the widget key, log in to your MilaCoins account and navigate to Payment Tools > Setup Payment Button > WooCommerce.',
                'default' => ''
            ),
            'wallet_target' => array(
                'title' => 'Target Wallet',
                'type' => 'select',
                'description' => 'Choose the wallet (currency) for receiving your automatically exchanged payments.',
                'options' => $currency_code_options
            ),
            'mode' => array(
                'title' => 'Mode',
                'type' => 'radio',
                'options'=> array('sandbox' => 'Sandbox (test)', 'prod' => 'Live'),
                'default' => 'sandbox',
            ),
            'button_color' => array(
                'title' => 'Button color',
                'label' => 'Enable/Disable',
                'type' => 'radio_button',
                'options'=> array(
                    'mila' => 'rounded bg-mila-btnwidget',
                    'secondary' => 'rounded bg-mila-400',
                    'black' => 'rounded bg-black',
                    'white' => 'border border-black rounded bg-white'
                ),
                'default' => 'mila',
            ),
            'button_style' => array(
                'title' => 'Button style',
                'label' => 'Enable/Disable',
                'type' => 'radio_button',
                'options'=> array(
                    'rounded' => 'rounded bg-mila-btnwidget',
                    'sharp' => 'bg-mila-btnwidget',
                    'roundedFull' => 'rounded-full bg-mila-btnwidget',
                ),
                'default' => 'rounded',
            ),
            'logging' => array(
                'title' => 'Enable logging',
                'label' => 'Enable/Disable',
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
        );

        $this->form_fields = $fields;
    }

    public function generate_radio_button_html($key, $data){
        $field_key = $this->get_field_key( $key );
        $defaults  = array(
            'title'             => '',
            'disabled'          => false,
            'class'             => '',
            'css'               => '',
            'placeholder'       => '',
            'type'              => 'text',
            'desc_tip'          => false,
            'description'       => '',
            'custom_attributes' => array(),
        );

        $data = wp_parse_args( $data, $defaults );

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <?php foreach ($data['options'] as $val => $name):?>
                        <label class="mila-buttons w-5\/12 2xl:w-1/4 m-3 flex items-center justify-center md:space-x-2 py-1 <?php echo esc_attr($name)?>">
                            <input type="radio" name="<?php echo esc_attr($field_key)?>" id="<?php echo esc_attr($val)?>" value="<?php echo esc_attr($val)?>" <?php checked( $val, esc_attr( $this->get_option( $key ) ) ); ?>>
                            <h6 class="<?php echo $val == 'white' ? 'text-black' : 'text-white'?> text-sm sm:text-3xs md:text-sm md:text-sm hpRegFont">Pay Crypto With</h6>
                            <img class="h-6 sm:h-4 md:h-8" src="<?php echo $val == 'white' ? Milacoins::$images_url.'btnLogoWhite' : Milacoins::$images_url.'btnLogo'?>.svg" alt="">
                        </label>

                    <?php endforeach; ?>
                </fieldset>
            </td>
        </tr>
        <?php

        return ob_get_clean();
    }

    public function generate_radio_html($key, $data){
        $field_key = $this->get_field_key( $key );
        $defaults  = array(
            'title'             => '',
            'disabled'          => false,
            'class'             => '',
            'css'               => '',
            'placeholder'       => '',
            'type'              => 'text',
            'desc_tip'          => false,
            'description'       => '',
            'custom_attributes' => array(),
        );

        $data = wp_parse_args( $data, $defaults );

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <?php foreach ($data['options'] as $val => $name):?>
                        <label for="<?php echo esc_attr($val)?>"><?php echo wp_kses_post($name)?></label>
                        <input type="radio" name="<?php echo esc_attr($field_key)?>" id="<?php echo esc_attr($val)?>" value="<?php echo esc_attr($val)?>" <?php checked( $val, esc_attr( $this->get_option( $key ) ) ); ?>>
                    <?php endforeach; ?>
                </fieldset>
            </td>
        </tr>
        <?php

        return ob_get_clean();
    }

    public function process_payment($order_id)
    {
        $invoice =  sanitize_text_field($_POST['invoice_id']);
        if(!empty($invoice)){
            update_post_meta($order_id, 'milacoins_invoice_id',  $invoice);
            $order = wc_get_order( $order_id );
            $order->payment_complete();
            return [ 'result' => 'success', 'redirect' => $order->get_checkout_order_received_url()];
        }

        return [ 'result' => 'success', 'order_id' => $order_id];
    }

    public function log($data, $prefix = '')
    {
        if ($this->logging) {
            $context = array('source' => $this->id);
            wc_get_logger()->debug($prefix . "\n" . print_r($data, 1), $context);
        }
    }
}