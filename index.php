<?php
    /**
     * Plugin Name: vlam-a-post
     * Plugin URI: http://wordpress.org/extend/plugins/vlam-a-post/
     * Description: Invlammable
     * Version: 0.1
     * Author: Walter Vos
     * Author URI: http://www.waltervos.nl/
     */

    add_action('admin_menu', 'add_vap_meta_box');
    add_action('admin_init', 'register_vap_settings' );
    //add_action('admin_init', 'vap_add_admin' );

        /*function vap_add_admin() {
            add_submenu_page(
                'tools.php',
                'vlam-a-post',
                'vlam-a-post',
                8,
                __FILE__,
                'vap_display_admin'
            );
        }*/


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
        if ($vlam_url = get_post_meta($post->ID, '_vlam_url', true)) { // There is already a vl.am URL in post_meta
            $vlam_count = get_vlam_count($vlam_url);
            $message = '<p>This posts vl.am URL is <a target="_blank" href="' . $vlam_url . '?">' . $vlam_url . '</a>. It has been clicked ' . $vlam_count . ' times. <a target="_blank" href="http://twitter.com/home?status=' . urlencode($vlam_url) . '">Tweet it!</a></p>';
        }
        elseif ($post->post_status != 'publish') {
            $message = '<p>This post hasn\'t been published yet. A vl.am URL will be created when you publish this post.</p>';
        }
        else { // We don't have a vl.am URL for this post yet, let's get one
            if ($vlam_url = get_vlam_url(get_permalink($post->ID))) { // Succes, now let's store it in post_meta
                update_post_meta($post->ID, '_vlam_url', $vlam_url);
                $vlam_count = get_vlam_count($vlam_url);
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
        echo '<div class="dbx-handle-wrapper"><h3 class="dbx-handle">' .
            __( 'vlam-a-post', 'vlam_a_post' ) . "</h3></div>";

        echo '<div class="dbx-content-wrapper"><div class="dbx-content">';

        // output editing form

        vap_meta_box();

        // end wrapper

        echo "</div></div></fieldset></div>\n";
    }


    function get_vlam_url($long_url) {
        $request = new WP_Http;
        $shorten_response = $request->request('http://vl.am/api/shorten/plain/' . $long_url);
        $vlam_url = $shorten_response['body'];
        unset ($request);
        if ($vlam_url == '') return false;
        return $vlam_url;
    }

    function get_vlam_count($vlam_url) {
        $request = new WP_Http;
        $count_response = $request->request('http://vl.am/api/count/plain/' . $vlam_url);
        $vlam_count = $count_response['body'];
        unset ($request);
        if ($vlam_count == '') return false;
        return $vlam_count;
    }

    function register_vap_settings() {
        register_setting('vap_options', 'vap_sizes');
    }

    /*function vap_display_admin() {
        $messages = false;
        ?>
    <div class="wrap">
        <h2>vlam-a-post</h2>
        <p>Ik hoef hier helemaal niks mee!</p>
        <?php if (isset($messages['errors'])) { ?>
        <div class="error below-h2" id="message">
            <p><strong>Something(s) went wrong:</strong></p>
            <ul>
                <?php foreach ($messages['errors'] as $error) { ?>
                <li><?php echo $error; ?></li>
                <?php } ?>
            </ul>
        </div>
        <?php } ?>
        <?php if (isset($messages['succes'])) { ?>
        <div class="updated fade below-h2" id="message">
            <p><strong>That went quite well:</strong></p>
            <ul>
                <?php foreach ($messages['succes'] as $succes) { ?>
                <li><?php echo $succes; ?></li>
                <?php } ?>
            </ul>
        </div>
        <?php } ?>
        <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
                <?php settings_fields(vap_options); ?>

            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save Changes'); ?>" />
            </p>
        </form>
    </div>
        <?php
    }*/
?>