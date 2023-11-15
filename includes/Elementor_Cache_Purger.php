<?php

namespace KitestringStudio\CloudflareTools;

class Elementor_Cache_Purger {

    private static $instance = null;


    public function __construct() {
        add_filter( 'post_row_actions', [ $this, 'add_row_action' ], 10, 2 );
        add_filter( 'page_row_actions', [ $this, 'add_row_action' ], 10, 2 );
        add_filter( 'bulk_actions-edit-post', [ $this, 'register_bulk_action' ] );
        add_filter( 'handle_bulk_actions-edit-post', [ $this, 'handle_bulk_action' ], 10, 3 );
        add_action( 'admin_notices', [ $this, 'show_notices' ] );
        add_action( 'admin_init', [ $this, 'handle_row_action' ] );
    }

    public static function get_instance() {
        if ( self::$instance == null ) {
            self::$instance = new Elementor_Cache_Purger();
        }

        return self::$instance;
    }

    public function add_row_action($actions, $post) {
        if (current_user_can('edit_others_posts', $post->ID)) {
            $aria_label = sprintf(__('Purge cache for "%s"', 'cloudflare-tools'), get_the_title($post->ID));
            $actions['purge_cache'] = '<a href="' . wp_nonce_url('edit.php?action=purge_elementor_cache&post=' . $post->ID, 'purge_elementor_cache') . '" aria-label="' . esc_attr($aria_label) . '">Purge</a>';
        }

        return $actions;
    }


    public function register_bulk_action( $bulk_actions ) {
        $bulk_actions['purge_elementor_cache_bulk'] = 'Purge from Cache';

        return $bulk_actions;
    }

    public function handle_row_action() {
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'purge_elementor_cache' && check_admin_referer( 'purge_elementor_cache' ) ) {
            $post_id = isset( $_GET['post'] ) ? intval( $_GET['post'] ) : 0;
            if ( $post_id ) {
                $this->purge_cache_for_post( $post_id );
                // Redirect back to posts list with a success message
                wp_redirect( add_query_arg( 'purged_cache', 1, admin_url( 'edit.php' ) ) );
                exit;
            }
        }
    }

    public function handle_bulk_action( $redirect_to, $doaction, $post_ids ) {
        if ( $doaction !== 'purge_elementor_cache_bulk' ) {
            return $redirect_to;
        }

        foreach ( $post_ids as $post_id ) {
            $this->purge_cache_for_post( $post_id );
        }

        return add_query_arg( 'purged_cache', count( $post_ids ), $redirect_to );
    }

    private function purge_cache_for_post( $post_id ) {
        if ( current_user_can( 'edit_others_posts', $post_id ) ) {
            // Purge cache logic here
            // Elementor\Plugin::$instance->files_manager->clear_cache();
            // Update the post to trigger Elementor cache refresh
            wp_update_post( array(
                'ID' => $post_id,
                'post_modified' => current_time('mysql'),
                'post_modified_gmt' => current_time('mysql', 1),
            ) );
            // clean_post_cache( $post_id );
        }
    }

    public function show_notices() {
        if ( ! empty( $_REQUEST['purged_cache'] ) ) {
            $count = intval( $_REQUEST['purged_cache'] );
            printf( '<div class="notice notice-success is-dismissible"><p>%d posts have been purged from cache.</p></div>', $count );
        }
    }
}
