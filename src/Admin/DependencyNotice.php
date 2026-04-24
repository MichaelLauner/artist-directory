<?php
namespace ArtistDirectory\Admin;

use ArtistDirectory\Contracts\Service;
use ArtistDirectory\Infrastructure\PluginContext;

class DependencyNotice implements Service {
	private PluginContext $context;

	public function __construct( PluginContext $context ) {
		$this->context = $context;
	}

	public function register(): void {
		add_action( 'admin_notices', array( $this, 'renderNotice' ) );
	}

	public function renderNotice(): void {
		if ( class_exists( '\DirectoryCore\Integration\CoreApi' ) ) {
			return;
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'Artist Directory requires the Directory Core plugin to be active.', $this->context->textDomain() ); ?></p>
		</div>
		<?php
	}
}
