<?php
namespace ArtistDirectory\Frontend;

use ArtistDirectory\Contracts\Service;
use ArtistDirectory\Infrastructure\PluginContext;
use DirectoryCore\Integration\CoreApi;
use WP_Query;

class QueryManager implements Service {
	private PluginContext $context;

	public function __construct( PluginContext $context ) {
		$this->context = $context;
	}

	public function register(): void {
		add_action( 'pre_get_posts', array( $this, 'filterArchiveQuery' ) );
		add_action( 'template_redirect', array( $this, 'enforceSingleVisibility' ) );
	}

	public function filterArchiveQuery( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() || ! $this->isArtistArchiveRequest( $query ) ) {
			return;
		}

		$query->set(
			'meta_query',
			array(
				'relation' => 'OR',
				array(
					'key'     => 'mw_visibility_state',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'mw_visibility_state',
					'value'   => array( 'directory', 'profile' ),
					'compare' => 'IN',
				),
			)
		);
		$query->set( 'posts_per_page', -1 );
		$query->set( 'orderby', 'title' );
		$query->set( 'order', 'ASC' );

		$media_filters = $this->getMediaFilters();
		if ( ! empty( $media_filters ) ) {
			$query->set(
				'tax_query',
				array(
					array(
						'taxonomy' => 'mw_media',
						'field'    => 'slug',
						'terms'    => $media_filters,
					),
				)
			);
		}
	}

	public function enforceSingleVisibility(): void {
		if ( ! is_singular( 'mw_artist' ) ) {
			return;
		}

		$post_id = (int) get_queried_object_id();
		if ( $post_id <= 0 ) {
			return;
		}

		if ( current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( CoreApi::isArtistPubliclyViewable( $post_id ) ) {
			return;
		}

		global $wp_query;

		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		include get_404_template();
		exit;
	}

	private function getMediaFilters(): array {
		$raw_value = $_GET['media'] ?? array();

		if ( is_string( $raw_value ) ) {
			$raw_value = explode( ',', $raw_value );
		}

		if ( ! is_array( $raw_value ) ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map(
					'sanitize_title',
					array_map( 'wp_unslash', $raw_value )
				)
			)
		);
	}

	private function isArtistArchiveRequest( WP_Query $query ): bool {
		if ( $query->is_post_type_archive( 'mw_artist' ) ) {
			return true;
		}

		$post_type = $query->get( 'post_type' );
		if ( is_array( $post_type ) ) {
			return in_array( 'mw_artist', $post_type, true );
		}

		if ( 'mw_artist' === $post_type && $query->is_archive() ) {
			return true;
		}

		$object = get_queried_object();

		if ( $object instanceof \WP_Post_Type && 'mw_artist' === $object->name ) {
			return true;
		}

		$request_path = wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
		$archive_path = wp_parse_url( (string) get_post_type_archive_link( 'mw_artist' ), PHP_URL_PATH );

		return ! empty( $request_path ) && ! empty( $archive_path ) && trailingslashit( $request_path ) === trailingslashit( $archive_path );
	}
}
