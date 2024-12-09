<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config\DataSource;

use RemoteDataBlocks\Config\ArraySerializable;
use RemoteDataBlocks\Config\ArraySerializableInterface;
use RemoteDataBlocks\Config\UiDisplayableInterface;
use RemoteDataBlocks\Validation\ConfigSchemas;
use RemoteDataBlocks\WpdbStorage\DataSourceCrud;
use WP_Error;

/**
 * HttpDataSource class
 *
 * Implements the HttpDataSourceInterface to define a generic HTTP data source.
 */
class HttpDataSource extends ArraySerializable implements HttpDataSourceInterface, ArraySerializableInterface, UiDisplayableInterface {
	public function get_display_name(): string {
		return $this->config['display_name'];
	}

	public function get_endpoint(): string {
		return $this->config['endpoint'];
	}

	public function get_request_headers(): array {
		return $this->get_or_call_from_config( 'request_headers' ) ?? [];
	}

	public function get_image_url(): ?string {
		return null;
	}

	public function get_service_config(): array {
		return $this->config['service_config'] ?? [];
	}

	public function get_service_name(): ?string {
		return $this->config['service'] ?? null;
	}

	public function get_slug(): string {
		return $this->config['slug'];
	}

	public static function from_slug( string $slug ): DataSourceInterface|WP_Error {
		$config = DataSourceCrud::get_by_slug( $slug );

		if ( ! $config ) {
			return new WP_Error( 'data_source_not_found', __( 'Data source not found.', 'remote-data-blocks' ), [ 'status' => 404 ] );
		}

		return static::from_array( $config );
	}

	/**
	 * @inheritDoc
	 */
	public function to_ui_display(): array {
		// TODO: Implement remove from children and implement here in standardized way
		return [
			'display_name' => $this->get_display_name(),
			'slug' => $this->get_slug(),
			'service' => $this->get_service_name(),
		];
	}

	/**
	 * @inheritDoc
	 */
	protected static function get_config_schema(): array {
		return ConfigSchemas::get_http_data_source_config_schema();
	}
}
