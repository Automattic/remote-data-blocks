<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Mocks;

use RemoteDataBlocks\Config\DataSource\HttpDataSourceInterface;
use RemoteDataBlocks\Config\Query\HttpQueryInterface;
use RemoteDataBlocks\Validation\ValidatorInterface;
use WP_Error;

/**
 * An HTTP query implementing the interface as it existed before cache-key
 * request headers became configurable.
 */
class LegacyHttpQuery implements HttpQueryInterface {
	public function __construct( private HttpDataSourceInterface $data_source ) {}

	public static function from_array( array $config, ?ValidatorInterface $validator ): static|WP_Error {
		return new static( $config['data_source'] );
	}

	public static function preprocess_config( array $config ): array|WP_Error {
		return $config;
	}

	public static function get_config_schema(): array {
		return [];
	}

	public static function migrate_config( array $config ): array|WP_Error {
		return $config;
	}

	public function to_array(): array {
		return [];
	}

	public function execute( array $input_variables ): array|WP_Error {
		return [];
	}

	public function execute_batch( array $array_of_input_variables ): array|WP_Error {
		return [];
	}

	public function get_data_source(): HttpDataSourceInterface {
		return $this->data_source;
	}

	public function get_image_url(): ?string {
		return null;
	}

	public function get_input_schema(): array {
		return [];
	}

	public function get_output_schema(): array {
		return [];
	}

	public function get_pagination_schema(): ?array {
		return null;
	}

	public function get_cache_ttl( array $input_variables ): null|int {
		return null;
	}

	public function get_endpoint( array $input_variables ): string {
		return $this->data_source->get_endpoint();
	}

	public function get_request_method(): string {
		return 'GET';
	}

	public function get_request_headers( array $input_variables ): array|WP_Error {
		return $this->data_source->get_request_headers();
	}

	public function get_request_body( array $input_variables ): array|null {
		return null;
	}

	public function preprocess_response( mixed $response_data, array $request_details ): mixed {
		return $response_data;
	}
}
