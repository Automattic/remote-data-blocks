<?php declare( strict_types = 1 );

// WordPress script functions the settings page calls, recording what it localizes.

function wp_get_environment_type(): string {
	return 'production';
}

function wp_is_development_mode(): bool {
	return false;
}

function wp_localize_script( string $handle, string $object_name, array $data ): bool {
	$GLOBALS['rdb_test_localized_scripts'][ $handle ] = [
		'object_name' => $object_name,
		'data' => $data,
	];

	return true;
}
