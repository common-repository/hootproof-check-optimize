<?php
/*
Plugin Name: HootProof Check & Optimize
Plugin URI: https://hootproof.de/hootproof-plugin
Description: Check & optimize WordPress with individual suggestions for security, performance, SEO, privacy. Especially for EU/German privacy laws.
Version: 1.0
Author: Michelle Retzlaff
Author URI: https://hootproof.de/about
Text Domain: hootproof-check
Domain Path: /languages/
License: GPLv2 or later

    Copyright 2015  Michelle Retzlaff  (email : michelle@hootproof.de)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
 
 //load other classes
require_once(dirname( __FILE__ ) . '/includes/class-hootproof-general-checks.php');
require_once(dirname( __FILE__ ) . '/includes/class-hootproof-new-comment-ips.php');
require_once(dirname( __FILE__ ) . '/includes/class-hootproof-footer-branding.php');
require_once(dirname( __FILE__ ) . '/includes/class-hootproof-gtmetrix.php');


class HootProof_Website_Check {

    public function __construct() {
 
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
   
        if ( is_admin() ) {
           add_action( 'init', array( &$this, 'construct_admin_dependencies' ), 0 );           
        }
        else {
           add_action( 'init', array( &$this, 'construct_frontend_dependencies' ), 0 );
        }
        
        add_action( 'admin_init', array( &$this, 'register_settings' ) );
        add_action( 'admin_init', array( &$this, 'system_check' ), 0 );
        add_action( 'admin_menu', array( &$this, 'add_menu_items' ) );
        add_action( 'admin_print_styles', array( &$this, 'admin_styles' ) );
        add_action( 'admin_notices', array( &$this, 'admin_notices' ) );
        add_filter( 'plugin_row_meta', array( &$this, 'plugin_links' ), 10, 2 );
        
        add_action( 'hpwc_gfw_hourly_event', array( $this, 'schedule_wrapper_hourly') );
        add_action( 'hpwc_gfw_daily_event', array( $this, 'schedule_wrapper_daily') );
   
        add_action( 'wp_ajax_expand_report', array( &$this->gfw, 'expand_report_callback' ) );

        //prepare for translation
        load_plugin_textdomain( 'hootproof-check', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
        load_plugin_textdomain( 'tgmpa', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
        
        //dummy translations for plugin header data
        __('Check & optimize WordPress with individual suggestions for security, performance, SEO, privacy. Especially for EU/German privacy laws.','hootproof-check');

        $options = get_option( 'hpwc_hootproof_options' );
        //define( 'HPWC_PW_AUTHORIZED', isset( $options['pw_authorized'] ) && $options['pw_authorized'] ? true : false );
        define('HPWC_PW_AUTHORIZED', true);
        define( 'HPWC_HOOTPROOF_WP_VERSION', '3.3.1' );
        define( 'HPWC_HOOTPROOF_VERSION', '1.0' );
        
        define( 'HPWC_HOOTPROOF_TIMEZONE', get_option( 'timezone_string' ) ? get_option( 'timezone_string' ) : date_default_timezone_get() );
        define( 'HPWC_HOOTPROOF_URL', plugins_url( '/', __FILE__ ) );
        define( 'HPWC_HOOTPROOF_SETTINGS', get_admin_url() . 'admin.php?page=hootproof_menu_settings' );
        define( 'HPWC_HOOTPROOF_TESTS', get_admin_url() . 'admin.php?page=hootproof_menu_gfw_tests' );
        define( 'HPWC_HOOTPROOF_FRONT', get_home_url( null, '', 'http' ) );       
 }

    public function schedule_wrapper_hourly() {
       $this->gfw = new HootProof_GTMetrix();
       $this->gfw->scheduled_events('hourly');
    }

    public function schedule_wrapper_daily() {
       $this->gfw = new HootProof_GTMetrix();
       $this->gfw->scheduled_events('daily');
    }
    
    
    public function activate() {
        wp_schedule_event( mktime( date( 'H' ) + 1, 10, 0 ), 'hourly', 'hpwc_gfw_hourly_event');
        wp_schedule_event( mktime( date( 'H' ) + 1, 10, 0 ), 'daily', 'hpwc_gfw_daily_event' );
    
        $role = get_role( 'administrator' );
        $role->add_cap( 'access_hootproof_check' );

        $options = get_option( 'hpwc_hootproof_options' );
        $options['front_url'] = isset( $options['front_url'] ) ? $options['front_url'] : 'wp';
        $options['remove_comment_ips'] = true;
        $options['show_branding'] = false;
        update_option( 'hpwc_hootproof_options', $options );
    }

    public function deactivate() {
        wp_clear_scheduled_hook( 'hpwc_gfw_hourly_event');
        wp_clear_scheduled_hook( 'hpwc_gfw_daily_event');
    }

    public function system_check() {
        global $wp_version;
        $plugin = plugin_basename( __FILE__ );
        if ( is_plugin_active( $plugin ) ) {
            if ( version_compare( $wp_version, HPWC_HOOTPROOF_WP_VERSION, '<' ) ) {
                $message = '<p>HootProof requires WordPress ' . HPWC_HOOTPROOF_WP_VERSION . ' or higher. ';
            } elseif ( !function_exists( 'curl_init' ) ) {
                $message = '<p>HootProof requires cURL to be enabled. ';
            }
            if ( isset( $message ) ) {
                deactivate_plugins( $plugin );
                wp_die( $message . 'Deactivating Plugin.</p><p>Back to <a href="' . admin_url() . '">WordPress admin</a>.</p>' );
            }
        }
    }

    public function construct_admin_dependencies() {    
       $this->general_checks = new HootProof_General_Checks();
       $this->gfw = new HootProof_GTMetrix();
       $this->gfw->enqueue_scripts();
    }
    
    public function construct_frontend_dependencies() {
       $this->new_comment_ips = new HootProof_New_Comment_IPs();
       $this->footer_branding = new HootProof_Footer_Branding();
    }
    
    public function plugin_links( $links, $file ) {
        if ( $file == plugin_basename( __FILE__ ) ) {
            return array_merge( $links, array( sprintf( '<a href="%1$s">%2$s</a>', HPWC_HOOTPROOF_SETTINGS, 'Settings' )) );
        }
        return $links;
    }

    public function add_menu_items() {
         
       add_menu_page('HootProof',
                     'HootProof',
                     'manage_options',
                     'hootproof_menu',  // slug
                       array( $this, 'dashboard_page') 
                     //plugins_url( '/images/wp-icon.png', __FILE__ ), //TODO
                    );
                    
       $this->dashboard_page_hook = add_submenu_page( 'hootproof_menu', //parent slug
                        'HootProof ' . _x( 'Check Results', 'menu', 'hootproof-check' ),
                        _x( 'Check Results', 'menu', 'hootproof-check' ), 
                      'manage_options',
                      'hootproof_menu', 
                       array( $this, 'dashboard_page') );
       
       $this->settings_page_hook =  add_submenu_page( 'hootproof_menu', //parent slug
                       'HootProof ' . __( 'Settings', 'hootproof-check' ),
                       _x('General Settings','menu', 'hootproof-check') , 
                       'manage_options',
                       'hootproof_menu'.'_settings', 
                       array($this, 'settings_page') );
        
        
        /* GTMetrix */
        
         $this->gfw->settings_page_hook = add_submenu_page( 'hootproof_menu', __('HootProof Performance Settings', 'hootproof-check'), __('Performance Settings', 'hootproof-check'), 'manage_options', 'hootproof_menu_gfw_settings', array( $this->gfw, 'settings_page' ) );
   
        
        if ( HPWC_GFW_AUTHORIZED ) {
            
            $this->gfw->tests_page_hook = add_submenu_page( 'hootproof_menu', _x('HootProof Performance Tests', 'menu', 'hootproof-check'), _x('Performance Tests', 'menu', 'hootproof-check'), 'manage_options', 'hootproof_menu_gfw_tests', array( $this->gfw, 'tests_page' ) );
            $this->gfw->schedule_page_hook = add_submenu_page( 'hootproof_menu', _x('HootProof Test Schedule', 'menu', 'hootproof-check'), _x('Test Schedule', 'menu', 'hootproof-check'), 'manage_options', 'hootproof_menu_gfw_schedule', array( $this->gfw, 'schedule_page' ) );
            add_action( 'load-' . $this->gfw->tests_page_hook, array( &$this->gfw, 'page_loading' ) );
            add_action( 'load-' . $this->gfw->schedule_page_hook, array( &$this->gfw, 'page_loading' ) );
        } 
        
        add_action( 'load-' . $this->settings_page_hook, array( &$this->gfw, 'page_loading' ) );
       
       
       //side
		add_meta_box( 'hpwc-upgrade-meta-box', __('Want more? Upgrade to Premium!', 'hootproof-check'), array( &$this, 'upgrade_meta_box' ), $this->gfw->settings_page_hook, 'side', 'default' );
		add_meta_box( 'hpwc-support-meta-box', __('Problems? Ask support!', 'hootproof-check'), array( &$this, 'support_meta_box' ), $this->gfw->settings_page_hook, 'side', 'default' );
		add_meta_box( 'hpwc-resources-meta-box', __('More Resources', 'hootproof-check'), array( &$this, 'resources_meta_box' ), $this->gfw->settings_page_hook, 'side', 'default' );
		
		add_meta_box( 'hpwc-upgrade-meta-box', __('Want more? Upgrade to Premium!', 'hootproof-check'), array( &$this, 'upgrade_meta_box' ), $this->gfw->tests_page_hook, 'side', 'default' );
		add_meta_box( 'hpwc-support-meta-box', __('Problems? Ask support!', 'hootproof-check'), array( &$this, 'support_meta_box' ), $this->gfw->tests_page_hook, 'side', 'default' );
		add_meta_box( 'hpwc-resources-meta-box', __('More Resources', 'hootproof-check'), array( &$this, 'resources_meta_box' ), $this->gfw->tests_page_hook, 'side', 'default' );
       
       
    }

    public function admin_notices() {
    
       if ( !HPWC_PW_AUTHORIZED ) {
            echo $this->set_notice( sprintf(__('<strong>HootProof Check &amp; Optimize is almost ready.</strong> Please <a href="%s">enter the HootProof password</a> to make it work.', 'hootproof-check') , HPWC_HOOTPROOF_SETTINGS) );
        }
        
        $notice = get_transient( 'admin_notice' );
        if ( $notice ) {
            echo $this->set_notice( $notice );
            delete_transient( 'admin_notice' );
        }
    }

    public function register_settings() {
         
        register_setting( 'hpwc_hootproof_options_group', 'hpwc_hootproof_options', array( &$this, 'sanitize_settings' ) );
         
        // password
        //add_settings_field( 'password', __('HootProof Plugin Password', 'hootproof-check'), array( &$this, 'set_password' ), 'hpwc_hootproof_settings', 'password_section' );
        
        //basic settings
        add_settings_section( 'general_options_section', '', array( &$this, 'section_text' ), 'hpwc_hootproof_settings' );
        add_settings_field( 'front_url', __('Front page URL', 'hootproof-check'), array( &$this, 'set_front_url' ), 'hpwc_hootproof_settings', 'general_options_section' );
        add_settings_field( 'show_branding', // id
		                    __('Show branding','hootproof-check'), //label
							array(&$this, 'set_show_branding'), //Callback
							'hpwc_hootproof_settings',
							'general_options_section');
             
        //privacy settings
        add_settings_section( 'privacy_options_section', '', array( &$this, 'section_text' ), 'hpwc_hootproof_settings' );
        add_settings_field( 'remove_comment_ips', __('Remove comment IPs', 'hootproof-check'), array( &$this, 'set_remove_comment_ips' ), 'hpwc_hootproof_settings', 'privacy_options_section' ); 
        
    }
    
    public function set_password() {
        $options = get_option( 'hpwc_hootproof_options' );
        echo '<input type="text" name="hpwc_hootproof_options[password]" id="password" value="' . (isset( $options['password'] ) ? $options['password'] : '') . '" />';
    }
    
    public function sanitize_settings( $input ) {

        $valid = array( );
        
        
        /*
        $valid['pw_authorized'] = 0;
    
        //validate password
        if($input['password'] != 'hoot15AUplugin' ) {
           if ( !get_settings_errors( 'hpwc_hootproof_options' ) ) {
                add_settings_error( 'hpwc_hootproof_options', 'password_error', __('Invalid password', 'hootproof-check') );
            }
        }
        else {
           $valid['pw_authorized'] = 1;
        }
        $valid['password'] = $input['password'];
        
        //end validate password
        */
        
        $options = get_option( 'hpwc_hootproof_options' );
        
        $valid['front_url'] = isset( $input['front_url'] ) ? $input['front_url'] : $options['front_url'];
        $valid['remove_comment_ips'] =  isset( $input['remove_comment_ips'] ); //? $input['remove_comment_ips'] : false;       
        //(isset( $options['remove_comment_ips'] ) ? $options['remove_comment_ips'] : 'off');
        
        $valid['show_branding'] =  isset( $input['show_branding'] ) ;//? $input['show_branding'] : 0;        
        //(isset( $options['show_branding'] ) ? $options['show_branding'] : 'off');
        
        return $valid;
    }

    public function section_text() {
        // Placeholder for settings section (which is required for some reason)
    }

    protected function set_notice( $message, $class = 'updated' ) {
        return '<div class="' . $class . '"><p>' . $message . '</p></div>';
    }

    /*
       Functions for options fields
    */
    public function set_front_url() {
        $options = get_option( 'hpwc_hootproof_options' );
        
        echo '<p><select name="hpwc_hootproof_options[front_url]" id="front_url">';
        foreach ( array( 'wp' => sprintf(__('WordPress Address (%s)', 'hootproof-check'), site_url()), 
                        'site' => sprintf(__('Site Address (%s)', 'hootproof-check'), home_url()) ) as $key => $value ) {
            echo '<option value="' . $key . '" ' . selected( $options['front_url'], $key, false ) . '>' . $value . '</option>';
        }
        echo '</select></p>';
    }
    
    public function set_remove_comment_ips() {
    
    	$options = get_option( 'hpwc_hootproof_options' );
    	$remove_comment_ips = $options['remove_comment_ips'];
	    
	    echo "<input id='remove_comment_ips' name='hpwc_hootproof_options[remove_comment_ips]' type='checkbox'". checked( $remove_comment_ips, 1, false ). " />";
    }
    
    public function set_show_branding() {
    
		$options = get_option( 'hpwc_hootproof_options' );
		$show_branding = $options['show_branding'];
	
		echo "<input id='hpwc_hootproof_show_branding' name='hpwc_hootproof_options[show_branding]' type='checkbox' ". checked( $show_branding, 1, false ). " />";
}

    // END Functions for options fields
    
       
    public function settings_page() {
    
       //add_meta_box( 'password-meta-box', __('Authentication', 'hootproof-check'), array( &$this, 'password_meta_box' ), $this->settings_page_hook, 'normal', 'core' );
       
       
       if ( HPWC_PW_AUTHORIZED ) {
        
         global $screen_layout_columns;
	 	 wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
	     wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
	
	 
         if (isset($_GET['hpwc_remove_comment_ips_nonce']) && wp_verify_nonce($_GET['hpwc_remove_comment_ips_nonce'], 'remove_comment_ips')) {      
            $this->remove_comment_ips();
            echo $this->set_notice( __('Comment IPs masked', 'hootproof-check'));
         } 
       
		
		//main
		add_meta_box( 'hpwc-general-options-meta-box', __('General Options', 'hootproof-check'), array( &$this, 'general_options_meta_box' ), $this->settings_page_hook, 'normal', 'core' );
		add_meta_box( 'hpwc-privacy-options-meta-box', __('Privacy Options', 'hootproof-check'), array( &$this, 'privacy_options_meta_box' ), $this->settings_page_hook, 'normal', 'core' );
	
	   } // end if HPWC_PW_AUTHORIZED
	
		//side
		add_meta_box( 'hpwc-upgrade-meta-box', __('Want more? Upgrade to Premium!', 'hootproof-check'), array( &$this, 'upgrade_meta_box' ), $this->settings_page_hook, 'side', 'core' );
	
		add_meta_box( 'hpwc-support-meta-box', __('Problems? Ask support!', 'hootproof-check'), array( &$this, 'support_meta_box' ), $this->settings_page_hook, 'side', 'core' );
		add_meta_box( 'hpwc-resources-meta-box', __('More Resources', 'hootproof-check'), array( &$this, 'resources_meta_box' ), $this->settings_page_hook, 'side', 'core' );
	
	
		?>
		<div class="wrap gfw">
			<div id="gfw-icon" class="icon32"></div>
			<h2><?php _e('HootProof Settings', 'hootproof-check'); ?></h2>
			<?php settings_errors( 'hpwc_hootproof_options', false ); ?>
			<div id="poststuff" class="metabox-holder has-right-sidebar">
				<div id="side-info-column" class="inner-sidebar">
					<?php do_meta_boxes( $this->settings_page_hook, 'side', 0 ); ?>
				</div>
				<div id="post-body" class="has-sidebar">
					<div id="post-body-content" class="has-sidebar-content">
						<form method="post" action="options.php">
							<?php
							wp_nonce_field( 'update-options' );
							settings_fields( 'hpwc_hootproof_options_group' );
							do_meta_boxes( $this->settings_page_hook, 'normal', 0 );
							submit_button( __('Save Changes', 'hootproof-check'), 'primary', 'submit', false );
							?>
						</form>
					</div>
				</div>
			</div>	
		</div>
		<?php
   
        
    } // end settings_page
    
    public function password_meta_box() {
       if ( !HPWC_PW_AUTHORIZED ) {
                echo '<p style="font-weight:bold">'
                . __('You need a password to use this plugin.', 'hootproof-check')
                . '</p><p>' 
                . __('To get the password, sign up for the free HootProof newsletter. The password will be sent to you as soon as your free subscription is confirmed.', 'hootproof-check')
                . '</p><p><a href="https://hootproof.de/newsletter/" target="_blank">'
                . __('Sign up &amp; get password', 'hootproof-check')
                . ' &raquo;</a></p>';
            }
            echo '<table class="form-table">';
            do_settings_fields( 'hpwc_hootproof_settings', 'password_section' );
            echo '</table>';
        }
        
   public function dashboard_page() {
   
       if ( HPWC_PW_AUTHORIZED ) {
          
           //main     
           add_meta_box( 'hpwc-check-results-meta-box', __('Check Results', 'hootproof-check'), array( &$this->general_checks, 'check_results_meta_box' ), $this->dashboard_page_hook, 'normal', 'core' );
        
           //add_meta_box( 'hpwc-performance-results-meta-box', __('Performance Results', 'hootproof-check'), array( &$this->gfw, 'reports_list' ), $this->dashboard_page_hook, 'normal', 'core' );
        
           add_meta_box( 'hpwc-notices-meta-box', __('Privacy Notices', 'hootproof-check'), array( &$this->general_checks, 'notices_meta_box' ), $this->dashboard_page_hook, 'normal', 'core' );
         
        } // end if HPWC_PW_AUTHORIZED
        
        //side
        add_meta_box( 'hpwc-upgrade-meta-box', __('Want more? Upgrade to Premium!', 'hootproof-check'), array( &$this, 'upgrade_meta_box' ), $this->dashboard_page_hook, 'side', 'core' );
        
        add_meta_box( 'hpwc-support-meta-box', __('Problems? Ask support!', 'hootproof-check'), array( &$this, 'support_meta_box' ), $this->dashboard_page_hook, 'side', 'core' );
        add_meta_box( 'hpwc-resources-meta-box', __('More Resources', 'hootproof-check'), array( &$this, 'resources_meta_box' ), $this->dashboard_page_hook, 'side', 'core' );
     

        ?>
        <div class="wrap gfw">
            <div id="gfw-icon" class="icon32"></div>
            <h2>HootProof  &raquo; Dashboard</h2>
            <?php settings_errors( 'hpwc_hootproof_options', false ); ?>
            <div id="poststuff" class="metabox-holder has-right-sidebar">
                <div id="side-info-column" class="inner-sidebar">
                    <?php do_meta_boxes( $this->dashboard_page_hook, 'side', 0 ); ?>
                </div>
                <div id="post-body" class="has-sidebar">
                    <div id="post-body-content" class="has-sidebar-content">
                       
                            <?php
                            do_meta_boxes( $this->dashboard_page_hook, 'normal', 0 );
                            ?>
                    </div>
                </div>
            </div>	
        </div>
        <?php
        
    } // end dashboard_page

   /*
      META BOXES
   */
   public function general_options_meta_box() {
            echo '<table class="form-table">';
            do_settings_fields( 'hpwc_hootproof_settings', 'general_options_section' );
            echo '</table>';      
    }
   
   public function privacy_options_meta_box() {
            echo '<table class="form-table">';
            do_settings_fields( 'hpwc_hootproof_settings', 'privacy_options_section' );
            echo '</table>';
            
            //query comment count with real IP addresses
            global $wpdb;
            $sql = "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_author_ip  NOT LIKE '127.0%'";
            $comments_count = $wpdb->get_var($sql);
            
            echo '<p><strong>' . sprintf( _n( 'Still %s comment with an actual IP address.', 'Still %s comments with actual IP addresses.', $comments_count, 'hootproof-check' ), $comments_count) . '</strong></p>'; 
			echo '<a href="' . wp_nonce_url( HPWC_HOOTPROOF_SETTINGS, 'remove_comment_ips', 'hpwc_remove_comment_ips_nonce' ) .'" class="button-secondary">'. __('Remove IP addresses now', 'hootproof-check') . '</a>';
				
    }
    
    public function upgrade_meta_box() {
    
        echo __('<p><strong>Upgrade now to receive:</strong>
          <ol><li>Detailed step-by-step instructions on how to fix each issue and implement the suggestions</li>
              <li>A discount code for the general HootProof support services</li>
          </ol>
           (currently only available in German)</p>', 'hootproof-check')
        . '<a href="https://hootproof.de/plugin-anmeldung/" target="_blank" class="button-secondary">' . __('See features &amp; upgrade', 'hootproof-check') . '</a>';
        
    }
    
    public function support_meta_box() {
        
        echo '<p>'
        . __('HootProof can solve your WordPress problems and assist you with many other website related tasks.', 'hootproof-check')
        . '</p><p>'
        . __('Find out more about our affordable, professional WordPress support.', 'hootproof-check')
        . '</p>'
        . '<a href="' . _x('https://hootproof.de/en', 'Link to support', 'hootproof-check') . '" target="_blank" class="button-secondary">' . __('Get WordPress support', 'hootproof-check') . '</a>';
    }
    
    public function resources_meta_box() {
        ?>
        
        <p><?php _e('Visit the HootProof blog for even more resources and articles about getting the most out of your website (currently only available in German).', 'hootproof-check'); ?></p>
        <a href="https://hootproof.de/blog" target="_blank" class="button-secondary">HootProof Blog</a>

        <p><?php _e('See the plugin documentation for details on how to setup the HootProof Plugin.', 'hootproof-check'); ?></p>
        <a href="<?php echo _x('https://wordpress.org/plugins/hootproof-check-optimize/', 'URL Documentation','hootproof-check'); ?>" target="_blank" class="button-primary"><?php _e('Plugin Documentation', 'hootproof-check'); ?></a>
        <?php
    }
    
   //END META BOXES
      
      
   /*
      HELPERS
    */
    public function admin_styles() {
        wp_enqueue_style( 'hootproof-style', HPWC_HOOTPROOF_URL . '/css/hootproof-check.css', array( ), HPWC_HOOTPROOF_VERSION );
    }
    
    protected function remove_comment_ips() {

        global $wpdb;
        $results = $wpdb->get_results( "UPDATE $wpdb->comments SET comment_author_IP = '127.0.0.1'  WHERE comment_author_IP != '127.0.0.1'", OBJECT );
        
    }
   
    //END HELPERS

} // end class HootProof_Website_Check

    $hpwc_hootproof = new HootProof_Website_Check();
    
