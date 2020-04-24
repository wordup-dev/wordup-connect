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

//error_reporting( E_ALL );

require_once __DIR__.'/includes/rest.php';
require_once __DIR__.'/includes/updater.php';

if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register delete hook
 */
function wordup_connect_uninstall(){
	delete_option('wordup_projects');
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
	wp_enqueue_style( 'wordup-admin-css', plugins_url('/assets/css/styles.css', __FILE__), false, '1.0.0' );
	wp_enqueue_script( 'wordup-admin-js', plugins_url('/assets/js/main.js', __FILE__), array( ), '1.0.0', true);
	wp_localize_script( 'wordup-admin-js', 'wordupApiSettings', array(
		'root' => esc_url_raw( rest_url() ),
		'tools' => admin_url( '/tools.php?page=wordup-connect' ),
		'nonce' => wp_create_nonce( 'wp_rest' )
	) );
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
			<p><?php printf( __('The wordup-connect plugin connects this WordPress installation with your plugin and theme directory on <a href="%s">wordup.dev</a>.', 'wordup-connect'), 'https://wordup.dev'); ?></p>
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

		<form action="" method="post" id="wordup-create-project" >			
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
						<input type="text" class="regular-text" value="" placeholder="Project ID" name="wordup_project" required />
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
					<tr class="no-items"><td class="colspanchange" colspan="5"><?php _e('No projects found', 'wordup-connect'); ?></td></tr>
				<?php else: ?>
					<?php foreach($wordup_projects as $id => $project): ?>
						<tr>
							<th scope="row"></th>
							<td class="column-primary"> 
								<strong>
									<span class="row-title"><?php echo esc_html($id); ?></span>
								</strong>
								<div class="row-actions visible">
									<span class="link"><a href="https://console.wordup.dev/projects/<?php echo esc_attr($id); ?>" target="_blank">Project page</a></span> | 
									<span class="delete"><button type="button" class="button-link delete" data-project="<?php echo esc_attr($id); ?>" aria-label="Delete project"><?php _e('Delete', 'wordup-connect'); ?></button></span>
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
									<button type="button" class="install" aria-label="Install project" data-project="<?php echo esc_attr($id); ?>" ><?php _e('Install', 'wordup-connect'); ?></button>
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
 * Wordup register REST
 */
function wordup_rest_router(){
	(new Wordup_REST_Route)->register_routes();
}
add_action('rest_api_init', 'wordup_rest_router');


/**
 * Wordup admin updater. This will be run, even on lower permission level
 * The final update, will only be possible with admin permissions
 */
function wordup_admin_updater() {

    if( !is_admin() ){
        return;
	}

	if( !function_exists('get_plugin_data') ){
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    }

	$wordup_projects = get_option('wordup_projects', array()); 
    $all_plugins = get_plugins();

	foreach($wordup_projects as $id => $project){

		$slug = $project['type'] === 'plugins' ? $id.'/'.$id.'.php' : $id;

		$project_infos = array(
			'id' => $id,
			'key' => $project['private_key'],
			'slug'		=> $slug,
			'basename'	=> $slug,
			'version'	=> false,
		);

		if($project['type'] === 'theme'){
			$theme_data = wp_get_theme($id);

			if($theme_data->exists()){
				$project_infos['version'] = $theme_data->get('Version');
				wordup_register_theme_update($project_infos );
			}
		}else if($project['type'] === 'plugin'){
			$plugin_found = wordup_get_plugin_by_id($id, $all_plugins);

			if(!empty($plugin_found)){
				$project_infos['version'] = $plugin_found['Version'];
				wordup_register_plugin_update($project_infos );
			}
		}
	}

	// Check also for updates for this plugin
	if(getenv('WORDUP_PROJECT') !== 'wordup-connect'){
		
		$infos = wordup_get_plugin_by_id('wordup-connect', $all_plugins);
		$wordup_connect_infos = array(
			'id' => 'wordup-connect',
			'key' => '',
			'slug'		=> 'wordup-connect/wordup-connect.php',
			'basename'	=> 'wordup-connect/wordup-connect.php',
			'version'	=> $infos['Version'],
		);
		wordup_register_plugin_update($wordup_connect_infos);

	}
}
add_action('init', 'wordup_admin_updater');


