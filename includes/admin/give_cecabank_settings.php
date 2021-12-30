<?php

/**
 * Class Give_Cecabank_Settings
 *
 * @since 0.0.1
 */
class Give_Cecabank_Settings
{

    /**
     * @access private
     * @var Give_Cecabank_Settings $instance
     */
    private static $instance;

    /**
     * @access private
     * @var string $section_id
     */
    private $section_id;

    /**
     * @access private
     *
     * @var string $section_label
     */
    private $section_label;

    /**
     * Give_Cecabank_Settings constructor.
     */
    private function __construct()
    {

    }

    /**
     * get class object.
     *
     * @return Give_Cecabank_Settings
     */
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

        $this->section_id = 'cecabank';
        $this->section_label = __('Cecabank', 'give_cecabank');

        if (is_admin()) {
            // Add settings.
            add_filter('give_get_settings_gateways', array($this, 'add_settings'), 99);
            add_filter('give_get_sections_gateways', array($this, 'add_sections'), 99);
        }
    }

    /**
     * Add setting section.
     *
     * @param array $sections Array of section.
     *
     * @return array
     */
    public function add_sections($sections)
    {
        $sections[$this->section_id] = $this->section_label;

        return $sections;
    }

    /**
     * Add plugin settings.
     *
     * @param array $settings Array of setting fields.
     *
     * @return array
     */
    public function add_settings($settings)
    {
        $current_section = give_get_current_setting_section();

        if ($current_section != 'cecabank') {
            return $settings;
        }

        $give_cecabank_settings = array(
            array(
                'name' => __('Configuración Cecabank', 'give_cecabank'),
                'id' => 'give_title_gateway_cecabank',
                'type' => 'title',
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
            array(
                'type' => 'sectionend',
                'id' => 'give_title_gateway_cecabank',
            ),
        );

        return array_merge($settings, $give_cecabank_settings);
    }
}

Give_Cecabank_Settings::get_instance()->setup_hooks();
