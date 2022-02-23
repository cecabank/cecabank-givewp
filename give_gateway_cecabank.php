<?php
/**
 * Plugin Name: Cecabank GiveWP Plugin
 * Plugin URI: https://github.com/cecabank/cecabank-givewp
 * Description: Plugin de GiveWP para conectar con la pasarela de Cecabank.
 * Author: Cecabank, S.A.
 * Author URI: https://www.cecabank.es/
 * Version: 0.0.1
 * Text Domain: givewp_cecabank
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2021 Cecabank, S.A. (tpv@cecabank.es) y GiveWP
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   GiveWP-Gateway-Cecabank
 * @author    Cecabank, S.A.
 * @category  Admin
 * @copyright Copyright (c) 2021 Cecabank, S.A. (tpv@cecabank.es) y GiveWP
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

if (!defined('GIVE_CECABANK_PLUGIN_FILE')) {
  define('GIVE_CECABANK_PLUGIN_FILE', __FILE__);
}
if (!defined('GIVE_CECABANK_PLUGIN_DIR')) {
  define('GIVE_CECABANK_PLUGIN_DIR', dirname(GIVE_CECABANK_PLUGIN_FILE));
}

/**
 * Cecabank GiveWP Payment Gateway
 *
 * Provides an Cecabank GiveWP Payment Gateway.
 *
 * @class 		GiveWP_Gateway_Cecabank
 * @version		0.0.1
 * @author 		Cecabank, S.A.
 */

