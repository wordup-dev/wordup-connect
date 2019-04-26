<?php

if( ! defined( 'ABSPATH' ) ) exit;

require_once( ABSPATH .'/wp-admin/includes/file.php' );

/**
 * Wordup_backup
 */

class Wordup_backup
{
    const BACKUP_FOLDER = 'wordup/';

    public $unique_folder;

    public $wordup_backup_pkg = array(
        'wp_version'=>'',
        'db_name'=>'',
        'themes'=> array(),
        'plugins'=> array()
    );
    
    function __construct() {
        global $wp_filesystem;
        WP_Filesystem();

        ini_set('memory_limit', '512M'); 

        $this->unique_folder = self::get_random_key();
    }

    /**
     * Create a downloadable, temporary folder in wp-content
     */
    public function create_updraft_archive($test=FALSE){
        $this->delete_old_backup();

        $parts = array('plugins','themes','others','db','uploads');
        $files = array();
        $last_backup = get_option('updraft_last_backup', FALSE);
        $updraft_folder =  trailingslashit(WP_CONTENT_DIR).get_option('updraft_dir', 'updraft').'/';  
        
        if( $last_backup && $last_backup['success'] == '1'){
            $backups = $last_backup['backup_array'];
            foreach($parts as $part){
                $backup_part =  !is_array($backups[$part]) ? array($backups[$part]) : $backups[$part];

                foreach($backup_part as $file){
                    if(is_file($updraft_folder.$file)){
                        if($test === TRUE){
                            return TRUE;
                        }
                        $files[$part][] = trailingslashit(self::get_backup_root(TRUE)).$this->unique_folder.'/'.$file;
                        copy($updraft_folder.$file ,  trailingslashit($this->get_backup_path()).$file);
                    }
                }
            }
        }

        if($test === TRUE){
            return FALSE;
        }

        if(count($files) > 0){
            $this->update_wordup_options('updraft', $files);
            return $files;
        }
        return $files;
    }

    /**
     * Delete an old backup folder
     */
    public function delete_old_backup(){
        global $wp_filesystem;

        $old_backup = get_option('wordup_backup_infos');
        if($old_backup){
            $wp_filesystem->delete(self::get_backup_root().$old_backup['folder'], TRUE);
        }
    }

    /**
     * Delete all wordup folders in wp-content
     */
    public function delete_all_wordup_folders(){
        global $wp_filesystem;

        $wp_filesystem->delete(self::get_backup_root(), TRUE);
    }


    /**
     * Set wordup options for a backup
     */
    public function update_wordup_options($type, $files){
        global $wp_version;

        $data = array(
            'folder'=>$this->unique_folder,
            'locale'=>get_locale(), 
            'wp_version'=>$wp_version,
            'type'=>$type,
            'files'=>$files,
            'created'=>time()
        );
        update_option('wordup_backup_infos', $data);
    }

    /**
     * Get the wordup folder or url path
     */
    public static function get_backup_root($url=FALSE){
        if($url === TRUE){
            return trailingslashit(content_url()).self::BACKUP_FOLDER;
        }else{
            return trailingslashit(WP_CONTENT_DIR).self::BACKUP_FOLDER;  
        }
    }

    /**
     * Get a specific wordup temp folder
     */
    public function get_backup_path($is_tmp=FALSE){
        
        $path = self::get_backup_root().$this->unique_folder;
        if(!is_dir($path)){
            wp_mkdir_p( $path.'/tmp' );
        }
        return $path.(($is_tmp===TRUE) ? '/tmp' : '');
    }


    /**
     * Check if a plugin is active
     */
    public static function is_plugin_active( $slug ){
        $active_plugins=get_option('active_plugins');
        return in_array($slug,$active_plugins);
    }


    /**
     * Generate a random key
     */
    public static function get_random_key($length=32){
        if(function_exists('random_bytes')){
            $random_token = random_bytes(32);
        }else if(function_exists('openssl_random_pseudo_bytes')){
            $random_token = openssl_random_pseudo_bytes(32);
        } else {
            $random_token = uniqid("", TRUE); //This is not really secure.
        }

        $random_b64 = base64_encode($random_token);
        return substr(str_replace(['/', '+', '='],'', $random_b64), 0, $length);

    }

}