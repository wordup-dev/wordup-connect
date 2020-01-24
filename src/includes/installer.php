<?php

include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
include_once ABSPATH . 'wp-admin/includes/file.php' ;
include_once ABSPATH . 'wp-admin/includes/misc.php' ;


/**
 * Wordup_Quiet_Skin class
 * 
 * Grabs the feedback of the upgrader process
 * 
 */
class Wordup_Quiet_Skin extends WP_Upgrader_Skin
{
    public $wordup_feedback = array();
    public $done_header = true;
    public $done_footer = true;

    public function feedback($string, ...$args) { 
        $this->wordup_feedback[] = $string;
    }
}

/**
 * Wordup_Project_install class
 * 
 * Installs a wordup hosted project
 * 
 */

class Wordup_Project_install
{

    const API_SERVER = 'https://wordup-test.appspot.com/release_dl';
    public $download_url = '';
    public $type = '';
    public $logs = array();

    function __construct($project_id, $type) {
        global $wp_filesystem;
        WP_Filesystem();

        $this->type = $type;
        $this->download_url = self::API_SERVER.'/'.$project_id.'/latest/'.$project_id.'.zip';
    }

    public function install($private_key=false) {
        $skin = new Wordup_Quiet_Skin();

        if($this->type === 'plugin'){
            $upgrader = new Plugin_Upgrader($skin);
        }else{
            $upgrader = new Theme_Upgrader($skin);
        }

        $dl_url = $this->download_url ;
        if($private_key){
            $dl_url = $dl_url.'?key='.$private_key;
        }
        echo $dl_url;
        $installed = $upgrader->install( $dl_url);
        $this->add_log($upgrader->skin->wordup_feedback, 'installer');

        return $installed;
    }

    public function add_log($string, $category='main'){
        $this->logs[$category][] = $string;
    }

}


