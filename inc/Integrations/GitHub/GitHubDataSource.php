<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\GitHub;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Validation\Types;
use RemoteDataBlocks\Validation\Validator;
use WP_Error;

class GitHubDataSource extends HttpDataSource {
	protected const SERVICE_NAME = REMOTE_DATA_BLOCKS_GITHUB_SERVICE;
	protected const SERVICE_SCHEMA_VERSION = 1;

	public static function create( array $service_config, array $config_overrides = [] ): self|WP_Error {
		$validator = new Validator( self::get_service_config_schema() );
		$validated = $validator->validate( $service_config );

		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		return self::from_array(
			array_merge(
				[
					'display_name' => sprintf( 'GitHub: %s/%s (%s)', $service_config['repo_owner'], $service_config['repo_name'], $service_config['ref'] ),
					'endpoint' => function () use ( $service_config ): string {
						return sprintf(
							'https://api.github.com/repos/%s/%s/git/trees/%s?recursive=1',
							$service_config['repo_owner'],
							$service_config['repo_name'],
							$service_config['ref']
						);
					},
					'request_headers' => [
						'Accept' => 'application/vnd.github+json',
					],
					'service' => REMOTE_DATA_BLOCKS_GITHUB_SERVICE,
					'service_config' => $service_config,
					'slug' => sanitize_title( sprintf( '%s/%s/%s', $service_config['repo_owner'], $service_config['repo_name'], $service_config['ref'] ) ),
				],
				$config_overrides
			)
		);
	}

	private static function get_service_config_schema(): array {
		return Types::object( [
			'repo_owner' => Types::string(),
			'repo_name' => Types::string(),
			'ref' => Types::string(),
		] );
	}
}
