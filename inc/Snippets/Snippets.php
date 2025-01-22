<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Snippets;

use WP_Error;
use RemoteDataBlocks\WpdbStorage\DataSourceCrud;

class Snippets {
	public static function get_snippets( string $uuid ): array|WP_Error {
		$data_source_config = DataSourceCrud::get_config_by_uuid( $uuid );

		if ( is_wp_error( $data_source_config ) ) {
			return $data_source_config;
		}

		$service = $data_source_config['service'];


		switch ( $service ) {
			case 'shopify':
				return self::get_shopify_snippets( $data_source_config );
			default:
				return new WP_Error( 'invalid_service', __( 'Invalid service', 'remote-data-blocks' ) );
		}
	}

	private static function strip_template_comments( string $content ): string {
		// Match PHPDoc blocks that contain @template tags and any preceding blank lines
		return preg_replace(
			'/\n*\/\*\*\s*\n\s*\*\s*@template-.*?\*\/\n*/s',
			"\n\n",
			$content
		);
	}

	private static function get_shopify_snippets( array $data_source_config ): array {
		$raw_snippet = file_get_contents( __DIR__ . '/Templates/Shopify.template' );
		$snippet = strtr( $raw_snippet, [
			'{{DATA_SOURCE_UUID}}' => $data_source_config['uuid'],
		] );
		return [ self::strip_template_comments( $snippet ) ];
	}
}
