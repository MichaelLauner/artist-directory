<?php
namespace ArtistDirectory\Settings;

class DirectorySettings {
	public const OPTION_DIRECTORY_PAGE_ID = 'artist_directory_page_id';

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

	public static function sanitizeDirectoryPageId( $value ): int {
		$page_id = absint( $value );

		if ( $page_id <= 0 ) {
			return 0;
		}

		return 'page' === get_post_type( $page_id ) ? $page_id : 0;
	}
}
