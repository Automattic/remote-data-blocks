<?php

/**
 * DatasourceInterface
 *
 * @package remote-data-blocks
 * @since 0.1.0
 */

namespace RemoteDataBlocks\Config;

defined( 'ABSPATH' ) || exit();

/**
 * Interface used to define a Remote Data Blocks Datasource. It defines the
 * properties of a datasource that will be shared by queries against that
 * datasource.
 *
 * If you are a WPVIP customer, datasources are automatically provided by VIP.
 * Only implement this interface if you have additional custom datasources.
 */
interface DatasourceInterface {

	/**
	 * Constructor for the Datasource.
	 *
	 * @param array $config The configuration object for this datasource.
	 */
	public function __construct( array $config );

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
	 * Get a unique identifier for this datasource.
	 *
	 * This method should return a globally unique identifier for the datasource's
	 * configuration that can be used to identify the datasource authoritatively.
	 *
	 * @return string Globally unique identifier of the datasource.
	 */
	public function get_uid(): string;

	/**
	 * An optional image URL that can represent the datasource in the block editor
	 * (e.g., in modals or in the block inspector).
	 *
	 * @return string|null The image URL or null if not set.
	 */
	public function get_image_url(): ?string;
}
