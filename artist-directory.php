<?php
/**
 * Plugin Name: Artist Directory
 * Plugin URI:  https://mostlywanted.com/
 * Description: Public artist directory templates and filters powered by Directory Core, by Mostly Wanted.
 * Version:     0.1.8
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Requires Plugins: directory-core
 * Author:      Mostly Wanted
 * License:     GPL2+
 * Text Domain: artist-directory
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'ARTIST_DIRECTORY_VERSION', '0.1.8' );
define( 'ARTIST_DIRECTORY_TEXT_DOMAIN', 'artist-directory' );
define( 'ARTIST_DIRECTORY_PLUGIN_FILE', __FILE__ );
define( 'ARTIST_DIRECTORY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once ARTIST_DIRECTORY_PLUGIN_DIR . 'src/Infrastructure/Autoloader.php';

ArtistDirectory\Infrastructure\Autoloader::register( 'ArtistDirectory', ARTIST_DIRECTORY_PLUGIN_DIR . 'src' );

ArtistDirectory\Plugin::boot(
	new ArtistDirectory\Infrastructure\PluginContext(
		ARTIST_DIRECTORY_PLUGIN_FILE,
		ARTIST_DIRECTORY_VERSION,
		ARTIST_DIRECTORY_TEXT_DOMAIN
	)
);
