<?php
/**
 *
 * @package     EDD\EDD Metrics
 * @since       1.0.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'EDD_Metrics_Functions' ) ) {

    /**
     * EDD_Metrics_Functions class
     *
     * @since       1.0.0
     */
    class EDD_Metrics_Functions {

        /**
         * @var         EDD_Metrics_Functions $instance The one true EDD_Metrics_Functions
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
         * @since       1.0.0
         * @return      void
         */
        private function hooks() {

            add_action( 'edd_metrics_dash_sidebar', array( $this, 'do_sidebar' ) );

            add_action( 'wp_ajax_edd_metrics_change_date', array( $this, 'change_date' ) );

            // add_action( 'admin_enqueue_scripts', array( $this, 'localized_vars' ), 101 );

            // add_action('init', function() {
            //     self::get_new_subscriptions();
            // });

        }

        // not used
        public static function localized_vars() {
        	wp_localize_script( 'edd-metrics-js', 'eddMetrics', array(
	            //'some_string' => __( 'Some string to translate', 'edd-metrics' ),
	            'stats' => self::get_stats(),
	            'renewals' => self::get_renewals(),
	            'refunds' => self::get_refunds(),
	            )
	        );
        }

        /**
         * Change date and reload everything, called via ajax. Echoes json string.
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public static function change_date( $start, $end ) {
        	self::$endstr = $_POST['end'];
        	self::$startstr = $_POST['start'];
            self::$end = strtotime( self::$endstr );
        	self::$start = strtotime( self::$startstr );

            $date_hash = hash('md5', self::$startstr . self::$endstr );

            $metrics = get_transient( $date_hash );

            if ( false === $metrics ) {

                $dates = self::get_compare_dates();

                $metrics = array(
                    'dates' => $dates,
                    'sales' => self::get_sales(), 
                    'earnings' => self::get_earnings(),
                    'renewals' => self::get_renewals( self::$start, self::$end ),
                    'refunds' => self::get_refunds(),
                    'subscriptions' => self::get_subscriptions( self::$startstr, self::$endstr ),
                );

                $metrics = apply_filters( 'metrics_json_output', $metrics );

                $metrics['transient'] = 'false';

                set_transient( $date_hash, $metrics, 1 * HOUR_IN_SECONDS );

            } else {
                $metrics['transient'] = 'true';
            }

            echo json_encode( $metrics );

	        wp_die();
        }

        /**
         * Return earnings
         *
         * @access      public
         * @since       1.0.0
         * @return      array()
         */
        public static function get_earnings() {

        	$dates = self::get_compare_dates();

        	// Get current and previous period earnings
        	$EDD_Stats = new EDD_Payment_Stats();
        	$earnings = $EDD_Stats->get_earnings( 0, $dates['start'], $dates['end'] );
        	$previous_earnings = $EDD_Stats->get_earnings( 0, $dates['previous_start'], $dates['previous_end'] );

        	// output classes for arrows and colors
        	$classes = self::get_arrow_classes( $earnings, $previous_earnings );

        	return array( 
                'total' => number_format( $earnings, 2 ), 
                'compare' => array( 
                    'classes' => $classes, 
                    'percentage' => self::get_percentage( $earnings, $previous_earnings ),
                    'total' => number_format( $previous_earnings, 2 ) 
                    ), 
                'avgyearly' => self::get_avg_yearly( $earnings, $previous_earnings, $dates['num_days'] ), 
                'avgpercust' => self::get_avg_percust( $earnings, $previous_earnings ),
                'avgmonthly' => self::get_avg_monthly(),
                );

        }

        /**
         * Get average revenue per customer. Earnings/Customers
         *
         * @access      public
         * @since       1.0.0
         * @return      array()
         */
        public function get_avg_percust( $earnings = null, $previous_earnings = null ) {

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
                'total' => number_format( $total, 2 ),
                'compare' => array( 
                    'classes' => $classes, 
                    'percentage' => self::get_percentage( $total, $prev_total ) 
                    ) 
                );
        }

        /**
         * Use EDD_DB_Customers class to get customer count
         * https://github.com/easydigitaldownloads/easy-digital-downloads/blob/master/includes/class-edd-db-customers.php#L523
         *
         * @access      public
         * @since       1.0.0
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
         * @since       1.0.0
         * @return      array()
         */
        public function get_avg_monthly() {
            $avg = edd_estimated_monthly_stats();
            return array(
                'earnings' => number_format( $avg['earnings'], 2 ),
                'sales' => $avg['sales']
                );
        }

        /**
         * Get average yearly estimates
         *
         * @access      public
         * @since       1.0.0
         * @return      array()
         */
        public function get_avg_yearly( $earnings = null, $previous_earnings = null, $num_days = null ) {

        	// Fix division by 0 errors
        	if( !$earnings && !$previous_earnings ) {
        		return array( 'total' => 0, 'compare' => array( 'classes' => 'metrics-nochange', 'percentage' => 0 ) );
        	} else if( empty( $earnings ) ) {
        		$earnings = 1;
        	} else if( empty( $previous_earnings ) ) {
        		$previous_earnings = 1;
        	}

        	// Yearly estimate - avg rev per day in set time period, averaged out over 365 days. So $287/day in the last 30 days would be $287*365
			$num_days = self::get_compare_dates()['num_days'];

			$avgyearly = ( $earnings/$num_days )*365;
			$previous_avgyearly = ( $previous_earnings/$num_days )*365;

			// output classes for arrows and colors
        	$classes = self::get_arrow_classes( $avgyearly, $previous_avgyearly );

			return array( 'total' => number_format( $avgyearly, 2 ), 'compare' => array( 'classes' => $classes, 'percentage' => self::get_percentage( $avgyearly, $previous_avgyearly ) ) );

		}

        /**
         * Return sales
         *
         * @access      public
         * @since       1.0.0
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
         * Get previous number of customers and compare
         *
         * @access      public
         * @since       1.0.0
         * @return      array()
         */
        // public function compare_customers( $current_customers = null ) {

        //     $dates = self::get_compare_dates();

        //     $args = array(
        //         'date' => array(
        //             'start' => $dates['previous_start'],
        //             'end' => $dates['previous_end']
        //             )
        //         );

        //     $previous_customers = EDD()->customers->get_customers( $args );

        //     $i = 0;

        //     // count customers
        //     foreach ($previous_customers as $key => $value) {
        //         $i++;
        //     }

        //     // output classes for arrows and colors
        //     $classes = self::get_arrow_classes( $current_customers, $i );

        //     return array( 
        //         'classes' => $classes, 
        //         'percentage' => self::get_percentage( $current_customers, $i ),
        //         'count' => $i 
        //         );
        // }

        /**
         * Get start and end dates for compare periods
         *
         * @access      public
         * @since       1.0.0
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
         * @since       1.0.0
         * @return      array()
         */
        public function subtract_days( $start = null, $end = null, $num_days = null ) {

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
         * @since       1.0.0
         * @return      integer
         */
        public function percent_change($new_val, $old_val) {

            if( empty( $old_val ) )
                return 0;

		    return ( ( $new_val - $old_val ) / $old_val ) * 100;
		}

        /**
         * Helper method to prevent division by zero errors
         *
         * @access      public
         * @since       1.0.0
         * @return      integer
         */
        public function get_percentage( $current_value = null, $prev_value = null ) {

            // avoid division by 0 errors
            if( empty( $prev_value ) && $current_value > 0 ) {

                // can't calculate percentage increase from zero?
                //return round( $current_value * 100, 1 );
                return '-';

            } else if ( $prev_value > 0 && empty( $current_value ) ) {

                return round( $prev_value * 100, 1 );

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
         * @since       1.0.0
         * @return      string
         */
        public function get_arrow_classes( $current = null, $previous = null ) {

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
         * @since       1.0.0
         * @return      void
         */
        public static function do_sidebar() {

        	$edd_payment = get_post_type_object( 'edd_payment' );

        	$args = array(
				'post_type' => 'edd_payment',
                'post_status' => array( 'publish' )
			);

        	// The Query
			$the_query = new WP_Query( $args );

        	?>

        	<div class="postbox metrics-sidebar">
                <h2 class="hndle ui-sortable-handle"><span><?php _e('Recent Payments', 'edd-metrics'); ?></span></h2>
                <div class="inside">
                    <ul>
                    <?php
                    	// Recent payments loop
						if ( $the_query->have_posts() ) {
							while ( $the_query->have_posts() ) {
								$the_query->the_post();
								$total = get_post_meta( get_the_ID(), '_edd_payment_total' )[0];
								echo '<li><a href="' . admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id=' . get_the_ID() ) . '"><span class="metrics-positive">$' . $total . '</span> ' . get_the_title() . '</a></li>';
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
         * @since       1.0.0
         * @return      array( 'count' => $count, 'earnings' => $earnings )
         */
        public static function get_renewals( $start = null, $end = null, $compare = true ) {

            if( !class_exists('EDD_Software_Licensing') ) {
            	return array( 'count' => '0', 'earnings' => '0', 'compare' => array( 'classes' => 'edd-metrics-nochange', 'percentage' => '0' ) );
            }

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

            $ret = array( 
                'count' => $count, 
                'earnings' => number_format( $earnings, 2 )
                );

            if( $compare ) {
                $ret['compare'] = self::compare_renewals( $count ); 
            }

	        return $ret;
			        
        }

        /**
         * Compare renewals
         *
         * @access      public
         * @since       1.0.0
         * @return      array()
         */
        public static function compare_renewals( $current_renewals = null ) {

            $dates = self::get_compare_dates();

            $start = strtotime( $dates['previous_start'] );
            $end = strtotime( $dates['previous_end'] );
            $count = 0;
            $earnings = 0;

            // Loop between timestamps, 24 hours at a time
            for ( $i = $start; $i <= $end; $i = $i + 86400 ) {
                $previous_renewals = edd_sl_get_renewals_by_date( date( 'd', $i ), date( 'm', $i ), date( 'Y', $i ) );
                if( $previous_renewals['count'] === 0 )
                    continue;
                $count++;
                // $earnings = $previous_renewals['earnings'];
            }

            // output classes for arrows and colors
            $classes = self::get_arrow_classes( $current_renewals, $count );

            return array( 'classes' => $classes, 'percentage' => self::get_percentage( $current_renewals, $count ) );
        }

        /**
         * Query for refunds
         *
         * @access      public
         * @since       1.0.0
         * @return      array( 'count' => $count, 'earnings' => $earnings )
         */
        public function refund_query( $start, $end ) {

            $args = array(
                'post_type' => 'edd_payment',
                'nopaging' => true,
                'post_status' => array( 'refunded' ),
                'date_query' => array(
                    array(
                        'after'     => $start, // june 1
                        'before'    => $end, // june 30
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
	     * @since       1.0.0
	     * @return      array( 'count' => $count, 'losses' => $losses )
	     */
	    public static function get_refunds() {

        	$refunds = self::refund_query( self::$startstr, self::$endstr );

            if( $refunds['count'] == 0 && $refunds['earnings'] == 0 ) {
                $compare = array( 'classes' => 'metrics-nochange', 'percentage' => 0 );
            } else {
                $compare = self::compare_refunds( $refunds['count'] );
            }

			return array( 'count' => $refunds['count'], 'losses' => number_format( $refunds['earnings'], 2 ), 'compare' => $compare );
	    }

        /**
         * Compare refunds
         *
         * @access      public
         * @since       1.0.0
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
         * @since       1.0.0
         * @return      array()
         */
        public function subscriptions_db( $start = string, $end = string ) {

            if( !class_exists('EDD_Subscriptions_DB') ) {
                return null;
            }

            global $wp_query;

            $db = new EDD_Subscriptions_DB;

            // $total is an array of objects. To get more info, use $total[$key]->whatever
            $total = $db->get_subscriptions( 
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
         * @since       1.0.0
         * @return      array()
         */
        public function get_subscriptions( $start = string, $end = string ) {

            $total = self::subscriptions_db( $start, $end );

            $total_count = count($total);

            return array( 'count' => $total_count, 'compare' => self::compare_subscriptions( $total_count ) );

            // $total_count     = $db->count();
            // $active_count    = $db->count( array( 'status' => 'active' ) );
            // $pending_count   = $db->count( array( 'status' => 'pending', 'search' => $search ) );
            // $expired_count   = $db->count( array( 'status' => 'expired', 'search' => $search ) );
            // $cancelled_count = $db->count( array( 'status' => 'cancelled', 'search' => $search ) );
            // $completed_count = $db->count( array( 'status' => 'completed', 'search' => $search ) );
            // $failing_count   = $db->count( array( 'status' => 'failing', 'search' => $search ) );

            

            // get_subscriptions_by_date() doesn't work because it counts all sales of a product with subscription enabled. If you have an old product that was not subscription, then change it to subscription, you get false totals.

            // $earnings_totals      = 0.00; // Total earnings for time period shown
            // $subscriptions_totals = 0;    // Total sales for time period shown

            // $EDD_Recurring_Reports = new EDD_Recurring_Reports();

            // for ( $i = $start; $i <= $end; $i = $i + 86400 ) {

            //     $subscriptions = $EDD_Recurring_Reports->get_subscriptions_by_date( date( 'd', $i ), date( 'm', $i ), date( 'Y', $i ), null, false );

            //     print_r( $subscriptions );

            //     $earnings_totals += $subscriptions['earnings'];
            //     $subscriptions_totals += $subscriptions['count'];

            // }

            //var_dump($subscriptions_totals);
        }

        /**
         * Compare subscriptions
         * @access      public
         * @since       1.0.0
         * @return      array()
         */
        public function compare_subscriptions( $current_subscriptions = null ) {

            $dates = self::get_compare_dates();

            $total = self::subscriptions_db( $dates['previous_start'], $dates['previous_end'] );

            $previous_subscriptions = count($total);

            // output classes for arrows and colors
            $classes = self::get_arrow_classes( $current_subscriptions, $previous_subscriptions );

            return array( 'previous_count' => $previous_subscriptions, 'classes' => $classes, 'percentage' => self::get_percentage( $current_subscriptions, $previous_subscriptions ) );
        }

    }

	$edd_metrics_class = new EDD_Metrics_Functions();
	$edd_metrics_class->instance();

} // end class_exists check