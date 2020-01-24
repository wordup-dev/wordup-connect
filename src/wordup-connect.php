<?php
/** 
* Plugin Name: Wordup-Connect
* Description: This plugin connects your WordPress installation with the wordup development suite. 
* Version: %%VERSION%%
* Author: Wordup
* Author URI: https://wordup.dev
*
* @package   Wordup_connect
*/

error_reporting( E_ALL );
require_once __DIR__.'/includes/installer.php';
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
			<hr />
			<?php wordup_projects_form(); ?>
		</section>
	</div>
	<?php
}

/**
 * Wordup: Get a wordup plugin by ID
 * 
 */
function wordup_get_plugin_by_id($id, $all_plugins){

	$plugin_found = false;
	foreach ( $all_plugins as $plugin_slug => $values ){
		$plugin_id = basename($plugin_slug,'.php');
		if($plugin_id === $id){
			$plugin_found = $values;
			break;
		}
	}

	return $plugin_found;
}

/**
 * Wordup Project Form
 */
function wordup_projects_form() {
	$wordup_projects = get_option('wordup_projects', array()); 

    $all_plugins = get_plugins();

	?>

	<div class="wordup-manage-projects">
		<h3><?php _e('Projects', 'wordup-connect'); ?></h3>
		<?php _e('Add wordup hosted plugins or themes to this WordPress installation and provide the same update functionality like standard plugins/themes:', 'wordup-connect'); ?></p>

		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" >			
			<input type="hidden" name="action" value="wordup_manage_projects">
			<?php wp_nonce_field( 'wordup_manage_projects' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><?php _e('Add project:', 'wordup-connect'); ?></th>
					<td>
						<select name="wordup_type" id="wordup_type">
							<option value="plugin">Plugin</option>
							<option value="theme">Theme</option>
						</select>
					</td>
					<td>
						<input type="text" class="regular-text" value="" placeholder="Project ID" name="wordup_project" />
					</td>
					<td>
						<input type="text" class="regular-text" value="" minlength="10" placeholder="Private key (optional)" name="wordup_private_key" />
					</td>
					<td>
						<input class="button-primary" type="submit" name="submit" value="<?php esc_attr_e( 'Add' ); ?>" />
					</td>
				</tr>

			</table>
		</form>
		<table class="wp-list-table widefat fixed striped wordup-projects">
			<thead>
				<tr>
					<td class="manage-column column-cb check-column"></td>
					<th scope="col" class="manage-column column-primary">Project ID</th>
					<th scope="col" class="manage-column"><?php _e('Private key', 'wordup-connect'); ?></th>
					<th scope="col" class="manage-column"><?php _e('Type', 'wordup-connect'); ?></th>
					<th scope="col" class="manage-column"><?php _e('Status', 'wordup-connect'); ?></th>
				</tr>
			</thead>
			<tbody id="the-list">
				<?php if(empty($wordup_projects)): ?>
					<tr class="no-items"><td class="colspanchange" colspan="4"><?php _e('No projects found', 'wordup-connect'); ?></td></tr>
				<?php else: ?>
					<?php foreach($wordup_projects as $id => $project): ?>
						<tr>
							<th scope="row"></th>
							<td class="column-primary"> 
								<strong>
									<span class="row-title"><?php echo $id; ?></span>
								</strong>
								<div class="row-actions visible">
									<span class="link"><a href="https://cloud.wordup.dev/projects">Project page</a></span> | 
									<span class="delete"><a href="<?php echo wp_nonce_url(admin_url('tools.php?page=wordup-connect&action=wordup-delete-project&project='.$id), 'wordup_delete_project'); ?>" class="delete" aria-label="Delete project"><?php _e('Delete', 'wordup-connect'); ?></a></span>
								</div>
							</td>
							<td > <?php echo !empty($project['private_key']) ?  substr($project['private_key'], 0, 3).'...'.substr($project['private_key'], -3) : '-'; ?> </td>
							<td > <?php echo $project['type']; ?> </td>
							<td>
								<?php
									$project_found = false;
									if($project['type'] === 'theme'){
										$theme_data = wp_get_theme($id);

										if($theme_data->exists()){
											$project_found = true;
											echo 'Installed version: '.$theme_data->get('Version');
										}
									}else if($project['type'] === 'plugin'){
										$plugin_found = wordup_get_plugin_by_id($id, $all_plugins);

										if(!empty($plugin_found)){
											$project_found = true;
											echo 'Installed version: '.$plugin_found['Version'];
										}
									}
								?>

								<?php if(!$project_found): ?>
									<?php _e('Not installed:', 'wordup-connect'); ?>
									<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" >			
										<input type="hidden" name="action" value="wordup_install_project">
										<input type="hidden" name="project" value="<?php echo $id; ?>">
										<input type="hidden" name="type" value="<?php echo $project['type']; ?>">
										<?php wp_nonce_field( 'wordup_install_project' ); ?>
										<button type="submit" aria-label="Install project"><?php _e('Install', 'wordup-connect'); ?></button>
									</form>
								<?php endif; ?>
							</td>	
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
			<tfoot>
				<tr>
					<td class="manage-column column-cb check-column"></td>
					<th scope="col" class="manage-column column-primary">Project ID</th>
					<th scope="col" class="manage-column"><?php _e('Private key', 'wordup-connect'); ?></th>
					<th scope="col" class="manage-column"><?php _e('Type', 'wordup-connect'); ?></th>
					<th scope="col" class="manage-column"><?php _e('Status', 'wordup-connect'); ?></th>
				</tr>
			</tfoot>
		</table>

	</div>


	<?php
}

/**
 * Wordup admin actions: Manage projects
 */
function wordup_admin_post_manage_projects() {

	if ( !current_user_can( 'manage_options' ) )  {
	    wp_die( __( 'You do not have sufficient permissions to access this page.' ,'wordup-connect') );
	}

	if (! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'wordup_manage_projects' ) ) {
	    wp_die( 'Sorry, your nonce did not verify.');
	}

	$projects = get_option('wordup_projects', array()); 
	
	$type = $_POST['wordup_type'];
	$project_id = $_POST['wordup_project'];

	$projects[$project_id] = array(
		'type' => $type,
		'private_key' => $_POST['wordup_private_key']
	);

	update_option('wordup_projects', $projects);
	wp_redirect( admin_url( '/tools.php?page=wordup-connect' ), 302 );

	exit;
}
add_action( 'admin_post_wordup_manage_projects', 'wordup_admin_post_manage_projects' );


