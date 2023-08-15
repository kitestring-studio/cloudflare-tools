<?php

namespace KitestringStudio\CloudflareExtra;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Cloudflare_Extra_List_Table extends \WP_List_Table {
    public function prepare_items() {
        $columns = $this->get_columns();
        $this->_column_headers = [$columns, [], []];

        $args = [
            'post_type' => [ 'post', 'page' ],
            'meta_key' => 'always_purge',
            'meta_value' => 'on',
            'posts_per_page' => 20,
            'paged' => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1
        ];
        $query = new \WP_Query( $args );

        $this->items = $query->posts;
        $this->set_pagination_args([
            'total_items' => $query->found_posts,
            'per_page' => 20
        ]);
    }

    public function get_columns() {
        return [
            'title' => __( 'Title', 'cloudflare-tools' ),
            'edit' => __( 'Edit', 'cloudflare-tools' )
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
}
