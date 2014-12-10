<?php

/**
 *
 * @package     EIC_User
 * @author      Nina Cecilie Højholdt
 * @license     @TODO [description]
 * @copyright   2014 MICO
 * @link        MICO, http://www.mico.dk
 *
 * @wordpress-plugin
 * Plugin Name:     Editor-in-chief user role
 * Plugin URI:      @TODO
 * Description:     Creates a new user role, Editor-in-chief, with capabilities to add users and edit theme apperances.
 * Version:         1.0.0
 * Author:          Nina Cecilie Højholdt
 * Author URI:      http://www.mico.dk
 * Text Domain:     eic-user
 * License:         @TODO
 * GitHub URI:      @TODO
 */
 
 class EIC_User {

    protected $plugin_slug = 'eic-user';

    public $role_name = 'editor_in_chief';
    public $role_display_name = 'Editor-in-chief';


    function __construct() {

        // Load plugin text domain
        add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

        // Add role  when new blog is added
        add_action( 'wpmu_new_blog', array( $this, 'add_role_to_blog' ) );

        //Remove 
        add_filter('editable_roles', array(&$this, 'remove_admin_editable_roles'));
        add_filter('map_meta_cap', array(&$this, 'map_meta_cap'), 10, 4);


        // Function to translate role display name. Currently not working. 
        //add_filter( 'gettext_with_context', array(&$this,'wpdev_141551_translate_user_roles'), 10, 4 );
    }

    function activate( $network_wide ) {
        if ( $network_wide ) {
            $blogs = $this->_blogs();
            foreach ( $blogs as $blog_id ) {
                switch_to_blog( $blog_id );

                $capabilities = $this->capabilities();
                add_role( $this->role_name, $this->role_display_name, $capabilities );
                
                //add_role( $this->role_name, __('Editor-in-chief','eic-user'), $capabilities );
                //_x( 'Editor-in-chief', 'User role', 'plugin-text-domain' );
                
                restore_current_blog();
            }

        } else {
            $capabilities = $this->capabilities();
            add_role( $this->role_name, $this->role_display_name, $capabilities );

            //add_role( $this->role_name, __('Editor-in-chief','eic-user'), $capabilities );
            //_x( 'Editor-in-chief', 'User role', 'plugin-text-domain' );
        }
    }

    function deactivate( $network_wide ) {
        if ( $network_wide ) {
            $blogs = $this->_blogs();
            foreach ( $blogs as $blog_id ) {
                switch_to_blog( $blog_id );

                remove_role( $this->role_name );

                restore_current_blog();
            }

        } else {
            remove_role( $this->role_name );
        }
    }


    // Load the plugin text domain for translation
    public function load_plugin_textdomain() {
        $domain = $this->plugin_slug;

        $locale = apply_filters( 'plugin_locale', get_locale(), $domain );
        $fullpath = dirname( basename( plugins_url() ) ) . '/' . basename(dirname(__FILE__))  . '/languages/';
    
        load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
        load_plugin_textdomain( $domain, false, $fullpath );

        //_e('Editor-in-chief', 'eic-user');
        //die();
    
    }


    // Manage capabilities for the new user
    function capabilities() {

        // Get editor user capabilities
        $admin_role = get_role( 'editor' );
        $capabilities = $admin_role->capabilities;

        // Can edit theme options
        $capabilities[ 'edit_theme_options' ] = true;
        $capabilities[ 'switch_themes' ] = false;
        
        $capabilities[ 'edit_users' ] = true;
        $capabilities[ 'list_users' ] = true;
        $capabilities[ 'promote_users' ] = true;
        $capabilities[ 'create_users' ] = true;
        $capabilities[ 'add_users' ] = true;
        $capabilities[ 'delete_users' ] = true;

        $capabilities = apply_filters( 'td_webmaster_capabilities', $capabilities );

        return $capabilities;
    }

    // Remove administrator from list of editable roles
    function remove_admin_editable_roles($roles) {
        if(isset($roles['administrator']) && !current_user_can('administrator')) {
            unset($roles['administrator']);
        }
        return $roles;
    }


    // Prevent EIC from editing or deleting administrator 
    function map_meta_cap($caps, $cap, $user_id, $args) {


        if($cap == 'edit_user' || $cap == 'remove_user' || $cap == 'promote_user') :


            if(isset($args[0]) && $args[0] == $user_id) : 
                return $caps;
            elseif(!isset($args[0])) :
                $caps[] = 'do_not_allow';
            endif;

            $other = new WP_User(absint($args[0]));

            if($other->has_cap('administrator')) : 
                if(!current_user_can('administrator')) :
                    $caps[] = 'do_not_allow';
                endif;
            endif;

            return $caps;

        elseif($cap == 'delete_user' || $cap == 'delete_users') :

            if(!isset($args[0])) :
                return $caps;
            endif;

            $other = new WP_User(absint($args[0]));

            if($other->has_cap('administrator')) : 
                if(!current_user_can('administrator')) :
                    $caps[] = 'do_not_allow';
                endif;
            endif;

            return $caps;

        endif;

        return $caps;

    }
}


if(!isset($eic_user)) :
    $eic_user = new EIC_User(); 
endif;

register_activation_hook( __FILE__, array( $eic_user, 'activate' ) );
register_deactivation_hook( __FILE__, array( $eic_user, 'deactivate' ) );


?>