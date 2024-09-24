<?php

declare(strict_types = 1);

namespace RemoteDataBlocks\Config\Datasource;

/**
 * DatasourceInterface
 *
 * Interface used to define a Remote Data Blocks Datasource. It defines the
 * properties of a datasource that will be shared by queries against that
 * datasource.
 *
 * If you are a WPVIP customer, datasources are automatically provided by VIP.
 * Only implement this interface if you have additional custom datasources.
 * 
 * @package remote-data-blocks
 * @since 0.1.0
 */
interface DatasourceInterface {
	public const BASE_SCHEMA = [
		'type'       => 'object',
		'properties' => [
			'__metadata'             => [
				'type'     => 'object',
				'required' => false,
			],
			'service'                => [ 'type' => 'string' ],
			'service_schema_version' => [ 'type' => 'integer' ],
			'slug'                   => [
				'type'    => 'string',
				'pattern' => '/^[a-z0-9-]+$/',
			],
			'uuid'                   => [
				'type'     => 'string',
				'callback' => 'wp_is_uuid',
				'required' => false,
			],
		],
	];

	/**
	 * Get a human-readable name for this datasource.
	 *
	 * This method should return a display name for the datasource that can be
	 * used in user interfaces or for identification purposes.
	 *
	 * @return string The display name of the datasource.
	 */
	public function get_display_name(): string;

	/**
	 * Get the schema for the datasource's configuration.
	 *
	 * This method should return an array that defines the schema for the datasource's configuration.
	 *
	 * @return array The schema for the datasource's configuration.
	 */
	public static function get_config_schema(): array;

	/**
	 * An optional image URL that can represent the datasource in the block editor
	 * (e.g., in modals or in the block inspector).
	 *
	 * @return string|null The image URL or null if not set.
	 */
	public function get_image_url(): ?string;
}
