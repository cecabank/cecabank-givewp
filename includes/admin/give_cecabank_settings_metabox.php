<?php

class Give_Cecabank_Settings_Metabox
{
    private static $instance;

    private function __construct()
    {
    }

    public static function get_instance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Setup hooks.
     */
    public function setup_hooks()
    {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_js'));
            add_filter('give_forms_cecabank_metabox_fields', array($this, 'give_cecabank_add_settings'));
            add_filter('give_metabox_form_data_settings', array($this, 'add_cecabank_setting_tab'), 0, 1);
        }
    }

    public function add_cecabank_setting_tab($settings)
    {
        if (give_is_gateway_active('cecabank')) {
            $settings['cecabank_options'] = apply_filters('give_forms_cecabank_options', array(
                'id' => 'cecabank_options',
                'title' => __('Cecabank', 'give'),
                'icon-html' => '<span class="give-icon give-icon-purse"></span>',
                'fields' => apply_filters('give_forms_cecabank_metabox_fields', array()),
            ));
        }

        return $settings;
    }

    public function give_cecabank_add_settings($settings)
    {

        // Bailout: Do not show offline gateways setting in to metabox if its disabled globally.
        if (in_array('cecabank', (array) give_get_option('gateways'))) {
            return $settings;
        }

        $is_gateway_active = give_is_gateway_active('cecabank');

        //this gateway isn't active
        if (!$is_gateway_active) {
            //return settings and bounce
            return $settings;
        }

        //Fields
        $check_settings = array(

            array(
                'name' => __('Cecabank', 'give_cecabank'),
                'desc' => __('¿Habilitar Cecabank?', 'give_cecabank'),
                'id' => 'cecabank_customize_cecabank_donations',
                'type' => 'radio_inline',
                'default' => 'disabled',
                'options' => apply_filters('give_forms_content_options_select', array(
                    'enabled' => __('Si', 'give_cecabank'),
                    'disabled' => __('No', 'give_cecabank'),
                )
                ),
            ),

            array(
                'name' => __('Código de comercio', 'give_cecabank'),
                'desc' => __('Código de comercio dado por Cecabank.', 'give_cecabank'),
                'id' => 'cecabank_merchant',
                'type' => 'text',
                'row_classes' => 'give-cecabank-key',
            ),
            array(
                'name' => __('Adquiriente', 'give_cecabank'),
                'desc' => __('Adquiriente dado por Cecabank.', 'give_cecabank'),
                'id' => 'cecabank_acquirer',
                'type' => 'text',
                'row_classes' => 'give-cecabank-key',
            ),
            array(
                'name' => __('Clave Secreta', 'give_cecabank'),
                'desc' => __('Clave Secreta dado por Cecabank.', 'give_cecabank'),
                'id' => 'cecabank_secret_key',
                'type' => 'text',
                'row_classes' => 'give-cecabank-key',
            ),
            array(
                'name' => __('Terminal', 'give_cecabank'),
                'desc' => __('Terminal dado por Cecabank.', 'give_cecabank'),
                'id' => 'cecabank_terminal',
                'type' => 'text',
                'row_classes' => 'give-cecabank-key',
            ),
            array(
                'name' => __('Título', 'give_cecabank'),
                'desc' => __('Título mostrado al cliente durante el proceso de compra con este método de pago.', 'give_cecabank'),
                'id' => 'cecabank_title',
                'default' => __('Tarjeta', 'give_cecabank'),
                'type' => 'text',
                'row_classes' => 'give-cecabank-key',
            ),
            array(
                'name' => __('Descripción', 'give_cecabank'),
                'desc' => __('Descripción mostrada al cliente durante el proceso de compra con este método de pago.', 'give_cecabank'),
                'id' => 'cecabank_description',
                'default' => __('Paga con tu tarjeta', 'give_cecabank'),
                'type' => 'text',
                'row_classes' => 'give-cecabank-key',
            ),
            array(
                'name' => __('Entorno', 'give_cecabank'),
                'desc' => __('Entorno que se usará al realizar las transacciones.', 'give_cecabank'),
                'id' => 'cecabank_environment',
                'row_classes' => 'give-subfield give-hidden',
                'type' => 'radio_inline',
                'default' => 'test',
                'options' => array(
                    'test' => __('Prueba', 'give_cecabank'),
                    'real' => __('Real', 'give_cecabank'),
                ),
            ),
        );

        return array_merge($settings, $check_settings);
    }

    public function enqueue_js($hook)
    {
        if ('post.php' === $hook || $hook === 'post-new.php') {
            wp_enqueue_script('give_cecabank_each_form', GIVE_MPAY_PLUGIN_URL . '/includes/js/meta-box.js');
        }
    }

}
Give_Cecabank_Settings_Metabox::get_instance()->setup_hooks();
