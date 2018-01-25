<?php
/**
 *
 * @package     EDD\EDD Metrics
 * @since       0.2.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'EDD_Metrics_Functions' ) ) {

    /**
     * EDD_Metrics_Functions class
     *
     * @since       0.2.0
     */
    class EDD_Metrics_Functions {

        /**
         * @var         EDD_Metrics_Functions $instance The one true EDD_Metrics_Functions
         * @since       0.2.0
         */
        private static $instance;
        public static $end = null;
        public static $start = null;
        public static $endstr = null;
        public static $startstr = null;
        public static $errorpath = '../php-error-log.php';
        // sample: error_log("meta: " . $meta . "\r\n",3,self::$errorpath);

        /**
         * Get active instance
         *
         * @access      public
         * @since       0.2.0
         * @return      object self::$instance The one true EDD_Metrics_Functions
         */
        public static function instance() {
            if( !self::$instance ) {
                self::$instance = new EDD_Metrics_Functions();
                self::$instance->hooks();
            }

            self::$endstr = "now";
            self::$startstr = "-30 days";
            self::$end = strtotime( self::$endstr );
            self::$start = strtotime( self::$startstr );

            return self::$instance;
        }


        /**
         * Include necessary files
         *
         * @access      private
         * @since       0.2.0
         * @return      void
         */
        private function hooks() {

            add_action( 'edd_metrics_dash_sidebar', array( $this, 'do_sidebar' ) );

            add_action( 'wp_ajax_metrics_batch_1', array( $this, 'metrics_batch_1' ), 10, 2 );

            add_action( 'wp_ajax_metrics_batch_2', array( $this, 'metrics_batch_2' ), 10 );



            // add_action('init', function() {
                
            //     var_dump( self::metrics_batch_2() );
            //     exit;
            // });


        }

        /**
         * Change date and reload everything, called via ajax. Echoes json string.
         *
         * @access      public
         * @since       0.2.0
         * @return      void
         */
        public static function metrics_batch_1( $start = '', $end = '' ) {
            self::$endstr = $_POST['end'];
            self::$startstr = $_POST['start'];
            self::$end = strtotime( self::$endstr );
            self::$start = strtotime( self::$startstr );

            $date_hash = hash('md5', self::$startstr . self::$endstr );

            $metrics = get_transient( 'metrics1_' . $date_hash );

            if ( false === $metrics ) {

                $dates = self::get_compare_dates();

                $metrics = array(
                    'dates' => $dates,
                    'sales' => self::get_sales(), 
                    'earnings' => self::get_earnings(),
                );

                if( $dates['num_days'] < 100 ) {
                    $chart_data = self::get_chart_data( $dates );
                    $metrics['lineChart'] = $chart_data;
                }

                set_transient( 'metrics1_' . $date_hash, $metrics, HOUR_IN_SECONDS );

            }

            echo json_encode( $metrics );

            exit;
        }

        /**
         * Ajax call
         *
         * @access      public
         * @since       0.2.0
         * @return      void
         */
        public static function metrics_batch_2() {
            self::$endstr = $_POST['end'];
            self::$startstr = $_POST['start'];
            self::$end = strtotime( self::$endstr );
            self::$start = strtotime( self::$startstr );

            $date_hash = hash('md5', self::$startstr . self::$endstr );

            $metrics = get_transient( 'metrics2_' . $date_hash );

            if ( false === $metrics ) {

                $discounts = self::get_discounts( self::$startstr, self::$endstr );

                $dates = self::get_compare_dates();

                $recurring_revenue = self::get_subscription_revenue( self::$start, self::$end );
                $prev_recurring_rev = self::get_subscription_revenue( strtotime( $dates['previous_start'] ), strtotime( $dates['previous_end'] ) );

                $earnings_30 = self::get_subscription_revenue( strtotime( 'now' ), strtotime( '+ 30 days' ) );

                $metrics = array(
                    'dates' => $dates,
                    'renewals' => self::get_renewals( self::$start, self::$end ),
                    'subscriptions' => array( 
                        'number' => self::get_subscriptions( self::$startstr, self::$endstr ),
                        'earnings' => array(
                            'total' => edd_currency_filter( edd_format_amount( edd_sanitize_amount( $recurring_revenue ) ) ),
                            'compare' => self::subscription_compare( $recurring_revenue, $prev_recurring_rev),
                            ),
                        'earnings30' => edd_currency_filter( edd_format_amount( edd_sanitize_amount( $earnings_30 ) ) )
                        ),
                    'discounts' => array( 
                        'now' => $discounts, 
                        'compare' => self::compare_discounts( $discounts['amount'] ),
                    ),
                    'commissions' => self::get_commissions( self::$start, self::$end )
                );

                $metrics = apply_filters( 'metrics_json_output', $metrics );

                set_transient( 'metrics2_' . $date_hash, $metrics, HOUR_IN_SECONDS );

            }

            echo json_encode( $metrics );

            exit;
        }

        /**
         * Get comparison of current and previous recurring revenue
         *
         * @access      public
         * @since       0.5.2
         * @return      array()
         */
        public static function subscription_compare( $rev, $prev_rev) {

            if( empty( $rev ) || empty ( $prev_rev ) ) {
                return array( 
                    'classes' => '', 
                    'percentage' => '-' 
                );
            }

            return array( 
                'classes' => self::get_arrow_classes( $rev, $prev_rev ), 
                'percentage' => self::get_percentage( $rev, $prev_rev )
            );

        }

        /**
         * Return earnings
         *
         * @access      public
         * @since       0.2.0
         * @return      array()
         */
        public static function get_earnings() {

            $dates = self::get_compare_dates();

            // Get current and previous period earnings
            $earnings = self::get_net_revenue( strtotime( $dates['start'] ), strtotime( $dates['end'] ) );
            $previous_earnings = self::get_net_revenue( strtotime( $dates['previous_start'] ), strtotime( $dates['previous_end'] ) );

            // output classes for arrows and colors
            $classes = self::get_arrow_classes( $earnings, $previous_earnings );

            $refunds = self::get_refunds();

            return array( 
                'total' => edd_currency_filter( edd_format_amount( $earnings ) ),
                'compare' => array( 
                    'classes' => $classes, 
                    'percentage' => self::get_percentage( $earnings, $previous_earnings ),
                    'total' => edd_currency_filter( edd_format_amount( $previous_earnings ) ), 
                    ), 
                //'avgyearly' => self::get_avg_yearly( $earnings, $previous_earnings, $dates['num_days'] ), 
                'avgpercust' => self::get_avg_percust( $earnings, $previous_earnings ),
                'refunds' => $refunds,
                'avgmonthly' => self::get_avg_monthly()
                );

        }

        /**
         * Get average revenue per customer. Earnings/Customers
         *
         * @access      public
         * @since       0.2.0
         * @return      array()
         */
        public static function get_avg_percust( $earnings = null, $previous_earnings = null ) {

            $dates = self::get_compare_dates();

            $current_customers = self::get_edd_customers_by_date( $dates['start'], $dates['end'] );
            $previous_customers = self::get_edd_customers_by_date( $dates['previous_start'], $dates['previous_end'] );

            if( empty( $earnings ) || empty( $current_customers ) ) {
                // can't divide by 0
                $total = 0;
            } else {
                $total = $earnings/$current_customers;
            }

            if( empty( $previous_earnings ) || empty( $previous_customers ) ) {
                // can't divide by 0
                $prev_total = 0;
            } else {
                $prev_total = $previous_earnings/$previous_customers;
            }

            // output classes for arrows and colors
            $classes = self::get_arrow_classes( $total, $prev_total );

            return array( 
                'total' => edd_currency_filter( edd_format_amount( $total ) ),
                'compare' => array( 
                    'classes' => $classes, 
                    'percentage' => self::get_percentage( $total, $prev_total ) 
                    ),
                'current_customers' => $current_customers
                );
        }

        /**
         * Use EDD_DB_Customers class to get customer count
         * https://github.com/easydigitaldownloads/easy-digital-downloads/blob/master/includes/class-edd-db-customers.php#L523
         *
         * @access      public
         * @since       0.2.0
         * @return      array()
         */
        public static function get_edd_customers_by_date( $start, $end ) {

            $EDD_DB_Customers = new EDD_DB_Customers();

            $args = array( 
                'date' => array( 
                    'start' => $start, 
                    'end' => $end 
                    )
                );

            $customers = $EDD_DB_Customers->count( $args );

            return $customers;
        }

        /**
         * Get average monthly estimates
         * see edd/includes/admin/reporting/reports.php line 486
         *
         * @access      public
         * @since       0.2.0
         * @return      array()
         */
        public static function get_avg_monthly() {
            $avg = edd_estimated_monthly_stats();
            return array(
                'earnings' => edd_currency_filter( edd_format_amount( $avg['earnings'] ) ),
                'sales' => $avg['sales']
                );
        }

        /**
         * Get average yearly estimates
         *
         * @access      public
         * @since       0.2.0
         * @return      array()
         */
        public static function get_avg_yearly( $earnings = null, $previous_earnings = null, $num_days = null ) {

            // Fix division by 0 errors
            if( !$earnings && !$previous_earnings ) {
                return array( 'total' => 0, 'compare' => array( 'classes' => 'metrics-nochange', 'percentage' => 0 ) );
            } else if( empty( $earnings ) ) {
                $earnings = 1;
            } else if( empty( $previous_earnings ) ) {
                $previous_earnings = 1;
            }

            // Yearly estimate - avg rev per day in set time period, averaged out over 365 days. So $287/day in the last 30 days would be $287*365
        $comp_dates = self::get_compare_dates();
        $num_days = $comp_dates['num_days'];        

            $avgyearly = ( $earnings/$num_days )*365;
            $previous_avgyearly = ( $previous_earnings/$num_days )*365;

            // output classes for arrows and colors
            $classes = self::get_arrow_classes( $avgyearly, $previous_avgyearly );

            return array( 'total' => edd_currency_filter( edd_format_amount( $avgyearly ) ), 'compare' => array( 'classes' => $classes, 'percentage' => self::get_percentage( $avgyearly, $previous_avgyearly ) ) );

        }

        /**
         * Return sales
         *
         * @access      public
         * @since       0.2.0
         * @return      array()
         */
        public static function get_sales() {

            $dates = self::get_compare_dates();
 
            // Get current and previous period sales
            $EDD_Stats = new EDD_Payment_Stats();
            $sales = $EDD_Stats->get_sales( 0, $dates['start'], $dates['end'] );
            $previous_sales = $EDD_Stats->get_sales( 0, $dates['previous_start'], $dates['previous_end'] );

            // output classes for arrows and colors
            $classes = self::get_arrow_classes( $sales, $previous_sales );

            return array( 'count' => $sales, 'previous' => $previous_sales, 'compare' => array( 'classes' => $classes, 'percentage' => self::get_percentage( $sales, $previous_sales ) ) );

        }

        /**
         * Get start and end dates for compare periods
         *
         * @access      public
         * @since       0.2.0
         * @return      array()
         */
        public static function get_compare_dates() {
            // current period
            $start = date("jS F Y", self::$start );
            $end = date("jS F Y", self::$end );

            $datediff = self::$end - self::$start;
            $num_days = floor( $datediff/( 60*60*24 ) ) + 1;

            $prev = self::subtract_days( $start, $end, $num_days );

            return array( 'start' => $start, 'end' => $end, 'previous_start' => $prev[0], 'previous_end' => $prev[1], 'num_days' => $num_days );
        }

        /**
         * Helper function to subtract days from 2 dates, for getting compare periods
         *
         * @access      public
         * @since       0.2.0
         * @return      array()
         */
        public static function subtract_days( $start = null, $end = null, $num_days = null ) {

            // Switch to datetime format, subtract time, then back to string
            $startdate = date_create( $start );
            $enddate = date_create( $end );

            // subtract number of days
            $previous_start = date_sub( $startdate, date_interval_create_from_date_string( $num_days . " days" ) );
            $previous_end = date_sub( $enddate, date_interval_create_from_date_string( $num_days . " days" ) );

            // previous period
            $previous_start = $previous_start->format('jS F Y');
            $previous_end = $previous_end->format('jS F Y');

            return array( $previous_start, $previous_end );
        }

        /**
         * Get a percentage based on 2 numbers
         *
         * @access      public
         * @since       0.2.0
         * @return      integer
         */
        public static function percent_change($new_val, $old_val) {

        $new_val = edd_sanitize_amount( $new_val );
            $old_val = edd_sanitize_amount( $old_val );
        
            if( empty( $old_val ) || $old_val === 0 )
                return 0;

            // Commas break this equation
            $new_val = str_replace( ',', '', $new_val );
            $old_val = str_replace( ',', '', $old_val );

            return ( ( $new_val - $old_val ) / $old_val ) * 100;
        }

        /**
         * Helper method to prevent division by zero errors
         *
         * @access      public
         * @since       0.2.0
         * @return      integer
         */
        public static function get_percentage( $current_value = null, $prev_value = null ) {

            // avoid division by 0 errors
            if( empty( $prev_value ) && $current_value > 0 ) {

                // can't calculate percentage increase from zero?
                //return round( $current_value * 100, 1 );
                return '-';

            } else if ( $prev_value > 0 && empty( $current_value ) ) {

                // return round( $prev_value * 100, 1 );
                return '-';

            } else if ( empty( $current_value ) && empty( $prev_value ) ) {

                return '0';

            } else {

                return round( self::percent_change( $current_value, $prev_value ), 1 );
                
            }

        }

        /**
         * Return the classes we need for arrows
         *
         * @access      public
         * @since       0.2.0
         * @return      string
         */
        public static function get_arrow_classes( $current = null, $previous = null ) {

            // output classes for arrows and colors
            if( intval( $previous ) > intval( $current ) ) {
                return 'metrics-negative metrics-downarrow';
            } else if( intval( $previous ) < intval( $current ) ) {
                return 'metrics-positive metrics-uparrow';
            } else {
                return 'metrics-nochange';
            }

        }

        /**
         * Add metrics sidebar
         *
         * @access      public
         * @since       0.2.0
         * @return      void
         */
        public static function do_sidebar() {

            $edd_payment = get_post_type_object( 'edd_payment' );

            $args = array(
                'post_type' => 'edd_payment',
                // 'post_status' => array( 'publish' )
            );

            // The Query
            $the_query = new WP_Query( $args );

            ?>

            <div class="postbox metrics-sidebar">
                <h2 class="hndle ui-sortable-handle"><span><?php _e('Recent Activity', 'edd-metrics'); ?></span></h2>
                <div class="inside">
                    <ul>
                    <?php
                        // Recent payments loop
                        if ( $the_query->have_posts() ) {
                            while ( $the_query->have_posts() ) {
                                $the_query->the_post();

                                $status = get_post_status( get_the_ID() );

                                switch ( $status ) {
                                    case 'publish':
                                        $classes = 'metrics-positive';
                                        $status = 'completed';
                                        break;
                                    case 'refunded':
                                        $classes = 'metrics-negative';
                                        break;
                                    case 'revoked':
                                        $classes = 'metrics-negative';
                                        break;
                                    case 'failed':
                                        $classes = 'metrics-negative';
                                        break;
                                    
                                    default:
                                        $classes = 'metrics-nochange';
                                        break;
                                }

                                $status = ucfirst( $status );

                                $total = get_post_meta( get_the_ID(), '_edd_payment_total' )[0];
                                echo '<li><a href="' . admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id=' . get_the_ID() ) . '"><span class="' . $classes . '"><span class="status-wrap">' . $status . '</span> ' . edd_currency_filter( edd_format_amount( $total ) ) . '</span> ' . get_the_title() . '</a></li>';
                            }
                            wp_reset_postdata();
                        } else {
                            echo '<li>' . _e('No Payments Found', 'edd-metrics') . '</li>';
                        }
                    ?>
                    </ul>
                </div>
            </div>

            <?php
        }

        /**
         * Get renewal count and earnings and return
         * $start & $end should be date objects
         *
         * @access      public
         * @since       0.2.0
         * @return      array( 'count' => $count, 'earnings' => $earnings )
         */
        public static function get_renewals_by_date( $start = null, $end = null ) {

            // see reports.php in EDD SL plugin
            // edd_sl_get_renewals_by_date( $day = null, $month = null, $year = null, $hour = null  )

            $count = 0;
            $earnings = 0;

            // Loop between timestamps, 24 hours at a time
            for ( $i = $start; $i <= $end; $i = $i + 86400 ) {
                $renewals = edd_sl_get_renewals_by_date( date( 'd', $i ), date( 'm', $i ), date( 'Y', $i ) );
                if( $renewals['count'] === 0 )
                    continue;
                $count++;
                $earnings += $renewals['earnings'];
            }

            if( empty($count) )
                $count = '0';

            return array( 
                'count' => $count, 
                'earnings' => edd_currency_filter( edd_format_amount( $earnings ) ),
                );
        }

        /**
         * Get renewal count and earnings and return
         * $start & $end should be date objects
         *
         * @access      public
         * @since       0.2.0
         * @return      array( 'count' => $count, 'earnings' => $earnings )
         */
        public static function get_renewals( $start = null, $end = null ) {

            if( !class_exists('EDD_Software_Licensing') ) {
                return array( 'count' => '0', 'earnings' => '0', 'compare' => array( 'classes' => 'edd-metrics-nochange', 'percentage' => '0' ) );
            }

            $renewals = self::get_renewals_by_date( $start, $end );

            $renewals['compare'] = self::compare_renewals( $renewals['count'] ); 

            return $renewals;
                    
        }

        /**
         * Compare renewals
         *
         * @access      public
         * @since       0.2.0
         * @return      array()
         */
        public static function compare_renewals( $current_renewals = null ) {

            $dates = self::get_compare_dates();

            $start = strtotime( $dates['previous_start'] );
            $end = strtotime( $dates['previous_end'] );
            
            $previous_renewals = self::get_renewals_by_date( $start, $end );

            // output classes for arrows and colors
            $classes = self::get_arrow_classes( $current_renewals, $previous_renewals['count'] );

            return array( 'classes' => $classes, 'percentage' => self::get_percentage( $current_renewals, $previous_renewals['count'] ) );
        }

        /**
         * Get commissions count and earnings and return
         * $start & $end should be date objects
         *
         * @access      public
         * @since       0.4.0
         * @return      array( 'count' => $count, 'earnings' => $earnings )
         */
        public static function get_commissions( $start = null, $end = null ) {

            if( !defined('EDD_COMMISSIONS_VERSION') ) {
                return array( 'count' => '0', 'earnings' => '0', 'compare' => array( 'classes' => 'edd-metrics-nochange', 'percentage' => '0' ) );
            }

            $commissions['count'] = count( eddc_get_unpaid_commissions( $args = array() ) );
            $commissions['earnings'] = edd_currency_filter( edd_format_amount( eddc_get_unpaid_totals( 0 ) ) );

            return $commissions;

        }

        /**
         * Query for discounts
         *
         * @access      public
         * @since       0.2.0
         * @return      
         */
        public static function get_discounts( $start = string, $end = string ) {

            $args = array(
                'post_type' => 'edd_payment',
                'nopaging' => true,
                'post_status' => array( 'publish' ),
                'date_query' => array(
                    array(
                        'after'     => $start,
                        'before'    => $end,
                        'inclusive' => true,
                    ),
                ),
            );

            $amount = 0;
            $count = 0;

            // The Query
            $the_query = new WP_Query( $args );

            if ( $the_query->have_posts() ) {
                while ( $the_query->have_posts() ) {
                    $the_query->the_post();


                    // get discount amount from post meta
                    $discount_amount = get_post_meta( get_the_ID(), '_edd_payment_meta' )[0]['cart_details'][0]['discount'];

                    if( !empty( $discount_amount ) ) {

                        $amount += $discount_amount;
                        $count++;
                    }
                    
                }
                wp_reset_postdata();
            } else {
                return array( 'amount' => 0, 'count' => 0 );
            }

            return array( 'amount' => edd_currency_filter( edd_format_amount( $amount ) ), 'count' => $count
                );
        }

        /**
         * Discount compare. Compared by amounts, not count.
         *
         * @access      public
         * @since       0.2.0
         * @return      array()    
         */
        public static function compare_discounts( $current_discounts_amount = null ) {

            $dates = self::get_compare_dates();

            $previous_discounts = self::get_discounts( $dates['previous_start'], $dates['previous_end'] );

            // output classes for arrows and colors
            $classes = self::get_arrow_classes( $current_discounts_amount, $previous_discounts['amount'] );

            return array( 'classes' => $classes, 'percentage' => self::get_percentage( $current_discounts_amount, $previous_discounts['amount'] ) );
        }

        /**
         * Query for refunds
         *
         * @access      public
         * @since       0.2.0
         * @return      array( 'count' => $count, 'earnings' => $earnings )
         */
        public static function refund_query( $start, $end ) {

            $args = array(
                'post_type' => 'edd_payment',
                'nopaging' => true,
                'post_status' => array( 'refunded' ),
                'date_query' => array(
                    array(
                        'after'     => $start,
                        'before'    => $end,
                        'inclusive' => true,
                    ),
                ),
            );

            $earnings = 0;

            // The Query
            $the_query = new WP_Query( $args );

            if ( $the_query->have_posts() ) {
                $refunds = 0;
                while ( $the_query->have_posts() ) {
                    $the_query->the_post();
                    $earnings += get_post_meta( get_the_ID(), '_edd_payment_total' )[0];
                    $refunds++;
                }
                wp_reset_postdata();
            } else {
                return array( 'count' => 0, 'earnings' => 0 );
            }

            return array( 'count' => $refunds, 'earnings' => $earnings );
        }

        /**
         * Get refund count and losses and return
         *
         * @access      public
         * @since       0.2.0
         * @return      array( 'count' => $count, 'losses' => $losses )
         */
        public static function get_refunds() {

            $refunds = self::refund_query( self::$startstr, self::$endstr );

            if( $refunds['count'] == 0 && $refunds['earnings'] == 0 ) {
                $compare = array( 'classes' => 'metrics-nochange', 'percentage' => 0 );
            } else {
                $compare = self::compare_refunds( $refunds['count'] );
            }

            return array( 'count' => $refunds['count'], 'losses' => edd_currency_filter( edd_format_amount( $refunds['earnings'] ) ), 'compare' => $compare, 'integer' => $refunds['earnings'] );
        }

        /**
         * Compare refunds
         *
         * @access      public
         * @since       0.2.0
         * @return      array()
         */
        public static function compare_refunds( $current_refunds = null ) {

            $dates = self::get_compare_dates();

            $previous_refunds = self::refund_query( $dates['previous_start'], $dates['previous_end'] );

            // output classes for arrows and colors
            $classes = self::get_arrow_classes( $current_refunds, $previous_refunds['count'] );

            return array( 'classes' => $classes, 'percentage' => self::get_percentage( $current_refunds, $previous_refunds['count'] ) );
        }

        /**
         * Get subscriptions using EDD_Subscriptions_DB class 
         * taken from edd-recurring/includes/admin/class-subscriptions-list-table.php
         * $start and $end must be strings, not strtotime or date objects
         * @access      public
         * @since       0.2.0
         * @return      array()
         */
        public static function subscriptions_db( $start = string, $end = string ) {

            if( !class_exists('EDD_Subscriptions_DB') ) {
                return 0;
            }

            global $wp_query;

            $db = new EDD_Subscriptions_DB;

            // $total is an array of objects. To get more info, use $total[$key]->whatever
            $total = $db->count( 
                array( 
                    'status' => 'active', 
                    'date' => array( 
                        'start' => $start, 
                        'end' => $end
                    ) 
                )
            );

            return $total;

        }

        /**
         * Get subscriptions
         * taken from edd-recurring/includes/admin/class-subscriptions-list-table.php
         * $start and $end must be strings, not strtotime or date objects
         * @access      public
         * @since       0.2.0
         * @return      array()
         */
        public static function get_subscriptions( $start = string, $end = string ) {

            $total = self::subscriptions_db( $start, $end );

            return array( 'count' => $total, 'compare' => self::compare_subscriptions( $total ) );

        }

        /**
         * Compare subscriptions
         * @access      public
         * @since       0.2.0
         * @return      array()
         */
        public static function compare_subscriptions( $current_subscriptions = null ) {

            $dates = self::get_compare_dates();

            $previous_subscriptions = self::subscriptions_db( $dates['previous_start'], $dates['previous_end'] );

            // output classes for arrows and colors
            $classes = self::get_arrow_classes( $current_subscriptions, $previous_subscriptions );

            return array( 'previous_count' => $previous_subscriptions, 'classes' => $classes, 'percentage' => self::get_percentage( $current_subscriptions, $previous_subscriptions ) );
        }

        /**
         * Retrieve estimated revenue for the number of days given
         * Copied directly from edd-recurring/includes/admin/class-summary-widget.php and modified for my evil purposes muhahaha
         *
         * @since  2.4.15
         * @return float
         */
        public static function get_subscription_revenue( $start, $end ) {

            if( !class_exists('EDD_Recurring') )
                return '0';

            global $wpdb;

            if( !empty( $start ) ) {

                $start   = date( 'Y-n-d H:i:s', $start );
                $end    = date( 'Y-n-d H:i:s', $end );
                $amount = $wpdb->get_var( "SELECT sum(recurring_amount) FROM {$wpdb->prefix}edd_subscriptions WHERE expiration >= '$start' AND expiration <= '$end' AND status IN( 'active', 'trialling' );" );

            }

            return $amount;
        }

        /**
         * Get data for chart
         *
         * @access      public
         * @since       0.2.0
         * @return      array
         */
        public static function get_chart_data( $dates = null, $monthly = false ) {

            $EDD_Stats = new EDD_Payment_Stats();

            // Loop through each day between two dates, and get totals
            $begin = new DateTime( $dates['start'] );
            $end = new DateTime( $dates['end'] );
            
            $interval = DateInterval::createFromDateString('1 day');
            $period = new DatePeriod($begin, $interval, $end);

            foreach ( $period as $dt ) {
              $earnings[] = $EDD_Stats->get_earnings( 0, $dt->format( "F j, Y" ), false );
              $labels[] = $dt->format( "F j" );
            }

            foreach ( $period as $dt ) {
              $sales[] = $EDD_Stats->get_sales( 0, $dt->format( "F j, Y" ), false, array( 'publish', 'revoked' ) );
            }

            return array( 'sales' => $sales, 'earnings' => $earnings, 'labels' => $labels );

        }

        /*
         * Net revenue is total revenue minus refunds. Have to go back 3 months because EDD counts refunds by purchase date, not refund date. So some refunds are not counted in refund query.
         * $start and $end should be time strings
         */
        public static function get_net_revenue( $start, $end ) {

            $twomoago = date( "jS F Y", strtotime( "-2 months", $start ) );
            
            $EDD_Stats = new EDD_Payment_Stats();
            $earnings_then = $EDD_Stats->get_earnings( 0, $twomoago, date( "jS F Y", $start ) );

            $earnings_uptonow = $EDD_Stats->get_earnings( 0, $twomoago, date( "jS F Y", $end ) );

            $net_revenue = $earnings_uptonow - $earnings_then;

            return $net_revenue;

        }


    }

    $edd_metrics_class = new EDD_Metrics_Functions();
    $edd_metrics_class->instance();

} // end class_exists check
