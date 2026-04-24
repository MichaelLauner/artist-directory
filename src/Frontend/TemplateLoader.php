<?php
namespace ArtistDirectory\Frontend;

use ArtistDirectory\Contracts\Service;
use ArtistDirectory\Infrastructure\PluginContext;

class TemplateLoader implements Service {
	private PluginContext $context;

	public function __construct( PluginContext $context ) {
		$this->context = $context;
	}

	public function register(): void {
		add_filter( 'template_include', array( $this, 'loadTemplates' ) );
	}

	public function loadTemplates( string $template ): string {
		if ( $this->isArtistArchiveRequest() ) {
			return $this->locateTemplate( 'archive-mw_artist.php' );
		}

		if ( is_singular( 'mw_artist' ) ) {
			return $this->locateTemplate( 'single-mw_artist.php' );
		}

		return $template;
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

	private function locateTemplate( string $filename ): string {
		$candidates = array(
			'artist-directory/' . $filename,
			$filename,
		);

		$template = locate_template( $candidates );
		if ( $template ) {
			return $template;
		}

		return $this->context->templatePath( $filename );
	}
}
