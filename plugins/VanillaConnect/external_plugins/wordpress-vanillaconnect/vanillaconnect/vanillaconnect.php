<?php
/*
Plugin Name: VanillaConnect
Plugin URI: http://vanillaforums.org/addons/vanillaconnect
Description: VanillaConnect allows Wordpress users to be quickly and automagically registered and signed into your Vanilla forum.
Version: 1.0.0
Author: Tim Gunter
Author URI: http://vanillaforums.com/
*/

/*
Copyright 2009 Mark O'Sullivan
This file is part of the Vanilla QuickIn plugin for WordPress 2.9.
The Vanilla QuickIn plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
The Vanilla QuickIn plugin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with the Vanilla QuickIn plugin.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] vanillaforums [dot] com
*/

add_action('wp_head', 'vanilla_connect');
add_action('admin_head', 'vanilla_connect');
function vanilla_connect() {

   global $current_user;
   if (!function_exists('get_currentuserinfo'))
      require (ABSPATH . WPINC . '/pluggable.php');

   get_currentuserinfo();
   
   // Only report user info if the user is authenticated and the vanilla domain has been defined.
   $VanillaConnectDomain = get_option('vanilla_connect_domain');
   if ($current_user->ID != '' && $VanillaConnectDomain != '') {
   
      $VanillaConnectKey = get_option('vanilla_connect_key');
      $VanillaConnectSecret = get_option('vanilla_connect_secret');
      
      if ($VanillaConnectKey == '') return;
      if ($VanillaConnectSecret == '') return;
      
      require_once(WP_CONTENT_DIR . '/plugins/vanillaconnect/class.VanillaConnect.php');
      require_once(WP_CONTENT_DIR . '/plugins/vanillaconnect/class.OAuth.php');
      echo VanillaConnect::Authenticate(
         $VanillaConnectDomain,
         $VanillaConnectKey,
         $VanillaConnectSecret,
         $current_user->user_email, 
         $current_user->nickname, 
         $current_user->ID,
         FALSE,
         array()
      )->Script();

   }
   
}

add_action('login_form', 'vanilla_disconnect');
function vanilla_disconnect() {
   if (!isset($_GET['loggedout']) || !$_GET['loggedout']) return;
   
   // Only report user info if the user is authenticated and the vanilla domain has been defined.
   $VanillaConnectDomain = get_option('vanilla_connect_domain');
   $VanillaConnectKey = get_option('vanilla_connect_key');
   $VanillaConnectSecret = get_option('vanilla_connect_secret');

   if ($VanillaConnectKey == '') return;
   if ($VanillaConnectSecret == '') return;
   
   require_once(WP_CONTENT_DIR . '/plugins/vanillaconnect/class.VanillaConnect.php');
   require_once(WP_CONTENT_DIR . '/plugins/vanillaconnect/class.OAuth.php');
   echo VanillaConnect::DeAuthenticate(
      $VanillaConnectDomain,
      $VanillaConnectKey,
      $VanillaConnectSecret,
      FALSE,
      array()
   )->Script();
}

add_action('admin_menu', 'vanilla_connect_menu');
function vanilla_connect_menu() {
   add_submenu_page('plugins.php', 'VanillaConnect', 'VanillaConnect', 'administrator', 'vanilla-connect', 'vanilla_connect_options');
}
function vanilla_connect_options() {
   if (isset($_POST['save'])) {
      if (function_exists('current_user_can') && !current_user_can('manage_options'))
         die(__('Permission Denied'));

      $VanillaConnectDomain = array_key_exists('vanilla_connect_domain', $_POST) ? $_POST['vanilla_connect_domain'] : '';
      update_option('vanilla_connect_domain', $VanillaConnectDomain);
      
      $VanillaConnectKey = array_key_exists('vanilla_connect_domain', $_POST) ? $_POST['vanilla_connect_key'] : '';
      update_option('vanilla_connect_key', $VanillaConnectKey);
      
      $VanillaConnectSecret = array_key_exists('vanilla_connect_domain', $_POST) ? $_POST['vanilla_connect_secret'] : '';
      update_option('vanilla_connect_secret', $VanillaConnectSecret);
   
   } else {
      $VanillaConnectDomain = get_option('vanilla_connect_domain');
      $VanillaConnectKey = get_option('vanilla_connect_key');
      $VanillaConnectSecret = get_option('vanilla_connect_secret');
   }
   
   if ($VanillaConnectDomain == '')
      $VanillaConnectDomain = 'http://domain.com/vanilla';
      
?>
<div class="wrap">
   <div id="icon-options-general" class="icon32"><br /></div>
   <h2><?php _e('VanillaConnect Settings'); ?></h2>
   <p><?php _e('Enter the full web address to your Vanilla forum:'); ?></p>
   <form method="post">
      <table class="form-table">
         <tr>
            <th>Vanilla's Web Address</th>
            <td><input type="text" name="vanilla_connect_domain" value="<?php echo $VanillaConnectDomain; ?>" style="width: 400px;" /></td>
         </tr>
         <tr>
            <th>VanillaConnect Key</th>
            <td><input type="text" name="vanilla_connect_key" value="<?php echo $VanillaConnectKey; ?>" style="width: 400px;" /></td>
         </tr>
         <tr>
            <th>VanillaConnect Secret Code</th>
            <td><input type="text" name="vanilla_connect_secret" value="<?php echo $VanillaConnectSecret; ?>" style="width: 400px;" /></td>
         </tr>
      </table>
      <p class="submit"><input type="submit" name="save" value="<?php _e('Save &raquo;'); ?>" /></p>
   </form>
   <?php
   $VanillaConnectAnchor = 'the VanillaConnect page';
   if ($VanillaConnectDomain != 'http://domain.com/vanilla') {
      $VanillaConnectUrl = $VanillaConnectDomain;
      $VanillaConnectUrl .= substr($VanillaConnectDomain, -1, 1) == '/' ? '' : '/';
      $VanillaConnectUrl .= 'plugin/vanillaconnect/';
      $VanillaConnectAnchor = '<a href="'.$VanillaConnectUrl.'" target="Vanilla">'.$VanillaConnectAnchor.'</a>';
   }
   ?>
   <p>Copy & paste these login definitions into <?php echo $VanillaConnectAnchor; ?> in your Vanilla installation:</p>
   <table class="form-table">
      <tr>
         <th>Registration Url</th>
         <td><span class="description"><?php echo site_url('wp-login.php?action=register', 'login'); ?></span></td>
      </tr>
      <tr>
         <th>Sign-in Url</th>
         <td><span class="description"><?php echo wp_login_url(); ?></span></td>
      </tr>
      <tr>
         <th>Sign-out Url</th>
         <td><span class="description"><?php
            echo add_query_arg(array('action' => 'logout', '_wpnonce' => '{Session_TransientKey}'), site_url('wp-login.php', 'login'));
         ?></span></td>
      </tr>
   </table>
</div>
<?php
}
?>
