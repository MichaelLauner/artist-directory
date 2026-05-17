<?php
namespace ArtistDirectory\Settings;

class DirectorySettings {
	public const OPTION_DIRECTORY_PAGE_ID = 'artist_directory_page_id';
	public const OPTION_STYLE_MODE = 'artist_directory_style_mode';
	public const OPTION_DEFAULT_VIEW = 'artist_directory_default_view';

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
}