/**
 * Wordup admin actions: Manage projects
 */
function wordup_admin_post_install_project() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ,'wordup-connect') );
	}

	if (! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'wordup_install_project' ) ) {
		wp_die( 'Sorry, your nonce did not verify.');
	}

	$wordup_projects = get_option('wordup_projects', array()); 

	$project =  $wordup_projects[$_POST['project']];
	$installer = new Wordup_Project_install($_POST['project'], $project['type'] );
	$result = $installer->install( $project['private_key'] );
	echo $result;
	print_r($installer->logs['installer']);
	
	//wp_redirect( admin_url( '/tools.php?page=wordup-connect&installed_project='.($test ? 'yes' :'no') ), 302 );
	//exit;
}
add_action( 'admin_post_wordup_install_project', 'wordup_admin_post_install_project' );


/**
 * Wordup process get requests
 */
function wordup_admin_process_get(){

	if(isset($_GET['action']) && $_GET['action'] === 'wordup-delete-project'){
		$wordup_projects = get_option('wordup_projects', array()); 

		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ,'wordup-connect') );
		}
	
		if (! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'wordup_delete_project' ) ) {
			wp_die( 'Sorry, your nonce did not verify.');
		}
	
		unset($wordup_projects[$_GET['project']]);
	
		update_option('wordup_projects', $wordup_projects);
	
		wp_redirect( admin_url( '/tools.php?page=wordup-connect' ), 302 );
		exit;
	}
	
}
add_action( 'init', 'wordup_admin_process_get' );


