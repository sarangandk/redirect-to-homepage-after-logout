<?php
/*
* Plugin Name: Redirect to Homepage After Logout
* Description: This plugin simply redirect you to home of your WordPress site, instead of just back to the login page again.
* Author: Sarangan Thillayampalam
* Version: 0.1
* Author URI: https://sarangan.dk
* License: GPLv2 or later
*/

 function wpc_auto_redirect_after_logout(){
  wp_redirect( home_url() );
  exit();
}
add_action('wp_logout','wpc_auto_redirect_after_logout');
?>