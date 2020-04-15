<?php

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( ! class_exists('Wordup_Updater') ) :

class Wordup_Updater {


    private $plugins = array();
    private $themes = array();

    /**
     * Class constructor.
     *
     * @return null
     */
    function __construct()
    {
        add_filter( "pre_set_site_transient_update_plugins", array( $this, "change_plugin_update_transient" ) );
		add_filter( "pre_set_site_transient_update_themes", array( $this, "change_theme_update_transient" ) );
		add_filter( "plugins_api", array( $this, "set_plugin_info" ), 10, 3 );
        //add_filter( "upgrader_pre_install", array( $this, "pre_install" ), 10, 3 );
		//add_filter( "upgrader_post_install", array( $this, "post_install" ), 10, 3 );
		add_action( 'upgrader_process_complete', array( $this, "update_complete" ), 10, 0 );

	}
	
	/*
	*  update_complete
	*
	*  Sets the new versions in this class, so that the update is visible
	*  Currently only necessary for themes
	*
	*  @return	void
	*/

	public function update_complete(){

		foreach($this->themes as $basename => &$project){
			$theme_data = wp_get_theme($basename);
			$project['version'] = $theme_data->get('Version');
		}

	}

	/*
	*  add_project
	*
	*  Registeres a plugin or theme for updates.
	*
	*  @param	array $plugin The plugin array.
	*  @return	void
	*/

