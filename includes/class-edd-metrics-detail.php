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
            add_filter( 'metrics_json_output', array( $this, 'get_single_product_detail' ) );

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

            // for yearly renewal rate
            $sales_12mo_ago = $EDD_Stats->get_sales( 0, strtotime(  $twelve_mo_ago[0] ), strtotime( $twelve_mo_ago[1] ) );

            if( $dates['num_days'] < 100 ) {
                $chart_data = self::get_chart_data( $dates, $monthly );
                $data['lineChart'] = $chart_data;
            }

            $classes6mo = self::get_arrow_classes( $earnings, $earnings_6mo_ago );
            $classes12mo = self::get_arrow_classes( $earnings, $earnings_12mo_ago );

            $data['yearly_renewal_rate'] = self::get_yearly_renewal_rate();

            $data['earnings']['detail'] = array( 
                'sixmoago' => array( 
                    'total' => number_format( $earnings_6mo_ago, 2 ),
                    'compare' => self::get_percentage( $earnings, $earnings_6mo_ago ),
                    'classes' => $classes6mo
                    ),
                'twelvemoago' => array( 
                    'total' => number_format( $earnings_12mo_ago, 2 ),
                    'compare' => self::get_percentage( $earnings, $earnings_12mo_ago ),
                    'classes' => $classes12mo
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
        public function get_chart_data( $dates = null, $monthly = false ) {

            $EDD_Stats = new EDD_Payment_Stats();

            // Loop through each day between two dates, and get totals
            $begin = new DateTime( $dates['start'] );
            $end = new DateTime( $dates['end'] );
            
            $interval = DateInterval::createFromDateString('1 day');
            $period = new DatePeriod($begin, $interval, $end);

            foreach ( $period as $dt ) {
              $earnings[] = $EDD_Stats->get_earnings( 0, $dt->format( "jS F, Y" ), false );
              $labels[] = $dt->format( "F j" );
            }

            foreach ( $period as $dt ) {
              $sales[] = $EDD_Stats->get_sales( 0, $dt->format( "jS F, Y" ), false, array( 'publish', 'revoked' ) );
            }

            return array( 'sales' => $sales, 'earnings' => $earnings, 'labels' => $labels );

        }

        /**
         * Get yearly renewal rate. Only reliable way to do this is to use a really long time period, because people don't always renew exactly 12 months after they purchase. 
         * $period is how far back we go in days, if set to 1 year, we get sales from 24mo ago -> 12mo ago, and renewals from 12mo ago -> now.
         * Calculation is (renewals last 12 mo) / (sales count from 24mo ago -> 12 mo ago)
         *
         * @access      public
         * @since       1.0.0
         * @return      array
         */
        public function get_yearly_renewal_rate( $period = 365 ) {

            // renewals last 12 mo / sales total from 24mo ago -> 12 mo ago
            $now = strtotime('now');
            $period_ago = strtotime( '-' . strval( $period ) . ' days' );
            $two_periods_ago = strtotime( '-' . strval( $period*2 ) . ' days' );

            $renewals = self::get_renewals( $period_ago, $now );

            $EDD_Stats = new EDD_Payment_Stats();

            $sales = $EDD_Stats->get_sales( 0, $two_periods_ago, $period_ago );
            

            if( empty( $renewals) || empty( $sales ) ) {
                return array( 'percent' => 0, 'period' => '' );
            }

            $percent = ( intval($renewals) / intval($sales) ) * 100;

            return array( 'percent' => number_format( $percent, 2 ), 'period' => $period );
        }

        /**
         * Get earnings for each product individually
         *
         * @access      public
         * @since       1.0.0
         * @return      array
         */
        public function get_single_product_detail( $data ) {

            $dates = self::get_compare_dates();

            // Get current and previous period earnings
            $EDD_Stats = new EDD_Payment_Stats();

            $args = array(
                'post_type' => 'download',
            );

            // The Query
            $the_query = new WP_Query( $args );

            if ( $the_query->have_posts() ) {
                while ( $the_query->have_posts() ) {
                    $the_query->the_post();

                    $title = get_the_title();
                    $product_earnings = $EDD_Stats->get_earnings( get_the_id(), $dates['start'], $dates['end'] );

                    $labels[] = $title;
                    $earnings[] = $product_earnings;

                }
                wp_reset_postdata();
            } else {
                return $data;
            }

            $chart_data = array( 'labels' => $labels, 'earnings' => $earnings );

            $data['pieChart'] = $chart_data;

            return $data;

        }


    }

    $edd_metrics_class = new EDD_Metrics_Detail();
    $edd_metrics_class->instance();

} // end class_exists check