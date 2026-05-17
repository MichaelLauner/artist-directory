<?php
defined( 'ABSPATH' ) || exit;

use DirectoryCore\Integration\CoreApi;
use ArtistDirectory\Settings\DirectorySettings;

global $wp_query;

$selected_media = $_GET['media'] ?? array();
if ( is_string( $selected_media ) ) {
	$selected_media = explode( ',', $selected_media );
}
$selected_media = is_array( $selected_media ) ? array_values( array_filter( array_map( 'sanitize_title', array_map( 'wp_unslash', $selected_media ) ) ) ) : array();
$current_view   = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : DirectorySettings::getDefaultView();
$current_view   = in_array( $current_view, array( 'cards', 'text' ), true ) ? $current_view : DirectorySettings::getDefaultView();
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
$reset_url = add_query_arg( array( 'view' => $current_view ), $archive_url );
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

get_header();
?>
<main class="artist-directory artist-directory--archive <?php echo esc_attr( DirectorySettings::getThemeClass() ); ?> <?php echo esc_attr( DirectorySettings::getCardImageClass() ); ?> artist-directory--view-<?php echo esc_attr( $current_view ); ?>">
	<section class="artist-directory__hero">
		<div class="artist-directory__inner">
			<p class="artist-directory__eyebrow"><?php esc_html_e( 'Artist Directory', 'artist-directory' ); ?></p>
			<h1 class="artist-directory__title"><?php post_type_archive_title(); ?></h1>
			<p class="artist-directory__intro"><?php esc_html_e( 'A public-facing roster of artists managed through a reusable directory system. Filter by media or switch views to browse visually or alphabetically.', 'artist-directory' ); ?></p>
		</div>
	</section>

	<section class="artist-directory__filters">
		<div class="artist-directory__inner">
			<form method="get" class="artist-directory__filter-form">
				<input type="hidden" name="view" value="<?php echo esc_attr( $current_view ); ?>">
				<div class="artist-directory__toolbar">
					<div class="artist-directory__filter-controls">
						<span class="artist-directory__filter-label"><?php esc_html_e( 'Filters', 'artist-directory' ); ?></span>
						<details class="artist-directory__filter-menu">
							<summary>
								<span><?php esc_html_e( 'Media', 'artist-directory' ); ?></span>
								<?php if ( ! empty( $selected_media ) ) : ?>
									<small><?php echo esc_html( count( $selected_media ) ); ?></small>
								<?php endif; ?>
							</summary>
							<div class="artist-directory__filter-popover">
								<?php if ( is_array( $media_terms ) ) : ?>
									<?php foreach ( $media_terms as $media_term ) : ?>
										<label class="artist-directory__filter-option">
											<input type="checkbox" name="media[]" value="<?php echo esc_attr( $media_term->slug ); ?>" <?php checked( in_array( $media_term->slug, $selected_media, true ), true ); ?>>
											<span><?php echo esc_html( $media_term->name ); ?></span>
										</label>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
						</details>
						<?php if ( ! empty( $selected_media ) && is_array( $media_terms ) ) : ?>
							<div class="artist-directory__active-filters" aria-label="<?php esc_attr_e( 'Active media filters', 'artist-directory' ); ?>">
								<?php foreach ( $media_terms as $media_term ) : ?>
									<?php if ( ! in_array( $media_term->slug, $selected_media, true ) ) : ?>
										<?php continue; ?>
									<?php endif; ?>
									<?php $remaining_media = array_values( array_diff( $selected_media, array( $media_term->slug ) ) ); ?>
									<?php $remove_url = empty( $remaining_media ) ? add_query_arg( array( 'view' => $current_view ), $archive_url ) : add_query_arg( array( 'view' => $current_view, 'media' => $remaining_media ), $archive_url ); ?>
									<a class="artist-directory__active-chip" href="<?php echo esc_url( $remove_url ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Remove %s filter', 'artist-directory' ), $media_term->name ) ); ?>">
										<span><?php echo esc_html( $media_term->name ); ?></span>
										<span aria-hidden="true">x</span>
									</a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
					<div class="artist-directory__toolbar-actions">
						<div class="artist-directory__view-toggle" aria-label="<?php esc_attr_e( 'Directory view', 'artist-directory' ); ?>">
							<a class="<?php echo 'cards' === $current_view ? 'is-active' : ''; ?>" href="<?php echo esc_url( $cards_url ); ?>"><?php esc_html_e( 'Cards', 'artist-directory' ); ?></a>
							<a class="<?php echo 'text' === $current_view ? 'is-active' : ''; ?>" href="<?php echo esc_url( $text_url ); ?>"><?php esc_html_e( 'Text', 'artist-directory' ); ?></a>
						</div>
						<?php if ( ! empty( $selected_media ) ) : ?>
							<a href="<?php echo esc_url( $reset_url ); ?>" class="artist-directory__button artist-directory__button--ghost"><?php esc_html_e( 'Reset', 'artist-directory' ); ?></a>
						<?php endif; ?>
					</div>
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
										<li class="artist-directory__text-item">
											<?php if ( $artist['can_view'] ) : ?>
												<a class="artist-directory__text-link" href="<?php echo esc_url( $artist['url'] ); ?>"><?php echo esc_html( $artist['name'] ); ?></a>
											<?php else : ?>
												<span class="artist-directory__text-link" tabindex="0"><?php echo esc_html( $artist['name'] ); ?></span>
											<?php endif; ?>
											<div class="artist-directory__text-preview" aria-hidden="true">
												<div class="artist-directory__text-preview-media">
													<?php if ( $artist['image_html'] ) : ?>
														<?php echo $artist['image_html']; ?>
													<?php else : ?>
														<div class="artist-card__placeholder"></div>
													<?php endif; ?>
												</div>
												<div class="artist-directory__text-preview-body">
													<strong><?php echo esc_html( $artist['name'] ); ?></strong>
													<?php if ( ! empty( $artist['media'] ) ) : ?>
														<span><?php echo esc_html( implode( ' / ', $artist['media'] ) ); ?></span>
													<?php endif; ?>
												</div>
											</div>
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
</main>
<?php
get_footer();