    function add_project( $project, $type='plugin') {
		
		// validate
		$project = wp_parse_args($project, array(
			'id'		=> '',
			'key'		=> '',
			'slug'		=> '',
			'basename'	=> '',
			'version'	=> '',
		));
		
		// Check if is_plugin_active() function exists. This is required on the front end of the
		// site, since it is in a file that is normally only loaded in the admin.
		if( !function_exists( 'is_plugin_active' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php');
		}
		
		// add if is active plugin (not included in theme)
		if($type === 'plugin'){
			// We will always look for updates
			// if( is_plugin_active($project['basename']) ) {
				$this->plugins[ $project['basename'] ] = $project;
			//}
		}else{
			$this->themes[ $project['basename'] ] = $project;
		}
	}


    /**
     * Get information regarding a plugin on wordup
     *
     * @return null
     */
    private function get_wordup_release_infos($plugin, $force_check=false)
    {

		$id = $plugin['id'];
		$key = (!empty($plugin['key']) ? '?key='.$plugin['key'] : '');

		$transient_name = 'wordup_plugin_info_' . $id;
		
		// check cache but allow for $force_check override
		if( !$force_check ) {
			$transient = get_transient( $transient_name );
			if( $transient !== false ) {
				return $transient;
			}
		}
		
		// Call API
		$response = $this->api_call("projects/".$id."/release_info/latest/".$key, json_decode("{}") );
		
		// convert string (misc error) to WP_Error object
		if( is_string($response) ) {
			$response = new WP_Error( 'server_error', esc_html($response) );
		}
		
		// allow json to include expiration but force minimum and max for safety
		$expiration = 60;
		
		// update transient
		set_transient( $transient_name, $response, $expiration );
		
		// return
		return $response;
    }


    /**
	*  get_project_updates
	*
	*  Checks for project updates.
	*
	*  @param	boolean $force_check Bypasses cached result. Defaults to false.
	*  @return	array|WP_Error.
	*/
	
	function get_project_updates($type='plugins', $force_check = false ) {

		
		// var
		$transient_name = 'wordup_'.$type.'_updates';
		
		$projects_of_type = $this->$type;

		// construct array of 'checked' plugins
		// sort by key to avoid detecting change due to "include order"
		$checked = array();
		foreach( $projects_of_type as $basename => $project ) {
			$checked[ $basename ] = $project['version'];
		}
		ksort($checked);
		
		// $force_check prevents transient lookup
		if( !$force_check ) {
			$transient = get_transient($transient_name);

			// if cached response was found, compare $transient['checked'] against $checked and ignore if they don't match (plugins/versions have changed)
			if( is_array($transient) ) {
				$transient_checked = isset($transient['checked']) ? $transient['checked'] : array();
				
				if( wp_json_encode($checked) !== wp_json_encode($transient_checked) ) {
					$transient = false;
				}
			}

			if( $transient !== false ) {
				return $transient;
			}
		}
		
		// vars
		$post = array(
			'projects'		=> array_values($projects_of_type),
			'wp'			=> array(
				'wp_name'		=> get_bloginfo('name'),
				'wp_url'		=> home_url(),
				'wp_version'	=> get_bloginfo('version'),
				'wp_language'	=> get_bloginfo('language'),
				'wp_timezone'	=> get_option('timezone_string'),
			)
		);

		// request
		$response = $this->api_call('updater/', $post);
		// append checked reference
		if( is_array($response) ) {
			$response['checked'] = $checked;
		}
		
		// allow json to include expiration but force minimum and max for safety
		//$expiration = $this->get_expiration($response, DAY_IN_SECONDS, MONTH_IN_SECONDS);
		$expiration = 60;

		// update transient
		set_transient($transient_name, $response, $expiration );
		
		// return
		return $response;
	}



    /**
     * Push in project version information to get the update notification
     *
     * @param  object $transient
     * @return object
     */
    public function change_update_transient( $transient, $type)
    {

        if( !isset($transient->response) ) {
			return $transient;
        }
        
		$force_check = !empty($_GET['force-check']);

		// get all updates
		$updates = $this->get_project_updates($type, $force_check);
        // append
		if( is_array($updates) ) {
			foreach( $updates[$type] as $basename => $update ) {

				// Plguins & Themes are represented with object or array
				$transient->response[ $basename ] = $type === 'plugins' ? (object) $update : (array) $update;
			}
		}
		// Get plugin & GitHub release information
        return $transient;
	}

	
	/**
     * Push in plugin version information to get the update notification
     *
     * @param  object $transient
     * @return object
     */
	public function change_plugin_update_transient( $transient ){
		return $this->change_update_transient($transient, 'plugins');
	}
	
	/**
     * Push in theme version information to get the update notification
     *
     * @param  object $transient
     * @return object
     */
	public function change_theme_update_transient( $transient ){
		return $this->change_update_transient($transient, 'themes');
	}


	function api_call($param, $body = null ) {
		
		// vars
		$url = 'https://api.wordup.dev/'.$param;
		
		// post
		$raw_response = wp_safe_remote_post( $url, array(
			'timeout'	=> 10,
			'body'		=> json_encode($body),
			'headers'   => array('Content-Type' => 'application/json; charset=utf-8'),
		));

		// wp error
		if( is_wp_error($raw_response) ) {
			return $raw_response;
		
		// http error
		} elseif( wp_remote_retrieve_response_code($raw_response) != 200 ) {
			return new WP_Error( 'server_error', wp_remote_retrieve_response_message($raw_response) );
		}
		
		// decode response
		$json = json_decode( wp_remote_retrieve_body($raw_response), true );
				
		// return
		return $json;
	}


    /**
     * Push in plugin version information to display in the details lightbox
     *
     * @return object
     */
    public function set_plugin_info( $result, $action = null, $args = null )
    {
	
		if( $action !== 'plugin_information' ) return $result;

		$plugin = $this->get_plugin_by_slug($args->slug);
		if( !$plugin ) return $result;

		$response = $this->get_wordup_release_infos($plugin);

		if( !is_array($response) ) return $result;

		// convert to object
		$response = (object) $response;

		// sections
        $sections = array(
			'description' => '',
			'changelog' => ''
        );
        foreach( $sections as $k => $v ) {
	        $sections[ $k ] = $response->$k;
        }
        $response->sections = $sections;


        return $response;
	}
	
	public function get_plugin_by_slug($value = null ) {
		foreach( $this->plugins as $plugin ) {
			if( $plugin['slug'] === $value ) {
				return $plugin;
			}
		}
		return false;
	}

}

/*
*  wordup_updates
*
*  @param	void
*  @return	object
*/

function wordup_updates() {
	global $wordup_updates;
	if( !isset($wordup_updates) ) {
		$wordup_updates = new Wordup_Updater();
	}
	return $wordup_updates;
}


/*
*  wordup_register_plugin_update
*
*  @param	array $plugin
*  @return	void
*/

function wordup_register_plugin_update( $plugin ) {
	wordup_updates()->add_project( $plugin, 'plugin' );	
}

/*
*  wordup_register_theme_update
*
*  @param	array $plugin
*  @return	void
*/

function wordup_register_theme_update( $theme ) {
	wordup_updates()->add_project( $theme, 'theme' );	
}

endif; // class_exists check