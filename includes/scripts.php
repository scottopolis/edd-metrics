<?php
/**
 * Scripts
 *
 * @package     EDD\EDD Metrics\Scripts
 * @since       0.2.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


/**
 * Load admin scripts
 *
 * @since       0.2.0
 * @global      array $edd_settings_page The slug for the EDD settings page
 * @global      string $post_type The type of post that we are editing
 * @return      void
 */
function EDD_Metrics_admin_scripts( $hook ) {

    // Use minified libraries if SCRIPT_DEBUG is turned off
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

    /**
     * @todo		This block loads styles or scripts explicitly on the
     *				EDD metrics page.
     */
    if( $hook == 'download_page_edd_metrics' ) {
        wp_enqueue_script( 'moment-js', EDD_Metrics_URL . 'assets/js/moment.js', array( 'jquery' ) );
        wp_enqueue_script( 'edd-metrics-js', EDD_Metrics_URL . 'assets/js/admin' . $suffix . '.js', array( 'jquery' ), EDD_Metrics_VER, true );
        wp_enqueue_style( 'edd-metrics-css', EDD_Metrics_URL . 'assets/css/admin' . $suffix . '.css', null, EDD_Metrics_VER );
        wp_enqueue_script( 'baremetrics-calendar', EDD_Metrics_URL . 'assets/js/Calendar.js', array( 'jquery' ), '1.0', true );
        wp_enqueue_script( 'chartsjs', EDD_Metrics_URL . 'assets/js/Chart.min.js', array( 'jquery' ), '2.2.1', true );

        wp_enqueue_style( 'baremetrics-calendar', EDD_Metrics_URL . 'assets/css/calendar.css' );

        wp_localize_script( 'edd-metrics-js', 'eddMetrics', array(
            'compare_string' => __( '% over previous', 'edd-metrics' ),
            'compare_string_2' => __( '% compared to this period', 'edd-metrics' ),
            'days' => __( 'days', 'edd-metrics' ),
            'revenue' => __( 'Revenue', 'edd-metrics' ),
            'downloads' => __( 'Downloads', 'edd-metrics' ),
            )
        );

    }
}
add_action( 'admin_enqueue_scripts', 'EDD_Metrics_admin_scripts', 100 );