if (!class_exists('GiveWP_Gateway_Cecabank')):

    class GiveWP_Gateway_Cecabank {
        /**
         * @var GiveWP_Gateway_Cecabank The reference the *Singleton* instance of this class.
         */
        private static $instance;


        /**
         * Returns the *Singleton* instance of this class.
         *
         * @return GiveWP_Gateway_Cecabank The *Singleton* instance.
         */
        public static function get_instance() {
          if (null === self::$instance) {
            self::$instance = new self();
          }
    
          return self::$instance;
        }

        /**
         * Private clone method to prevent cloning of the instance of the
         * *Singleton* instance.
         *
         * @return void
         */
        private function __clone() {
    
        }

        /**
         * GiveWP_Gateway_Cecabank constructor.
         *
         * Protected constructor to prevent creating a new instance of the
         * *Singleton* via the `new` operator from outside of this class.
         */
        protected function __construct() {
          add_action('admin_init', array($this, 'check_environment'));
          add_action('admin_notices', array($this, 'admin_notices'), 15);
          add_action('plugins_loaded', array($this, 'init'));
          add_filter('query_vars', array($this, 'query_vars'), 10, 1);
          add_action('parse_request', array($this, 'parse_request'), 10, 1);
          add_filter( 'give_recurring_available_gateways', [ $this, 'add_cecabank_recurring_support' ], 99 );
    
        }

        /**
         * Init the plugin after plugins_loaded so environment variables are set.
         */
        public function init() {
    
          // Don't hook anything else in the plugin if we're in an incompatible environment.
          if (self::get_environment_warning()) {
            return;
          }
    
          add_filter('give_payment_gateways', array($this, 'register_gateway'));
          
          $this->includes();
        }

        /**
         * Add recurring support for cecabank.
         *
         */
        public function add_cecabank_recurring_support( $gateways ) {
            $gateways['cecabank'] = 'Cecabank_Recurring';
            
            return $gateways;
        }
    
        public function query_vars($vars){
            $vars[] = 'cecabank-payment-pg';
            return $vars;
        }
    
        public function parse_request($wp){
            if ( array_key_exists( 'cecabank-payment-pg', $wp->query_vars ) ){
                include plugin_dir_path(__FILE__) . 'cecabank_payment_pg.php';
                exit();
            }
        }

        /**
         * The primary sanity check, automatically disable the plugin on activation if it doesn't
         * meet minimum requirements.
         *
         * Based on http://wptavern.com/how-to-prevent-wordpress-plugins-from-activating-on-sites-with-incompatible-hosting-environments
         */
        public static function activation_check() {
          $environment_warning = self::get_environment_warning(true);
          if ($environment_warning) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die($environment_warning);
          }
        }

        /**
         * Check the server environment.
         *
         * The backup sanity check, in case the plugin is activated in a weird way,
         * or the environment changes after activation.
         */
        public function check_environment() {
    
          $environment_warning = self::get_environment_warning();
          if ($environment_warning && is_plugin_active(plugin_basename(__FILE__))) {
            deactivate_plugins(plugin_basename(__FILE__));
            $this->add_admin_notice('bad_environment', 'error', $environment_warning);
            if (isset($_GET['activate'])) {
              unset($_GET['activate']);
            }
          }
    
          // Check for if give plugin activate or not.
          $is_give_active = defined('GIVE_PLUGIN_BASENAME') ? is_plugin_active(GIVE_PLUGIN_BASENAME) : false;
          // Check to see if Give is activated, if it isn't deactivate and show a banner.
          if (is_admin() && current_user_can('activate_plugins') && !$is_give_active) {
    
            $this->add_admin_notice('prompt_give_activate', 'error', sprintf(__('<strong>Error en la activación:</strong> Debes tener el plugin de <a href="%s" target="_blank">Give</a> instalado y activado.', 'givewp_cecabank'), 'https://givewp.com'));
    
            // Don't let this plugin activate
            deactivate_plugins(plugin_basename(__FILE__));
    
            if (isset($_GET['activate'])) {
              unset($_GET['activate']);
            }
    
            return false;
          }
        }

        /**
         * Environment warnings.
         *
         * Checks the environment for compatibility problems.
         * Returns a string with the first incompatibility found or false if the environment has no problems.
         *
         * @param bool $during_activation
         *
         * @return bool|mixed|string
         */
        public static function get_environment_warning($during_activation = false) {
          return false;
        }
    
        public function add_admin_notice($slug, $class, $message)
        {
            $this->notices[$slug] = array(
                'class' => $class,
                'message' => $message,
            );
        }

        /**
         * Display admin notices.
         */
        public function admin_notices()
        {
            $allowed_tags = array(
                'a' => array(
                    'href' => array(),
                    'title' => array(),
                    'class' => array(),
                    'id' => array()
                ),
                'br' => array(),
                'em' => array(),
                'span' => array(
                    'class' => array(),
                ),
                'strong' => array(),
            );
            foreach ((array) $this->notices as $notice_key => $notice) {
                echo "<div class='" . esc_attr($notice['class']) . "'><p>";
                echo wp_kses($notice['message'], $allowed_tags);
                echo '</p></div>';
            }
        }

        /**
         * Give Cecabank Includes.
         */
        private function includes() {
    
          // Checks if Give is installed.
          if (!class_exists('Give')) {
            return false;
          }
    
          if (is_admin()) {
            include GIVE_CECABANK_PLUGIN_DIR . '/includes/admin/give_cecabank_activation.php';
            include GIVE_CECABANK_PLUGIN_DIR . '/includes/admin/give_cecabank_settings.php';
            include GIVE_CECABANK_PLUGIN_DIR . '/includes/admin/give_cecabank_settings_metabox.php';
          }
          include GIVE_CECABANK_PLUGIN_DIR . '/includes/give_cecabank_gateway.php';
    
          // Load the file only when recurring donations addo-on is enabled.
          if ( defined( 'GIVE_RECURRING_VERSION' ) ) {
              include GIVE_RECURRING_PLUGIN_DIR . '/includes/gateways/give-recurring-gateway.php';
              include GIVE_CECABANK_PLUGIN_DIR . '/includes/class_cecabank_recurring.php';
          }
        }

        /**
         * Register Cecabank.
         *
         * @access      public
         * @since       0.0.1
         *
         * @param $gateways array
         *
         * @return array
         */
        public function register_gateway($gateways) {
    
          // Format: ID => Name
          $label = array(
            'admin_label'    => __('Cecabank', 'give_cecabank'),
            'checkout_label' => __('Tarjeta', 'give_cecabank'),
          );
    
          $gateways['cecabank'] = apply_filters('give_cecabank_label', $label);
    
          return $gateways;
        }
    } // end \GiveWP_Gateway_Cecabank class
  
    // test shortcode
    function get_cecabank() {
        return 'cecabank shortcode';
    }

    add_shortcode('cecabank', 'get_cecabank');
  
    $GLOBALS['give_cecabank'] = GiveWP_Gateway_Cecabank::get_instance();
    register_activation_hook(__FILE__, array('GiveWP_Gateway_Cecabank', 'activation_check'));

endif; // End if class_exists check.
