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

namespace KitestringStudio\CloudflareTools;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once __DIR__ . '/vendor/autoload.php';

class CloudflareTools {
	private static $instance;
	private string $purge_key = 'always_purge';

	private function __construct() {
	}

	public static function get_instance() {
		if ( self::$instance == null ) {
			self::$instance = new CloudflareTools();
		}

		return self::$instance;
	}

	public function init() {
		add_filter( 'cloudflare_purge_by_url', [ $this, 'filter_urls' ], 10, 2 );

		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );

		add_action( 'save_post', [ $this, 'save_meta_box_data' ] );
		add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ] );

		add_action( 'admin_init', [ $this, 'check_dependency' ] );
		add_action( 'admin_init', [ $this, 'register_additional_url_setting' ] );
		add_action( 'admin_init', [ $this, 'handle_delete_purge' ] );
	}

	public function check_dependency() {
		if ( ! is_plugin_active( 'cloudflare/cloudflare.php' ) ) {
			add_action( 'admin_notices', [ $this, 'dependency_notice' ] );
		}
	}

	public function dependency_notice() {
		echo '<div class="error notice"><p>' . __( 'Cloudflare Tools requires the official Cloudflare plugin to be activated.', 'cloudflare-tools' ) . '</p></div>';
	}

	public function register_meta_box() {
		add_meta_box(
			'cloudflare_tools_always_purge',
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
            <input type="checkbox" name="always_purge" <?php checked( esc_attr( $always_purge ), 'on' ); ?>>
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

		$always_purge = isset( $_POST[ $this->purge_key ] ) ? 'on' : 'off';
		update_post_meta( $post_id, $this->purge_key, $always_purge );
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
		echo '<div class="wrap">';
		$this->display_notices();

		$this->display_page_header();

		$this->display_purge_list();

//		$this->display_additional_urls();
//		$this->display_add_url_form();
		echo "</div><!-- /.wrap -->";
	}

	private function display_notices() {
		if ( isset( $_GET['deleted'] ) && $_GET['deleted'] === 'true' ) {
			echo '<div class="notice notice-success is-dismissible"><p>Item deleted successfully.</p></div>';
		}
	}

	private function display_page_header() {
		echo '<h1>' . __( 'Cloudflare Tools', 'cloudflare-tools' ) . '</h1>';
	}

	private function display_purge_list() {
//		$args       = $this->get_query_args();
//		$query      = new \WP_Query( $args );
		$list_table = new Cloudflare_Extra_List_Table( $this->purge_key );
		$list_table->prepare_items();
		echo '<h2>' . __( 'Pages to Always Purge', 'cloudflare-tools' ) . '</h2>';
		$list_table->display();
		wp_reset_postdata();
	}

	private function display_additional_urls() {
		$additional_urls = get_option( 'cloudflare_tools_additional_urls', [] );
		echo '<h2>' . __( 'Additional URLs to Always Purge', 'cloudflare-tools' ) . '</h2>';
		echo '<ul class="cloudflare-tools__additional-urls">';
		foreach ( $additional_urls as $url ) {
			$url = esc_url( $url );
			echo "<li><a href='$url'>" . $url . "</a></li>";
		}
		echo '</ul>';
	}

	private function display_add_url_form() {
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

	public function handle_delete_purge() {
		if ( isset( $_GET['action'] ) && $_GET['action'] == 'delete' && isset( $_GET['post_id'] ) ) {
			$post_id = intval( $_GET['post_id'] );
			if ( ! current_user_can( 'delete_others_posts' ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'delete_purge_' . $post_id ) ) {
				wp_die( 'You are not allowed to delete this item.' );
			}

			delete_post_meta( $post_id, $this->purge_key );

			// Redirect with success message
			wp_redirect( add_query_arg( 'deleted', 'true', admin_url( 'admin.php?page=cloudflare-tools' ) ) );
			exit;
		}
	}

	public function filter_urls( $urls, $postId ): array {
		// Add URLs from posts/pages with "always_purge" set
		$args                   = $this->get_query_args();
		$args['posts_per_page'] = - 1; // Get all posts
		$query                  = new \WP_Query( $args );
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

	/**
	 * @return array
	 */
	private function get_query_args(): array {
		return [
			'post_type'      => [ 'post', 'page' ], // Add other custom post types if needed
			'meta_key'       => $this->purge_key,
			'meta_value'     => 'on',
			'posts_per_page' => 20 // WP default
		];
	}

	public function register_additional_url_setting() {
		register_setting( 'cloudflare-tools', 'cloudflare_tools_additional_urls', [
			'sanitize_callback' => [ $this, 'sanitize_additional_urls' ]
		] );
	}

	public function sanitize_additional_urls( array $urls ): array {
		$additional_url = isset( $_POST['additional_url'] ) ? esc_url_raw( $_POST['additional_url'] ) : '';
		if ( $additional_url ) {
			$urls[] = $additional_url;
		}

		return $urls;
	}
}

// Initialize the class
$cloudflare_tools = CloudflareTools::get_instance();
$cloudflare_tools->init();
