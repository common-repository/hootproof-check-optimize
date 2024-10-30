<?php

// If uninstall not called from WordPress exit 
if( !defined( 'WP_UNINSTALL_PLUGIN' ) )
   exit ();
   
// Delete option from options table 
delete_option( 'hpwc_hootproof_options' );
delete_option ('hpwc_hootproof_gfw_options' );
delete_option( "hpwc_un_settings" );
delete_option( "hpwc_un_settings_ver" );
   