<?php
require __DIR__ . '/../wp-load.php';

global $wpdb;

$table = defined('GEODIR_CUSTOM_FIELDS_TABLE') ? GEODIR_CUSTOM_FIELDS_TABLE : ($wpdb->prefix . 'geodir_custom_fields');
$post_type = $argv[1] ?? 'gd_place';

$fields = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT htmlvar_name, frontend_title, admin_title, field_type FROM {$table} WHERE post_type = %s ORDER BY sort_order ASC",
		$post_type
	)
);

foreach ($fields as $f) {
	$title = $f->frontend_title ?: $f->admin_title;
	echo "{$f->htmlvar_name}\t{$f->field_type}\t{$title}\n";
}

echo "\n-- CPT table columns --\n";

if (function_exists('geodir_db_cpt_table')) {
	$cpt_table = geodir_db_cpt_table($post_type);
	$cols = $wpdb->get_col("SHOW COLUMNS FROM {$cpt_table}");
	foreach ($cols as $c) {
		echo $c . "\n";
	}
}

