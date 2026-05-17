<?php
namespace ArtistDirectory\Frontend;

use ArtistDirectory\Contracts\Service;
use ArtistDirectory\Infrastructure\PluginContext;
use ArtistDirectory\Settings\DirectorySettings;

class PageDirectoryInjector implements Service {
	private PluginContext $context;
	private DirectoryRenderer $renderer;

	public function __construct( PluginContext $context, ?DirectoryRenderer $renderer = null ) {
		$this->context  = $context;
		$this->renderer = $renderer ?: new DirectoryRenderer();
	}

	public function register(): void {
		add_filter( 'the_content', array( $this, 'appendDirectoryToSelectedPage' ), 20 );
	}

	public function appendDirectoryToSelectedPage( string $content ): string {
		if ( is_admin() || ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$page_id = DirectorySettings::getDirectoryPageId();
		if ( $page_id <= 0 || (int) get_queried_object_id() !== $page_id ) {
			return $content;
		}

		if ( has_block( 'artist-directory/directory', get_queried_object_id() ) ) {
			return $content;
		}

		return $content . $this->renderer->render( null, (string) get_permalink( $page_id ) );
	}
}
