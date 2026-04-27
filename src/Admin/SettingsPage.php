<?php
namespace ArtistDirectory\Admin;

use ArtistDirectory\Contracts\Service;
use ArtistDirectory\Infrastructure\PluginContext;
use ArtistDirectory\Settings\DirectorySettings;
use DirectoryCore\Integration\CoreApi;

class SettingsPage implements Service {
	private const PAGE_SLUG = 'artist-directory-settings';

	private PluginContext $context;

	public function __construct( PluginContext $context ) {
		$this->context = $context;
	}

	public function register(): void {
		add_action( 'admin_init', array( $this, 'registerSettings' ) );
		add_action( 'admin_menu', array( $this, 'registerMenuPage' ) );
	}

	public function registerSettings(): void {
		register_setting(
			'artist_directory_settings',
			DirectorySettings::OPTION_DIRECTORY_PAGE_ID,
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( DirectorySettings::class, 'sanitizeDirectoryPageId' ),
				'default'           => 0,
			)
		);
	}

	public function registerMenuPage(): void {
		add_submenu_page(
			CoreApi::adminMenuSlug(),
			__( 'Artist Directory Settings', $this->context->textDomain() ),
			__( 'Artist Directory', $this->context->textDomain() ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'renderSettingsPage' )
		);
	}

	public function renderSettingsPage(): void {
		$selected_page_id = DirectorySettings::getDirectoryPageId();
		$directory_url    = DirectorySettings::getDirectoryUrl();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Artist Directory Settings', $this->context->textDomain() ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'artist_directory_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( DirectorySettings::OPTION_DIRECTORY_PAGE_ID ); ?>">
								<?php esc_html_e( 'Directory page', $this->context->textDomain() ); ?>
							</label>
						</th>
						<td>
							<?php
							wp_dropdown_pages(
								array(
									'name'              => DirectorySettings::OPTION_DIRECTORY_PAGE_ID,
									'id'                => DirectorySettings::OPTION_DIRECTORY_PAGE_ID,
									'selected'          => $selected_page_id,
									'show_option_none'  => __( 'Use the default /artists/ archive', $this->context->textDomain() ),
									'option_none_value' => 0,
								)
							);
							?>
							<p class="description">
								<?php esc_html_e( 'Choose a WordPress page where the public artist directory should render inside the theme page template.', $this->context->textDomain() ); ?>
							</p>
							<?php if ( $directory_url ) : ?>
								<p>
									<a href="<?php echo esc_url( $directory_url ); ?>" target="_blank" rel="noopener noreferrer">
										<?php esc_html_e( 'View current directory page', $this->context->textDomain() ); ?>
									</a>
								</p>
							<?php endif; ?>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
