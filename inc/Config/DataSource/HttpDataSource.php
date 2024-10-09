<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config\DataSource;

use RemoteDataBlocks\Config\ArraySerializableInterface;
use RemoteDataBlocks\Config\UiDisplayableInterface;
use RemoteDataBlocks\Sanitization\Sanitizer;
use RemoteDataBlocks\Sanitization\SanitizerInterface;
use RemoteDataBlocks\Validation\Validator;
use RemoteDataBlocks\Validation\ValidatorInterface;
use RemoteDataBlocks\WpdbStorage\DataSourceCrud;
use WP_Error;

/**
 * HttpDataSource class
 *
 * Implements the HttpDataSourceInterface to define a generic HTTP data source.
 */
abstract class HttpDataSource implements DataSourceInterface, HttpDataSourceInterface, ArraySerializableInterface, UiDisplayableInterface {
	protected const SERVICE_NAME           = 'unknown';
	protected const SERVICE_SCHEMA_VERSION = -1;
	protected const SERVICE_SCHEMA         = [];

	final private function __construct( protected array $config ) {}

	abstract public function get_display_name(): string;

	abstract public function get_endpoint(): string;

	/**
	 * @inheritDoc
	 */
	abstract public function get_request_headers(): array;

	public function get_image_url(): ?string {
		return null;
	}

	/**
	 * Get the service name.
	 */
	public function get_service(): ?string {
		return isset( $this->config['service'] ) ? $this->config['service'] : null;
	}

	public function get_slug(): string {
		return $this->config['slug'];
	}

	/**
	 * @inheritDoc
	 */
	final public static function get_config_schema(): array {
		$schema = DataSourceInterface::BASE_SCHEMA;

		if ( isset( static::SERVICE_SCHEMA['properties'] ) ) {
			$schema['properties'] = array_merge( DataSourceInterface::BASE_SCHEMA['properties'], static::SERVICE_SCHEMA['properties'] );
		}

		return $schema;
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
	 * @psalm-suppress ParamNameMismatch reason: we want the clarity provided by the rename here
	 */
	final public static function from_array( array $config, ?ValidatorInterface $validator = null, ?SanitizerInterface $sanitizer = null ): DataSourceInterface|WP_Error {
		$config['service_schema_version'] = static::SERVICE_SCHEMA_VERSION;
		$schema                           = static::get_config_schema();

		$validator = $validator ?? new Validator( $schema );
		$validated = $validator->validate( $config );

		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		$sanitizer = $sanitizer ?? new Sanitizer( $schema );
		$sanitized = $sanitizer->sanitize( $config );

		return new static( $sanitized );
	}

	/**
	 * @inheritDoc
	 */
	public function to_array(): array {
		return $this->config;
	}

	/**
	 * @inheritDoc
	 */
	public function to_ui_display(): array {
		// TODO: Implement remove from children and implement here in standardized way
		return [
			'display_name' => $this->get_display_name(),
			'slug'         => $this->get_slug(),
			'service'      => static::SERVICE_NAME,
		];
	}
}
