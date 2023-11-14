<?php

namespace KitestringStudio\CloudflareTools;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Cloudflare_Extra_List_Table extends \WP_List_Table {
    public function __construct( $purge_key ) {
        $this->purge_key = $purge_key;
//        $this->query = $query;
        parent::__construct();
    }

    public function prepare_items() {
        $columns               = $this->get_columns();
        $this->_column_headers = [ $columns, [], [] ];

        $args  = [
            'post_type'      => [ 'post', 'page' ],
            'meta_key'       => $this->purge_key,
            'meta_value'     => 'on',
            'posts_per_page' => 20,
            'paged'          => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1
        ];
        $query = new \WP_Query( $args );
//        $query = $this->query;
        $this->items = $query->posts;
        $this->set_pagination_args( [
            'total_items' => $query->found_posts,
            'per_page'    => 20
        ] );
    }

    public function get_columns() {
        return [
            'title'  => __( 'Title', 'cloudflare-tools' ),
            'edit'   => __( 'Edit Post', 'cloudflare-tools' ),
            'delete' => __( 'Delete', 'cloudflare-tools' ),
        ];
    }

    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'title':
                return $item->post_title;
            case 'edit':
                return '<a href="' . get_edit_post_link( $item->ID ) . '">' . __( 'Edit', 'cloudflare-tools' ) . '</a>';
            default:
                return '';
        }
    }

    function column_delete( $item ) {
        $delete_url = wp_nonce_url(
            add_query_arg( array(
                'action'  => 'delete',
                'post_id' => $item->ID
            ), admin_url( 'admin.php?page=cloudflare-tools' ) ),
            'delete_purge_' . $item->ID
        );

        return '<a href="' . $delete_url . '" onclick="return confirm(\'Are you sure you want to delete this?\');">Delete</a>';
    }
}
