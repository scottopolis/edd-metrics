<?php
/**
 * Plugin Name:     EDD Metrics
 * Plugin URI:      http://metricswp.com
 * Description:     All the stats, analytics, and metrics you need when selling stuff with Easy Digital Downloads.
 * Version:         0.0.1
 * Author:          Scott Bolinger
 * Author URI:      http://scottbolinger.com
 * Text Domain:     edd-metrics
 *
 * @package         EDD\EDD Metrics
 * @author          Scott Bolinger
 * @copyright       Copyright (c) Scott Bolinger 2016
 *
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'EDD_Metrics' ) ) {

    /**
     * Main EDD_Metrics class
     *
     * @since       1.0.0
     */
    class EDD_Metrics {

        /**
         * @var         EDD_Metrics $instance The one true EDD_Metrics
         * @since       1.0.0
         */
        private static $instance;


        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      object self::$instance The one true EDD_Metrics
         */
        public static function instance() {
            if( !self::$instance ) {
                self::$instance = new EDD_Metrics();
                self::$instance->setup_constants();
                self::$instance->includes();
                self::$instance->load_textdomain();
                self::$instance->hooks();
            }

            return self::$instance;
        }


        /**
         * Setup plugin constants
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function setup_constants() {
            // Plugin version
            define( 'EDD_Metrics_VER', '1.0.0' );

            // Plugin path
            define( 'EDD_Metrics_DIR', plugin_dir_path( __FILE__ ) );

            // Plugin URL
            define( 'EDD_Metrics_URL', plugin_dir_url( __FILE__ ) );
        }


        /**
         * Include necessary files
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function includes() {
            // Include scripts
            require_once EDD_Metrics_DIR . 'includes/scripts.php';
            require_once EDD_Metrics_DIR . 'includes/class-edd-metrics-functions.php';
            require_once EDD_Metrics_DIR . 'includes/class-edd-metrics-detail.php';

            // require_once EDD_Metrics_DIR . 'includes/shortcodes.php';
            // require_once EDD_Metrics_DIR . 'includes/widgets.php';
        }


        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         *
         *              This method should be used to add any filters or actions
         *              that are necessary to the core of your extension only.
         *              Hooks that are relevant to meta boxes, widgets and
         *              the like can be placed in their respective files.
         *
         */
        private function hooks() {

            add_action( 'admin_menu', array( $this, 'settings_page' ) );

            // Handle licensing
            // @todo        Replace the EDD Metrics and Your Name with your data
            // if( class_exists( 'EDD_License' ) ) {
            //     $license = new EDD_License( __FILE__, 'EDD Metrics', EDD_Metrics_VER, 'Your Name' );
            // }
        }


        /**
         * Internationalization
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function load_textdomain() {
            // Set filter for language directory
            $lang_dir = EDD_Metrics_DIR . '/languages/';
            $lang_dir = apply_filters( 'EDD_Metrics_languages_directory', $lang_dir );

            // Traditional WordPress plugin locale filter
            $locale = apply_filters( 'plugin_locale', get_locale(), 'edd-metrics' );
            $mofile = sprintf( '%1$s-%2$s.mo', 'edd-metrics', $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/edd-metrics/' . $mofile;

            if( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/edd-metrics/ folder
                load_textdomain( 'edd-metrics', $mofile_global );
            } elseif( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/edd-metrics/languages/ folder
                load_textdomain( 'edd-metrics', $mofile_local );
            } else {
                // Load the default language files
                load_plugin_textdomain( 'edd-metrics', false, $lang_dir );
            }
        }


        /**
         * Add settings
         *
         * @access      public
         * @since       1.0.0
         */
        public function settings_page() {

            add_submenu_page( 'edit.php?post_type=download', 'Metrics', 'Metrics', 'manage_options', 'edd_metrics', array( $this, 'render_settings') );
            
        }

        /**
         * Add settings
         *
         * @access      public
         * @since       1.0.0
         */
        public function render_settings() {

            ?>
            <div id="edd-metrics-wrap" class="wrap">

                <?php do_action('edd_metrics_select'); ?>

                <?php

                if ( isset( $_GET['view'] ) && 'metrics-details' == $_GET['view'] ) {
                    require_once EDD_Metrics_DIR . 'includes/view-metrics-details.php';
                } else {
                    require_once EDD_Metrics_DIR . 'includes/view-dashboard.php';
                }

                ?>

            </div>
            <?php
            
        }
    }
} // End if class_exists check


/**
 * The main function responsible for returning the one true EDD_Metrics
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      \EDD_Metrics The one true EDD_Metrics
 *
 */
function EDD_Metrics_load() {
    if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
        if( ! class_exists( 'EDD_Extension_Activation' ) ) {
            require_once 'includes/class.extension-activation.php';
        }

        $activation = new EDD_Extension_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
        $activation = $activation->run();
    } else {
        return EDD_Metrics::instance();
    }
}
add_action( 'plugins_loaded', 'EDD_Metrics_load' );


/**
 * The activation hook is called outside of the singleton because WordPress doesn't
 * register the call from within the class, since we are preferring the plugins_loaded
 * hook for compatibility, we also can't reference a function inside the plugin class
 * for the activation function. If you need an activation function, put it here.
 *
 * @since       1.0.0
 * @return      void
 */
function EDD_Metrics_activation() {
    /* Activation functions here */
}
register_activation_hook( __FILE__, 'EDD_Metrics_activation' );
