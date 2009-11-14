<?php
    /**
     * Plugin Name: vlam-a-post
     * Plugin URI: http://wordpress.org/extend/plugins/vlam-a-post/
     * Description: Automatically creates a vl.am URL for every post you publish. Just write a new post, you'll catch my drift :-)
     * Version: 1.0
     * Author: Walter Vos
     * Author URI: http://www.waltervos.nl/
     */

    register_activation_hook( __FILE__, 'vap_activate' );
    add_action('admin_menu', 'add_vap_meta_box');
    add_action('admin_init', 'vap_admin_init');
    add_action('wp_dashboard_setup', 'add_vap_dashboard_widget' );
    add_action('admin_notices', 'vap_activation_notice');
    add_action('admin_print_styles', 'vap_add_css');

    function vap_admin_init() {
        wp_register_style('vap_css', WP_PLUGIN_URL . '/vlam-a-post/vap.css');
        register_setting('vap_options', 'vap_activated');
    }

    function vap_add_css() {
        wp_enqueue_style('vap_css');
    }

    function vap_activate() {
        update_option('vap_activated', true);
    }

    function vap_activation_notice() {
        if (get_option('vap_activated')) {
            echo '<div class="updated fade" id="message"><p><strong>Thanks for using vlam-a-post!</strong> I added a widget to your dashboard to keep track of your most recent vlam&apos;s. You can deactivate it if you wish by using the screen options on the dashboard.</p></div>';
            delete_option('vap_activated');
        }
    }

    function vap_dashboard_widget() {
        global $post;
        $myposts = get_posts('numberposts=5');
        if ($myposts) {
            echo '<div class="rss-widget"><ul>';
            foreach($myposts as $post) :
                if (!get_vlam_url($post->ID)) continue;
                echo '<li><a class="rsswidget" href="' . get_vlam_url($post->ID) . '?">' . get_the_title() . '</a> (' . get_vlam_count($post->ID) . ' clicks). <a target="_blank" href="http://twitter.com/home?status=' . get_vlam_url($post->ID) . '">Post to twitter</a></li>';
            endforeach;
            echo '</ul></div>';
        }
    }

    // Create the function use in the action hook

    function add_vap_dashboard_widget() {
        wp_add_dashboard_widget('vap_dashboard_widget', 'Recent vl.am&apos;s', 'vap_dashboard_widget');
    }

    // Hoook into the 'wp_dashboard_setup' action to register our other functions


    function add_vap_meta_box() {
        if( function_exists( 'add_meta_box' )) {
            $context = (get_bloginfo('version') >= '2.7') ? 'side' : 'advanced';
            add_meta_box('vlam_a_post', __('vlam-a-post'), 'vap_meta_box', 'post', $context, 'high');
        }
        else {
            add_action('dbx_post_advanced', 'vap_old_meta_box' );
        }
    }

    function vap_meta_box () {
        global $post;
        if ($post->post_status != 'publish') { // We don't do anything when the post hasn't been published yet
            $message = '<p>This post hasn\'t been published yet. A vl.am URL will be created when you publish this post.</p>';
        }
        else { // Post is published, let's get some vl.am info
            if ($vlam_url = get_vlam_url($post->ID)) { // Succes
                $vlam_count = get_vlam_count($post->ID, false);
                $message = '<p>This posts vl.am URL is <a target="_blank" href="' . $vlam_url . '?">' . $vlam_url . '</a>. It has been clicked ' . $vlam_count . ' times. <a target="_blank" href="http://twitter.com/home?status=' . urlencode($vlam_url) . '">Tweet it!</a></p>';
            }
            else { // We were unable to get a vl.am URL for this post
                $message = '<p>An error occured. I\'d like to remind you that vl.am doesn\'t create short URLs from local domains so if this is your test install on your localhost than that should be the problem.</p>';
            }
        }
        echo $message;
    }

    function vap_old_meta_box() {
        echo '<div class="dbx-box-wrapper">' . "\n";
        echo '<fieldset id="vlam-a-post-fieldset" class="dbx-box">' . "\n";
        echo '<div class="dbx-handle-wrapper"><h3 class="dbx-handle">' . __( 'vlam-a-post', 'vlam_a_post' ) . "</h3></div>";
        echo '<div class="dbx-content-wrapper"><div class="dbx-content">';
        vap_meta_box();
        echo "</div></div></fieldset></div>\n";
    }


    function get_vlam_url($post_id) {
        if ($vlam_url = get_post_meta($post_id, '_vlam_url', true)) return $vlam_url; // We already have a vl.am URL for this post, return immediately
        $request = new WP_Http;
        $shorten_response = $request->request('http://vl.am/api/shorten/plain/' . get_permalink($post_id));
        $vlam_url = $shorten_response['body'];
        unset ($request);
        if ($vlam_url == '') return false; // Something went wrong trying to get a vl.am URL
        return $vlam_url; // All is well
    }

    function get_vlam_count($post_id, $use_cache = true) {
        if ($use_cache) {
            if ($vlam_count = get_post_meta($post_id, '_vlam_count', false)) {
                $vlam_count = $vlam_count[0];
                if ((strtotime('now') - $vlam_count['last_update']) < 300) {
                    return $vlam_count['value'];
                }
            }
        }
        $vlam_url = get_vlam_url($post_id);
        $request = new WP_Http;
        $count_response = $request->request('http://vl.am/api/count/plain/' . $vlam_url);
        $vlam_count = $count_response['body'];
        unset ($request);
        if ($vlam_count == '') return false;
        update_post_meta($post_id, '_vlam_count', array('value' => $vlam_count, 'last_update' => strtotime('now')));
        return $vlam_count;
    }
?>