<?php
namespace ArtistDirectory\Assets;

use ArtistDirectory\Contracts\Service;
use ArtistDirectory\Infrastructure\PluginContext;
use ArtistDirectory\Settings\DirectorySettings;

class AssetManager implements Service {
	private PluginContext $context;

	public function __construct( PluginContext $context ) {
		$this->context = $context;
	}

	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueueAssets' ) );
	}

	public function enqueueAssets(): void {
		if ( ! $this->isArtistArchiveRequest() && ! $this->isDirectoryPageRequest() && ! is_singular( 'mw_artist' ) ) {
			return;
		}

		wp_enqueue_style(
			'artist-directory',
			$this->context->assetUrl( 'assets/css/artist-directory.css' ),
			array(),
			$this->context->version()
		);

		if ( $this->isArtistArchiveRequest() || $this->isDirectoryPageRequest() ) {
			wp_enqueue_script(
				'artist-directory',
				$this->context->assetUrl( 'assets/js/artist-directory.js' ),
				array(),
				$this->context->version(),
				true
			);
		}
	}

	private function isArtistArchiveRequest(): bool {
		if ( is_post_type_archive( 'mw_artist' ) ) {
			return true;
		}

		$post_type = get_query_var( 'post_type' );
		if ( is_array( $post_type ) ) {
			return in_array( 'mw_artist', $post_type, true );
		}

		if ( 'mw_artist' === $post_type && is_archive() ) {
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

	private function isDirectoryPageRequest(): bool {
		$page_id = DirectorySettings::getDirectoryPageId();

		return $page_id > 0 && is_singular( 'page' ) && (int) get_queried_object_id() === $page_id;
	}
}
