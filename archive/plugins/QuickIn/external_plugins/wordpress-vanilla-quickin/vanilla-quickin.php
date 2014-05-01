<?php
/*
Plugin Name: Vanilla QuickIn
Plugin URI: http://vanillaforums.org/addons/
Description: QuickIn allows Wordpress users to be quickly and automagically registered and signed into your Vanilla forum.
Version: 1.0.0
Author: Mark O'Sullivan
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

add_action('wp_head', 'vanilla_quickin');
add_action('admin_head', 'vanilla_quickin');
function vanilla_quickin() {
	global $current_user;
	if (!function_exists('get_currentuserinfo'))
		require (ABSPATH . WPINC . '/pluggable.php');
		
	get_currentuserinfo();
	// Only report user info if the user is authenticated and the vanilla domain has been defined.
	$QuickInDomain = get_option('vanilla_quickin_domain');
	if ($current_user->ID != '' && $QuickInDomain != '') {
// TODO: SET A COOKIE OR CONF SETTING SO WE KNOW THAT THIS VALUE HAS BEEN SENT AND WE DON'T KEEP HAMMERING VANILLA WITH THE INFORMATION.
		$QuickInUrl = $QuickInDomain;
		$QuickInUrl .= substr($QuickInDomain, -1, 1) == '/' ? '' : '/';
		$QuickInUrl .= 'entry/quickin/?QuickIn=';
		$QuickIn = array(
			'UniqueID' => $current_user->ID,
			'Email' => $current_user->user_email,
			'Name' => $current_user->display_name,
			'Attributes' => array(
				'TransientKey' => wp_create_nonce('log-out') // Vanilla's "TransientKey" is the equivalent of WordPress' "wpnonce".
			)
		);
		$QuickInUrl .= urlencode('arr:'.json_encode($QuickIn));
		// echo '<script type="text/javascript" src="'.$QuickInUrl.'"></script>';
		echo '<a href="http://gunn.local/vanilla2/?QuickIn='.urlencode('arr:'.json_encode($QuickIn)).'">Vanilla Test Link</a>';
		/*
		?>
		<script type="text/javascript">
			var ajax = new XMLHttpRequest();
			ajax.open('GET', '<?php echo $QuickInUrl; ?>', false);
			ajax.send(null);
		</script>
		<?php
		*/
	}
}

add_action('admin_menu', 'vanilla_quickin_menu');
function vanilla_quickin_menu() {
  add_submenu_page('plugins.php', 'Vanilla QuickIn', 'Vanilla QuickIn', 'administrator', 'vanilla-quickin', 'vanilla_quickin_options');
}
function vanilla_quickin_options() {
	if (isset($_POST['save'])) {
		if (function_exists('current_user_can') && !current_user_can('manage_options'))
			die(__('Permission Denied'));

		$QuickInDomain = array_key_exists('vanilla_quickin_domain', $_POST) ? $_POST['vanilla_quickin_domain'] : '';
			update_option('vanilla_quickin_domain', $QuickInDomain);
	
	} else {
		$QuickInDomain = get_option('vanilla_quickin_domain');
	}
	if ($QuickInDomain == '')
		$QuickInDomain = 'http://domain.com/vanilla';
?>
<div class="wrap">
	<div id="icon-options-general" class="icon32"><br /></div>
	<h2><?php _e('Vanilla QuickIn Settings'); ?></h2>
	<p><?php _e('Enter the full web address to your Vanilla forum:'); ?></p>
	<form method="post">
		<table class="form-table">
			<tr>
				<th>Vanilla's Web Address</th>
				<td><input type="text" name="vanilla_quickin_domain" value="<?php echo $QuickInDomain; ?>" style="width: 400px;" /></td>
			</tr>
		</table>
		<p class="submit"><input type="submit" name="save" value="<?php _e('Save &raquo;'); ?>" /></p>
	</form>
	<?php
	$QuickInAnchor = 'the QuickIn page';
	if ($QuickInDomain != 'http://domain.com/vanilla') {
		$QuickInUrl = $QuickInDomain;
		$QuickInUrl .= substr($QuickInDomain, -1, 1) == '/' ? '' : '/';
		$QuickInUrl .= 'settings/quickin/';
		$QuickInAnchor = '<a href="'.$QuickInUrl.'" target="Vanilla">'.$QuickInAnchor.'</a>';
	}
	?>
	<p>Copy & paste these login definitions into <?php echo $QuickInAnchor; ?> in your Vanilla installation:</p>
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
