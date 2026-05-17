<?php
namespace ArtistDirectory\Block;

use ArtistDirectory\Contracts\Service;
use ArtistDirectory\Frontend\DirectoryRenderer;
use ArtistDirectory\Infrastructure\PluginContext;

class DirectoryBlock implements Service {
	private PluginContext $context;
	private DirectoryRenderer $renderer;

	public function __construct( PluginContext $context, ?DirectoryRenderer $renderer = null ) {
		$this->context  = $context;
		$this->renderer = $renderer ?: new DirectoryRenderer();
	}

	public function register(): void {
		add_action( 'init', array( $this, 'registerBlock' ) );
	}

	public function registerBlock(): void {
		$block_dir = $this->context->pluginDir() . 'blocks/artist-directory';

		wp_register_script(
			'artist-directory-block-editor',
			$this->context->assetUrl( 'blocks/artist-directory/index.js' ),
			array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n' ),
			$this->context->version(),
			true
		);

		register_block_type(
			$block_dir,
			array(
				'editor_script'   => 'artist-directory-block-editor',
				'render_callback' => array( $this, 'renderBlock' ),
			)
		);
	}

	public function renderBlock( array $attributes = array() ): string {
		$this->enqueueFrontendAssets();

		$align     = isset( $attributes['align'] ) ? sanitize_html_class( (string) $attributes['align'] ) : '';
		$classes   = array( 'wp-block-artist-directory-directory' );
		$classes[] = $align ? 'align' . $align : '';
		$classes   = array_filter( $classes );
		$base_url  = $this->getCurrentBaseUrl();

		return sprintf(
			'<div class="%1$s">%2$s</div>',
			esc_attr( implode( ' ', $classes ) ),
			$this->renderer->render( null, $base_url )
		);
	}

	private function enqueueFrontendAssets(): void {
		wp_enqueue_style(
			'artist-directory',
			$this->context->assetUrl( 'assets/css/artist-directory.css' ),
			array(),
			$this->context->version()
		);

		wp_enqueue_script(
			'artist-directory',
			$this->context->assetUrl( 'assets/js/artist-directory.js' ),
			array(),
			$this->context->version(),
			true
		);
	}

	private function getCurrentBaseUrl(): string {
		if ( is_singular() ) {
			$permalink = get_permalink( get_queried_object_id() );
			if ( $permalink ) {
				return (string) $permalink;
			}
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$request_uri = strtok( $request_uri, '?' );

		return home_url( $request_uri ?: '/' );
	}
}
