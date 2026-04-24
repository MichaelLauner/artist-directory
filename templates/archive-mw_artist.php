<?php
defined( 'ABSPATH' ) || exit;

use DirectoryCore\Integration\CoreApi;

$selected_media = $_GET['media'] ?? array();
if ( is_string( $selected_media ) ) {
	$selected_media = explode( ',', $selected_media );
}
$selected_media = is_array( $selected_media ) ? array_values( array_filter( array_map( 'sanitize_title', array_map( 'wp_unslash', $selected_media ) ) ) ) : array();
$media_terms    = get_terms(
	array(
		'taxonomy'   => 'mw_media',
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	)
);

get_header();
?>
<main class="artist-directory artist-directory--archive">
	<section class="artist-directory__hero">
		<div class="artist-directory__inner">
			<p class="artist-directory__eyebrow"><?php esc_html_e( 'Artist Directory', 'artist-directory' ); ?></p>
			<h1 class="artist-directory__title"><?php post_type_archive_title(); ?></h1>
			<p class="artist-directory__intro"><?php esc_html_e( 'A public-facing roster of artists managed through a reusable directory system. Filter by media to narrow the list.', 'artist-directory' ); ?></p>
		</div>
	</section>

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
			<?php if ( have_posts() ) : ?>
				<div class="artist-directory__grid">
					<?php while ( have_posts() ) : ?>
						<?php the_post(); ?>
						<?php
						$artist_id   = get_the_ID();
						$media_names = wp_get_post_terms( $artist_id, 'mw_media', array( 'fields' => 'names' ) );
						$can_view    = CoreApi::isArtistPubliclyViewable( $artist_id );
						?>
						<article <?php post_class( 'artist-card' ); ?>>
							<div class="artist-card__media">
								<?php if ( has_post_thumbnail() ) : ?>
									<?php the_post_thumbnail( 'large' ); ?>
								<?php else : ?>
									<div class="artist-card__placeholder"></div>
								<?php endif; ?>
							</div>
							<div class="artist-card__body">
								<p class="artist-card__kicker"><?php esc_html_e( 'Directory Listing', 'artist-directory' ); ?></p>
								<h2 class="artist-card__title">
									<?php if ( $can_view ) : ?>
										<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
									<?php else : ?>
										<?php the_title(); ?>
									<?php endif; ?>
								</h2>
								<?php if ( ! empty( $media_names ) ) : ?>
									<p class="artist-card__meta"><?php echo esc_html( implode( ' / ', $media_names ) ); ?></p>
								<?php endif; ?>
								<div class="artist-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 28 ) ); ?></div>
								<div class="artist-card__footer">
									<?php if ( $can_view ) : ?>
										<a href="<?php the_permalink(); ?>" class="artist-directory__button artist-directory__button--solid"><?php esc_html_e( 'View Profile', 'artist-directory' ); ?></a>
									<?php else : ?>
										<span class="artist-card__state"><?php esc_html_e( 'Public listing only', 'artist-directory' ); ?></span>
									<?php endif; ?>
								</div>
							</div>
						</article>
					<?php endwhile; ?>
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
</main>
<?php
get_footer();
