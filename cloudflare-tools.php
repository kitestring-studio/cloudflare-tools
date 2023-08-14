<?php
/**
 * Plugin Name: Cloudflare Extra
 * Plugin URI: https://kitestring.studio/plugins/cloudflare-tools
 * Description: An extension to the official Cloudflare plugin to add extra functionality.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL-2.0+
 * Text Domain: cloudflare-tools
 */

namespace CloudflareExtra;

class Cloudflare_Extra {
    public function __construct() {
        add_action( 'admin_init', [ $this, 'check_dependency' ] );
        add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ] );
        add_action( 'save_post', [ $this, 'save_meta_box_data' ] );
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
        add_filter( 'cloudflare_purge_by_url', [ $this, 'filter_urls' ], 10, 2 );

    }

    public function check_dependency() {
        if ( ! is_plugin_active( 'cloudflare/cloudflare.php' ) ) {
            add_action( 'admin_notices', [ $this, 'dependency_notice' ] );
        }
    }

    public function dependency_notice() {
        echo '<div class="error notice"><p>' . __( 'Cloudflare Extra requires the official Cloudflare plugin to be activated.', 'cloudflare-tools' ) . '</p></div>';
    }

    public function register_meta_box() {
        add_meta_box(
            'cloudflare_extra_always_purge',
            __( 'Always Purge This Page', 'cloudflare-tools' ),
            [ $this, 'meta_box_content' ],
            [ 'post', 'page' ], // Add other custom post types if needed
            'side'
        );
    }

    public function meta_box_content( $post ) {
        $always_purge = get_post_meta( $post->ID, 'always_purge', true );
        ?>
        <label>
            <input type="checkbox" name="always_purge" <?php checked( $always_purge, 'on' ); ?>>
            <?php _e( 'Always purge this page', 'cloudflare-tools' ); ?>
        </label>
        <?php
    }

    public function save_meta_box_data( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $always_purge = isset( $_POST['always_purge'] ) ? 'on' : 'off';
        update_post_meta( $post_id, 'always_purge', $always_purge );
    }

    public function add_settings_page() {
        add_options_page(
            'Cloudflare Tools',
            'Cloudflare Tools',
            'manage_options',
            'cloudflare-tools',
            [ $this, 'settings_page_content' ]
        );
    }

    public function settings_page_content() {
        // Query for posts that have the "always_purge" option set
        $args  = [
            'post_type'      => [ 'post', 'page' ], // Add other custom post types if needed
            'meta_key'       => 'always_purge',
            'meta_value'     => 'on',
            'posts_per_page' => 20 // WP default
        ];
        $query = new \WP_Query( $args );

        // Display the list
        echo '<h1>' . __( 'Cloudflare Tools', 'cloudflare-tools' ) . '</h1>';
        echo '<h2>' . __( 'Pages to Always Purge', 'cloudflare-tools' ) . '</h2>';
        echo '<ul>';
        while ( $query->have_posts() ) {
            $query->the_post();
            echo '<li>' . get_the_title() . ' <a href="' . get_edit_post_link() . '">' . __( 'Edit', 'cloudflare-tools' ) . '</a></li>';
        }
        echo '</ul>';
        wp_reset_postdata();

        // Add additional URLs form
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'cloudflare-tools' );
            do_settings_sections( 'cloudflare-tools' );
            ?>
            <input type="text" name="additional_url" placeholder="<?php _e( 'Additional URL', 'cloudflare-tools' ); ?>">
            <?php submit_button( __( 'Add Additional URL', 'cloudflare-tools' ) ); ?>
        </form>
        <?php
    }

    public function filter_urls( $urls, $postId ) {
        // Add URLs from posts/pages with "always_purge" set
        $args  = [
            'post_type'      => [ 'post', 'page' ], // Add other custom post types if needed
            'meta_key'       => 'always_purge',
            'meta_value'     => 'on',
            'posts_per_page' => - 1
        ];
        $query = new WP_Query( $args );
        while ( $query->have_posts() ) {
            $query->the_post();
            $urls[] = get_permalink();
        }
        wp_reset_postdata();

        // Add additional URLs
        $additional_urls = get_option( 'cloudflare_tools_additional_urls', [] );
        $urls            = array_merge( $urls, $additional_urls );

        return $urls;
    }
}

// Initialize the class
$cloudflare_extra = new Cloudflare_Extra();
