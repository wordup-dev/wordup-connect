<?php
/** 
* Plugin Name: Wordup-Connect
* Description: This plugin connects your WordPress installation with your local development stack. You can use this plugin to download automatically a backup of your public WordPress website to your local machine. 
* Version: 0.1.0
* Author: Wordup
* Author URI: https://wordup.dev
*
* @package   Wordup_connect
*/


if( ! defined( 'ABSPATH' ) ) exit;

include_once (__DIR__.'/class.wordup-backup.php');


/**
 * Register activation hook
 */
function wordup_connect_activation(){
	//Set private key
	$private_key = Wordup_backup::get_random_key();
	update_option('wordup_connect_private_key', $private_key);
}
register_activation_hook(__FILE__, 'wordup_connect_activation');

/**
 * Register delete hook
 */
function wordup_connect_uninstall(){
	//Delete wordup folder
	$wordup_backup = new Wordup_backup();
	$wordup_backup->delete_all_wordup_folders();
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
			<?php if(Wordup_backup::is_plugin_active('updraftplus/updraftplus.php')): ?>
			
			<?php 
			    $last_backup = get_option('updraft_last_backup', FALSE);
				if(!$last_backup){
					echo '<div class="notice notice-error"><p>' .__('There is no Updraftplus backup available. Please make a local backup with Updraftplus.', 'wordup-connect').'</p></div>';
				}
			?>
			</p>
			<?php else: ?>
				<div class="notice notice-error"><p><?php _e('Please install Updraftplus to use this plugin', 'wordup-connect'); ?></p></div>
			<?php endif; ?>
			<p><?php _e('The plugin connects this WordPress installation with your wordup CLI.', 'wordup-connect'); ?>
			<p><?php _e('Currently it only supports installations via an Updraftplus local backup.', 'wordup-connect'); ?>
			<p><?php _e('In your terminal use: ') ?><code>$ wordup install --connect=<?php echo get_site_url(); ?></code>
			<hr>
			<?php if($last_backup): ?>
			<h3><?php _e('Backup'); ?></h3>
			<p><?php _e('We found a backup. Created: ', 'wordup-connect'); echo date_i18n( get_option( 'date_format' ).' '.get_option( 'time_format' ),$last_backup['backup_time']); ?>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" >			
				<input type="hidden" name="action" value="wordup_verify_backup">
				<?php wp_nonce_field( 'wordup_verify_backup' ); ?>
				<input class="button-primary" type="submit" name="submit" value="<?php esc_attr_e( 'Verify backup' ); ?>" />
			</form>
			<hr>
			<?php endif; ?>
			<div class="wordup-private-key">
				<h3>Private-Key</h3>
				<?php _e('This is the private key to connect via wordup CLI with this installation:', 'wordup-connect'); ?></p>
				<?php if($private_key): ?>
					<input type="text" class="regular-text" readonly="readonly" value="<?php echo $private_key; ?>" />
				<?php endif; ?>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" >			
					<input type="hidden" name="action" value="wordup_create_private_key">
					<?php wp_nonce_field( 'wordup_create_private_key' ); ?>

					<input class="button-primary" type="submit" name="submit" value="<?php esc_attr_e( 'Renew private key' ); ?>" />
				</form>
			</div>
		</section>
	</div>
	<?php
}

/**
 * Wordup admin actions: Create private key
 */
function wordup_admin_post_create_private_key() {

	if ( !current_user_can( 'manage_options' ) )  {
	    wp_die( __( 'You do not have sufficient permissions to access this page.' ,'wordup-connect') );
	}

	if (! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'wordup_create_private_key' ) ) {
	   wp_die( 'Sorry, your nonce did not verify.');
	}

	update_option('wordup_connect_private_key', Wordup_backup::get_random_key());
	wp_redirect( admin_url( '/tools.php?page=wordup-connect' ), 302 );
	exit;

}
add_action( 'admin_post_wordup_create_private_key', 'wordup_admin_post_create_private_key' );


/**
 * Wordup admin actions: Verify backup
 */
