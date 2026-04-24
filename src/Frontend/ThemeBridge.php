<?php
namespace ArtistDirectory\Frontend;

use ArtistDirectory\Contracts\Service;
use ArtistDirectory\Infrastructure\PluginContext;
use DirectoryCore\Integration\CoreApi;
use Twig\Markup;
use WP_Query;
use WP_Post_Type;

class ThemeBridge implements Service {
	private PluginContext $context;

	public function __construct( PluginContext $context ) {
		$this->context = $context;
	}

	public function register(): void {
		add_action( 'template_redirect', array( $this, 'renderNativeTemplates' ), 0 );
		add_filter( 'timber/loader/loader', array( $this, 'registerTwigPath' ) );
		add_filter( 'fritz/post/data', array( $this, 'injectThemeData' ), 20, 3 );
	}

	public function renderNativeTemplates(): void {
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}

		if ( $this->isArtistArchiveRequest() ) {
			include $this->context->templatePath( 'archive-mw_artist.php' );
			exit;
		}

		if ( is_singular( 'mw_artist' ) ) {
			$artist_id = (int) get_queried_object_id();

			if ( ! $this->canRenderArtistProfile( $artist_id ) ) {
				$this->renderNotFound();
			}

			include $this->context->templatePath( 'single-mw_artist.php' );
			exit;
		}
	}

	public function registerTwigPath( $loader ) {
		$loader->addPath( $this->context->pluginDir() . 'templates/twig', 'artist-directory' );

		return $loader;
	}

	public function injectThemeData( $data, $post_id, $template ): array {
		$data = is_array( $data ) ? $data : array();

		if ( $this->isArtistArchiveRequest() ) {
			return array_merge( $data, $this->buildArchiveData() );
		}

		if ( is_singular( 'mw_artist' ) ) {
			return array_merge( $data, $this->buildSingleData( (int) get_queried_object_id() ) );
		}

		return $data;
	}

	private function buildArchiveData(): array {
		global $wp_query;

		$selected_media = $this->getSelectedMedia();
		$current_view   = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'cards';
		$current_view   = in_array( $current_view, array( 'cards', 'text' ), true ) ? $current_view : 'cards';
		$media_terms    = get_terms(
			array(
				'taxonomy'   => 'mw_media',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
				)
			);
		$archive_url    = (string) get_post_type_archive_link( 'mw_artist' );
		$toggle_params  = array();
		if ( ! empty( $selected_media ) ) {
			$toggle_params['media'] = $selected_media;
		}
		$cards_url = add_query_arg( array_merge( $toggle_params, array( 'view' => 'cards' ) ), $archive_url );
		$text_url  = add_query_arg( array_merge( $toggle_params, array( 'view' => 'text' ) ), $archive_url );
		$artists   = array();

		foreach ( $wp_query->posts as $artist_post ) {
			$artist_id   = (int) $artist_post->ID;
			$media_names = wp_get_post_terms( $artist_id, 'mw_media', array( 'fields' => 'names' ) );
			$artists[]   = array(
				'id'         => $artist_id,
				'name'       => CoreApi::getArtistDisplayName( $artist_id ),
				'sort_name'  => CoreApi::getArtistSortName( $artist_id ),
				'initial'    => CoreApi::getArtistSortInitial( $artist_id ),
				'media'      => is_array( $media_names ) ? $media_names : array(),
				'can_view'   => CoreApi::isArtistPubliclyViewable( $artist_id ),
				'url'        => get_permalink( $artist_id ),
				'image_html' => get_the_post_thumbnail( $artist_id, 'large' ),
			);
		}

		usort(
			$artists,
			static function ( array $a, array $b ): int {
				$sort = strcasecmp( $a['sort_name'], $b['sort_name'] );
				return 0 !== $sort ? $sort : strcasecmp( $a['name'], $b['name'] );
			}
		);

		$artist_groups = array();
		foreach ( $artists as $artist ) {
			$artist_groups[ $artist['initial'] ][] = $artist;
		}
		ksort( $artist_groups );

		ob_start();
		?>
		<section class="artist-directory__filters">
			<div class="artist-directory__inner">
				<form method="get" class="artist-directory__filter-form">
					<input type="hidden" name="view" value="<?php echo esc_attr( $current_view ); ?>">
					<div class="artist-directory__toolbar">
						<div class="artist-directory__view-toggle" aria-label="<?php esc_attr_e( 'Directory view', 'artist-directory' ); ?>">
							<a class="<?php echo 'cards' === $current_view ? 'is-active' : ''; ?>" href="<?php echo esc_url( $cards_url ); ?>"><?php esc_html_e( 'Cards', 'artist-directory' ); ?></a>
							<a class="<?php echo 'text' === $current_view ? 'is-active' : ''; ?>" href="<?php echo esc_url( $text_url ); ?>"><?php esc_html_e( 'Text', 'artist-directory' ); ?></a>
						</div>
					</div>
					<div class="artist-directory__filter-row">
						<span class="artist-directory__filter-label"><?php esc_html_e( 'Filter by media', 'artist-directory' ); ?></span>
						<div class="artist-directory__chips">
							<?php if ( is_array( $media_terms ) ) : ?>
								<?php foreach ( $media_terms as $media_term ) : ?>
									<label class="artist-directory__chip">
										<input type="checkbox" name="media[]" value="<?php echo esc_attr( $media_term->slug ); ?>" <?php checked( in_array( $media_term->slug, $selected_media, true ), true ); ?>>
										<span><?php echo esc_html( $media_term->name ); ?></span>
									</label>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
					</div>
					<div class="artist-directory__filter-actions">
						<button type="submit" class="artist-directory__button artist-directory__button--solid"><?php esc_html_e( 'Apply Filters', 'artist-directory' ); ?></button>
						<a href="<?php echo esc_url( $archive_url ); ?>" class="artist-directory__button artist-directory__button--ghost"><?php esc_html_e( 'Reset', 'artist-directory' ); ?></a>
					</div>
				</form>
			</div>
		</section>
		<section class="artist-directory__results">
			<div class="artist-directory__inner">
				<?php if ( ! empty( $artists ) ) : ?>
					<?php if ( 'text' === $current_view ) : ?>
						<div class="artist-directory__text-list">
							<?php foreach ( $artist_groups as $initial => $group_artists ) : ?>
								<section class="artist-directory__letter-group">
									<h2><?php echo esc_html( $initial ); ?></h2>
									<ul>
										<?php foreach ( $group_artists as $artist ) : ?>
											<li>
												<?php if ( $artist['can_view'] ) : ?>
													<a href="<?php echo esc_url( $artist['url'] ); ?>"><?php echo esc_html( $artist['name'] ); ?></a>
												<?php else : ?>
													<span><?php echo esc_html( $artist['name'] ); ?></span>
												<?php endif; ?>
											</li>
										<?php endforeach; ?>
									</ul>
								</section>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<div class="artist-directory__grid">
							<?php foreach ( $artists as $artist ) : ?>
								<?php $tag_name = $artist['can_view'] ? 'a' : 'article'; ?>
								<<?php echo esc_html( $tag_name ); ?> class="artist-card <?php echo $artist['can_view'] ? 'artist-card--link' : 'artist-card--static'; ?>" <?php echo $artist['can_view'] ? 'href="' . esc_url( $artist['url'] ) . '"' : ''; ?>>
									<div class="artist-card__media">
										<?php if ( $artist['image_html'] ) : ?>
											<?php echo $artist['image_html']; ?>
										<?php else : ?>
											<div class="artist-card__placeholder"></div>
										<?php endif; ?>
									</div>
									<div class="artist-card__body">
										<h2 class="artist-card__title"><?php echo esc_html( $artist['name'] ); ?></h2>
										<?php if ( ! empty( $artist['media'] ) ) : ?>
											<p class="artist-card__meta"><?php echo esc_html( implode( ' / ', $artist['media'] ) ); ?></p>
										<?php endif; ?>
									</div>
								</<?php echo esc_html( $tag_name ); ?>>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				<?php else : ?>
					<div class="artist-directory__empty">
						<h2><?php esc_html_e( 'No artists match those filters.', 'artist-directory' ); ?></h2>
						<p><?php esc_html_e( 'Try removing one or more filters to broaden the directory results.', 'artist-directory' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php

		return array(
			'mw_template' => 'artist-directory/archive-mw-artist.twig',
			'page'        => array(
				'class' => 'artist-directory artist-directory--archive artist-directory--view-' . $current_view,
			),
			'hero'        => array(
				'kicker'   => __( 'Artist Directory', 'artist-directory' ),
				'title'    => post_type_archive_title( '', false ) ?: __( 'Artists', 'artist-directory' ),
				'abstract' => __( 'A public-facing roster of artists managed through a reusable directory system. Filter by media or switch views to browse visually or alphabetically.', 'artist-directory' ),
			),
			'content'     => new Markup( ob_get_clean(), 'UTF-8' ),
		);
	}

	private function buildSingleData( int $artist_id ): array {
		$media_names         = wp_get_post_terms( $artist_id, 'mw_media', array( 'fields' => 'names' ) );
		$related_venue_ids   = CoreApi::sanitizeIdList( get_post_meta( $artist_id, 'mw_related_venue_ids', true ) );
		$profile_artwork_ids = CoreApi::sanitizeIdList( get_post_meta( $artist_id, 'mw_profile_artwork_ids', true ) );
		$featured_id         = (int) get_post_thumbnail_id( $artist_id );

		if ( $featured_id > 0 ) {
			$profile_artwork_ids = array_values( array_diff( $profile_artwork_ids, array( $featured_id ) ) );
		}

		ob_start();
		?>
		<div class="artist-directory__inner">
			<article class="artist-profile">
				<div class="artist-profile__content">
					<div class="artist-profile__main">
						<?php echo apply_filters( 'the_content', get_post_field( 'post_content', $artist_id ) ); ?>
					</div>
					<aside class="artist-profile__sidebar">
						<?php if ( ! empty( $related_venue_ids ) ) : ?>
							<section class="artist-profile__panel">
								<h2><?php esc_html_e( 'Related Venues', 'artist-directory' ); ?></h2>
								<ul>
									<?php foreach ( $related_venue_ids as $venue_id ) : ?>
										<li><?php echo esc_html( get_the_title( $venue_id ) ); ?></li>
									<?php endforeach; ?>
								</ul>
							</section>
						<?php endif; ?>
					</aside>
				</div>

				<?php if ( ! empty( $profile_artwork_ids ) ) : ?>
					<section class="artist-profile__gallery">
						<h2><?php esc_html_e( 'Artwork Gallery', 'artist-directory' ); ?></h2>
						<div class="artist-profile__gallery-grid">
							<?php foreach ( $profile_artwork_ids as $attachment_id ) : ?>
								<?php $image_html = wp_get_attachment_image( $attachment_id, 'large' ); ?>
								<?php if ( $image_html ) : ?>
									<div class="artist-profile__gallery-item"><?php echo $image_html; ?></div>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>
			</article>
		</div>
		<?php

		return array(
			'mw_template' => 'artist-directory/single-mw-artist.twig',
			'page'        => array(
				'class' => 'artist-directory artist-directory--single',
			),
			'hero'        => array(
				'kicker'   => __( 'Artist Profile', 'artist-directory' ),
				'title'    => get_the_title( $artist_id ),
				'abstract' => ! empty( $media_names ) ? implode( ' / ', $media_names ) : '',
				'image'    => has_post_thumbnail( $artist_id ) ? array(
					'src' => get_the_post_thumbnail_url( $artist_id, 'large' ),
					'alt' => get_post_meta( $featured_id, '_wp_attachment_image_alt', true ),
				) : null,
			),
			'content'     => new Markup( ob_get_clean(), 'UTF-8' ),
		);
	}

	private function getSelectedMedia(): array {
		$raw_value = $_GET['media'] ?? array();

		if ( is_string( $raw_value ) ) {
			$raw_value = explode( ',', $raw_value );
		}

		if ( ! is_array( $raw_value ) ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map(
					'sanitize_title',
					array_map( 'wp_unslash', $raw_value )
				)
			)
		);
	}

	private function isArtistArchiveRequest(): bool {
		if ( is_post_type_archive( 'mw_artist' ) ) {
			return true;
		}

		$post_type = get_query_var( 'post_type' );
		if ( is_array( $post_type ) && in_array( 'mw_artist', $post_type, true ) ) {
			return true;
		}

		if ( 'mw_artist' === $post_type && is_archive() ) {
			return true;
		}

		$object = get_queried_object();
		if ( $object instanceof WP_Post_Type && 'mw_artist' === $object->name ) {
			return true;
		}

		$request_path = wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
		$archive_path = wp_parse_url( (string) get_post_type_archive_link( 'mw_artist' ), PHP_URL_PATH );

		return ! empty( $request_path ) && ! empty( $archive_path ) && trailingslashit( $request_path ) === trailingslashit( $archive_path );
	}

	private function canRenderArtistProfile( int $artist_id ): bool {
		if ( $artist_id <= 0 ) {
			return false;
		}

		if ( current_user_can( 'edit_post', $artist_id ) ) {
			return true;
		}

		return CoreApi::isArtistPubliclyViewable( $artist_id );
	}

	private function renderNotFound(): void {
		global $wp_query;

		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		wp_die(
			wp_kses_post(
				sprintf(
					'<p>%1$s</p><p><a href="%2$s">%3$s</a></p>',
					esc_html__( 'That artist profile is not publicly available.', 'artist-directory' ),
					esc_url( (string) get_post_type_archive_link( 'mw_artist' ) ),
					esc_html__( 'Return to the artist directory', 'artist-directory' )
				)
			),
			esc_html__( 'Artist not found', 'artist-directory' ),
			array( 'response' => 404 )
		);
		exit;
	}
}
