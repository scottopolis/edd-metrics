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

            add_action( 'admin_enqueue_scripts', array( $this, 'localized_vars' ), 101 );

            

        }

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
         * Change date and reload everything
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
	            'dates' => array( 'start' => $start, 'end' => $end ), 
	            'sales' => self::get_sales(), 
	            'earnings' => self::get_earnings(), 
	            // 'avgpercust' => $avg,
	            'renewals' => self::get_renewals(),
	            'refunds' => self::get_refunds(),
	        ) );

	        wp_die();
        }

        /**
         * Return sales and earnings
         *
         * @access      public
         * @since       1.0.0
         * @return      array()
         */
        public static function get_stats() {

        	$start = date("jS F, Y", self::$start );
        	$end = date("jS F, Y", self::$end );
        	
        	$EDD_Stats = new EDD_Payment_Stats();
        	$sales = $EDD_Stats->get_sales( 0, $start, $end );
        	$earnings = $EDD_Stats->get_earnings( 0, $start, $end );

        	if( $sales > 0 ) { 
        		$avg = round( $earnings/$sales, 2); 
        	} else { 
        		$avg = 'N/A'; 
        	}

        	// Yearly estimate - avg rev per day in set time period, averaged out over 365 days. So $287/day in the last 30 days would be $287*365
			$datediff = self::$end - self::$start;
			$num_days = floor( $datediff/( 60*60*24 ) );
			$avgyearly = ($earnings/$num_days)*365;

        	return array( 'dates' => array( 'start' => $start, 'end' => $end ), 'sales' => self::get_sales(), 'earnings' => self::get_earnings(), 'avgpercust' => $avg, 'avgyearly' => round( $avgyearly, 2 ) );
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
        	if( $previous_earnings > $earnings ) {
        		$classes = 'metrics-negative metrics-downarrow';
        	} else if( $previous_earnings == $earnings ) {
        		return 'No change';
        	} else {
        		$classes = 'metrics-positive metrics-uparrow';
        	}

        	// avoid division by 0 errors
        	if( $previous_earnings === 0 && $earnings > 0 ) {

        		$percentage = $earnings * 100;

        	} else if ( $previous_earnings > 0 && $earnings === 0 ) {

        		$percentage = $previous_earnings * 100;

        	} else {

        		$percentage = self::percent( $earnings, $previous_earnings );
        		
        	}



        	return array( 'total' => $earnings, 'compare' => '<span class="' . $classes . '">' . round( $percentage, 2 ) . '%' . '</span> over last ' . $dates['num_days'] . ' days', 'avgyearly' => self::get_avg_yearly( $earnings, $previous_earnings, $dates['num_days'] ), 'avgpercust' => 'N/A' );

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
        	if( !$earnings || !$previous_earnings ) {
        		return 'N/A';
        	} else if( $earnings === 0 ) {
        		$earnings = 1;
        	} else if( $previous_earnings === 0 ) {
        		$previous_earnings = 1;
        	}

        	// Yearly estimate - avg rev per day in set time period, averaged out over 365 days. So $287/day in the last 30 days would be $287*365
			$datediff = self::$end - self::$start;
			$num_days = floor( $datediff/( 60*60*24 ) );

			$avgyearly = ( $earnings/$num_days )*365;
			$previous_avgyearly = ( $previous_earnings/$num_days )*365;

			// output classes for arrows and colors
        	if( $previous_avgyearly > $avgyearly ) {
        		$classes = 'metrics-negative metrics-downarrow';
        	} else if( $previous_avgyearly == $avgyearly ) {
        		return 'No change';
        	} else {
        		$classes = 'metrics-positive metrics-uparrow';
        	}

        	// avoid division by 0 errors
        	if( $previous_avgyearly === 0 && $avgyearly > 0 ) {

        		$percentage = $avgyearly * 100;

        	} elseif ( $previous_avgyearly > 0 && $avgyearly === 0 ) {

        		$percentage = $previous_avgyearly * 100;

        	} else {

        		$percentage = self::percent( $avgyearly, $previous_avgyearly );

        	}

			return array( 'total' => round( $avgyearly, 2 ), 'compare' => '<span class="' . $classes . '">' . round( $percentage, 2 ) . '%' . '</span> over last ' . $num_days . ' days' );

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
        	if( $previous_sales > $sales ) {
        		$classes = 'metrics-negative metrics-downarrow';
        	} else if( $previous_sales == $sales ) {
        		return 'No change';
        	} else {
        		$classes = 'metrics-positive metrics-uparrow';
        	}

        	// avoid division by 0 errors
        	if( $previous_sales === 0 && $sales > 0 ) {

        		$percentage = $sales * 100;

        	} elseif ( $previous_sales > 0 && $sales === 0 ) {

        		$percentage = $previous_sales * 100;

        	} else {

        		$percentage = self::percent( $sales, $previous_sales );

        	}

        	return array( 'count' => $sales, 'compare' => '<span class="' . $classes . '">' . round( $percentage, 2 ) . '%' . '</span> over last ' . $dates['num_days'] . ' days' );

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
        public function percent($num_amount, $num_total) {
		    return ( $num_amount / $num_total ) * 100;
		}

        /**
         * Add metrics boxes on dash
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public static function do_boxes() {

        	$start = date("jS F, Y", self::$start );
        	$end = date("jS F, Y", self::$end );
        	
        	$EDD_Stats = new EDD_Payment_Stats();
        	$sales = $EDD_Stats->get_sales( 0, $start, $end );
        	$earnings = $EDD_Stats->get_earnings( 0, $start, $end );

        	?>

        	<div class="one-half">
                <div class="edd-metrics-box">
                    <p class="top-text"><?php _e('Revenue', 'edd-metrics'); ?></p>
                    <h2 id="revenue">$<?php echo number_format( $earnings, 2 ); ?></h2>
                    <p class="bottom-text" id="revenue-compare"></p>
                </div>
            </div>

            <div class="one-half last-col">
                <div class="edd-metrics-box">
                    <p class="top-text"><?php _e('Sales', 'edd-metrics'); ?></p>
                    <h2 id="sales"><?php echo $sales; ?></h2>
                    <p class="bottom-text" id="sales-compare"></p>
                </div>
            </div>

            <div class="one-half">
                <div class="edd-metrics-box">
                    <p class="top-text"><?php _e('Avg. Per Customer', 'edd-metrics'); ?></p>
                    <h2 id="avgpercust">$<?php if( $sales > 0 ) { echo round( $earnings/$sales, 2); } else { echo 'N/A'; } ?></h2>
                    <p class="bottom-text"><span class="metrics-positive metrics-uparrow">20%</span> over last year</p>
                </div>
            </div>

            <div class="one-half last-col">
                <div class="edd-metrics-box">
                    <p class="top-text"><?php _e('Renewals', 'edd-metrics'); ?></p>
                    <h2 class="metrics-title1" id="renewals"><?php echo self::get_renewals()['count']; ?></h2>
                    <h2 class="metrics-title2">$<?php echo number_format( self::get_renewals()['earnings'], 2 ); ?></h2>
                    <p class="bottom-text"><span class="metrics-negative metrics-downarrow">4%</span> over last year</p>
                </div>
            </div>

            <div class="one-half">
                <div class="edd-metrics-box">
                    <p class="top-text"><?php _e('Refunds', 'edd-metrics'); ?></p>
                    <h2 class="metrics-title1" id="refunds"><?php echo self::$instance->get_refunds()['count']; ?></h2>
                    <h2 class="metrics-title2">$<?php echo number_format( self::$instance->get_refunds()['losses'], 2 ); ?></h2>
                    <p class="bottom-text"><span class="metrics-positive metrics-uparrow">20%</span> over last year</p>
                </div>
            </div>

            <div class="one-half last-col">
                <div class="edd-metrics-box">
                    <p class="top-text"><?php _e('Est. Yearly Revenue', 'edd-metrics'); ?></p>
                    <?php // Equation: avg rev per day in set time period, averaged out over 365 days. So $287/day in the last 30 days would be $287*365

                    // get number of days in time period
					$datediff = self::$end - self::$start;
					$num_days = floor( $datediff/( 60*60*24 ) );
					$total = ($earnings/$num_days)*365;

                    ?>
                    <h2 id="yearly">$<?php echo round( $total, 2 ); ?></h2>
                    <p class="bottom-text" id="avgyearly-compare"></p>
                </div>
            </div>

            <div class="one-half">
                <div class="edd-metrics-box">
                    <p class="top-text"><?php _e('Subscriptions', 'edd-metrics'); ?></p>
                    <h2 class="metrics-title1">2</h2>
                    <h2 class="metrics-title2">$12</h2>
                    <p class="bottom-text"><span class="metrics-positive metrics-uparrow">20%</span> over last year</p>
                </div>
            </div>

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

        	// This gets renewals for one day, need to loop through and count up based on selected dates. See reports.php in SL plugin
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

	        return array( 'count' => $count, 'earnings' => number_format( $earnings, 2 ) );
			        
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
				return array( 'count' => 0, 'losses' => 0 );
			}

			return array( 'count' => $i, 'losses' => number_format( $losses, 2 ) );
	    }

    }

	$edd_metrics_class = new EDD_Metrics_Functions();
	$edd_metrics_class->instance();

} // end class_exists check