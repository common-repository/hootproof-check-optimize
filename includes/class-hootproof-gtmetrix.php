<?php

  /* 
  Parts of this class were taken form the GTmetrix for WordPress plugin 
  (http://gtmetrix.com/gtmetrix-for-wordpress-plugin.html) and modified by 
  Michelle Retzlaff  (email : michelle@hootproof.de)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if ( !class_exists( 'HootProof_GTMetrix' ) ) {
	
class HootProof_GTMetrix {

    public function __construct() {

        add_action( 'init', array( &$this, 'register_post_types' ) );
        add_action( 'admin_init', array( &$this, 'register_settings' ) );
        add_action( 'admin_notices', array( &$this, 'admin_notices' ) );
       /* add_action( 'hpwc_gfw_hourly_event', array( &$this, 'scheduled_events' ) );
        add_action( 'hpwc_gfw_daily_event', array( &$this, 'scheduled_events' ) );
        add_action( 'hpwc_gfw_weekly_event', array( &$this, 'scheduled_events' ) );
        add_action( 'hpwc_gfw_monthly_event', array( &$this, 'scheduled_events' ) );*/
        
        add_action( 'wp_ajax_autocomplete', array( &$this, 'autocomplete_callback' ) );
        add_action( 'wp_ajax_save_report', array( &$this, 'save_report_callback' ) );
        add_action( 'wp_ajax_expand_report', array( &$this, 'expand_report_callback' ) );
        add_action( 'wp_ajax_report_graph', array( &$this, 'report_graph_callback' ) );
        add_action( 'wp_ajax_reset', array( &$this, 'reset_callback' ) );
        add_filter( 'cron_schedules', array( &$this, 'add_intervals' ) );
        add_filter( 'plugin_row_meta', array( &$this, 'plugin_links' ), 10, 2 );

        $options = get_option( 'hpwc_hootproof_gfw_options' );
        define( 'HPWC_GFW_WP_VERSION', '3.3.1' );
        define( 'HPWC_GFW_VERSION', '0.4' );
        define( 'HPWC_GFW_USER_AGENT', 'HootProof Check and optimize/' . HPWC_GFW_VERSION . ' (+http://gtmetrix.com/gtmetrix-for-wordpress-plugin.html)' );
        define( 'HPWC_GFW_TIMEZONE', get_option( 'timezone_string' ) ? get_option( 'timezone_string' ) : date_default_timezone_get() );
        define( 'HPWC_GFW_AUTHORIZED', isset( $options['authorized'] ) && $options['authorized'] ? true : false );
        define( 'HPWC_GFW_URL', plugins_url( '/', __FILE__ ) );
        define( 'HPWC_GFW_TESTS', get_admin_url() . 'admin.php?page=hootproof_menu_gfw_tests' );
        define( 'HPWC_GFW_SETTINGS', get_admin_url() . 'admin.php?page=hootproof_menu_gfw_settings' );
        define( 'HPWC_GFW_SCHEDULE', get_admin_url() . 'admin.php?page=hootproof_menu_gfw_schedule' );
        define( 'HPWC_GFW_TRIES', 3 );
    }

    public function plugin_links( $links, $file ) {
        if ( $file == plugin_basename( __FILE__ ) ) {
            return array_merge( $links, array( sprintf( '<a href="%1$s">%2$s</a>', hpwc_hootproof_gfw_settings, __('Settings','hootproof-check') ) ) );
        }
        return $links;
    }

    public function add_intervals( $schedules ) {
        $schedules['hourly'] = array( 'interval' => 3600, 'display' => __('Hourly', 'hootproof-check') );
        //$schedules['weekly'] = array( 'interval' => 604800, 'display' => __('Weekly', 'hootproof-check') );
        //$schedules['monthly'] = array( 'interval' => 2635200, 'display' => __('Monthly', 'hootproof-check') );
        return $schedules;
    }
    
    public function scheduled_events( $recurrence ) {
    
        if ( HPWC_GFW_AUTHORIZED ) {
            $args = array(
                'post_type' => 'hpwc_gfw_event',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => 'gfw_recurrence',
                        'value' => $recurrence
                    ),
                ),
            );
            
            $query = new WP_Query( $args );
            while ( $query->have_posts() ) {
                $query->next_post();
                $event_id = $query->post->ID;
                $event_custom = get_post_custom( $event_id );
// As well as testing those events with a gfw_status of 1, we also need to test where gfw_status does not exist (those set pre version 0.4)
                if ( !isset( $event_custom['gfw_status'][0] ) || (isset( $event_custom['gfw_status'][0] ) && (1 == $event_custom['gfw_status'][0])) ) {

                    $parameters = array( );
                    foreach ( $event_custom as $meta_key => $meta_value ) {
                        $parameters[$meta_key] = $meta_value[0];
                    }
                    $report = $this->run_test( $parameters );
                    $last_report_id = $this->save_report( array_merge( $parameters, $report ), $event_id );

                    date_default_timezone_set( HPWC_GFW_TIMEZONE );
                    update_post_meta( $event_id, 'gfw_last_report', date( 'Y-m-d H:i:s' ) );
                    update_post_meta( $event_id, 'gfw_last_report_id', $last_report_id );
                    if ( isset( $report['error'] ) ) {
                        $gfw_event_error = get_post_meta( $event_id, 'gfw_event_error', true );
                        if ( HPWC_GFW_TRIES == ++$gfw_event_error ) {
                            update_post_meta( $event_id, 'gfw_status', 3 );
                        }
                        update_post_meta( $event_id, 'gfw_event_error', $gfw_event_error );
                    } else {
                        update_post_meta( $event_id, 'gfw_event_error', 0 );
                    }

                }
            }
        }
    }


    public function add_menu_items() {
    
    $this->settings_page_hook = add_submenu_page( 'hootproof_menu', __('HootProof Performance Settings', 'hootproof-check'), __('Performance Settings', 'hootproof-check'), 'manage_options', 'hootproof_menu_gfw_settings', array( $this, 'settings_page' ) );
    
        if ( HPWC_GFW_AUTHORIZED ) {
            
            $this->tests_page_hook = add_submenu_page( 'hootproof_menu', __('HootProof Performance Tests', 'hootproof-check') , __('Performance Tests', 'hootproof-check'), 'manage_options', 'hootproof_menu_gfw_tests', array( $this, 'tests_page' ) );
            $this->schedule_page_hook = add_submenu_page( 'hootproof_menu', __('HootProof Test Schedule', 'hootproof-check'), __('Test Schedule', 'hootproof-check'), 'manage_options', 'hootproof_menu_gfw_schedule', array( $this, 'schedule_page' ) );
            add_action( 'load-' . $this->tests_page_hook, array( &$this, 'page_loading' ) );
            add_action( 'load-' . $this->schedule_page_hook, array( &$this, 'page_loading' ) );
        } 
        
        add_action( 'load-' . $this->settings_page_hook, array( &$this, 'page_loading' ) );
    }

    public function admin_notices() {
        if ( !HPWC_GFW_AUTHORIZED ) {
            echo $this->set_notice( sprintf(__('<strong>GTmetrix integration is almost ready.</strong> You must <a href="%s">enter your GTmetrix API key</a> for it to work.', 'hootproof-check') , HPWC_GFW_SETTINGS) );
        }
        
        global $wpdb;
        $sql = "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'hpwc_gfw_report' ";
        $report_count = $wpdb->get_var($sql);
        if ( !isset($report_count) || $report_count <= 0) {
            echo $this->set_notice( sprintf(__('<strong>Please start your first performance test for more optimization suggestions from HootProof.</strong> Go to <a href="%s">Tests</a> to test your site perforamnce.', 'hootproof-check') , HPWC_HOOTPROOF_TESTS) );
        }

        $notice = get_transient( 'admin_notice' );
        if ( $notice ) {
            echo $this->set_notice( $notice );
            delete_transient( 'admin_notice' );
        }
    }

    public function register_settings() {
        register_setting( 'hpwc_hootproof_gfw_options_group', 'hpwc_hootproof_gfw_options', array( &$this, 'sanitize_settings' ) );
        add_settings_section( 'authentication_section', '', array( &$this, 'section_text' ), 'hpwc_hootproof_gfw_settings' );
        add_settings_field( 'api_username', __('GTmetrix Account Email', 'hootproof-check'), array( &$this, 'set_api_username' ), 'hpwc_hootproof_gfw_settings', 'authentication_section' );
        add_settings_field( 'api_key', __('API Key', 'hootproof-check'), array( &$this, 'set_api_key' ), 'hpwc_hootproof_gfw_settings', 'authentication_section' );
        if ( HPWC_GFW_AUTHORIZED ) {
            add_settings_section( 'options_section', '', array( &$this, 'section_text' ), 'hpwc_hootproof_gfw_settings' );
         
            add_settings_field( 'default_location', __('Default location', 'hootproof-check'), array( &$this, 'set_default_location' ), 'hpwc_hootproof_gfw_settings', 'options_section' );
            
            add_settings_section( 'reset_section', '', array( &$this, 'section_text' ), 'hpwc_hootproof_gfw_settings' );
            add_settings_field( 'reset', __('Reset', 'hootproof-check'), array( &$this, 'set_reset' ), 'hpwc_hootproof_gfw_settings', 'reset_section' );
        }
    }

    public function set_api_username() {
        $options = get_option( 'hpwc_hootproof_gfw_options' );
        echo '<input type="text" name="hpwc_hootproof_gfw_options[api_username]" id="api_username" value="' . (isset( $options['api_username'] ) ? $options['api_username'] : '') . '" />';
    }

    public function set_api_key() {
        $options = get_option( 'hpwc_hootproof_gfw_options' );
        echo '<input type="text" name="hpwc_hootproof_gfw_options[api_key]" id="api_key" value="' . (isset( $options['api_key'] ) ? $options['api_key'] : '') . '" />';
    }

    public function set_default_location() {
        $options = get_option( 'hpwc_hootproof_gfw_options' );
        echo '<p><select name="hpwc_hootproof_gfw_options[default_location]" id="default_location">';
        foreach ( $options['locations'] as $location ) {
            echo '<option value="' . $location['id'] . '" ' . selected( $options['default_location'], $location['id'], false ) . '>' . $location['name'] . '</option>';
        }
        echo '</select><br /><span class="description">' . __('Test Server Region (scheduled tests will override this setting)', 'hootproof-check') . '</span></p>';
    }

    public function set_reset() {
        echo '<p class="description">'
        . __('This will flush all GTmetrix records from the WordPress database!', 'hootproof-check')
        . '</p><input type="button" value="'. __('Reset', 'hootproof-check') .'" class="button-primary" id="gfw-reset" />';
    }


    public function section_text() {
        // Placeholder for settings section (which is required for some reason)
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'common' );
        wp_enqueue_script( 'wp-lists' );
        wp_enqueue_script( 'postbox' );
        wp_enqueue_script( 'jquery-ui-tooltip' );
        wp_enqueue_script( 'gfw-script', HPWC_GFW_URL . '../js/gtmetrix-for-wordpress.js', array( 'jquery-ui-autocomplete', 'jquery-ui-dialog' ), HPWC_GFW_VERSION, true );
        wp_localize_script( 'gfw-script', 'gfwObject', array( 'gfwnonce' => wp_create_nonce( 'gfwnonce' ) ) );

    }
    public function page_loading() {
        $screen = get_current_screen();
        $this->enqueue_scripts();
        
        if ( HPWC_GFW_AUTHORIZED ) {
            add_meta_box( 'gfw-credits-meta-box', 'API Credits', array( &$this, 'credits_meta_box' ), $this->tests_page_hook, 'side', 'core' );
            add_meta_box( 'gfw-credits-meta-box', 'API Credits', array( &$this, 'credits_meta_box' ), $this->schedule_page_hook, 'side', 'core' );
        }


        if ( method_exists( $screen, 'add_help_tab' ) ) {
            $settings_help = '<p>You will need an account at <a href="http://gtmetrix.com/" target="_blank">Gtmetrix.com</a> to use GTmetrix for WordPress. Registration is free. Once registered, go to the <a href="http://gtmetrix.com/api/" target="_blank">API page</a> and generate an API key. Enter this key, along with your registered email address, in the authentication fields below, and you\'re ready to go!</p>';
            $options_help = '<p>You would usually set your <i>default location</i> to the city nearest to your target audience. When you run a test on a URL, the report returned will reflect the experience of a user connecting from this location.</p>';

            $test_help = '<p>To analyze the performance of a page or post on your blog, simply enter it\'s URL. You can even just start to type the title into the box, and an autocomplete facility will try and help you out.</p>';
            $test_help .= '<p>The optional <i>Label</i> is simply used to help you identify a given report in the system.</p>';

            $reports_help = '<p>The Reports section shows summaries of your reports. For even more detailed information, click on the Report\'s URL/label, and the full GTmetrix.com report will open. You can also delete a report.</p>';
            $reports_help .= '<p><b>Note:</b> deleting a report here only removes it from GTmetrix for WordPress - not from your GTmetrix account.<br /><b>Note:</b> if the URL/label is not a link, this means the report is no longer available on GTmetrix.com.</p>';

            $schedule_help = '<p>You can set up your reports to be generated even when you\'re away. Simply run the report as normal (in Reports), then expand the report\'s listing, and click <i>Schedule tests</i>. You will be redirected to this page, where you can choose how often you want this report to run.</p>';
            $schedule_help .= '<p>You can also choose to be sent an email when certain conditions apply. This email can go to either your admin email address or your GTmetrix email address, as defined in settings.</p>';
            $schedule_help .= '<p><b>Note:</b> every test will use up 1 of your API credits on GTmetrix.com<br /><b>Note:</b> scheduled tests use the WP-Cron functionality that is built into WordPress. This means that events are only triggered when your site is visited.</p>';

            switch ( $screen->id ) {

               // case 'toplevel_page_hpwc_hootproof_gfw_settings':
                case 'hootproof-check-results_page_hootproof_menu_settings':
                    $screen->add_help_tab(
                            array(
                                'title' => 'Authentication',
                                'id' => 'authentication_help_tab',
                                'content' => $settings_help
                            )
                    );
                    $screen->add_help_tab(
                            array(
                                'title' => 'Options',
                                'id' => 'options_help_tab',
                                'content' => $options_help
                            )
                    );
                    break;

                case 'hootproof-check-results_page_hootproof_menu_gfw_tests':
                    wp_enqueue_style( 'smoothness', HPWC_GFW_URL . 'lib/smoothness/jquery-ui-1.10.2.custom.min.css', HPWC_GFW_VERSION );
                    $screen->add_help_tab(
                            array(
                                'title' => 'Test',
                                'id' => 'test_help_tab',
                                'content' => $test_help
                            )
                    );
                    $screen->add_help_tab(
                            array(
                                'title' => 'Reports',
                                'id' => 'reports_help_tab',
                                'content' => $reports_help
                            )
                    );
                    break;

                case 'hootproof-check-results_page_hootproof_menu_gfw_schedule':
                    wp_enqueue_style( 'smoothness', HPWC_GFW_URL . 'lib/smoothness/jquery-ui-1.10.2.custom.min.css', HPWC_GFW_VERSION );
                    wp_enqueue_script( 'flot', HPWC_GFW_URL . 'lib/flot/jquery.flot.min.js', 'jquery' );
                    wp_enqueue_script( 'flot.resize', HPWC_GFW_URL . 'lib/flot/jquery.flot.resize.min.js', 'flot' );
                    $screen->add_help_tab(
                            array(
                                'title' => 'Schedule a Test',
                                'id' => 'schedule_help_tab',
                                'content' => $schedule_help
                            )
                    );
                    break;
            }

           // $screen->set_help_sidebar( '<p><strong>For more information:</strong></p><p><a href="http://gtmetrix.com/wordpress-optimization-guide.html" target="_blank">GTmetrix Wordpress Optimization Guide</a></p>' );
        }
    }

    public function schedule_page() {

        global $screen_layout_columns;
        $report_id = isset( $_GET['report_id'] ) ? $_GET['report_id'] : 0;
        $event_id = isset( $_GET['event_id'] ) ? $_GET['event_id'] : 0;
        $delete = isset( $_GET['delete'] ) ? $_GET['delete'] : 0;
        $status = isset( $_GET['status'] ) ? $_GET['status'] : 0;


        if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
            $data = $_POST;

            if ( $data['report_id'] ) {

                $custom_fields = get_post_custom( $data['report_id'] );

                $event_id = wp_insert_post( array(
                    'post_type' => 'hpwc_gfw_event',
                    'post_status' => 'publish',
                    'post_author' => 1
                        ) );

                update_post_meta( $event_id, 'gfw_url', $custom_fields['gfw_url'][0] );
                update_post_meta( $event_id, 'gfw_label', $custom_fields['gfw_label'][0] );
                update_post_meta( $event_id, 'gfw_location', 1 ); // restricted to Vancouver
                update_post_meta( $event_id, 'gfw_event_error', 0 );
            }

            $event_id = $data['event_id'] ? $data['event_id'] : $event_id;

            update_post_meta( $event_id, 'gfw_recurrence', $data['gfw_recurrence'] );
            update_post_meta( $event_id, 'gfw_status', $data['gfw_status'] );

            $notifications = array( );
            if ( isset( $data['gfw_condition'] ) ) {
                foreach ( $data['gfw_condition'] as $key => $value ) {
                    $notifications[$value] = $data[$value][$key];
                }
                update_post_meta( $event_id, 'gfw_notifications', $notifications );
            } else {
                delete_post_meta( $event_id, 'gfw_notifications' );
            }
            echo '<div id="message" class="updated"><p><strong>'
             . __('Schedule updated.', 'hootproof-check') . '</strong></p></div>';
        } // end if POST

        if ( ($event_id || $report_id) && !isset( $data ) ) {
            add_meta_box( 'schedule-meta-box', __('Schedule a Test', 'hootproof-check'), array( &$this, 'schedule_meta_box' ), $this->schedule_page_hook, 'normal', 'core' );
        }

        if ( $delete ) {
            $args = array(
                'post_type' => 'hpwc_gfw_report',
                'meta_key' => 'gfw_event_id',
                'meta_value' => $delete,
                'posts_per_page' => -1
            );

            $query = new WP_Query( $args );

            while ( $query->have_posts() ) {
                $query->next_post();
                wp_delete_post( $query->post->ID );
            }

            wp_delete_post( $delete );
            echo $this->set_notice( __('Event deleted', 'hootproof-check') );
        }

        if ( $status ) {
            $gfw_status = get_post_meta( $status, 'gfw_status', true );
            if ( 1 == $gfw_status ) {
                update_post_meta( $status, 'gfw_status', 2 );
                echo $this->set_notice( __('Event paused', 'hootproof-check') );
            } else {
                update_post_meta( $status, 'gfw_status', 1 );
                update_post_meta( $status, 'gfw_event_error', 0 );
                echo $this->set_notice( __('Event reactivated', 'hootproof-check') );
            }
        }

        add_meta_box( 'events-meta-box', __('Scheduled Tests', 'hootproof-check'), array( &$this, 'events_list' ), $this->schedule_page_hook, 'normal', 'core' );
        ?>

        <div class="wrap gfw">
            <div id="gfw-icon" class="icon32"></div>
            <h2><?php _e('HootProof Performance Test Schedule', 'hootproof-check'); ?></h2>
            <?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
            <?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
            <div id="poststuff" class="metabox-holder has-right-sidebar">
                <div id="side-info-column" class="inner-sidebar">
                    <?php do_meta_boxes( $this->schedule_page_hook, 'side', 0 ); ?>
                </div>
                <div id="post-body" class="has-sidebar">
                    <div id="post-body-content" class="has-sidebar-content">
                        <?php do_meta_boxes( $this->schedule_page_hook, 'normal', false ); ?>
                    </div>
                </div>
            </div>	
        </div>
        <?php
    }

    protected function set_notice( $message, $class = 'updated' ) {
        return '<div class="' . $class . '"><p>' . $message . '</p></div>';
    }

    public function tests_page() {
    
        $delete = isset( $_GET['delete'] ) ? $_GET['delete'] : 0;
        if ( $delete ) {
            wp_delete_post( $delete );
            echo $this->set_notice( __('Report deleted', 'hootproof-check') );
        }

        global $screen_layout_columns;
        wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
        wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
        add_meta_box( 'gfw-score-meta-box', __('Latest Front Page Score', 'hootproof-check'), array( &$this, 'score_meta_box' ), $this->tests_page_hook, 'normal', 'core' );
        add_meta_box( 'gfw-test-meta-box', __('Test Performance of:', 'hootproof-check'), array( &$this, 'test_meta_box' ), $this->tests_page_hook, 'normal', 'core' );
        add_meta_box( 'gfw-reports-meta-box', __('Reports', 'hootproof-check'), array( &$this, 'reports_list' ), $this->tests_page_hook, 'normal', 'core' );
        
        
        ?>
        <div class="wrap gfw">
            <div id="gfw-icon" class="icon32"></div>
            <h2><?php _e('HootProof Performance &raquo; Tests (powered by GTMetrix)', 'hootproof-check'); ?></h2>
            <div id="poststuff" class="metabox-holder has-right-sidebar">
                <div id="side-info-column" class="inner-sidebar">
                    <?php do_meta_boxes( $this->tests_page_hook, 'side', 0 ); ?>
                </div>
                <div id="post-body" class="has-sidebar">
                    <div id="post-body-content" class="has-sidebar-content">
                        <?php do_meta_boxes( $this->tests_page_hook, 'normal', 0 ); ?>
                    </div>
                </div>
            </div>	
        </form>
        <div id="gfw-confirm-delete" class="gfw-dialog" title="<?php _e('Delete this report?', 'hootproof-check'); ?>">
            <p><?php _e('Are you sure you want to delete this report?', 'hootproof-check'); ?></p>
        </div>
        </div>
        <?php
    }

    public function settings_page() {
        global $screen_layout_columns;
        wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
        wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
        add_meta_box( 'authenticate-meta-box', __('Authentication', 'hootproof-check'), array( &$this, 'authenticate_meta_box' ), $this->settings_page_hook, 'normal', 'core' );
        if ( HPWC_GFW_AUTHORIZED ) {
            add_meta_box( 'options-meta-box', __('Options', 'hootproof-check'), array( &$this, 'options_meta_box' ), $this->settings_page_hook, 'normal', 'core' );
            add_meta_box( 'reset-meta-box', _x('Reset', 'meta box', 'hootproof-check'), array( &$this, 'reset_meta_box' ), $this->settings_page_hook, 'normal', 'core' );
        }
        ?>
        <div class="wrap gfw">
            <div id="gfw-icon" class="icon32"></div>
            <h2><?php _e('Performance Settings', 'hootproof-check'); ?></h2>
            <?php settings_errors( 'hpwc_hootproof_gfw_options', false ); ?>
            <div id="poststuff" class="metabox-holder has-right-sidebar">
                <div id="side-info-column" class="inner-sidebar">
                    <?php do_meta_boxes( $this->settings_page_hook, 'side', 0 ); ?>
                </div>
                <div id="post-body" class="has-sidebar">
                    <div id="post-body-content" class="has-sidebar-content">
                        <form method="post" action="options.php">
                            <?php
                            wp_nonce_field( 'update-options' );
                            settings_fields( 'hpwc_hootproof_gfw_options_group' );
                            do_meta_boxes( $this->settings_page_hook, 'normal', 0 );
                            submit_button( __('Save Changes', 'hootproof-check'), 'primary', 'submit', false );
                            ?>
                        </form>
                    </div>
                </div>
            </div>	
        </div>
        <?php
    }

    public function sanitize_settings( $input ) {

        $valid = array( );
        $valid['authorized'] = 0;

        $valid['api_username'] = sanitize_email( $input['api_username'] );
        $valid['api_key'] = $input['api_key'];
        if ( !is_email( $valid['api_username'] ) ) {
            if ( !get_settings_errors( 'hpwc_hootproof_gfw_options' ) ) {
                add_settings_error( 'hpwc_hootproof_gfw_options', 'api_error', __('GTmetrix Account Email must be a valid email address.', 'hootproof-check') );
            }
        } else {

            require_once('lib/Services_WTF_Test.php');
            $test = new Services_WTF_Test();
            $test->api_username( $valid['api_username'] );
            $test->api_password( $valid['api_key'] );
            $test->user_agent( HPWC_GFW_USER_AGENT );
            $locations = $test->locations();

            if ( $test->error() ) {
                if ( !get_settings_errors( 'hpwc_hootproof_gfw_options' ) ) {
                    add_settings_error( 'hpwc_hootproof_gfw_options', 'api_error', $test->error() );
                }
            } else {
                foreach ( $locations as $location ) {
                    $valid['locations'][$location['id']] = $location;
                }
                $valid['authorized'] = 1;
                if ( !get_settings_errors( 'hpwc_hootproof_gfw_options' ) ) {
                    add_settings_error( 'hpwc_hootproof_gfw_options', 'settings_updated',  
                       sprintf(__('Settings Saved. Please click on <a href="%s">Tests</a> to test your WordPress installation.', 'hootproof-check'), HPWC_GFW_TESTS), 'updated' );
                }
            }
        }
        $options = get_option( 'hpwc_hootproof_gfw_options' );
        $valid['default_location'] = isset( $input['default_location'] ) ? $input['default_location'] : (isset( $options['default_location'] ) ? $options['default_location'] : 1);
        $valid['toolbar_link'] = isset( $input['toolbar_link'] ) ? $input['toolbar_link'] : (isset( $options['toolbar_link'] ) ? $options['toolbar_link'] : 1);
        $valid['notifications_email'] = isset( $input['notifications_email'] ) ? $input['notifications_email'] : (isset( $options['notifications_email'] ) ? $options['notifications_email'] : 'api_username');

        return $valid;
    }

    public function register_post_types() {

        register_post_type( 'hpwc_gfw_report', array(
            'label' => 'GFW Reports',
            'public' => false,
            'supports' => array( false ),
            'rewrite' => false,
            'can_export' => false
        ) );

        register_post_type( 'hpwc_gfw_event', array(
            'label' => 'GFW Events',
            'public' => false,
            'supports' => array( false ),
            'rewrite' => false,
            'can_export' => false
        ) );
    }

    public function save_report( $data, $event_id = 0 ) {

        $post_id = wp_insert_post( array(
            'post_type' => 'hpwc_gfw_report',
            'post_status' => 'publish',
            'post_author' => 1
                ) );

        update_post_meta( $post_id, 'gfw_url', $this->append_http( $data['gfw_url'] ) );
        update_post_meta( $post_id, 'gfw_label', $data['gfw_label'] );
        update_post_meta( $post_id, 'gfw_location', $data['gfw_location'] );
        update_post_meta( $post_id, 'gfw_adblock', isset( $data['gfw_adblock'] ) ? $data['gfw_adblock'] : 0  );
        update_post_meta( $post_id, 'gfw_video', isset( $data['gfw_video'] ) ? $data['gfw_video'] : 0  );
        update_post_meta( $post_id, 'gfw_event_id', $event_id );

        if ( !isset( $data['error'] ) ) {
            update_post_meta( $post_id, 'gtmetrix_test_id', $data['test_id'] );
            update_post_meta( $post_id, 'page_load_time', $data['page_load_time'] );
            update_post_meta( $post_id, 'html_bytes', $data['html_bytes'] );
            update_post_meta( $post_id, 'page_elements', $data['page_elements'] );
            update_post_meta( $post_id, 'report_url', $data['report_url'] );
            update_post_meta( $post_id, 'html_load_time', $data['html_load_time'] );
            update_post_meta( $post_id, 'page_bytes', $data['page_bytes'] );
            update_post_meta( $post_id, 'pagespeed_score', $data['pagespeed_score'] );
            update_post_meta( $post_id, 'pagespeed', $data['pagespeed'] );
            update_post_meta( $post_id, 'pagespeed_obj', wp_slash($data['pagespeed_obj'] ));
        } else {
            update_post_meta( $post_id, 'gtmetrix_test_id', 0 );
            update_post_meta( $post_id, 'gtmetrix_error', $data['error'] );
        }
        return $post_id;
    }

    protected function run_test( $parameters ) {

        $api = $this->api();
        $response = array( );
        delete_transient( 'credit_status' );

        $test_id = $api->test( array(
            'url' => $this->append_http( $parameters['gfw_url'] ),
            'location' => $parameters['gfw_location'],
            'x-metrix-adblock' => isset( $parameters['gfw_adblock'] ) ? $parameters['gfw_adblock'] : 0,
            'x-metrix-video' => isset( $parameters['gfw_video'] ) ? $parameters['gfw_video'] : 0,
                ) );

        if ( $api->error() ) {
            $response['error'] = $api->error();
            return $response;
        }

        $api->get_results();

        if ( $api->error() ) {
            $response['error'] = $api->error();
            return $response;
        }

        if ( $api->completed() ) {
            
            $response['test_id'] = $test_id;
            return array_merge( $response, $api->results(), $api->resources(), $api->get_pagespeed_obj($test_id) );
        }
    }

    public function save_report_callback() {
        if ( check_ajax_referer( 'gfwnonce', 'security' ) ) {
            $fields = array( );
            parse_str( $_POST['fields'], $fields );
            $report = $this->run_test( $fields );
            if ( isset( $report['error'] ) ) {
                $response = json_encode( array( 'error' => $this->translate_message( $report['error'] ) ) );
            } else {
                $this->save_report( array_merge( $fields, $report ) );
                set_transient( 'admin_notice', __('Test complete', 'hootproof-check') );
                $response = json_encode( array(
                    'screenshot' => $report['report_url'] . '/screenshot.jpg'
                        ) );
            }
            echo $response;
        }
        die();
    }

    public function autocomplete_callback() {
        $args['s'] = stripslashes( $_GET['term'] );
        $args['pagenum'] = !empty( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
        require(ABSPATH . WPINC . '/class-wp-editor.php');
        $results = _WP_Editors::wp_link_query( $args );
        echo json_encode( $results ) . "\n";
        die();
    }

    public function expand_report_callback() {
   
        $post = get_post( $_POST['id'] );

        if ( 'hpwc_gfw_report' == $post->post_type ) {
            $report_id = $post->ID;
        } else {

            $args = array(
                'post_type' => 'hpwc_gfw_report',
                'posts_per_page' => 1,
                'orderby' => 'post_date',
                'order' => 'DESC',
                'meta_query' => array(
                    array(
                        'key' => 'gfw_event_id',
                        'value' => $post->ID
                    ),
                    array(
                        'key' => 'gtmetrix_test_id',
                        'value' => 0,
                        'compare' => '!='
                    )
                ),
            );
            $query = new WP_Query( $args );
            $report_id = ($query->post_count ? $query->post->ID : 0);
        }

        echo '<div class="gfw-expansion">';
        echo '<div class="gfw-expansion-right">';
        if ( $report_id ) {
            $report = get_post( $report_id );
            $custom_fields = get_post_custom( $report->ID );
            $options = get_option( 'hpwc_hootproof_gfw_options' );
            $expired = ($this->gtmetrix_file_exists( $custom_fields['report_url'][0] . '/screenshot.jpg' ) ? false : true);
            ?>
            <div class="gfw-meta">
                <div><b>URL:</b> <?php echo $custom_fields['gfw_url'][0]; ?></div>
                <div><b><?php _e('Test server region:', 'hootproof-check'); ?></b> <?php echo $options['locations'][$custom_fields['gfw_location'][0]]['name']; ?></div>
                <div style="text-align: center"></div>
                <div style="text-align: right"><b><?php _e('Latest successful test:', 'hootproof-check'); ?></b> <?php echo date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $report->post_date ) ); ?></div>
            </div>
            <div>
                <table>
                    <tr>
                        <th><?php _e('Page Speed score:', 'hootproof-check'); ?></th>
                        <td><?php echo $custom_fields['pagespeed_score'][0]; ?></td>
                        <th><?php _e('No. of page elements:', 'hootproof-check'); ?></th>
                        <td><?php echo $custom_fields['page_elements'][0]; ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Page load time:', 'hootproof-check'); ?></th>
                        <td><?php echo number_format( $custom_fields['page_load_time'][0] / 1000, 2 ). __('seconds', 'hootproof-check'); ?> </td>
                        <th><?php _e('HTML load time:', 'hootproof-check'); ?></th>
                        <td><?php echo number_format( $custom_fields['html_load_time'][0] / 1000, 2 ) . __('seconds', 'hootproof-check'); ?> </td>
                    </tr>
                    <tr>
                        <th><?php _e('Total page size:', 'hootproof-check'); ?></th>
                        <td><?php echo size_format( $custom_fields['page_bytes'][0], 2 ); ?></td>
                        <th><?php _e('Total HTML size:', 'hootproof-check'); ?></th>
                        <td><?php echo size_format( $custom_fields['html_bytes'][0], 1 ); ?></td>
                    </tr>
                </table>
            </div>
            <?php
            if ( 'hpwc_gfw_event' == $post->post_type ) {
                echo '<div class="graphs">';
                echo '<div><a href="' . $_POST['id'] . '" class="gfw-open-graph gfw-scores-graph" id="gfw-scores-graph">Page Speed graph</a></div>';
                echo '<div><a href="' . $_POST['id'] . '" class="gfw-open-graph gfw-times-graph" id="gfw-times-graph">Page load times graph</a></div>';
                echo '<div><a href="' . $_POST['id'] . '" class="gfw-open-graph gfw-sizes-graph" id="gfw-sizes-graph">Page sizes graph</a></div>';
                echo '</div>';
            }
            echo '<div class="actions">';
            if ( 'hpwc_gfw_report' == $post->post_type ) {
                echo '<div><a href="' . HPWC_GFW_SCHEDULE . '&report_id=' . $report->ID . '" class="gfw-schedule-icon-large">' . __('Schedule tests', 'hootproof-check') .'</a></div>';
            }
            if ( !$expired ) {
                echo '<div><a href="' . $custom_fields['report_url'][0] . '" target="_blank" class="gfw-report-icon">'. __('Detailed report', 'hootproof-check') .'</a></div>';
                echo '<div><a href="' . $custom_fields['report_url'][0] . '/pdf?full=1' . '" class="gfw-pdf-icon">'. __('Download PDF', 'hootproof-check') .'</a></div>';
            }
            echo '</div>';
            echo '</div>';
            echo '<div class="gfw-expansion-left">';
            if ( !$expired ) {
                echo '<img src="' . $custom_fields['report_url'][0] . '/screenshot.jpg' . '" />';
            }
        } else {
            echo '<p>'. __('There are currently no successful reports in the database for this event', 'hootproof-check') .'</p>';
        }
        echo '</div>';
        echo '</div>';
        die();
    }

    public function report_graph_callback() {

        $graph = $_GET['graph'];

        $args = array(
            'post_type' => 'hpwc_gfw_report',
            'numberposts' => 6,
            'orderby' => 'post_date',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => 'gfw_event_id',
                    'value' => $_GET['id']
                ),
                array(
                    'key' => 'gtmetrix_test_id',
                    'value' => 0,
                    'compare' => '!='
                )
            ),
        );
        $query = new WP_Query( $args );
        while ( $query->have_posts() ) {
            $query->next_post();
            $custom_fields = get_post_custom( $query->post->ID );
            $milliseconds = strtotime( $query->post->post_date ) * 1000;
            $pagespeed_scores[] = array( $milliseconds, $custom_fields['pagespeed_score'][0] );
            $page_load_times[] = array( $milliseconds, number_format( $custom_fields['page_load_time'][0] / 1000, 1 ) );
            $html_load_times[] = array( $milliseconds, number_format( $custom_fields['html_load_time'][0] / 1000, 1 ) );
            $html_bytes[] = array( $milliseconds, $custom_fields['html_bytes'][0] / 1024 );
            $page_bytes[] = array( $milliseconds, $custom_fields['page_bytes'][0] / 1024 );
        }
        $graph_data = array( );
        switch ( $graph ) {
            case 'gfw-scores-graph':
                $graph_data[] = array( 'label' => 'Pagespeed Score', 'data' => $pagespeed_scores );
                break;
            case 'gfw-times-graph':
                $graph_data[] = array( 'label' => 'Page Load Time', 'data' => $page_load_times );
                $graph_data[] = array( 'label' => 'HTML Load Time', 'data' => $html_load_times );
                break;
            case 'gfw-sizes-graph':
                $graph_data[] = array( 'label' => 'HTML Size', 'data' => $html_bytes );
                $graph_data[] = array( 'label' => 'Total Page Size', 'data' => $page_bytes );
                break;
        }
        echo json_encode( $graph_data );
        die();
    }

    public function reset_callback() {
        if ( check_ajax_referer( 'gfwnonce', 'security' ) ) {


            $args = array(
                'post_type' => 'hpwc_gfw_report',
                'posts_per_page' => -1
            );

            $query = new WP_Query( $args );

            while ( $query->have_posts() ) {
                $query->next_post();
                wp_delete_post( $query->post->ID );
            }
        }
        die();
    }

    protected function api() {
        $options = get_option( 'hpwc_hootproof_gfw_options' );
        require_once('lib/Services_WTF_Test.php');
        $api = new Services_WTF_Test();
        $api->api_username( $options['api_username'] );
        $api->api_password( $options['api_key'] );
        $api->user_agent( HPWC_GFW_USER_AGENT );
        return $api;
    }

    public function credits_meta_box() {
        $api = $this->api();
        $status = get_transient( 'credit_status' );

        if ( false === $status ) {
            $status = $api->status();
            set_transient( 'credit_status', $status, 60 * 2 );
        }

        if ( $api->error() ) {
            $response['error'] = $test->error();
            return $response;
        }
        ?>
        <p style="font-weight:bold">API Credits Remaining: <?php echo $status['api_credits']; ?></p>
        <p style="font-style:italic">Next top-up: <?php echo $this->wp_date( $status['api_refill'], true ); ?></p>
        <p>Every test costs 1 API credit, except tests that use video, which cost 5 credits. You are topped up to 20 credits per day. If you need more, you can purchase them from GTmetrix.com.</p>
        <a href="https://gtmetrix.com/pro/" target="_blank" class="button-secondary">Get More API Credits</a>
        <?php
    }

    protected function front_score( $dashboard = false ) {
        $args = array(
            'post_type' => 'hpwc_gfw_report',
            'posts_per_page' => 1,
            'orderby' => 'post_date',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => 'gfw_url',
                    'value' => array( trailingslashit( HPWC_HOOTPROOF_FRONT ), untrailingslashit( HPWC_HOOTPROOF_FRONT ) ),
                    'compare' => 'IN'
                ),
                array(
                    'key' => 'gtmetrix_test_id',
                    'value' => 0,
                    'compare' => '!='
                )
            ),
        );

        $query = new WP_Query( $args );

        echo '<input type="hidden" id="gfw-front-url" value="' . trailingslashit( HPWC_HOOTPROOF_FRONT ) . '" />';

        if ( $query->have_posts() ) {

            while ( $query->have_posts() ) {
                $query->next_post();
                $custom_fields = get_post_custom( $query->post->ID );
                $pagespeed_grade = $this->score_to_grade( $custom_fields['pagespeed_score'][0] );
                $expired = true;
                if ( $this->gtmetrix_file_exists( $custom_fields['report_url'][0] . '/screenshot.jpg' ) ) {
                    $expired = false;
                }
                if ( !$dashboard && !$expired ) {
                    echo '<img src="' . $custom_fields['report_url'][0] . '/screenshot.jpg" style="display: inline-block; margin-right: 10px; border-radius: 8px 8px 8px 8px;" />';
                }
                ?>

                <div class="gfw gfw-latest-report-wrapper">
                    <div class="gfw-box gfw-latest-report">
                        <div class="gfw-latest-report-pagespeed gfw-report-grade-<?php echo $pagespeed_grade['grade']; ?>">
                            <span class="gfw-report-grade"><?php echo $pagespeed_grade['grade']; ?></span>
                            <span class="gfw-report-title"><?php _e('Page Speed:', 'hootproof-check'); ?></span><br>
                            <span class="gfw-report-score">(<?php echo $custom_fields['pagespeed_score'][0]; ?>%)</span>
                        </div>
                        <div class="gfw-latest-report-details">
                            <b><?php _e('Page load time:', 'hootproof-check'); ?></b> <?php echo number_format( $custom_fields['page_load_time'][0] / 1000, 2 ) . __('seconds', 'hootproof-check'); ?><br />
                            <b><?php _e('Total page size:', 'hootproof-check'); ?></b> <?php echo size_format( $custom_fields['page_bytes'][0], 2 ); ?><br />
                            <b><?php _e('Total number of requests:', 'hootproof-check'); ?></b> <?php echo $custom_fields['page_elements'][0]; ?><br />
                        </div>
                    </div>
                    <p>
                        <?php
                        if ( !$expired ) {
                            echo '<a href="' . $custom_fields['report_url'][0] . '" target="_blank" class="gfw-report-icon">'. __('Detailed report', 'hootproof-check') .'</a> &nbsp;&nbsp; ';
                        }
                        ?>
                        <a href="<?php echo HPWC_GFW_SCHEDULE; ?>&report_id=<?php echo $query->post->ID; ?>" class="gfw-schedule-icon-large"><?php _e('Schedule tests','hootproof-check'); ?></a></p>
                    <p><a href="<?php echo HPWC_GFW_TESTS; ?>" class="button-primary" id="gfw-test-front"><?php _e('Re-test your Front Page','hootproof-check'); ?></a></p>
                </div>
                <?php
            }
        } else {
            echo sprintf(__('<h4>Your Front Page (%s) has not been analyzed yet</h4><p>Your front page is set in the <a href="%soptions-general.php">Settings</a> of your WordPress install.</p><p><a href="%s" class="button-primary" id="gfw-test-front">Test your Front Page now</a></p>','hootproof-check'), HPWC_HOOTPROOF_FRONT, get_admin_url(), HPWC_GFW_TESTS);
        }
    }

    public function score_meta_box() {
        $this->front_score( false );
    }

    public function test_meta_box() {
   
        $passed_url = isset( $_GET['url'] ) ? HPWC_HOOTPROOF_FRONT . $_GET['url'] : '';
        ?>
        <form method="post" id="gfw-parameters">
            <input type="hidden" name="post_type" value="gfw_report" />
            <div id="gfw-scan" class="gfw-dialog" title="<?php _e('Testing with GTmetrix','hootproof-check'); ?> ">
                <div id="gfw-screenshot"><img src="<?php echo HPWC_GFW_URL . '../images/scanner.png'; ?>" alt="" id="gfw-scanner" /><div class="gfw-message"></div></div>
            </div>
            <?php
            wp_nonce_field( plugin_basename( __FILE__ ), 'gfwtestnonce' );
            $options = get_option( 'hpwc_hootproof_gfw_options' );
            ?>

            <p><input type="text" id="gfw_url" name="gfw_url" value="<?php echo $passed_url; ?>" placeholder="<?php _e('You can enter a URL (eg http://yourdomain.com), or start typing the title of your page/post','hootproof-check'); ?>" /><br />
                <span class="gfw-placeholder-alternative description"><?php _e('You can enter a URL (eg http://yourdomain.com), or start typing the title of your page/post','hootproof-check'); ?></span></p>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('Label','hootproof-check'); ?></th>
                    <td><input type="text" id="gfw_label" name="gfw_label" value="" /><br />
                        <span class="description"><?php _e('Optionally enter a label for your report','hootproof-check'); ?></span></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Locations','hootproof-check'); ?><a class="gfw-help-icon tooltip" href="#" title="<?php _e('Analyze the performance of the page from one of our several test regions.  Your Page Speed score usually stays roughly the same, but Page Load times and Waterfall should be different. Use this to see how latency affects your page load times from different parts of the world.','hootproof-check'); ?>"></a></th>
                    <td><select name="gfw_location" id="gfw_location">
                            <?php
                            foreach ( $options['locations'] as $location ) {
                                echo '<option value="' . $location['id'] . '" ' . selected( isset( $options['default_location'] ) ? $options['default_location'] : $location['default'], $location['id'], false ) . '>' . $location['name'] . '</option>';
                            }
                            ?>
                        </select><br />
                        <span class="description"><?php _e('Test Server Region','hootproof-check'); ?></span></td>
                </tr>
            </table>


            <?php submit_button( __('Test URL now!','hootproof-check'), 'primary', 'submit', false ); ?>
        </form>
        <?php
    }

    public function schedule_meta_box() {
        $report_id = isset( $_GET['report_id'] ) ? $_GET['report_id'] : 0;
        $event_id = isset( $_GET['event_id'] ) ? $_GET['event_id'] : 0;
        $cpt_id = $report_id ? $report_id : $event_id;
        $custom_fields = get_post_custom( $cpt_id );
        $options = get_option( 'hpwc_hootproof_gfw_options' );
        $grades = array( 90 => 'A', 80 => 'B', 70 => 'C', 60 => 'D', 50 => 'E', 40 => 'F' );

        if ( empty( $custom_fields ) ) {
            echo '<p>' . __('Event not found.','hootproof-check') .'</p>';
            return false;
        }
        ?>
        <form method="post">
            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>" />
            <input type="hidden" name="report_id" value="<?php echo $report_id; ?>" />
            <?php wp_nonce_field( plugin_basename( __FILE__ ), 'gfwschedulenonce' ); ?>

            <p><b><?php _e('URL/label:','hootproof-check'); ?></b> <?php echo ($custom_fields['gfw_label'][0] ? $custom_fields['gfw_label'][0] . ' (' . $custom_fields['gfw_url'][0] . ')' : $custom_fields['gfw_url'][0]); ?></p>
            <p><b><?php _e('Location:','hootproof-check'); ?></b> Vancouver, Canada <i><?php _e('(scheduled tests always use the Vancouver, Canada test server region)','hootproof-check'); ?></i></p>


            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('Frequency','hootproof-check'); ?></th>
                    <td><select name="gfw_recurrence" id="gfw_recurrence">
                            <?php
                            foreach ( array( __('Hourly','hootproof-check') => 'hourly', __('Daily','hootproof-check') => 'daily' ) as $name => $recurrence ) {
                                echo '<option value="' . $recurrence . '" ' . selected( isset( $custom_fields['gfw_recurrence'][0] ) ? $custom_fields['gfw_recurrence'][0] : 'daily', $recurrence, false ) . '>' . $name . '</option>';
                            }
                            ?>
                        </select><br />
                        <span class="description"><?php _e('Note: every report will use up 1 of your API credits on GTmetrix.com','hootproof-check'); ?></span></td>
                </tr>
                <?php
                if ( isset( $custom_fields['gfw_notifications'][0] ) ) {
                    $notifications = unserialize( $custom_fields['gfw_notifications'][0] );
                    $notifications_count = count( $notifications );
                } else {
                    // display a disabled, arbitrary condition if no conditions are already set
                    $notifications = array( 'pagespeed_score' => 90 );
                    $notifications_count = 0;
                }
                ?>

                <tr valign="top">
                    <th scope="row">Status</th>
                    <td><select name="gfw_status" id="gfw_status">
                            <?php
                            foreach ( array( 1 => __('Active','hootproof-check'), 2 => __('Paused','hootproof-check'), 3 => __('Paused due to recurring failures','hootproof-check') ) as $key => $status ) {
                                echo '<option value="' . $key . '" ' . selected( isset( $custom_fields['gfw_status'][0] ) ? $custom_fields['gfw_status'][0] : 1, $key, false ) . '>' . $status . '</option>';
                            }
                            ?>
                        </select></td>
                </tr>

            </table>
            <?php
            submit_button( __('Save','hootproof-check'), 'primary', 'submit', false );
            echo '</form>';
        }

        public function reports_list() {
        
            $args = array(
                'post_type' => 'hpwc_gfw_report',
                'posts_per_page' => -1,
                'meta_key' => 'gfw_event_id',
                'meta_value' => 0
            );
            $query = new WP_Query( $args );
            $no_posts = !$query->post_count;
            ?>
            <p><?php _e('Click a report to see more detail, or to schedule future tests.','hootproof-check');?></p>
            <div class="gfw-table-wrapper">
                <table class="gfw-table">
                    <thead>
                        <tr style="display: <?php echo $no_posts ? 'none' : 'table-row' ?>">
                            <th class="gfw-reports-url"><?php _e('Label/URL','hootproof-check');?></th>
                            <th class="gfw-reports-pagespeed"><?php _e('Page Speed','hootproof-check');?></th>
                            <th class="gfw-reports-load-time"><?php _e('Page Load Time','hootproof-check');?></th>
                            <th class="gfw-reports-last"><?php _e('Date','hootproof-check');?></th>
                            <th class="gfw-reports-delete"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $row_number = 0;
                        while ( $query->have_posts() ) {
                            $query->next_post();
                            $custom_fields = get_post_custom( $query->post->ID );
                            foreach ( $custom_fields as $name => $value ) {
                                $$name = $value[0];
                            }

                            if ( !isset( $gtmetrix_error ) ) {
                                $pagespeed_grade = $this->score_to_grade( $pagespeed_score );
                            }
                            $report_date = $this->wp_date( $query->post->post_date, true );
                            $title = $gfw_label ? $gfw_label : $this->append_http( $gfw_url );

                            echo '<tr class="' . ($row_number++ % 2 ? 'even' : 'odd') . '" id="post-' . $query->post->ID . '">';

                            if ( isset( $gtmetrix_error ) ) {
                                echo '<td class="gfw-reports-url">' . $title . '</td>';
                                echo '<td class="reports-error" colspan="3">' . $this->translate_message( $gtmetrix_error ) . '</td>';
                                echo '<td>' . $report_date . '</td>';
                            } else {
                                echo '<td title="' . __('Click to expand/collapse','hootproof-check') . '" class="gfw-reports-url gfw-toggle tooltip">' . $title . '</td>';
                                echo '<td class="gfw-toggle gfw-reports-pagespeed"><div class="gfw-grade-meter gfw-grade-meter-' . $pagespeed_grade['grade'] . '" style="background-position: ' . $pagespeed_grade['position'] . '">' . $pagespeed_grade['grade'] . ' (' . $pagespeed_score . ')</div></td>';
                                echo '<td class="gfw-toggle">' . number_format( $page_load_time / 1000, 2 ) . ' seconds</td>';
                                echo '<td class="gfw-toggle">' . $report_date . '</td>';
                            }
                            echo '<td><a href="' . HPWC_GFW_SCHEDULE . '&report_id=' . $query->post->ID . '" class="gfw-schedule-icon-small tooltip" title="' . __('Schedule tests','hootproof-check') . '">'. __('Schedule test', 'hootproof-check') . '</a> <a href="' . HPWC_GFW_TESTS . '&delete=' . $query->post->ID . '" rel="#gfw-confirm-delete" class="gfw-delete-icon delete-report tooltip" title="' . __('Delete Report','hootproof-check') . '">' . __('Delete Report','hootproof-check') . '</a></td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
                <?php
                if ( $no_posts ) {
                    echo '<p class="gfw-no-posts">' . esc_html__('You have no reports yet. Go to the Tests Page to start your first test.', 'hootproof-check') . '</p>';
                }
                ?>
            </div>
            <?php
        }

        public function events_list() {

            $args = array(
                'post_type' => 'hpwc_gfw_event',
                'posts_per_page' => -1,
                'meta_key' => 'gfw_recurrence'
            );
            $query = new WP_Query( $args );
            $no_posts = !$query->post_count;
            ?>

            <div id="gfw-graph" class="gfw-dialog" title="">
                <div id="gfw-flot-placeholder"></div>
                <div class="graph-legend" id="gfw-graph-legend"></div>
            </div>

            <div class="gfw-table-wrapper">
                <table class="gfw-table events">
                    <thead>
                        <tr style="display: <?php echo $no_posts ? 'none' : 'table-row' ?>">
                            <th><?php _e('Label/URL', 'hootproof-check'); ?></th>
                            <th><?php _e('Frequency', 'hootproof-check'); ?></th>
                            <th><?php _e('Last Report', 'hootproof-check'); ?></th>
                            <th><?php _e('Next Report', 'hootproof-check'); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $row_no = 0;
                        $next_report['hourly'] = wp_next_scheduled( 'hpwc_gfw_hourly_event');
                        $next_report['daily'] = wp_next_scheduled( 'hpwc_gfw_daily_event');
                        //$next_report['weekly'] = wp_next_scheduled( 'hpwc_gfw_weekly_event', array( 'weekly' ) );
                        //$next_report['monthly'] = wp_next_scheduled( 'hpwc_gfw_monthly_event', array( 'monthly' ) );

                        while ( $query->have_posts() ) {
                            $query->next_post();

                            $custom_fields = get_post_custom( $query->post->ID );
                            if ( $custom_fields['gfw_event_error'][0] ) {
                                $gtmetrix_error = get_post_meta( $custom_fields['gfw_last_report_id'][0], 'gtmetrix_error', true );
                            }
                            $last_report = isset( $custom_fields['gfw_last_report'][0] ) ? $this->wp_date( $custom_fields['gfw_last_report'][0], true ) : __('Pending', 'hootproof-check');

                            $title = $custom_fields['gfw_label'][0] ? $custom_fields['gfw_label'][0] : $custom_fields['gfw_url'][0];
                            $row = '<tr class="' . ($row_no % 2 ? 'even' : 'odd') . '" id="post-' . $query->post->ID . '">';
                            $toggle_title = ' title="' . __('Click to expand/collapse.', 'hootproof-check') .'" ';
                            $toggle_class = 'gfw-toggle tooltip';
                            if ( isset( $gtmetrix_error ) ) {
                                $toggle_title = '';
                                $toggle_class = '';
                            }

                            $row .= '<td class="' . $toggle_class . ' gfw-reports-url"' . $toggle_title . '>' . $title . '</td>';
                            $row .= '<td class="' . $toggle_class . '"' . $toggle_title . '>' . ucwords( $custom_fields['gfw_recurrence'][0] ) . '</div></td>';
                            //$row .= '<td class="' . $toggle_class . '"' . $toggle_title . '>' . (isset( $custom_fields['gfw_notifications'][0] ) ? __('Enabled', 'hootproof-check')  : __('Disabled', 'hootproof-check')) . '</div></td>';
                            $row .= '<td class="' . $toggle_class . '"' . $toggle_title . '>' . $last_report . ($custom_fields['gfw_event_error'][0] ? ' <span class="gfw-failed tooltip" title="' . $gtmetrix_error . '">(' . __('failed', 'hootproof-check'). ')</span>' : '') . '</td>';
                            $row .= '<td class="' . $toggle_class . '"' . $toggle_title . '>' . $this->wp_date( $next_report[$custom_fields['gfw_recurrence'][0]], true ) . '</td>';
                            $row .= '<td><a href="' . HPWC_GFW_SCHEDULE . '&event_id=' . $query->post->ID . '" rel="" class="gfw-edit-icon tooltip" title="Edit this event">' . __('Edit', 'hootproof-check') .'</a> <a href="' . HPWC_GFW_SCHEDULE . '&delete=' . $query->post->ID . '" rel="#gfw-confirm-delete" title="' . __('Delete this event', 'hootproof-check') .'" class="gfw-delete-icon delete-event tooltip">'. __('Delete Event', 'hootproof-check') .'</a> <a href="' . HPWC_GFW_SCHEDULE . '&status=' . $query->post->ID . '" class="tooltip gfw-pause-icon' . (1 == $custom_fields['gfw_status'][0] ? '" title="'. __('Pause this event', 'hootproof-check') .'">'. __('Pause Event', 'hootproof-check') : ' paused" title="'. __('Reactivate this event', 'hootproof-check') .'">'. __('Reactivate Event', 'hootproof-check') ) . '</a></td>';
                            $row .= '</tr>';
                            echo $row;
                            $row_no++;
                        }
                        ?>
                    </tbody>
                </table>

                <?php
                if ( $no_posts ) {
                    echo '<p class="gfw-no-posts">'
                    . sprintf(__('You have no Scheduled Tests. Go to <a href="%s">Performance Tests</a> to create one.', 'hootproof-check'), HPWC_GFW_TESTS)
                    . '</p>';
                }
                ?>

            </div>

            <div id="gfw-confirm-delete" class="gfw-dialog" title="Delete this event?">
                <p><?php _e('Are you sure you want to delete this event?', 'hootproof-check'); ?></p>
                <p><?php _e('This will delete all the reports generated so far by this event.', 'hootproof-check'); ?></p>
            </div>


            <?php
        }

        protected function translate_message( $message ) {

            if ( 0 === stripos( $message, 'Maximum number of API calls reached.' ) ) {
                $message = __('Maximum number of GTmetrix API calls reached. Wait until the next top-up or <a href="https://gtmetrix.com/pro/" target="_blank" title="Go Pro">go Pro</a> to receive bigger daily top-ups and other benefits.','hootproof-check');
            }
            return $message;
        }

        public function authenticate_meta_box() {
            if ( !HPWC_GFW_AUTHORIZED ) {
                echo '<p style="font-weight:bold">'
                . __('You must have an API key to use this plugin.', 'hootproof-check')
                . '</p><p>' 
                . __('To get an API key, register for a free account at gtmetrix.com and generate one in the API section.', 'hootproof-check')
                . '</p><p><a href="http://gtmetrix.com/api/" target="_blank">'
                . __('Register for a GTmetrix account now', 'hootproof-check')
                . ' &raquo;</a></p>';
            }
            echo '<table class="form-table">';
            do_settings_fields( 'hpwc_hootproof_gfw_settings', 'authentication_section' );
            echo '</table>';
        }

        public function options_meta_box() {
            echo '<table class="form-table">';
            do_settings_fields( 'hpwc_hootproof_gfw_settings', 'options_section' );
            echo '</table>';
        }

        public function widget_meta_box() {
            echo '<table class="form-table">';
            do_settings_fields( 'hpwc_hootproof_gfw_settings', 'widget_section' );
            echo '</table>';
        }

        public function reset_meta_box() {
            echo '<table class="form-table">';
            do_settings_fields( 'hpwc_hootproof_gfw_settings', 'reset_section' );
            echo '</table>';
        }

        protected function score_to_grade( $score ) {
            $grade = array( );
            $grade['grade'] = $score >= 50 ? '&#' . (74 - floor( $score / 10 )) . ';' : 'F';
            $grade['position'] = '-' . (100 - $score) . 'px -' . ($score >= 50 ? (9 - floor( $score / 10 )) * 15 : 75) . 'px';
            return $grade;
        }

        protected function gtmetrix_file_exists( $url ) {
            $options = get_option( 'hpwc_hootproof_gfw_options' );
            $ch = curl_init();
            curl_setopt( $ch, CURLOPT_URL, $url );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt( $ch, CURLOPT_NOBODY, true );
            if ( curl_exec( $ch ) !== false ) {
                $curl_info = curl_getinfo( $ch );
                if ( $curl_info['http_code'] == 200 ) {
                    return true;
                }
                return false;
            } else {
                echo curl_error( $ch );
                return false;
            }
        }

        protected function append_http( $url ) {
            if ( stripos( $url, 'http' ) === 0 || !$url ) {
                return $url;
            } else {
                return 'http://' . $url;
            }
        }

        protected function wp_date( $date_time, $time = false ) {
            date_default_timezone_set( HPWC_GFW_TIMEZONE );
            $local_date_time = date( get_option( 'date_format' ) . ($time ? ' ' . get_option( 'time_format' ) : ''), (is_numeric( $date_time ) ? $date_time : strtotime( $date_time ) ) );
            return $local_date_time;
        }

    }
}
   