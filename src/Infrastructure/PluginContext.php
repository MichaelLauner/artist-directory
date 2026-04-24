<?php
namespace ArtistDirectory\Infrastructure;

class PluginContext {
	private string $plugin_file;
	private string $version;
	private string $text_domain;

	public function __construct( string $plugin_file, string $version, string $text_domain ) {
		$this->plugin_file = $plugin_file;
		$this->version     = $version;
		$this->text_domain = $text_domain;
	}

	public function pluginFile(): string {
		return $this->plugin_file;
	}

	public function pluginDir(): string {
		return plugin_dir_path( $this->plugin_file );
	}

	public function pluginUrl(): string {
		return plugin_dir_url( $this->plugin_file );
	}

	public function version(): string {
		return $this->version;
	}

	public function textDomain(): string {
		return $this->text_domain;
	}

	public function pluginBasename(): string {
		return plugin_basename( $this->plugin_file );
	}

	public function templatePath( string $relative_path ): string {
		return $this->pluginDir() . 'templates/' . ltrim( $relative_path, '/' );
	}

	public function assetUrl( string $relative_path ): string {
		return $this->pluginUrl() . ltrim( $relative_path, '/' );
	}
}
