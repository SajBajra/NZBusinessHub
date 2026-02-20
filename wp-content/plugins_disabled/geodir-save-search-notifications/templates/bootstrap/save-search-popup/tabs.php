<?php
/**
 * Save Search Popup Tabs
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/bootstrap/save-search-popup/tabs.php.
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

global $aui_bs5;

$bs_prefix = $aui_bs5 ? 'bs-' : '';
?>
<ul class="nav nav-tabs nav-justified m-0" id="geodir_save_search_tab" role="tablist">
	<li class="nav-item m-0" role="presentation">
		<button class="w-100 nav-link active text-dark" id="geodir-save-search-pt1" data-<?php echo $bs_prefix; ?>toggle="tab" data-<?php echo $bs_prefix; ?>target="#geodir-save-search-tc1" type="button" role="tab" aria-controls="geodir-save-search-tc1" aria-selected="true"><i class="fas fa-floppy-disk <?php echo ( $aui_bs5 ? 'me-1' : 'mr-1' ); ?>"></i><?php _e( 'Save', 'geodir-save-search' ); ?></button>
	</li>
	<li class="nav-item m-0" role="presentation">
		<button class="nav-link w-100 text-dark" id="geodir-save-search-pt2" data-<?php echo $bs_prefix; ?>toggle="tab" data-<?php echo $bs_prefix; ?>target="#geodir-save-search-tc2" type="button" role="tab" aria-controls="geodir-save-search-tc2" aria-selected="false"><i class="fas fa-list <?php echo ( $aui_bs5 ? 'me-1' : 'mr-1' ); ?>"></i><?php _e( 'Your Saved List', 'geodir-save-search' ); ?><span class="badge <?php echo ( $aui_bs5 ? 'rounded-pill bg-info ms-2' : 'badge-pill badge-info ml-2' ); ?> geodir-save-search-count"><?php echo (int) $count; ?></span></button>
	</li>
</ul>
<div class="tab-content pt-3" id="geodir_save_search_tabC">
	<div class="tab-pane fade show active" id="geodir-save-search-tc1" role="tabpanel" aria-labelledby="geodir-save-search-pt1"><?php echo $input_content; ?></div>
	<div class="tab-pane fade" id="geodir-save-search-tc2" role="tabpanel" aria-labelledby="geodir-save-search-pt2"><?php echo $list_content; ?></div>
</div>
