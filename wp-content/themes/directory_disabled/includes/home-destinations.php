<?php
/**
 * Home page "Destinations nearby" section.
 *
 * Shows a configurable set of New Zealand destinations in a simple grid.
 *
 * @package Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const DIRECTORY_HOME_DESTINATIONS_OPTION = 'directory_home_destinations';

/**
 * Get configured destinations with NZ-focused defaults.
 *
 * @return array[]
 */
function directory_get_home_destinations() {
	$defaults = array(
		'destinations' => array(
			array(
				'name'    => 'Auckland',
				'image'   => 'https://images.pexels.com/photos/7846472/pexels-photo-7846472.jpeg',
				'enabled' => 1,
				'slug'    => 'auckland',
			),
			array(
				'name'    => 'Wellington',
				'image'   => 'https://images.pexels.com/photos/6127335/pexels-photo-6127335.jpeg',
				'enabled' => 1,
				'slug'    => 'wellington',
			),
			array(
				'name'    => 'Christchurch',
				'image'   => 'https://images.pexels.com/photos/2098079/pexels-photo-2098079.jpeg',
				'enabled' => 1,
				'slug'    => 'christchurch',
			),
			array(
				'name'    => 'Queenstown',
				'image'   => 'https://images.pexels.com/photos/730981/pexels-photo-730981.jpeg',
				'enabled' => 1,
				'slug'    => 'queenstown',
			),
			array(
				'name'    => 'Rotorua',
				'image'   => 'https://images.pexels.com/photos/459203/pexels-photo-459203.jpeg',
				'enabled' => 1,
				'slug'    => 'rotorua',
			),
			array(
				'name'    => 'Taupō',
				'image'   => 'https://images.pexels.com/photos/1624496/pexels-photo-1624496.jpeg',
				'enabled' => 1,
				'slug'    => 'taupo',
			),
			array(
				'name'    => 'Dunedin',
				'image'   => 'https://images.pexels.com/photos/414612/pexels-photo-414612.jpeg',
				'enabled' => 1,
				'slug'    => 'dunedin',
			),
			array(
				'name'    => 'Bay of Islands',
				'image'   => 'https://images.pexels.com/photos/189296/pexels-photo-189296.jpeg',
				'enabled' => 1,
				'slug'    => 'bay-of-islands',
			),
			array(
				'name'    => 'Napier',
				'image'   => 'https://images.pexels.com/photos/208738/pexels-photo-208738.jpeg',
				'enabled' => 1,
				'slug'    => 'napier',
			),
			array(
				'name'    => 'Nelson',
				'image'   => 'https://images.pexels.com/photos/460680/pexels-photo-460680.jpeg',
				'enabled' => 1,
				'slug'    => 'nelson',
			),
		),
	);

	$stored = get_option( DIRECTORY_HOME_DESTINATIONS_OPTION, array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	$destinations = isset( $stored['destinations'] ) && is_array( $stored['destinations'] ) ? $stored['destinations'] : array();

	// Ensure we have at least the default set, merge by index.
	foreach ( $defaults['destinations'] as $i => $default ) {
		if ( ! isset( $destinations[ $i ] ) || ! is_array( $destinations[ $i ] ) ) {
			$destinations[ $i ] = $default;
		} else {
			$destinations[ $i ] = array_merge( $default, $destinations[ $i ] );
		}

		// Ensure each destination has a slug – default to sanitized name.
		if ( empty( $destinations[ $i ]['slug'] ) ) {
			$destinations[ $i ]['slug'] = sanitize_title( $destinations[ $i ]['name'] );
		}
	}

	return array(
		'destinations' => $destinations,
	);
}

/**
 * Register the destinations option.
 */
function directory_home_destinations_register_setting() {
	register_setting(
		'directory_home_destinations_group',
		DIRECTORY_HOME_DESTINATIONS_OPTION,
		'directory_home_destinations_sanitize'
	);
}
add_action( 'admin_init', 'directory_home_destinations_register_setting' );

/**
 * Sanitize destinations settings.
 *
 * @param array $input Raw input.
 * @return array
 */
function directory_home_destinations_sanitize( $input ) {
	$out = array( 'destinations' => array() );

	if ( isset( $input['destinations'] ) && is_array( $input['destinations'] ) ) {
		foreach ( $input['destinations'] as $row ) {
			$name    = isset( $row['name'] ) ? sanitize_text_field( $row['name'] ) : '';
			$image   = isset( $row['image'] ) ? esc_url_raw( $row['image'] ) : '';
			$enabled = ! empty( $row['enabled'] ) ? 1 : 0;
			$slug    = isset( $row['slug'] ) && $row['slug'] !== '' ? sanitize_title( $row['slug'] ) : sanitize_title( $name );
			$out['destinations'][] = array(
				'name'    => $name,
				'image'   => $image,
				'enabled' => $enabled,
				'slug'    => $slug,
			);
		}
	}

	return $out;
}

/**
 * Add admin page to configure destinations.
 */
function directory_home_destinations_admin_menu() {
	add_theme_page(
		__( 'Home Destinations', 'directory' ),
		__( 'Home Destinations', 'directory' ),
		'manage_options',
		'directory-home-destinations',
		'directory_home_destinations_admin_page'
	);
}
add_action( 'admin_menu', 'directory_home_destinations_admin_menu' );

/**
 * Render admin page markup.
 */
function directory_home_destinations_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$data         = directory_get_home_destinations();
	$destinations = $data['destinations'];
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Home Destinations', 'directory' ); ?></h1>
		<p><?php esc_html_e( 'Control the static "Destinations nearby" grid on the home page. These should be well-known destinations within New Zealand.', 'directory' ); ?></p>

		<form action="options.php" method="post">
			<?php
			settings_fields( 'directory_home_destinations_group' );
			?>
			<table class="form-table" role="presentation">
				<thead>
				<tr>
					<th><?php esc_html_e( 'Show', 'directory' ); ?></th>
					<th><?php esc_html_e( 'Destination name', 'directory' ); ?></th>
					<th><?php esc_html_e( 'Slug / URL segment', 'directory' ); ?></th>
					<th><?php esc_html_e( 'Image URL', 'directory' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ( $destinations as $i => $dest ) : ?>
					<tr>
						<td style="vertical-align: top;">
							<label>
								<input type="checkbox" name="<?php echo esc_attr( DIRECTORY_HOME_DESTINATIONS_OPTION ); ?>[destinations][<?php echo (int) $i; ?>][enabled]" value="1" <?php checked( ! empty( $dest['enabled'] ) ); ?> />
								<?php esc_html_e( 'Enable', 'directory' ); ?>
							</label>
						</td>
						<td style="vertical-align: top;">
							<input type="text" class="regular-text" name="<?php echo esc_attr( DIRECTORY_HOME_DESTINATIONS_OPTION ); ?>[destinations][<?php echo (int) $i; ?>][name]" value="<?php echo esc_attr( $dest['name'] ); ?>" />
						</td>
						<td style="vertical-align: top;">
							<?php
							$slug = isset( $dest['slug'] ) && $dest['slug'] !== '' ? $dest['slug'] : sanitize_title( $dest['name'] );
							?>
							<input type="text" class="regular-text" name="<?php echo esc_attr( DIRECTORY_HOME_DESTINATIONS_OPTION ); ?>[destinations][<?php echo (int) $i; ?>][slug]" value="<?php echo esc_attr( $slug ); ?>" />
							<br /><span class="description"><?php esc_html_e( 'Used for the destination URL, e.g. /auckland.', 'directory' ); ?></span>
						</td>
						<td style="vertical-align: top;">
							<input type="url" class="regular-text" name="<?php echo esc_attr( DIRECTORY_HOME_DESTINATIONS_OPTION ); ?>[destinations][<?php echo (int) $i; ?>][image]" value="<?php echo esc_attr( $dest['image'] ); ?>" />
							<br /><span class="description"><?php esc_html_e( 'Can be an external image URL (e.g. from Unsplash/Pexels).', 'directory' ); ?></span>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Output the "Destinations nearby" section on the front page.
 */
function directory_render_home_destinations() {
	$data         = directory_get_home_destinations();
	$destinations = $data['destinations'];

	$active = array();
	foreach ( $destinations as $dest ) {
		if ( ! empty( $dest['enabled'] ) && ! empty( $dest['name'] ) ) {
			$active[] = $dest;
		}
	}

	if ( empty( $active ) ) {
		return;
	}

	// Limit to 10 destinations visually, even if more are configured.
	$active = array_slice( $active, 0, 10 );
	?>
	<section class="fp__section fp__destinations">
		<div class="fp__wrap">
			<h2 class="fp__section-title fp__destinations-title"><?php esc_html_e( 'Destinations nearby', 'directory' ); ?></h2>
			<div class="fp__destinations-slider" data-destinations-slider>
				<div class="fp__destinations-grid" data-destinations-grid>
				<?php foreach ( $active as $dest ) :
					$name = isset( $dest['name'] ) ? trim( $dest['name'] ) : '';
					if ( $name === '' ) {
						continue;
					}

					// Destination slug used for pretty URLs like /auckland.
					$slug = isset( $dest['slug'] ) && $dest['slug'] !== '' ? $dest['slug'] : sanitize_title( $name );

					$dest_url = home_url( '/' . $slug . '/' );
					if ( function_exists( 'directory_relative_url' ) ) {
						$dest_url = directory_relative_url( $dest_url );
					}
					?>
					<a class="fp__destination-card" href="<?php echo esc_url( $dest_url ); ?>">
						<div class="fp__destination-img" <?php if ( ! empty( $dest['image'] ) ) : ?>style="background-image:url('<?php echo esc_url( $dest['image'] ); ?>');"<?php endif; ?>></div>
						<div class="fp__destination-label">
							<span class="fp__destination-name"><?php echo esc_html( $name ); ?></span>
						</div>
					</a>
				<?php endforeach; ?>
				</div>
				<nav class="fp__destinations-pages" data-destinations-pages aria-label="<?php esc_attr_e( 'Destinations navigation', 'directory' ); ?>"></nav>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Ensure each enabled destination has a corresponding WordPress page using the
 * Destination template and register pretty permalinks for them.
 */
function directory_register_destination_rewrites() {
	$data         = directory_get_home_destinations();
	$destinations = $data['destinations'];

	foreach ( $destinations as $dest ) {
		if ( empty( $dest['enabled'] ) || empty( $dest['name'] ) ) {
			continue;
		}
		$slug = isset( $dest['slug'] ) && $dest['slug'] !== '' ? $dest['slug'] : sanitize_title( $dest['name'] );

		// Create page if it does not already exist.
		$page = get_page_by_path( $slug );
		if ( ! $page ) {
			$page_id = wp_insert_post(
				array(
					'post_title'   => $dest['name'],
					'post_name'    => $slug,
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_content' => '',
				)
			);
			if ( $page_id && ! is_wp_error( $page_id ) ) {
				update_post_meta( $page_id, '_wp_page_template', 'page-destination.php' );
			}
		}
	}

	// Flush rewrite rules once after this code runs the first time.
	if ( get_option( 'directory_destination_rewrite_version' ) !== '1' ) {
		flush_rewrite_rules( false );
		update_option( 'directory_destination_rewrite_version', '1' );
	}
}
add_action( 'init', 'directory_register_destination_rewrites' );


