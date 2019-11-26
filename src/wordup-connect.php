<?php
/** 
* Plugin Name: Wordup-Connect
* Description: This plugin connects your WordPress installation with your local development stack. You can use this plugin to download automatically a backup of your public WordPress website to your local machine. 
* Version: 0.2.0
* Author: Wordup
* Author URI: https://wordup.dev
*
* @package   Wordup_connect
*/


if( ! defined( 'ABSPATH' ) ) exit;

/** 
 * Set PHP mailer 
 * 
 */
function wordup_local_mail_setup( PHPMailer $phpmailer ) {
    $phpmailer->Host = 'mail';
    $phpmailer->Port = 1025;
    $phpmailer->IsSMTP();
}

if(getenv('WORDUP_PROJECT')){
	add_action( 'phpmailer_init', 'wordup_local_mail_setup' );
}


/**
 * Register activation hook
 */
function wordup_connect_activation(){
	//Set private key
	//$private_key = Wordup_backup::get_random_key();
	//update_option('wordup_connect_private_key', $private_key);
}
register_activation_hook(__FILE__, 'wordup_connect_activation');

/**
 * Register delete hook
 */
function wordup_connect_uninstall(){
	//Delete wordup folder
	//$wordup_backup = new Wordup_backup();
	//$wordup_backup->delete_all_wordup_folders();
}
register_uninstall_hook(__FILE__, 'wordup_connect_uninstall');


/**
 * Add settings link on plugins page
 */
function wordup_connect_plugin_action_links( $links ) {
	$wordup_links = array(
		'<a href="' . esc_url( admin_url( '/tools.php?page=wordup-connect' ) ) . '">' . __( 'Tools', 'wordup-connect' ) . '</a>'
	);
	return array_merge( $links, $wordup_links);
}
add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wordup_connect_plugin_action_links' );


/**
 * Register some admin styles
 */
function wordup_admin_enqueue_scripts()
{
	wp_enqueue_style( 'wordup-admin', plugins_url('/assets/css/styles.css', __FILE__), false, '1.0.0' );
}
add_action( 'admin_enqueue_scripts', 'wordup_admin_enqueue_scripts' );



/**
 * Add Menu to manage_options
 */
function wordup_plugin_menu() {
	add_management_page( 'Wordup Connect', 'Wordup Connect', 'manage_options', 'wordup-connect', 'wordup_plugin_options' );
}
add_action( 'admin_menu', 'wordup_plugin_menu' );

/**
 * Wordup Connect Manage Options Page
 */
function wordup_plugin_options() {

	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ,'wordup-connect') );
	}

	$private_key = get_option('wordup_connect_private_key'); 
	
	?>
	<div class="wrap wordup-wrap">
		<section>

			<h1><?php echo __( 'Wordup Connect', 'wordup-connect' ); ?></h1>
			<?php if(getenv('WORDUP_PROJECT')): ?>
				<div class="notice notice-success">
					<p><?php _e('You are currently developing the following wordup project: ', 'wordup-connect'); ?><strong><?php echo esc_html(getenv('WORDUP_PROJECT')); ?></strong></p>
				</div>
			<?php endif; ?>
			<p><?php _e('The plugin connects this WordPress installation with wordup.', 'wordup-connect'); ?></p>
			<hr>

		</section>
	</div>
	<?php
}


