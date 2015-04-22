<?php
    /**
     * Plugin Name: vlam-a-post
     * Plugin URI: http://wordpress.org/extend/plugins/vlam-a-post/
     * Description: Automatically creates a vl.am URL for every post you publish. Just write a new post, you'll catch my drift :-)
     * Version: 2.0
     * Author: Walter Vos
     * Author URI: http://www.waltervos.nl/
     */

    add_action('admin_init', 'vap_admin_init');
    add_action('admin_notices', 'vap_activation_notice');

    function vap_admin_init() {
        register_setting('vap_options', 'vap_eol');
        unregister_setting('vap_options', 'vap_activated');
    }

    function vap_activation_notice() {
        if (!get_option('vap_eol')) :
    ?>
        <div class="updated">
            <p>Were you really still using vlam-a-post? How? As far as I know, it hasn't been working for years :). You should really de-activate this plugin and delete it.</p>
        </div>
    <?php
        endif;
        update_option('vap_eol', true);
    }
?>