<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config\DataSource;

/**
 * DataSourceInterface
 *
 * Interface used to define a Remote Data Blocks Data Source. It defines the
 * properties of a data source that will be shared by queries against that
 * data source.
 *
 * If you are a WPVIP customer, data sources are automatically provided by VIP.
 * Only implement this interface if you have additional custom data sources.
 * 
 * @package remote-data-blocks
 * @since 0.1.0
 */
interface DataSourceInterface {
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
	 * Get a human-readable name for this data source.
	 *
	 * This method should return a display name for the data source that can be
	 * used in user interfaces or for identification purposes.
	 *
	 * @return string The display name of the data source.
	 */
	public function get_display_name(): string;

	/**
	 * Get the schema for the data source's configuration.
	 *
	 * This method should return an array that defines the schema for the data source's configuration.
	 *
	 * @return array The schema for the data source's configuration.
	 */
	public static function get_config_schema(): array;

	/**
	 * An optional image URL that can represent the data source in the block editor
	 * (e.g., in modals or in the block inspector).
	 *
	 * @return string|null The image URL or null if not set.
	 */
	public function get_image_url(): ?string;
}
