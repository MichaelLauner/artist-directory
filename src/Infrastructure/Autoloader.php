<?php
namespace ArtistDirectory\Infrastructure;

class Autoloader {
	private string $prefix;
	private string $base_dir;

	public function __construct( string $prefix, string $base_dir ) {
		$this->prefix   = trim( $prefix, '\\' ) . '\\';
		$this->base_dir = trailingslashit( $base_dir );
	}

	public static function register( string $prefix, string $base_dir ): self {
		$loader = new self( $prefix, $base_dir );
		spl_autoload_register( array( $loader, 'autoload' ) );

		return $loader;
	}

	public function autoload( string $class ): void {
		if ( 0 !== strpos( $class, $this->prefix ) ) {
			return;
		}

		$relative_class = substr( $class, strlen( $this->prefix ) );
		$file           = $this->base_dir . str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
