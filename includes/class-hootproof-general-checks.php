<?php
/*  Copyright 2015  Michelle Retzlaff  (email : michelle@hootproof.de)

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
require_once dirname( __FILE__ ) . '/hootproof-result-details.php';

if ( !class_exists( 'HootProof_General_Checks' ) ) {

class HootProof_General_Checks {

    public function __construct() {
    
       global $hpwc_hootproof_result_details;
       $this->check_result_details = $hpwc_hootproof_result_details;
       $this->check_results = array();
       
       //Include the TGM_Plugin_Activation class
       require_once dirname( __FILE__ ) . '/class-tgm-plugin-activation.php';
       add_action( 'tgmpa_register', array($this, 'register_recommended_plugins') );
      
       //frontend checks are performed separately
       require_once dirname( __FILE__ ) . '/class-hootproof-frontend-checks.php';
       
    }
    
    public function perform_checks() {
    
       //hook functions
       add_action('hpwc_hootproof_perform_checks', array($this, 'check_basic_settings'));
       add_action('hpwc_hootproof_perform_checks', array($this, 'hello_dolly_active'));
       add_action('hpwc_hootproof_perform_checks', array($this, 'is_sample_content_public'));
       add_action('hpwc_hootproof_perform_checks', array($this, 'check_basic_security'));
       add_action('hpwc_hootproof_perform_checks', array($this, 'required_legal_content_exists'));
       add_action('hpwc_hootproof_perform_checks', array($this, 'check_existing_comment_ips'));
       add_action('hpwc_hootproof_perform_checks', array($this, 'check_basic_performance'));
       add_action('hpwc_hootproof_perform_checks', array($this, 'check_recommended_plugins'));
       add_action('hpwc_hootproof_perform_checks', array($this, 'evaluate_gtmetrix_results'));
       
       //frontend checks are in extra class for better code maintenance
       $frontend_checks = new HootProof_Frontend_Checks();
       $frontend_check_results = $frontend_checks->perform_checks();
       
       $this->check_results = array_merge($this->check_results, $frontend_check_results);
              
       //and execute the other checks
       do_action( 'hpwc_hootproof_perform_checks');
         
    }
    
    
    public function notices_meta_box() {
           
        $this->check_ip_plugins();
        
        if ( sizeof($this->ip_processing_plugins) > 0 ) {
        
			echo '<div class="inside"><h4>';
			esc_html_e("The following plugins and scripts process website users' IP addresses. Make sure you inform them where necessary or consider deactivating the plugins.", 'hootproof-check');
			;
			echo '</h4></div>';
		
			?>
		   <table class="widefat"> 
			  <thead>
				<tr>
				<th><?php esc_html_e('Plugin/Script Name', 'hootproof-check'); ?></th>
				<th><?php esc_html_e('Note', 'hootproof-check'); ?></th>
				<th><?php esc_html_e('Recommended Action', 'hootproof-check'); ?></th>
				</tr>
			  </thead>
			  <tbody>
		  
				 <?php foreach($this->ip_processing_plugins as $result) {
				
				   echo "<tr>
						  <td>" . esc_html($result['name']) . "</td>
						  <td>" . esc_html($result['msg']) . "</td>
						  <td>" . esc_html($result['action']) . "</td> 
						  </tr>";
			
				  } //end foreach ?>
			 
			  </tbody>
			</table>
			  <?php
        } // sizeof > 0
    }
    
    //outputs the check results
    public function check_results_meta_box() {
          
        $this->perform_checks();  
        
     
        if (sizeof($this->check_results) > 0 ) {
			?>
			<p>
			  <a class="button-primary" href="javascript:history.go(0)"><?php esc_html_e('Check again', 'hootproof-check'); ?></a></p>
		  
			<table class="widefat"> 
			  <thead>
				<tr>
				<th><?php esc_html_e('Check Result Name', 'hootproof-check'); ?></th>
				<th><?php esc_html_e('Category', 'hootproof-check'); ?></th>
				<th><?php esc_html_e('Severity', 'hootproof-check'); ?></th>
				<th><?php esc_html_e('Details', 'hootproof-check'); ?></th>
				</tr>
			  </thead>
			  <tbody>
		  
				 <?php foreach($this->check_results as $result) {
			// var_dump($result);
					if(is_array($result)) {
					
					   if($result['type'] == 'gtmetrix_details') {
					   
						   $name = esc_html__($this->check_result_details[$result['key']]['name'], 'hootproof-check');
						   $category = esc_html__($this->check_result_details[$result['key']]['category'], 'hootproof-check');
						   $severity = $this->get_gtmetrix_severity($result['value']);
						   
						   $link = $this->get_result_link($result['key']);
					   }
					   elseif($result['type'] == 'gtmetrix_summary') {
					   
						   $name = esc_html__($this->check_result_details[$result['key']]['name'], 'hootproof-check');
						   $category = esc_html__($this->check_result_details[$result['key']]['category'], 'hootproof-check');
						   switch($this->check_result_details[$result['key']]) {
						   
						      case 'pagespeed_score':
						      
   						         $severity = $this->get_gtmetrix_severity($result['value']);
			                     break;
   						         
   						      case 'page_load_time':
   						         
   						         if($result['value'] > 5) esc_html__('Significant', 'hootproof-check');
   						         else esc_html__('Medium', 'hootproof-check');
			                     break;
             
							  case 'page_elements':
   						         
   						         if($result['value'] > 120) esc_html__('Significant', 'hootproof-check');
   						         elseif ($result['value'] > 90) esc_html__('Medium', 'hootproof-check');
   						         else esc_html__('Low', 'hootproof-check');
			                     break;
   						         
							  case 'page_bytes':
   						         
   						         if($result['value'] > 4) esc_html__('Significant', 'hootproof-check');
   						         else esc_html__('Medium', 'hootproof-check');
			                     break;
   						      
						    }//end switch
						   
						   $link = $this->get_result_link($result['key']);
					   
					   }
					   else {
					   
					   
						   $name = sprintf(_n($this->check_result_details[$result['key']]['name_singular'], $this->check_result_details[$result['key']]['name'], $result['value'], 'hootproof-check'), $result['value']);
						   $category = esc_html__($this->check_result_details[$result['key']]['category'], 'hootproof-check');
						   $severity = esc_html__($this->check_result_details[$result['key']]['severity'], 'hootproof-check');
						   $link = $this->get_result_link($result['key']);
					   
					   }
					}//end if is_array
					else {
					       
					       $name = esc_html__($this->check_result_details[$result]['name'], 'hootproof-check');
						   $category = esc_html__($this->check_result_details[$result]['category'], 'hootproof-check');
						   $severity = esc_html__($this->check_result_details[$result]['severity'], 'hootproof-check');
						   $link = $this->get_result_link($result);
					}
				
					
					echo "<tr><td>" . $name . "</td>
							  <td>" . $category . "</td>
							  <td>" . $severity . "</td>
							  <td>" . $link. "</td></tr>";
				  } //end foreach ?>
			 
			  </tbody>
			</table>
			<p>
			  <a class="button-primary" href="javascript:history.go(0)"><?php esc_html_e('Check again', 'hootproof-check'); ?></a></p>
			<?php
		
		}
		
		//no negative results
		else {
		  echo '<h4>' . esc_html__('All checks passed successfully! Congrats!', 'hootproof-check'). '</h4>';
		}
    }
    
    /* HELPER OUTPUT FUNCTIONS */
    public function get_gtmetrix_severity($score) {
    
       $grade = array( );
       $grade['grade'] = $score >= 50 ? '&#' . (74 - floor( $score / 10 )) . ';' : 'F';
       $grade['position'] = '-' . (100 - $score) . 'px -' . ($score >= 50 ? (9 - floor( $score / 10 )) * 15 : 75) . 'px';
             
       return '<div class="gfw-grade-meter gfw-grade-meter-' . $grade['grade'] . '" style="background-position: ' . $grade['position'] . '">' . $grade['grade'] . ' (' . $score . ')</div>';
    
    }
    
    public function get_result_link($result) {
       // Link to "Install Plugins" page
       if ( strpos($result, 'inst_') !== false || strpos($result, 'act_') !== false ) {       
          return '<a href="'.$this->tgmpa_instance->get_tgmpa_url().'">' . esc_html__('Install & activate plugins', 'hootproof-check') . '</a>';
       }
    

	   $anchor = esc_html__('Details on how to fix this issue', 'hootproof-check');
	   return '<a target="_blank" href="' . $this->check_result_details[$result]['link'] . '">' . $anchor . '</a>';
	  
    } //end function get_result_link

    
    /* 
       CHECKING FUNCTIONS 
    */
    public function evaluate_gtmetrix_results() {

       global $wpdb;
       
       $sql = "SELECT MAX(ID) FROM {$wpdb->posts} WHERE post_type = 'hpwc_gfw_report'";
       $post_id = $wpdb->get_var($sql);
    
       if (!isset($post_id) ) {
          return;
       }
    
       $sql_meta = "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = $post_id";
       $post_meta = $wpdb->get_results($sql_meta);
              
       foreach ($post_meta as $meta) {      
       
          switch($meta->meta_key) {
          case 'pagespeed_obj':
          
             $pagespeed_obj = json_decode($meta->meta_value, true);
             foreach ($pagespeed_obj['rules'] as $ps) {
                
                if ( $ps['score'] < 90 ) {
                   $this->check_results[] = array('key' => $ps['shortName'], 
                                                  'type' => 'gtmetrix_details',
                                                  'value' => $ps['score']);
                }                               
             }
            
          case 'page_load_time':
          
             if($meta->meta_value / 1000 > 3.5) {
                $this->check_results[] =  array('key' => 'page_load_time', 
                                                'type' => 'gtmetrix_summary',
                                                'value' => $meta->meta_value/1000);
             }
             
          case 'page_elements':
          
             if($meta->meta_value > 80) {
                $this->check_results[] =  array('key' => 'page_elements', 
                                                'type' => 'gtmetrix_summary',
                                                'value' => $meta->meta_value);
             }
             
          case 'page_bytes':
          
             if($meta->meta_value / 1000 > 2) {
                $this->check_results[] =  array('key' => 'page_bytes', 
                                                'type' => 'gtmetrix_summary',
                                                'value' => $meta->meta_value/1000);
             }
             
          case 'pagespeed_score':
          
             if($meta->meta_value < 92) {
                $this->check_results[] =  array('key' => 'pagespeed_score', 
                                                'type' => 'gtmetrix_summary',
                                                'value' => $meta->meta_value);
             }
          
          }//end switch
       }//end foreach $post_meta
       
       
    }
    
    public function check_ip_plugins() {
    
       $this->ip_processing_plugins = array();
       
       //Akismet
       if ( is_plugin_active('akismet/' .'akismet.php') ) {
       
          $this->ip_processing_plugins[] =  array('name' => 'Akismet', 
                                                  'msg' => __('When commenting, registering or logging in, IP addresses are sent to Akismet for spam protection.', 'hootproof-check'),
                                                  'action' => __('Include a note in your comments & login forms or use AntiSpam bee instead','hootproof-check'));
       }
       
       //Jetpack
       if ( is_plugin_active('akismet/' .'akismet.php') ) {
       
          $this->ip_processing_plugins[] =  array('name' => 'Akismet', 
                                                  'msg' => __('The Jetpack Statistics module processes and saves users\'s IP addresses', 'hootproof-check'),
                                                  'action' => __('Include a note in your privacy declaration or use Statify instead.','hootproof-check'));
       }
    }
    
    public function check_existing_comment_ips() {
        
        global $wpdb;
	
		$sql = "SELECT COUNT(comment_id) FROM {$wpdb->comments} WHERE comment_author_IP NOT LIKE '127.0%'";
		$comments_with_ips = $wpdb->get_var( $sql );
	
		if( $comments_with_ips > 0 )
		    $this->check_results[] = array('key' => 'comment_ips', 
		                                   'type' => 'counting',
		                                   'value' => $comments_with_ips);
		
    }
    
    public function check_basic_settings() {
  
	   /* Tagline */
  
	   //TODO: auto-translate
	   $tag_line = get_option('blogdescription');
	   if(!$tag_line) {
		 $this->check_results[] = 'no_blog_description';
	   }
	   elseif (strcasecmp( $tag_line, 'Eine weitere WordPress-Seite' ) == 0
			  || strcasecmp( $tag_line, 'Just another WordPress site' ) == 0) {
		 $this->check_results[] = 'standard_blog_description';
	   } //end elseif
      
	   /* General Settings */
	   $users_can_register = get_option('users_can_register');
	   $default_role = get_option('default_role');
	   $sensitive_roles = array("administrator", "editor", "author");
   
	   if ( $users_can_register == "1"  && in_array($default_role, $sensitive_roles)) {
		  $this->check_results[] = 'registration_enabled';
	   }
   
	   /*   Reading */
	   if( get_option('blog_public') == "0") {
		  $this->check_results[] = 'no_index';
	   }
   
	   /* Permalinks */
	   $permalinks = get_option('permalink_structure');
	   if( is_null($permalinks) or strlen($permalinks) < 1 ) 
		   $this->check_results[] = "standard_permalinks";

	}

    public function hello_dolly_active() {
       if ( is_plugin_active('hello.php') ) $this->check_results[] = 'hello_dolly';
    }   
    
    public function is_sample_content_public() {
		
		global $wpdb;
	
		//check whether the sample post exists
		$hpwc_sql = "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type = 'post' AND ID = 1";
		$hpwc_sample_post = $wpdb->get_var( $hpwc_sql );
	
		if( $hpwc_sample_post > 0 ) $this->check_results[] = "sample_post";
		
		//sample page
		$hpwc_sql = "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type = 'page' AND ID = 2";
		$hpwc_sample_page = $wpdb->get_var( $hpwc_sql );
		if( $hpwc_sample_page > 0 ) $this->check_results[] = "sample_page";
	
		//sample comment
		$hpwc_sql = "SELECT COUNT(comment_ID) FROM {$wpdb->comments} WHERE comment_approved = 1 AND comment_id = 1";
		$hpwc_sample_comment = $wpdb->get_var( $hpwc_sql );
		if( $hpwc_sample_comment > 0 ) $this->check_results[] = "sample_comment";
		
    }

    public function check_basic_security() {
    	if (WP_DEBUG) $this->check_results[] = "debug_on";  
	}
	
	function check_basic_performance() {
       if (count(get_option('active_plugins')) > 30) $this->check_results[] = "many_plugins";
   }

	
	function required_legal_content_exists() {

		global $wpdb;
		
		//check whether an "Impressum" exists
		$hpwc_sql = "SELECT COUNT(ID) FROM {$wpdb->posts} 
					 WHERE post_status = 'publish'
						AND UPPER(post_title) LIKE '%IMPRESSUM%'";
		$hpwc_imprint = $wpdb->get_var( $hpwc_sql );
	
		if( $hpwc_imprint < 1 ) $this->check_results[] = "missing_imprint";
	
		//check whether a "Datenschutzerklärung" exists
		$hpwc_sql = "SELECT COUNT(ID) FROM {$wpdb->posts} 
					 WHERE post_status = 'publish'
						AND UPPER(post_title) LIKE '%DATENSCHUTZ%'";
		$hpwc_privacy_statement = $wpdb->get_var( $hpwc_sql );
	
		if( $hpwc_privacy_statement < 1 ) $this->check_results[] = "missing_privacy_statement";
    
    }
	
	public function check_recommended_plugins() {
	   
	    $this->tgmpa_instance = call_user_func( array( get_class( $GLOBALS['tgmpa'] ), 'get_instance' ) );
  	
		foreach ( $this->tgmpa_instance->plugins as $slug => $plugin ) {
	  
		   if ( $this->tgmpa_instance->is_plugin_active( $slug ) ) {
				continue;// ist aktiviert.
			} 
			else {
		   
			   if ( ! $this->tgmpa_instance->is_plugin_installed( $slug ) ) 
			      $this->check_results[] = "inst_" . $slug; //muss installiert werde
			   else
			       $this->check_results[] = "act_" . $slug; //muss aktiviert werden
			   	   
			} //end else
	  
		} //end foreach
	}
	
	/**
	 * Register the required plugins for this theme.
	 * The variable passed to tgmpa_register_plugins() should be an array of plugin
	 * arrays.
	 *
	 * This function is hooked into tgmpa_init, which is fired within the
	 * TGM_Plugin_Activation class constructor.
	 */
	public function register_recommended_plugins() {
		 /*
		 * Array of plugin arrays. Required keys are name and slug.
		 * If the source is NOT from the .org repo, then source is also required.
		 */
	 
		$plugins = array(

			/*array(
				'name'      => 'Broken Link Checker',
				'slug'      => 'broken-link-checker',
				'required'  => false,
			),*/
            array(
				'name'      => 'Limit Login Attempts',
				'slug'      => 'limit-login-attempts',
				'required'  => false,
			),
			array(
				'name'      => 'Subscribe to Double-Opt-In Comments',
				'slug'      => 'subscribe-to-doi-comments',
				'required'  => false,
			),
			/*array(
				'name'      => 'W3 Total Cache',
				'slug'      => 'w3-total-cache',
				'required'  => false,
			),*/

			// This is an example of the use of 'is_callable' functionality. A user could - for instance -
			// have WPSEO installed *or* WPSEO Premium. The slug would in that last case be different, i.e.
			// 'wordpress-seo-premium'.
			// By setting 'is_callable' to either a function from that plugin or a class method
			// `array( 'class', 'method' )` similar to how you hook in to actions and filters, TGMPA can still
			// recognize the plugin as being installed.
			array(
				'name'        => 'WordPress SEO by Yoast',
				'slug'        => 'wordpress-seo',
				'is_callable' => 'wpseo_init',
				'required'  => false,
			),

		);

		/*
		 * Array of configuration settings. Amend each line as needed.
		 *
		 * TGMPA will start providing localized text strings soon. If you already have translations of our standard
		 * strings available, please help us make TGMPA even better by giving us access to these translations or by
		 * sending in a pull-request with .po file(s) with the translations.
		 *
		 * Only uncomment the strings in the config array if you want to customize the strings.
		 */
		$config = array(
			'id'           => 'tgmpa',                 // Unique ID for hashing notices for multiple instances of TGMPA.
			'default_path' => '',                      // Default absolute path to bundled plugins.
			'menu'         => 'tgmpa-install-plugins', // Menu slug.
			'parent_slug'  => 'hootproof_menu',            // Parent menu slug.
			'capability'   => 'manage_options',    // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
			'has_notices'  => true,                    // Show admin notices or not.
			'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
			'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
			'is_automatic' => false,                   // Automatically activate plugins after installation or not.
			'message'      => '',                      // Message to output right before the plugins table.
		);

		tgmpa( $plugins, $config );
	
	}
	
	//END CHECKING FUNCTIONS

} //end of class
}