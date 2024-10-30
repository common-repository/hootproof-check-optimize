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

if ( !class_exists( 'HootProof_New_Comment_IPs' ) ) {
	
	class HootProof_New_Comment_IPs {

	   public function __construct() {
		  add_filter('pre_comment_user_ip',  array($this, 'remove_comment_ip'));
	   }
   
	   public function remove_comment_ip() {    
		   $ip_address = "127.0.0.1";
		   return $ip_address;
	   }

	} // end of class

}