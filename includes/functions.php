<?php
/**
 * Helper Functions
 *
 * @package     EDD\EDD Metrics\Functions
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

            add_action( 'edd_metrics_dash_content', array( $this, 'do_boxes' ) );

            add_action( 'edd_metrics_dash_sidebar', array( $this, 'do_sidebar' ) );

            add_action( 'edd_metrics_select', array( $this, 'do_select' ) );

            add_action( 'wp_ajax_edd_metrics_change_date', array( $this, 'change_date' ) );

            // add_action( 'admin_enqueue_scripts', array( $this, 'localized_vars' ), 101 );

            

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

        	echo json_encode( array(
	            //'some_string' => __( 'Some string to translate', 'edd-metrics' ),
	            'dates' => self::get_compare_dates(),
	            'sales' => self::get_sales(), 
	            'earnings' => self::get_earnings(),
	            'renewals' => self::get_renewals(),
	            'refunds' => self::get_refunds(),
                'customers' => self::get_customers()
	        ) );

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
                    'percentage' => self::get_percentage( $earnings, $previous_earnings ) 
                    ), 
                'avgyearly' => self::get_avg_yearly( $earnings, $previous_earnings, $dates['num_days'] ), 
                'avgpercust' => self::get_avg_percust( $earnings, $previous_earnings ) 
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

            $customers = self::get_customers();

            $current_customers = $customers['count'];
            $previous_customers = $customers['compare']['count'];

            if( empty( $earnings ) || empty( $current_customers ) ) {
                // can't divide by 0
                $total = 0;
            } else {
                $total = number_format( $earnings/$current_customers, 2);
            }

            if( empty( $previous_earnings ) || empty( $previous_customers ) ) {
                // can't divide by 0
                $prev_total = 0;
            } else {
                $prev_total = number_format( $previous_earnings/$previous_customers, 2);
            }

            // output classes for arrows and colors
            $classes = self::get_arrow_classes( $total, $prev_total );

            return array( 
                'total' => $total, 
                'compare' => array( 
                    'classes' => $classes, 
                    'percentage' => self::get_percentage( $total, $prev_total ) 
                    ) 
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
         * Get number of customers
         *
         * @access      public
         * @since       1.0.0
         * @return      array()
         */
        public function get_customers() {

            $dates = self::get_compare_dates();

            $args = array(
                'date' => array(
                    'start' => $dates['start'],
                    'end' => $dates['end']
                    )
                );

            // see easy-digital-downloads/includes/class-edd-db-customers.php
            $customers = EDD()->customers->get_customers( $args );

            $i = 0;

            foreach ($customers as $key => $value) {
                $i++;
            }

            return array( 
                'count' => $i, 
                'compare' => self::compare_customers( $i ) 
                );

        }

        /**
         * Get previous number of customers and compare
         *
         * @access      public
         * @since       1.0.0
         * @return      array()
         */
        public function compare_customers( $current_customers = null ) {

            $dates = self::get_compare_dates();

            $args = array(
                'date' => array(
                    'start' => $dates['previous_start'],
                    'end' => $dates['previous_end']
                    )
                );

            $previous_customers = EDD()->customers->get_customers( $args );

            $i = 0;

            // count customers
            foreach ($previous_customers as $key => $value) {
                $i++;
            }

            // output classes for arrows and colors
            $classes = self::get_arrow_classes( $current_customers, $i );

            return array( 
                'classes' => $classes, 
                'percentage' => self::get_percentage( $current_customers, $i ),
                'count' => $i 
                );
        }

        /**
         * Get start and end dates for compare periods
         *
         * @access      public
         * @since       1.0.0
         * @return      array()
         */
        public static function get_compare_dates() {
        	// current period
			$start = date("jS F, Y", self::$start );
        	$end = date("jS F, Y", self::$end );

        	$datediff = self::$end - self::$start;
			$num_days = floor( $datediff/( 60*60*24 ) ) + 1;

			// Switch to datetime format, subtract time, then back to string
			$startdate = date_create( $start );
			$enddate = date_create( $end );

			// add 1 day to num_days so it doesn't overlap by 1 day
			$previous_start = date_sub( $startdate, date_interval_create_from_date_string( $num_days . " days" ) );
			$previous_end = date_sub( $enddate, date_interval_create_from_date_string( $num_days . " days" ) );

			// previous period
			$previous_start = $previous_start->format('jS F, Y');
			$previous_end = $previous_end->format('jS F, Y');

			return array( 'start' => $start, 'end' => $end, 'previous_start' => $previous_start, 'previous_end' => $previous_end, 'num_days' => $num_days );
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
            if( $previous > $current ) {
                return 'metrics-negative metrics-downarrow';
            } else if( $previous < $current ) {
                return 'metrics-positive metrics-uparrow';
            } else {
                return 'metrics-nochange';
            }

        }

        /**
         * Add metrics boxes on dash
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public static function do_boxes() {

        	?>

        	<div class="one-half">
                <div class="edd-metrics-box">
                    <p class="top-text"><?php _e('Revenue', 'edd-metrics'); ?></p>
                    <h2 id="revenue"></h2>
                    <p class="bottom-text" id="revenue-compare"><span></span></p>
                </div>
            </div>

            <div class="one-half last-col">
                <div class="edd-metrics-box">
                    <p class="top-text"><?php _e('Sales', 'edd-metrics'); ?></p>
                    <h2 id="sales"></h2>
                    <p class="bottom-text" id="sales-compare"><span></span></p>
                </div>
            </div>

            <div class="one-half">
                <div class="edd-metrics-box">
                    <p class="top-text"><?php _e('Avg. Per Customer', 'edd-metrics'); ?></p>
                    <h2 id="avgpercust"></h2>
                    <p class="bottom-text" id="avgpercust-compare"><span></span></p>
                </div>
            </div>

            <div class="one-half last-col">
                <div class="edd-metrics-box">
                    <p class="top-text"><?php _e('Est. Yearly Revenue', 'edd-metrics'); ?></p>
                    <h2 id="yearly"></h2>
                    <p class="bottom-text" id="avgyearly-compare"><span></span></p>
                </div>
            </div>

            <div class="one-half">
                <div class="edd-metrics-box">
                    <p class="top-text"><?php _e('Refunds', 'edd-metrics'); ?></p>
                    <h2 id="refunds"></h2>
                    <p class="bottom-text" id="refunds-compare"><span></span></p>
                </div>
            </div>

            <div class="one-half last-col">
                <div class="edd-metrics-box">
                    <p class="top-text"><?php _e('Renewals', 'edd-metrics'); ?></p>
                    <h2 id="renewals"></h2>
                    <p class="bottom-text" id="renewals-compare"><span></span></p>
                </div>
            </div>

            <!--div class="one-half">
                <div class="edd-metrics-box">
                    <p class="top-text"><?php _e('Subscriptions', 'edd-metrics'); ?></p>
                    <h2 id="subscriptions">2</h2>
                    <p class="bottom-text" id="subscription-compare"></p>
                </div>
            </div-->

            <?php
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
         * Add metrics select menu
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public static function do_select() {
        	?>

        	<div class="daterange daterange--double metrics-datepicker"></div>

            <?php
        }

        /**
         * Get renewal count and earnings and return
         *
         * @access      public
         * @since       1.0.0
         * @return      array( 'count' => $count, 'earnings' => $earnings )
         */
        public static function get_renewals() {

            if( !class_exists('EDD_Software_Licensing') )
            	return;

        	// see reports.php in EDD SL plugin
    		// edd_sl_get_renewals_by_date( $day = null, $month = null, $year = null, $hour = null  )

        	$start = self::$start;
        	$end = self::$end;
			$count = 0;
			$earnings = 0;

			// Loop between timestamps, 24 hours at a time
			for ( $i = $start; $i <= $end; $i = $i + 86400 ) {
				$renewals = edd_sl_get_renewals_by_date( date( 'd', $i ), date( 'm', $i ) );
				if( $renewals['count'] === 0 )
					continue;
				$count = $renewals['count'];
			  	$earnings = $renewals['earnings'];
			}

	        return array( 'count' => $count, 'earnings' => number_format( $earnings, 2 ), 'compare' => self::compare_renewals( $count ) );
			        
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

            $start = $dates['previous_start'];
            $end = $dates['previous_end'];
            $count = 0;
            $earnings = 0;

            // Loop between timestamps, 24 hours at a time
            for ( $i = $start; $i <= $end; $i = $i + 86400 ) {
                $previous_renewals = edd_sl_get_renewals_by_date( date( 'd', $i ), date( 'm', $i ) );
                if( $previous_renewals['count'] === 0 )
                    continue;
                $count = $previous_renewals['count'];
                // $earnings = $previous_renewals['earnings'];
            }

            // output classes for arrows and colors
            $classes = self::get_arrow_classes( $current_renewals, $count );

            return array( 'classes' => $classes, 'percentage' => self::get_percentage( $current_renewals, $count ) );
        }

        /**
	     * Get refund count and losses and return
	     *
	     * @access      public
	     * @since       1.0.0
	     * @return      array( 'count' => $count, 'losses' => $losses )
	     */
	    public static function get_refunds() {

        	$args = array(
				'post_type' => 'edd_payment',
				'post_status' => array( 'refunded' ),
				'date_query' => array(
					array(
						'after'     => self::$startstr,
						'before'    => self::$endstr,
						'inclusive' => true,
					),
				),
			);

			$losses = 0;

        	// The Query
			$the_query = new WP_Query( $args );

			if ( $the_query->have_posts() ) {
				$i = 0;
				while ( $the_query->have_posts() ) {
					$the_query->the_post();
					$losses += get_post_meta( get_the_ID(), '_edd_payment_total' )[0];
					$i++;
				}
				wp_reset_postdata();
			} else {
				return array( 'count' => 0, 'losses' => 0, 'compare' => self::compare_refunds( $i ) );
			}

			return array( 'count' => $i, 'losses' => number_format( $losses, 2 ), 'compare' => self::compare_refunds( $i ) );
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

            $args = array(
                'post_type' => 'edd_payment',
                'post_status' => array( 'refunded' ),
                'date_query' => array(
                    array(
                        'after'     => $dates['previous_start'], // june 1
                        'before'    => $dates['previous_end'], // june 30
                        'inclusive' => true,
                    ),
                ),
            );

            $losses = 0;

            // The Query
            $the_query = new WP_Query( $args );

            if ( $the_query->have_posts() ) {
                $previous_refunds = 0;
                while ( $the_query->have_posts() ) {
                    $the_query->the_post();
                    $losses += get_post_meta( get_the_ID(), '_edd_payment_total' )[0];
                    $previous_refunds++;
                }
                wp_reset_postdata();
            } else {
                return array( 'classes' => 'metrics-nochange', 'percentage' => 0 );
            }

            // output classes for arrows and colors
            $classes = self::get_arrow_classes( $current_refunds, $previous_refunds );

            return array( 'classes' => $classes, 'percentage' => self::get_percentage( $current_refunds, $previous_refunds ) );
        }

    }

	$edd_metrics_class = new EDD_Metrics_Functions();
	$edd_metrics_class->instance();

} // end class_exists check