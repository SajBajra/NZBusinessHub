<?php
/**
 * Outputs start of HTML document for custom frontend (no BlockStrap).
 * Use with custom-frontend-doc-end.php.
 *
 * @package Directory
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'custom-frontend' ); ?>>
<?php wp_body_open(); ?>
