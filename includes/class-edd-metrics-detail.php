<?php
/**
 * Handles metrics for revenue details page
 *
 * @package     EDD\EDD Metrics\Functions
 * @since       0.2.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'EDD_Metrics_Detail' ) ) {

    /**
     * EDD_Metrics_Detail class
     *
     * @since       0.2.0
     */
    class EDD_Metrics_Detail extends EDD_Metrics_Functions {

        /**
         * @var         EDD_Metrics_Detail $instance The one true EDD_Metrics_Detail
         * @since       0.2.0
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
         * @since       0.2.0
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
         * @since       0.2.0
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
         * @since       0.2.0
         * @return      array
         */
        public static function revenue_callback( $data ) {

            $dates = self::get_compare_dates();

            // Get current and previous period earnings
            $EDD_Stats = new EDD_Payment_Stats();
            $earnings = $EDD_Stats->get_earnings( 0, $dates['start'], $dates['end'] );

            $earnings_today = $EDD_Stats->get_earnings( 0, 'today' );
            $earnings_this_month = $EDD_Stats->get_earnings( 0, 'this_month' );

            $six_mo_ago = self::subtract_days( $dates['start'], $dates['end'], 182 );
            $earnings_6mo_ago = $EDD_Stats->get_earnings( 0, strtotime( $six_mo_ago[0] ), strtotime( $six_mo_ago[1] ) );

            $twelve_mo_ago = self::subtract_days( $dates['start'], $dates['end'], 365 );
            $earnings_12mo_ago = $EDD_Stats->get_earnings( 0, strtotime(  $twelve_mo_ago[0] ), strtotime( $twelve_mo_ago[1] ) );

            // for yearly renewal rate
            $sales_12mo_ago = $EDD_Stats->get_sales( 0, strtotime(  $twelve_mo_ago[0] ), strtotime( $twelve_mo_ago[1] ) );

            $data['earnings']['gateways'] = self::get_edd_gateway_reports();

            $classes6mo = self::get_arrow_classes( $earnings, $earnings_6mo_ago );
            $classes12mo = self::get_arrow_classes( $earnings, $earnings_12mo_ago );

            $data['yearly_renewal_rate'] = self::get_yearly_renewal_rate();

            $data['earnings']['detail'] = array( 
                'today' => edd_currency_filter( edd_format_amount( $earnings_today ) ),
                'this_month' => edd_currency_filter( edd_format_amount( $earnings_this_month ) ),
                'sixmoago' => array( 
                    'total' => edd_currency_filter( edd_format_amount( $earnings_6mo_ago ) ),
                    'compare' => self::get_percentage( $earnings, $earnings_6mo_ago ),
                    'classes' => $classes6mo
                    ),
                'twelvemoago' => array( 
                    'total' => edd_currency_filter( edd_format_amount( $earnings_12mo_ago ) ),
                    'compare' => self::get_percentage( $earnings, $earnings_12mo_ago ),
                    'classes' => $classes12mo
                    ),
                );

            return $data;
            
        }

        /**
         * Get yearly renewal rate. Only reliable way to do this is to use a really long time period, because people don't always renew exactly 12 months after they purchase. 
         * $period is how far back we go in days, if set to 365, we get sales from 24mo ago -> 12mo ago, and renewals from 12mo ago -> now.
         * Calculation is (renewals last 12 mo) / (sales count from 24mo ago -> 12 mo ago)
         *
         * @access      public
         * @since       0.2.0
         * @return      array
         */
        public static function get_yearly_renewal_rate( $period = 365 ) {

            // renewals last 12 mo / sales total from 24mo ago -> 12 mo ago
            $now = strtotime('now');
            $period_ago = strtotime( '-' . strval( $period ) . ' days' );
            $two_periods_ago = strtotime( '-' . strval( $period*2 ) . ' days' );

            $renewals = self::get_renewals( $period_ago, $now )['count'];

            $EDD_Stats = new EDD_Payment_Stats();

            $sales = $EDD_Stats->get_sales( 0, date("jS F Y", $two_periods_ago ), date("jS F Y", $period_ago ) );

            if( empty( $renewals) || empty( $sales ) ) {
                return array( 'percent' => 0, 'period' => $period );
            }

            $percent = ( intval($renewals) / intval($sales) ) * 100;

            return array( 'percent' => edd_format_amount( $percent ), 'period' => $period );
        }

        /**
         * Get earnings for each product individually
         *
         * @access      public
         * @since       0.2.0
         * @return      array
         */
        public static function get_single_product_detail( $data ) {

            $dates = self::get_compare_dates();

            // Get current and previous period earnings
            $EDD_Stats = new EDD_Payment_Stats();

            $args = array(
                'post_type'      => 'download',
                'posts_per_page' => -1,
            );

            // The Query
            $the_query = new WP_Query( $args );

            if ( $the_query->have_posts() ) {
                while ( $the_query->have_posts() ) {
                    $the_query->the_post();

                    $title = get_the_title();
                    $product_earnings = $EDD_Stats->get_earnings( get_the_id(), $dates['start'], $dates['end'] );

                    $labels[] = html_entity_decode( $title );
                    $earnings[] = $product_earnings;

                }
                wp_reset_postdata();
            } else {
                $data['pieChart'] = array( 'labels' => array( 'No data' ), 'earnings' => array( '0' ) );
                return $data;
            }

            $chart_data = array( 'labels' => $labels, 'earnings' => $earnings );

            $data['pieChart'] = $chart_data;

            return $data;

        }

        /**
         * Get gateway info
         * For more methods see edd/includes/admin/reporting/class-gateways-reports-table.php ->reports_data()
         *
         * @access      public
         * @since       0.2.0
         * @return      array()
         */
        public static function get_edd_gateway_reports() {
            
            $reports_data = array();
            $gateways     = edd_get_enabled_payment_gateways();

            foreach ( $gateways as $gateway_id => $gateway ) {

                $complete_count = edd_count_sales_by_gateway( $gateway_id, 'publish' );

                $reports_data['labels'][] = $gateway['admin_label'];
                $reports_data['earnings'][] = $complete_count;

            }

            return $reports_data;

        }


    }

    $edd_metrics_class = new EDD_Metrics_Detail();
    $edd_metrics_class->instance();

} // end class_exists check