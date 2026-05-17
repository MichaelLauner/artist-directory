<?php
namespace ArtistDirectory\Settings;

class DirectorySettings {
	public const OPTION_DIRECTORY_PAGE_ID = 'artist_directory_page_id';
	public const OPTION_STYLE_MODE = 'artist_directory_style_mode';
	public const OPTION_DEFAULT_VIEW = 'artist_directory_default_view';
	public const OPTION_VISIBLE_TAXONOMIES = 'artist_directory_visible_taxonomies';
	public const OPTION_CROP_CARD_IMAGES = 'artist_directory_crop_card_images';

	public static function getDirectoryPageId(): int {
		$page_id = absint( get_option( self::OPTION_DIRECTORY_PAGE_ID, 0 ) );

		if ( $page_id <= 0 || 'page' !== get_post_type( $page_id ) ) {
			return 0;
		}

		return $page_id;
	}

	public static function getDirectoryUrl(): string {
		$page_id = self::getDirectoryPageId();
		if ( $page_id > 0 ) {
			return (string) get_permalink( $page_id );
		}

		return (string) get_post_type_archive_link( 'mw_artist' );
	}

	public static function getStyleMode(): string {
		return self::sanitizeStyleMode( get_option( self::OPTION_STYLE_MODE, 'light' ) );
	}

	public static function getThemeClass(): string {
		return 'artist-directory--theme-' . self::getStyleMode();
	}

	public static function getDefaultView(): string {
		return self::sanitizeDefaultView( get_option( self::OPTION_DEFAULT_VIEW, 'cards' ) );
	}

	public static function shouldCropCardImages(): bool {
		return self::sanitizeBoolean( get_option( self::OPTION_CROP_CARD_IMAGES, '1' ) );
	}

	public static function getCardImageClass(): string {
		return self::shouldCropCardImages() ? 'artist-directory--card-images-cropped' : 'artist-directory--card-images-uncropped';
	}

	public static function discoveryTaxonomyLabels(): array {
		return array(
			'mw_artist_service'       => __( 'Services', 'artist-directory' ),
			'mw_artist_audience'      => __( 'Audience', 'artist-directory' ),
			'mw_artist_project_scale' => __( 'Project Scale', 'artist-directory' ),
			'mw_artist_availability'  => __( 'Availability', 'artist-directory' ),
			'mw_artist_service_area'  => __( 'Service Area', 'artist-directory' ),
		);
	}

	public static function getVisibleTaxonomies(): array {
		return self::sanitizeVisibleTaxonomies( get_option( self::OPTION_VISIBLE_TAXONOMIES, array_keys( self::discoveryTaxonomyLabels() ) ) );
	}

	public static function isTaxonomyVisible( string $taxonomy ): bool {
		return in_array( $taxonomy, self::getVisibleTaxonomies(), true );
	}

	public static function sanitizeDirectoryPageId( $value ): int {
		$page_id = absint( $value );

		if ( $page_id <= 0 ) {
			return 0;
		}

		return 'page' === get_post_type( $page_id ) ? $page_id : 0;
	}

	public static function sanitizeStyleMode( $value ): string {
		$value = sanitize_key( (string) $value );

		return in_array( $value, array( 'light', 'dark' ), true ) ? $value : 'light';
	}

	public static function sanitizeDefaultView( $value ): string {
		$value = sanitize_key( (string) $value );

		return in_array( $value, array( 'cards', 'text' ), true ) ? $value : 'cards';
	}

	public static function sanitizeBoolean( $value ): bool {
		return ! empty( $value ) && '0' !== (string) $value;
	}

	public static function sanitizeVisibleTaxonomies( $value ): array {
		$value = is_array( $value ) ? $value : array();
		$allowed = array_keys( self::discoveryTaxonomyLabels() );

		return array_values(
			array_intersect(
				$allowed,
				array_map( 'sanitize_key', $value )
			)
		);
	}
}
