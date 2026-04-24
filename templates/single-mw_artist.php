<?php
defined( 'ABSPATH' ) || exit;

use DirectoryCore\Integration\CoreApi;

get_header();

the_post();

$artist_id          = get_the_ID();
$media_names        = wp_get_post_terms( $artist_id, 'mw_media', array( 'fields' => 'names' ) );
$related_venue_ids  = CoreApi::sanitizeIdList( get_post_meta( $artist_id, 'mw_related_venue_ids', true ) );
$profile_artwork_ids = CoreApi::sanitizeIdList( get_post_meta( $artist_id, 'mw_profile_artwork_ids', true ) );
$featured_id        = (int) get_post_thumbnail_id( $artist_id );

if ( $featured_id > 0 ) {
	$profile_artwork_ids = array_values( array_diff( $profile_artwork_ids, array( $featured_id ) ) );
}
?>
<main class="artist-directory artist-directory--single">
	<div class="artist-directory__inner">
		<nav class="artist-directory__breadcrumbs">
			<a href="<?php echo esc_url( get_post_type_archive_link( 'mw_artist' ) ); ?>"><?php esc_html_e( 'Artist Directory', 'artist-directory' ); ?></a>
		</nav>

		<article <?php post_class( 'artist-profile' ); ?>>
			<header class="artist-profile__header">
				<div class="artist-profile__title-wrap">
					<p class="artist-directory__eyebrow"><?php esc_html_e( 'Artist Profile', 'artist-directory' ); ?></p>
					<h1 class="artist-directory__title"><?php the_title(); ?></h1>
					<?php if ( ! empty( $media_names ) ) : ?>
						<p class="artist-profile__meta"><?php echo esc_html( implode( ' / ', $media_names ) ); ?></p>
					<?php endif; ?>
				</div>
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="artist-profile__lead-media">
						<?php the_post_thumbnail( 'large' ); ?>
					</div>
				<?php endif; ?>
			</header>

			<div class="artist-profile__content">
				<div class="artist-profile__main">
					<?php the_content(); ?>
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
</main>
<?php
get_footer();
