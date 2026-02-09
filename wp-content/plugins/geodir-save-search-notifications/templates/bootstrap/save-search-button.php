<?php
/**
 * Save Search Button
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/bootstrap/save-search-button.php.
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
?>
<div class="geodir-save-search-wrap <?php echo $wrap_class; ?>">
<?php 
echo aui()->button(
	array(
		'type' => 'button',
		'content' => $instance['btn_text'],
		'class' => 'geodir-save-search-btn ' . $button_class,
		'icon' => $instance['btn_icon'],
		'onclick' => $onclick
	)
);
?>
</div>
