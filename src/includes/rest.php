<?php
 
require_once __DIR__.'/installer.php';

class Wordup_REST_Route extends WP_REST_Controller {
 
  /**
   * Register the routes for the objects of the controller.
   */
  public function register_routes() {
    $version = '1';
    $namespace = 'wordup/v' . $version;
    $base = 'projects';

    register_rest_route( $namespace, '/' . $base, array(
      array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => array( $this, 'get_projects' ),
        'permission_callback' => array( $this, 'get_projects_permissions_check' ),
        'args'                => array(
 
        ),
      ),
      array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => array( $this, 'create_project' ),
        'permission_callback' => array( $this, 'create_project_permissions_check' ),
        'args'                => array( 
                                    'wordup_project' => array('required' => true),
                                    'wordup_type' => array('required' => true),
                                    'wordup_private_key' => array('required' => true)
                                )
       ),
       array(
        'methods'             => WP_REST_Server::DELETABLE,
        'callback'            => array( $this, 'delete_project' ),
        'permission_callback' => array( $this, 'delete_project_permissions_check' ),
        'args'                => array( 
                                    'wordup_project' => array('required' => true),
                                )
       )
    ) );

    register_rest_route( $namespace, '/' . $base . '/install', array(
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'install_project' ),
            'permission_callback' => array( $this, 'install_project_permissions_check' ),
            'args'                => array( 
                                        'wordup_project' => array('required' => true),
                                    )
           )
    ));

  }
 
  /**
   * Get a collection of items
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|WP_REST_Response
   */
  public function get_projects( $request ) {
    $items = array(); //do a query, call another class, etc
    $data = array();
    
 
    return new WP_REST_Response( $data, 200 );
  }
 
 
  /**
   * Create one item from the collection
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|WP_REST_Response
   */
  public function create_project( $request ) {
    //$item = $this->prepare_item_for_database( $request );
    
    $params = $request->get_params();

    $projects = get_option('wordup_projects', array()); 
	
	$type = $params['wordup_type'];
	$project_id = $params['wordup_project'];

	$projects[$project_id] = array(
		'type' => $type,
		'private_key' => $params['wordup_private_key']
	);

	update_option('wordup_projects', $projects);

    return new WP_REST_Response( 201 );
    
  }
 
  
  /**
   * Delete one item from the collection
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|WP_REST_Response
   */
  public function delete_project( $request ) {
    $item = $this->prepare_item_for_database( $request );

    $params = $request->get_params();

    $wordup_projects = get_option('wordup_projects', array()); 

    unset($wordup_projects[$params['wordup_project']]);
	
	update_option('wordup_projects', $wordup_projects);

    return new WP_REST_Response( true, 204 );
      
  }

   /**
   * Instsall a project
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|WP_REST_Response
   */
  public function install_project( $request ) {
    $item = $this->prepare_item_for_database( $request );

    $params = $request->get_params();

    $wordup_projects = get_option('wordup_projects', array()); 

    $project =  $wordup_projects[$params['wordup_project']];
    
	$installer = new Wordup_Project_install($params['wordup_project'], $project['type'] );
    $result = $installer->install( $project['private_key'] );
    
    return new WP_REST_Response( array('success' => $result, 'logs'=> $installer->logs['installer']), 200 );
      
  }
 
  /**
   * Check if a given request has access to get items
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|bool
   */
  public function get_projects_permissions_check( $request ) {
    //return true; <--use to make readable by all
    return current_user_can( 'manage_options' );
  }
 
 
  /**
   * Check if a given request has access to create project
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|bool
   */
  public function create_project_permissions_check( $request ) {
    return current_user_can( 'manage_options' );
  }
 
  /**
   * Check if a given request has access to delete a specific item
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|bool
   */
  public function delete_project_permissions_check( $request ) {
    return $this->create_project_permissions_check( $request );
  }

    /**
   * Check if a given request has access to install
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|bool
   */
  public function install_project_permissions_check( $request ) {
    return $this->create_project_permissions_check( $request );
  }
 
 
  /**
   * Prepare the item for create or update operation
   *
   * @param WP_REST_Request $request Request object
   * @return WP_Error|object $prepared_item
   */
  protected function prepare_item_for_database( $request ) {
    return array();
  }
 
  /**
   * Prepare the item for the REST response
   *
   * @param mixed $item WordPress representation of the item.
   * @param WP_REST_Request $request Request object.
   * @return mixed
   */
  public function prepare_item_for_response( $item, $request ) {
    return array();
  }
 
  
  
}