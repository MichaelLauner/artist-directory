<?php
namespace ArtistDirectory;

use ArtistDirectory\Admin\DependencyNotice;
use ArtistDirectory\Assets\AssetManager;
use ArtistDirectory\Contracts\Service;
use ArtistDirectory\Frontend\QueryManager;
use ArtistDirectory\Frontend\TemplateLoader;
use ArtistDirectory\Frontend\ThemeBridge;
use ArtistDirectory\Infrastructure\PluginContext;

class Plugin {
	private static ?self $instance = null;

	private PluginContext $context;

	/** @var array<string, object> */
	private array $services = array();

	private function __construct( PluginContext $context ) {
		$this->context = $context;
		$this->buildServices();
	}

	public static function boot( PluginContext $context ): self {
		if ( null === self::$instance ) {
			self::$instance = new self( $context );
			self::$instance->register();
		}

		return self::$instance;
	}

	private function buildServices(): void {
		$this->services = array(
			DependencyNotice::class => new DependencyNotice( $this->context ),
			QueryManager::class     => new QueryManager( $this->context ),
			TemplateLoader::class   => new TemplateLoader( $this->context ),
			ThemeBridge::class      => new ThemeBridge( $this->context ),
			AssetManager::class     => new AssetManager( $this->context ),
		);
	}

	private function register(): void {
		add_action( 'plugins_loaded', array( $this, 'loadTextdomain' ), 5 );
		add_action( 'plugins_loaded', array( $this, 'registerServices' ), 20 );
	}

	public function loadTextdomain(): void {
		load_plugin_textdomain(
			$this->context->textDomain(),
			false,
			dirname( $this->context->pluginBasename() ) . '/languages'
		);
	}

	public function registerServices(): void {
		$this->services[ DependencyNotice::class ]->register();

		if ( ! $this->hasCoreDependency() ) {
			return;
		}

		foreach ( $this->services as $class => $service ) {
			if ( DependencyNotice::class === $class ) {
				continue;
			}

			if ( $service instanceof Service ) {
				$service->register();
			}
		}
	}

	private function hasCoreDependency(): bool {
		return class_exists( '\DirectoryCore\Integration\CoreApi' );
	}
}
