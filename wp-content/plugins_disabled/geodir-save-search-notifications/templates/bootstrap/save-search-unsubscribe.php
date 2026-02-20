<?php
/**
 * Save Search Unsubscribe
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/bootstrap/save-search-unsubscribe.php.
 *
 * HOWEVER, on occasion GeoDirectory will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see       https://wpgeodirectory.com/documentation/article/how-tos/customizing-templates/
 * @package   GeoDir_Save_Search
 * @version   1.0
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $subscriber ) ) {
	return;
}
?>
<!doctype html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<title><?php echo esc_html( __( 'Unsubscribe', 'geodir-save-search' ) ); ?></title>
	</head>
	<body style="background-color:#f6f8fa;color:#555;margin:0 10%;padding:0;font-family:arial,sans-serif;line-height:1.5;font-size:1rem;">
		<div style="margin:2.5rem auto;text-align:center;max-width:600px;background-color:#fff;">
			<div style="margin:0;font-size:125%;font-weight:bold;padding:1.25rem 2rem;border-bottom:1px solid #f6f8fa"><?php echo esc_html( __( 'Unsubscribe', 'geodir-save-search' ) ); ?></div>
			<div style="min-height:100px;padding:2rem;">
				<div style="font-size:103%"><?php echo wp_sprintf( __( 'You have been successfully unsubscribed from saved search: <b>%s</b>', 'geodir-save-search' ), $subscriber->search_name ); ?></div>
			</div>
			<div style="margin:0;padding:.75rem 2rem;border-top:1px solid #f6f8fa">
				<a style="text-decoration:none" href="<?php echo esc_url( home_url() ); ?>"><?php echo esc_html( geodir_get_blogname() ); ?></a>
			</div>
		</div>
	</body>
</html>
