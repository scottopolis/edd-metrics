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

            add_action( 'edd_metrics_download_earnings', array( $this, 'get_single_product_detail') );

        }

        /**
         * Revenue stats for detail view
         *
         * @access      public
         * @since       1.0.0
         * @return      array
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

            $chart_data = self::get_chart_data( $dates );

            $data['chart'] = $chart_data;

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

        /**
         * Get data for chart
         *
         * @access      public
         * @since       1.0.0
         * @return      array
         */
        public function get_chart_data( $dates = null ) {

            $EDD_Stats = new EDD_Payment_Stats();

            // Loop through each day between two dates, and get totals
            $begin = new DateTime( $dates['start'] );
            $end = new DateTime( $dates['end'] );

            $interval = DateInterval::createFromDateString('1 day');
            $period = new DatePeriod($begin, $interval, $end);

            foreach ( $period as $dt ) {
              $earnings[] = $EDD_Stats->get_earnings( 0, $dt->format( "jS F, Y" ), false, array( 'publish', 'revoked' ) );
              $labels[] = $dt->format( "F j" );
            }

            foreach ( $period as $dt ) {
              $sales[] = $EDD_Stats->get_sales( 0, $dt->format( "jS F, Y" ), false, array( 'publish', 'revoked' ) );
            }

            return array( 'sales' => $sales, 'earnings' => $earnings, 'labels' => $labels );

        }

        /**
         * Get earnings for each product individually
         *
         * @access      public
         * @since       1.0.0
         * @return      array
         */
        public function get_single_product_detail() {

            $dates = self::get_compare_dates();

            // Get current and previous period earnings
            $EDD_Stats = new EDD_Payment_Stats();

            $args = array(
                'post_type' => 'download',
            );

            // The Query
            $the_query = new WP_Query( $args );

            ?>

            <div class="postbox metrics-sidebar">
                <h2 class="hndle ui-sortable-handle"><span><?php _e('Earnings By Product', 'edd-metrics'); ?></span></h2>
                <div class="inside">
                    <ul>

            <?php

            if ( $the_query->have_posts() ) {
                while ( $the_query->have_posts() ) {
                    $the_query->the_post();
                    // $downloads[] = array( 'id' => get_the_id(), 'title' => get_the_title() );

                    echo '<li>';

                    echo '<strong>' . get_the_title() . '</strong>';

                    echo '<span class="metrics-right">$' . $EDD_Stats->get_earnings( get_the_id(), $dates['start'], $dates['end'] ) . '</span>';

                    echo '</li>';

                }
                wp_reset_postdata();
            } else {
                echo '<li>' . _e('No Product Detail Found', 'edd-metrics') . '</li>';
            }

            ?>

                    </ul>
                </div>
            </div>

            <?php

            // foreach ($downloads as $key => $value) {
            //     // print_r( $value['id'] . ' ' );
            //     $earnings[$key][ $value['title'] ] = $EDD_Stats->get_earnings( $value['id'], $dates['start'], $dates['end'] );
            // }

            // return $earnings;

        }


    }

    $edd_metrics_class = new EDD_Metrics_Detail();
    $edd_metrics_class->instance();

} // end class_exists check