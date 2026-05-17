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

		register_setting(
			'artist_directory_settings',
			DirectorySettings::OPTION_STYLE_MODE,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( DirectorySettings::class, 'sanitizeStyleMode' ),
				'default'           => 'light',
			)
		);

		register_setting(
			'artist_directory_settings',
			DirectorySettings::OPTION_DEFAULT_VIEW,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( DirectorySettings::class, 'sanitizeDefaultView' ),
				'default'           => 'cards',
			)
		);

		register_setting(
			'artist_directory_settings',
			DirectorySettings::OPTION_VISIBLE_TAXONOMIES,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( DirectorySettings::class, 'sanitizeVisibleTaxonomies' ),
				'default'           => array_keys( DirectorySettings::discoveryTaxonomyLabels() ),
			)
		);

		register_setting(
			'artist_directory_settings',
			DirectorySettings::OPTION_CROP_CARD_IMAGES,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( DirectorySettings::class, 'sanitizeBoolean' ),
				'default'           => true,
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
		$style_mode       = DirectorySettings::getStyleMode();
		$default_view     = DirectorySettings::getDefaultView();
		$visible_taxonomies = DirectorySettings::getVisibleTaxonomies();
		$crop_card_images = DirectorySettings::shouldCropCardImages();
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
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( DirectorySettings::OPTION_STYLE_MODE ); ?>">
								<?php esc_html_e( 'Directory style', $this->context->textDomain() ); ?>
							</label>
						</th>
						<td>
							<select name="<?php echo esc_attr( DirectorySettings::OPTION_STYLE_MODE ); ?>" id="<?php echo esc_attr( DirectorySettings::OPTION_STYLE_MODE ); ?>">
								<option value="light" <?php selected( $style_mode, 'light' ); ?>><?php esc_html_e( 'Light', $this->context->textDomain() ); ?></option>
								<option value="dark" <?php selected( $style_mode, 'dark' ); ?>><?php esc_html_e( 'Dark', $this->context->textDomain() ); ?></option>
							</select>
							<p class="description">
								<?php esc_html_e( 'Light is the default public style. Dark keeps the original high-contrast directory treatment available.', $this->context->textDomain() ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( DirectorySettings::OPTION_DEFAULT_VIEW ); ?>">
								<?php esc_html_e( 'Default view', $this->context->textDomain() ); ?>
							</label>
						</th>
						<td>
							<select name="<?php echo esc_attr( DirectorySettings::OPTION_DEFAULT_VIEW ); ?>" id="<?php echo esc_attr( DirectorySettings::OPTION_DEFAULT_VIEW ); ?>">
								<option value="cards" <?php selected( $default_view, 'cards' ); ?>><?php esc_html_e( 'Cards', $this->context->textDomain() ); ?></option>
								<option value="text" <?php selected( $default_view, 'text' ); ?>><?php esc_html_e( 'List', $this->context->textDomain() ); ?></option>
							</select>
							<p class="description">
								<?php esc_html_e( 'Choose which view visitors see first when the URL does not include a view parameter.', $this->context->textDomain() ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Card images', $this->context->textDomain() ); ?>
						</th>
						<td>
							<label for="<?php echo esc_attr( DirectorySettings::OPTION_CROP_CARD_IMAGES ); ?>">
								<input type="hidden" name="<?php echo esc_attr( DirectorySettings::OPTION_CROP_CARD_IMAGES ); ?>" value="0">
								<input type="checkbox" name="<?php echo esc_attr( DirectorySettings::OPTION_CROP_CARD_IMAGES ); ?>" id="<?php echo esc_attr( DirectorySettings::OPTION_CROP_CARD_IMAGES ); ?>" value="1" <?php checked( $crop_card_images, true ); ?>>
								<?php esc_html_e( 'Crop images to a consistent card shape', $this->context->textDomain() ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'When disabled, card images keep their original proportions and are not clipped.', $this->context->textDomain() ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Profile taxonomy sections', $this->context->textDomain() ); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><?php esc_html_e( 'Profile taxonomy sections', $this->context->textDomain() ); ?></legend>
								<?php foreach ( DirectorySettings::discoveryTaxonomyLabels() as $taxonomy => $label ) : ?>
									<label>
										<input type="checkbox" name="<?php echo esc_attr( DirectorySettings::OPTION_VISIBLE_TAXONOMIES ); ?>[]" value="<?php echo esc_attr( $taxonomy ); ?>" <?php checked( in_array( $taxonomy, $visible_taxonomies, true ), true ); ?>>
										<?php echo esc_html( $label ); ?>
									</label><br>
								<?php endforeach; ?>
							</fieldset>
							<p class="description">
								<?php esc_html_e( 'Choose which discovery taxonomy sections appear on public artist profile pages when terms are assigned.', $this->context->textDomain() ); ?>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
