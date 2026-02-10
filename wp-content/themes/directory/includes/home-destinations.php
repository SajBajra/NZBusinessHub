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
			),
			array(
				'name'    => 'Wellington',
				'image'   => 'https://images.pexels.com/photos/6127335/pexels-photo-6127335.jpeg',
				'enabled' => 1,
			),
			array(
				'name'    => 'Christchurch',
				'image'   => 'https://images.pexels.com/photos/2098079/pexels-photo-2098079.jpeg',
				'enabled' => 1,
			),
			array(
				'name'    => 'Queenstown',
				'image'   => 'https://images.pexels.com/photos/730981/pexels-photo-730981.jpeg',
				'enabled' => 1,
			),
			array(
				'name'    => 'Rotorua',
				'image'   => 'https://images.pexels.com/photos/459203/pexels-photo-459203.jpeg',
				'enabled' => 1,
			),
			array(
				'name'    => 'Taupō',
				'image'   => 'https://images.pexels.com/photos/1624496/pexels-photo-1624496.jpeg',
				'enabled' => 1,
			),
			array(
				'name'    => 'Dunedin',
				'image'   => 'https://images.pexels.com/photos/414612/pexels-photo-414612.jpeg',
				'enabled' => 1,
			),
			array(
				'name'    => 'Bay of Islands',
				'image'   => 'https://images.pexels.com/photos/189296/pexels-photo-189296.jpeg',
				'enabled' => 1,
			),
			array(
				'name'    => 'Napier',
				'image'   => 'https://images.pexels.com/photos/208738/pexels-photo-208738.jpeg',
				'enabled' => 1,
			),
			array(
				'name'    => 'Nelson',
				'image'   => 'https://images.pexels.com/photos/460680/pexels-photo-460680.jpeg',
				'enabled' => 1,
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
			$out['destinations'][] = array(
				'name'    => $name,
				'image'   => $image,
				'enabled' => $enabled,
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
			<div class="fp__destinations-grid">
				<?php foreach ( $active as $dest ) : ?>
					<article class="fp__destination-card">
						<div class="fp__destination-img" <?php if ( ! empty( $dest['image'] ) ) : ?>style="background-image:url('<?php echo esc_url( $dest['image'] ); ?>');"<?php endif; ?>></div>
						<div class="fp__destination-label">
							<span class="fp__destination-name"><?php echo esc_html( $dest['name'] ); ?></span>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
}

