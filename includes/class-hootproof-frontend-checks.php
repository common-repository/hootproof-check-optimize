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
class HootProof_Frontend_Checks {
   
   public function perform_checks() {
   
      $this->check_results = array();
   
      add_action('hpwc_hootproof_perform_frontend_checks', array($this, 'is_ga_anonymize_ips_active'));      
      do_action('hpwc_hootproof_perform_frontend_checks');
   
      return $this->check_results;
   }
   
   public function is_ga_anonymize_ips_active() {

    //get homepage via HTTP request
    $response = wp_remote_get( get_home_url() );
    
    // Check for server response
    if( is_wp_error( $response ) ) {
       $code = $response->get_error_message();
       wp_die( 'Requests could not execute. Error was: ' . $code );
    }
    
    // if response was successful or redirected 
    if( strpos (wp_remote_retrieve_response_code( $response ), '2') == 0 || strpos (wp_remote_retrieve_response_code( $response ), '3') == 0) {
       
       //search header and body for Google Analytics code
       $response_body = wp_remote_retrieve_body($response);
       $strpos_GA = stripos ($response_body, 'GoogleAnalyticsObject');
       if ( $strpos_GA !== false ) {
          
          
          //anonymize IP must be after the GoogleAnalyticsObject
          $strpos_AIP = stripos($response_body, 'anonymizeIp', $strpos_GA);
          
          if ( $strpos_AIP === FALSE || $strpos_AIP - $strpos_GA > 500) {
             $this->check_results[] = 'ga_anonymize';
          }
          

       }
    }
     else {
        wp_die( 'Link not found' ); 
      }

}

}