function wordup_admin_post_wordup_verify_backup() {

	if ( !current_user_can( 'manage_options' ) )  {
	    wp_die( __( 'You do not have sufficient permissions to access this page.' ,'wordup-connect') );
	}

	if (! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'wordup_verify_backup' ) ) {
	   wp_die( 'Sorry, your nonce did not verify.');
	}

	$wordup_backup = new Wordup_backup();
	$test = $wordup_backup->create_updraft_archive(TRUE);

	wp_redirect( admin_url( '/tools.php?page=wordup-connect&wordup_verified_backup='.($test ? 'yes' :'no') ), 302 );
	exit;
}
add_action( 'admin_post_wordup_verify_backup', 'wordup_admin_post_wordup_verify_backup' );

if(!empty($_GET['wordup_verified_backup'])){
	function wordup_verified_backup_notice() {
		if($_GET['wordup_verified_backup'] == 'no'){
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php _e( 'We could not find the backup files. Perhaps there not locally saved on this server.', 'wordup-connect' ); ?></p>
		</div>
		<?php
		}else{
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php _e( 'Your backup seems ready for wordup :)', 'wordup-connect' ); ?></p>
		</div>
		<?php
		}
	}
	add_action( 'admin_notices', 'wordup_verified_backup_notice' );
}

/**
 * API Endpoint/Callback: Setups the wordup folders and backup files, so they can be downloaded
 */
function wordup_get_rest_endpoint_setup() {

	$wordup_backup = new Wordup_backup();
	$files = $wordup_backup->create_updraft_archive();
	if($files){
		return array('status'=>'ok');
	}
	return array('status'=>'error');
}

/**
 * API Endpoint/Callback: Get download link for backup
 */
function wordup_get_rest_endpoint_dl() {

	$backup_data = get_option('wordup_backup_infos');
	if($backup_data){
		return rest_ensure_response(array('status'=>'ok','data'=>$backup_data));
	}
	
	return array('status'=>'not_found');
}


/**
 * API Endpoint/Callback: Removes current wordup backup files
 */
function wordup_get_rest_endpoint_clean() {

	$backup_data = get_option('wordup_backup_infos');
	if($backup_data){
		$wordup_backup = new Wordup_backup();
		$wordup_backup->delete_old_backup();
		delete_option('wordup_backup_infos');

		return array('status'=>'cleaned');
	}
	return array('status'=>'not_found');
}

/**
 *  API permission: HMAC signature authentication
 */

function wordup_verify_signature($string_to_sign, $signature){
	$key = get_option('wordup_connect_private_key');

	if(empty($key) || empty($string_to_sign) || empty($signature)){
		return FALSE;
	}

	return (base64_encode(hash_hmac('sha256', $string_to_sign, $key, TRUE)) === $signature) ? TRUE : FALSE;
}


function wordup_get_rest_api_permission($request) {
	$params = $request->get_query_params();

	if(!empty($params['expires']) && !empty($params['signature'])){
		$string_to_sign = $params['rest_route'].$params['expires'];
		if(time() <= intval($params['expires']) && wordup_verify_signature($string_to_sign, rawurldecode($params['signature'])) === TRUE){
			return TRUE;
		}
	}

	return new WP_Error( 'rest_forbidden', esc_html__( 'OMG you can not view private data.'.$_SERVER["HTTP_AUTHORIZATION"], 'wordup' ), array( 'status' => 401 ) );
}

/**
 * Register API - Calls
 */
function wordup_register_api_route() {

	register_rest_route( 'wordup/v1', '/setup', array(
        'methods'  => WP_REST_Server::READABLE,
		'callback' => 'wordup_get_rest_endpoint_setup',
		'permission_callback' => 'wordup_get_rest_api_permission'
	) );

	register_rest_route( 'wordup/v1', '/clean', array(
        'methods'  => WP_REST_Server::READABLE,
		'callback' => 'wordup_get_rest_endpoint_clean',
		'permission_callback' => 'wordup_get_rest_api_permission'
	) );
	
    register_rest_route( 'wordup/v1', '/dl', array(
        'methods'  => WP_REST_Server::READABLE,
		'callback' => 'wordup_get_rest_endpoint_dl',
		'permission_callback' => 'wordup_get_rest_api_permission'
	) );
	
}
 
add_action( 'rest_api_init', 'wordup_register_api_route' );


