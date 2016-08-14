<?php
/**
 * Helper Functions
 *
 * @package     EDD\EDD Metrics\Functions
 * @since       1.0.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'EDD_Metrics_Detail' ) ) {

    /**
     * EDD_Metrics_Detail class
     *
     * @since       1.0.0
     */
    class EDD_Metrics_Detail extends EDD_Metrics_Functions {

        /**
         * @var         EDD_Metrics_Detail $instance The one true EDD_Metrics_Detail
         * @since       1.0.0
         */
        private static $instance;
        public static $end = null;
        public static $start = null;
        public static $endstr = null;
        public static $startstr = null;


        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      object self::$instance The one true EDD_Metrics_Detail
         */
        public static function instance() {
            if( !self::$instance ) {
                self::$instance = new EDD_Metrics_Detail();
                self::$instance->hooks();
            }

            return self::$instance;
        }

        /**
         * Hooks
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function hooks() {

            add_filter( 'metrics_json_output', array( $this, 'revenue_callback' ) );

        }

        /**
         * Revenue stats for detail view
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public static function revenue_callback( $data ) {

            $dates = self::get_compare_dates();

            // Get current and previous period earnings
            $EDD_Stats = new EDD_Payment_Stats();
            $earnings = $EDD_Stats->get_earnings( 0, $dates['start'], $dates['end'] );

            $six_mo_ago = self::subtract_days( $dates['start'], $dates['end'], 182 );
            $earnings_6mo_ago = $EDD_Stats->get_earnings( 0, strtotime( $six_mo_ago[0] ), strtotime( $six_mo_ago[1] ) );

            $twelve_mo_ago = self::subtract_days( $dates['start'], $dates['end'], 365 );
            $earnings_12mo_ago = $EDD_Stats->get_earnings( 0, strtotime(  $twelve_mo_ago[0] ), strtotime( $twelve_mo_ago[1] ) );

            // print_r( $twelve_mo_ago[0] . ' ' . $twelve_mo_ago[1] . ' ' . $earnings_12mo_ago );

            $data['earnings']['detail'] = array( 
                'sixmoago' => array( 
                    'total' => $earnings_6mo_ago,
                    'compare' => self::get_percentage( $earnings, $earnings_6mo_ago )
                    ),
                'twelvemoago' => array( 
                    'total' => $earnings_12mo_ago,
                    'compare' => self::get_percentage( $earnings, $earnings_12mo_ago )
                    ),
                );

            return $data;
            
        }


    }

    $edd_metrics_class = new EDD_Metrics_Detail();
    $edd_metrics_class->instance();

} // end class_exists check