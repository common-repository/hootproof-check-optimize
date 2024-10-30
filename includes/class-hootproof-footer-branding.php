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

if ( !class_exists( 'HootProof_Footer_Branding' ) ) {
	
	class HootProof_Footer_Branding {

	   public function __construct() {
		  add_action( 'wp_footer', array( $this, 'show_footer_branding'), 100 ); 
	   }
   
	   public function show_footer_branding() {
 
		  //get branding option 
		  $options = get_option( 'hpwc_hootproof_options' );
		  $show_branding = $options['show_branding'];
  
		  if( $show_branding != 'on' ) return;
   
		   echo '<div style="display:block !important;float:right; position:relative; top:-60px; height:0px;">'
		   . '<a href="https://hootproof.de" title="'.__('Website checked by HootProof', 'hootproof-check').'">'
		   . '<img width="60" height="60" src="' . plugins_url( '/../images/hootproof.png', __FILE__) . '"/>'
		   .'</a>.'
		   . '</div>';
	   }

	} // end of class

}