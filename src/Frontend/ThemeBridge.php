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
		$media_terms    = get_terms(
			array(
				'taxonomy'   => 'mw_media',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		ob_start();
		?>
		<section class="artist-directory__filters">
			<div class="artist-directory__inner">
				<form method="get" class="artist-directory__filter-form">
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
						<a href="<?php echo esc_url( get_post_type_archive_link( 'mw_artist' ) ); ?>" class="artist-directory__button artist-directory__button--ghost"><?php esc_html_e( 'Reset', 'artist-directory' ); ?></a>
					</div>
				</form>
			</div>
		</section>
		<section class="artist-directory__results">
			<div class="artist-directory__inner">
				<?php if ( ! empty( $wp_query->posts ) ) : ?>
					<div class="artist-directory__grid">
						<?php foreach ( $wp_query->posts as $artist_post ) : ?>
							<?php
							$artist_id   = (int) $artist_post->ID;
							$media_names = wp_get_post_terms( $artist_id, 'mw_media', array( 'fields' => 'names' ) );
							$can_view    = CoreApi::isArtistPubliclyViewable( $artist_id );
							?>
							<article class="artist-card">
								<div class="artist-card__media">
									<?php if ( has_post_thumbnail( $artist_id ) ) : ?>
										<?php echo get_the_post_thumbnail( $artist_id, 'large' ); ?>
									<?php else : ?>
										<div class="artist-card__placeholder"></div>
									<?php endif; ?>
								</div>
								<div class="artist-card__body">
									<p class="artist-card__kicker"><?php esc_html_e( 'Directory Listing', 'artist-directory' ); ?></p>
									<h2 class="artist-card__title">
										<?php if ( $can_view ) : ?>
											<a href="<?php echo esc_url( get_permalink( $artist_id ) ); ?>"><?php echo esc_html( get_the_title( $artist_id ) ); ?></a>
										<?php else : ?>
											<?php echo esc_html( get_the_title( $artist_id ) ); ?>
										<?php endif; ?>
									</h2>
									<?php if ( ! empty( $media_names ) ) : ?>
										<p class="artist-card__meta"><?php echo esc_html( implode( ' / ', $media_names ) ); ?></p>
									<?php endif; ?>
									<div class="artist-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $artist_id ), 28 ) ); ?></div>
									<div class="artist-card__footer">
										<?php if ( $can_view ) : ?>
											<a href="<?php echo esc_url( get_permalink( $artist_id ) ); ?>" class="artist-directory__button artist-directory__button--solid"><?php esc_html_e( 'View Profile', 'artist-directory' ); ?></a>
										<?php else : ?>
											<span class="artist-card__state"><?php esc_html_e( 'Public listing only', 'artist-directory' ); ?></span>
										<?php endif; ?>
									</div>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
					<div class="artist-directory__pagination">
						<?php
						echo wp_kses_post(
							paginate_links(
								array(
									'prev_text' => __( 'Previous', 'artist-directory' ),
									'next_text' => __( 'Next', 'artist-directory' ),
								)
							)
						);
						?>
					</div>
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
				'class' => 'artist-directory artist-directory--archive',
			),
			'hero'        => array(
				'kicker'   => __( 'Artist Directory', 'artist-directory' ),
				'title'    => post_type_archive_title( '', false ) ?: __( 'Artists', 'artist-directory' ),
				'abstract' => __( 'A public-facing roster of artists managed through a reusable directory system. Filter by media to narrow the list.', 'artist-directory' ),
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
